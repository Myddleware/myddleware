<?php

namespace App\Controller\Admin;

use DateTimeImmutable;
use App\Entity\Connector;
use App\Form\ConnectorParamFormType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ConnectorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Connector::class;
    }

    public function createEntity(string $entityFqcn)
    {
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

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if(!$entityInstance instanceof Connector) return;
        $entityInstance->setUpdatedAt(new \DateTimeImmutable());
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnDetail(),
            TextField::new('name'),
            AssociationField::new('solution'),
            CollectionField::new('connectorParams', 'Credentials')
                ->setEntryIsComplex(true)
                ->setEntryType(ConnectorParamFormType::class),
                // ->setTemplatePath('admin/connector_params.html.twig')
            // AssociationField::new('connectorParams')->setCrudController(ConnectorParamCrudController::class),
            AssociationField::new('rulesWhereIsSource')->hideOnForm(),
            AssociationField::new('rulesWhereIsTarget')->hideOnForm(),
            AssociationField::new('createdBy')->hideOnForm(),
            AssociationField::new('modifiedBy')->hideOnForm(),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('updatedAt')->hideOnForm(),
            BooleanField::new('deleted')->hideOnForm(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('name')
                        ->add('rulesWhereIsTarget')
                        ->add('rulesWhereIsSource')
                        ->add('createdBy')
                        ->add('modifiedBy')
                        ->add('createdAt')
                        ->add('updatedAt')
                        ->add('deleted')
                        ->add('solution')
                        ;
    }
}
