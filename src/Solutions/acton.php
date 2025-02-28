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
	
	protected bool $sendDeletion = true;
	
	protected $modules = array(
								'target' => array(
													'list' => 'List',
													'list_contact' => 'List contact'
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
				$this->refreshToken = $tokens->refresh_token;
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
			throw new \Exception('You can only search list by name. Please add the check duplicate field : name.');
		}
		if (
				$param['module'] == 'list_contact'
			AND empty($param['query']['id'])
		) {
			throw new \Exception('You can only search list member by contact id.');
		}
		
		// Parameters to read data from Act-on
        $result = [];
		$parameters = [
			'headers' => [
				'Authorization' => 'Bearer '.$this->accessToken,
				'accept' => 'application/json',
			]
		];
		// For list module
		if ($param['module'] == 'list') {
			// Call act-on
			$response = $this->client->request('GET', 'https://api.actonsoftware.com/api/1/list/?count=1000', $parameters);
			// Manage response
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
		// For module contact list module
		if ($param['module'] == 'list_contact') {
			// Get the list id
			$listId = explode(':',$param['query']['id'])[0];
			// Get the contacts
			$response = $this->client->request('GET', 'https://api.actonsoftware.com/api/1/list/'.$listId.'/record/'.$param['query']['id'], $parameters);
			$responseBodyContact = json_decode($response->getBody()->getContents(), true);
			if (!empty($responseBodyContact)) {
				// Get the field list from the Act-on list
				$response = $this->client->request('GET', 'https://api.actonsoftware.com/api/1/list/'.$listId, $parameters);
				$responseBodyList = json_decode($response->getBody()->getContents(), true);

				// Remove first field that contains the contact id
				$result['id'] = $responseBodyContact[0];
				unset($responseBodyContact[0]);
				$result['listid'] = $listId;
				// Build body
				foreach($responseBodyContact as $key => $value) {
					// Replace the key by the field name in the list
					if (!empty($responseBodyList['headers'][$key])) {
						$contact[$responseBodyList['headers'][$key]] = $value;
					}
				}				
				$result['body'] = json_encode($contact);
				$result['tsLastModified'] = time();
				return array($result);				
			}
		}
    }
	
	// Create record to Act-On
	protected function create($param, $record, $idDoc = null)
    {
		$result = [];
		// Parameters
		$parameters = [
			'headers' => [
				'Authorization' => 'Bearer '.$this->accessToken,
				'accept' => 'application/json',
				'content-type' => 'application/json',
			]
		];
		// For module contact list
		if ($param['module'] == 'list_contact') {
			// Check required fields
			if (empty($record['listid'])) {
				throw new \Exception('The field listid is required to add a member to a list.');
			}
			// Get email address
			$emailAddress = json_decode($record['body'])->EMAIL;
			if (empty($emailAddress)) {
				throw new \Exception('The email address is required to add a member to a list.');
			}
			// Send data to Act-on
			$parameters['body'] = $record['body']; 
			$response = $this->client->request('POST', 'https://api.actonsoftware.com/api/1/list/'.$record['listid'].'/record', $parameters);
			$responseBody = json_decode($response->getBody()->getContents(), true);
			if (empty($responseBody['contact_id'])) {
				throw new \Exception('No contact ID returned.');
			}
			return $responseBody['contact_id'];
		}
    }
	
	// Update a record to Acton
	protected function update($param, $record, $idDoc = null)
    {
		$result = [];
		// Parameters
		$parameters = [
			'headers' => [
				'Authorization' => 'Bearer '.$this->accessToken,
				'accept' => 'application/json',
				'content-type' => 'application/json',
			]
		];
		// For module contact list
		if ($param['module'] == 'list_contact') {
			// Check required fields
			if (empty($record['listid'])) {
				throw new \Exception('The field listid is required to update a member to a list.');
			}
			if (empty($record['target_id'])) {
				throw new \Exception('The field target_id (contact_id) is required to update a member to a list.');
			}
			// Send data to Act-on
			$parameters['body'] = $record['body']; 
			$response = $this->client->request('PUT', 'https://api.actonsoftware.com/api/1/list/'.$record['listid'].'/record/'.$record['target_id'], $parameters);
			$responseBody = json_decode($response->getBody()->getContents(), true);
			// Managed response
			if (
					!empty($responseBody['status'])
				AND $responseBody['status'] == 'success'
			) {
				return $record['target_id'];
			} else {
				throw new \Exception('Failed to update the contact in the list.');
			}
		}
    }
	
	// Delete a record from Acton
	protected function delete($param, $record, $idDoc = null)
    {
		$result = [];
		// Parameters
		$parameters = [
			'headers' => [
				'Authorization' => 'Bearer '.$this->accessToken,
				'accept' => 'application/json',
				'content-type' => 'application/json',
			]
		];
		// For module contact list
		if ($param['module'] == 'list_contact') {
			// Check required fields
			if (empty($record['listid'])) {
				throw new \Exception('The field listid is required to update a member to a list.');
			}
			if (empty($record['target_id'])) {
				throw new \Exception('The field target_id (contact_id) is required to update a member to a list.');
			}
			// Delete data from Act-on
			$response = $this->client->request('DELETE', 'https://api.actonsoftware.com/api/1/list/'.$record['listid'].'/record/'.$record['target_id'], $parameters);
			$responseBody = json_decode($response->getBody()->getContents(), true);
			// Managed response
			if (
					!empty($responseBody['status'])
				AND $responseBody['status'] == 'success'
			) {
				return $record['target_id'];
			} else {
				throw new \Exception('Failed to update the contact in the list.');
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

