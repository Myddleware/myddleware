<?php

namespace App\Controller;

use App\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class InstallRequirements extends AbstractController
{
    private $phpVersion;

    private $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }
    
    #[Route('/install_requirements', name: 'app_install_requirements')]
    public function requirements(): Response
    {
        $configs = $this->configRepository->findAll();
        $this->phpVersion = phpversion();

         //to help voter decide whether we allow access to install process again or not
         $configs = $this->configRepository->findAll();
         if (!empty($configs)) {
             foreach ($configs as $config) {
                 if ('allow_install' === $config->getName()) {
                     $this->denyAccessUnlessGranted('DATABASE_VIEW', $config);
                 }
             }
         }

        return $this->render('install_setup/install_requirements.html.twig', [
            'php_version' => $this->phpVersion,
        ]);
        
    }
    // public function installRequirements()
    // {
    //     try {
    //         //to help voter decide whether we allow access to install process again or not
    //         $configs = $this->configRepository->findAll();
    //         if (!empty($configs)) {
    //             foreach ($configs as $config) {
    //                 if ('allow_install' === $config->getName()) {
    //                     $this->denyAccessUnlessGranted('DATABASE_VIEW', $config);
    //                 }
    //             }
               
    //         }
    //     } catch (Exception $e) {
    //         $this->logger->error($e->getMessage());
    //         $return['error'] = $e->getMessage();
    //     }        
    // }

}