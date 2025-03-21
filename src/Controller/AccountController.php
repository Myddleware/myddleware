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

use Psr\Log\LoggerInterface;
use App\Manager\ToolsManager;
use App\Form\Type\ProfileFormType;
use App\Form\Type\ResetPasswordType;
use App\Form\Type\TwoFactorAuthFormType;
use App\Service\UserManagerInterface;
use App\Service\AlertBootstrapInterface;
use App\Service\TwoFactorAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Dotenv\Dotenv;


/**
 * @Route("/rule")
 */
class AccountController extends AbstractController
{
    /**
     * @var ToolsManager
     */
    private $toolsManager;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ParameterBagInterface
     */
    private $params;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var string
     */
    private $env;

    /**
     * @var AlertBootstrapInterface
     */
    private $alert;

    /**
     * @var TwoFactorAuthService
     */
    private $twoFactorAuthService;

    public function __construct(
        KernelInterface $kernel,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params,
        TranslatorInterface $translator,
        ToolsManager $toolsManager,
        AlertBootstrapInterface $alert,
        TwoFactorAuthService $twoFactorAuthService
    ) {
        $this->kernel = $kernel;
        $this->env = $kernel->getEnvironment();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->translator = $translator;
        $this->toolsManager = $toolsManager;
        $this->alert = $alert;
        $this->twoFactorAuthService = $twoFactorAuthService;
    }

    /**
     * @Route("/account/locale/{locale}", name="account_locale", options={"expose"=true})
     */
    public function changeLocale(string $locale, Request $request): RedirectResponse
    {
        $request->getSession()->set('_locale', $locale);

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/account", name="my_account")
     */
    public function myAccount(Request $request, UserPasswordEncoderInterface $encoder, UserManagerInterface $userManager): Response
    {
        $user = $this->getUser();
        $em = $this->entityManager;
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);
        $timezone = $user->getTimezone();
        
        // Get or create the 2FA record for this user
        $twoFactorAuth = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);
        $twoFactorAuthForm = $this->createForm(TwoFactorAuthFormType::class, $twoFactorAuth);
        $twoFactorAuthForm->handleRequest($request);
        
        // Check if SMTP is configured
        $smtpConfigured = false;
        if (file_exists(__DIR__ . '/../../.env.local')) {
            try {
                (new Dotenv())->load(__DIR__ . '/../../.env.local');
                
                // Check for MAILER_URL configuration
                $mailerUrl = $_ENV['MAILER_URL'] ?? null;
                if (isset($mailerUrl) && $mailerUrl !== '' && $mailerUrl !== 'null://localhost' && $mailerUrl !== false) {
                    $smtpConfigured = true;
                }
                
                // Check for Sendinblue API key
                $sendinblueApiKey = $_ENV['SENDINBLUE_APIKEY'] ?? null;
                if (!empty($sendinblueApiKey)) {
                    $smtpConfigured = true;
                }
            } catch (\Exception $e) {
                $this->logger->warning('Error loading environment variables: ' . $e->getMessage());
            }
        }
        
        if ($form->isSubmitted() && $form->isValid()) {
            $request->getSession()->set('_timezone', $timezone);
            $this->entityManager->flush();

            return $this->redirectToRoute('my_account');
        }
        
        if ($twoFactorAuthForm->isSubmitted() && $twoFactorAuthForm->isValid()) {
            // If SMTP is not configured, disable 2FA
            if (!$smtpConfigured && $twoFactorAuth->isEnabled()) {
                $twoFactorAuth->setEnabled(false);
                $this->addFlash('error', 'Two-factor authentication requires email configuration. Please configure either SMTP settings or Sendinblue API key first.');
            } else {
                $this->addFlash('success', 'Two-factor authentication settings updated successfully.');
            }
            
            $this->entityManager->flush();
            return $this->redirectToRoute('my_account');
        }

        return $this->render('Account/index.html.twig', [
            'locale' => $request->getLocale(),
            'form' => $form->createView(), // change profile form
            'twoFactorAuthForm' => $twoFactorAuthForm->createView(),
            'smtpConfigured' => $smtpConfigured,
        ]);
    }

    /**
     * @return RedirectResponse|Response|null
     *
     * @Route("/account/reset-password", name="my_account_reset_password")
     */
    public function resetPasswordAction(Request $request, UserPasswordEncoderInterface $encoder, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $form = $this->createForm(ResetPasswordType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $oldPassword = $request->request->get('reset_password')['oldPassword'];
            // first we test whether the old password input is correct
            if ($encoder->isPasswordValid($user, $oldPassword)) {
                $newEncodedPassword = $encoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($newEncodedPassword);
                $em->persist($user);
                $em->flush();
                $success = $translator->trans('password_reset.success');
                $this->addFlash('success', $success);

                return $this->redirectToRoute('my_account');
            } else {
                $failure = $translator->trans('password_reset.incorrect_password');
                $this->addFlash('error', $failure);
            }
        }

        return $this->render('Account/resetPassword.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/download", name="download_log")
     **/
    public function downloadFileAction()
    {
        if ($this->env === "dev") {
            $logType = 'dev.log';
        } else {
            $logType = 'prod.log';
        }
        $cwd = getcwd();
        $cwdWithoutPublic = preg_replace('/\\\\public$/', '', $cwd);
        $varPath = "\\var\log\\".$logType;
        $file = $cwdWithoutPublic . $varPath;
        $absolutePathFile = realpath($file);

        // If realpath returns empty, try with Linux path
        if (!$absolutePathFile) {
            $cwdWithoutPublic = preg_replace('/\/public$/', '', $cwd);
            $varPath = "/var/log/" . $logType;
            $file = $cwdWithoutPublic . $varPath;
            $absolutePathFile = realpath($file);
        }
       

        $response = new BinaryFileResponse($absolutePathFile);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $logType);
        return $response;
    }

    /**
     * @Route("/emptylog", name="empty_log")
     **/
    public function emptyLogAction(Request $request): Response
    {
        if ($this->env === "dev") {
            $logType = 'dev.log';
        } else {
            $logType = 'prod.log';
        }
        $cwd = getcwd();
        $cwdWithoutPublic = preg_replace('/\\\\public$/', '', $cwd);
        $varPath = "\\var\log\\".$logType;
        $file = $cwdWithoutPublic . $varPath;
        $absolutePathFile = realpath($file);

        // If realpath returns empty, try with Linux path
        if (!$absolutePathFile) {
            $cwdWithoutPublic = preg_replace('/\/public$/', '', $cwd);
            $varPath = "/var/log/" . $logType;
            $file = $cwdWithoutPublic . $varPath;
            $absolutePathFile = realpath($file);
        }

        // Open the file in write mode
        $handle = fopen($absolutePathFile, 'w');

        // Check if the file was successfully opened
        if ($handle) {
            // Truncate the file by writing an empty string to it
            fwrite($handle, '');

            // Close the file
            fclose($handle);
        }

        return $this->redirect($request->headers->get('referer'));
    }
}
