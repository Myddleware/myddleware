<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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
use Symfony\Component\HttpFoundation\Session\Session;

class cirrusshieldcore  extends solution { 

	protected $url = 'https://www.cirrus-shield.net/RestApi/';
	protected $token;
	protected $update;
	protected $organizationTimezoneOffset;
	protected $limitCall = 1;
	
	protected $required_fields = array('default' => array('Id','CreationDate','ModificationDate'));
	
	protected $FieldsDuplicate = array(	'Contact' => array('Email','Name'),
										'default' => array('Name')
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
						)
		);
	}
 	
	// Login to Cirrus Shield
	public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
			// Generate parameters to connect to Cirrus Shield
			$login = ["Username" => $this->paramConnexion['login'],
					  "password" => $this->paramConnexion['password'],
					 ];
			$url = sprintf("%s?%s", $this->url."AuthToken", http_build_query($login));
			
			// Get the token
			$this->token = $this->call($url,'login');
			if (empty($this->token)) {
				throw new \Exception('login error');	
			}
			
			// Connection validation
			$this->connexion_valide = true; 
		}
		catch (\Exception $e) {
			$error = $e->getMessage();
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)
	
	
	// Get the modules available
	public function get_modules($type = 'source') {
		try{			
			$apiModules = $this->call($this->url.'DescribeAll?authToken='.$this->token);
			if (!empty($apiModules)) {
				foreach($apiModules as $apiModule) {
					$modules[$apiModule['APIName']] = $apiModule['Label'];
				}
			}
			return $modules;			
		} catch (\Exception $e) {
			$error = $e->getMessage();
			return $error;			
		}
	} 
	
	// Get the fields available for the module in input
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			$apiFields = $this->call($this->url.'Describe/'.$module.'?authToken='.$this->token);	
			if (!empty($apiFields['Fields'])) {
				// Add each field in the right list (relate fields or normal fields)
				foreach($apiFields['Fields'] as $field) {
					// Field not editable can't be display on the target side
					if (
							empty($field['IsEditable'])
						AND $type == 'target'
					){
						continue;
					}
					// If the fields is a relationship
					if ($field['DataType'] == 'LookupRelationship') {
						$this->fieldsRelate[$field['Name']] = array(
													'label' => $field['Label'],
													'type' => 'varchar(36)',
													'type_bdd' => 'varchar(36)',
													'required' => $field['IsRequired'],
													'required_relationship' => $field['IsRequired'],
												);
					} else {							
						$this->moduleFields[$field['Name']] = array(
													'label' => $field['Label'],
													'type' => $field['DataType'],
													'type_bdd' => $field['DataType'],
													'required' => $field['IsRequired']
												);
						// Add list of values
						if (!empty($field['PicklistValues'])) {
							foreach($field['PicklistValues'] as $value) {
								$this->moduleFields[$field['Name']]['option'][$value['Name']] = $value['Label'];
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
		}
		catch (\Exception $e){
			return false;
		}
	} // get_module_fields($module)	 
	

	// Get the last data in the application
	public function read_last($param) {	
		try {
			$param['fields'] = $this->addRequiredField($param['fields']);
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			
			$query = 'SELECT ';
			// Build the SELECT 
			if (!empty($param['fields'])) {
				foreach ($param['fields'] as $field) {
					$query .= $field.',';
				}
				// Delete the last coma 
				$query = rtrim($query, ',');
			} else {
				$query .= ' * ';
			}
			
			// Add the FROM
			$query .= ' FROM '.$param['module'].' ';
			
			// Generate the WHERE
			if (!empty($param['query'])) {
				$query .= ' WHERE ';
				$first = true;
				foreach ($param['query'] as $key => $value) {
					// Add the AND only if we are not on the first condition
					if ($first) {
						$first = false;
					} else {
						$query .= ' AND ';
					}
					// The field id in Cirrus shield as a capital letter for the I, not in Myddleware
					if ($key == 'id') {
						$key = 'Id';
					}
					// Add the condition
					$query .= $key." = '".$value."' ";
				}
			// The function is called for a simulation (rule creation) if there is no query
			} else {
				$query .= " WHERE ModificationDate < '".date('Y-m-d H:i:s')."'" ; // Need to add 'limit 1' here when the command LIMIT will be available
			}
	
			// Buid the input parameter
			$selectparam = ["authToken" 	=> $this->token,
							"selectQuery" 	=> $query,
							];
			$url = sprintf("%s?%s", $this->url."Query", http_build_query($selectparam));
			$resultQuery = $this->call($url);
		
			// If the query return an error 
			if (!empty($resultQuery['Message'])) {
				throw new \Exception($resultQuery['Message']);	
			}	
			// If no result
			if (empty($resultQuery)) {
				$result['done'] = false;
			}
			// Format the result
			// If several results, we take the first one
			if (!empty($resultQuery[$param['module']][0])) {
				$record = $resultQuery[$param['module']][0];	
			// If one result we take the first one
			} else {
				$record = $resultQuery[$param['module']];
			}
			
			foreach($param['fields'] as $field) {
				// We check the lower case because the result of the webservice return sfield without capital letter (first_name instead of First_Name)
				if(isset($record[$field])) {
					// Cirrus return an array when the data is empty
					if (is_array($record[$field])) {
						$result['values'][$field] = '';
					} else {
						// The field id in Cirrus shield as a capital letter for the I, not in Myddleware
						if ($field == 'Id') {
							$result['values']['id'] = $record[$field];
						} else {
							$result['values'][$field] = $record[$field];
						}
					}
				}
			}
			if (!empty($result['values'])) {
				$result['done'] = true;
			}
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;
		}			
		return $result;
	}

	public function read($param) {
		try {
			$result['date_ref'] = $param['date_ref'];
			$result['count'] = 0;
			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields']);
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			
			// Get the reference date field name
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
			
			// Get the organization timezone
			if (empty($this->organizationTimezoneOffset)) {
				$this->getOrganizationTimezone();
				// If the organization timezone is still empty, we generate an error
				if (empty($this->organizationTimezoneOffset)) {
					throw new \Exception('Failed to get the organization Timezone. This timezone is requierd to save the reference date.');
				}
			}
			
			$query = 'SELECT ';
			// Build the SELECT 
			if (!empty($param['fields'])) {
				foreach ($param['fields'] as $field) {
					$query .= $field.',';
				}
				// Delete the last coma 
				$query = rtrim($query, ',');
			} else {
				$query .= ' * ';
			}
			
			// Add the FROM
			$query .= ' FROM '.$param['module'].' ';
			
			// Generate the WHERE
			// if a specific query is requeted we don't use date_ref (used for child document)
			if (!empty($param['query'])) {
				$query .= ' WHERE ';
				$first = true;
				foreach ($param['query'] as $key => $value) {
					// Add the AND only if we are not on the first condition
					if ($first) {
						$first = false;
					} else {
						$query .= ' AND ';
					}
					// The field id in Cirrus shield as a capital letter for the I, not in Myddleware
					if ($key == 'id') {
						$key = 'Id';
					}
					// Add the condition
					$query .= $key." = '".$value."' ";
				}
			// Function called as a standard read, we use the reference date
			} else {
				$query .= " WHERE ".$dateRefField." > ".$param['date_ref'].$this->organizationTimezoneOffset; 
				// $query .= " WHERE ".$dateRefField." > 2017-03-30 14:42:35-05 "; 
			}		

			// Buid the parameters to call the solution
			$selectparam = ["authToken" 	=> $this->token,
							"selectQuery" 	=> $query,
							];
			$url = sprintf("%s?%s", $this->url."Query", http_build_query($selectparam));
			$resultQuery = $this->call($url);

			// If the query return an error 
			if (!empty($resultQuery['Message'])) {
				throw new \Exception($resultQuery['Message']);	
			}	
			// If no result
			if (!empty($resultQuery[$param['module']])) {
				// If only one record, we add a dimension to be able to use the foreach below
				if (empty($resultQuery[$param['module']][0])) {
					$tmp[$param['module']][0] = $resultQuery[$param['module']];
					$resultQuery = $tmp;
				}
				// For each records
				foreach($resultQuery[$param['module']] as $record) {				
					// For each fields expected
					foreach($param['fields'] as $field) {
						if ($field == 'id') {
							$field = 'Id';
						}					
						// We check the lower case because the result of the webservice return sfield without capital letter (first_name instead of First_Name)
						if(isset($record[$field])) {
							// If we are on the date ref field, we add the entry date_modified (put to lower case because ModificationDate in the where is modificationdate int the select
							if ($field == $dateRefField) {
								$row['date_modified'] = $record[$field];
							} elseif ($field == 'Id') {
								$row['id'] = $record[$field];
							} else {
								// Cirrus return an array when the data is empty
								if (is_array($record[$field])) {
									$row[$field] = '';
								} else {
									$row[$field] = $record[$field];
								}
							}
						}
					}
					if (
							!empty($record[$dateRefField])
						&&	$result['date_ref'] < $record[$dateRefField]
					) {								
						// Transform the date with the organization timezone
						$dateRef = new \DateTime($record[$dateRefField]);
						$dateRef->modify($this->organizationTimezoneOffset.' hours');
						$result['date_ref'] = $dateRef->format('Y-m-d H:i:s');
					}
					$result['values'][$row['id']] = $row;
					$result['count']++;
					$row = array();
				}
			}
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
		}	
		return $result;
	}	
	
	// Create data in the target solution
	public function create($param) {	
		try {
			$i = 0;
			$nb_record = count($param['data']);	
			// XML creation (for the first call)
			$xmlData = '<Data>';
			foreach($param['data'] as $idDoc => $data) {
				$i++;
				 // Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
				$xmlData .= '<'.$param['module'].'>';
				
				// Save the idoc to manage result in case of mass upsert
				if ($this->limitCall > 1) {
					$xmlData .= '<OrderId>'.$idDoc.'</OrderId>';
				}
				
				foreach ($data as $key => $value) {
					// Field only used for the update and contains the ID of the record in the target solution			
					if ($key=='target_id') {					
						// If updade then we change the key in Id
						if (!empty($value)) {
							$key = 'Id';
						} else { // If creation, we skip this field
							continue;
						}
					}
					$xmlData .= '<'.$key.'>'.$value.'</'.$key.'>';
					
				}
				$xmlData .= '</'.$param['module'].'>';
			
				// If we have finished to read all data or if the package is full we send the data to Sallesforce
				if (
						$i == $nb_record
					 || $i % $this->limitCall  == 0
				) {	
					$xmlData .= '</Data>';
					
					// Set parameters to send data to the target solution (creation or modification)
					$selectparam = ["authToken" 		=> $this->token,
									"action" 			=> ($this->update ? 'update' : 'insert'),
									"matchingFieldName" => 'Id',
									"useExternalId" 	=> 'false',
								];
					$url = sprintf("%s?%s", $this->url.'DataAction/'.$param['module'], http_build_query($selectparam));
					// Send data to the target solution					
					$resultCall = $this->call($url,'POST',urlencode($xmlData));
					if (empty($resultCall)) {
						throw new \Exception('Result from Cirrus Shield empty');
					}
					if (!empty($resultCall['Message'])) {
						throw new \Exception($resultCall['Message']);
					}		
					// XML initialisation (for the next call)
					$xmlData = '<Data>';

					// If only one result, we add a dimension
					if (isset($resultCall[$param['module']]['Success'])) {
						$resultCall[$param['module']] = array($resultCall[$param['module']]);
						
					}
					
					// Manage results
					if (!empty($resultCall[$param['module']])) {					
						foreach ($resultCall[$param['module']] as $record) {
							// General error
							if (!empty($record['Message'])) {
								throw new \Exception($record['Message']);
							}		
							// We use orderId as id document only when we execute to mass upsert. In this case this field orderid has to be created in Cirrus
							if (!empty($record['orderid'])) {
								$idDoc = $record['orderid'];
							}
							
							// Error managment for the record creation
							if (!empty($record['Success'])) {
								if ($record['Success'] == 'False') {
									$result[$idDoc] = array(
																	'id' => '-1',
																	'error' => $record['ErrorMessage']
																);
								} else {
									$result[$idDoc] = array(
															'id' => $record['GUID'],
															'error' => false
														);
								}
							} else {
								$result[$idDoc] = array(
																'id' => '-1',
																'error' => 'No success flag returned by Cirrus Shield'
															);

							}
							// Transfert status update
							$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
						}
					}
				}
			}				
		}
		catch (\Exception $e) {
			$error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
			$result['error'] = $error;
		}				
		return $result;
	}
	
	// Cirrus Shield use the same function for record's creation and modification
	public function update($param) {
		$this->update = true;
		$result = $this->create($param);
		$this->update = false;
		return $result;
	}
	
	// retrun the reference date field name
	public function getDateRefName($moduleSource, $RuleMode) {
		// Creation and modification mode
		if(in_array($RuleMode,array("0","S"))) {
			return "ModificationDate";
		// Creation mode only
		} else if ($RuleMode == "C"){
			return "CreationDate";
		} else {
			throw new \Exception ("$RuleMode is not a correct Rule mode.");
		}
		return null;
	}
	
	protected function getOrganizationTimezone() {
		// Get the organization in Cirrus
		$query = 'SELECT DefaultTimeZoneSidKey FROM Organization';
		// Buid the parameters to call the solution
		$selectparam = ["authToken" 	=> $this->token,
						"selectQuery" 	=> $query,
						];
		$url = sprintf("%s?%s", $this->url."Query", http_build_query($selectparam));
		$resultQuery = $this->call($url);
		if (empty($resultQuery['Organization']['DefaultTimeZoneSidKey'])) {
			throw new \Exception('Failed to retrieve the organisation timezone : no organization found '.$resultQuery['Organization']['DefaultTimeZoneSidKey'].'. ');	
		}
		
		// Get the list of timeZone  Cirrus
		$organizationFields = $this->call($this->url.'Describe/Organization?authToken='.$this->token);
		
		if (!empty($organizationFields['Fields'])) {
			// Get the content of the field DefaultTimeZoneSidKey
			$timezoneFieldKey = array_search('DefaultTimeZoneSidKey', array_column($organizationFields['Fields'], 'Name'));
			if (!empty($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'])) {
				// Get the key of the timezone of the organization
				$timezoneOrganizationKey = array_search($resultQuery['Organization']['DefaultTimeZoneSidKey'], array_column($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'], 'Name'));		
				if (!empty($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'][$timezoneOrganizationKey]['Label'])) {
					// Get the offset of the timezone formatted like (GMT-05:00) Eastern Standard Time (America/New_York)
					$this->organizationTimezoneOffset = substr($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'][$timezoneOrganizationKey]['Label'], strpos($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'][$timezoneOrganizationKey]['Label'], 'GMT')+3, 3);
				}
			}
		}	
		// Error management
		if (empty($this->organizationTimezoneOffset)) {
			throw new \Exception('Failed to retrieve the organisation timezone : no timezone found for the value ');	
		}
	}

	
	protected function call($url, $method = 'GET', $xmlData='', $timeout = 300){   
		if (function_exists('curl_init') && function_exists('curl_setopt')) {
            $ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			
			// Some additional parameters are required for POST 
			if ($method=='POST') {
				$headers = array(
								"Content-Type: application/x-www-form-urlencoded",
								"charset=utf-8"
								);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_POSTFIELDS, "=".$xmlData);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLINFO_HEADER_OUT, true);
				curl_setopt($ch, CURLOPT_SSLVERSION, 6);
				curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			}
            $result = curl_exec($ch);	
            curl_close($ch);
			// The login function return a string not an XML
			if ($method=='login') {
				return $result ? json_decode($result, true) : false;
			} else {
				if (@simplexml_load_string($result)) {
					$xml = simplexml_load_string($result);
					$json = json_encode($xml);
					return json_decode($json,TRUE);   
				// The result can be a json directly, in case of an error of query call (read last for example)
				} else {
					return json_decode($result,TRUE); 
				}
			} 
        }
        throw new \Exception('curl extension is missing!');
    }	
	 
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/cirrusshield.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class cirrusshield extends cirrusshieldcore {
		
	}
}