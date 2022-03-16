<?php

namespace App\Controller\Admin;

use App\Entity\Rule;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class RuleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Rule::class;
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
