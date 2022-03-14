<?php

namespace App\Controller\Admin;

use App\Entity\Connector;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="build/images/logo/logo.png" alt="Myddleware">')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        return [ MenuItem::linkToDashboard('Dashboard', 'fa fa-home'),
                MenuItem::section('Connectors'),
                MenuItem::linkToCrud('Connector', 'fa fa-link', Connector::class),
                // MenuItem::linkToCrud('Blog Posts', 'fa fa-file-text', BlogPost::class),
                // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
        ];
    }
}
