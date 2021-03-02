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

class SecurityAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    private $entityManager;

    private $urlGenerator;

    private $csrfTokenManager;

    private $passwordEncoder;

    private $securityService;

    private $env;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager = null,
        UserPasswordEncoderInterface $passwordEncoder,
        SecurityService $securityService,
        KernelInterface $kernel
    ) {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->securityService = $securityService;
        $this->env = $kernel->getEnvironment();
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
            throw new CustomUserMessageAuthenticationException('Identifiants invalides.');
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        if (!$user->isEnabled()) {
            throw new CustomUserMessageAuthenticationException('Votre compte est désactivé.');
        }

       //TODO : THIS NEEDS TO BE UPDATED / FINISHED TO BE IN LINE WITH NEW ENCRYTPION (bcrypt)

        // FOS USER BUNDLE ALGORYTHM
        // $password = $credentials['password'];
        // $salt = $user->getSalt();
      
        // $this->securityService->hashPassword($password, $salt);

        // // Check validation
        // if ($password === $user->getPassword()) {
        //     // Save Last Login date
        //     $user->setLastLogin(new DateTime());
        //     $this->entityManager->flush();

        //     return true;
        // }

        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
        throw new CustomUserMessageAuthenticationException('Identifiants invalides.');
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
