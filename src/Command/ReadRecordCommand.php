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

use App\Entity\Job;
use App\Manager\JobManager;
use App\Repository\RuleRepository;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ReadRecordCommand.
 *
 * @package App\Command
 *
 *
 */
class ReadRecordCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var JobManager
     */
    private $jobManager;
    /**
     * @var RuleRepository
     */
    private $ruleRepository;

    public function __construct(
        LoggerInterface $logger,
        JobManager $jobManager,
        RuleRepository $ruleRepository,
        $name = null
    ) {
        $this->logger = $logger;
        $this->jobManager = $jobManager;
        $this->ruleRepository = $ruleRepository;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:readrecord')
            ->setDescription('Read specific records for a rule')
            ->addArgument('ruleId', InputArgument::REQUIRED, 'Rule used to read the records')
            ->addArgument('filterQuery', InputArgument::REQUIRED, 'Filter used to read data in the source application, eg : id')
            ->addArgument('filterValues', InputArgument::REQUIRED, 'Values corresponding to the fileter separated by comma, eg : 1256,4587')
            ->addArgument('api', InputArgument::OPTIONAL, 'Call from API')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ruleId = $input->getArgument('ruleId');
        $filterQuery = $input->getArgument('filterQuery');
        $filterValues = $input->getArgument('filterValues');
        $filterValuesArray = explode(',', $filterValues);
        if (empty($filterValuesArray)) {
            throw new Exception('No filter values found. Please add values to run this action.');
        }
        $rule = $this->ruleRepository->findOneBy(['id' => $ruleId, 'deleted' => false]);
        if (null === $rule) {
            throw new Exception('No rule found. Please add values to run this action.');
        }
        $api = $input->getArgument('api');

        $data = $this->jobManager->initJob('read records wilth filter '.$filterQuery.' IN ('.$filterValues.')', $api);

        if (false === $data['success']) {
            $output->writeln('0;<error>'.$data['message'].'</error>');
            $this->logger->error($data['message']);

            return 0;
        }
        /** @var Job $job */
        $job = $data['job'];

        $this->jobManager->readRecord($rule, $filterQuery, $filterValuesArray);
        $output->writeln($job->getId());  // This is requiered to display the log (link creation with job id) when the job is run manually

        // Close job if it has been created
        $responseCloseJob = $this->jobManager->closeJob($job);

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
