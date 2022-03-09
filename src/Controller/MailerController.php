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
            ->cc('estellegaits@myddleware.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            // ->text(fopen('/path/to/emails/user_signup.txt', 'r'))
            // ->html(fopen('/path/to/emails/user_signup.html', 'r'))
            // ->attachFromPath('/path/to/documents/terms-of-use.pdf')
            // optionally you can tell email clients to display a custom name for the file
            // ->attachFromPath('/path/to/documents/privacy.pdf', 'Privacy Policy')
            // optionally you can provide an explicit MIME type (otherwise it's guessed)
            // ->attachFromPath('/path/to/documents/contract.doc', 'Contract', 'application/msword')
            // ->attachFromPath('/path/to/documents/terms-of-use.pdf')
            // optionally you can tell email clients to display a custom name for the file
            // ->attachFromPath('/path/to/documents/privacy.pdf', 'Privacy Policy')
            // optionally you can provide an explicit MIME type (otherwise it's guessed)
            // ->attachFromPath('/path/to/documents/contract.doc', 'Contract', 'application/msword')
            ->subject('Time for Symfony Mailer!')
            ->text('Sending emails is fun again!')
            ->html('<p>See Twig integration for better HTML integration!</p>');
            // https://symfony.com/doc/5.4/mailer.html
            try {
                $mailer->send($email);
                // return new Response(200, );
            } catch (TransportExceptionInterface $e) {
                // some error prevented the email sending; display an
                // error message or try to resend the message
                $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());
                dump($e->getMessage().' '.$e->getFile().' '.$e->getLine());
            }
        // ...
    }
}
