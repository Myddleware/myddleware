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
					$modules[$module] = $label; 
				}
			}
			return $modules;  	
	    }
		catch (\Exception $e) {
			return false;
		}
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