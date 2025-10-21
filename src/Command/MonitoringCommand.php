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

use App\Manager\ToolsManager;

class MonitoringCommand extends Command
{
    private LoggerInterface $logger;
    private JobManager $jobManager;
	private ToolsManager $toolsManager;

    public function __construct(
        LoggerInterface $logger,
        JobManager $jobManager,
		ToolsManager $toolsManager,
        $name = null
    ) {
        $this->logger = $logger;
        $this->jobManager = $jobManager;
		$this->toolsManager = $toolsManager;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:monitoring')
            ->setDescription('Export technical data from Myddleware to monitoring tool')
        ;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
		if (!$this->toolsManager->isPremium()) {
            $output->writeln('This feature requires a premium license.');
            return Command::FAILURE;
        }

        $data = $this->jobManager->initJob('monitoring');
        if (false === $data['success']) {
            $output->writeln('0;<error>'.$data['message'].'</error>');
            $this->logger->error($data['message']);
            return 0;
        }
        $this->jobManager->monitoring(); 
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