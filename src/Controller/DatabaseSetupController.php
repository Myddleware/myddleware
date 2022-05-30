<?php

namespace App\Controller;

use App\Entity\Config;
use App\Form\DatabaseSetupType;
use App\Entity\DatabaseParameter;
use App\Form\DatabaseSetupFormType;
use App\Repository\ConfigRepository;
use Doctrine\DBAL\Exception as DBALException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;


class DatabaseSetupController extends AbstractController
{
    private $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }
    
    #[Route('/database_setup', name: 'app_database_setup')]
    public function requirements(Request $request , KernelInterface $kernel ): Response
    {
        try {
            $submitted = false;
            //get all parameters from config/parameters.yml and push them in a new instance of DatabaseParameters()
            $database = new DatabaseParameter();
            $parameters = ['database_driver', 'database_host', 'database_port', 'database_name', 'database_user', 'database_password', 'secret'];
            foreach ($parameters as $parameter) {
                // Here if !!!!
                //$value = $this->getParameter($parameter);
                // try {
                    
                // } catch (Exception $e) {
                //     //if parameters are not accessible yet
                //     if ($e instanceof InvalidArgumentException) {
                //         continue;
                //     }
                // }
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
            $form = $this->createForm(DatabaseSetupFormType::class, $database);
            //dd($database);
            $form->handleRequest($request);
            // send database parameters to .env.local
            if ($form->isSubmitted() && $form->isValid()) {
                $envLocal = __DIR__.'/../../.env.local';
                // we edit the database connection parameters with form input
                $newUrl = 'DATABASE_URL="mysql://'.$database->getUser().':'.$database->getPassword().'@'.$database->getHost().':'.$database->getPort().'/'.$database->getName().'?serverVersion=5.7"';
                $prodString = 'APP_ENV=prod'.PHP_EOL.'APP_DEBUG=false';
                // add Symfony secret to .env.local
                $appSecret = 'APP_SECRET='.$database->getSecret();
                // write new URL into the .env.local file (EOL ensures it's written on a new line)
                //dd($form);
                $ok = file_put_contents($envLocal, PHP_EOL.$newUrl.PHP_EOL.$prodString.PHP_EOL.$appSecret, LOCK_EX);
                // allow to proceed to next step
                $submitted = true;
            }                  

            return $this->render('install_setup/database_setup.html.twig'
            , [
                'form' => $form->createView(),
                    'submitted' => $submitted,
                ]
            );
        
        }catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $return['error'] = $e->getMessage();  
            // Do you have a DB ? Is it filled yet ? Do you have permission to install?
            // $config = $this->configRepository->findOneByAllowInstall('allow_install');  
            // //to help voter decide whether we allow access to install process again or not
            if (!empty($config)) {
                if ('allow_install' === $config->getName()) {
                    $this->denyAccessUnlessGranted('DATABASE_EDIT', $config);
                }
            } 
        }
    }








    //Database Connection

    #[Route('/database_connection', name: 'app_database_connection')]
    public function dataConnection(): Response
    {
        return $this->render('install_setup/database_connection.html.twig');

        // Request $request, KernelInterface $kernel
        // try {
        //     try {
        //         // Do you have a DB ? Is it filled yet ? Do you have permission to install?
        //         $config = $this->configRepository->findOneByAllowInstall('allow_install');
        //     } catch (Exception $e) {
        //         if ($e instanceof TableNotFoundException) {
        //             //DO NOTHING HERE, this means that you have a DB but it's still empty
        //         }
        //     }


        //     //IN CATCH ?
        //     // //to help voter decide whether we allow access to install process again or not
        //     if (!empty($config)) {
        //         if ('allow_install' === $config->getName()) {
        //             $this->denyAccessUnlessGranted('DATABASE_EDIT', $config);
        //         }
        //     }

        //     $env = $kernel->getEnvironment();
        //     dump($env);
        //     $application = new Application($kernel);
        //     $application->setAutoExit(false);

        //     // we execute Doctrine console commands to test the connection to the database
        //     $input = new ArrayInput([
        //         'command' => 'doctrine:schema:update',
        //         '--force' => true,
        //         '--env' => $env,
        //     ]);
        //     $output = new BufferedOutput();
        //     $application->run($input, $output);
        //     $content = $output->fetch();

        //     //send the message sent by Doctrine to the user's view
        //     $this->connectionSuccessMessage = $content;

        //     //slight bug : sometimes the ERROR message is sent as a success, so if it's too long we reset it as an error
        //     if (strlen($this->connectionSuccessMessage) > 300) {
        //         $this->connectionFailedMessage = $this->connectionSuccessMessage;
        //         // trim the message to remove unnecessary stack trace
        //         $this->connectionFailedMessage = strstr($this->connectionFailedMessage, 'Exception trace', true);
        //     }

        //     // will be used by the InstallVoter to determine access to all install routes
        //     if (empty($config)) {
        //         $config = new Config();
        //         $config->setName('allow_install');
        //     }
        //     $config->setValue('true');
        //     $this->entityManager->persist($config);
        //     $this->entityManager->flush();

        //     return $this->render('install_setup/database_connection.html.twig', [
        //         'connection_success_message' => $this->connectionSuccessMessage,
        //         'connection_failed_message' => $this->connectionFailedMessage,
        //     ]);
        // } catch (ConnectionException  | DBALException  | Exception $e) {
        //     // if the database doesn't exist yet, ask user to go create it
        //     if ($e instanceof ConnectionException) {
        //         $this->connectionFailedMessage = 'Unknown database. Please make sure your database exists. '.$e->getMessage();
        //         die();

        //         return $this->render('install_setup/database_connection.html.twig', [
        //             'connection_success_message' => $this->connectionSuccessMessage,
        //             'connection_failed_message' => $this->connectionFailedMessage,
        //         ]);
        //     }

        //     if ($e instanceof TableNotFoundException) {
        //         return $this->render('install_setup/database_connection.html.twig', [
        //             'connection_success_message' => $this->connectionSuccessMessage,
        //             'connection_failed_message' => $this->connectionFailedMessage,
        //         ]);
        //     }

        //     if ($e instanceof AccessDeniedException) {
        //         return $this->redirectToRoute('login');
        //     }

         //   return $this->redirectToRoute('app_database_setup');
        //}
        
    }

    //Database Connection

    #[Route('/load_fixtures', name: 'app_load_fixtures')]
    public function LoadFixtures(): Response
    {
        return $this->render('install_setup/load_fixtures.html.twig');
        
    }


}