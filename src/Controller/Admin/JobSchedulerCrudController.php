<?php

namespace App\Controller\Admin;

use App\Entity\JobScheduler;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class JobSchedulerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return JobScheduler::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
