<?php

namespace App\Doctrine;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Psr\Log\LoggerInterface;
use SensitiveParameter;

class DebugSqlDriver extends AbstractDriverMiddleware
{
    private LoggerInterface $logger;

    public function __construct(
        \Doctrine\DBAL\Driver $wrappedDriver,
        LoggerInterface $logger
    ) {
        parent::__construct($wrappedDriver);
        $this->logger = $logger;
    }

    public function connect(
        #[SensitiveParameter]
        array $params
    ) {
        return new DebugSqlConnection(parent::connect($params), $this->logger);
    }
}
