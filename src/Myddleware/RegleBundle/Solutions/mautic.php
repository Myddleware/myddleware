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
use Mautic\Auth\ApiAuth;

class mauticcore  extends solution {
	
	// Enable to read deletion and to delete data
	protected $sendDeletion = true;	
	
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
	
	
    public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {

			require('lib/mautic/MauticApi.php');	
			// ApiAuth->newAuth() will accept an array of Auth settings
			$settings = array(
				'userName'   => 'admin',             // Create a new user       
				'password'   => 'Recette?1'              // Make it a secure password
			);

			// Initiate the auth object specifying to use BasicAuth
			$initAuth = new ApiAuth();
			$auth = $initAuth->newAuth($settings, 'BasicAuth');

throw new \Exception('Please check url'.print_r($auth));			
			
			// Nothing else to do ... It's ready to use.
// Just pass the auth object to the API context you are creating.
			/* $login_paramaters = array( 
			'user_auth' => array( 
				'user_name' => $this->paramConnexion['login'], 
				'password' => md5($this->paramConnexion['password']), 
				'version' => '.01' 
			), 
			'application_name' => 'myddleware',
			); 
			// remove index.php in the url
			$this->paramConnexion['url'] = str_replace('index.php', '', $this->paramConnexion['url']);
			// Add the suffix with rest parameters to the url
			$this->paramConnexion['url'] .= $this->urlSuffix;

			$result = $this->call('login',$login_paramaters,$this->paramConnexion['url']); 
			
			if($result != false) {
				if ( empty($result->id) ) {
				   throw new \Exception($result->description);
				}
				else {
					$this->session = $result->id;
					$this->connexion_valide = true;
				}				
			}
			else {
				throw new \Exception('Please check url');
			} */
		} 
		catch (\Exception $e) {
			$error = $e->getMessage();
			$this->logger->error($error);
			return array('error' => $error);
		} 
    }
	
	

	//function to make cURL request	
	protected function call($method, $parameters){
		try {
			ob_start();
			$curl_request = curl_init();
			curl_setopt($curl_request, CURLOPT_URL, $this->paramConnexion['url']);
			curl_setopt($curl_request, CURLOPT_POST, 1);	
			curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);	
			curl_setopt($curl_request, CURLOPT_HEADER, 1);
			curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);
		
			$jsonEncodedData = json_encode($parameters);
			$post = array(
				"method" => $method,
				"input_type" => "JSON",
				"response_type" => "JSON",
				"rest_data" => $jsonEncodedData
			);
		
			curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post);
			$result = curl_exec($curl_request);
			curl_close($curl_request);
			if(empty($result))	return false;
			$result = explode("\r\n\r\n", $result, 2);
			$response = json_decode($result[1]);
			ob_end_flush();
	
			return $response;			
		}
		catch(\Exception $e) {
			return false;	
		}	
    }	
	
}

/* * * * * * * *  * * * * * *  * * * * * * 
if a custom file exists we include it
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/mautic.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class mautic extends mauticcore {
		
	}
}