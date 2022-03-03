<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AlertBootstrap implements AlertBootstrapInterface
{
    public const ALERT_PRIMARY = 'primary';
    public const ALERT_SECONDARY = 'secondary';
    public const ALERT_SUCCESS = 'success';
    public const ALERT_DANGER = 'danger';
    public const ALERT_WARNING = 'warning';
    public const ALERT_INFO = 'info';
    public const ALERT_LIGHT = 'light';
    public const ALERT_DARK = 'dark';

    private $flashBag;
    private $translator;

    public function __construct(FlashBagInterface $flashBag, TranslatorInterface $translator)
    {
        $this->flashBag = $flashBag;
        $this->translator = $translator;
    }

    public function primary(string $message): void
    {
        $this->flashBag->add(self::ALERT_PRIMARY, $this->translator->trans($message));
    }

    public function secondary(string $message): void
    {
        $this->flashBag->add(self::ALERT_SECONDARY, $this->translator->trans($message));
    }

    public function success(string $message): void
    {
        $this->flashBag->add(self::ALERT_SUCCESS, $this->translator->trans($message));
    }

    public function danger(string $message): void
    {
        $this->flashBag->add(self::ALERT_DANGER, $this->translator->trans($message));
    }

    public function warning(string $message): void
    {
        $this->flashBag->add(self::ALERT_WARNING, $this->translator->trans($message));
    }

    public function info(string $message): void
    {
        $this->flashBag->add(self::ALERT_INFO, $this->translator->trans($message));
    }

    public function light(string $message): void
    {
        $this->flashBag->add(self::ALERT_LIGHT, $this->translator->trans($message));
    }

    public function dark(string $message): void
    {
        $this->flashBag->add(self::ALERT_DARK, $this->translator->trans($message));
    }
}
