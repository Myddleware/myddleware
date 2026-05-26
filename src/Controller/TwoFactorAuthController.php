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
use App\Service\DebugLogger;
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
    private DebugLogger $debugLogger;

    public function __construct(
        TwoFactorAuthService $twoFactorAuthService,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        DebugLogger $debugLogger
    ) {
        $this->twoFactorAuthService = $twoFactorAuthService;
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
        $this->debugLogger = $debugLogger;
    }

    /**
     * @Route("/verify", name="two_factor_auth_verify")
     */
    public function verify(Request $request): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request]);
        $__debugReturn = null;
        try {
            $session = $request->getSession();

            if (!$session->has('_security_main')) {
                return $__debugReturn = $this->redirectToRoute('login');
            }

            if ($session->get('two_factor_auth_complete', false)) {
                return $__debugReturn = $this->redirectToRoute('regle_panel');
            }

            $token = $this->tokenStorage->getToken();
            if (!$token) {
                return $__debugReturn = $this->redirectToRoute('login');
            }

            $user = $token->getUser();
            if (!$user instanceof User) {
                return $__debugReturn = $this->redirectToRoute('login');
            }

            $twoFactorAuth = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);

            if (!$twoFactorAuth->isEnabled()) {
                $session->set('two_factor_auth_complete', true);
                return $__debugReturn = $this->redirectToRoute('regle_panel');
            }

            $rememberedAuth = $this->twoFactorAuthService->checkRememberCookie($request);
            if ($rememberedAuth && $rememberedAuth->getUser()->getId() === $user->getId()) {
                $session->set('two_factor_auth_complete', true);
                return $__debugReturn = $this->redirectToRoute('regle_panel');
            }

            $form = $this->createForm(VerificationCodeFormType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $code = $data['code'];
                $rememberDevice = $data['rememberDevice'] ?? false;

                if ($this->twoFactorAuthService->verifyCode($twoFactorAuth, $code)) {
                    $session->set('two_factor_auth_complete', true);

                    if ($rememberDevice) {
                        $this->twoFactorAuthService->setRememberDevice($twoFactorAuth, true);
                        $response = $this->redirectToRoute('regle_panel');
                        $response->headers->setCookie($this->twoFactorAuthService->createRememberCookie($twoFactorAuth));
                        return $__debugReturn = $response;
                    }

                    return $__debugReturn = $this->redirectToRoute('regle_panel');
                } else {
                    $this->addFlash('twofa.verify.danger', 'Invalid verification code. Please try again.');

                    if ($twoFactorAuth->isBlocked()) {
                        $this->addFlash('twofa.verify.danger', 'Too many failed attempts. Please try again in 1 minute.');
                    }
                }
            } else if (!$form->isSubmitted()) {
                $this->twoFactorAuthService->sendVerificationCode($twoFactorAuth);
            }

            return $__debugReturn = $this->render('TwoFactorAuth/verify.html.twig', [
                'form' => $form->createView(),
                'twoFactorAuth' => $twoFactorAuth,
            ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/verify/resend", name="two_factor_auth_resend")
     */
    public function resend(Request $request): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request]);
        $__debugReturn = null;
        try {
            if (!$request->getSession()->has('_security_main')) {
                return $__debugReturn = $this->redirectToRoute('login');
            }

            $token = $this->tokenStorage->getToken();
            if (!$token) {
                return $__debugReturn = $this->redirectToRoute('login');
            }

            $user = $token->getUser();
            if (!$user instanceof User) {
                return $__debugReturn = $this->redirectToRoute('login');
            }

            $twoFactorAuth = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);

            if ($twoFactorAuth->isBlocked()) {
                $this->addFlash('twofa.resend.danger', 'Too many failed attempts. Please try again in 1 minute.');
                return $__debugReturn = $this->redirectToRoute('two_factor_auth_verify');
            }

            if ($this->twoFactorAuthService->sendVerificationCode($twoFactorAuth)) {
                $this->addFlash('twofa.resend.success', 'A new verification code has been sent.');
            } else {
                $this->addFlash('twofa.resend.danger', 'Failed to send verification code. Please try again.');
            }

            return $__debugReturn = $this->redirectToRoute('two_factor_auth_verify');
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/verify/switch-method/{method}", name="two_factor_auth_switch_method")
     */
    public function switchMethod(string $method, Request $request): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['method' => $method, 'request' => $request]);
        $__debugReturn = null;
        try {
            if (!$request->getSession()->has('_security_main')) {
                return $__debugReturn = $this->redirectToRoute('login');
            }

            $token = $this->tokenStorage->getToken();
            if (!$token) {
                return $__debugReturn = $this->redirectToRoute('login');
            }

            $user = $token->getUser();
            if (!$user instanceof User) {
                return $__debugReturn = $this->redirectToRoute('login');
            }

            $twoFactorAuth = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);

            if (!in_array($method, ['email', 'sms'])) {
                $this->addFlash('twofa.switchMethod.danger', 'Invalid verification method.');
                return $__debugReturn = $this->redirectToRoute('two_factor_auth_verify');
            }

            if ($method === 'sms' && !$twoFactorAuth->getPhoneNumber()) {
                $this->addFlash('twofa.switchMethod.danger', 'You need to set up a phone number in your account settings first.');
                return $__debugReturn = $this->redirectToRoute('two_factor_auth_verify');
            }

            $twoFactorAuth->setPreferredMethod($method);
            $this->getDoctrine()->getManager()->flush();

            if ($this->twoFactorAuthService->sendVerificationCode($twoFactorAuth)) {
                $this->addFlash('twofa.switchMethod.success', 'Verification method switched to ' . strtoupper($method) . '. A new code has been sent.');
            } else {
                $this->addFlash('twofa.switchMethod.danger', 'Failed to send verification code. Please try again.');
            }

            return $__debugReturn = $this->redirectToRoute('two_factor_auth_verify');
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}
