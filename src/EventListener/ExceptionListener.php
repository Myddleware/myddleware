<?php

namespace App\EventListener;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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

        // we intercept exceptions to do with the fact that user hasn't set up his database parameters yet
        if ($exception instanceof ConnectionException || $exception instanceof TableNotFoundException) {
            $urlInstall = $this->router->generate('install_requirements');
            // redirect to installation page
            $response = new RedirectResponse($urlInstall);
            $event->setResponse($response);
        }

        // Gracefully redirect on access denied HTTP exceptions
        if ($exception instanceof AccessDeniedHttpException) {
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add('error-permission', 'You do not have permission to access this page.');

            $request = $this->requestStack->getCurrentRequest();
            $referer = $request->headers->get('referer');

            // Redirect to the referring page, or home if no referer
            $url = $referer ?: $this->router->generate('home');
            $response = new RedirectResponse($url);
            $event->setResponse($response);
        }
    }
}
