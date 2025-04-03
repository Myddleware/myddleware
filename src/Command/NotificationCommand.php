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
use App\Manager\NotificationManager;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class NotificationCommand.
 */
class NotificationCommand extends Command
{
    private NotificationManager $notificationManager;
    private JobManager $jobManager;

    public function __construct(
        NotificationManager $notificationManager,
        JobManager $jobManager,
        $name = null)
    {
        parent::__construct($name);
        $this->notificationManager = $notificationManager;
        $this->jobManager = $jobManager;
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:notification')
            ->setDescription('Send notification')
            ->addArgument('type', InputArgument::OPTIONAL, 'Notification type')
        ;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // We don't create job for alert
            if ('alert' == $input->getArgument('type')) {
                $output->writeln('<info>Sending alert notification...</info>');
                $this->notificationManager->sendAlert();
                $output->writeln('<info>Alert notification sent successfully!</info>');
            }
            // Standard notification
            else {
                $output->writeln('<info>Initializing notification job...</info>');
                $data = $this->jobManager->initJob('notification '.$input->getArgument('type'));

                if (false === $data['success']) {
                    $output->writeln('<error>'.$data['message'].'</error>');
                    return Command::FAILURE;
                }

                $output->writeln('<info>Sending notification...</info>');
                $this->notificationManager->sendNotification();
                // Close job if it has been created
                $this->jobManager->closeJob();
                $output->writeln('<info>Notification sent successfully!</info>');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
            return Command::FAILURE;
        }
    }
}
