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

class generateTemplateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('myddleware:generateTemplate')
            ->setDescription('Génération de template')
            ->addArgument('nomTemplate', InputArgument::REQUIRED, "nom")
            ->addArgument('descriptionTemplate', InputArgument::REQUIRED, "nom")
            ->addArgument('rulesId', InputArgument::REQUIRED, "nom") // Ids separated by ,
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		
		$nomTemplate = $input->getArgument('nomTemplate');
		$rulesId = $input->getArgument('rulesId');
		$descriptionTemplate = $input->getArgument('descriptionTemplate');
		
		$rulesId = explode("/", $rulesId);
		
		// Récupération du Job
		$job = $this->getContainer()->get('myddleware_job.job');
		$result = $job->generateTemplate($nomTemplate,$descriptionTemplate,$rulesId);
		if ($result) {
			$output->writeln( 'Template '.$nomTemplate.' generated.' );
		}
		else {
			$output->writeln( '<error>Failed to generate template '.$nomTemplate.' : '.$job->message.'</error>' );
		}
	}
}