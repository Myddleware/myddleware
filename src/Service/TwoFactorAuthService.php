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

namespace App\Service;

use App\Entity\TwoFactorAuth;
use App\Entity\User;
use App\Repository\TwoFactorAuthRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use GuzzleHttp\Client;

class TwoFactorAuthService
{
    private EntityManagerInterface $entityManager;
    private TwoFactorAuthRepository $twoFactorAuthRepository;
    private LoggerInterface $logger;
    private MailerInterface $mailer;
    private ParameterBagInterface $params;
    private SmsService $smsService;
    private ?TransactionalEmailsApi $brevo = null;

    public function __construct(
        EntityManagerInterface $entityManager,
        TwoFactorAuthRepository $twoFactorAuthRepository,
        LoggerInterface $logger,
        MailerInterface $mailer,
        ParameterBagInterface $params,
        SmsService $smsService,
        ?string $brevoApiKey = null
    ) {
        $this->entityManager = $entityManager;
        $this->twoFactorAuthRepository = $twoFactorAuthRepository;
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->params = $params;
        $this->smsService = $smsService;
        
        // Initialize Brevo client if API key exists and is not empty
        if (!empty($brevoApiKey)) {
            try {
                $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $brevoApiKey);
                $this->brevo = new TransactionalEmailsApi(new Client(), $config);
                $this->logger->info('Brevo client initialized successfully');
            } catch (\Exception $e) {
                $this->logger->error('Failed to initialize Brevo client: ' . $e->getMessage());
            }
        }
    }

    public function getOrCreateTwoFactorAuth(User $user): TwoFactorAuth
    {
        $twoFactorAuth = $this->twoFactorAuthRepository->findByUser($user);
        
        if (!$twoFactorAuth) {
            $twoFactorAuth = new TwoFactorAuth();
            $twoFactorAuth->setUser($user);
            $this->entityManager->persist($twoFactorAuth);
            $this->entityManager->flush();
        }
        
        return $twoFactorAuth;
    }

    public function generateVerificationCode(): string
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function sendVerificationCode(TwoFactorAuth $twoFactorAuth): bool
    {
        $user = $twoFactorAuth->getUser();
        $code = $this->generateVerificationCode();
        
        // Set the verification code and expiration time (1 minute)
        $twoFactorAuth->setVerificationCode($code);
        $expiresAt = new DateTime();
        $expiresAt->modify('+1 minute');
        $twoFactorAuth->setCodeExpiresAt($expiresAt);
        
        $this->entityManager->flush();
        
        // Always use email method regardless of the preferredMethod setting
        try {
            return $this->sendEmailCode($user, $code);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send verification code: ' . $e->getMessage());
            return false;
        }
    }

    private function sendEmailCode(User $user, string $code): bool
    {
        // Check if Brevo API is configured
        if ($this->brevo instanceof TransactionalEmailsApi) {
            try {
                $sendSmtpEmail = new \Brevo\Client\Model\SendSmtpEmail();
                $sendSmtpEmail['to'] = [['email' => $user->getEmail()]];
                $sendSmtpEmail['subject'] = 'Myddleware - Your verification code';
                $sendSmtpEmail['htmlContent'] = '<p>Hello ' . $user->getUsername() . ',</p>' .
                    '<p>Your verification code is: <strong>' . $code . '</strong></p>' .
                    '<p>This code will expire in 1 minute.</p>' .
                    '<p>If you did not request this code, please ignore this email.</p>';
                $sendSmtpEmail['sender'] = ['email' => $this->params->get('email_from', 'no-reply@myddleware.com')];

                $result = $this->brevo->sendTransacEmail($sendSmtpEmail);
                return true;
            } catch (\Exception $e) {
                $this->logger->error('Failed to send verification email via Brevo: ' . $e->getMessage());
                return false;
            }
        }

        // Fallback to Symfony Mailer if Brevo is not configured
        try {
            $email = (new Email())
                ->from($this->params->get('email_from', 'no-reply@myddleware.com'))
                ->to($user->getEmail())
                ->subject('Myddleware - Your verification code')
                ->html(
                    '<p>Hello ' . $user->getUsername() . ',</p>' .
                    '<p>Your verification code is: <strong>' . $code . '</strong></p>' .
                    '<p>This code will expire in 1 minute.</p>' .
                    '<p>If you did not request this code, please ignore this email.</p>'
                );
            
            $this->mailer->send($email);
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to send verification email: ' . $e->getMessage());
            return false;
        }
    }

    private function sendSmsCode(TwoFactorAuth $twoFactorAuth, string $code): bool
    {
        $phoneNumber = $twoFactorAuth->getPhoneNumber();
        
        if (!$phoneNumber) {
            return false;
        }
        
        $message = 'Your Myddleware verification code is: ' . $code . '. This code will expire in 1 minute.';
        
        return $this->smsService->send($phoneNumber, $message);
    }

    public function verifyCode(TwoFactorAuth $twoFactorAuth, string $code): bool
    {
        // Check if the user is blocked
        if ($twoFactorAuth->isBlocked()) {
            return false;
        }
        
        // Check if the code is expired
        if ($twoFactorAuth->isCodeExpired()) {
            $twoFactorAuth->incrementFailedAttempts();
            $this->checkAndBlockIfNeeded($twoFactorAuth);
            return false;
        }
        
        // Check if the code matches
        if ($twoFactorAuth->getVerificationCode() !== $code) {
            $twoFactorAuth->incrementFailedAttempts();
            $this->checkAndBlockIfNeeded($twoFactorAuth);
            return false;
        }
        
        // Code is valid, reset failed attempts
        $twoFactorAuth->resetFailedAttempts();
        $this->entityManager->flush();
        
        return true;
    }

    private function checkAndBlockIfNeeded(TwoFactorAuth $twoFactorAuth): void
    {
        if ($twoFactorAuth->getFailedAttempts() >= 5) {
            $blockedUntil = new DateTime();
            $blockedUntil->modify('+1 minute');
            $twoFactorAuth->setBlockedUntil($blockedUntil);
        }
        
        $this->entityManager->flush();
    }

    public function setRememberDevice(TwoFactorAuth $twoFactorAuth, bool $remember): void
    {
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $twoFactorAuth->setRememberToken($token);
        } else {
            $twoFactorAuth->setRememberToken(null);
        }
        
        $twoFactorAuth->setRememberDevice($remember);
        $this->entityManager->flush();
    }

    public function createRememberCookie(TwoFactorAuth $twoFactorAuth): Cookie
    {
        $token = $twoFactorAuth->getRememberToken();
        
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $twoFactorAuth->setRememberToken($token);
            $this->entityManager->flush();
        }
        
        // Create a cookie that expires in 30 days
        return Cookie::create(
            'myddleware_2fa_remember',
            $token,
            time() + (30 * 24 * 60 * 60), // 30 days
            '/',
            null,
            false,
            true
        );
    }

    public function checkRememberCookie(Request $request): ?TwoFactorAuth
    {
        $cookie = $request->cookies->get('myddleware_2fa_remember');
        
        if (!$cookie) {
            return null;
        }
        
        return $this->twoFactorAuthRepository->findByRememberToken($cookie);
    }

    public function clearRememberCookie(Response $response): void
    {
        $response->headers->clearCookie('myddleware_2fa_remember');
    }
} 