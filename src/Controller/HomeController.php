<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        if ($user = $this->getUser()) {
            return $this->render('home/index.html.twig', [
                'user' => $user,
            ]);
        }

        return $this->render('home/index.html.twig', [
            'user' => $user,
        ]);
    }
}
