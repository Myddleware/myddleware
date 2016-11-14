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
            ->addArgument('action', InputArgument::REQUIRED, "Action") // id séparés par des ";"
            ->addArgument('idsDoc', InputArgument::REQUIRED, "Ids de document") // id séparés par des ";"
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$step = 1;
		try {		
			$logger = $this->getContainer()->get('logger');
			
			// Récupération des paramètres
			$action = $input->getArgument('action');
			$idsDoc = $input->getArgument('idsDoc');
			// Récupération du Job
			$job = $this->getContainer()->get('myddleware_job.job');
			
			if ($job->initJob('Mass '.$action)) {
				$output->writeln( $job->id );  // Ne pas supprimer car nécessaire pour afficher les log d'un job manuel
				
				// Annulation en masse
				$job->massAction($action,$idsDoc);
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