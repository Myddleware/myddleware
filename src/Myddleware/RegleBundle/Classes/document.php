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
// use Myddleware\RegleBundle\Encryption\encryption as enCrypt; // Encryption
use Myddleware\RegleBundle\Classes\tools as MyddlewareTools; // SugarCRM Myddleware

class documentcore { 
	
	public $id;
	
	protected $typeError = 'S';
	protected $message = '';
	protected $dateCreated;
	protected $connection;
	protected $ruleName;
	protected $ruleVersion;
	protected $ruleMode;
	protected $ruleId;
	protected $ruleFields;
	protected $fieldsType;
	protected $ruleRelationships;
	protected $ruleParams;
	protected $sourceId;
	protected $targetId;
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
										'Error_history' => 'Error',
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
			'Error_history' => 'flux.status.error_history',
			'Error_sending' => 'flux.status.error_sending'		
		);		
	}
	
    // Instanciation de la classe de génération de log Symfony
    public function __construct(Logger $logger, Container $container, Connection $dbalConnection, $param) {
		$this->connection = $dbalConnection;
    	$this->logger = $logger;
		$this->container = $container;
		
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
		if (!empty($param['fieldsType'])) {
			$this->fieldsType = $param['fieldsType'];
		}	

		// Stop the processus if the job has been manually stopped
		if ($this->getJobStatus() != 'Start') {
			$this->jobActive = false;
		}		
		
		// Si le mode est Front office alors on n'initialise pas les attributs de l'objet document
		if(
				empty($param['mode']) 
			 || (
					!empty($param['mode'])
				&& $param['mode'] != 'front_office'
			)
		) {
			if (!empty($param['id_doc_myddleware'])) {
				$this->setDocument($param['id_doc_myddleware']);
			}
			else {
				$this->id = uniqid('', true);
				$this->dateCreated = gmdate('Y-m-d H:i:s');
				$this->ruleName = $param['rule']['rule_name_slug'];
				$this->ruleVersion = $param['rule']['rule_version'];
				$this->ruleMode = $param['rule']['rule_mode'];
				$this->ruleId = $param['rule']['rule_id'];
				$this->ruleFields = $param['ruleFields'];
				$this->data = $param['data'];
				$this->sourceId = $this->data['id'];
				$this->userId = $param['rule']['rule_created_by'];
				$this->status = 'New';
				$this->attempt = 0;
			} 
			// Ajout des paramètre de la règle
			$this->setRuleParams();
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
								Documents.*, 
								Rule.rule_name_slug,
								Rule.rule_version,
								RuleParams.rulep_value rule_mode,
								Rule.conn_id_source,
								Rule.rule_module_source
							FROM Documents 
								INNER JOIN Rule
									ON Documents.rule_id = Rule.rule_id
								INNER JOIN RuleParams
									ON  RuleParams.rule_id = Rule.rule_id
									AND rulep_name= 'mode'
							WHERE id = :id_doc";											
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
				$this->ruleName = $this->document_data['rule_name_slug'];
				$this->ruleVersion = $this->document_data['rule_version'];
				$this->ruleMode = $this->document_data['rule_mode'];
				$this->type_document = $this->document_data['type'];
				$this->attempt = $this->document_data['attempt'];
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
			$query_header = "INSERT INTO Documents (id, rule_id, date_created, date_modified, created_by, modified_by, source_id, target_id,source_date_modified, mode, type) VALUES";

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
			$query_header .= "('$this->id','$this->ruleId','$this->dateCreated','$this->dateCreated','$this->userId','$this->userId','$this->sourceId','$this->targetId','$date_modified','$this->ruleMode','$this->type_document')";
			$stmt = $this->connection->prepare($query_header); 
			$stmt->execute();
		
			// Si la règle est seulement en création et que le document est en update alors on passe au document suivant
			// Cependant si le document vient d'une règle child alors on autorise l'Update
			if (
					$this->ruleMode == 'C' 
				&& $this->type_document == 'U'
				&& (
						empty($this->ruleParams['group'])
					|| (
							!empty($this->ruleParams['group'])
						&& $this->ruleParams['group'] != 'child'
					)
				)
			) {
				$this->message .= 'Rule mode only allows to create data. Filter because this document updates data.';
				$this->updateStatus('Filter');
				$createDocuement = $this->updateSourceTable();
			}
			// Si la règle est seulement en recherche et que le document est en update alors on passe au document suivant
			elseif (
					$this->ruleMode == 'S' 
				&& !empty($old_type_document)
				&& $old_type_document == 'U'
			) {
				$this->message .= 'Rule mode only allows to search data. Filter because this document updates data.';
				$this->updateStatus('Filter');
				$createDocuement = $this->updateSourceTable();
			}
			elseif (
					$this->ruleMode == 'S' 
				&& $this->type_document == 'U'
			) {
				$this->message .= 'Rule mode only allows to search data. Filter because this document updates data.';
				$this->updateStatus('Filter');
				$createDocuement = $this->updateSourceTable();
			}
			elseif (!empty($this->type_document)) {
				// Mise à jour de la table des données source
				$createDocuement = $this->updateSourceTable();
				if ($createDocuement) {
					$this->updateStatus('New');
				}
				else {
					throw new \Exception( 'Failed to create source data for this document.' );
				}
			}
			else {
				throw new \Exception( 'Failed to get the mode of the document. Failed to create the document.' );
			}
			return $createDocuement;
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
				// Récupération des données source
				$this->getSourceData();
			
				// Boucle sur les filtres
				foreach ($ruleFilters as $ruleFilter) {			
					if(!$this->checkFilter($this->sourceData[$ruleFilter['rfi_target']],$ruleFilter['rfi_type'],$ruleFilter['rfi_value'])) {
						$this->message .= 'This document is filtered. This operation is false : '.$ruleFilter['rfi_target'].' '.$ruleFilter['rfi_type'].' '.$ruleFilter['rfi_value'].'.';
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
		$sqlJobDetail = "SELECT * FROM Job WHERE job_id = :jobId";
		$stmt = $this->connection->prepare($sqlJobDetail);
		$stmt->bindValue(":jobId", $this->jobId);
		$stmt->execute();	    
		$job = $stmt->fetch(); // 1 row
		if (!empty($job['job_status'])) {
			return $job['job_status'];
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
			case 'is':
				if (strtoupper($fieldValue) == strtoupper($filterValue)) {
					return true;
				}
				else {
					return false;
				}
				break;
			case 'not':
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
					// Selection des documents antérieurs de la même règle (toute version confondues) avec le même id au statut différent de closed et Cancel
					$sqlParams = "	SELECT 
										Documents.id							
									FROM Documents
										INNER JOIN Rule
											ON Documents.rule_id = Rule.rule_id
											AND Rule.rule_name_slug = :rule_name_slug
										INNER JOIN Rule Rule_version
											ON Rule_version.rule_name = Rule.rule_name
									WHERE 
											Rule_version.conn_id_source = :conn_id_source 
										AND Rule_version.rule_module_source = :rule_module_source  	
										AND Documents.source_id = :source_id 
										AND Documents.global_status NOT IN ('Close','Cancel') 
										AND Documents.date_created < :date_created  
									LIMIT 1";								
					$stmt = $this->connection->prepare($sqlParams);
					$stmt->bindValue(":rule_name_slug", $this->document_data['rule_name_slug']);
					$stmt->bindValue(":conn_id_source", $this->document_data['conn_id_source']);
					$stmt->bindValue(":source_id", $this->document_data['source_id']);
					$stmt->bindValue(":rule_module_source", $this->document_data['rule_module_source']);
					$stmt->bindValue(":date_created", $this->document_data['date_created']);
					$stmt->execute();	   				
					$result = $stmt->fetch(); 				
					
					// Si aucun prédécesseur en erreur sur la règle en cours on vérifie les document de la règle inverse (bidirectionnelle) si elle existe
					if (
							empty($result['id'])
						&&	!empty($this->ruleParams['bidirectional'])
					) {
						$sqlParams = "	SELECT 
											Documents.id							
										FROM Documents
											INNER JOIN Rule
												ON Documents.rule_id = Rule.rule_id
												AND Rule.rule_id = :bidirectional
											INNER JOIN Rule Rule_version
												ON Rule_version.rule_name = Rule.rule_name
										WHERE 
												Rule_version.conn_id_target = :conn_id_source 
											AND Rule_version.rule_module_target = :rule_module_source  	
											AND Documents.target_id = :source_id  
											AND Documents.global_status NOT IN ('Close','Cancel') 
											AND Documents.date_created < :date_created  
										LIMIT 1";
						$stmt = $this->connection->prepare($sqlParams);
						$stmt->bindValue(":bidirectional", $this->ruleParams['bidirectional']);
						$stmt->bindValue(":conn_id_source", $this->document_data['conn_id_source']);
						$stmt->bindValue(":rule_module_source",$this->document_data['rule_module_source']);
						$stmt->bindValue(":source_id",$this->document_data['source_id']);
						$stmt->bindValue(":date_created",$this->document_data['date_created']);
						$stmt->execute();	   				
						$result = $stmt->fetch();
					} 
				
					// Si un prédécesseur non clos est trouvé on passe le document au statut Predecessor_KO
					if (!empty($result['id'])) {
						$this->docIdRefError = $result['id'];
						throw new \Exception('Another document for the same record is not close. This document is queued. ');
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
							elseif (
									empty($this->ruleParams['group'])
								|| (
										!empty($this->ruleParams['group'])
									&& $this->ruleParams['group'] != 'child'
								)
							) {
								throw new \Exception('Failed to retrieve the target in a parent document. Failed to unblock this update document. ');
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
	public function ckeckParentDocument($ruleRelationships) {
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
					!empty($ruleRelationships)
				&& empty($this->ruleParams['group'])
			) {
				$this->getSourceData();
				$error = false;
				// Vérification de chaque relation de la règle
				foreach ($ruleRelationships as $ruleRelationship) {			
					if(empty(trim($this->sourceData[$ruleRelationship['rrs_field_name_source']]))) {				
						continue; // S'il n'y a pas de relation, on envoie sans erreur
					}				
					// Selection des documents antérieurs de la même règle avec le même id au statut différent de closed		
					$targetId = $this->getTargetId($ruleRelationship,$this->sourceData[$ruleRelationship['rrs_field_name_source']]);
					if (empty($targetId)) {
						$error = true;
						break;
					}
				}
				// Si aucun document parent n'est trouvé alors bloque le document
				if ($error) {
					// récupération du nom de la règle pour avoir un message plus clair
					$sqlParams = "	SELECT rule_name FROM Rule WHERE rule_id = :rule_id";								
					$stmt = $this->connection->prepare($sqlParams);
					$stmt->bindValue(":rule_id", $ruleRelationship['rrs_field_id']);
					$stmt->execute();	   				
					$ruleResult = $stmt->fetch(); 
					$direction = $this->getRelationshipDirection($ruleRelationship);
					throw new \Exception( 'Failed to retrieve a related document. No data for the field '.$ruleRelationship['rrs_field_name_source'].'. There is not record with the '.($direction == '-1' ? 'source_id' : 'target_id').' '.$this->sourceData[$ruleRelationship['rrs_field_name_source']].' in the rule '.$ruleResult['rule_name'].'. This document is queued. ');
				}
			}
			$this->updateStatus('Relate_OK');
			$this->connection->commit(); // -- COMMIT TRANSACTION	
			return true;
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .= 'No data for the field '.$ruleRelationship['rrs_field_name_source'].' in the rule '.$this->ruleName.'. Failed to check document related : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
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
				if (
						$this->type_document == 'U'
					&& empty($this->targetId)
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
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		try {
			
			// Si le document est une modification de données alors on va chercher les données dans la cible avec l'ID
			if ($this->type_document == 'U') {
				// Récupération des données avec l'id de la cible
				$searchFields = array('id' => $this->targetId);
				$history = $this->getDocumentHistory($searchFields);
	
				// Ici la table d'historique doit obligatoirement avoir été mise à jour pour continuer
				if ($history !== -1) {
					$this->updateStatus('Ready_to_send');
					$return = true;
				}
				else {
					$this->message .= 'Failed to retrieve record in target system before update. Id target : '.$this->targetId.'. Check this record is not deleted.';
					$this->typeError = 'E';
					$this->updateStatus('Error_history');
					$return = false;
				}
			}
			// Si on est en mode recherche on récupère la donnée cible avec les paramètre de la source
			elseif ($this->type_document == 'S') {
				$this->getSourceData();
				if (!empty($this->sourceData)) {
					// Un seul champ de recherche pour l'instant. Les règle recherche ne peuvent donc n'avoir qu'un seul champ
					$searchFields[$this->ruleField[0]['rulef_target_field_name']] = $this->getTransformValue($this->sourceData,$this->ruleFields[0]);
				}
				else {
					$this->message .= 'Failed to search data because there is no field in the query. This document is queued. ';
					$this->typeError = 'E';
					$this->updateStatus('Error_history');
					$return = false;
				}

				if(!empty($searchFields)) {
					$history = $this->getDocumentHistory($searchFields);
				} 
				else {
					$history = -1;
				}
		
				// Gestion de l'erreur
				if ($history === -1) {
					$this->message .= 'Failed to search data because the query is empty. This document is queued. ';
					$this->typeError = 'E';
					$this->updateStatus('Error_history');
					$return = false;
				}
				// Si la fonction renvoie false (pas de données trouvée dans la cible) ou true (données trouvée et correctement mise à jour)
				elseif ($history === false) {
					$rule = $this->getRule();
					$this->message .= 'No data found in the target application. To synchronize data, you have to create a record in the target module ('.$rule['rule_module_target'].') with these data : '.print_r($searchFields,true).'. Then rerun this document. This document is queued. ';
					$this->typeError = 'E';
					$this->updateStatus('Error_history');
					$return = false;
				}
				// renvoie l'id : Si une donnée est trouvée dans le système cible alors on passe le flux à envoyé car le lien est fait
				else {
					$this->updateStatus('Send');
					$this->updateTargetId($history);
					$return = true;
				}
			}
			// Si on est en création et que la règle a un paramètre de recherche de doublon, on va chercher dans la cible
			elseif (!empty($this->ruleParams['duplicate_fields'])) {
				$duplicate_fields = explode(';',$this->ruleParams['duplicate_fields']);
				// Charge les données source du document dans $this->sourceData
				$this->getSourceData();
				// Récupération des valeurs de la source pour chaque champ de recherche
				foreach($duplicate_fields as $duplicate_field) {
					foreach ($this->ruleFields as $ruleField) {
						if($ruleField['rulef_target_field_name'] == $duplicate_field)
							$sourceDuplicateField = $ruleField['rulef_source_field_name'];
					}
					// On ne fait pas de recherche dans la cible sur des champs vides. S'ils sont vides, ils sont excluent.
					if (!empty($sourceDuplicateField)) {
						$searchFields[$duplicate_field] = $this->sourceData[$sourceDuplicateField];
					}
				}
				if(!empty($searchFields)) {
					$history = $this->getDocumentHistory($searchFields);
				} 
				else {
					$history = -1;
				}
				if ($history === -1) {
					$this->message .= 'Failed to search duplicate data in the target system. This document is queued. ';
					$this->typeError = 'E';
					$this->updateStatus('Error_history');
					$return = false;
				}
				// Si la fonction renvoie false (pas de données trouvée dans la cible) ou true (données trouvée et correctement mise à jour)
				elseif ($history === false) {
					$this->updateStatus('Ready_to_send');
					$return = true;
				}
				// renvoie l'id : Si une donnée est trouvée dans le système cible alors on modifie le document pour ajouter l'id target et modifier le type
				else {
					$this->updateStatus('Ready_to_send');
					$this->updateTargetId($history);
					$this->updateType('U');
					$return = true;
				}
			}
			// Sinon on mets directement le document en ready to send
			else {
				$this->updateStatus('Ready_to_send');
				$return = true;
			}
			
			// S'il n'y a aucun changement entre la cible actuelle et les données qui seront envoyée alors on clos directement le document
			// Si le document est en type recherche, alors la sible est forcément égal à la source et il ne fait pas annuler le doc. 
			if ($this->type_document != 'S') {
				$this->checkNoChange();
			}
			$this->connection->commit(); // -- COMMIT TRANSACTION	
			return $return;
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .= 'Failed to get target document : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error($this->message);
			$this->createDocLog();
			return false;
		}	
	}
	
	// Vérifie si les données sont différente entre ce qu'il y a dans la cible et ce qui devrait être envoyé
	protected function checkNoChange() {
		// Récupération des données à envoyer
		$tableTarget = "z_".$this->ruleName."_".$this->ruleVersion."_target";
		$sendQuery = "SELECT * FROM $tableTarget WHERE id_".$this->ruleName."_".$this->ruleVersion."_target = '$this->id'";		
		$stmt = $this->connection->prepare($sendQuery);
		$stmt->execute();	   				
		$send = $stmt->fetch();

		// Récupération des données actuele de la cible
		$tableHistory = "z_".$this->ruleName."_".$this->ruleVersion."_history";
		$currentQuery = "SELECT * FROM $tableHistory WHERE id_".$this->ruleName."_".$this->ruleVersion."_history = '$this->id'";		
		$stmt = $this->connection->prepare($currentQuery);
		$stmt->execute();	   				
		$history = $stmt->fetch();
		
		// Comparaison des tableau
		if (
				!empty($history)
			&& !empty($send)
		) {
			$diff1 = array_diff($history, $send);
			$diff2 = array_diff($send, $history);
			// Si aucun changement alors clôture directe du document sans envoi à la cible
			if (
					empty($diff1)
				&&	empty($diff2)
			) {
				$this->message .= 'Identical data to the target system. This document is canceled. ';
				$this->typeError = 'W';
				$this->updateStatus('No_send');
			}
		}
	}
	
	// Récupération des données dans la cible et sauvegarde dans la table d'historique
	protected function getDocumentHistory ($searchFields) {	
		// Permet de renseigner le tableau rule avec les données d'entête
		$rule = $this->getRule();
		$read['module'] = $rule['rule_module_target'];
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
	protected function getSourceData() {
		try {
			$table = "z_".$this->ruleName."_".$this->ruleVersion."_source";
			$tableId = "id_".$this->ruleName."_".$this->ruleVersion."_source";
		
			// Récupération de toutes les sources au statut "New" pour la règle 
			$sqlSource = "SELECT $table.* 
							FROM Documents
								INNER JOIN $table 
									ON Documents.id = $table.$tableId 
							WHERE 
									Documents.id = :id				
								";
								/*statut Predecessor_OK à revoir, sera certainement relate_OK*/ 
			$stmt = $this->connection->prepare($sqlSource);
			$stmt->bindValue(":id", $this->id);
			$stmt->execute();	   				
			$sourceData = $stmt->fetch();		
			$this->sourceData = $sourceData;
		} catch (\Exception $e) {
			$this->message .= 'Error getSourceData  : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error( $this->message );
		}		
	}
	
	
	// Mise à jour de la table des données source
	protected function updateSourceTable() {
		try {
			$query_data = "INSERT INTO z_".$this->ruleName."_".$this->ruleVersion."_source VALUES ";
			// Création de la requête de données
			$query_data .= "(";
			$columns_data = "DESCRIBE z_".$this->ruleName."_".$this->ruleVersion."_source";
			$stmt = $this->connection->prepare($columns_data);
			$stmt->execute();
			$columns = $stmt->fetchAll();

			$first = true;		
			if($columns) {
				foreach ($columns as $column) {
					if ($first === true) {
						$query_data .= "'$this->id',";
						$first = false;
						continue;
					}

					// Si le champ Myddleware_element_id est trouve (champ relation sur l'id de la source en cours de lecture)
					if ($column['Field'] == 'Myddleware_element_id') {
						$value = $this->data['id'];
					}
					elseif (!empty($this->data[$column['Field']])) {
						$value = $this->data[$column['Field']];
					}
					else {
						$value = '';
					}				
					$query_data .= "'".addslashes($value)."',";
				}							
				// Suppression de la dernière virgule  
				$query_data = rtrim($query_data,','); 
				$query_data .= ")";
				$stmt = $this->connection->prepare($query_data);
				$stmt->execute();
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
		try {
			$query_data = "INSERT INTO z_".$this->ruleName."_".$this->ruleVersion."_history VALUES ";
			// Création de la requête de données
			$query_data .= "(";
			$columns_data = "DESCRIBE z_".$this->ruleName."_".$this->ruleVersion."_history";
			$stmt = $this->connection->prepare($columns_data);
			$stmt->execute();
			$columns = $stmt->fetchAll();

			$first = true;		
			if($columns) {
				foreach ($columns as $column) {

					if ($first === true) {
						$query_data .= "'$this->id',";
						$first = false;
					}
					// Si le champ Myddleware_element_id est demandé, on le renseigne avec le target_id
					elseif($column['Field'] == 'Myddleware_element_id') {
						$value = $dataTarget['id'];
						$query_data .= "'".addslashes($value)."',";
					}
					else {
						if(!empty($dataTarget[$column['Field']])){
							$value = $dataTarget[$column['Field']];
							$query_data .= "'".addslashes($value)."',";
						}
						// Si le champ est vide o ne décrypte pas, on laisse le champ vide
						else {
							$query_data .= "'',";
						}
					}
				}	
						
				// Suppression de la dernière virgule  
				$query_data = rtrim($query_data,','); 
				$query_data .= ")";
				$stmt = $this->connection->prepare($query_data);
				$stmt->execute();			
			}
		} catch (\Exception $e) {
			$this->message .= 'Failed : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->logger->error( $this->message );
			return -1;
		}	
		return true;
	}
	
	// Mise à jour de la table des données cibles
	protected function updateTargetTable() {
		$nameIdSource = "id_".$this->ruleName."_".$this->ruleVersion."_source";
		// Charge les données source du document dans $this->sourceData
		$this->getSourceData();
		if (!empty($this->sourceData)) {
			try{			
				// Préparation de la requête d'insertion dans la table target
				$query_data = "INSERT INTO z_".$this->ruleName."_".$this->ruleVersion."_target VALUES ";
				$query_data .= "(";
				
				// Récupération de tous les champs de la table target
				$columns_data = "DESCRIBE z_".$this->ruleName."_".$this->ruleVersion."_target";
				$stmt = $this->connection->prepare($columns_data);
				$stmt->execute();
				$columns = $stmt->fetchAll();

				$first = true;
				if($columns) {
					// Boucle sur tous les champs target
					foreach ($columns as $column) { 
						if ($first === true) {
							$query_data .= "'".$this->sourceData[$nameIdSource]."',";
							$first = false;
						}
						else {
							// Recherche du champ cible ($column['Field']) dans les valeurs de la source ($this->RuleFields) pour le transformer et le sauvegarder dans la table target
							// Boucle sur toutes les champs jusqu'à trouver celle du champ en cours
							if (!empty($this->ruleFields)) {
								foreach ($this->ruleFields as $ruleField) {
									// Si la formule est trouvée
									if ($ruleField['rulef_target_field_name'] == $column['Field']) {
										// Transformation du champ
										$value = $this->getTransformValue($this->sourceData,$ruleField);
										if ($value === false) {
											throw new \Exception( 'Failed to transform data.' );
										}
										$query_data .= "'".addslashes($value)."',";
										break;
									} 
								}
							}
							if(isset($this->ruleRelationships)) {
								// Récupération de l'ID target
								foreach ($this->ruleRelationships as $ruleRelationships) {
									// Si la formule est trouvée
									if ($ruleRelationships['rrs_field_name_target'] == $column['Field']) {
										// Transformation du champ
										$value = $this->getTransformValue($this->sourceData,$ruleRelationships);
										if ($value === false) {
											throw new \Exception( 'Failed to transform relationship data.' );
										}
										$query_data .= "'".addslashes($value)."',";
										break;
									} 
								}
							}
						}
					}
					// Suppression de la dernière virgule
					$query_data = rtrim($query_data,','); 
					$query_data .= ")";			
					$stmt = $this->connection->prepare($query_data);
					$stmt->execute();
					return true;
				}
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
			[rulef_id] => 52 								: champ non nécessaire en mode test sur le front office
			[rule_id] => 53500e0bf2d06 						: champ non nécessaire en mode test sur le front office
			[rulef_target_field_name] => name
			[rulef_source_field_name] => name
			[rulef_formula] =>
			[rulef_related_rule] =>
		)
	En sortie la fonction renvoie la valeur du champ à envoyer dans le cible	
	 */
	public function getTransformValue($source,$ruleField) {
		try {
			//--
			if (!empty($ruleField['rulef_formula'])) {
				// -- -- -- Gestion des formules
	
				// préparation des variables	
				$r = explode(';', $ruleField['rulef_source_field_name']);	
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
								throw new \Exception( 'The field '.$listFields.' is unknow in the formula '.$ruleField['rulef_formula'].'. ' );
							}
						}
					}									
				}
				else {
					// On ne traite pas l'entrée my_value
					if ($ruleField['rulef_source_field_name'] != 'my_value') {
						$fieldNameDyn = $ruleField['rulef_source_field_name']; // value : nom de la variable exemple name
						$$fieldNameDyn = $source[$ruleField['rulef_source_field_name']]; // variable dynamique name = $name									
					}
				}
				// préparation des variables	 
				
				$formule = $this->container->get('formula.myddleware'); // service formule myddleware
				$formule->init($ruleField['rulef_formula']); // mise en place de la règle dans la classe
				$formule->generateFormule(); // Genère la nouvelle formule à la forme PhP
				
				// Exécute la règle si pas d'erreur de syntaxe
				if($f = $formule->execFormule()) {
					eval('$rFormula = '.$f.';'); // exec
					if(isset($rFormula)) {
						// affectation du résultat
						return $rFormula;
					}
					else {
						throw new \Exception( 'Invalid formula (failed to retrieve formula) : '.$ruleField['rulef_formula'] );	
					}
				}
				else {
					throw new \Exception( 'Invalid formula (failed to execute) : '.$ruleField['rulef_formula'] );
				}
				// -- -- -- Gestion des formules
			}
			// S'il s'agit d'un champ relation
			elseif (!empty($ruleField['rrs_field_id'])) {	
				// Si l'id est vide on renvoie vide
				if(empty(trim($source[$ruleField['rrs_field_name_source']]))){
					return null;
				}
				
				// Si on est sur une règle groupée alors l'id à retourner est forcément Myddleware_element_id (seule relation possible pour les règles groupées)
				if (!empty($this->ruleParams['group'])) {
					if (!empty($source['Myddleware_element_id'])) {
						return $source['Myddleware_element_id'];
					}
					else {
						throw new \Exception( 'Failed to get the field Myddleware_element_id for the group rule. ' );
					}
				}
				
				// Récupération de l'ID de l'enregistrement lié dans la cible avec l'id correspondant dans la source et la correspondance existante dans la règle liée.
				$targetId = $this->getTargetId($ruleField,$source[$ruleField['rrs_field_name_source']]);
				if (!empty($targetId)) {
					return $targetId;
				}
				else {
					throw new \Exception( 'Target id not found for id source '.$source[$ruleField['rulef_source_field_name']].' of the rule '.$ruleField['rulef_related_rule'] );
				}
			}
			// Si le champ est envoyé sans transformation
			elseif (isset($source[$ruleField['rulef_source_field_name']])) {			
				return $this->checkField($source[$ruleField['rulef_source_field_name']]);
			}
			elseif (is_null($source[$ruleField['rulef_source_field_name']])) {			
				return null;
			}
			else {
				throw new \Exception( 'Field '.$ruleField['rulef_source_field_name'].' not found in source data.------'.print_r($ruleField,true) );
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
				$rule = "SELECT * FROM Rule WHERE rule_id = :ruleId";
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
	
	// Permet de récupérer les champs de la cible
	protected function getTargetFields() {
		try {
			if (!empty($this->ruleId)) {
				$rule = "SELECT * FROM RuleFields WHERE rule_id = :ruleId";
				$stmt = $this->connection->prepare($rule);
				$stmt->bindValue(":ruleId", $this->ruleId);
				$stmt->execute();		
				$ruleFields = $stmt->fetchAll();
				foreach ($ruleFields AS $ruleField) {
					$fields[] = $ruleField['rulef_target_field_name'];
				}
				
				// Ajout des champs de relation s'il y en a
				$rule = "SELECT * FROM RuleRelationShips WHERE rule_id = :ruleId";
				$stmt = $this->connection->prepare($rule);
				$stmt->bindValue(":ruleId", $this->ruleId);
				$stmt->execute();		
				$ruleRelationShips = $stmt->fetchAll();
				if(!empty($ruleRelationShips)){
					foreach ($ruleRelationShips AS $ruleRelationShip) {
						$fields[] = $ruleRelationShip['rrs_field_name_target'];
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
	protected function setRuleParams() {	
		try {
			$sqlParams = "SELECT * 
							FROM RuleParams 
							WHERE rule_id = :ruleId";
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue(":ruleId", $this->ruleId);
		    $stmt->execute();	   				
			$ruleParams = $stmt->fetchAll();
			if($ruleParams) {
				foreach ($ruleParams as $ruleParam) {
					$this->ruleParams[$ruleParam['rulep_name']] = ltrim($ruleParam['rulep_value']);
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
			// Si on est sur une règle de type groupe avec la valeur child alors on est focément en update (seule la règle root est autorisée à créer des données)
			if (
					!empty($this->ruleParams['group'])
				&& $this->ruleParams['group'] == 'child'
			){
				return 'U';
			}
		
			// Recherche d'un enregitsrement avec un target id sur la même source quelques soit la version de la règle
			// Le tri sur target_id permet de récupérer le target id non vide en premier
			$sqlParamsSoure = "	SELECT 
								Documents.id, 
								Documents.target_id 
							FROM Rule
								INNER JOIN Rule Rule_version
									ON Rule_version.rule_name = Rule.rule_name
								INNER JOIN Documents 
									ON Documents.rule_id = Rule_version.rule_id
							WHERE 
									Rule.rule_id IN (:ruleId)									
								AND Documents.global_status != 'Cancel'	
								AND	Documents.source_id = :id
								AND Documents.id != :id_doc
							ORDER BY target_id DESC
							LIMIT 1";
			$stmt = $this->connection->prepare($sqlParamsSoure);
			$stmt->bindValue(":ruleId", $this->ruleId);
			$stmt->bindValue(":id", $id);
			$stmt->bindValue(":id_doc", $this->id);
		    $stmt->execute();	   				
			$result = $stmt->fetch();
		
			// Si on ne trouve pas d'id alors on prépare la requête pour rechercher dans la partie target
			if (empty($result['id'])) {
				$sqlParamsTarget = "	SELECT 
									Documents.id, 
									Documents.source_id target_id 
								FROM Rule
									INNER JOIN Rule Rule_version
										ON Rule_version.rule_name = Rule.rule_name
									INNER JOIN Documents 
										ON Documents.rule_id = Rule_version.rule_id
								WHERE 
										Rule.rule_id IN (:ruleId)									
									AND Documents.global_status != 'Cancel'	
									AND	Documents.target_id = :id
									AND Documents.id != :id_doc
								ORDER BY target_id DESC
								LIMIT 1";
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
			
			if (!empty($result['id'])) {
				$this->targetId = $result['target_id'];
				return 'U';
			}
			// Si aucun doc trouvé sur la règle actuelle
			else {
				// Si une relation avec le champ Myddleware_element_id est présente alors on passe en update et on change l'id source en prenant l'id de la relation
				// En effet ce champ indique que l'on va modifié un enregistrement créé par une autre règle
				if (!empty($this->ruleRelationships)) {
					// Boucle sur les relation
					foreach ($this->ruleRelationships as $ruleRelationship) {
						// Si on est sur une relation avec le champ Myddleware_element_id
						if ($ruleRelationship['rrs_field_name_target'] == 'Myddleware_element_id'){						
							// Si le champs avec l'id source n'est pas vide
							// S'il s'agit de Myddleware_element_id on teste id
							if (
									!empty($this->data[$ruleRelationship['rrs_field_name_source']])
								 || (
										$ruleRelationship['rrs_field_name_source'] == 'Myddleware_element_id'
									&& !empty($this->data['id'])	
								 )
							) {
								// On recherche l'id target dans la règle liée
								$this->sourceId = ($ruleRelationship['rrs_field_name_source'] == 'Myddleware_element_id' ? $this->data['id'] : $this->data[$ruleRelationship['rrs_field_name_source']]);
								// On récupère la direction de la relation pour rechercher dans le target id ou dans le source id
								$direction = $this->getRelationshipDirection($ruleRelationship);
								if ($direction == '-1') {	
									$stmt = $this->connection->prepare($sqlParamsTarget);
								}
								else {
									$stmt = $this->connection->prepare($sqlParamsSoure);
								}
								$stmt->bindValue(":ruleId", $ruleRelationship['rrs_field_id']);
								$stmt->bindValue(":id", $this->sourceId);
								$stmt->bindValue(":id_doc", $this->id);
								$stmt->execute();	   				
								$result = $stmt->fetch();				
							
								// Si on trouve la target dans la règle liée alors on passe le doc en UPDATE
								if (!empty($result['id'])) {
									$this->targetId = $result['target_id'];
									return 'U';
								}
								// Sinon on bloque la création du document 
								else {
									throw new \Exception( 'Failed to get the id target of the current module in the rule linked.' );
								}
							}
							else {
								throw new \Exception( 'Failed to get the id source of the current module.' );
							}
						}
					}
				}
				// Si aucune règle avec relation Myddleware_element_id alors on est en création
				return 'C';
			}
		} catch (\Exception $e) {
			$this->typeError = 'E';
			$this->message .= 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$this->logger->error( $this->message );
			return null;
		}
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
			$query = "	UPDATE Documents 
								SET 
									date_modified = :now,
									global_status = :globalStatus,
									attempt = :attempt,
									status = :new_status
								WHERE
									id = :id
								";
			echo 'statut '.$new_status.' id = '.$this->id.'  '.$now.chr(10);;				
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
			$query = "	UPDATE Documents 
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
			$query = "	UPDATE Documents 
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
			// depuis la version 1.1.2 on enlève les test sur delete des règles car un document en erreur peut porter sur une ancienne version de règle
			$sqlParams = "	SELECT 
								IF(RuleA.conn_id_source = RuleB.conn_id_source, '1', IF(RuleA.conn_id_source = RuleB.conn_id_target, '-1', '1')) direction
							FROM RuleRelationShips
								INNER JOIN Rule RuleA
									ON RuleRelationShips.rule_id = RuleA.rule_id
									#AND RuleA.rule_deleted = 0
								INNER JOIN Rule RuleB
									ON RuleRelationShips.rrs_field_id = RuleB.rule_id		
									#AND RuleB.rule_deleted = 0
							WHERE  
								RuleRelationShips.rrs_id = :rrs_id 
						";
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue(":rrs_id", $ruleRelationship['rrs_id']);
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
	
	// Permet de récupérer l'id target pour une règle (indépendemment de la version de la règle) et un id source
	protected function getTargetId($ruleRelationship,$record_id) {
		try {
			$direction = $this->getRelationshipDirection($ruleRelationship);
			// En fonction du sens de la relation, la recherche du parent id peut-être inversée (recherchée en source ou en cible)
			if ($direction == '-1') {
				$sqlParams = "	SELECT source_id record_id 
								FROM Rule
									INNER JOIN Rule Rule_version
										ON Rule_version.rule_name = Rule.rule_name
									INNER JOIN Documents 
										ON Documents.rule_id = Rule_version.rule_id
								WHERE  
										Rule.rule_id = :ruleRelateId 
									AND Documents.source_id != '' 
									AND Documents.target_id = :record_id 
									AND Documents.global_status = 'Close' 
								LIMIT 1";	
			}
			elseif ($direction == '1') {
				$sqlParams = "	SELECT target_id record_id
								FROM Rule
									INNER JOIN Rule Rule_version
										ON Rule_version.rule_name = Rule.rule_name
									INNER JOIN Documents 
										ON Documents.rule_id = Rule_version.rule_id
								WHERE  
										Rule.rule_id = :ruleRelateId 
									AND Documents.source_id = :record_id 
									AND Documents.target_id != '' 
									AND Documents.global_status = 'Close'
								LIMIT 1";	
			}
			else {
				throw new \Exception( 'Failed to find the direction of the relationship with the rule_id '.$ruleRelationship['rrs_field_id'].'. ' );
			}
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue(":ruleRelateId", $ruleRelationship['rrs_field_id']);
			$stmt->bindValue(":record_id", $record_id);
			$stmt->execute();	   				
			$result = $stmt->fetch();
			if (!empty($result['record_id'])) {
				return $result['record_id'];
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
			$query_header = "INSERT INTO Log (log_created, log_type, log_msg, rule_id, doc_id, ref_doc_id, job_id) VALUES ('$now','$this->typeError','$this->message','$this->ruleId','$this->id','$this->docIdRefError','$this->jobId')";
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