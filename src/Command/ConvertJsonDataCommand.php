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

use App\Manager\UpgradeManager;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertJsonDataCommand extends Command
{
    private LoggerInterface $logger;
    private UpgradeManager $upgradeManager;

    public function __construct(
        LoggerInterface $logger,
        UpgradeManager $upgradeManager,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->logger = $logger;
        $this->upgradeManager = $upgradeManager;
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:convert-json-data')
            ->setDescription('Convert the serialized data into JSON before running doctrine:schema:update')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln('<info>'.$this->upgradeManager->convertDataToJson().'</info>');
        } catch (Exception $e) {
            $message = $e->getMessage();
            $output->writeln('<error>'.$message.'</error>');
            $this->logger->error($message);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
