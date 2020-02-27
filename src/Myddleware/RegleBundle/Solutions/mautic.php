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

use Mautic\MauticApi;
use Mautic\Auth\ApiAuth;  

class mauticcore  extends solution {

	protected $auth;
	
	protected $plurialModuleName = array(
											'contact' => 'contacts',
											'company' => 'companies'
										);
	protected $required_fields = array(
										'default' => array('id', 'dateModified', 'dateAdded'),
										'company' => array('id'),
									);
	
	// Enable to read deletion and to delete data
	protected $sendDeletion = true;	
	
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

	
    public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
			// Add login/password
			$settings = array(
				'userName'   => $this->paramConnexion['login'],
				'password'   => $this->paramConnexion['password']
			);

			// Ini api
			$initAuth = new ApiAuth();
			$auth = $initAuth->newAuth($settings, 'BasicAuth');
			$api = new MauticApi();
			
			// Get the current user to check the connection parameters
			$userApi = $api->newApi("users", $auth, $this->paramConnexion['url']);
			$user = $userApi->getSelf();

			// Managed API return. The API call is OK if the user id is found
			if(!empty($user['id'])) {
				$this->auth = $auth;
				$this->connexion_valide = true;	
			} elseif(!empty($user['error']['message'])) {
				throw new \Exception('Failed to login to Mautic. Code '.$user['error']['code'].' : '.$user['error']['message']);
			} else {
				throw new \Exception('Failed to login to Mautic. No error message returned by the API.');
			} 
		} 
		catch (\Exception $e) {
			$error = $e->getMessage();
			$this->logger->error($error);
			return array('error' => $error);
		} 
    }
	
	// Get the modules available 
	public function get_modules($type = 'source') {
		return array(
						'contact' => 'Contacts',
						'company' => 'Companies',
						'companies__contact' => 'Add contact to company'
					);
	}
	
	// Get the fields available 
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			if ($module == 'companies__contact') {
				$fieldlist['fields'][] = array('alias' => 'contact', 'label' => 'Contact', 'type' => 'relate', 'isRequired' => true);
				$fieldlist['fields'][] = array('alias' => 'company', 'label' => 'Company', 'type' => 'relate', 'isRequired' => true);
			} else {
				// Call Mautic to get the module fields
				$api = new MauticApi();
				$fieldApi = $api->newApi($module."Fields", $this->auth, $this->paramConnexion['url']);
				$fieldlist = $fieldApi->getList();
			}
			
			// Transform fields to Myddleware format
			if (!empty($fieldlist['fields'])) {
				foreach ($fieldlist['fields'] as $field) {
					if ($field['type'] == 'relate') {
						$this->fieldsRelate[$field['alias']] = array(
								'label' => $field['label'],
								'type' => 'varchar(255)',
								'type_bdd' => 'varchar(255)',
								'required' => '',
								'required_relationship' => (!empty($field['isRequired']) ? true : false)
							);
					} else {
						$this->moduleFields[$field['alias']] = array(
														'label' => $field['label'],
														'type' => ($field['type'] == 'text' ? TextType::class : 'varchar(255)'),
														'type_bdd' => ($field['type'] == 'text' ? $field['type'] : 'varchar(255)'),
														'required' => (!empty($field['isRequired']) ? true : false),
													);
						// manage dropdown lists
						if (!empty($field['properties']['list'])) {
							$options = explode('|', $field['properties']['list']);
							foreach ($options as $option) {
								$this->moduleFields[$field['alias']]['option'][$option] = $option;
							}
						}
					}
				}
			}
			
			// Add relate field in the field mapping
			if (!empty($this->fieldsRelate)) {
				$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
			}				
			return $this->moduleFields;
		} catch (\Exception $e){
			return false;
		}
	} // get_module_fields($module)	 

	
	// Permet de récupérer le dernier enregistrement de la solution (utilisé pour tester le flux)
	public function read_last($param) {	
		try {		
			// Create API object depending on the module
			$api = new MauticApi();
			$moduleName = (!empty($this->plurialModuleName[$param['module']]) ? $this->plurialModuleName[$param['module']] : $param['module']);
			$moduleApi = $api->newApi($moduleName , $this->auth, $this->paramConnexion['url']);
			
			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields']);
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			
			$result = array();
			
			// Build query to search data 
			// if id in the query we use the corresponding API call
			if (!empty($param['query']['id'])) {
				$recordReturned = $moduleApi->get($param['query']['id']);
				if (!empty($recordReturned[$param['module']])) {
					$record = $recordReturned[$param['module']];
				}
			// Otherwise we use the search API  	
			} elseif (!empty($param['query'])) {
				foreach ($param['query'] as $key => $value) {
					if (empty($searchFilter)) {
						$searchFilter = $key.':'.$value;
					} else {
						$searchFilter .= ' AND '.$key.':'.$value;
					}
				}
				// We use date_add because date_modified can be empty
				$recordReturned = $moduleApi->getList($searchFilter, 0, 1, 'date_added', 'desc', false, true);
				if (!empty($recordReturned[$moduleName][1])) {
					$record = $recordReturned[$moduleName][1];
				}
			// No query, we get the last record (used for simulation in rule creation process)	
			} else {
				// We use date_add because date_modified can be empty
				$recordReturned = $moduleApi->getList('', 0, 1, 'date_added', 'desc', false, true);
				if (!empty($recordReturned[$moduleName][1])) {
					$record = $recordReturned[$moduleName][1];
				}
			}
			// Convert Mautic result to Myddleware format
			if (!empty($record)) {			
				foreach ($param['fields'] as $field) {			
					// Some fields are directly in the record
					if (array_key_exists($field, $record)) {				
						$result['values'][$field] = $record[$field]; 
					// Other fields are in a sub tab
					} elseif (array_key_exists($field,$record['fields']['all'])) {				
						$result['values'][$field] = $record['fields']['all'][$field]; 
					}
				}
				// Add requiered field for Myddleware
				$result['values']['id'] = $record['id']; 
				if (!empty($record['dateModified'])) {
					$refDate = $record['dateModified'];
					$result['values']['date_modified'] = $this->dateTimeToMyddleware($refDate); 
				} elseif (!empty($record['dateAdded'])) {
					$refDate = $record['dateAdded'];
					$result['values']['date_modified'] = $this->dateTimeToMyddleware($refDate); 
				// e.g. No date for company
				} else {
					$refDate = $record['id'];
					$result['values']['date_modified'] = $refDate; 
				}
			}
			// If no result
			if (empty($result)) {
				$result['done'] = false;
			} else {
				if (!empty($result['values'])) {
					$result['done'] = true;
				}
			}
		} catch (\Exception $e) {
			$result['error'] = 'Error : ' . $e->getMessage().' '.$e->getFile().' '.$e->getLine();
			$result['done'] = -1;
		}	
		return $result;
	}
	
	public function create($param) {
		// Specific management for relationship creation between company and contact
		if ($param['module'] == 'companies__contact') {
			return $this->addConatctToCompany($param);
		}
		return $this->createUpdate('create', $param);

	}// end function create

	public function update($param) {
		return $this->createUpdate('update', $param);
	}// end function create
	
	
	// Create reconto to Mautic
	public function createUpdate($action, $param) {
		// Create API object depending on the module
		$api = new MauticApi();
		$moduleName = (!empty($this->plurialModuleName[$param['module']]) ? $this->plurialModuleName[$param['module']] : $param['module']);
		$moduleApi = $api->newApi($moduleName , $this->auth, $this->paramConnexion['url']);
	
		// Transformation du tableau d'entrée pour être compatible webservice Sugar
		foreach($param['data'] as $idDoc => $data) {
			try {
				// Manage target id for update action
				$targetId = '';
				if ($action == 'update') {
					if (empty($data['target_id'])) {
						throw new \Exception('Failed to update the record to Mautic. The target id is empty.');
					}
					$targetId = $data['target_id'];
				}
				// Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
				// update the record to Mautic
				if ($action == 'update') {	
					$record = $moduleApi->edit($targetId, $data, true); 
				// create the record to Mautic
				} else { 
					$record = $moduleApi->create($data);
				}				
				// Manage return data from Mautic
				if (!empty($record[$param['module']]['id'])) {
					$result[$idDoc] = array(
											'id' => $record[$param['module']]['id'],
											'error' => false
									); 
				} elseif(!empty($record['error']['message'])) {
					throw new \Exception('Failed to '.$action.' the record to Mautic. Code '.$record['error']['code'].' : '.$record['error']['message']);
				} else {
					throw new \Exception('Failed to '.$action.' the record to Mautic. No error message returned by the API.');
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
	
	// Create reconto to Mautic
	public function addConatctToCompany($param) {
		// Create API object depending on the module
		$api = new MauticApi();
		// Module companies__contact use companies API
		$moduleApi = $api->newApi('companies' , $this->auth, $this->paramConnexion['url']);
	
		// Transformation du tableau d'entrée pour être compatible webservice Sugar
		foreach($param['data'] as $idDoc => $data) {
			try {
				// Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
				if (empty($data['company'])) {
					throw new \Exception('Failed to add the contact to the company to Mautic because company is empty.');					
				}
				if (empty($data['contact'])) {
					throw new \Exception('Failed to add the contact to the company to Mautic because contact is empty.');					
				}
				// Create relationship into Mautic
				$record = $moduleApi->addContact($data['company'], $data['contact']);
				
				// Manage return data from Mautic
				if (!empty($record['success'])) {
					$result[$idDoc] = array(
											'id' => $data['company'].'_'.$data['contact'],
											'error' => false
									); 
				} else {
					throw new \Exception('Failed to add the contact to the company to Mautic.');
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
		try {	
			// Create API object depending on the module
			$api = new MauticApi();
			$moduleName = (!empty($this->plurialModuleName[$param['module']]) ? $this->plurialModuleName[$param['module']] : $param['module']);
			$moduleApi = $api->newApi($moduleName , $this->auth, $this->paramConnexion['url']);
			
			// For every document
			foreach($param['data'] as $idDoc => $data) {
				try {
					// Check control before delete
					$data = $this->checkDataBeforeDelete($param, $data);
					if (empty($data['target_id'])) {
						throw new \Exception('No target id found. Failed to delete the record.');
					}
					// remove record from Mautic
					$record = $moduleApi->delete($data['target_id']);
		
					// Manage return data from Mautic
					if (
							!empty($record[$param['module']]) 
						AND array_key_exists('id', $record[$param['module']])
					) {
						$result[$idDoc] = array(
												'id' => $data['target_id'],
												'error' => false
										); 
					} elseif(!empty($record['error']['message'])) {
						throw new \Exception('Failed to delete the record to Mautic. Code '.$record['error']['code'].' : '.$record['error']['message']);
					} else {
						throw new \Exception('Failed to delete the record to Mautic. No error message returned by the API.');
					} 								
				}
				catch (\Exception $e) {
					$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
					$result[$idDoc] = array(
							'id' => '-1',
							'error' => $error
					);
				}
				// Status modification for the transfer
				$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
			}
		}
		catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$result[$idDoc] = array(
					'id' => '-1',
					'error' => $error
			);
		}
		return $result;
	}
	
	
	// Build the direct link to the record (used in data transfer view)
	public function getDirectLink($rule, $document, $type){		
		try {
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
			return rtrim($url,'/').'/s/'.$this->plurialModuleName[$module].'/view/'.$recordId;
		} catch (\Exception $e) {
			return null;
		}
	}
		
	// Return if the connector can send deletion
	public function getSendDeletion($module) {
		// Don't allow to remove relation company - contact. 
		// This relationship will be remove when we will remove a contact or a company
		if ($module == 'companies__contact') {
			return false;
		}
		return parent::getSendDeletion($module);
	}	
	
	protected function checkDataBeforeCreate($param, $data) {
		// Remove target_id field as it is a Myddleware field		
		if (array_key_exists('target_id', $data)) {
			unset($data['target_id']);
		}
		return $data;
	}
	
	// Function to convert datetime format from the current application to Myddleware date format
	protected function dateTimeToMyddleware($dateTime) {
		$date = new \DateTime($dateTime);
		return $date->format('Y-m-d H:i:s');
	}// dateTimeToMyddleware($dateTime)	
}

/* * * * * * * *  * * * * * *  * * * * * * 
if a custom file exists we include it
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/mautic.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class mautic extends mauticcore {
		
	}
}
