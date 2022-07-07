<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestEventSubscriber implements EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event)
    {
        // Let's clear the special values to protect against Format String Attack
        $request = $event->getRequest();

        $params = $request->request->all();

        $forbiddenString = ['%%', '%p', '%d', '%c', '%u', '%x', '%s', '%n'];
        $replaceString = ['', '', '', '', '', '', '', ''];

        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $value = str_replace($forbiddenString, $replaceString, $value);
            } elseif (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    if (is_string($value2)) {
                        $value[$key2] = str_replace($forbiddenString, $replaceString, $value2);
                    } elseif (is_array($value2)) {
                        foreach ($value2 as $key3 => $value3) {
                            if (is_string($value3)) {
                                $value[$key2][$key3] = str_replace($forbiddenString, $replaceString, $value3);
                            } elseif (is_array($value3)) {
                                foreach ($value3 as $key4 => $value4) {
                                    if (is_string($value4)) {
                                        $value[$key2][$key3][$key4] = str_replace($forbiddenString, $replaceString, $value4);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $request->request->set($key, $value);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }
}
