<?php

namespace App\EventListener;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExceptionListener
{
    private UrlGeneratorInterface $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        // we intercept exceptions to do with the fact that the app cannot access the database
        if ($exception instanceof ConnectionException || $exception instanceof TableNotFoundException) {
            // this will redirect to install if no DB set in .env.local or to login if DB set but not accessible
            $urlInstall = $this->router->generate('app_setup');
            // redirect to installation page
            $response = new RedirectResponse($urlInstall);
            $event->setResponse($response);
        }
    }
}
