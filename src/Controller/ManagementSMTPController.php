<?php

namespace App\Controller;

use App\Form\ManagementSMTPType;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Repository\UserRepository;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

/**
 * @Route("/rule")
 */
class ManagementSMTPController extends AbstractController
{
    const LOCAL_ENV_FILE = __DIR__.'/../../.env.local';

    protected $tools;
    private LoggerInterface $logger;
    private TranslatorInterface $translator;
    private UserRepository $userRepository;
    private RequestStack $requestStack;


    public function __construct(
        LoggerInterface $logger, 
        TranslatorInterface $translator, 
        UserRepository $userRepository,
        RequestStack $requestStack
    )
    {
        $this->logger = $logger;
        $this->translator = $translator;
        $this->userRepository = $userRepository;
        $this->requestStack = $requestStack;
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

        // we prioritize the api key, so we start by checking if it's present
        $apiKeyFromEnv = $this->checkIfApiKeyInEnv();
        if ($apiKeyFromEnv !== false) {
            $form = $this->getParametersFromApiKey($form, $apiKeyFromEnv);
        } else {
            $mailerDsnFromEnv = $this->checkIfmailerDsnInEnv();
        if ($mailerDsnFromEnv !== false) {
            $form = $this->getParametersFromMailerDsn($form, $mailerDsnFromEnv);
        }
        }
        return $this->render('ManagementSMTP/index.html.twig', ['form' => $form->createView()]);
    }

    public function checkIfmailerDsnInEnv()
    {
        error_log('checkIfmailerDsnInEnv');
        $mailerDsnEnv = false;
        if (file_exists(__DIR__ . '/../../.env.local')) {
            (new Dotenv())->load(__DIR__ . '/../../.env.local');
            $mailerDsnEnv = $_ENV['MAILER_DSN'] ?? false;
            if (!(isset($mailerDsnEnv) && $mailerDsnEnv !== '' && $mailerDsnEnv !== 'null://localhost' && $mailerDsnEnv !== false)) {
                $mailerDsnEnv = false;
            }
        }
        return $mailerDsnEnv;
    }

    public function checkIfApiKeyInEnv()
    {
        error_log('checkIfApiKeyInEnv');
        $apiKeyEnv = false;
        if (file_exists(__DIR__ . '/../../.env.local')) {
            (new Dotenv())->load(__DIR__ . '/../../.env.local');
            $apiKeyEnv = getenv('BREVO_APIKEY');
            if (!(isset($apiKeyEnv) && $apiKeyEnv !== '' && $apiKeyEnv !== false)) {
                // as a fallback, check if the global variable $_ENV['BREVO_APIKEY'] is set
                if (isset($_ENV['BREVO_APIKEY'])) {
                    $apiKeyEnv = $_ENV['BREVO_APIKEY'];
                } else {
                    $apiKeyEnv = false;
                }
            }
        }
        return $apiKeyEnv;
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
                $this->envMailerDsnVsApiKey($form);
            }
            if ($form->isValid() && $form->isSubmitted()) {
                $this->putMailerConfig($form);
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

    // Function to create the mail mailing form.
    // Is called once when you go to the smtp page.
    // Is called twice when you click on Save SMTP config.
    // Is called twice when you click on Send test mail.
    private function createCreateForm(): \Symfony\Component\Form\FormInterface
    {
        error_log('createCreateForm');
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

    // Function to verify whether the Save SMTP config should write an api key into the .env or the mailer dsn
    public function envMailerDsnVsApiKey($form)
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
            if (strpos($line, "BREVO_APIKEY") !== false) {
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
    public function EmptyMailerDsnEnv()
    {
        $envFile = file_get_contents(self::LOCAL_ENV_FILE);
        $linesEnv = explode("\n", $envFile);
        $lineCounter = 0;
        foreach ($linesEnv as $line) {
            if (strpos($line, "MAILER_DSN") !== false) {
                unset($linesEnv[$lineCounter]);
            }
            $lineCounter++;
        }
        $envFileFinal = implode("\n", $linesEnv);
        $clearContentOfDotEnv = fopen(self::LOCAL_ENV_FILE, "w");
        fclose($clearContentOfDotEnv);
        file_put_contents(self::LOCAL_ENV_FILE, $envFileFinal);
    }




    // Function to obtain parameters from the MAILER_DSN in .env and puts it in the form.
    public function getParametersFromMailerDsn($form, $mailerDsnFromEnv)
    {
        $mailerDsnArray = $this->envMailerDsnToArray($mailerDsnFromEnv);
        $form->get('transport')->setData('smtp');
        $form->get('host')->setData($mailerDsnArray[0]);
        $form->get('port')->setData($mailerDsnArray[1]);
        $form->get('auth_mode')->setData($mailerDsnArray[3]);
        $form->get('encryption')->setData($mailerDsnArray[2]);
        $form->get('user')->setData($mailerDsnArray[4]);
        $form->get('password')->setData($mailerDsnArray[5]);
        return $form;
    }

    // Function to obtain parameters from the MAILER_URL in .env and puts it in the form.
    public function getParametersFromApiKey($form, $apiKeyFromEnv)
    {
        $form->get('transport')->setData('sendinblue');
        $form->get('ApiKey')->setData($apiKeyFromEnv);
        return $form;
    }

    // Takes MAILER_DSN and turns it into an array with all parameters
    public function envMailerDsnToArray(string $envString): array
    {
        try {
            // Initialize default values
            $result = [
                '', // host
                '', // port
                '', // encryption
                '', // auth_mode
                '', // username
                ''  // password
            ];
            
            // Remove the transport prefix (smtp://, sendmail://, etc.)
            $withoutTransport = preg_replace('#^[^:]+://#', '', $envString);
            
            // Split credentials and host
            if (strpos($withoutTransport, '@') !== false) {
                list($credentials, $hostPart) = explode('@', $withoutTransport);
                
                // Parse credentials
                if (strpos($credentials, ':') !== false) {
                    list($username, $password) = explode(':', $credentials);
                    $result[4] = urldecode($username); // username
                    $result[5] = urldecode($password); // password
                } else {
                    $result[4] = urldecode($credentials); // username only
                }
            } else {
                $hostPart = $withoutTransport;
            }
            
            // Parse host and port
            if (strpos($hostPart, ':') !== false) {
                list($host, $portAndParams) = explode(':', $hostPart, 2);
                $result[0] = $host; // host
                
                // Split port and parameters
                if (strpos($portAndParams, '?') !== false) {
                    list($port, $params) = explode('?', $portAndParams, 2);
                    $result[1] = $port; // port
                    
                    // Parse parameters
                    $parameters = [];
                    parse_str($params, $parameters);
                    
                    if (isset($parameters['encryption'])) {
                        $result[2] = $parameters['encryption']; // encryption
                    }
                    if (isset($parameters['auth_mode'])) {
                        $result[3] = $parameters['auth_mode']; // auth_mode
                    }
                } else {
                    $result[1] = $portAndParams; // port only
                }
            } else {
                $result[0] = $hostPart; // host only
            }
            
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error parsing DSN: ' . $e->getMessage());
            // Return array with empty values to prevent undefined index errors
            return ['', '', '', '', '', ''];
        }
    }

    /**
     * set data form from files parameter_stml.yml. - this is for Myddleware 2.
     */
    private function putMailerConfig($form)
    {
        $transport = $form->get('transport')->getData();
        if ($transport == 'sendinblue') {
            $transport = 'smtp';
        }

        // Create DSN string
        $dsn = sprintf(
            '%s://%s%s@%s:%d',
            $transport,
            urlencode($form->get('user')->getData()),
            $form->get('password')->getData() ? ':' . urlencode($form->get('password')->getData()) : '',
            $form->get('host')->getData(),
            $form->get('port')->getData()
        );

        if ($form->get('encryption')->getData()) {
            $dsn .= '?encryption=' . $form->get('encryption')->getData();
        }
        if ($form->get('auth_mode')->getData()) {
            $dsn .= ($form->get('encryption')->getData() ? '&' : '?') . 'auth_mode=' . $form->get('auth_mode')->getData();
        }

        // Update .env.local file
        $this->EmptyMailerDsnEnv();
        $envFile = file_get_contents(self::LOCAL_ENV_FILE);
        $envFile .= "\nMAILER_DSN=" . $dsn;
        file_put_contents(self::LOCAL_ENV_FILE, $envFile);
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
            $swiftParams = [];
            $swiftParams['transport'] = $form->get('transport')->getData();
            $swiftParams['host'] = $form->get('host')->getData();
            $swiftParams['port'] = $form->get('port')->getData();
            $swiftParams['encryption'] = $form->get('encryption')->getData();
            $swiftParams['auth_mode'] = $form->get('auth_mode')->getData();
            $swiftParams['user'] = $form->get('user')->getData();
            $swiftParams['password'] = $form->get('password')->getData();
            $swiftParams['spool'] = ['type' => 'memory'];
            $swiftParams['delivery_addresses'] = null;
            $swiftParams['disable_delivery'] = false;

            $mailerUrl = $swiftParams['transport'] . '://';
            if ($swiftParams['user'] !== null && $swiftParams['user'] !== '') {
                $mailerUrl .= $swiftParams['user'];
                if ($swiftParams['password'] !== null && $swiftParams['password'] !== '') {
                    $mailerUrl .= ':' . $swiftParams['password'];
                }
                $mailerUrl .= '@';
            }
            $mailerUrl .= $swiftParams['host'];
            if ($swiftParams['port'] !== null && $swiftParams['port'] !== '') {
                $mailerUrl .= ':' . $swiftParams['port'];
            }
            if ($swiftParams['encryption'] !== null && $swiftParams['encryption'] !== '') {
                $mailerUrl .= '?encryption=' . $swiftParams['encryption'];
            }
            if ($swiftParams['auth_mode'] !== null && $swiftParams['auth_mode'] !== '') {
                $mailerUrl .= '&auth_mode=' . $swiftParams['auth_mode'];
            }

            $this->EmptyMailerDsnEnv();
            $envFile = file_get_contents(self::LOCAL_ENV_FILE);
            $envFile .= "\nMAILER_DSN=" . $mailerUrl;
            file_put_contents(self::LOCAL_ENV_FILE, $envFile);

            $session = $this->requestStack->getSession();
            $session->set('success', [$this->translator->trans('management_smtp.success')]);
        } catch (Exception $e) {
            $session = $this->requestStack->getSession();
            $session->set('error', [$this->translator->trans('management_smtp.error')]);
            $this->logger->error('Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
        }
    }

    /**
     * Retrieve Swiftmailer config & pass it to MAILER_URL env variable in .env.local file.
     */
    protected function parseApiKeyYamlConfigToLocalEnv(array $swiftParams)
    {
        try {
            $apiKey = $swiftParams['ApiKey'];
            $this->EmptyApiKeyEnv();
            $envFile = file_get_contents(self::LOCAL_ENV_FILE);
            $envFile .= "\nBREVO_APIKEY=" . $apiKey;
            file_put_contents(self::LOCAL_ENV_FILE, $envFile);
            $session = $this->requestStack->getSession();
            $session->set('success', [$this->translator->trans('management_smtp.success')]);
        } catch (Exception $e) {
            $session = $this->requestStack->getSession();
            $session->set('error', [$this->translator->trans('management_smtp.error')]);
            $this->logger->error('Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
        }
    }

    /**
     * Send mail for test configuration in the parameters_smtp.yml. - Myddleware 2.
     *
     * @throws Exception
     */
    public function testMailConfiguration($form): bool
    {
        $isApiEmailSent = null; // Initialize to null
        $isRegularEmailSent = null; // Initialize to null

        if ($form->get('transport')->getData() === "sendinblue") {
            $isApiEmailSent = $this->sendinblueSendMailByApiKey($form);
        } else {
            $user_email = null;
            $user = $this->getUser();
            // Ensure we have the correct user type before getting email
            if ($user instanceof \App\Entity\User) { 
                 $user_email = $user->getEmail();
            }
            // Use null-safe operator and check below

            try {
                // Check if a user email is available
                if (empty($user_email)) {
                    throw new Exception('No email address found for the current user to send the test email.');
                }

                // 1. Try to get DSN from environment first
                $dsn = $this->checkIfmailerDsnInEnv();

                // 2. If no DSN in env, build it from the form (allows testing unsaved config)
                if ($dsn === false) {
                    $host = $form->get('host')->getData();
                    $port = $form->get('port')->getData();
                    $user = $form->get('user')->getData();
                    $auth_mode = $form->get('auth_mode')->getData();
                    $encryption = $form->get('encryption')->getData();
                    // IMPORTANT: Get password from form ONLY if building DSN from form
                    $password = $form->get('password')->getData(); 
                    $transportType = $form->get('transport')->getData();

                    // Basic check for essential parts if building from form
                    if (empty($transportType) || empty($host)) {
                         throw new Exception('Transport and Host are required to build a test DSN from the form.');
                    }

                    $dsn = sprintf(
                        '%s://%s%s@%s%s', // Removed port initially
                        $transportType,
                        urlencode((string)$user),
                        $password ? ':' . urlencode($password) : '',
                        $host,
                        $port ? ':' . $port : '' // Add port only if specified
                    );

                    $queryParams = [];
                    if ($encryption) {
                        $queryParams['encryption'] = $encryption;
                    }
                    if ($auth_mode) {
                        $queryParams['auth_mode'] = $auth_mode;
                    }
                    if (!empty($queryParams)) {
                        $dsn .= '?' . http_build_query($queryParams);
                    }
                }

                // Ensure DSN is valid before proceeding
                 if (empty($dsn) || $dsn === false) {
                     throw new Exception('Could not determine a valid Mailer DSN for testing.');
                 }


                // Create the Transport using the determined DSN
                $transport = Transport::fromDsn($dsn);

                // Create the Mailer
                $mailer = new Mailer($transport);

                $textMail = $this->translator->trans('management_smtp_sendmail.textMail') . "\n";
                $textMail .= $this->translator->trans('email_notification.best_regards') . "\n" . $this->translator->trans('email_notification.signature');

                // Determine 'from' address
                $emailFrom = $this->getParameter('email_from'); // Use getParameter which handles default/null
                 if (empty($emailFrom)) {
                     $emailFrom = 'no-reply@myddleware.com'; // Default fallback
                 }


                // Create the email
                $email = (new Email())
                    ->from($emailFrom)
                    ->to($user_email)
                    ->subject($this->translator->trans('management_smtp_sendmail.subject'))
                    ->text($textMail);

                // Send the email
                $mailer->send($email);
                $isRegularEmailSent = true;

            } catch (Exception $e) {
                // Log the detailed error for debugging
                 $this->logger->error('Email Test Error: ' . $e->getMessage() . ' DSN used: ' . (is_string($dsn) ? $dsn : 'N/A') . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine());

                // Set a user-friendly error message using the controller's flash message helper
                $error = $this->translator->trans('management_smtp.sendtestmail_error') . ' (' . $e->getMessage() . ')';
                $this->addFlash('error', $error);
                
                $isRegularEmailSent = false;
            }
        }

        // Flash messages handled here based on results
        if ($isApiEmailSent === true || $isRegularEmailSent === true) {
             $success = $this->translator->trans('email_validation.success');
             $this->addFlash('success', $success);
        } elseif ($isApiEmailSent === false && $form->get('transport')->getData() === "sendinblue") {
             // Error flash for API was likely added in sendinblueSendMailByApiKey or caught above
             // If not, add a generic one:
             // $failed = $this->translator->trans('email_validation.error');
             // $this->addFlash('error', $failed);
        } elseif ($isRegularEmailSent === false && $form->get('transport')->getData() !== "sendinblue") {
            // Error flash for regular SMTP was added in the catch block above
        }


        // Return overall success status
        return ($isApiEmailSent === true || $isRegularEmailSent === true);
    }

    protected function sendinblueSendMailByApiKey($form)
    {
        try {
            $apiKey = $this->checkIfApiKeyInEnv();
            $user_email = $this->getUser()->getEmail();

            // Prepare the email data
            $emailData = [
                'sender' => [
                    'email' => !empty($this->getParameter('email_from')) ? $this->getParameter('email_from') : 'no-reply@myddleware.com'
                ],
                'to' => [
                    [
                        'email' => $user_email
                    ]
                ],
                'subject' => $this->translator->trans('management_smtp_sendmail.subject'),
                'htmlContent' => $this->translator->trans('management_smtp_sendmail.textMail') . "\n" .
                               $this->translator->trans('email_notification.best_regards') . "\n" .
                               $this->translator->trans('email_notification.signature')
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.brevo.com/v3/smtp/email",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($emailData),
                CURLOPT_HTTPHEADER => [
                    "accept: application/json",
                    "api-key: " . $apiKey,
                    "content-type: application/json"
                ],
            ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                $session = $this->requestStack->getSession();
                $session->set('error', [$this->translator->trans('management_smtp.error')]);
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            $this->logger->error('Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
            return false;
        }
    }
}
