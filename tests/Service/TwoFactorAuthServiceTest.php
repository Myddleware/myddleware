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

namespace App\Tests\Service;

use App\Entity\TwoFactorAuth;
use App\Entity\User;
use App\Repository\TwoFactorAuthRepository;
use App\Service\SmsService;
use App\Service\TwoFactorAuthService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;

class TwoFactorAuthServiceTest extends TestCase
{
    private $entityManager;
    private $twoFactorAuthRepository;
    private $logger;
    private $mailer;
    private $params;
    private $smsService;
    private $twoFactorAuthService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->twoFactorAuthRepository = $this->createMock(TwoFactorAuthRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->smsService = $this->createMock(SmsService::class);

        $this->twoFactorAuthService = new TwoFactorAuthService(
            $this->entityManager,
            $this->twoFactorAuthRepository,
            $this->logger,
            $this->mailer,
            $this->params,
            $this->smsService
        );
    }

    public function testGetOrCreateTwoFactorAuth(): void
    {
        $user = new User();
        $twoFactorAuth = new TwoFactorAuth();
        $twoFactorAuth->setUser($user);

        // Test when the record exists
        $this->twoFactorAuthRepository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn($twoFactorAuth);

        $result = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);
        $this->assertSame($twoFactorAuth, $result);

        // Test when the record doesn't exist
        $this->twoFactorAuthRepository->expects($this->once())
            ->method('findByUser')
            ->with($user)
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($arg) use ($user) {
                return $arg instanceof TwoFactorAuth && $arg->getUser() === $user;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);
        $this->assertInstanceOf(TwoFactorAuth::class, $result);
        $this->assertSame($user, $result->getUser());
    }

    public function testGenerateVerificationCode(): void
    {
        $code = $this->twoFactorAuthService->generateVerificationCode();
        $this->assertIsString($code);
        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function testVerifyCode(): void
    {
        $twoFactorAuth = new TwoFactorAuth();
        $twoFactorAuth->setVerificationCode('123456');
        $expiresAt = new DateTime();
        $expiresAt->modify('+5 minutes'); // Not expired
        $twoFactorAuth->setCodeExpiresAt($expiresAt);

        // Test valid code
        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->twoFactorAuthService->verifyCode($twoFactorAuth, '123456');
        $this->assertTrue($result);
        $this->assertEquals(0, $twoFactorAuth->getFailedAttempts());

        // Test invalid code
        $twoFactorAuth->setFailedAttempts(0);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->twoFactorAuthService->verifyCode($twoFactorAuth, '654321');
        $this->assertFalse($result);
        $this->assertEquals(1, $twoFactorAuth->getFailedAttempts());

        // Test expired code
        $twoFactorAuth->setFailedAttempts(0);
        $expiresAt = new DateTime();
        $expiresAt->modify('-5 minutes'); // Expired
        $twoFactorAuth->setCodeExpiresAt($expiresAt);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->twoFactorAuthService->verifyCode($twoFactorAuth, '123456');
        $this->assertFalse($result);
        $this->assertEquals(1, $twoFactorAuth->getFailedAttempts());

        // Test blocked user
        $twoFactorAuth->setFailedAttempts(0);
        $blockedUntil = new DateTime();
        $blockedUntil->modify('+5 minutes'); // Still blocked
        $twoFactorAuth->setBlockedUntil($blockedUntil);

        $result = $this->twoFactorAuthService->verifyCode($twoFactorAuth, '123456');
        $this->assertFalse($result);
    }

    public function testCheckAndBlockIfNeeded(): void
    {
        $twoFactorAuth = new TwoFactorAuth();
        
        // Test with less than 5 attempts
        $twoFactorAuth->setFailedAttempts(4);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $reflectionMethod = new \ReflectionMethod(TwoFactorAuthService::class, 'checkAndBlockIfNeeded');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->twoFactorAuthService, $twoFactorAuth);

        $this->assertNull($twoFactorAuth->getBlockedUntil());

        // Test with 5 attempts
        $twoFactorAuth->setFailedAttempts(5);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $reflectionMethod->invoke($this->twoFactorAuthService, $twoFactorAuth);

        $this->assertNotNull($twoFactorAuth->getBlockedUntil());
        $this->assertGreaterThan(new DateTime(), $twoFactorAuth->getBlockedUntil());
    }

    public function testCreateRememberCookie(): void
    {
        $twoFactorAuth = new TwoFactorAuth();
        
        // Test with existing token
        $twoFactorAuth->setRememberToken('existing_token');
        
        $cookie = $this->twoFactorAuthService->createRememberCookie($twoFactorAuth);
        
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertEquals('myddleware_2fa_remember', $cookie->getName());
        $this->assertEquals('existing_token', $cookie->getValue());
        
        // Test without existing token
        $twoFactorAuth->setRememberToken(null);
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $cookie = $this->twoFactorAuthService->createRememberCookie($twoFactorAuth);
        
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertEquals('myddleware_2fa_remember', $cookie->getName());
        $this->assertNotNull($twoFactorAuth->getRememberToken());
        $this->assertEquals($twoFactorAuth->getRememberToken(), $cookie->getValue());
    }

    public function testCheckRememberCookie(): void
    {
        $request = new Request();
        $twoFactorAuth = new TwoFactorAuth();
        
        // Test without cookie
        $result = $this->twoFactorAuthService->checkRememberCookie($request);
        $this->assertNull($result);
        
        // Test with cookie
        $request->cookies->set('myddleware_2fa_remember', 'token_value');
        
        $this->twoFactorAuthRepository->expects($this->once())
            ->method('findByRememberToken')
            ->with('token_value')
            ->willReturn($twoFactorAuth);
        
        $result = $this->twoFactorAuthService->checkRememberCookie($request);
        $this->assertSame($twoFactorAuth, $result);
    }

    public function testClearRememberCookie(): void
    {
        $response = new Response();
        
        $this->twoFactorAuthService->clearRememberCookie($response);
        
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertEquals('myddleware_2fa_remember', $cookies[0]->getName());
        $this->assertEquals('', $cookies[0]->getValue());
    }
} 