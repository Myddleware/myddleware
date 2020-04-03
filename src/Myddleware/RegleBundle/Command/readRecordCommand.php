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
use Symfony\Component\Console\Output\OutputInterface;

class readRecordCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('myddleware:readrecord')
            ->setDescription('Read specific records for a rule')
            ->addArgument('ruleId', InputArgument::REQUIRED, "Rule used to read the records")
            ->addArgument('filterQuery', InputArgument::REQUIRED, "Filter used to read data in the source application, eg : id")
            ->addArgument('filterValues', InputArgument::REQUIRED, "Values corresponding to the fileter separated by comma, eg : 1256,4587")
			->addArgument('api', InputArgument::OPTIONAL, "Call from API")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {		
		try {		
			$logger = $this->getContainer()->get('logger');
			$ruleId = $input->getArgument('ruleId');
			$filterQuery = $input->getArgument('filterQuery');
			$filterValues = $input->getArgument('filterValues');
			$api = $input->getArgument('api');

			// Get the Job container
			$job = $this->getContainer()->get('myddleware_job.job');
			$job->setApi($api);		
			
			if ($job->initJob('read records wilth filter '.$filterQuery.' IN ('.$filterValues.')')) {
				$output->writeln( $job->id );  // This is requiered to display the log (link creation with job id) when the job is run manually
				$job->readRecord($ruleId, $filterQuery, $filterValues);	
			}
		}
		catch(\Exception $e) {
			$job->message .= $e->getMessage();
		}
		
		// Close job if it has been created
		if($job->createdJob === true) {
			$job->closeJob();
		}
		
		// Display message on the console
		if (!empty($job->message)) {
			$output->writeln('<error>'.$job->message.'</error>');
			$logger->error($job->message);
		} 	
	}
}