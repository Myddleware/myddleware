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

namespace App\Solutions;

use App\Solutions\salesforce;
use App\Solutions\solution;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class salesforce_v2 extends salesforce {					  
			
	// Parameters list to login to Salesforce
	public function getFieldsLogin(): array
    {
        return array(
                    array(
                            'name' => 'url',
                            'type' => TextType::class,
                            'label' => 'solution.fields.url'
                        ),
                    array(
                            'name' => 'consumerkey',
                            'type' => PasswordType::class,
                            'label' => 'solution.fields.consumerkey'
                        ),
                    array(
                            'name' => 'consumersecret',
                            'type' => PasswordType::class,
                            'label' => 'solution.fields.consumersecret'
                        )
        );
	}
	
	// Redefine the method to connect Salesforce (grant_type client_credentials)
    public function login($paramConnexion) {
		// Call the grand parent
		solution::login($paramConnexion);	
		try {		
		    $post_fields = array(
		        'grant_type' => 'client_credentials',
		        'client_id' => $this->paramConnexion['consumerkey'],
		        'client_secret' => $this->paramConnexion['consumersecret'],
		    );
 			$token_request_data = $this->call($this->paramConnexion['url'].'/services/oauth2/token', $post_fields);
		    if (!isset($token_request_data['access_token'])||
		        !isset($token_request_data['instance_url'])){
				throw new \Exception("Missing expected data from ".print_r($token_request_data, true));
		    } else {
			    $this->setAccessToken($token_request_data['access_token']);
			    $this->instance_url = $token_request_data['instance_url'];
				$this->connexion_valide = true;
		    }
		}
		catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
		}
	}

    // Build the direct link to the record (used in data transfer view)
    public function getDirectLink($rule, $document, $type): string
    {
        // Get url, module and record ID depending on the type
        if ('source' == $type) {
            $url = $this->getConnectorParam($rule->getConnectorSource(), 'url');
            $recordId = $document->getSource();
        } else {
            $url = $this->getConnectorParam($rule->getConnectorTarget(), 'url');
            $recordId = $document->gettarget();
        }
		return $url.'/'.$recordId;
    }
	
	/**
     * @throws \Exception
     */
    protected function call($url, $parameters, $method = null){
		ob_start();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // important (testé en Local wamp) afin de ne pas vérifier le certificat SSL
		if($parameters === false){ // Si l'appel ne possède pas de paramètres, on exécute un GET en curl
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$this->getAccessToken()));
		} else {
			// No Authorization in case of login action
			if(!isset($parameters['grant_type'])) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $this->getAccessToken(), "Content-type: application/json"));
			}
			// PATCH or DELETE
			if (!empty($method)) { 
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			// POST
			} else {	
				curl_setopt($ch, CURLOPT_POST, TRUE);
			}
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		}
		
		$query_request_body = curl_exec($ch);	 
		// Si on est sur un update et que l'on a un retour 204 on renvoie true
		if (!empty($method)) {		
			if(curl_getinfo($ch, CURLINFO_HTTP_CODE) == '204') {
				return true;
			}
		}
		
		if(curl_error($ch)) throw new \Exception("Call failed: " . curl_error($ch));
		
	    $query_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (($query_response_code<200)||($query_response_code>=300)||empty($query_request_body)){
             $query_request_data = json_decode($query_request_body, true);	
			if(
					!empty($query_request_data['hasErrors'])
				 &&	$query_request_data['hasErrors'] == true
			) {
				return $query_request_data;
			} elseif(isset($query_request_data['error_description'])) {
            	throw new \Exception(ucfirst($query_request_data['error_description']));
			} else {
				throw new \Exception("Call failed - " . $query_response_code . ' ' . $query_request_body);
			}
        }

	    $query_request_data = json_decode($query_request_body, true);
	    if (empty($query_request_data))
	        throw new \Exception("Couldn't decode '$query_request_data' as a JSON object");

		curl_close($ch);
		ob_end_flush();
		return $query_request_data;	
    }
}
