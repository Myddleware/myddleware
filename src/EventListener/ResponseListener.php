<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseListener
{
    public function onKernelResponse(ResponseEvent $event)
    {
        $event->getResponse()->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $event->getResponse()->headers->set('X-XSS-Protection', '1; mode=block');
        $event->getResponse()->headers->set('X-Content-Type-Options', 'nosniff');
    }
}
