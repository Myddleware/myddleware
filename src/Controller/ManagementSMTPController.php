<?php

namespace App\Controller;

use App\Form\ManagementSMTPType;
use Exception;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Swift_SendmailTransport;
use Swift_SmtpTransport;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/rule")
 */
class ManagementSMTPController extends AbstractController
{
    const PATH = './../config/swiftmailer.yaml';
    const LOCAL_ENV_FILE = __DIR__.'/../../.env.local';
    const PATHNOTIFICATION = './../config/packages/swiftmailer.yaml';


    protected $tools;
    private LoggerInterface $logger;
    private TranslatorInterface $translator;

    public function __construct(LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->logger = $logger;
        $this->translator = $translator;
    }

    /**
     * @Route("/managementsmtp", name="management_smtp_index")
     */
    public function index(): Response
    {
        $form = $this->createCreateForm();
        $form = $this->getData($form);

        return $this->render('ManagementSMTP/index.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/managementsmtp/readConfig", name="management_smtp_create")
     */
    public function createConfig(Request $request)
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

    private function createCreateForm(): \Symfony\Component\Form\FormInterface
    {
        $form = $this->createForm(ManagementSMTPType:: class, null, [
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

    /***
     * get data for file parameters_smtp.yml - this is for Myddleware 2
     */
    private function getData($form)
    {
        if (file_exists(__DIR__ . '/../../.env.local')) {
            (new Dotenv())->load(__DIR__ . '/../../.env.local');
        }
        $mailerUrlEnv = getenv('MAILER_URL');
        if (isset($mailerUrlEnv) && $mailerUrlEnv !== '' && $mailerUrlEnv !== 'null://localhost' && $mailerUrlEnv !== false) {

            $mailerUrlArray = $this->splitEnvString($mailerUrlEnv);
            $form->get('transport')->setData('smtp');
            $form->get('host')->setData($mailerUrlArray[0]);
            $form->get('port')->setData($mailerUrlArray[1]);
            $form->get('auth_mode')->setData($mailerUrlArray[3]);
            $form->get('encryption')->setData($mailerUrlArray[2]);
            $form->get('user')->setData($mailerUrlArray[4]);
            $form->get('password')->setData($mailerUrlArray[5]);
        } else {
        $value = Yaml::parse(file_get_contents(self::PATH));
        $form->get('transport')->setData($value['swiftmailer']['transport']);
        $form->get('host')->setData($value['swiftmailer']['host']);
        $form->get('port')->setData($value['swiftmailer']['port']);
        $form->get('auth_mode')->setData($value['swiftmailer']['auth_mode']);
        $form->get('encryption')->setData($value['swiftmailer']['encryption']);
        $form->get('user')->setData($value['swiftmailer']['user']);
        $form->get('password')->setData($value['swiftmailer']['password']);
        }

        return $form;
    }

    public function splitEnvString(string $envString): array
    {
        $delimiters = ['?', '?encryption=', '&auth_mode=', '&username=', '&password='];
        $envStringQuestionMarks = str_replace($delimiters, $delimiters[0], $envString);
        $envArrayBeforeSplitHostPort = explode($delimiters[0], $envStringQuestionMarks);
        $noTsplitHostPort = $envArrayBeforeSplitHostPort[0];
        $splitHostPort = explode(':', $noTsplitHostPort);
        $port = $splitHostPort[2];
        $hostWithSlashes = $splitHostPort[1];
        $hostWithoutSlashes = substr($hostWithSlashes, 2);
        $hostAndPort = [$hostWithoutSlashes, $port];

        $removeFirstElement = array_shift($envArrayBeforeSplitHostPort);
        $envArray = array_merge($hostAndPort, $envArrayBeforeSplitHostPort);


        return $envArray;
    }

    /**
     * set data form from files parameter_stml.yml. - this is for Myddleware 2.
     */
    private function setData($form)
    {
        $array = ['swiftmailer' => [
            'transport' => $form->get('transport')->getData(),
            'host' => $form->get('host')->getData(),
            'port' => $form->get('port')->getData(),
            'auth_mode' => $form->get('auth_mode')->getData(),
            'encryption' => $form->get('encryption')->getData(),
            'user' => $form->get('user')->getData(),
            'password' => $form->get('password')->getData(),
        ]];
        $yaml = Yaml::dump($array);
        file_put_contents(self::PATH, $yaml);

        // Exports config data to notification config file
        $arrayNotification = ['swiftmailer' => [
            'transport' => $form->get('transport')->getData(),
            'host' => $form->get('host')->getData(),
            'port' => $form->get('port')->getData(),
            'auth_mode' => $form->get('auth_mode')->getData(),
            'encryption' => $form->get('encryption')->getData(),
            'username' => $form->get('user')->getData(),
            'password' => $form->get('password')->getData(),
        ]];
        $yamlNotification = Yaml::dump($arrayNotification);
        file_put_contents(self::PATHNOTIFICATION, $yamlNotification);

        $this->parseYamlConfigToLocalEnv($array['swiftmailer']);
    }

    /**
     * Retrieve Swiftmailer config & pass it to MAILER_URL env variable in .env.local file.
     */
    protected function parseYamlConfigToLocalEnv(array $swiftParams)
    {
        try {
            $transport = isset($swiftParams['transport']) ? $swiftParams['transport'] : null;
            $host = isset($swiftParams['host']) ? $swiftParams['host'] : null;
            $port = isset($swiftParams['port']) ? $swiftParams['port'] : null;
            $auth_mode = isset($swiftParams['auth_mode']) ? $swiftParams['auth_mode'] : null;
            $encryption = isset($swiftParams['encryption']) ? $swiftParams['encryption'] : null;
            $user = isset($swiftParams['user']) ? $swiftParams['user'] : null;
            $password = isset($swiftParams['password']) ? $swiftParams['password'] : null;
            $mailerUrl = "MAILER_URL=$transport://$host:$port?encryption=$encryption&auth_mode=$auth_mode&username=$user&password=$password";
            // for now we send it at the end of the file but if the operation is repeated multiple times, it will write multiple lines
            // TODO: find a way to check whether the variable is already set & if so overwrite it
            file_put_contents(self::LOCAL_ENV_FILE, $mailerUrl.PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            $this->logger->error("Unable to write MAILER_URL in .env.local file : $e->getMessage() on file $e->getFile() line $e->getLine()");
            $session = new Session();
            $session->set('error', [$e]);
        }
    }

    /**
     * Send mail for test configuration in the parameters_smtp.yml. - Myddleware 2.
     *
     * @throws Exception
     */
    public function testMailConfiguration($form): void
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
                throw new Exception('No email address found to send notification. You should have at least one admin user with an email address.');
            }
            $textMail = $this->translator->trans('management_smtp_sendmail.textMail').chr(10);
            $textMail .= $this->translator->trans('email_notification.best_regards').chr(10).$this->translator->trans('email_notification.signature');
            $message = (new \Swift_Message($subject));
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

    /**
     * TODO: refactor so that the sendmail code from the above function
     *  is decoupled from the config part.
     *
     * @return void
     */
    public function sendEmail($name, Swift_Mailer $mailer)
    {
        $message = (new \Swift_Message('Hello Email'))
            ->setFrom('send@example.com')
            ->setTo('recipient@example.com')
            ->setBody('You should see me from the profiler!')
        ;
        $mailer->send($message);
    }
}
