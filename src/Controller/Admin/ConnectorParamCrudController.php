<?php

namespace App\Controller\Admin;

use App\Form\SolutionType;
use App\Entity\ConnectorParam;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ConnectorParamCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ConnectorParam::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
        ->setEntityLabelInSingular('Authentification Credentials')
        ->setEntityLabelInPlural('Credentials')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            CollectionField::new('solution')
            ->setEntryIsComplex(true)
            ->setEntryType(SolutionType::class),
            AssociationField::new('connector')->hideOnForm(),
            TextField::new('name'),
            TextField::new('value'),
        ];
    }

}
