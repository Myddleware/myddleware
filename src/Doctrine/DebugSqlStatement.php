<?php

namespace App\Doctrine;

use App\EventListener\DynamicLogLevelListener;
use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;

class DebugSqlStatement extends AbstractStatementMiddleware
{
    private LoggerInterface $logger;
    private string $sql;
    private array $params = [];
    private array $types = [];

    public function __construct(Statement $wrappedStatement, LoggerInterface $logger, string $sql)
    {
        parent::__construct($wrappedStatement);
        $this->logger = $logger;
        $this->sql = $sql;
    }

    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        $this->params[$param] = $value;
        $this->types[$param] = $type;

        return parent::bindValue($param, $value, $type);
    }

    public function execute($params = null): Result
    {
        if (DynamicLogLevelListener::isDebugModeEnabled()) {
            $allParams = $params ?? $this->params;
            $this->logger->critical(sprintf(
                '[DEBUG SQL] %s | Params: %s',
                $this->sql,
                json_encode($this->sanitizeParams($allParams), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ));
        }

        return parent::execute($params);
    }

    private function sanitizeParams(array $params): array
    {
        $sanitized = [];
        foreach ($params as $key => $value) {
            if (is_string($value) && strlen($value) > 200) {
                $sanitized[$key] = substr($value, 0, 200) . '...';
            } elseif (is_object($value)) {
                $sanitized[$key] = sprintf('object(%s)', get_class($value));
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
