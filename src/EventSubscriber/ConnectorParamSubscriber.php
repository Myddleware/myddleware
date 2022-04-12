<?php

namespace App\EventSubscriber;

use App\Entity\ConnectorParam;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConnectorParamSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityPersistedEvent::class => ['setConnector'],
        ];
    }

    public function setConnector(BeforeEntityPersistedEvent $event)
    {
        $entity = $event->getEntityInstance();

        if (!($entity instanceof ConnectorParam)) {
            return;
        }
        //TODO: find a way to setconnector() when cascade persist from connector is used (from connectorcrudcontroller)
    }
}
