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

use Symfony\Bridge\Monolog\Logger; // Logs
use Symfony\Component\DependencyInjection\ContainerInterface as Container; // Service access
use Doctrine\DBAL\Connection; // Connection database

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Filesystem\Filesystem;
use Myddleware\RegleBundle\Entity\RuleParamAudit as RuleParamAudit;


use Myddleware\RegleBundle\Classes\tools as MyddlewareTools; // Tools
use Myddleware\RegleBundle\Entity\RuleParam;

class rulecore {
	
	protected $connection;
	protected $container;
	protected $logger;
	protected $em;
	protected $ruleId;
	protected $rule;
	protected $ruleFields;
	protected $ruleParams;
	protected $sourceFields;
	protected $targetFields;
	protected $ruleRelationships;
	protected $ruleFilters;
	protected $solutionSource;
	protected $solutionTarget;
	protected $jobId;
	protected $manual;
	protected $key;
	protected $limit = 100;
	protected $limitReadCommit = 1000;
	protected $tools;
	
    public function __construct(Logger $logger, Container $container, Connection $dbalConnection, $param) {
    	$this->logger = $logger;
		$this->container = $container;
		$this->connection = $dbalConnection;
		$this->em = $this->container->get('doctrine')->getEntityManager();
				
		if (!empty($param['ruleId'])) {
			$this->ruleId = $param['ruleId'];
			$this->setRule($this->ruleId);
		}		
		if (!empty($param['jobId'])) {
			$this->jobId = $param['jobId'];
		}	
		if (!empty($param['manual'])) {
			$this->manual = $param['manual'];
		}	

		$this->setRuleParam();
		$this->setLimit();
		$this->setRuleRelationships();
		$this->tools = new MyddlewareTools($this->logger, $this->container, $this->connection);			
	}
	
	// Set the limit rule
	protected function setLimit() {
		// Change the default value if a limit exists on the rule
		if (!empty($this->ruleParams['limit'])) {
			$this->limit = $this->ruleParams['limit'];
		}
		// Add one to the rule limit because when we reach the limit in the read finction,
		// we remove at least one record (see function validateReadDataSource)
		$this->limit++;
	}
	
	public function setRule($idRule) {
		$this->ruleId = $idRule;
		if (!empty($this->ruleId)) {
			$rule = "SELECT *, (SELECT value FROM RuleParam WHERE rule_id = :ruleId and name= 'mode') mode FROM Rule WHERE id = :ruleId";
		    $stmt = $this->connection->prepare($rule);
			$stmt->bindValue(":ruleId", $this->ruleId);
		    $stmt->execute();
			$this->rule = $stmt->fetch();
			// Set the rule parameters and rule relationships
			$this->setRuleParam();
			$this->setRuleRelationships();
			// Set the rule fields (we use the name_slug in $this->rule)
			$this->setRuleField();
		}
	}

	public function getRule() {
		return $this->rule;
	}
	
	// Generate a document for the current rule for a specific id in the source application. We don't use the reference for the function read.
	// If parameter readSource is false, it means that the data source are already in the parameter param, so no need to read in the source application 
	public function generateDocuments($idSource, $readSource = true, $param = '', $idFiledName = 'id') {
		try {
			if ($readSource) {
				// Connection to source application
				$connexionSolution = $this->connexionSolution('source');
				if ($connexionSolution === false) {
					throw new \Exception ('Failed to connect to the source solution.');
				}
				
				// Read data in the source application
				$read['module'] = $this->rule['module_source'];
				$read['fields'] = $this->sourceFields;
				$read['ruleParams'] = $this->ruleParams;
				$read['rule'] = $this->rule;
				// If the query is in the current record we replace Myddleware_element_id by id
				if ($idFiledName == 'Myddleware_element_id') {
					$idFiledName = 'id';
				}	
				$read['query'] = array($idFiledName => $idSource);	
				// In case we search a specific record, we set an default value in date_ref because it is a requiered parameter in the read function
				$read['date_ref'] = '1970-01-02 00:00:00';			
			
				$dataSource = $this->solutionSource->read($read);			;				
				if (!empty($dataSource['error'])) {
					throw new \Exception ('Failed to read record '.$idSource.' in the module '.$read['module'].' of the source solution. '.(!empty($dataSource['error']) ? $dataSource['error'] : ''));
				}
			}
			else {
				$dataSource['values'][] = $param['values'];
			}
			
			if (!empty($dataSource['values'])) {
				foreach($dataSource['values'] as $docData) {
					// Generate document
					$doc['rule'] = $this->rule;
					$doc['ruleFields'] = $this->ruleFields;
					$doc['ruleRelationships'] = $this->ruleRelationships;
					$doc['data'] = $docData;
					$doc['jobId'] = $this->jobId;		
					// If the document is a child, we save the parent in the table Document
					if (!empty($param['parent_id'])) {
						$doc['parentId'] = $param['parent_id'];		
					}
					$document = new document($this->logger, $this->container, $this->connection, $doc);
					$createDocument = $document->createDocument();		
					if (!$createDocument) {
						throw new \Exception ('Failed to create document : '.$document->getMessage());
					}
					$documents[] = $document;
				}
				return $documents;
			}
			return null;
		} catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error($error);
			$errorObj = new \stdClass();
			$errorObj->error = $error;		
			return $errorObj;
		}	
	}
	
	// Connect to the source or target application
	public function connexionSolution($type) {
		try {
			if ($type == 'source') {
				$connId = $this->rule['conn_id_source'];
			}
			elseif ($type == 'target') {
				$connId = $this->rule['conn_id_target'];
			}
			else {
				return false;
			}
			
			// Get the name of the application			
		    $sql = "SELECT Solution.name  
		    		FROM Connector
						INNER JOIN Solution 
							ON Solution.id  = Connector.sol_id
		    		WHERE Connector.id = :connId";
		    $stmt = $this->connection->prepare($sql);
			$stmt->bindValue(":connId", $connId);
		    $stmt->execute();		
			$r = $stmt->fetch();			
			// Get params connection
		    $sql = "SELECT id, conn_id, name, value
		    		FROM ConnectorParam 
		    		WHERE conn_id = :connId";
		    $stmt = $this->connection->prepare($sql);
			$stmt->bindValue(":connId", $connId);
		    $stmt->execute();	    
			$tab_params = $stmt->fetchAll();
			$params = array();
			if(!empty($tab_params)) {
				foreach ($tab_params as $key => $value) {
					$params[$value['name']] = $value['value'];
					$params['ids'][$value['name']] = array('id' => $value['id'],'conn_id' => $value['conn_id']);
				}			
			}
			
			// Connect to the application
			if ($type == 'source') {	
				$this->solutionSource = $this->container->get('myddleware_rule.'.$r['name']);				
				$loginResult = $this->solutionSource->login($params);			
				$c = (($this->solutionSource->connexion_valide) ? true : false );				
			}
			else {
				$this->solutionTarget = $this->container->get('myddleware_rule.'.$r['name']);		
				$loginResult = $this->solutionTarget->login($params);			
				$c = (($this->solutionTarget->connexion_valide) ? true : false );			
			}
			if(!empty($loginResult['error'])) {
				return $loginResult;
			}

			return $c; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
			return false;
		}	
	}

	// Logout to the application
	protected function logoutSolution($type) {
		try {	
			if ($type == 'source') {
				$this->solutionSource = $this->container->get('myddleware_rule.'.$r['name']);		
				return $this->solutionSource->logout($params);							
			}
			else {
				$this->solutionTarget = $this->container->get('myddleware_rule.'.$r['name']);		
				return $this->solutionTarget->logout($params);				
			}
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
			return false;
		}	
	}
	

	// Permet de mettre toutes les données lues dans le système source dans le tableau $this->dataSource
	// Cette fonction retourne le nombre d'enregistrements lus
	public function createDocuments() {	
		$readSource = null;
		// Si la lecture pour la règle n'est pas désactivée
		// Et si la règle est active et pas supprimée ou bien le lancement est en manuel
		if (
				empty($this->ruleParams['disableRead'])
			&&	(
					(
							$this->rule['deleted'] == 0
						&& $this->rule['active'] == 1	
					)
					|| (
						$this->manual == 1
					)
				)
		) {
			// lecture des données dans la source
			$readSource = $this->readSource();
			if (empty($readSource['error'])) {
				$readSource['error'] = '';
			}
	
			// Si erreur
			if (!isset($readSource['count'])) {
				return $readSource;
			}		
			$this->connection->beginTransaction(); // -- BEGIN TRANSACTION suspend auto-commit
			try {
				if ($readSource['count'] > 0) {					
					include_once 'document.php';		
					$param['rule'] = $this->rule;
					$param['ruleFields'] = $this->ruleFields;
					$param['ruleRelationships'] = $this->ruleRelationships;
					$i = 0;
					if($this->dataSource['values']) {
						// If migration mode, we select all documents to improve performance. For example, we won't execute queries is method document->checkRecordExist
						$migrationParameters = $this->container->getParameter('migration');
						if (!empty($migrationParameters['mode'])) {
							$param['ruleDocuments'][$this->ruleId] = $this->getRuleDocuments($this->ruleId);
						}				
						// Boucle sur chaque document
						foreach ($this->dataSource['values'] as $row) {
							if ($i >= $this->limitReadCommit){
								$this->connection->commit(); // -- COMMIT TRANSACTION
								$this->connection->beginTransaction(); // -- BEGIN TRANSACTION suspend auto-commit
								$i = 0;
							}
							$i++;
							$param['data'] = $row;
							$param['jobId'] = $this->jobId;
							$document = new document($this->logger, $this->container, $this->connection, $param);
							$createDocument = $document->createDocument();
							if (!$createDocument) {
								$readSource['error'] .= $document->getMessage();
							}
						}			
					}
					// Mise à jour de la date de référence si des documents ont été créés
					$this->updateReferenceDate();
				}				
				// If params has been added in the output of the rule we saved it
				$this->updateParams();
				
				// Rollback if the job has been manually stopped
				if ($this->getJobStatus() != 'Start') {
					throw new \Exception('The task has been stopped manually during the document creation. No document generated. ');
				}
				$this->connection->commit(); // -- COMMIT TRANSACTION
			} catch (\Exception $e) {
				$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
				$this->logger->error( 'Failed to create documents : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
				$readSource['error'] = 'Failed to create documents : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			}	
		}
		// On affiche pas d'erreur si la lecture est désactivée
		elseif (empty($this->ruleParams['disableRead'])) {
			$readSource['error'] = 'The rule '.$this->rule['name_slug'].($this->rule['deleted'] == 1 ? ' is deleted.' : ' is disabled.');
		}
		return $readSource;
	}
	
	protected function getJobStatus() {
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
	
	// Permet de mettre à jour la date de référence pour ne pas récupérer une nouvelle fois les données qui viennent d'être écrites dans la cible
	protected function updateReferenceDate() {			
		$param = $this->em->getRepository('RegleBundle:RuleParam')
			->findOneBy(array(
					'rule' => $this->ruleId,
					'name' => 'datereference'
				)
			);
		// Every rules should have the param datereference
		if (empty($param)) {
			throw new \Exception ('No reference date for the rule '.$this->ruleId.'.');	
		} else {
			// Save param modification in the audit table		
			if ($param->getValue() != $this->dataSource['date_ref']) {
				$paramAudit = new RuleParamAudit();
				$paramAudit->setRuleParamId($param->getId());
				$paramAudit->setDateModified(new \DateTime);
				$paramAudit->setBefore($param->getValue());
				$paramAudit->setAfter($this->dataSource['date_ref']);
				$paramAudit->setJob($this->jobId);
				$this->em->persist($paramAudit);					
			}
			// Update reference 
			$param->setValue($this->dataSource['date_ref']);
			$this->em->persist($param);					
			$this->em->flush();
		}				
	}
	
	// Update/create rule parameter
	protected function updateParams() {
		if (!empty($this->dataSource['ruleParams'])) {
			foreach ($this->dataSource['ruleParams'] as $ruleParam) {				
				// Search to check if the param already exists
				 $paramEntity = $this->em->getRepository('RegleBundle:RuleParam')
					   ->findOneBy( array(
									'rule' => $this->ruleId, 
									'name' => $ruleParam['name']
								)
						);	
				// Update or create the new param		
				if (!empty($paramEntity)) {
					$paramEntity->setValue( $ruleParam['value'] );
				} else {
					$paramEntity = new RuleParam();		
					$paramEntity->setRule($this->ruleId);
					$paramEntity->setName($ruleParam['name']);
					$paramEntity->setValue($ruleParam['value']); 						
				}
				$this->em->persist($paramEntity);
				$this->em->flush();				
			}
		}
	}
	
	protected function readSource() {		
		$read['module'] = $this->rule['module_source'];
		$read['rule'] = $this->rule;
		$read['date_ref'] = $this->ruleParams['datereference'];
		$read['ruleParams'] = $this->ruleParams;
		$read['fields'] = $this->sourceFields;
		$read['offset'] = 0;
		$read['limit'] = $this->limit;
		$read['jobId'] = $this->jobId;
		$read['manual'] = $this->manual;
		// Ajout des champs source des relations de la règle
		if (!empty($this->ruleRelationships)) {
			foreach ($this->ruleRelationships as $ruleRelationship) {
				$read['fields'][] = $ruleRelationship['field_name_source'];
			}
		}

		// si champs vide
		if(!empty($read['fields'])) {
			$connect = $this->connexionSolution('source');
			if ($connect === true) {												
				$this->dataSource = $this->solutionSource->read($read);
				
				// If Myddleware has reached the limit, we validate data to make sure no doto won't be lost
				if (
						!empty($this->dataSource['count'])
					&&	$this->dataSource['count'] == $this->limit
				) {
					// Check and clean data source
					$validateReadDataSource = $this->validateReadDataSource();
					if (!empty($validateReadDataSource['error'])){
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
			}
			elseif (!empty($connect['error'])){
				return $connect;
			}
			else {
				return array('error' => 'Failed to connect to the source with rule : '.$this->ruleId.' .' );
			}
		}
		return array('error' => 'No field to read in source system. ');
	} 
	
	// Check every record haven't the same reference date
	// Make sure the next record hasn't the same date modified, so we delete at least the last one
	// This function run only when the limit call has been reached
	protected function validateReadDataSource() {
		if (!empty($this->dataSource['values'])) {
			$dataSourceValues = $this->dataSource['values'];
			
			// Order data in the date_modified order
			$modified  = array_column($dataSourceValues, 'date_modified');
			array_multisort($modified, SORT_DESC, $dataSourceValues);
			foreach ($dataSourceValues as $value) {
				// Check if the previous record has the same date_modified than the current record
				if (
						empty($previousValue)   // first call
					OR (
							!empty($previousValue['date_modified'])
						AND $previousValue['date_modified'] == $value['date_modified']
					)
				) {
					// Remove the current item, it will be read in the next call
					unset($this->dataSource['values'][$value['id']]); // id equal the key in the dataSource table
					$this->dataSource['count']--;
					$previousValue = $value;
					continue;
				}
				// Keep the reference date of the last record we have read
				$this->dataSource['date_ref'] = $value['date_modified'];			
				break;
			}
			if (empty($this->dataSource['values'])) {
				return array('error' => 'All records read have the same reference date in rule '.$this->rule['name'].'. Myddleware cannot garanty all data will be read. Job interrupted. Please increase the number of data read by changing the limit attribut in job and rule class.');
			}
			return true;
		}
	}
	
	// Permet de filtrer les nouveau documents d'une règle
	public function filterDocuments($documents = null) {
		include_once 'document.php';
		$response = array();
		
		// Sélection de tous les docuements de la règle au statut 'New' si aucun document n'est en paramètre
		if (empty($documents)) {
			$documents = $this->selectDocuments('New');
		}

		// Pour tous les docuements sélectionnés on vérifie les prédécesseurs
		if(!empty($documents)) {
			$this->setRuleFilter();
			foreach ($documents as $document) { 
				$param['id_doc_myddleware'] = $document['id'];
				$param['jobId'] = $this->jobId;
				$doc = new document($this->logger, $this->container, $this->connection, $param);
				$response[$document['id']] = $doc->filterDocument($this->ruleFilters);
			}			
		}
		return $response;
	}
    
	// Permet de contrôler si un document de la même règle pour le même enregistrement n'est pas close
	// Si un document n'est pas clos alors le statut du docuement est mis à "pending"
	public function ckeckPredecessorDocuments($documents = null) {
		include_once 'document.php';
		$response = array();
			
		// Sélection de tous les docuements de la règle au statut 'Filter_OK' si aucun document n'est en paramètre
		if (empty($documents)) {
			$documents = $this->selectDocuments('Filter_OK');
		}
		// Pour tous les docuements sélectionnés on vérifie les prédécesseurs
		if(!empty($documents)) { 
			foreach ($documents as $document) { 
				$param['id_doc_myddleware'] = $document['id'];
				$param['jobId'] = $this->jobId;
				$param['ruleRelationships'] = $this->ruleRelationships;
				$doc = new document($this->logger, $this->container, $this->connection, $param);
				$response[$document['id']] = $doc->ckeckPredecessorDocument();
			}			
		}
		return $response;
	}
    
	// Permet de contrôler si un document de la même règle pour le même enregistrement n'est pas close
	// Si un document n'est pas clos alors le statut du docuement est mis à "pending"
	public function ckeckParentDocuments($documents = null) {
		include_once 'document.php';
		// Permet de charger dans la classe toutes les relations de la règle
		$response = array();
		
		// Sélection de tous les docuements de la règle au statut 'New' si aucun document n'est en paramètre
		if (empty($documents)) {
			$documents = $this->selectDocuments('Predecessor_OK');
		}
		if(!empty($documents)) {
			$param['jobId'] = $this->jobId;			
			$param['ruleRelationships'] = $this->ruleRelationships;
			// If migration mode, we select all documents to improve performance. For example, we won't execute queries is method document->getTargetId
			$migrationParameters = $this->container->getParameter('migration');
			if (!empty($migrationParameters['mode'])) {
				if (!empty($this->ruleRelationships)) {
					// Get all documents of every rules linked
					foreach($this->ruleRelationships as $ruleRelationship) {
						// Get documents only if we don't have them yet (we could have several relationship to the same rule)
						if (empty($param['ruleDocuments'][$ruleRelationship['field_id']])) {
							$param['ruleDocuments'][$ruleRelationship['field_id']] = $this->getRuleDocuments($ruleRelationship['field_id'],true,true);		
						}
					}
				}
			}				
			// Pour tous les docuements sélectionnés on vérifie les parents
			foreach ($documents as $document) { 
				$param['id_doc_myddleware'] = $document['id'];
				$param['jobId'] = $this->jobId;
				$param['ruleRelationships'] = $this->ruleRelationships;
				$doc = new document($this->logger, $this->container, $this->connection, $param);
				$response[$document['id']] = $doc->ckeckParentDocument();
			}			
		}			
		return $response;
	}
    
	// Permet de contrôler si un docuement de la même règle pour le même enregistrement n'est pas close
	// Si un document n'est pas clos alors le statut du docuement est mis à "pending"
	public function transformDocuments($documents = null){
		include_once 'document.php';
				
		// Permet de charger dans la classe toutes les relations de la règle
		$response = array();
	
		// Sélection de tous les docuements de la règle au statut 'New' si aucun document n'est en paramètre
		if (empty($documents)) {
			$documents = $this->selectDocuments('Relate_OK');
		}
		if(!empty($documents)) {
			$param['ruleFields'] = $this->ruleFields;
			$param['ruleRelationships'] = $this->ruleRelationships;
			$param['jobId'] = $this->jobId;
			$param['key'] = $this->key;
			// If migration mode, we select all documents to improve performance. For example, we won't execute queries is method document->getTargetId
			$migrationParameters = $this->container->getParameter('migration');
			if (!empty($migrationParameters['mode'])) {
				if (!empty($this->ruleRelationships)) {
					// Get all documents of every rules linked
					foreach($this->ruleRelationships as $ruleRelationship) {
						// Get documents only if we don't have them yet (we could have several relationship to the same rule)
						if (empty($param['ruleDocuments'][$ruleRelationship['field_id']])) {
							$param['ruleDocuments'][$ruleRelationship['field_id']] = $this->getRuleDocuments($ruleRelationship['field_id'],true,true);					
						}
					}
				}
			}		
			// Transformation de tous les docuements sélectionnés
			foreach ($documents as $document) { 			
				$param['id_doc_myddleware'] = $document['id'];
				$doc = new document($this->logger, $this->container, $this->connection, $param);
				$response[$document['id']] = $doc->transformDocument();
			}	
		}	
		return $response;		
	}
	

	// Permet de récupérer les données de la cible avant modification des données
	// 2 cas de figure : 
	//     - Le document est un document de modification
	//     - Le document est un document de création mais la règle a un paramètre de vérification des données pour ne pas créer de doublon
	public function getTargetDataDocuments($documents = null) {
		include_once 'document.php';
	
		// Permet de charger dans la classe toutes les relations de la règle
		$response = array();
		
		// Sélection de tous les docuements de la règle au statut 'New' si aucun document n'est en paramètre
		if (empty($documents)) {
			$documents = $this->selectDocuments('Transformed');
		}
		
		if(!empty($documents)) {
			// Connexion à la solution cible pour rechercher les données
			$this->connexionSolution('target');
			
			// Récupération de toutes les données dans la cible pour chaque document
			foreach ($documents as $document) {
				$param['id_doc_myddleware'] = $document['id'];
				$param['solutionTarget'] = $this->solutionTarget;
				$param['ruleFields'] = $this->ruleFields;
				$param['ruleRelationships'] = $this->ruleRelationships;
				$param['jobId'] = $this->jobId;
				$param['key'] = $this->key;
				$doc = new document($this->logger, $this->container, $this->connection, $param);
				$response[$document['id']] = $doc->getTargetDataDocument();
				$response['doc_status'] = $doc->getStatus();
			}			
		}	
		return $response;			
	}
	
	public function sendDocuments() {	
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
	
	public function actionDocument($id_document,$event, $param1 = null) {
		switch ($event) { 
			case 'rerun':
				return $this->rerun($id_document);
				break;
			case 'cancel':
				return $this->cancel($id_document);
				break;
			case 'remove':
				return $this->changeDeleteFlag($id_document,true);
				break;
			case 'restore':
				return $this->changeDeleteFlag($id_document,false);
				break;
			case 'changeStatus':
				return $this->changeStatus($id_document,$param1);
				break;
			default:
				return 'Action '.$event.' unknown. Failed to run this action. ';
		}
	}
	
	public function actionRule($event) {
		switch ($event) {
			case 'ALL':
				return $this->runMyddlewareJob("ALL");
				break;
			case 'ERROR':
				return $this->runMyddlewareJob("ERROR");
				break;
			case 'runMyddlewareJob':
				return $this->runMyddlewareJob($this->rule['name_slug']);
				break;
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
	public static function beforeSave($containeur,$data) {
		// Contrôle sur la solution source
		$solutionSource = $containeur->get('myddleware_rule.'.$data['module']['source']['solution']);
		$check = $solutionSource->beforeRuleSave($data,'source');
		// Si OK contôle sur la solution cible
		if ($check['done']) {
			$solutionTarget = $containeur->get('myddleware_rule.'.$data['module']['target']['solution']);
			$check = $solutionTarget->beforeRuleSave($data,'target');
		}
		return $check;
	}
	
	// Permet d'effectuer une action après la sauvegarde de la règle dans Myddleqare
	// Mêmes paramètres en entrée que pour la fonction beforeSave sauf que l'on a ajouté les entrées ruleId et date de référence au tableau
	public static function afterSave($containeur,$data) {
		// Contrôle sur la solution source
		$solutionSource = $containeur->get('myddleware_rule.'.$data['module']['source']['solution']);
		$messagesSource = $solutionSource->afterRuleSave($data,'source');

		$solutionTarget = $containeur->get('myddleware_rule.'.$data['module']['target']['solution']);
		$messagesTarget = $solutionTarget->afterRuleSave($data,'target');
		
		$messages = array_merge($messagesSource,$messagesTarget);
		$data['testMessage'] = '';
		// Affichage des messages
		if (!empty($messages)) {
			$session = new Session();
			foreach ($messages as $message) {
				if ($message['type'] == 'error') {
					$errorMessages[] = $message['message'];
				}
				else {
					$successMessages[] = $message['message'];
				}
				$data['testMessage'] .= $message['type'].' : '.$message['message'].chr(10);
			}
			if (!empty($errorMessages)) {
				$session->set( 'error', $errorMessages);
			}
			if (!empty($successMessages)) {
				$session->set( 'success', $successMessages);
			}
		}
	}
	
	// Get all document of the rule
	protected function getRuleDocuments($ruleId, $sourceId = true, $targetId = false) {
		$sql = "SELECT id, source_id, target_id, status, global_status FROM Document WHERE rule_id = :ruleId";
		$stmt = $this->connection->prepare($sql);
		$stmt->bindValue(":ruleId", $ruleId);
		$stmt->execute();	    
		$documents = $stmt->fetchAll();
		if (!empty($documents)) {
			foreach ($documents as $document) {
				$documentResult['sourceId'][$document['source_id']][] = $document;
				if (
						$targetId
					AND !empty($document['source_id'])
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
	public static function getBidirectionalRules($connection, $params) {
		try {					
			// Récupération des règles opposées à la règle en cours de création
			$queryBidirectionalRules = "SELECT 
											id, 
											name
										FROM Rule 
										WHERE 
												conn_id_source = :conn_id_target
											AND conn_id_target = :conn_id_source
											AND module_source = :module_target
											AND module_target = :module_source
											AND deleted = 0
										";
			$stmt = $connection->prepare($queryBidirectionalRules);
			$stmt->bindValue(":conn_id_source", $params['connector']['source']);
			$stmt->bindValue(":conn_id_target", $params['connector']['cible']);
			$stmt->bindValue(":module_source", $params['module']['source']);
			$stmt->bindValue(":module_target", $params['module']['cible']);
		    $stmt->execute();	   				
			$bidirectionalRules = $stmt->fetchAll();
			
			// Construction du tableau de sortie
			if (!empty($bidirectionalRules)) {
				$option[''] = ''; 
				foreach ($bidirectionalRules as $rule) {
					$option[$rule['id']] = $rule['name'];
				}
				if (!empty($option)) {
					return array(	
						array(
							'id' 		=> 'bidirectional',
							'name' 		=> 'bidirectional',
							'required'	=> false,
							'type'		=> 'option',
							'label' => 'create_rule.step3.params.sync',
							'option'	=> $option
						)
					);		
				}
			}
		} catch (\Exception $e) {
			return null;
		}
		return null;
	}
		
	// Permet d'annuler un docuement 
	protected function cancel($id_document) {	
		$param['id_doc_myddleware'] = $id_document;
		$param['jobId'] = $this->jobId;
		$doc = new document($this->logger, $this->container, $this->connection, $param);
		$doc->documentCancel(); 
		$session = new Session();
		$message = $doc->getMessage();
		
		// Si on a pas de jobId cela signifie que l'opération n'est pas massive mais sur un seul document
		// On affiche alors le message directement dans Myddleware
		if (empty($this->jobId)) {
			if (empty($message)) {
				$session->set( 'success', array('Data transfer has been successfully cancelled.'));
			}
			else {
				$session->set( 'error', array($doc->getMessage()));
			}
		}
	}
	
	// Remove a document 
	protected function changeDeleteFlag($id_document,$deleteFlag) {	
		$param['id_doc_myddleware'] = $id_document;
		$param['jobId'] = $this->jobId;
		$doc = new document($this->logger, $this->container, $this->connection, $param);
		$doc->changeDeleteFlag($deleteFlag); 
		$session = new Session();
		$message = $doc->getMessage();
		
		// Si on a pas de jobId cela signifie que l'opération n'est pas massive mais sur un seul document
		// On affiche alors le message directement dans Myddleware
		if (empty($this->jobId)) {
			if (empty($message)) {
				$session->set( 'success', array('Data transfer has been successfully removed.'));
			}
			else {
				$session->set( 'error', array($doc->getMessage()));
			}
		}
	}
	
	// Remove a document 
	protected function changeStatus($id_document,$toStatus) {	
		$param['id_doc_myddleware'] = $id_document;
		$param['jobId'] = $this->jobId;
		$doc = new document($this->logger, $this->container, $this->connection, $param);
		$doc->updateStatus($toStatus);
	}
	
	protected function runMyddlewareJob($ruleSlugName) {
		try{
			$session = new Session();	

			// create temp file
			$guid = uniqid();
			
			// récupération de l'exécutable PHP, par défaut c'est php
			$php = $this->container->getParameter('php');
			if (empty($php['executable'])) {
				$php['executable'] = 'php';
			}
			
			$fileTmp = $this->container->getParameter('kernel.cache_dir') . '/myddleware/job/'.$guid.'.txt';		
			$fs = new Filesystem();
			try {
				$fs->mkdir(dirname($fileTmp));
			} catch (IOException $e) {
				throw new \Exception ($this->tools->getTranslation(array('messages', 'rule', 'failed_create_directory')));
			}
			
			exec($php['executable'].' '.__DIR__.'/../../../../bin/console myddleware:synchro '.$ruleSlugName.' --env='.$this->container->get( 'kernel' )->getEnvironment().' > '.$fileTmp.' &', $output);
			$cpt = 0;
			// Boucle tant que le fichier n'existe pas
			while (!file_exists($fileTmp)) {
				if($cpt >= 29) {
					throw new \Exception ($this->tools->getTranslation(array('messages', 'rule', 'failed_running_job')));
				}
				sleep(1);
				$cpt++;
			}
			
			// Boucle tant que l id du job n'est pas dans le fichier (écris en premier)
			$file = fopen($fileTmp, 'r');
			$firstLine = fgets($file);
			fclose($file);
			while (empty($firstLine)) {
				if($cpt >= 29) {
					throw new \Exception ($this->tools->getTranslation(array('messages', 'rule', 'failed_get_task_id')));
				}
				sleep(1);
				$file = fopen($fileTmp, 'r');
				$firstLine = fgets($file); 
				fclose($file);
				$cpt++;
			}
			
			// transform all information of the first line in an arry
			$result = explode(';',$firstLine);
			// Renvoie du message en session
			if ($result[0]) {
				$session->set('info', array('<a href="'.$this->container->get('router')->generate('task_view', array('id'=>trim($result[1]))).'" target="blank_">'.$this->tools->getTranslation(array('messages', 'rule', 'open_running_task')).'</a>.'));
			}
			else {
				$session->set('error', array($result[1].(!empty($result[2]) ? '<a href="'.$this->container->get('router')->generate('task_view', array('id'=>trim($result[2]))).'" target="blank_">'.$this->tools->getTranslation(array('messages', 'rule', 'open_running_task')).'</a>' : '')));
			}
			return $result[0];
		} catch (\Exception $e) {
			$session = new Session();
			$session->set( 'error', array($e->getMessage())); 
			return false;
		}
	}
	
	// Permet de relancer un document quelque soit son statut
	protected function rerun($id_document) {
		$session = new Session();
		$msg_error = array();
		$msg_success = array();
		$msg_info = array();
		// Récupération du statut du document
		$param['id_doc_myddleware'] = $id_document;
		$param['jobId'] = $this->jobId;
		$doc = new document($this->logger, $this->container, $this->connection, $param);
		$status = $doc->getStatus();
		// Si la règle n'est pas chargée alors on l'initialise.
		if (empty($this->ruleId)) {
			$this->ruleId = $doc->getRuleId();
			$this->setRule($this->ruleId);
			$this->setRuleRelationships();
			$this->setRuleParam();
			$this->setRuleField();
		}
		
		$response[$id_document] = false;
		// On lance des méthodes différentes en fonction du statut en cours du document et en fonction de la réussite ou non de la fonction précédente
		if (in_array($status,array('New','Filter_KO'))) {
			$response = $this->filterDocuments(array(array('id' => $id_document)));
			if ($response[$id_document] === true) {
				$msg_success[] = 'Transfer id '.$id_document.' : Status change => Filter_OK';
			}
			elseif ($response[$id_document] == -1) {
				$msg_info[] = 'Transfer id '.$id_document.' : Status change => Filter';
			}
			else {
				$msg_error[] = 'Transfer id '.$id_document.' : Error, status transfer => Filter_KO';
			}
		}
		if ($response[$id_document] === true || in_array($status,array('Filter_OK','Predecessor_KO'))) {
			$response = $this->ckeckPredecessorDocuments(array(array('id' => $id_document)));
			if ($response[$id_document] === true) {
				$msg_success[] = 'Transfer id '.$id_document.' : Status change => Predecessor_OK';
			}
			else {
				$msg_error[] = 'Transfer id '.$id_document.' : Error, status transfer => Predecessor_KO';
			}
		}
		if ($response[$id_document] === true || in_array($status,array('Predecessor_OK','Relate_KO'))) {
			$response = $this->ckeckParentDocuments(array(array('id' => $id_document)));
			if ($response[$id_document] === true) {
				$msg_success[] = 'Transfer id '.$id_document.' : Status change => Relate_OK';
			}
			else {
				$msg_error[] = 'Transfer id '.$id_document.' : Error, status transfer => Relate_KO';
			}
		}
		if ($response[$id_document] === true || in_array($status,array('Relate_OK','Error_transformed'))) {
			$response = $this->transformDocuments(array(array('id' => $id_document)));
			if ($response[$id_document] === true) {
				$msg_success[] = 'Transfer id '.$id_document.' : Status change : Transformed';
			}
			else {
				$msg_error[] = 'Transfer id '.$id_document.' : Error, status transfer : Error_transformed';
			}
		}
		if ($response[$id_document] === true || in_array($status,array('Transformed','Error_checking','Not_found'))) {
			$response = $this->getTargetDataDocuments(array(array('id' => $id_document)));			
			if ($response[$id_document] === true) {
				if ($this->rule['mode'] == 'S') {
					$msg_success[] = 'Transfer id '.$id_document.' : Status change : '.$response['doc_status'];
				}
				else {
					$msg_success[] = 'Transfer id '.$id_document.' : Status change : '.$response['doc_status'];
				}
			}
			else {
				$msg_error[] = 'Transfer id '.$id_document.' : Error, status transfer : '.$response['doc_status'];
			}
		}
		// Si la règle est en mode recherche alors on n'envoie pas de données
		// Si on a un statut compatible ou si le doc vient de passer dans l'étape précédente et qu'il n'est pas no_send alors on envoie les données
		if (
				$this->rule['mode'] != 'S'
			&& (
					in_array($status,array('Ready_to_send','Error_sending'))
				|| (
						$response[$id_document] === true 	
					&& (
							empty($response['doc_status'])
						|| (
								!empty($response['doc_status'])
							&& $response['doc_status'] != 'No_send'
						)
					)
				)
			)
		){
			$response = $this->sendTarget('',$id_document);		
			if (
					!empty($response[$id_document]['id']) 
				&&	empty($response[$id_document]['error'])
				&&	empty($response['error']) // Error can be on the document or can be a general error too
			) {
				$msg_success[] = 'Transfer id '.$id_document.' : Status change : Send';			
			}
			else {
				$msg_error[] = 'Transfer id '.$id_document.' : Error, status transfer : Error_sending. '.(!empty($response['error']) ? $response['error'] : $response[$id_document]['error']);				
			}
		}		
			
		// If the job is manual, we display error in the UI
		if ($this->manual) {
			if (!empty($msg_error)) {
				$session->set( 'error', $msg_error);
			}
			if (!empty($msg_success)) {
				$session->set( 'success', $msg_success);
			}
			if (!empty($msg_info)) {
				$session->set( 'info', $msg_info);
			}
		}
		return $msg_error;
	}
	
	protected function clearSendData($sendData) {
		if (!empty($sendData)) {
			foreach($sendData as $key => $value){
				unset($value['source_date_modified']);
				unset($value['id_doc_myddleware']);
				$sendData[$key] = $value;
			}
			return $sendData;
		}
	}
	
	protected function beforeDelete($sendData) {
		return $sendData;
	}
	
	// Check if the rule is a child rule
	public function isChild() {
		try {					
			$queryChild = "	SELECT Rule.id 
									FROM RuleRelationShip 
										INNER JOIN Rule
											ON Rule.id  = RuleRelationShip.rule_id 
									WHERE 
											RuleRelationShip.field_id = :ruleId
										AND RuleRelationShip.parent = 1
										AND Rule.deleted = 0
								";							
			$stmt = $this->connection->prepare($queryChild);
			$stmt->bindValue(":ruleId", $this->ruleId);
		    $stmt->execute();	   				
			$rules = $stmt->fetchAll();
			if (!empty($rules)) {
				return true;
			}
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
		}
		return false;
	}
	
	protected function sendTarget($type, $documentId = null) {
		try {	
			// Permet de charger dans la classe toutes les relations de la règle
			$response = array();
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
			$send['jobId'] = $this->jobId;
			// Si des données sont prêtes à être créées
			if (!empty($send['data'])) {
				// If the rule is a child rule, no document is sent. They will be sent with the parent rule.
				if ($this->isChild()) {
					foreach($send['data'] as $key => $data) {			
						// True is send to avoid an error in rerun method. We should put the target_id but the document will be send with the parent rule.
						$response[$key] = array('id' => true);		
					}
					return $response;
				}
				
				// Connexion à la cible
				$connect = $this->connexionSolution('target');				
				if ($connect === true) {
					// Création des données dans la cible
					if ($type == 'C') {
						// Permet de vérifier que l'on ne va pas créer un doublon dans la cible
						$send['data'] = $this->checkDuplicate($send['data']);
						$send['data'] = $this->clearSendData($send['data']);
						$response = $this->solutionTarget->create($send);
					}
					// Modification des données dans la cible
					elseif ($type == 'U') {			
						$send['data'] = $this->clearSendData($send['data']);
						// permet de récupérer les champ d'historique, nécessaire pour l'update de SAP par exemple
						$send['dataHistory'] = $this->getSendDocuments($type, $documentId, 'history');
						$send['dataHistory'] = $this->clearSendData($send['dataHistory']);
						$response = $this->solutionTarget->update($send);
					}
					// Delete data from target application
					elseif ($type == 'D') {			
						$send['data'] = $this->beforeDelete($send['data']);;
						$response = $this->solutionTarget->delete($send);
					}
					else {
						$response[$documentId] = false;
						$response['error']= 'Type transfer '.$type.' unknown. ';
					}
				}
				else {
					$response[$documentId] = false;
					$response['error'] = $connect['error'];
				}
			}
		} catch (\Exception $e) {
			$response['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			echo $response['error'];
			$this->logger->error( $response['error'] );
		}	
		return $response;
	}
	
	protected function checkDuplicate($transformedData) {
		// Traitement si présence de champ duplicate
		if (empty($this->ruleParams['duplicate_fields'])) {
			return $transformedData;
		}
		$duplicate_fields = explode(';',$this->ruleParams['duplicate_fields']);
		$searchDuplicate = array();
		// Boucle sur chaque donnée qui sera envoyée à la cible
		foreach ($transformedData AS $docId => $rowTransformedData) {
			// Stocke la valeur des champs duplicate concaténée
			$concatduplicate = '';

			// Récupération des valeurs de la source pour chaque champ de recherche
			foreach($duplicate_fields as $duplicate_field) {
				$concatduplicate .= $rowTransformedData[$duplicate_field];
			}
			// Empty data aren't used for duplicate search 
			if (!empty(trim($concatduplicate))) {
				$searchDuplicate[$docId] = array('concatKey' => $concatduplicate, 'source_date_modified' => $rowTransformedData['source_date_modified']);
			}
		}
	
		// Recherche de doublons dans le tableau searchDuplicate
		if (!empty($searchDuplicate)) {
			// Obtient une liste de colonnes
			foreach ($searchDuplicate as $key => $row) {
				$concatKey[$key]  = $row['concatKey'];
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
					$doc = new document($this->logger, $this->container, $this->connection, $param);
					$doc->setMessage('Failed to send document because this record is already send in another document. To prevent create duplicate data in the target system, this document will be send in the next job.');
					$doc->setTypeError('W');
					$doc->updateStatus('Transformed');
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
	
	protected function selectDocuments($status, $type = '') {
		try {					
			$query_documents = "	SELECT * 
									FROM Document 
									WHERE 
											rule_id = :ruleId
										AND status = :status
										AND Document.deleted = 0 
									ORDER BY Document.source_date_modified ASC	
									LIMIT $this->limit
								";							
			$stmt = $this->connection->prepare($query_documents);
			$stmt->bindValue(":ruleId", $this->ruleId);
			$stmt->bindValue(":status", $status);
		    $stmt->execute();	   				
			return $stmt->fetchAll();
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
		}
	}
	
	// Permet de récupérer les données d'un document
	protected function getDocumentHeader($documentId) {
		try {
			// We allow to get date from a document flagged deleted
			$query_document = "SELECT * FROM Document WHERE id = :documentId";
			$stmt = $this->connection->prepare($query_document);
			$stmt->bindValue(":documentId", $documentId);
		    $stmt->execute();	   				
			$document = $stmt->fetch();	   				
			if (!empty($document)) {
				return $document;
			}
			else {
				return false;
			}
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
		}
	}
	
	protected function getSendDocuments($type,$documentId,$table = 'target',$parentDocId = '',$parentRuleId = '') {	
		// Init $limit parameter
		$limit = " LIMIT ".$this->limit;
		// Si un document est en paramètre alors on filtre la requête sur le document 
		if (!empty($documentId)) {
			$documentFilter = " Document.id = '$documentId'";
		}
		elseif (!empty($parentDocId)) {
			$documentFilter = " Document.parent_id = '$parentDocId' AND Document.rule_id = '$parentRuleId' "; 
			// No limit when it comes to child rule. A document could have more than $limit child documents
			$limit = "";
		}
		// Sinon on récupère tous les documents élligible pour l'envoi
		else {
			$documentFilter = 	"	Document.rule_id = '$this->ruleId'
								AND Document.status = 'Ready_to_send'
								AND Document.deleted = 0 
								AND Document.type = '$type' ";
		}
		// Sélection de tous les documents au statut transformed en attente de création pour la règle en cours
		$sql = "SELECT Document.id id_doc_myddleware, Document.target_id, Document.source_date_modified
				FROM Document
				WHERE $documentFilter 
				ORDER BY Document.source_date_modified ASC
				$limit";
		$stmt = $this->connection->prepare($sql);
		$stmt->execute();	    
		$documents = $stmt->fetchAll();
				
		foreach ($documents as $document) {		
			// If the rule is a parent, we have to get the data of all rules child		
			$childRules = $this->getChildRules();		
			if (!empty($childRules)) {
				foreach($childRules as $childRule) {
					$ruleChildParam['ruleId'] = $childRule['field_id'];
					$ruleChildParam['jobId'] = $this->jobId;				
					$childRuleObj = new rule($this->logger, $this->container, $this->connection, $ruleChildParam);					
					// Recursive call to get all data from all child in status ready to send generated by the method Document=>runChildRule
					// Child document has the type 'U'									
					$dataChild = $childRuleObj->getSendDocuments('U','',$table,$document['id_doc_myddleware'], $childRule['field_id']);
				
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
			$data = $this->getDocumentData($document['id_doc_myddleware'], 'T');
			if (!empty($data)) {
				$return[$document['id_doc_myddleware']] = array_merge($document,$data);
			}
		}

		if (!empty($return)) {
			return $return;
		}
		return null;
	}

	// Permet de charger tous les champs de la règle
	protected function setRuleField() {	
		try {	
			// Lecture des champs de la règle
			$sqlFields = "SELECT * 
							FROM RuleField 
							WHERE rule_id = :ruleId";
			$stmt = $this->connection->prepare($sqlFields);
			$stmt->bindValue(":ruleId", $this->ruleId);
		    $stmt->execute();	   				
			$this->ruleFields = $stmt->fetchAll();
		
			if($this->ruleFields) {
				foreach ($this->ruleFields as $RuleField) { 
					// Plusieurs champs source peuvent être utilisé pour un seul champ cible
					$fields = explode(";", $RuleField['source_field_name']);
					foreach ($fields as $field) {
						$this->sourceFields[] = ltrim($field);
					}
					$this->targetFields[] = ltrim($RuleField['target_field_name']);
				}			
			}
		
			// Lecture des relations de la règle
			if($this->ruleRelationships) {
				foreach ($this->ruleRelationships as $ruleRelationship) { 
					$this->sourceFields[] = ltrim($ruleRelationship['field_name_source']);
					$this->targetFields[] = ltrim($ruleRelationship['field_name_target']);
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
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
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

	
	
	// Permet de charger toutes les relations de la règle
	protected function setRuleRelationships() {
		try {					
			$sqlFields = "SELECT * 
							FROM RuleRelationShip 
							WHERE 
									rule_id = :ruleId
								AND rule_id IS NOT NULL";
			$stmt = $this->connection->prepare($sqlFields);
			$stmt->bindValue(":ruleId", $this->ruleId);
		    $stmt->execute();	   				
			$this->ruleRelationships = $stmt->fetchAll();
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
		}
	}
    
	// Permet de charger toutes les filtres de la règle
	protected function setRuleFilter() {
		try {					
			$sqlFields = "SELECT * 
							FROM RuleFilter 
							WHERE 
								rule_id = :ruleId";
			$stmt = $this->connection->prepare($sqlFields);
			$stmt->bindValue(":ruleId", $this->ruleId);
		    $stmt->execute();	   				
			$this->ruleFilters= $stmt->fetchAll();
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
		}
	}
    
	// Get the child rules of the current rule
	// Return the relationships between the parent and the clild rules
	public function getChildRules() {
		try {		
			// get the rule linked to the current rule and check if they have the param child
			$sqlFields = "SELECT RuleRelationShip.*
							FROM RuleRelationShip
							WHERE 
									RuleRelationShip.rule_id = :ruleId
								AND RuleRelationShip.parent = 1";
			$stmt = $this->connection->prepare($sqlFields);			
			$stmt->bindValue(":ruleId", $this->ruleId);
		    $stmt->execute();	   				
			return $stmt->fetchAll();
		} catch (\Exception $e) {
			throw new \Exception ('failed to get the child rules : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
		}
	}
	
	// Permet de charger les données du système source pour ce document
	protected function getDocumentData($documentId, $type) {
		try {	
			$documentDataEntity = $this->em
							->getRepository('RegleBundle:DocumentData')
							->findOneBy( array(
										'doc_id' => $documentId,
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
	
	// Parametre de la règle choix utilisateur
	/* 
	array(
		'id' 		=> 'datereference',
		'name' 		=> 'datereference',
		'required'	=> true,
		'type'		=> 'text',
		'label' => 'solution.params.dateref',
		'readonly' => true
	),	*/		
	public static function getFieldsParamUpd() {         
	   return array();
	}
	
	// Parametre de la règle obligation du système par défaut
	public static function getFieldsParamDefault($idSolutionSource = '',$idSolutionTarget = '') {
		return array(
			'active' => false,
			'RuleParam' => array(
				'limit' => '100',
				'delete' => '60',
				'datereference' => date('Y-m-d').' 00:00:00'			
			),
		);		
	}

	// Parametre de la règle en modification dans la fiche
	public static function getFieldsParamView($idRule = '') { 
	   return array(	 
			array(
				'id' 		=> 'datereference',
				'name' 		=> 'datereference',
				'required'	=> true,
				'type'		=> TextType::class,
				'label' => 'solution.params.dateref'
			),
			array(
				'id' 		=> 'limit',
				'name' 		=> 'limit',
				'required'	=> true,
				'type'		=> IntegerType::class,
				'label' => 'solution.params.limit'
			),
			array( // clear data
				'id' 		=> 'delete',
				'name' 		=> 'delete',
				'required'	=> false,
				'type'		=> 'option',
				'label' => 'solution.params.delete',
				'option'	=> array (
								'0' => 'solution.params.0_day',
								'1' => 'solution.params.1_day',
								'7' => 'solution.params.7_day',
								'14' => 'solution.params.14_day',
								'30' => 'solution.params.30_day',
								'60' => 'solution.params.60_day'
							),
			) 		
		);
	}

}


/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Classes/rule.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class rule extends rulecore {
		
	}
}
?>