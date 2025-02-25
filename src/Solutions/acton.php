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
	protected $client;
	protected $accessToken;
	protected $refreshToken;
	
	protected $modules = array(
								'target' => array(
													'list' => 'List'
											)
							);

    protected array $FieldsDuplicate = [
											'list' => ['name'],
										];
	
	protected array $required_fields = ['default' => ['id', 'tsLastModified']];
	
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
			$this->client = new \GuzzleHttp\Client();
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
			$response = $this->client->request('POST', 'https://api.actonsoftware.com/token', $parameters);
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
	
	/**
     * Retrieve the list of modules available to be read or sent.
     *
     * @param string $type source|target
     *
     * @return array
	*/
    public function get_modules($type = 'source'): array
    {
        if (!empty($this->modules[$type])) {
            return $this->modules[$type];
        }
        return [];
    }
	

    /**
     * Retrieve the list of fields (metadata) associated to each module.
     *
     * @param string $module
     * @param string $type
     * @param array  $param
     *
     * @return array
     */
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            // Use Act-on metadata
            require 'lib/acton/metadata.php';
            if (!empty($moduleFields[$module])) {
                $this->moduleFields = array_merge($this->moduleFields, $moduleFields[$module]);
            }
            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }
	
	
 /**
     * @throws \Exception
     */
    public function read($param)
    {
		// List can be read only using a query by name
		if (
				$param['module'] == 'list'
			AND empty($param['query']['name'])
		) {
			throw new \Exception('You can only search list from ACT-ON. Please add the check duplicate field : name.');
		}
		
        $result = [];
		$parameters = [
			'headers' => [
				'Authorization' => 'Bearer '.$this->accessToken,
				'accept' => 'application/json',
			]
		];
		
		$response = $this->client->request('GET', 'https://api.actonsoftware.com/api/1/list/?count=1000', $parameters);
	
		if (!empty($response)) {
			$records = json_decode($response->getBody(),1);
			if (!empty($records['result'])) {
				if ($param['module'] == 'list') {
					foreach($records['result'] as $record) {
						if ($record['name'] == $param['query']['name']) {
							return array($record);
						}
					}
				}
			}
		}
    }
	
	public function getRefFieldName($param): string
    {
        return 'tsLastModified';
    }
	
	 public function getRuleMode($module, $type): array
    {
        if (
                $type == 'target'
            && in_array($module, ['list'])
        ) {
            return [
                'S' => 'search_only',
            ];
        }

        return parent::getRuleMode($module, $type);
    }
}

