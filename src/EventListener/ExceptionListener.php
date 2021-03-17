<?php

namespace App\EventListener;

use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExceptionListener
{

    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public function onKernelException(ExceptionEvent $event)
    {	
        
        $exception = $event->getThrowable();
  
        if ($exception instanceof ConnectionException) {

            $urlInstall=  $this->router->generate('install_requirements');
            
            $response = new RedirectResponse($urlInstall);
            $event->setResponse($response);
          
        } 

    }
}
