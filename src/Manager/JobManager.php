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

use App\Repository\DocumentRepository;
use App\Repository\JobRepository;
use App\Repository\LogRepository;
use App\Repository\RuleRepository;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Connection as DriverConnection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;
use PDO;
use App\Entity\Job;

class JobManager
{
    protected $id;
    public string $message = '';
    public bool $createdJob = false;

    protected $container;
    protected DriverConnection $connection;
    protected LoggerInterface $logger;
    protected ToolsManager $toolsManager;

    protected RuleManager $ruleManager;
    protected $ruleId;
    protected $logData;
    protected $start;
    protected $paramJob;
    protected $manual;
    protected int $api = 0; 	// Specify if the class is called by the API
    protected $env;
    protected int $nbDayClearJob = 7;
	protected int $limitOfDeletePerRequest = 3;
	protected int $limitOfRequestExecution = 10;
	protected int $noDocumentsTablesToEmptyCounter;
	protected int $noRulesTablesToEmptyCounter;

    protected int $limitDelete = 1000;
    protected int $nbCallMaxDelete = 50;
    protected int $checkJobPeriod = 900;

    private ParameterBagInterface $parameterBagInterface;

    private RouterInterface $router;

    private TemplateManager $templateManager;

    private TranslatorInterface $translator;

    private string $projectDir;

    private UpgradeManager $upgrade;

    private EntityManagerInterface $entityManager;

    private JobRepository $jobRepository;

    private DocumentRepository $documentRepository;

    private RuleRepository $ruleRepository;

    private LogRepository $logRepository;

    private SessionInterface $session;

    private int $nbDays;
    private string $pruneDatabaseMaxDate;


    public function __construct(
        LoggerInterface $logger,
        DriverConnection $dbalConnection,
        KernelInterface $kernel,
        ParameterBagInterface $parameterBagInterface,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        JobRepository $jobRepository,
        DocumentRepository $documentRepository,
        RuleRepository $ruleRepository,
        LogRepository $logRepository,
        RouterInterface $router,
        SessionInterface $session,
        ToolsManager $toolsManager,
        RuleManager $ruleManager,
        TemplateManager $templateManager,
        UpgradeManager $upgrade
    ) {
        $this->logger = $logger; // gestion des logs symfony monolog
        $this->connection = $dbalConnection;
        $this->parameterBagInterface = $parameterBagInterface;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->ruleRepository = $ruleRepository;
        $this->logRepository = $logRepository;
        $this->router = $router;
        $this->session = $session;
        $this->toolsManager = $toolsManager;
        $this->ruleManager = $ruleManager;
        $this->upgrade = $upgrade;
        $this->templateManager = $templateManager;
        $this->projectDir = $kernel->getProjectDir();
        $this->jobRepository = $jobRepository;
        $this->documentRepository = $documentRepository;

        $this->env = $parameterBagInterface->get('kernel.environment');
        $this->setManual();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    // Set the rule data in the current inctance of the rule
    public function setRule($filter): bool
    {
        try {
            // Get the connector ID
            $sqlRule = 'SELECT * 
		    		FROM rule 
		    		WHERE 
							(
								name_slug = :filter
							 OR id = :filter	
							)
						AND deleted = 0
					';
            $stmt = $this->connection->prepare($sqlRule);
            $stmt->bindValue('filter', $filter);
            $result = $stmt->executeQuery();
            $rule = $result->fetchAssociative(); // 1 row
            if (empty($rule['id'])) {
                throw new Exception('Rule '.$filter.' doesn\'t exist or is deleted.');
            }
            // Error if the rule is inactive and if we try to run it from a job (not manually)
            elseif (
                    empty($rule['active'])
                && 0 == $this->manual
            ) {
                throw new Exception('Rule '.$filter.' is inactive.');
            }

            $this->ruleId = $rule['id'];
            // We instance the rule
            $this->ruleManager->setRule($this->ruleId);
            $this->ruleManager->setJobId($this->id);
            $this->ruleManager->setManual($this->manual);
            $this->ruleManager->setApi($this->api);

            if ($this->ruleManager->isChild()) {
                throw new Exception('Rule '.$filter.' is a child rule. Child rules can only be run by the parent rule.');
            }

            return true;
        } catch (Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            $this->message .= $e->getMessage();

            return false;
        }
    }
	
	// Unset the lock on the rule 
	public function unsetRuleLock() {
		return $this->ruleManager->unsetRuleLock();
	}

    // Permet de contrôler si un docuement de la même règle pour le même enregistrement n'est pas close
    public function createDocuments()
    {
        $createDocuments = $this->ruleManager->createDocuments();
        if (!empty($createDocuments['error'])) {
            $this->message .= print_r($createDocuments['error'], true);
        }
        if (!empty($createDocuments['count'])) {
            return $createDocuments['count'];
        } else {
            return 0;
        }
    }

    // Permet de contrôler si un docuement de la même règle pour le même enregistrement n'est pas close
    public function checkPredecessorDocuments()
    {
        $this->ruleManager->checkPredecessorDocuments();
    }

    // Permet de filtrer les documents en fonction des filtres de la règle
    public function filterDocuments()
    {
        $this->ruleManager->filterDocuments();
    }

    // Permet de contrôler si un docuement a une relation mais n'a pas de correspondance d'ID pour cette relation dans Myddleware
    public function checkParentDocuments()
    {
        $this->ruleManager->checkParentDocuments();
    }

    // Permet de trasformer les documents
    public function transformDocuments()
    {
        $this->ruleManager->transformDocuments();
    }

    // Permet de récupérer les données de la cible avant modification des données
    // 2 cas de figure :
    //     - Le document est un document de modification
    //     - Le document est un document de création mais la règle a un paramètre de vérification des données pour ne pas créer de doublon
    public function getTargetDataDocuments()
    {
        $this->ruleManager->getTargetDataDocuments();
    }

    // Ecriture dans le système source et mise à jour de la table document
    public function sendDocuments()
    {
        $sendDocuments = $this->ruleManager->sendDocuments();
        if (!empty($sendDocuments['error'])) {
            $this->message .= $sendDocuments['error'];
        }
    }

    // Ecriture dans le système source et mise à jour de la table document
	public function runError($limit, $attempt)
    {
        try {
			$ruleId = '';
            // Récupération de tous les flux en erreur ou des flux en attente (new) qui ne sont pas sur règles actives (règle child pour des règles groupées)
            $sqlParams = "	SELECT * 
							FROM document
								INNER JOIN ruleorder
									ON document.rule_id = ruleorder.rule_id
							WHERE 
									global_status = 'Error'
								AND deleted = 0 
								AND attempt <= :attempt 
							ORDER BY ruleorder.order ASC, document.rule_id, source_date_modified ASC	
							LIMIT $limit";
            $stmt = $this->connection->prepare($sqlParams);
            $stmt->bindValue('attempt', $attempt);
            $result = $stmt->executeQuery();
            $documentsError = $result->fetchAllAssociative();
            if (!empty($documentsError)) {
                $ruleId = null;
				$this->ruleManager->setJobId($this->id);
				$this->ruleManager->setManual($this->manual);
				$this->ruleManager->setApi($this->api);
                foreach ($documentsError as $documentError) {
					// Load the rule only if it has changed
					if ($ruleId != $documentError['rule_id']) {
						$this->ruleManager->setRule($documentError['rule_id']);
						$this->ruleManager->setJobId($this->id);
						$this->ruleManager->setManual($this->manual);
						$this->ruleManager->setApi($this->api);
					}
                    $errorActionDocument = $this->ruleManager->actionDocument($documentError['id'], 'rerun');
                    if (!empty($errorActionDocument)) {
                        $this->message .= print_r($errorActionDocument, true);
                    }
					$ruleId = $documentError['rule_id'];
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Error AAA : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            $this->message .= 'Error AAA : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function initJob(string $paramJob): array
    {
		try {
			$this->paramJob = $paramJob;
			$this->id = uniqid('', true);
			$this->start = microtime(true);
			
			// Do not run several job in the same time for non premium version
			$sqlJobOpen = "SELECT * FROM job WHERE status = 'Start' LIMIT 1";
			$stmt = $this->connection->prepare($sqlJobOpen);
			$result = $stmt->executeQuery();
			$job = $result->fetchAssociative(); // 1 row
			// Error if one job is still running
			if (!empty($job)) {
				$this->message .= $this->toolsManager->getTranslation(['messages', 'rule', 'another_task_running']).';'.$job['id'];
				return ['success' => false, 'message' => $this->message];
			}

			// Create Job
			$insertJob = $this->insertJob();
			if ($insertJob) {
				$this->createdJob = true;
				return ['success' => true, 'message' => ''];
			} else {
				$this->message .= 'Failed to create the Job in the database';
				return ['success' => false, 'message' => $this->message];
			}
		} catch (Exception $e) {
            throw new Exception('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function closeJob(): bool
    {
        // Get job data
        $this->logData = $this->getLogData();
		
		// No lock should remain but we check to be sure nothing remains locked
		// Remove lock on document locked by this job
		$this->documentRepository->removeLock($this->id);

		// Remove lock (send and read) on rule locked by this job
		$this->ruleRepository->removeLock($this->id);

        // Update table job
        return $this->updateJob();
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
        } else {
            return 'Action '.$event.' unknown. Failed to run this action. ';
        }
    }

    /**
     * @param $job
     * @param $param
     *
     * @return false|string
     */
    public function runBackgroundJob($job, $param)
    {
        try {
            // Création d'un fichier temporaire
            $guid = uniqid();
            $params = '';

            // If cancel job, we force the Y (used for super admin because the cancel button is also diplayed for the closed documents)
            if ('cancel' == $param[0]) {
                $param[] = 'Y';
            }
            // Formatage des paramètres
            if (!empty($param)) {
                foreach ($param as $valueParam) {
                    $params .= $valueParam.' ';
                }
            }
            // Get the php executable
            $php = $this->toolsManager->getPhpVersion();

            //Create your own folder in the cache directory
            $fileTmp = $this->parameterBagInterface->get('kernel.cache_dir').'/myddleware/job/'.$guid.'.txt';
            $fs = new Filesystem();
            try {
                $fs->mkdir(dirname($fileTmp));
            } catch (IOException $e) {
                throw new Exception('An error occurred while creating your directory');
            }
            exec($php.' '.__DIR__.'/../../bin/console myddleware:'.$job.' '.$params.' --env='.$this->env.'  > '.$fileTmp.' &');
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
            // Massaction returns "1;" + the job ID
            $idJob = substr(fread($file, 25), -23);
            fclose($file);
            while (empty($idJob)) {
                if ($cpt >= 29) {
                    throw new Exception('No task id given.');
                }
                sleep(1);
                $file = fopen($fileTmp, 'r');
                $idJob = substr(fread($file, 25), -23);
                fclose($file);
                ++$cpt;
            }

            // Renvoie du message en session
            $session = new Session();
            $session->set('info', ['<a href="'.$this->router->generate('task_view', ['id' => $idJob]).'" target="_blank">'.$this->toolsManager->getTranslation(['session', 'task', 'msglink']).'</a>. '.$this->toolsManager->getTranslation(['session', 'task', 'msginfo'])]);

            return $idJob;
        } catch (Exception $e) {
            $session = new Session();
            $session->set('info', [$e->getMessage()]); // Vous venez de lancer une nouvelle longue tâche. Elle est en cours de traitement.

            return false;
        }
    }

    // Function to modify a group of documents
    public function massAction($action, $dataType, $ids, $forceAll, $fromStatus, $toStatus): bool
    {
        try {
			$errors = array();
            if (empty($ids)) {
                throw new Exception('No ids in the input parameter of the function massAction.');
            }
			//Check if the parameter is a rule group 
			if ($this->toolsManager->isPremium()) {
				// Get the rules from the group
				$rulesGroup = $this->toolsManager->getRulesFromGroup(implode(',',$ids));
				if (!empty($rulesGroup)) {
					// Recalculate the array ids
					$ids = array();
					foreach($rulesGroup as $ruleGroup) {
						$ids[] = $ruleGroup['id'];
					}
				}
			}
			
            // Build IN parameter
            $queryIn = '(';
            foreach ($ids as $recordId) {
                $queryIn .= "'".$recordId."',";
            }
            $queryIn = rtrim($queryIn, ',');
            $queryIn .= ')';

            // Buid WHERE section
            // Filter on rule or docuement depending on the data type
            $where = ' WHERE ';
            if (in_array($dataType, array('rule','group'))) {
                $where .= " rule.id IN $queryIn ";
            } elseif ('document' == $dataType) {
                $where .= " document.id IN $queryIn ";
            }
            // No filter on status if the action is restore/changeStatus or if forceAll = 'Y'
            if (
                    'Y' != $forceAll
                and 'restore' != $action
                and 'changeStatus' != $action
            ) {
                $where .= " AND document.global_status IN ('Open','Error') ";
            }
            // Filter on relevant delete flag (select deleted = 1 only for restore action)
            if ('restore' == $action) {
                $where .= ' AND document.deleted = 1 ';
            } else {
                $where .= ' AND document.deleted = 0 ';
            }
            // Filter on status for the changeStatus action
            if ('changeStatus' == $action) {
                $where .= " AND document.status = '$fromStatus' ";
            }
            
			// Filter on document locked
            if ($action == 'unlock') {
                $where .= " AND document.job_lock != '' AND document.job_lock IS NOT NULL ";
            }
			
			// Filter on document with workflow in error
            if ($action == 'rerunWorkflow') {
                $where .= " AND document.workflow_error = 1 ";
            }
            // Build the query
            $sqlParams = '	SELECT 
								document.id,
								document.rule_id
							FROM document	
								INNER JOIN rule
									ON document.rule_id = rule.id'
                            .$where.'
							ORDER BY rule.id';

            $stmt = $this->connection->prepare($sqlParams);
            $result = $stmt->executeQuery();
            $documents = $result->fetchAllAssociative();

            // include_once 'rule.php';
            if (!empty($documents)) {
                $param['ruleId'] = '';
                foreach ($documents as $document) {  
                    // If new rule, we create a new instance of RuleManager
                    if ($param['ruleId'] != $document['rule_id']) {
                        $this->ruleManager->setApi($this->api);
                        $this->ruleManager->setJobId($this->id);
						$this->ruleManager->setManual($this->manual);
                        $this->ruleManager->setRule($document['rule_id']);
                    }
                    $error = $this->ruleManager->actionDocument($document['id'], $action, $toStatus);
					// Save the error if exists
					if (!empty($error)) {
						$errors[] = current($error);
					}
                }
            } else {
                throw new Exception('No document found corresponding to the input parameters. No action done in the job massAction. ');
            }
			// Log all erros 
			if (!empty($errors)) {
				throw new Exception(implode('|', $errors));
			}
        } catch (Exception $e) {
            $this->message .= 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            return false;
        }
        return true;
    }

    // Function to clear the unlock on rule
    public function clearLock($dataType, $ids): bool
    {
        try {
            if (empty($ids)) {
                throw new Exception('No ids in the input parameter of the function clearLock.');
            }
    
            $queryIn = '(' . implode(',', array_map(function($id) { return "'" . $id . "'"; }, $ids)) . ')';
            $where = ' WHERE id IN ' . $queryIn;
    
            if ('rule' == $dataType) {
                $sqlClear = "UPDATE rule SET read_job_lock = '' " . $where;
                $stmtClear = $this->connection->prepare($sqlClear);
                $result = $stmtClear->executeQuery();
            } else {
                throw new Exception('Unsupported data type for clearLock function.');
            }
        } catch (Exception $e) {
            $this->logger->error('Error in clearLock: ' . $e->getMessage());
            return false;
        }
    
        return true;
    }

    // Function to create one of several document using a query 
    public function readRecord($ruleId, $filterQuery, $filterValues): bool
    {
        try {
            $responseFilter = [];
            $responseCheckPredecessor = [];
            $responseCheckParent = [];
            $responseTransform = [];
            $responseGetTargetData = [];
            $responseSend = [];
            $documentIds = [];

            // Get the filter values
            $filterValuesArray = explode(',', $filterValues);
            if (empty($filterValuesArray)) {
                throw new Exception('Invalide filter value. Failed to read data.');
            }

            // Check that the rule value is valid
            $sqlRule = 'SELECT * FROM rule WHERE id = :filter AND deleted = 0';
            $stmt = $this->connection->prepare($sqlRule);
            $stmt->bindValue('filter', $ruleId);
            $result = $stmt->executeQuery();
            $rule = $result->fetchAssociative(); // 1 row
            if (empty($rule['id'])) {
                throw new Exception('Rule '.$ruleId.' doesn\'t exist or is deleted. Failed to read data.');
            }

            // We instanciate the rule
            $this->ruleManager->setRule($ruleId);
            $this->ruleManager->setJobId($this->id);
			$this->ruleManager->setManual($this->manual);
            $this->ruleManager->setApi($this->api);

            // Try to read data for each values
            foreach ($filterValuesArray as $value) {
                // Generate documents
                $documents = $this->ruleManager->generateDocuments($value, true, '', $filterQuery);
                if (!empty($documents->error)) {
                    throw new Exception($documents->error);
                }
                // We assign the id to an id section of the array
                if (!empty($documents[0]->id)) {
                    $documentIds[]['id'] = $documents[0]->id;
                }
            }

            // Filter documents
            if (!empty($documentIds)) {
                $responseFilter = $this->ruleManager->filterDocuments($documentIds);
            }

            // Check predecessor
            $documentIds = $this->refreshDocumentList($documentIds, $responseFilter);
            if (!empty($documentIds)) {
                $responseCheckPredecessor = $this->ruleManager->checkPredecessorDocuments($documentIds);
            }

            // Check parent
            $documentIds = $this->refreshDocumentList($documentIds, $responseCheckPredecessor);
            if (!empty($documentIds)) {
                $responseCheckParent = $this->ruleManager->checkParentDocuments($documentIds);
            }

            // Transform document
            $documentIds = $this->refreshDocumentList($documentIds, $responseCheckParent);
            if (!empty($documentIds)) {
                $responseTransform = $this->ruleManager->transformDocuments($documentIds);
            }

            // Get history
            $documentIds = $this->refreshDocumentList($documentIds, $responseTransform);
            if (!empty($documentIds)) {
                $responseGetTargetData = $this->ruleManager->getTargetDataDocuments($documentIds);
            }

            // Send document
            $documentIds = $this->refreshDocumentList($documentIds, $responseGetTargetData);
            if (!empty($documentIds)) {
                $responseSend = $this->ruleManager->sendDocuments($documentIds);
            }
        } catch (Exception $e) {
            $this->message .= 'Error : '.$e->getMessage();
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            return false;
        }
        return true;
    }

    // Remove the document from the list if the retrun value is not true
    public function refreshDocumentList($documentIds, $response) {
        $documentListRefresh = [];
        if (!empty($response)) {
            foreach($response as $docId => $return) {
                if ($return == true) {
                    $documentListRefresh[]['id'] = $docId;
                }
            }
        }
        return $documentListRefresh;
    }

    // Remove all data flagged deleted in the database
    public function pruneDatabase($nbDays): void
    {
        $this->noDocumentsTablesToEmptyCounter = 0;
        $this->noRulesTablesToEmptyCounter = 0;

        $date = new DateTime();
        $date->modify('-' . (int) $nbDays . ' days');
        $this->pruneDatabaseMaxDate = $date->format('Y-m-d H:i:s');

        try {
            $this->processDeletableItems($this->getListOfSqlDocumentParams(), 'document');
            // Start deleteing rules when there is no more documents to delete
            if($this->noDocumentsTablesToEmptyCounter === 4)
            {
                $this->processDeletableItems($this->documentSqlParams(), 'document');
            }
            if($this->noDocumentsTablesToEmptyCounter === 5)
            {
                $this->processDeletableItems($this->getListOfSqlRuleParams(), 'rule');
                if ($this->noRulesTablesToEmptyCounter === 7)
                {
                    $this->processDeletableItems($this->ruleSqlParams(), 'rule');
                }
            }
        } catch (Exception $e) {
            $this->message .= 'Error  : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
            $this->logger->error($this->message);
        }
    }

    public function processDeletableItems($listOfSqlParams, $tableTypeToDelete)
    {
        foreach ($listOfSqlParams as $oneSqlParam => $oneDeleteStatement)
            {
                $requestCounter = 0;
                while ($requestCounter < $this->limitOfRequestExecution) {
                    $requestCounter++;
                    $itemIds = $this->findItemsToDelete($oneSqlParam);
                    if(empty($itemIds)) {
                        if($tableTypeToDelete === 'document')
                        {
                            $this->noDocumentsTablesToEmptyCounter++;
                            break;
                        }
                        else {
                            $this->noRulesTablesToEmptyCounter++;
                            break;
                        }
                    }
                    $cleanItemIds = $this->cleanItemIds($itemIds);
                    $this->deleteSelectedItems($cleanItemIds, $oneDeleteStatement);
                }
            }
    }

    public function getListOfSqlDocumentParams(): array
    {


        $listOfSqlDocumentParams = [
            "SELECT log.id
        FROM log
        LEFT OUTER JOIN document ON log.doc_id = document.id
        WHERE document.deleted = 1 AND log.created < '$this->pruneDatabaseMaxDate'
        LIMIT :limitOfDeletePerRequest" => "DELETE FROM log WHERE id IN (%s)",

        "SELECT documentdata.id
        FROM documentdata
        LEFT OUTER JOIN document ON documentdata.doc_id = document.id
        WHERE document.deleted = 1 AND document.date_modified < '$this->pruneDatabaseMaxDate'
        LIMIT :limitOfDeletePerRequest" => "DELETE FROM documentdata WHERE id IN (%s)",

        "SELECT documentaudit.id
        FROM documentaudit
        LEFT OUTER JOIN document ON documentaudit.doc_id = document.id
        WHERE document.deleted = 1 AND document.date_modified < '$this->pruneDatabaseMaxDate'
        LIMIT :limitOfDeletePerRequest" => "DELETE FROM documentaudit WHERE id IN (%s)",

        "SELECT documentrelationship.id
        FROM documentrelationship
        LEFT OUTER JOIN document ON documentrelationship.doc_id = document.id
        WHERE document.deleted = 1 AND document.date_modified < '$this->pruneDatabaseMaxDate'
        LIMIT :limitOfDeletePerRequest" => "DELETE FROM documentrelationship WHERE id IN (%s)",
        ];

        return $listOfSqlDocumentParams;
    }

    public function documentSqlParams()
    {

        $listOfSqlDocumentParams = [
            "SELECT document.id
        FROM document
        WHERE document.deleted = 1 AND document.date_modified < '$this->pruneDatabaseMaxDate'
        LIMIT :limitOfDeletePerRequest" => "DELETE FROM document WHERE id IN (%s)",
        ];
        return $listOfSqlDocumentParams;
    }

    public function getListOfSqlRuleParams()
    {
        $listOfSqlRuleParams = [
        "SELECT ruleaudit.id
        FROM ruleaudit
        LEFT OUTER JOIN rule ON ruleaudit.rule_id = rule.id
        WHERE rule.deleted = 1 AND rule.date_modified < '$this->pruneDatabaseMaxDate'
        LIMIT :limitOfDeletePerRequest" => "DELETE FROM ruleaudit WHERE id IN (%s)",

        "SELECT rulefield.id
        FROM rulefield
        LEFT OUTER JOIN rule ON rulefield.rule_id = rule.id
        WHERE rule.deleted = 1 AND rule.date_modified < '$this->pruneDatabaseMaxDate'
        LIMIT :limitOfDeletePerRequest" => "DELETE FROM rulefield WHERE id IN (%s)",

        "SELECT rulefilter.id
        FROM rulefilter
        LEFT OUTER JOIN rule ON rulefilter.rule_id = rule.id
        WHERE rule.deleted = 1 AND rule.date_modified < '$this->pruneDatabaseMaxDate'
        LIMIT :limitOfDeletePerRequest" => "DELETE FROM rulefilter WHERE id IN (%s)",

        "SELECT ruleorder.rule_id
        FROM ruleorder
        LEFT OUTER JOIN rule ON ruleorder.rule_id = rule.id
        WHERE rule.deleted = 1 AND rule.date_modified < '$this->pruneDatabaseMaxDate'
        LIMIT :limitOfDeletePerRequest" => "DELETE FROM ruleorder WHERE rule_id IN (%s)",

        "SELECT rulerelationship.id
        FROM rulerelationship
        LEFT OUTER JOIN rule ON rulerelationship.rule_id = rule.id
        WHERE rule.deleted = 1 AND rule.date_modified < '$this->pruneDatabaseMaxDate'
        LIMIT :limitOfDeletePerRequest" => "DELETE FROM rulerelationship WHERE id IN (%s)",

        "SELECT ruleparamaudit.id
        FROM ruleparamaudit
        LEFT OUTER JOIN ruleparam ON ruleparamaudit.rule_param_id = ruleparam.id
            LEFT OUTER JOIN rule ON ruleparam.rule_id = rule.id
        WHERE rule.deleted = 1 AND rule.date_modified < '$this->pruneDatabaseMaxDate'
        LIMIT :limitOfDeletePerRequest" => "DELETE FROM ruleparamaudit WHERE id IN (%s)",

        "SELECT ruleparam.id
        FROM ruleparam
        LEFT OUTER JOIN rule ON ruleparam.rule_id = rule.id
        WHERE rule.deleted = 1 AND rule.date_modified < '$this->pruneDatabaseMaxDate'
        LIMIT :limitOfDeletePerRequest" => "DELETE FROM ruleparam WHERE id IN (%s)",
        ];

        return $listOfSqlRuleParams;
    }

    public function ruleSqlParams()
    {

        $listOfSqlRuleParams = [
        "SELECT rule.id
        FROM rule
        WHERE rule.deleted = 1 AND rule.date_modified < '$this->pruneDatabaseMaxDate'
        LIMIT :limitOfDeletePerRequest" => "DELETE FROM rule WHERE id IN (%s)"
        ];

        return $listOfSqlRuleParams;
    }
    
    public function findItemsToDelete($oneSqlParam): array
    {
            $stmt = $this->connection->prepare($oneSqlParam);
            $stmt->bindValue(':limitOfDeletePerRequest', (int) trim($this->limitOfDeletePerRequest), PDO::PARAM_INT);
            $result = $stmt->executeQuery();
            $itemIds= [];
            $itemIds = $result->fetchAllAssociative();
        return $itemIds;
    }

    public function cleanItemIds($itemIds)
    {
        $cleanItemIds = [];
        foreach ($itemIds as $oneIdKey => $oneIdValue) {
            foreach ($oneIdValue as $oneInnerKey => $oneInnerValue) {
                    $cleanItemIds[] = $oneInnerValue;
            }
        }
        return $cleanItemIds;
    }

    public function deleteSelectedItems(array $itemIds, string $oneDeleteStatement)
    {
        try {
            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            $sqlParams = sprintf($oneDeleteStatement, $placeholders);
            $stmt = $this->connection->prepare($sqlParams);
            $stmt->execute($itemIds);
        } catch (Exception $e) {
            $this->message .= 'Error  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($this->message);
        }
    }

    public function getRules($force = false)
    {
        try {
            $sqlParams = '	SELECT name_slug 
							FROM ruleorder
								INNER JOIN rule
									ON rule.id = ruleorder.rule_id
							WHERE 
									rule.deleted = 0
								'.(!$force ? ' AND rule.active = 1 ' : '').'
							ORDER BY ruleorder.order ASC';
            $stmt = $this->connection->prepare($sqlParams);
            $result = $stmt->executeQuery();
            $rules = $result->fetchAllAssociative();
            if (!empty($rules)) {
                foreach ($rules as $rule) {
                    $ruleOrder[] = $rule['name_slug'];
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');

            return false;
        }
        if (empty($ruleOrder)) {
            return null;
        }

        return $ruleOrder;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function orderRules(): bool
    {
        $this->connection->beginTransaction(); // -- BEGIN TRANSACTION
        try {
            // Récupération de toutes les règles avec leurs règles liées (si plusieurs elles sont toutes au même endroit)
            // Si la règle n'a pas de relation on initialise l'ordre à 1 sinon on met 99
            $sql = "SELECT
						rule.id,
						GROUP_CONCAT(rulerelationship.field_id SEPARATOR ';') field_id,
						GROUP_CONCAT(rulefield.formula SEPARATOR ';') formula
					FROM rule
						LEFT OUTER JOIN rulefield
							ON rule.id = rulefield.rule_id
						LEFT OUTER JOIN rulerelationship
							ON rule.id = rulerelationship.rule_id
					WHERE
						rule.deleted = 0
					GROUP BY rule.id";
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->executeQuery();
            $rules = $result->fetchAllAssociative();

            if (!empty($rules)) {
				// Add the rule in formula to the list of rules in relationship
				foreach ($rules as $key => $rule) {
					$matches = array();
					// Get the second parameters (rule id) of the lookup functions
					preg_match_all('/lookup\(\{[^}]+\},"([^"]+)"/', $rule['formula'], $matches);
					// Transform result and add it to the other rules (old relationships)
					$rules[$key]['field_id'] .= ';'.implode(';', array_unique($matches[1]));
					$rules[$key]['field_id'] = trim($rules[$key]['field_id'],';');
				}
                // Création d'un tableau en clé valeur et sauvegarde d'un tableau de référence
                $ruleKeyValue = [];
                foreach ($rules as $key => $rule) {
                    // Init order depending on the field_id value
                    if (empty($rule['field_id'])) {
                        $rules[$key]['rule_order'] = 1;
                    } else {
                        $rules[$key]['rule_order'] = 99;
                    }
                    $ruleKeyValue[$rule['id']] = $rules[$key]['rule_order'];
                    $rulesRef[$rule['id']] = $rule;
                }

                // On calcule les priorité tant que l'on a encore des priorité 99
                // On fait une condition sur le $i pour éviter une boucle infinie
                $i = 0;
                while ($i < 20 && in_array('99', $ruleKeyValue)) {
                    ++$i;
                    // Boucles sur les régles
                    foreach ($rules as $rule) {
                        $order = 0;
                        // Si on est une règle sans ordre
                        if (
                                !empty($rule['rule_order'])
                            and '99' == $rule['rule_order']
                        ) {
                            // Récupération des règles liées et recherche dans le tableau keyValue
                            $rulesLink = explode(';', $rule['field_id']);
                            foreach ($rulesLink as $ruleLink) {
                                if (
                                        !empty($ruleKeyValue[$ruleLink])
                                    && $ruleKeyValue[$ruleLink] > $order
                                ) {
                                    $order = $ruleKeyValue[$ruleLink];
                                }
                            }
                            // Si toutes les règles trouvées ont une priorité autre que 99 alors on affecte à la règle la piorité +1 dans les tableaux de références
                            if ($order < 99) {
                                $ruleKeyValue[$rule['id']] = $order + 1;
                                $rulesRef[$rule['id']]['rule_order'] = $order + 1;
                            }
                        }
                    }
                    $rules = $rulesRef;
                }
                // On vide la table RuleOrder
                $sql = 'DELETE FROM ruleorder';
                $stmt = $this->connection->prepare($sql);
                $result = $stmt->executeQuery();

                //Mise à jour de la table
                $insert = 'INSERT INTO ruleorder VALUES ';
                foreach ($ruleKeyValue as $key => $value) {
                    $insert .= "('$key','$value'),";
                }
                // Suppression de la dernière virgule
                $insert = rtrim($insert, ',');
                $stmt = $this->connection->prepare($insert);
                $result = $stmt->executeQuery();
            }
            $this->connection->commit(); // -- COMMIT TRANSACTION
        } catch (Exception $e) {
            $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
            $this->message .= 'Failed to update table RuleOrder : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($this->message);
            return false;
        }

        return true;
    }

    public function generateTemplate($nomTemplate, $descriptionTemplate, $rulesId): array
    {
        try {
            // Init array
            $templateArray = [
                                'name' => $nomTemplate,
                                'description' => $descriptionTemplate,
                            ];
            if (!empty($rulesId)) {
                $rulesOrderIds = $this->templateManager->setRules($rulesId);
                foreach ($rulesOrderIds as $rulesOrderId) {
                    // Generate array with all rules parameters
                    $templateArray['rules'][] = $this->templateManager->extractRule($rulesOrderId['rule_id']);
                }
                // Ecriture du fichier
                $yaml = Yaml::dump($templateArray, 4);
                file_put_contents($this->parameterBagInterface->get('kernel.project_dir').'/src/Templates/'.$nomTemplate.'.yml', $yaml);
            }
        } catch (Exception $e) {
            $this->message .= 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($this->message);

            return ['success' => false, 'message' => $this->message];
        }

        return ['success' => true, 'message' => ''];
    }

    // Permet d'indiquer que le job est lancé manuellement
    protected function setManual()
    {
        if ('background' == $this->env) {
            $this->manual = 0;
        } else {
            $this->manual = 1;
        }
    }

    // Set webserice flag
    public function setApi($value)
    {
        // default value = 0
        $this->api = (!empty($value) ? $value : 0);
    }

    // Myddleware upgrade
    public function upgrade($output)
    {
        // $upgrade = new Upgrade($this->logger, $this->container, $this->connection);
        $upgrade = $this->upgrade;
        $this->message = $upgrade->processUpgrade($output);
    }

    /**
     * Permet de supprimer toutes les données des tabe source, target et history en fonction des paramètre de chaque règle.
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public function clearData($actvieRule)
    { 	
		$where = "ruleparam.name = 'delete'";
		if (!empty($actvieRule)) {
			$where .= " AND rule.active = '1' ";
		}
        // Récupération de chaque règle et du paramètre de temps de suppression
        $sqlParams = "	SELECT 
							rule.id,
							rule.name,
							ruleparam.value days,
							deleted
						FROM rule
							INNER JOIN ruleparam
								ON rule.id = ruleparam.rule_id
						WHERE ".$where;
        $stmt = $this->connection->prepare($sqlParams);
        $result = $stmt->executeQuery();
        $rules = $result->fetchAllAssociative();
		// Calculate the limit for selection
		$limit = $this->nbCallMaxDelete * $this->limitDelete;
        if (!empty($rules)) {
            // Boucle sur toutes les règles
            foreach ($rules as $rule) {
				echo date('Y-m-d H:i:s').' - Rule '.$rule['name'].chr(10);
                // Calculate the date corresponding depending the rule parameters
                $limitDate = new DateTime('now', new DateTimeZone('GMT'));
                $limitDate->modify('-'.$rule['days'].' days');
				
				// Delete document data
                // Select the list of documentdata to be deleted
				try {
					$deleteSourceSelection = "
						SELECT documentdata.doc_id
						FROM document
							INNER JOIN documentdata
								ON document.id = documentdata.doc_id
						WHERE 
								document.rule_id = :ruleId
							AND document.global_status IN ('Close','Cancel')
							AND document.deleted = 0 
							AND document.date_modified < :limitDate
						LIMIT ".$limit;
					// Get selection
					$stmt = $this->connection->prepare($deleteSourceSelection);
					$stmt->bindValue('ruleId', $rule['id']);
					$stmt->bindValue('limitDate', $limitDate->format('Y-m-d H:i:s'));
					$resultDeleteSourceSelection = $stmt->executeQuery();
					$documentIds = $resultDeleteSourceSelection->fetchAllAssociative();
					// $this->connection->commit(); // -- COMMIT TRANSACTION
				} catch (Exception $e) {
                    // $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
                    $this->message .= 'Failed to select the records in table DocumentData: '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                    $this->logger->error($this->message);
                }
				
                try {
					$count = 0;
					$nbCall = 0;
					// Delete data using pack size of $this->limitDelete rows
					// Continue while we have results and if the program dosn't reach the limit call
					while (
							!empty($documentIds)
						AND $nbCall < $this->nbCallMaxDelete
					){
						$i = 0;
						$nbCall++;
						
						// Prepare delete query
						if (!empty($documentIds)) {
							$idString = '';
							// Build IN parameter
							foreach ($documentIds as $key => $documentId) {
								if ($i >= $this->limitDelete) {
									break;
								}
								$i++;
								$idString .= "'".$documentId['doc_id']."',";
								unset($documentIds[$key]);
							}
							$idString = rtrim($idString, ',');
							if (!empty($idString)) {
								$this->connection->beginTransaction();
								// Delete rows in table documentdata
								$deleteDocumentData = "DELETE FROM documentdata WHERE doc_id IN (".$idString.")";
								$stmtDelete = $this->connection->prepare($deleteDocumentData);
								$result = $stmtDelete->executeQuery();
								// Save the number of rows deleted
								if ($result->rowCount() > 0) {
									$count += $result->rowCount();
								}
								$this->connection->commit(); // -- COMMIT TRANSACTION
							}
						}
					}
                } catch (Exception $e) {
                    $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
                    $this->message .= 'Failed to clear the table DocumentData: '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                    $this->logger->error($this->message);
                }
				// Add log 
				if ($count > 0) {
					echo date('Y-m-d H:i:s').' - '.$count.' rows deleted in the table DocumentData for the rule '.$rule['name'].'. '.chr(10);
					$this->message .= $count.' rows deleted in the table DocumentData for the rule '.$rule['name'].'. ';
				}

				// Delete log
                // Select the list of log to be deleted
				try {
					$deleteLogSelection = "
						SELECT log.id
						FROM log
							INNER JOIN document
								ON log.doc_id = document.id
						WHERE 
								document.rule_id = :ruleId
							AND log.msg IN ('Status : Filter_OK','Status : Predecessor_OK','Status : Relate_OK','Status : Transformed','Status : Ready_to_send')	
							AND document.global_status IN ('Close','Cancel')
							AND document.deleted = 0 
							AND document.date_modified < :limitDate	
						LIMIT ".$limit;
					// Get selection
					$stmt = $this->connection->prepare($deleteLogSelection);
					$stmt->bindValue('ruleId', $rule['id']);
					$stmt->bindValue('limitDate', $limitDate->format('Y-m-d H:i:s'));
					$resultDeleteLogSelection = $stmt->executeQuery();
					$logIds = $resultDeleteLogSelection->fetchAllAssociative();
				} catch (Exception $e) {
                    $this->message .= 'Failed to select the records in table DocumentData: '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                    $this->logger->error($this->message);
                }


                try {
					$count = 0;
					$nbCall = 0;
					// Delete data using pack size of $this->limitDelete rows
					// Continue while we have results and if the program dosn't reach the limit call
					while (
							!empty($logIds)
						AND $nbCall < $this->nbCallMaxDelete
					){
						$i = 0;
						$nbCall++;
						
						// Prepare delete query
						if (!empty($logIds)) {
							$idString = '';
							// Build IN parameter
							foreach ($logIds as $key => $logId) {
								if ($i >= $this->limitDelete) {
									break;
								}
								$i++;
								$idString .= "'".$logId['id']."',";
								unset($logIds[$key]);
							}
							$idString = rtrim($idString, ',');
							if (!empty($idString)) {
								$this->connection->beginTransaction();
								// Delete rows in table log
								$deleteLog = "DELETE FROM log WHERE id IN (".$idString.")";
								$stmtDeleteLog = $this->connection->prepare($deleteLog);
								$result = $stmtDeleteLog->executeQuery();
								// Save the number of rows deleted
								if ($result->rowCount() > 0) {
									$count += $result->rowCount();
								}
								$this->connection->commit(); // -- COMMIT TRANSACTION
							}
						}
						
					} 
                } catch (Exception $e) {
                    $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
                    $this->message .= 'Failed to clear the table Log: '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                    $this->logger->error($this->message);
                }
				// Add log 
				if ($count > 0) {
					echo date('Y-m-d H:i:s').' - '.$count.' rows deleted in the table Log for the rule '.$rule['name'].'. '.chr(10);
					$this->message .= $count.' rows deleted in the table Log for the rule '.$rule['name'].'. ';
				}	  
            }
        }
		
		// Delete job
        try {
            $limitDate = new DateTime('now', new DateTimeZone('GMT'));
            $limitDate->modify('-'.$this->nbDayClearJob.' days');
            // Remove empty jobs
            $deleteJob = " 	
				DELETE 
				FROM job
				WHERE 
						status = 'End'
					AND param NOT IN ('cleardata', 'notification')
					AND message  = ''
					AND open = 0
					AND close = 0
					AND cancel = 0
					AND error = 0
					AND end < :limitDate
					AND job.id NOT IN (select job_id from log) 
				LIMIT ".$this->limitDelete;
			$nbCall = 0;
			$count = 0;
			do {
				$nbCall++;
				$this->connection->beginTransaction();
				$stmt = $this->connection->prepare($deleteJob);
				$stmt->bindValue('limitDate', $limitDate->format('Y-m-d H:i:s'));
				$resultDeleteJob = $stmt->executeQuery();
				$this->connection->commit(); // -- COMMIT TRANSACTION
				// Save the number of rows deleted
				$count += $resultDeleteJob->rowCount();
			} while (
					$resultDeleteJob->rowCount() > 0
				AND $nbCall < $this->nbCallMaxDelete
			);
			
			// Add log 
			if ($count > 0) {
				$this->message .= $count.' rows deleted in the table Job. ';
				echo date('Y-m-d H:i:s').' - '.$count.' rows deleted in the table Job. '.chr(10);
			}
        } catch (Exception $e) {
            $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
            $this->message .= 'Failed to clear job: '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($this->message);		
        }
    }

    // Récupération des données du job
    public function getLogData()
    {
        try {
            // Récupération du nombre de document envoyé et en erreur pour ce job
            $this->logData['Close'] = 0;
            $this->logData['Cancel'] = 0;
            $this->logData['Open'] = 0;
            $this->logData['Error'] = 0;
            $this->logData['paramJob'] = $this->paramJob;
            $sqlParams = '	SELECT 
								count(distinct document.id) nb,
								document.global_status
							FROM log
								INNER JOIN document
									ON log.doc_id = document.id
							WHERE
								log.job_id = :id
							GROUP BY document.global_status';
            $stmt = $this->connection->prepare($sqlParams);
            $stmt->bindValue('id', $this->id);
            $result = $stmt->executeQuery();
            $data = $result->fetchAllAssociative();
            if (!empty($data)) {
                foreach ($data as $row) {
                    if ('Close' == $row['global_status']) {
                        $this->logData['Close'] = $row['nb'];
                    } elseif ('Error' == $row['global_status']) {
                        $this->logData['Error'] = $row['nb'];
                    } elseif ('Cancel' == $row['global_status']) {
                        $this->logData['Cancel'] = $row['nb'];
                    } elseif ('Open' == $row['global_status']) {
                        $this->logData['Open'] = $row['nb'];
                    }
                }
            }

            // Récupération des solutions du job
            $sqlParams = '	SELECT 
								Connector_target.sol_id sol_id_target,
								Connector_source.sol_id sol_id_source
							FROM (SELECT DISTINCT rule_id FROM log WHERE job_id = :id) rule_job
								INNER JOIN rule
									ON rule_job.rule_id = rule.id
								INNER JOIN connector Connector_source
									ON Connector_source.id = rule.conn_id_source
								INNER JOIN connector Connector_target
									ON Connector_target.id = rule.conn_id_target';
            $stmt = $this->connection->prepare($sqlParams);
            $stmt->bindValue('id', $this->id);
            $result = $stmt->executeQuery();
            $solutions = $result->fetchAllAssociative();
            $this->logData['solutions'] = '';
            if (!empty($solutions)) {
                foreach ($solutions as $solution) {
                    $concatSolution[] = $solution['sol_id_target'];
                    $concatSolution[] = $solution['sol_id_source'];
                }
                $concatSolutions = array_unique($concatSolution);
                // Mise au format pour la liste multi de Sugar
                $concatSolutions = '^'.implode('^,^', $concatSolutions).'^';
                $this->logData['solutions'] = $concatSolutions;
            }

            // Get the document detail if requested
            if (isset($documentDetail) && $documentDetail) {
                $sqlParamsDoc = '	SELECT DISTINCT document.*
								FROM log
									INNER JOIN document
										ON log.doc_id = document.id
								WHERE
									log.job_id = :id';
                $stmt = $this->connection->prepare($sqlParamsDoc);
                $stmt->bindValue('id', $this->id);
                $result = $stmt->executeQuery();
                $this->logData['documents'] = $result->fetchAllAssociative();
            }

            // Récupération de la durée du job
            $time_end = microtime(true);
            $this->logData['duration'] = round($time_end - $this->start, 2);

            // récupération de l'id du job
            $this->logData['myddlewareId'] = $this->id;

            // Indique si le job est lancé manuellement ou non
            $this->logData['Manual'] = $this->manual;
            $this->logData['Api'] = $this->api;

            // Récupération des erreurs
            $this->logData['jobError'] = $this->message;
        } catch (Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            $this->logData['jobError'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }

        return $this->logData;
    }

    // Mise à jour de la table Job

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function updateJob(): bool
    {
        try {
            $close = $this->logData['Close'];
            $cancel = $this->logData['Cancel'];
            $open = $this->logData['Open'];
            $error = $this->logData['Error'];
            $now = gmdate('Y-m-d H:i:s');
            $message = $this->message;
            if (!empty($this->message)) {
                $message = htmlspecialchars($this->message);
            }
            $query_header = "UPDATE job 
							SET 
								end = :now, 
								status = 'End', 
								close = :close, 
								cancel = :cancel, 
								open = :open, 
								error = :error, 
								message = :message
							WHERE id = :id";
            $stmt = $this->connection->prepare($query_header);
            $stmt->bindValue('now', $now);
            $stmt->bindValue('close', $close);
            $stmt->bindValue('cancel', $cancel);
            $stmt->bindValue('open', $open);
            $stmt->bindValue('error', $error);
            $stmt->bindValue('message', $message);
            $stmt->bindValue('id', $this->id);
            $result = $stmt->executeQuery();
        } catch (Exception $e) {
            $this->logger->error('Failed to update Job : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            $this->message .= 'Failed to update Job : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            return false;
        }
        return true;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function insertJob(): bool
    {
        try {
			$job = new Job();
			$job->setId($this->id);
			$job->setStatus('Start');
			$job->setParam($this->paramJob);
			$job->setBegin(new DateTime());
			$job->setManual($this->manual);
			$job->setApi($this->api);
			$this->entityManager->persist($job);
			$this->entityManager->flush();
        } catch (Exception $e) {
            $this->logger->error('Failed to create Job : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            $this->message .= 'Failed to create Job : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            return false;
        }

        return true;
    }

    //check if the job is too long
    public function checkJob($period)
    {
        try {
            if (empty($period)) {
                $period = $this->checkJobPeriod;
            }
            // Search only jobs with status start and last log created before the limit
			// We use the begin of teh job if no log
            $sqlParams = "
			SELECT 
				job.id, 
				job.begin, 
				TIMESTAMPDIFF(SECOND,  job.begin, NOW()) diff_job,
				MAX(log.created) log_created,
				TIMESTAMPDIFF(SECOND, MAX(log.created), NOW()) diff_log
			FROM job
				LEFT OUTER JOIN log
					ON job.id = log.job_id
			WHERE
					job.status = 'start'
			GROUP BY job.id
			HAVING diff_log > :period OR (diff_job > :period AND log_created IS NULL)
			";
            $stmt = $this->connection->prepare($sqlParams);
            $stmt->bindValue('period', $period);

            $result = $stmt->executeQuery();
            $jobs = $result->fetchAllAssociative();
            foreach ($jobs as $job) {
                //clone because, the job that is not the current job
                $jobManagerChekJob = clone $this;
                $jobManagerChekJob->setId($job['id']);
                $jobManagerChekJob->closeJob();   
				$this->setMessage('Task '.$job['id'].' successfully closed. ');
            }
        } catch (Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
			$this->setMessage('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            return false;
        }
        return true;
    }
}
