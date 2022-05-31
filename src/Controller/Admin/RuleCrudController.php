<?php

namespace App\Controller\Admin;

use App\Entity\Rule;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RuleCrudController extends AbstractCrudController
{
    public const ACTION_DUPLICATE = 'duplicate';

    public static function getEntityFqcn(): string
    {
        return Rule::class;
    }

    public function createEntity(string $entityFqcn)
    {
        $user = $this->getUser();
        $rule = new Rule();
        $now = new DateTimeImmutable('now');
        $rule->setDeleted(false);
        $rule->setCreatedAt($now);
        $rule->setUpdatedAt($now);
        $rule->setCreatedBy($user);
        $rule->setModifiedBy($user);

        return $rule;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Rule) {
            return;
        }
        $entityInstance->setUpdatedAt(new \DateTimeImmutable());
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureActions(Actions $actions): Actions
    {
        $duplicate = Action::new(self::ACTION_DUPLICATE)
            ->linkToCrudAction('duplicateRule')
            ->setCssClass('btn btn-warning');

        return $actions->add(Crud::PAGE_EDIT, $duplicate)
            ->add(Crud::PAGE_DETAIL, $duplicate)
            ->reorder(Crud::PAGE_DETAIL, [Action::EDIT, self::ACTION_DUPLICATE]);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            TextField::new('nameSlug')->hideOnForm(),
            AssociationField::new('connectorSource'),
            AssociationField::new('connectorTarget'),
            AssociationField::new('sourceModule'),
            AssociationField::new('targetModule'),
            BooleanField::new('active'),
            BooleanField::new('deleted')->hideOnForm(),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('updatedAt')->hideOnForm(),
        ];
    }

    public function duplicateRule(AdminContext $context, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        /**
         * @var Rule $rule
         */
        $rule = $context->getEntity()->getInstance();
        $duplicatedRule = clone $rule;
        parent::persistEntity($entityManager, $duplicatedRule);

        $url = $adminUrlGenerator->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($duplicatedRule->getId())
            ->generateUrl();

        return $this->redirect($url);
    }
}
