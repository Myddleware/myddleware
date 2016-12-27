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

use Symfony\Bridge\Monolog\Logger;

require_once('lib/shopapplication/Unirest.php');

class shopapplicationcore extends solution {

	protected $url;
	protected $apiKey;
	protected $docIdList;
	protected $docIdListResult;
	
	protected $required_fields = array('default' => array('id','date_modified','date_created'));
	protected $FieldsDuplicate = array('customers' => array('email'));
	protected $IdByModule = array(
							'customers_addresses' => 'address_id'
							);
							
	// Structure of child module : module => childmodule => entry name and id name of the child array in the parent array					
	protected $arrayKey = array(
							'customers' => array('customers_addresses' => array('entry_name' => 'addresses', 'id_name' => 'address_id', 'max_level' => 1)),
							'orders' => array('orders_products' => array('entry_name' => 'products', 'id_name' => 'id', 'max_level' => 1)),
							'products' => array('products_options' => array('entry_name' => 'options', 'id_name' => 'option_id', 'max_level' => 1)),
							// 'products_options' => array('options_values' => array('entry_name' => 'options', 'id_name' => 'option_id', 'max_level' => 1)),
							'options' => array('options_values' => array('entry_name' => 'values', 'id_name' => 'value_id', 'max_level' => 1)),
							);
							
	// Modules with language						
	protected $moduleWithLanguage = array('products','categories','options','options_values');	
	
	// Submodule 
	// protected $childModule = array('customers_addresses','orders_products','options_values');
	
	// Connection parameters
	public function getFieldsLogin() {	
        return array(
					array(
							'name' => 'url',
							'type' => 'text',
							'label' => 'solution.fields.url'
						),
                   array(
                            'name' => 'apikey',
                            'type' => 'password',
                            'label' => 'solution.fields.apikey'
                        )
        );
	} // getFieldsLogin()
	
	// Connexion to Shop-application
    public function login($paramConnexion) {
		// Call parent to set $paramConnexion in an attribut of the class 
		parent::login($paramConnexion);
		try{	
			// Delete the "/" at the end of the url if the user have added one
			$this->url = rtrim($this->paramConnexion['url'],'/').'/api/';
			$this->apiKey = '?key='.$this->paramConnexion['apikey'];
			// Try to access to the shop
			$result = $this->call(trim($this->url.$this->apiKey), 'get', '');	
			// get the code, if 200 then success otherwise error
			$code = $result->__get('code');
			if ($code <> '200') {
				// Get the error message
				$body = $result->__get('body');
				throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
			}
			$this->connexion_valide = true;
		}
		catch (\Exception $e) {
			$error = 'Failed to login to Shop-application : '.$e->getMessage();
			echo $error . ';';
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)*/

	 
	public function get_modules($type = 'source') {
		return array(	
			'customers' => 'Customers',
			'customers_addresses' => 'Customers addresses',
			'orders' => 'Orders',
			'orders_products' => 'Orders products',
			'products' => 'Products',
			'products_options' => 'Products options',
			'options' => 'Options',
			'options_values' => 'Options values',
			'categories' => 'Categories',
			'brands' => 'Brands',
		);

	} // get_modules()	
	 
	// Renvoie les champs du module passé en paramètre
	public function get_module_fields($module, $type = 'source', $extension = false) {
		require_once('lib/shopapplication/metadata.php');		
		parent::get_module_fields($module, $type, $extension);
		try{
// echo '<pre>';
// echo 'module : '.$module;	
// echo 'AAA';	
// print_r($moduleFields);
// print_r($moduleFields[$module]);
			if (!empty($moduleFields[$module])) {
				$this->moduleFields = $moduleFields[$module];
			}
// echo 'BBB';	}
			if (!empty($fieldsRelate[$module])) {
				$this->fieldsRelate = $fieldsRelate[$module]; 
			}
// echo 'CCC';	
// print_r($this->moduleFields);	
// die();				
			// Retrieve specific list
			if ($module == 'customers') {
				try {
					// Get customer's groups
					$urlApi = $this->url.'customers/groups'.$this->apiKey;
					$return = $this->call($urlApi, 'get', '');	
					$code = $return->__get('code');
					if ($code == '200') {		
						$body = $return->__get('body');
						if (!empty($body)) {
							foreach ($body as $group) {
								$this->fieldsRelate['group_id']['option'][$group->id] = $group->name;
							}
						}
					}
				} 
				catch (\Exception $e) {
				} 			
			}
			if ($module == 'orders') {	
				try {
					// Get order's status
					$urlApi = $this->url.'orders/status'.$this->apiKey;
					$return = $this->call($urlApi, 'get', '');	
					$code = $return->__get('code');
					/* if ($code == '200') {		
						$body = $return->__get('body');
						if (!empty($body)) {
							foreach ($body as $status) {
								$this->fieldsRelate['group_id']['option'][$status->id] = $status->name;
							}
						}
					} */
					// Get currencies
					$urlApi = $this->url.'currencies'.$this->apiKey;
					$return = $this->call($urlApi, 'get', '');	
					$code = $return->__get('code');
					if ($code == '200') {		
						$body = $return->__get('body');
						if (!empty($body)) {
							foreach ($body as $currency) {
								$this->fieldsRelate['currency']['option'][$currency->code] = $currency->name;
							}
						}
					} 
				} 
				catch (\Exception $e) {
				} 			
			}
			if ($module == 'customers_addresses') {			
				try {
					// Get countries
					$urlApi = $this->url.'countries'.$this->apiKey;
					$return = $this->call($urlApi, 'get', '');	
					$code = $return->__get('code');
					if ($code == '200') {		
						$body = $return->__get('body');
						if (!empty($body)) {
							foreach ($body as $country) {
								$this->moduleFields['country_id']['option'][$country->id] = $country->name;
							}
						}
					}
				} 
				catch (\Exception $e) {
				} 		
			}
			if ($module == 'categories') {			
				try {
					// Get store id
					$urlApi = $this->url.'stores'.$this->apiKey;
					$return = $this->call($urlApi, 'get', '');	
					$code = $return->__get('code');
					if ($code == '200') {		
						$body = $return->__get('body');
						if (!empty($body)) {
							foreach ($body as $store) {
								$this->moduleFields['store_id']['option'][$store->id] = $store->name;
							}
						}
					}
				} 
				catch (\Exception $e) {
				} 		
			}
			// Ajout des champ relate au mapping des champs 
			if (!empty($this->fieldsRelate)) {
				$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
			}
			// Si l'extension est demandée alors on vide relate 
			if ($extension) {
				$this->fieldsRelate = array();
			}	
// echo '<pre>';
// print_r($this->moduleFields);	
// die();		
			return $this->moduleFields;
		}
		catch (\Exception $e){
			$error = $e->getMessage();
			return false;
		}
	} // get_module_fields($module)	 
	
	// Read one specific record
	public function read_last($param) {	
// print_r($param);
// return null;	
		$result = array();
		try {
			// No history search for module options_values
			if (in_array($param['module'], array('options_values','products_options'))) {
				$result['done'] = false;					
				return $result;			
			}
			// Add requiered fields 
			$param['fields'] = $this->addRequiredField($param['fields']);	

			// Simulation : we get the last record
			if (empty($param['query'])) {
				$urlApi = $this->url.$param['module'].'/orderby/date_modified/desc/limit/1'.$this->apiKey;	
			}
			// We try to get the history of the record
			elseif (!empty($param['query']['id'])) {
				$urlApi = $this->url.$param['module'].'/'.$param['query']['id'].$this->apiKey;
			}
			// search for duplicate date in the target module
			else {
				// Buid the search query
				$search = '';
				foreach ($param['query'] as $key => $value) {
					$search .= '/filter/'.$key.'/equal/'.urlencode($value);
				}
				$urlApi = $this->url.$param['module'].$search.'/orderby/date_modified/desc/limit/1'.$this->apiKey;
			}
// echo $urlApi;			
			// Try to access to the shop
			$return = $this->call($urlApi, 'get', '');	
			$code = $return->__get('code');
			// If the call is a success
			if ($code == '200') {		
				$body = $return->__get('body');
// print_r($body);			
				if (!empty($body)) {
					// destroy the dimension because we can have only one record
					$body = current($body);			
					foreach ($body as $key => $value) {
						// If the field is requested
						if(in_array($key, $param['fields'])) {			
							$result['values'][$key] = $value;
						}
					}
					$result['done'] = true;
				}
				// If id is in query we should have a result
				elseif (!empty($param['query']['id'])) {
					throw new \Exception('Failed to get the history of the record in the target solution. ');
				}
				else {
					$result['done'] = false;
				}
			}
			else {
				// Get the error message
				$body = $return->__get('body');
				throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
			}			
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;			
		}						
		return $result;
	}
	
	// Read one specific record
	public function read($param) {
		$result['count'] = 0;
		try {
			// Get the reference date
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['rule_mode']);
			
			// Add requiered fields 
			$param['fields'] = $this->addRequiredField($param['fields']);
			
			// We build the url (get all data after the reference date)
			$urlApi = $this->url.$param['module'].'/filter/'.$dateRefField.'/superior/'.urlencode($param['date_ref']).'/orderby/date_created/asc'.$this->apiKey;
// echo $urlApi;			
			// http://test-api.shop-application.com/api/customers/filter/date_created/superior/2000-11-29 00:00:00/orderby/date_created/desc?key=jhkCNgPmRUacE4JqMFe4
// print_r($param);
		
			// Try to access to the shop
			$return = $this->call($urlApi, 'get', '');	
			
			$code = $return->__get('code');
			// If the call is a success
			if ($code == '200') {		
				$body = $return->__get('body');
				if (!empty($body)) {
					// For each record
					foreach ($body as $id => $record) {
						$row = array();
						// For each fields
						foreach ($record as $key => $value) {
							// prepare data (id is always present in $param['fields'] because we have added it via the method addRequiredField
							if(in_array($key, $param['fields'])) {
								$row[$key] = $value;
							}
							if ($key == $dateRefField) {
								$row['date_modified'] = $value;
								// Save the latest reference date
								if (	
										empty($result['date_ref'])
									 || $value > $result['date_ref']
								) {
									$result['date_ref'] = $value;
								}
							}
						}
						$result['values'][$id] = $row;
						$result['count']++;
					}
				}
			}
			else {
				// Get the error message
				$body = $return->__get('body');
				throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
			}		
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';		
		}		 
// print_r($result);
// return null;					
		return $result;
	}

	// Permet de créer un enregistrement
	public function create($param) {
print_r($param);
// return null;			
		// For each record to send
		foreach($param['data'] as $idDoc => $data) {
			try {	
				$dataTosSend = '';
				// Check control before update
				$data = $this->checkDataBeforeCreate($param, $data);
				// Preparation of the post
				$dataTosSendTmp = $this->buildSendingData($param,$data,'C');
// print_r($dataTosSend);			
				// Add a dimension for the webservice
				$dataTosSend[] = $dataTosSendTmp;
				
				// Generate URL
				$urlApi = $this->url.$param['module'].$this->apiKey;
// print_r($param['data']);
print_r($dataTosSend);
return null;	
				// Creation of the record
				$return = $this->call($urlApi, 'post', $dataTosSend);	
				
				// Get the response code
				$code = $return->__get('code');			
				// Get the data from the response
				$body = $return->__get('body');	
// print_r($code);
				// If the call is a success
				if ($code == '200') {
// print_r($body);					
					// Could be in 200 with an error
					if (!empty($body->errors)) {
						throw new \Exception(print_r($body->errors,true));	
					}		
					// The record has been successfully created if the id exist
					if (!empty($body[0]->id)) {
						$result[$idDoc] = array(
												'id' => $body[0]->id,
												'error' => false
										);
// print_r($result);											
						// Set all id from the childs documents in the array $this->docIdList
						$this->getTargetIds($param,$body[0]);
// print_r($this->docIdList);											
						if (!empty($this->docIdList)) {
							$result = array_merge($this->docIdList,$result);
						}
// print_r($result);											
					}
					else  {
						$result[$idDoc] = array(
												'id' => '-1',
												'error' => '01'
										);
					} 
				}
				else {
					// Set the error message
					throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
				}			
			}
			catch (\Exception $e) {
				$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
		} 
// print_r($result);
// return null;			
// print_r($this->docIdList);
		// Change document status
		if (!empty($result)) {
			foreach ($result as $key => $value) {
				$this->updateDocumentStatus($key,$value,$param);	
			}
		}
		return $result;			
	}	
	

	
		// Permet de créer un enregistrement
	public function update($param) {
print_r($param);
// return null;		
		// For each record to send
		foreach($param['data'] as $idDoc => $data) {
// print_r($data);
			try {		
				$dataTosSend = '';
				// Check control before update
				$data = $this->checkDataBeforeUpdate($param, $data);
				// Preparation of the put
				$dataTosSendTmp = $this->buildSendingData($param,$data,'U');

				// Add a dimension for the webservice
				$dataTosSend[] = $dataTosSendTmp;
				// Generate URL
				$urlApi = $this->url.$param['module'].$this->apiKey;
// print_r($urlApi);		
print_r($dataTosSend);		
return null;
				// Creation of the record
				$return = $this->call($urlApi, 'put', $dataTosSend);	
				
				// Get the response code
				$code = $return->__get('code');		
				// Get the data from the response
				$body = $return->__get('body');				
// print_r($code);				
				// If the call is a success
				if ($code == '200') {
// print_r($body);					
					// Could be in 200 with an error
					if (!empty($body->errors)) {
						throw new \Exception(print_r($body->errors,true));	
					}		
					// The record has been successfully created if the id exist
					if (!empty($body[0]->id)) {
						$result[$idDoc] = array(
												'id' => $body[0]->id,
												'error' => false
										);
						// Set all id from the childs documents in the array $this->docIdList
						$this->getTargetIds($param,$body[0]);
						if (!empty($this->docIdList)) {
							$result = array_merge($this->docIdList,$result);
						}
					}
					else  {
						$result[$idDoc] = array(
												'id' => '-1',
												'error' => '01'
										);
					} 
				}
				else {
					// Set the error message
					throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
				}			
			}
			catch (\Exception $e) {
				$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Modification du statut du flux
			// $this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		} 
// print_r($this->docIdList);
// print_r($result);
		// Change document status
		if (!empty($result)) {
			foreach ($result as $key => $value) {
				$this->updateDocumentStatus($key,$value,$param);	
			}
		}
// return null;			
		return $result;			
	}	
	
	// Get the child target id from the response
	protected function getTargetIds($param,$data,$entryName = '') {
		if (!empty($data)) {
			$idDocMyddlewareTemp = '';			
			foreach($data as $key => $value) {				
				if (
						is_array($value)
					 ||	is_object($value)
				) {
					// We don't keep numreric entry name because it is only the index of tab. 
					// Exemple : for module optons_values we want to keep the entry name values
					if (!is_numeric($key)) {
						$newEntryName = $key;
					}
					else {
						$newEntryName  = $entryName;
					}
					// Recursiv call
					$this->getTargetIds($param,$value,$newEntryName);
				}
				// Save the document id
				elseif ($key == 'id_doc_myddleware') {
					// $idDocMyddlewareTemp = $value;	
// print_r($data);	
// echo 'entry_name : '.$entryName.chr(10);				
// print_r($this->arrayKey);
					// We have the entry_name, we search the id name
					foreach ($this->arrayKey[$param['module']] as $subModule) {
// print_r($subModule);	
					
						if ($subModule['entry_name'] == $entryName) {
// echo 'ZZZZ'.chr(10);						
							$this->docIdList[$value] = array(
																	'id' => $data->$subModule['id_name'],
																	'error' => false
															); 	
// print_r($this->docIdList);															
							// break;								
						}
					}
					// if (!empty($data[$this->arrayKey[$param['module']][$entryName]['id_name']]
					// Get the id value (always after the field id_doc_myddleware)
					// $this->docIdList[$idDocMyddlewareTemp] = array(
																	// 'id' => $data[$this->arrayKey[$param['module']][$entryName]['id_name']],
																	// 'error' => false
															// ); 							
				}
				/* 
				elseif (
						!empty($idDocMyddlewareTemp)
					AND (
							$key == 'id'
						 OR substr($key,-3) == '_id'
					)
				) {			
					$this->docIdList[$idDocMyddlewareTemp] = array(
																	'id' => $value,
																	'error' => false
															); 				
				} */
			}
		}
	}
	
		
	// Generate the data to send in the create or update POST
	// Entry_name is the name of the entry in cas the function is call for a child data
	protected function buildSendingData($param,$data,$mode,$entry_name = '',$level = 0) {		
		$first = false;	
		foreach ($data as $key => $value) {	
print_r($data);		
			$fieldStructure = '';
			// Replace __ISO__ if the field contains __ISO__
			if (!empty($param['ruleParams']['language'])) {
				$key = str_replace('__ISO__', '__'.$param['ruleParams']['language'].'__', $key);
			}
			
			// Jump the first value of the table data (contain the document id)
			if (!$first) {
				// Save all doc ID to change their status to send (child and parent document)
				$this->docIdList[$value] = $value;
				$first = true;
				continue;
			}
			// Target id isn't a shop-application field (it is used by Myddleware)
			if ($key == 'target_id') {
				if ($mode == 'U') {					
					// If a specific id exist we get it otherwise we put the default value id
					if (!empty($this->arrayKey[$param['module']][$entry_name]['id_name'])) {
						$dataTosSend[$this->arrayKey[$param['module']][$entry_name]['id_name']] = $value;
					} else {
						$dataTosSend['id'] = $value;
					}
				}
				continue;
			}
			if (is_array($value)) {
				$level++;
				foreach($value as $subrecord) {
					// recursive call in case sub tab exist
					$dataChild = $this->buildSendingData($param,$subrecord,$mode,$key,$level);
echo 'module :'.$param['module'].chr(10);
echo '$key :'.$key	.chr(10);
print_r($this->arrayKey);					
					// If the deep level is greater than the maximu allowed by the module, we merge data into the maximum level 
					if ($level > $this->arrayKey[$param['module']][$key]['max_level']) {
						$dataTosSend = array_merge($dataTosSend, $dataChild);
					} else {
						$dataTosSend[$this->arrayKey[$param['module']][$key]['entry_name']][] = $dataChild;
					}
				}
			} else {		
				// Structure transformation to an array id needed
				$fieldStructure = explode('__',$key);			
				$nbLevel = count($fieldStructure);
				if ($nbLevel == 3) {
					$dataTosSend[$fieldStructure[0]][$fieldStructure[1]][$fieldStructure[2]] = $value;
				}
				elseif ($nbLevel == 2) {
					$dataTosSend[$fieldStructure[0]][$fieldStructure[1]] = $value;
				} else {		
					$dataTosSend[$key] = $value;
				}
			}
		}
		return $dataTosSend;
	}
	
	protected function myExplode($value) {
		$fieldStructure = explode('__',$value);
		if (is_array($fieldStructure)) {
			$value = $this->myExplode($fieldStructure);
		}
		return $value;
	}
	
	
	// Force some module in child
	public function getFieldsParamUpd($type, $module, $myddlewareSession) {	
		$params = array();
		try {	
			if ($type == 'target') {			
				// If language is required for the module
				if (in_array($module,$this->moduleWithLanguage)	){
					// Get languages
					$urlApi = $this->url.'languages'.$this->apiKey;
					$return = $this->call($urlApi, 'get', '');	
					$code = $return->__get('code');
					if ($code == '200') {		
						$body = $return->__get('body');
						if (!empty($body)) {
							$idParam = array(
										'id' => 'language',
										'name' => 'language',
										'type' => 'option',
										'label' => 'Language',
										'required'	=> true
									);
							foreach ($body as $language) {		
								$idParam['option'][$language->code] = $language->name;
							}
							$params[] = $idParam;
						}
					} 		
				}
			}
		}
		catch (\Exception $e) {
		}			
		return $params;
	}
	
	// Return the filed reference
	public function getDateRefName($moduleSource, $ruleMode) {
		if ($ruleMode == '0') {
			return 'date_modified';
		} elseif ($ruleMode == 'C'){
			return 'date_created';
		} else {
			throw new \Exception ("Rule mode $RuleMode unknown.");
		}
		return null;
	}
	
	protected function call($url, $method = 'get', $data=array()){	
		if (function_exists('curl_init') && function_exists('curl_setopt')) {
			$response = \Unirest::$method(
				$url, // URL de destination
				array('Accept'=>'application/json'), // Type des données envoyées
				json_encode($data) // On encode nos données en JSON
			);
			return $response;
        }
        throw new \Exception('curl extension is missing!');
    }	
}// class shopappcore

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/shopapplication.php';
if(file_exists($file)){
	require_once($file);
}
else {
	class shopapplication extends shopapplicationcore {
	}
}