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

class acton extends solution
{
	protected $proxy = 'http://srvproxy.iruworld.org:8080';
	protected $token;
	
	public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
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
	
	public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
			
			
			$client = new \GuzzleHttp\Client();

			$response = $client->request('POST', 'https://api.actonsoftware.com/token', [
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
			]);

			// echo $response->getBody();
			
			
			// $config = [
				// 'grant_type'      => 'password',
				// 'username'        => $this->paramConnexion['login'],
				// 'password'        => $this->paramConnexion['password'],
				// 'client_id'       => $this->paramConnexion['clientid'],
				// 'client_secret'   => $this->paramConnexion['clientsecret'],
			// ];

			// $curl = curl_init();
			// curl_setopt_array($curl,
				// [
					// CURLOPT_URL => $this->paramConnexion['url'] . '/token',
					// CURLOPT_RETURNTRANSFER => true,
					// CURLOPT_ENCODING => '',
					// CURLOPT_MAXREDIRS => 10,
					// CURLOPT_TIMEOUT => 0,
					// CURLOPT_FOLLOWLOCATION => true,
					// CURLOPT_VERBOSE => true,
					// CURLOPT_PROXY => $this->proxy,
					// CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					// CURLOPT_CUSTOMREQUEST => 'POST',
					// CURLOPT_POSTFIELDS => http_build_query($config),
				// ]
			// );

			// $response = json_decode(curl_exec($curl),true);

			
            if (!empty($response)) {
                // if (empty($result->id)) {
                    // throw new \Exception($result->description);
                // }

                $this->token = $response->getBody()
                $this->connexion_valide = true;
            } else {
                throw new \Exception('Please check url');
            }
			curl_close($curl);
        } catch (\Exception $e) {
            $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }
	
	
}

