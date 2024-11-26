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
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class PruneDatabaseCommand extends Command
{
    private LoggerInterface $logger;
    private JobManager $jobManager;

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
            ->addArgument('nbDays', InputArgument::REQUIRED, 'Number of days to prune data for');
        ;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nbDays = $input->getArgument('nbDays');
        $data = $this->jobManager->initJob('prunedatabase '.$nbDays);

        if (false === $data['success']) {
            $output->writeln('0;<error>'.$data['message'].'</error>');
            $this->logger->error($data['message']);

            return 0;
        }

        $this->jobManager->pruneDatabase($nbDays);

        $responseCloseJob = $this->jobManager->closeJob();

        if ($responseCloseJob) {
            $this->jobManager->setMessage('The database has been pruned successfully');
        } else {
            $this->jobManager->setMessage('An error occurred while pruning the database');
        }

        if (!empty($this->jobManager->getMessage())) {
            if ($responseCloseJob) {
                $output->writeln('<info>'.$this->jobManager->getMessage().'</info>');
                $this->logger->info($this->jobManager->getMessage());
            } else {
                $output->writeln('<error>'.$this->jobManager->getMessage().'</error>');
                $this->logger->error($this->jobManager->getMessage());
            }
        }

        return 0;
    }
}
