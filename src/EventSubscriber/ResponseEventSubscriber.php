<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseEventSubscriber implements EventSubscriberInterface
{
    public function onKernelResponse(ResponseEvent $event)
    {
        $event->getResponse()->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $event->getResponse()->headers->set('X-XSS-Protection', '1; mode=block');
        $event->getResponse()->headers->set('X-Content-Type-Options', 'nosniff');
    }
    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }
}
