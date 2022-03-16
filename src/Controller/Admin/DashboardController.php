<?php

namespace App\Controller\Admin;

use App\Entity\Connector;
use App\Entity\Job;
use App\Entity\JobScheduler;
use App\Entity\Rule;
use App\Entity\Solution;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Option 1. You can make your dashboard redirect to some common page of your backend
        $routeBuilder = $this->container->get(AdminUrlGenerator::class);
        $url = $routeBuilder->setController(ConnectorCrudController::class)->generateUrl();

        return $this->redirect($url);

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="build/images/logo/logo.png" alt="Myddleware">')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linkToDashboard('Dashboard', 'fa fa-home'),
            MenuItem::section('Rules'),
            MenuItem::linkToCrud('Rule', 'fas fa-sync', Rule::class),
            MenuItem::section('Connectors'),
            MenuItem::linkToCrud('Connector', 'fa fa-link', Connector::class),
            MenuItem::linkToCrud('Solution', 'fa fa-bullseye', Solution::class),
            MenuItem::section('Jobs'),
            MenuItem::linkToCrud('Job', 'fas fa-tasks', Job::class),
            MenuItem::section('JobScheduler'),
            MenuItem::linkToCrud('JobScheduler', 'fa fa-calendar', JobScheduler::class),
            MenuItem::section('Users'),
            MenuItem::linkToCrud('User', 'fa fa-user', User::class),
        ];
    }
}
