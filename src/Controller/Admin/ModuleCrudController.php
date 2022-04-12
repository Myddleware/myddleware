<?php

namespace App\Controller\Admin;

use App\Entity\Module;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ModuleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Module::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
                ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
                ->setPermission(Action::NEW, 'ROLE_SUPER_ADMIN')
                ->setPermission(Action::EDIT, 'ROLE_SUPER_ADMIN')
                ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            TextField::new('nameKey'),
            AssociationField::new('solution'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('name')
                        ->add('nameKey')
                        ->add('solution')

        ;
    }
}
