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

class sugarcrmcore extends solution
{
    protected $sugarAPI;
    protected $sugarAPIVersion = 'v11';
    protected $sugarPlatform = 'base';
    protected $defaultLimit = 100;
    protected $bulkLimit = 500;
    protected $delaySearch = '-1 month';

    protected $required_fields = ['default' => ['id', 'date_modified']];

    protected $FieldsDuplicate = ['Contacts' => ['email1', 'last_name'],
        'Accounts' => ['email1', 'name'],
        'Users' => ['email1', 'last_name'],
        'Leads' => ['email1', 'last_name'],
        'Prospects' => ['email1', 'name'],
        'default' => ['name'],
    ];

    public function getFieldsLogin()
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
						AND $value->relationship_type <> 'many-to-many'
					) {
						continue;
					}
                    $modules['link_'.$relationship] = $value->name;

            // [s7_catalogues_accounts_1] => stdClass Object
                // (
                    // [from_studio] => 1
                    // [join_key_lhs] => s7_catalogues_accounts_1s7_catalogues_ida
                    // [join_key_rhs] => s7_catalogues_accounts_1accounts_idb
                    // [join_table] => s7_catalogues_accounts_1_c
                    // [lhs_key] => id
                    // [lhs_module] => S7_Catalogues
                    // [lhs_table] => s7_catalogues
                    // [name] => s7_catalogues_accounts_1
                    // [relationship_type] => many-to-many
                    // [rhs_key] => id
                    // [rhs_module] => Accounts
                    // [rhs_table] => accounts
                    // [true_relationship_type] => many-to-many
                // )
					
                }
            }
            return $modules;
        } catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );	
            return false;
        }
    }
	
	// Check if the module is a relationship and return the relationship parameters
	protected function isManyToManyRel($module) {
		if (
				!empty($module)
			AND substr($module,0,5) == 'link_'
		) {
			$relationshipsSugar = $this->customCall($this->paramConnexion['url'].'/rest/'.$this->sugarAPIVersion.'/metadata?type_filter=relationships');
			$relName = substr($module,5);
			if (!empty($relationshipsSugar->relationships->$relName)) {
				return $relationshipsSugar->relationships->$relName;
			}
		}
		return false;
	}
	
    public function get_module_fields($module, $type = 'source', $param = null)
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
						'relate' => true
                    ];
				$this->moduleFields[$rel->join_key_rhs] = [
						'label' => $rel->join_key_rhs,
						'type' => 'varchar(36)',
						'type_bdd' => 'varchar(36)',
						'required' => 0,
						'required_relationship' => 1,
						'relate' => true
                    ];
				return $this->moduleFields;
			}			
            // Call teh detail of all Sugar fields for the module
            $fieldsSugar = $this->customCall($this->paramConnexion['url'].'/rest/'.$this->sugarAPIVersion.'/metadata?type_filter=modules&module_filter='.$module);

            // Browse fields
            if (!empty($fieldsSugar->modules->$module->fields)) {
                foreach ($fieldsSugar->modules->$module->fields as $field) {
                    if (empty($field->type)) {
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
						'relate' => false
                    ];

                    // Add option for enum fields
                    if (in_array($field->type, ['enum', 'multienum'])) {
                        $fieldsList = $this->customCall($this->paramConnexion['url'].'/rest/'.$this->sugarAPIVersion.'/'.$module.'/enum/'.$field->name);
                        if (
								!empty($fieldsList)
							AND is_array($fieldsList)
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
							'relate' => true
                        ];
                    }
                }
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );			
            return false;
        }
    }
    // get_module_fields($module)


    /**
     * Function read data.
     *
     * @param $param
     *
     * @return mixed
     */
    // public function readData($param)
    public function read($param)
    {
		$result = [];

		// Init search parameters
		$filterArgs = [
			'max_num' => $param['limit'],
			'offset' => 0,
			'fields' => implode($param['fields'], ','),
			'order_by' => 'date_modified',
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
			throw new \Exception(print_r($response->getBody(), true));
		}

		// Format records to result format
		if (!empty($records)) {
			foreach ($records as $record) {
				foreach ($param['fields'] as $field) {
					// Sugar returns multilist value as array
					if (is_array($record->$field)) {
						$record->$field = implode(',', $record->$field);
					}
					$result[$record->id][$field] = (!empty($record->$field) ? $record->$field : '');
				}
			}
		}
        return $result;
    }
	
	protected function changeReadFilterArgs($param, $filterArgs) {
		return $filterArgs;
	}

	public function getRefFieldName($moduleSource, $RuleMode) {
		return 'date_modified';
    }
    // end function read

    /**
     * Function create data.
     *
     * @param $param
     *
     * @return mixed
     */
    public function createData($param)
    {	
		$result = array();
		$error = '';
		// Limit each call using the bulk limit
		// Split the data into several array using the bulk limite size
		$paramDataBulkArray = array_chunk($param['data'], $this->bulkLimit, true);
		// Call several time SugarCRM API bulk
		foreach ($paramDataBulkArray as $paramDataBulk) {
			// Change the data fo teh call			
			$param['data'] = $paramDataBulk;			
			$resultBulk = $this->upsert('create', $param);;
			// Manage result
			if (!empty($resultBulk['error'])) {
				$error .= $resultBulk['error'];
			}			
			$result = array_merge($result,$resultBulk);
		}
		// get the concatenation of all bulk calls
		$result['error'] = $error;		
        return $result;
    }

    // end function create

    /**
     * Function update data.
     *
     * @param $param
     *
     * @return mixed
     */
    public function updateData($param)
    {
		$result = array();
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
			$result = array_merge($result,$resultBulk);
		}
		// get the concatenation of all bulk calls
		$result['error'] = $error;
		return $result;
    }

    // end function create

    public function upsert($method, $param)
    {
		try {
			$i = 0;
			// Build bulk call
			foreach ($param['data'] as $idDoc => $data) {
                // Check control before create/update
                $param['method'] = $method;
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);

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
					$dataRel = array(
									'link_name' => $rel->name,
									'ids' => array(
											$data[$rel->join_key_lhs] => array('id' => $data[$rel->join_key_rhs])
										),
								);
					$bulkData['requests'][] = array('url' => '/'.$this->sugarAPIVersion.'/'.$rel->lhs_module.'/'.$data[$rel->join_key_lhs].'/link', 'method' => 'POST', 'data' => $dataRel);
                // Create record
				} elseif ('create' == $method) {
                    // Myddleware field empty when data transfer type is create
                    unset($data['target_id']);
					$bulkData['requests'][] = array('url' => '/'.$this->sugarAPIVersion.'/'.$param['module'], 'method' => 'POST', 'data' => $data);
                // Update record
				} else {
                    // The record id is stored in $data['target_id']
                    $targetId = $data['target_id'];
                    unset($data['target_id']);				
					$bulkData['requests'][] = array('url' => '/'.$this->sugarAPIVersion.'/'.$param['module'].'/'.$targetId, 'method' => 'PUT', 'data' => $data);
                }
			}
			
			// Send all data in 1 call
			$recordResult = $this->sugarAPI->bulk()->execute($bulkData);
			$response = $recordResult->getResponse();
			// Manage response
			if ($response->getStatus() == '200') {
				$records = $response->getBody(false);					
				$i = 0;
				// Manage result returned from SugarCRM
				foreach ($param['data'] as $idDoc => $data) {
					if ($records[$i]->status == '200') {
						// Return for create relationship
						if (!empty($rel)) {
							if (!empty($records[$i]->contents->related_records[0]->id)) {
								$result[$idDoc] = array(
														'id' => $records[$i]->contents->related_records[0]->id.'__'.$records[$i]->contents->record->id,
														'error' => false,
													);
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
							$result[$idDoc] = array(
													'id' => $records[$i]->contents->id,
													'error' => false,
												);
						} else {
							$result[$idDoc]['id'] = '-1';
							if (!empty($records[$i]->contents)) {
								$result[$idDoc]['error'] = 'Error '.$records[$i]->contents->error.' : '.$records[$i]->contents->error_message;
							} else {
								$result[$idDoc]['error'] = 'No id returned from SugarCRM.';
							}
						}
					} else {
						$result[$idDoc] = array(
								'id' => '-1',
								'error' => 'Error '.$records[$i]->status.' : '.$records[$i]->status_text.'. '.
									(!empty($records[$i]->contents->error) ? $records[$i]->contents->error.' : '.$records[$i]->contents->error_message : ''),
							);
					}
					$this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
					$i++;
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
    // end function create

    // Convert date to Myddleware format
    // 2020-07-08T12:33:06+02:00 to 2020-07-08 10:33:06
    protected function dateTimeToMyddleware($dateTime)
    {
        $dto = new \DateTime($dateTime);
        // We save the UTC date in Myddleware
        $dto->setTimezone(new \DateTimeZone('UTC'));

        return $dto->format('Y-m-d H:i:s');
    }
    // dateTimeToMyddleware($dateTime)

    // Convert date to SugarCRM format
    protected function dateTimeFromMyddleware($dateTime)
    {
        $dto = new \DateTime($dateTime);
        // Return date to UTC timezone
        return $dto->format('Y-m-d\TH:i:s+00:00');
    }

    // dateTimeToMyddleware($dateTime)

    // Build the direct link to the record (used in data transfer view)
    public function getDirectLink($rule, $document, $type)
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

    // Used only for metadata (get_modules and )
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
            throw new \Exception($e->getMessage());
        }
        // Send exception catched into functions
        if (!empty($response_obj->error_message)) {
            throw new \Exception($response_obj->error_message);
        }

        return $response_obj;
    }
}
class sugarcrm extends sugarcrmcore
{
}

