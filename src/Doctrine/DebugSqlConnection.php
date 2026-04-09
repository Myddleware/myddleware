<?php

namespace App\Doctrine;

use App\EventListener\DynamicLogLevelListener;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Psr\Log\LoggerInterface;

class DebugSqlConnection extends AbstractConnectionMiddleware
{
    private LoggerInterface $logger;

    public function __construct(ConnectionInterface $connection, LoggerInterface $logger)
    {
        parent::__construct($connection);
        $this->logger = $logger;
    }

    public function prepare(string $sql): Statement
    {
        return new DebugSqlStatement(parent::prepare($sql), $this->logger, $sql);
    }

    public function query(string $sql): Result
    {
        if (DynamicLogLevelListener::isDebugModeEnabled()) {
            $this->logger->critical(sprintf('[DEBUG SQL] %s', $sql));
        }

        return parent::query($sql);
    }

    public function exec(string $sql): int
    {
        if (DynamicLogLevelListener::isDebugModeEnabled()) {
            $this->logger->critical(sprintf('[DEBUG SQL] %s', $sql));
        }

        return parent::exec($sql);
    }
}
