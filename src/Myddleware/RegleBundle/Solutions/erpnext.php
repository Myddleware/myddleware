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
use Symfony\Component\Filesystem\Filesystem;

class erpnextcore  extends solution { 

	// protected $url = 'https://www.cirrus-shield.net/RestApi/';
	protected $token;
	protected $update;
	protected $organizationTimezoneOffset;
	protected $limitCall = 100;
	
	protected $required_fields = array('default' => array('Id','CreationDate','ModificationDate'));
	
	protected $FieldsDuplicate = array(	'Contact' => array('Email','Name'),
										'default' => array('Name')
									  );

	public function getFieldsLogin() {	
		return array(
					array(
							'name' => 'url',
							'type' => 'text',
							'label' => 'solution.fields.url'
						),
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
			$parameters = array("usr" => $this->paramConnexion['login'],
								"pwd" => $this->paramConnexion['password']
								);
			$url = $this->paramConnexion['url'].'/api/method/login';
			// Connect to ERPNext
			$result = $this->call($url,'GET',$parameters);		
			
			if (empty($result->message)) {
				throw new \Exception('Login error');	
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
			$url = $this->paramConnexion['url'].'/api/resource/DocType';
			$parameters = array("limit_page_length" => 1000);
			$APImodules = $this->call($url,'GET',$parameters);		
			if (!empty($APImodules->data)) {
				foreach($APImodules->data as $APImodule) {
					$modules[$APImodule->name] = $APImodule->name;
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
			// Get the list field for a module
			$url = $this->paramConnexion['url'].'/api/method/frappe.client.get_list?doctype=DocField&parent='.$module.'&fields=*&filters={%22parent%22:%22'.$module.'%22}&limit_page_length=500';
			$recordList = $this->call($url,'GET','');	
			
			// Format outpput data
			if (!empty($recordList->message)) {
				foreach($recordList->message as $field) {
					if (empty( $field->label)) {
						continue;
					}
					if ($field->fieldtype == 'Link') {
						$this->fieldsRelate[$field->fieldname] = array(
																'label' => $field->label,
																'type' => 'varchar(255)',
																'type_bdd' => 'varchar(255)',
																'required' => '',
																'required_relationship' => '',
															);
					} else {
						$this->moduleFields[$field->fieldname] = array(
																'label' => $field->label,
																'type' => 'varchar(255)',
																'type_bdd' => 'varchar(255)',
																'required' => '',
															);
						if(!empty($field->options)) {
							$options = explode(chr(10),$field->options);
							if (
									!empty($options)
								AND count($options) > 1
							) {
								foreach ($options as $option) {
									$this->moduleFields[$field->fieldname]['option'][$option] = $option;
								}
							}
						}
					}
				}
			} else {
				throw new \Exception('No data in the module '.$module.'. Failed to get the field list.');
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
	
	protected function call($url, $method = 'GET', $parameters=array(), $timeout = 300){   
		if (!function_exists('curl_init') OR !function_exists('curl_setopt')) {
			throw new \Exception('curl extension is missing!');
		}
		$fileTmp = $this->container->getParameter('kernel.cache_dir') . '/myddleware/solutions/erpnext/erpnext.txt';	
		$fs = new Filesystem();
		try {
			$fs->mkdir(dirname($fileTmp));
		} catch (IOException $e) {
			throw new \Exception ($this->tools->getTranslation(array('messages', 'rule', 'failed_create_directory')));
		}		
		$ch = curl_init($url);
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $parameters);
		curl_setopt($ch,CURLOPT_CUSTOMREQUEST, $method);
		// common description bellow
		curl_setopt($ch,CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch,CURLOPT_COOKIEJAR, $fileTmp);
		curl_setopt($ch,CURLOPT_COOKIEFILE, $fileTmp);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		// curl_setopt($ch,CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch,CURLOPT_TIMEOUT, $timeout);
		$response = curl_exec($ch);		
		
		$header = curl_getinfo($ch);
		// 200? 404? or something?
		$error_no = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);
		if($error_no!=200){
		  // do something for login error
		  // return or exit
		}
		$body = json_decode($response);
		if(JSON_ERROR_NONE == json_last_error()){
		  // $response is not valid (as JSON)
		  // do something for login error
		  // return or exit
		}
		return json_decode($response);
    }
 
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/erpnext.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class erpnext extends erpnextcore {
		
	}
}