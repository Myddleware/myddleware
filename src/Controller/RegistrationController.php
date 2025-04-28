<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\ConfigRepository;
use App\Security\SecurityAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    private ConfigRepository $configRepository;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ConfigRepository $configRepository, 
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $logger;
        $this->configRepository = $configRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, UserAuthenticatorInterface $userAuthenticator, SecurityAuthenticator $authenticator): Response
    {
        try {
            //to help voter decide whether we allow access to install process again or not
            $configs = $this->configRepository->findAll();
            if (!empty($configs)) {
                foreach ($configs as $config) {
                    if ('allow_install' === $config->getName()) {
                        $this->denyAccessUnlessGranted('DATABASE_EDIT', $config);
                    }
                }
            }

            $user = new User();
            $form = $this->createForm(RegistrationFormType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // encode the plain password
                $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

                $user->addRole('ROLE_ADMIN');
                // allows user to login to Myddleware
                $user->setEnabled(true);
                $user->setUsernameCanonical($user->getUsername());
                $user->setEmailCanonical($user->getEmail());
                $user->setTimezone('UTC');

                $entityManager = $this->entityManager;


                // block install from here as user has successfully installed Myddleware now
                foreach ($configs as $config) {
                    if ('allow_install' === $config->getName()) {
                        $config->setValue('false');
                        $this->entityManager->persist($config);
                    }
                }
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // do anything else you need here, like send an email
                return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());

            return $this->redirectToRoute('login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
