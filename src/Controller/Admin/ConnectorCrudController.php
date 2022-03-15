<?php

namespace App\Controller\Admin;

use App\Entity\Connector;
use DateTimeImmutable;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;

class ConnectorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Connector::class;
    }

    public function createEntity(string $entityFqcn) {
        $user = $this->getUser();
        $connector = new Connector();
        $connector->setDeleted(false);
        $now = new DateTimeImmutable('now');
        $connector->setCreatedAt($now);
        $connector->setUpdatedAt($now);
        $connector->setCreatedBy($user);
        $connector->setModifiedBy($user);
        return $connector;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnDetail(),
            TextField::new('name'),
            // AssociationField::new('connectorParams'),
            // AssociationField::new('solution'),
            AssociationField::new('rulesWhereIsSource')->hideOnForm(),
            AssociationField::new('rulesWhereIsTarget')->hideOnForm(),
            AssociationField::new('createdBy')->hideOnForm(),
            AssociationField::new('modifiedBy')->hideOnForm(),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('updatedAt')->hideOnForm(),
            BooleanField::new('deleted')->hideOnForm(),

        ];
    }
}
