<?php

namespace App\Service;

use App\EventListener\DynamicLogLevelListener;
use Psr\Log\LoggerInterface;

class DebugLogger
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function logStart(string $class, string $method, array $params = []): void
    {
        if (!DynamicLogLevelListener::isDebugModeEnabled()) {
            return;
        }

        $this->logger->critical(sprintf(
            '[DEBUG] ENTERING %s::%s | Params: %s',
            $this->shortClass($class),
            $method,
            $this->serializeParams($params)
        ));
    }

    public function logEnd(string $class, string $method, $result = null): void
    {
        if (!DynamicLogLevelListener::isDebugModeEnabled()) {
            return;
        }

        $this->logger->critical(sprintf(
            '[DEBUG] EXITING %s::%s | Return: %s',
            $this->shortClass($class),
            $method,
            $this->serializeValue($result)
        ));
    }

    private function shortClass(string $class): string
    {
        $short = strrchr($class, '\\');

        return $short ? substr($short, 1) : $class;
    }

    private function serializeParams(array $params): string
    {
        $serialized = [];
        foreach ($params as $key => $value) {
            $serialized[$key] = $this->serializeValue($value);
        }

        return json_encode($serialized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function serializeValue($value): string
    {
        if (is_null($value)) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            return strlen($value) > 200 ? substr($value, 0, 200) . '...' : $value;
        }
        if (is_array($value)) {
            $count = count($value);
            $keys = array_keys($value);
            $keyPreview = array_slice($keys, 0, 5);

            return sprintf('array(%d items, keys: [%s]%s)', $count, implode(', ', $keyPreview), $count > 5 ? '...' : '');
        }
        if (is_object($value)) {
            return sprintf('object(%s)', get_class($value));
        }

        return gettype($value);
    }
}
