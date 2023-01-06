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
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MassActionCommand.
 */
class MassActionCommand extends Command
{
    private LoggerInterface $logger;

    private JobManager $jobManager;

    public function __construct(
        LoggerInterface $logger,
        JobManager $jobManager,
        $name = null
    ) {
        parent::__construct($name);
        $this->logger = $logger;
        $this->jobManager = $jobManager;
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:massaction')
            ->setDescription('Action massive sur les flux')
            ->addArgument('action', InputArgument::REQUIRED, 'Action (rerun, cancel, remove, restore or changeStatus)')
            ->addArgument('dataType', InputArgument::REQUIRED, 'Data type (rule or document)')
            ->addArgument('ids', InputArgument::REQUIRED, 'Rule or document ids') // id séparés par des ";"
            ->addArgument('force', InputArgument::OPTIONAL, 'Force run even if another task is running.')
            ->addArgument('forceAll', InputArgument::OPTIONAL, 'Set Y to process action on all documents (not only open and error ones)')
            ->addArgument('fromStatus', InputArgument::OPTIONAL, 'Get all document with this status(Only with changeStatus action)')
            ->addArgument('toStatus', InputArgument::OPTIONAL, 'Set this status (Only with changeStatus action)')
            ->addArgument('api', InputArgument::OPTIONAL, 'Call from API')
        ;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = $input->getArgument('action');
        $dataType = $input->getArgument('dataType');
        $ids = $input->getArgument('ids');
        $forceAll = $input->getArgument('forceAll');
        $fromStatus = $input->getArgument('fromStatus');
        $toStatus = $input->getArgument('toStatus');
        $api = $input->getArgument('api');
        $force = $input->getArgument('force') ? $input->getArgument('force') : false;
        // to avoid unwanted apostrophes in SQL queries
        $action = str_replace('\'', '', $action);
        $dataType = str_replace('\'', '', $dataType);
        $ids = str_replace('\'', '', $ids);
        $forceAll = str_replace('\'', '', $forceAll);
        $fromStatus = str_replace('\'', '', $fromStatus);
        $toStatus = str_replace('\'', '', $toStatus);
        $api = str_replace('\'', '', $api);

        // Set the API value
        $this->jobManager->setApi((bool) $api);

        $paramJobString = "Mass $action on data type $dataType";

        $data = $this->jobManager->initJob($paramJobString, $force);

        if (false === $data['success']) {
            $output->writeln('1;<error>'.$data['message'].'</error>');
            $this->logger->error($data['message']);

            return 1;
        }

        $output->writeln('1;'.$this->jobManager->getId());  // Do not remove, used for manual job and webservices (display logs)

        // Récupération des paramètres
        if (!in_array($action, ['rerun', 'cancel', 'remove', 'restore', 'changeStatus'])) {
            throw new Exception('Action '.$action.' unknown. Please use action rerun, cancel or remove.');
        }
        if (!in_array($dataType, ['document', 'rule'])) {
            throw new Exception('Data type '.$dataType.' unknown. Please use data type document or rule.');
        }
        if (empty($ids)) {
            throw new Exception('No ids in the command parameters. Please add ids to run this action.');
        }
        $ids = explode(',', $ids);
        if (
            'changeStatus' == $action
            and (
                empty($fromStatus)
                or empty($toStatus)
            )
        ) {
            throw new Exception('fromStatus and toStatus parameters are required for the changeStatus action.');
        }

        // Mass action
        $response = $this->jobManager->massAction($action, $dataType, $ids, $forceAll, $fromStatus, $toStatus);
        if (!empty($this->jobManager->getMessage())) {
            if ($response) {
                $output->writeln('<info>'.$this->jobManager->getMessage().'</info>');
                $this->logger->info($this->jobManager->getMessage());
            } else {
                $output->writeln('<error>'.$this->jobManager->getMessage().'</error>');
                $this->logger->error($this->jobManager->getMessage());
            }
        }

        // Clear job message to avoid duplicate messages
        $this->jobManager->setMessage('');
        // Close job if it has been created
        $responseCloseJob = $this->jobManager->closeJob();

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
