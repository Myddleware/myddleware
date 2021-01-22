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

use App\Entity\Job;
use App\Entity\Rule;
use App\Manager\DocumentManager;
use App\Manager\JobManager;
use App\Manager\RuleManager;
use App\Repository\DocumentRepository;
use App\Repository\RuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SynchroCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var JobManager
     */
    private $jobManager;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var DocumentManager
     */
    private $documentManager;
    /**
     * @var RuleManager
     */
    private $ruleManager;
    /**
     * @var DocumentRepository
     */
    private $documentRepository;

    public function __construct(
        LoggerInterface $logger,
        JobManager $jobManager,
        DocumentManager $documentManager,
        RuleManager $ruleManager,
        EntityManagerInterface $entityManager,
        DocumentRepository $documentRepository,
        $name = null
    ) {
        parent::__construct($name);
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->jobManager = $jobManager;
        $this->ruleManager = $ruleManager;
        $this->documentManager = $documentManager;
        $this->documentRepository = $documentRepository;
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:synchro')
            ->setDescription('Synchronisation des données')
            ->addArgument('rule', InputArgument::REQUIRED, 'Alias de la règle')
            ->addArgument('api', InputArgument::OPTIONAL, 'Call from API')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Source -------------------------------------------------
        // alias de la règle en params
        $rule = $input->getArgument('rule');
        $api = $input->getArgument('api');
        $api = 1 == $api;
        $data = $this->jobManager->initJob($rule, $api);

        if (false === $data['success']) {
            $output->writeln('0;<error>'.$data['message'].'</error>');
            $this->logger->error($data['message']);

            return 0;
        }

        /** @var Job $job */
        $job = $data['job'];
        $output->writeln('1;'.$job->getId());  // Not removed, user for manual job and webservices
        if (!empty($rule)) {
            if ('ERROR' == $rule) {
                // Premier paramètre : limite d'enregistrement traités
                // Deuxième paramètre, limite d'erreur : si un flux a plus de tentative que le paramètre il n'est pas relancé
                $this->jobManager->runError(50, 100);
            } else {
                /** @var RuleRepository $ruleRepository */
                $ruleRepository = $this->entityManager->getRepository(Rule::class);
                // Envoi du job sur toutes les règles demandées. Si ALL est sélectionné alors on récupère toutes les règle dans leur ordre de lancement sinon on lance seulement la règle demandée.
                if ('ALL' == $rule) {
                    $rules = $ruleRepository->findAllRulesByOrder();
                } else {
                    $rules = $ruleRepository->findBy(['id' => $rule]);
                }
// var_dump($rules);
// var_dump($rule);
                if (!empty($rules)) {
                    foreach ($rules as $key => $rule) {
                        // echo 'allo';
                        // var_dump($key);
                        // var_dump($rule->getNameSlug());
                        $value = $rule->getNameSlug();
                        // Don't display rule id if the command is called from the api
                        if (!$api) {
                            echo $value.chr(10);
                        }
                        $output->writeln('Read data for rule : <question>'.$value.'</question>');
                        // Sauvegarde des données sources dans les tables de myddleware
                        $output->writeln($value.' : Create documents.');
                        $nb = $this->jobManager->createDocuments($rule, $job);
                        $output->writeln($value.' : Number of documents created : '.$nb);

                        // Permet de filtrer les documents
                        $documents = $this->documentRepository->findby(['rule' => $rule, 'status' => 'NEW', 'deleted' => false]);
                        $this->documentManager->filterDocuments($documents, $job);

                        // Permet de valider qu'aucun document précédent pour la même règle et le même id n'est pas bloqué
                        $documents = $this->documentRepository->findby(['rule' => $rule, 'status' => 'Filter_OK', 'deleted' => false]);
                        $this->documentManager->checkPredecessorDocuments($documents, $job);

                        // Permet de valider qu'au moins un document parent(relation père) est existant
                        $documents = $this->documentRepository->findby(['rule' => $rule, 'status' => 'Predecessor_OK', 'deleted' => false]);
                        $this->documentManager->checkParentDocuments($documents, $job);

                        // Permet de transformer les docuement avant d'être envoyés à la cible
                        $documents = $this->documentRepository->findby(['rule' => $rule, 'status' => 'Relate_OK', 'deleted' => false]);
                        $this->documentManager->transformDocuments($documents, $job);

                        // Historisation des données avant modification dans la cible
                        $documents = $this->documentRepository->findby(['rule' => $rule, 'status' => 'Transformed', 'deleted' => false]);
                        $this->documentManager->getTargetDataDocuments($documents, $job);

                        // Envoi des documents à la cible
                        $this->ruleManager->sendDocuments($documents, $job);
                    }
                }
            }
        }

        // Close job if it has been created
        $responseCloseJob = $this->jobManager->closeJob($job);

        if (!empty($responseCloseJob['message'])) {
            if ($responseCloseJob['success']) {
                $output->writeln('<info>'.$responseCloseJob['message'].'</info>');
                $this->logger->info($responseCloseJob['message']);
            } else {
                $output->writeln('<error>'.$responseCloseJob['message'].'</error>');
                $this->logger->error($responseCloseJob['message']);
            }
        }

        return 1;
    }
}
