<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com

 This file is part of Myddleware.

 Myddleware is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Myddleware is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
*********************************************************************************/

namespace App\Command;

use App\Manager\JobManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PruneDatabaseCommand.
 */
class PruneDatabaseCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var JobManager
     */
    private $jobManager;

    public function __construct(
        LoggerInterface $logger,
        JobManager $jobManager,
        string $name = null
    ) {
        $this->logger = $logger;
        $this->jobManager = $jobManager;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:prunedatabase')
            ->setDescription('Remove all data flagged as deleted in Myddleware')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = $this->jobManager->initJob('Prune database');

        if (false === $data['success']) {
            $output->writeln('0;<error>'.$data['message'].'</error>');
            $this->logger->error($data['message']);

            return 0;
        }

        $this->jobManager->pruneDatabase();

        $responseCloseJob = $this->jobManager->closeJob();

        if (!empty($responseCloseJob['message'])) {
            if ($responseCloseJob['success']) {
                $output->writeln('<info>'.$responseCloseJob['message'].'</info>');
                $this->logger->info($responseCloseJob['message']);
            } else {
                $output->writeln('<error>'.$responseCloseJob['message'].'</error>');
                $this->logger->error($responseCloseJob['message']);
            }
        }

        return 1;
    }
}
