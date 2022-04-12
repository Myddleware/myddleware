<?php

namespace App\Controller\Admin;

use App\Entity\ConnectorParam;
use App\Entity\Solution;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ConnectorParamCrudController extends AbstractCrudController
{
    // private $entityManager;

    // public function __construct(EntityManagerInterface $entityManager)
    // {
    //     $this->entityManager = $entityManager;
    // }

    public static function getEntityFqcn(): string
    {
        return ConnectorParam::class;
    }

    // public function createEntity(string $entityFqcn)
    // {
    //     $connectorParam = new ConnectorParam();
    //     // $connectorParam->setSolution();
    // }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
        ->setEntityLabelInSingular('Authentification Credentials')
        ->setEntityLabelInPlural('Credentials')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // $solutionRepository = $this->entityManager->getRepository(Solution::class);
        // $solutions = $solutionRepository->findAll();
        // foreach($solutions as $solution){
        //     $solutionsNames[$solution->getName()] = $solution->getName();
        // }
        return [
            // ChoiceField::new('solution')->setChoices($solutionsNames),
            // AssociationField::new('solution'),
            AssociationField::new('connector'),
            TextField::new('name'),
            TextField::new('value'),
        ];
    }
}
