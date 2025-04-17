<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\ConfigRepository;
use App\Security\SecurityAuthenticator;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class RegistrationController extends AbstractController
{
    private ConfigRepository $configRepository;
    private LoggerInterface $logger;

    public function __construct(ConfigRepository $configRepository, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, GuardAuthenticatorHandler $guardHandler, SecurityAuthenticator $authenticator): Response
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
                $passwordEncoder->encodePassword(
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
                        $entityManager->persist($config);
                    }
                }
                $entityManager->persist($user);
                $entityManager->flush();

                // do anything else you need here, like send an email
                return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main' // firewall name in security.yaml
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
