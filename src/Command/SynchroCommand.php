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

use App\Manager\DocumentManager;
use App\Manager\JobManager;
use App\Manager\RuleManager;
use App\Manager\ToolsManager;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Persistence\ManagerRegistry;

class SynchroCommand extends Command
{
    private LoggerInterface $logger;
    private JobManager $jobManager;
    private EntityManagerInterface $entityManager;
    private DocumentManager $documentManager;
    private RuleManager $ruleManager;
    private DocumentRepository $documentRepository;
	private ToolsManager $toolsManager;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'myddleware:synchro';
    protected array $statusLevel = [
        'Create_KO' 		=> 0,
        'New' 				=> 10,
        'Filter_KO' 		=> 20,
        'Filter' 			=> 30,
        'Filter_OK' 		=> 40,
        'Predecessor_KO' 	=> 50,
        'Predecessor_OK' 	=> 60,
        'Relate_KO' 		=> 70,
        'Relate_OK' 		=> 80,
        'Error_transformed' => 90,
        'Transformed' 		=> 100,
        'Not_found' 		=> 110,
        'Found'				=> 120,
        'Error_checking' 	=> 130,
        'Ready_to_send' 	=> 140,
        'No_send' 			=> 150,
        'Error_sending' 	=> 160,
        'Send' 				=> 170,
        'Cancel' 			=> 180,
        'Error_expected' 	=> 190,
    ];

    public function __construct(
        LoggerInterface $logger,
        JobManager $jobManager,
        DocumentManager $documentManager,
        RuleManager $ruleManager,
        EntityManagerInterface $entityManager,
        DocumentRepository $documentRepository,
		ManagerRegistry $registry,
		ToolsManager $toolsManager,
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->jobManager = $jobManager;
        $this->ruleManager = $ruleManager;
        $this->documentManager = $documentManager;
        $this->documentRepository = $documentRepository;
		$this->registry = $registry;
		$this->toolsManager = $toolsManager;
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:synchro')
            ->setDescription('Execute all active Myddleware transfer rules')
            ->addArgument('rule', InputArgument::REQUIRED, 'Rule id, you can put several rule id separated by coma')
			->addArgument('fromStatus', InputArgument::OPTIONAL, 'Set the status from where you want the rule to start.')
            ->addArgument('force', InputArgument::OPTIONAL, 'Force run even if the rule is inactive.')
            ->addArgument('api', InputArgument::OPTIONAL, 'Call from API')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $step = 1;
		$fromStatusLevel = 0;
        try {
            // Source -------------------------------------------------
            // alias de la règle en params
            $rule = $input->getArgument('rule');
            $api = $input->getArgument('api');
            $force = $input->getArgument('force');
            if (empty($force)) {
                $force = false;
            }
			$fromStatus = $input->getArgument('fromStatus');
            if (!empty($fromStatus)) {
				if (!empty($this->statusLevel[$fromStatus])) {
					$fromStatusLevel = $this->statusLevel[$fromStatus];
				} else {
					$output->writeln('1;<error>'.$fromStatus.' is not a valid status.</error>');
					$this->logger->error($fromStatus.' is not a valid status.');
					return 1;
				}
            }
            // Récupération du Job
            // $job = $this->jobManager;
            // Clear message in case this task is run by jobscheduler. In this case message has to be refreshed.
            $this->jobManager->message = '';
            $this->jobManager->setApi($api);
            $data = $this->jobManager->initJob("synchro $rule $force $api");
            if (true === $data['success']) {
                $output->writeln('1;'.$this->jobManager->getId());  // Not removed, user for manual job and webservices

                if (!empty($rule)) {
                    if ('ERROR' == $rule) {
                        // Premier paramètre : limite d'enregistrement traités
                        // Deuxième paramètre, limite d'erreur : si un flux a plus de tentative que le paramètre il n'est pas relancé
                        $this->jobManager->runError(50, 100);
                    } else {
                        // Envoi du job sur toutes les règles demandées. Si ALL est sélectionné alors on récupère toutes les règle dans leur ordre de lancement sinon on lance seulement la règle demandée.
                        if ('ALL' == $rule) {
                            $rules = $this->jobManager->getRules($force);
                        } else {
							//Check if the parameter is a rule group 
							if ($this->toolsManager->isPremium()) {
								// Get the rules from the group
								$rulesGroup = $this->toolsManager->getRulesFromGroup($rule, $force);
								if (!empty($rulesGroup)) {
									foreach($rulesGroup as $ruleGroup) {
										$rules[] = $ruleGroup['name_slug'];
									}
								}
							}
							// If the parameter isn't a group
							if (empty($rules)) {
								$rules = explode(',',$rule);
							}
                        }
                        if (!empty($rules)) {
                            foreach ($rules as $key => $value) {
                                // Don't display rule id if the command is called from the api
                                if (empty($api)) {
                                    echo $value.chr(10);
                                }
                                $output->writeln('Read data for rule : <question>'.$value.'</question>');
                                // Chargement des données de la règle
                                if ($this->jobManager->setRule($value)) {
									try {
										// Sauvegarde des données sources dans les tables de myddleware
										if ($fromStatusLevel <= 0) {
											$output->writeln($value.' : Create documents.');
											$nb = $this->jobManager->createDocuments();
											$output->writeln($value.' : Number of documents created : '.$nb);
										}
										
										// Permet de filtrer les documents
										if ($fromStatusLevel <= 20) {
											$this->jobManager->filterDocuments();
										}
										
										// Permet de valider qu'aucun document précédent pour la même règle et le même id n'est pas bloqué
										if ($fromStatusLevel <= 50) {
											$this->jobManager->checkPredecessorDocuments();
										}
										
										// Permet de valider qu'au moins un document parent(relation père) est existant
										if ($fromStatusLevel <= 70) {
											$this->jobManager->checkParentDocuments();
										}
										
										// Permet de transformer les docuement avant d'être envoyés à la cible
										if ($fromStatusLevel <= 90) {
											$this->jobManager->transformDocuments();
										}
										
										// Historisation des données avant modification dans la cible
										if ($fromStatusLevel <= 130) {
											$this->jobManager->getTargetDataDocuments();
										}
										
										// Envoi des documents à la cible
										$this->jobManager->sendDocuments();
									} catch (\Exception $e) {
										$this->jobManager->message .= 'Error rule '.$value.' '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
										// Reset entity manager in case it has been closed by the exception
										if (!$this->entityManager->isOpen()) {
											$this->entityManager = $this->registry->resetManager();
										}
										// Unset all the read and send locks of the rule in case of fatal error (if the losk correspond to the current job)
										if (!$this->jobManager->unsetRuleLock()) {
											$this->jobManager->message .= 'Failed to unset the lock for the rule '.$value.'. ';
										}
									}
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->jobManager->message .= $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }

        // Close job if it has been created
        if (true === $this->jobManager->createdJob) {
            $this->jobManager->closeJob();
        }
        // Retour en console --------------------------------------
        if (!empty($this->jobManager->message)) {
            $output->writeln('1;<error>'.$this->jobManager->message.'</error>');
            $this->logger->error($this->jobManager->message);

            return 1;
        }

        return 0;
    }
}
