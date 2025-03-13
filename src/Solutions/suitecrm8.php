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
	public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
			$token_url = rtrim($this->paramConnexion['url'], '/').'/Api/access_token';
			// $token_url = rtrim($this->paramConnexion['url'], '/').'/Api/Oauth/access_token';

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
				'username'=> $this->paramConnexion['login'],
				'password' => $this->paramConnexion['password']
			));
			curl_setopt($ch, CURLOPT_URL, $token_url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			$output = curl_exec($ch);
			$auth_out = json_decode($output,true);
// if (curl_errno($ch)) {
    // throw new \Exception('cURL error: ' . curl_error($ch));
// }
			// print_r ($output); // For debug purposes
			// print_r ($auth_out); // For debug purposes
			// throw new \Exception('test : '.print_r($output,1));
			// throw new \Exception('test : '.print_r($postStr,1));
			throw new \Exception('test : '.$token_url.' : '.print_r($output,1));

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
}
