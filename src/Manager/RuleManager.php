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

use App\Entity\Config;
use App\Entity\DocumentData;
use App\Entity\Document;
use App\Entity\Rule;
use App\Entity\RuleParam;
use App\Entity\RuleParamAudit as RuleParamAudit;
use App\Entity\Variable;
use App\Repository\DocumentRepository;
use App\Repository\RuleOrderRepository;
use App\Repository\RuleRelationShipRepository;
use App\Repository\RuleRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface; // Tools
use Symfony\Component\Routing\RouterInterface;
use App\Manager\NotificationManager;

class RuleManager
{
    protected Connection $connection;
    protected LoggerInterface $logger;
    protected $ruleId;
    protected $rule;
    protected $ruleFields;
    protected $ruleParams;
    protected $variables;
    protected $sourceFields;
    protected $targetFields;
    protected $ruleRelationships;
    protected $ruleWorkflows;
    protected $ruleFilters;
    protected $solutionSource;
    protected $solutionTarget;
    protected $jobId;
    protected $manual;
    protected $key;
    protected int $limit = 100;
    protected int $offset = 0;
    protected int $limitReadCommit = 1000;
    protected ?ToolsManager $tools;
    protected $configParams;
    protected $api;    // Specify if the class is called by the API
    protected EntityManagerInterface $entityManager;
    protected ParameterBagInterface $parameterBagInterface;
    protected ?DocumentManager $documentManager;
    private $env;
    private ?RouterInterface $router;
    private ?RuleRepository $ruleRepository;
    private ?RuleRelationShipRepository $ruleRelationShipRepository;
    protected ?SolutionManager $solutionManager;
    private ?DocumentRepository $documentRepository;
    private ?RuleOrderRepository $ruleOrderRepository;
    private ?RequestStack $requestStack;
    protected FormulaManager $formulaManager;
    private $dataSource;
    private ?NotificationManager $notificationManager;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBagInterface,
        FormulaManager $formulaManager,
        SolutionManager $solutionManager = null,
        DocumentManager $documentManager = null,
        RuleRepository $ruleRepository = null,
        RuleRelationShipRepository $ruleRelationShipRepository = null,
        RuleOrderRepository $ruleOrderRepository = null,
        DocumentRepository $documentRepository = null,
        RouterInterface $router = null,
        KernelInterface $kernel = null,
        RequestStack $requestStack = null,
        ToolsManager $tools = null,
        NotificationManager $notificationManager = null
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->entityManager = $entityManager;
        $this->ruleRepository = $ruleRepository;
        $this->ruleRelationShipRepository = $ruleRelationShipRepository;
        $this->ruleOrderRepository = $ruleOrderRepository;
        $this->documentRepository = $documentRepository;
        $this->tools = $tools;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->solutionManager = $solutionManager;
        $this->documentManager = $documentManager;
        $this->parameterBagInterface = $parameterBagInterface;
        $this->env = $this->parameterBagInterface->get('env'); // access env variable defined in config/services.yaml
        $this->formulaManager = $formulaManager;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function setRule($idRule)
    {
        $this->ruleId = $idRule;
        if (!empty($this->ruleId)) {
            $rule = "	SELECT 
							rule.*, 
							(SELECT value FROM ruleparam WHERE rule_id = :ruleId and name= 'mode') mode,
							source_solution.name as solution_source_name,
							target_solution.name as solution_target_name
						FROM rule 
							INNER JOIN connector source_connector
								 ON rule.conn_id_source = source_connector.id
								AND source_connector.deleted = 0
								INNER JOIN solution	source_solution
									ON source_connector.sol_id = source_solution.id
							INNER JOIN connector target_connector
								 ON rule.conn_id_target = target_connector.id
								AND target_connector.deleted = 0
								INNER JOIN solution	target_solution
									ON target_connector.sol_id = target_solution.id
						WHERE rule.id = :ruleId";
			$stmt = $this->connection->prepare($rule);
            $stmt->bindValue(':ruleId', $this->ruleId);
            $result = $stmt->executeQuery();
            $this->rule = $result->fetchAssociative();
            // Set the rule parameters and rule relationships
            $this->setRuleParam();
            $this->setLimit();
            $this->setRuleRelationships();
			$this->setRuleFilter();
			$this->setRuleWorkflows();
            // Set the rule fields (we use the name_slug in $this->rule)
            $this->setRuleField();
            $this->setVariable();
        }
    }

    public function getRule()
    {
        return $this->rule;
    }

    public function getSourceFields()
    {
        return $this->sourceFields;
    }

    public function setJobId($jobId)
    {
        $this->jobId = $jobId;
    }

    public function setManual($manual)
    {
        $this->manual = $manual;
    }

    public function setApi($api)
    {
        $this->api = $api;
    }

    // Unset the lock on the rule
	protected function setRuleLock() {
		try {
			// Get the rule details
			$rule = $this->entityManager->getRepository(Rule::class)->findOneBy(['id' => $this->ruleId, 'deleted' => false]);
			// If read lock empty, we set the lock with the job id
			if (empty($rule->getReadJobLock())) {
				$rule->setReadJobLock($this->jobId);
				$this->entityManager->persist($rule);
				$this->entityManager->flush();
				return true;
			}	
        } catch (Exception $e) {
            $this->logger->error('Failed set the lock on the rule '.$this->ruleId.' : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
		return false;
	}
	
    // Unset the lock on the rule 
	public function unsetRuleLock() {
		try {
            // Get the rule details
            $rule = $this->entityManager->getRepository(Rule::class)->findOneBy(['id' => $this->ruleId, 'deleted' => false]);
            // If read lock empty, we set the lock with the job id
            $readJobLock = $rule->getReadJobLock();
            if (
                    !empty($readJobLock)
                AND $readJobLock == $this->jobId
            ) {
                $rule->setReadJobLock('');
                $this->entityManager->persist($rule);
                $this->entityManager->flush();
            }  elseif (!empty($readJobLock)) {
                return false;
            }
        } catch (Exception $e) {
            $this->logger->error('Failed unset the lock on the rule '.$this->ruleId.' : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            return false;
        }
		return true;
	}
	
	protected function getRuleLock() {
		// Get the rule details
		$rule = $this->entityManager->getRepository(Rule::class)->findOneBy(['id' => $this->ruleId, 'deleted' => false]);
		return $rule->getReadJobLock();
	}
	
    /**
     * Generate a document for the current rule for a specific id in the source application. We don't use the reference for the function read.
     * If parameter readSource is false, it means that the data source are already in the parameter param, so no need to read in the source application.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function generateDocuments($idSource, $readSource = true, $param = '', $idFiledName = 'id')
    {
        try {
            $documents = [];
            if ($readSource) {
                // Connection to source application
                $connexionSolution = $this->connexionSolution('source');
                if (false === $connexionSolution) {
                    throw new \Exception('Failed to connect to the source solution.');
                }

                // Read data in the source application
                $read['module'] = $this->rule['module_source'];
                $read['fields'] = $this->sourceFields;
                $read['ruleParams'] = $this->ruleParams;
                $read['rule'] = $this->rule;
                // If the query is in the current record we replace Myddleware_element_id by id
                if ('Myddleware_element_id' == $idFiledName) {
                    $idFiledName = 'id';
                }
                $read['query'] = [$idFiledName => $idSource];
                // In case we search a specific record, we set an default value in date_ref because it is a requiered parameter in the read function
                $read['date_ref'] = '1970-01-01 00:00:00';
                $read['call_type'] = 'read';
                $read['jobId'] = $this->jobId;
                $dataSource = $this->solutionSource->readData($read);
                if (!empty($dataSource['error'])) {
                    throw new \Exception('Failed to read record '.$idSource.' in the module '.$read['module'].' of the source solution. '.(!empty($dataSource['error']) ? $dataSource['error'] : ''));
                }
            } else {
                $dataSource['values'][] = $param['values'];
            }
            if (!empty($dataSource['values'])) {
                foreach ($dataSource['values'] as $docData) {
                    // Generate document
                    $docParam['rule'] = $this->rule;
                    $docParam['ruleFields'] = $this->ruleFields;
                    $docParam['ruleRelationships'] = $this->ruleRelationships;
                    $docParam['ruleFilters'] = $this->ruleFilters;
					$docParam['ruleWorkflows'] = $this->ruleWorkflows;
                    $docParam['data'] = $docData;
                    $docParam['jobId'] = $this->jobId;
                    $docParam['api'] = $this->api;
                    // If the document is a child, we save the parent in the table Document
                    if (!empty($param['parent_id'])) {
                        $docParam['parentId'] = $param['parent_id'];
                    }
					
                    // Create new documentManager with a clean entityManager			
                    $chlidEntityManager = clone $this->entityManager;
					$chlidEntityManager->clear();
					$childDocument = new DocumentManager($this->logger, $this->connection, $chlidEntityManager, $this->formulaManager);
            
                    // Set the param values and clear all document attributes
                    $childDocument->setParam($docParam, true);
                    $createDocument = $childDocument->createDocument();
                    if (!$createDocument) {
                        throw new \Exception('Failed to create document : '.$this->documentManager->getMessage());
                    }
                    $documents[] = $childDocument;
                }
            }
            return $documents;
        } catch (\Exception $e) {
            $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            $errorObj = new \stdClass();
            $errorObj->error = $error;

            return $errorObj;
        }
    }

    // Connect to the source or target application
    public function connexionSolution($type): bool
    {
        try {
            if ('source' == $type) {
                $connId = $this->rule['conn_id_source'];
            } elseif ('target' == $type) {
                $connId = $this->rule['conn_id_target'];
            } else {
                return false;
            }

            // Get params connection
            $sql = 'SELECT id, conn_id, name, value
		    		FROM connectorparam 
		    		WHERE conn_id = :connId';
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(':connId', $connId);
            $result = $stmt->executeQuery();
            $tab_params = $result->fetchAllAssociative();
            $params = [];
            if (!empty($tab_params)) {
                foreach ($tab_params as $key => $value) {
                    $params[$value['name']] = $value['value'];
                    $params['ids'][$value['name']] = ['id' => $value['id'], 'conn_id' => $value['conn_id']];
                }
            }

            // Connect to the application
            if ('source' == $type) {
                $this->solutionSource = $this->solutionManager->get($this->rule['solution_source_name']);
                $this->solutionSource->setApi($this->api);
                $loginResult = $this->solutionSource->login($params);
                $c = (($this->solutionSource->connexion_valide) ? true : false);
            } else {
                $this->solutionTarget = $this->solutionManager->get($this->rule['solution_target_name']);
                $this->solutionTarget->setApi($this->api);
                $loginResult = $this->solutionTarget->login($params);
                $c = (($this->solutionTarget->connexion_valide) ? true : false);
            }
            if (!empty($loginResult['error'])) {
                throw new \Exception($loginResult['error']);
            }

            return $c;
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            return false;
        }
    }

    /**
     * Permet de mettre toutes les données lues dans le système source dans le tableau $this->dataSource
     * Cette fonction retourne le nombre d'enregistrements lus.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function createDocuments()
    {
        $readSource = null;
        // Si la lecture pour la règle n'est pas désactivée
        // Et si la règle est active et pas supprimée ou bien le lancement est en manuel
        if (
                empty($this->ruleParams['disableRead'])
            && (
                    (
                            0 == $this->rule['deleted']
                        && 1 == $this->rule['active']
                    )
                    || (
                        1 == $this->manual
                    )
                )
        ) {
			// Check the rule isn't locked
			if (!$this->setRuleLock()) {
				return array('error' => 'The rule '.$this->ruleId.' is locked by the task '.$this->getRuleLock().'. Failed to read the source application. ');
			}
			
            $this->connection->beginTransaction(); // -- BEGIN TRANSACTION suspend auto-commit
            try {
				// lecture des données dans la source
				$readSource = $this->readSource();
				if (empty($readSource['error'])) {
					$readSource['error'] = '';
				}
				// If error we unlock the rule and we return the result
				if (!isset($readSource['count'])) {
					$this->unsetRuleLock();
					$this->connection->commit();
					return $readSource;
				}
			
                if ($readSource['count'] > 0) {
					// Before creating the documents, we check the job id is the one in the rule lock
					if ($this->getRuleLock() != $this->jobId) {
						throw new \Exception('The rule '.$this->ruleId.' is locked by the task '.$this->getRuleLock().'. Failed to generate the documents. ');
					}
                    $param['rule'] = $this->rule;
                    $param['ruleFields'] = $this->ruleFields;
                    $param['ruleRelationships'] = $this->ruleRelationships;
                    $param['ruleFilters'] = $this->ruleFilters;
					$param['ruleWorkflows'] = $this->ruleWorkflows;
                    // Set the param of the rule one time for all
                    $this->documentManager->setRuleId($this->ruleId);
                    $this->documentManager->setRuleParam();
                    if ($this->dataSource['values']) {
                        // Set all config parameters
                        $this->setConfigParam();
                        // If migration mode, we select all documents to improve performance. For example, we won't execute queries is method document->checkRecordExist
                        if (!empty($this->configParams['migration_mode'])) {
                            $param['ruleDocuments'][$this->ruleId] = $this->getRuleDocuments($this->ruleId);
                        }
                        // Boucle sur chaque document
                        foreach ($this->dataSource['values'] as $row) {
                            $param['data'] = $row;
                            $param['jobId'] = $this->jobId;
                            $param['api'] = $this->api;
                            // Set the param values and clear all document attributes but not rule attributes
                            if($this->documentManager->setParam($param, true)) {
								$createDocument = $this->documentManager->createDocument();
							}
                            if (!$createDocument) {
                                $readSource['error'] .= $this->documentManager->getMessage();
                            }
                        }
                    }
                    // Mise à jour de la date de référence si des documents ont été créés
                    $this->updateReferenceDate();
                // In case there is no result but the reference date has changed (e.g. stat reding from Brevo)
				} elseif (
						$readSource['count'] == 0
					AND !empty($readSource['date_ref'])
					AND $readSource['date_ref'] != $this->ruleParams['datereference']
				) {
					$this->updateReferenceDate();
				}
                // If params has been added in the output of the rule we saved it
                $this->updateParams();
				
				// No error management because we don't want any rollback because of the lock. 
				// If the lock isn't removed, the next task will generate an error
				$this->unsetRuleLock();

                // Rollback if the job has been manually stopped
                if ('Start' != $this->getJobStatus()) {
                    throw new \Exception('The task has been stopped manually. No document generated. ');
                }
                $this->connection->commit();
            } catch (\Exception $e) {
                $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
                $this->logger->error('Failed to create documents : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
                $readSource['error'] = 'Failed to create documents : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
				// The process is finished even if there is an exception so we unlock the rule
				$this->unsetRuleLock();
            }
        }
        // On affiche pas d'erreur si la lecture est désactivée
        elseif (empty($this->ruleParams['disableRead'])) {
            $readSource['error'] = 'The rule '.$this->rule['name_slug'].(1 == $this->rule['deleted'] ? ' is deleted.' : ' is disabled.');
        }

        return $readSource;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getJobStatus()
    {
        $sqlJobDetail = 'SELECT * FROM job WHERE id = :jobId';
        $stmt = $this->connection->prepare($sqlJobDetail);
        $stmt->bindValue(':jobId', $this->jobId);
        $result = $stmt->executeQuery();
        $job = $result->fetchAssociative(); // 1 row
        if (!empty($job['status'])) {
            return $job['status'];
        }

        return false;
    }

    /**
     * Permet de mettre à jour la date de référence pour ne pas récupérer une nouvelle fois les données qui viennent d'être écrites dans la cible.
     *
     * @throws Exception
     */
    protected function updateReferenceDate()
    {
        $param = $this->entityManager->getRepository(RuleParam::class)
            ->findOneBy([
                    'rule' => $this->ruleId,
                    'name' => 'datereference',
                ]
            );
        // Every rules should have the param datereference
        if (empty($param)) {
            throw new \Exception('No reference date for the rule '.$this->ruleId.'.');
        } else {
            // Save param modification in the audit table
            if ($param->getValue() != $this->dataSource['date_ref']) {
                $paramAudit = new RuleParamAudit();
                $paramAudit->setRuleParamId($param->getId());
                $paramAudit->setDateModified(new \DateTime());
                $paramAudit->setBefore($param->getValue());
                $paramAudit->setAfter($this->dataSource['date_ref']);
                $paramAudit->setJob($this->jobId);
                $this->entityManager->persist($paramAudit);
            }
            // Update reference
            $param->setValue($this->dataSource['date_ref']);
            $this->entityManager->persist($param);
            $this->entityManager->flush();
        }
    }

    // Update/create rule parameter
    protected function updateParams()
    {
        if (!empty($this->dataSource['ruleParams'])) {
            foreach ($this->dataSource['ruleParams'] as $ruleParam) {
                // Search to check if the param already exists
                $paramEntity = $this->entityManager->getRepository(RuleParam::class)
                       ->findOneBy([
                                    'rule' => $this->ruleId,
                                    'name' => $ruleParam['name'],
                                ]
                        );
                // Update or create the new param
                if (!empty($paramEntity)) {
                    if ($ruleParam['value'] != $paramEntity->getValue()) {
                        $paramAudit = new RuleParamAudit();
                        $paramAudit->setRuleParamId($paramEntity->getId());
                        $paramAudit->setDateModified(new \DateTime());
                        $paramAudit->setBefore($paramEntity->getValue());
                        $paramAudit->setAfter($ruleParam['value']);
                        $paramAudit->setJob($this->jobId);
                        $this->entityManager->persist($paramAudit);
                    }
                    $paramEntity->setValue($ruleParam['value']);
                } else {
                    $rule = $this->entityManager->getRepository(Rule::class)
                                            ->findOneBy([
                                                'id' => $this->ruleId,
                                            ]
                                            );

                    $paramEntity = new RuleParam();
                    $paramEntity->setRule($rule);
                    $paramEntity->setName($ruleParam['name']);
                    $paramEntity->setValue($ruleParam['value']);
                }
                $this->entityManager->persist($paramEntity);
                $this->entityManager->flush();
            }
        }
    }

    protected function readSource()
    {
        $read['module'] = $this->rule['module_source'];
        $read['rule'] = $this->rule;
        $read['date_ref'] = $this->ruleParams['datereference'];
        $read['ruleParams'] = $this->ruleParams;
        $read['fields'] = $this->sourceFields;
        $read['offset'] = $this->offset;
        $read['limit'] = $this->limit;
        $read['jobId'] = $this->jobId;
        $read['manual'] = $this->manual;
        $read['call_type'] = 'read';
        // Ajout des champs source des relations de la règle
        if (!empty($this->ruleRelationships)) {
            foreach ($this->ruleRelationships as $ruleRelationship) {
                $read['fields'][] = $ruleRelationship['field_name_source'];
            }
        }

        // si champs vide
        if (!empty($read['fields'])) {
            $connect = $this->connexionSolution('source');
            if (true === $connect) {
                $this->dataSource = $this->solutionSource->readData($read);
                // If Myddleware has reached the limit, we validate data to make sure no doto won't be lost
                if (
                        !empty($this->dataSource['count'])
                    && $this->dataSource['count'] == $this->limit
                ) {
                    // Check and clean data source
                    $validateReadDataSource = $this->validateReadDataSource();
                    if (!empty($validateReadDataSource['error'])) {
                        // If the run isn't validated, we set back the previous reference date
                        // so Myddleware won't continue to read next data during the next run
                        $this->dataSource['date_ref'] = $this->ruleParams['datereference'];

                        return $validateReadDataSource;
                    }
                }
                // Logout (source solution)
                if (!empty($this->solutionSource)) {
                    $loginResult = $this->solutionSource->logout();
                    if (!$loginResult) {
                        $this->dataSource['error'] .= 'Failed to logout from the source solution';
                    }
                }

                return $this->dataSource;
            } elseif (!empty($connect['error'])) {
                return $connect;
            } else {
                return ['error' => 'Failed to connect to the source with rule : '.$this->ruleId.' .'];
            }
        }

        return ['error' => 'No field to read in source system. '];
    }

    // Check every record haven't the same reference date
    // Make sure the next record hasn't the same date modified, so we delete at least the last one
    // This function run only when the limit call has been reached
    protected function validateReadDataSource()
    {
        if (!empty($this->dataSource['values'])) {
            $dataSourceValues = $this->dataSource['values'];

            // Order data in the date_modified order
            $modified = array_column($dataSourceValues, 'date_modified');
            array_multisort($modified, SORT_DESC, $dataSourceValues);
            foreach ($dataSourceValues as $value) {
                // Check if the previous record has the same date_modified than the current record
                // Check only if offset isn't managed into the source application connector
                if (
                        empty($this->dataSource['ruleParams']['offset'])
                    and (
                            empty($previousValue)   // first call
                        or (
                                !empty($previousValue['date_modified'])
                            and $previousValue['date_modified'] == $value['date_modified']
                        )
                    )
                ) {
                    // Remove the current item, it will be read in the next call
                    unset($this->dataSource['values'][$value['id']]); // id equal the key in the dataSource table
                    --$this->dataSource['count'];
                    $previousValue = $value;
                    continue;
                }
                // Keep the reference date of the last record we have read
                $this->dataSource['date_ref'] = $value['date_modified'];
                break;
            }

            // If no result => it means that all value have the same reference date
            // If reference date hasn't changed => it means that we reached the read limit and there are only 2 reference dates in the records,
            //									=> we removed the most recent ones (to be sure to miss no records) and only one reference date remain
            //									=> If we don't stop the process, Myddleware will always read the same records
            // Check only if offset isn't managed into the source application connector
            if (
                    empty($this->dataSource['ruleParams']['offset'])
                and (
                        empty($this->dataSource['values'])
                    or $this->ruleParams['datereference'] == $this->dataSource['date_ref']
                )
            ) {

                // On top of returning the error message, we should also send an alert to the user with that message
                // create an array that is composed of the job id, the rule id, and the date reference
                $JobSettings = [
                    'job_id' => $this->jobId,
                    'rule_id' => $this->ruleId,
                    'reference_date' => $this->ruleParams['datereference'],
                ];

                // send the alert to the user
                $this->notificationManager->sendAlertSameDocReference($JobSettings);
				
				// Disable the rule to avoid to send the alert every time the cronjob runs
				$rule = $this->entityManager->getRepository(Rule::class)->findOneBy(['id' => $this->ruleId, 'deleted' => false]);
				$rule->setActive(false);
                $this->entityManager->persist($rule);
                $this->entityManager->flush();

                return ['error' => 'All records read have the same reference date in rule '.$this->rule['name'].'. Myddleware cannot guarantee that all data will be read. Job interrupted. Please increase the number of data read by changing the limit attribute in job and rule classes. The rule has been disabled. '];
            }

            return true;
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function filterDocuments($documents = null): array
    {
        // include_once 'document.php';
        $response = [];

        // Sélection de tous les docuements de la règle au statut 'New' si aucun document n'est en paramètre
        if (empty($documents)) {
            $documents = $this->selectDocuments('New');
        } else {
			// Lock the documents in input parameter
			foreach ($documents as $document) {
				$this->setDocumentLock($document['id'], true);
			}
		}

        // Pour tous les docuements sélectionnés on vérifie les prédécesseurs
        if (!empty($documents)) {
            try {
				if ('Start' != $this->getJobStatus()) {
					throw new \Exception('The task has been stopped manually. No document generated. ');
				}
                $this->setRuleFilter();
                foreach ($documents as $document) {
                    $param['id_doc_myddleware'] = $document['id'];
                    $param['jobId'] = $this->jobId;
                    $param['api'] = $this->api;
                    $param['ruleWorkflows'] = $this->ruleWorkflows;
                    // Set the param values and clear all document attributes
                    if($this->documentManager->setParam($param, true)) {
                        // Check the document is in the right status
						if (in_array($this->documentManager->getStatus(), array('New', 'Filter_KO'))) {
						    $response[$document['id']] = $this->documentManager->filterDocument($this->ruleFilters);
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to filter documents : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
                $readSource['error'] = 'Failed to filter documents : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            }
        }

        return $response;
    }

    /**
     * Permet de contrôler si un document de la même règle pour le même enregistrement n'est pas close
     * Si un document n'est pas clos alors le statut du docuement est mis à "pending".
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function checkPredecessorDocuments($documents = null): array
    {
        // include_once 'document.php';
        $response = [];

        // Sélection de tous les docuements de la règle au statut 'Filter_OK' si aucun document n'est en paramètre
        if (empty($documents)) {
            $documents = $this->selectDocuments('Filter_OK');
        } else {
			// Lock the documents in input parameter
			foreach ($documents as $document) {
				$this->setDocumentLock($document['id'], true);
			}
		}
        // Pour tous les docuements sélectionnés on vérifie les prédécesseurs
        if (!empty($documents)) {
            try {
				if ('Start' != $this->getJobStatus()) {
					throw new \Exception('The task has been stopped manually. No document generated. ');
				}
                foreach ($documents as $document) {
                    $param['id_doc_myddleware'] = $document['id'];
                    $param['jobId'] = $this->jobId;
                    $param['api'] = $this->api;
                    $param['ruleRelationships'] = $this->ruleRelationships;
					$param['ruleWorkflows'] = $this->ruleWorkflows;
                    // Set the param values and clear all document attributes
                    if($this->documentManager->setParam($param, true)) {
                        // Check the document is in the right status
						if (in_array($this->documentManager->getStatus(), array('Filter_OK', 'Predecessor_KO'))) {
						    $response[$document['id']] = $this->documentManager->checkPredecessorDocument();
                        }
					}
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to check predecessors : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
                $readSource['error'] = 'Failed to check predecessors : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            }
        }

        return $response;
    }

    /**
     * Permet de contrôler si un document de la même règle pour le même enregistrement n'est pas close
     * Si un document n'est pas clos alors le statut du docuement est mis à "pending".
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function checkParentDocuments($documents = null): array
    {
        // include_once 'document.php';
        // Permet de charger dans la classe toutes les relations de la règle
        $response = [];

        // Sélection de tous les docuements de la règle au statut 'New' si aucun document n'est en paramètre
        if (empty($documents)) {
            $documents = $this->selectDocuments('Predecessor_OK');
        } else {
			// Lock the documents in input parameter
			foreach ($documents as $document) {
				$this->setDocumentLock($document['id'], true);
			}
		}
        if (!empty($documents)) {
            $param['jobId'] = $this->jobId;
            $param['ruleRelationships'] = $this->ruleRelationships;
			$param['ruleWorkflows'] = $this->ruleWorkflows;
            // Set all config parameters
            $this->setConfigParam();
            // If migration mode, we select all documents to improve performance. For example, we won't execute queries is method document->getTargetId
            if (!empty($this->configParams['migration_mode'])) {
                if (!empty($this->ruleRelationships)) {
                    // Get all documents of every rules linked
                    foreach ($this->ruleRelationships as $ruleRelationship) {
                        // Get documents only if we don't have them yet (we could have several relationship to the same rule)
                        if (empty($param['ruleDocuments'][$ruleRelationship['field_id']])) {
                            $param['ruleDocuments'][$ruleRelationship['field_id']] = $this->getRuleDocuments($ruleRelationship['field_id'], true, true);
                        }
                    }
                }
            }
            try {
				if ('Start' != $this->getJobStatus()) {
					throw new \Exception('The task has been stopped manually. No document generated. ');
				}
                // Pour tous les docuements sélectionnés on vérifie les parents
                foreach ($documents as $document) {
                    $param['id_doc_myddleware'] = $document['id'];
                    $param['jobId'] = $this->jobId;
                    $param['api'] = $this->api;
                    $param['ruleRelationships'] = $this->ruleRelationships;
					$param['ruleWorkflows'] = $this->ruleWorkflows;
                    // Set the param values and clear all document attributes
                    if($this->documentManager->setParam($param, true)) {
                        // Check the document is in the right status
						if (in_array($this->documentManager->getStatus(), array('Predecessor_OK', 'Relate_KO'))) {
						    $response[$document['id']] = $this->documentManager->checkParentDocument();
                        }
					}
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to check parents : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
                $readSource['error'] = 'Failed to check parents : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            }
        }

        return $response;
    }

    /**
     * Permet de contrôler si un docuement de la même règle pour le même enregistrement n'est pas close
     * Si un document n'est pas clos alors le statut du docuement est mis à "pending".
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function transformDocuments($documents = null): array
    {
        // Permet de charger dans la classe toutes les relations de la règle
        $response = [];
        // Sélection de tous les docuements de la règle au statut 'New' si aucun document n'est en paramètre
        if (empty($documents)) {
            $documents = $this->selectDocuments('Relate_OK');
        } else {
			// Lock the documents in input parameter
			foreach ($documents as $document) {
				$this->setDocumentLock($document['id'], true);
			}
		}
        if (!empty($documents)) {
            $param['ruleFields'] = $this->ruleFields;
            $param['ruleRelationships'] = $this->ruleRelationships;
			$param['ruleWorkflows'] = $this->ruleWorkflows;
			$param['variables'] = $this->variables;
            $param['jobId'] = $this->jobId;
            $param['api'] = $this->api;
            // Set all config parameters
            $this->setConfigParam();
            // If migration mode, we select all documents to improve performance. For example, we won't execute queries is method document->getTargetId
            if (!empty($this->configParams['migration_mode'])) {
                if (!empty($this->ruleRelationships)) {
                    // Get all documents of every rules linked
                    foreach ($this->ruleRelationships as $ruleRelationship) {
                        // Get documents only if we don't have them yet (we could have several relationship to the same rule)
                        if (empty($param['ruleDocuments'][$ruleRelationship['field_id']])) {
                            $param['ruleDocuments'][$ruleRelationship['field_id']] = $this->getRuleDocuments($ruleRelationship['field_id'], true, true);
                        }
                    }
                }
            }

            try {
				if ('Start' != $this->getJobStatus()) {
					throw new \Exception('The task has been stopped manually. No document generated. ');
				}
                // Transformation de tous les docuements sélectionnés
                foreach ($documents as $document) {
                    $param['id_doc_myddleware'] = $document['id'];
                    // Set the param values and clear all document attributes
                    if($this->documentManager->setParam($param, true)) {
                        // Check the document is in the right status
                        if (in_array($this->documentManager->getStatus(), array('Relate_OK', 'Error_transformed'))) {
						    $response[$document['id']] = $this->documentManager->transformDocument();
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to transform documents : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
                $response['error'] = 'Failed to transform documents : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            }
        }

        return $response;
    }

    /**
     * Permet de récupérer les données de la cible avant modification des données
     * 2 cas de figure :
     *  - Le document est un document de modification
     *  - Le document est un document de création mais la règle a un paramètre de vérification des données pour ne pas créer de doublon.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getTargetDataDocuments($documents = null): array
    {
        // include_once 'document.php';

        // Permet de charger dans la classe toutes les relations de la règle
        $response = [];

        // Sélection de tous les documents de la règle au statut 'New' si aucun document n'est en paramètre
        if (empty($documents)) {
            $documents = $this->selectDocuments('Transformed');
        } else {
			// Lock the documents in input parameter
			foreach ($documents as $document) {
				$this->setDocumentLock($document['id'], true);
			}
		}

        if (!empty($documents)) {
            // Connexion à la solution cible pour rechercher les données
            $this->connexionSolution('target');
            try {
				if ('Start' != $this->getJobStatus()) {
					throw new \Exception('The task has been stopped manually. No document generated. ');
				}
                // Récupération de toutes les données dans la cible pour chaque document
                foreach ($documents as $document) {
                    $param['id_doc_myddleware'] = $document['id'];
                    $param['solutionTarget'] = $this->solutionTarget;
                    $param['ruleFields'] = $this->ruleFields;
                    $param['ruleRelationships'] = $this->ruleRelationships;
					$param['ruleWorkflows'] = $this->ruleWorkflows;
                    $param['jobId'] = $this->jobId;
                    $param['api'] = $this->api;
                    // Set the param values and clear all document attributes
                    if($this->documentManager->setParam($param, true)) {
                        // Check the document is in the right status
						if (in_array($this->documentManager->getStatus(), array('Transformed', 'Error_checking'))) {
                            $response[$document['id']] = $this->documentManager->getTargetDataDocument();
                            $response['doc_status'] = $this->documentManager->getStatus();
                        }
					}
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to create documents : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
                $readSource['error'] = 'Failed to create documents : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            }
        }

        return $response;
    }

    public function sendDocuments(): array
    {
        // creation into the target application
        $sendTarget = $this->sendTarget('C');
        // Update into the target application
        if (empty($sendTarget['error'])) {
            $sendTarget = $this->sendTarget('U');
        }
        // Deletion from the target application
        if (empty($sendTarget['error'])) {
            $sendTarget = $this->sendTarget('D');
        }
        // Logout target solution
        if (!empty($this->solutionTarget)) {
            $loginResult['error'] = $this->solutionTarget->logout();
            if (!$loginResult) {
                $sendTarget['error'] .= 'Failed to logout from the target solution';
            }
        }
        return $sendTarget;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function actionDocument($id_document, $event, $param1 = null)
    {
        switch ($event) {
            case 'rerun':
                return $this->rerun($id_document);
            case 'cancel':
                return $this->cancel($id_document);
            case 'remove':
                return $this->changeDeleteFlag($id_document, true);
            case 'restore':
                return $this->changeDeleteFlag($id_document, false);
            case 'changeStatus':
                return $this->changeStatus($id_document, $param1);
			case 'unlock':
                return $this->unlockDocument($id_document);
            case 'rerunWorkflow':
                return $this->rerunWorkflowDocument($id_document);
            default:
                return 'Action '.$event.' unknown. Failed to run this action. ';
        }
    }

    public function actionRule($event, $jobName = null, $documentId = null)
    {
        switch ($event) {
            case 'ALL':
                return $this->runMyddlewareJob('ALL');
            case 'ERROR':
                return $this->runMyddlewareJob('ERROR');
            case 'runMyddlewareJob':
                return $this->runMyddlewareJob($this->ruleId, $jobName);
                break;
            case 'runRuleByDocId':
                return $this->runMyddlewareJob($this->ruleId, $jobName, $documentId);
            default:
                return 'Action '.$event.' unknown. Failed to run this action. ';
        }
    }

    // Permet de faire des contrôles dans Myddleware avant sauvegarde de la règle
    // Si le retour est false, alors la sauvegarde n'est pas effectuée et un message d'erreur est indiqué à l'utilisateur
    // data est de la forme :
    // [ruleName] => nom
    // [oldRule] => id de la règle précédente
    // [connector] => Array ( [source] => 3 [cible] => 30 )
    // [content] => Array (
    // [fields] => Array ( [name] => Array ( [Date] => Array ( [champs] => Array ( [0] => date_entered [1] => date_modified ) [formule] => Array ( [0] => {date_entered}.{date_modified} ) ) [account_Filter] => Array ( [champs] => Array ( [0] => name ) ) ) )
    // [params] => Array ( [mode] => 0 ) )
    // [relationships] => Array ( [0] => Array ( [target] => compte_Reference [rule] => 54ea64f1601fc [source] => Myddleware_element_id ) )
    // [module] => Array ( [source] => Array ( [solution] => sugarcrm [name] => Accounts ) [target] => Array ( [solution] => bittle [name] => oppt_multi7 ) )
    // La valeur de retour est de a forme : array('done'=>false, 'message'=>'message erreur');	ou array('done'=>true, 'message'=>'')
    public static function beforeSave($solutionManager, $data)
    {
        // Contrôle sur la solution source
        $solutionSource = $solutionManager->get($data['module']['source']['solution']);
        $check = $solutionSource->beforeRuleSave($data, 'source');
        // Si OK contôle sur la solution cible
        if ($check['done']) {
            $solutionTarget = $solutionManager->get($data['module']['target']['solution']);
            $check = $solutionTarget->beforeRuleSave($data, 'target');
        }

        return $check;
    }

    // Permet d'effectuer une action après la sauvegarde de la règle dans Myddleqare
    // Mêmes paramètres en entrée que pour la fonction beforeSave sauf que l'on a ajouté les entrées ruleId et date de référence au tableau
    public static function afterSave($solutionManager, $data, RequestStack $requestStack)
    {
        // Contrôle sur la solution source
        $solutionSource = $solutionManager->get($data['module']['source']['solution']);
        $messagesSource = $solutionSource->afterRuleSave($data, 'source');

        $solutionTarget = $solutionManager->get($data['module']['target']['solution']);
        $messagesTarget = $solutionTarget->afterRuleSave($data, 'target');

        $messages = array_merge($messagesSource, $messagesTarget);
        $data['testMessage'] = '';
        
        // Get the request from RequestStack
        $session = $requestStack->getSession();
        
        // Affichage des messages
        if (!empty($messages)) {
            foreach ($messages as $message) {
                if ('error' == $message['type']) {
                    $errorMessages[] = $message['message'];
                } else {
                    $successMessages[] = $message['message'];
                }
                $data['testMessage'] .= $message['type'].' : '.$message['message'].chr(10);
            }
            if (!empty($errorMessages)) {
                $session->getFlashBag()->set('error', $errorMessages);
            }
            if (!empty($successMessages)) {
                $session->getFlashBag()->set('success', $successMessages);
            }
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getRuleDocuments($ruleId, $sourceId = true, $targetId = false)
    {
        $sql = 'SELECT id, source_id, target_id, status, global_status FROM document WHERE rule_id = :ruleId AND deleted = 0';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':ruleId', $ruleId);
        $result = $stmt->executeQuery();
        $documents = $result->fetchAllAssociative();
        if (!empty($documents)) {
            foreach ($documents as $document) {
                $documentResult['sourceId'][$document['source_id']][] = $document;
                if (
                        $targetId
                    and !empty($document['source_id'])
                ) {
                    $documentResult['targetId'][$document['target_id']][] = $document;
                }
            }

            return $documentResult;
        }
    }

    // Permet de récupérer les règles potentiellement biderectionnelle.
    // Cette fonction renvoie les règles qui utilisent les même connecteurs et modules que la règle en cours mais en sens inverse (source et target inversées)
    // On est sur une méthode statique c'est pour cela que l'on récupère la connexion e paramètre et non dans les attributs de la règle
    public static function getBidirectionalRules($connection, $params, $solutionSource, $solutionTarget): ?array
    {
        try {
			// Call solutions in cas target ans source modules hasn't the same name (ex Moodle manual_enrol_users and get_enrolments_by_date)
			$params = $solutionSource->beforeGetBidirectionalRules($params, 'source');
			$params = $solutionTarget->beforeGetBidirectionalRules($params, 'target');

            // Récupération des règles opposées à la règle en cours de création
            $queryBidirectionalRules = 'SELECT 
											id, 
											name
										FROM rule 
										WHERE 
												conn_id_source = :conn_id_target
											AND conn_id_target = :conn_id_source
											AND module_source = :module_target
											AND module_target = :module_source
											AND deleted = 0
										';
            $stmt = $connection->prepare($queryBidirectionalRules);
            $stmt->bindValue(':conn_id_source', $params['connector']['source']);
            $stmt->bindValue(':conn_id_target', $params['connector']['cible']);
            $stmt->bindValue(':module_source', $params['module']['source']);
            $stmt->bindValue(':module_target', $params['module']['cible']);
            $result = $stmt->executeQuery();
            $bidirectionalRules = $result->fetchAllAssociative();

            // Construction du tableau de sortie
            if (!empty($bidirectionalRules)) {
                $option[''] = '';
                foreach ($bidirectionalRules as $rule) {
                    $option[$rule['id']] = $rule['name'];
                }
                if (!empty($option)) {
                    return [
                        [
                            'id' => 'bidirectional',
                            'name' => 'bidirectional',
                            'required' => false,
                            'type' => 'option',
                            'label' => 'create_rule.step3.params.sync',
                            'option' => $option,
                        ],
                    ];
                }
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function cancel($id_document)
    {
        $param['id_doc_myddleware'] = $id_document;
        $param['jobId'] = $this->jobId;
        $param['api'] = $this->api;
        // Set the param values and clear all document attributes
        $this->documentManager->setParam($param, true);
		
		// TO BE TESTED and REPLACE the 4 lines above
		// $this->documentManager->setId($id_document);
        $this->documentManager->documentCancel();

        // Get the request from RequestStack
        if (!empty($this->requestStack->getCurrentRequest())) {
            // Get the request from RequestStack
            $session = $this->requestStack->getSession();
        }
        $message = $this->documentManager->getMessage();

        // Si on a pas de jobId cela signifie que l'opération n'est pas massive mais sur un seul document
        // On affiche alors le message directement dans Myddleware
        if (empty($this->jobId)) {
            if (empty($message)) {
                $session->getFlashBag()->set('success', ['Data transfer has been successfully cancelled.']);
            } else {
                $session->getFlashBag()->set('error', [$this->documentManager->getMessage()]);
            }
        }
    }
	
	/**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function unlockDocument($id_document)
    {
        $param['id_doc_myddleware'] = $id_document;
        $param['jobId'] = $this->jobId;
        $param['api'] = $this->api;
        // Set the param values and clear all document attributes
		// We don't lock the document because the action is : unset the current lock on the document
        $this->documentManager->setNoLock(true);
        $this->documentManager->setParam($param, true);
        $this->documentManager->unsetLock(true);
        
        // Get the request from RequestStack
        if (!empty($this->requestStack->getCurrentRequest())) {
            // Get the request from RequestStack
            $session = $this->requestStack->getSession();
        }

        $message = $this->documentManager->getMessage();

        // Si on a pas de jobId cela signifie que l'opération n'est pas massive mais sur un seul document
        // On affiche alors le message directement dans Myddleware
        if (empty($this->jobId)) {
            if (
					empty($message)
				OR $this->documentManager->getTypeError() == 'S'
			) {
                $session->getFlashBag()->set('success', ['Data transfer has been successfully unlocked.']);
            } else {
                $session->getFlashBag()->set('error', [$this->documentManager->getMessage()]);
            }
        }
    }

	/**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function rerunWorkflowDocument($id_document)
    {
		try {
			$param['id_doc_myddleware'] = $id_document;
			$param['jobId'] = $this->jobId;
			$param['api'] = $this->api;
			$param['ruleWorkflows'] = $this->ruleWorkflows;
			// Set the param values and clear all document attributes
			$this->documentManager->setParam($param, true);
			// Set the message that could be used in workflow
			if (!empty($param['error'])) {
				$this->documentManager->setMessage($param['error']);
			}
			$this->documentManager->runWorkflow(true);
			$this->documentManager->updateWorkflowError(0);
		} catch (\Exception $e) {
			$this->logger->error('Failed to rerun the workflow : '.$e->getMessage());
			$this->documentManager->generateDocLog('E','Failed to rerun the workflow : '.$e->getMessage());
		} 
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function changeDeleteFlag($id_document, $deleteFlag)
    {
        $param['id_doc_myddleware'] = $id_document;
        $param['jobId'] = $this->jobId;
        $param['api'] = $this->api;
        // Set the param values and clear all document attributes
        $this->documentManager->setParam($param, true);
        $this->documentManager->changeDeleteFlag($deleteFlag);
        
        if (!empty($this->requestStack->getCurrentRequest())) {
            // Get the request from RequestStack
            $session = $this->requestStack->getSession();
        }
        $message = $this->documentManager->getMessage();

        // Si on a pas de jobId cela signifie que l'opération n'est pas massive mais sur un seul document
        // On affiche alors le message directement dans Myddleware
        if (empty($this->jobId)) {
            if (empty($message)) {
                $session->getFlashBag()->set('success', ['Data transfer has been successfully removed.']);
            } else {
                $session->getFlashBag()->set('error', [$this->documentManager->getMessage()]);
            }
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function changeStatus($id_document, $toStatus, $message = null, $docIdRefError = null, $typeError = null)
    {
        $param['id_doc_myddleware'] = $id_document;
        $param['jobId'] = $this->jobId;
        $param['api'] = $this->api;
        // Set the param values and clear all document attributes
        $this->documentManager->setParam($param, true);
        if (!empty($message)) {
            $this->documentManager->setMessage($message);
        }
        if (!empty($typeError)) {
            $this->documentManager->setTypeError($typeError);
        }
        if (!empty($docIdRefError)) {
            $this->documentManager->setDocIdRefError($docIdRefError);
        }
        $this->documentManager->updateStatus($toStatus);
    }

    protected function runMyddlewareJob($ruleId, $event = null, $documentId = null)
    {
        try {
            // Check if exec function is disabled
            if (!function_exists('exec') || in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
                throw new \Exception('The PHP exec() function is disabled. Please enable it in php.ini to run background jobs.');
            }

            if (!empty($this->requestStack->getCurrentRequest())) {
            // Get the request from RequestStack
            $session = $this->requestStack->getSession();
        }
            
            // create temp file
            $guid = uniqid();

            // Get the php executable
            $php = $this->tools->getPhpVersion();

            $fileTmp = $this->parameterBagInterface->get('kernel.cache_dir').'/myddleware/job/'.$guid.'.txt';
            $fs = new Filesystem();
            try {
                $fs->mkdir(dirname($fileTmp));
            } catch (IOException $e) {
                throw new \Exception($this->tools->getTranslation(['messages', 'rule', 'failed_create_directory']));
            }
            if ($documentId !== null) {
                exec($php.' '.__DIR__.'/../../bin/console myddleware:readrecord '.$ruleId.' id '.$documentId.' --env='.$this->env.' > '.$fileTmp.' &', $output);
            }
            //if user clicked on cancel all transfers of a rule
            elseif ('cancelDocumentJob' === $event) {
                exec($php.' '.__DIR__.'/../../bin/console myddleware:massaction cancel rule '.$ruleId.' --env='.$this->env.' > '.$fileTmp.' &', $output);
            //if user clicked on delete all transfers from a rule
            } elseif ('deleteDocumentJob' === $event) {
                exec($php.' '.__DIR__.'/../../bin/console myddleware:massaction remove rule '.$ruleId.' Y --env='.$this->env.' > '.$fileTmp.' &', $output);
            } elseif ('ALL' == $ruleId) {
                // We don't set the parameter force to 1 when we synchronize all rules
                exec($php.' '.__DIR__.'/../../bin/console myddleware:synchro '.$ruleId.' --env='.$this->env.' > '.$fileTmp.' &', $output);
            } else {
                exec($php.' '.__DIR__.'/../../bin/console myddleware:synchro '.$ruleId.' --env='.$this->env.' > '.$fileTmp.' &', $output);
            }
            $cpt = 0;
            // Boucle tant que le fichier n'existe pas
            while (!file_exists($fileTmp)) {
                if ($cpt >= 29) {
                    throw new \Exception($this->tools->getTranslation(['messages', 'rule', 'failed_running_job']));
                }
                sleep(1);
                ++$cpt;
            }

            // Boucle tant que l id du job n'est pas dans le fichier (écris en premier)
            $file = fopen($fileTmp, 'r');
            $firstLine = fgets($file);
            fclose($file);
            while (empty($firstLine)) {
                if ($cpt >= 29) {
                    throw new \Exception($this->tools->getTranslation(['messages', 'rule', 'failed_get_task_id']));
                }
                sleep(1);
                $file = fopen($fileTmp, 'r');
                $firstLine = fgets($file);
                fclose($file);
                ++$cpt;
            }

            // transform all information of the first line in an arry
            $result = explode(';', $firstLine);
            // if result 1 contains the substring "Failed to create the task because another task is already running"
            // then we generate a message to inform the user that another task is running and that he can stop it manually
            // this was originally not handled by the else at the bottom because there was a 1 in the result[0]
            if ($result[0] == '1' && strpos($result[1], 'Failed to create the task because another task is already running') !== false) {
                $session->set('error', [$result[1].(!empty($result[2]) ? '<a href="'.$this->router->generate('task_view', ['id' => trim($result[2])]).'" target="blank_">'.$this->tools->getTranslation(['messages', 'rule', 'open_running_task']).'</a>' : '')]);
            }else if ($result[0]) {
                $session->set('info', ['<a href="'.$this->router->generate('task_view', ['id' => trim($result[1])]).'" target="blank_">'.$this->tools->getTranslation(['messages', 'rule', 'open_running_task']).'</a>.']);
            } else {
                $session->set('error', [$result[1].(!empty($result[2]) ? '<a href="'.$this->router->generate('task_view', ['id' => trim($result[2])]).'" target="blank_">'.$this->tools->getTranslation(['messages', 'rule', 'open_running_task']).'</a>' : '')]);
            }

            return $result[0];
        } catch (\Exception $e) {
            // Get the request from RequestStack
            if (!empty($this->requestStack->getCurrentRequest())) {
            // Get the request from RequestStack
            $session = $this->requestStack->getSession();
            
            $session->getFlashBag()->set('error', [$e->getMessage()]);
        }
            $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());

            return false;
        }
    }

    /**
     * Permet de relancer un document quelque soit son statut.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function rerun($id_document): array
    {
		try {
			
            // Get the request from RequestStack
            $session = new Session();
    

			$msg_error = [];
			$msg_success = [];
			$msg_info = [];
			// Récupération du statut du document
			$param['id_doc_myddleware'] = $id_document;
			$param['jobId'] = $this->jobId;
			$param['api'] = $this->api;
			// Set the param values and clear all document attributes
			$this->documentManager->setParam($param, true);
			$status = $this->documentManager->getStatus();
			// Si la règle n'est pas chargée alors on l'initialise.
			if (empty($this->ruleId)) {
				$this->ruleId = $this->documentManager->getRuleId();
				$this->setRule($this->ruleId);
				$this->setRuleRelationships();
				$this->setRuleFilter();
				$this->setRuleParam();
				$this->setRuleField();
			}

			$response[$id_document] = false;
			// On lance des méthodes différentes en fonction du statut en cours du document et en fonction de la réussite ou non de la fonction précédente
			if (in_array($status, ['New', 'Filter_KO'])) {
				$response = $this->filterDocuments([['id' => $id_document]]);
				if (true === $response[$id_document]) {
					$msg_success[] = 'Transfer id '.$id_document.' : Status change => Filter_OK';
				} elseif (-1 == $response[$id_document]) {
					$msg_info[] = 'Transfer id '.$id_document.' : Status change => Filter';
				} else {
					$msg_error[] = 'Transfer id '.$id_document.' : Error, status transfer => Filter_KO';
				}
				// Update status if an action has been executed
				$status = $this->documentManager->getStatus();
			}
			if (in_array($status, ['Filter_OK', 'Predecessor_KO'])) {
				$response = $this->checkPredecessorDocuments([['id' => $id_document]]);
				if (true === $response[$id_document]) {
					$msg_success[] = 'Transfer id '.$id_document.' : Status change => Predecessor_OK';
				} else {
					$msg_error[] = 'Transfer id '.$id_document.' : Error, status transfer => Predecessor_KO';
				}
				// Update status if an action has been executed
				$status = $this->documentManager->getStatus();
			}
			if (in_array($status, ['Predecessor_OK', 'Relate_KO'])) {
				$response = $this->checkParentDocuments([['id' => $id_document]]);
				if (true === $response[$id_document]) {
					$msg_success[] = 'Transfer id '.$id_document.' : Status change => Relate_OK';
				} else {
					$msg_error[] = 'Transfer id '.$id_document.' : Error, status transfer => Relate_KO';
				}
				// Update status if an action has been executed
				$status = $this->documentManager->getStatus();
			}
			if (in_array($status, ['Relate_OK', 'Error_transformed'])) {
				$response = $this->transformDocuments([['id' => $id_document]]);
				if (true === $response[$id_document]) {
					$msg_success[] = 'Transfer id '.$id_document.' : Status change : Transformed';
				} else {
					$msg_error[] = 'Transfer id '.$id_document.' : Error, status transfer : Error_transformed';
				}
				// Update status if an action has been executed
				$status = $this->documentManager->getStatus();
			}
			if (in_array($status, ['Transformed', 'Error_checking', 'Not_found'])) {
				$response = $this->getTargetDataDocuments([['id' => $id_document]]);
				if (true === $response[$id_document]) {
					if ('S' == $this->rule['mode']) {
						$msg_success[] = 'Transfer id '.$id_document.' : Status change : '.$response['doc_status'];
					} else {
						$msg_success[] = 'Transfer id '.$id_document.' : Status change : '.$response['doc_status'];
					}
				} else {
					$msg_error[] = 'Transfer id '.$id_document.' : Error, status transfer : '.$response['doc_status'];
				}
				// Update status if an action has been executed
				$status = $this->documentManager->getStatus();
			}
			// Si la règle est en mode recherche alors on n'envoie pas de données
			// Si on a un statut compatible ou si le doc vient de passer dans l'étape précédente et qu'il n'est pas no_send alors on envoie les données
			if (
					'S' != $this->rule['mode']
				&& (
						in_array($status, ['Ready_to_send', 'Error_sending'])
					|| (
							true === $response[$id_document]
						&& (
								empty($response['doc_status'])
							|| (
									!empty($response['doc_status'])
								&& 'No_send' != $response['doc_status']
							)
						)
					)
				)
			) {
				$response = $this->sendTarget('', $id_document);
				if (
						!empty($response[$id_document]['id'])
					&& empty($response[$id_document]['error'])
					&& empty($response['error']) // Error can be on the document or can be a general error too
				) {
					$msg_success[] = 'Transfer id '.$id_document.' : Status change : Send';
				} else {
					$msg_error[] = 'Transfer id '.$id_document.' : Error, status transfer : Error_sending. '.(!empty($response[$id_document]['error']) ? $response[$id_document]['error'] : $response['error'] );
				}
			}
			// If the job is manual, we display error in the UI
			if ($this->manual) {
				if (!empty($msg_error)) {
					$session->getFlashBag()->set('error', $msg_error);
				}
				if (!empty($msg_success)) {
					$session->getFlashBag()->set('success', $msg_success);
				}
				if (!empty($msg_info)) {
					$session->getFlashBag()->set('info', $msg_info);
				}
			}
		} catch (Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            $msg_error[] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }
        return $msg_error;
    }


    protected function clearSendData($sendData)
    {
        if (!empty($sendData)) {
            foreach ($sendData as $key => $value) {
                if (isset($value['source_date_modified'])) {
                    unset($sendData[$key]['source_date_modified']);
                }
                if (isset($value['id_doc_myddleware'])) {
                    unset($sendData[$key]['id_doc_myddleware']);
                }
                if (isset($value['Myddleware_element_id'])) {
                    unset($sendData[$key]['Myddleware_element_id']);
                }     
            }
            return $sendData;
        }
    }

    protected function beforeDelete($sendData)
    {
        return $sendData;
    }

    // Check if the rule is a child rule
    public function isChild(): bool
    {
        try {
            $queryChild = '	SELECT rule.id 
									FROM rulerelationship 
										INNER JOIN rule
											ON rule.id  = rulerelationship.rule_id 
									WHERE 
											rulerelationship.field_id = :ruleId
										AND rulerelationship.parent = 1
										AND rule.deleted = 0
								';
            $stmt = $this->connection->prepare($queryChild);
            $stmt->bindValue(':ruleId', $this->ruleId);
            $result = $stmt->executeQuery();
            $rules = $result->fetchAllAssociative();
            if (!empty($rules)) {
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }

        return false;
    }

    protected function sendTarget($type, $documentId = null): array
    {
        try {	
            // Permet de charger dans la classe toutes les relations de la règle
            $response = [];
            $response['error'] = '';

            // Le type peut-être vide das le cas d'un relancement de flux après une erreur
            if (empty($type)) {
                $documentData = $this->getDocumentHeader($documentId);
                if (!empty($documentData['type'])) {
                    $type = $documentData['type'];
                }
            }
            
            // Récupération du contenu de la table target pour tous les documents à envoyer à la cible
            $send['data'] = $this->getSendDocuments($type, $documentId);
            $send['module'] = $this->rule['module_target'];
            $send['ruleId'] = $this->rule['id'];
            $send['rule'] = $this->rule;
            $send['ruleFields'] = $this->ruleFields;
            $send['ruleParams'] = $this->ruleParams;
            $send['ruleRelationships'] = $this->ruleRelationships;
			$send['ruleWorkflows'] = $this->ruleWorkflows;
            $send['jobId'] = $this->jobId;
            // Si des données sont prêtes à être créées
            if (!empty($send['data'])) {
                // If the rule is a child rule, no document is sent. They will be sent with the parent rule.
                if ($this->isChild()) {
                    foreach ($send['data'] as $key => $data) {
                        // True is send to avoid an error in rerun method. We should put the target_id but the document will be send with the parent rule.
                        $response[$key] = ['id' => true];
                    }

                    return $response;
                }

                // Connexion à la cible
                $connect = $this->connexionSolution('target');
                if (true === $connect) {
					// Call source to add data into $send array if a call has to be done
					$send = $this->checkSourceBeforeSend($send);
                    // Création des données dans la cible
                    if ('C' == $type) {
                        // Permet de vérifier que l'on ne va pas créer un doublon dans la cible
                        $send['data'] = $this->checkDuplicate($send['data']);
                        $send['data'] = $this->clearSendData($send['data']);
                        $response = $this->solutionTarget->createData($send);
                    }
                    // Modification des données dans la cible
                    elseif ('U' == $type) {
                        $send['data'] = $this->clearSendData($send['data']);

						// Allows to get the history fields, necessary for updating the SAP for instance
                        foreach ($send['data'] as $docId => $value) {
                            $send['dataHistory'][$docId] = $this->getDocumentData($docId, 'H');
                        }
                        $response = $this->solutionTarget->updateData($send);
                    }
                    // Delete data from target application
                    elseif ('D' == $type) {
                        $send = $this->checkBeforeDelete($send);
                        if (empty($send['error'])) {
                            $send['data'] = $this->beforeDelete($send['data']);
                            $response = $this->solutionTarget->deleteData($send);
                        } else {
                            $response['error'] = $send['error'];
                        }
                    } else {
                        $response[$documentId] = false;
                        $response['error'] = 'Type transfer '.$type.' unknown. ';
                    }
                } else {
                    $response[$documentId] = false;					
					// If we couldn't connect the target application, we set the status error_sending to all documents not sent
					foreach ($send['data'] as $idDoc => $data) {
						$this->changeStatus($idDoc, 'Error_sending', 'Failed to connect to the target application.', null, 'E');
					}
                    throw new \Exception('Failed to connect to the target application.');
                }
            }

			// Run workflow after send
			if (
					!empty($response)
				AND empty($response['error'])
				AND !empty($this->ruleWorkflows)
			) {
				foreach($response as $docId => $value) {
					if (!empty($value)) {
						$this->rerunWorkflowDocument($docId);
					}
				}
			}
        } catch (\Exception $e) {
            $response['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            if (!$this->api) {
                echo $response['error'];
            }
            $this->logger->error($response['error']);
        }
        return $response;
    }
	
	protected function checkSourceBeforeSend($send) {	
		if (empty($this->solutionSource)) {		
			$this->solutionSource = $this->solutionManager->get($this->rule['solution_source_name']);
		}
		if($this->solutionSource->sourceCallRequestedBeforeSend($send)) {
			$connect = $this->connexionSolution('source');
			if ($connect) {		
				// Add source data into send array
				if (!empty($send['data'])) {
					foreach ($send['data'] as $documentId => $record) {		
						$send['source'][$documentId] = $this->getDocumentData($documentId, 'S');						
					}
				}	
				$send = $this->solutionSource->sourceActionBeforeSend($send);
			} else {	
				throw new \Exception('Failed to connect to the source solution before sending data.');
			}
		}
		return $send;
	}

    protected function massSendTarget($type, $documentId = null)
    {
        try {

            if ((strpos($documentId, ',') !== false)) {
                $arrayDocumentsIds = explode(',', $documentId);
            } else {
                $arrayDocumentsIds[0] = $documentId;
            }
            
            // Permet de charger dans la classe toutes les relations de la règle
            $response = [];
            $response['error'] = '';

            // Le type peut-être vide das le cas d'un relancement de flux après une erreur
            if (empty($type)) {
                foreach($arrayDocumentsIds as $documentId){
                    $documentData = $this->getDocumentHeader($documentId);
                }
                if (!empty($documentData['type'])) {
                    $type = $documentData['type'];
                }
            }

            foreach($arrayDocumentsIds as $documentId){
                $sendDataDocumentArrayElement = $this->getSendDocuments($type, $documentId);
                $send['data'] = (object) [$documentId => $sendDataDocumentArrayElement[$documentId]];
            }
            // Récupération du contenu de la table target pour tous les documents à envoyer à la cible
            $send['module'] = $this->rule['module_target'];
            $send['ruleId'] = $this->rule['id'];
            $send['rule'] = $this->rule;
            $send['ruleFields'] = $this->ruleFields;
            $send['ruleParams'] = $this->ruleParams;
            $send['ruleRelationships'] = $this->ruleRelationships;
			$send['ruleWorkflows'] = $this->ruleWorkflows;
            $send['jobId'] = $this->jobId;
            // Si des données sont prêtes à être créées
            if (!empty($send['data'])) {
                // If the rule is a child rule, no document is sent. They will be sent with the parent rule.
                if ($this->isChild()) {
                    foreach ($send['data'] as $key => $data) {
                        // True is send to avoid an error in rerun method. We should put the target_id but the document will be send with the parent rule.
                        $response[$key] = ['id' => true];
                    }

                    return $response;
                }

                // Connexion à la cible
                $connect = $this->connexionSolution('target');
                if (true === $connect) {
                    // Création des données dans la cible
                    if ('C' == $type) {
                        // Permet de vérifier que l'on ne va pas créer un doublon dans la cible
                        $send['data'] = $this->checkDuplicate($send['data']);
                        $send['data'] = $this->clearSendData($send['data']);
                        $response = $this->solutionTarget->createData($send);
                    }
                    // Modification des données dans la cible
                    elseif ('U' == $type) {
                        $send['data'] = $this->clearSendData($send['data']);
                        // permet de récupérer les champ d'historique, nécessaire pour l'update de SAP par exemple
                        $send['dataHistory'][$documentId] = $this->getDocumentData($documentId, 'H');
                        $send['dataHistory'][$documentId] = $this->clearSendData($send['dataHistory'][$documentId]);
                        $response = $this->solutionTarget->updateData($send);
                    }
                    // Delete data from target application
                    elseif ('D' == $type) {
                        $send = $this->checkBeforeDelete($send);
                        if (empty($send['error'])) {
                            $send['data'] = $this->beforeDelete($send['data']);
                            $response = $this->solutionTarget->deleteData($send);
                        } else {
                            $response['error'] = $send['error'];
                        }
                    } else {
                        $response[$documentId] = false;
                        $response['error'] = 'Type transfer '.$type.' unknown. ';
                    }
                } else {
                    $response[$documentId] = false;
                    $response['error'] = $connect['error'];
                }
            }
        
        } catch (\Exception $e) {
            $response['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            if (!$this->api) {
                echo $response['error'];
            }
            $this->logger->error($response['error']);
        }

        return $response;
    }

    // Check before we send a record deletion
    protected function checkBeforeDelete($send)
    {
        // Check in case of several source records data point to the same target record
        // In this case we can't send the deletion as the record still exists in the source application
        // A merge action in the source application could have generated the deletion action but the deletion can't be sent.
        if (!empty($send['data'])) {
            foreach ($send['data'] as $docId => $record) {
                try {
                    // Should never be empty
                    if (empty($record['target_id'])) {
                        throw new \Exception('Failed to send deletion because there is no target id in the document');
                    }
                    // First step, we get all the document with the same target module, connector and record id and a source id different
                    // We exclude the cancel document except the one no_send
                    // We exclude document from a rule linked with Myddleware_element_id (when 2 source modules update one target module)
                    // At the end (HAVING) we exclude the group of document that have a deleted document (should have the status no_send)
                    $query = "	SELECT rule.conn_id_target, rule.module_target, document.target_id, document.source_id, 
									GROUP_CONCAT(DISTINCT document.type) types,
									GROUP_CONCAT(DISTINCT document.id ORDER BY document.date_created DESC) documents
								FROM document 
									INNER JOIN rule
										ON document.rule_id = rule.id
									LEFT OUTER JOIN rulerelationship
										 ON rule.id = rulerelationship.field_id
										AND rulerelationship.field_name_target = 'Myddleware_element_id'
										AND rulerelationship.rule_id <> rule.id
								WHERE 
										rule.conn_id_target = :conn_id_target
									AND rule.module_target = :module_target
									AND document.target_id = :target_id
									AND document.source_id <> (SELECT source_id from document WHERE id = :docId)
									AND document.deleted = 0
									AND (
												document.global_status <> 'Cancel'
										OR (
												document.global_status = 'Cancel'
											AND document.status = 'No_send'
										)
									)
								GROUP BY rule.conn_id_target, rule.module_target, document.target_id, document.source_id
								HAVING types NOT LIKE '%D%'";
                    $stmt = $this->connection->prepare($query);
                    $stmt->bindValue(':conn_id_target', $this->rule['conn_id_target']);
                    $stmt->bindValue(':module_target', $this->rule['module_target']);
                    $stmt->bindValue(':target_id', $record['target_id']);
                    $stmt->bindValue(':docId', $docId);
                    $result = $stmt->executeQuery();
                    $results = $result->fetchAllAssociative();
                    if (!empty($results)) {
                        foreach ($results as $result) {
                            // Get the last reference document created to add it into the log
                            $documents = explode(',', $result['documents']);
                            if (!empty($documents[0])) {
                                $docIdRefError = $documents[0];
                            }
                            throw new \Exception('A duplicate source record not deleted exists for the target record.');
                        }
                    }
                } catch (\Exception $e) {
                    // Remove the document in the list to be sent
                    unset($send['data'][$docId]);
                    // Change document status
                    $this->changeStatus($docId, 'No_send', $e->getMessage(), (!empty($docIdRefError) ? $docIdRefError : ''));
                }
            }
            // Exception if all documents has been removed from data
            if (empty($send['data'])) {
                $send['error'] = 'Every deletion record haven been cancelled for the rule '.$this->ruleId.'. Nothing to send.';
            }
        }

        return $send;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function checkDuplicate($transformedData)
    {
        // Traitement si présence de champ duplicate
        if (empty($this->ruleParams['duplicate_fields'])) {
            return $transformedData;
        }
        $duplicate_fields = explode(';', $this->ruleParams['duplicate_fields']);
        $searchDuplicate = [];
        // Boucle sur chaque donnée qui sera envoyée à la cible
        foreach ($transformedData as $docId => $rowTransformedData) {
            // Stocke la valeur des champs duplicate concaténée
            $concatduplicate = '';

            // Récupération des valeurs de la source pour chaque champ de recherche
            foreach ($duplicate_fields as $duplicate_field) {
				// No duplicate check if one of the duplicate field is empty
				if (empty($rowTransformedData[$duplicate_field])) {
					$concatduplicate = '';
					break;
				}
                $concatduplicate .= $rowTransformedData[$duplicate_field];
            }
            // Empty data aren't used for duplicate search
            if (!empty(trim($concatduplicate))) {
                $searchDuplicate[$docId] = ['concatKey' => $concatduplicate, 'source_date_modified' => $rowTransformedData['source_date_modified']];
            }
        }

        // Recherche de doublons dans le tableau searchDuplicate
        if (!empty($searchDuplicate)) {
            // Obtient une liste de colonnes
            foreach ($searchDuplicate as $key => $row) {
                $concatKey[$key] = $row['concatKey'];
                $source_date_modified[$key] = $row['source_date_modified'];
            }

            // Trie les données par volume décroissant, edition croissant
            // Ajoute $data en tant que dernier paramètre, pour trier par la clé commune
            array_multisort($concatKey, SORT_ASC, $source_date_modified, SORT_ASC, $searchDuplicate);

            // Si doublon charge on charge les documents doublons, on récupère les plus récents et on les passe à transformed sans les envoyer à la cible.
            // Le plus ancien est envoyé.
            $previous = '';
            foreach ($searchDuplicate as $key => $value) {
                if (empty($previous)) {
                    $previous = $value['concatKey'];
                    continue;
                }
                // Si doublon
                if ($value['concatKey'] == $previous) {
                    $param['id_doc_myddleware'] = $key;
                    $param['jobId'] = $this->jobId;
                    $param['api'] = $this->api;
                    // Set the param values and clear all document attributes
                    $this->documentManager->setParam($param, true);
                    $this->documentManager->setMessage('Failed to send document because this record is already send in another document. To prevent create duplicate data in the target system, this document will be send in the next job.');
                    $this->documentManager->setTypeError('W');
                    $this->documentManager->updateStatus('Transformed');
                    // Suppression du document dans l'envoi
                    unset($transformedData[$key]);
                }
                $previous = $value['concatKey'];
            }
        }
        if (!empty($transformedData)) {
            return $transformedData;
        }

        return null;
    }

    protected function selectDocuments($status, $type = '')
    {
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
        try {
			// Select documents depending of the status
            $queryDocuments = "	SELECT d
									FROM App\Entity\Document d
									WHERE 
											d.rule = :ruleId
										AND d.status = :status
										AND d.deleted = 0
										AND (
												d.jobLock = '' 
											 OR d.jobLock = :jobId
										)
									ORDER BY d.sourceDateModified ASC	
								";
								
			$query = $this->entityManager->createQuery($queryDocuments);
			$query->setParameters([
				'ruleId' => $this->entityManager->getRepository(Rule::class)->findOneBy(['id' => $this->ruleId, 'deleted' => false]),
				'status' => $status,
				'jobId' => $this->jobId,
			]);
			$query->setMaxResults($this->limit);
			// Lock the table during the query until all documents are locked
			$query->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
			$documents = $query->getArrayResult(); // array of ForumUser objects getArrayResult	
			// Lock all the document
			if (!empty($documents)) {
				foreach ($documents as $document) {
					// Lock focument without checking bacause we have selected only document with no lock
                    $this->setDocumentLock($document['id'], false);
                }
			}
			$this->connection->commit(); // -- COMMIT TRANSACTION - release documents
            return $documents;
        } catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION - release documents
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
    }

    // Permet de récupérer les données d'un document
    protected function getDocumentHeader($documentId)
    {
        try {
            // We allow to get date from a document flagged deleted
            $query_document = 'SELECT * FROM document WHERE id = :documentId';
            $stmt = $this->connection->prepare($query_document);
            $stmt->bindValue(':documentId', $documentId);
            $result = $stmt->executeQuery();
            $document = $result->fetchAssociative();
            if (!empty($document)) {
                return $document;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getSendDocuments($type, $documentId=null, $table = 'target', $parentDocId = '', $parentRuleId = ''): ?array
    {
        // Init $limit parameter
        $limit = ' LIMIT '.$this->limit;
        // Si un document est en paramètre alors on filtre la requête sur le document
        if (!empty($documentId)) {
            $documentFilter = " 	document.id = '$documentId'
								AND document.deleted = 0 
                                AND document.status IN ('Ready_to_send', 'Error_sending')
                                AND (
										document.job_lock = '' 
									 OR document.job_lock = '$this->jobId'
								)
								AND document.workflow_error = 0
							";
        } elseif (!empty($parentDocId)) {
            $documentFilter = " 	document.parent_id = '$parentDocId' 
								AND document.rule_id = '$parentRuleId'
								AND document.deleted = 0 
                                AND document.status IN ('Ready_to_send', 'Error_sending')
                                AND (
										document.job_lock = '' 
									 OR document.job_lock = '$this->jobId'
								)
								AND document.workflow_error = 0
							";
            // No limit when it comes to child rule. A document could have more than $limit child documents
            $limit = '';
        }
        // Sinon on récupère tous les documents élligible pour l'envoi
        else {
            $documentFilter = "	    document.rule_id = '$this->ruleId'
								AND document.status = 'Ready_to_send'
								AND document.deleted = 0
								AND document.type = '$type' 
                                AND (
										document.job_lock = '' 
									 OR document.job_lock = '$this->jobId'
								)
								AND document.workflow_error = 0
							";
        }
        // Sélection de tous les documents au statut transformed en attente de création pour la règle en cours
        $sql = "SELECT document.id id_doc_myddleware, document.target_id, document.source_date_modified
				FROM document
				WHERE $documentFilter 
				ORDER BY document.source_date_modified ASC
				$limit";
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery();
        $documents = $result->fetchAllAssociative();
        foreach ($documents as $document) {
			$error = '';
            // If the rule is a parent, we have to get the data of all rules child
            $childRules = $this->getChildRules();
            if (!empty($childRules)) {
                foreach ($childRules as $childRule) {
                    $childRuleObj = new RuleManager($this->logger, $this->connection, $this->entityManager, $this->parameterBagInterface, $this->formulaManager, $this->solutionManager, $this->documentManager);
                    $childRuleObj->setRule($childRule['field_id']);
                    $childRuleObj->setJobId($this->jobId);
                    // Recursive call to get all data from all child in status ready to send generated by the method Document=>runChildRule
                    // Child document has the type 'U'
                    $dataChild = $childRuleObj->getSendDocuments('U', '', $table, $document['id_doc_myddleware'], $childRule['field_id']);

                    $childRuleDetail = $childRuleObj->getRule();
                    // Store the submodule data to be send in the parent document
                    // If the structure already exists in the document array, we merge data (several rules can add dsata in the same structure)
                    if (empty($document[$childRuleDetail['module_target']])) {
                        $document[$childRuleDetail['module_target']] = $dataChild;
                    } else {
                        if (!empty($dataChild)) {
                            $document[$childRuleDetail['module_target']] = array_merge($document[$childRuleDetail['module_target']], $dataChild);
                        }
                    }
                }
            }

            // Lock the document 
            $documentLock = $this->setDocumentLock($document['id_doc_myddleware']);
            if($documentLock['success']) {
                // Get document data
                $data = $this->getDocumentData($document['id_doc_myddleware'], strtoupper(substr($table, 0, 1)));
                if (!empty($data)) {
                    // Document is added to the result to be sent
                    $return[$document['id_doc_myddleware']] = array_merge($document, $data);
                } else {
                    $error = 'No data found in the document. ';
                }
            } else {
                $error = $documentLock['error'];
            }
            // If error we create a log on the document but we keep the status ready to send
            if (!empty($error)) {
                $param['id_doc_myddleware'] = $document['id_doc_myddleware'];
                $param['jobId'] = $this->jobId;
                $param['api'] = $this->api;
                // Set the param values and clear all document attributes
                if($this->documentManager->setParam($param, true)) {
                    $this->documentManager->generateDocLog('W', $error);
                } else {
                    $this->logger->error('Job '.$this->jobId.' : Failed to create log for the document '.$document['id_doc_myddleware'].'. ');
                }
            } 
        }
        if (!empty($return)) {
            return $return;
        }

        return null;
    }

    // Set the document lock
    protected function setDocumentLock($docId, $check = true) {
        try {
			// Get the job lock on the document
			if ($check) {
				$documentQuery = 'SELECT * FROM document WHERE id = :doc_id';
				$stmt = $this->connection->prepare($documentQuery);
				$stmt->bindValue(':doc_id', $docId);
				$documentResult = $stmt->executeQuery();
				$documentData = $documentResult->fetchAssociative(); // 1 row
			}
			
            // If document already lock by the current job (rerun action for example), we return true;
            if (
					$check
				AND $documentData['job_lock'] == $this->jobId
			) {
                return array('success' => true);
            // If document not locked, we lock it.
            } elseif (
					!$check
				 OR	(
						$check
					AND empty($documentData['job_lock'])
				)
			) {
                $now = gmdate('Y-m-d H:i:s');
                $query = '	UPDATE document 
                                SET 
                                    date_modified = :now,
                                    job_lock = :job_id
                                WHERE
                                    id = :id
                                ';
                $stmt = $this->connection->prepare($query);
                $stmt->bindValue(':now', $now);
                $stmt->bindValue(':job_id', $this->jobId);
                $stmt->bindValue(':id', $docId);
                $result = $stmt->executeQuery();
                return array('success' => true);
            // Error for all other cases
            } else {
                return array('success' => false, 'error' => 'The document is locked by the task '.$documentData['job_lock'].'. ');
            }
        } catch (\Exception $e) {
            // $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
            return array('success' => false, 'error' => 'Failed to lock the document '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
		}
    }

    // Permet de charger tous les champs de la règle
    protected function setRuleField()
    {
        try {
            $this->sourceFields = [];
            // Lecture des champs de la règle
            $sqlFields = 'SELECT * 
							FROM rulefield 
							WHERE rule_id = :ruleId';
            $stmt = $this->connection->prepare($sqlFields);
            $stmt->bindValue(':ruleId', $this->ruleId);
            $result = $stmt->executeQuery();
            $this->ruleFields = $result->fetchAllAssociative();
            if ($this->ruleFields) {
                foreach ($this->ruleFields as $RuleField) {
                    // Plusieurs champs source peuvent être utilisé pour un seul champ cible
                    $fields = explode(';', $RuleField['source_field_name']);
                    foreach ($fields as $field) {
                        $this->sourceFields[] = ltrim($field);
                    }
                    $this->targetFields[] = ltrim($RuleField['target_field_name']);
                }
            }

            // Lecture des relations de la règle
            if ($this->ruleRelationships) {
                foreach ($this->ruleRelationships as $ruleRelationship) {
                    $this->sourceFields[] = ltrim($ruleRelationship['field_name_source']);
                    $this->targetFields[] = ltrim($ruleRelationship['field_name_target']);
                }
            }

			// Read fields used in filter
			if (!empty($this->ruleFilters)) {
                foreach ($this->ruleFilters as $ruleFilter) {
                    $this->sourceFields[] = ltrim($ruleFilter['target']);
                }
            }
			
            // Dédoublonnage des tableaux
            if (!empty($this->targetFields)) {
                $this->targetFields = array_unique($this->targetFields);
            }
            if (!empty($this->sourceFields)) {
                $this->sourceFields = array_unique($this->sourceFields);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
    }

    // Set rule param from the database
    protected function setRuleParam()
    {
        try {
            $this->ruleParams = [];
            $sqlParams = 'SELECT * 
							FROM ruleparam 
							WHERE rule_id = :ruleId';
            $stmt = $this->connection->prepare($sqlParams);
            $stmt->bindValue(':ruleId', $this->ruleId);
            $result = $stmt->executeQuery();
            $ruleParams = $result->fetchAllAssociative();
            if ($ruleParams) {
                foreach ($ruleParams as $ruleParam) {
                    $this->ruleParams[$ruleParam['name']] = ltrim($ruleParam['value']);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
    }

   // Set variable from the database
    protected function setVariable()
    {
        try {
			$variablesEntity = $this->entityManager->getRepository(Variable::class)->findAll();
            if (!empty($variablesEntity)) {
				foreach ($variablesEntity as $variable) {
					$this->variables[$variable->getName()] = $variable->getvalue();
				}
			}
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
    }

    // Permet de charger toutes les relations de la règle
    protected function setRuleRelationships()
    {
        try {
            $sqlFields = 'SELECT * 
							FROM rulerelationship 
							WHERE 
									rule_id = :ruleId
								AND rule_id IS NOT NULL';
            $stmt = $this->connection->prepare($sqlFields);
            $stmt->bindValue(':ruleId', $this->ruleId);
            $result = $stmt->executeQuery();
            $this->ruleRelationships = $result->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
    }

	// Permet de charger toutes les relations de la règle
    protected function setRuleWorkflows()
    {
        try {
            $sqlWorkflows = 'SELECT * FROM workflow WHERE rule_id = :ruleId AND deleted = 0 AND active = 1 ORDER BY `order` ASC';
            $stmt = $this->connection->prepare($sqlWorkflows);
            $stmt->bindValue(':ruleId', $this->ruleId);
            $result = $stmt->executeQuery();
            $this->ruleWorkflows = $result->fetchAllAssociative();
			if (!empty($this->ruleWorkflows)) {
				foreach($this->ruleWorkflows as $key => $workflow) {
					$sqlActions = 'SELECT * FROM workflowaction WHERE workflow_id = :workflowid AND deleted = 0 AND active = 1 ORDER BY `order` ASC';
					$stmt = $this->connection->prepare($sqlActions);
					$stmt->bindValue(':workflowid', $workflow['id']);
					$result = $stmt->executeQuery();
					$this->ruleWorkflows[$key]['actions'] = $result->fetchAllAssociative();
				}
			}
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
    }

    // Set the limit rule
    protected function setLimit()
    {
        // Change the default value if a limit exists on the rule
        if (!empty($this->ruleParams['limit'])) {
            $this->limit = $this->ruleParams['limit'];
        }
        // Add one to the rule limit because when we reach the limit in the read finction,
        // we remove at least one record (see function validateReadDataSource)
        ++$this->limit;
    }

    // Permet de charger toutes les filtres de la règle
    protected function setRuleFilter()
    {
        try {
            $sqlFields = 'SELECT * 
							FROM rulefilter 
							WHERE 
								rule_id = :ruleId';
            $stmt = $this->connection->prepare($sqlFields);
            $stmt->bindValue(':ruleId', $this->ruleId);
            $result = $stmt->executeQuery();
            $this->ruleFilters = $result->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
    }

    // Get the child rules of the current rule
    // Return the relationships between the parent and the clild rules

    /**
     * @throws Exception
     */
    public function getChildRules(): array
    {
        try {
            // get the rule linked to the current rule and check if they have the param child
            $sqlFields = 'SELECT rulerelationship.*
							FROM rulerelationship
							WHERE 
									rulerelationship.rule_id = :ruleId
								AND rulerelationship.parent = 1';
            $stmt = $this->connection->prepare($sqlFields);
            $stmt->bindValue(':ruleId', $this->ruleId);
            $result = $stmt->executeQuery();

            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            throw new \Exception('failed to get the child rules : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
    }

    // Permet de charger les données du système source pour ce document
    protected function getDocumentData($documentId, $type)
    {
        try {
            $documentDataEntity = $this->entityManager->getRepository(DocumentData::class)
                                    ->findOneBy([
                                        'doc_id' => $documentId,
                                        'type' => $type,
                                        ]
                                );
            // Generate data array
            if (!empty($documentDataEntity)) {
                return json_decode($documentDataEntity->getData(), true);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error getSourceData  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }

        return false;
    }

    // Delete a document data
    protected function deleteDocumentData($documentId, $type): bool
    {
        try {
            $documentDataEntity = $this->entityManager->getRepository(DocumentData::class)
                                    ->findOneBy([
                                        'doc_id' => $documentId,
                                        'type' => $type,
                                        ]
                                );
            // Generate data array
            if (!empty($documentDataEntity)) {
                $this->entityManager->remove($documentDataEntity);
                $this->entityManager->flush();

                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error('Error getSourceData  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }

        return false;
    }

    // Get the content of the table config
    protected function setConfigParam()
    {
        if (empty($this->configParams)) {
            $configRepository = $this->entityManager->getRepository(Config::class);
            $configs = $configRepository->findAll();
            if (!empty($configs)) {
                foreach ($configs as $config) {
                    $this->configParams[$config->getName()] = $config->getvalue();
                }
            }
        }
    }


    /**
     *  Parameter de la règle choix utilisateur.
     *
     * @return array
     *               [
     *               'id' 		=> 'datereference',
     *               'name' 		=> 'datereference',
     *               'required'	=> true,
     *               'type'		=> 'text',
     *               'label' => 'solution.params.dateref',
     *               'readonly' => true
     *               ]
     */
    public static function getFieldsParamUpd(): array
    {
        return [];
    }

    // Parametre de la règle obligation du système par défaut
    public static function getFieldsParamDefault($idSolutionSource = '', $idSolutionTarget = ''): array
    {
        return [
            'active' => false,
            'RuleParam' => [
                'limit' => '100',
                'delete' => '60',
                'datereference' => date('Y-m-d').' 00:00:00',
            ],
        ];
    }

    // Parametre de la règle en modification dans la fiche
    public static function getFieldsParamView($idRule = ''): array
    {
        return [
            [
                'id' => 'datereference',
                'name' => 'datereference',
                'required' => true,
                'type' => TextType::class,
                'label' => 'solution.params.dateref',
            ],
            [
                'id' => 'limit',
                'name' => 'limit',
                'required' => true,
                'type' => IntegerType::class,
                'label' => 'solution.params.limit',
            ],
            [ // clear data
                'id' => 'delete',
                'name' => 'delete',
                'required' => false,
                'type' => 'option',
                'label' => 'solution.params.delete',
                'option' => [
                                '0' => 'solution.params.0_day',
                                '1' => 'solution.params.1_day',
                                '7' => 'solution.params.7_day',
                                '14' => 'solution.params.14_day',
                                '30' => 'solution.params.30_day',
                                '60' => 'solution.params.60_day',
                                '90' => 'solution.params.90_day',
                            ],
            ],
            [
                'id' => 'description',
                'name' => 'description',
                'required' => true,
                'type' => TextareaType::class,
                'label' => 'solution.params.description',
            ],
        ];
    }
}
