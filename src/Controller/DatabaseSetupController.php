<?php

declare(strict_types=1);

namespace App\Controller;



use App\Entity\Config;
use Psr\Log\LoggerInterface;
use App\Entity\DatabaseParameter;
use App\Form\DatabaseSetupFormType;
use App\Repository\ConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception as DBALException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;


class DatabaseSetupController extends AbstractController
{
    private $connectionSuccessMessage;
    private $connectionFailedMessage;
    private $configRepository;
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $entityManager;

    public function __construct(ConfigRepository $configRepository, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->configRepository = $configRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/database_setup', name: 'app_database_setup')]
    public function index(Request $request): Response
    {
        try {
            $submitted = false;
            $database = new DatabaseParameter();

            // force user to change the default Symfony secret for security
            if ('Thissecretisnotsosecretchangeit' === $database->getSecret() || null === $database->getSecret() || empty($database)) {
                $database->setSecret(md5(rand(0, 10000) . date('YmdHis') . 'myddleware'));
            }
            $form = $this->createForm(DatabaseSetupFormType::class, $database);
            $form->handleRequest($request);

            // send database parameters to .env.local
            if ($form->isSubmitted() && $form->isValid()) {
                $envLocal = __DIR__ . '/../../.env.local';
                // we edit the database connection parameters with form input
                $newUrl = 'DATABASE_URL="mysql://' . $database->getUser() . ':' . $database->getPassword() . '@' . $database->getHost() . ':' . $database->getPort() . '/' . $database->getName() . '?serverVersion=5.7"';
                $prodString = 'APP_ENV=prod' . PHP_EOL . 'APP_DEBUG=false';
                // add Symfony secret to .env.local
                $appSecret = 'APP_SECRET=' . $database->getSecret();
                // write new URL into the .env.local file (EOL ensures it's written on a new line)
                $ok = file_put_contents($envLocal, PHP_EOL . $newUrl . PHP_EOL . $prodString . PHP_EOL . $appSecret, LOCK_EX);
                // allow to proceed to next step
                $submitted = true;
            }
            return $this->render('install_setup/database_setup.html.twig', [
                'form' => $form->createView(),
                'submitted' => $submitted,
            ]);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $return['error'] = $e->getMessage();
            if ($e instanceof ConnectionException | $e instanceof TableNotFoundException) {
                $submitted = false;

                // get all parameters from config/parameters.yml and push them in a new instance of DatabaseParameters()
                $database = new DatabaseParameter();
                $parameters = ['database_driver', 'database_host', 'database_port', 'database_name', 'database_user', 'database_password', 'secret'];
                foreach ($parameters as $parameter) {
                    try {
                        $value = $this->getParameter($parameter);
                        if (!empty($value)) {
                            switch ($parameter) {
                                case 'database_driver':
                                    $database->setDriver($value);
                                    break;
                                case 'database_host':
                                    $database->setHost($value);
                                    break;
                                case 'database_port':
                                    $database->setPort($value);
                                    break;
                                case 'database_name':
                                    $database->setName($value);
                                    break;
                                case 'database_user':
                                    $database->setUser($value);
                                    break;
                                case 'database_password':
                                    $database->setPassword($value);
                                    break;
                                case 'secret':
                                    $database->setSecret($value);
                                    break;
                                default:
                                    break;
                            }
                        }
                    } catch (Exception $e) {
                        $this->logger->error($e->getMessage());
                        $return['error'] = $e->getMessage();
                        if ($e instanceof InvalidArgumentException) {
                            // force user to change the default Symfony secret for security
                            if ('Thissecretisnotsosecretchangeit' === $database->getSecret() || null === $database->getSecret() || empty($database)) {
                                $database->setSecret(md5(rand(0, 10000) . date('YmdHis') . 'myddleware'));
                            }
                            $form = $this->createForm(DatabaseSetupFormType::class, $database);
                            $form->handleRequest($request);

                            // send database parameters to .env.local
                            if ($form->isSubmitted() && $form->isValid()) {
                                $envLocal = __DIR__ . '/../../.env.local';
                                // we edit the database connection parameters with form input
                                $newUrl = 'DATABASE_URL="mysql://' . $database->getUser() . ':' . $database->getPassword() . '@' . $database->getHost() . ':' . $database->getPort() . '/' . $database->getName() . '?serverVersion=5.7"';
                                $prodString = 'APP_ENV=dev' . PHP_EOL . 'APP_DEBUG=true';
                                $appSecret = 'APP_SECRET=' . $database->getSecret();
                                // write new URL into the .env.local file (EOL ensures it's written on a new line)
                                file_put_contents($envLocal, PHP_EOL . $newUrl . PHP_EOL . $prodString . PHP_EOL . $appSecret, LOCK_EX);

                                // allow to proceed to next step
                                $submitted = true;
                            }

                            // if there's already a database in .env.local but it isn't yet linked to database, then allow access to form
                            return $this->render('install_setup/database_setup.html.twig', [
                                'form' => $form->createView(),
                                'submitted' => $submitted,
                            ]);
                        } else {
                            return $this->redirectToRoute('login');
                        }
                    }
                }
            }
        }
        //if there's already a database in .env.local but it isn't yet linked to database, then allow access to form
        return $this->redirectToRoute('login');
    }

    #[Route('/database_connection', name: 'app_database_connection')]
    public function connectDatabase(Request $request, KernelInterface $kernel, TranslatorInterface $translator): Response
    {
        try {
            $env = $kernel->getEnvironment();
            $application = new Application($kernel);
            $application->setAutoExit(false);
            $config = null;

            // we execute Doctrine console commands to test the connection to the database
            $input = new ArrayInput([
                'command' => 'doctrine:schema:update',
                '--force' => true,
                '--env' => $env,
            ]);
            $output = new BufferedOutput();
            $application->run($input, $output);
            $content = $output->fetch();

            //send the message sent by Doctrine to the user's view
            $this->connectionSuccessMessage = $content;

            //slight bug : sometimes the ERROR message is sent as a success, so if it's too long we reset it as an error
            if (strlen($this->connectionSuccessMessage) > 300) {
                $this->connectionFailedMessage = $this->connectionSuccessMessage;
                // trim the message to remove unnecessary stack trace
                $this->connectionFailedMessage = strstr($this->connectionFailedMessage, 'Exception trace', true);
            }

            // get the content of config
            $allowInstalls = $this->configRepository->findBy(['name' => 'allow_install']);

            // will be used by the InstallVoter to determine access to all install routes       
            if (empty($config) && empty($allowInstalls)) {
                $config = new Config();
                $config->setName('allow_install');
                $config->setValue('true');
                $this->entityManager->persist($config);
                $this->entityManager->flush();
            }            

            return $this->render('install_setup/database_connection.html.twig', [
                'connection_success_message' => $this->connectionSuccessMessage,
                'connection_failed_message' => $this->connectionFailedMessage,
            ]);
        } catch (ConnectionException  | DBALException  | Exception $e) {

            $this->logger->error($e->getMessage());
            $return['error'] = $e->getMessage();
            // if the database doesn't exist yet, ask user to go create it
            if ($e instanceof ConnectionException) {
                $this->connectionFailedMessage = $translator->trans('install.connection_failed_message'). $e->getMessage();
                return $this->render('install_setup/database_connection.html.twig', [
                    'connection_success_message' => $this->connectionSuccessMessage,
                    'connection_failed_message' => $this->connectionFailedMessage,
                ]);
            }
            if ($e instanceof TableNotFoundException) {
                return $this->render('install_setup/database_connection.html.twig', [
                    'connection_success_message' => $this->connectionSuccessMessage,
                    'connection_failed_message' => $this->connectionFailedMessage,
                ]);
            }
            if ($e instanceof AccessDeniedException) {
                return $this->redirectToRoute('login');
            }

            return $this->redirectToRoute('app_database_setup');
        }
    }

    #[Route('/load_fixtures', name: 'app_load_fixtures')]
    public function doctrineFixturesLoad(Request $request, KernelInterface $kernel): Response
    {
        try {
            $config = $this->configRepository->findOneByAllowInstall('allow_install');
            //to help voter decide whether we allow access to install process again or not
            if (!empty($config)) {
                if ('allow_install' === $config->getName()) {
                    $this->denyAccessUnlessGranted('DATABASE_EDIT', $config);
                }
            }
            $env = $kernel->getEnvironment();
            $application = new Application($kernel);
            $application->setAutoExit(false);

            //load database tables
            $fixturesInput = new ArrayInput([
                'command' => 'doctrine:fixtures:load',
                '--append' => true,
                '--env' => $env,
            ]);

            $fixturesOutput = new BufferedOutput();
            $application->run($fixturesInput, $fixturesOutput);
            $content = $fixturesOutput->fetch();

            //send the message sent by Doctrine to the user's view
            $this->connectionSuccessMessage = $content;

            //slight bug : sometimes the ERROR message is sent as a success, so if it's too long we reset it as an error
            if (strlen($this->connectionSuccessMessage) > 300) {
                $this->connectionFailedMessage = $this->connectionSuccessMessage;
                // trim the message to remove unnecessary stack trace
                $this->connectionFailedMessage = strstr($this->connectionFailedMessage, 'Exception trace', true);
            }

            return $this->render(
                'install_setup/load_fixtures.html.twig',
                [
                    'connection_success_message' => $this->connectionSuccessMessage,
                    'connection_failed_message' => $this->connectionFailedMessage,
                ]
            );
        } catch (Exception $e) {
            if ($e instanceof AccessDeniedException) {
                return $this->redirectToRoute('login');
            }
        }
        return $this->render(
            'install_setup/load_fixtures.html.twig',
            [
                'connection_success_message' => $this->connectionSuccessMessage,
                'connection_failed_message' => $this->connectionFailedMessage,
            ]
        );
    }
}