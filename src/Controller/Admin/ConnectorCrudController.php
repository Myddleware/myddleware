<?php

namespace App\Controller\Admin;

use App\Entity\Connector;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ConnectorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Connector::class;
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
