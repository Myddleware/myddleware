<?php

namespace App\Controller;

use App\Entity\Config;
use App\Entity\DatabaseParameter;
use App\Form\DatabaseSetupType;
use App\Repository\ConfigRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DatabaseSetupController extends AbstractController
{
    private $connectionSuccessMessage;
    private $connectionFailedMessage;
    private ConfigRepository $configRepository;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(ConfigRepository $configRepository, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->configRepository = $configRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * @Route("install/database/setup", name="database_setup")
     */
    public function index(Request $request, KernelInterface $kernel): Response
    {
        try {
            $submitted = false;
            try {
                // Do you have a DB ? Is it filled yet ? Do you have permission to install?
                $config = $this->configRepository->findOneByAllowInstall('allow_install');
            } catch (Exception $e) {
                if ($e instanceof TableNotFoundException) {
                    //DO NOTHING HERE, this means that you have a DB but it's still empty
                }
            }

            //to help voter decide whether we allow access to install process again or not
            if (!empty($config)) {
                if ('allow_install' === $config->getName()) {
                    $this->denyAccessUnlessGranted('DATABASE_EDIT', $config);
                }
            }

            //get all parameters from config/parameters.yml and push them in a new instance of DatabaseParameters()
            $database = new DatabaseParameter();
            $parameters = ['database_driver', 'database_host', 'database_port', 'database_name', 'database_user', 'database_password', 'secret'];
            foreach ($parameters as $parameter) {
                try {
                    $value = $this->getParameter($parameter);
                } catch (Exception $e) {
                    //if parameters are not accessible yet
                    if ($e instanceof InvalidArgumentException) {
                        continue;
                    }
                }

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
            }

            // force user to change the default Symfony secret for security
            if ('Thissecretisnotsosecretchangeit' === $database->getSecret() || null === $database->getSecret() || empty($database)) {
                $database->setSecret(md5(rand(0, 10000).date('YmdHis').'myddleware'));
            }

            $form = $this->createForm(DatabaseSetupType::class, $database);
            $form->handleRequest($request);

            // send database parameters to .env.local
            if ($form->isSubmitted() && $form->isValid()) {
                $envLocal = __DIR__.'/../../.env.local';
                // Replace single URL with individual parameters
                $dbConfig = [
                    'DATABASE_HOST=' . $database->getHost(),
                    'DATABASE_PORT=' . $database->getPort(),
                    'DATABASE_NAME=' . $database->getName(),
                    'DATABASE_USER=' . $database->getUser(),
                    'DATABASE_PASSWORD=' . $database->getPassword(),
                    'APP_ENV=prod',
                    'APP_DEBUG=false',
                    'APP_SECRET=' . $database->getSecret()
                ];
                
                // Write each parameter on a new line
                file_put_contents($envLocal, implode(PHP_EOL, $dbConfig), LOCK_EX);

                // allow to proceed to next step
                $submitted = true;
            }

            return $this->render('database_setup/index.html.twig', [
                    'form' => $form->createView(),
                    'submitted' => $submitted,
                ]);
        } catch (Exception $e) {
            if ($e instanceof ConnectionException | $e instanceof TableNotFoundException) {
                $submitted = false;

                //get all parameters from config/parameters.yml and push them in a new instance of DatabaseParameters()
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
                        if ($e instanceof InvalidArgumentException) {
                            // force user to change the default Symfony secret for security
                            if ('Thissecretisnotsosecretchangeit' === $database->getSecret() || null === $database->getSecret() || empty($database)) {
                                $database->setSecret(md5(rand(0, 10000).date('YmdHis').'myddleware'));
                            }

                            $form = $this->createForm(DatabaseSetupType::class, $database);
                            $form->handleRequest($request);

                            // send database parameters to .env.local
                            if ($form->isSubmitted() && $form->isValid()) {
                                $envLocal = __DIR__.'/../../.env.local';
                                // Replace single URL with individual parameters
                                $dbConfig = [
                                    'DATABASE_HOST=' . $database->getHost(),
                                    'DATABASE_PORT=' . $database->getPort(),
                                    'DATABASE_NAME=' . $database->getName(),
                                    'DATABASE_USER=' . $database->getUser(),
                                    'DATABASE_PASSWORD=' . $database->getPassword(),
                                    'APP_ENV=prod',
                                    'APP_DEBUG=false',
                                    'APP_SECRET=' . $database->getSecret()
                                ];
                                
                                // Write each parameter on a new line
                                file_put_contents($envLocal, implode(PHP_EOL, $dbConfig), LOCK_EX);

                                // allow to proceed to next step
                                $submitted = true;
                            }

                            //if there's already a database in .env.local but it isn't yet linked to database, then allow access to form
                            return $this->render('database_setup/index.html.twig', [
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

    /**
     * Attempt to connect to database.
     *
     * @Route("install/database/connect", name="database_connect")
     */
    public function connectDatabase(Request $request, KernelInterface $kernel): Response
    {
        try {
            try {
                // Do you have a DB ? Is it filled yet ? Do you have permission to install?
                $config = $this->configRepository->findOneByAllowInstall('allow_install');
            } catch (Exception $e) {
                if ($e instanceof TableNotFoundException) {
                    //DO NOTHING HERE, this means that you have a DB but it's still empty
                }
            }

            // //to help voter decide whether we allow access to install process again or not
            if (!empty($config)) {
                if ('allow_install' === $config->getName()) {
                    $this->denyAccessUnlessGranted('DATABASE_EDIT', $config);
                }
            }
            $env = $kernel->getEnvironment();

            $application = new Application($kernel);
            $application->setAutoExit(false);

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

            // will be used by the InstallVoter to determine access to all install routes
            if (empty($config)) {
                $config = new Config();
                $config->setName('allow_install');
            }
            $config->setValue('true');
            $this->entityManager->persist($config);
            $this->entityManager->flush();

            return $this->render('database_setup/database_connection.html.twig', [
                'connection_success_message' => $this->connectionSuccessMessage,
                'connection_failed_message' => $this->connectionFailedMessage,
            ]);
        } catch (ConnectionException  | DBALException  | Exception $e) {
            // if the database doesn't exist yet, ask user to go create it
            if ($e instanceof ConnectionException) {
                $this->connectionFailedMessage = 'Unknown database. Please make sure your database exists. '.$e->getMessage();

                return $this->render('database_setup/database_connection.html.twig', [
                    'connection_success_message' => $this->connectionSuccessMessage,
                    'connection_failed_message' => $this->connectionFailedMessage,
                ]);
            }

            if ($e instanceof TableNotFoundException) {
                return $this->render('database_setup/database_connection.html.twig', [
                    'connection_success_message' => $this->connectionSuccessMessage,
                    'connection_failed_message' => $this->connectionFailedMessage,
                ]);
            }

            if ($e instanceof AccessDeniedException) {
                return $this->redirectToRoute('login');
            }

            return $this->redirectToRoute('database_setup');
        }
    }

    /**
     * Attempt to load Myddleware fixtures to database.
     *
     * @Route("install/database/fixtures/load", name="database_fixtures_load")
     */
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

            return $this->render('database_setup/load_fixtures.html.twig',
                [
                    'connection_success_message' => $this->connectionSuccessMessage,
                    'connection_failed_message' => $this->connectionFailedMessage,
                ]);
        } catch (Exception $e) {
            if ($e instanceof AccessDeniedException) {
                return $this->redirectToRoute('login');
            }
        }

        return $this->render('database_setup/load_fixtures.html.twig',
        [
            'connection_success_message' => $this->connectionSuccessMessage,
            'connection_failed_message' => $this->connectionFailedMessage,
        ]);
    }
}
