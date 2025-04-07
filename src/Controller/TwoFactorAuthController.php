<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 *
 * This file is part of Myddleware.
 *
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\VerificationCodeFormType;
use App\Service\TwoFactorAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TwoFactorAuthController extends AbstractController
{
    private TwoFactorAuthService $twoFactorAuthService;
    private TokenStorageInterface $tokenStorage;
    private RequestStack $requestStack;

    public function __construct(
        TwoFactorAuthService $twoFactorAuthService,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack
    ) {
        $this->twoFactorAuthService = $twoFactorAuthService;
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/verify", name="two_factor_auth_verify")
     */
    public function verify(Request $request): Response
    {
        $session = $request->getSession();
        
        // Check if we have an active session with initial authentication
        if (!$session->has('_security_main')) {
            // Session expired or no initial authentication, redirect to login
            return $this->redirectToRoute('login');
        }

        // Check if the user is already authenticated with 2FA
        if ($session->get('two_factor_auth_complete', false)) {
            return $this->redirectToRoute('regle_panel');
        }

        // Get the user from the token
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return $this->redirectToRoute('login');
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('login');
        }

        // Get or create the 2FA record for this user
        $twoFactorAuth = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);

        // Check if 2FA is enabled for this user
        if (!$twoFactorAuth->isEnabled()) {
            // If 2FA is not enabled, mark as complete and redirect
            $session->set('two_factor_auth_complete', true);
            return $this->redirectToRoute('regle_panel');
        }

        // Check if the user has a remember cookie
        $rememberedAuth = $this->twoFactorAuthService->checkRememberCookie($request);
        if ($rememberedAuth && $rememberedAuth->getUser()->getId() === $user->getId()) {
            // If the user has a valid remember cookie, mark as complete and redirect
            $session->set('two_factor_auth_complete', true);
            return $this->redirectToRoute('regle_panel');
        }

        // Create the verification code form
        $form = $this->createForm(VerificationCodeFormType::class);
        $form->handleRequest($request);

        // Check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $code = $data['code'];
            $rememberDevice = $data['rememberDevice'] ?? false;

            // Verify the code
            if ($this->twoFactorAuthService->verifyCode($twoFactorAuth, $code)) {
                // Code is valid, mark as complete
                $session->set('two_factor_auth_complete', true);

                // Handle remember device option
                if ($rememberDevice) {
                    $this->twoFactorAuthService->setRememberDevice($twoFactorAuth, true);
                    $response = $this->redirectToRoute('regle_panel');
                    $response->headers->setCookie($this->twoFactorAuthService->createRememberCookie($twoFactorAuth));
                    return $response;
                }

                return $this->redirectToRoute('regle_panel');
            } else {
                // Code is invalid
                $this->addFlash('error', 'Invalid verification code. Please try again.');

                // If the user is blocked, show a message
                if ($twoFactorAuth->isBlocked()) {
                    $this->addFlash('error', 'Too many failed attempts. Please try again in 1 minute.');
                }
            }
        } else if (!$form->isSubmitted()) {
            // Send a new verification code when the page is first loaded
            $this->twoFactorAuthService->sendVerificationCode($twoFactorAuth);
        }

        return $this->render('TwoFactorAuth/verify.html.twig', [
            'form' => $form->createView(),
            'twoFactorAuth' => $twoFactorAuth,
        ]);
    }

    /**
     * @Route("/verify/resend", name="two_factor_auth_resend")
     */
    public function resend(Request $request): Response
    {
        // Check if we have an active session with initial authentication
        if (!$request->getSession()->has('_security_main')) {
            // Session expired or no initial authentication, redirect to login
            return $this->redirectToRoute('login');
        }

        // Get the user from the token
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return $this->redirectToRoute('login');
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('login');
        }

        // Get the 2FA record for this user
        $twoFactorAuth = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);

        // Check if the user is blocked
        if ($twoFactorAuth->isBlocked()) {
            $this->addFlash('error', 'Too many failed attempts. Please try again in 1 minute.');
            return $this->redirectToRoute('two_factor_auth_verify');
        }

        // Send a new verification code
        if ($this->twoFactorAuthService->sendVerificationCode($twoFactorAuth)) {
            $this->addFlash('success', 'A new verification code has been sent.');
        } else {
            $this->addFlash('error', 'Failed to send verification code. Please try again.');
        }

        return $this->redirectToRoute('two_factor_auth_verify');
    }

    /**
     * @Route("/verify/switch-method/{method}", name="two_factor_auth_switch_method")
     */
    public function switchMethod(string $method, Request $request): Response
    {
        // Check if we have an active session with initial authentication
        if (!$request->getSession()->has('_security_main')) {
            // Session expired or no initial authentication, redirect to login
            return $this->redirectToRoute('login');
        }

        // Get the user from the token
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return $this->redirectToRoute('login');
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('login');
        }

        // Get the 2FA record for this user
        $twoFactorAuth = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);

        // Check if the method is valid
        if (!in_array($method, ['email', 'sms'])) {
            $this->addFlash('error', 'Invalid verification method.');
            return $this->redirectToRoute('two_factor_auth_verify');
        }

        // If switching to SMS, check if the user has a phone number
        if ($method === 'sms' && !$twoFactorAuth->getPhoneNumber()) {
            $this->addFlash('error', 'You need to set up a phone number in your account settings first.');
            return $this->redirectToRoute('two_factor_auth_verify');
        }

        // Update the preferred method
        $twoFactorAuth->setPreferredMethod($method);
        $this->getDoctrine()->getManager()->flush();

        // Send a new verification code
        if ($this->twoFactorAuthService->sendVerificationCode($twoFactorAuth)) {
            $this->addFlash('success', 'Verification method switched to ' . strtoupper($method) . '. A new code has been sent.');
        } else {
            $this->addFlash('error', 'Failed to send verification code. Please try again.');
        }

        return $this->redirectToRoute('two_factor_auth_verify');
    }
} 