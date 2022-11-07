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

use App\Repository\JobSchedulerRepository;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class JobSchedulerCommand extends Command
{
    private JobSchedulerRepository $jobSchedulerRepository;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        JobSchedulerRepository $jobSchedulerRepository,
        LoggerInterface $logger,
        $name = null
    ) {
        parent::__construct($name);
        $this->entityManager = $entityManager;
        $this->jobSchedulerRepository = $jobSchedulerRepository;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:jobScheduler')
            ->setDescription('Run every job in the scheduler')
        ;
    }

    // Run the job scheduler
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $env = $input->getParameterOption(['--env', '-e'], getenv('SYMFONY_ENV') ?: 'dev');
        $jobSchedulers = $this->jobSchedulerRepository->findJobsToRun();
        foreach ($jobSchedulers as $jobScheduler) {
            try {
                $command = $this->getApplication()->find('myddleware:'.$jobScheduler->getCommand());
                $arguments = ['--env' => $env];
                if ($jobScheduler->getParamName1()) {
                    $arguments[$jobScheduler->getParamName1()] = $jobScheduler->getParamValue1();
                }
                if ($jobScheduler->getParamName2()) {
                    $arguments[$jobScheduler->getParamName2()] = $jobScheduler->getParamValue2();
                }

                $jobSchedulerInput = new ArrayInput($arguments);
                $jobSchedulerOutput = new BufferedOutput();

                $now = new DateTime('now', new DateTimeZone('GMT'));
                $now->setTime($now->format('H'), $now->format('i'), 0);

                // You can use NullOutput() if you don't need the output
                $command->run($jobSchedulerInput, $jobSchedulerOutput);

                $content = $jobSchedulerOutput->fetch();
                // Send output to the logfile if debug mode selected
                if (!empty($content)) {
                    $this->logger->debug(print_r($content, true));
                }

                // Update the lastrun date for the job (GMT timezone)
                $jobScheduler->setLastRun($now);
                $this->entityManager->persist($jobScheduler);
                $this->entityManager->flush();
            } catch (\Exception $e) {
                echo $e->getMessage().chr(10);
                $io->error($e->getMessage());

                return 1;
            }
        }

        return 0;
    }
}
