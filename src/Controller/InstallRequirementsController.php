<?php

namespace App\Controller;

use Symfony\Requirements\SymfonyRequirements;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\DatabaseParameterRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class InstallRequirementsController extends AbstractController
{

    private $symfonyRequirements;
    private $phpVersion;
    private $systemStatus;

    private $databaseParameterRepository;

    public function __construct(DatabaseParameterRepository $databaseParameterRepository)
    {
        $this->databaseParameterRepository = $databaseParameterRepository;
    }




    /**
     * @Route("/install/requirements", name="install_requirements")
     */
    public function index(TranslatorInterface $translator): Response
    {

        // TODO : test db connection
        $connected = $this->getDoctrine()->getConnection()->isConnected();

        if($connected){

            //to help voter decide whether we allow access to install process again or not
            $databases = $this->databaseParameterRepository->findAll();
            if(!empty($databases)){
                foreach($databases as $database) {
                    $this->denyAccessUnlessGranted('DATABASE_EDIT', $database);
                }
            } 

        } else { 
               
                $this->symfonyRequirements = new SymfonyRequirements();

                $this->phpVersion = phpversion();

                $checkPassed = true;

            // TODO : get php.ini path info and display it in twig

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

        }

    }
}
