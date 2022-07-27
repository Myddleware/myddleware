<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Solution;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SolutionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Solution::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnDetail(),
            TextField::new('name'),
            BooleanField::new('active'),
            BooleanField::new('source')->renderAsSwitch(false),
            BooleanField::new('target')->renderAsSwitch(false),
            CollectionField::new('connector'),
            ImageField::new('logo')->setBasePath('build/images/solution/'),
        ];
    }
}
