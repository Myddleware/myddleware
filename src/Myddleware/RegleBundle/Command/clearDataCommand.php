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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class clearDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('myddleware:cleardata')
            ->setDescription('SUPPRESSION DES DONNEES DU CLIENT')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$logger = $this->getContainer()->get('logger');
		
		$job = $this->getContainer()->get('myddleware_job.job');
		// Clear message in case this task is run by jobscheduler. In this case message has to be refreshed.
		$job->message = '';	
		
		if ($job->initJob('cleardata')) {
			$job->clearData();
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