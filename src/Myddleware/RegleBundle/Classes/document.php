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
	protected $type_document;
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
	protected $tools;
	protected $globalStatus = array(
										'New' => 'Open',
										'Predecessor_OK' => 'Open',
										'Relate_OK' => 'Open',
										'Transformed' => 'Open',
										'Ready_to_send' => 'Open',
										'Filter_OK' => 'Open',
										'Send' => 'Close',
										'Filter' => 'Cancel',
										'No_send' => 'Cancel',
										'Cancel' => 'Cancel',
										'Create_KO' => 'Cancel',
										'Filter_KO' => 'Error',
										'Predecessor_KO' => 'Error',
										'Relate_KO' => 'Error',
										'Error_transformed' => 'Error',
										'Error_checking' => 'Error',
										'Error_sending' => 'Error'
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
			'Create_KO' => 'flux.status.create_ko',			
			'Predecessor_KO' => 'flux.status.predecessor_ko',
			'Relate_KO' => 'flux.status.relate_ko',
			'Error_transformed' => 'flux.status.error_transformed',
			'Error_checking' => 'flux.status.error_checking',
			'Error_sending' => 'flux.status.error_sending'		
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
				$this->type_document = $this->document_data['type'];
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
			$this->message .= 'Failed to retrieve document : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
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
			$query_header = "INSERT INTO Document (id, rule_id, date_created, date_modified, created_by, modified_by, source_id, target_id,source_date_modified, mode, type, parent_id) VALUES";

			// Récupération du type de document : vérification de l'existance d'un enregistrement avec le même ID dans Myddleware (passage du type docuement en U ou C)
			$this->type_document = $this->checkRecordExist($this->sourceId);	
	
			// SI la règle est en mode search alors on met le document en mode search aussi
			if ($this->ruleMode == 'S') {
				$old_type_document = $this->type_document;
				$this->type_document = 'S';
			}	
			
			// Création de la requête d'entête
			$date_modified = $this->data['date_modified'];
			// Encodage en UTF8, nécessaire pour SAP car les id peuvent avoir des accents
			$query_header .= "('$this->id','$this->ruleId','$this->dateCreated','$this->dateCreated','$this->userId','$this->userId','$this->sourceId','$this->targetId','$date_modified','$this->ruleMode','$this->type_document','$this->parentId')";
			$stmt = $this->connection->prepare($query_header); 
			$stmt->execute();
		
			// Si la règle est seulement en création et que le document est en update alors on passe au document suivant
			// Cependant si le document vient d'une règle child alors on autorise l'Update
			if (
					$this->ruleMode == 'C' 
				&& $this->type_document == 'U'
				&& !$this->isChild()
			) {
				$this->message .= 'Rule mode only allows to create data. Filter because this document updates data.';
				$this->updateStatus('Filter');
				$createDocument = $this->insertDataTable($this->data,'S');
			}
			// Si la règle est seulement en recherche et que le document est en update alors on passe au document suivant
			elseif (
					$this->ruleMode == 'S' 
				&& !empty($old_type_document)
				&& $old_type_document == 'U'
			) {
				$this->message .= 'Rule mode only allows to search data. Filter because this document updates data.';
				$this->updateStatus('Filter');
				$createDocument = $this->insertDataTable($this->data,'S');
			}
			elseif (
					$this->ruleMode == 'S' 
				&& $this->type_document == 'U'
			) {
				$this->message .= 'Rule mode only allows to search data. Filter because this document updates data.';
				$this->updateStatus('Filter');
				$createDocument = $this->insertDataTable($this->data,'S');
			}
			elseif (!empty($this->type_document)) {
				// Mise à jour de la table des données source
				$createDocument = $this->insertDataTable($this->data,'S');
				if ($createDocument) {
					$this->updateStatus('New');
				}
				else {
					throw new \Exception( 'Failed to create source data for this document.' );
				}
			}
			else {
				throw new \Exception( 'Failed to get the mode of the document. Failed to create the document.' );
			}
			return $createDocument;
		} catch (\Exception $e) {
			$this->message .= 'Failed to create document (id source : '.$this->id.'): '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->updateStatus('Create_KO');
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
			$this->message .= 'Failed to filter document : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
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
			// Vérification que pour les documents en modification, les création n'ont pas de prédécesseur
			if ($this->document_data['type'] == 'U') {
				// Avant de vérifier les prédécesseur, on re vérifie que le document en cours est toujours du type UPDATE. 
				// En effet si un docuement précédent a été annulé il se peut que se docuement doivent être transformé en CREATE
				// On ne fait cette vérification que si le target_id n'est pas renseigné, il peut l'etre quand on se sert de myddleware_element_id (plusieurs modules source pour un seul cible)
				if (empty($this->targetId)) {
					$this->type_document = $this->checkRecordExist($this->sourceId);	
				}
				// Si le document doit réellement changé en CREATE, on modifie le type du document et on passe le document en Predecessor_OK
				if($this->type_document == 'C') {
					$this->updateType($this->type_document);
				}
				// Sinon on fait la recherche du prédécesseur classique
				else {
					$rules[] = $this->document_data['rule_id'];
					// We check the bidirectionnal rule too if it exists
					if (!empty($this->ruleParams['bidirectional'])) {
						$rules[] = $this->ruleParams['bidirectional'];
					}
					foreach ($rules as $ruleId) {
						// Selection des documents antérieurs de la même règle avec le même id au statut différent de closed et Cancel
						// If rule child, document open in ready_to_send are accepted because data in ready to send could be pending
						$sqlParams = "	SELECT 
											Document.id,							
											Document.rule_id,
											Rule.id rule_parent,
											if(Rule.deleted=1,1,0) rule_parent_deleted,
											Document.status,
											Document.global_status											
										FROM Document
											LEFT OUTER JOIN RuleRelationShip
												ON RuleRelationShip.field_id = Document.rule_id
												AND RuleRelationShip.parent = 1
											LEFT OUTER JOIN Rule
												ON Rule.id = RuleRelationShip.rule_id 									
										WHERE 
												Document.rule_id = :rule_id 
											AND Document.source_id = :source_id 
											AND Document.date_created < :date_created  
										HAVING 
												rule_parent_deleted != 1
											AND (
													global_status = 'Error'
												OR (
														global_status = 'OPEN'
													AND (
															status != 'Ready_to_send'
														OR (
																status = 'Ready_to_send'
															AND rule_parent IS NULL
														)
													)
												)	
											)
										LIMIT 1	
										";								
						$stmt = $this->connection->prepare($sqlParams);
						$stmt->bindValue(":rule_id", $ruleId);
						$stmt->bindValue(":source_id", $this->document_data['source_id']);
						$stmt->bindValue(":date_created", $this->document_data['date_created']);
						$stmt->execute();	   				
						$result = $stmt->fetch();						
						// if id found, we stop to send an error
						if (!empty($result['id'])) {
							break;
						}
					}

					// Si un prédécesseur non clos est trouvé on passe le document au statut Predecessor_KO
					if (!empty($result['id'])) {		
						$this->docIdRefError = $result['id'];
						throw new \Exception('The document '.$result['id'].' is on the same record and is not closed. This document is queued. ');
					}
					else {
						// Mise à jour du target id si celui-ci n'était pas renseigné (document précédent sans target id au moment de la creation de ce docuement)
						if (empty($this->targetId)) {
							// Récupération de $this->targetId dans les documents clos
							$this->checkRecordExist($this->document_data['source_id']);
							if (!empty($this->targetId)) {
								if(!$this->updateTargetId($this->targetId)) {
									throw new \Exception('Failed to update the target id. Failed to unblock this update document. ');
								}
							}
							// Si on est sur une règle groupée avec un child alors il est possible qu'il n'y ait pas de prédécesseur 
							// mais que l'on vienne quand même mettre à jour la règle root.
							elseif (!$this->isChild()) {
								throw new \Exception('Failed to retrieve the target in a parent document. Failed to unlock this update document. ');
							}
						}
					}
				}
			}

			$this->updateStatus('Predecessor_OK');
			$this->connection->commit(); // -- COMMIT TRANSACTION
			return true;
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .= 'Failed to check document predecessor : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
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
					
					// Selection des documents antérieurs de la même règle avec le même id au statut différent de closed		
					$targetId = $this->getTargetId($ruleRelationship,$this->sourceData[$ruleRelationship['field_name_source']]);
					if (empty($targetId['record_id'])) {
						$error = true;
						break;
					}
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
				AND $this->type_document == 'C'
			) {			
				$this->type_document = $this->checkRecordExist($this->sourceId);					
				if ($this->type_document == 'U') {
					$this->updateTargetId($this->targetId);
					$this->updateType('U');
				}
			}
			$this->updateStatus('Relate_OK');
					
			$this->connection->commit(); // -- COMMIT TRANSACTION	
			return true;
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .= 'No data for the field '.$ruleRelationship['field_name_source'].' in the rule '.$this->ruleName.'. Failed to check document related : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
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
						$this->type_document == 'U'
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
			$this->message .= 'Failed to transform document : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
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
			
			// Si le document est une modification de données alors on va chercher les données dans la cible avec l'ID
			// And if the rule is not a child (no target id is required, it will be send with the parent rule)
			if (
					$this->type_document == 'U'
				 &&	!$this->isChild()
			) {
				// Récupération des données avec l'id de la cible
				$searchFields = array('id' => $this->targetId);
				$history = $this->getDocumentHistory($searchFields);
	
				// Ici la table d'historique doit obligatoirement avoir été mise à jour pour continuer
				if ($history !== -1) {
					$this->updateStatus('Ready_to_send');
				}
				else {
					throw new \Exception('Failed to retrieve record in target system before update. Id target : '.$this->targetId.'. Check this record is not deleted.');
				}
			}
			// Si on est en mode recherche on récupère la donnée cible avec les paramètre de la source
			elseif ($this->type_document == 'S') {
				if (!empty($this->sourceData)) {
					// Un seul champ de recherche pour l'instant. Les règle recherche ne peuvent donc n'avoir qu'un seul champ
					$searchFields[$this->ruleField[0]['target_field_name']] = $this->getTransformValue($this->sourceData,$this->ruleFields[0]);
				}
				else {
					throw new \Exception('Failed to search data because there is no field in the query. This document is queued. ');
				}

				if(!empty($searchFields)) {
					$history = $this->getDocumentHistory($searchFields);
				} 
				else {
					$history = -1;
				}
		
				// Gestion de l'erreur
				if ($history === -1) {
					throw new \Exception('Failed to search data because the query is empty. This document is queued. ');
				}
				// Si la fonction renvoie false (pas de données trouvée dans la cible) ou true (données trouvée et correctement mise à jour)
				elseif ($history === false) {
					$rule = $this->getRule();
					throw new \Exception('No data found in the target application. To synchronize data, you have to create a record in the target module ('.$rule['module_target'].') with these data : '.print_r($searchFields,true).'. Then rerun this document. This document is queued. ');
				}
				// renvoie l'id : Si une donnée est trouvée dans le système cible alors on passe le flux à envoyé car le lien est fait
				else {
					$this->updateStatus('Send');
					$this->updateTargetId($history);
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
					$this->updateStatus('Ready_to_send');
				}
				// renvoie l'id : Si une donnée est trouvée dans le système cible alors on modifie le document pour ajouter l'id target et modifier le type
				else {
					$this->updateStatus('Ready_to_send');
					$this->updateTargetId($history);
					$this->updateType('U');
				}
			}
			// Sinon on mets directement le document en ready to send (example child rule)
			else {
				$this->updateStatus('Ready_to_send');
			}		
			// S'il n'y a aucun changement entre la cible actuelle et les données qui seront envoyée alors on clos directement le document
			// Si le document est en type recherche, alors la sible est forcément égal à la source et il ne fait pas annuler le doc. 
			// We always send data if the rule is parent (the child data could be different even if the parent data didn't change)
			if (	
					$this->type_document != 'S' 
				&&	!$this->isParent()
			) {
				$this->checkNoChange();
			}		
			$this->connection->commit(); // -- COMMIT TRANSACTION	
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .= $e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->updateStatus('Error_checking');
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
					throw new \Exception( 'Failed to get the data in the document for the field '.$childRuleId['field_name_source'].'. The query to search to generate child data can\'t be created');
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
	protected function getDocumentHistory ($searchFields) {	
		// Permet de renseigner le tableau rule avec les données d'entête
		$rule = $this->getRule();
		$read['module'] = $rule['module_target'];
		$read['fields'] = $this->getTargetFields();
		$read['query'] = $searchFields;
		$read['ruleParams'] = $this->ruleParams;
		$read['rule'] = $rule;
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
			$this->message .= 'Error getSourceData  : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error( $this->message );
		}		
		return false;
	}
	
	
	// Insert source data in table documentData
	protected function insertDataTable($data,$type) {
		try {		
			// We save only fields which belong to the rule
			if (!empty($this->ruleFields)) {
				foreach ($this->ruleFields as $ruleField) {
					if ($type == 'S') {
						// We don't create entry in the array dataInsert when the filed is my_value because there is no filed in the source, just a formula to the target application
						if ($ruleField['source_field_name']=='my_value') {
							continue;
						}
						// It could be several fields in the source fields (in case of formula)
						$sourceFields = explode(";",$ruleField['source_field_name']);
						foreach ($sourceFields as $sourceField) {
							$dataInsert[$sourceField] = $data[$sourceField];
						}
					} else {
						// Some field can't be retrived from the target application (history). For example the field password on the module user of Moodle
						if (
								empty($data[$ruleField['target_field_name']])
							 && $type == 'H'	
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
						$dataInsert[$ruleRelationship['field_name_source']] = ($ruleRelationship['field_name_source'] == 'Myddleware_element_id' ? $data['id'] : $data[$ruleRelationship['field_name_source']]);
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
			$this->message .= 'Failed : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
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
				$this->message .= 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
				$this->typeError = 'E';
				$this->logger->error( $this->message );
			}		
		}
		return false;
	}
	
	// Mise à jour de la table des données cibles
	protected function updateTargetTable() {
		if (!empty($this->sourceData)) {
			try{			
				// Loop on every target field and calculate the value
				if (!empty($this->ruleFields)) {
					foreach ($this->ruleFields as $ruleField) {
						$value = $this->getTransformValue($this->sourceData,$ruleField);
						if ($value === false) {
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
						if ($value === false) {
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
				$this->message .= 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
				$this->typeError = 'E';
				$this->logger->error( $this->message );
			}		
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
					throw new \Exception( 'Target id not found for id source '.$source[$ruleField['source_field_name']].' of the rule '.$ruleField['related_rule'] );
				}
			}
			// Si le champ est envoyé sans transformation
			elseif (isset($source[$ruleField['source_field_name']])) {			
				return $this->checkField($source[$ruleField['source_field_name']]);
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
			$this->message .= 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$this->logger->error( $this->message );
			return false;
		}

	}
	
	// Fonction permettant de contrôle les données. 
	protected function checkField($value) {
		if (!empty($value)) {
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
			$this->message .= 'Error getRule  : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
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
			$sqlGetChilds = "SELECT * FROM Document WHERE parent_id = :docId";		
			$stmt = $this->connection->prepare($sqlGetChilds);
			$stmt->bindValue(":docId", $this->id);
			$stmt->execute();	    
			return $stmt->fetchAll();	
		} catch (\Exception $e) {
			$this->typeError = 'E';
			$this->message .= 'Error getTargetFields  : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
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
	protected function getTargetFields() {
		try {
			if (!empty($this->ruleId)) {
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
						$fields[] = $ruleRelationShip['field_name_target'];
					}
				}
				
				return $fields;
			}
		} catch (\Exception $e) {
			$this->typeError = 'E';
			$this->message .= 'Error getTargetFields  : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
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
			$this->logger->error( 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
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
							FROM Rule
								INNER JOIN Document 
									ON Document.rule_id = Rule.id
							WHERE 
									Rule.id IN (:ruleId)									
								AND (
										Document.global_status != 'Cancel'
									 OR (
											Document.global_status = 'Cancel'	
										AND Document.status = 'No_send'
									)
								)
								AND	Document.source_id = :id
								AND Document.id != :id_doc
							ORDER BY target_id DESC, global_status DESC
							LIMIT 1";
							
			// On prépare la requête pour rechercher dans la partie target
			$sqlParamsTarget = "SELECT 
								Document.id, 
								Document.source_id target_id, 
								Document.global_status 
							FROM Rule
								INNER JOIN Document 
									ON Document.rule_id = Rule.id
							WHERE 
									Rule.id IN (:ruleId)									
								AND (
										Document.global_status != 'Cancel'
									 OR (
											Document.global_status = 'Cancel'	
										AND Document.status = 'No_send'
									)
								)	
								AND	Document.target_id = :id
								AND Document.id != :id_doc
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
	
			// If no relationship or no child rule
			// Recherche d'un enregitsrement avec un target id sur la même source
			$stmt = $this->connection->prepare($sqlParamsSoure);
			$stmt->bindValue(":ruleId", $this->ruleId);
			$stmt->bindValue(":id", $id);
			$stmt->bindValue(":id_doc", $this->id);
		    $stmt->execute();	   				
			$result = $stmt->fetch();
		
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
			$this->message .= 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
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
			$this->message .=  'Error status update : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error( $this->message );
			$this->createDocLog();
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
			$this->message .= 'Error type   : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
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
			$stmt->bindValue(":target_id", $target_id);
			$stmt->bindValue(":id", $this->id); 
			$stmt->execute();
			$this->message .= 'Target id : '.$target_id;
			$this->connection->commit(); // -- COMMIT TRANSACTION
			$this->createDocLog();
			return true;
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .= 'Error target id  : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
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
								FROM Rule
									INNER JOIN Document 
										ON Document.rule_id = Rule.id
								WHERE  
										Rule.id = :ruleRelateId 
									AND Document.source_id != '' 
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
								FROM Rule
									INNER JOIN Document 
										ON Document.rule_id = Rule.id
								WHERE  
										Rule.id = :ruleRelateId 
									AND Document.source_id = :record_id 
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
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue(":ruleRelateId", $ruleRelationship['field_id']);
			$stmt->bindValue(":record_id", $record_id);
			$stmt->execute();	   				
			$result = $stmt->fetch();
			if (!empty($result['record_id'])) {
				return $result;
			}
			return null;
		} catch (\Exception $e) {
			$this->message .= 'Error getTargetId  : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error( $this->message );
		}	
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
			$this->message = substr(str_replace("'","",$this->message),0,1000);
			$query_header = "INSERT INTO Log (created, type, msg, rule_id, doc_id, ref_doc_id, job_id) VALUES ('$now','$this->typeError','$this->message','$this->ruleId','$this->id','$this->docIdRefError','$this->jobId')";
			$stmt = $this->connection->prepare($query_header); 
			$stmt->execute();
			$this->message = '';
			$this->connection->commit(); // -- COMMIT TRANSACTION
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->logger->error( 'Failed to create log : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
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
