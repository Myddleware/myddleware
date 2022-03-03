<?php

namespace App\Controller;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;


class MailerController extends AbstractController
{

    // TODO: this function must be implemented in order to let user which transport to use & send info to mailer
    public function setUpTransport()
    {
        
    }

    /**
     * @Route("/email", name="app_mailer")
     */
    public function sendEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('hello@example.com')
            // ->from(new Address('fabien@example.com', 'Fabien'))
            ->to('you@example.com')
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject('Time for Symfony Mailer!')
            ->text('Sending emails is fun again!')
            ->html('<p>See Twig integration for better HTML integration!</p>');
            // https://symfony.com/doc/5.4/mailer.html
            try {
                $mailer->send($email);
                return new Response();
            } catch (TransportExceptionInterface $e) {
                // some error prevented the email sending; display an
                // error message or try to resend the message
                $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());
            }
        // ...
    }
}
