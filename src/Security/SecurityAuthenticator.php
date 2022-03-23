<?php

namespace App\Security;

use App\Entity\User;
use App\Service\SecurityService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    private $entityManager;

    private $urlGenerator;

    private $csrfTokenManager;

    private $passwordEncoder;

    private $securityService;

    private $env;

    private $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager = null,
        UserPasswordEncoderInterface $passwordEncoder,
        SecurityService $securityService,
        KernelInterface $kernel,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->securityService = $securityService;
        $this->env = $kernel->getEnvironment();
        $this->translator = $translator;
    }

    public function supports(Request $request)
    {
        return 'login' === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
    {
        $credentials = [
            'username' => $request->request->get('_username'),
            'password' => $request->request->get('_password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];

        $session = $request->getSession();
        $session->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if ('test' !== $this->env) {
            $token = new CsrfToken('authenticate', $credentials['csrf_token']);
            if (!$this->csrfTokenManager->isTokenValid($token)) {
                throw new InvalidCsrfTokenException();
            }
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $credentials['username']]);

        if (!$user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException($this->translator->trans('login.invalid_credentials'));
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        if (!$user->isEnabled()) {
            throw new CustomUserMessageAuthenticationException($this->translator->trans('login.disabled_account'));
        }

        //check password, if correct, add last login to database
        if ($this->passwordEncoder->isPasswordValid($user, $credentials['password'])) {
            $user->setLastLogin(new DateTime());
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } else {
            throw new CustomUserMessageAuthenticationException($this->translator->trans('login.invalid_credentials'));
        }

        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('regle_panel'));
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate('login');
    }
}
