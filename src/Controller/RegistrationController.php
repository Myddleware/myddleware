<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    private LoggerInterface $logger;

    private MailerInterface $mailer;

    public function __construct(EmailVerifier $emailVerifier, LoggerInterface $logger, MailerInterface $mailer)
    {
        $this->emailVerifier = $emailVerifier;
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // encode the plain password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
                $user->setTimeZone('UTC');
                $entityManager->persist($user);
                $entityManager->flush(); // generate a signed url and email it to the user
                try {
                    $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                        (new TemplatedEmail())
                            ->from(new Address('no-reply@myddleware.com', 'Myddleware'))
                            ->to($user->getEmail())
                            ->subject('Please Confirm your Email')
                            ->htmlTemplate('registration/confirmation_email.html.twig'),
                    );
                    $this->addFlash(
                        'success',
                        $translator->trans('email_verification.validate_account_email')
                    );

                    return $this->redirectToRoute('admin_dashboard');
                } catch (TransportExceptionInterface $e) {
                    $this->addFlash(
                        'warning',
                        $e->getMessage()
                    );
                    $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());
                $this->addFlash(
                    'warning',
                    $e->getMessage()
                );
            }
        }

        return $this->renderForm('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $userId = $request->query->get('id');
            if (!$user = $this->getUser()) {
                if (!$user = $userRepository->find($userId)) {
                    throw $this->createNotFoundException();
                }
            }
            $this->emailVerifier->handleEmailConfirmation($request, $user);
            $this->logger->info('Email verification');
            $this->addFlash('success', $translator->trans('email_verification.email_verified'));

            return $this->redirectToRoute('app_successful_verification');
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->logger->error('Email verification error : '.$exception->getMessage().' '.$exception->getFile().' '.$exception->getLine());
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }
    }

    #[Route('/verify/email/success', name: 'app_successful_verification')]
    public function successfulVerification(TranslatorInterface $translator): Response
    {
        return $this->render('registration/successful_verification.html.twig');
    }
}
