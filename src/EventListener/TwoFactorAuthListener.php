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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;

class TwoFactorAuthListener implements EventSubscriberInterface
{
    private TokenStorageInterface $tokenStorage;
    private TwoFactorAuthService $twoFactorAuthService;
    private SessionInterface $session;
    private UrlGeneratorInterface $urlGenerator;
    private Security $security;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        TwoFactorAuthService $twoFactorAuthService,
        SessionInterface $session,
        UrlGeneratorInterface $urlGenerator,
        Security $security
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->twoFactorAuthService = $twoFactorAuthService;
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
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

        // Skip for login, verification, and public routes
        if ($this->isPublicRoute($path)) {
            return;
        }

        // Check if the user is authenticated
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Get the 2FA record for this user
        $twoFactorAuth = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);

        // If 2FA is not enabled, we're done
        if (!$twoFactorAuth->isEnabled()) {
            return;
        }

        // Check if the user has completed 2FA
        if ($this->session->get('two_factor_auth_complete', false)) {
            return;
        }

        // Check if the user has a remember cookie
        $rememberedAuth = $this->twoFactorAuthService->checkRememberCookie($request);
        if ($rememberedAuth && $rememberedAuth->getUser()->getId() === $user->getId()) {
            // If the user has a valid remember cookie, mark as complete
            $this->session->set('two_factor_auth_complete', true);
            return;
        }

        // Redirect to verification
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