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

use App\Entity\Document;
use App\Entity\DocumentData;
use App\Entity\DocumentData as DocumentDataEntity;
use App\Entity\Workflow;
use App\Entity\WorkflowLog;
use App\Entity\WorkflowAction;
use App\Entity\Job;
use App\Entity\DocumentRelationship as DocumentRelationship;
use App\Repository\DocumentRepository;
use App\Repository\RuleRelationShipRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Swift_Mailer;
use Swift_Message;

class documentcore
{
    public $id;

    protected EntityManagerInterface $entityManager;
    protected string $typeError = 'S';
    protected string $message = '';
    protected $dateCreated;
    protected Connection $connection;
    protected $ruleName;
    protected $ruleMode;
    protected $ruleId;
    protected $ruleFields;
    protected $ruleRelationships;
    protected $ruleWorkflows;
    protected $ruleParams;
    protected $sourceId;
    protected $targetId;
    protected $parentId;
    protected $sourceData;
    protected $data;
    protected $documentType;
    protected bool $jobActive = true;
    protected $attempt;
    protected $jobLock;
    protected $userId;
    protected $status;
    protected $document_data;
    protected $solutionTarget;
    protected $solutionSource;
    protected $jobId;
    protected $key;
    protected $docIdRefError;
	protected $env;
    protected bool $transformError = false;
    protected ?ToolsManager $tools;
    protected $api;    // Specify if the class is called by the API
    protected $ruleDocuments;
    protected $container;
    protected LoggerInterface $logger;
    protected FormulaManager $formulaManager;
    private DocumentRepository $documentRepository;
    private RuleRelationShipRepository $ruleRelationshipsRepository;
    protected ?ParameterBagInterface $parameterBagInterface;
    protected ?SolutionManager $solutionManager;
    protected array $globalStatus = [
        'New' => 'Open',
        'Predecessor_OK' => 'Open',
        'Relate_OK' => 'Open',
        'Transformed' => 'Open',
        'Ready_to_send' => 'Open',
        'Filter_OK' => 'Open',
        'Send' => 'Close',
        'Found' => 'Close',
        'Filter' => 'Cancel',
        'No_send' => 'Cancel',
        'Cancel' => 'Cancel',
        'Filter_KO' => 'Error',
        'Predecessor_KO' => 'Error',
        'Relate_KO' => 'Error',
        'Error_transformed' => 'Error',
        'Error_checking' => 'Error',
        'Error_sending' => 'Error',
        'Not_found' => 'Error',
    ];
    private array $notSentFields = [];

    // Instanciation de la classe de génération de log Symfony
    public function __construct(
        LoggerInterface $logger,
        Connection $dbalConnection,
        EntityManagerInterface $entityManager,
        DocumentRepository $documentRepository,
        RuleRelationShipRepository $ruleRelationshipsRepository,
        FormulaManager $formulaManager,
        SolutionManager $solutionManager = null,
        ParameterBagInterface $parameterBagInterface = null,
        ToolsManager $tools = null
    ) {
        $this->connection = $dbalConnection;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->documentRepository = $documentRepository;
        $this->ruleRelationshipsRepository = $ruleRelationshipsRepository;
        $this->parameterBagInterface = $parameterBagInterface;
        // $param = $params->get('param');
        $this->tools = $tools;
        $this->formulaManager = $formulaManager;
        $this->solutionManager = $solutionManager;
		$this->env = $_SERVER['APP_ENV'];
    }

    public static function lstGblStatus(): array
    {
        return [
            'Open' => 'flux.gbl_status.open',
            'Close' => 'flux.gbl_status.close',
            'Cancel' => 'flux.gbl_status.cancel',
            'Error' => 'flux.gbl_status.error',
        ];
    }

    public static function lstStatus(): array
    {
        return [
            'New' => 'flux.status.new',
            'Predecessor_OK' => 'flux.status.predecessor_ok',
            'Relate_OK' => 'flux.status.relate_ok',
            'Transformed' => 'flux.status.transformed',
            'Ready_to_send' => 'flux.status.ready_to_send',
            'Filter_OK' => 'flux.status.filter_ok',
            'Send' => 'flux.status.send',
            'Filter' => 'flux.status.filter',
            'No_send' => 'flux.status.no_send',
            'Cancel' => 'flux.status.cancel',
            'Filter_KO' => 'flux.status.filter_ko',
            'Predecessor_KO' => 'flux.status.predecessor_ko',
            'Relate_KO' => 'flux.status.relate_ko',
            'Error_transformed' => 'flux.status.error_transformed',
            'Error_checking' => 'flux.status.error_checking',
            'Error_sending' => 'flux.status.error_sending',
        ];
    }

    public static function lstType(): array
    {
        return [
            'C' => 'flux.type.create',
            'U' => 'flux.type.update',
            'D' => 'flux.type.delete',
            'S' => 'flux.type.search',
        ];
    }

    public function setDocument($id_doc)
    {
        try {
            $sqlParams = "	SELECT 
								document.*, 
								rule.name_slug,
								ruleparam.value mode,
								rule.conn_id_source,
								rule.conn_id_target,
								rule.module_source
							FROM document 
								INNER JOIN rule
									ON document.rule_id = rule.id
								INNER JOIN ruleparam
									ON  ruleparam.rule_id = rule.id
									AND ruleparam.name= 'mode'
							WHERE document.id = :id_doc";
            $stmt = $this->connection->prepare($sqlParams);
            $stmt->bindValue(':id_doc', $id_doc);
            $result = $stmt->executeQuery();
            $this->document_data = $result->fetchAssociative();

            if (!empty($this->document_data['id'])) {
                $this->id = $this->document_data['id'];
                $this->dateCreated = $this->document_data['date_created'];
                $this->userId = $this->document_data['created_by'];
                $this->ruleId = $this->document_data['rule_id'];
                $this->status = $this->document_data['status'];
                $this->sourceId = $this->document_data['source_id'];
                $this->targetId = $this->document_data['target_id'];
                $this->ruleName = $this->document_data['name_slug'];
                $this->ruleMode = $this->document_data['mode'];
                $this->documentType = $this->document_data['type'];
                $this->attempt = $this->document_data['attempt'];
                $this->jobLock = $this->document_data['job_lock'];
				// A document can be loaded only if there is no lock or if the lock is on the current job.
                if (
						!empty($this->jobLock)
					AND $this->jobLock != $this->jobId
				) {
					throw new \Exception('This document is locked by the task '.$this->jobLock.'. ');
				// No setlock if $this->jobLock == $this->jobId
				} elseif (!empty($this->jobLock)) {
					$this->setLock();
				}
				
                // Get source data and create data attribut
                $this->sourceData = $this->getDocumentData('S');
                $this->data = $this->sourceData;
                // Get document header
                $documentEntity = $this->entityManager
                              // ->getRepository('RegleBundle:Document')
                              ->getRepository(Document::class)
                              ->find($id_doc);
                $this->data['id'] = $documentEntity->getSource();
                $this->data['source_date_modified'] = $documentEntity->getSourceDateModified()->format('Y-m-d H:i:s');
            } else {
                $this->logger->error('Failed to retrieve Document '.$id_doc.'.');
            }
        } catch (\Exception $e) {
			// Remove the lock because there is not status changed (lock is usually remove when we change the status)
			$this->unsetLock();
            // Stop the process
            throw new \Exception('Failed to load the document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
    }

    // Set the document param
    // Clear parameter is used when we call the same instance of the Document to manage several documents (from RuleManager class)
    public function setParam($param, $clear = false, $clearRule = true)
    {
		try {
			if ($clear) {
				$this->clearAttributes($clearRule);
			}
			// Chargement des solution si elles sont présentent dans les paramètres de construction
			if (!empty($param['solutionTarget'])) {
				$this->solutionTarget = $param['solutionTarget'];
			}
			if (!empty($param['solutionSource'])) {
				$this->solutionSource = $param['solutionSource'];
			}
			if (!empty($param['jobId'])) {
				$this->jobId = $param['jobId'];
			}
			if (!empty($param['api'])) {
				$this->api = $param['api'];
			}
			if (!empty($param['parentId'])) {
				$this->parentId = $param['parentId'];
			}
			if (!empty($param['ruleDocuments'])) {
				$this->ruleDocuments = $param['ruleDocuments'];
			}

			// Init attribut of the class Document
			if (!empty($param['id_doc_myddleware'])) {
				// Instanciate attribut sourceData
				$this->setDocument($param['id_doc_myddleware']);
			} else {
				$this->id = uniqid('', true);
				$this->dateCreated = gmdate('Y-m-d H:i:s');
				$this->ruleName = $param['rule']['name_slug'];
				$this->ruleMode = $param['rule']['mode'];
				$this->ruleId = $param['rule']['id'];
				$this->ruleFields = $param['ruleFields'];
				$this->data = $param['data'];
				$this->sourceId = $this->data['id'];
				$this->userId = $param['rule']['created_by'];
				$this->status = 'New';
				$this->attempt = 0;
				$this->jobLock = $this->jobId;
				// Set the deletion type if myddleware deletion flag is true
				if (!empty($this->data['myddleware_deletion'])) {
					$this->documentType = 'D';
				}
			}
			// Ajout des paramètre de la règle
			if (empty($this->ruleParams)) {
				$this->setRuleParam();
			}
			// Mise à jour des tableaux s'ils existent.
			if (!empty($param['ruleFields'])) {
				$this->ruleFields = $param['ruleFields'];
			}
			if (!empty($param['ruleRelationships'])) {
				$this->ruleRelationships = $param['ruleRelationships'];
			}
			if (!empty($param['ruleWorkflows'])) {
				$this->ruleWorkflows = $param['ruleWorkflows'];
			}
			// Init type error for each new document
			$this->typeError = 'S';
		} catch (\Exception $e) {
            $this->message .= $e->getMessage();
            $this->typeError = 'E';
            $this->logger->error($this->message);
            $this->createDocLog();
			return false;
        }
		return true;
    }

    // Clear all class attributes
    protected function clearAttributes($clearRule = true)
    {
        // Clear rule parameter only if requested
        if ($clearRule) {
            $this->ruleName = '';
            $this->ruleMode = '';
            $this->ruleId = '';
            $this->ruleFields = [];
            $this->ruleRelationships = [];
            $this->ruleWorkflows = [];
            $this->ruleParams = [];
        }
        $this->id = '';
        $this->message = '';
        $this->dateCreated = '';
        $this->sourceId = '';
        $this->targetId = '';
        $this->parentId = '';
        $this->sourceData = [];
        $this->data = [];
        $this->documentType = '';
        $this->jobActive = true;
        $this->attempt = '';
        $this->userId = '';
        $this->status = '';
        $this->document_data = [];
        $this->solutionTarget = '';
        $this->solutionSource = '';
        $this->jobId = '';
        $this->docIdRefError = '';
        $this->transformError = false;
        $this->api = '';    // Specify if the class is called by the API
        $this->ruleDocuments = [];
    }

    public function createDocument(): bool
    {
        // On ne fait pas de beginTransaction ici car on veut pouvoir tracer ce qui a été fait ou non. Si le créate n'est pas du tout fait alors les données sont perdues
        // L'enregistrement même partiel d'un document nous permet de tracer l'erreur.
        try {
            // Return false if job has been manually stopped
            if (!$this->jobActive) {
                $this->message .= 'Job is not active. ';

                return false;
            }
            // Création du header de la requête
            $query_header = 'INSERT INTO document (id, rule_id, date_created, date_modified, created_by, modified_by, source_id, source_date_modified, mode, type, parent_id, job_lock) VALUES';
            // Création de la requête d'entête
            $date_modified = $this->data['date_modified'];
            // Source_id could contain accent
            $query_header .= "('$this->id','$this->ruleId','$this->dateCreated','$this->dateCreated','$this->userId','$this->userId','".utf8_encode($this->sourceId)."','$date_modified','$this->ruleMode','$this->documentType','$this->parentId', '')";
            $stmt = $this->connection->prepare($query_header);
            $result = $stmt->executeQuery();
            $this->updateStatus('New');
            // Insert source data
            return $this->insertDataTable($this->data, 'S');
        } catch (\Exception $e) {
            $this->message .= 'Failed to create document (id source : '.$this->sourceId.'): '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($this->message);
            return false;
        }
    }

    // Permet de filtrer ou non un document
    public function filterDocument($ruleFilters)
    {
        // Return false if job has been manually stopped
        if (!$this->jobActive) {
            $this->message .= 'Job is not active. ';

            return false;
        }
        try {
            $filterOK = true;
            // Only if there is a least one filter
			// No filter on delete document as they will be filter after is Myddleware never sent the data
            if (
					!empty($ruleFilters)
				AND $this->documentType != 'D'
			) {
                // Boucle sur les filtres
                foreach ($ruleFilters as $ruleFilter) {
                    if (!$this->checkFilter($this->sourceData[$ruleFilter['target']], $ruleFilter['type'], $ruleFilter['value'])) {
                        $this->message .= 'This document is filtered. This operation is false : '.$ruleFilter['target'].' '.$ruleFilter['type'].' '.$ruleFilter['value'].'.';
                        $this->updateStatus('Filter');
                        $filterOK = -1;
                        break;
                    }
                }
            }
            // Si on a pas eu d'erreur alors le document passe à l'étape suivante
            if (true === $filterOK) {
                $this->updateStatus('Filter_OK');
            }

            return $filterOK;
        } catch (\Exception $e) {
            $this->message .= 'Failed to filter document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->updateStatus('Filter_KO');
            $this->logger->error($this->message);

            return false;
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getJobStatus()
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

    public function getRuleId()
    {
        return $this->ruleId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getJobActive(): bool
    {
        return $this->jobActive;
    }

    public function setMessage($message)
    {
        $this->message .= $message;
    }

    public function setTypeError($typeError)
    {
        $this->typeError = $typeError;
    }

    public function setRuleId($ruleId)
    {
        $this->ruleId = $ruleId;
    }

    public function setDocIdRefError($docIdRefError)
    {
        $this->docIdRefError = $docIdRefError;
    }

    // Set the document lock
    protected function setLock() {
        try {
			// Get the job lock on the document
            $documentQuery = 'SELECT * FROM document WHERE id = :doc_id';
            $stmt = $this->connection->prepare($documentQuery);
            $stmt->bindValue(':doc_id', $this->id);
            $documentResult = $stmt->executeQuery();
            $documentData = $documentResult->fetchAssociative(); // 1 row

            // If document already lock by the current job, we return true;
            if ($documentData['job_lock'] == $this->jobId) {
                return array('success' => true);
            // If document not locked, we lock it.
            } elseif (empty($documentData['job_lock'])) {
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
                $stmt->bindValue(':id', $this->id);
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
	
	// Set the document lock
    public function unsetLock($force = false) {
        try {
			// Get the job lock on the document
            $documentQuery = 'SELECT * FROM document WHERE id = :doc_id';
            $stmt = $this->connection->prepare($documentQuery);
            $stmt->bindValue(':doc_id', $this->id);
            $documentResult = $stmt->executeQuery();
            $documentData = $documentResult->fetchAssociative(); // 1 row
            // If document already lock by the current job, we return true;
            if (
					$documentData['job_lock'] == $this->jobId
				 OR $force === true
			) {
                $now = gmdate('Y-m-d H:i:s');
                $query = "	UPDATE document 
                                SET 
                                    date_modified = :now,
                                    job_lock = ''
                                WHERE
                                    id = :id
                                ";
                $stmt = $this->connection->prepare($query);
                $stmt->bindValue(':now', $now);
                $stmt->bindValue(':id', $this->id);
                $result = $stmt->executeQuery();
                return true;
            }
        } catch (\Exception $e) {
            return false;
		}
    }

    // Permet d'indiquer si le filtreest rempli ou pas
    protected function checkFilter($fieldValue, $operator, $filterValue): bool
    {
        switch ($operator) {
            case 'content':
                $pos = stripos($fieldValue, $filterValue);
                if (false === $pos) {
                    return false;
                } else {
                    return true;
                }
            case 'notcontent':
                $pos = stripos($fieldValue, $filterValue);
                if (false === $pos) {
                    return true;
                } else {
                    return false;
                }
            case 'begin':
                $begin = substr($fieldValue, 0, strlen($filterValue));
                if (strtoupper($begin) == strtoupper($filterValue)) {
                    return true;
                } else {
                    return false;
                }
            case 'end':
                $begin = substr($fieldValue, 0 - strlen($filterValue));
                if (strtoupper($begin) == strtoupper($filterValue)) {
                    return true;
                } else {
                    return false;
                }
            case 'in':
                if (in_array(strtoupper($fieldValue), explode(';', strtoupper($filterValue)))) {
                    return true;
                } else {
                    return false;
                }
            case 'notin':
                if (!in_array(strtoupper($fieldValue), explode(';', strtoupper($filterValue)))) {
                    return true;
                } else {
                    return false;
                }
            case 'gt':
                if ($fieldValue > $filterValue) {
                    return true;
                } else {
                    return false;
                }
            case 'lt':
                if ($fieldValue < $filterValue) {
                    return true;
                } else {
                    return false;
                }
            case 'lteq':
                if ($fieldValue <= $filterValue) {
                    return true;
                } else {
                    return false;
                }
            case 'gteq':
                if ($fieldValue >= $filterValue) {
                    return true;
                } else {
                    return false;
                }
            case 'equal':
                if (strtoupper($fieldValue) == strtoupper($filterValue)) {
                    return true;
                } else {
                    return false;
                }
            case 'different':
                if (strtoupper($fieldValue) != strtoupper($filterValue)) {
                    return true;
                } else {
                    return false;
                }
            default:
                $this->message .= 'Failed to filter. Operator '.$operator.' unknown. ';

                return false;
        }
    }

    // Permet de valider qu'aucun document précédent pour la même règle et le même id sont bloqués
    public function checkPredecessorDocument(): bool
    {
        // Return false if job has been manually stopped
        if (!$this->jobActive) {
            $this->message .= 'Job is not active. ';

            return false;
        }
        try {
            // Check predecessor in the current rule
            $sqlParams = "	SELECT 
								document.id,							
								document.rule_id,
								document.status,
								document.global_status											
							FROM document								
							WHERE 
									document.rule_id = :rule_id 
								AND document.source_id = :source_id 
								AND document.date_created < :date_created  
								AND document.deleted = 0 
								AND document.global_status IN ('Error','Open')
							LIMIT 1	
							";
            $stmt = $this->connection->prepare($sqlParams);
            $stmt->bindValue(':rule_id', $this->document_data['rule_id']);
            $stmt->bindValue(':source_id', $this->document_data['source_id']);
            $stmt->bindValue(':date_created', $this->document_data['date_created']);
            $result = $stmt->executeQuery();
            $result = $result->fetchAssociative();

            // if id found, we stop to send an error
            if (!empty($result['id'])) {
                throw new \Exception('The document '.$result['id'].' is on the same record and is not closed. This document is queued. ');
            }

            // Check predecessor in the opposite bidirectional rule
            if (!empty($this->ruleParams['bidirectional'])) {
                $stmt = $this->connection->prepare($sqlParams);
                $stmt->bindValue(':rule_id', $this->ruleParams['bidirectional']);
                $stmt->bindValue(':source_id', $this->document_data['source_id']);
                $stmt->bindValue(':date_created', $this->document_data['date_created']);
                $result = $stmt->executeQuery();
                $result = $result->fetchAssociative();
                if (!empty($result['id'])) {
                    throw new \Exception('The document '.$result['id'].' is on the same record on the bidirectional rule '.$this->ruleParams['bidirectional'].'. This document is not closed. This document is queued. ');
                }
            }

            // Check predecessor in the child rule
            // Get all child rules
            $sqlGetChildRules = '	SELECT DISTINCT
										rulerelationship.rule_id 											
									FROM rule childRule
										INNER JOIN rulerelationship
											ON rulerelationship.field_id = childRule.id
											AND rulerelationship.parent = 1
										INNER JOIN rule parentRule
											ON parentRule.id = rulerelationship.rule_id 									
									WHERE 
											parentRule.id = :rule_id 
										AND parentRule.deleted = 0 
										AND	childRule.deleted = 0';
            $stmt = $this->connection->prepare($sqlGetChildRules);
            $stmt->bindValue(':rule_id', $this->document_data['rule_id']);
            $result = $stmt->executeQuery();
            $childRules = $result->fetchAllAssociative();
            if ($childRules) {
                // If rule child, document open in ready_to_send are accepted because data in ready to send could be pending
                $sqlParamsChild = "	SELECT 
										document.id,							
										document.rule_id,
										document.status,
										document.global_status											
									FROM document								
									WHERE 
											document.rule_id = :rule_id 
										AND document.source_id = :source_id 
										AND document.deleted = 0 
										AND document.date_created < :date_created  
										AND (
												global_status = 'Error'
											 OR (
													global_status = 'Open'
												AND status != 'Ready_to_send'
											)
										)
									LIMIT 1	
							";
                foreach ($childRules as $childRule) {
                    $stmt = $this->connection->prepare($sqlParamsChild);
                    $stmt->bindValue(':rule_id', $childRule['rule_id']);
                    $stmt->bindValue(':source_id', $this->document_data['source_id']);
                    $stmt->bindValue(':date_created', $this->document_data['date_created']);
                    $result = $stmt->executeQuery();
                    $result = $result->fetchAssociative();
                    if (!empty($result['id'])) {
                        throw new \Exception('The document '.$result['id'].' is on the same record on the rule '.$childRule['rule_id'].'. This document is not closed. This document is queued. ');
                    }
                }
            }

            // Get the target id and the type of the document
            $type_document = $this->checkRecordExist($this->sourceId);
            // Don't change the document type if the type is deletion
            if ('D' != $this->documentType) {
                $this->documentType = $type_document;
                // Override the document type in case of search type rule
                if ('S' == $this->ruleMode) {
                    $this->documentType = 'S';
                }
                // Update the type of the document
                if (empty($this->documentType)) {
                    throw new \Exception('Failed to find a type for this document. ');
                }
                $this->updateType($this->documentType);
            }

            // Update the target ID if we found it (target Id is required for update and deletion)
            if (
                    (
                        'U' == $this->documentType
                     or 'D' == $this->documentType
                    )
                and !$this->isChild()
            ) {
                if (empty($this->targetId)) {
                    // If no predecessor at all (even in error or open) and type D => it means that Myddleware has never sent the record so we can't delete it
                    if ('D' == $this->documentType) {
                        $this->message .= 'No predecessor. Myddleware has never sent this record so it cannot delete it. This data transfer is cancelled. ';
                        $this->updateStatus('Cancel');

                        return false;
                    }
                    throw new \Exception('No target id found for a document with the type Update. ');
                }
                if (!$this->updateTargetId($this->targetId)) {
                    throw new \Exception('Failed to update the target id. Failed to unblock this update document. ');
                }
            }

            // Set the status Predecessor_OK
            $this->updateStatus('Predecessor_OK');

            // Check compatibility between rule mode et document type
			// A rule in create mode can't update data except for a child rule
			if (
					$this->ruleMode == 'C'
				and	$this->documentType == 'U'
			) {
				// Check child in a second time to avoid to run a query each time
				if (!$this->isChild()) {
					$this->message .= 'Rule mode only allows to create data. Filter because this document updates data.';
					$this->updateStatus('Filter');
					// In case we flter the document, we return false to stop the process when this method is called in the rerun process
					return false;
				}
			}

            return true;
        } catch (\Exception $e) {
            // Reference document id is used to show which document is blocking the current document in Myddleware
            $this->docIdRefError = ((is_array($result) and !empty($result['id'])) ? $result['id'] : '');
            $this->message .= 'Failed to check document predecessor : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->updateStatus('Predecessor_KO');
            $this->logger->error($this->message);

            return false;
        }
    }

    // Permet de valider qu'aucun document précédent pour la même règle et le même id sont bloqués
    public function checkParentDocument(): bool
    {
        // Return false if job has been manually stopped
        if (!$this->jobActive) {
            $this->message .= 'Job is not active. ';

            return false;
        }
        try {
            // No relate check for deletion document. The document linked could be also deleted.
            if ('D' == $this->documentType) {
                $this->updateStatus('Relate_OK');

                return true;
            }

            // S'il y a au moins une relation sur la règle et si on n'est pas sur une règle groupée
            // alors on contôle les enregistrements parent
            if (
                    !empty($this->ruleRelationships)
                && !$this->isChild()
            ) {
                $error = false;
                // Vérification de chaque relation de la règle
                foreach ($this->ruleRelationships as $ruleRelationship) {
                    // If relationship source data is empty
                    if (empty(trim($this->sourceData[$ruleRelationship['field_name_source']]))) {
                        // If source data is empty and errorEmpty is empty too, then no error
                        if (empty($ruleRelationship['errorEmpty'])) {
                            $this->message .= 'The source field '.$ruleRelationship['field_name_source'].' is empty.';
                            continue;
                        } else {
                            $error = true;
                            break;
                        }
                    }

                    // No check if "error if missing" is false
                    if (empty($ruleRelationship['errorMissing'])) {
                        $this->message .= 'No check on target field '.$ruleRelationship['field_name_target'].' because "Error if missing" is set to false.';
                        continue;
                    }

                    // If the relationship is a parent type, we don't check parent document here. Data will be controlled and read from the child rule when we will send the parent document. So no target id is required now.
                    if (!empty($ruleRelationship['parent'])) {
                        continue;
                    }

                    // Select previous document in the same rule with the same id and status different than closed
                    $targetId = $this->getTargetId($ruleRelationship, $this->sourceData[$ruleRelationship['field_name_source']]);
                    if (empty($targetId['record_id'])) {
                        // If no target id found, we check if the parent has been filtered, in this case we filter the relate document too
                        $documentSearch = $this->searchRelateDocumentByStatus($ruleRelationship, $this->sourceData[$ruleRelationship['field_name_source']], 'Filter');
                        if (!empty($documentSearch['id'])) {
                            $this->docIdRefError = $documentSearch['id'];
                            $this->typeError = 'W';
                            $this->message .= 'Document filter because the parent document is filter too. Check reference column to open the parent document.';
                            $this->updateStatus('Filter');

                            return false;
                        }
                        $error = true;
                        break;
                    }
                    // Save document relationship to keep the relate id and display document linked into Myddleware
                    $this->insertDocumentRelationship($ruleRelationship, $targetId['document_id']);
                }

                // Si aucun document parent n'est trouvé alors bloque le document
                if ($error) {
                    // récupération du nom de la règle pour avoir un message plus clair
                    $sqlParams = '	SELECT name FROM rule WHERE id = :rule_id';
                    $stmt = $this->connection->prepare($sqlParams);
                    $stmt->bindValue(':rule_id', $ruleRelationship['field_id']);
                    $result = $stmt->executeQuery();
                    $ruleResult = $result->fetchAssociative();
                    $direction = $this->getRelationshipDirection($ruleRelationship);
                    throw new \Exception('Failed to retrieve a related document. No data for the field '.$ruleRelationship['field_name_source'].'. There is not record with the ID '.('1' == $direction ? 'source' : 'target').' '.$this->sourceData[$ruleRelationship['field_name_source']].' in the rule '.$ruleResult['name'].'. This document is queued. ');
                }
            }
            // Get the parent document to save it in the table Document for the child document
            $parentDocumentId = '';

            if (!empty($targetId['document_id'])) {
                $parentDocumentId = $targetId['document_id'];
            }
            // Check if the status was in relate_KO before we set the status Relate_OK
            // In this cas, new data has been created in Myddleware. So we check again if the mode of the document is still Create
            if (
                    'Relate_KO' == $this->status
                and 'C' == $this->documentType
            ) {
                $this->documentType = $this->checkRecordExist($this->sourceId);
                if ('U' == $this->documentType) {
                    $this->updateTargetId($this->targetId);
                    $this->updateType('U');
					// Check compatibility between rule mode et document type
					// A rule in create mode can't update data except for a child rule
					if (
							$this->ruleMode == 'C'
						and	$this->documentType == 'U'
					) {
						// Check child in a second time to avoid to run a query each time
						if (!$this->isChild()) {
							$this->message .= 'Rule mode only allows to create data. Filter because this document updates data.';
							$this->updateStatus('Filter');
							// In case we flter the document, we return false to stop the process when this method is called in the rerun process
							return false;
						}
					}
                }
            }
            $this->updateStatus('Relate_OK');
            return true;
        } catch (\Exception $e) {
            $this->message .= 'Failed to check document related : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->updateStatus('Relate_KO');
            $this->logger->error($this->message);

            return false;
        }
    }

    // Permet de transformer les données source en données cibles
    public function transformDocument(): bool
    {
        // Return false if job has been manually stopped
        if (!$this->jobActive) {
            $this->message .= 'Job is not active. ';

            return false;
        }
        try {
            // Transformation des données et insertion dans la table target
            $transformed = $this->updateTargetTable();
            if (!empty($transformed)) {
				// If the value mdw_cancel_document is found in the target data of the document after transformation we cancel the document
				if (array_search('mdw_cancel_document',$transformed) !== false) {
					$this->message .= 'The document contains the value mdw_cancel_document. ';
					$this->typeError = 'W';
					$this->updateStatus('Cancel');
					return false;
				}
                // If the type of this document is Create and if the field Myddleware_element_id isn't empty,
                // it means that the target ID is mapped in the rule field
                // In this case, we force the document's type to Update because Myddleware will update the record into the target application
                // using Myddleware_element_id as the target ID
                if ('C' == $this->documentType) {
                    $target = $this->getDocumentData('T');
                    if (!empty($target['Myddleware_element_id'])) {
                        $this->targetId = $target['Myddleware_element_id'];
                        if ($this->updateTargetId($this->targetId)) {
                            $this->updateType('U');
							// Check compatibility between rule mode et document type
							// A rule in create mode can't update data except for a child rule
							if (
									$this->ruleMode == 'C'
								and	$this->documentType == 'U'
							) {
								// Check child in a second time to avoid to run a query each time
								if (!$this->isChild()) {
									$this->message .= 'Rule mode only allows to create data. Filter because this document updates data.';
									$this->updateStatus('Filter');
									// In case we flter the document, we return false to stop the process when this method is called in the rerun process
									return false;
								}
							}
                        } else {
                            throw new \Exception('The type of this document is Update. Failed to update the target id '.$this->targetId.' on this document. This document is queued. ');
                        }
                    }
                }
                // If the type of this document is Update and the id of the target is missing, we try to get this ID
                // Except if the rule is a child (no target id is required, it will be send with the parent rule)
                if (
                        'U' == $this->documentType
                    && empty($this->targetId)
                    && !$this->isChild()
                ) {
                    $this->checkRecordExist($this->document_data['source_id']);
                    if (!empty($this->targetId)) {
                        if (!$this->updateTargetId($this->targetId)) {
                            throw new \Exception('The type of this document is Update. Failed to update the target id '.$this->targetId.' on this document. This document is queued. ');
                        }
                    } else {
                        throw new \Exception('The type of this document is Update. The id of the target is missing. This document is queued. ');
                    }
                }
            } else {
                throw new \Exception('Failed to transformed data. This document is queued. ');
            }
            $this->updateStatus('Transformed');

            return true;
        } catch (\Exception $e) {
            $this->message .= 'Failed to transform document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->updateStatus('Error_transformed');
            $this->logger->error($this->message);

            return false;
        }
    }

    // Permet de transformer les données source en données cibles
    public function getTargetDataDocument(): bool
    {
        // Return false if job has been manually stopped
        if (!$this->jobActive) {
            $this->message .= 'Job is not active. ';

            return false;
        }
        $history = false;
        try {
            // Check if the rule is a parent and run the child data.
            $this->runChildRule();

            // If the document type is a modification or a deletion we get target data for the record using its ID
            // And if the rule is not a child (no target id is required, it will be send with the parent rule)
            if (
                    (
                        'U' == $this->documentType
                     or 'D' == $this->documentType
                    )
                 && !$this->isChild()
            ) {
                // Récupération des données avec l'id de la cible
                $searchFields = ['id' => $this->targetId];
                $history = $this->getDocumentHistory($searchFields);

                // History is mandatory before a delete action, however if no record found, it means that the record has already been deleted
                if (
                        'D' == $this->documentType
                    and false === $history
                ) {
                    $this->message .= 'This document type is D (delete) and no record have been found in the target application. It means that the record has already been deleted in the target application. This document is cancelled.';
                    $this->updateStatus('Cancel');

                    return false;
                }

                // From here, the history table has to be filled
                if (-1 !== $history) {
                    $this->updateStatus('Ready_to_send');
                } else {
                    throw new \Exception('Failed to retrieve record in target system before update or deletion. Id target : '.$this->targetId.'. Check this record is not deleted.');
                }
            }
            // Else if create or search document, if we have duplicate_fields, we search the data in target application
            elseif (!empty($this->ruleParams['duplicate_fields'])) {
                $duplicate_fields = explode(';', $this->ruleParams['duplicate_fields']);
                // Get the field value from the document target data
                $target = $this->getDocumentData('T');
                if (empty($target)) {
                    throw new \Exception('Failed to search duplicate data in the target system because there is no target data in this data transfer. This document is queued. ');
                }
                // Prepare the search array with teh value for each duplicate field
                foreach ($duplicate_fields as $duplicate_field) {
                    // In case of Myddleware_element_id, we change it to id. Myddleware_element_id reprensents the id of the record in the target application
                    if ('Myddleware_element_id' == $duplicate_field) {
                        $searchFields['id'] = $target[$duplicate_field];
                        continue;
                    }
					// Do not search duplicates on an empty field
					if (!empty($target[$duplicate_field])) {
						$searchFields[$duplicate_field] = $target[$duplicate_field];
					}
                }
                if (!empty($searchFields)) {
                    $history = $this->getDocumentHistory($searchFields);
                }

                if (-1 === $history) {
                    throw new \Exception('Failed to search duplicate data in the target system. This document is queued. ');
                }
                // Si la fonction renvoie false (pas de données trouvée dans la cible) ou true (données trouvée et correctement mise à jour)
                elseif (false === $history) {
                    // If search document and don't found the record, we return an error
                    if ('S' == $this->documentType) {
                        $this->typeError = 'E';
                        $this->updateStatus('Not_found');
                    } else {
                        $this->updateStatus('Ready_to_send');
                    }
                }
                // renvoie l'id : Si une donnée est trouvée dans le système cible alors on modifie le document pour ajouter l'id target et modifier le type
                else {
                    // Add message detail when we have found a record
                    if (!empty($searchFields)) {
                        $this->message .= 'Found ';
                        foreach ($searchFields as $key => $value) {
                            $this->message .= $key.' = '.$value.' ; ';
                        }
                    }
                    // If search document we close it.
                    if ('S' == $this->documentType) {
                        $this->updateStatus('Found');
                    } else {
                        $this->updateStatus('Ready_to_send');
                        $this->updateType('U');
						// Check compatibility between rule mode et document type
						// A rule in create mode can't update data except for a child rule
						if (
								$this->ruleMode == 'C'
							and	$this->documentType == 'U'
						) {
							// Check child in a second time to avoid to run a query each time
							if (!$this->isChild()) {
								$this->message .= 'Rule mode only allows to create data. Filter because this document updates data.';
								$this->updateStatus('No_send');
								$this->updateTargetId($history['id']);
								// In case we flter the document, we return false to stop the process when this method is called in the rerun process
								return false;
							}
						}
                    }
                    $this->updateTargetId($history['id']);
                }
            }
            // Sinon on mets directement le document en ready to send (example child rule)
            else {
                $this->updateStatus('Ready_to_send');
            }
            // S'il n'y a aucun changement entre la cible actuelle et les données qui seront envoyée alors on clos directement le document
            // Si le document est en type recherche, alors la cible est forcément égale à la source et il ne fait pas annuler le doc.
            // We always send data if the rule is parent (the child data could be different even if the parent data didn't change)
            // No check for deletion document
            if (
                    'S' != $this->documentType
                and 'D' != $this->documentType
                and !$this->isParent()
            ) {
                $this->checkNoChange($history);
            }
            // Cancel if rule mode is update only and the document is a creation
            if (
                    'C' == $this->documentType
                and 'U' == $this->ruleMode
            ) {
				$this->message .= 'The document is a creation but the rule mode is UPDATE ONLY.';
				$this->updateStatus('Filter');
				return false;
            }
        } catch (\Exception $e) {
            $this->message .= $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            if ('S' == $this->documentType) {
                $this->updateStatus('Not_found');
            } else {
                $this->updateStatus('Error_checking');
            }
            $this->logger->error($this->message);
            return false;
        }
        return true;
    }

    /**
     * Get the child rule of the current rule
     * If child rule exist, we run it.
     *
     * @throws Exception
     */
    protected function runChildRule(): bool
    {
        $parentRule = new RuleManager($this->logger, $this->connection, $this->entityManager, $this->parameterBagInterface, $this->formulaManager, $this->solutionManager, clone $this);
        $parentRule->setRule($this->ruleId);
        $parentRule->setJobId($this->jobId);
        // Get the child rules of the current rule
        $childRuleIds = $parentRule->getChildRules();
        if (!empty($childRuleIds)) {
            foreach ($childRuleIds as $childRuleId) {
                // Instantiate the child rule
                $childRule = new RuleManager($this->logger, $this->connection, $this->entityManager, $this->parameterBagInterface, $this->formulaManager, $this->solutionManager, clone $this);
                $childRule->setRule($childRuleId['field_id']);
                $childRule->setJobId($this->jobId);
                // Build the query in function generateDocuments
                if (!empty($this->sourceData[$childRuleId['field_name_source']])) {
                    $idQuery = $this->sourceData[$childRuleId['field_name_source']];
                } else {
                    //throw new \Exception( 'Failed to get the data in the document for the field '.$childRuleId['field_name_source'].'. The query to search to generate child data can\'t be created');
                    continue;
                }
                // Generate documents for the child rule (could be several documents) => We search the value of the field_name_source in the field_name_target of the target rule
                $docsChildRule = $childRule->generateDocuments($idQuery, true, ['parent_id' => $this->id], $childRuleId['field_name_target']);
                if (!empty($docsChildRule->error)) {
                    throw new \Exception($docsChildRule->error);
                }
                // Run documents
                if (!empty($docsChildRule)) {
                    foreach ($docsChildRule as $doc) {
                        $errors = $childRule->actionDocument($doc->id, 'rerun');
                        // If a child is in error, we stop the whole processus : child document not saved (roolback) and parent document in error checking
                        if (!empty($errors)) {
                            // The error should be clear because the child document won't be saved
                            throw new \Exception('Child document in error (rule '.$childRuleId['field_id'].')  : '.$errors[0].' The child document has not be saved. Check the log (app/logs/'.$this->parameterBagInterface->get('kernel.environment').'.log) for more information. ');
                        }
                    }
                }
            }
        }

        return true;
    }

    // Vérifie si les données sont différente entre ce qu'il y a dans la cible et ce qui devrait être envoyé
    protected function checkNoChange($history): bool
    {
        try {
            // Get target data
            $target = $this->getDocumentData('T');

            // get history from the database if it isn't in the input parameter
            if (empty($history)) {
                // Get data in the target solution (if exists) before we update it
                $history = $this->getDocumentData('H');
            }

            // No comparaison if history is empty
            if (empty($history)) {
                return false;
            }
            // We don't compare field Myddleware_element_id as it can't exist in the history data (always empty if it exists)
            // This field can only exist in target data as it is created by Myddleware
            if (!empty($target['Myddleware_element_id'])) {
                $target['Myddleware_element_id'] = '';
            }

            // For each target fields, we compare the data we want to send and the data already in the target solution
            // If one is different we stop the function
            if (!empty($this->ruleFields)) {
                foreach ($this->ruleFields as $field) {
                    if (stripslashes(trim($history[$field['target_field_name']])) != stripslashes(trim($target[$field['target_field_name']]))) {
                        // Null text is considered as empty for comparaison
						if ($target[$field['target_field_name']] == 'null') {
							$target[$field['target_field_name']] = '';
						}
						// We check if both are empty not depending of the type 0 = ""
                        if (
                                empty($history[$field['target_field_name']])
                            and empty($target[$field['target_field_name']])
                        ) {
                            continue;
                        }
						// In case of date with different format (2024-03-14T11:04:16+00:00 == 2024-03-14T12:04:16+01:00)
						if (
								!empty(strtotime($history[$field['target_field_name']]))
							AND !empty(strtotime($target[$field['target_field_name']]))
							AND strtotime($history[$field['target_field_name']]) == strtotime($target[$field['target_field_name']])
						) {
							continue;
						}
                        return false;
                    }
                }
            }

            // We check relationship fields as well
            if (!empty($this->ruleRelationships)) {
                foreach ($this->ruleRelationships as $ruleRelationship) {
                    if (
                            'Myddleware_element_id' != $ruleRelationship['field_name_target']	// No check change on field Myddleware_element_id
                        and $history[$ruleRelationship['field_name_target']] != $target[$ruleRelationship['field_name_target']]
                    ) {
                        return false;
                    }
                }
            }
            // If all fields are equal, no need to update, so we cancel the document
            $this->message .= 'Identical data to the target system. This document is canceled. ';
            $this->typeError = 'W';
            $this->updateStatus('No_send');

            return true;
        } catch (\Exception $e) {
            // If something wrong happen (e.g. a field isn't set) the we return false
            return false;
        }
    }

    // Récupération des données dans la cible et sauvegarde dans la table d'historique
    protected function getDocumentHistory($searchFields)
    {
        // Permet de renseigner le tableau rule avec les données d'entête
        $rule = $this->getRule();
        $read['module'] = $rule['module_target'];
        // Get all fields for document type D (delete) to backup the whole record before delete it
        ('D' == $this->documentType ? $all = true : $all = false);
        $read['fields'] = $this->getTargetFields($all);
        $read['query'] = $searchFields;
        $read['ruleParams'] = $this->ruleParams;
        $read['rule'] = $rule;
        $read['call_type'] = 'history';
        $read['date_ref'] = '1970-01-01 00:00:00'; // Required field but no needed for history search
        $read['document']['type'] = $this->documentType;
        $read['document']['id'] = $this->id;
        $read['jobId'] = $this->jobId;
        $dataTarget = $this->solutionTarget->readData($read);
        // If read method returns no result with no error

        if (
                empty($dataTarget['values'])
            and empty($dataTarget['error'])
        ) {
            return false;
        }
        // If read method returns an error
        elseif (!empty($dataTarget['error'])) {
            $this->message .= $dataTarget['error'];

            return -1;
        }
        // If read method returns a result
        else {
            // Select the first result
            $record = current($dataTarget['values']);
            $updateHistory = $this->updateHistoryTable($record);
            if (true === $updateHistory) {
                return $record;
            }
            // Erreur dans la mise à jour de la table historique
            else {
                $this->message .= $dataTarget['error'];

                return -1;
            }
        }
    }

    // Permet de charger les données du système source pour ce document
    protected function getDocumentData($type)
    {
        try {
            $documentDataEntity = $this->entityManager
                // ->getRepository('RegleBundle:DocumentData')
                ->getRepository(DocumentData::class)
                ->findOneBy(
                    [
                        'doc_id' => $this->id,
                        'type' => $type,
                    ]
                );
            // Generate data array
            if (!empty($documentDataEntity)) {
                return json_decode($documentDataEntity->getData(), true);
            }
        } catch (\Exception $e) {
            $this->message .= 'Error getSourceData  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->logger->error($this->message);
        }
        return false;
    }

    // Insert source data in table documentData
    protected function insertDataTable($data, $type): bool
    {
        try {
            // We retrieve all the target fields (not just the rule flieds) before deleting data to the target solution to create a backup
            if (
                    'D' == $this->documentType
                and 'H' == $type
            ) {
                // Get all module fields
                $targetFields = $this->getTargetFields(true);
                // Format these target fields
                if (!empty($targetFields)) {
                    foreach ($targetFields as $targetField) {
                        $fields[] = ['target_field_name' => $targetField];
                    }
                }
            } else {
                $fields = $this->ruleFields;
            }

            // We save only fields which belong to the rule
            if (!empty($fields)) {
                foreach ($fields as $ruleField) {
                    if ('S' == $type) {
                        // We don't create entry in the array dataInsert when the filed is my_value because there is no filed in the source, just a formula to the target application
                        if ('my_value' == $ruleField['source_field_name']) {
                            continue;
                        }
                        // It could be several fields in the source fields (in case of formula)
                        $sourceFields = explode(';', $ruleField['source_field_name']);
                        foreach ($sourceFields as $sourceField) {
                            // if Myddleware_element_id is present, we transform it into id
                            if ('Myddleware_element_id' == $sourceField) {
                                $sourceField = 'id';
                            }
                            $dataInsert[$sourceField] = $data[$sourceField];
                        }
                    } else {
                        // Some field can't be retrived from the target application (history). For example the field password on the module user of Moodle
                        if (
                                !array_key_exists($ruleField['target_field_name'], $data)
                            and 'H' == $type
                        ) {
                            continue;
                        }
                        // foreach field of $this->notSentFields, we remove it from the data to send
                        if (
                                !empty($this->notSentFields)
                            and in_array($ruleField['target_field_name'], $this->notSentFields)
                        ) {
                            continue;
                        }
                        $dataInsert[$ruleField['target_field_name']] = $data[$ruleField['target_field_name']];
                    }
                }
            }

            // We save the relationship field too
            if (!empty($this->ruleRelationships)) {
                foreach ($this->ruleRelationships as $ruleRelationship) {
                    // if field = Myddleware_element_id then we take the id record in the osurce application
                    if ('S' == $type) {
                        $dataInsert[$ruleRelationship['field_name_source']] = ('Myddleware_element_id' == $ruleRelationship['field_name_source'] ? $data['id'] : (!empty($data[$ruleRelationship['field_name_source']]) ? $data[$ruleRelationship['field_name_source']] : ''));
                    } else {
                        $dataInsert[$ruleRelationship['field_name_target']] = (!empty($data[$ruleRelationship['field_name_target']]) ? $data[$ruleRelationship['field_name_target']] : '');
                    }
                }
            }
            $documentEntity = $this->entityManager
                                    ->getRepository(Document::class)
                                    ->find($this->id);
            $documentData = new DocumentDataEntity();
            $documentData->setDocId($documentEntity);
            $documentData->setType($type); // Source
            $documentData->setData(json_encode($dataInsert)); // Encode in JSON
            $this->entityManager->persist($documentData);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->message .= 'Failed : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->logger->error($this->message);

            return false;
        }

        return true;
    }

    // Mise à jour de la table des données source
    protected function updateHistoryTable($dataTarget): bool
    {
        if (!empty($dataTarget)) {
            try {
                if (!$this->insertDataTable($dataTarget, 'H')) {
                    throw new \Exception('Failed insert target data in the table DocumentData.');
                }

                return true;
            } catch (Exception $e) {
                $this->message .= 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->typeError = 'E';
                $this->logger->error($this->message);
            }
        }

        return false;
    }

    // Mise à jour de la table des données cibles
    protected function updateTargetTable()
    {
        try {
            // Loop on every target field and calculate the value
            if (!empty($this->ruleFields)) {
                foreach ($this->ruleFields as $ruleField) {
                    $value = $this->getTransformValue($this->sourceData, $ruleField);
                    if (!empty($this->transformError)) {
                        throw new \Exception('Failed to transform the field '.$ruleField['target_field_name'].'.');
                    }
                    $targetField[$ruleField['target_field_name']] = $value;
					// If the target value equals mdw_no_send_field, the field isn't sent to the target
                    if ($value === "mdw_no_send_field") {
                        unset($targetField[$ruleField['target_field_name']]);
                        $this->notSentFields[] = $ruleField['target_field_name'];
                    }
                }
            }
            // Loop on every relationship and calculate the value
            if (isset($this->ruleRelationships)) {
                // Récupération de l'ID target
                foreach ($this->ruleRelationships as $ruleRelationship) {
                    $value = $this->getTransformValue($this->sourceData, $ruleRelationship);
                    if (!empty($this->transformError)) {
                        if (empty($ruleRelationship['errorMissing'])) {
                            $this->message .= 'No value found for the target field '.$ruleRelationship['field_name_target'].' because "Error if missing" is set to false.';
                            $this->typeError = 'W';
                        } else {
                            throw new \Exception('Failed to transform relationship data.');
                        }
                    }
                    $targetField[$ruleRelationship['field_name_target']] = $value;
                }
            }
            if (!empty($targetField)) {
                if (!$this->insertDataTable($targetField, 'T')) {
                    throw new \Exception('Failed insert target data in the table DocumentData.');
                }
            } else {
                throw new \Exception('No target data found. Failed to create target data. ');
            }

            return $targetField;
        } catch (Exception $e) {
            $this->message .= 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->logger->error($this->message);
        }

        return null;
    }

    /*
    Fonction permettant de renvoyer la valeur du champ cible en fonction des données sources et de la règle (relation, formule)
    En entrée, 2 tableaux sont attendus
        => Les données source $source, par exemple :
        Array
        (
            [id_test_account_001_source] => 5352886318db0  	: champ non nécessaire en mode test sur le front office
            [name] => test sfaure 003
            [email1] => 003@test.test
        )
        => La définition du champ cible (avec formule et relation) $RuleField, par exemple :
        Array
        (
            [id] => 52 								: champ non nécessaire en mode test sur le front office
            [rule_id] => 53500e0bf2d06 						: champ non nécessaire en mode test sur le front office
            [target_field_name] => name
            [source_field_name] => name
            [formula] =>
            [related_rule] =>
        )
    En sortie la fonction renvoie la valeur du champ à envoyer dans le cible
     */
    public function getTransformValue($source, $ruleField)
    {
        try {
            //--
            if (!empty($ruleField['formula'])) {
                // -- -- -- Formula management

                // Build variables
                $r = explode(';', $ruleField['source_field_name']);
                if (count($r) > 1) {
                    foreach ($r as $listFields) {
                        // We skip my_value because it is a constant
                        if ('my_value' != $listFields) {
                            $fieldNameDyn = $listFields; // value : variable name
                            if (array_key_exists($listFields, $source)) {
                                $$fieldNameDyn = (!empty($source[$listFields]) ? $source[$listFields] : ''); // Dynamic variable (e.g $name = name)
                            } else {
                                // Erreur
                                throw new \Exception('The field '.$listFields.' is unknow in the formula '.$ruleField['formula'].'. ');
                            }
                        }
                    }
                } else {
                    // We skip my_value because it is a constante
                    if ('my_value' != $ruleField['source_field_name']) {
                        $fieldNameDyn = $ruleField['source_field_name']; // value : variable name
                        $$fieldNameDyn = $source[$ruleField['source_field_name']]; // Dynamic variable (e.g $name = name)
                    }
                }
                // préparation des variables
                $this->formulaManager->init($ruleField['formula']); // mise en place de la règle dans la classe
                $this->formulaManager->generateFormule(); // Genère la nouvelle formule à la forme PhP

                // Exécute la règle si pas d'erreur de syntaxe
                if (
                        $f = $this->formulaManager->execFormule()
                ) {
                    // Try the formula first
                    try {
                        // Trigger to redefine formula
                        $f = $this->changeFormula($f);
                        eval($f.';'); // exec
                    } catch (\ParseError $e) {
                        throw new \Exception('FATAL error because of Invalid formula "'.$ruleField['formula'].';" : '.$e->getMessage());
                    }
                    // Execute eval only if formula is valid
                    eval('$rFormula = '.$f.';'); // exec
                    if (isset($rFormula)) {
                        // affectation du résultat
                        return $rFormula;
                    } else {
                        throw new \Exception('Invalid formula (failed to retrieve formula) : '.$ruleField['formula']);
                    }
                } else {
                    throw new \Exception('Invalid formula (failed to execute) : '.$ruleField['formula']);
                }
                // -- -- -- Gestion des formules
            }
            // S'il s'agit d'un champ relation
            elseif (!empty($ruleField['field_id'])) {
                // Si l'id est vide on renvoie vide
                if (empty(trim($source[$ruleField['field_name_source']]))) {
                    return null;
                }

                // If the relationship is a parent type, we don't search the id in the child rule now. Data will be read from the child rule when we will send the parent document. So no target id is required now.
                if (!empty($ruleField['parent'])) {
                    return null;
                }

                // Récupération de l'ID de l'enregistrement lié dans la cible avec l'id correspondant dans la source et la correspondance existante dans la règle liée.
                $targetId = $this->getTargetId($ruleField, $source[$ruleField['field_name_source']]);
                if (!empty($targetId['record_id'])) {
                    return $targetId['record_id'];
                // No need of relate field in case of deletion
                } elseif ('D' != $this->documentType) {
                    throw new \Exception('Target id not found for id source '.$source[$ruleField['field_name_source']].' of the rule '.$ruleField['field_id']);
                } else {
                    return null;
                }
            }
            // Si le champ est envoyé sans transformation
            elseif (isset($source[$ruleField['source_field_name']])) {
                return $this->checkField($source[$ruleField['source_field_name']]);
            }
            // If Myddleware_element_id is requested, we return the id
            elseif (
                        'Myddleware_element_id' == $ruleField['source_field_name']
                    and isset($source['id'])
            ) {
                return $this->checkField($source['id']);
            } elseif (is_null($source[$ruleField['source_field_name']])) {
                return null;
            } else {
                throw new \Exception('Field '.$ruleField['source_field_name'].' not found in source data.------'.print_r($ruleField, true));
            }
        } catch (\Exception $e) {
            $this->typeError = 'E';
            $this->message .= 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($this->message);
            // Set the error to true. We can't set a specific value in the return because this function could return any value (even false depending the formula)
            $this->transformError = true;

            return null;
        }
    }

    // Trigger to be able to redefine formula
    protected function changeFormula($f)
    {
        return $f;
    }

    // Fonction permettant de contrôle les données.
    protected function checkField($value)
    {
        if (isset($value)) {
            return $value;
        }

        return null;
    }

    // Permet de récupérer les données d'entête de la règle
    protected function getRule()
    {
        try {
            if (!empty($this->ruleId)) {
                $rule = 'SELECT * FROM rule WHERE id = :ruleId';
                $stmt = $this->connection->prepare($rule);
                $stmt->bindValue(':ruleId', $this->ruleId);
                $result = $stmt->executeQuery();

                return $result->fetchAssociative();
            }
        } catch (\Exception $e) {
            $this->typeError = 'E';
            $this->message .= 'Error getRule  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($this->message);
        }

        return null;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     *                                  Check if the document is a child
     */
    public function isChild(): bool
    {
        $sqlIsChild = '	SELECT rule.id 
									FROM rulerelationship 
										INNER JOIN rule
											ON rule.id  = rulerelationship.rule_id 
									WHERE 
											rulerelationship.field_id = :ruleId
										AND rulerelationship.parent = 1
										AND rule.deleted = 0
								';
        $stmt = $this->connection->prepare($sqlIsChild);
        $stmt->bindValue(':ruleId', $this->ruleId);
        $result = $stmt->executeQuery();
        $isChild = $result->fetchAssociative(); // 1 row
        if (!empty($isChild)) {
            return true;
        }

        return false;
    }

    // Check if the document is a child
    protected function getChildDocuments()
    {
        try {
            $sqlGetChildren = 'SELECT * FROM document WHERE parent_id = :docId AND deleted = 0 ';
            $stmt = $this->connection->prepare($sqlGetChildren);
            $stmt->bindValue(':docId', $this->id);
            $result = $stmt->executeQuery();

            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->typeError = 'E';
            $this->message .= 'Error getTargetFields  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($this->message);
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     *                                  Check if the document is a parent
     */
    protected function isParent(): bool
    {
        $sqlIsChild = '	SELECT rulerelationship.rule_id 
							FROM rulerelationship 				
							WHERE 
									rulerelationship.rule_id = :ruleId
								AND rulerelationship.parent = 1
								';
        $stmt = $this->connection->prepare($sqlIsChild);
        $stmt->bindValue(':ruleId', $this->ruleId);
        $result = $stmt->executeQuery();
        $isChild = $result->fetchAssociative(); // 1 row
        if (!empty($isChild)) {
            return true;
        }

        return false;
    }

    // Permet de récupérer les champs de la cible
    protected function getTargetFields($all = false)
    {
        try {
            // if all fields are requested
            if ($all) {
                $rule = $this->getRule();
                $targetFields = $this->solutionTarget->get_module_fields($rule['module_target']);
                if (!empty($targetFields)) {
                    foreach ($targetFields as $fieldname => $value) {
                        $fields[] = $fieldname;
                    }
                }
            } elseif (!empty($this->ruleId)) {
                $rule = 'SELECT * FROM rulefield WHERE rule_id = :ruleId';
                $stmt = $this->connection->prepare($rule);
                $stmt->bindValue(':ruleId', $this->ruleId);
                $result = $stmt->executeQuery();
                $ruleFields = $result->fetchAllAssociative();
                foreach ($ruleFields as $ruleField) {
                    $fields[] = $ruleField['target_field_name'];
                }

                // Ajout des champs de relation s'il y en a
                $rule = 'SELECT * FROM rulerelationship WHERE rule_id = :ruleId';
                $stmt = $this->connection->prepare($rule);
                $stmt->bindValue(':ruleId', $this->ruleId);
                $result = $stmt->executeQuery();
                $ruleRelationShips = $result->fetchAllAssociative();
                if (!empty($ruleRelationShips)) {
                    foreach ($ruleRelationShips as $ruleRelationShip) {
                        // If it is a normal relationship we take the target field
                        // but if it is a parent relationship we have to take the source field in the relation (wich corresponding to the target field)
                        if (empty($ruleRelationShip['parent'])) {
                            $fields[] = $ruleRelationShip['field_name_target'];
                        } else {
                            $fields[] = $ruleRelationShip['field_name_source'];
                        }
                    }
                }
            }
            // We don't need the field Myddleware_element_id as it is the id of the current record
            if (!empty($fields)) {
                $key = array_search('Myddleware_element_id', $fields);
                if (false !== $key) {
                    unset($fields[$key]);
                }

                return $fields;
            }

            return null;
        } catch (\Exception $e) {
            $this->typeError = 'E';
            $this->message .= 'Error getTargetFields  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($this->message);
        }
    }

    // Permet de charger tous les paramètres de la règle
    public function setRuleParam()
    {
        try {
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

    // Permet de déterminer le type de document (Create ou Update)
    // En entrée : l'id de l'enregistrement source
    // En sortie : le type de docuement (C ou U)
    protected function checkRecordExist($id): ?string
    {
        try {
            // Query used in the method several times
            // Sort : targetOrder to get the target id non empty first; on global_status to get Cancel last
            // We dont take cancel document excpet if it is a no_send document (data really exists in this case)
            // Then we take the last document created to know if the last action sent was a deletion
            $sqlParamsSource = "	SELECT 
								document.id, 
								document.target_id, 
								document.type, 
								document.global_status,
								if(document.target_id = '', 0, 1) targetOrder
							FROM document 
							WHERE 
									document.rule_id IN (:ruleId)	
								AND (
										document.global_status = 'Close'
									 OR (
											document.global_status = 'Cancel'	
										AND document.status = 'No_send'
									)
								)
								AND	document.source_id = :id
								AND document.id != :id_doc
								AND document.deleted = 0 
							ORDER BY targetOrder DESC, global_status DESC, date_modified DESC
							LIMIT 1";

            // On prépare la requête pour rechercher dans la partie target
            $sqlParamsTarget = "SELECT 
								document.id, 
								document.source_id target_id, 
								document.type,
								document.global_status,
								if(document.target_id = '', 0, 1) targetOrder
							FROM document 
							WHERE 
									document.rule_id IN (:ruleId)	
								AND (
										document.global_status = 'Close'
									 OR (
											document.global_status = 'Cancel'	
										AND document.status = 'No_send'
									)
								)
								AND	document.target_id = :id
								AND document.id != :id_doc
								AND document.deleted = 0 
							ORDER BY targetOrder DESC, global_status DESC, date_modified DESC
							LIMIT 1";

            // Si une relation avec le champ Myddleware_element_id est présente alors on passe en update et on change l'id source en prenant l'id de la relation
            // En effet ce champ indique que l'on va modifié un enregistrement créé par une autre règle
            if (!empty($this->ruleRelationships)) {
                // Boucle sur les relation
                foreach ($this->ruleRelationships as $ruleRelationship) {
                    // If the relationship target is Myddleware element id and if the rule relate isn't a child (we don't get target id or define type of a document with a child rule)
                    if (
                            'Myddleware_element_id' == $ruleRelationship['field_name_target']
                        and empty($ruleRelationship['parent'])
                    ) {
                        // Si le champs avec l'id source n'est pas vide
                        // S'il s'agit de Myddleware_element_id on teste id
                        if (
                                !empty($this->data[$ruleRelationship['field_name_source']])
                             || (
                                    'Myddleware_element_id' == $ruleRelationship['field_name_source']
                                && !empty($this->data['id'])
                             )
                        ) {
                            // On recherche l'id target dans la règle liée
                            $this->sourceId = ('Myddleware_element_id' == $ruleRelationship['field_name_source'] ? $this->data['id'] : $this->data[$ruleRelationship['field_name_source']]);
                            // On récupère la direction de la relation pour rechercher dans le target id ou dans le source id
                            $direction = $this->getRelationshipDirection($ruleRelationship);
                            if ('-1' == $direction) {
                                $stmt = $this->connection->prepare($sqlParamsTarget);
                            } else {
                                $stmt = $this->connection->prepare($sqlParamsSource);
                            }
                            $stmt->bindValue(':ruleId', $ruleRelationship['field_id']);
                            $stmt->bindValue(':id', $this->sourceId);
                            $stmt->bindValue(':id_doc', $this->id);
                            $result = $stmt->executeQuery();
                            $result = $result->fetchAssociative();
                            // Si on trouve la target dans la règle liée alors on passe le doc en UPDATE (the target id can be found even if the relationship is a parent (if we update data), but it isn't required)
                            if (!empty($result['target_id'])) {
                                $this->targetId = $result['target_id'];

                                return 'U';
                            }
                            // Sinon on bloque la création du document
                            // Except if the rule is parent, no need of target_id, the target id will be retrived when we will send the data
                            elseif (empty($ruleRelationship['parent'])) {
                                $this->message .= 'Failed to get the id target of the current module in the rule linked.';
                            }
                            // If the document found is Cancel, there is only Cancel documents (see query order) so we return C and not U
                            if (
                                    empty($result['id'])
                                 || 'Cancel' == $result['global_status']
                            ) {
                                return 'C';
                            } else {
                                return 'U';
                            }
                        } else {
                            throw new \Exception('The field '.$ruleRelationship['field_name_source'].' used in the relationship is empty.');
                        }
                    }
                }
            }
            // A mass process exist for migration mode
            if (!empty($this->ruleDocuments[$this->ruleId])) {
                // If a least one record is already existing, we test if it was successfully sent
                if (!empty($this->ruleDocuments[$this->ruleId]['sourceId'][$id])) {
                    foreach ($this->ruleDocuments[$this->ruleId]['sourceId'][$id] as $document) {
                        if (
                            (
                                'Cancel' != $document['global_status']
                             or (
                                        'Cancel' == $document['global_status']
                                    and 'No_send' == $document['status']
                                )
                            )
                            and $document['id'] !== $this->id
                        ) {
                            // Si on trouve la target dans la règle liée alors on passe le doc en UPDATE (the target id can be found even if the relationship is a parent (if we update data), but it isn't required)
                            if (!empty($document['target_id'])) {
                                $this->targetId = $document['target_id'];

                                return 'U';
                            }
                            // If the document found is Cancel, there is only Cancel documents (see query order) so we return C and not U
                            if (
                                    empty($result['id'])
                                 || 'Cancel' == $result['global_status']
                            ) {
                                return 'C';
                            } else {
                                return 'U';
                            }
                        }
                    }
                }
            } else {
                // If no relationship or no child rule
                // Recherche d'un enregitsrement avec un target id sur la même source
                $stmt = $this->connection->prepare($sqlParamsSource);
                $stmt->bindValue(':ruleId', $this->ruleId);
                $stmt->bindValue(':id', $id);
                $stmt->bindValue(':id_doc', $this->id);
                $result = $stmt->executeQuery();
                $result = $result->fetchAssociative();
            }

            // Si on n'a pas trouvé de résultat et que la règle à une équivalente inverse (règle bidirectionnelle)
            // Alors on recherche dans la règle opposée
            if (
                    empty($result['id'])
                && !empty($this->ruleParams['bidirectional'])
            ) {
                $stmt = $this->connection->prepare($sqlParamsTarget);
                $stmt->bindValue(':ruleId', $this->ruleParams['bidirectional']);
                $stmt->bindValue(':id', $id);
                $stmt->bindValue(':id_doc', $this->id);
                $result = $stmt->executeQuery();
                $result = $result->fetchAssociative();
            }

            // If we found a record
            if (!empty($result['id'])) {
                $this->targetId = $result['target_id'];
                // If the document found is Cancel, there is only Cancel documents (see query order) so we return C and not U
                // Except if the rule is bidirectional, in this case, a no send document in the opposite rule means that the data really exists in the target application
                // OR if the last document sent is a deletion, we will create a new record because the record doesn't exist anymore in the target application
                if (
                        'D' == $result['type']
                     or (
                            'Cancel' == $result['global_status']
                        && empty($this->ruleParams['bidirectional'])
                    )
                ) {
                    return 'C';
                } else {
                    return 'U';
                }
            }
            // Si on est sur une règle child alors on est focément en update (seule la règle root est autorisée à créer des données)
            // We check now because we take every chance we can to get the target_id
            if ($this->isChild()) {
                return 'U';
            }
            // Si aucune règle avec relation Myddleware_element_id alors on est en création
            return 'C';
        } catch (\Exception $e) {
            $this->typeError = 'E';
            $this->message .= 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($this->message);

            return null;
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function documentCancel()
    {
        // Search if the document has child documents
        $childDocuments = $this->getChildDocuments();
        if (!empty($childDocuments)) {
            // We cancel each child, but a child document can be a parent document too, so we make a recursive call
            foreach ($childDocuments as $childDocument) {
                // We don't Cancel a document if it has been already cancelled
                if ('Cancel' != $childDocument['global_status']) {
                    $param['id_doc_myddleware'] = $childDocument['id'];
                    $param['jobId'] = $this->jobId;
                    $docChild = clone $this;
                    $docChild->setParam($param, true);
                    $docChild->documentCancel();
                }
            }
        }
        $this->updateStatus('Cancel');
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function changeDeleteFlag($deleteFlag)
    {
        $this->updateDeleteFlag($deleteFlag);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateStatus($new_status)
    {
        try {
            // On ajoute un contôle dans le cas on voudrait changer le statut
            $new_status = $this->beforeStatusChange($new_status);

            $now = gmdate('Y-m-d H:i:s');
            // Récupération du statut global
            $globalStatus = $this->globalStatus[$new_status];
            // Ajout d'un essai si erreur
            if ('Error' == $globalStatus || 'Close' == $globalStatus) {
                ++$this->attempt;
            }
            $query = '	UPDATE document 
								SET 
									date_modified = :now,
									global_status = :globalStatus,
									attempt = :attempt,
									status = :new_status,
									job_lock = :jobLock
								WHERE
									id = :id
								';
            // We don't send output for the API and Myddleware UI
			if (
					!$this->api
				AND $this->env == 'background'
			) {
                echo 'status '.$new_status.' id = '.$this->id.'  '.$now.chr(10);
            }
            $stmt = $this->connection->prepare($query);
            $stmt->bindValue(':now', $now);
            $stmt->bindValue(':globalStatus', $globalStatus);
            $stmt->bindValue(':attempt', $this->attempt);
            $stmt->bindValue(':new_status', $new_status);
            $stmt->bindValue(':id', $this->id);
			// Remove the lock on the document in the class and in the database
            $this->jobLock = '';
            $stmt->bindValue(':jobLock', $this->jobLock);
            $result = $stmt->executeQuery();
            $this->message .= 'Status : '.$new_status;
            $this->status = $new_status;
            $this->afterStatusChange($new_status);
            $this->createDocLog();
			$this->runWorkflow();
        } catch (\Exception $e) {
            $this->message .= 'Error status update : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->logger->error($this->message);
            $this->createDocLog();
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateDeleteFlag($deleted)
    {
        try {
            $now = gmdate('Y-m-d H:i:s');
            $query = '	UPDATE document 
								SET 
									date_modified = :now,
									deleted = :deleted
								WHERE
									id = :id
								';
            // We don't send output for the API and Myddleware UI
			if (
					!$this->api
				AND $this->env == 'background'
			) {
                echo(!empty($deleted) ? 'Remove' : 'Restore').' document id = '.$this->id.'  '.$now.chr(10);
            }
            $stmt = $this->connection->prepare($query);
            $stmt->bindValue(':now', $now);
            $stmt->bindValue(':deleted', $deleted);
            $stmt->bindValue(':id', $this->id);
            $result = $stmt->executeQuery();
            $this->message .= (!empty($deleted) ? 'Remove' : 'Restore').' document';
            $this->createDocLog();
        } catch (\Exception $e) {
            $this->message .= 'Failed to '.(!empty($deleted) ? 'Remove ' : 'Restore ').' : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->logger->error($this->message);
            $this->createDocLog();
        }
    }

    // Save document relationship
    protected function insertDocumentRelationship($ruleRelationship, $docRelId)
    {
        try {
            // Add the relationship in the table document Relationship
            $documentRelationship = new DocumentRelationship();
            $documentRelationship->setDocId($this->id);
            $documentRelationship->setDocRelId($docRelId);
            $documentRelationship->setDateCreated(new \DateTime());
            $documentRelationship->setCreatedBy((int) $this->userId);
            $documentRelationship->setSourceField($ruleRelationship['field_name_source']);
            $this->entityManager->persist($documentRelationship);
        } catch (\Exception $e) {
            $this->message .= 'Failed to save the document relationship for the field '.$ruleRelationship['field_name_source'].' : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'W';
            $this->logger->error($this->message);

            return false;
        }
    }

    // Permet d'intervenir avant le changement de statut
    protected function beforeStatusChange($new_status)
    {
        return $new_status;
    }

    // Permet d'intervenir après le changement de statut
    protected function afterStatusChange($new_status)
    {
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateType($new_type)
    {
        try {
            $now = gmdate('Y-m-d H:i:s');
            $query = '	UPDATE document 
								SET 
									date_modified = :now,
									type = :new_type
								WHERE
									id = :id
								';
            // Suppression de la dernière virgule
            $stmt = $this->connection->prepare($query);
            $stmt->bindValue(':now', $now);
            $stmt->bindValue(':new_type', $new_type);
            $stmt->bindValue(':id', $this->id);
            $result = $stmt->executeQuery();
            $this->message .= 'Type  : '.$new_type;
            $this->createDocLog();
            // Change the document type for the current process
            $this->documentType = $new_type;
        } catch (\Exception $e) {
            $this->message .= 'Error type   : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->logger->error($this->message);
            $this->createDocLog();
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateTargetId($target_id): bool
    {
        try {
            $now = gmdate('Y-m-d H:i:s');
            $query = '	UPDATE document 
								SET 
									date_modified = :now,
									target_id = :target_id
								WHERE
									id = :id
								';
            // Suppression de la dernière virgule
            $stmt = $this->connection->prepare($query);
            $stmt->bindValue(':now', $now);
            // Target id could contain accent
            $stmt->bindValue(':target_id', utf8_encode($target_id));
            $stmt->bindValue(':id', $this->id);
            $result = $stmt->executeQuery();
            $this->message .= 'Target id : '.$target_id;
            $this->createDocLog();
            // Change the target id for the current process
            $this->targetId = $target_id;

            return true;
        } catch (\Exception $e) {
            $this->message .= 'Error target id  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->logger->error($this->message);
            $this->createDocLog();

            return false;
        }
    }

    // Function to manually edit the data inside a Myddleware Document
    public function updateDocumentData(string $docId, array $newValues, string $dataType, bool $refreshData = false)
    {
        // check if data of that type with this docid and this data fields
        if (empty($docId)) {
            throw new Exception("No document id provided");
        }
        if (empty($newValues)) {
            throw new Exception("No data provided");
        }
        if (empty($dataType)) {
            throw new Exception("No data type provided");
        }
        if (
            $dataType !== 'S'
            & $dataType !== 'T'
            & $dataType !== 'H'
        ) {
            throw new Exception("This is not the correct data type. Source, Target, or History is required");
        }

        // Get the document data corresponding on the type in input
        $documentDataEntity = $this->entityManager
                            ->getRepository(DocumentData::class)
                            ->findOneBy([
                                        'doc_id' => $docId,
                                        'type' => $dataType,
                                        ]
                                );
        if (empty($documentDataEntity)) {
            throw new Exception("No document data found for the document ".$docId." and the type ".$dataType.".");
        }
        // Compare data                        
        $oldData = json_decode($documentDataEntity->getData());
        if(!empty($oldData)){
			if (!$refreshData) {
				foreach ($newValues as $key => $Value) {
					foreach ($oldData as $oldKey => $oldValue) {
						if ($oldKey === $key) {
							if ($oldValue !== $Value) {
								$newValues[$oldKey] = $Value;
								$this->message .= ($dataType == 'S' ? 'Source' : ($dataType == 'T' ? 'Target' : 'History')).' document value changed  from  '.$oldValue.' to '.$Value.'. ';
							}
						} else {
							$newValues[$oldKey] = $oldValue;
						}
					}
				}
            } else {
				$this->message .= ($dataType == 'S' ? 'Source' : ($dataType == 'T' ? 'Target' : 'History')).' document value changed by '.print_r($newValues,true).'. ';
			}
            $this->typeError = 'I';
            $this->createDocLog();
            // Update the data of the right type
            $documentDataEntity->setData(json_encode($newValues, true));
            $this->entityManager->persist($documentDataEntity);
            $this->entityManager->flush();
        }
    }

    protected function getRelationshipDirection($ruleRelationship)
    {
        try {
            // Calcul du sens de la relation. Si on ne trouve pas (exemple des relations custom) alors on met 1 par défaut.
            $sqlParams = "	SELECT 
								IF(rulea.conn_id_source = ruleb.conn_id_source, '1', IF(rulea.conn_id_source = ruleb.conn_id_target, '-1', '1')) direction
							FROM rulerelationship
								INNER JOIN rule rulea
									ON rulerelationship.rule_id = rulea.id
									#AND RuleA.deleted = 0
								INNER JOIN rule ruleb
									ON rulerelationship.field_id = ruleb.id		
									#AND RuleB.deleted = 0
							WHERE  
								rulerelationship.id = :id 
						";
            $stmt = $this->connection->prepare($sqlParams);
            $stmt->bindValue(':id', $ruleRelationship['id']);
            $result = $stmt->executeQuery();
            $result = $result->fetchAssociative();
            if (!empty($result['direction'])) {
                return $result['direction'];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // Permet de récupérer l'id target pour une règle et un id source ou l'inverse
    protected function getTargetId($ruleRelationship, $record_id)
    {
        try {
            $direction = $this->getRelationshipDirection($ruleRelationship);

            // En fonction du sens de la relation, la recherche du parent id peut-être inversée (recherchée en source ou en cible)
            // Search all documents with target ID not empty in status close or no_send (document canceled but it is a real document)
            if ('-1' == $direction) {
                $sqlParams = "	SELECT 
									source_id record_id,
									GROUP_CONCAT(DISTINCT document.id) document_id,
									GROUP_CONCAT(DISTINCT document.type) types
								FROM document
								WHERE  
										document.rule_id = :ruleRelateId
									AND document.source_id != ''
									AND document.deleted = 0
									AND document.target_id = :record_id
									AND (
											document.global_status = 'Close'
										 OR document.status = 'No_send'
									)
								GROUP BY source_id
								HAVING types NOT LIKE '%D%'
								LIMIT 1";
            } elseif ('1' == $direction) {
                $sqlParams = "	SELECT 
									target_id record_id,
									GROUP_CONCAT(DISTINCT document.id) document_id,
									GROUP_CONCAT(DISTINCT document.type) types
								FROM document 
								WHERE  
										document.rule_id = :ruleRelateId
									AND document.source_id = :record_id
									AND document.deleted = 0
									AND document.target_id != ''
									AND (
											document.global_status = 'Close'
										 OR document.status = 'No_send'
									)
								GROUP BY target_id
								HAVING types NOT LIKE '%D%'
								LIMIT 1";
            } else {
                throw new \Exception('Failed to find the direction of the relationship with the rule_id '.$ruleRelationship['field_id'].'. ');
            }

            // A mass process exist for migration mode
            if (!empty($this->ruleDocuments[$ruleRelationship['field_id']])) {
                // We search the target/source id in the array in memory
                if ('1' == $direction) {
                    if (!empty($this->ruleDocuments[$ruleRelationship['field_id']]['sourceId'][$record_id])) {
                        foreach ($this->ruleDocuments[$ruleRelationship['field_id']]['sourceId'][$record_id] as $document) {
                            if (
                                (
                                        'Close' == $document['global_status']
                                     or 'No_send' == $document['status']
                                )
                                and '' != $document['target_id']
                            ) {
                                $result['record_id'] = $document['target_id'];
                                $result['document_id'] = $document['id'];
                                break;
                            }
                        }
                    }
                } else {
                    if (!empty($this->ruleDocuments[$ruleRelationship['field_id']]['targetId'][$record_id])) {
                        foreach ($this->ruleDocuments[$ruleRelationship['field_id']]['targetId'][$record_id] as $document) {
                            if (
                                (
                                        'Close' == $document['global_status']
                                     or 'No_send' == $document['status']
                                )
                                and '' != $document['source_id']
                            ) {
                                $result['record_id'] = $document['source_id'];
                                $result['document_id'] = $document['id'];
                                break;
                            }
                        }
                    }
                }
            } else {
                $stmt = $this->connection->prepare($sqlParams);
                $stmt->bindValue(':ruleRelateId', $ruleRelationship['field_id']);
                $stmt->bindValue(':record_id', $record_id);
                $result = $stmt->executeQuery();
                $result = $result->fetchAssociative();

				// In cas of several document found we get only the last one
				if (
						!empty($result['document_id'])
					AND strpos($result['document_id'], ',')
				) {
					$result['document_id'] = end(explode(',',$result['document_id']));
				}
            }
			if (!empty($result['record_id'])) {
                return $result;
            }
            return null;
        } catch (\Exception $e) {
            $this->message .= 'Error getTargetId  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->logger->error($this->message);
        }
    }

    // Search relate document by status
    protected function searchRelateDocumentByStatus($ruleRelationship, $record_id, $status)
    {
        try {
            $direction = $this->getRelationshipDirection($ruleRelationship);
            // En fonction du sens de la relation, la recherche du parent id peut-être inversée (recherchée en source ou en cible)
            // Search all documents with target ID not empty in status close or no_send (document canceled but it is a real document)
            if ('-1' == $direction) {
                $sqlParams = '	SELECT *								
								FROM document
								WHERE  
										document.rule_id = :ruleRelateId 
									AND document.target_id = :record_id 
									AND document.status = :status 
									AND document.deleted = 0 
								LIMIT 1';
            } elseif ('1' == $direction) {
                $sqlParams = '	SELECT *
								FROM document 
								WHERE  
										document.rule_id = :ruleRelateId 
									AND document.source_id = :record_id 
									AND document.status = :status 
									AND document.deleted = 0 
								LIMIT 1';
            } else {
                throw new \Exception('Failed to find the direction of the relationship with the rule_id '.$ruleRelationship['field_id'].'. ');
            }
            $stmt = $this->connection->prepare($sqlParams);
            $stmt->bindValue(':ruleRelateId', $ruleRelationship['field_id']);
            $stmt->bindValue(':record_id', $record_id);
            $stmt->bindValue(':status', $status);
            $result = $stmt->executeQuery();
            $result = $result->fetchAssociative();
            if (!empty($result['id'])) {
                return $result;
            }
        } catch (\Exception $e) {
            $this->message .= 'Error searchRelateDocumentByStatus  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($this->message);
        }

        return null;
    }

    public function getStatus()
    {
        return $this->status;
    }
	
	protected function runWorkflow() {
		try {
			// Check if at least on workflow exist for the rule
			if (!empty($this->ruleWorkflows)) {
				// includ variables used in the formula
				include __DIR__.'/../Utils/workflowVariables.php';
				if (file_exists( __DIR__.'/../Custom/Utils/workflowVariables.php')) {
					include  __DIR__.'/../Custom/Utils/workflowVariables.php';
				}
				// Execute every workflow of the rule
				foreach ($this->ruleWorkflows as $ruleWorkflow) {
					// Check the condition 
					$this->formulaManager->init($ruleWorkflow['condition']); // mise en place de la règle dans la classe
					$this->formulaManager->generateFormule(); // Genère la nouvelle formule à la forme PhP
					$f = $this->formulaManager->execFormule();
					eval('$condition = '.$f.';'); // exec
					// Execute the action if the condition is met
					if ($condition == 1) {
						// Execute all actions 
						if (!empty($ruleWorkflow['actions'])) {
							// Call each actions
							foreach($ruleWorkflow['actions'] as $action) {
								// Check if the action has already been executed for the current document 
								// Only if attempt > 0, if it is the first attempt then the action has never been executed
								if ($this->attempt > 0) {
									// Search action for the current document
									$workflowLogEntity = $this->entityManager->getRepository(WorkflowLog::class)
															->findOneBy([
																		'triggerDocument' => $this->id,
																		'action' => $action['id'],
																		]
																	);
									// If the current action has been found for the current document, we don't execute the current action
									if (
											!empty($workflowLogEntity)
										AND $workflowLogEntity->getStatus() == 'Success'
									) {
										// GenerateDocument can be empty depending the action 
										if (!empty($workflowLogEntity->getGenerateDocument())) {
											$this->docIdRefError = $workflowLogEntity->getGenerateDocument()->getId();
										}
										$this->generateDocLog('W','Action ' . $action['id'] . ' already executed for this document. ');
										continue;
									}
								}

								// Execute action depending of the function in the workflow
								$arguments = unserialize($action['arguments']);
								switch ($action['action']) {
									case 'generateDocument':
										$this->generateDocument($arguments['ruleId'],$this->sourceData[$arguments['searchValue']],$arguments['searchField'],$arguments['rerun'], $action);
										break;
									case 'sendNotification':
										try	{
											$workflowStatus = 'Success';
											$error = '';
											// Method sendMessage throws an exception if it fails
											$this->tools->sendMessage($arguments['to'],$arguments['subject'],$arguments['message']);
										} catch (\Exception $e) {
											$workflowStatus = 'Error';
											$error = 'Failed to create workflow log : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
											$this->logger->error($error);
											$this->generateDocLog('E',$error);
										}
										$this->createWorkflowLog($action, $workflowStatus, $error);
										break;
									default:
									   throw new \Exception('Function '.key($action).' unknown.');
								}
							}
						}
					}
				}
			}
		} catch (\Exception $e) {
            $this->logger->error('Failed to create workflow log : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
			$this->generateDocLog('E','Failed to create workflow log : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
	}

	// Generate a document using the rule id and search parameters
	protected function generateDocument($ruleId, $searchValue = null, $searchField = 'id', $rerun = true, $action = null)
	{
		try {
			// Instantiate the rule
			$rule = new RuleManager($this->logger, $this->connection, $this->entityManager, $this->parameterBagInterface, $this->formulaManager, $this->solutionManager, clone $this);
			$rule->setRule($ruleId);
			$rule->setJobId($this->jobId);

			if (empty($searchValue)) {
				$searchValue = $this->sourceId;
			}

			// Generate the documents depending on the search parameter
			$documents = $rule->generateDocuments($searchValue, true, '', $searchField);
			if (!empty($documents->error)) {
				throw new \Exception($documents->error);
			}
			// Run documents
			if (
				!empty($documents)
				and $rerun
			) {
				foreach ($documents as $doc) {
					$errors = $rule->actionDocument($doc->id, 'rerun');
					// Check errors
					if (!empty($errors)) {
						$this->message .=  'Document ' . $doc->id . ' in error (rule ' . $ruleId . '  : ' . $errors[0] . '. ';
					}
					// Generate the workflow log for each document if it has been generated by a workflow
					if (!empty($action['id'])) {
						$error = '';
						if (!empty($errors)) {
							$error = $this->message; 
							$status = 'Error';
						} else {
							$status = 'Success';
						}
						$this->createWorkflowLog($action, $status, $error, $doc->id);
					}
				}
			}
		} catch (\Exception $e) {
			$this->logger->error('Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
			$this->generateDocLog('E',$this->message);
		}
	}
	
	// Create a workflow log
	protected function createWorkflowLog($action, $status, $error=null, $generateDocumentId=null) {
		try {
			// Generate the workflow log
			$workflowLog = new WorkflowLog();
			// Set the current document
			$triggerDocumentEntity = $this->entityManager->getRepository(Document::class)->find($this->id);
			$workflowLog->setTriggerDocument($triggerDocumentEntity);
			// Set the current action
			$workflowActionEntity = $this->entityManager->getRepository(WorkflowAction::class)->find($action['id']);
			$workflowLog->setAction($workflowActionEntity); 
			// Set the generated document if the action has generated a document
			if (!empty($generateDocumentId)) {
				$generateDocumentEntity = $this->entityManager->getRepository(Document::class)->find($generateDocumentId);
				$this->docIdRefError = $generateDocumentId;
				$workflowLog->setGenerateDocument($generateDocumentEntity);
			}
			// Set the workflow
			$workflowEntity = $this->entityManager->getRepository(Workflow::class)->find($action['workflow_id']);
			$workflowLog->setWorkflow($workflowEntity);
			// Set the job
			$jobEntity = $this->entityManager->getRepository(Job::class)->find($this->jobId);
			$workflowLog->setJob($jobEntity);
			// Set the creation date
			$workflowLog->setDateCreated(new \DateTime());
			// Set the status depending on the error message
			if (!empty($errors)) {
				$workflowLog->setMessage($error); 
				$workflowLog->setStatus($status);
			} else {
				$workflowLog->setStatus('Success');;
			}
			$this->entityManager->persist($workflowLog);
			$this->entityManager->flush();
			// Generate a document log.
			$this->generateDocLog('S','Action '.$action['id'].' executed. '.(!empty($generateDocumentId) ? 'The document '.$generateDocumentId.' has been generated. ' : ''));
		} catch (\Exception $e) {
			$this->logger->error('Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
			$this->generateDocLog('E','Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
		}
	}
	/**
     * @throws \Doctrine\DBAL\Exception
     *                                  Les id de la soluton, de la règle et du document
     *                                  $type peut contenir : I (info;), W(warning), E(erreur), S(succès)
     *                                  $code contient le code de l'erreur
     *                                  $message contient le message de l'erreur avec potentiellement des variable &1, &2...
     *                                  $data contient les varables du message de type array('id_contact', 'nom_contact')
     */
    protected function createDocLog()
    {
        try {
            $now = gmdate('Y-m-d H:i:s');
            $query_header = 'INSERT INTO log (created, type, msg, rule_id, doc_id, ref_doc_id, job_id) VALUES (:created,:typeError,:message,:rule_id,:doc_id,:ref_doc_id,:job_id)';
            $stmt = $this->connection->prepare($query_header);
            $stmt->bindValue(':created', $now);
            $stmt->bindValue(':typeError', $this->typeError);
            $stmt->bindValue(':message', str_replace("'", '', utf8_encode($this->message)));
            $stmt->bindValue(':rule_id', $this->ruleId);
            $stmt->bindValue(':doc_id', $this->id);
            $stmt->bindValue(':ref_doc_id', $this->docIdRefError);
            $stmt->bindValue(':job_id', $this->jobId);
            $result = $stmt->executeQuery();
            $this->message = '';
			$this->docIdRefError = '';
        } catch (\Exception $e) {
            $this->logger->error('Failed to create log : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function generateDocLog($errorType, $message)
    {
        $this->typeError = $errorType;
        $this->message = $message;
        $this->createDocLog();
    }
	
}

class DocumentManager extends documentcore
{
}
