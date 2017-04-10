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
use Symfony\Component\HttpFoundation\Session\Session;

class cirrusshieldcore  extends solution { 

	protected $url = 'https://www.cirrus-shield.net/RestApi/';
	protected $token;
	protected $update;
	
	protected $required_fields = array('default' => array('id','creationdate','modificationdate'));

	public function getFieldsLogin() {	
		return array(
					array(
							'name' => 'login',
							'type' => 'text',
							'label' => 'solution.fields.login'
						),
					array(
							'name' => 'password',
							'type' => 'password',
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
			$error = 'Failed to login to Cirrus Shield : '.$e->getMessage();
			echo $error . ';';
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
// print_r($param);	
		try {
			$param['fields'] = $this->addRequiredField($param['fields']);
				
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
				// $query .= " WHERE ModificationDate < '".date('Y-m-d H:i:s')."'" ; // Need to add 'limit 1' here when the command LIMIT will be available
				$query .= " WHERE modificationdate < '2018-01-01 00:00:00'" ; // Need to add 'limit 1' here when the command LIMIT will be available
			}
			
// echo $query.chr(10);			
// $query = 'SELECT Website,Shipping_ZIP,Shipping_State,Shipping_ZIP,Shipping_Google_Maps,Shipping_City,Rating,Phone,Industry,Email,Description,Billing_Street,Billing_State,Billing_ZIP,Billing_Country,Billing_City,Type,Name,Name FROM Account WHERE Id=1477692868289104797';
// $query_av_error = 'SELECT Website,Shipping_ZIP,Shipping_State,Shipping_ZIP,Shipping_Google_Maps,Shipping_City,Rating,Phone,Industry,Email,Description,Billing_Street,Billing_State,Billing_ZIP,Billing_Country,Billing_City,Type,Name,Name FROM Account';
// $query_OK = 'SELECT ModificationDate,Website,Shipping_ZIP,Shipping_State,Shipping_ZIP,Shipping_Google_Maps,Shipping_City,Rating,Phone,Industry,Email,Description,Billing_Street,Billing_State,Billing_ZIP,Billing_Country,Billing_City,Type,Name,Name FROM Account WHERE Id=1477692868289104797';
// $query_date = "SELECT Website,Shipping_ZIP,Shipping_State,Shipping_ZIP,Shipping_Google_Maps,Shipping_City,Rating,Phone,Industry,Email,Description,Billing_Street,Billing_State,Billing_ZIP,Billing_Country,Billing_City,Type,Name,Name, ModificationDate FROM Account WHERE ModificationDate = '2017-03-24 16:58:58' ";
// $query_email_OK = "SELECT Website,Shipping_ZIP,Shipping_State,Shipping_ZIP,Shipping_Google_Maps,Shipping_City,Rating,Phone,Industry,Email,Description,Billing_Street,Billing_State,Billing_ZIP,Billing_Country,Billing_City,Type,Name,Name, ModificationDate FROM Account WHERE Email = 'stephanefaure@myddleware.com' ";
// $query_limit = "SELECT Website,Shipping_ZIP,Shipping_State,Shipping_ZIP,Shipping_Google_Maps,Shipping_City,Rating,Phone,Industry,Email,Description,Billing_Street,Billing_State,Billing_ZIP,Billing_Country,Billing_City,Type,Name,Name, ModificationDate FROM Account WHERE Id=1477692868289104797 LIMIT 1 ";
// $query = urlencode('SELECT Name FROM Account WHERE Id=1477692868289104797');
// $query = "SELECT Name,Email,First_Name,Last_Name,OwnerId,Id,CreationDate,ModificationDate FROM Contact  WHERE ModificationDate > '2015-03-24 16:58:58'";
// $query = "SELECT Website,Shipping_ZIP,Shipping_State,Shipping_ZIP,Shipping_Google_Maps,Shipping_City,Rating,Phone,Industry,Email,Description,Billing_Street,Billing_State,Billing_ZIP,Billing_Country,Billing_City,Type,Name, ModificationDate FROM Account WHERE ModificationDate > '2000-01-01 20:51:05'";
// echo $query.chr(10);			

			$selectparam = ["authToken" 	=> $this->token,
							"selectQuery" 	=> $query,
							];
			$url = sprintf("%s?%s", $this->url."Query", http_build_query($selectparam));
			$resultQuery = $this->call($url);

// echo '<pre>';			
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
// print_r($record);
			
			foreach($param['fields'] as $field) {
				// We check the lower case because the result of the webservice return sfield without capital letter (first_name instead of First_Name)
				if(isset($record[$field])) {
					// Cirrus return an array when the data is empty
					if (is_array($record[$field])) {
						$result['values'][$field] = '';
					} else {
						$result['values'][$field] = $record[$field];
					}
				}
			}
			$result['done'] = true;

// print_r($query);
// echo 'BBBBBB'.chr(10)	;
// print_r($result);
// print_r($param['fields']);
// print_r($record);
// print_r($result);
// throw new \Exception('test read last');
// die();
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;
		}	
		return $result;
	}

	public function read($param) {
// print_r($param);	
		try {
			$result['date_ref'] = $param['date_ref'];
			$result['count'] = 0;
			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields']);
			
			// Get the reference date field name
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
				
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
					// if ($key == 'id') {
						// $key = 'Id';
					// }
					// Add the condition
					$query .= $key." = '".$value."' ";
				}
			// Function called as a standard read, we use the reference date
			} else {
				$query .= " WHERE ".$dateRefField." > ".$param['date_ref'].""; 
			}		

			// Buid the parameters to call the solution
			$selectparam = ["authToken" 	=> $this->token,
							"selectQuery" 	=> $query,
							];
			$url = sprintf("%s?%s", $this->url."Query", http_build_query($selectparam));
			$resultQuery = $this->call($url);

// echo '<pre>';			
// print_r($url);
// print_r($resultQuery);
// return null;
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
// print_r($record);				
// print_r($param['fields']);				
					// For each fields expected
					foreach($param['fields'] as $field) {
						// We check the lower case because the result of the webservice return sfield without capital letter (first_name instead of First_Name)
						if(isset($record[$field])) {
							// If we are on the date ref field, we add the entry date_modified (put to lower case because ModificationDate in the where is modificationdate int the select
							if ($field == strtolower($dateRefField)) {
								$row['date_modified'] = $record[$field];
							}
							
							// Cirrus return an array when the data is empty
							if (is_array($record[$field])) {
								$row[$field] = '';
							} else {
								$row[$field] = $record[$field];
							}
						}
					}
					if (
							!empty($record[strtolower($dateRefField)])
						&&	$result['date_ref'] < $record[strtolower($dateRefField)]
					) {
						$result['date_ref'] =  $record[strtolower($dateRefField)];
					}
					$result['values'][$record['id']] = $row;
					$result['count']++;
					$row = array();
				}
// echo $query;	
// print_r($result);
// return null;

			}
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
		}	
		return $result;
	}	
	
	// Create data in the target solution
	public function create($param) {
// print_r($param);
// return null;	
		foreach($param['data'] as $idDoc => $data) {
			try {
				 // Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
				
				// XML creation
				$xmlData = '<Data><'.$param['module'].'>';
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
				$xmlData .= '</'.$param['module'].'></Data>';
				
				// Set parameters to send data to the target solution (creation or modification)
				$selectparam = ["authToken" 		=> $this->token,
								"action" 			=> ($this->update ? 'update' : 'insert'),
								"matchingFieldName" => 'Id',
							];
				$url = sprintf("%s?%s", $this->url.'DataAction/'.$param['module'], http_build_query($selectparam));

				// Send data to the target solution
				$dataSent = $this->call($url,'POST',$xmlData);	
// print_r($xmlData);
// print_r($dataSent);
// return null;					
				// General error
				if (!empty($dataSent['Message'])) {
					throw new \Exception($dataSent['Message']);
				}
				if (!empty($dataSent['ErrorMessage'])) {
					throw new \Exception($dataSent['ErrorMessage']);
				}
				// Error managment for the record creation
				if (!empty($dataSent[$param['module']]['Success'])) {
					if ($dataSent[$param['module']]['Success'] == 'False') {
						throw new \Exception($dataSent[$param['module']]['ErrorMessage']);
					} else {
						$result[$idDoc] = array(
											'id' => $dataSent[$param['module']]['GUID'],
											'error' => false
									);
					}
				} else {
					throw new \Exception('No success flag returned by Cirrus Shield');
				}
			}
			catch (\Exception $e) {
				$error = $e->getMessage();
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Transfert status update
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}				
		return $result;
	}
	
	// Cirrus Shield use the same function for record's creation and modification
	public function update($param) {
		$this->update = true;
		return $this->create($param);
	}
	
	// retrun the reference date field name
	public function getDateRefName($moduleSource, $RuleMode) {
		// Creation and modification mode
		if($RuleMode == "0") {
			return "ModificationDate";
		// Creation mode only
		} else if ($RuleMode == "C"){
			return "CreationDate";
		} else {
			throw new \Exception ("$RuleMode is not a correct Rule mode.");
		}
		return null;
	}
	
	protected function call($url, $method = 'GET', $xmlData='', $timeout = 10){   
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
// echo 'AAAA'.chr(10);
            $result = curl_exec($ch);	
				// print_r($result); 
            curl_close($ch);
			// The login function return a string not an XML
			if ($method=='login') {
				return $result ? json_decode($result, true) : false;
			} else {
				if (@simplexml_load_string($result)) {
					$xml = simplexml_load_string($result);
					// print_r($xml); 
					$json = json_encode($xml);
					// print_r($json);
					return json_decode($json,TRUE);   
				// The result can be a json directly, in case of an error of query call (read last for example)
				} else {
					return json_decode($result,TRUE); 
				}
				// print_r($array);   
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