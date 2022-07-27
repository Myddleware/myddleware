<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InstallRequirements extends AbstractController
{
    #[Route('/install_requirements', name: 'app_install_requirements')]
    public function requirements(): Response
    {
        $phpVersion = phpversion();

        return $this->render('install_setup/install_requirements.html.twig', [
            'php_version' => $phpVersion,
        ]);
    }
}
