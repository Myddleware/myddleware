<?php

namespace App\Controller\Admin;

use App\Entity\Solution;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class SolutionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Solution::class;
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
