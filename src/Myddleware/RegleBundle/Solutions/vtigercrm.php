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

namespace Myddleware\RegleBundle\Solutions;

use Javanile\VtigerClient\VtigerClient;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class vtigercrmcore extends solution
{
    protected $limitPerCall = 100;

    protected $required_fields = [
                                    'default'    => [
                                                        'id',
                                                        'modifiedtime',
                                                        'createdtime'
                                                    ],
                                ];


	protected $FieldsDuplicate = array(	
										'Contacts' => array('email', 'lastname'),
										'CompanyDetails' => array('organizationname'),
										'Accounts' => array('accountname'),
										'Leads' => array('email', 'lastname'),
										'default' => array('')
									  );

    protected $exclude_module_list = [
                                        'default'    => ['Users', "Documents"],
                                        'target'     => [],
                                        'source'     => [],
                                    ];

    protected $exclude_field_list = [
                                        'default'    => [
                                                            'default'    => [
                                                                                'id'
                                                                            ],
                                                            'source'     => [
                                                                                'id'
                                                                            ],
                                                            'target'     => [
                                                                                'id',
                                                                                'modifiedby',
                                                                                'modifiedtime',
                                                                                "createdtime"
                                                                            ],
                                                        ],
                                    ];


    protected $inventoryModules = [
                                    "Invoice",
                                    "SalesOrder",
                                    "Quotes",
                                    "PurchaseOrder",
                                    "GreenTimeControl",
                                    "DDT",
                                ];

	protected $allowParentRelationship = array('Quotes');
	

    /** @var array $moduleList */
    protected $moduleList;

    /** @var VtigerClient $vtigerClient */
    protected $vtigerClient;

    /**
     * Make the login
     *
     * @param array $paramConnexion
     * @return void|array
     */
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);

        try {
            $client = new VtigerClient($this->paramConnexion['url']);
            $result = $client->login(trim($this->paramConnexion['username']), trim($this->paramConnexion['accesskey']));

            if (!$result['success']) {
                throw new \Exception($result['error']['message']);
            }

            $this->session = $client->getSessionName();
            $this->connexion_valide = true;
            $this->vtigerClient = $client;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    /**
     * Make the logout
     *
     * @return bool
     */
    public function logout()
    {
        // TODO: Creare ed usare il loguot di vtiger (Non Funziona)
        /*
        if(empty($this->vtigerClient))
            return false;

        return $this->vtigerClient->logout();
        */

        return true;
    }

    /**
     * Return the login fields
     *
     * @return array
     */
    public function getFieldsLogin()
    {
        return [
            [
                'name'  => 'username',
                'type'  => TextType::class,
                'label' => 'solution.fields.username',
            ],
            [
                'name'  => 'accesskey',
                'type'  => PasswordType::class,
                'label' => 'solution.fields.accesskey',
            ],
            [
                'name'  => 'url',
                'type'  => TextType::class,
                'label' => 'solution.fields.url',
            ],
        ];
    }

    /**
     * Return of the modules without the specified ones
     *
     * @param string $type
     * @return array|bool
     */
    public function get_modules($type = 'source')
    {		
		try {
			if (empty($this->vtigerClient)) {
				return false;
			}

			$result = $this->vtigerClient->listTypes();

			if (!$result['success'] || ($result['success'] && count($result['result']) == 0)) {
				return false;
			}

			$modules = $result['result'] ?? null;

			if (empty($modules)) {
				return false;
			}

			$escludedModule = $this->exclude_module_list[$type] ?: $this->exclude_module_list['default'];
			$options = [];
			foreach ($modules['information'] as $moduleName => $moduleInfo) {
				if (!in_array($moduleName, $escludedModule)) {
					$options[$moduleName] = $moduleInfo['label'];
					$this->setModulePrefix($moduleName);
				}
			}

			return $options ?: false;
		} catch (\Exception $e) {
			$error = $e->getMessage();
			return $error;
		}
    }

    public function setModulePrefix($moduleName = null)
    {			
        if (empty($moduleName)) {
            $result = $this->vtigerClient->listTypes();

            if (!$result['success'] || ($result['success'] && count($result['result']) == 0)) {
                return false;
            }

            foreach ($result['result']["types"] as $moduleName) {
                $describe = $this->vtigerClient->describe($moduleName);
                if ($describe['success'] && count($describe['result']) != 0) {
                    $this->moduleList[$describe["result"]["idPrefix"]] = $moduleName;
                }
            }
        }
        else {
            $describe = $this->vtigerClient->describe($moduleName);
            if ($describe['success'] && count($describe['result']) != 0) {
                $this->moduleList[$describe["result"]["idPrefix"]] = $moduleName;
                return true;
            }
            else {
                return false;
            }
        }
    }

    /**
     * Return the fields for a specific module without the specified ones
     *
     * @param string $module
     * @param string $type
     * @return array|bool
     */
    public function get_module_fields($module, $type = 'source')
    {	
		parent::get_module_fields($module, $type);
		try {
			if (empty($this->vtigerClient)) {
				return false;
			}

			$describe = $this->vtigerClient->describe($module);

			if (!$describe['success'] || ($describe['success'] && count($describe['result']) == 0)) {
				return false;
			}

			$fields = $describe['result']['fields'] ?? null;

			if (empty($fields)) {
				return false;
			}

			$escludeField = $this->exclude_field_list[$module] ?? $this->exclude_field_list['default'];
			$escludeField = $escludeField[$type] ?? $escludeField['default'];
			$this->moduleFields = [];			
			foreach ($fields as $field) {
				if (!in_array($field['name'], $escludeField)) {
					if ($field['type']["name"] == "reference" || $field['type']["name"] == "owner") {
						$this->fieldsRelate[$field['name']] = array(
													'label' => $field['label'],
													'required' => $field['mandatory'],
													'type' => 'varchar(127)', // ? Settare il type giusto?
													'type_bdd' => 'varchar(127)',
													'required_relationship' => 0
												);
					} else {
						$this->moduleFields[$field['name']] = [
													'label'    => $field['label'],
													'required' => $field['mandatory'],
													'type' => 'varchar(127)', // ? Settare il type giusto?
													'type_bdd' => 'varchar(127)'
												];
						if ($field['type']["name"] == "picklist" || $field['type']["name"] == "multipicklist") {
							foreach ($field['type']["picklistValues"] as $option) {
								$this->moduleFields[$field['name']]["option"][$option["value"]] = $option["label"];
							}
						}
					}
				}
			}

			if (!empty($this->fieldsRelate)) {
				$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
			}

			return $this->moduleFields ?: false;
		} catch (\Exception $e) {		
			$this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());		
			return false;
		}	
    }

    /**
     * Read Last
     *
     * @param array $param
     * @return array
     */
    public function read_last($param)
    {			
		try {
			if (empty($this->vtigerClient)) {
				return [
					'error' => 'Error: no VtigerClient setup',
					'done'  => -1,
				];
			}

			if (empty($this->moduleList)) {
				$this->setModulePrefix();
			}

			$queryParam = implode(',', $param['fields'] ?? "") ?: '*';
			$where = '';
			if (!empty($param['query'])) {
				$where = 'WHERE ';
				foreach ($param['query'] as $key => $item) {
					if (substr($where, -strlen("'")) === "'") {
						$where .= ' AND ';
					}
					$where .= "$key = '$item'";
				}
			}
			if ($param["module"] == "LineItem") {
				$query = $this->vtigerClient->query("SELECT parent_id FROM $param[module] $where;");

				$parentModules = [];
				foreach ($query['result'] as $parent) {
					$prefix = explode("x", $parent["parent_id"])[0];
					if (!array_key_exists($prefix, $parentModules)) {
						$parentModules[$prefix] = $this->moduleList[$prefix];
					}
				}

				$entity = [];
				$maxtime = "";
				foreach ($parentModules as $prefix => $moduleName) {
					$query = $this->vtigerClient->query("SELECT id, createdtime, modifiedtime FROM $moduleName $where ORDER BY modifiedtime ASC LIMIT 0, 1;");
					if (empty($query) || !$query['success']) {
						continue;
					}

					foreach ($query["result"] as $parentElement) {
						if (empty($maxtime) || $maxtime < $parentElement["modifiedtime"]) {
							$maxtime = $parentElement["modifiedtime"];
							$retrive = $this->vtigerClient->retrieve($parentElement["id"]);
							foreach ($retrive["result"]["LineItems"] as $index => $lineitem) {
								$lineitem["parent_id"] = $parentElement["id"];
								$lineitem["modifiedtime"] = $parentElement["modifiedtime"];
								$lineitem["createdtime"] = $parentElement["createdtime"];
								$entity[] = $lineitem;
							}
						}
					}
				}

				$query = ["success" => true, "result" => $entity];
			}
			else {
				// If we search a specific record
				if (!empty($param['query']['id'])) {
					$query = $this->vtigerClient->retrieve($param['query']['id']);
					// Add a dimension to the result to have the same format than the other call below
					if (!empty($query)) {
						$query['result'][0] = $query['result'];
					}
				} else {					
					$query = $this->vtigerClient->query("SELECT $queryParam FROM $param[module] $where ORDER BY modifiedtime DESC LIMIT 0,1;");
				}
			}

			if (empty($query) || (!empty($query) && !$query['success'])) {
				return [
							'error' => 'Error: Request Failed!',
							'done'  => -1,
						];
			}

			if (count($query['result']) == 0) {
				return [
							'error' => 'No Data Retrived',
							'done'  => false,
						];
			}

			$fields = $query['result'][0];
			$result = ['done' => true];

			foreach ($fields as $fieldName => $value) {
				$result['values'][$fieldName] = $value;
			}

			/*
			if(in_array($param['rule']['mode'], ["0", "S"])) {
				$result['values']['date_modified'] = $fields['modifiedtime'];
			} else if ($param['rule']['mode'] == "C") {
				$result['values']['date_modified'] = $fields['createdtime'];
			}
			*/
		} catch (\Exception $e) {
			$result['error'] = 'Error : ' . $e->getMessage().' '.$e->getFile().' '.$e->getLine();
			$result['done'] = -1;
		}
		return $result;
    }

    /**
     * Read
     *
     * @param array $param
     * @return array
     */
    public function read($param)
    {		
		try {
			if (empty($this->vtigerClient)) {
				return [
							'error' => 'Error: no VtigerClient setup',
							'done'  => false,
						];
			}

			if (count($param['fields']) == 0) {
				return [
							'error' => 'Error: no Param Given',
							'done'  => false,
						];
			}

			if (empty($param['offset'])) {
				$param['offset'] = 0;
			}
			if (empty($param['limit'])) {
				$param['limit'] = 100;
			}

			$queryParam = implode(',', $param['fields'] ?? "") ?: '*';
			if ($queryParam != '*') {
				$requiredField = $this->required_fields[$param['module']] ?? $this->required_fields['default'];
				$queryParam = implode(',', $requiredField).','.$queryParam;
				$queryParam = str_replace(["my_value,", "my_value"], "", $queryParam);
			}
			$queryParam = rtrim($queryParam, ',');
			
			$where = !empty($param['date_ref']) ? "WHERE modifiedtime > '$param[date_ref]'" : '';		
			if (!empty($param['query'])) {
				$where .= empty($where) ? 'WHERE ' : ' AND ';		
				foreach ($param['query'] as $key => $item) {
					// if id we don't add other filter, we keep only id
					if ($key == 'id') {
						$where = " WHERE id = '$item' ";
						break;
					}
					if (substr($where, -strlen("'")) === "'") {
						$where .= ' AND ';
					}
					$where .= "$key = '$item'";			
				}
			}		

			/** @var array $result */
			$result = [
				'count' => 0,
			];


			$orderby = "ORDER BY modifiedtime ASC";;
			if (in_array($param["module"], $this->inventoryModules, true)) {
				$orderby = "";
			}

			$dataLeft = $param["limit"];
			do {
				$nDataCall = $dataLeft - $this->limitPerCall <= 0 ? $dataLeft : $this->limitPerCall;
				// TODO: Considerare di implementare Sync API in VtigerClient
				if ($param["module"] == "LineItem") {
					if (empty($this->moduleList)) {
						$this->setModulePrefix();
					}
					
					$query = $this->vtigerClient->query("SELECT parent_id FROM $param[module];");

					$parentModules = [];
					foreach ($query['result'] as $parent) {
						$prefix = explode("x", $parent["parent_id"])[0];
						if (!array_key_exists($prefix, $parentModules)) {
							$parentModules[$prefix] = $this->moduleList[$prefix];
						}
					}

					$entitys = [];
					foreach ($parentModules as $prefix => $moduleName) {
						$query = $this->vtigerClient->query("SELECT id, modifiedtime, createdtime FROM $moduleName $where $orderby LIMIT $param[offset], $nDataCall;");
						if (empty($query) || !$query['success']) {
							continue;
						}

						foreach ($query["result"] as $parentElement) {
							$retrive = $this->vtigerClient->retrieve($parentElement["id"]);
							foreach ($retrive["result"]["LineItems"] as $index => $lineitem) {
								if ($index == 0) {
									continue;
								}
								$lineitem["parent_id"] = $parentElement["id"];
								$lineitem["modifiedtime"] = $parentElement["modifiedtime"];
								$lineitem["createdtime"] = $parentElement["createdtime"];
								$entitys[] = $lineitem;
							}
						}
					}

					$query = ["success" => true, "result" => $entitys];
				}
				else {					
					$query = $this->vtigerClient->query("SELECT $queryParam FROM $param[module] $where $orderby LIMIT $param[offset], $nDataCall;");
				}
				if (empty($query) || (!empty($query) && !$query['success'])) {
					return [
								'error' => 'Error: Request Failed! (' . ($query["error"]["message"] ?? "Error") . ')',
								'count' => 0,
							];
				}

				if (count($query['result']) == 0) {
					break;
				}

				$countResult = 0;
				$entitys = $query['result'];
				foreach ($entitys as $value) {
					if (!isset($result['values']) || !array_key_exists($value['id'], $result['values'])) {
						$result['date_ref'] = $value['modifiedtime'];
						$result['values'][$value['id']] = $value;
						if(in_array($param['rule']['mode'], ["0", "S"])) {
							$result['values'][$value['id']]["date_modified"] = $value['modifiedtime'];
						} else if ($param['rule']['mode'] == "C") {
							$result['values'][$value['id']]["date_modified"] = $value['createdtime'];
						}
						$result['count']++;
						$countResult++;
					}
				}

				$param["offset"] += $nDataCall;
				$dataLeft -= $nDataCall;

			} while ($dataLeft > 0 && $countResult >= $nDataCall);
		} catch (\Exception $e) {
			$result['error'] = 'Error : ' . $e->getMessage().' '.$e->getFile().' '.$e->getLine();
		}	
        return $result;
    }

    /**
     * Create new record in target
     *
     * @param array $param
     * @return array
     */
    public function create($param)
    {		
		try {
			$subDocIdArray = array();
			if (empty($this->vtigerClient)) {
				return ['error' => 'Error: no VtigerClient setup'];
			}

			$result = [];

			$lineItemFields = [];
			if (in_array($param['module'], $this->inventoryModules, true)) {
				$describe = $this->vtigerClient->describe("LineItem");

				foreach ($describe["result"]["fields"] as $field) {
					$lineItemFields[] = $field["name"];
				}
			}

			foreach ($param['data'] as $idDoc => $data) {
				try {
					// Clean record by removing Myddleware fields (ex : target_id)
					$data = $this->cleanRecord($param, $data);
					
					// In case of LineItem (sub array in the data array => an order can have seeral orderItems), 
					// We transform the lineItem array into a LineItems array with the right format
					if (!empty(	$data['LineItem'])) {
						foreach($data['LineItem'] as $subIdDoc => $childRecord) {
							// Save the subIdoc to change the sub data transfer status
							$subDocIdArray[$subIdDoc] = array('id' => uniqid('', true));
							// Clean subrecord by removing Myddleware fields (ex : target_id)
							$childRecord = $this->cleanRecord($param, $childRecord);
							$data['LineItems'][] = $childRecord;
						}
						// Add the product at the order level (work around because of an issue in Vtiger API)
						$data['productid'] = $childRecord['productid'];
						unset($data['LineItem']);
					}	
				
					if (!empty($lineItemFields) && in_array($param['module'], $this->inventoryModules, true)) {
						foreach ($data as $inventorykey => $inventoryValue) {
							if (in_array($inventorykey, $lineItemFields, true) && $inventorykey != "id") {
								$data["LineItems"][0][$inventorykey] = $inventoryValue;
							}
						}
						if (!isset($data["LineItems"][0]["sequence_no"])) {
							$data["LineItems"][0]["sequence_no"] = 1;
						}
					}

					$resultCreate = $this->vtigerClient->create($param['module'], $data);


					if (!empty($resultCreate) && $resultCreate['success'] && !empty($resultCreate['result'])) {
						if ($param['module'] == "LineItem") {
							if (empty($this->moduleList)) {
								$this->setModulePrefix();
							}
							$parent = $this->vtigerClient->retrieve($resultCreate['result']["parent_id"]);
							$parent = $parent["result"];
							if (!isset($parent["invoicestatus"]) || empty($parent["invoicestatus"])) {
								$parent["invoicestatus"] = "AutoCreated";
							}
							unset($parent["LineItems_FinalDetails"]);
							$r = $this->vtigerClient->update($this->moduleList[explode("x", $parent["id"])[0]], $parent);
						}
						$result[$idDoc] = [
											'id'    => $resultCreate['result']['id'],
											'error' => false,
										];
					} else {
						throw new \Exception($resultCreate["error"]["message"] ?? "Error");
					}
				} catch (\Exception $e) {
					$result[$idDoc] = array(
						'id' => '-1',
						'error' => $e->getMessage()
					);
				}
				// Transfert status update
				if (
						!empty($subDocIdArray)
					AND empty($result[$idDoc]['error'])
				) {				
					foreach($subDocIdArray as $idSubDoc => $valueSubDoc) {				
						$this->updateDocumentStatus($idSubDoc,$valueSubDoc,$param);
					}
				}
				$this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
			}
		} catch (\Exception $e) {
			$error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
			$result['error'] = $error;
		}	
		return $result;
    }

    /**
     * Update exist record in target
     *
     * @param array $param
     * @return array
     */
    public function update($param)
    {		
		try {
			$subDocIdArray = array();
			if (empty($this->vtigerClient)) {
				return ['error' => 'Error: no VtigerClient setup'];
			}

			$result = [];

			$lineItemFields = [];
			if (in_array($param['module'], $this->inventoryModules, true)) {
				$describe = $this->vtigerClient->describe("LineItem");

				foreach ($describe["result"]["fields"] as $field) {
					$lineItemFields[] = $field["name"];
				}
			}

			foreach ($param['data'] as $idDoc => $data) {
				try {
					$data['id'] = $data['target_id'];
					// Clean record by removing Myddleware fields (ex : target_id)
					$data = $this->cleanRecord($param, $data);
					
					// In case of LineItem (sub array in the data array => an order can have seeral orderItems), 
					// We transform the lineItem array into a LineItems array with the right format
					if (!empty(	$data['LineItem'])) {
						foreach($data['LineItem'] as $subIdDoc => $childRecord) {
							// Save the subIdoc to change the sub data transfer status
							$subDocIdArray[$subIdDoc] = array('id' => uniqid('', true));
							// Clean subrecord by removing Myddleware fields (ex : target_id)
							$childRecord = $this->cleanRecord($param, $childRecord);
							$data['LineItems'][] = $childRecord;
						}
						// Add the product at the order level (work around because of an issue in Vtiger API)
						$data['productid'] = $childRecord['productid'];
						unset($data['LineItem']);
					}	

					if (!empty($lineItemFields) && in_array($param['module'], $this->inventoryModules, true)) {
						foreach ($data as $inventorykey => $inventoryValue) {
							if (in_array($inventorykey, $lineItemFields, true) && $inventorykey != "id") {
								$data["LineItems"][0][$inventorykey] = $inventoryValue;
							}
						}
						if (!isset($data["LineItems"][0]["sequence_no"])) {
							$data["LineItems"][0]["sequence_no"] = 1;
						}
					}

					$resultUpdate = $this->vtigerClient->update($param['module'], $data);

					if (!empty($resultUpdate) && $resultUpdate['success'] && !empty($resultUpdate['result'])) {
						if ($param['module'] == "LineItem") {
							if (empty($this->moduleList)) {
								$this->setModulePrefix();
							}							
							$parent = $this->vtigerClient->retrieve($resultUpdate['result']["parent_id"]);
							$parent = $parent["result"];
							if (!isset($parent["invoicestatus"]) || empty($parent["invoicestatus"])) {
								$parent["invoicestatus"] = "AutoCreated";
							}
							unset($parent["LineItems_FinalDetails"]);
							$r = $this->vtigerClient->update($this->moduleList[explode("x", $parent["id"])[0]], $parent);
						}
						$result[$idDoc] = [
											'id'    => $resultUpdate['result']['id'],
											'error' => false,
										];
					} else {
						throw new \Exception($resultUpdate["error"]["message"] ?? "Error");
					}
				} catch (\Exception $e) {
					$result[$idDoc] = array(
						'id' => '-1',
						'error' => $e->getMessage()
					);
				}
				// Transfert status update
				if (
						!empty($subDocIdArray)
					AND empty($result[$idDoc]['error'])
				) {				
					foreach($subDocIdArray as $idSubDoc => $valueSubDoc) {				
						$this->updateDocumentStatus($idSubDoc,$valueSubDoc,$param);
					}
				}
				$this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
			}
		} catch (\Exception $e) {
			$error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
			$result['error'] = $error;
		}	
		return $result;
    }
	
	// Clean a record by removing all Myddleware fields
	protected function cleanRecord($param, $data) {
		$myddlewareFields = array('target_id', 'source_date_modified', 'id_doc_myddleware','Myddleware_element_id');
		foreach ($myddlewareFields as $myddlewareField) {
			if (array_key_exists($myddlewareField, $data)) {
				unset($data[$myddlewareField]);
			}
		}
		return $data;
	}
	
	// Build the direct link to the record (used in data transfer view)
	public function getDirectLink($rule, $document, $type){		
		// Get url, module and record ID depending on the type
		if ($type == 'source') {
			$url = $this->getConnectorParam($rule->getConnectorSource(), 'url');
			$module = $rule->getModuleSource();
			$recordId = $document->getSource();
		} else {
			$url = $this->getConnectorParam($rule->getConnectorTarget(), 'url');
			$module = $rule->getModuleTarget();
			$recordId = $document->gettarget();
		}	
		
		// Get the record id, id format <key>x<recordId>
		$recordIdArray = explode('x',$recordId);
		if (!empty($recordIdArray[1])) {
			// Build the URL (delete if exists / to be sure to not have 2 / in a row) 
			return rtrim($url,'/').'/index.php?module='.$module.'&view=Detail&record='.$recordIdArray[1];
		} 
		return null;
	}
	
}

/* * * * * * * *  * * * * * *  * * * * * *
    si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/vtigercrm.php';
if (file_exists($file)) {
    require_once $file;
} else {
    //Sinon on met la classe suivante
    class vtigercrm extends vtigercrmcore
    {
    }
}