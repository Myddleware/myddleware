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
use Symfony\Component\HttpFoundation\Session\Session;

class hubspotcore  extends solution { 

	protected $url = 'https://api.hubapi.com/';
	protected $version = 'v1';
	/* protected $token;
	protected $update;
	protected $organizationTimezoneOffset;
	
	protected $required_fields = array('default' => array('Id','CreationDate','ModificationDate'));
	
	protected $FieldsDuplicate = array(	'Contact' => array('Email','Name'),
										'default' => array('Name')
									  ); */

	public function getFieldsLogin() {	
		return array(
					array(
							'name' => 'apikey',
							'type' => 'password',
							'label' => 'solution.fields.apikey'
						)
		);
	}
 	
	// Conect to Hubspot
    public function login($paramConnexion) {
		parent::login($paramConnexion);
		try{	
			// $this->paramConnexion['apikey'] = 'f91a946d-701e-4a0d-acdf-c5204c556901';
			$result = $this->call($this->url.'properties/'.$this->version.'/contacts/properties?hapikey='.$this->paramConnexion['apikey']);				
			if (!empty($result['message'])) {
				throw new \Exception($result['message']);
			}
			elseif (empty($result)) {
				throw new \Exception('Failed to connect but no error returned by Hubspot. ');
			}
			$this->connexion_valide = true;	
		}
		catch (\Exception $e) {
			$error = 'Failed to login to Hubspot : '.$e->getMessage();
			echo $error . ';';
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)*/

	
	public function get_modules($type = 'source') {
		return array(	
					'companies' => 'Companies',
					'contacts' 	=> 'Contacts',
					'deals' 	=> 'Deals',
				);	
	} // get_modules()	
	
		// Renvoie les champs du module passé en paramètre
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try {
			$result = $this->call($this->url.'properties/'.$this->version.'/'.$module.'/properties?hapikey='.$this->paramConnexion['apikey']);		
			if (!empty($result['message'])) {
				throw new \Exception($result['message']);
			}
			elseif (empty($result)) {
				throw new \Exception('No fields returned by Hubspot. ');
			}
			// Add each field in the right list (relate fields or normal fields)
			foreach($result as $field) {
				// Field not editable can't be display on the target side
				if (
						!empty($field['readOnlyValue'])
					AND $type == 'target'
				){
					continue;
				}
				// If the fields is a relationship
				if (substr($field['name'],-2) == 'id') {
					$this->fieldsRelate[$field['name']] = array(
												'label' => $field['label'],
												'type' => 'varchar(36)',
												'type_bdd' => 'varchar(36)',
												'required' => 0,
												'required_relationship' => 0,
											);
				} 							
				$this->moduleFields[$field['name']] = array(
											'label' => $field['label'],
											'type' => $field['type'],
											'type_bdd' => $field['type'],
											'required' => 0
										);
				// Add list of values
				if (!empty($field['options'])) {
					foreach($field['options'] as $value) {
						$this->moduleFields[$field['name']]['option'][$value['value']] = $value['label'];
					}
				}
			}			
			return $this->moduleFields;
		}
		catch (\Exception $e){
			$error = $e->getMessage();
			return false;
		}
	} // get_module_fields($module)	 
	

	
	/**
     * Performs the underlying HTTP request. Not very exciting
     * @param  string $method The API method to be called
     * @param  array  $args   Assoc array of parameters to be passed
     * @return array          Assoc array of decoded result
     */   
    protected function call($url, $method = 'GET', $args=array(), $timeout = 120){   
		if (function_exists('curl_init') && function_exists('curl_setopt')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method );
			$headers = array();
			$headers[] = "Content-Type: application/json";
			if (!empty($this->token)) {	
				$headers[] = "Authorization: Bearer ".$this->token;
			}
			if (!empty($args)) {
				$jsonArgs = json_encode($args);
				$headers[] = "Content-Lenght: ".$jsonArgs;
				curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonArgs);			
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
            $result = curl_exec($ch);
            curl_close($ch);            
            return $result ? json_decode($result, true) : false;
        }
        throw new \Exception('curl extension is missing!');
    }	
	
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/hubspot.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class hubspot extends hubspotcore {
		
	}
}