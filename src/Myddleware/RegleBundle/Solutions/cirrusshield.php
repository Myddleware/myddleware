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
			$this->token = $this->call($url);
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
			
			$query = 'SELECT ';
			// Build the SELECT 
			if (!empty($param['fields'])) {
				foreach ($param['fields'] as $field) {
					$query .= $field.',';
				}
				// Delete the last coma 
				$query .= rtrim($field, ',');
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
					// Add the condition
					$query .= $key." = '".$value."' ";
				}
			} 
// $query = 'SELECT Website,Shipping_ZIP,Shipping_State,Shipping_ZIP,Shipping_Google_Maps,Shipping_City,Rating,Phone,Industry,Email,Description,Billing_Street,Billing_State,Billing_ZIP,Billing_Country,Billing_City,Type,Name,Name FROM Account WHERE Id=1477692868289104797';
$query_av_error = 'SELECT Website,Shipping_ZIP,Shipping_State,Shipping_ZIP,Shipping_Google_Maps,Shipping_City,Rating,Phone,Industry,Email,Description,Billing_Street,Billing_State,Billing_ZIP,Billing_Country,Billing_City,Type,Name,Name FROM Account';
$query_OK = 'SELECT Website,Shipping_ZIP,Shipping_State,Shipping_ZIP,Shipping_Google_Maps,Shipping_City,Rating,Phone,Industry,Email,Description,Billing_Street,Billing_State,Billing_ZIP,Billing_Country,Billing_City,Type,Name,Name FROM Account WHERE Id=1477692868289104797';
$query = 'SELECT Website,Shipping_ZIP,Shipping_State,Shipping_ZIP,Shipping_Google_Maps,Shipping_City,Rating,Phone,Industry,Email,Description,Billing_Street,Billing_State,Billing_ZIP,Billing_Country,Billing_City,Type,Name,Name FROM Account WHERE Id=1477692868289104797 ';
// $query = urlencode('SELECT Name FROM Account WHERE Id=1477692868289104797');
			$result = $this->call($this->url.'Query?authToken='.$this->token.'&selectQuery='.urlencode($query));
			
echo '<pre>';
print_r($query);
print_r($result);
die();
			/* // Ajout des champs obligatoires pour 
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
			}			 */									
			return $result;		
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;
			return $result;
		}	
	}
	
	protected function call($url, $method = 'GET', $args=array(), $timeout = 10){   
	 if (function_exists('curl_init') && function_exists('curl_setopt')) {
            $ch = curl_init();
			
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			curl_setopt($ch, CURLOPT_URL, $url);
           /* curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json\r\n'));  
            curl_setopt($ch, CURLOPT_USERAGENT, 'oauth2-draft-v10');
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			if (!empty($this->access_token) and empty($args['oauth_token'])) {
				curl_setopt($ch, CURLOPT_USERPWD, "user:".$this->access_token.'-'.$this->dc);
            }
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

			// For metadata and authentificate call only
			if (!empty($args['oauth_token']) || !empty($args['grant_type'])) {
				$value = http_build_query($args); //params is an array	
				curl_setopt($ch, CURLOPT_POSTFIELDS, $value);
			}
            elseif (!empty($args)) {
                $jsonData = json_encode($args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            }*/
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
print_r($result);			
            curl_close($ch);
            
            return $result ? json_decode($result, true) : false; 
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