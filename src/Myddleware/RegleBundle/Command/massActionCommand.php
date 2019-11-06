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

namespace Myddleware\RegleBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class massActionCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('myddleware:massaction')
            ->setDescription('Action massive sur les flux')
            ->addArgument('action', InputArgument::REQUIRED, "Action (rerun, cancel, remove, restore or changeStatus)")
            ->addArgument('dataType', InputArgument::REQUIRED, "Data type (rule or doculent)") 
            ->addArgument('ids', InputArgument::REQUIRED, "Rule or document ids") // id séparés par des ";"
            ->addArgument('forceAll', InputArgument::OPTIONAL, "Set Y to process action on all documents (not only open and erro ones)") 
            ->addArgument('fromStatus', InputArgument::OPTIONAL, "Get all document with this status(Only with changeStatus action)")
            ->addArgument('toStatus', InputArgument::OPTIONAL, "Set this status (Only with changeStatus action)")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$step = 1;
		try {		
			$logger = $this->getContainer()->get('logger');
			// Récupération du Job
			$job = $this->getContainer()->get('myddleware_job.job');
			// Clear message in case this task is run by jobscheduler. In this case message has to be refreshed.
			$job->message = '';	
			
			$action = $input->getArgument('action');
			$dataType = $input->getArgument('dataType');
			$ids = $input->getArgument('ids');			
			$forceAll = $input->getArgument('forceAll');			
			$fromStatus = $input->getArgument('fromStatus');			
			$toStatus = $input->getArgument('toStatus');			
			
			if ($job->initJob('Mass '.$action.' on data type '.$dataType)) {
				$output->writeln( $job->id );  // Ne pas supprimer car nécessaire pour afficher les log d'un job manuel
			
				// Récupération des paramètres
				if (!in_array($action, array('rerun', 'cancel', 'remove', 'restore', 'changeStatus'))) {
					throw new \Exception ('Action '.$action.' unknown. Please use action rerun, cancel or remove.');
				}
				if (!in_array($dataType, array('document', 'rule'))) {
					throw new \Exception ('Data type '.$dataType.' unknown. Please use data type document or rule.');
				}
				if (empty($ids)) {
					throw new \Exception ('No ids in the command parameters. Please add ids to run this action.');
				}
				if (
						$action == 'changeStatus'
					AND (
							empty($fromStatus)
						 OR empty($toStatus)
					)
				) {
					throw new \Exception ('fromStatus and toStatus parameters are required for the changeStatus action.');
				}

				// Mass action
				$job->massAction($action, $dataType, $ids, $forceAll, $fromStatus, $toStatus);
			}
			else {
				$output->writeln( $job->id ); // Ne pas supprimer car nécessaire pour afficher les log d'un job manuel
			}
		}
		catch(\Exception $e) {
			$job->message .= $e->getMessage();
		}
		
		// Close job if it has been created
		if($job->createdJob == true) {
			$job->closeJob();
		}
		
		// Display message on the console
		if (!empty($job->message)) {
			$output->writeln('<error>'.$job->message.'</error>');
			$logger->error($job->message);
		} 	
	}
}