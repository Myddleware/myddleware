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

namespace App\EventListener;

use App\Entity\User;
use App\Service\TwoFactorAuthService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;
use Psr\Log\LoggerInterface;

class TwoFactorAuthListener implements EventSubscriberInterface
{
    private TokenStorageInterface $tokenStorage;
    private TwoFactorAuthService $twoFactorAuthService;
    private RequestStack $requestStack;
    private UrlGeneratorInterface $urlGenerator;
    private Security $security;
    private LoggerInterface $logger;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        TwoFactorAuthService $twoFactorAuthService,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        Security $security,
        LoggerInterface $logger
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->twoFactorAuthService = $twoFactorAuthService;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        $this->logger->debug('TwoFactorAuthListener: Checking path: ' . $path);

        // Skip for login, verification, and public routes
        if ($this->isPublicRoute($path)) {
            $this->logger->debug('TwoFactorAuthListener: Skipping public route');
            return;
        }

        // Check if the user is authenticated
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            $this->logger->debug('TwoFactorAuthListener: No authenticated user');
            return;
        }

        // Get the 2FA record for this user
        $twoFactorAuth = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);

        // If 2FA is not enabled, we're done
        if (!$twoFactorAuth->isEnabled()) {
            $this->logger->debug('TwoFactorAuthListener: 2FA not enabled for user');
            return;
        }

        // Get the session from the request
        $session = $request->getSession();
        
        // Check if the user has completed 2FA
        if ($session->get('two_factor_auth_complete', false)) {
            $this->logger->debug('TwoFactorAuthListener: 2FA already completed');
            return;
        }

        // Check if the user has a remember cookie
        $rememberedAuth = $this->twoFactorAuthService->checkRememberCookie($request);
        if ($rememberedAuth && $rememberedAuth->getUser()->getId() === $user->getId()) {
            // If the user has a valid remember cookie, mark as complete
            $session->set('two_factor_auth_complete', true);
            $this->logger->debug('TwoFactorAuthListener: Valid remember cookie found, marking 2FA as complete');
            return;
        }

        // Redirect to verification
        $this->logger->debug('TwoFactorAuthListener: Redirecting to verification page');
        $verifyUrl = $this->urlGenerator->generate('two_factor_auth_verify');
        $event->setResponse(new RedirectResponse($verifyUrl));
    }

    private function isPublicRoute(string $path): bool
    {
        $publicRoutes = [
            '/login',
            '/verify',
            '/verify/resend',
            '/verify/switch-method/email',
            '/verify/switch-method/sms',
            '/resetting',
            '/install',
            '/install/requirements',
            '/install/database',
            '/install/admin',
            '/install/finish',
            '/css',
            '/js',
            '/images',
            '/fonts',
        ];

        foreach ($publicRoutes as $route) {
            if (strpos($path, $route) === 0) {
                return true;
            }
        }

        return false;
    }
} 