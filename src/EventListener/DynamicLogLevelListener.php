<?php

namespace App\EventListener;

use App\Repository\ConfigRepository;
use Monolog\Handler\AbstractHandler;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class DynamicLogLevelListener
{
    private ConfigRepository $configRepository;
    private LoggerInterface $logger;
    private bool $applied = false;
    private static bool $debugModeEnabled = false;

    public static function isDebugModeEnabled(): bool
    {
        return self::$debugModeEnabled;
    }

    public function __construct(
        ConfigRepository $configRepository,
        LoggerInterface $logger
    ) {
        $this->configRepository = $configRepository;
        $this->logger = $logger;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->applyDynamicLogLevel();
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $this->applyDynamicLogLevel();
    }

    private function applyDynamicLogLevel(): void
    {
        if ($this->applied) {
            return;
        }
        $this->applied = true;

        try {
            $debugEnabled = $this->configRepository->getDebugMode();
            self::$debugModeEnabled = $debugEnabled;

            if (!$debugEnabled) {
                return;
            }

            $level = $this->configRepository->getLogLevel();
            $monologLevel = Level::fromName($level);

            if (!$this->logger instanceof \Monolog\Logger) {
                return;
            }

            foreach ($this->logger->getHandlers() as $handler) {
                if ($handler instanceof AbstractHandler) {
                    $handler->setLevel($monologLevel);
                }
            }
        } catch (\Throwable $e) {
        }
    }
}
