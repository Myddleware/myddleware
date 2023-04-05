<?php
namespace App\Custom\Solutions;

use App\Solutions\mysql;
use App\Solutions\suitecrm;
use App\Custom\Manager\DocumentManagerCustom;
use App\Manager\DocumentManager;

use Symfony\Bridge\Monolog\Logger;
use Myddleware\RegleBundle\Classes\rule as ruleMyddleware;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType; 


//Sinon on met la classe suivante
class mysqlcustom extends mysql {
	
	private $suitecrmConnId = 1; 	// SuiteCRM connector ID
	private $mysqlREECConnId = 2; 	// MySQL REEC connector ID
	private $mysqlCometConnId = 3; 	// MySQL COMET connector ID
	private $mysqlCometUser = 'middleware'; 	// MySQL connector ID
	private $suitecrm;			 	// SuiteCRM connector object
	private $suiteCRMConnexion = false;
	
	protected $FieldsDuplicate = array(	
										'contact' => array('email'),
										'coupon' => array('jeune_id'),
								  );

	 /**
	 * @var SolutionManager
	 */
	private $solutionManager;

	private $documentManager;
		
	// Si le connecteur est MySQL pour le COMET alros on change la connexion pour utiliser le webservice custom de SuiteCRM
	public function login($paramConnexion) {	
		try {		
			// If SuiteCRM
			if (
				 	$paramConnexion['login'] == $this->mysqlCometUser
				 OR (
						!empty($paramConnexion['ids']['login']['conn_id'])
					AND $paramConnexion['ids']['login']['conn_id'] == $this->mysqlCometConnId
				)
			) {				
				$this->suiteCRMConnexion = true;
				return $this->connexionSuiteCRM();
			} else {		
				$this->suiteCRMConnexion = false;
				return parent::login($paramConnexion);	
			}
		} catch (\Exception $e) {
			$error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)
	
	// Add filter in table securitygroups_records to avoid to read all records
	protected function queryValidation($param, $functionName, $requestSQL, $record) {
		$requestSQL = parent::queryValidation($param, $functionName, $requestSQL, $record);
		if (
				$param['module'] == 'securitygroups_records'
			AND !empty($param['ruleParams']['recordType'])
		) {
			// $param['query']['module'] = $param['ruleParams']['recordType'];
			$requestSQL = str_replace(' WHERE ', ' WHERE '.$this->stringSeparatorOpen.'module'.$this->stringSeparatorClose." = '".$this->escape($param['ruleParams']['recordType'])."' AND ", $requestSQL); 
		}
		return $requestSQL;	
	}
	
	// Clean id empty for foreign key
	protected function checkDataBeforeUpdate($param, $data, $idDoc){
		
		// Manage roles field for reec. We have to merge data coming from 2 rules users custom and engagé by using the history data
		if (in_array($param['rule']['id'], array('5ce3621156127','5d01a630c217c', '63e1007614977', '6273905a05cb2'))) { // REEC users custom / engagé
			// Error if no history data
			if (empty($param['dataHistory'][$idDoc])) {
				throw new \Exception('History data is requiered to calculate the filed role_reec. Errror because history data is empty.');
			}
			// Roles by type (engagé or user)
			$userRoles = array('ROLE_SALARIE','ROLE_SIEGE','ROLE_ADMIN');
			$engageRoles = array('ROLE_MENTOR','ROLE_VOLONTAIRE');
			$partenaireRoles = array('ROLE_ETABLISSEMENT');
			$reperantRoles = array('ROLE_REPERANT');

			// Get the roles to be sent
			$targetRoles = array();
			$sourceRoles = unserialize($data['roles']);
			$historyRoles = unserialize($param['dataHistory'][$idDoc]['roles']);
			// Keep the right history roles if exists
			if (!empty($historyRoles)) {
				if ($param['rule']['id'] == '5ce3621156127') { // REEC engagé
					// Always keep the non engage role
					$targetRoles = array_intersect($historyRoles, array_merge($userRoles,$partenaireRoles,$reperantRoles));
				} elseif ($param['rule']['id'] == '63e1007614977') { // REEC user custom
					// Always keep the non user role
					$targetRoles = array_intersect($historyRoles, array_merge($engageRoles,$partenaireRoles,$reperantRoles));
				} elseif ($param['rule']['id'] == '5d01a630c217c') { //  REEC - Contact - Composante
					// Always keep the non contact partenaire role
					$targetRoles = array_intersect($historyRoles, array_merge($userRoles,$engageRoles,$reperantRoles));
				} elseif ($param['rule']['id'] == '6273905a05cb2') { //  Esp Rep - Contacts repérants
					// Always keep the non reperant role
					$targetRoles = array_intersect($historyRoles, array_merge($userRoles,$engageRoles,$partenaireRoles));
				}
			}
			// Merge the roles coming from the source and the roles coming from the history
			if (empty($targetRoles)) {
				$targetRoles = $sourceRoles;
			} elseif (!empty($sourceRoles)) {
				$targetRoles = array_unique(array_merge($targetRoles, $sourceRoles));
			}

			if ($data['roles'] != serialize($targetRoles)) {
				$data['roles'] = serialize($targetRoles);

				// Change the document data 
				if (!isset($this->documentManager)) {
					$this->documentManager = new DocumentManager(
						$this->logger, 
						$this->connection, 
						$this->entityManager,
						$this->documentRepository,
						$this->ruleRelationshipsRepository,
						$this->formulaManager
					);
				}
				$paramDoc['id_doc_myddleware'] = $idDoc;
				$paramDoc['jobId'] = $param['jobId'];
				$this->documentManager->setParam($paramDoc);
				$this->documentManager->updateDocumentData($idDoc, array('roles'=>$data['roles']), 'T');
			};
		}

		// Call standard function
		$data = parent::checkDataBeforeUpdate($param, $data, $idDoc);
		return $this->checkData($param, $data);	
	}
	
	// Clean id empty for foreign key
	protected function checkDataBeforeCreate($param, $data, $idDoc){
		// Call standard function
		$data = parent::checkDataBeforeCreate($param, $data, $idDoc);
		return $this->checkData($param, $data);
	}	
	
	// Clean id empty for foreign key
	protected function checkData($param, $data){
		// We can't send a field with id empty because of foreign key
		if ($param['module'] == 'contact') {
			if (
					isset($data['universite_id'])
				AND empty($data['universite_id'])
			) {
				unset($data['universite_id']);
			}
			if (
					isset($data['referant_id'])
				AND empty($data['referant_id'])
			) {
				unset($data['referant_id']);
			}
			if (
					isset($data['date_naissance'])
				AND empty($data['date_naissance'])
			) {
				unset($data['date_naissance']);
			}
			if (
					isset($data['date_fin_acces_bilan'])
				AND empty($data['date_fin_acces_bilan'])
			) {
				unset($data['date_fin_acces_bilan']);
			}
			if (
					isset($data['date_acces_bilan'])
				AND empty($data['date_acces_bilan'])
			) {
				unset($data['date_acces_bilan']);
			}
			if (
					isset($data['date_deb_mentorat'])
				AND empty($data['date_deb_mentorat'])
			) {
				unset($data['date_deb_mentorat']);
			}
		}
		
		// We can't send decimal empty and don't wnt to set a 0 instead
		if ($param['module'] == 'binome') {
			if (
					isset($data['moyenne_note_famille'])
				AND empty($data['moyenne_note_famille'])
			) {
				unset($data['moyenne_note_famille']);
			}
			if (
					isset($data['moyenne_note_suivi'])
				AND empty($data['moyenne_note_suivi'])
			) {
				unset($data['moyenne_note_suivi']);
			}
			if (
					isset($data['referant_id'])
				AND empty($data['referant_id'])
			) {
				unset($data['referant_id']);
			}
		}
		
		// We can't send decimal empty and don't wnt to set a 0 instead
		if ($param['module'] == 'suivi') {
			if (
					isset($data['note'])
				AND empty($data['note'])
			) {
				unset($data['note']);
			}
			if (
					isset($data['note_famille'])
				AND empty($data['note_famille'])
			) {
				unset($data['note_famille']);
			}
			if (
					isset($data['binome_id'])
				AND empty($data['binome_id'])
			) {
				unset($data['binome_id']);
			}
			if (
					isset($data['nb_seances_annulees'])
				AND empty($data['nb_seances_annulees'])
			) {
				unset($data['nb_seances_annulees']);
			}
			if (
					isset($data['nb_seances_realisees'])
				AND empty($data['nb_seances_realisees'])
			) {
				unset($data['nb_seances_realisees']);
			}
		}
		
		// We can't send a field with id empty because of foreign key
		if ($param['module'] == 'coupon') {
			if (
					isset($data['date_naissance'])
				AND empty($data['date_naissance'])
			) {
				unset($data['date_naissance']);
			}
			if (
							isset($data['reperant_id'])
					AND empty($data['reperant_id'])
			) {
					unset($data['reperant_id']);
			}
			if (
							isset($data['composante_id'])
					AND empty($data['composante_id'])
			) {
					unset($data['composante_id']);
			}
		}
		return $data;		
	}
	
	// Add filter for contact module
	public function getFieldsParamUpd($type, $module): array {	
		try {
			$params = parent::getFieldsParamUpd($type, $module);
			 if ($type == 'source'){
				if ($module == 'securitygroups_records'){
					$params[] = array(
							'id' => 'recordType',
							'name' => 'recordType',
							'type' => 'option',
							'label' => 'Record type',
							'required'	=> true,
							'option'	=> array(
												'Accounts' => 'Accounts',
												'AOS_PDF_Templates' => 'AOS_PDF_Templates',
												'Calls' => 'Calls',
												'Contacts' => 'Contacts',
												'CRMC_binome' => 'CRMC_binome',
												'CRMC_Evaluation' => 'CRMC_Evaluation',
												'CRMC_Suivi' => 'CRMC_Suivi',
												'CRMC__etablissement_sup' => 'CRMC__etablissement_sup',
												'CRMD_dispositif_financement' => 'CRMD_dispositif_financement',
												'CRMD_historique_engage' => 'CRMD_historique_engage',
												'Emails' => 'Emails',
												'EmailTemplates' => 'EmailTemplates',
												'FP_events' => 'FP_events',
												'jjwg_Maps' => 'jjwg_Maps',
												'jjwg_Markers' => 'jjwg_Markers',
												'Leads' => 'Leads',
												'Meetings' => 'Meetings',
												'modcl_creation_volontaire' => 'modcl_creation_volontaire',
												'modcl_objectifs_annuels' => 'modcl_objectifs_annuels',
												'mod_2_quartiers' => 'mod_2_quartiers',
												'Notes' => 'Notes',
												'Project' => 'Project',
												'ProjectTask' => 'ProjectTask',
												'Tasks' => 'Tasks'
											)
						); 
				}
			}
			return $params;
		}
		catch (\Exception $e){
			return array();
		}
	}
	
	
	// Get all tables from the database
	public function get_modules($type = 'source') {		
		try{
			// Call standard function in case of standard MySQL connector
			if (!$this->suiteCRMConnexion) {	
				return parent::get_modules($type);	
			} 
			
			// If SQL using SuiteCRM webservice
			$modules = array();
			// Send Query to SuiteCRM and not MySQL
			$res = $this->suitecrm->send_query('SHOW TABLES');	
			$queryResult = json_decode($res);		
			if ($queryResult->status != 'success') {
				throw new \Exception('Error call function send_special_query : '.$queryResult->message);
			}
			if (!empty($queryResult->values)) {
				$fetchAll = $queryResult->values;
				if (!empty($fetchAll)) {
					foreach($fetchAll as $module) {
						$modules[$module->Tables_in_crm] = $module->Tables_in_crm;
					}	
				}
			}
			return $modules;			
		} catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			return $error;			
		}
	} 	
	
	
	// Get all fields from the table selected
	public function get_module_fields($module, $type = 'source', $param = null): array {
		try{
			// Call standard function in case of standard MySQL connector
			if (!$this->suiteCRMConnexion) {		
				return parent::get_module_fields($module, $type);	
			} 
			
			// If SQL using SuiteCRM webservice
			if (empty($this->suitecrm)) {
				$this->connexionSuiteCRM();
			}
			$res = $this->suitecrm->send_query($this->get_query_describe_table($module));	
			$queryResult = json_decode($res);		
			if ($queryResult->status != 'success') {
				throw new \Exception('Error call function send_special_query : '.$queryResult->message);
			}
			// Change object to array 
			if (!empty($queryResult->values)) {
				$fetchAll = $queryResult->values;
				if (!empty($fetchAll)) {
					foreach($fetchAll as $field) {
						$fieldArray = array();
						foreach($field as $key => $paramField) {
							$fieldArray[$key] = $paramField;
						}
						$fields[] = $fieldArray;
					}
				}
			}
			// Add email_address field for user module
			if ($module == 'users') {
				$fields[] = array('Field'=> 'email_address', 'Type'=> 'varchar(255)');
			}

			// STANDARD CODE FROM THERE
			// Get field ID
			$idFields = $this->getIdFields($module,$type,$fields);			
		
			foreach ($fields as $field) {
				// Convert field to be compatible with Myddleware. For example, error happens when there is space in the field name
				$field[$this->fieldName] = rawurlencode($field[$this->fieldName]);
				
				$this->moduleFields[$field[$this->fieldName]] = array(
						'label' => $field[$this->fieldLabel],
						'type' => $field[$this->fieldType],
						'type_bdd' => 'varchar(255)',
						'required' => false,
						'relate' => false
				);
				if (
						strtoupper(substr($field[$this->fieldName],0,2)) == 'ID'
					OR	strtoupper(substr($field[$this->fieldName],-2)) == 'ID'
				) {
					$this->moduleFields[$field[$this->fieldName]] = array(
							'label' => $field[$this->fieldLabel],
							'type' => $field[$this->fieldType],
							'type_bdd' => 'varchar(255)',
							'required' => false,
							'relate' => true
					);
				}
				// If the field contains the id indicator, we add it to the fieldsRelate list
				if (!empty($idFields)) {
					foreach ($idFields as $idField) {		
						if (strpos($field[$this->fieldName],$idField) !== false) {
							$this->moduleFields[$field[$this->fieldName]] = array(
									'label' => $field[$this->fieldLabel],
									'type' => $field[$this->fieldType],
									'type_bdd' => 'varchar(255)',
									'required' => false,
									'relate' => true
							);
						}
					}
				}
			}
	
			// Add field current ID in the relationships
			if ($type == 'target') {
				$this->moduleFields['Myddleware_element_id'] = array(
										'label' => 'ID '.$module,
										'type' => 'varchar(255)',
										'type_bdd' => 'varchar(255)',
										'required' => false,
										'relate' => true
									);
			}				
			return $this->moduleFields;
		}
		catch (\Exception $e){
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			return false;
		}
	} // get_module_fields($module) 
	
	// On se connecte à la base de données en lecture en utilisant l'accès à SuiteCRM etune fonction webservice custom
	public function readData($param) {
		// No read action for rule REEC - Users custom when we read using date ref. The rule can now be activated
		if (
				$param['rule']['id'] == '63e1007614977'
			AND $param['call_type'] == 'read'
			AND empty($param['query']['id'])
		) {
			return array();
		}

		// On appel le code custom que pour le connecteur MySQL COMET 
		if (
			(
					$param['call_type'] == 'history'
				AND $param['rule']['conn_id_target'] != $this->mysqlCometConnId	
			)
			OR (
					$param['call_type'] == 'read'
				AND $param['rule']['conn_id_source'] != $this->mysqlCometConnId				
			)
		) {
			return parent::readData($param);
		}
	
		// In case we search by contact (generate document after an engage is sent) for the rule Composante - Engagé, we don't search for deleted record
		if (
				$param['rule']['id'] == '5f8486295b5a7'
			AND !empty($param['query']['contact_id'])
		) {
			$param['ruleParams']['deletion'] = 0;
		}
		
		/* DEBUT COPIE DE CODE STANDARD */	
		$result = array();
		// Decode field name (converted in method get_module_fields)
		$param['fields'] = array_map('rawurldecode',$param['fields']);
		try {
			// On contrôle la date de référence, si elle est vide on met 0 (cas fréquent si l'utilisateur oublie de la remplir)		
			if(empty($param['date_ref'])) {
				$param['date_ref'] = 0;
			}
			if (empty($param['limit'])) {
				$param['limit'] = 100;
			}
			
			// Add the deletion field into the list field to be read if deletion is enabled on the rule
			if (
					!empty($param['ruleParams']['deletion'])
				AND	!empty($param['ruleParams']['deletionField'])
			) {
				$param['fields'][] = $param['ruleParams']['deletionField'];
			}
			
			// Add requiered fields
			if(!isset($param['ruleParams']['fieldId'])) {
				throw new \Exception('FieldId has to be specified for the read.');
			}
			if(!isset($param['ruleParams']['fieldDateRef'])) {
				throw new \Exception('"fieldDateRef" has to be specified for the read.');
			}
			$this->required_fields =  array('default' => array($param['ruleParams']['fieldId'], $param['ruleParams']['fieldDateRef']));
			
			if(!isset($param['fields'])) {
				$param['fields'] = array();
			}
			$param['fields'] = array_unique($param['fields']);
			$param['fields'] = $this->addRequiredField($param['fields']);
			$param['fields'] = array_values($param['fields']);
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			
			// Query building
			$query['select'] = $this->get_query_select_header($param, 'read');	
			// Build field list
			foreach ($param['fields'] as $field){
				// myddleware_generated isn't a real field in the database
				if ($field != 'myddleware_generated') {
					// $query['select'] .= $this->stringSeparatorOpen.$field.$this->stringSeparatorClose. ", "; // MODIF AFEV
					$query['select'] .= $this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose.'.'.$this->stringSeparatorOpen.$field.$this->stringSeparatorClose. ", "; // MODIF AFEV
				}
			}
			// Remove the last coma
			$query['select'] = rtrim($query['select'],' '); 
			$query['select'] = rtrim($query['select'],',').' '; 		
			$query['from'] = "FROM ".$this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose;

			// if a specific query is requested we don't use date_ref
			if (!empty($param['query'])) {
				$nbFilter = count($param['query']);
				$query['where'] = " WHERE ";
				foreach ($param['query'] as $queryKey => $queryValue) {
					// Manage query with id, to be replaced by the ref Id fieldname
					if ($queryKey == 'id') {
						if ($param['ruleParams']['fieldId'] == 'myddleware_generated') {
							throw new \Exception('Not possible to read a specific record when myddleware_generated is selected as the Primary key in your source table');
						}
						$queryKey = $param['ruleParams']['fieldId'];
					}
					$query['where'] .= $this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose.'.'.$this->stringSeparatorOpen.$queryKey.$this->stringSeparatorClose." = '".$this->escape($queryValue)."' "; 
					$nbFilter--;
					if ($nbFilter > 0){
						$query['where'] .= " AND ";	
					}
				}
			} else {
				$query['where'] = " WHERE ".$this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose.'.'.$this->stringSeparatorOpen.$param['ruleParams']['fieldDateRef'].$this->stringSeparatorClose. " > '".$param['date_ref']."'";
			}
			
			$query['order'] = " ORDER BY ".$this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose.'.'.$this->stringSeparatorOpen.$param['ruleParams']['fieldDateRef'].$this->stringSeparatorClose. " ASC"; // Tri par date utilisateur
			$query['limit'] = $this->get_query_select_limit_offset($param, 'read'); // Add query limit
			
			// Build query
			$requestSQL = $this->buildQuery($param, $query);
			// Query validation
			$requestSQL = $this->queryValidation($param, 'read', $requestSQL, '');
			/* FIN COPIE DE CODE STANDARD */	



			/* DEBUT RECUPERATION DES DONNEES EN APPELANT SUITECRM */
			// Envoie de la requête à SuiteCRM et non à la base de données						
			$res = $this->suitecrm->send_query($requestSQL);
			$queryResult = json_decode($res);		
			if ($queryResult->status != 'success') {
				throw new \Exception('Error call function send_special_query : '.$queryResult->message);
			}
			if (!empty($queryResult->values)) {
				$fetchAll = $queryResult->values;
			}
			/* FIN RECUPERATION DES DONNEES EN APPELANT SUITECRM */


			/* DEBUT COPIE DE CODE STANDARD */
			$row = array();
			if(!empty($fetchAll)) {
				$result['count'] = count($fetchAll);
				foreach ($fetchAll as $elem) {
					$row = array();
					// Generate an id in case myddleware_generated is selected in the rule
					if ($param['ruleParams']['fieldId'] == 'myddleware_generated') {
						$row['id'] = $this->generateId($param,$elem);
					}
					foreach ($elem as $key => $value) {
						if($key === $param['ruleParams']['fieldId']) { // key can't be equal to 'myddleware_generated' (no in select part of the query)
							$row['id'] = $value;
						}
						if($key === $param['ruleParams']['fieldDateRef']) {
							// If the reference isn't a valid date (it could be an ID in case there is no date in the table) we set the current date
							if ((bool)strtotime($value)) {;
								$row['date_modified'] = $value;
							} else {							
								$row['date_modified'] = date('Y-m-d H:i:s');
							}
							$result['date_ref'] = $value;
						}
						if(in_array($key, $param['fields'])) {
							// Encode the field to match with the fields retruned by method get_module_fields
							$row[rawurlencode($key)] = $value;
						}
						// Manage deletion by adding the flag Myddleware_deletion to the record						
						if (
								!empty($param['ruleParams']['deletion'])
							AND $param['ruleParams']['deletionField'] === $key
							AND !empty($value)
						) {
							$row['myddleware_deletion'] = true;
						}
					}
					$result['values'][$row['id']] = $row;
				}
			}
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
		}
		return $result;
	} // read($param)
	
	// Function to buid the SELECT query
	protected function buildQuery($param, $query): string {
		// If deletetion not requested we add the filter on deleted field (only for COMET connector)
		if (
				empty($param['ruleParams']['deletion'])
			AND (
					(
							$param['call_type'] == 'history'
						AND $param['rule']['conn_id_target'] != $this->mysqlREECConnId	
					)
					OR (
							$param['call_type'] == 'read'
						AND $param['rule']['conn_id_source']!= $this->mysqlREECConnId				
					)
				)
			// No deleted field for custom tables
			AND substr($param['module'], -5) != '_cstm'
		) {		
			$query['where'] .= ' AND '.$this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose.'.deleted = 0 ';
		}
		
		// On récupère l'email du user grâce à une jointure
		if (
				in_array($param['rule']['id'], array('5cf98651a17f3','61a9190e40965')) // règle users		
			AND $param['call_type'] != 'history' // Not for history because history call REEC database	
		) { 
			// Correction du select 
			$query['select'] = str_replace("`users`.`email_address`" , "`email_addresses`.`email_address`", $query['select']);
			// Ajout de la jointure
			$query['from'] .= "
				LEFT OUTER JOIN email_addr_bean_rel
					 ON email_addr_bean_rel.bean_id = users.id
					AND email_addr_bean_rel.bean_module = 'Users'
					AND email_addr_bean_rel.deleted = 0
					AND email_addr_bean_rel.primary_address = 1
					LEFT OUTER JOIN email_addresses
						 ON email_addresses.id = email_addr_bean_rel.email_address_id
						AND email_addresses.deleted = 0 ";	
		}
		
		// Add record type filter for rule coupon - pole vers COMET
		if (in_array($param['rule']['id'], array('62b30d5c4a3fe'))) { // Esp Rep - Coupon - Pôles vers COMET
			$query['where'] .= " AND record_type = 'Leads' ";
		}
		return parent::buildQuery($param, $query);
	}
	
	
	public function createData($param): array {	
		// Myddleware can change the email only if compte_reec_ok = 0
		// règle REEC - users, REEC - Composante, Esp Rep - Coupons vers Esp Rep
		if (in_array($param['rule']['id'], array('5cf98651a17f3', '5ce362b962b63', '62739b419755f'))) {	
			// For every document
			foreach($param['data'] as $idDoc => $data) {				
				// compte_reec_ok always equal to 0. It can be equal to "DO NOT SEND" in case of volontaire but only for update
				if ($param['rule']['id'] == '5cf98651a17f3') {	// règle users
					if (!empty($param['data'][$idDoc]['compte_reec_ok'])) {
						$param['data'][$idDoc]['compte_reec_ok'] = '0';				
					}
				}
				// Do not send an empty etablissement on the composante
				if (
						$param['rule']['id'] == '5ce362b962b63' // REEC - Composante
					AND empty($data['etablissement_sup_id'])
				) {
					unset($param['data'][$idDoc]['etablissement_sup_id']);
				}
				// Do not send an empty jeune on the coupon
				if (
						$param['rule']['id'] == '62739b419755f' // Esp Rep - Coupons vers Esp Rep
					AND empty($data['jeune_id'])
				) {
					unset($param['data'][$idDoc]['jeune_id']);
				}
			}
		}
		return parent::createData($param);
	}
	
	public function updateData($param): array {		
		// Myddleware can change the email only if compte_reec_ok = 0
		// règle users , engagé , REEC - Composante, Esp Rep - Coupons vers Esp Rep
		if (in_array($param['rule']['id'], array('5cf98651a17f3','5ce3621156127','5ce362b962b63','62739b419755f'))) {
			// For every document
			foreach($param['data'] as $idDoc => $data) {	
				if (in_array($param['rule']['id'], array('5cf98651a17f3','5ce3621156127'))) {		// règle users , engagé	
					// If rule user and title = Volontaire, we never update the contact into REEC.
					// Updates will be managed by the corresponding contact				
					if (
							$param['rule']['id'] == '5cf98651a17f3'	
						AND $param['data'][$idDoc]['compte_reec_ok'] == 'DO NOT SEND IF UPDATE, 0 IF CREATE'
					) {
						$value = array(
										'id' => $param['data'][$idDoc]['target_id'],
										'error' => 'No update using rule user for volontaire, only rule engage can update contact volontaire'
									);
						$this->updateDocumentStatus($idDoc,$value,$param, 'No_send');
						unset($param['data'][$idDoc]); // Do not send this document
					}
					
					// Update email if 
						// 		Email has been changed
						// AND 	volontaire = 1 OR type = “User COMET” + compte_reec_ok = 1 
					if (
							!empty($param['data'][$idDoc]['email'])
						AND !empty($param['dataHistory'][$idDoc])	
						AND $param['data'][$idDoc]['email'] != $param['dataHistory'][$idDoc]['email']
						AND (
								!empty($param['data'][$idDoc]['volontaire'])
							 OR (
									!empty($param['dataHistory'][$idDoc]['compte_reec_ok'])
								AND $param['dataHistory'][$idDoc]['type'] == 'User Comet'
							)
						)
					) {
						$param['data'][$idDoc]['email_updated'] = 1;					
					// Email can always be updated if compet REEC isn't created
					} elseif (!empty($param['dataHistory'][$idDoc]['compte_reec_ok'])) {
						unset($param['data'][$idDoc]['email']);				
					}					
						
					// Never modify compte_reec_ok
					unset($param['data'][$idDoc]['compte_reec_ok']);
				}
				// Do not send an empty etablissement on the composante
				if (
						$param['rule']['id'] == '5ce362b962b63' // REEC - Composante
					AND empty($data['etablissement_sup_id'])
				) {
					unset($param['data'][$idDoc]['etablissement_sup_id']);
				}
				// Do not send an empty jeune on the coupon
				if (
						$param['rule']['id'] == '62739b419755f' // Esp Rep - Coupons vers Esp Rep
					AND empty($data['jeune_id'])
				) {
					unset($param['data'][$idDoc]['jeune_id']);
				}
			}		
		}
	
		// We send nouveau = 1 only in creation otherwise we don't send the field
		if (in_array($param['rule']['id'], array('5ce3621156127', '5cdf83721067d'))) { 	// règle engagé + accompagné
			// For every document
			foreach($param['data'] as $idDoc => $data) {
				if (isset($param['data'][$idDoc]['nouveau'])) {					
					unset($param['data'][$idDoc]['nouveau']);
				}
			}
		}	
		if (empty($param['data'])) {
			return array('error' => 'All documents cancelled before sending.');
		}
		return parent::updateData($param);
	}
	
	// Connect to the source or target application
	private function connexionSuiteCRM() {
		try {
		
			// Get the name of the application			
		    $sql = "SELECT solution.name  
		    		FROM connector
						INNER JOIN solution 
							ON solution.id  = connector.sol_id
		    		WHERE connector.id = :connId";
		    $stmt = $this->connection->prepare($sql);
			$stmt->bindValue(":connId", $this->suitecrmConnId);
			$result = $stmt->executeQuery();
            $r = $result->fetchAssociative();	
			
			// Get params connection
		    $sql = "SELECT id, conn_id, name, value
		    		FROM connectorparam 
		    		WHERE conn_id = :connId";
		    $stmt = $this->connection->prepare($sql);
			$stmt->bindValue(":connId", $this->suitecrmConnId);
		    $stmt->execute();	    
			$resultConn = $stmt->executeQuery();
            $tab_params = $resultConn->fetchAllAssociative();
			
			$params = array();
			if(!empty($tab_params)) {
				foreach ($tab_params as $key => $value) {
					$params[$value['name']] = $value['value'];
					$params['ids'][$value['name']] = array('id' => $value['id'],'conn_id' => $value['conn_id']);
				}			
			}
			
			// Connect to the application
			$this->suitecrm = new suitecrmcustom(
				$this->logger, $this->connection, 
				$this->parameterBagInterface, 
				$this->entityManager,
				$this->documentRepository,
				$this->ruleRelationshipsRepository,
				$this->formulaManager
			);								
			$this->suitecrm->setApi(0);				
			$loginResult = $this->suitecrm->login($params);			
			$this->connexion_valide = (($this->suitecrm->connexion_valide) ? true : false );				
			if(!empty($loginResult['error'])) {
				throw new \Exception($loginResult['error']);
			}
		} catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error($error);
			throw new \Exception($error);
		}	
		return $this->connexion_valide;		
	}
	
	// Allow the search only mode on coupon module
	public function getRuleMode($module, $type): array
    {
        $ruleMode = parent::getRuleMode($module, $type);
        if (
                'target' == $type
            AND $module == 'coupon'
        ) {
            $ruleMode['S'] = 'search_only';
        }
		return $ruleMode;
    }
	
	// Generate ID for the document
    protected function generateId($param, $record): string
    {
		if ($param['rule']['id'] == '627153382dc34') { // Mobilisation - Participations RI
			return $record->fp_events_leads_1fp_events_ida.$record->fp_events_leads_1leads_idb;
		}
        return parent::generateId($param, $record);
    }
}