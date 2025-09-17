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

namespace App\Solutions;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class salesforce extends solution {

	protected int $limitCall = 500;

	protected array $required_fields =  array('default' => array('Id','LastModifiedDate', 'CreatedDate'));

	protected array $FieldsDuplicate = array(
										'Contact' => array('Email','LastName'),
										'Lead' => array('Email','LastName'),
										'Account' => array('Email', 'Name'),
										'default' => array('Name')
									  );

	protected $required_relationships = array(
												'default' => array(),
												'Contact' => array()
											);

	private $access_token;
	protected $instance_url;
	
	// Listes des modules et des champs à exclure de Salesforce
	protected array $exclude_module_list = array(
										'default' => 
											array("AccountFeed", "AccountShare", "ActivityHistory", "AggregateResult", "ApexClass", "ApexComponent", "ApexLog", "ApexPage","AccountHistory","CaseHistory", "ContactHistory","ContractHistory","LeadHistory", "OpportunityHistory",
											"ApexTestQueueItem", "ApexTestResult", "ApexTrigger", "AssetFeed", "AsyncApexJob", "BrandTemplate", "CampaignFeed", "CampaignShare", "CaseFeed", "CaseHistory","SolutionHistory",
											"CaseShare", "CaseTeamTemplate", "CaseTeamTemplateRecord", "CategoryNode", "ChatterActivity", "ClientBrowser", "CollaborationGroupFeed", "CollaborationGroupMemberRequest",
											"CollaborationInvitation", "ContactFeed", "ContactShare", "ContentDocumentFeed", "ContentDocumentHistory", "ContentDocumentLink", "ContentVersion", "ContentVersionHistory", "ContentWorkspaceDoc",
											"ContractFeed", "CronTrigger", "CustomConsoleComponent", "DashboardComponent", "DashboardComponentFeed", "DashboardFeed", "DocumentAttachmentMap",
											"DomainSite", "EntitySubscription", "EventFeed", "FeedComment", "FeedItem", "FeedLike", "FeedTrackedChange", "FieldPermissions", "FiscalYearSettings", "ForecastShare",
											"HashtagDefinition", "IdeaComment", "LeadFeed", "LeadShare", "LoginHistory", "LoginIp", "ObjectPermissions", "OpenActivity", "OpportunityFeed",
											"OpportunityFieldHistory", "OpportunityShare", "PermissionSet", "PermissionSetAssignment", "Pricebook2History",
											"ProcessInstanceHistory", "ProcessInstanceStep", "ProcessInstanceWorkitem", "Product2Feed", "QueueSobject", "ReportFeed", "SetupEntityAccess", "SiteFeed", "SolutionFeed",
											"TaskFeed", "UserFeed", "UserLicense", "UserPreference", "UserProfileFeed", "UserRecordAccess", "UserRole", "UserShare", "Vote"),
										'source' => array(),
										'target' => array()
										);
										
	protected array $exclude_field_list = array(
										'default' => array('CreatedDate','LastModifiedDate','SystemModstamp'),
										"Contact" => array("Name"), 
										"Case" => array("CaseNumber")
									);
									
	protected string $versionApi = 'v62.0';
	
	protected bool $sendDeletion = true;

	// Connexion à Salesforce - Instancie la classe salesforce et affecte access_token et instance_url
    public function login($paramConnexion) {
		parent::login($paramConnexion);	
		try {
			if (
					!empty($this->paramConnexion['sandbox'])
				&&	$this->paramConnexion['sandbox'] == 1
			) {
				$token_url = 'https://test.salesforce.com/services/oauth2/token';
			}
			else {
				$token_url = 'https://login.salesforce.com/services/oauth2/token';
			}
			
		    $post_fields = array(
		        'grant_type' => 'password',
		        'client_id' => $this->paramConnexion['consumerkey'],
		        'client_secret' => $this->paramConnexion['consumersecret'],
		        'username' => $this->paramConnexion['login'],
		        'password' => $this->paramConnexion['password'].$this->paramConnexion['token']
		    );

 			$token_request_data = $this->call($token_url, $post_fields);

		    if (!isset($token_request_data['access_token'])||
		        !isset($token_request_data['instance_url'])){
				throw new \Exception("Missing expected data from ".print_r($token_request_data, true));
		    } else {
			    $this->access_token = $token_request_data['access_token'];
			    $this->instance_url = $token_request_data['instance_url'];
				$this->connexion_valide = true;
		    }
		}
		catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
		}
	}
	
	// Fonction qui renvoie les données de connexion
	public function getToken(): array
    {
		return array('sf_access_token' => $this->access_token,
					 'sf_instance_url' => $this->instance_url);
	}

	// Liste des paramètres de connexion
	public function getFieldsLogin(): array
    {
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
                            'name' => 'consumerkey',
                            'type' => PasswordType::class,
                            'label' => 'solution.fields.consumerkey'
                        ),
                    array(
                            'name' => 'consumersecret',
                            'type' => PasswordType::class,
                            'label' => 'solution.fields.consumersecret'
                        ),
                    array(
                            'name' => 'token',
                            'type' => PasswordType::class,
                            'label' => 'solution.fields.token'
                        ),
                    array(
                            'name' => 'sandbox',
                            'type' => TextType::class,
                            'label' => 'solution.fields.sandbox'
                        )
        );
	}

	// Renvoie les modules disponibles du compte Salesforce connecté
	public function get_modules($type = 'source') {
		$token = $this->getToken();
		$instance_url = $token['sf_instance_url'];
		// Accès au service de SalesForce renvoyant la liste des objets disponibles propres à l'organisation
		$query_url = $instance_url.'/services/data/'.$this->versionApi.'/sobjects/';
		try{
			$query_request_data = $this->call($query_url, false);

			foreach ($query_request_data['sobjects'] as $object) {
				// On ne renvoie que les modules autorisés
				if (
						!in_array($object['name'],$this->exclude_module_list['default'])
					&&	!in_array($object['name'],$this->exclude_module_list[$type])
				) {
					if($object['label'] == 'Groupe'){ // A travailler
						$modules[$object['name']] = $object['label'].' ('.$object['name'].')';
					} else {
						$modules[$object['name']] = $object['label'];						
					}
				}
			}
			return ((isset($modules)) ? $modules : false );
		}
		catch (\Exception $e){
			$error = $e->getMessage();
			$this->logger->error($error);
			return $error;
		}
	}

	// Renvoie les champs du module passé en paramètre
	public function get_module_fields($module, $type = 'source', $param = null): array
    {
		parent::get_module_fields($module, $type);
		$token = $this->getToken();
		$instance_url = $token['sf_instance_url'];
		// Accès au service de SalesForce renvoyant la liste des champs du module passé en paramètre
		$query_url = $instance_url.'/services/data/'.$this->versionApi.'/sobjects/' . $module . '/describe/';
		try {
			$query_request_data = $this->call($query_url, false);		
            // Ces champs ne doivent pas apparaître comme requis
            $calculateFields = array("NumberOfLeads", "NumberOfConvertedLeads", "NumberOfContacts","NumberOfResponses","NumberOfOpportunities","NumberOfWonOpportunities","AmountAllOpportunities","AmountWonOpportunities","ForecastCategory");
            
            foreach ($query_request_data['fields'] AS $field) {
				// One garde pas les champs calculés lorsque l'on est en cible qui ne doivent jamais être renseignés
				if(in_array($field['name'],$calculateFields) && $type == 'target') {
					continue;
				}
				if(isset($this->exclude_field_list['default'])){
					if(in_array($field['name'], $this->exclude_field_list['default']) && $type == 'target')
						continue; // Ces champs doivent être exclus en écriture de la liste des modules pour des raisons de structure de BD Salesforce
				}
            	if(isset($this->exclude_field_list[$module])) {
	            	if(in_array($field['name'], $this->exclude_field_list[$module]) && $type == 'target') {
	            		continue; // Ces champs doivent être exclus en écriture de la liste des modules pour des raisons de structure de BD Salesforce
					}
				}
                if ($field['type'] == 'picklist'){
                    // Ne rien faire
                }
                else if ($field['type'] == "boolean"){
                    $type_bdd = 'bool';
                }
                else if (!in_array($field['type'],$this->type_valide)) { 
                    $type_bdd = 'varchar(255)';
                }
                else {
                    $type_bdd = $field['type'];
                }

                if ($field['type'] == 'reference') {
                     $this->moduleFields[$field['name']] = array(
                                                'label' => $field['label'],
                                                'type' => $field['type'],
                                                'type_bdd' => $type_bdd,
                                                'required' => !$field['nillable'],
												'required_relationship' => 0,
												'relate' => true
                                            );
                }
                else {
					// Le champ id n'est envoyé qu'en source
                    if($field['type'] != 'id' || $type == 'source') {
						// Required n'exite pas dans l'API REST,
						// seul nillable existe (à true si la variable peut prendre des valeurs NULL)
						// Les champs avec une valeur par défaut ne sont pas requis
						$required = false;
						if (
								empty($field['nillable'])
							&& empty($field['defaultedOnCreate'])
						) {
							$required = true;
						}
						$this->moduleFields[$field['name']] = array(
												'label' => $field['label'],
												'type' => $field['type'],
												'type_bdd' => $type_bdd,
												'required' => $required,
												'relate' => false
											);
						if(strpos($field['name'], "__c")) // Si le champs est un champs custom, il n'est pas requis par défaut
							$this->moduleFields[$field['name']]['required'] = false;
                    } 
					else {
						// Ajout du champ ID permettant de gérer les relations
						$this->moduleFields['Myddleware_element_id'] = array(
                                                'label' => $field['label'],
                                                'type' => $field['type'],
                                                'type_bdd' => $type_bdd,
                                                'required' => !$field['nillable'],
												'required_relationship' => 0,
												'relate' => true
                                            );
					}
                    // Récupération des listes déroulantes
                    if ($field['type']=='picklist') {
                        foreach($field['picklistValues'] as $option) {
                            $this->moduleFields[$field['name']]['option'][$option['value']] = parent::truncate($option['label'], 80);
                        }
                         $this->moduleFields[$field['name']]['type_bdd'] = 'varchar(255)';
                    }   
                }           
            }
			return $this->moduleFields;
		}
		catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
		}
	}

	// Permet d'ajouter des paramètres 
	public function getFieldsParamUpd($type,$module): array
    {
		try {
			// Si le module est PricebookEntry (produit dans le catalogue) alors il faut indiquer le catalogue de produit utilisé
			if (
						$type == 'target'
					&& $module == 'PricebookEntry'
			){
				// Récupération des catalogue Salesforce
				$param = array(
					'date_ref' => '2000-01-01 00:00:00',
					'module' => 'Pricebook2',
					'fields' => array('Id', 'Name'),
					'rule' => array('mode' => 'C'),
					'limit' => 100
				);
				$priceBook2SF = $this->read($param);				
				if (!empty($priceBook2SF)) {
					// Création du champ
					$pricebook2Param = array(
								'id' => 'Pricebook2Id',
								'name' => 'Pricebook2Id',
								'type' => 'option',
								'label' => 'Price book',
								'required'	=> false
							);
					$pricebook2Param['option'][''] = '';	
					foreach ($priceBook2SF['values'] as $priceBook2SFVal) {
						$pricebook2Param['option'][$priceBook2SFVal['id']] = $priceBook2SFVal['Name'];
					}
					$params[] = $pricebook2Param;
				}
				return $params;
			}
			return array();
		}
		catch (\Exception $e){
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
		}
	}
	
	
	// Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
	public function readData($param): array
	{
		$result = array();
		$result['error'] = '';
		$result['count'] = 0;
		$currentCount = 0;
		$querySelect = '';
		$queryFrom = '';
		$queryWhere = '';
		$queryOrder = '';
		$queryLimit = '';
		$queryOffset = '';
		if (empty($param['limit'])) {
			$param['limit'] = $this->limitCall;
		}
		if (!isset($param['offset'])) {
			$param['offset'] = 0;
		}

		try {
			$param['fields'] = array_unique($param['fields']);
			$param['fields'] = array_values($param['fields']);
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);

			// Ajout des champs obligatoires
			$param['fields'] = $this->addRequiredField($param['fields'], $param['module'], $param['ruleParams']['mode']);

			// Récupération du nom du champ date
			$DateRefField = $this->getRefFieldName($param);

			// Construction de la requête pour Salesforce
			$baseQuery = $this->instance_url."/services/data/".$this->versionApi."/query/?q=";

			// Gestion du SELECT
			$querySelect = $this->getSelect($param);
			// Gestion de FROM
			$queryFrom = $this->getFrom($param);
			// Gestion du WHERE
			$queryWhere = $this->getWhere($param);
			// Gestion du ORDER
			$queryOrder = $this->getOrder($param);

			// Gstion du LIMIT
			$queryLimit .= "+LIMIT+" . $param['limit']; // Ajout de la limite souhaitée
			// On lit les données dans Salesforce
			do {
				if(!empty($param['offset'])) {
					$queryOffset = "+OFFSET+".$param['offset'];
				}
				// Appel de la requête
				if (!empty($query_request_data['nextRecordsUrl'])) {
					$query = $this->instance_url.$query_request_data['nextRecordsUrl'];
				} else {
					$query = $baseQuery.$querySelect.$queryFrom.$queryWhere.$queryOrder.$queryLimit.$queryOffset;
				}
				$query_request_data = $this->call($query, false);
				$query_request_data = $this->formatResponse($param,$query_request_data);

				if (!empty($query_request_data['records'])){
					$i = 0;
					// Traitement des informations reçues
					foreach($query_request_data['records'] as $record){
						$record = $query_request_data['records'][$i];
						foreach (array_keys($record) as $key) {
							if($key == $DateRefField){
								$record[$key] = $this->dateTimeToMyddleware($record[$key]);
								$row['date_modified'] = $record[$key];
							}
							// Manage relationship fields stored in a sub array
							elseif(
									$key != 'attributes'
								AND	is_array($record[$key])
							) {
								foreach($record[$key] as $fieldKey => $fieldValue) {
									// Don't save attributes
									if($fieldKey != 'attributes'){
										// In case there are 2 levels of relationship (think about a recursive function here) 
										if(is_array($fieldValue)) {
											if (!empty($fieldValue)) {
												foreach($fieldValue as $fieldKeyLevel2 => $fieldValueLevel2) {
													if($fieldKeyLevel2 != 'attributes'){
														// In case there are 3 levels of relationship (think about a recursive function here) 
														if(is_array($fieldValueLevel2)) {
															if(!empty($fieldValueLevel2)) {
																foreach($fieldValueLevel2 as $fieldKeyLevel3 => $fieldValueLevel3) {
																	if($fieldKeyLevel3 != 'attributes'){
																		$row[mb_strtolower($fieldKeyLevel3)] = $fieldValueLevel3;
																		$row[$param['module'].'.'.$key.'.'.$fieldKey.'.'.$fieldKeyLevel2.'.'.$fieldKeyLevel3] = $fieldValueLevel3;
																	}
																}
															// If a relationship is empty, we set all field under this relationship to empty
															} else {
																foreach($param['fields'] as $field) {
																	if (str_starts_with($field, $param['module'].'.'.$key.'.'.$fieldKey.'.'.$fieldKeyLevel2)) {
																		$row[$field] = '';
																	}
																}
															}
														}
														else {
															$row[$param['module'].'.'.$key.'.'.$fieldKey.'.'.$fieldKeyLevel2] = $fieldValueLevel2;
														}
													}
												}
											// If a relationship is empty, we set all field under this relationship to empty
											} else {
												foreach($param['fields'] as $field) {
													if (str_starts_with($field, $param['module'].'.'.$key.'.'.$fieldKey)) {
														$row[$field] = '';
													}
												}
											}
										}
										else {
											$row[$param['module'].'.'.$key.'.'.$fieldKey] = $fieldValue;
										}
									}
								}
							}
							// On enlève le tableau "attributes" ajouté par Salesforce afin d'extraire les éléments souhaités
							elseif($key != 'attributes'){
								if($key == 'Id')
									$row[mb_strtolower($key)] = $record[$key];
									// If Id is requested in the field mapping
									if (!empty($param['fields']['Id'])) {
										$row[$key] = $record[$key];
									}
								else {
									if($key == 'CreatedDate') {
										$record[$key] = $this->dateTimeToMyddleware($record[$key]);
									}
									$row[$key] = $record[$key];
								}
							}
							if($key == "MailingAddress") {
								if(!empty($record[$key])) {
									$MailinAddress = "";
									foreach ($record[$key] as $elem => $elemvalue) {
										if(!empty($elemvalue))
											$MailinAddress .= $elem .": " . $elemvalue ." ";
									}
									$MailinAddress = rtrim($MailinAddress,' ');
									$row[$key] = $MailinAddress;
								}
							}
						}
						$result['date_ref'] = $record[$DateRefField];
						$result['values'][$record['Id']] = $row;
						$result['count']++;
						$i++;
						$row = array();
					}
					// Préparation de l'offset dans le cas où on ferait un nouvel appel à Salesforce
					$param['offset'] += $i;
				}
			}
			// On continue si :
			// 1.   Le nombre de résultat du dernier appel est égal à la limite
			// 2.   Et si la date de référence de l’enregistrement précédent est égale à la date de référence du tout dernier enregistrement
			while (
						$result['count'] < $param['limit']
					&& !$query_request_data['done']
			);
		}
		catch (\Exception $e) {
			$error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error($error);
			$result['error'] = $error;
		}
		return $result;
	}

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Exception
     */
    public function createData($param): array
    {
		$query_url = $this->instance_url."/services/data/".$this->versionApi."/composite/tree/" . $param['module'] . '/';

		if(!(isset($param['data']))) {
			throw new \Exception ('Data missing for create');
		}
		// Get the type of each fields by calling Salesforce
		$moduleFields = $this->get_module_fields($param['module'],'target');
		try{
			$parameters = array();
			$i=0;
			$nb_record = count($param['data']);			
			foreach($param['data'] as $idDoc => $data) {
				// Check control before create
				$data = $this->checkDataBeforeCreate($param, $data, $idDoc);
				// Generate a reference and store it in an array
				$i++;	
				$idDocReference['Ref'.$i] = $idDoc;
				$parameter = array();
				$parameter['attributes'] = array('type' => $param['module'], 'referenceId' => 'Ref'.$i);
			    foreach ($data as $key => $value) {		
			        if ($key == 'target_id') {
			            continue;
			        }
					elseif($key == 'Birthdate') {
						if($value == '0000-00-00' || empty($value)) {
							continue;
						}
						if(!date_create_from_format('Y-m-d', $value)) {
							throw new \Exception ("Birthdate format is not compatible with Salesforce.");
						}
						$parameter[$key] = $value;
					}
					// Gestion des champs de type booleen
					elseif ($moduleFields[$key]['type'] == 'boolean') {
						if (!empty($value)) {
							$parameter[$key] = true;
						}
						else{
							$parameter[$key] = false;
						} 
					}
					else {
						$parameter[$key] = $value;
					}
			    }
				// Si le module est PricebookEntry alors il faut ajouter le catalogue qui est dans les paramètre
				if (
						$param['module'] == 'PricebookEntry'
					&& !empty($param['ruleParams']['Pricebook2Id'])
				) {
					$parameter['Pricebook2Id'] = $param['ruleParams']['Pricebook2Id'];
				}			
				$parameters['records'][] = $parameter;
				
				// If we have finished to read all data or if the package is full we send the data to Sallesforce
				if (
						$nb_record == $i
					 || $i % $this->limitCall  == 0
				) {			
					$parameters = json_encode($parameters);
					// Call to Salesforce
					$query_request_data = $this->call($query_url, $parameters);					
					if (!empty($query_request_data['results'])) {
						foreach ($query_request_data['results'] as $result_record) {	
							// Check that we have the document id
							if (empty($idDocReference[$result_record['referenceId']])) {
								throw new \Exception ('Failed to get the id doc with the reference id '.$result_record['referenceId'].'. WARNING : the record is probably already created in Saleforce. Check it before restart this data transfer to avoid duplicate data. ');
							}
							// Detection of the error with the field hasErrors
							if (!empty($query_request_data['hasErrors'])) {
								$result[$idDocReference[$result_record['referenceId']]] = array(
														'id' => -1,
														'error' => print_r($result_record['errors'],true)
													);
							// No error
							} elseif (!empty($result_record['id'])) {
								// Generate the result. Detection of the error with the field hasErrors
								$result[$idDocReference[$result_record['referenceId']]] = array(
														'id' => $result_record['id'],
														'error' => false
													);
							}
							else  {
								throw new \Exception ('No id found in the response of Salesforce call : '.print_r($result_record,true));
							}
							// Modification du statut du flux
							$this->updateDocumentStatus($idDocReference[$result_record['referenceId']],$result[$idDocReference[$result_record['referenceId']]],$param);	
						}
					}
					$query_request_data = array();
					$parameters = array();
				}
			}			
		} catch (\Exception $e) {
			// Global error
			$error = 'An error happens in mass creation process. One of the record in the task '.$param['jobId'].' has failed. ALl records of this task have been rejected. Detail of the error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$result['error'] = $error;
			// Erro on each data transfer
			$error = 'An error happens in mass creation process. One of the record in the task '.$param['jobId'].' has failed. ALl records of this task have been rejected. Please open the task to get more detail.';
			// Set status for all data transfer when an error happens in a mass action
			foreach($param['data'] as $idDoc => $data) {
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
				// Change status for all records
				$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
			}
		}					
		return $result;
	}

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Exception
     */
    public function updateData($param): array
    {
		if(!(isset($param['data']))) {
			throw new \Exception ('Data missing for update');
		}
		// Get the type of each fields by calling Salesforce
		$moduleFields = $this->get_module_fields($param['module'],'target');		
		foreach($param['data'] as $idDoc => $data) {
			try{
				// Check control before update
				$data = $this->checkDataBeforeUpdate($param, $data, $idDoc);
				$parameters = array();
				// Instanciation de l'URL d'appel				
				$query_url = $this->instance_url."/services/data/".$this->versionApi."/sobjects/" . $param['module'] . '/';
			    foreach ($data as $key => $value) {
					if ($key == 'target_id') {
			        	$target_id = $value;
						// Ajout de l'ID à l'URL pour la modification
            			$query_url .= $value . '/';				
			            continue;
			        }
					elseif($key == 'Birthdate') {		
						if($value == '0000-00-00' || empty($value)) {
							continue;
						}
						if(!date_create_from_format('Y-m-d', $value)) {
							throw new \Exception ("Birthdate format is not compatible with Salesforce.");
						}
						$parameters[$key] = $value;
					}
					// Gestion des champs de type booleen				
					elseif ($moduleFields[$key]['type'] == 'boolean') {
						if (!empty($value)) {
							$parameters[$key] = true;						
						}
						else{
							$parameters[$key] = false;							
						} 
					}
					else {
						$parameters[$key] = $value;
					}
			    }

				// Si le module est PricebookEntry alors il faut ajouter le catalogue qui est dans les paramètre
				if (
						$param['module'] == 'PricebookEntry'
					&& !empty($param['ruleParams']['Pricebook2Id'])
				) {
					$parameters['Pricebook2Id'] = $param['ruleParams']['Pricebook2Id'];
				}				
				if (empty($target_id)) {
					throw new \Exception ('The target id is requiered for an update.');
				}
				$parameters = json_encode($parameters);
				// Appel de la requête				
                $query_request_data = $this->call($query_url, $parameters, 'PATCH');             				
				
				if ($query_request_data === true) {
					$result[$idDoc] = array(
											'id' => $target_id,
											'error' => false
									);
				}
				else  {
					$result[$idDoc] = array(
											'id' => '-1',
											'error' => 'Failed to update Data in Salesforce : '.print_r($query_request_data['errors'],true),
									);
				}
			}
			catch (\Exception $e) {
				$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
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

	/**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Exception
     */
    public function deleteData($param): array
    {
		if(!(isset($param['data']))) {
			throw new \Exception ('Data missing for update');
		}
		foreach($param['data'] as $idDoc => $data) {
			try{
				// Check control before update
				$data = $this->checkDataBeforeDelete($param, $data, $idDoc);
				// Instanciation de l'URL d'appel	
				if (empty($data['target_id'])) {
					throw new \Exception ('The target id is requiered for a deletion.');
				}			
				$query_url = $this->instance_url."/services/data/".$this->versionApi."/sobjects/".$param['module'].'/'.$data['target_id'];
			    
				// Appel de la requête				
                $query_request_data = $this->call($query_url, true, 'DELETE');             				
				
				if ($query_request_data === true) {
					$result[$idDoc] = array(
											'id' => $data['target_id'],
											'error' => false
									);
				}
				else  {
					$result[$idDoc] = array(
											'id' => '-1',
											'error' => 'Failed to update Data in Salesforce : '.print_r($query_request_data['errors'],true),
									);
				}
			}
			catch (\Exception $e) {
				$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
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

	// Permet de formater la réponse si besoin
	protected function formatResponse($param,$query_request_data) {
		return $query_request_data;
	}
	
	// Génération du SELECT
	protected function getSelect($param): string
    {
		$querySelect = "SELECT+";
		// Gestion du SELECT
		foreach ($param['fields'] as $field){
			$querySelect .= $field . ",+"; // Ajout de chaque champ souhaité
		}
		// Suppression de la dernière virgule en laissant le +
		$querySelect = rtrim($querySelect,'+'); 
		$querySelect = rtrim($querySelect,','); 
		return $querySelect;
	}
	
	// Génération du FROM
	protected function getFrom($param): string
    {
		return "+FROM+".$param['module'];
	}

    /**
     * @throws \Exception
     */
    protected function getWhere($param): string
    {
		if (!empty($param['query'])) {
			$queryWhere = "+WHERE+";
			foreach ($param['query'] as $key => $value) {
				// Manage boolean search
				if (in_array($value, array('TRUE', 'FALSE'))) {
					$queryWhere .= $key."+=+".$value;
				} else {
					$queryWhere .= $key."+=+'".str_replace(' ', '+', addslashes($value))."'";
				}
				// Add the AND if not the last entry of the array
				if ($key !== array_key_last($param['query'])) {
						$queryWhere .= "+AND+";
				}
			}
		} else {
			// On va chercher le nom du champ pour la date de référence: Création ou Modification
			$DateRefField = $this->getRefFieldName($param);

			// Mis en forme de la date de référence pour qu'elle corresponde aux exigeances du service Salesforce
			$tab = explode(' ', $param['date_ref']);
			$date = $tab[0] . 'T' . $tab[1];

			// Encodage de la date
			$startDateAndTime = urlencode($date . '+00:00');
			if($DateRefField == 'LastModifiedDate') {
				$queryWhere = "+WHERE+LastModifiedDate+>+" . $startDateAndTime;
			} else {
				$queryWhere = "+WHERE+CreatedDate+>+" . $startDateAndTime;
			}
		
			// Si le module est CampaignMember alors on ne récupère que les membre compatible avec la règle : piste ou contact
			if ($param['module'] == 'CampaignMember') {
				if (array_search('ContactId',$param['fields']) !== false){
					$queryWhere .= "+AND+ContactId+!=+''";
				}
				else {
					$queryWhere .= "+AND+LeadId+!=+''";
				}
			}
		}
		return $queryWhere;
	}

    /**
     * @throws \Exception
     */
    protected function getOrder($param): string
    {
		$DateRefField = $this->getRefFieldName($param);
		if($DateRefField == 'LastModifiedDate') {
			$queryOrder = "+ORDER+BY+LastModifiedDate"; // Ajout du module souhaité
		} else {
			$queryOrder = "+ORDER+BY+CreatedDate"; // Ajout du module souhaité
		}
		return $queryOrder;
	}
	
	// Permet de renvoyer le mode de la règle en fonction du module target
	// Valeur par défaut "0"
	// Si la règle n'est qu'en création, pas en modicication alors le mode est C
	public function getRuleMode($module,$type): array
    {
		if(
			$type == 'target'
			&& in_array($module, array('GroupMember','CollaborationGroupMember','CaseTeamMember','CaseTeamTemplateMember'))
		) {
			return array(
				'C' => 'create_only'
			);
		}
		return parent::getRuleMode($module,$type);
	}
	
	// Renvoie le nom du champ de la date de référence en fonction du module et du mode de la règle

    /**
     * @throws \Exception
     */
    public function getRefFieldName($param): string
    {
		if(in_array($param['ruleParams']['mode'],array("0","S","U"))) {
			return "LastModifiedDate";
		} else if ($param['ruleParams']['mode'] == "C"){
			return "CreatedDate";
		} else {
			throw new \Exception ($param['ruleParams']['mode']." is not a correct Rule mode.");
		}
		return "";
	}

    /**
     * @throws \Exception
     */
    protected function call($url, $parameters, $method = null){
		ob_start();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // important (testé en Local wamp) afin de ne pas vérifier le certificat SSL
		if($parameters === false){ // Si l'appel ne possède pas de paramètres, on exécute un GET en curl
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth '.$this->access_token));
		} else {
			// No Authorization in case of login action
			if(!isset($parameters['grant_type'])) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: OAuth " . $this->access_token, "Content-type: application/json"));
			}
			// PATCH or DELETE
			if (!empty($method)) { 
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			// POST
			} else {	
				curl_setopt($ch, CURLOPT_POST, TRUE);
			}
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		}
		
		$query_request_body = curl_exec($ch);	 
		// Si on est sur un update et que l'on a un retour 204 on renvoie true
		if (!empty($method)) {		
			if(curl_getinfo($ch, CURLINFO_HTTP_CODE) == '204') {
				return true;
			}
		}
		
		if(curl_error($ch)) throw new \Exception("Call failed: " . curl_error($ch));
		
	    $query_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (($query_response_code<200)||($query_response_code>=300)||empty($query_request_body)){
             $query_request_data = json_decode($query_request_body, true);	
			if(
					!empty($query_request_data['hasErrors'])
				 &&	$query_request_data['hasErrors'] == true
			) {
				return $query_request_data;
			} elseif(isset($query_request_data['error_description'])) {
            	throw new \Exception(ucfirst($query_request_data['error_description']));
			} else {
				throw new \Exception("Call failed - " . $query_response_code . ' ' . $query_request_body);
			}
        }

	    $query_request_data = json_decode($query_request_body, true);
	    if (empty($query_request_data))
	        throw new \Exception("Couldn't decode '$query_request_data' as a JSON object");

		curl_close($ch);
		ob_end_flush();
		return $query_request_data;	
    }
	
	// Function de conversion de datetime format solution à un datetime format Myddleware
	protected function dateTimeToMyddleware($dateTime) {
		$tab = explode('T', $dateTime);
		$dateTime = $tab[0] . ' ' . $tab[1];
		$tab = explode('.', $dateTime);
		$dateTime = $tab[0];
		return $dateTime;
	}
	
	// Function de conversion de datetime format Myddleware à un datetime format solution
	protected function dateTimeFromMyddleware($dateTime): string
    {
		$tab = explode(' ', $dateTime);
		$date = $tab[0] . 'T' . $tab[1];
		$date .= '+00:00';
		return $date;
	}
    
}
