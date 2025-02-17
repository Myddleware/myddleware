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

class acton extends solution
{
	protected $accessToken;
	protected $refreshToken;
	
	public function getFieldsLogin(): array
    {
        return [
			[
                'name' => 'login',
                'type' => TextType::class,
                'label' => 'solution.fields.login',
            ],
            [
                'name' => 'password',
                'type' => PasswordType::class,
                'label' => 'solution.fields.password',
            ],
            [
                'name' => 'clientid',
                'type' => PasswordType::class,
                'label' => 'solution.fields.clientid',
            ],
			[
                'name' => 'clientsecret',
                'type' => PasswordType::class,
                'label' => 'solution.fields.clientsecret',
            ],
        ];
    } 
	
	// Login to Act-On
	public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
			$client = new \GuzzleHttp\Client();
			// Build parameters
			$parameters = [
			  'form_params' => [
				'grant_type' => 'password',
				'username' => $this->paramConnexion['login'],
				'password' => $this->paramConnexion['password'],
				'client_id' => $this->paramConnexion['clientid'],
				'client_secret' => $this->paramConnexion['clientsecret'],
			  ],
			  'headers' => [
				'accept' => 'application/json',
				'content-type' => 'application/x-www-form-urlencoded',
			  ],
			];
			// Save tokens
			$response = $client->request('POST', 'https://api.actonsoftware.com/token', $parameters);
			$tokens = json_decode($response->getBody());
			if (!empty($tokens->refresh_token)) {
				$this->accessToken = $tokens->refresh_token;
			}
			if (!empty($tokens->access_token)) {
				$this->accessToken = $tokens->access_token;
				$this->connexion_valide = true;
			} else {
				throw new \Exception('No exception during the connextion to Act-On but the token is empty.');
			}
        } catch (\Exception $e) {
            $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }
	
	
}

