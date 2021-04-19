<?php

namespace App\Controller;

use Exception;
use PDOException;
use Doctrine\DBAL\DBALException;
use App\Repository\ConfigRepository;
use Symfony\Requirements\SymfonyRequirements;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\DBAL\Driver\PDO\Exception as DoctrinePDOException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\DBAL\ConnectionException as DoctrineConnectionException;
use Doctrine\DBAL\Exception\TableNotFoundException;

class InstallRequirementsController extends AbstractController
{

    private $symfonyRequirements;
    private $phpVersion;
    private $systemStatus;

    private $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }




    /**
     * @Route("/install/requirements", name="install_requirements")
     */
    public function index(TranslatorInterface $translator): Response
    {
 
        try {

            //to help voter decide whether we allow access to install process again or not
            $configs = $this->configRepository->findAll();
            if(!empty($configs)){
                foreach($configs as $config) {
                    if($config->getName() === 'allow_install'){
                        $this->denyAccessUnlessGranted('DATABASE_VIEW', $config);
                    }
                }
            }

        } catch(Exception | DBALException | PDOException | DoctrinePDOException | TableNotFoundException $e){

            // if we have a database in .env.local but the connection hasn't been made yet
            if ($e instanceof DBALException | $e instanceof PDOException | $e instanceof DoctrineConnectionException | $e instanceof TableNotFoundException ) {
               
                $this->symfonyRequirements = new SymfonyRequirements();

                $this->phpVersion = phpversion();

                $checkPassed = true;


                $requirementsErrorMesssages = [];
                foreach($this->symfonyRequirements->getRequirements() as $req){
                    if(!$req->isFulfilled()){
                        $requirementsErrorMesssages[] = $req->getHelpText();
                        $checkPassed = false;
                    }
                }

                $recommendationMesssages = array();
                foreach($this->symfonyRequirements->getRecommendations() as $req){
                    if(!$req->isFulfilled()){
                        $recommendationMesssages[] = $req->getHelpText();
                    } 
                }

                $this->systemStatus = '';
                if(!$checkPassed){
                    $this->systemStatus = $translator->trans('install.system_status_not_ready');
                }else{
                    $this->systemStatus = $translator->trans('install.system_status_ready');
                }

                return $this->render('install_requirements/index.html.twig', [
                    'php_version' => $this->phpVersion,
                    'error_messages' => $requirementsErrorMesssages,
                    'recommendation_messages' => $recommendationMesssages,
                    'system_status' => $this->systemStatus
                ]);
    
            } else {
                // if other error, deny access
                return $this->redirectToRoute('login');
            }
            
        }
        $this->symfonyRequirements = new SymfonyRequirements();

        $this->phpVersion = phpversion();

        $checkPassed = true;

        $requirementsErrorMesssages = [];
        foreach($this->symfonyRequirements->getRequirements() as $req){
            if(!$req->isFulfilled()){
                $requirementsErrorMesssages[] = $req->getHelpText();
                $checkPassed = false;
            }
        }

        $recommendationMesssages = array();
        foreach($this->symfonyRequirements->getRecommendations() as $req){
            if(!$req->isFulfilled()){
                $recommendationMesssages[] = $req->getHelpText();
            } 
        }

        $this->systemStatus = '';
        if(!$checkPassed){
            $this->systemStatus = $translator->trans('install.system_status_not_ready');
        }else{
            $this->systemStatus = $translator->trans('install.system_status_ready');
        }

        //allow access if no errors
        return $this->render('install_requirements/index.html.twig', [
            'php_version' => $this->phpVersion,
            'error_messages' => $requirementsErrorMesssages,
            'recommendation_messages' => $recommendationMesssages,
            'system_status' => $this->systemStatus
        ]);
        
        // //allow access if no errors
        // return $this->render('install_requirements/index.html.twig', [
        //     'php_version' => $this->phpVersion,
        //     'error_messages' => $requirementsErrorMesssages,
        //     'recommendation_messages' => $recommendationMesssages,
        //     'system_status' => $this->systemStatus
        // ]);

    }
}
