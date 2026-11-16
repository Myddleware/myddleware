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

use App\Controller\WebhookController;
use App\Entity\Connector;
use App\Manager\SolutionManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WebhookUrlCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private SolutionManager $solutionManager;
    private ParameterBagInterface $parameterBag;

    public function __construct(
        EntityManagerInterface $entityManager,
        SolutionManager $solutionManager,
        ParameterBagInterface $parameterBag,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->entityManager = $entityManager;
        $this->solutionManager = $solutionManager;
        $this->parameterBag = $parameterBag;
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:webhook-url')
            ->setDescription('Display the webhook url of the connectors able to receive webhooks')
            ->addArgument('baseUrl', InputArgument::REQUIRED, 'Public url of Myddleware, e.g. https://myddleware.example.com/public')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $baseUrl = rtrim($input->getArgument('baseUrl'), '/');
        $found = false;
        foreach ($this->entityManager->getRepository(Connector::class)->findBy(['deleted' => false]) as $connector) {
            if (!$this->solutionManager->get($connector->getSolution()->getName())->isWebhookAvailable()) {
                continue;
            }
            $found = true;
            $output->writeln($connector->getSolution()->getName().' - '.$connector->getName().' (id '.$connector->getId().') : '
                .$baseUrl.'/webhook/'.$connector->getId().'/'.WebhookController::getWebhookToken($connector->getId(), $this->parameterBag->get('secret')));
        }
        if (!$found) {
            $output->writeln('<comment>No connector able to receive webhooks. Create a connector (e.g. Zoom) first.</comment>');
        }

        return Command::SUCCESS;
    }
}
