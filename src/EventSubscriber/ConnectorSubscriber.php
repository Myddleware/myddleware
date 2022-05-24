<?php

namespace App\EventSubscriber;

use App\Entity\Connector;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConnectorSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityPersistedEvent::class => ['setCreatedAt'],
            BeforeEntityUpdatedEvent::class => ['setUpdatedAt'],
        ];
    }

    public function setCreatedAt(BeforeEntityPersistedEvent $event)
    {
        $entity = $event->getEntityInstance();
        if (!($entity instanceof Connector)) {
            return;
        }
        $now = new \DateTimeImmutable('now');
        $entity->setCreatedAt($now);
        $entity->setUpdatedAt($now);
    }

    public function setUpdatedAt(BeforeEntityUpdatedEvent $event)
    {
        $entity = $event->getEntityInstance();
        if (!($entity instanceof Connector)) {
            return;
        }
        $now = new \DateTimeImmutable('now');
        $entity->setUpdatedAt($now);
    }
}
