<?php

namespace App\Controller\Admin;

use App\Entity\Connector;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ConnectorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Connector::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('name'),
            // AssociationField::new('connectorParams'),
            // AssociationField::new('solution'),
            AssociationField::new('rulesWhereIsSource'),
            AssociationField::new('rulesWhereIsTarget'),
            AssociationField::new('createdBy'),
            AssociationField::new('modifiedBy'),
            DateTimeField::new('createdAt'),
            DateTimeField::new('updatedAt'),

        ];
    }
}
