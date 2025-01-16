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

class sugarcrm extends solution
{
    // Enable to read deletion and to delete data
    protected bool $readDeletion = true;
    protected bool $sendDeletion = true;

    protected $sugarAPI;
    protected string $sugarAPIVersion = 'v11';
    protected string $sugarPlatform = 'base';
    protected int $defaultLimit = 100;
    protected int $bulkLimit = 250;
    protected string $delaySearch = '-1 month';

    protected array $required_fields = ['default' => ['id', 'date_modified']];

    protected array $FieldsDuplicate = 
	[
        'default' => ['name'],
		'Contacts' => ['email1', 'last_name'],
        'Accounts' => ['email1', 'name'],
        'Users' => ['email1', 'last_name'],
        'Leads' => ['email1', 'last_name'],
        'Prospects' => ['email1', 'name'],
        'EmailAddresses' => ['email_address'],
    ];
	
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
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
        ];
    }

    // Connect to SugarCRM
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            $server = $this->paramConnexion['url'].'/rest/'.$this->sugarAPIVersion.'/';
            $credentials = [
                'username' => $this->paramConnexion['login'],
                'password' => $this->paramConnexion['password'],
                'platform' => $this->sugarPlatform,
            ];

            // Log into Sugar
            $this->sugarAPI = new \SugarAPI\SDK\SugarAPI($server, $credentials);
            $this->sugarAPI->login();

            // Check the token
            $token = $this->sugarAPI->getToken();
            if (!empty($token->access_token)) {
                $this->connexion_valide = true;
            } else {
                return ['error' => 'Failed to connect to Sugar, no error returned.'];
            }
        } catch (\SugarAPI\SDK\Exception\SDKException $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Get module list
    public function get_modules($type = 'source')
    {
        try {
            $modulesSugar = $this->customCall($this->paramConnexion['url'].'/rest/'.$this->sugarAPIVersion.'/metadata?type_filter=full_module_list');
            if (!empty($modulesSugar->full_module_list)) {
                foreach ($modulesSugar->full_module_list as $module => $label) {
                    // hash isn't a Sugar module
                    if ('_hash' == $module) {
                        continue;
                    }
                    $modules[$module] = $label;
                }
            }

            // Add many-to-many relationships
            $relationshipsSugar = $this->customCall($this->paramConnexion['url'].'/rest/'.$this->sugarAPIVersion.'/metadata?type_filter=relationships');
            if (!empty($relationshipsSugar->relationships)) {
                foreach ($relationshipsSugar->relationships as $relationship => $value) {
                    // hash isn't a Sugar module
                    if ('_hash' == $relationship) {
                        continue;
                    }
                    // Only many-to-many relationships
                    if (
                            !empty($value->relationship_type)
                        and 'many-to-many' != $value->relationship_type
                    ) {
                        continue;
                    }
                    $modules['link_'.$relationship] = $value->name;
                }
            }

            return $modules;
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');

            return false;
        }
    }

    // Check if the module is a relationship and return the relationship parameters

    /**
     * @throws \Exception
     */
    protected function isManyToManyRel($module)
    {
        if (
                !empty($module)
            and 'link_' == substr($module, 0, 5)
        ) {
            $relationshipsSugar = $this->customCall($this->paramConnexion['url'].'/rest/'.$this->sugarAPIVersion.'/metadata?type_filter=relationships');
            $relName = substr($module, 5);
            if (!empty($relationshipsSugar->relationships->$relName)) {
                return $relationshipsSugar->relationships->$relName;
            }
        }

        return false;
    }

    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            // If module is a many-to-many relationship
            $rel = $this->isManyToManyRel($module);
            if (!empty($rel)) {
                $this->moduleFields[$rel->join_key_lhs] = [
                        'label' => $rel->join_key_lhs,
                        'type' => 'varchar(36)',
                        'type_bdd' => 'varchar(36)',
                        'required' => 0,
                        'required_relationship' => 1,
                        'relate' => true,
                    ];
                $this->moduleFields[$rel->join_key_rhs] = [
                        'label' => $rel->join_key_rhs,
                        'type' => 'varchar(36)',
                        'type_bdd' => 'varchar(36)',
                        'required' => 0,
                        'required_relationship' => 1,
                        'relate' => true,
                    ];

                return $this->moduleFields;
            }
            // Call teh detail of all Sugar fields for the module
            $fieldsSugar = $this->customCall($this->paramConnexion['url'].'/rest/'.$this->sugarAPIVersion.'/metadata?type_filter=modules&module_filter='.$module);
            // Browse fields
            if (!empty($fieldsSugar->modules->$module->fields)) {
                foreach ($fieldsSugar->modules->$module->fields as $field) {
                    if (
                            empty($field->type)
                         or 'link' == $field->type // Module linked not just a related fields (example : module bigs for contact)
                    ) {
                        continue;
                    }

                    // Calculate the database type
                    if (!in_array($field->type, $this->type_valide)) {
                        if (isset($this->exclude_field_list[$module])) {
                            if (in_array($field->name, $this->exclude_field_list[$module]) && 'target' == $type) {
                                continue;
                            } // Ces champs doivent être exclus de la liste des modules pour des raisons de structure de BD SuiteCRM
                        }
                        $type_bdd = 'varchar(255)';
                    } else {
                        $type_bdd = $field->type;
                    }

                    // Add all field in moduleFields
                    $this->moduleFields[$field->name] = [
                        'label' => (!empty($field->comment) ? $field->comment : $field->name),
                        'type' => $field->type,
                        'type_bdd' => $type_bdd,
                        'required' => (!empty($field->required) ? $field->required : 0),
                        'relate' => false,
                    ];

                    // Add option for enum fields
                    if (in_array($field->type, ['enum', 'multienum'])) {
                        $fieldsList = $this->customCall($this->paramConnexion['url'].'/rest/'.$this->sugarAPIVersion.'/'.$module.'/enum/'.$field->name);
                        if (
                                !empty($fieldsList)
                            and is_array($fieldsList)
                        ) {
                            // Transform object to array
                            foreach ($fieldsList as $key => $value) {
                                $this->moduleFields[$field->name]['option'][$key] = $value;
                            }
                        }
                    }

                    // Add relate fields
                    if (
                            '_id' == substr($field->name, -3)
                        or '_ida' == substr($field->name, -4)
                        or '_idb' == substr($field->name, -4)
                        or (
                                'id' == $field->type
                            and 'id' != $field->name
                        )
                        or 'created_by' == $field->name
                    ) {
                        $this->moduleFields[$field->name] = [
                            'label' => (!empty($field->comment) ? $field->comment : $field->name),
                            'type' => 'varchar(36)',
                            'type_bdd' => 'varchar(36)',
                            'required' => (!empty($field->required) ? $field->required : 0),
                            'required_relationship' => 0,
                            'relate' => true,
                        ];
                    }
                }
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // public function readRelationship($param)
    /**
     * @throws \Exception
     */
    public function readRelationship($param, $rel) {
		// Set the parameters for relationship reading
		$filterArgs = [
            'max_num' => $param['limit'],
            'offset' => 0,
            'fields' => implode(',',$param['fields']),
            'order_by' => 'date_modified',
            'deleted' => $param['ruleParams']['deletion'],
        ];
		// The call for relationship required the id of the root record
		if (empty($param['query'])) {
			throw new \Exception('No query parameter, failed to read relationship');
		}
		// The query parameter must be the reference id. 
		// For example if we try to read the users from a team, query must contains the team id
		$refereceId = current($param['query']);

		// Get the record using the input parameters
		$getRecords = $this->sugarAPI->filterRelated(key($param['query']), $refereceId, $rel->rhs_table)->execute($filterArgs);
		$response = $getRecords->getResponse();
        // Format response if http return = 200
        if ('200' == $response->getStatus()) {
            $body = $getRecords->getResponse()->getBody(false);
            if (!empty($body->records)) {
                $records = $body->records;
            }
        } else {
            $bodyError = $response->getBody();
            throw new \Exception('Status '.$response->getStatus().' : '.$bodyError['error'].', '.$bodyError['error_message']);
        }
		// Format records to result format
        if (!empty($records)) {
            foreach ($records as $record) {
				// The record id is build with both ids from the relationship
				$recordId = $refereceId.'_'.$record->id;
                $result[$recordId]['id'] = $recordId;
                $result[$recordId]['date_modified'] = $record->date_modified;
				//  Get both ids
                $result[$recordId][$rel->join_key_lhs] = $refereceId;
                $result[$recordId][$rel->join_key_rhs] = $record->id;
            }
        }
        return $result;
	}
	
    public function read($param)
    {
        $result = [];
		$maxDateModified = '';
		$rel = $this->isManyToManyRel($param['module']);
		if ($rel !== false) {
			return $this->readRelationship($param, $rel);
		}

        // Manage delete option to enable
        $deleted = false;
        if (!empty($param['ruleParams']['deletion'])) {
            $deleted = true;
            $param['fields'][] = 'deleted';
        }
		// Flag to know if Myddleware has read only deleted records
		$onlyDeletion = true;

        // Init search parameters
        $filterArgs = [
            'max_num' => $param['limit'],
            'offset' => 0,
            'fields' => implode(',',$param['fields']),
            'order_by' => 'date_modified',
            'deleted' => $deleted,
        ];
        // Init search filters
        // Search by fields (id or duplicate fields)
        if (!empty($param['query'])) {
            // Add every filter (AND operator by default)
            foreach ($param['query'] as $key => $value) {
                $filterArgs['filter'][] = [$key => ['$equals' => $value]];
            }
            // Search By reference
        } else {
            $filterArgs['filter'] = [
                [
                    'date_modified' => [
                        '$gt' => $this->dateTimeFromMyddleware($param['date_ref']),
                    ],
                ],
            ];
        }
        // Add function to odify filter id needed
        $filterArgs = $this->changeReadFilterArgs($param, $filterArgs);
        // Get the records
        $getRecords = $this->sugarAPI->filterRecords($param['module'])->execute($filterArgs);
        $response = $getRecords->getResponse();
        // Format response if http return = 200
        if ('200' == $response->getStatus()) {
            $body = $getRecords->getResponse()->getBody(false);
            if (!empty($body->records)) {
                $records = $body->records;
            }
        } else {
            $bodyError = $response->getBody();
            throw new \Exception('Status '.$response->getStatus().' : '.$bodyError['error'].', '.$bodyError['error_message']);
        }

        // Format records to result format
        if (!empty($records)) {			
			// Manage deletion by adding the flag Myddleware_deletion to the record
            foreach ($records as $record) {
                if (
                        true == $deleted
                    and !empty($record->deleted)
                ) {
                    $result[$record->id]['myddleware_deletion'] = true;
                } else {
					// At least one non deleted record read
					$onlyDeletion = false;
				}
				// Keep the date modified max (date modified is empty for deletion record)
				if (!empty($record->date_modified)) {
					$maxDateModified = $record->date_modified;
				}
			}			
			// Error if only deletion records read
			if ($onlyDeletion) {
				if (count($result) >= $param['limit']) {
					throw new \Exception('Only deletion records read. It is not possible to determine the reference date with only deletion. Please increase the rule limit to include non deletion records.');
				} else {
					// If only deletion without new or modified record, we send no result. We wait for new or modified record. 
					// Otherwise we will read the deleted record until a new or modified record is read because Sugar doesn't return modified date for deleted record.
					throw new \Exception('Only deletion records read. It is not possible to determine the reference date with only deletion. Waiting for a new record to be created in the source application for this rule.');
				}
			}	
					
			// Build the results
			foreach ($records as $record) {		
                foreach ($param['fields'] as $field) {			
                    // Sugar returns multilist value as array
                    if (
                            !empty($record->$field)
                        and is_array($record->$field)
                    ) {
                        // Some fields can be an object like teamname field
                        if (is_object($record->$field[0])) {
							$fieldObjectList = array();
							// Get all ids of the object list
							foreach($record->$field as $fieldObject) {
								$fieldObjectList[] = $fieldObject->id;
							}
							$record->$field = implode(',', $fieldObjectList);
                        } else {
                            $record->$field = implode(',', $record->$field);
                        }
                    }
                    $result[$record->id][$field] = (!empty($record->$field) ? $record->$field : '');
                }
                // No date modified returned if record deleted, we set a default date (the last reference date read)
                if (!empty($result[$record->id]['myddleware_deletion'])) {
                    $result[$record->id]['date_modified'] = $maxDateModified;
                }	
            }
        }
        return $result;
    }

    protected function changeReadFilterArgs($param, $filterArgs)
    {
        return $filterArgs;
    }

    public function getRefFieldName($param): string
    {
        return 'date_modified';
    }

    public function createData($param): array
    {
        $result = [];
        $error = '';
        // Limit each call using the bulk limit
        // Split the data into several array using the bulk limite size
        $paramDataBulkArray = array_chunk($param['data'], $this->bulkLimit, true);
        // Call several time SugarCRM API bulk
        foreach ($paramDataBulkArray as $paramDataBulk) {
            // Change the data fo teh call
            $param['data'] = $paramDataBulk;
            $resultBulk = $this->upsert('create', $param);
            // Manage result
            if (!empty($resultBulk['error'])) {
                $error .= $resultBulk['error'];
            }
            $result = array_merge($result, $resultBulk);
        }
        // get the concatenation of all bulk calls
        $result['error'] = $error;

        return $result;
    }

    public function updateData($param): array
    {
        $result = [];
        $error = '';
        // Limit each call using the bulk limit
        // Split the data into several array using the bulk limite size
        $paramDataBulkArray = array_chunk($param['data'], $this->bulkLimit, true);
        // Call several time SugarCRM API bulk
        foreach ($paramDataBulkArray as $paramDataBulk) {
            // Change the data fo teh call
            $param['data'] = $paramDataBulk;
            $resultBulk = $this->upsert('update', $param);
            // Manage result
            if (!empty($resultBulk['error'])) {
                $error .= $resultBulk['error'];
            }
            $result = array_merge($result, $resultBulk);
        }
        // get the concatenation of all bulk calls
        $result['error'] = $error;

        return $result;
    }

    public function deleteData($param): array
    {
        $result = [];
        $error = '';
        // Limit each call using the bulk limit
        // Split the data into several array using the bulk limite size
        $paramDataBulkArray = array_chunk($param['data'], $this->bulkLimit, true);
        // Call several time SugarCRM API bulk
        foreach ($paramDataBulkArray as $paramDataBulk) {
            // Change the data fo teh call
            $param['data'] = $paramDataBulk;
            $resultBulk = $this->upsert('delete', $param);
            // Manage result
            if (!empty($resultBulk['error'])) {
                $error .= $resultBulk['error'];
            }
            $result = array_merge($result, $resultBulk);
        }
        // get the concatenation of all bulk calls
        $result['error'] = $error;

        return $result;
    }

    public function upsert($method, $param): array
    {
        try {
            $i = 0;
            // Build bulk call
            foreach ($param['data'] as $idDoc => $data) {
                // Check control before create/update
                $param['method'] = $method;
                // Check if the module is a many-to-many relationship
                $rel = $this->isManyToManyRel($param['module']);
                if (!empty($rel)) {
                    if (empty($data[$rel->join_key_lhs])) {
                        throw new \Exception('No value for the field '.$rel->join_key_lhs.' in the document '.$idDoc.'.');
                    }
                    if (empty($data[$rel->join_key_rhs])) {
                        throw new \Exception('No value for the field '.$rel->join_key_rhs.' in the document '.$idDoc.'.');
                    }
                    unset($data['target_id']);
                    $dataRel = [
                                    'link_name' => $rel->name,
                                    'ids' => [
                                            $data[$rel->join_key_lhs] => ['id' => $data[$rel->join_key_rhs]],
                                        ],
                                ];
                    $bulkData['requests'][] = ['url' => '/'.$this->sugarAPIVersion.'/'.$rel->lhs_module.'/'.$data[$rel->join_key_lhs].'/link', 'method' => 'POST', 'data' => $dataRel];
                // Create record
                } elseif ('create' == $method) {
					$data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                    // Myddleware field empty when data transfer type is create
                    unset($data['target_id']);
                    $bulkData['requests'][] = ['url' => '/'.$this->sugarAPIVersion.'/'.$param['module'], 'method' => 'POST', 'data' => $data];
                // Update record
                } elseif ('delete' == $method) {
					$data = $this->checkDataBeforeDelete($param, $data, $idDoc);
                    // The record id is stored in $data['target_id']
                    $targetId = $data['target_id'];
                    $bulkData['requests'][] = ['url' => '/'.$this->sugarAPIVersion.'/'.$param['module'].'/'.$targetId, 'method' => 'DELETE'];
                } else {
					$data = $this->checkDataBeforeUpdate($param, $data, $idDoc);
                    // The record id is stored in $data['target_id']
                    $targetId = $data['target_id'];
                    unset($data['target_id']);
                    $bulkData['requests'][] = ['url' => '/'.$this->sugarAPIVersion.'/'.$param['module'].'/'.$targetId, 'method' => 'PUT', 'data' => $data];
                }
            }
            // Send all data in 1 call
            $recordResult = $this->sugarAPI->bulk()->execute($bulkData);
            $response = $recordResult->getResponse();
            // Manage response
            if ('200' == $response->getStatus()) {
                $records = $response->getBody(false);
                $i = 0;
                // Manage result returned from SugarCRM
                foreach ($param['data'] as $idDoc => $data) {
                    if ('200' == $records[$i]->status) {
                        // Return for create relationship
                        if (!empty($rel)) {
                            if (!empty($records[$i]->contents->related_records[0]->id)) {
                                $result[$idDoc] = [
                                                        'id' => $records[$i]->contents->related_records[0]->id.'__'.$records[$i]->contents->record->id,
                                                        'error' => false,
                                                    ];
                            } else {
                                $result[$idDoc]['id'] = '-1';
                                if (!empty($records[$i]->contents)) {
                                    $result[$idDoc]['error'] = 'Error '.$records[$i]->contents->error.' : '.$records[$i]->contents->error_message;
                                } else {
                                    $result[$idDoc]['error'] = 'No id returned from SugarCRM.';
                                }
                            }

                            // return for create/update record
                        } elseif (!empty($records[$i]->contents->id)) {
                            $result[$idDoc] = [
                                                    'id' => $records[$i]->contents->id,
                                                    'error' => false,
                                                ];
                        } else {
                            $result[$idDoc]['id'] = '-1';
                            if (!empty($records[$i]->contents)) {
                                $result[$idDoc]['error'] = 'Error '.$records[$i]->contents->error.' : '.$records[$i]->contents->error_message;
                            } else {
                                $result[$idDoc]['error'] = 'No id returned from SugarCRM.';
                            }
                        }
                    } else {
                        $result[$idDoc] = [
                                'id' => '-1',
                                'error' => 'Error '.$records[$i]->status.' : '.$records[$i]->status_text.'. '.
                                    (!empty($records[$i]->contents->error) ? $records[$i]->contents->error.' : '.$records[$i]->contents->error_message : ''),
                            ];
                    }
                    $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
                    ++$i;
                }
            } else {
                throw new \Exception('Error '.$response->getStatus().' : '.$response->getError());
            }
            // Modification du statut du flux
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
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

    // Build the direct link to the record (used in data transfer view)
    public function getDirectLink($rule, $document, $type): string
    {
        // Get url, module and record ID depending on the type
        if ('source' == $type) {
            $url = $this->getConnectorParam($rule->getConnectorSource(), 'url');
            $module = $rule->getModuleSource();
            $recordId = $document->getSource();
        } else {
            $url = $this->getConnectorParam($rule->getConnectorTarget(), 'url');
            $module = $rule->getModuleTarget();
            $recordId = $document->gettarget();
        }
        // Build the URL (delete if exists / to be sure to not have 2 / in a row)
        return rtrim($url, '/').'/#'.$module.'/'.$recordId;
    }

    /**
     * @throws \Exception
     */
    protected function customCall($url, $parameters = null, $method = null)
    {
        try {
            $request = curl_init($url);
            if (!empty($method)) {
                curl_setopt($request, CURLOPT_CUSTOMREQUEST, $method);
            }
            curl_setopt($request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            curl_setopt($request, CURLOPT_HEADER, false);
            curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($request, CURLOPT_FOLLOWLOCATION, 0);

            $token = $this->sugarAPI->getToken();
            curl_setopt($request, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                "oauth-token: {$token->access_token}",
            ]);
            //convert arguments to json
            if (!empty($parameters)) {
                $json_arguments = json_encode($parameters);
                curl_setopt($request, CURLOPT_POSTFIELDS, $json_arguments);
            }

            //execute request
            $response = curl_exec($request);
            //decode response
            $response_obj = json_decode($response);
        } catch (Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
        // Send exception catched into functions
        if (!empty($response_obj->error_message)) {
            throw new \Exception($response_obj->error_message);
        }

        return $response_obj;
    }
}
