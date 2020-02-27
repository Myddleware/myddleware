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

namespace Myddleware\RegleBundle\Classes;

use Symfony\Bridge\Monolog\Logger; // Gestion des logs
use Symfony\Component\DependencyInjection\ContainerInterface as Container; // Accède aux services
use Doctrine\DBAL\Connection; // Connexion BDD
use Myddleware\RegleBundle\Classes\tools as MyddlewareTools; // SugarCRM Myddleware
use Myddleware\RegleBundle\Entity\DocumentData as DocumentDataEntity;
use Myddleware\RegleBundle\Entity\DocumentRelationship as DocumentRelationship;

class documentcore { 
	
	public $id;
	
	protected $em;
	protected $typeError = 'S';
	protected $message = '';
	protected $dateCreated;
	protected $connection;
	protected $ruleName;
	protected $ruleMode;
	protected $ruleId;
	protected $ruleFields;
	protected $ruleRelationships;
	protected $ruleParams;
	protected $sourceId;
	protected $targetId;
	protected $parentId;
	protected $sourceData;
	protected $data;
	protected $documentType;
	protected $jobActive = true;
	protected $attempt;
	protected $userId;
	protected $status;
	protected $document_data;
	protected $solutionTarget;
	protected $solutionSource;
	protected $jobId;
	protected $key;
	protected $docIdRefError;
	protected $transformError = false;
	protected $tools;
	protected $ruleDocuments;
	protected $globalStatus = array(
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
										'Not_found' => 'Error'
								);
	
	protected $container;
	protected $logger;
	
	static function lstGblStatus() {				
		return array(
			'Open' => 'flux.gbl_status.open',
			'Close' => 'flux.gbl_status.close',
			'Cancel' => 'flux.gbl_status.cancel',
			'Error' => 'flux.gbl_status.error'
		);		
	}

	static function lstStatus() {				
		return array(
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
			'Error_sending' => 'flux.status.error_sending'		
		);		
	}
	
	static function lstType() {				
		return array(
			'C' => 'flux.type.create',
			'U' => 'flux.type.update',
			'D' => 'flux.type.delete',
			'S' => 'flux.type.search'
		);		
	}

	
    // Instanciation de la classe de génération de log Symfony
    public function __construct(Logger $logger, Container $container, Connection $dbalConnection, $param) {
		$this->connection = $dbalConnection;
    	$this->logger = $logger;
		$this->container = $container;
		$this->em = $this->container->get('doctrine')->getEntityManager();
		
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
		if (!empty($param['key'])) {
			$this->key = $param['key'];
		}
		if (!empty($param['parentId'])) {
			$this->parentId = $param['parentId'];
		}
		if (!empty($param['ruleDocuments'])) {
			$this->ruleDocuments = $param['ruleDocuments'];
		}	

		// Stop the processus if the job has been manually stopped
		if ($this->getJobStatus() != 'Start') {
			$this->jobActive = false;
		}		

		// If mode isn't front ofice => only when the user click on "Simulation" during the rule creation
		if(
				empty($param['mode']) 
			 || (
					!empty($param['mode'])
				&& $param['mode'] != 'front_office'
			)
		) {
			// Init attribut of the class Document
			if (!empty($param['id_doc_myddleware'])) {	
				// Instanciate attribut sourceData
				$this->setDocument($param['id_doc_myddleware']);
			}
			else {
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
				// Set the deletion type if myddleware deletion flag is true
				if (!empty($this->data['myddleware_deletion'])) {
					$this->documentType = 'D';
				}
			} 
			// Ajout des paramètre de la règle
			$this->setRuleParam();
		}
		// Mise à jour des tableaux s'ils existent.
		if (!empty($param['ruleFields'])) {
			$this->ruleFields = $param['ruleFields'];
		}
		if (!empty($param['ruleRelationships'])) {
			$this->ruleRelationships = $param['ruleRelationships'];
		}
		$this->tools = new MyddlewareTools($this->logger, $this->container, $this->connection);	
	}
	
	public function setDocument($id_doc) {
		try {		
			$sqlParams = "	SELECT 
								Document.*, 
								Rule.name_slug,
								RuleParam.value mode,
								Rule.conn_id_source,
								Rule.module_source
							FROM Document 
								INNER JOIN Rule
									ON Document.rule_id = Rule.id
								INNER JOIN RuleParam
									ON  RuleParam.rule_id = Rule.id
									AND RuleParam.name= 'mode'
							WHERE Document.id = :id_doc";											
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue(":id_doc", $id_doc);
			$stmt->execute();	   				
			$this->document_data = $stmt->fetch();
		
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
				
				// Get source data and create data attribut
				$this->sourceData = $this->getDocumentData('S');			  
				$this->data = $this->sourceData;
				// Get document header 				
 			 	$documentEntity = $this->em
	                          ->getRepository('RegleBundle:Document')
	                          ->findOneById( $id_doc );	
				$this->data['id'] = $documentEntity->getSource();	
				$this->data['source_date_modified'] = $documentEntity->getSourceDateModified()->format('Y-m-d H:i:s');				
			}
			else {
				$this->logger->error( 'Failed to retrieve Document '.$id_doc.'.');
			}
		}
		catch (\Exception $e) {
			$this->message .= 'Failed to retrieve document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error($this->message);
			$this->createDocLog();
		}			
	}
	
	public function createDocument() {	
		// On ne fait pas de beginTransaction ici car on veut pouvoir tracer ce qui a été fait ou non. Si le créate n'est pas du tout fait alors les données sont perdues
		// L'enregistrement même partiel d'un document nous permet de tracer l'erreur.
		try {
			// Return false if job has been manually stopped
			if (!$this->jobActive) {
				$this->message .= 'Job is not active. ';
				return false;
			}		
			// Création du header de la requête 
			$query_header = "INSERT INTO Document (id, rule_id, date_created, date_modified, created_by, modified_by, source_id, source_date_modified, mode, type, parent_id) VALUES";		
			// Création de la requête d'entête
			$date_modified = $this->data['date_modified'];
			// Source_id could contain accent
			$query_header .= "('$this->id','$this->ruleId','$this->dateCreated','$this->dateCreated','$this->userId','$this->userId','".utf8_encode($this->sourceId)."','$date_modified','$this->ruleMode','$this->documentType','$this->parentId')";
			$stmt = $this->connection->prepare($query_header); 
			$stmt->execute();
			$this->updateStatus('New');
			// Insert source data			
			return $this->insertDataTable($this->data,'S');		
		} catch (\Exception $e) {
			$this->message .= 'Failed to create document (id source : '.$this->sourceId.'): '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error($this->message);
			return false;
		}		
	}
	
	// Permet de filtrer ou non un document
	public function filterDocument($ruleFilters) {
		// Return false if job has been manually stopped			
		if (!$this->jobActive) {
			$this->message .= 'Job is not active. ';
			return false;
		}
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		try {
			$filterOK = true;
			// Si des filtres sont présents 
			if (!empty($ruleFilters)) {		
				// Boucle sur les filtres
				foreach ($ruleFilters as $ruleFilter) {			
					if(!$this->checkFilter($this->sourceData[$ruleFilter['target']],$ruleFilter['type'],$ruleFilter['value'])) {
						$this->message .= 'This document is filtered. This operation is false : '.$ruleFilter['target'].' '.$ruleFilter['type'].' '.$ruleFilter['value'].'.';
						$this->updateStatus('Filter');
						$filterOK = -1;
						break;
					}
				}
			}
			// Si on a pas eu d'erreur alors le document passe à l'étape suivante 
			if ($filterOK === true) {
				$this->updateStatus('Filter_OK');
			}
			$this->connection->commit(); // -- COMMIT TRANSACTION
			return $filterOK;
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .= 'Failed to filter document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->updateStatus('Filter_KO');
			$this->logger->error($this->message); 
			return false;
		}		
	}
			
	public function getJobStatus() {
		$sqlJobDetail = "SELECT * FROM Job WHERE id = :jobId";
		$stmt = $this->connection->prepare($sqlJobDetail);
		$stmt->bindValue(":jobId", $this->jobId);
		$stmt->execute();	    
		$job = $stmt->fetch(); // 1 row
		if (!empty($job['status'])) {
			return $job['status'];
		}
		return false;
	}
	
	public function getRuleId() {
		return $this->ruleId;
	}
	
	public function getMessage() {
		return $this->message;
	}
	
	public function getJobActive() {
		return $this->jobActive;
	}
	
	public function setMessage($message) {
		$this->message .= $message;
	}
	
	public function setTypeError($typeError) {
		$this->typeError = $typeError;
	}
	
	// Permet d'indiquer si le filtreest rempli ou pas
	protected function checkFilter($fieldValue,$operator,$filterValue){
		switch ($operator) {
			case 'content':
				$pos = stripos($fieldValue, $filterValue);
				if ($pos === false) {
					return false;
				}
				else {
					return true;
				}
				break;
			case 'notcontent':
				$pos = stripos($fieldValue, $filterValue);
				if ($pos === false) {
					return true;
				}
				else {
					return false;
				}
				break;
			case 'begin':
				$begin = substr($fieldValue, 0, strlen($filterValue));
				if (strtoupper($begin) == strtoupper($filterValue)) {
					return true;
				}
				else {
					return false;
				}
				break;
			case 'end':
				$begin = substr($fieldValue, 0-strlen($filterValue));
				if (strtoupper($begin) == strtoupper($filterValue)) {
					return true;
				}
				else {
					return false;
				}
				break;
			case 'in':
				if (in_array(strtoupper($fieldValue),explode(';',strtoupper($filterValue)))) {
					return true;
				}
				else {
					return false;
				}
				break;
			case 'notin':
				if (!in_array(strtoupper($fieldValue),explode(';',strtoupper($filterValue)))) {
					return true;
				}
				else {
					return false;
				}
				break;
			case 'gt':
				if ($fieldValue > $filterValue) {
					return true;
				}
				else {
					return false;
				}
				break;
			case 'lt':
				if ($fieldValue < $filterValue) {
					return true;
				}
				else {
					return false;
				}
				break;
			case 'lteq':
				if ($fieldValue <= $filterValue) {
					return true;
				}
				else {
					return false;
				}
				break;
			case 'gteq':
				if ($fieldValue >= $filterValue) {
					return true;
				}
				else {
					return false;
				}
				break;
			case 'equal':
				if (strtoupper($fieldValue) == strtoupper($filterValue)) {
					return true;
				}
				else {
					return false;
				}
				break;
			case 'different':	
				if (strtoupper($fieldValue) != strtoupper($filterValue)) {
					return true;
				}
				else {
					return false;
				}
				break;
			default:
				$this->message .= 'Failed to filter. Operator '.$operator.' unknown. ';
				return false;
		}
		
	}
	
	// Permet de valider qu'aucun document précédent pour la même règle et le même id sont bloqués
	public function ckeckPredecessorDocument() {
		// Return false if job has been manually stopped
		if (!$this->jobActive) {
			$this->message .= 'Job is not active. ';
			return false;
		}
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		try {
			// Check predecessor in the current rule
			$sqlParams = "	SELECT 
								Document.id,							
								Document.rule_id,
								Document.status,
								Document.global_status											
							FROM Document								
							WHERE 
									Document.rule_id = :rule_id 
								AND Document.source_id = :source_id 
								AND Document.date_created < :date_created  
								AND Document.deleted = 0 
								AND Document.global_status IN ('Error','Open')
							LIMIT 1	
							";								
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue(":rule_id", $this->document_data['rule_id']);
			$stmt->bindValue(":source_id", $this->document_data['source_id']);
			$stmt->bindValue(":date_created", $this->document_data['date_created']);
			$stmt->execute();	   				
			$result = $stmt->fetch();
		
			// if id found, we stop to send an error
			if (!empty($result['id'])) {
				throw new \Exception('The document '.$result['id'].' is on the same record and is not closed. This document is queued. ');
			}
			
			// Check predecessor in the opposite bidirectional rule
			if (!empty($this->ruleParams['bidirectional'])) {
				$stmt = $this->connection->prepare($sqlParams);
				$stmt->bindValue(":rule_id", $this->ruleParams['bidirectional']);
				$stmt->bindValue(":source_id", $this->document_data['source_id']);
				$stmt->bindValue(":date_created", $this->document_data['date_created']);
				$stmt->execute();	   				
				$result = $stmt->fetch();
				if (!empty($result['id'])) {
					throw new \Exception('The document '.$result['id'].' is on the same record on the bidirectional rule '.$this->ruleParams['bidirectional'].'. This document is not closed. This document is queued. ');
				}				
			}
			
			// Check predecessor in the child rule
			// Get all child rules 
			$sqlGetChildRules = "	SELECT DISTINCT
										RuleRelationShip.rule_id 											
									FROM Document
										INNER JOIN RuleRelationShip
											ON RuleRelationShip.field_id = Document.rule_id
											AND RuleRelationShip.parent = 1
										INNER JOIN Rule
											ON Rule.id = RuleRelationShip.rule_id 									
									WHERE 
											Document.rule_id = :rule_id 
										AND Document.deleted = 0 
										AND	Rule.deleted = 0";					
			$stmt = $this->connection->prepare($sqlGetChildRules);
			$stmt->bindValue(":rule_id", $this->document_data['rule_id']);
			$stmt->execute();	    
			$childRules = $stmt->fetchAll();	
			if($childRules) {
				// If rule child, document open in ready_to_send are accepted because data in ready to send could be pending
				$sqlParamsChild = "	SELECT 
										Document.id,							
										Document.rule_id,
										Document.status,
										Document.global_status											
									FROM Document								
									WHERE 
											Document.rule_id = :rule_id 
										AND Document.source_id = :source_id 
										AND Document.deleted = 0 
										AND Document.date_created < :date_created  
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
					$stmt->bindValue(":rule_id", $childRule['rule_id']);
					$stmt->bindValue(":source_id", $this->document_data['source_id']);
					$stmt->bindValue(":date_created", $this->document_data['date_created']);
					$stmt->execute();	   				
					$result = $stmt->fetch();

					if (!empty($result['id'])) {
						throw new \Exception('The document '.$result['id'].' is on the same record on the bidirectional rule '.$childRule['rule_id'].'. This document is not closed. This document is queued. ');
					}	
				}			
			}	

			// Get the target id and the type of the document
			$type_document = $this->checkRecordExist($this->sourceId);			
			// Don't change the document type if the type is deletion
			if ($this->documentType <> 'D') {
				$this->documentType = $type_document;
				// Override the document type in case of search type rule
				if ($this->ruleMode == 'S') {
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
						$this->documentType == 'U'
					 OR $this->documentType == 'D'
					)
				AND !$this->isChild()
			) {
				if (empty($this->targetId)) {
					throw new \Exception('No target id found for a document with the type Update. ');
				}
				if(!$this->updateTargetId($this->targetId)) {
					throw new \Exception('Failed to update the target id. Failed to unblock this update document. ');
				}
			}		
			
			// Set the status Predecessor_OK
			$this->updateStatus('Predecessor_OK');
			
			// Check compatibility between rule mode et document tupe
			// A rule in create mode can't update data excpt for a child rule
			if (
					$this->ruleMode == 'C' 
				AND $this->documentType == 'U'
				AND !$this->isChild()
			) {	
				$this->message .= 'Rule mode only allows to create data. Filter because this document updates data.';
				$this->updateStatus('Filter');
				// In case we flter the document, we return false to stop the process when this method is called in the rerun process
				$this->connection->commit(); // -- COMMIT TRANSACTION
				return false;
			}
			$this->connection->commit(); // -- COMMIT TRANSACTION
			return true;
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			// Reference document id is used to show which document is blocking the current document in Myddleware
			$this->docIdRefError = $result['id'];
			$this->message .= 'Failed to check document predecessor : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->updateStatus('Predecessor_KO');
			$this->logger->error($this->message);
			return false;
		}		
	}
	
	// Permet de valider qu'aucun document précédent pour la même règle et le même id sont bloqués
	public function ckeckParentDocument() {
		// Return false if job has been manually stopped
		if (!$this->jobActive) {
			$this->message .= 'Job is not active. ';
			return false;
		}	
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		try {		
			// S'il y a au moins une relation sur la règle et si on n'est pas sur une règle groupée
			// alors on contôle les enregistrements parent 		
			if (
					!empty($this->ruleRelationships)
				&& !$this->isChild()
			) {
				$error = false;
				// Vérification de chaque relation de la règle
				foreach ($this->ruleRelationships as $ruleRelationship) {			
					if(empty(trim($this->sourceData[$ruleRelationship['field_name_source']]))) {				
						continue; // S'il n'y a pas de relation, on envoie sans erreur
					}		

					// If the relationship is a parent type, we don't check parent document here. Data will be controlled and read from the child rule when we will send the parent document. So no target id is required now.
					if (!empty($ruleRelationship['parent'])) {
						continue;
					}	
					

					// Select previous document in the same rule with the same id and status different than closed
					$targetId = $this->getTargetId($ruleRelationship,$this->sourceData[$ruleRelationship['field_name_source']]);
					if (empty($targetId['record_id'])) {					
						// If no target id found, we check if the parent has been filtered, in this case we filter the relate document too
						$documentSearch = $this->searchRelateDocumentByStatus($ruleRelationship,$this->sourceData[$ruleRelationship['field_name_source']], 'Filter');
						if (!empty($documentSearch['id'])) {
							$this->docIdRefError = $documentSearch['id'];
							$this->typeError = 'W';
							$this->message .= 'Document filter because the parent document is filter too. Check reference column to open the parent document.';
							$this->updateStatus('Filter');
							$this->connection->commit(); // -- COMMIT TRANSACTION	
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
					$sqlParams = "	SELECT name FROM Rule WHERE id = :rule_id";								
					$stmt = $this->connection->prepare($sqlParams);
					$stmt->bindValue(":rule_id", $ruleRelationship['field_id']);
					$stmt->execute();	   				
					$ruleResult = $stmt->fetch(); 
					$direction = $this->getRelationshipDirection($ruleRelationship);
					throw new \Exception( 'Failed to retrieve a related document. No data for the field '.$ruleRelationship['field_name_source'].'. There is not record with the ID '.($direction == '1' ? 'source' : 'target').' '.$this->sourceData[$ruleRelationship['field_name_source']].' in the rule '.$ruleResult['name'].'. This document is queued. ');
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
					$this->status == 'Relate_KO'
				AND $this->documentType == 'C'
			) {			
				$this->documentType = $this->checkRecordExist($this->sourceId);					
				if ($this->documentType == 'U') {
					$this->updateTargetId($this->targetId);
					$this->updateType('U');
				}
			}
			
			$this->updateStatus('Relate_OK');
					
			$this->connection->commit(); // -- COMMIT TRANSACTION	
			return true;
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .= 'No data for the field '.$ruleRelationship['field_name_source'].' in the rule '.$this->ruleName.'. Failed to check document related : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->updateStatus('Relate_KO');
			$this->logger->error($this->message);
			return false;
		}		
	}
	
	// Permet de transformer les données source en données cibles
	public function transformDocument() {	
		// Return false if job has been manually stopped
		if (!$this->jobActive) {
			$this->message .= 'Job is not active. ';
			return false;
		}	
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		try {
			// Transformation des données et insertion dans la table target
			$transformed = $this->updateTargetTable();
			if ($transformed) {
				// If the type of this document is Update and the id of the target is missing, we try to get this ID
				// Except if the rule is a child (no target id is required, it will be send with the parent rule)
				if (
						$this->documentType == 'U'
					&& empty($this->targetId)
					&& !$this->isChild()
				) {
					$this->checkRecordExist($this->document_data['source_id']);
					if (!empty($this->targetId)) {
						if(!$this->updateTargetId($this->targetId)) {
							throw new \Exception( 'The type of this document is Update. Failed to update the target id '.$this->targetId.' on this document. This document is queued. ' );
						}
					}
					else {
						throw new \Exception( 'The type of this document is Update. The id of the target is missing. This document is queued. ' );
					}
				}
			}
			else {
				throw new \Exception( 'Failed to transformed data. This document is queued. ' );
			}			
			$this->updateStatus('Transformed');
			$this->connection->commit(); // -- COMMIT TRANSACTION	
			return true;
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .= 'Failed to transform document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->updateStatus('Error_transformed');
			$this->logger->error($this->message);
			return false;
		}	
	}
	
	// Permet de transformer les données source en données cibles
	public function getTargetDataDocument() {	
		// Return false if job has been manually stopped
		if (!$this->jobActive) {
			$this->message .= 'Job is not active. ';
			return false;
		}	
		$history = false;
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		try {
			// Check if the rule is a parent and run the child data.
			$this->runChildRule();
			
			// If the document type is a modification or a deletion we get target data for the record using its ID
			// And if the rule is not a child (no target id is required, it will be send with the parent rule)
			if (
					(
						$this->documentType == 'U'
					 OR $this->documentType == 'D'
					)
				 &&	!$this->isChild()
			) {
				// Récupération des données avec l'id de la cible
				$searchFields = array('id' => $this->targetId);
				$history = $this->getDocumentHistory($searchFields);
	
				// History is mandatory before a delete action
				if (	
						$this->documentType == 'D'
					AND (
							$history === -1
						 OR	$history === false
					)
				) {
					throw new \Exception('Failed to retrieve record in target system before deletion. Id target : '.$this->targetId.'. Check this record is not already deleted.');
				}
				// Ici la table d'historique doit obligatoirement avoir été mise à jour pour continuer
				if ($history !== -1) {
					$this->updateStatus('Ready_to_send');
				}
				else {
					throw new \Exception('Failed to retrieve record in target system before update. Id target : '.$this->targetId.'. Check this record is not deleted.');
				}
			}
			// Si on est en création et que la règle a un paramètre de recherche de doublon, on va chercher dans la cible
			elseif (!empty($this->ruleParams['duplicate_fields'])) {
				$duplicate_fields = explode(';',$this->ruleParams['duplicate_fields']);		
				// Récupération des valeurs de la source pour chaque champ de recherche
				foreach($duplicate_fields as $duplicate_field) {
					foreach ($this->ruleFields as $ruleField) {
						if($ruleField['target_field_name'] == $duplicate_field) {
							$sourceDuplicateField = $ruleField;
						}
					}			
					if (!empty($sourceDuplicateField)) {
						// Get the value of the field (could be a formula)
						$searchFieldValue = $this->getTransformValue($this->sourceData,$sourceDuplicateField);
						// Add filed in duplicate search only if not empty
						if (!empty($searchFieldValue)) {
							$searchFields[$duplicate_field] = $searchFieldValue;
						// If no value, we check if the field is a relationship
						} elseif (!empty($this->ruleRelationships)) {	
							foreach ($this->ruleRelationships as $ruleRelationship) {	
								if($ruleRelationship['field_name_target'] == $duplicate_field) {	
									$sourceDuplicateFieldRelationship = $ruleRelationship;
								}
							}					
							if (!empty($sourceDuplicateFieldRelationship)) {
								$searchFieldValue = $this->getTransformValue($this->sourceData,$ruleRelationship);
							}
							if (!empty($searchFieldValue)) {
								$searchFields[$duplicate_field] = $searchFieldValue;
							}							
						}
					}
				}					
				if(!empty($searchFields)) {
					$history = $this->getDocumentHistory($searchFields);
				} 
	
				if ($history === -1) {
					throw new \Exception('Failed to search duplicate data in the target system. This document is queued. ');
				}
				// Si la fonction renvoie false (pas de données trouvée dans la cible) ou true (données trouvée et correctement mise à jour)
				elseif ($history === false) {
					// If search document and don't found the record, we return an error
					if ($this->documentType == 'S') {
						$this->typeError = 'E';
						$this->updateStatus('Not_found');
					} else {
						$this->updateStatus('Ready_to_send');
					}
				}
				// renvoie l'id : Si une donnée est trouvée dans le système cible alors on modifie le document pour ajouter l'id target et modifier le type
				else {
					// If search document we close it. 
					if ($this->documentType == 'S') {
						$this->updateStatus('Found');
					} else {
						$this->updateStatus('Ready_to_send');
						$this->updateType('U');
					}
					$this->updateTargetId($history);
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
					$this->documentType != 'S' 
				AND	$this->documentType != 'D' 
				AND	!$this->isParent()
			) {
				$this->checkNoChange();
			}		
			$this->connection->commit(); // -- COMMIT TRANSACTION	
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .= $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			if ($this->documentType == 'S') {
				$this->updateStatus('Not_found');
			} else {
				$this->updateStatus('Error_checking');
			}
			$this->logger->error($this->message);
			return false;
		}	
		return true;
	}
	
	// Get the child rule of the current rule
	// If child rule exist, we run it
	protected function runChildRule() {
	
		$ruleParam['ruleId'] = $this->ruleId;
		$ruleParam['jobId'] = $this->jobId;		
		$parentRule = new rule($this->logger, $this->container, $this->connection, $ruleParam);
		// Get the child rules of the current rule
		$childRuleIds = $parentRule->getChildRules();
		if (!empty($childRuleIds)) {
			foreach($childRuleIds as $childRuleId) {
				// Instantiate the child rule
				$ruleParam['ruleId'] = $childRuleId['field_id'];
				$ruleParam['jobId'] = $this->jobId;				
				$childRule = new rule($this->logger, $this->container, $this->connection, $ruleParam);				

				// Build the query in function generateDocuments
				if (!empty($this->sourceData[$childRuleId['field_name_source']])) {
					$idQuery = $this->sourceData[$childRuleId['field_name_source']];
				} else {
					//throw new \Exception( 'Failed to get the data in the document for the field '.$childRuleId['field_name_source'].'. The query to search to generate child data can\'t be created');
					continue;
				}

				// Generate documents for the child rule (could be several documents) => We search the value of the field_name_source in the field_name_target of the target rule 
				$docsChildRule = $childRule->generateDocuments($idQuery, true, array('parent_id' => $this->id), $childRuleId['field_name_target']);
				if (!empty($docsChildRule->error)) {
					throw new \Exception($docsChildRule->error);
				}
				// Run documents
				if (!empty($docsChildRule)) {
					foreach ($docsChildRule as $doc) {
						$errors = $childRule->actionDocument($doc->id,'rerun');
						// If a child is in error, we stop the whole processus : child document not saved (roolback) and parent document in error checking
						if (!empty($errors)) {									
							// The error should be clear because the child document won't be saved
							throw new \Exception( 'Child document in error (rule '.$childRuleId['field_id'].')  : '.$errors[0].' The child document has not be saved. Check the log (app/logs/'.$this->container->get( 'kernel' )->getEnvironment().'.log) for more information. ');
						}
					}
				}
			}
		}
		return true;
	}
	
	// Vérifie si les données sont différente entre ce qu'il y a dans la cible et ce qui devrait être envoyé
	protected function checkNoChange() {
		try {
			// Get target data 
			$target = $this->getDocumentData('T');
		
			// Get data in the target solution (if exists) before we update it
			$history = $this->getDocumentData('H');
			
			// For each target fields, we compare the data we want to send and the data already in the target solution
			// If one is different we stop the function
			if (!empty($this->ruleFields)) {
				foreach ($this->ruleFields as $field) {
					if (trim($history[$field['target_field_name']]) != trim($target[$field['target_field_name']])){
						return false;
					}
				}
			}
			
			// We check relationship fields as well
			if (!empty($this->ruleRelationships)) {
				foreach ($this->ruleRelationships as $ruleRelationship) {
					if ($history[$ruleRelationship['field_name_target']] != $target[$ruleRelationship['field_name_target']]){
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
	protected function getDocumentHistory($searchFields) {	
		// Permet de renseigner le tableau rule avec les données d'entête
		$rule = $this->getRule();
		$read['module'] = $rule['module_target'];
		// Get all fields for document type D (delete) to backup the whole record before delete it
		($this->documentType == 'D' ? $all = true : $all = false);
		$read['fields'] = $this->getTargetFields($all);	
		$read['query'] = $searchFields;
		$read['ruleParams'] = $this->ruleParams;
		$read['rule'] = $rule;
		// In case we search a specific record, we set an default value in date_ref because it is a requiered parameter in the read function
		$read['date_ref'] = '1970-01-02 00:00:00';	
		$dataTarget = $this->solutionTarget->read_last($read);		
		if (empty($dataTarget['done'])) {
			return false;
		}
		elseif ($dataTarget['done'] === -1) {
			$this->message .= $dataTarget['error'];
			return -1;
		}
		else {
			$updateHistory = $this->updateHistoryTable($dataTarget['values']);		
			if ($updateHistory === true) {
				return $dataTarget['values']['id'];
			}
			// Erreur dans la mise à jour de la table historique
			else {
				$this->message .= $dataTarget['error'];
				return -1;
			}
		}
	}

	// Permet de charger les données du système source pour ce document
	protected function getDocumentData($type) {
		try {	
			$documentDataEntity = $this->em
							->getRepository('RegleBundle:DocumentData')
							->findOneBy( array(
										'doc_id' => $this->id,
										'type' => $type
										)
								);
			// Generate data array
			if (!empty($documentDataEntity)) {
				return json_decode($documentDataEntity->getData(),true);
			}
		} catch (\Exception $e) {
			$this->message .= 'Error getSourceData  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error( $this->message );
		}		
		return false;
	}
	
	
	// Insert source data in table documentData
	protected function insertDataTable($data,$type) {
		try {	
			// We retrieve all the target fields (not just the rule flieds) before deleting data to the target solution to create a backup
			if (
					$this->documentType == 'D'
				AND	$type == 'H'
			) {
				// Get all module fields
				$targetFields = $this->getTargetFields(true);
				// Format these target fields
				if (!empty($targetFields)) {
					foreach ($targetFields as $targetField) {
						$fields[]= array('target_field_name' => $targetField);
					}
				}
			} else {
				$fields = $this->ruleFields;
			}
	
			// We save only fields which belong to the rule
			if (!empty($fields)) {
				foreach ($fields as $ruleField) {
					if ($type == 'S') {
						// We don't create entry in the array dataInsert when the filed is my_value because there is no filed in the source, just a formula to the target application
						if ($ruleField['source_field_name']=='my_value') {
							continue;
						}
						// It could be several fields in the source fields (in case of formula)
						$sourceFields = explode(";",$ruleField['source_field_name']);					
						foreach ($sourceFields as $sourceField) {
							// if Myddleware_element_id is present, we transform it into id 
							if ($sourceField=='Myddleware_element_id') {
								$sourceField = 'id';
							}
							$dataInsert[$sourceField] = $data[$sourceField];
						}
					} else {	
						// Some field can't be retrived from the target application (history). For example the field password on the module user of Moodle
						if (
								!array_key_exists($ruleField['target_field_name'], $data)
							 AND $type == 'H'	
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
					if ($type == 'S') {
						$dataInsert[$ruleRelationship['field_name_source']] = ($ruleRelationship['field_name_source'] == 'Myddleware_element_id' ? $data['id'] : (!empty($data[$ruleRelationship['field_name_source']]) ? $data[$ruleRelationship['field_name_source']] : ''));
					} else {	
						$dataInsert[$ruleRelationship['field_name_target']] = (!empty($data[$ruleRelationship['field_name_target']]) ? $data[$ruleRelationship['field_name_target']] : '');
					}
				}
			}		
			$documentEntity = $this->em
	                          ->getRepository('RegleBundle:Document')
	                          ->findOneById( $this->id );	
			$documentData = new DocumentDataEntity();
			$documentData->setDocId($documentEntity);
			$documentData->setType($type); // Source		
			$documentData->setData(json_encode($dataInsert)); // Encode in JSON
			$this->em->persist($documentData);
			$this->em->flush();		
			if (empty($documentData->getId())) {
				throw new \Exception( 'Failed to insert data source in table Document Data.' );
			}
		} 
		catch (\Exception $e) {
			$this->message .= 'Failed : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error( $this->message );			
			return false;
		}	
		return true;
	}
	
	
	// Mise à jour de la table des données source
	protected function updateHistoryTable($dataTarget) {
		if (!empty($dataTarget)) {
			try{	
				if (!$this->insertDataTable($dataTarget,'H')) {
					throw new \Exception( 'Failed insert target data in the table DocumentData.' );
				}
				return true;
			}
			catch(Exception $e) {
				$this->message .= 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
				$this->typeError = 'E';
				$this->logger->error( $this->message );
			}		
		}
		return false;
	}
	
	// Mise à jour de la table des données cibles
	protected function updateTargetTable() {
		try{	
			// Loop on every target field and calculate the value
			if (!empty($this->ruleFields)) {
				foreach ($this->ruleFields as $ruleField) {
					$value = $this->getTransformValue($this->sourceData,$ruleField);
					if (!empty($this->transformError)) {
						throw new \Exception( 'Failed to transform data.' );
					}
					$targetField[$ruleField['target_field_name']] = $value;
				}
			}
			// Loop on every relationship and calculate the value
			if(isset($this->ruleRelationships)) {
				// Récupération de l'ID target
				foreach ($this->ruleRelationships as $ruleRelationships) {
					$value = $this->getTransformValue($this->sourceData,$ruleRelationships);
					if (!empty($this->transformError)) {
						throw new \Exception( 'Failed to transform relationship data.' );
					}
					$targetField[$ruleRelationships['field_name_target']] = $value;
				}
			}		
			if (!empty($targetField)) {
				if (!$this->insertDataTable($targetField,'T')) {
					throw new \Exception( 'Failed insert target data in the table DocumentData.' );
				}
			}
			else {
				throw new \Exception( 'No target data found. Failed to create target data. ' );
			}				
			return true;
		}
		catch(Exception $e) {
			$this->message .= 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error( $this->message );				
		}			
		return false;
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
	public function getTransformValue($source,$ruleField) {
		try {		
			//--
			if (!empty($ruleField['formula'])) {
				// -- -- -- Gestion des formules
	
				// préparation des variables	
				$r = explode(';', $ruleField['source_field_name']);	
				if(count($r) > 1) {
					foreach ( $r as $listFields ) {
						// On ne traite pas l'entrée my_value
						if ($listFields != 'my_value') {
							$fieldNameDyn = $listFields; // value : nom de la variable exemple name
							if (isset($source[$listFields])) {
								$$fieldNameDyn = $source[$listFields]; // variable dynamique name = $name	
							}
							else {
								// Erreur
								throw new \Exception( 'The field '.$listFields.' is unknow in the formula '.$ruleField['formula'].'. ' );
							}
						}
					}									
				}
				else {
					// On ne traite pas l'entrée my_value
					if ($ruleField['source_field_name'] != 'my_value') {
						$fieldNameDyn = $ruleField['source_field_name']; // value : nom de la variable exemple name
						$$fieldNameDyn = $source[$ruleField['source_field_name']]; // variable dynamique name = $name									
					}
				}
				// préparation des variables	 
				
				$formule = $this->container->get('formula.myddleware'); // service formule myddleware
				$formule->init($ruleField['formula']); // mise en place de la règle dans la classe
				$formule->generateFormule(); // Genère la nouvelle formule à la forme PhP
				
				// Exécute la règle si pas d'erreur de syntaxe
				if($f = $formule->execFormule()) {
					eval('$rFormula = '.$f.';'); // exec
					if(isset($rFormula)) {
						// affectation du résultat
						return $rFormula;
					}
					else {
						throw new \Exception( 'Invalid formula (failed to retrieve formula) : '.$ruleField['formula'] );	
					}
				}
				else {
					throw new \Exception( 'Invalid formula (failed to execute) : '.$ruleField['formula'] );
				}
				// -- -- -- Gestion des formules
			}
			// S'il s'agit d'un champ relation
			elseif (!empty($ruleField['field_id'])) {	
				// Si l'id est vide on renvoie vide
				if(empty(trim($source[$ruleField['field_name_source']]))){
					return null;
				}
				
				// If the relationship is a parent type, we don't search the id in the child rule now. Data will be read from the child rule when we will send the parent document. So no target id is required now.
				if (!empty($ruleField['parent'])) {
					return null;
				}
				
				// Récupération de l'ID de l'enregistrement lié dans la cible avec l'id correspondant dans la source et la correspondance existante dans la règle liée.
				$targetId = $this->getTargetId($ruleField,$source[$ruleField['field_name_source']]);
				if (!empty($targetId['record_id'])) {
					return $targetId['record_id'];
				}
				else {
					throw new \Exception( 'Target id not found for id source '.$source[$ruleField['field_name_source']].' of the rule '.$ruleField['field_id'] );
				}
			}
			// Si le champ est envoyé sans transformation
			elseif (isset($source[$ruleField['source_field_name']])) {			
				return $this->checkField($source[$ruleField['source_field_name']]);
			}
			// If Myddleware_element_id is requested, we return the id 
			elseif (
						$ruleField['source_field_name'] == 'Myddleware_element_id'
					AND	isset($source['id'])
			) {			
				return $this->checkField($source['id']);
			}
			elseif (is_null($source[$ruleField['source_field_name']])) {			
				return null;
			}
			else {
				throw new \Exception( 'Field '.$ruleField['source_field_name'].' not found in source data.------'.print_r($ruleField,true) );
			}
		}
		catch(\Exception $e) {		
			$this->typeError = 'E';
			$this->message .= 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error( $this->message );
			// Set the error to true. We can't set a specific value in the return because this function could return any value (even false depending the formula)
			$this->transformError = true;
			return null;
		}

	}
	
	// Fonction permettant de contrôle les données. 
	protected function checkField($value) {
		if (isset($value)) {
			return $value; 
		}
		return null;
	}
	
	// Permet de récupérer les données d'entête de la règle
	protected function getRule() {
		try {
			if (!empty($this->ruleId)) {
				$rule = "SELECT * FROM Rule WHERE id = :ruleId";
				$stmt = $this->connection->prepare($rule);
				$stmt->bindValue(":ruleId", $this->ruleId);
				$stmt->execute();		
				return $stmt->fetch();
			}
		} catch (\Exception $e) {
			$this->typeError = 'E';
			$this->message .= 'Error getRule  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error( $this->message );
		}	
	}
	
	
	// Check if the document is a child
	public function isChild() {	
		$sqlIsChild = "	SELECT Rule.id 
									FROM RuleRelationShip 
										INNER JOIN Rule
											ON Rule.id  = RuleRelationShip.rule_id 
									WHERE 
											RuleRelationShip.field_id = :ruleId
										AND RuleRelationShip.parent = 1
										AND Rule.deleted = 0
								";		
		$stmt = $this->connection->prepare($sqlIsChild);
		$stmt->bindValue(":ruleId", $this->ruleId);
		$stmt->execute();	    
		$isChild = $stmt->fetch(); // 1 row
		if (!empty($isChild)) {
			return true;
		}
		return false;;		
	}
	
	// Check if the document is a child
	protected function getChildDocuments() {	
		try {
			$sqlGetChilds = "SELECT * FROM Document WHERE parent_id = :docId AND deleted = 0 ";		
			$stmt = $this->connection->prepare($sqlGetChilds);
			$stmt->bindValue(":docId", $this->id);
			$stmt->execute();	    
			return $stmt->fetchAll();	
		} catch (\Exception $e) {
			$this->typeError = 'E';
			$this->message .= 'Error getTargetFields  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error( $this->message );
		}	
	}
		
	// Check if the document is a parent
	protected function isParent() {	
		$sqlIsChild = "	SELECT RuleRelationShip.rule_id 
							FROM RuleRelationShip 				
							WHERE 
									RuleRelationShip.rule_id = :ruleId
								AND RuleRelationShip.parent = 1
								";		
		$stmt = $this->connection->prepare($sqlIsChild);
		$stmt->bindValue(":ruleId", $this->ruleId);
		$stmt->execute();	    
		$isChild = $stmt->fetch(); // 1 row
		if (!empty($isChild)) {
			return true;
		}
		return false;;		
	}
	
	// Permet de récupérer les champs de la cible
	protected function getTargetFields($all = false) {
		try {		
			// if all fields are requested
			if ($all) {
				$rule = $this->getRule();
				$targetFields = $this->solutionTarget->get_module_fields($rule['module_target']);
				if (!empty($targetFields)) {
					foreach($targetFields as $fieldname => $value) {
						$fields[] = $fieldname;
					}
				}			
			} elseif (!empty($this->ruleId)) {
				$rule = "SELECT * FROM RuleField WHERE rule_id = :ruleId";
				$stmt = $this->connection->prepare($rule);
				$stmt->bindValue(":ruleId", $this->ruleId);
				$stmt->execute();		
				$ruleFields = $stmt->fetchAll();
				foreach ($ruleFields AS $ruleField) {
					$fields[] = $ruleField['target_field_name'];
				}
				
				// Ajout des champs de relation s'il y en a
				$rule = "SELECT * FROM RuleRelationShip WHERE rule_id = :ruleId";
				$stmt = $this->connection->prepare($rule);
				$stmt->bindValue(":ruleId", $this->ruleId);
				$stmt->execute();		
				$ruleRelationShips = $stmt->fetchAll();
				if(!empty($ruleRelationShips)){						
					foreach ($ruleRelationShips AS $ruleRelationShip) {
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
				if ($key !== false) {
					unset($fields[$key]);
				}					
				return $fields;
			}
			return null;
		} catch (\Exception $e) {
			$this->typeError = 'E';
			$this->message .= 'Error getTargetFields  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error( $this->message );
		}		
	}
	
	// Permet de charger tous les paramètres de la règle
	protected function setRuleParam() {	
		try {
			$sqlParams = "SELECT * 
							FROM RuleParam 
							WHERE rule_id = :ruleId";
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue(":ruleId", $this->ruleId);
		    $stmt->execute();	   				
			$ruleParams = $stmt->fetchAll();
			if($ruleParams) {
				foreach ($ruleParams as $ruleParam) {
					$this->ruleParams[$ruleParam['name']] = ltrim($ruleParam['value']);
				}			
			}			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
		}
	}	

	// Permet de déterminer le type de document (Create ou Update)
	// En entrée : l'id de l'enregistrement source
	// En sortie : le type de docuement (C ou U)
	protected function checkRecordExist($id) {	
		try {	
			// Query used in the method several times
			// Sort : target_id to get the target id non empty first; on global_status to get Cancel last 
			// We dont take cancel document excpet if it is a no_send document (data really exists in this case)		
			$sqlParamsSoure = "	SELECT 
								Document.id, 
								Document.target_id, 
								Document.global_status 
							FROM Document 
							WHERE 
									Document.rule_id IN (:ruleId)	
								AND (
										Document.global_status = 'Close'
									 OR (
											Document.global_status = 'Cancel'	
										AND Document.status = 'No_send'
									)
								)
								AND	Document.source_id = :id
								AND Document.id != :id_doc
								AND Document.deleted = 0 
							ORDER BY target_id DESC, global_status DESC
							LIMIT 1";
							
			// On prépare la requête pour rechercher dans la partie target
			$sqlParamsTarget = "SELECT 
								Document.id, 
								Document.source_id target_id, 
								Document.global_status 
							FROM Document 
							WHERE 
									Document.rule_id IN (:ruleId)	
								AND (
										Document.global_status = 'Close'
									 OR (
											Document.global_status = 'Cancel'	
										AND Document.status = 'No_send'
									)
								)
								AND	Document.target_id = :id
								AND Document.id != :id_doc
								AND Document.deleted = 0 
							ORDER BY target_id DESC, global_status DESC
							LIMIT 1";	
					
			// Si une relation avec le champ Myddleware_element_id est présente alors on passe en update et on change l'id source en prenant l'id de la relation
			// En effet ce champ indique que l'on va modifié un enregistrement créé par une autre règle	
			if (!empty($this->ruleRelationships)) {
				// Boucle sur les relation
				foreach ($this->ruleRelationships as $ruleRelationship) {		
					// If the relationship target is Myddleware element id and if the rule relate isn't a child (we don't get target id or define type of a document with a child rule)
					if (
							$ruleRelationship['field_name_target'] == 'Myddleware_element_id'
						AND empty($ruleRelationship['parent'])
					){						
						// Si le champs avec l'id source n'est pas vide
						// S'il s'agit de Myddleware_element_id on teste id
						if (
								!empty($this->data[$ruleRelationship['field_name_source']])
							 || (
									$ruleRelationship['field_name_source'] == 'Myddleware_element_id'
								&& !empty($this->data['id'])	
							 )
						) {					
							// On recherche l'id target dans la règle liée
							$this->sourceId = ($ruleRelationship['field_name_source'] == 'Myddleware_element_id' ? $this->data['id'] : $this->data[$ruleRelationship['field_name_source']]);
							// On récupère la direction de la relation pour rechercher dans le target id ou dans le source id
							$direction = $this->getRelationshipDirection($ruleRelationship);
							if ($direction == '-1') {	
								$stmt = $this->connection->prepare($sqlParamsTarget);
							}
							else {
								$stmt = $this->connection->prepare($sqlParamsSoure);
							}
							$stmt->bindValue(":ruleId", $ruleRelationship['field_id']);
							$stmt->bindValue(":id", $this->sourceId);
							$stmt->bindValue(":id_doc", $this->id);
							$stmt->execute();	   				
							$result = $stmt->fetch();
				
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
								 || $result['global_status'] == 'Cancel'
							) {
								return 'C';
							} else {
								return 'U';
							}
						}
						else {
							throw new \Exception( 'The field '.$ruleRelationship['field_name_source'].' used in the relationship is empty. Failed to create the document.' );
						}
					}
				}
			}
			// A mass process exist for migration mode 
			if (!empty($this->ruleDocuments[$this->ruleId])) {
				// If a least one record is already existing, we test if it was successfully sent
				if (!empty($this->ruleDocuments[$this->ruleId]['sourceId'][$id])) {
					foreach($this->ruleDocuments[$this->ruleId]['sourceId'][$id] as $document) {
						if (
							(
								$document['global_status'] != 'Cancel'
							 OR (
										$document['global_status'] == 'Cancel'	
									AND $document['status'] == 'No_send'
								)
							)	
							AND $document['id'] !== $this->id
						) {
							// Si on trouve la target dans la règle liée alors on passe le doc en UPDATE (the target id can be found even if the relationship is a parent (if we update data), but it isn't required)
							if (!empty($document['target_id'])) {							
								$this->targetId = $document['target_id'];
								return 'U';
							}
							// If the document found is Cancel, there is only Cancel documents (see query order) so we return C and not U
							if (
									empty($result['id']) 
								 || $result['global_status'] == 'Cancel'
							) {
								return 'C';
							} else {
								return 'U';
							}
						}
					}
				} 
			} 
			else {	
				// If no relationship or no child rule
				// Recherche d'un enregitsrement avec un target id sur la même source
				$stmt = $this->connection->prepare($sqlParamsSoure);
				$stmt->bindValue(":ruleId", $this->ruleId);
				$stmt->bindValue(":id", $id);
				$stmt->bindValue(":id_doc", $this->id);
				$stmt->execute();	   				
				$result = $stmt->fetch();
			}
			
			// Si on n'a pas trouvé de résultat et que la règle à une équivalente inverse (règle bidirectionnelle)
			// Alors on recherche dans la règle opposée		
			if (
					empty($result['id'])
				&&	!empty($this->ruleParams['bidirectional'])
			) {
				
				$stmt = $this->connection->prepare($sqlParamsTarget);
				$stmt->bindValue(":ruleId", $this->ruleParams['bidirectional']);
				$stmt->bindValue(":id", $id);
				$stmt->bindValue(":id_doc", $this->id);
				$stmt->execute();	   				
				$result = $stmt->fetch();				
			}
			
			// If we found a record
			if (!empty($result['id'])) {
				$this->targetId = $result['target_id'];
				// If the document found is Cancel, there is only Cancel documents (see query order) so we return C and not U
				// Except if the rule is bidirectional, in this case, a no send document in the opposite rule means that the data really exists in the target application
				if (
						$result['global_status'] == 'Cancel' 
					&& empty($this->ruleParams['bidirectional'])
				) {
					return 'C';
				} else {
					return 'U';
				}
			}
			// Si on est sur une règle child alors on est focément en update (seule la règle root est autorisée à créer des données)
			// We check now because we take every chance we can to get the target_id
			if ($this->isChild()){			
				return 'U';
			}
			// Si aucune règle avec relation Myddleware_element_id alors on est en création
			return 'C';
		} catch (\Exception $e) {
			$this->typeError = 'E';
			$this->message .= 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error( $this->message );	
			return null;
		}
	}
	
	public function documentCancel() {
		// Search if the document has child documents
		$childDocuments = $this->getChildDocuments();		
		if (!empty($childDocuments)) {
			// We cancel each child, but a child document can be a parent document too, so we make a recursive call
			foreach ($childDocuments as $childDocument) {
				// We don't Cancel a document if it has been already cancelled
				if ($childDocument['global_status'] != 'Cancel') {
					$param['id_doc_myddleware'] = $childDocument['id'];
					$param['jobId'] = $this->jobId;
					$docChild = new document($this->logger, $this->container, $this->connection, $param);
					$docChild->documentCancel();
				}			
			}
		}
		$this->updateStatus('Cancel'); 
	}
	
	public function changeDeleteFlag($deleteFlag) {
		$this->updateDeleteFlag($deleteFlag);
	}
	
	public function updateStatus($new_status) {
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		try {
			// On ajoute un contôle dans le cas on voudrait changer le statut
			$new_status = $this->beforeStatusChange($new_status);
			
			$now = gmdate('Y-m-d H:i:s');
			// Récupération du statut global
			$globalStatus = $this->globalStatus[$new_status];
			// Ajout d'un essai si erreur
			if ($globalStatus == 'Error' || $globalStatus == 'Close') {
				$this->attempt++;
			}
			$query = "	UPDATE Document 
								SET 
									date_modified = :now,
									global_status = :globalStatus,
									attempt = :attempt,
									status = :new_status
								WHERE
									id = :id
								";
			echo 'statut '.$new_status.' id = '.$this->id.'  '.$now.chr(10);			
			// Suppression de la dernière virgule	
			$stmt = $this->connection->prepare($query);
			$stmt->bindValue(":now", $now);
			$stmt->bindValue(":globalStatus", $globalStatus);
			$stmt->bindValue(":attempt", $this->attempt);
			$stmt->bindValue(":new_status", $new_status);
			$stmt->bindValue(":id", $this->id);
			$stmt->execute();
			$this->message .= 'Status : '.$new_status;
			$this->connection->commit(); // -- COMMIT TRANSACTION
			$this->status = $new_status;
			$this->afterStatusChange($new_status);
			$this->createDocLog();
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .=  'Error status update : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error( $this->message );
			$this->createDocLog();
		}
	}
	
	public function updateDeleteFlag($deleted) {
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		try {
			$now = gmdate('Y-m-d H:i:s');
			$query = "	UPDATE Document 
								SET 
									date_modified = :now,
									deleted = :deleted
								WHERE
									id = :id
								";
			echo (!empty($deleted) ? 'Remove' : 'Restore').' document id = '.$this->id.'  '.$now.chr(10);	
			$stmt = $this->connection->prepare($query);
			$stmt->bindValue(":now", $now);
			$stmt->bindValue(":deleted", $deleted);
			$stmt->bindValue(":id", $this->id);
			$stmt->execute();
			$this->message .= (!empty($deleted) ? 'Remove' : 'Restore').' document';
			$this->connection->commit(); // -- COMMIT TRANSACTION
			$this->createDocLog();
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .=  'Failed to '.(!empty($deleted) ? 'Remove ' : 'Restore ').' : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error( $this->message );
			$this->createDocLog();
		}
	}
	
	// Save document relationship
	protected function insertDocumentRelationship ($ruleRelationship, $docRelId) {		
		try {	
			// Add the relationship in the table document Relationship
			$documentRelationship = new DocumentRelationship();
			$documentRelationship->setDocId($this->id);
			$documentRelationship->setDocRelId($docRelId);
			$documentRelationship->setDateCreated(new \DateTime);
			$documentRelationship->setCreatedBy((int)$this->userId);
			$documentRelationship->setSourceField($ruleRelationship['field_name_source']);				
			$this->em->persist($documentRelationship);
			$this->em->flush();	
		} catch (\Exception $e) {
			$this->message .= 'Failed to save the document relationship for the field '.$ruleRelationship['field_name_source'].' : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'W';
			$this->logger->error($this->message);
			return false;
		}		
	}
	
	// Permet d'intervenir avant le changement de statut
	protected function beforeStatusChange($new_status) {
		return $new_status;
	}
	
	// Permet d'intervenir après le changement de statut
	protected function afterStatusChange($new_status) {
	}
	
	// Permet de modifier le type du document
	public function updateType($new_type) {
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		try {
			$now = gmdate('Y-m-d H:i:s');
			$query = "	UPDATE Document 
								SET 
									date_modified = :now,
									type = :new_type
								WHERE
									id = :id
								";
			// Suppression de la dernière virgule	
			$stmt = $this->connection->prepare($query); 
			$stmt->bindValue(":now", $now);
			$stmt->bindValue(":new_type", $new_type);
			$stmt->bindValue(":id", $this->id);
			$stmt->execute();
			$this->message .= 'Type  : '.$new_type;
			$this->connection->commit(); // -- COMMIT TRANSACTION
			$this->createDocLog();
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .= 'Error type   : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error( $this->message );
			$this->createDocLog();
		}
	}
	
	// Permet de modifier le type du document
	public function updateTargetId($target_id) {
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		try {
			$now = gmdate('Y-m-d H:i:s');
			$query = "	UPDATE Document 
								SET 
									date_modified = :now,
									target_id = :target_id
								WHERE
									id = :id
								";
			// Suppression de la dernière virgule	
			$stmt = $this->connection->prepare($query);
			$stmt->bindValue(":now", $now);
			// Target id could contain accent
			$stmt->bindValue(":target_id", utf8_encode($target_id));
			$stmt->bindValue(":id", $this->id); 
			$stmt->execute();
			$this->message .= 'Target id : '.$target_id;
			$this->connection->commit(); // -- COMMIT TRANSACTION
			$this->createDocLog();
			return true;
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .= 'Error target id  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error( $this->message );
			$this->createDocLog();
			return false;
		}
	}
	
	protected function getRelationshipDirection($ruleRelationship) {
		try {
			// Calcul du sens de la relation. Si on ne trouve pas (exemple des relations custom) alors on met 1 par défaut.
			$sqlParams = "	SELECT 
								IF(RuleA.conn_id_source = RuleB.conn_id_source, '1', IF(RuleA.conn_id_source = RuleB.conn_id_target, '-1', '1')) direction
							FROM RuleRelationShip
								INNER JOIN Rule RuleA
									ON RuleRelationShip.rule_id = RuleA.id
									#AND RuleA.deleted = 0
								INNER JOIN Rule RuleB
									ON RuleRelationShip.field_id = RuleB.id		
									#AND RuleB.deleted = 0
							WHERE  
								RuleRelationShip.id = :id 
						";
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue(":id", $ruleRelationship['id']);
			$stmt->execute();	   				
			$result = $stmt->fetch();		
			if (!empty($result['direction'])) {
				return $result['direction'];
			}	
			return null;
		} catch (\Exception $e) {
			return null;
		}
	}
	
	// Permet de récupérer l'id target pour une règle et un id source ou l'inverse
	protected function getTargetId($ruleRelationship,$record_id) {
		try {
			$direction = $this->getRelationshipDirection($ruleRelationship);
			// En fonction du sens de la relation, la recherche du parent id peut-être inversée (recherchée en source ou en cible)
			// Search all documents with target ID not empty in status close or no_send (document canceled but it is a real document)
			if ($direction == '-1') {
				$sqlParams = "	SELECT 
									source_id record_id,
									Document.id document_id								
								FROM Document
								WHERE  
										Document.rule_id = :ruleRelateId 
									AND Document.source_id != '' 
									AND Document.deleted = 0 
									AND Document.target_id = :record_id 
									AND (
											Document.global_status = 'Close' 
										 OR Document.status = 'No_send'
									)	 
								LIMIT 1";	
			}
			elseif ($direction == '1') {
				$sqlParams = "	SELECT 
									target_id record_id,
									Document.id document_id
								FROM Document 
								WHERE  
										Document.rule_id = :ruleRelateId 
									AND Document.source_id = :record_id 
									AND Document.deleted = 0 
									AND Document.target_id != '' 
									AND (
											Document.global_status = 'Close' 
										 OR Document.status = 'No_send'
									)	
								LIMIT 1";	
			}
			else {
				throw new \Exception( 'Failed to find the direction of the relationship with the rule_id '.$ruleRelationship['field_id'].'. ' );
			}
			
			// A mass process exist for migration mode 
			if (!empty($this->ruleDocuments[$ruleRelationship['field_id']])) {
				// We search the target/source id in the array in memory
				if ($direction == '1') {	
					if (!empty($this->ruleDocuments[$ruleRelationship['field_id']]['sourceId'][$record_id])) {
						foreach($this->ruleDocuments[$ruleRelationship['field_id']]['sourceId'][$record_id] as $document) {
							if (
								(
										$document['global_status'] == 'Close'	
									 OR $document['status'] == 'No_send'	
								)	
								AND $document['target_id'] != '' 
							) {
								$result['record_id'] = $document['target_id'];
								$result['document_id'] = $document['id'];
								break;
							}
						}
					}
				} else {
					if (!empty($this->ruleDocuments[$ruleRelationship['field_id']]['targetId'][$record_id])) {
						foreach($this->ruleDocuments[$ruleRelationship['field_id']]['targetId'][$record_id] as $document) {
							if (
								(
										$document['global_status'] == 'Close'	
									 OR $document['status'] == 'No_send'	
								)	
								AND $document['source_id'] != '' 
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
				$stmt->bindValue(":ruleRelateId", $ruleRelationship['field_id']);
				$stmt->bindValue(":record_id", $record_id);
				$stmt->execute();	   				
				$result = $stmt->fetch();		
			}
			if (!empty($result['record_id'])) {
				return $result;
			}
			return null;
		} catch (\Exception $e) {
			$this->message .= 'Error getTargetId  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error( $this->message );
		}	
	}
	
	// Search relate document by status
	protected function searchRelateDocumentByStatus($ruleRelationship,$record_id, $status) {
		try {		
			$direction = $this->getRelationshipDirection($ruleRelationship);		
			// En fonction du sens de la relation, la recherche du parent id peut-être inversée (recherchée en source ou en cible)
			// Search all documents with target ID not empty in status close or no_send (document canceled but it is a real document)
			if ($direction == '-1') {
				$sqlParams = "	SELECT *								
								FROM Document
								WHERE  
										Document.rule_id = :ruleRelateId 
									AND Document.target_id = :record_id 
									AND Document.status = :status 
									AND Document.deleted = 0 
								LIMIT 1";	
			}
			elseif ($direction == '1') {
				$sqlParams = "	SELECT *
								FROM Document 
								WHERE  
										Document.rule_id = :ruleRelateId 
									AND Document.source_id = :record_id 
									AND Document.status = :status 
									AND Document.deleted = 0 
								LIMIT 1";	
			}
			else {
				throw new \Exception( 'Failed to find the direction of the relationship with the rule_id '.$ruleRelationship['field_id'].'. ' );
			}			
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue(":ruleRelateId", $ruleRelationship['field_id']);
			$stmt->bindValue(":record_id", $record_id);
			$stmt->bindValue(":status", $status);
			$stmt->execute();	   				
			$result = $stmt->fetch();
			if (!empty($result['id'])) {
				return $result;
			}
		} catch (\Exception $e) {
			$this->message .= 'Error searchRelateDocumentByStatus  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error( $this->message );
		}	
		return null;
	}
	
	// Permet de renvoyer le statut du document
	public function getStatus() {
		return $this->status;
	}
	
	// Fonction permettant de créer un log pour un docuement
		// Les id de la soluton, de la règle et du document
		// $type peut contenir : I (info;), W(warning), E(erreur), S(succès)
		// $code contient le code de l'erreur
		// $message contient le message de l'erreur avec potentiellement des variable &1, &2...
		// $data contient les varables du message de type array('id_contact', 'nom_contact')
	protected function createDocLog() {
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		try {
			$now = gmdate('Y-m-d H:i:s');
			$query_header = "INSERT INTO Log (created, type, msg, rule_id, doc_id, ref_doc_id, job_id) VALUES (:created,:typeError,:message,:rule_id,:doc_id,:ref_doc_id,:job_id)";
			$stmt = $this->connection->prepare($query_header); 
			$stmt->bindValue(":created",$now);
			$stmt->bindValue(":typeError",$this->typeError);
			$stmt->bindValue(":message", str_replace("'","",utf8_encode($this->message)));
			$stmt->bindValue(":rule_id",$this->ruleId);
			$stmt->bindValue(":doc_id",$this->id);
			$stmt->bindValue(":ref_doc_id",$this->docIdRefError);
			$stmt->bindValue(":job_id",$this->jobId);
			$stmt->execute();
			$this->message = '';
			$this->connection->commit(); // -- COMMIT TRANSACTION
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->logger->error( 'Failed to create log : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
		}
	}
	
}


/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Classes/document.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class document extends documentcore {
		
	}
}
