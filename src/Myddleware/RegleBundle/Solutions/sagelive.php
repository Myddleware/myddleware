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

class sagelivecore extends solution {
	protected $tokenUrl = 'https://login.salesforce.com/services/oauth2/token';

	// Liste des paramètres de connexion
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
					),
                    array(
                            'name' => 'consumerkey',
                            'type' => 'password',
                            'label' => 'solution.fields.consumerkey'
					),
                    array(
                            'name' => 'consumersecret',
                            'type' => 'password',
                            'label' => 'solution.fields.consumersecret'
					)
        );
	} // getFieldsLogin()
	
	// Connect to SageLive
    public function login($paramConnexion) {
		parent::login($paramConnexion);	
		try {
			$post_fields = array(
		        'grant_type' => 'password',
		        'client_id' => $this->paramConnexion['consumerkey'],
		        'client_secret' => $this->paramConnexion['consumersecret'],
		        'username' => $this->paramConnexion['login'],
		        'password' => $this->paramConnexion['password']
		    );
			// Send the request to SageLive
 			$token_request_data = $this->call($this->tokenUrl, $post_fields);
			// Connection OK if the accessToken exists
		    if (!isset($token_request_data['access_token'])||
		        !isset($token_request_data['instance_url'])){
				throw new \Exception("Missing expected data from ".print_r($token_request_data, true));
		    } else {
			    $this->access_token = $token_request_data['access_token'];
			    $this->instance_url = $token_request_data['instance_url'];
				$this->connexion_valide = true;
		    }
		}
		catch (\Exception $e) {
			$error = 'Failed to login to Sagelive : '.$e->getMessage();
			echo $error . ';';
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)
	
	
	
	
	
		// Fonction permettant de faire l'appel REST
	protected function call($url, $parameters, $update = false){	
		ob_start();
		$ch = curl_init();
		if($parameters === false){ // Si l'appel ne possède pas de paramètres, on exécute un GET en curl
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth '.$this->access_token));
		} 
		elseif ($update === false) { // Si l'appel en revanche possède des paramètres dans $parameters, on exécute un POST en curl
		    curl_setopt($ch, CURLOPT_URL, $url);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			if(!isset($parameters['grant_type'])) // A ne pas ajouter pour la connexion
		    	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: OAuth " . $this->access_token, "Content-type: application/json"));
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // important (testé en Local wamp) afin de ne pas vérifier le certificat SSL
		    curl_setopt($ch, CURLOPT_POST, TRUE);
		    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		}
		else {
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // important (testé en Local wamp) afin de ne pas vérifier le certificat SSL
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: OAuth " . $this->access_token, "Content-type: application/json"));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		}
		
		$query_request_body = curl_exec($ch);	 
		// Si on est sur un update et que l'on a un retour 204 on renvoie true
		if ($update === true) {		
			if(curl_getinfo($ch, CURLINFO_HTTP_CODE) == '204') {
				return true;
			}
		}
		
		if(curl_error($ch)) throw new \Exception("Call failed: " . curl_error($ch));
		
	    $query_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (($query_response_code<200)||($query_response_code>=300)||empty($query_request_body)){
             $query_request_data = json_decode($query_request_body, true);
			if(isset($query_request_data['error_description']))
            	throw new \Exception(ucfirst($query_request_data['error_description']));
			else
				throw new \Exception("Call failed - " . $query_response_code . ' ' . $query_request_body);
        }

	    $query_request_data = json_decode($query_request_body, true);
	    if (empty($query_request_data))
	        throw new \Exception("Couldn't decode '$query_request_data' as a JSON object");

		curl_close($ch);
		ob_end_flush();
		return $query_request_data;	
    } // call($method, $parameters)
    
}// class sagelivecore

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/sagelive.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class sagelive extends sagelivecore {
		
	}
} 