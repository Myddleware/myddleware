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
	
	
	protected array $required_fields = ['default' => ['id', 'date_modified', 'date_entered','deleted']];
		
	protected array $FieldsDuplicate = [
		'Contacts' => ['email1', 'last_name'],
		'Accounts' => ['email1', 'name'],
		'Users' => ['email1', 'last_name'],
		'Leads' => ['email1', 'last_name'],
		'Prospects' => ['email1', 'name'],
		'default' => ['name'],
    ];
	
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
			// Call to SuiteCRM
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

	// Get the field used to login into SuiteCRM
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
	
	// Get the module list
	public function get_modules($type = 'source')
    {
        try {
			$moduleData = $this->call('GET', 'V8/meta/modules'); 
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
	
	// Get the field of the module in input
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
			$fields = $this->call('GET', 'V8/meta/fields/'.$module); 
			if (!empty($fields['data']['attributes'])) {
				foreach($fields['data']['attributes'] as $key => $field) {
					$this->moduleFields[$key] = [
							'label' => $key,
							'type' => 'varchar(255)',
							'type_bdd' => 'varchar(255)',
							'required' => (!empty($field['required']) ? 1 : 0),
							'relate' => (!empty($field['relationship']) ? 1 : 0),
						];
				}
			}
            return $this->moduleFields;
        } catch (\Exception $e) {
            return false;
        }
    }

	public function read($param)
    {
        try {			
			$result = array();
			// Generate the URL
			// Get a specific record
            if (!empty($param['query']['id'])) {
                $url = 'V8/module/'.$param['module'].'/'.$param['query']['id'];		
			// Search records with orther filters
            } elseif (!empty($param['query'])) {
				$filter = '';
				foreach($param['query'] as $key => $value) {
					$filter .= 'filter['.$key.'][eq]='.$value.'&and&';
				}
				$filter = rtrim($filter, '&and&');
                $url = 'V8/module/'.$param['module'].'?'.$filter;	
			// Search by date ref
            } else { 
				$dateRef = $this->dateTimeFromMyddleware($param['date_ref']);
				$dateRefField = $this->getRefFieldName($param);
				$url = 'V8/module/'.$param['module'].'?filter[date_modified][gt]='.$dateRef;	
			}
			$readSuite = $this->call('GET', $url); 
			if (!empty($readSuite['data'])) {
				// Add a dimension in case we search by id (the response is diffrent from SuiteCRM)
				if (!empty($param['query']['id'])) {
					$records[0] = $readSuite['data'];
				} else {
					$records = $readSuite['data'];
				}
				foreach ($records as $record) {
					foreach($param['fields'] as $fieldName) {
						$result[$record['id']][$fieldName] = (!empty($record['attributes'][$fieldName]) ? $record['attributes'][$fieldName] : '');
					}
					$result[$record['id']]['id'] = $record['id'];
				}
			}
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
        }
        return $result;
    }	
	
	protected function create($param, $record, $idDoc = null)
    {    
		try {
			// Build creation parameters
			$parameter['data']['type']=$param['module'];
			$parameter['data']['attributes']=$record;
			$url = 'V8/module';
			// Call to suiteCRM
			$createSuite = $this->call('POST', $url, json_encode($parameter)); 
			if (!empty($createSuite['data']['id'])) {
				return $createSuite['data']['id'];
			}
			throw new \Exception('No id returned.');
		} catch (\Exception $e) {
            throw new \Exception('Exception during creation call: '.$e->getMessage());
        }
    }
	
	protected function update($param, $record, $idDoc = null)
    {    
		try {
			// Build update parameters
			$parameter['data']['type'] = $param['module'];
			$parameter['data']['id'] = $record['target_id'];
			unset($record['target_id']);
			$parameter['data']['attributes'] = $record;
			$url = 'V8/module';
			// Call to suiteCRM
			$createSuite = $this->call('PATCH', $url, json_encode($parameter)); 
			if (!empty($createSuite['data']['id'])) {
				return $createSuite['data']['id'];
			}
			throw new \Exception('No id returned.');
		} catch (\Exception $e) {
            throw new \Exception('Exception during update call: '.$e->getMessage());
        }
    }
	
	protected function delete($param, $record)
    {    
		try {
			$url = 'V8/module/'.$param['module'].'/'.$record['target_id'];
			// Call to suiteCRM
			$deleteSuite = $this->call('DELETE', $url); 
			if (!empty($deleteSuite['errors'])) {
				throw new \Exception($deleteSuite['errors']['detail']);
			}
			if (array_key_exists('data',$deleteSuite)) {
				return $record['target_id'];
			}
		} catch (\Exception $e) {
            throw new \Exception('Exception during deletion call: '.$e->getMessage());
        }
    }
	
	public function getRefFieldName($param): string
    {
        if (in_array($param['ruleParams']['mode'], ['0', 'S', 'U'])) {
            return 'date_modified';
        } elseif ('C' == $param['ruleParams']['mode']) {
            return 'date_entered';
        }
        throw new \Exception("$param[ruleParams][mode] is not a correct Rule mode.");
    }

	// Convert date to Myddleware format
    // 2020-07-08T12:33:06+02:00 to 2020-07-08 10:33:06
    /**
     * @throws \Exception
     */
    protected function dateTimeToMyddleware($dateTime)
    {
        $dto = new \DateTime($dateTime);
        // We save the UTC date in Myddleware
        $dto->setTimezone(new \DateTimeZone('UTC'));

        return $dto->format('Y-m-d H:i:s');
    }

    // Convert date to SugarCRM format
    /**
     * @throws \Exception
     */
    protected function dateTimeFromMyddleware($dateTime)
    {
        $dto = new \DateTime($dateTime);
        // Return date to UTC timezone
        return $dto->format('Y-m-d\TH:i:s+00:00');
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
