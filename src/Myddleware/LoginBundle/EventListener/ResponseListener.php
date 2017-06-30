<?php
namespace Myddleware\LoginBundle\EventListener;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {   
        $event->getResponse()->headers->set('x-frame-options', 'deny');
    }   
}

