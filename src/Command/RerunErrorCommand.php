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
 * Class RerunErrorCommand.
 */
class RerunErrorCommand extends Command
{
    private JobManager $jobManager;
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        JobManager $jobManager,
        string $name = null
    ) {
        parent::__construct($name);
        $this->logger = $logger;
        $this->jobManager = $jobManager;
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:rerunerror')
            ->setDescription('Synchronisation des données')
            ->addArgument('limit', InputArgument::REQUIRED, 'Nombre maximum de flux en erreur traité')
            ->addArgument('attempt', InputArgument::REQUIRED, 'Nombre maximum de tentative')
            ->addArgument('api', InputArgument::OPTIONAL, 'Call from API')
        ;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $input->getArgument('limit');
        $attempt = $input->getArgument('attempt');
        $api = $input->getArgument('api');

        // Set the API value
        $this->jobManager->setApi((bool) $api);

        $data = $this->jobManager->initJob("rerunerror $limit $attempt $api");

        if (false === $data['success']) {
            $output->writeln('0;<error>'.$data['message'].'</error>');
            $this->logger->error($data['message']);

            return 0;
        }
        $this->jobManager->setApi($api);
        $output->writeln($this->jobManager->getId());

        try {
            // Premier paramètre : limite d'enregistrement traités
            // Deuxième paramètre, limite d'erreur : si un flux a plus de tentative que le paramètre il n'est pas relancé
            $this->jobManager->runError($limit, $attempt);
            // Clear message in case this task is run by jobscheduler. In this case message has to be refreshed.
        } catch (Exception $e) {
            $message = $e->getMessage();
            $output->writeln('<error>'.$message.'</error>');
        }

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
