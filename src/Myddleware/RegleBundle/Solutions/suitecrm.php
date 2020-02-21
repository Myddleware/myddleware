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

namespace Myddleware\RegleBundle\Solutions;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class suitecrmcore  extends solution {

	protected $limitCall = 100;
	protected $urlSuffix = '/service/v4_1/rest.php';
	
	// Enable to read deletion and to delete data
	protected $readDeletion = true;	
	protected $sendDeletion = true;	
	
    protected $required_fields = array('default' => array('id','date_modified','date_entered'));
	
	protected $FieldsDuplicate = array(	'Contacts' => array('email1', 'last_name'),
										'Accounts' => array('email1', 'name'),
										'Users' => array('email1', 'last_name'),
										'Leads' => array('email1', 'last_name'),
										'Prospects' => array('email1', 'name'),
										'default' => array('name')
									  );

	protected $required_relationships = array(
												'default' => array(),
												'Contacts' => array(),
												'Cases' => array()
										);
	
	// liste des modules à exclure pour chaque solution
	protected $exclude_module_list = array(
										'default' => array('Home','Calendar','Documents','Administration','Currencies','CustomFields','Connectors','Dropdown','Dynamic','DynamicFields','DynamicLayout','EditCustomFields','Help','Import','MySettings','FieldsMetaData','UpgradeWizard','Sync','Versions','LabelEditor','Roles','OptimisticLock','TeamMemberships','TeamSets','TeamSetModule','Audit','MailMerge','MergeRecords','Schedulers','Schedulers_jobs','Groups','InboundEmail','ACLActions','ACLRoles','DocumentRevisions','ACL','Configurator','UserPreferences','SavedSearch','Studio','SugarFeed','EAPM','OAuthKeys','OAuthTokens'),
										'target' => array(),
										'source' => array(),
									);
									
	protected $exclude_field_list = array(
											'default' => array('date_entered','date_modified','created_by_name','modified_by_name','created_by','modified_user_id'),
											'Contacts' => array('c_accept_status_fields','m_accept_status_fields','accept_status_id','accept_status_name','opportunity_role_fields','opportunity_role_id','opportunity_role','email'),
											'Leads' => array('email'),
											'Accounts' => array('email'),
											'Cases' => array('case_number')
										);
	
	
	// Tableau représentant les relation many-to-many de Sugar
	protected $module_relationship_many_to_many = array(
													'calls_contacts' => array('label' => 'Relationship Call Contact', 'module_name' => 'Calls', 'link_field_name' => 'contacts', 'fields' => array(), 'relationships' => array('call_id','contact_id')),
													'calls_users' => array('label' => 'Relationship Call User', 'module_name' => 'Calls', 'link_field_name' => 'users', 'fields' => array(), 'relationships' => array('call_id','user_id')),
													'calls_leads' => array('label' => 'Relationship Call Lead', 'module_name' => 'Calls', 'link_field_name' => 'leads', 'fields' => array(), 'relationships' => array('call_id','lead_id')),
													'cases_bugs' => array('label' => 'Relationship Case Bug', 'module_name' => 'Cases', 'link_field_name' => 'bugs', 'fields' => array(), 'relationships' => array('case_id','bug_id')),
													'contacts_bugs' => array('label' => 'Relationship Contact Bug', 'module_name' => 'Contacts', 'link_field_name' => 'bugs', 'fields' => array(), 'relationships' => array('contact_id','bug_id')),
													'contacts_cases' => array('label' => 'Relationship Contact Case', 'module_name' => 'Contacts', 'link_field_name' => 'cases', 'fields' => array(), 'relationships' => array('contact_id','case_id')),
													'meetings_contacts' => array('label' => 'Relationship Metting Contact', 'module_name' => 'Meetings', 'link_field_name' => 'contacts', 'fields' => array(), 'relationships' => array('meeting_id','contact_id')),
													'meetings_users' => array('label' => 'Relationship Meeting User', 'module_name' => 'Meetings', 'link_field_name' => 'users', 'fields' => array(), 'relationships' => array('meeting_id','user_id')),
													'meetings_leads' => array('label' => 'Relationship Meeting Lead', 'module_name' => 'Meetings', 'link_field_name' => 'leads', 'fields' => array(), 'relationships' => array('meeting_id','lead_id')),
													'opportunities_contacts' => array('label' => 'Relationship Opportunity Contact', 'module_name' => 'Opportunities', 'link_field_name' => 'contacts', 'fields' => array('contact_role'), 'relationships' => array('opportunity_id','contact_id')), // contact_role exist in opportunities vardef for module contact (entry rel_fields)
													'prospect_list_campaigns' => array('label' => 'Relationship Prospect_list Campaign', 'module_name' => 'ProspectLists', 'link_field_name' => 'campaigns', 'fields' => array(), 'relationships' => array('prospect_list_id','campaign_id')),
													'prospect_list_contacts' => array('label' => 'Relationship Prospect_list Contact', 'module_name' => 'ProspectLists', 'link_field_name' => 'contacts', 'fields' => array(), 'relationships' => array('prospect_list_id','contact_id')),
													'prospect_list_prospects' => array('label' => 'Relationship Prospect_list Prospect', 'module_name' => 'ProspectLists', 'link_field_name' => 'prospects', 'fields' => array(), 'relationships' => array('prospect_list_id','Prospect_id')),
													'prospect_list_leads' => array('label' => 'Relationship Prospect_list Lead', 'module_name' => 'ProspectLists', 'link_field_name' => 'leads', 'fields' => array(), 'relationships' => array('prospect_list_id','lead_id')),
													'prospect_list_users' => array('label' => 'Relationship Prospect_list User', 'module_name' => 'ProspectLists', 'link_field_name' => 'users', 'fields' => array(), 'relationships' => array('prospect_list_id','user_id')),
													'prospect_list_accounts' => array('label' => 'Relationship Prospect_list Account', 'module_name' => 'ProspectLists', 'link_field_name' => 'accounts', 'fields' => array(), 'relationships' => array('prospect_list_id','account_id')),
													'projects_bugs' => array('label' => 'Relationship Project Bug', 'module_name' => 'Projects', 'link_field_name' => 'bugs', 'fields' => array(), 'relationships' => array('project_id','bug_id')),
													'projects_cases' => array('label' => 'Relationship Project Case', 'module_name' => 'Projects', 'link_field_name' => 'cases', 'fields' => array(), 'relationships' => array('project_id','case_id')),
													'projects_accounts' => array('label' => 'Relationship Project Account', 'module_name' => 'Projects', 'link_field_name' => 'accounts', 'fields' => array(), 'relationships' => array('project_id','account_id')),
													'projects_contacts' => array('label' => 'Relationship Project Contact', 'module_name' => 'Projects', 'link_field_name' => 'contacts', 'fields' => array(), 'relationships' => array('project_id','contact_id')),
													'projects_opportunities' => array('label' => 'Relationship Project Opportunity', 'module_name' => 'Projects', 'link_field_name' => 'opportunities', 'fields' => array(), 'relationships' => array('project_id','opportunity_id')),
													'email_marketing_prospect_lists' => array('label' => 'Relationship Email_marketing Prospect_list', 'module_name' => 'EmailMarketing', 'link_field_name' => 'prospect_lists', 'fields' => array(), 'relationships' => array('email_marketing_id','prospect_list_id')),
													'leads_documents' => array('label' => 'Relationship Lead Document', 'module_name' => 'Leads', 'link_field_name' => 'documents', 'fields' => array(), 'relationships' => array('lead_id','document_id')),
													'documents_accounts' => array('label' => 'Relationship Document Account', 'module_name' => 'Documents', 'link_field_name' => 'accounts', 'fields' => array(), 'relationships' => array('document_id','account_id')),
													'documents_contacts' => array('label' => 'Relationship Document Contact', 'module_name' => 'Documents', 'link_field_name' => 'contacts', 'fields' => array(), 'relationships' => array('document_id','contact_id')),
													'documents_opportunities' => array('label' => 'Relationship Document Opportunity', 'module_name' => 'Documents', 'link_field_name' => 'opportunities', 'fields' => array(), 'relationships' => array('document_id','opportunity_id')),
													'documents_cases' => array('label' => 'Relationship Document Case', 'module_name' => 'Documents', 'link_field_name' => 'cases', 'fields' => array(), 'relationships' => array('document_id','case_id')),
													'documents_bugs' => array('label' => 'Relationship Document Bug', 'module_name' => 'Documents', 'link_field_name' => 'bugs', 'fields' => array(), 'relationships' => array('document_id','bug_id')),
													'aos_quotes_aos_invoices' => array('label' => 'Relationship Quote Invoice', 'module_name' => 'AOS_Quotes', 'link_field_name' => 'aos_quotes_aos_invoices', 'fields' => array(), 'relationships' => array('aos_quotes77d9_quotes_ida','aos_quotes6b83nvoices_idb')),
													'fp_events_contacts' => array('label' => 'Relationship Event Contact', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_contacts', 'fields' => array(), 'relationships' => array('fp_events_contactsfp_events_ida','fp_events_contactscontacts_idb')),
													'fp_events_leads_1' => array('label' => 'Relationship Event Lead', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_leads_1', 'fields' => array(), 'relationships' => array('fp_events_leads_1fp_events_ida','fp_events_leads_1leads_idb')),
													'fp_events_prospects_1' => array('label' => 'Relationship Event Prospect', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_prospects_1', 'fields' => array(), 'relationships' => array('fp_events_prospects_1fp_events_ida','fp_events_prospects_1prospects_idb'))
												);
	

	protected $customRelationship = 'MydCustRelSugar';
	
	
    public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
			$login_paramaters = array( 
			'user_auth' => array( 
				'user_name' => $this->paramConnexion['login'], 
				'password' => md5($this->paramConnexion['password']), 
				'version' => '.01' 
			), 
			'application_name' => 'myddleware',
			); 
			// remove index.php in the url
			$this->paramConnexion['url'] = str_replace('index.php', '', $this->paramConnexion['url']);
			// Add the suffix with rest parameters to the url
			$this->paramConnexion['url'] .= $this->urlSuffix;

			$result = $this->call('login',$login_paramaters,$this->paramConnexion['url']); 
			
			if($result != false) {
				if ( empty($result->id) ) {
				   throw new \Exception($result->description);
				}
				else {
					$this->session = $result->id;
					$this->connexion_valide = true;
				}				
			}
			else {
				throw new \Exception('Please check url');
			}
		} 
		catch (\Exception $e) {
			$error = $e->getMessage();
			$this->logger->error($error);
			return array('error' => $error);
		} 
    }
	
	public function logout() {	
		try {
			$logout_parameters = array("session" => $this->session);
			$this->call('logout',$logout_parameters,$this->paramConnexion['url']); 	
			return true;
		} 
		catch (\Exception $e) {
			$this->logger->error('Error logout REST '.$e->getMessage());
			return false;
		} 
    }
	
	public function getFieldsLogin() {	
		return array(
					array(
							'name' => 'login',
							'type' => TextType::class,
							'label' => 'solution.fields.login'
						),
					array(
							'name' => 'password',
							'type' => PasswordType::class,
							'label' => 'solution.fields.password'
						),
					array(
							'name' => 'url',
							'type' => TextType::class,
							'label' => 'solution.fields.url'
						)
		);
	}
	
	// Permet de récupérer tous les modules accessibles à l'utilisateur
	public function get_modules($type = 'source') {
	    try {
			$get_available_modules_parameters  = array( 
				'session' => $this->session
			);	
			$get_available_modules = $this->call('get_available_modules',$get_available_modules_parameters);
			if (!empty($get_available_modules->modules)) {
				foreach ($get_available_modules->modules as $module) {			
					// On ne renvoie que les modules autorisés
					if (
							!in_array($module->module_key,$this->exclude_module_list['default'])
						&&	!in_array($module->module_key,$this->exclude_module_list[$type])
					) {
						$modules[$module->module_key] = $module->module_label;
					}
				}
			}
			// Création des modules type relationship
			if (!empty($this->module_relationship_many_to_many)) {
				foreach ($this->module_relationship_many_to_many as $key => $value) {
					$modules[$key] = $value['label'];
				}
			}
			return ((isset($modules)) ? $modules : false );	    	
	    }
		catch (\Exception $e) {
			return false;
		}
	}
	
	// Permet de récupérer tous les champs d'un module
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try {
			$this->moduleFields = array();

			// Si le module est un module "fictif" relation créé pour Myddlewar	
			if(array_key_exists($module, $this->module_relationship_many_to_many)) {

				foreach ($this->module_relationship_many_to_many[$module]['fields'] as $name) {
					$this->moduleFields[$name] = array(
												'label' => $name,
												'type' => 'varchar(255)',
												'type_bdd' => 'varchar(255)',
												'required' => 0
											);						
				}
				foreach ($this->module_relationship_many_to_many[$module]['relationships'] as $relationship) {
					$this->fieldsRelate[$relationship] = array(
												'label' => $relationship,
												'type' => 'varchar(36)',
												'type_bdd' => 'varchar(36)',
												'required' => 0,
												'required_relationship' => 0
											);
				}
			}
			else {
				$get_module_fields_parameters  = array( 
					'session' 		=> $this->session,
					'module_name' 	=> $module
				);
				
				$get_module_fields = $this->call('get_module_fields',$get_module_fields_parameters);
				foreach ($get_module_fields->module_fields AS $field) {
					if(isset($this->exclude_field_list['default']) ){
						// Certains champs ne peuvent pas être modifiés
						if(in_array($field->name, $this->exclude_field_list['default']) && $type == 'target')
							continue; // Ces champs doivent être exclus de la liste des modules pour des raisons de structure de BD SuiteCRM
					}
		
					if (!in_array($field->type,$this->type_valide)) { 
						if(isset($this->exclude_field_list[$module])){
							if(in_array($field->name, $this->exclude_field_list[$module]) && $type == 'target')
								continue; // Ces champs doivent être exclus de la liste des modules pour des raisons de structure de BD SuiteCRM
						}
						$type_bdd = 'varchar(255)';
					}
					else {
						$type_bdd = $field->type;
					}
					if (
							substr($field->name,-3) == '_id' 
						|| substr($field->name,-4) == '_ida'
						|| substr($field->name,-4) == '_idb'
						|| (
								$field->type == 'id' 
							&& $field->name != 'id'
						)
						|| $field->name	== 'created_by'
					) {
						$this->fieldsRelate[$field->name] = array(
													'label' => $field->label,
													'type' => 'varchar(36)',
													'type_bdd' => 'varchar(36)',
													'required' => $field->required,
													'required_relationship' => 0
												);
					}
					//To enable to take out all fields where there are 'relate' in the type of the field
					else {	
						// Le champ id n'est envoyé qu'en source
						if($field->name != 'id' || $type == 'source') {
							$this->moduleFields[$field->name] = array(
													'label' => $field->label,
													'type' => $field->type,
													'type_bdd' => $type_bdd,
													'required' => $field->required
												);
						}   
						// Récupération des listes déroulantes (sauf si datetime pour SuiteCRM)
						if (
								!empty($field->options) 
							&& !in_array($field->type, array('datetime','bool')) 
						){
							foreach($field->options as $option) {
								$this->moduleFields[$field->name]['option'][$option->name] = $option->value;
							}
						}	
					}
				}
				
				// Ajout des champ type link (custom relationship ou custom module souvent)
				if (!empty($get_module_fields->link_fields)) {
					foreach ($get_module_fields->link_fields AS $field) {
						if(isset($this->exclude_field_list['default'])){
							if(in_array($field->name, $this->exclude_field_list['default']) && $type == 'target')
								continue; // Ces champs doivent être exclus en écriture de la liste des modules pour des raisons de structure de BD SuiteCRM
						}
						if (!in_array($field->type,$this->type_valide)) { 
							if(isset($this->exclude_field_list[$module])){
								if(in_array($field->name, $this->exclude_field_list[$module]) && $type == 'target')
									continue; // Ces champs doivent être exclus en écriture de la liste des modules pour des raisons de structure de BD SuiteCRM
							}
							$type_bdd = 'varchar(255)';
						}
						else {
							$type_bdd = $field->type;
						}
						if (
								substr($field->name,-3) == '_id' 
							|| substr($field->name,-4) == '_ida'
							|| substr($field->name,-4) == '_idb'
							|| (
									$field->type == 'id' 
								&& $field->name != 'id'
							)
						) {
							// On met un préfix pour les relation custom afin de pouvoir les détecter dans le read
							$this->fieldsRelate[$this->customRelationship.$field->name] = array(
														'label' => $field->relationship,
														'type' => 'varchar(36)',
														'type_bdd' => 'varchar(36)',
														'required' => 0,
														'required_relationship' => 0
													);
						}
					}
				}
			}
			// Ajout des champ relate au mapping des champs 
			if (!empty($this->fieldsRelate)) {
				$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
			}
			return $this->moduleFields;					
		}	
		catch (\Exception $e) {
			return false;
		}		
	}
	
	// Permet de récupérer le dernier enregistrement de la solution (utilisé pour tester le flux)
	public function read_last($param) {		
		// Si le module est un module "fictif" relation créé pour Myddlewar	alors on ne fait pas de readlast
		if(array_key_exists($param['module'], $this->module_relationship_many_to_many)) {
			$result['done'] = true;					
			return $result;
		}	
		// Build the query to read data 
		$query = $this->generateQuery($param, 'read_last');

		try {
			if(!isset($param['fields'])) {
				$param['fields'] = array();
			}
			// Ajout des champs obligatoires pour 
			$param['fields'] = $this->addRequiredField($param['fields']);
			$get_entry_list_parameters = array(
											'session' => $this->session,
											'module_name' => $param['module'],
											'query' => $query,
											'order_by' => "date_entered DESC",
											'offset' => '0',
											'select_fields' => $param['fields'],
											'link_name_to_fields_array' => '',
											'max_results' => '1',
											'deleted' => 0,
											'Favorites' => '',
										);										
			$get_entry_list_result = $this->call("get_entry_list", $get_entry_list_parameters);									
			// Si as d'erreur
			if (isset($get_entry_list_result->result_count)) {
				// Si pas de résultat
				if(!isset($get_entry_list_result->entry_list[0])) {
					$result['done'] = false;
				}
				else {
					foreach ($get_entry_list_result->entry_list[0]->name_value_list as $key => $value) {
						$result['values'][$key] = $value->value;
					}
					$result['done'] = true;
				}
			}	
			// Si erreur
			else {
				$result['error'] = $get_entry_list_result->number.' : '. $get_entry_list_result->name.'. '. $get_entry_list_result->description;
				$result['done'] = false;
			}												
			return $result;		
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;
			return $result;
		}	
	}
	
	// Permet de lire les données
	public function read($param) {		
		try {
			$result = array();
			$result['error'] = '';
			$result['count'] = 0;
			
			// Manage delete option to enable 
			$deleted = false;
			if (!empty($param['ruleParams']['deletion'])) {
				$deleted = true;
				$param['fields'][] = 'deleted';
			}
			
			if (empty($param['offset'])) {
				$param['offset'] = 0;
			}
			$currentCount = 0;		
			$query = '';
			if (empty($param['limit'])) {
				$param['limit'] = 100;
			}	
			// On va chercher le nom du champ pour la date de référence: Création ou Modification
			$DateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
			
			// Si le module est un module "fictif" relation créé pour Myddlewar	alors on récupère tous les enregistrements du module parent modifié
			if(array_key_exists($param['module'], $this->module_relationship_many_to_many)) {
				$paramSave = $param;
				$param['fields'] = array();
				$param['module'] = $this->module_relationship_many_to_many[$paramSave['module']]['module_name'];
			}

			// Ajout des champs obligatoires
			$param['fields'] = $this->addRequiredField($param['fields']);
			$param['fields'] = array_unique($param['fields']);			
			// Construction de la requête pour SugarCRM
			$query = $this->generateQuery($param, 'read');		
			//Pour tous les champs, si un correspond à une relation custom alors on change le tableau en entrée
			$link_name_to_fields_array = array();
			foreach ($param['fields'] as $field) {
				if (substr($field,0,strlen($this->customRelationship)) == $this->customRelationship) {
					$link_name_to_fields_array[] = array('name' => substr($field, strlen($this->customRelationship)), 'value' => array('id'));
				}
			}
			// On lit les données dans le CRM
            do {
				$get_entry_list_parameters = array(
												'session' => $this->session,
												'module_name' => $param['module'],
												'query' => $query,
												'order_by' => $DateRefField.' ASC',
												'offset' => $param['offset'],
												'select_fields' => $param['fields'],
												'link_name_to_fields_array' => $link_name_to_fields_array,
												'max_results' => $this->limitCall,
												'deleted' => $deleted,
												'Favorites' => '',
											);												
				$get_entry_list_result = $this->call("get_entry_list", $get_entry_list_parameters);									
				// Construction des données de sortie
				if (isset($get_entry_list_result->result_count)) {
					$currentCount = $get_entry_list_result->result_count;
					$result['count'] += $currentCount;
					$record = array();
					$i = 0;
					for ($i = 0; $i < $currentCount; $i++) {
						$entry = $get_entry_list_result->entry_list[$i]; 
						foreach ($entry->name_value_list as $value){
							$record[$value->name] = $value->value;
							if (
									$value->name == $DateRefField
								&&	(
										empty($result['date_ref'])
									|| (
											!empty($result['date_ref'])
										&&	$result['date_ref'] < $value->value
									)
								)
							) {
								$result['date_ref'] = $value->value;
							}
						}	
						// Manage deletion by adding the flag Myddleware_deletion to the record							
						if (
								$deleted == true
							AND !empty($entry->name_value_list->deleted->value)
						) {
							$record['myddleware_deletion'] = true;
						}
						
						// S'il y a des relation custom, on ajoute la relation custom 
						if (!empty($get_entry_list_result->relationship_list[$i]->link_list)) {
							foreach ($get_entry_list_result->relationship_list[$i]->link_list as $Relationship) {
								$record[$this->customRelationship.$Relationship->name] = $Relationship->records[0]->link_value->id->value;
							}
						}
						$result['values'][$entry->id] = $record;
						$record = array();
					}
					 // Préparation l'offset dans le cas où on fera un nouvel appel à Salesforce
                    $param['offset'] += $this->limitCall;
				}
				else {
					if (!empty($get_entry_list_result->number)) {
						$result['error'] = $get_entry_list_result->number.' : '. $get_entry_list_result->name.'. '. $get_entry_list_result->description;
					} else {
						$result['error'] = 'Failed to read data from SuiteCRM. No error return by SuiteCRM';
					}
				}			
			}
            // On continue si le nombre de résultat du dernier appel est égal à la limite
            while ($currentCount == $this->limitCall AND $result['count'] < $param['limit']-1); // -1 because a limit of 1000 = 1001 in the system				
			// Si on est sur un module relation, on récupère toutes les données liées à tous les module sparents modifiés
			if (!empty($paramSave)) {
				$resultRel = $this->readRelationship($paramSave,$result);
				// Récupération des données sauf de la date de référence qui dépend des enregistrements parent
				if(!empty($resultRel['count'])) {
					$result['count'] = $resultRel['count'];
					$result['values'] = $resultRel['values'];
				}
				// Si aucun résultat dans les relations on renvoie null, sinon un flux vide serait créé. 
				else {
					return null;
				}
			}	
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
		}
		return $result;	
	}

	// Build the direct link to the record (used in data transfer view)
	public function getDirectLink($rule, $document, $type){		
		// Get url, module and record ID depending on the type
		if ($type == 'source') {
			$url = $this->getConnectorParam($rule->getConnectorSource(), 'url');
			$module = $rule->getModuleSource();
			$recordId = $document->getSource();
		} else {
			$url = $this->getConnectorParam($rule->getConnectorTarget(), 'url');
			$module = $rule->getModuleTarget();
			$recordId = $document->gettarget();
		}	
		
		// Build the URL (delete if exists / to be sure to not have 2 / in a row) 
		return rtrim($url,'/').'/index.php?module='.$module.'&action=DetailView&record='.$recordId;
	}
	

	protected function readRelationship($param,$dataParent) {
		if (empty($param['limit'])) {
			$param['limit'] = 100;
		}	
		$result['error'] = '';
		$i = 0;
		// Pour toutes les données parents, on récupère toutes les données liées de la relation
		if (!empty($dataParent['values']))	 {
			$module_relationship_many_to_many = $this->module_relationship_many_to_many[$param['module']];

			foreach ($dataParent['values'] as $parent) {
				$get_relationships_parameters = array(
												 'session'=>$this->session,
												 'module_name' => $module_relationship_many_to_many['module_name'],
												 'module_id' => $parent['id'],
												 'link_field_name' => $module_relationship_many_to_many['link_field_name'],
												 'related_module_query' => '',
												 'related_fields' => array('id'),
												 'related_module_link_name_to_fields_array' => array(),
												 'deleted'=> '0',
												 'order_by' => '',
												 'offset' => 0,
												 'limit' => $param['limit'],
											);										
				$get_entry_list_result = $this->call("get_relationships", $get_relationships_parameters);

				if (!empty($get_entry_list_result)) {
					$record = array();
					foreach ($get_entry_list_result->entry_list as $entry){
						// R2cupération de l'id parent
						$record[$module_relationship_many_to_many['relationships'][0]] = $parent['id'];
						foreach ($entry->name_value_list as $value){
							if ($value->name == 'id') {
								$record[$module_relationship_many_to_many['relationships'][1]] = $value->value;
							}
							else {
								$record[$value->name] = $value->value;
							}
						}	
						// La date de référence de chaque relation est égale à la date de référence du parent
						$record['date_modified'] = $parent['date_modified'];
						// L'id de la relation est généré en concatenant les 2 id
						$record['id'] = $record[$module_relationship_many_to_many['relationships'][0]].$record[$module_relationship_many_to_many['relationships'][1]];
						$result['values'][$record['id']] = $record;
						$record = array();
						$i++;
					}
				}
				else {
					$result['error'] .= $get_entry_list_result->number.' : '. $get_entry_list_result->name.'. '. $get_entry_list_result->description.'       ';
				}
			}
		}
		$result['count'] = $i;
		return $result;
	}

	
	// Permet de créer des données
	public function create($param) {	
		// Si le module est un module "fictif" relation créé pour Myddlewar	alors on ne fait pas de readlast
		if(array_key_exists($param['module'], $this->module_relationship_many_to_many)) {
			return $this->createRelationship($param);
		}
	
		// Transformation du tableau d'entrée pour être compatible webservice Sugar
		foreach($param['data'] as $idDoc => $data) {
			try {
				// Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
				$dataSugar = array();
				foreach ($data as $key => $value) {
					if($key == 'Birthdate' && $value == '0000-00-00') {
						continue;
					}
					// Si un champ est une relation custom alors on enlève le prefix
					if (substr($key,0,strlen($this->customRelationship)) == $this->customRelationship) {
						$key = substr($key, strlen($this->customRelationship));
					}
					$dataSugar[] = array('name' => $key, 'value' => $value);
				}
				$setEntriesListParameters = array(
												'session' => $this->session,
												'module_name' => $param['module'],
												'name_value_lists' => $dataSugar
											);							
				$get_entry_list_result = $this->call("set_entry", $setEntriesListParameters);

				if (!empty($get_entry_list_result->id)) {
					$result[$idDoc] = array(
											'id' => $get_entry_list_result->id,
											'error' => false
									);
				}
				else  {
					throw new \Exception('error '.(!empty($get_entry_list_result->name) ? $get_entry_list_result->name : "").' : '.(!empty($get_entry_list_result->description) ? $get_entry_list_result->description : ""));
				}
			}
			catch (\Exception $e) {
				$error = $e->getMessage();
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}
		return $result;
	}
	
	// Permet de créer les relation many-to-many (considéré comme un module avec 2 relation 1-n dans Myddleware)
	protected function createRelationship($param) {
		foreach($param['data'] as $key => $data) {
			try {
				// Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
				$dataSugar = array();
				if (!empty($this->module_relationship_many_to_many[$param['module']]['fields'])) {
					foreach ($this->module_relationship_many_to_many[$param['module']]['fields'] as $field) {
						if (isset($data[$field])) {
							$dataSugar[] = array('name'=> $field, 'value' => $data[$field]);
						}
					}
				}
				$set_relationship_params = array( 
					'session' => $this->session,
					'module_name' => $this->module_relationship_many_to_many[$param['module']]['module_name'], 
					'module_id' => $data[$this->module_relationship_many_to_many[$param['module']]['relationships'][0]], 
					'link_field_name' => $this->module_relationship_many_to_many[$param['module']]['link_field_name'],
					'related_ids' =>array($data[$this->module_relationship_many_to_many[$param['module']]['relationships'][1]]),
					'name_value_list' => $dataSugar
				);				
				$set_relationship_result = $this->call("set_relationship", $set_relationship_params);
		
				if (!empty($set_relationship_result->created)) {
					$result[$key] = array(
											'id' => $key, // On met $key car onn a pas l'id de la relation
											'error' => false
									);
				}
				else  {
					$result[$key] = array(
											'id' => '-1',
											'error' => '01'
									);
				}
				
			}
			catch (\Exception $e) {
				$error = $e->getMessage();
				$result[$key] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($key,$result[$key],$param);	
		}
		return $result;
	}
	
	// Permet de mettre à jour un enregistrement
	public function update($param) {
		// Transformation du tableau d'entrée pour être compatible webservice Sugar
		foreach($param['data'] as $idDoc => $data) {
			try {	
				// Check control before update	
				$data = $this->checkDataBeforeUpdate($param, $data);
				$dataSugar = array();
				foreach ($data as $key => $value) {
					// Important de renommer le champ id pour que SuiteCRM puisse effectuer une modification et non une création
					if ($key == 'target_id') {
						$key = 'id';
					}
					// Si un champ est une relation custom alors on enlève le prefix
					if (substr($key,0,strlen($this->customRelationship)) == $this->customRelationship) {
						$key = substr($key, strlen($this->customRelationship));
					}
					
					if($key == 'Birthdate' && $value == '0000-00-00') {
						continue;
					}
					$dataSugar[] = array('name' => $key, 'value' => $value);
				}
				$setEntriesListParameters = array(
												'session' => $this->session,
												'module_name' => $param['module'],
												'name_value_lists' => $dataSugar
											);
	
				$get_entry_list_result = $this->call("set_entry", $setEntriesListParameters);
				if (!empty($get_entry_list_result->id)) {
					$result[$idDoc] = array(
											'id' => $get_entry_list_result->id,
											'error' => false
									);
				}
				else  {
					throw new \Exception('error '.(!empty($get_entry_list_result->name) ? $get_entry_list_result->name : "").' : '.(!empty($get_entry_list_result->description) ? $get_entry_list_result->description : ""));
				} 
			}
			catch (\Exception $e) {
				$error = $e->getMessage();
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}	
		return $result;			
	}
	
	// Function to delete a record
	public function delete($param) {
		// We set the flag deleted to 1 and we call the update function
		foreach($param['data'] as $idDoc => $data) {
			$param['data'][$idDoc]['deleted'] = 1;
		}	
		return $this->update($param);		
	}
		
	// Build the query for read data to SuiteCRM
	protected function generateQuery($param, $method){				
		$query = '';
		// if a specific query is requeted we don't use date_ref
		if (!empty($param['query'])) {
			foreach ($param['query'] as $key => $value) {
				if (!empty($query)) {
					$query .= ' AND ';
				}
				if ($key == 'email1') {
					$query .= strtolower($param['module']).".id in (SELECT eabr.bean_id FROM email_addr_bean_rel eabr JOIN email_addresses ea ON (ea.id = eabr.email_address_id) WHERE eabr.deleted=0 and ea.email_address LIKE '".$value."') ";
				}
				else {	
					// Pour ProspectLists le nom de la table et le nom de l'objet sont différents
					if($param['module'] == 'ProspectLists') {	
						$query .= "prospect_lists.".$key." = '".$value."' ";
					}
					else {
						$query .= strtolower($param['module']).".".$key." = '".$value."' ";
					}
				}
			}
		// Filter by date only for read method (no need for read_last method	
		} elseif ($method == 'read') {
			$DateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
			// Pour ProspectLists le nom de la table et le nom de l'objet sont différents
			if($param['module'] == 'ProspectLists') {	
				$query = "prospect_lists.". $DateRefField ." > '".$param['date_ref']."'";
			}
			else {
				$query = strtolower($param['module']).".". $DateRefField ." > '".$param['date_ref']."'";
			}
		}		
		return $query;
	}
	
	// Permet de renvoyer le mode de la règle en fonction du module target
	// Valeur par défaut "0"
	// Si la règle n'est qu'en création, pas en modicication alors le mode est C
	public function getRuleMode($module,$type) {
		if(
				$type == 'target'
			&&	array_key_exists($module, $this->module_relationship_many_to_many)
		) {
			return array(
				'C' => 'create_only'
			);
		}
		return parent::getRuleMode($module,$type);
	} 
	
	// Renvoie le nom du champ de la date de référence en fonction du module et du mode de la règle
	public function getDateRefName($moduleSource, $RuleMode) {
		if(in_array($RuleMode,array("0","S"))) {
			return "date_modified";
		} else if ($RuleMode == "C"){
			return "date_entered";
		} else {
			throw new \Exception ("$RuleMode is not a correct Rule mode.");
		}
		return null;
	}
	
		
	//function to make cURL request	
	protected function call($method, $parameters){
		try {
			ob_start();
			$curl_request = curl_init();
			curl_setopt($curl_request, CURLOPT_URL, $this->paramConnexion['url']);
			curl_setopt($curl_request, CURLOPT_POST, 1);	
			curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);	
			curl_setopt($curl_request, CURLOPT_HEADER, 1);
			curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);
		
			$jsonEncodedData = json_encode($parameters);
			$post = array(
				"method" => $method,
				"input_type" => "JSON",
				"response_type" => "JSON",
				"rest_data" => $jsonEncodedData
			);
		
			curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post);
			$result = curl_exec($curl_request);
			curl_close($curl_request);
			if(empty($result))	return false;
			$result = explode("\r\n\r\n", $result, 2);
			$response = json_decode($result[1]);
			ob_end_flush();
	
			return $response;			
		}
		catch(\Exception $e) {
			return false;	
		}	
    }	
	
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/suitecrm.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class suitecrm extends suitecrmcore {
		
	}
}