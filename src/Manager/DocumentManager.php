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

class DocumentManager
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
    protected $ruleFilters;
    protected $variables;
    protected $ruleWorkflows;
    protected bool $workflowAction = false;
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
    protected $noLock;
    protected $workflowError = false;
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
    protected $filterDocRef;
    protected ?ToolsManager $tools;
    protected $api;    // Specify if the class is called by the API
    protected $ruleDocuments;
    protected $container;
    protected LoggerInterface $logger;
    protected FormulaManager $formulaManager;
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
        'Create_KO' => 'Error',
        'Filter_KO' => 'Error',
        'Predecessor_KO' => 'Error',
        'Relate_KO' => 'Error',
        'Error_transformed' => 'Error',
        'Error_checking' => 'Error',
        'Error_sending' => 'Error',
        'Error_expected' => 'Error',
        'Not_found' => 'Error',
    ];
    private array $notSentFields = [];

    // Instanciation de la classe de génération de log Symfony
    public function __construct(
        LoggerInterface $logger,
        Connection $dbalConnection,
        EntityManagerInterface $entityManager,
        FormulaManager $formulaManager,
        SolutionManager $solutionManager = null,
        ParameterBagInterface $parameterBagInterface = null,
        ToolsManager $tools = null
    ) {
        $this->connection = $dbalConnection;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
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
            'Error_expected' => 'flux.status.Error_expected',
            'Cancel' => 'flux.status.cancel',
            'Create_KO' => 'flux.status.create_ko',
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
		
	public function setId($id) {
		$this->id = $id;
	}
	
	public function setNoLock($noLock) {
		$this->noLock = $noLock;
	}

	public function setDocumentType($documentType) {
		$this->documentType = $documentType;
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
                $this->workflowError = $this->document_data['workflow_error'];
				// A document can be loaded only if there is no lock or if the lock is on the current job.
				// Don't do this action if the attribut $noLock is true
                if (!$this->noLock) {
					if (
							!empty($this->jobLock)
						AND $this->jobLock != $this->jobId
					) {
							throw new \Exception('This document is locked by the task '.$this->jobLock.'. ');
					// No setlock if $this->jobLock == $this->jobId
					} elseif (!empty($this->jobLock)) {
						$this->setLock();
					}
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
                $this->logger->error($this->id.' - Failed to retrieve Document '.$id_doc.'.');
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
			// Clear no send field that belongs to each document
			$this->notSentFields = array();
			// Re activate the workflow if a new document is loaded or if the current document is reloaded
			$this->workflowAction = false;
			// Clear attributes if requested
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
			if (!empty($param['ruleFilters'])) {
				$this->ruleFilters = $param['ruleFilters'];
			}
			// Init type error for each new document
			$this->typeError = 'S';
		} catch (\Exception $e) {
            $this->message .= $e->getMessage();
            $this->typeError = 'E';
            $this->logger->error((!empty($this->id) ? $this->id.' - ' : '').$this->message);
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
            $this->ruleFilters = [];
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
        $this->filterDocRef = '';
        $this->api = '';    // Specify if the class is called by the API
        $this->ruleDocuments = [];
    }

    public function createDocument(): bool
    {
        // On ne fait pas de beginTransaction ici car on veut pouvoir tracer ce qui a été fait ou non. Si le créate n'est pas du tout fait alors les données sont perdues
        // L'enregistrement même partiel d'un document nous permet de tracer l'erreur.
        try {
			// Check on current document before any action
			$this->checkDocumentBeforeAction();
			
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
            // Insert source data
            $insertDataTable = $this->insertDataTable($this->data, 'S');
            $this->updateStatus('New');
			return $insertDataTable;
        } catch (\Exception $e) {
            $this->message .= 'Failed to create document (id source : '.$this->sourceId.'): '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($this->id.' - '.$this->message);
			$this->updateStatus('Create_KO');
            return false;
        }
    }

    // Permet de filtrer ou non un document
    public function filterDocument($ruleFilters)
    {
        try {
			// Check on current document before any action
			$this->checkDocumentBeforeAction();
			
			// Return false if job has been manually stopped
			if (!$this->jobActive) {
				$this->message .= 'Job is not active. ';
				return false;
			}
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
            $this->logger->error($this->id.' - '.$this->message);
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

    public function getTypeError()
    {
        return $this->typeError;
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
            // No action if document not locked
			if (empty($documentData['job_lock'])) {
				return true;
			}
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
				// Create log only when force is true
				if ($force === true) {
					$this->typeError = 'S';
					$this->message .= 'Document unlocked. ';
					$this->createDocLog();
					// Display the result in the terminal
					if (
							!$this->api
						AND $this->env == 'background'
					) {
						echo 'Unlock document '.$this->id.'  '.$now.chr(10);
					}
				}
				return true;
			} else {
				// Only throw exception if jobId exists
				throw new \Exception('This document is locked by the task '.$documentData['job_lock'].' and cannot be unclocked by the task '.$this->jobId.'. ');
			}
        } catch (\Exception $e) {
			$this->message .= 'Failed to unlock the document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->logger->error($this->id.' - '.$this->message);
            $this->createDocLog();
		}
		return false;
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
        try {
			// Check on current document before any action
			$this->checkDocumentBeforeAction();
			
			// Return false if job has been manually stopped
			if (!$this->jobActive) {
				$this->message .= 'Job is not active. ';
				return false;
			}
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

            // if id found, we stop to send an error, we cancel the predecessor and try again
            if (!empty($result['id'])) {
				// Load the document that locks the current document
				$paramCancel['id_doc_myddleware'] = $result['id'];
				$paramCancel['jobId'] = $this->jobId;
				$docCancel = clone $this;
				// Cancel the documents that locks the current document
				$docCancel->setParam($paramCancel, true);
				$docCancel->docIdRefError = $this->id;
				$docCancel->setMessage('This document is cancelled because a predecessor document has been generated (same source_id for the same rule). ');
				// Set status predecessor_ko if the ref document couldn't be cancelled. 
				if ($docCancel->updateStatus('Cancel') == false) {
					throw new \Exception('The cancellation of the document '.$result['id'].' failed.');
				}
				// Add an error message to the current document
				$this->docIdRefError = $result['id'];
				$this->setMessage('The document in reference has been cancelled to execute the current one. ');
				$this->createDocLog();
				unset($docCancel);
				$result = [];
				$result = $stmt->executeQuery();
				$result = $result->fetchAssociative();
				// If there is still another document in error, we set the status "predecessor_ko" for the current document
				if (!empty($result['id'])) {
					throw new \Exception('The document '.$result['id'].' is on the same record and is not closed. This document is queued. ');
				}
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
            $this->docIdRefError = ((!empty($result) and is_array($result) and !empty($result['id'])) ? $result['id'] : '');
            $this->message .= 'Failed to check document predecessor : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->updateStatus('Predecessor_KO');
            $this->logger->error($this->id.' - '.$this->message);
            return false;
        }
    }

    // Permet de valider qu'aucun document précédent pour la même règle et le même id sont bloqués
    public function checkParentDocument(): bool
    {
        try {
			// Check on current document before any action
			$this->checkDocumentBeforeAction();
			
			// Return false if job has been manually stopped
			if (!$this->jobActive) {
				$this->message .= 'Job is not active. ';
				return false;
			}
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
            $this->logger->error($this->id.' - '.$this->message);
            return false;
        }
    }

    // Permet de transformer les données source en données cibles
    public function transformDocument(): bool
    {
        try {
			// Check on current document before any action
			$this->checkDocumentBeforeAction();
			
			// Return false if job has been manually stopped
			if (!$this->jobActive) {
				$this->message .= 'Job is not active. ';
				return false;
			}
            // Transformation des données et insertion dans la table target
            $transformed = $this->updateTargetTable();
            if (!empty($transformed)) {
				// If the value mdw_cancel_document is found in the target data of the document after transformation we cancel the document
				if (array_search('mdw_cancel_document',$transformed, true) !== false) {
					$this->message .= 'The document contains the value mdw_cancel_document. ';
					$this->typeError = 'W';
					$this->updateStatus('Cancel',$this->workflowAction);
					return false;
				}
				// If the value mdw_error_transformed is found in the target data of the document after transformation we send an error transformed
				if (array_search('mdw_error_transformed',$transformed, true) !== false) {
					 throw new \Exception('The code mdw_error_transformed found in document'); 
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
									$this->updateStatus('Filter',$this->workflowAction);
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
			// if the filter status has been forced, we don't set the status Transformed or Error_transformed
            } elseif(!empty($this->filterDocRef)) {
				return true;
			} else {
                throw new \Exception('Failed to transformed data. This document is queued. ');
            }
            $this->updateStatus('Transformed',$this->workflowAction);

            return true;
        } catch (\Exception $e) {
            $this->message .= 'Failed to transform document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->updateStatus('Error_transformed',$this->workflowAction);
            $this->logger->error($this->id.' - '.$this->message);
            return false;
        }
    }

    // Permet de transformer les données source en données cibles
    public function getTargetDataDocument(): bool
    {
        try {
			// Check on current document before any action
			$this->checkDocumentBeforeAction();
			
			// Return false if job has been manually stopped
			if (!$this->jobActive) {
				$this->message .= 'Job is not active. ';
				return false;
			}
			$history = false;
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
				$searchFields = $this->prepareSearchFields($duplicate_fields, $target);
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
                        $this->updateType('U');
                        $this->updateStatus('Ready_to_send');
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
            $this->logger->error($this->id.' - '.$this->message);
            return false;
        }
        return true;
    }

	// Prepare the search fields
	protected function prepareSearchFields($duplicateFields, $target) {
		$searchFields = array();
		if (!empty($duplicateFields)) {
			foreach ($duplicateFields as $duplicateField) {
				// In case of Myddleware_element_id, we change it to id. Myddleware_element_id reprensents the id of the record in the target application
				if ('Myddleware_element_id' == $duplicateField) {
					$searchFields['id'] = $target[$duplicateField];
					continue;
				}
				// Do not search duplicates on an empty field
				if (empty($target[$duplicateField])) {
					$searchFields= array();
					break;
				}
				$searchFields[$duplicateField] = $target[$duplicateField];
			}
		}
		return $searchFields;
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
			// Check on current document before any action
			$this->checkDocumentBeforeAction();
			
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
					// If one of the field isn't set then we return false
					if (
							!array_key_exists($field['target_field_name'], $history)
						 OR !array_key_exists($field['target_field_name'], $target)
					){
						return false;
					}
                    if (stripslashes(trim($history[$field['target_field_name']])) != stripslashes(trim($target[$field['target_field_name']]))) {
                        // Null text is considered as empty for comparaison
						if ($target[$field['target_field_name']] === 'null') {
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
            // Error il multiple duplicate records found
			if (count($dataTarget['values']) > 1) {
				$this->message .= count($dataTarget['values']).' duplicates found. Only one duplicate maximum can be found.';
                return -1;
			}
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
            $this->logger->error($this->id.' - '.$this->message);
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
							if (array_key_exists($sourceField,$data)) {
								$dataInsert[$sourceField] = $data[$sourceField];
							} else {
								throw new \Exception('The field '.$sourceField.' is missing in the source values. ');
							}
                        }
                    } else {
                        // Some field can't be retrived from the target application (history). For example the field password on the module user of Moodle
                        if (
                                !array_key_exists($ruleField['target_field_name'], $data)
                            and 'H' == $type
                        ) {
                            continue;
                        }
                        // foreach field of $this->notSentFields, we remove it from the data to send (Target only)
                        if (
                                !empty($this->notSentFields)
                            and in_array($ruleField['target_field_name'], $this->notSentFields)
							and $type == 'T'
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
		
			// We save the filter fields too (if not already in the list)
            if (!empty($this->ruleFilters)) {
                foreach ($this->ruleFilters as $ruleFilter) {
                    if (!array_key_exists($ruleFilter['target'],$dataInsert)) {
                        $dataInsert[$ruleFilter['target']] = (!empty($data[$ruleFilter['target']]) ? $data[$ruleFilter['target']] : '');
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
            $this->typeError = 'E';
			// Change status depending on the data type inserted
			if($type == 'S') {
				$this->message .= 'Failed - Create_KO : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
				$this->updateStatus('Create_KO');
			} elseif($type == 'T') {
				$this->message .= 'Failed - Error_transformed : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
				$this->updateStatus('Error_transformed');
			} elseif($type == 'H') {
				$this->message .= 'Failed - Error_checking : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
				$this->updateStatus('Error_checking');
			}
            $this->logger->error($this->id.' - '.$this->message);
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
                $this->logger->error($this->id.' - '.$this->message);
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
                    // Force the filter status if requested
					} elseif (!empty($this->filterDocRef)) {
						$this->docIdRefError = $this->filterDocRef;
						$this->typeError = 'W';
						$this->message .= 'Document filter because the parent document is filter too. Check reference column to open the parent document.';
						$this->updateStatus('Filter');
						return null;
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
            $this->logger->error($this->id.' - '.$this->message);
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
			// Error if no source data
			if (empty($source)) {
				throw new \Exception('Source data are empty. Failed to transform data.' );
			}
			// Include variable in the database
			if (!empty($this->variables)) {
				foreach($this->variables as $key => $value) {
					$fieldNameDyn = $key;
					$$fieldNameDyn = $value;
				}
			}
            // Manage formula
            if (!empty($ruleField['formula'])) {
                // -- -- -- Formula management

                // Build variables
                $r = explode(';', $ruleField['source_field_name']);
                if (count($r) > 1) {
                    foreach ($r as $listFields) {
                        // We skip my_value because it is a constant
                        if ('my_value' != $listFields) {
                            $fieldNameDyn = $listFields; // value : variable name
							// Replace dot by a string because dot can't be into a variable in php, the formula can't work
							if (strpos($ruleField['source_field_name'], '.') !== false) {
								$fieldNameDyn = str_replace('.', '___dot___', $listFields);
								$ruleField['formula'] = str_replace($listFields, $fieldNameDyn, $ruleField['formula']);
							}
                            if (array_key_exists($listFields, $source)) {
                                $$fieldNameDyn = (array_key_exists($listFields,$source) ? $source[$listFields] : ''); // Dynamic variable (e.g $name = name)
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
						// Replace dot by a string because dot can't be into a variable in php, the formula can't work
						if (strpos($ruleField['source_field_name'], '.') !== false) {
							$fieldNameDyn = str_replace('.', '___dot___', $ruleField['source_field_name']);
							$ruleField['formula'] = str_replace($ruleField['source_field_name'], $fieldNameDyn, $ruleField['formula']);
						}
                        $$fieldNameDyn = $source[$ruleField['source_field_name']]; // Dynamic variable (e.g $name = name)
                    }
                }
				// Replace Document type variable 
				if (str_contains($ruleField['formula'], "mdw_document_type")) {
					$ruleField['formula'] = str_replace("mdw_document_type", $this->documentType, $ruleField['formula']);
				}
				
                // préparation des variables
                $this->formulaManager->init($ruleField['formula']); // mise en place de la règle dans la classe
                $this->formulaManager->generateFormule(); // Genère la nouvelle formule à la forme PhP

                // Execute formula
                if ($f = $this->formulaManager->execFormule()) {
					// Check if variables are requiered for the formula
					if ($this->isVariableRequested($f)) {
						$currentRule = $this->ruleId;
						$connection = $this->connection;
						$entityManager = $this->entityManager;
						$solutionManager = $this->solutionManager;
						$myddlewareUserId = $this->userId;
						$sourceFieldName = $ruleField['source_field_name'];
						$docId = $this->id;
					}
					
					// Manage lookup formula by adding parameters
					if (strpos($f, 'lookup') !== false ) {
						$f = str_replace('lookup(', 'lookup($entityManager, $connection, $currentRule, $docId, $myddlewareUserId, $sourceFieldName, ', $f);
					}
					// Manage getRecord formula by adding parameters
					if (strpos($f, 'getRecord') !== false ) {
						$f = str_replace('getRecord(', 'getRecord($entityManager, $connection, $solutionManager, ', $f);
					}
                    try {
                        // Trigger to redefine formula
                        $f = $this->changeFormula($f);
						eval('$rFormula = '.$f.';'); // exec
						if (isset($rFormula)) {
							return $rFormula;	
						}
						// Second check in case isset returns false, we check the variable doesn't exist at all
						if (array_key_exists('rFormula', get_defined_vars())) {
							// Return result
							return $rFormula;
						} else {
							throw new \Exception('Invalid formula (failed to retrieve formula) : '.$ruleField['formula']);
						}
                    } catch (\Throwable $e) {
                        throw new \Exception('Failed to execute the formula "'.$ruleField['formula'].';" : '.$e->getMessage());
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
            $this->logger->error($this->id.' - '.$this->message);
            // Set the error to true. We can't set a specific value in the return because this function could return any value (even false depending the formula)
			// We set the ref id returned in the message (id lenght is 23 characters)
			if (str_contains($e->getMessage(), 'mdw_set_filter_status')) {
				$this->filterDocRef = substr($e->getMessage(), -23);
			// Set the error to true. We can't set a specific value in the return because this function could return any value (even false depending the formula)
			} else {
				$this->transformError = true;
			}

            return null;
        }
    }

	// Function to check if a formula require variables
	protected function isVariableRequested($formula) {
		if (
				strpos($formula, 'lookup') !== false
			 or strpos($formula, 'getRecord') !== false
		) {
			return true;
		}
		return false;
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
            $this->logger->error($this->id.' - '.$this->message);
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
            $this->logger->error($this->id.' - '.$this->message);
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
            $this->logger->error($this->id.' - '.$this->message);
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
            $this->logger->error($this->id.' - Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
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
            $this->logger->error($this->id.' - '.$this->message);

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
    public function updateStatus($new_status, $workflow = false)
    {
        try {
			$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
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
									status = :new_status
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
            $this->message .= 'Status : '.$new_status;
            $this->status = $new_status;
            $this->afterStatusChange($new_status);
            $result = $stmt->executeQuery();
            // We don't clear the message because we could need it in the workflow, we clear it after the workflow execution
			$this->createDocLog(false);
			// runWorkflow can't be executed if updateStatus is called from the solution class
			if (
                    $new_status!='Send'
                AND !$workflow          // Do not run wroklow if the change comes from a workflow
            ) {
				$this->runWorkflow();
			}
			// Clear message after running workflow because we could use it in the workflow
			$this->message = '';
			$this->docIdRefError = '';
			// Remove the lock on the document in the class and in the database
			// Exception : status New because there is no lock on document for this status, the lock in on the rule
			// Exception : status No_send because the document has already been unlock by the status ready_to_send
			// Exception : Update status call by a workflow, the lock will be removed only by the main call
            // Exception : Status Cancel, we should be able to unlock the document
			if (
					!in_array($new_status, array('New','No_send', "Cancel"))
				AND !$workflow
			) {
				if ($this->unsetLock() == false) {
					throw new \Exception('Status has been changed but document has not been unlocked. ');
				}
			}
			$this->connection->commit(); // -- COMMIT TRANSACTION
			return true;
        } catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
            $this->message .= 'Error status update : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->logger->error($this->id.' - '.$this->message);
            $this->createDocLog();
			return false;
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
            $this->logger->error($this->id.' - '.$this->message);
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
            $this->logger->error($this->id.' - '.$this->message);

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
            $this->logger->error($this->id.' - '.$this->message);
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
            $this->logger->error($this->id.' - '.$this->message);
            $this->createDocLog();
            return false;
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateWorkflowError($workflowError)
    {
        try {
            $now = gmdate('Y-m-d H:i:s');
            $query = '	UPDATE document 
								SET 
									date_modified = :now,
									workflow_error = :workflowError
								WHERE
									id = :id
								';
            // Suppression de la dernière virgule
            $stmt = $this->connection->prepare($query);
            $stmt->bindValue(':now', $now);
            $stmt->bindValue(':workflowError', $workflowError);
            $stmt->bindValue(':id', $this->id);
            $result = $stmt->executeQuery();
            $this->message .= 'Workflow error set to '.$workflowError;
            $this->createDocLog();
        } catch (\Exception $e) {
            $this->message .= 'Error type   : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->logger->error($this->id.' - '.$this->message);
            $this->createDocLog();
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
        // Get the current values                      
        $values = json_decode($documentDataEntity->getData(),1);
        if(!empty($values)){
			// In case we replace all values
			if ($refreshData) {
				$values = $newValues;
				$this->message .= ($dataType == 'S' ? 'Source' : ($dataType == 'T' ? 'Target' : 'History')).' document value changed by '.print_r($newValues,true).'. ';
			} else {
				// Replace only the new values 
				foreach ($newValues as $key => $value) {
					if(array_key_exists($key,$values)){
						// Remove the field if code mdw_no_send_field
						if (str_contains($value,'mdw_no_send_field')) {
							unset($values[$key]);
							$this->message .= ($dataType == 'S' ? 'Source' : ($dataType == 'T' ? 'Target' : 'History')).' field '.$key.' removed from document. ';
						} else {
							$this->message .= ($dataType == 'S' ? 'Source' : ($dataType == 'T' ? 'Target' : 'History')).' value changed  from  '.$values[$key].' to '.$value.'. ';
							$values[$key] = $value;
						}
					} else {
						$this->message .= ($dataType == 'S' ? 'Source' : ($dataType == 'T' ? 'Target' : 'History')).' field '.$key.' not found in the document. ';
					}
				}
            }
            $this->typeError = 'I';
            $this->createDocLog();
            // Update the data of the right type
            $documentDataEntity->setData(json_encode($values, true));
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
									GROUP_CONCAT(DISTINCT document.id ORDER BY document.source_date_modified DESC) document_id,
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
									GROUP_CONCAT(DISTINCT document.id ORDER BY document.source_date_modified DESC) document_id,
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

				// In cas of several document found we get only the first one (which is the most recent one)
				if (
						!empty($result['document_id'])
					AND strpos($result['document_id'], ',')
				) {
					$documentList = explode(',',$result['document_id']);
					$result['document_id'] = $documentList[0];
				}
            }
			if (!empty($result['record_id'])) {
                return $result;
            }
            return null;
        } catch (\Exception $e) {
            $this->message .= 'Error getTargetId  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->typeError = 'E';
            $this->logger->error($this->id.' - '.$this->message);
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
            $this->logger->error($this->id.' - '.$this->message);
        }

        return null;
    }

    public function getStatus()
    {
        return $this->status;
    }
	
	// Premium method
	public function runWorkflow($rerun=false) {
		return true;
	}	
	
	// Check the document before an action is executed
	protected function checkDocumentBeforeAction() {
		if (!empty($this->workflowError)) {
			throw new \Exception('The action can\'t be executed because there is an error on a workflow. ');
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
    protected function createDocLog($clearMessage=true)
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
			if ($clearMessage) {
				$this->message = '';
				$this->docIdRefError = '';
			}
        } catch (\Exception $e) {
            $this->logger->error($this->id.' - Failed to create log : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
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