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
     * Function read data
     * @param $param
     * @return mixed
     */
    public function read($param) {
        try {
print_r($param);
			$result = array();
			$result['count'] = 0;
			$result['date_ref'] = $param['date_ref'];
			
			if (empty($param['limit'])) {
				$param['limit'] = $this->defaultLimit;
			}
			
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);

			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields'],$param['module']);	
// https://support.sugarcrm.com/Documentation/Sugar_Developer/Sugar_Developer_Guide_9.2/Integration/Web_Services/REST_API/Endpoints/modulefilter_POST/			
			// In case we search a specific record with an ID, we call the function getResource
			if (!empty($param['query']['id'])) {
				$this->sugarAPI->getRecord($param['module'],$param['query']['id'])->execute();
			// Search by other fields (duplicate fields)
			} elseif (!empty($param['query'])) { // Iadvise used only in source so we don't have to develop this part

// foreach($ids as $id) {
	// $searchIds[] = array('id' => array('$equals' => $id));
// }
// $filter[] = array('$or' => $searchIds);			
			
$filterArgs = array(
    'max_num' => 100,
    'offset' => 0,
    'fields' => implode($param['fields'],',')
);
foreach($param['query'] as $key => $value) {
	$filterArgs['filter'] = array($key => array($equals => $value));
}
$this->sugarAPI->filterRecords($param['module'])->execute($filterArgs);
			// Search By reference
			} else {
				$filterArgs = array(
					'max_num' => 5,
					'offset' => 0,
					'filter' => array(
									array(
										"date_modified" => array(
											'$gte'=>$param['date_ref'],
										)
									),
					),
					'fields' => implode($param['fields'],','),
					'order_by' => 'date_modified'
				);
print_r($param);
print_r($filterArgs);
				$records = $this->sugarAPI->filterRecords($param['module'])->execute($filterArgs);
print_r($records);
			}		
			// if (!empty($records)) {
				// $result['values'] = $records;
				// $result['count'] = count($records);
				// $result['date_ref'] = current($records)['created_at'];
			// }
return null;	
	  } catch (\Exception $e) {
            $result['error'] = 'Error : ' . $e->getMessage() . ' ' . __CLASS__ . ' Line : ( ' . $e->getLine() . ' )';	
        }						
		return $result;
    }// end function read

	
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
			throw new Exception($e->getMessage());
		}	
		// Send exception catched into functions
		if (!empty($response_obj->error_message)) {
			 throw new Exception($response_obj->error_message);
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