<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SetupController extends AbstractController
{
    #[Route('/setup', name: 'app_setup')]
    public function index(): Response
    {
        return $this->render('setup/index.html.twig', [
            'controller_name' => 'SetupController',
        ]);
    }
}
