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

use Exception;
use App\Manager\JobManager;
use Psr\Log\LoggerInterface;
use App\Repository\JobRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckJobCommand extends Command
{
    private LoggerInterface $logger;
    private JobManager $jobManager;

    public function __construct(
        LoggerInterface $logger,
        JobManager $jobManager,
        $name = null
    ) {
        $this->logger = $logger;
        $this->jobManager = $jobManager;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:checkjob')
            ->setDescription('check if the job is too long')
            ->addArgument('period', InputArgument::OPTIONAL, 'Default value : 900')
        ;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $period = $input->getArgument('period');

        $data = $this->jobManager->initJob('checkJob '.$period);
        if (false === $data['success']) {
            $output->writeln('0;<error>'.$data['message'].'</error>');
            $this->logger->error($data['message']);
            return 0;
        }
        $this->jobManager->checkJob($period); 
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