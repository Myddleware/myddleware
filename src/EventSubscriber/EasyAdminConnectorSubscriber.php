<?php

namespace App\EventSubscriber;

use App\Entity\Connector;
use App\Entity\ConnectorParam;
use App\Form\DataTransformer\ConnectorParamsValueTransformer;
use App\Repository\ConnectorParamRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EasyAdminConnectorSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;
    private ConnectorParamRepository $connectorParamRepository;
    private ConnectorParamsValueTransformer $connectorParamsValueTransformer;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager, ConnectorParamsValueTransformer $connectorParamsValueTransformer, ConnectorParamRepository $connectorParamRepository)
    {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->connectorParamsValueTransformer = $connectorParamsValueTransformer;
        $this->connectorParamRepository = $connectorParamRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            AfterEntityPersistedEvent::class => ['createConnectorParams'],
            AfterEntityUpdatedEvent::class => ['updateConnectorParams']
        ];
    }

    public function createConnectorParams(AfterEntityPersistedEvent $event)
    {
        $entity = $event->getEntityInstance();

        if (!($entity instanceof Connector)) {
            return;
        }

        $currentRequest = $this->requestStack->getCurrentRequest();

        if ($currentRequest->request->has('form')) {
            $connectorParamsForm = $currentRequest->request->get('form');

            foreach ($connectorParamsForm as $paramKey => $paramValue) {
                if ('_token' === $paramKey) {
                    continue;
                }

                $connectorParam = new ConnectorParam();
                $connectorParam->setName($paramKey);
                $connectorParam->setValue($paramValue);
                $connectorParam->setConnector($entity);
                $connectorParam = $this->connectorParamsValueTransformer->reverseTransform($connectorParam);

                $this->entityManager->persist($connectorParam);
            }
        }

        $this->entityManager->flush();
    }

    public function updateConnectorParams(AfterEntityUpdatedEvent $event)
    {
        $entity = $event->getEntityInstance();

        if (!($entity instanceof Connector)) {
            return;
        }

        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest->request->has('form')) {
            $connectorParamsForm = $currentRequest->request->get('form');

            foreach ($connectorParamsForm as $paramKey => $paramValue) {
                if ('_token' === $paramKey) {
                    continue;
                }

                $connectorParam = $this->connectorParamRepository->findOneBy([
                    'name' => $paramKey,
                    'connector' => $entity
                ]);
                $connectorParam->setValue($paramValue);
                $connectorParam = $this->connectorParamsValueTransformer->reverseTransform($connectorParam);
                $this->entityManager->persist($connectorParam);
            }
        }

        $this->entityManager->flush();
    }
}