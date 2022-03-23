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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateTemplateCommand.
 */
class GenerateTemplateCommand extends Command
{
    /**
     * @var JobManager
     */
    private $jobManager;

    public function __construct(JobManager $jobManager, string $name = null)
    {
        parent::__construct($name);
        $this->jobManager = $jobManager;
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:generateTemplate')
            ->setDescription('Génération de template')
            ->addArgument('nomTemplate', InputArgument::REQUIRED, 'Nom')
            ->addArgument('descriptionTemplate', InputArgument::REQUIRED, 'Description')
            ->addArgument('rulesId', InputArgument::REQUIRED, 'Rules IDs') // Ids separated by ,
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $nomTemplate = $input->getArgument('nomTemplate');
        $rulesIds = $input->getArgument('rulesId');
        $descriptionTemplate = $input->getArgument('descriptionTemplate');

        $rulesIds = explode('/', $rulesIds);

        // Récupération du Job
        $response = $this->jobManager->generateTemplate($nomTemplate, $descriptionTemplate, $rulesIds);
        if (true === $response['success']) {
            $output->writeln('Template '.$nomTemplate.' generated.');
        } else {
            $output->writeln('<error>Failed to generate template '.$nomTemplate.' : '.$response['message'].'</error>');
        }
    }
}
