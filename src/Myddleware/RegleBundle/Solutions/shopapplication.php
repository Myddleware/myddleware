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
	
	protected $required_fields = array('default' => array('id','date_modified','date_created'));
	protected $FieldsDuplicate = array('customers' => array('email'));
	
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
			'address' => 'Address',
			'orders' => 'Order',
			'products' => 'Products',
		);

	} // get_modules()	
	 
	// Renvoie les champs du module passé en paramètre
	public function get_module_fields($module, $type = 'source', $extension = false) {
		parent::get_module_fields($module, $type, $extension);
		try{
			// Pour chaque module, traitement différent
			switch ($module) {
				case 'customers':
					$this->moduleFields = array(
						'id' => array('label' => 'ID customer', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'gender' => array('label' => 'Gender', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('m' => 'Homme', 'f' => 'Femme')),
						'first_name' => array('label' => 'First name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'last_name' => array('label' => 'Last_name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'email' => array('label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'birth_date' => array('label' => 'Birth date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'phone' => array('label' => 'Phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'fax' => array('label' => 'Fax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'discount' => array('label' => 'Discount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'credit' => array('label' => 'Credit', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'credit_expiration_date"' => array('label' => 'Credit expiration date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'reward_points"' => array('label' => 'Reward points', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'password' => array('label' => 'Password', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'date_created' => array('label' => 'Date created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'auto_connect_url' => array('label' => 'Auto connect url', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'photo' => array('label' => 'Photo', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					);
					$this->fieldsRelate = array(
						'store_id' => array('label' => 'Store ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						'group_id' => array('label' => 'Group ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						'default_address_id' => array('label' => 'Default address id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0)
					);
					break;
				case 'address':			
					$this->moduleFields = array(
						'id' => array('label' => 'ID customer', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'company' => array('label' => 'Company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'vat_number' => array('label' => 'Vat_number', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'gender' => array('label' => 'Gender', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('m' => 'Homme', 'f' => 'Femme')),
						'first_name' => array('label' => 'First name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'last_name' => array('label' => 'Last_name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'phone' => array('label' => 'Phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'street' => array('label' => 'Street', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'suburb' => array('label' => 'Suburb', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'doorcode' => array('label' => 'Door code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'floor' => array('label' => 'Floor', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'postcode' => array('label' => 'Postcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'city' => array('label' => 'City', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'state"' => array('label' => 'State', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'department' => array('label' => 'Department', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'region' => array('label' => 'Region', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'latitude' => array('label' => 'Latitude', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'longitude' => array('label' => 'Longitude', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'country_id' => array('label' => 'Country ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					);
					$this->fieldsRelate = array(
						'customers_id' => array('label' => 'Customer ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0)
					);
					break;
				default:
					throw new \Exception("Module ".$module." unknown.");
					break;
			}
			// Ajout des champ relate au mapping des champs 
			if (!empty($this->fieldsRelate)) {
				$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
			}
			// Si l'extension est demandée alors on vide relate 
			if ($extension) {
				$this->fieldsRelate = array();
			}
			return $this->moduleFields;
		}
		catch (\Exception $e){
			$error = $e->getMessage();
			return false;
		}
	} // get_module_fields($module)	 
	
	// Read one specific record
	public function read_last($param) {
		$result = array();	
		try {
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
// print_r($param);
// return null;		
		// For each record to send
		foreach($param['data'] as $idDoc => $data) {
			try {		
				// Check control before update
				$data = $this->checkDataBeforeCreate($param, $data);
				$first = false;
				// Preparation of the post
				$dataTosSendTmp = array();
				// For each fields
				foreach ($data as $key => $value) {				
					// Jump the first value of the table data (contain the document id)
					if (!$first) {
						$first = true;
						continue;
					}
					// Target id isn't a shop-application field (it is used by Myddleware)
					if ($key == 'target_id') {
						continue;
					}
					$dataTosSendTmp[$key] = $value;
				}
				// Add a dimension for the webservice
				$dataTosSend[] = $dataTosSendTmp;
				
				// Generate URL
				$urlApi = $this->url.$param['module'].$this->apiKey;
		
				// Creation of the record
				$return = $this->call($urlApi, 'post', $dataTosSend);	
				
				// Get the response code
				$code = $return->__get('code');			
				// If the call is a success
				if ($code == '200') {
					// Get the data from the response
					$body = $return->__get('body');				
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
					}
					else  {
						$result[$idDoc] = array(
												'id' => '-1',
												'error' => '01'
										);
					} 
				}
				else {
					// Get the error message
					$body = $return->__get('body');
					throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
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
// print_r($result);
// return null;			
		return $result;			
	}	
	
		// Permet de créer un enregistrement
	public function update($param) {
// print_r($param);
// return null;		
		// For each record to send
		foreach($param['data'] as $idDoc => $data) {
			try {		
				// Check control before update
				$data = $this->checkDataBeforeCreate($param, $data);
				$first = false;
				// Preparation of the post
				$dataTosSendTmp = array();
				// For each fields
				foreach ($data as $key => $value) {				
					// Jump the first value of the table data (contain the document id)
					if (!$first) {
						$first = true;
						continue;
					}
					// Target id contains te id of teh record in the target application
					if ($key == 'target_id') {
						$dataTosSendTmp['id'] = $value;
						continue;
					}
					$dataTosSendTmp[$key] = $value;
				}
				// Add a dimension for the webservice
				$dataTosSend[] = $dataTosSendTmp;
				// Generate URL
				$urlApi = $this->url.$param['module'].$this->apiKey;
		
				// Creation of the record
				$return = $this->call($urlApi, 'put', $dataTosSend);	
				
				// Get the response code
				$code = $return->__get('code');			
				// If the call is a success
				if ($code == '200') {
					// Get the data from the response
					$body = $return->__get('body');				
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
					}
					else  {
						$result[$idDoc] = array(
												'id' => '-1',
												'error' => '01'
										);
					} 
				}
				else {
					// Get the error message
					$body = $return->__get('body');
					throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
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
print_r($result);
return null;			
		return $result;			
	}	
	
	// Force some module in child
	public function getFieldsParamUpd($type, $module) {	
		try {
			if (
					$type == 'target'
				&& in_array($module,array('address'))	
			){
				$groupParam = array(
							'id' => 'group',
							'name' => 'Group',
							'type' => 'option',
							'label' => 'Group',
							'required'	=> true,
							'option'	=> array('child' => 'child')
						);
				$params[] = $groupParam;
			}			
			return $params;
		}
		catch (\Exception $e){
			return array();
		}
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