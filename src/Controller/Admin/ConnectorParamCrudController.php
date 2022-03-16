<?php

namespace App\Controller\Admin;

use App\Entity\ConnectorParam;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ConnectorParamCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ConnectorParam::class;
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
