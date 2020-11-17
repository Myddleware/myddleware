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

class sugarcrmcore extends solution { 

	protected $sugarAPI;
	protected $defaultLimit = 100;
	protected $delaySearch = '-1 month';
	
	protected $required_fields = array('default' => array('id','date_modified'));
	
	protected $FieldsDuplicate = array(	'Contacts' => array('email1', 'last_name'),
										'Accounts' => array('email1', 'name'),
										'Users' => array('email1', 'last_name'),
										'Leads' => array('email1', 'last_name'),
										'Prospects' => array('email1', 'name'),
										'default' => array('name')
									  );

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
	
	
	// Connect to SugarCRM
    public function login($paramConnexion) {
        parent::login($paramConnexion);
        try {;
			$server = $this->paramConnexion['url'].'/rest/v10/';
			$credentials = array(
				'username' => $this->paramConnexion['login'],
				'password' => $this->paramConnexion['password']
			);
			
			// Log into Sugar
			$this->sugarAPI = new \SugarAPI\SDK\SugarAPI($server,$credentials);
			$this->sugarAPI->login();
			
			// Check the token
			$token = $this->sugarAPI->getToken();	
			if (!empty($token->access_token)) {
				$this->connexion_valide = true;
			} else {
				 return array('error' => 'Failed to connect to Sugar, no error returned.');
			}
			
		} catch(\SugarAPI\SDK\Exception\SDKException $e){
			$error = $e->getMessage();
            $this->logger->error($error);
            return array('error' => $error);
		}
	}
	
	// Get module list
	public function get_modules($type = 'source') {
	    try {
			$modulesSugar = $this->customCall($this->paramConnexion['url'].'/rest/v10/metadata?type_filter=full_module_list');		
			if (!empty($modulesSugar->full_module_list)) {
				foreach ($modulesSugar->full_module_list as $module => $label) {
					// hash isn't a Sugar module
					if ($module == '_hash') {
						continue;
					}
					$modules[$module] = $label; 
				}
			}
			return $modules;  	
	    }
		catch (\Exception $e) {
			return false;
		}
	}
	
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			$this->moduleFields = array();
			$this->fieldsRelate = array();
			
			// Call teh detail of all Sugar fields for the module 
			$fieldsSugar = $this->customCall($this->paramConnexion['url'].'/rest/v10/metadata?type_filter=modules&module_filter='.$module);

			// Browse fields
			if (!empty($fieldsSugar->modules->$module->fields)) {
				foreach($fieldsSugar->modules->$module->fields as $field) {
					if (empty($field->type)) {
						continue;
					}
					
					// Calculate the database type
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
					
					// Add all field in moduleFields
					$this->moduleFields[$field->name] = array(
														'label' => (!empty($field->comment) ? $field->comment : $field->name),
														'type' => $field->type,
														'type_bdd' => $type_bdd,
														'required' => (!empty($field->required) ? $field->required : 0) 
													);

					// Add option for enum fields
					if (in_array($field->type,array('enum','multienum'))) {
						$fieldsList = $this->customCall($this->paramConnexion['url'].'/rest/v10/'.$module.'/enum/'.$field->name);
						if (!empty($fieldsList)) {
							// Transform object to array
							foreach($fieldsList as $key => $value) {
								$this->moduleFields[$field->name]['option'][$key] = $value;
							}
						}
					}

					// Add relate fields
					if (
							substr($field->name,-3) == '_id' 
						OR substr($field->name,-4) == '_ida'
						OR substr($field->name,-4) == '_idb'
						OR (
								$field->type == 'id' 
							AND $field->name != 'id'
						)
						OR $field->name	== 'created_by'
					) {
						$this->fieldsRelate[$field->name] = array(
														'label' => (!empty($field->comment) ? $field->comment : $field->name),
														'type' => 'varchar(36)',
														'type_bdd' => 'varchar(36)',
														'required' => (!empty($field->required) ? $field->required : 0),
														'required_relationship' => 0
													);
					} 
				}
			}			
			return $this->moduleFields;
		}
		catch (\Exception $e){	
			return false;
		}
	} // get_module_fields($module)	 


   /**
     * Get the last data in the application
     * @param $param
     * @return mixed
     */
    public function read_last($param) {	

		// Query empty when the rule simulation is requested
		if (empty($param['query'])) {
			// For the simulation we set the search date to last week (we don't put 0 for peformance matters but it is possible to redefine it)
			$param['date_ref'] =  date('Y-m-d H:i:s', strtotime($this->delaySearch));
		}
		$param['limit'] = 1;
	
		// We re use read function for the read_last 
		$read = $this->read($param);	

		// Format output values
		if (!empty($read['error'])) {
			$result['error'] = $read['error'];
		} else {
			if (!empty($read['values'])) {
				$result['done'] = true;
				// Get only one record
				$result['values'] = current($read['values']);
			} else {
				$result['done'] = false;
			}
		}	
		return $result; 
    }// end function read_last
	
	  /**
     * Function read data
     * @param $param
     * @return mixed
     */
    public function read($param) {
        try {			
			$result = array();
			$result['count'] = 0;
			$result['date_ref'] = $param['date_ref'];
			
			// Set default limit
			if (empty($param['limit'])) {
				$param['limit'] = $this->defaultLimit;
			}
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields'],$param['module']);	
		
			// Init search parameters
			$filterArgs = array(
				'max_num' => $param['limit'],
				'offset' => 0,
				'fields' => implode($param['fields'],','),
				'order_by' => 'date_modified'
			);
			// Init search filters
			// Search by fields (id or duplicate fields)
			if (!empty($param['query'])) { 
				// Add every filter (AND operator by default)
				foreach($param['query'] as $key => $value) {
					$filterArgs['filter'][] = array($key => array('$equals' => $value));
				}
			// Search By reference
			} else {
				$filterArgs['filter'] = array(
												array(
													"date_modified" => array(
														'$gt' => $this->dateTimeFromMyddleware($param['date_ref']),
													)
												),
										);			
			}	
			
			// Get the records
			$getRecords = $this->sugarAPI->filterRecords($param['module'])->execute($filterArgs);
			$response = $getRecords->getResponse();
			// Format response if http return = 200
			if ($response->getStatus()=='200'){
				$body = $getRecords->getResponse()->getBody(false);
				if (!empty($body->records)) {
					$records = $body->records;
				}
			} else {
				throw new \Exception(print_r($response->getBody(),true));
			}

			// Format records to result format
			if (!empty($records)) {
				foreach ($records as $record) {
					foreach($param['fields'] as $field) {
						$result['values'][$record->id][$field] = (!empty($record->$field) ? $record->$field : '');
					}
					$result['values'][$record->id]['id'] = $record->id;
					$result['values'][$record->id]['date_modified'] = $record->date_modified;
				}
				// We get the date_modified of the last records because SugarCRM webservice returns record sorted by date_modified
				$result['date_ref'] = $this->dateTimeToMyddleware($record->date_modified);
				$result['count'] = count($records);
			}	
	  } catch (\Exception $e) {
            $result['error'] = 'Error : ' . $e->getMessage() . ' ' . __CLASS__ . ' Line : ( ' . $e->getLine() . ' )';	
        }						
		return $result;
    }// end function read

	/**
	 * Function create data
	 * @param $param
	 * @return mixed
	 */
	public function create($param) {
		return $this->upsert('create', $param);

	}// end function create

	/**
	 * Function update data
	 * @param $param
	 * @return mixed
	 */
	public function update($param) {
		return $this->upsert('update', $param);
	}// end function create

	public function upsert($method,$param) {
		foreach($param['data'] as $idDoc => $data) {
			try {
				// Check control before create/update
				$param['method'] = $method;
				$data = $this->checkDataBeforeCreate($param, $data);
				
				if ($method == 'create') {
					// Myddleware field empty when data transfer type is create
					unset($data['target_id']);
					$recordResult = $this->sugarAPI->createRecord($param['module'])->execute($data);
				} else {			
					// The record id is stored in $data['target_id']
					$targetId = $data['target_id'];
					unset($data['target_id']);
					$recordResult = $this->sugarAPI->updateRecord($param['module'], $targetId)->execute($data);
				}
				
				$response = $recordResult->getResponse();
				if ($response->getStatus()=='200'){
					$record = $response->getBody(false);
					if (!empty($record->id)) {
						$result[$idDoc] = array(
												'id' => $record->id,
												'error' => false
										);
					}
					else  {
						throw new \Exception('Error during '.print_r($response->getBody(),true));
					}
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
	}// end function create
	
	
	// Convert date to Myddleware format 
	// 2020-07-08T12:33:06+02:00 to 2020-07-08 10:33:06
	protected function dateTimeToMyddleware($dateTime) {
		$dto = new \DateTime($dateTime);
		// We save the UTC date in Myddleware
		$dto->setTimezone(new \DateTimeZone('UTC'));
		return $dto->format("Y-m-d H:i:s");
	}// dateTimeToMyddleware($dateTime)	
	
	// Convert date to SugarCRM format
	protected function dateTimeFromMyddleware($dateTime) {
		$dto = new \DateTime($dateTime);
		// Return date to UTC timezone
		return $dto->format('Y-m-d\TH:i:s+00:00');
	}// dateTimeToMyddleware($dateTime)    	

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
		return rtrim($url,'/').'/#'.$module.'/'.$recordId;
	}
	
	// Used only for metadata (get_modules and )
	protected function customCall($url, $parameters = null, $method = null){
		try {
			$request = curl_init($url);
			if (!empty($method)) {
				curl_setopt($request, CURLOPT_CUSTOMREQUEST, $method);
			}
			curl_setopt($request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
			curl_setopt($request, CURLOPT_HEADER, false);
			curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($request, CURLOPT_FOLLOWLOCATION, 0);
			
			$token = $this->sugarAPI->getToken();	
			curl_setopt($request, CURLOPT_HTTPHEADER, array(
				"Content-Type: application/json",
				"oauth-token: {$token->access_token}"
			));
			//convert arguments to json
			if (!empty($parameters)) {
				$json_arguments = json_encode($parameters);
				curl_setopt($request, CURLOPT_POSTFIELDS, $json_arguments);
			}
			
			//execute request
			$response = curl_exec($request);
			//decode response
			$response_obj = json_decode($response);
			
		}
		catch(Exception $e) {
			throw new \Exception($e->getMessage());
		}	
		// Send exception catched into functions
		if (!empty($response_obj->error_message)) {
			 throw new \Exception($response_obj->error_message);
		}
		return $response_obj;			
    }	
}

/* * * * * * * *  * * * * * *  * * * * * *
    Include custom file if exists : used to redefine Myddleware standard code
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__ . '/../Custom/Solutions/sugarcrm.php';
if (file_exists($file)) {
    require_once($file);
} else {
    // Otherwise, we use the current class (in this file)
    class sugarcrm extends sugarcrmcore
    {

    }
}