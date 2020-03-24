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

class facebookcore  extends solution {
	
	protected $baseUrl = 'https://graph.facebook.com';
	
	public function getFieldsLogin() {	
		return array(
					array(
							'name' => 'clientid',
							// 'type' => PasswordType::class,
							'type' => TextType::class,
							'label' => 'solution.fields.clientid'
						),
					array(
							'name' => 'clientsecret',
							// 'type' => PasswordType::class,
							'type' => TextType::class,
							'label' => 'solution.fields.clientsecret'
						)
		);
	}
	
	public function login($paramConnexion) {

		parent::login($paramConnexion);
		$fb = new \Facebook\Facebook([
		  'app_id' => $this->paramConnexion['clientid'],          
		  'app_secret' => $this->paramConnexion['clientsecret'],  
		  'graph_api_version' => 'v5.0',
		]);

// curl -X GET "https://graph.facebook.com/oauth/access_token ?client_id={your-app-id} &client_secret={your-app-secret} &grant_type=client_credentials"
		try {
		   
			// Get your UserNode object, replace {access-token} with your token
			$response = $fb->get('/me', 'EAACZBvvM1kpoBAPucZCnP42mZAo2wO8jCNHii55k8DHMnHdXpZC6qNCHynOK7KixzeuKS9KZCjXwW1RxrZCZB9OV2LZBBdZCk54DedVD3ZBZBTnLMQ4ZAch5CpAseMzkAhh4EwJY7ah7vVKkvfKBEIZBZApKTggyDAoZAsYcwpfUxA9OyWmVg52KaQqKYZAiOuDTgze0srBzwYTChGnvEzJD153497xlAOdNItEH9ZB9lBE1s7syzfwZDZD');

		} catch(\Facebook\Exceptions\FacebookResponseException $e) {
			// Returns Graph API errors when they occur
			  echo 'Graph returned an error: ' . $e->getMessage();
			  exit;
		} catch(\Facebook\Exceptions\FacebookSDKException $e) {
			// Returns SDK errors when validation fails or other local issues
			  echo 'Facebook SDK returned an error: ' . $e->getMessage();
			  exit;
		}

		$me = $response->getGraphUser();

	   //All that is returned in the response
		echo 'All the data returned from the Facebook server: ' . $me;

	   //Print out my name
		echo 'My name is ' . $me->getName();
		
		// $this->connexion_valide = true;
		// return true;
    }
	

	

	
}

/* * * * * * * *  * * * * * *  * * * * * * 
	Includ custom class if exists
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/facebook.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class facebook extends facebookcore {
		
	}
}