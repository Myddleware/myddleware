<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\DatabaseSetupFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DatabaseSetupController extends AbstractController
{
    #[Route('/database_setup', name: 'app_database_setup')]
    public function requirements(): Response
    {
        $form = $this->createForm(DatabaseSetupFormType::class);

        return $this->render('install_setup/database_setup.html.twig', [
            'databaseForm' => $form->createView(),
        ]);
    }

    #[Route('/database_connection', name: 'app_database_connection')]
    public function dataConnection(): Response
    {
        return $this->render('install_setup/database_connection.html.twig');
    }

    #[Route('/load_fixtures', name: 'app_load_fixtures')]
    public function LoadFixtures(): Response
    {
        return $this->render('install_setup/load_fixtures.html.twig');
    }
}
