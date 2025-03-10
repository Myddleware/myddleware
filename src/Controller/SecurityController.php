<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\ResetPasswordType;
use App\Form\Type\UserForgotPasswordType;
use App\Manager\NotificationManager;
use App\Repository\UserRepository;
use App\Service\SecurityService;
use App\Service\TwoFactorAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SecurityController extends AbstractController
{
    protected AuthorizationCheckerInterface $authorizationChecker;
    private UserRepository $userRepository;
    private EncoderFactoryInterface $encoder;
    private EntityManagerInterface $entityManager;
    private NotificationManager $notificationManager;
    private SecurityService $securityService;
    private TwoFactorAuthService $twoFactorAuthService;
    private SessionInterface $session;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        EncoderFactoryInterface $encoder,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        NotificationManager $notificationManager,
        SecurityService $securityService,
        TwoFactorAuthService $twoFactorAuthService,
        SessionInterface $session
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->encoder = $encoder;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->notificationManager = $notificationManager;
        $this->securityService = $securityService;
        $this->twoFactorAuthService = $twoFactorAuthService;
        $this->session = $session;
    }

    /**
     * @Route("/", name="login")
     * @Route("/login")
     */
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // Check if the user is already authenticated
        if ($this->getUser() instanceof User) {
            // Check if the user has completed 2FA
            $user = $this->getUser();
            $twoFactorAuth = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);
            
            // If 2FA is enabled and not completed, redirect to verification
            if ($twoFactorAuth->isEnabled() && !$this->session->get('two_factor_auth_complete', false)) {
                // Check if the user has a remember cookie
                $rememberedAuth = $this->twoFactorAuthService->checkRememberCookie($request);
                if ($rememberedAuth && $rememberedAuth->getUser()->getId() === $user->getId()) {
                    // If the user has a valid remember cookie, mark as complete and redirect
                    $this->session->set('two_factor_auth_complete', true);
                } else {
                    // Otherwise, redirect to verification
                    return $this->redirectToRoute('two_factor_auth_verify');
                }
            }
            
            return $this->redirectToRoute('regle_panel');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        if (!empty($error)) {
            $error = $error->getMessage();
        }

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        $this->calculBan($lastUsername);

        $attempt = ((isset($_SESSION['myddleware']['secure'][$lastUsername]['attempt'])) ? $_SESSION['myddleware']['secure'][$lastUsername]['attempt'] : 0);
        $remaining = ((isset($_SESSION['myddleware']['secure'][$lastUsername]['remaining'])) ? $_SESSION['myddleware']['secure'][$lastUsername]['remaining'] : 0);

        // If we are on platform.sh, we check that the password has been changed because the first user is always admin/admin
        $passwordMessage = false;
        $platformSh = false;
        if (isset($_ENV['PLATFORM_RELATIONSHIPS'])) {
            $platformSh = true;
            // Get the admin user
            $userAdmin = $this->userRepository->loadUserByUsername('admin');
            if (!empty($userAdmin)) {
                $encoder = $this->encoder->getEncoder($userAdmin);
                // Compare password with admin encoded
                if ($encoder->encodePassword('admin', $userAdmin->getSalt()) == $userAdmin->getPassword()) {
                    $passwordMessage = true;
                }
            }
        }

        return $this->render('Login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'attempt' => $attempt,
            'remaining' => $remaining,
            'password_message' => $passwordMessage,
            'platform_sh' => $platformSh,
        ]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout(): Response
    {
        // Clear the 2FA session flag
        $this->session->remove('two_factor_auth_complete');
        
        // Ignored by the system of logout @see security.yaml
        return $this->redirectToRoute('login');
    }

    private function calculBan($lastUsername)
    {
        if (isset($_SESSION['myddleware']['secure'][$lastUsername]['time'])) {
            if (time() > $_SESSION['myddleware']['secure'][$lastUsername]['time']) {
                $_SESSION['myddleware']['secure'][$lastUsername]['attempt'] = 1;
            } else {
                // RESTE X MINUTES AVANT LA FUTUR CONNEXION
                $date1 = time();
                $date2 = $_SESSION['myddleware']['secure'][$lastUsername]['time'];
                $diff = abs($date1 - $date2);

                $diff = abs($date1 - $date2); // abs pour avoir la valeur absolute, ainsi éviter d'avoir une différence négative
                $remaining = [];

                $tmp = $diff;
                $remaining['second'] = $tmp % 60;

                $tmp = floor(($tmp - $remaining['second']) / 60);
                $remaining['minute'] = $tmp % 60;

                $tmp = floor(($tmp - $remaining['minute']) / 60);
                $remaining['hour'] = $tmp % 24;

                $tmp = floor(($tmp - $remaining['hour']) / 24);
                $remaining['day'] = $tmp;

                $_SESSION['myddleware']['secure'][$lastUsername]['remaining'] = $remaining;
            }
        }
    }

    public function verifAccount(Request $request): Response
    {
        try {
            if ($request->isMethod('POST')) {
                $lastUsername = trim($request->request->get('login'));

                // contrôle des tentatives
                // si le nombre de tentative n'existe pas on affecte 0
                if (!isset($_SESSION['myddleware']['secure'][$lastUsername]['attempt'])) {
                    $_SESSION['myddleware']['secure'][$lastUsername]['attempt'] = 1;
                } else { // si existe on ajoute +1
                    $_SESSION['myddleware']['secure'][$lastUsername]['attempt'];
                }

                // si le nombre de tentative est supérieur à 5 alors on ajoute une date de contrôle
                if ($_SESSION['myddleware']['secure'][$lastUsername]['attempt'] > 4) {
                    if (!isset($_SESSION['myddleware']['secure'][$lastUsername]['time'])) {
                        $_SESSION['myddleware']['secure'][$lastUsername]['time'] = strtotime('+15 minutes', time());
                    } else {
                        $this->calculBan($lastUsername);
                    }
                }

                return new Response(1);
            }

            return new Response(0);
        } catch (Exception $e) {
            return new Response(0);
        }
    }

    /**
     * @Route("/resetting/{token}", name="resetting_request", defaults={"token"=null})
     *
     * @throws Exception
     */
    public function reset(Request $request, $token, UserPasswordEncoderInterface $encoder)
    {
        if (!$token) {
            $form = $this->createForm(UserForgotPasswordType::class);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $email = $form->get('email')->getData();
                /** @var User|null $user */
                $user = $this->userRepository->findOneBy(['email' => $email]);
                if (!$user) {
                    $this->addFlash('danger-reset-password', 'No user with this email was found.');
                    return $this->redirectToRoute('resetting_request');
                }

                $user->setConfirmationToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='));
                $this->entityManager->flush();

                try {
                    $this->notificationManager->resetPassword($user);
                    $this->addFlash('success-reset-password', 'An email has been sent to ' . $user->getEmail() . ' with a password reset link.');
                    return $this->redirectToRoute('resetting_request');
                } catch (Exception $e) {
                    $this->addFlash('danger', 'Unable to send email. ' . $e->getMessage());
                    return $this->redirectToRoute('resetting_request');
                }
            }

            return $this->render('Login/reset_request.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        /** @var User|null $user */
        $user = $this->userRepository->findOneBy(['confirmationToken' => $token]);
        if (null === $user) {
            return $this->redirectToRoute('regle_panel');
        }

        $form = $this->createForm(ResetPasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldPassword = $request->request->get('reset_password')['oldPassword'];
            if ($encoder->isPasswordValid($user, $oldPassword)) {
                $newEncodedPassword = $encoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($newEncodedPassword);
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->addFlash('success', 'Password has been successfully reset.');
                return $this->redirectToRoute('regle_panel');
            } else {
                $this->addFlash('danger', 'The old password is incorrect.');
            }
        }

        return $this->render('Login/reset.html.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }

}
