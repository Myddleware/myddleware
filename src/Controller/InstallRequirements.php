<?php

namespace App\Controller;

use App\Repository\ConfigRepository;
use Symfony\Requirements\SymfonyRequirements;
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
        try {
            $this->symfonyRequirements = new SymfonyRequirements();
            $this->phpVersion = phpversion();
            $checkPassed = true;
            $requirementsErrorMesssages = [];

            foreach ($this->symfonyRequirements->getRequirements() as $req) {
                if (!$req->isFulfilled()) {
                    $requirementsErrorMesssages[] = $req->getHelpText();
                    $checkPassed = false;
                }
            }
            $recommendationMesssages = [];
            foreach ($this->symfonyRequirements->getRecommendations() as $req) {
                if (!$req->isFulfilled()) {
                    $recommendationMesssages[] = $req->getHelpText();
                }
            }
            $this->systemStatus = '';
            if (!$checkPassed) {
                $this->systemStatus = 'Your system is not ready to run Myddleware yet';
            } else {
                $this->systemStatus = 'Your system is ready to run Myddleware';
            }

            //allow access if no errors
            return $this->render('install_setup/install_requirements.html.twig', [
                'php_version' => $this->phpVersion,
                'error_messages' => $requirementsErrorMesssages,
                'recommendation_messages' => $recommendationMesssages,
                'system_status' => $this->systemStatus,
            ]);
        } catch (Exception $e) {
            //to help voter decide whether we allow access to install process again or not
            $configs = $this->configRepository->findAll();
            if (!empty($configs)) {
                foreach ($configs as $config) {
                    if ('allow_install' === $config->getName()) {
                        $this->denyAccessUnlessGranted('DATABASE_VIEW', $config);
                    }
                }
            }
        }
    }
}