<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ConfigRepository;
use Symfony\Requirements\SymfonyRequirements;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
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
    public function requirements(TranslatorInterface $translator): Response
    {
        try {

            if($this->getUser()){
                return $this->redirectToRoute('app_home');
            }
            
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
                $this->systemStatus = $translator->trans('install.system_status_not_ready');
            } else {
                $this->systemStatus = $translator->trans('install.system_status_ready');
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