<?php

namespace App\Controller;

use App\Form\DatabaseSetupType;
use App\Entity\DatabaseParameter;
use App\Form\DatabaseSetupFormType;
use App\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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

                $ok = file_put_contents($envLocal, PHP_EOL.$newUrl.PHP_EOL.$prodString.PHP_EOL.$appSecret, LOCK_EX);
                // allow to proceed to next step
                $submitted = true;
            }      
            

            return $this->render('install_setup/database_setup.html.twig', [
                'form' => $form->createView(),
                    'submitted' => $submitted,
                ]);
        
        }catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $return['error'] = $e->getMessage();        
        }
    }


    //Database Connection

    #[Route('/database_connection', name: 'app_database_connection')]
    public function dataConnection(): Response
    {
        return $this->render('install_setup/database_connection.html.twig');
        
    }

    //Database Connection

    #[Route('/load_fixtures', name: 'app_load_fixtures')]
    public function LoadFixtures(): Response
    {
        return $this->render('install_setup/load_fixtures.html.twig');
        
    }


}