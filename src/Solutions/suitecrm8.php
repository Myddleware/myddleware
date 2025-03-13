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

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class suitecrm8 extends solution
{
	protected $accessToken;
	protected $baseUrl;
	protected $componentUrl = '/Api/';
	
	public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
			$this->baseUrl = rtrim($this->paramConnexion['url'], '/').$this->componentUrl;
			// Authentication - Begin
			$ch = curl_init();
			$header = array(
				'Content-type: application/vnd.api+json',
				'Accept: application/vnd.api+json'
			 );
			$postStr = json_encode(array(
				'grant_type' => 'client_credentials',
				'client_id' => $this->paramConnexion['clientid'],
				'client_secret' => $this->paramConnexion['clientsecret'],
			));
			curl_setopt($ch, CURLOPT_URL, $this->baseUrl.'access_token');
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			$output = curl_exec($ch);
			$authOut = json_decode($output,true);
			// Manage return : token or error
			if (curl_errno($ch)) {
				throw new \Exception('cURL error: ' . curl_error($ch));
			}
			if (!empty($authOut['message'])) {
				throw new \Exception($authOut['message']);
			}
			if (!empty($authOut['access_token'])) {
				$this->accessToken = $authOut['access_token'];
				$this->connexion_valide = true;
			} else {
				throw new \Exception('Failed to connect to SuiteCRM. No error message returned.');
			}
        } catch (\Exception $e) {
            $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

	public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
            [
                'name' => 'clientid',
                'type' => TextType::class,
                'label' => 'solution.fields.clientid',
            ],
            [
                'name' => 'clientsecret',
                'type' => PasswordType::class,
                'label' => 'solution.fields.clientsecret',
            ],
        ];
    }
	
	
    // Permet de récupérer tous les modules accessibles à l'utilisateur
    public function get_modules($type = 'source')
    {
        try {
			$moduleData = $this->call('GET', 'V8/meta/modules'); 
// $moduleData = $this->call('GET', 'V8/module/ProspectLists'); 
// return $moduleData;
			if (!empty($moduleData['data']['attributes'])) {
				foreach($moduleData['data']['attributes'] as $key => $module) {
					$modules[$key] = $module['label'];
				}
			}
			return (isset($modules)) ? $modules : false;
        } catch (\Exception $e) {
            return false;
        }
    }
	
	// Call to SuiteCRM API
	protected function call($method, $suffixUrl, $parameters=array())
    {
        try {
			$ch = curl_init();
			$header = array(
				'Accept: application/vnd.api+json',
				'authorization: Bearer '.$this->accessToken,
				'Content-type: application/vnd.api+json'
			);
			
			curl_setopt($ch, CURLOPT_URL, $this->baseUrl.$suffixUrl);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			if (!empty($parameters)) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			
			if (curl_errno($ch)) {
				throw new \Exception('cURL error: ' . curl_error($ch));
			}
			$output = curl_exec($ch);
			$response = json_decode($output,true);
			
			return $response;
        } catch (\Exception $e) {
            return false;
        }
    }

}
