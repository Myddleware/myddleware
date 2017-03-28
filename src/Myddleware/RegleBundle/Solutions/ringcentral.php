<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
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

require_once('lib/ringcentral/SDK.php');

class ringcentralcore  extends solution { 
	
	protected $server = 'https://platform.devtest.ringcentral.com';

	public function getFieldsLogin() {	
		return array(
					array(
                            'name' => 'username',
                            'type' => 'text',
                            'label' => 'solution.fields.username'
                        ),
					array(
                            'name' => 'password',
                            'type' => 'password',
                            'label' => 'solution.fields.password'
                        ),
					array(
                            'name' => 'apikey',
                            'type' => 'password',
                            'label' => 'solution.fields.apikey'
                        ),
					array(
                            'name' => 'apikeysecret',
                            'type' => 'password',
                            'label' => 'solution.fields.apikeysecret'
                        )
		);
	}
	
 	public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
		
		// $response =  new \SDK($credentials['appKey'], $credentials['appSecret'], $credentials['server'], 'Demo', '1.0.0');
		$response =  new \SDK( $this->paramConnexion['apikey'], $this->paramConnexion['apikeysecret'], $this->server, 'Demo', '1.0.0');
throw new \Exception('test      '.print_r($response,true).'      '.print_r($this->paramConnexion,true));		
			
				$this->connexion_valide = true; 
		}
		catch (\Exception $e) {
			$error = 'Failed to login to Mailchimp : '.$e->getMessage();
			echo $error . ';';
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)
 
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/ringcentral.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class ringcentral extends ringcentralcore {
		
	}
}