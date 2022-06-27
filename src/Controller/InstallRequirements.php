<?php

namespace App\Controller;

use App\Repository\ConfigRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InstallRequirements extends AbstractController
{
    // public function __construct(ConfigRepository $configRepository)
    // {
    //     $this->configRepository = $configRepository;
    // }

    #[Route('/install_requirements', name: 'app_install_requirements')]
    public function requirements(): Response
    {
        // $configs = $this->configRepository->findAll();
        $phpVersion = phpversion();

        return $this->render('install_setup/install_requirements.html.twig', [
            'php_version' => $phpVersion,
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
