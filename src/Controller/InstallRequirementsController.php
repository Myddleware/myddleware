<?php

namespace App\Controller;

use Symfony\Requirements\SymfonyRequirements;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class InstallRequirementsController extends AbstractController
{

    private $symfonyRequirements;
    private $phpVersion;
    private $systemStatus;

    /**
     * @Route("/install/requirements", name="install_requirements")
     */
    public function index(TranslatorInterface $translator): Response
    {

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
