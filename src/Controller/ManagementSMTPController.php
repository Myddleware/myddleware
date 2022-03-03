<?php

namespace App\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use App\Form\ManagementSMTPType;
use Symfony\Component\Form\Form;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/rule")
 */
class ManagementSMTPController extends AbstractController
{
    public const LOCAL_ENV_FILE = __DIR__.'/../../.env.local';

    protected $tools;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->logger = $logger;
        $this->translator = $translator;
    }

    /**
     * @return Response
     *
     * @Route("/managementsmtp", name="management_smtp_index")
     */
    public function indexAction()
    {
        $form = $this->createCreateForm();

        return $this->render('ManagementSMTP/index.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Read config stmp.
     *
     * @Route("/managementsmtp/readConfig", name="management_smtp_create")
     */
    public function createConfigAction(Request $request)
    {
        try {
            $form = $this->createCreateForm();
            $form->handleRequest($request);

            if ($form->get('submit_test') === $form->getClickedButton()) {
                $this->testMailConfiguration($form);
            }

            if ($form->isValid() && $form->isSubmitted()) {
                $this->setData($form);

                return $this->redirect($this->generateUrl('management_smtp_index'));
            }
        } catch (ParseException $exception) {
            printf('Unable to parse the YAML string: %s', $exception->getMessage());
        }

        return $this->render('ManagementSMTP/index.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Creates a form to create a JobScheduler entity.
     *
     * @return Form The form
     */
    private function createCreateForm()
    {
        $form = $this->createForm(ManagementSMTPType::class, null, [
            'action' => $this->generateUrl('management_smtp_create'),
        ]);
        $form->add('submit', SubmitType::class, [
                    'label' => 'management_smtp.submit',
                    'attr' => [
                        'class' => 'btn btn-outline-primary mb-2',
                        ],
                    ]);
        $form->add('submit_test', SubmitType::class, [
            'label' => 'management_smtp.sendtestmail',
            'attr' => [
                'class' => 'btn btn-outline-primary mb-2',
                ],
            ]);

        return $form;
    }


    /**
     * Send mail for test configuration in the parameters_smtp.yml.
     *
     * @return RedirectResponse
     *
     * @throws Exception
     *
     * @param mixed $form
     */
    public function testMailConfiguration($form)
    {
        // $host = $form->get('host')->getData();
        // $port = $form->get('port')->getData();
        // $user = $form->get('user')->getData();
        // $auth_mode = $form->get('auth_mode')->getData();
        // $encryption = $form->get('encryption')->getData();
        // $password = $form->get('password')->getData();
        // $user_email = $this->getUser()->getEmail();
        // if ('sendmail' == $form->get('transport')->getData()) {
        //     // Create the Transport for sendmail
        //     $transport = new Swift_SendmailTransport();
        // } else {
        //     // Create the Transport for gmail and smtp
        //     $transport = new Swift_SmtpTransport($host, $port);
        //     if (!empty($user)) {
        //         $transport->setUsername($user);
        //         $transport->setPassword($password);
        //     }
        //     if (!empty($auth_mode)) {
        //         $transport->setAuthMode($auth_mode);
        //     }
        //     if (!empty($encryption)) {
        //         $transport->setEncryption($encryption);
        //     }
        // }

        // Create the Mailer using your created Transport
        // $mailer = new Mailer($transport);
        $subject = $this->translator->trans('management_smtp_sendmail.subject');
        try {
            // Check that we have at least one email address
            if (empty($user_email)) {
                throw new Exception('No email address found to send notification. You should have at least one admin user with an email address.');
            }
            $textMail = $this->translator->trans('management_smtp_sendmail.textMail').chr(10);
            $textMail .= $this->translator->trans('email_notification.best_regards').chr(10).$this->translator->trans('email_notification.signature');
            $message = (new \Swift_Message($subject));
            $message
                ->setFrom((!empty($this->getParameter('email_from')) ? $this->getParameter('email_from') : 'no-reply@myddleware.com'))
                ->setBody($textMail);
            $message->setTo($user_email);
            // $send = $mailer->send($message);
            if (!$send) {
                $this->logger->error('Failed to send email : '.$textMail.' to '.$user_email);
                throw new Exception('Failed to send email : '.$textMail.' to '.$user_email);
            }
        } catch (Exception $e) {
            $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $session = new Session();
            $session->set('error', [$error]);
        }
    }

    /**
     * TODO: refactor so that the sendmail code from the above function
     *  is decoupled from the config part.
     *
     * @return void
     */
    public function sendEmail($name, Mailer $mailer)
    {
        $message = (new \Swift_Message('Hello Email'))
            ->setFrom('send@example.com')
            ->setTo('recipient@example.com')
            ->setBody('You should see me from the profiler!')
        ;

        $mailer->send($message);

        // ...
    }
}
