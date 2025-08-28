<?php

namespace App\EventListener;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExceptionListener
{
    private UrlGeneratorInterface $router;
    private RequestStack $requestStack;

    public function __construct(UrlGeneratorInterface $router, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        
        // Get the current request
        $request = $this->requestStack->getCurrentRequest();
        
        // Only handle web requests (not API/console)
        if (!$request || !$request->getSession()) {
            return;
        }
        
        $session = $request->getSession();
        
        // Add flash message with exception details
        $errorMessage = sprintf(
            'An error occurred: %s',
            $exception->getMessage()
        );
        
        $session->getFlashBag()->add('danger', $errorMessage);
        
        // Create redirect response to login page
        $loginUrl = $this->router->generate('login');
        $response = new RedirectResponse($loginUrl);
        
        // Set the response to redirect
        $event->setResponse($response);
    }
}
