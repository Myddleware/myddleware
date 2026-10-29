<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\ResetPasswordType;
use App\Form\Type\UserForgotPasswordType;
use App\Manager\NotificationManager;
use App\Repository\UserRepository;
use App\Service\DebugLogger;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    protected AuthorizationCheckerInterface $authorizationChecker;
    private UserRepository $userRepository;
    private PasswordHasherFactoryInterface $passwordHasherFactory;
    private EntityManagerInterface $entityManager;
    private NotificationManager $notificationManager;
    private SecurityService $securityService;
    private DebugLogger $debugLogger;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        PasswordHasherFactoryInterface $passwordHasherFactory,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        NotificationManager $notificationManager,
        SecurityService $securityService,
        DebugLogger $debugLogger
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->passwordHasherFactory = $passwordHasherFactory;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->notificationManager = $notificationManager;
        $this->securityService = $securityService;
        $this->debugLogger = $debugLogger;
    }

    #[Route('/', name: 'login')]
    #[Route('/login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['authenticationUtils' => $authenticationUtils]);
        $__debugReturn = null;
        try {
            if ($this->getUser() instanceof User) {
                return $__debugReturn = $this->redirectToRoute('regle_panel');
            }

            // get the login error if there is one
            $error = $authenticationUtils->getLastAuthenticationError();
            if (!empty($error)) {
                $error = $error->getMessage();
            }

            // last username entered by the user
            $lastUsername = $authenticationUtils->getLastUsername();

            // If we are on platform.sh, we check that the password has been changed because the first user is always admin/admin
            $passwordMessage = false;
            $platformSh = false;
            if (isset($_ENV['PLATFORM_RELATIONSHIPS'])) {
                $platformSh = true;
                // Get the admin user
                $userAdmin = $this->userRepository->loadUserByUsername('admin');
                if (!empty($userAdmin)) {
                    $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($userAdmin);
                    // Compare password with admin encoded
                    if ($passwordHasher->verify($userAdmin->getPassword(), 'admin')) {
                        $passwordMessage = true;
                    }
                }
            }

            return $__debugReturn = $this->render('Login/index.html.twig', [
                'last_username' => $lastUsername,
                'error' => $error,
                'password_message' => $passwordMessage,
                'platform_sh' => $platformSh,
            ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    #[Route('/resetting/{token}', name: 'resetting_request', defaults: ['token' => null])]
    public function reset(Request $request, $token, UserPasswordHasherInterface $passwordHasher)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'token' => $token, 'passwordHasher' => $passwordHasher]);
        $__debugReturn = null;
        try {
            if (!$token) {
                $form = $this->createForm(UserForgotPasswordType::class);
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $email = $form->get('email')->getData();
                    /** @var User|null $user */
                    $user = $this->userRepository->findOneBy(['email' => $email]);
                    if (!$user) {
                        $this->addFlash('security.reset.danger', 'No user with this email was found.');
                        return $__debugReturn = $this->redirectToRoute('resetting_request');
                    }

                    $user->setConfirmationToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='));
                    $this->entityManager->flush();

                    try {
                        $this->notificationManager->resetPassword($user);
                        $this->addFlash('security.reset.success', 'An email has been sent to ' . $user->getEmail() . ' with a password reset link.');
                        return $__debugReturn = $this->redirectToRoute('resetting_request');
                    } catch (Exception $e) {
                        $this->addFlash('security.reset.danger', 'Unable to send email. ' . $e->getMessage());
                        return $__debugReturn = $this->redirectToRoute('resetting_request');
                    }
                }

                return $__debugReturn = $this->render('Login/reset_request.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            /** @var User|null $user */
            $user = $this->userRepository->findOneBy(['confirmationToken' => $token]);
            if (null === $user) {
                return $__debugReturn = $this->redirectToRoute('regle_panel');
            }

            $form = $this->createForm(ResetPasswordType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $newHashedPassword = $passwordHasher->hashPassword($user, $user->getPlainPassword());
                $user->setPassword($newHashedPassword);
                $user->setConfirmationToken(null); // Clear the token after successful reset
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->addFlash('security.reset.success', 'Password has been successfully reset.');
                return $__debugReturn = $this->redirectToRoute('login');
            }

            return $__debugReturn = $this->render('Login/reset.html.twig', [
                'token' => $token,
                'form' => $form->createView(),
            ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}
