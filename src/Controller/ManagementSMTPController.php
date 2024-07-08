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
use App\Repository\UserRepository;

use Swift_Message;

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
    private UserRepository $userRepository;


    public function __construct(LoggerInterface $logger, TranslatorInterface $translator, UserRepository $userRepository)
    {
        $this->logger = $logger;
        $this->translator = $translator;
        $this->userRepository = $userRepository;
    }

    // Function that loads the main smtp page, check for the api key and the mailer url when the user loads the page. 
    // Adds the authorized form fields to the page but not the sensitive content, ie password and api key.
    // If none of them are present then the default form is loaded.

    /**
     * @Route("/managementsmtp", name="management_smtp_index")
     */
    public function index(): Response
    {
        $form = $this->createCreateForm();
        $mailerUrlFromEnv = $this->checkIfmailerUrlInEnv();
        if ($mailerUrlFromEnv !== false) {
            $form = $this->getParametersFromMailerUrl($form, $mailerUrlFromEnv);
        } else {
            $form = $this->getParametersFromSwiftmailerYaml($form);
        }
        return $this->render('ManagementSMTP/index.html.twig', ['form' => $form->createView()]);
    }

    // Function that creates a configuration for the smtp system. Creates a form and test the mail configuration.
    // Is called if you click on the Save SMTP config button OR the Send test mail button.

    /**
     * @Route("/managementsmtp/readConfig", name="management_smtp_create")
     */
    public function createConfig(Request $request)
    {
        try {
            $form = $this->createCreateForm();
            $form->handleRequest($request);
            if ($form->get('submit_test') === $form->getClickedButton()) {
                $isMailSent = $this->testMailConfiguration($form);
            } else {
                $this->envMailerUrlVsApiKey($form);
            }
            if ($form->isValid() && $form->isSubmitted()) {
                $this->putParamsInSwiftMailerYaml($form);
                if (!empty($isMailSent)) {
                    if ($isMailSent === true) {
                        $success = $this->translator->trans('email_validation.success');
                        $this->addFlash('success', $success);
                    } else if ($isMailSent === false) {
                        $failed = $this->translator->trans('email_validation.error');
                        $this->addFlash('error', $failed);
                    }
                }
                return $this->redirect($this->generateUrl('management_smtp_index'));
            }
        } catch (ParseException $exception) {
            printf('Unable to parse the YAML string: %s', $exception->getMessage());
        }
        return $this->render('ManagementSMTP/index.html.twig', ['form' => $form->createView()]);
    }

    // Function to verify whether the Save SMTP config should write an api key into the .env or the mailer url
    public function envMailerUrlVsApiKey($form)
    {
        if ($form->get('transport')->getData() === 'sendinblue') {
            if ($this->checkIfApiKeyInEnv() !== $form->get('ApiKey')->getData()) {
                $this->EmptyApiKeyEnv();
                $this->putApiKeyInDotEnv($form);
            }
        } else {
            $this->parseYamlConfigToLocalEnv($form);
        }
    }

    // Function to remove the api key from the .env, it actually clears the .env and refills it with everything but the api key
    public function EmptyApiKeyEnv()
    {
        // Finds the api key and removes it
        $envFile = file_get_contents(self::LOCAL_ENV_FILE);
        $linesEnv = explode("\n", $envFile);
        $lineCounter = 0;
        foreach ($linesEnv as $line) {
            if (strpos($line, "SENDINBLUE_APIKEY") !== false) {
                unset($linesEnv[$lineCounter]);
            }
            $lineCounter++;
        }
        $envFileFinal = implode("\n", $linesEnv);
        // Clears the .env
        $clearContentOfDotEnv = fopen(self::LOCAL_ENV_FILE, "w");
        fclose($clearContentOfDotEnv);
        // Refills the content with everythintg but the api key
        file_put_contents(self::LOCAL_ENV_FILE, $envFileFinal);
    }
    // Function to remove the api key from the .env, it actually clears the .env and refills it with everything but the api key
    public function EmptyMailerUrlEnv()
    {
        // Finds the api key and removes it
        $envFile = file_get_contents(self::LOCAL_ENV_FILE);
        $linesEnv = explode("\n", $envFile);
        $lineCounter = 0;
        foreach ($linesEnv as $line) {
            if (strpos($line, "MAILER_URL") !== false) {
                unset($linesEnv[$lineCounter]);
            }
            $lineCounter++;
        }
        $envFileFinal = implode("\n", $linesEnv);
        // Clears the .env
        $clearContentOfDotEnv = fopen(self::LOCAL_ENV_FILE, "w");
        fclose($clearContentOfDotEnv);
        // Refills the content with everythintg but the api key
        file_put_contents(self::LOCAL_ENV_FILE, $envFileFinal);
    }
    // Function to create the mail mailing form.
    // Is called once when you go to the smtp page.
    // Is called twice when you click on Save SMTP config.
    // Is called twice when you click on Send test mail.
    private function createCreateForm(): \Symfony\Component\Form\FormInterface
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

    // Function to obtain parameters from the yaml file and puts it in the form.
    // Is called once when you go to the smtp page.
    // Is called once when you click on Save SMTP config.
    // Is called once when you click on Send test mail.

    /***
     * get data for file parameters_smtp.yml - this is for Myddleware 2
     */
    private function getParametersFromSwiftmailerYaml($form)
    {
        if (file_exists(__DIR__ . '/../../.env.local')) {
            (new Dotenv())->load(__DIR__ . '/../../.env.local');
        }
        $mailerUrlEnv = getenv('MAILER_URL');
        if (isset($mailerUrlEnv) && $mailerUrlEnv !== '' && $mailerUrlEnv !== 'null://localhost' && $mailerUrlEnv !== false) {

            $mailerUrlArray = $this->envMailerUrlToArray($mailerUrlEnv);
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


    // Function to obtain parameters from the MAILER_URL in .env and puts it in the form.
    public function getParametersFromMailerUrl($form, $mailerUrlFromEnv)
    {
        $mailerUrlArray = $this->envMailerUrlToArray($mailerUrlFromEnv);
        $form->get('transport')->setData('smtp');
        $form->get('host')->setData($mailerUrlArray[0]);
        $form->get('port')->setData($mailerUrlArray[1]);
        $form->get('auth_mode')->setData($mailerUrlArray[3]);
        $form->get('encryption')->setData($mailerUrlArray[2]);
        $form->get('user')->setData($mailerUrlArray[4]);
        $form->get('password')->setData($mailerUrlArray[5]);
        return $form;
    }

    // Function to obtain parameters from the MAILER_URL in .env and puts it in the form.
    public function getParametersFromApiKey($form, $apiKeyFromEnv)
    {
        $form->get('transport')->setData('sendinblue');
        $form->get('ApiKey')->setData($apiKeyFromEnv);
        return $form;
    }

    // Takes MAILER_URL and turns it into an array with all parameters
    public function envMailerUrlToArray(string $envString): array
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
    private function putParamsInSwiftMailerYaml($form)
    {
        $transport = $form->get('transport')->getData();
        if ($transport == 'sendinblue') {
            $transport = 'smtp';
        }
        $array = ['swiftmailer' => [
            'transport' => $transport,
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
            'transport' => $transport,
            'host' => $form->get('host')->getData(),
            'port' => $form->get('port')->getData(),
            'auth_mode' => $form->get('auth_mode')->getData(),
            'encryption' => $form->get('encryption')->getData(),
            'username' => $form->get('user')->getData(),
            'password' => $form->get('password')->getData(),
        ]];
        $yamlNotification = Yaml::dump($arrayNotification);
        file_put_contents(self::PATHNOTIFICATION, $yamlNotification);
    }

    // If there is no api key in the .env, takes data from swiftmailer and puts it in the .env as MAILER_URL
    public function putApiKeyInDotEnv($form)
    {
        $array = ['swiftmailer' => [
            'transport' => $form->get('transport')->getData(),
            'ApiKey' => $form->get('ApiKey')->getData(),
        ]];
        if ((!isset($apiKeyEnv) || $apiKeyEnv === '' || $apiKeyEnv === false)) {
            $this->parseApiKeyYamlConfigToLocalEnv($array['swiftmailer']);
        }
    }


    /**
     * Retrieve Swiftmailer config & pass it to MAILER_URL env variable in .env.local file.
     */
    protected function parseYamlConfigToLocalEnv($form)
    {
        try {
            $swiftParams = [
                'transport' => $form->get('transport')->getData(),
                'host' => $form->get('host')->getData(),
                'port' => $form->get('port')->getData(),
                'auth_mode' => $form->get('auth_mode')->getData(),
                'encryption' => $form->get('encryption')->getData(),
                'user' => $form->get('user')->getData(),
                'password' => $form->get('password')->getData(),
            ];

            $transport = isset($swiftParams['transport']) ? $swiftParams['transport'] : null;
            $host = isset($swiftParams['host']) ? $swiftParams['host'] : null;
            $port = isset($swiftParams['port']) ? $swiftParams['port'] : null;
            $auth_mode = isset($swiftParams['auth_mode']) ? $swiftParams['auth_mode'] : null;
            $encryption = isset($swiftParams['encryption']) ? $swiftParams['encryption'] : null;
            $user = isset($swiftParams['user']) ? $swiftParams['user'] : null;
            $password = isset($swiftParams['password']) ? $swiftParams['password'] : null;
            $mailerUrl = "MAILER_URL=$transport://$host:$port?encryption=$encryption&auth_mode=$auth_mode&username=$user&password=$password";

            // If the mailer url is already present and identical, we do not add the line
            $mailerUrlWithoutTitle = str_replace("MAILER_URL=", "", $mailerUrl);
            $mailerInEnv = $this->checkIfmailerUrlInEnv();

            // Put the content if the mailer is not present in the .env
            if ($mailerInEnv === false) {
                $currentContent = file_get_contents(self::LOCAL_ENV_FILE);
                // Check if the last character(s) is/are PHP_EOL, if not, append it
                if (substr($currentContent, -strlen(PHP_EOL)) !== PHP_EOL) {
                    file_put_contents(self::LOCAL_ENV_FILE, PHP_EOL, FILE_APPEND | LOCK_EX);
                }
                file_put_contents(self::LOCAL_ENV_FILE, $mailerUrl . PHP_EOL, FILE_APPEND | LOCK_EX);
            }

            // Put the content if there is already a mailer url but it is different from the current one
            if ($mailerInEnv !== false && $mailerInEnv !== $mailerUrlWithoutTitle) {
                $this->EmptyMailerUrlEnv();
                $currentContent = file_get_contents(self::LOCAL_ENV_FILE);
                // Check if the last character(s) is/are PHP_EOL, if not, append it
                if (substr($currentContent, -strlen(PHP_EOL)) !== PHP_EOL) {
                    file_put_contents(self::LOCAL_ENV_FILE, PHP_EOL, FILE_APPEND | LOCK_EX);
                }
                file_put_contents(self::LOCAL_ENV_FILE, $mailerUrl . PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        } catch (Exception $e) {
            $this->logger->error("Unable to write MAILER_URL in .env.local file : $e->getMessage() on file $e->getFile() line $e->getLine()");
            $session = new Session();
            $session->set('error', [$e]);
        }
    }

    /**
     * Retrieve Swiftmailer config & pass it to MAILER_URL env variable in .env.local file.
     */
    protected function parseApiKeyYamlConfigToLocalEnv(array $swiftParams)
    {
        try {
            $apiKey = isset($swiftParams['ApiKey']) ? $swiftParams['ApiKey'] : null;
            $apiKeyEnv = "SENDINBLUE_APIKEY=$apiKey";

            // If the api key is already present and identical, we do not add the line
            $apiKeyWithoutTitle = str_replace("SENDINBLUE_APIKEY=", "", $apiKeyEnv);
            $apiKeyInEnv = $this->checkIfApiKeyInEnv();
            if ($apiKeyInEnv === false || $apiKeyInEnv !== $apiKeyWithoutTitle) {
                file_put_contents(self::LOCAL_ENV_FILE, $apiKeyEnv . PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        } catch (Exception $e) {
            $this->logger->error("Unable to write SENDINBLUE_APIKEY in .env.local file : $e->getMessage() on file $e->getFile() line $e->getLine()");
            $session = new Session();
            $session->set('error', [$e]);
        }
    }

    /**
     * Send mail for test configuration in the parameters_smtp.yml. - Myddleware 2.
     *
     * @throws Exception
     */
    public function testMailConfiguration($form): bool
    {
        if ($form->get('transport')->getData() === "sendinblue") {
            $isApiEmailSent = $this->sendinblueSendMailByApiKey($form);
        }else {
            // Standard email
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
                $textMail = $this->translator->trans('management_smtp_sendmail.textMail') . chr(10);
                $textMail .= $this->translator->trans('email_notification.best_regards') . chr(10) . $this->translator->trans('email_notification.signature');
                $message = (new \Swift_Message($subject));
                $message
                    ->setFrom((!empty($this->getParameter('email_from')) ? $this->getParameter('email_from') : 'no-reply@myddleware.com'))
                    ->setBody($textMail);
                $message->setTo($user_email);
                $send = $mailer->send($message);
                $this->resetSwiftmailerYaml();
                if (!$send) {
                    $this->logger->error('Failed to send email : ' . $textMail . ' to ' . $user_email);
                    throw new Exception('Failed to send email : ' . $textMail . ' to ' . $user_email);
                } else {
                    $isRegularEmailSent = true;
                }
            } catch (Exception $e) {
                $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
                $session = new Session();
                $session->set('error', [$error]);
            }
        }


    // Error message if the api mail didn't work    
    if (isset($isApiEmailSent)) {
        if ($isApiEmailSent === false) {
            $failed = $this->translator->trans('email_validation.error');
            $this->addFlash('error', $failed);
        }
    }

    if (isset($isRegularEmailSent)) {
        if ($isRegularEmailSent === false) {
            $failed = $this->translator->trans('email_validation.error');
            $this->addFlash('error', $failed);
        }
    }



        // Adds a return value to the function to allow the index to display the success and error message.
        $isFinalEmailSent = false;
        if (!empty($isApiEmailSent)) {
            if ($isApiEmailSent === true) {
                $isFinalEmailSent = true;
            }
        }
        if (!empty($isRegularEmailSent)) {
            if ($isRegularEmailSent === true) {
                $isFinalEmailSent = true;
            }
        }
        return $isFinalEmailSent;
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
            ->setBody('You should see me from the profiler!');
        $mailer->send($message);
    }

    protected function sendinblueSendMailByApiKey($textMail)
    {
        // Get the email adresses of all ADMIN
        $this->setEmailAddresses();
        // Check that we have at least one email address
        if (empty($this->emailAddresses)) {
            throw new Exception('No email address found to send notification. You should have at least one admin user with an email address.');
        }
        $apiKey = $textMail->get('ApiKey')->getData();
        $this->sendinblue = \SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
            $apiInstance = new \SendinBlue\Client\Api\TransactionalEmailsApi(new \GuzzleHttp\Client(), $this->sendinblue);
            $sendSmtpEmail = new \SendinBlue\Client\Model\SendSmtpEmail(); // \SendinBlue\Client\Model\SendSmtpEmail | Values to send a transactional email
            foreach ($this->emailAddresses as $emailAddress) {
                $sendSmtpEmailTo[] = array('email' => $emailAddress);
            }
            $sendSmtpEmail['to'] = $sendSmtpEmailTo;
            $sendSmtpEmail['subject'] = $this->translator->trans('email_sendinblue.subject');
            $sendSmtpEmail['htmlContent'] = $this->translator->trans('email_sendinblue.content');
            $sendSmtpEmail['sender'] = array('email' => $this->configParams['email_from'] ?? 'no-reply@myddleware.com');

            try {
                $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
            } catch (Exception $e) {
                return false;
            }

        
        return true;
    }

    // Add every admin email in the notification list
    protected function setEmailAddresses()
    {
        $users = $this->userRepository->findEmailsToNotification();
        foreach ($users as $user) {
            $this->emailAddresses[] = $user['email'];
        }
    }

    public function checkIfmailerUrlInEnv()
    {
        $mailerUrlEnv = false;
        if (file_exists(__DIR__ . '/../../.env.local')) {
            (new Dotenv())->load(__DIR__ . '/../../.env.local');
            $mailerUrlEnv = $_ENV['MAILER_URL'];
            if (!(isset($mailerUrlEnv) && $mailerUrlEnv !== '' && $mailerUrlEnv !== 'null://localhost' && $mailerUrlEnv !== false)) {
                $mailerUrlEnv = false;
            }
        }
        return $mailerUrlEnv;
    }

    public function checkIfApiKeyInEnv()
    {
        $apiKeyEnv = false;
        if (file_exists(__DIR__ . '/../../.env.local')) {
            (new Dotenv())->load(__DIR__ . '/../../.env.local');
            $apiKeyEnv = getenv('SENDINBLUE_APIKEY');
            if (!(isset($apiKeyEnv) && $apiKeyEnv !== '' && $apiKeyEnv !== false)) {
                $apiKeyEnv = false;
            }
        }
        return $apiKeyEnv;
    }

    /**
     * set data form from files parameter_stml.yml. - this is for Myddleware 2.
     */
    private function resetSwiftmailerYaml()
    {
        $array = ['swiftmailer' => [
            'transport' => "gmail",
            'host' => "smtp.gmail.com",
            'port' => 465,
            'auth_mode' => "login",
            'encryption' => "ssl",
            'user' => null,
            'password' => null,
        ]];
        $yaml = Yaml::dump($array);
        file_put_contents(self::PATH, $yaml);

        // Exports config data to notification config file
        $arrayNotification = ['swiftmailer' => [
            'transport' => "gmail",
            'host' => "smtp.gmail.com",
            'port' => 465,
            'auth_mode' => "login",
            'encryption' => "ssl",
            'username' => null,
            'password' => null,
        ]];
        $yamlNotification = Yaml::dump($arrayNotification);
        file_put_contents(self::PATHNOTIFICATION, $yamlNotification);
    }
}
