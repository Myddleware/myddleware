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

	protected $force_required_module_fields = [
		'default'    => [],
		'LineItem'   => [
			'productid',
			'quantity',
			'quantity',
			'parent_id',
			'sequence_no',
			'listprice'
		],
	];

	protected $FieldsDuplicate = [
		'Contacts' => ['email', 'lastname'],
		'CompanyDetails' => ['organizationname'],
		'Leads' => ['email', 'lastname'],
		'Accounts' => ['accountname'],
		'default' => []
	];

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
			'source'     => [],
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
	];


	/** @var array $moduleList */
	protected $moduleList;

	/** @var VtigerClient $vtigerClient */
	protected $vtigerClient;



	public function getVtigerClient()
	{
		return $this->vtigerClient;
	}

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
		// Vtiger Logout doesn't work
		/*
        if (empty($this->vtigerClient))
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
		} else {
			$describe = $this->vtigerClient->describe($moduleName);
			if ($describe['success'] && count($describe['result']) != 0) {
				$this->moduleList[$describe["result"]["idPrefix"]] = $moduleName;
				return true;
			} else {
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

			$excludeFields = $this->exclude_field_list[$module] ?? $this->exclude_field_list['default'];
			$excludeFields = $excludeFields[$type] ?? $excludeFields['default'];
			$requiredFields = $this->force_required_module_fields[$module] ?? [];
			$this->moduleFields = [];
			foreach ($fields as $field) {
				if (!in_array($field['name'], $excludeFields)) {
					$mandatory = $field['mandatory'] || in_array($field['name'], $requiredFields, true);
					if ($field['type']["name"] == "reference" || $field['type']["name"] == "owner") {
						$this->fieldsRelate[$field['name']] = array(
							'label' => $field['label'],
							'required' => $mandatory,
							'type' => 'varchar(127)', // ? Set right type?
							'type_bdd' => 'varchar(127)',
							'required_relationship' => 0
						);
					} else {
						$this->moduleFields[$field['name']] = [
							'label'    => $field['label'],
							'required' => $mandatory,
							'type' => 'varchar(127)', // ? Set right type?
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
			$this->logger->error($e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine());
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

			$param['field'] = $this->cleanMyddlewareElementId($param['field'] ?? []);
			$queryParam = implode(',', $param['fields'] ?? "") ?: '*';
			$where = '';
			if (!empty($param['query'])) {
				$where = [];
				foreach ($param['query'] as $key => $item) {
					$where[] = "$key = '$item'";
				}
				$where = "WHERE " . implode(" AND ", $where);
			}

			if ($param["module"] == "LineItem") {
				$query = $this->vtigerClient->query("SELECT parent_id FROM $param[module] $where;");

				$parentModules = [];
				foreach ($query['result'] as $parent) {
					if (empty($parent["parent_id"])) {
						continue;
					}
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
			} else {
				$query = $this->vtigerClient->query("SELECT $queryParam FROM $param[module] $where ORDER BY modifiedtime DESC LIMIT 0,1;");
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
			$result['error'] = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
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
				$param['limit'] = $this->limitPerCall;
			}

			$deletion = false;
			if (isset($param['ruleParams']['deletion']) && !empty($param['ruleParams']['deletion'])) {
				$deletion = true;
			}

			/** @var array $result */
			$result = [
				'count' => 0,
			];

			if ($param['module'] == 'LineItem' && $deletion) {
				return $result;
			} elseif ($param['module'] == 'LineItem') {
				$whereStr = !empty($param['date_ref']) ? "WHERE modifiedtime > '$param[date_ref]'" : '';
				if (!empty($param['query'])) {
					$whereStr = [];
					if (!array_key_exists('id', $param['query'])) {
						foreach ($param['query'] as $key => $item) {
							$whereStr[] = "$key = '$item'";
						}
						$whereStr = (empty($whereStr) ? 'WHERE ' : ' AND ') . implode(" AND ", $whereStr);
					} else {
						$whereStr = (empty($whereStr) ? 'WHERE ' : ' AND ') . "id = '" . $param['query']['id'] . "'";
					}
				}
			}

			do {
				$more = true;
				$entitys = [];

				if ($param['module'] == 'LineItem') {
					if (empty($this->moduleList)) {
						$this->setModulePrefix();
					}

					$query = $this->vtigerClient->query("SELECT parent_id FROM $param[module];");

					$parentModules = [];
					foreach ($query['result'] as $parent) {
						if (empty($parent["parent_id"])) {
							continue;
						}
						$prefix = explode("x", $parent["parent_id"])[0];
						if (!array_key_exists($prefix, $parentModules)) {
							$parentModules[$prefix] = $this->moduleList[$prefix];
						}
					}

					$lineitems = [];
					foreach ($parentModules as $prefix => $moduleName) {
						$query = $this->vtigerClient->query("SELECT id, modifiedtime, createdtime FROM $moduleName $whereStr LIMIT $param[offset], " . $this->limitPerCall . ";");
						if (empty($query) || !$query['success']) {
							continue;
						}

						foreach ($query["result"] as $parentElement) {
							$retrive = $this->vtigerClient->retrieve($parentElement["id"]);
							foreach ($retrive["result"]["LineItems"] as $lineitem) {
								$lineitem["parent_id"] = $parentElement["id"];
								$lineitem["modifiedtime"] = $parentElement["modifiedtime"];
								$lineitem["createdtime"] = $parentElement["createdtime"];
								$lineitems[] = $lineitem;
							}
						}
					}

					$entitys = $lineitems;
					$more = count($entitys) != 0;
				} else {
					$dateRef = !empty($param['date_ref']) ? strtotime($param['date_ref']) : 1;
					$sync = $this->vtigerClient->sync($param['module'], $dateRef);
					if (empty($sync) || !$sync['success']) {
						return [
							'error' => 'Error: Request Failed! (' . ($sync['error']['message'] ?? 'Error') . ')',
							'count' => 0,
						];
					}

					if (!empty($param['query']) && !$deletion) {
						// $iterable = !$deletion ? $sync['result']['updated'] : $sync['result']['deleted'];
						$iterable = $sync['result']['updated'];
						foreach ($iterable as $item) {
							if (!array_key_exists('id', $param['query'])) {
								$all = true;
								foreach ($param['query'] as $key => $item) {
									if ($item[$key] != $item) {
										$all = false;
										break;
									}
								}
								if ($all) {
									$entitys[] = $item;
								}
							} else {
								if ($item['id'] == $param['query']['id']) {
									$entitys[] = $item;
								}
							}
						}
					} else {
						$entitys = !$deletion ? $sync['result']['updated'] : $sync['result']['deleted'];
					}
					if (in_array($param['module'], $this->inventoryModules, true) && !$deletion) {
						foreach ($entitys as &$entity) {
							$ret = $this->vtigerClient->retrieve($entity['id']);
							if (empty($ret) || !$ret['success']) {
								continue;
							}
							$entity = array_merge($ret['result']['LineItems'][0], $entity);
						}
						unset($entity);
					}
					$more = $sync['result']['more'];
					$lastModifiedTime = $sync['result']['lastModifiedTime'];
				}

				if (!$deletion) {
					foreach ($entitys as $value) {
						if (!isset($result['values']) || !array_key_exists($value['id'], $result['values'])) {
							$result['values'][$value['id']] = $value;
							$result['date_ref'] = $value['modifiedtime'];
							$result['values'][$value['id']]['date_modified'] = $value[$this->getDateRefName($param['module'], $param['rule']['mode'])];

							$result['count']++;

							if ($result['count'] >= $param['limit']) {
								$more = false;
								break;
							}
						}
					}
				} else {
					foreach ($entitys as $value) {
						if (!isset($result['values']) || !array_key_exists($value, $result['values'])) {
							$result['values'][$value] = ['id' => $value, 'myddleware_deletion' => true];
							$result['date_ref'] = $lastModifiedTime;
							$result['values'][$value]['date_modified'] = $lastModifiedTime;


							$result['count']++;

							if ($result['count'] >= $param['limit']) {
								$more = false;
								break;
							}
						}
					}
				}
				$param['offset'] += count($entitys);
			} while ($more);
		} catch (\Exception $e) {
			$result['error'] = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
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
		//var_dump($param);
		try {
			if (empty($this->vtigerClient)) {
				throw new \Exception('Error: no VtigerClient setup');
			}

			$result = [];

			if ($param['module'] != 'LineItem') {
				$lineItemFields = [];
				if (in_array($param['module'], $this->inventoryModules, true)) {
					$describe = $this->vtigerClient->describe("LineItem");

					foreach ($describe["result"]["fields"] as $field) {
						$lineItemFields[] = $field["name"];
					}
				}
				foreach ($param['data'] as $idDoc => $data) {
					try {
						unset($data['target_id']);

						if (!empty($lineItemFields)) {
							foreach ($data as $inventorykey => $inventoryValue) {
								if (in_array($inventorykey, $lineItemFields, true) && $inventorykey != "id") {
									$data["LineItems"][0][$inventorykey] = $inventoryValue;
								}
							}
							if (!isset($data["LineItems"][0]["sequence_no"])) {
								$data["LineItems"][0]["sequence_no"] = 1;
							}

							$data["hdnTaxType"] = (($data["hdnTaxType"] ?? "") ?: "group");
						}

						$resultCreate = $this->vtigerClient->create($param['module'], $data);

						if (!empty($resultCreate) && $resultCreate['success'] && !empty($resultCreate['result'])) {
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
					$this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
				}
			} else {
				$parents = [];
				foreach ($param['data'] as $idDoc => $data) {
					if (!in_array($data['parent_id'], array_keys($parents))) {
						$ret = $this->vtigerClient->retrieve($data['parent_id']);
						if (!empty($ret) && $ret['success']) {
							$parents[$data['parent_id']] = $ret['result'];
						}
					}
				}
				foreach ($parents as &$parent) {
					while (!empty($parent['LineItems'])) {
						$this->vtigerClient->delete($parent['LineItems'][0]['id']);
						$ret = $this->vtigerClient->retrieve($parent['id']);
						if (!empty($ret) && $ret['success']) {
							$parent['LineItems'] = $ret['result']['LineItems'];
						}
					}
				}
				unset($parent);

				foreach ($parents as $parent) {
					$lineitems = [];
					foreach ($param['data'] as $idDoc => $data) {
						if ($data['parent_id'] == $parent['id']) {
							unset($data['target_id']);
							unset($data['parent_id']);
							$lineitems[$idDoc] = $data;
						}
					}
					$resultUpdate = null;
					if (!empty($lineitems)) {
						if (!isset($parent["invoicestatus"]) || empty($parent["invoicestatus"])) {
							$parent["invoicestatus"] = "AutoCreated";
						}
						unset($parent["LineItems_FinalDetails"]);
						$parent['LineItems'] = [];
						foreach ($lineitems as $lineItem) {
							$parent['LineItems'][] = $lineItem;
						}

						$resultUpdate = $this->vtigerClient->update($parent["id"], $parent);
						if (!empty($resultUpdate) && $resultUpdate['success'] && !empty($resultUpdate['result'])) {
							$retrive = $this->vtigerClient->retrieve($resultUpdate['result']['id']);
							if (!empty($ret) && $ret['success']) {
								foreach ($lineitems as $idDoc => $lineitem) {
									foreach ($retrive['result']['LineItems'] as $retriveLineItem) {
										if ($retriveLineItem['sequence_no'] == $lineitem['sequence_no']) {
											$result[$idDoc] = [
												'id'    => $retriveLineItem['id'],
												'error' => false,
											];
											$this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
										}
									}
								}
							}
						} else {
							foreach ($lineitems as $idDoc => $lineItem) {
								$result[$idDoc] = [
									'id' => '-1',
									'error' => $resultUpdate["error"]["message"]
								];
								$this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
							}
						}
					}
				}
			}
		} catch (\Exception $e) {
			$error = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
			$result['error'] = $error;
		}
		return $result;
	}

	/**
	 * Update existing record in target
	 *
	 * @param array $param
	 * @return array
	 */
	public function update($param)
	{
		try {
			if (empty($this->vtigerClient)) {
				throw new \Exception('Error: no VtigerClient setup');
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
					unset($data['target_id']);

					if (!empty($lineItemFields) && in_array($param['module'], $this->inventoryModules, true)) {
						foreach ($data as $inventorykey => $inventoryValue) {
							if (in_array($inventorykey, $lineItemFields, true) && $inventorykey != "id") {
								$data["LineItems"][0][$inventorykey] = $inventoryValue;
							}
						}
						if (!isset($data["LineItems"][0]["sequence_no"])) {
							$data["LineItems"][0]["sequence_no"] = 1;
						}
						$data["hdnTaxType"] = (($data["hdnTaxType"] ?? "") ?: "group");
					}

					$resultUpdate = $this->vtigerClient->update($param['module'], $data);

					if (!empty($resultUpdate) && $resultUpdate['success'] && !empty($resultUpdate['result'])) {
						$result[$idDoc] = [
							'id'    => $resultUpdate['result']['id'],
							'error' => false,
						];
					} else {
						throw new \Exception($resultUpdate["error"]["message"] ?? "Error");
					}
				} catch (\Exception $e) {
					$result[$idDoc] = [
						'id' => '-1',
						'error' => $e->getMessage()
					];
				}
				$this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
			}
		} catch (\Exception $e) {
			$error = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
			$result['error'] = $error;
		}
		return $result;
	}

	/**
	 * Delete existing record in target
	 *
	 * @param array $param
	 * @return array
	 */
	public function delete($param)
	{
		try {
			if (empty($this->vtigerClient)) {
				throw new \Exception('Error: no VtigerClient setup');
			}

			$result = [];

			if ($param['module'] == 'LineItem') {
				$result = $this->update($param);
			} else {
				foreach ($param['data'] as $idDoc => $data) {
					try {
						$id = $data['target_id'];

						$resultDelete = $this->vtigerClient->delete($id);

						if (
							!empty($resultDelete) &&
							(!(isset($resultDelete['success']) && !$resultDelete['success']) &&
								(isset($resultDelete['status']) && $resultDelete['status'] == 'successful'))
						) {
							$result[$idDoc] = [
								'id'    => $id,
								'error' => false,
							];
						} else {
							throw new \Exception($resultDelete["error"]["message"] ?? "Error");
						}
					} catch (\Exception $e) {
						$result[$idDoc] = array(
							'id' => '-1',
							'error' => $e->getMessage()
						);
					}
					$this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
				}
			}
		} catch (\Exception $e) {
			$error = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
			$result['error'] = $error;
		}
		return $result;
	}


	public function getRuleMode($module, $type)
	{
		if ($module == 'LineItem' && $type == 'target') {
			return ['C' => 'create_only'];
		}

		return [
			'0' => 'create_modify',
			'C' => 'create_only'
		];
	}

	public function getDateRefName($moduleSource, $RuleMode)
	{
		if (in_array($RuleMode, ['0', 'S'])) {
			return 'modifiedtime';
		} else if ($RuleMode == 'C') {
			return 'createdtime';
		} else {
			throw new \Exception("$RuleMode is not a correct Rule mode.");
		}
	}

	// Build the direct link to the record (used in data transfer view)
	public function getDirectLink($rule, $document, $type)
	{
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
		$recordIdArray = explode('x', $recordId);
		if (substr($url, 0, strlen("http")) !== "http") {
			$url = 'http://' . $url;
		}
		if (!empty($recordIdArray[1])) {
			// Build the URL (delete if exists / to be sure to not have 2 / in a row) 
			return rtrim($url, '/') . '/index.php?module=' . $module . '&view=Detail&record=' . $recordIdArray[1];
		}
		return null;
	}
}

/* * * * * * * *  * * * * * *  * * * * * *
    si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__ . '/../Custom/Solutions/vtigercrm.php';
if (file_exists($file)) {
	require_once $file;
} else {
	//Sinon on met la classe suivante
	class vtigercrm extends vtigercrmcore
	{
	}
}
