<?php

namespace App\Controller\Admin;


use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class HomeController extends AbstractDashboardController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        
        return parent::index();
        //return $this->render('home.html.twig');
    }
}
