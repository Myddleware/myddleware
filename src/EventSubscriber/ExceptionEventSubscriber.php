<?php

namespace App\EventSubscriber;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExceptionEventSubscriber implements EventSubscriberInterface
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
            $urlInstall = $this->router->generate('app_install_requirements');
            // redirect to installation page
            $response = new RedirectResponse($urlInstall);
            $event->setResponse($response);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }
}
