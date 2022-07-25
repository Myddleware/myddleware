<?php

namespace App\EventSubscriber;

use App\Entity\Connector;
use App\Entity\ConnectorParam;
use App\Form\DataTransformer\ConnectorParamsValueTransformer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EasyAdminConnectorSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;
    private ConnectorParamsValueTransformer $connectorParamsValueTransformer;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager, ConnectorParamsValueTransformer $connectorParamsValueTransformer)
    {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->connectorParamsValueTransformer = $connectorParamsValueTransformer;
    }

    public static function getSubscribedEvents()
    {
        return [
            AfterEntityPersistedEvent::class => ['createConnectorParams'],
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
}