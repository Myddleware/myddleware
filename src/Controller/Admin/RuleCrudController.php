<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Rule;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RuleCrudController extends AbstractCrudController
{
    public const ACTION_DUPLICATE = 'duplicate';

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addWebpackEncoreEntry('admin');
    }

    public static function getEntityFqcn(): string
    {
        return Rule::class;
    }

    public function createEntity(string $entityFqcn): Rule
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
            ->reorder(Crud::PAGE_DETAIL, [Action::EDIT, self::ACTION_DUPLICATE])
        ;
    }

    public function configureFields(string $pageName): iterable
    {

        return [
            IdField::new('id')->hideOnForm(),
            FormField::addTab('Details')->setIcon('home'),
            BooleanField::new('active'),
            TextField::new('name'),
            TextField::new('nameSlug')->hideOnForm(),
            FormField::addPanel('Fields mapping')->collapsible()
                ->setHelp('You must map at least 1 pair of source/target fields. You can only map 1 source & 1 target module, if you need to use other modules, please create a new rule.'),
            AssociationField::new('connectorSource')
                ->addCssClass('rule')
                ->setFormTypeOptions([
                    'row_attr' => [
                        'data-controller' => 'rule',
                        // 'data-solution-info-url-value' => $getModulesController,
                    ],
                    'attr' => [
                        'data-action' => 'change->rule#onSelectSource',
                        'data-rule-target' => 'connector',
                        'class' => 'd-flex',
                    ],
                ])
                ->setHelp('Modules disponibles: '),
            AssociationField::new('connectorTarget')
                ->addCssClass('rule')
                ->setFormTypeOptions([
                    'row_attr' => [
                        'data-controller' => 'rule',
                        // 'data-solution-info-url-value' => $getModulesController,
                    ],
                    'attr' => [
                        'data-action' => 'change->rule#onSelectTarget',
                        'data-rule-target' => 'connector',
                        'class' => 'd-flex',
                    ],
                ])
                ->setHelp('Modules disponibles: '),
            AssociationField::new('sourceModule')->hideOnForm(),
            AssociationField::new('targetModule')->hideOnForm(),
            AssociationField::new('fields')->hideOnForm(),
            FormField::addTab('Parameters'),
            AssociationField::new('params'),
            FormField::addTab('Filters'),
            AssociationField::new('filters'),
            FormField::addTab('Formulae'),
//            CodeEditorField::new('customFormula')->setLanguage('php'),
//            AssociationField::new('formula'),
            FormField::addTab('Relationships'),
            AssociationField::new('relationships'),
            FormField::addTab('Order'),
            AssociationField::new('orders'),
            FormField::addTab('Audits'),
            AssociationField::new('audits'),

            BooleanField::new('deleted')->renderAsSwitch(false)->hideOnForm(),
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
