<?php
namespace Myddleware\LoginBundle\EventListener;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {   
        $event->getResponse()->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $event->getResponse()->headers->set('X-XSS-Protection', '1; mode=block');  
        $event->getResponse()->headers->set('X-Content-Type-Options', 'nosniff');
    }   
}

