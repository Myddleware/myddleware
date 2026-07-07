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
use App\Service\DebugLogger;

class DatabaseSetupController extends AbstractController
{
    private $connectionSuccessMessage;
    private $connectionFailedMessage;
    private ConfigRepository $configRepository;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private DebugLogger $debugLogger;

    public function __construct(ConfigRepository $configRepository, EntityManagerInterface $entityManager, LoggerInterface $logger, DebugLogger $debugLogger)
    {
        $this->configRepository = $configRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->debugLogger = $debugLogger;
    }

    /**
     * @Route("install/database/setup", name="database_setup")
     */
    public function index(Request $request, KernelInterface $kernel): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'kernel' => $kernel]);
        $__debugReturn = null;
        try {
            try {
                $submitted = false;
                try {
                    $config = $this->configRepository->findOneByAllowInstall('allow_install');
                } catch (Exception $e) {
                    if ($e instanceof TableNotFoundException) {
                    }
                }

                if (!empty($config)) {
                    if ('allow_install' === $config->getName()) {
                        $this->denyAccessUnlessGranted('DATABASE_EDIT', $config);
                    }
                }

                $database = new DatabaseParameter();
                $parameters = ['database_driver', 'database_host', 'database_port', 'database_name', 'database_user', 'database_password', 'secret'];
                foreach ($parameters as $parameter) {
                    try {
                        $value = $this->getParameter($parameter);
                    } catch (Exception $e) {
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

                if ('Thissecretisnotsosecretchangeit' === $database->getSecret() || null === $database->getSecret() || empty($database)) {
                    $database->setSecret(md5(rand(0, 10000).date('YmdHis').'myddleware'));
                }

                $form = $this->createForm(DatabaseSetupType::class, $database);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $envLocal = __DIR__.'/../../.env.local';
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

                    file_put_contents($envLocal, implode(PHP_EOL, $dbConfig), LOCK_EX);

                    $submitted = true;
                }

                return $__debugReturn = $this->render('database_setup/index.html.twig', [
                        'form' => $form->createView(),
                        'submitted' => $submitted,
                    ]);
            } catch (Exception $e) {
                if ($e instanceof ConnectionException | $e instanceof TableNotFoundException) {
                    $submitted = false;

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
                                if ('Thissecretisnotsosecretchangeit' === $database->getSecret() || null === $database->getSecret() || empty($database)) {
                                    $database->setSecret(md5(rand(0, 10000).date('YmdHis').'myddleware'));
                                }

                                $form = $this->createForm(DatabaseSetupType::class, $database);
                                $form->handleRequest($request);

                                if ($form->isSubmitted() && $form->isValid()) {
                                    $envLocal = __DIR__.'/../../.env.local';
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

                                    file_put_contents($envLocal, implode(PHP_EOL, $dbConfig), LOCK_EX);

                                    $submitted = true;
                                }

                                return $__debugReturn = $this->render('database_setup/index.html.twig', [
                                    'form' => $form->createView(),
                                    'submitted' => $submitted,
                                ]);
                            } else {
                                return $__debugReturn = $this->redirectToRoute('login');
                            }
                        }
                    }
                }
            }

            return $__debugReturn = $this->redirectToRoute('login');
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("install/database/connect", name="database_connect")
     */
    public function connectDatabase(Request $request, KernelInterface $kernel): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'kernel' => $kernel]);
        $__debugReturn = null;
        try {
            try {
                try {
                    $config = $this->configRepository->findOneByAllowInstall('allow_install');
                } catch (Exception $e) {
                    if ($e instanceof TableNotFoundException) {
                    }
                }

                if (!empty($config)) {
                    if ('allow_install' === $config->getName()) {
                        $this->denyAccessUnlessGranted('DATABASE_EDIT', $config);
                    }
                }
                $env = $kernel->getEnvironment();

                $application = new Application($kernel);
                $application->setAutoExit(false);

                $input = new ArrayInput([
                    'command' => 'doctrine:schema:update',
                    '--force' => true,
                    '--complete' => true,
                    '--env' => $env,
                ]);
                $output = new BufferedOutput();
                $application->run($input, $output);
                $content = $output->fetch();

                $this->connectionSuccessMessage = $content;

                if (strlen($this->connectionSuccessMessage) > 300) {
                    $this->connectionFailedMessage = $this->connectionSuccessMessage;
                    $this->connectionFailedMessage = strstr($this->connectionFailedMessage, 'Exception trace', true);
                }

                if (empty($config)) {
                    $config = new Config();
                    $config->setName('allow_install');
                }
                $config->setValue('true');
                $this->entityManager->persist($config);
                $this->entityManager->flush();

                return $__debugReturn = $this->render('database_setup/database_connection.html.twig', [
                    'connection_success_message' => $this->connectionSuccessMessage,
                    'connection_failed_message' => $this->connectionFailedMessage,
                ]);
            } catch (ConnectionException  | DBALException  | Exception $e) {
                if ($e instanceof ConnectionException) {
                    $this->connectionFailedMessage = 'Unknown database. Please make sure your database exists. '.$e->getMessage();

                    return $__debugReturn = $this->render('database_setup/database_connection.html.twig', [
                        'connection_success_message' => $this->connectionSuccessMessage,
                        'connection_failed_message' => $this->connectionFailedMessage,
                    ]);
                }

                if ($e instanceof TableNotFoundException) {
                    return $__debugReturn = $this->render('database_setup/database_connection.html.twig', [
                        'connection_success_message' => $this->connectionSuccessMessage,
                        'connection_failed_message' => $this->connectionFailedMessage,
                    ]);
                }

                if ($e instanceof AccessDeniedException) {
                    return $__debugReturn = $this->redirectToRoute('login');
                }

                return $__debugReturn = $this->redirectToRoute('database_setup');
            }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * Attempt to load Myddleware fixtures to database.
     *
     * @Route("install/database/fixtures/load", name="database_fixtures_load")
     */
    public function doctrineFixturesLoad(Request $request, KernelInterface $kernel): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'kernel' => $kernel]);
        $__debugReturn = null;
        try {
            try {
                $config = $this->configRepository->findOneByAllowInstall('allow_install');

                if (!empty($config)) {
                    if ('allow_install' === $config->getName()) {
                        $this->denyAccessUnlessGranted('DATABASE_EDIT', $config);
                    }
                }
                $env = $kernel->getEnvironment();
                $application = new Application($kernel);
                $application->setAutoExit(false);

                $fixturesInput = new ArrayInput([
                    'command' => 'doctrine:fixtures:load',
                    '--append' => true,
                    '--env' => $env,
                ]);

                $fixturesOutput = new BufferedOutput();
                $application->run($fixturesInput, $fixturesOutput);
                $content = $fixturesOutput->fetch();

                $this->connectionSuccessMessage = $content;

                if (strlen($this->connectionSuccessMessage) > 300) {
                    $this->connectionFailedMessage = $this->connectionSuccessMessage;
                    $this->connectionFailedMessage = strstr($this->connectionFailedMessage, 'Exception trace', true);
                }

                return $__debugReturn = $this->render('database_setup/load_fixtures.html.twig',
                    [
                        'connection_success_message' => $this->connectionSuccessMessage,
                        'connection_failed_message' => $this->connectionFailedMessage,
                    ]);
            } catch (Exception $e) {
                if ($e instanceof AccessDeniedException) {
                    return $__debugReturn = $this->redirectToRoute('login');
                }
            }

            return $__debugReturn = $this->render('database_setup/load_fixtures.html.twig',
            [
                'connection_success_message' => $this->connectionSuccessMessage,
                'connection_failed_message' => $this->connectionFailedMessage,
            ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}
