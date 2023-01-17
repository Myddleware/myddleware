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
    private JobRepository $jobRepository;

    public function __construct(
        LoggerInterface $logger,
        JobManager $jobManager,
        JobRepository $jobRepository,
        $name = null
    ) {
        $this->logger = $logger;
        $this->jobManager = $jobManager;
        $this->jobRepository = $jobRepository;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:checkjob')
            ->setDescription('check job every 900 secondes')
            ->addArgument('jobId', InputArgument::OPTIONAL)
        ;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jobId = $input->getArgument('jobId');
        $force = 1;
        // if (empty($force)) {
        //     $force = 1;
        // }

        $rule = $this->jobRepository->findOneBy(['id' => $jobId]);
        // if (null === $rule) {
        //     throw new Exception('No rule found. Please add values to run this action.');
        // }
        // $api = $input->getArgument('api');

        // // Set the API value
        // $this->jobManager->setApi((bool) $api);

        $data = $this->jobManager->initJob('test', 1);
        if (false === $data['success']) {
            $output->writeln('0;<error>'.$data['message'].'</error>');
            $this->logger->error($data['message']);

            return 0;
        }

        // If the query is by id, we use the flag that leads to massIdRerun()
        // Otherwise we use the regular rerun
       // if ()) {
            $this->jobManager->checkJob(); 

        // }else $this->jobManager->checkJob();

        // Close job if it has been created
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
