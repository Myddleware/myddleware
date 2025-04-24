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
use App\Form\Type\UpdatePasswordType;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


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

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        KernelInterface $kernel,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params,
        TranslatorInterface $translator,
        ToolsManager $toolsManager,
        AlertBootstrapInterface $alert,
        TwoFactorAuthService $twoFactorAuthService,
        SerializerInterface $serializer = null,
        ValidatorInterface $validator = null
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
        $this->serializer = $serializer;
        $this->validator = $validator;
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
    public function myAccount(Request $request, UserPasswordHasherInterface $hasher, UserManagerInterface $userManager): Response
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
                
                // Check for Brevo API key
                $brevoApiKey = $_ENV['BREVO_APIKEY'] ?? null;
                if (!empty($brevoApiKey)) {
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
    public function resetPasswordAction(Request $request, UserPasswordHasherInterface $hasher, TranslatorInterface $translator)
    {
        $em = $this->entityManager;
        $user = $this->getUser();
        $form = $this->createForm(UpdatePasswordType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // start by getting the request all
            $requestData = $request->request->all();

            // then get the old password from the request data
            $oldPassword = $requestData['update_password']['oldPassword'];

            // first we test whether the old password input is correct
            if ($hasher->isPasswordValid($user, $oldPassword)) {
                $newHashedPassword = $hasher->hashPassword($user, $user->getPlainPassword());
                $user->setPassword($newHashedPassword);
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

    /**
     * Modern JavaScript-based account page
     * 
     * @Route("/account/modern", name="account_modern")
     */
    public function accountModern(): Response
    {
        return $this->render('Account/account-js.html.twig');
    }

    /**
     * @Route("/api/account/info", name="api_account_info", methods={"GET"})
     */
    public function getAccountInfo(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }
        
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
                
                // Check for Brevo API key
                $brevoApiKey = $_ENV['BREVO_APIKEY'] ?? null;
                if (!empty($brevoApiKey)) {
                    $smtpConfigured = true;
                }
            } catch (\Exception $e) {
                $this->logger->warning('Error loading environment variables: ' . $e->getMessage());
            }
        }
        
        // Get two-factor auth status
        $twoFactorAuth = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);
        
        // Get all available locales
        $locales = [];
        $defaultLocale = $request->getLocale();
        
        // Get list of available translations
        $translationDir = $this->params->get('kernel.project_dir') . '/translations';
        if (is_dir($translationDir)) {
            $translationFiles = scandir($translationDir);
            foreach ($translationFiles as $file) {
                if (preg_match('/^messages\.([a-z]{2})\.yaml$/', $file, $matches)) {
                    $locales[] = $matches[1];
                }
            }
        }
        
        // If no translations found, provide default ones
        if (empty($locales)) {
            $locales = ['en', 'fr'];
        }
        
        // Get user preferences from session or defaults
        $dateFormat = $request->getSession()->get('_date_format', 'Y-m-d');
        $exportSeparator = $request->getSession()->get('_export_separator', ',');
        $encoding = $request->getSession()->get('_encoding', 'UTF-8');
        
        return new JsonResponse([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'timezone' => $user->getTimezone() ?? 'UTC',
            'currentLocale' => $defaultLocale,
            'availableLocales' => $locales,
            'twoFactorEnabled' => $twoFactorAuth->isEnabled(),
            'smtpConfigured' => $smtpConfigured,
            'dateFormat' => $dateFormat,
            'exportSeparator' => $exportSeparator,
            'encoding' => $encoding
        ]);
    }

    /**
     * @Route("/api/account/profile/update", name="api_account_profile_update", methods={"POST"})
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON data'], 400);
        }
        
        // Basic validation
        $errors = [];
        
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], 400);
        }
        
        // Update user data
        if (isset($data['username']) && !empty($data['username'])) {
            $user->setUsername($data['username']);
        }
        
        if (isset($data['email']) && !empty($data['email'])) {
            $user->setEmail($data['email']);
        }
        
        if (isset($data['timezone']) && !empty($data['timezone'])) {
            try {
                new \DateTimeZone($data['timezone']); // Validate timezone
                $user->setTimezone($data['timezone']);
                $request->getSession()->set('_timezone', $data['timezone']);
            } catch (\Exception $e) {
                return new JsonResponse(['errors' => ['Invalid timezone']], 400);
            }
        }
        
        // Store additional preferences in session
        if (isset($data['dateFormat'])) {
            // Validate date format
            $validFormats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd.m.Y'];
            if (in_array($data['dateFormat'], $validFormats)) {
                $request->getSession()->set('_date_format', $data['dateFormat']);
            }
        }
        
        if (isset($data['exportSeparator'])) {
            // Validate export separator
            $validSeparators = [',', ';', "\t", '|'];
            if (in_array($data['exportSeparator'], $validSeparators)) {
                $request->getSession()->set('_export_separator', $data['exportSeparator']);
            }
        }
        
        if (isset($data['encoding'])) {
            // Validate encoding
            $validEncodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252'];
            if (in_array($data['encoding'], $validEncodings)) {
                $request->getSession()->set('_encoding', $data['encoding']);
            }
        }
        
        $this->entityManager->flush();
        
        return new JsonResponse(['success' => true, 'message' => 'Profile updated successfully']);
    }

    /**
     * @Route("/api/account/password/update", name="api_account_password_update", methods={"POST"})
     */
    public function updatePassword(Request $request, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON data'], 400);
        }
        
        // Validate input
        if (!isset($data['oldPassword']) || empty($data['oldPassword'])) {
            return new JsonResponse(['error' => 'Current password is required'], 400);
        }
        
        if (!isset($data['plainPassword']) || empty($data['plainPassword'])) {
            return new JsonResponse(['error' => 'New password is required'], 400);
        }
        
        // Check if old password is valid
        if (!$hasher->isPasswordValid($user, $data['oldPassword'])) {
            return new JsonResponse(['error' => 'Current password is incorrect'], 400);
        }
        
        // Update password
        $newHashedPassword = $hasher->hashPassword($user, $data['plainPassword']);
        $user->setPassword($newHashedPassword);
        
        $this->entityManager->flush();
        
        return new JsonResponse(['success' => true, 'message' => 'Password updated successfully']);
    }

    /**
     * @Route("/api/account/twofactor/update", name="api_account_twofactor_update", methods={"POST"})
     */
    public function updateTwoFactor(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON data'], 400);
        }
        
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
                
                // Check for Brevo API key
                $brevoApiKey = $_ENV['BREVO_APIKEY'] ?? null;
                if (!empty($brevoApiKey)) {
                    $smtpConfigured = true;
                }
            } catch (\Exception $e) {
                $this->logger->warning('Error loading environment variables: ' . $e->getMessage());
            }
        }
        
        // Get two-factor auth record
        $twoFactorAuth = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);
        
        // If SMTP is not configured, disable 2FA
        if (!$smtpConfigured && isset($data['enabled']) && $data['enabled']) {
            return new JsonResponse([
                'error' => 'Two-factor authentication requires email configuration. Please configure either SMTP settings or Brevo API key first.'
            ], 400);
        }
        
        // Update 2FA settings
        if (isset($data['enabled'])) {
            $twoFactorAuth->setEnabled($data['enabled']);
        }
        
        $this->entityManager->flush();
        
        return new JsonResponse(['success' => true, 'message' => 'Two-factor authentication settings updated successfully']);
    }

    /**
     * @Route("/api/account/locale", name="api_account_locale", methods={"POST"})
     */
    public function apiChangeLocale(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['locale'])) {
            return new JsonResponse(['error' => 'Locale is required'], 400);
        }
        
        $locale = $data['locale'];
        
        // Validate locale
        $validLocales = ['en', 'fr']; // Add more as needed
        
        // Get list of available translations
        $translationDir = $this->params->get('kernel.project_dir') . '/translations';
        if (is_dir($translationDir)) {
            $validLocales = [];
            $translationFiles = scandir($translationDir);
            foreach ($translationFiles as $file) {
                if (preg_match('/^messages\.([a-z]{2})\.yaml$/', $file, $matches)) {
                    $validLocales[] = $matches[1];
                }
            }
        }
        
        if (!in_array($locale, $validLocales)) {
            return new JsonResponse(['error' => 'Invalid locale'], 400);
        }
        
        $request->getSession()->set('_locale', $locale);
        
        return new JsonResponse(['success' => true, 'message' => 'Locale changed to ' . $locale]);
    }

    /**
     * @Route("/api/account/logs/download", name="api_account_logs_download", methods={"GET"})
     */
    public function apiDownloadLog(): Response
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
     * @Route("/api/account/logs/empty", name="api_account_logs_empty", methods={"POST"})
     */
    public function apiEmptyLog(): JsonResponse
    {
        // Check user permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Permission denied'], 403);
        }
        
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
            
            return new JsonResponse(['success' => true, 'message' => 'Log file emptied successfully']);
        }
        
        return new JsonResponse(['error' => 'Failed to empty log file'], 500);
    }
}
