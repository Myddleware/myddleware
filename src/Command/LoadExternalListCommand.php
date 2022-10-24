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

use App\Manager\LoadExternalListManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LoadExternalListCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private SymfonyStyle $io;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'myddleware:loadexternallist';
    protected static string $defaultDescription = 'transfers all lines of csv into InternalListValue';

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
            ->setName('myddleware:loadexternallist')
            ->addArgument('file', InputArgument::REQUIRED, 'selected file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $output->writeln([
            '',
            '##############',
            'INTERNALLIST LOADER',
            '##############',
            '',
        ]);
        try {
            $file = $input->getArgument('file');
            $manager = new LoadExternalListManager($this->entityManager);
            $manager->loadExternalList($file, $input, $output);
            $io->success([
                '',
                '##############',
                'THE LIST WAS LOADED !',
                '##############',
                '',
            ]);

            return 0;
        } catch (\Exception $e) {
            $output->writeln([
                '',
                '##############',
                'THE LIST COULD NOT BE LOADED !',
                '##############',
                '',
            ]);
            $io->getErrorStyle()->warning('Debugging information or errors: '.$e);
            $io->error('The user command did not work');

            return 1;
        }
    }
}
