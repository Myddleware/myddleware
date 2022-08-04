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

use Psr\Log\LoggerInterface;
use App\Manager\ExtractCsvManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ExtractCsvCommand extends Command
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;




    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SymfonyStyle
     */
    private $io;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'myddleware:extractcsv';
    protected static $defaultDescription = 'transfers all lines of csv into InternalListValue';


    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:extractcsv')
            ->addArgument('file', InputArgument::REQUIRED, 'selected file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $output->writeln([
            '',
            '============',
            'CSV EXTRACTOR',
            '============',
            '',
        ]);
        try {
            // Source -------------------------------------------------
            // alias de la règle en params
            $file = $input->getArgument('file');

            //################################################################


            /* //progress bar

            $progressBar = new ProgressBar($output, 10);
            // starts and displays the progress bar
            $progressBar->start();
            $i = 0;
            while ($i++ < 10) {
                // ... do some work
                echo "toto";
                // advances the progress bar 1 unit
                $progressBar->advance();

                // you can also advance the progress bar by more than 1 unit
                // $progressBar->advance(3);
            }

            // ensures that the progress bar is at 100%
            $progressBar->finish(); */


            //################################################################
            $manager = new ExtractCsvManager($this->entityManager);
            $manager->extractcsv($file);
            $output->writeln([
                '',
                '============',
                'THE CSV WAS EXTRACTED !',
                '============',
                '',
            ]);
            return 0;
        } catch (\Exception $e) {
            $output->writeln([
                '',
                '##############',
                'THE CSV COULD NOT BE EXTRACTED !',
                '##############',
                '',
            ]);
            $io->error(sprintf('The user command could not be ran.', $e));
            return 1;
        }
        // Retour en console --------------------------------------
    }
}
