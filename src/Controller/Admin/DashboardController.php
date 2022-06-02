<?php

namespace App\Controller\Admin;

use App\Entity\Job;
use App\Entity\Rule;
use App\Entity\User;
use App\Entity\Module;
use App\Entity\Solution;
use App\Entity\Connector;
use App\Entity\JobScheduler;
use App\Entity\ConnectorParam;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use Symfony\Component\Security\Core\User\UserInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin_dashboard')]
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
            MenuItem::subMenu('Rules', 'fas fa-sync')->setSubItems([
                MenuItem::linkToCrud('My rules', 'fas fa-eye', Rule::class),
                MenuItem::linkToCrud('Create new rule', 'fas fa-plus', Rule::class)->setAction(Crud::PAGE_NEW),
            ]),
            MenuItem::section('Connectors'),
            MenuItem::subMenu('Connectors', 'fa fa-link')->setSubItems([
                MenuItem::linkToCrud('My connectors', 'fa fa-eye', Connector::class),
                MenuItem::linkToCrud('Add connector', 'fa fa-plus', Connector::class)->setAction(Crud::PAGE_NEW),
                MenuItem::linkToCrud('Credentials', 'fa fa-plug', ConnectorParam::class),
                MenuItem::linkToCrud('Add credentials', 'fas fa-plus', ConnectorParam::class)->setAction(Crud::PAGE_NEW),
            ]),
            MenuItem::section('Solutions'),
            MenuItem::linkToCrud('Solutions', 'fa fa-bullseye', Solution::class),
            MenuItem::linkToCrud('Modules', 'fa fa-cubes', Module::class),
            MenuItem::section('Jobs'),
            MenuItem::linkToCrud('Job', 'fas fa-tasks', Job::class),
            MenuItem::linkToCrud('Job Scheduler', 'fa fa-calendar', JobScheduler::class),
            MenuItem::section('Users'),
            MenuItem::linkToCrud('User', 'fa fa-user', User::class),
        ];
    }
    
    /**
     * @param UserInterface|User $user
     */
    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            //->setAvatarUrl($user->getAvatarUrl())
            ->addMenuItems([
                MenuItem::linkToUrl('My Profile', 'fas fa-user', $this->generateUrl('app_profile_show'))
            ]);
    }
    // public function configureUserMenu(UserInterface $user): UserMenu
    // {

    //     return parent::configureUserMenu($user)
    //         // use the given $user object to get the user name
    //         //->setName($user->getUsername())

    //         // you can use any type of menu item, except submenus
    //         ->addMenuItems([
    //             MenuItem::linkToRoute('My Profile', 'fa fa-id-card', $this->generateUrl('app_profile_show')),
    //             //MenuItem::linkToRoute('Settings', 'fa fa-user-cog', '...', ['...' => '...']),
    //             //MenuItem::section(),
    //             //MenuItem::linkToLogout('Logout', 'fa fa-sign-out'),
    //         ]);
    // }
    public function configureActions(): Actions
    {
        return parent::configureActions()->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}
