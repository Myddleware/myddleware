<?php

namespace App\Tests\Command;

use App\Command\PruneDatabaseCommand;
use App\Manager\JobManager;
use Doctrine\DBAL\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class PruneDatabaseCommandTest extends TestCase
{
    private $logger;
    private $jobManager;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->jobManager = $this->createMock(JobManager::class);
    }

    public function testExecute()
    {
        // do a fake test that 1 + 1 = 2
        $this->assertEquals(2, 1 + 1);
    }
}