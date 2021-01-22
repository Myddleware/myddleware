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

namespace App\Manager;

use App\Entity\DocumentData;
use App\Entity\Job;
use App\Entity\Rule;
use App\Repository\DocumentDataRepository;
use App\Repository\DocumentRepository;
use App\Repository\JobRepository;
use App\Repository\LogRepository;
use App\Repository\RuleRepository;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

$file = __DIR__.'/../Custom/Manager/JobManager.php';
if (file_exists($file)) {
    require_once $file;
} else {
    class JobManager
    {
        public $id;
        public $message = '';

        protected $connection;
        protected $logger;
        protected $tools;

        protected $rule;
        protected $ruleId;
        protected $start;
        protected $manual;
        protected $api = false;    // Specify if the class is called by the API
        protected $env;
        protected $nbDayClearJob = 7;
        /**
         * @var ParameterBagInterface
         */
        private $params;
        /**
         * @var string
         */
        private $cacheDir;
        /**
         * @var RouterInterface
         */
        private $router;
        /**
         * @var TemplateManager
         */
        private $template;
        /**
         * @var TranslatorInterface
         */
        private $translator;
        /**
         * @var string
         */
        private $projectDir;
        /**
         * @var UpgradeManager
         */
        private $upgrade;
        /**
         * @var EntityManagerInterface
         */
        private $entityManager;
        /**
         * @var JobRepository
         */
        private $jobRepository;
        /**
         * @var DocumentRepository
         */
        private $documentRepository;
        /**
         * @var RuleRepository
         */
        private $ruleRepository;
        /**
         * @var LogRepository
         */
        private $logRepository;
        /**
         * @var SessionInterface
         */
        private $session;

        public function __construct(
            LoggerInterface $logger,
            DriverConnection $dbalConnection,
            KernelInterface $kernel,
            ParameterBagInterface $params,
            TranslatorInterface $translator,
            EntityManagerInterface $entityManager,
            JobRepository $jobRepository,
            DocumentRepository $documentRepository,
            RuleRepository $ruleRepository,
            LogRepository $logRepository,
            RouterInterface $router,
            SessionInterface $session,
            ToolsManager $tools,
            RuleManager $rule,
            TemplateManager $template,
            UpgradeManager $upgrade
        ) {
            $this->logger = $logger; // gestion des logs symfony monolog
            $this->connection = $dbalConnection;
            $this->params = $params;
            $this->translator = $translator;
            $this->entityManager = $entityManager;
            $this->ruleRepository = $ruleRepository;
            $this->logRepository = $logRepository;
            $this->router = $router;
            $this->session = $session;
            $this->tools = $tools;
            $this->rule = $rule;
            $this->upgrade = $upgrade;
            $this->template = $template;
            $this->cacheDir = $kernel->getCacheDir();
            $this->projectDir = $kernel->getProjectDir();
            $this->jobRepository = $jobRepository;
            $this->documentRepository = $documentRepository;

            $this->env = $params->get('kernel.environment');
            $this->manual = 'background' != $this->env;
        }

        public function setApi($api)
        {
            $this->api = $api;
        }

        public function setManual($manual)
        {
            $this->manual = $manual;
        }

        // Permet de contrôler si un docuement de la même règle pour le même enregistrement n'est pas close
        public function createDocuments(Rule $rule, Job $job)
        {
           
            $createDocuments = $this->rule->createDocuments($rule, $job);
            if (!empty($createDocuments['error'])) {
                $this->message .= print_r($createDocuments['error'], true);
            }
            if (!empty($createDocuments['count'])) {
                return $createDocuments['count'];
            }

            return 0;
        }

        // Ecriture dans le système source et mise à jour de la table document
        public function sendDocuments()
        {
            $sendDocuments = $this->rule->sendDocuments();
            if (!empty($sendDocuments['error'])) {
                $this->message .= $sendDocuments['error'];
            }
        }

        // Fonction permettant d'initialiser le job
        public function initJob($rule, $api = false): array
        {
            $this->id = uniqid('', true);
            $this->start = microtime(true);
            $job = $this->jobRepository->findOneBy(['status' => 'Start']);
        
            if ($job) {
                return ['success' => false, 'message' => $this->translator->trans('messages.rule.another_task_running').';'.$job['id']];
            }
            // Create Job
            $job = new Job();
            $job->setStatus('Start')
                ->setParam('Synchro : '.$rule)
                ->setManual($this->manual)
                ->setApi(true === $api)
                ->setId($this->id);

            try {
                $this->entityManager->persist($job);
                $this->entityManager->flush();

                return ['success' => true, 'job' => $job];
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Failed to create the Job in the database'];
            }
        }

        // Ecriture dans le système source et mise à jour de la table document
        public function runError($limit, $attempt)
        {
            $documentsError = $this->documentRepository->findDocumentsError($limit, $attempt);
            if (!empty($documentsError)) {
                foreach ($documentsError as $documentError) {
                    $errorActionDocument = $this->rule->actionDocument($documentError, 'rerun');
                    if (!empty($errorActionDocument)) {
                        $this->message .= print_r($errorActionDocument, true);
                    }
                }
            }
        }

        // Permet de clôturer un job
        public function closeJob(Job $job)
        {
            // Get job data
            $logData = $this->getLogData($job);

            // Update table job
            return $this->updateJob($job, $logData);
        }

        // Permet d'exécuter des jobs manuellement depuis Myddleware
        public function actionMassTransfer($event, $datatype, $param)
        {
            if (in_array($event, ['rerun', 'cancel'])) {
                // Pour ces 2 actions, l'event est le premier paramètre, le type de donnée est le deuxième
                // et ce sont les ids des documents ou règles qui sont envoyés dans le $param
                $paramJob[] = $event;
                $paramJob[] = $datatype;
                $paramJob[] = implode(',', $param);

                return $this->runBackgroundJob('massaction', $paramJob);
            }

            return 'Action '.$event.' unknown. Failed to run this action. ';
        }

        // Lancement d'un job manuellement en arrière plan
        public function runBackgroundJob($job, $param)
        {
            try {
                // Création d'un fichier temporaire
                $guid = uniqid();

                // Formatage des paramètres
                $params = implode(' ', $param);

                // récupération de l'exécutable PHP, par défaut c'est php
                $php = $this->params->get('php');
                if (empty($php['executable'])) {
                    $php['executable'] = 'php';
                }

                //Create your own folder in the cache directory
                $fileTmp = $this->cacheDir.'/myddleware/job/'.$guid.'.txt';
                $fs = new Filesystem();
                try {
                    $fs->mkdir(dirname($fileTmp));
                } catch (IOException $e) {
                    throw new Exception('An error occured while creating your directory');
                }
                exec($php['executable'].' '.__DIR__.'/../../../../bin/console myddleware:'.$job.' '.$params.' --env='.$this->env.'  > '.$fileTmp.' &', $output);
                $cpt = 0;
                // Boucle tant que le fichier n'existe pas
                while (!file_exists($fileTmp)) {
                    if ($cpt >= 29) {
                        throw new Exception('Failed to run the job.');
                    }
                    sleep(1);
                    ++$cpt;
                }

                // Boucle tant que l id du job n'est pas dans le fichier (écris en premier)
                $file = fopen($fileTmp, 'r');
                $idJob = fread($file, 23);
                fclose($file);
                while (empty($idJob)) {
                    if ($cpt >= 29) {
                        throw new Exception('No task id given.');
                    }
                    sleep(1);
                    $file = fopen($fileTmp, 'r');
                    $idJob = fread($file, 23);
                    fclose($file);
                    ++$cpt;
                }
                // Renvoie du message en session
                $this->session->set('info', ['<a href="'.$this->router->generate('task_view', ['id' => $idJob]).'" target="_blank">'.$this->translator->trans('session.task.msglink').'</a>. '.$this->translator->trans('session.task.msginfo')]);

                return $idJob;
            } catch (Exception $e) {
                $this->session->set('info', [$e->getMessage()]); // Vous venez de lancer une nouvelle longue tâche. Elle est en cours de traitement.

                return false;
            }
        }

        // Function to modify a group of documents
        public function massAction($action, $dataType, $ids, $forceAll, $fromStatus, $toStatus)
        {
            try {
                // No filter on status if the action is restore/changeStatus or if forceAll = 'Y'
                // Build the query
                $documents = $this->documentRepository->getDocumentsForMassAction($action, $dataType, $ids, $forceAll, $fromStatus);

                if (!empty($documents)) {
                    $message = '';
                    foreach ($documents as $document) {
                        $errorActionDocument = $this->rule->actionDocument($document, $action, $toStatus);
                        if (!empty($errorActionDocument)) {
                            $message .= print_r($errorActionDocument, true);
                        }
                    }

                    return ['success' => true, 'message' => $message];
                }
                $message = 'No document found corresponding to the input parameters. No action done in the job massAction. ';

                return ['success' => false, 'message' => $message];
            } catch (Exception $e) {
                $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');

                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        // Fonction permettant d'annuler massivement des documents
        public function readRecord(Rule $rule, $filterQuery, $filterValuesArray)
        {
            try {
                $message = '';
                // Try to read data for each values
                foreach ($filterValuesArray as $type) {
                    // Generate documents
                    try {
                        $documents = $this->rule->generateDocuments($rule, $type, true, '', $filterQuery);
                        // Run documents
                        foreach ($documents as $document) {
                            $errors = $this->rule->actionDocument($document, 'rerun');
                            // Check errors
                            if (!empty($errors)) {
                                $message .= 'Document '.$document->getId().' in error (rule '.$rule->getId().')  : '.$errors[0].'. ';
                            }
                        }
                    } catch (Exception $e) {
                        throw new Exception($e->getMessage());
                    }
                }
            } catch (Exception $e) {
                $message = 'Error : '.$e->getMessage();
                $this->logger->error($message.' '.$e->getFile().' Line : ( '.$e->getLine().' )');

                return ['success' => false, 'message' => $message];
            }

            return ['success' => true, 'message' => $message];
        }

        // Remove all data flagged deleted in the database
        public function pruneDatabase()
        {
            // Documents

            // Rules

            // Connectors
        }

        /**
         * @param $nomTemplate
         * @param $descriptionTemplate
         * @param $rulesIds
         */
        public function generateTemplate(string $nomTemplate, string $descriptionTemplate, array $rulesIds)
        {
            try {
                // Init array
                $templateArray = [
                    'name' => $nomTemplate,
                    'description' => $descriptionTemplate,
                ];
                if (!empty($rulesIds)) {
                    $rules = $this->ruleRepository->findRulesByIds($rulesIds);
                    foreach ($rules as $rule) {
                        $templateArray['rules'][] = $this->template->extractRule($rule);
                    }
                    // Ecriture du fichier
                    $yaml = Yaml::dump($templateArray, 4);
                    file_put_contents($this->projectDir.'/../src/Templates/'.$nomTemplate.'.yml', $yaml);
                }
            } catch (Exception $e) {
                $this->logger->error($this->message);

                return ['success' => false, 'message' => 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )'];
            }

            return ['success' => true];
        }

        // Myddleware upgrade
        public function upgrade($output)
        {
            $this->message = $this->upgrade->processUpgrade($output);
        }

        // Permet de supprimer toutes les données des tabe source, target et history en fonction des paramètre de chaque règle
        public function clearData()
        {
            $message = '';
            // Récupération de chaque règle et du paramètre de temps de suppression
            $rules = $this->ruleRepository->findRulesWithDeletedParams();
            if (count($rules)) {
                /** @var DocumentDataRepository $documentDataRepository */
                $documentDataRepository = $this->entityManager->getRepository(DocumentData::class);
                // Boucle sur toutes les règles
                foreach ($rules as $rule) {
                    $ruleParam = $rule->getParamByName('deleted');
                    // Calculate the date corresponding depending the rule parameters
                    $limitDate = new DateTime('now', new DateTimeZone('GMT'));
                    $limitDate->modify('-'.$ruleParam->getValue().' days');

                    $documentsDatas = $documentDataRepository->findDataToRemoveByRule($rule, $limitDate);
                    // Delete document data
                    $documentsDatasCount = count($documentsDatas);
                    if ($documentsDatasCount > 0) {
                        foreach ($documentsDatas as $documentData) {
                            $this->entityManager->remove($documentData);
                        }
                        $this->entityManager->flush();
                        $message .= $documentsDatasCount.' rows deleted in the table DocumentData for the rule '.$rule->getName().'. ';
                    }

                    // Delete log for these rule
                    $logs = $this->logRepository->findLogsToRemoveByRule($rule, $limitDate);
                    $logsCount = count($logs);
                    if ($logsCount > 0) {
                        $message .= $logsCount.' rows deleted in the table Log for the rule '.$rule->getName().'. ';
                        foreach ($logs as $log) {
                            $this->entityManager->remove($log);
                        }
                        $this->entityManager->flush();
                    }
                }
            }

            $limitDate = new DateTime('now', new DateTimeZone('GMT'));
            $limitDate->modify('-'.$this->nbDayClearJob.' days');
            $jobs = $this->jobRepository->findJobsToRemoveByLimitDate($limitDate);
            $jobsCount = count($jobs);
            if ($jobsCount > 0) {
                foreach ($jobs as $job) {
                    $this->entityManager->remove($job);
                }
                $this->entityManager->flush();
                $message .= $jobsCount.' rows deleted in the table Job.';
            }

            return ['success' => true, 'message' => $message];
        }

        // Récupération des données du job
        public function getLogData(Job $job, $documentDetail = false)
        {
            $logData = [
                'Close' => 0,
                'Cancel' => 0,
                'Open' => 0,
                'Error' => 0,
            ];

            // Récupération du nombre de document envoyé et en erreur pour ce job
            $data = $this->logRepository->getLogsReportForDocumentsSent($job);
            if (!empty($data)) {
                foreach ($data as $row) {
                    if ('Close' == $row['globalStatus']) {
                        $logData['Close'] = $row['nb'];
                    } elseif ('Error' == $row['globalStatus']) {
                        $logData['Error'] = $row['nb'];
                    } elseif ('Cancel' == $row['globalStatus']) {
                        $logData['Cancel'] = $row['nb'];
                    } elseif ('Open' == $row['globalStatus']) {
                        $logData['Open'] = $row['nb'];
                    }
                }
            }

            try {
                $solutions = $this->ruleRepository->getSolutionsByJob($job);
                $logData['solutions'] = '';
                if (!empty($solutions)) {
                    foreach ($solutions as $solution) {
                        $concatSolution[] = $solution['sol_id_target'];
                        $concatSolution[] = $solution['sol_id_source'];
                    }
                    $concatSolutions = array_unique($concatSolution);
                    // Mise au format pour la liste multi de Sugar
                    $concatSolutions = '^'.implode('^,^', $concatSolutions).'^';
                    $logData['solutions'] = $concatSolutions;
                }

                // Get the document detail if requested
                if (true == $documentDetail) {
                    $documents = $this->documentRepository->getSolutionsByJob($job);
                    $logData['documents'] = $documents;
                }

                // Récupération de la durée du job
                $time_end = microtime(true);
                $logData['duration'] = round($time_end - $this->start, 2);

                // récupération de l'id du job
                $logData['myddlewareId'] = $this->id;

                // Indique si le job est lancé manuellement ou non
                $logData['Manual'] = $this->manual;
                $logData['Api'] = $this->api;
            } catch (Exception $e) {
                $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
                $logData['jobError'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            }

            return $logData;
        }

        // Mise à jour de la table Job
        protected function updateJob(Job $job, array $logData)
        {
            try {
        
                $now = new DateTime('now', new DateTimeZone('GMT'));
                if(!empty($logData['jobError'])){
                    $message = $logData['jobError'];
                }else{
                    $message = '';
                }
            
                if (!empty($message)) {
                    $message = htmlspecialchars($message);
                }
                $job
                    ->setMessage($message)
                    ->setError($logData['Error'])
                    ->setOpen($logData['Open'])
                    ->setCancel($logData['Cancel'])
                    ->setClose($logData['Close'])
                    ->setStatus('End')
                    ->setEnd($now);
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->logger->error('Failed to update Job : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
                $message = 'Failed to update Job : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';

                return ['success' => false, 'message' => $message];
            }

            return ['success' => true, 'message' => $message];
        }
    }
}
