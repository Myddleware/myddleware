<?php

namespace App\Controller;

use App\Form\DatabaseSetupType;
use App\Form\DatabaseSetupFormType;
use App\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DatabaseSetupController extends AbstractController
{
    //private $configRepository;

    // public function __construct(ConfigRepository $configRepository)
    // {
    //     $this->configRepository = $configRepository;
    // }
    
    #[Route('/database_setup', name: 'app_database_setup')]
    public function requirements(): Response
    {
        $form = $this->createForm(DatabaseSetupFormType::class);
        //$form->handleRequest($request);

        return $this->render('install_setup/database_setup.html.twig', [
            'databaseForm' => $form->createView(),
        ]);
        
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