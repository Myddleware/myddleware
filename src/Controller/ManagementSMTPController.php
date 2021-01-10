<?php

namespace App\Controller;

use App\Form\ManagementSMTPType;
use Exception;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Swift_Message;
use Swift_SendmailTransport;
use Swift_SmtpTransport;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ManagementSMTPController.
 *
 * @package App\Controller
 *
 * @Route("/rule")
 */
class ManagementSMTPController extends AbstractController
{
    const PATH = './../app/config/public/parameters_smtp.yml';
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
        $form = $this->getData($form);

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

            if ($form->get('submit_test')->isClicked()) {
                $this->testMailConfiguration($form);
            }

            if ($form->isValid()) {
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
        $form = $this->createForm(ManagementSMTPType:: class, null, [
            'action' => $this->generateUrl('management_smtp_create'),
        ]);
        $form->add('submit', SubmitType::class, ['label' => 'management_smtp.submit']);
        $form->add('submit_test', SubmitType::class, ['label' => 'management_smtp.sendtestmail']);

        return $form;
    }

    /***
     * get data for file parameters_smtp.yml
     * @param $form
     * @return mixed
     */
    private function getData($form)
    {
        $value = Yaml::parse(file_get_contents(self::PATH));
        $form->get('transport')->setData($value['parameters']['mailer_transport']);
        $form->get('host')->setData($value['parameters']['mailer_host']);
        $form->get('port')->setData($value['parameters']['mailer_port']);
        $form->get('auth_mode')->setData($value['parameters']['mailer_auth_mode']);
        $form->get('encryption')->setData($value['parameters']['mailer_encryption']);
        $form->get('user')->setData($value['parameters']['mailer_user']);
        $form->get('password')->setData($value['parameters']['mailer_password']);

        return $form;
    }

    /**
     * set data form from files parameter_stml.yml.
     *
     * @param $form
     */
    private function setData($form)
    {
        $array = ['parameters' => [
            'mailer_transport' => $form->get('transport')->getData(),
            'mailer_host' => $form->get('host')->getData(),
            'mailer_port' => $form->get('port')->getData(),
            'mailer_auth_mode' => $form->get('auth_mode')->getData(),
            'mailer_encryption' => $form->get('encryption')->getData(),
            'mailer_user' => $form->get('user')->getData(),
            'mailer_password' => $form->get('password')->getData(),
        ]];
        $yaml = Yaml::dump($array);
        file_put_contents(self::PATH, $yaml);
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
        $host = $form->get('host')->getData();
        $port = $form->get('port')->getData();
        $user = $form->get('user')->getData();
        $auth_mode = $form->get('auth_mode')->getData();
        $encryption = $form->get('encryption')->getData();
        $password = $form->get('password')->getData();
        $user_email = $this->getUser()->getEmail();
        if ('sendmail' == $form->get('transport')->getData()) {
            // Create the Transport for sendmail
            $transport = new Swift_SendmailTransport();
        } else {
            // Create the Transport for gmail and smtp
            $transport = new Swift_SmtpTransport($host, $port);
            if (!empty($user)) {
                $transport->setUsername($user);
                $transport->setPassword($password);
            }
            if (!empty($auth_mode)) {
                $transport->setAuthMode($auth_mode);
            }
            if (!empty($encryption)) {
                $transport->setEncryption($encryption);
            }
        }

        // Create the Mailer using your created Transport
        $mailer = new Swift_Mailer($transport);
        $subject = $this->translator->trans('management_smtp_sendmail.subject');
        try {
            // Check that we have at least one email address
            if (empty($user_email)) {
                throw new Exception('No email address found to send notification. You should have at leas one admin user with an email address.');
            }
            $textMail = $this->translator->trans('management_smtp_sendmail.textMail').chr(10);
            $textMail .= $this->translator->trans('email_notification.best_regards').chr(10).$this->translator->trans('email_notification0signature');
            $message = Swift_Message::newInstance($subject);
            $message
                ->setFrom((!empty($this->getParameter('email_from')) ? $this->getParameter('email_from') : 'no-reply@myddleware.com'))
                ->setBody($textMail);
            $message->setTo($user_email);
            $send = $mailer->send($message);
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
}
