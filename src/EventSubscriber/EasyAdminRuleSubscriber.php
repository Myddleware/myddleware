<?php

namespace App\EventSubscriber;

use App\Entity\Rule;
use App\Entity\RuleField;
use App\Repository\ModuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RequestStack;

class EasyAdminRuleSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private ModuleRepository $moduleRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(RequestStack $requestStack, ModuleRepository $moduleRepository, EntityManagerInterface $entityManager)
    {
        $this->requestStack = $requestStack;
        $this->moduleRepository = $moduleRepository;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['addModulesAndFields'],
            BeforeCrudActionEvent::class => [
                ['disableDeleteActions', 1],
                ['beforeShow', 2],
            ],
        ];
    }

    public function addModulesAndFields(BeforeEntityPersistedEvent $event)
    {
        $entity = $event->getEntityInstance();

        if (!($entity instanceof Rule)) {
            return;
        }

        $currentRequest = $this->requestStack->getCurrentRequest();

        if ($currentRequest->request->has('form')) {
            $ruleModulesForm = $currentRequest->request->get('form');
            if (!is_array($ruleModulesForm)) {
                throw new BadRequestException('Modules source IDs missing');
            }

            if (array_key_exists('source', $ruleModulesForm)) {
                $moduleSource = $this->moduleRepository->find($ruleModulesForm['source']);
                $entity->setSourceModule($moduleSource);
            }
            if (array_key_exists('target', $ruleModulesForm)) {
                $moduleTarget = $this->moduleRepository->find($ruleModulesForm['target']);
                $entity->setTargetModule($moduleTarget);
            }

            if (array_key_exists('fieldSelect_target', $ruleModulesForm)
                && array_key_exists('fieldSelect_source', $ruleModulesForm)) {
                // @TODO: for now, we're handling rule fields as if there was only
                // 1 pair of target & source inputs per rule. Later on, we will need
                // to handle the real use case which is several source fields & several target fields too
                $targetField = $ruleModulesForm['fieldSelect_target'];
                $sourceFields = $ruleModulesForm['fieldSelect_source'];
                $ruleField = new RuleField();
                $ruleField->setRule($entity);
                $ruleField->setTarget($targetField);
                $sourceFieldsString = implode(';', $sourceFields);
                $ruleField->setSource($sourceFieldsString);
                // @TODO: next steps will also need to include setting potential formulae
                $entity->addField($ruleField);
                $this->entityManager->persist($ruleField);
                $this->entityManager->persist($entity);
            }
        }
        $this->entityManager->flush();
    }

    public function disableDeleteActions(BeforeCrudActionEvent $event)
    {
        if (!$adminContext = $event->getAdminContext()) {
            return;
        }
        if (!$crudDto = $adminContext->getCrud()) {
            return;
        }

        if (Rule::class !== $crudDto->getEntityFqcn()) {
            return;
        }

        $rule = $adminContext->getEntity()->getInstance();

        // absolutely necessary to check against Rule entity because this subscriber
        // is actually called for ALL entities before each Crud generation
        if ($rule instanceof Rule && !$rule->getDeleted()) {
            $crudDto->getActionsConfig()->disableActions([
                Action::BATCH_DELETE,
                Action::DELETE,
            ]);
        }
        $currentlyEnabledActions = $crudDto->getActionsConfig()->getActions();
        if (!$deleteAction = $currentlyEnabledActions[Action::DELETE] ?? null) {
            return;
        }

        $deleteAction->setDisplayCallable(function (Rule $rule) {
            return $rule->getDeleted();
        });

        if (!$batchDeleteAction = $currentlyEnabledActions[Action::BATCH_DELETE] ?? null) {
            return false;
        }

        // @TODO: find out how to make this happen (currently not being called) in order to
        // disable the batch delete action altogether
        $batchDeleteAction->setDisplayCallable(function (Rule $rule) {
            return $rule->getDeleted();
        });
    }

    public function beforeShow(BeforeCrudActionEvent $event)
    {
        $crud = $event->getAdminContext()->getCrud();
        if (Rule::class !== $crud->getEntityFqcn() || 'detail' !== $crud->getCurrentAction()) {
            return;
        }
        $entityDto = $event->getAdminContext()->getEntity();
        /** @var Rule $rule */
        $rule = $entityDto->getInstance();
        $moduleSource = $rule->getSourceModule();
        $moduleTarget = $rule->getTargetModule();
        $fields = $rule->getFields();
    }
}
