<?php

namespace App\EventSubscriber;

use App\Entity\Rule;
use App\Repository\ModuleRepository;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RequestStack;

class EasyAdminRuleSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private ModuleRepository $moduleRepository;

    public function __construct(RequestStack $requestStack, ModuleRepository $moduleRepository)
    {
        $this->requestStack = $requestStack;
        $this->moduleRepository = $moduleRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['addModules'],
            BeforeCrudActionEvent::class => ['beforeShow'],
        ];
    }

    public function addModules(BeforeEntityPersistedEvent $event)
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
        }
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
        /**
         * @TODO : add a property & getter for rulefields on Rule entity
         */
        $fields = $rule->getRuleFields();
    }
}
