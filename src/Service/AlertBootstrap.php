<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class AlertBootstrap implements AlertBootstrapInterface
{
    const ALERT_PRIMARY = 'primary';
    const ALERT_SECONDARY = 'secondary';
    const ALERT_SUCCESS = 'success';
    const ALERT_DANGER = 'danger';
    const ALERT_WARNING = 'warning';
    const ALERT_INFO = 'info';
    const ALERT_LIGHT = 'light';
    const ALERT_DARK = 'dark';

    private RequestStack $requestStack;
    private TranslatorInterface $translator;

    public function __construct(RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    private function getFlashBag()
    {
        return $this->requestStack->getSession()->getFlashBag();
    }

    public function primary(string $message): void
    {
        $this->getFlashBag()->add(self::ALERT_PRIMARY, $this->translator->trans($message));
    }

    public function secondary(string $message): void
    {
        $this->getFlashBag()->add(self::ALERT_SECONDARY, $this->translator->trans($message));
    }

    public function success(string $message): void
    {
        $this->getFlashBag()->add(self::ALERT_SUCCESS, $this->translator->trans($message));
    }

    public function danger(string $message): void
    {
        $this->getFlashBag()->add(self::ALERT_DANGER, $this->translator->trans($message));
    }

    public function warning(string $message): void
    {
        $this->getFlashBag()->add(self::ALERT_WARNING, $this->translator->trans($message));
    }

    public function info(string $message): void
    {
        $this->getFlashBag()->add(self::ALERT_INFO, $this->translator->trans($message));
    }

    public function light(string $message): void
    {
        $this->getFlashBag()->add(self::ALERT_LIGHT, $this->translator->trans($message));
    }

    public function dark(string $message): void
    {
        $this->getFlashBag()->add(self::ALERT_DARK, $this->translator->trans($message));
    }
}
