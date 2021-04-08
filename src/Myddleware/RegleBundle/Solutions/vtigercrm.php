<?php

/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 *
 * This file is part of Myddleware.
 *
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

namespace Myddleware\RegleBundle\Solutions;

use Javanile\VtigerClient\VtigerClient;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class vtigercrmcore extends solution
{
    /**
     * Limit number of element per API call.
     *
     * @var int
     */
    protected $limitPerCall = 100;

    /**
     * Required fields
     *
     * @var string[][]
     */
    protected $required_fields = [
        'default' => [
            'id',
            'modifiedtime',
            'createdtime'
        ],
    ];

    /**
     * Per-module required fields.
     *
     * @var array
     */
    protected $force_required_module_fields = [
        'default' => [],
        'LineItem' => [
            'productid',
            'quantity',
            'quantity',
            'parent_id',
            'sequence_no',
            'listprice'
        ],
    ];

    /**
     * Control duplicate fields.
     *
     * @var array
     */
    protected $FieldsDuplicate = [
        'Contacts' => ['email', 'lastname'],
        'CompanyDetails' => ['organizationname'],
        'Leads' => ['email', 'lastname'],
        'Accounts' => ['accountname'],
        'default' => []
    ];

    /**
     * Excluded modules.
     *
     * @var array
     */
    protected $exclude_module_list = [
        'default' => ['Users', "Documents"],
        'target' => [],
        'source' => [],
    ];

    /**
     * Excluded fields.
     *
     * @var array[]
     */
    protected $exclude_field_list = [
        'default' => [
            'default' => [
                'id'
            ],
            'source' => [],
            'target' => [
                'id',
                'modifiedby',
                'modifiedtime',
                "createdtime"
            ],
        ],
    ];

    /**
     * Inventory modules
     *
     * @var string[]
     */
    protected $inventoryModules = [
        'Invoice',
        'SalesOrder',
        'Quotes',
        'PurchaseOrder'
    ];

    /**
     * Module list that allows to make parent relationships
     *
     * @var string[]
     */
    protected $allowParentRelationship = [
        'Invoice',
        'Quotes',
        'SalesOrder',
        'PurchaseOrder'
    ];

    /**
     * Current module list.
     *
     * @var array $moduleList
     */
    protected $moduleList;

    /**
     * Current vtiger client.
     *
     * @var VtigerClient $vtigerClient
     */
    protected $vtigerClient;

    /**
     * @return VtigerClient
     * @throws \Exception
     */
    protected function createVtigerClient()
    {
        $client = new VtigerClient([
            'endpoint' => $this->paramConnexion['url'],
            'verify' => false,
        ]);
        //file_put_contents('/var/www/html/var/logs/vtigercrm.0.log', __FILE__.':'.__LINE__."\n", FILE_APPEND);
        $result = $client->login(trim($this->paramConnexion['username']), trim($this->paramConnexion['accesskey']));

        if (empty($result['success'])) {
            throw new \Exception($result['error']['message']);
        }

        return $client;
    }

    /**
     * @param $vtigerClient
     * @return VtigerClient
     */
    protected function setVtigerClient($vtigerClient)
    {
        $this->vtigerClient = $vtigerClient;
        $this->session = $vtigerClient->getSessionName();
        $this->connexion_valide = true;
    }

    /**
     * @return VtigerClient
     */
    protected function getVtigerClient()
    {
        return $this->vtigerClient;
    }

    /**
     * @return VtigerClient
     */
    protected function notVtigerClient()
    {
        return empty($this->vtigerClient);
    }

    /**
     * Make the login
     *
     * @param $paramConnexion
     * @return void|array
     */
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);

        try {
            $client = $this->createVtigerClient();
            $this->setVtigerClient($client);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Make the logout
     *
     * @return bool
     */
    public function logout()
    {
        // TODO: Vtiger Logout doesn't work.
        /*
        if ($this->notVtigerClient()) {
            return false;
        }

        return $this->getVtigerClient()->logout();
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
                'name' => 'username',
                'type' => TextType::class,
                'label' => 'solution.fields.username',
            ],
            [
                'name' => 'accesskey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.accesskey',
            ],
            [
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
        ];
    }

    /**
     * Return of the modules without the specified ones.
     *
     * @param string $type
     * @return array|bool
     */
    public function get_modules($type = 'source')
    {
        if ($this->notVtigerClient()) {
            return false;
        }

        try {
            return $this->getVtigerModules($type) ?: false;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param string $type
     * @return false
     */
    protected function getVtigerModules($type = 'source')
    {
        $result = $this->getVtigerClient()->listTypes();
        if (empty($result['success']) || empty($result['result']) || count($result['result']) == 0) {
            return false;
        }

        $currentModules = [];
        $excludedModules = $this->exclude_module_list[$type] ?: $this->exclude_module_list['default'];

        foreach ($result['result']['information'] as $moduleName => $moduleInfo) {
            if (in_array($moduleName, $excludedModules)) {
                continue;
            }
            $currentModules[$moduleName] = $moduleInfo['label'];
            $this->setModulePrefix($moduleName);
        }

        return $currentModules ?: false;
    }

    /**
     * Populate $moduleList with current module prefix.
     *
     * @return void
     */
    public function setAllModulesPrefix()
    {
        $result = $this->getVtigerClient()->listTypes();
        if (empty($result['success']) || empty($result['result']) || count($result['result']) == 0) {
            return;
        }

        $this->moduleList = [];
        foreach ($result['result']['types'] as $moduleName) {
            $describe = $this->getVtigerClient()->describe($moduleName);
            if ($describe['success'] && count($describe['result']) > 0) {
                $this->moduleList[$describe['result']['idPrefix']] = $moduleName;
            }
        }
    }

    /**
     * @param null $moduleName
     * @return bool
     */
    protected function setModulePrefix($moduleName)
    {
        $describe = $this->getVtigerClient()->describe($moduleName);

        if (empty($describe['success']) || empty($describe['result']['idPrefix'])) {
            return;
        }

        $this->moduleList[$describe['result']['idPrefix']] = $moduleName;
    }

    /**
     * Return the fields for a specific module without the specified ones.
     *
     * @param string $module
     * @param string $type
     *
     * @return array|bool
     */
    public function get_module_fields($module, $type = 'source')
    {
        parent::get_module_fields($module, $type);

        if ($this->notVtigerClient()) {
            return false;
        }

        try {
            return $this->populateModuleFieldsFromVtigerModule($module, $type) ?: false;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine());
            return false;
        }
    }

    /**
     * Fill the local attribute moduleFields
     *
     * @param $module
     * @param string $type
     * @return array|bool[]
     */
    protected function populateModuleFieldsFromVtigerModule($module, $type = 'source')
    {
        $describe = $this->getVtigerClient()->describe($module, $type == 'source' ? 1 : 0);
        if (empty($describe['success']) || empty($describe['result']['fields'])) {
            return false;
        }

        $this->moduleFields = [];
        $this->fieldsRelate = [];
        $excludeFields = $this->exclude_field_list[$module] ?? $this->exclude_field_list['default'];
        $excludeFields = $excludeFields[$type] ?? $excludeFields['default'];
        $requiredFields = $this->force_required_module_fields[$module] ?? [];

        foreach ($describe['result']['fields'] as $field) {
            if (in_array($field['name'], $excludeFields)) {
                continue;
            }

            $mandatory = $field['mandatory'] || in_array($field['name'], $requiredFields, true);
            $this->addVtigerFieldToModuleFields($field, $mandatory);
        }

        if (count($this->fieldsRelate) > 0) {
            $this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
        }

        return $this->moduleFields;
    }

    /**
     * Add field to $moduleFields.
     *
     * @param $field
     * @param $mandatory
     */
    protected function addVtigerFieldToModuleFields($field, $mandatory)
    {
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
                'label' => $field['label'],
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

    /**
     * Read Last
     *
     * @param array $param
     * @return array
     */
    public function read_last($param)
    {
        if ($this->notVtigerClient()) {
            return $this->errorMissingVtigerClient(['done' => -1]);
        }

        try {
            if (empty($this->moduleList)) {
                $this->setAllModulesPrefix();
            }

            $module = $param["module"];
            $param['fields'] = $this->cleanMyddlewareElementId($param['fields'] ?? []);
            $hasVtigerRelatedRecordFields = $this->hasVtigerRelatedRecordFields($param['fields']);
            $baseFields = $this->cleanVtigerRelatedRecordFields($param['fields']);

            $queryParam = implode(',', $baseFields ?? "") ?: '*';
            $where = $this->getReadLastVtigerWhereCondition($param);

            if ($module == "LineItem") {
                $query = $this->readLastVtigerLineItemQuery($param, $where);
            } elseif (empty($param['query']['id'])) {
                //file_put_contents('/var/www/html/var/logs/vtigercrm.0.log', __FILE__.':'.__LINE__."\n", FILE_APPEND);
                $query = $this->getVtigerClient()->query("SELECT {$queryParam} FROM {$module} {$where} ORDER BY modifiedtime DESC LIMIT 0,1;");
            } else {
                $query = $this->getVtigerClient()->retrieve($param['query']['id']);
                $query['result'][0] = $query['result'];
            }

            if (empty($query['success'])) {
                return $this->errorVtigerRequestFailed(['done' => -1]);
            }

            if (empty($query['result'][0])) {
                return $this->errorVtigerNoDataRetrieved(['done' => false]);
            }

            if ($hasVtigerRelatedRecordFields) {
                //file_put_contents('/var/www/html/var/logs/vtigercrm.0.log', __FILE__.':'.__LINE__.' ID='.$query['result'][0]['id']."\n", FILE_APPEND);
                $retrieveResponse = $this->getVtigerClient()->retrieve($query['result'][0]['id'], 1);
                $query['result'][0] = empty($retrieveResponse['success']) ? $query['result'][0] : $retrieveResponse['result'];
                //file_put_contents('/var/www/html/var/logs/vtigercrm.0.log', __FILE__.':'.__LINE__.' '.json_encode($retrieveResponse)."\n", FILE_APPEND);
            }

            $result = ['done' => true];
            foreach ($query['result'][0] as $fieldName => $value) {
                $result['values'][$fieldName] = $value;
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
            $result['done'] = -1;
        }

        return $result;
    }

    /**
     * @param $param
     * @param $where
     */
    protected function readLastVtigerLineItemQuery($param, $where)
    {
        $module = $param['module'];
        //file_put_contents('/var/www/html/var/logs/vtigercrm.0.log', __FILE__.':'.__LINE__."\n", FILE_APPEND);
        $query = $this->getVtigerClient()->query("SELECT parent_id FROM {$module} {$where};");

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
        $maxTime = '';
        foreach ($parentModules as $prefix => $moduleName) {
            //file_put_contents('/var/www/html/var/logs/vtigercrm.0.log', __FILE__.':'.__LINE__."\n", FILE_APPEND);
            $query = $this->getVtigerClient()->query("SELECT id, createdtime, modifiedtime FROM {$moduleName} {$where} ORDER BY modifiedtime ASC LIMIT 0, 1;");
            if (empty($query['success']) || empty($query['result'])) {
                continue;
            }
            foreach ($query['result'] as $parentElement) {
                if ($maxTime && $maxTime >= $parentElement['modifiedtime']) {
                    continue;
                }
                $retrieve = $this->getVtigerClient()->retrieve($parentElement['id']);
                if (empty($retrieve['result']['LineItems'])) {
                    continue;
                }
                $maxTime = $parentElement['modifiedtime'];
                foreach ($retrieve['result']['LineItems'] as $index => $lineItem) {
                    $lineItem['parent_id'] = $parentElement['id'];
                    $lineItem['modifiedtime'] = $parentElement['modifiedtime'];
                    $lineItem['createdtime'] = $parentElement['createdtime'];
                    $entity[] = $lineItem;
                }
            }
        }

        return ["success" => true, "result" => $entity];
    }

    /**
     * @param $param
     *
     * @return string
     */
    protected function getReadLastVtigerWhereCondition($param)
    {
        $where = '';
        if (!empty($param['query'])) {
            $where = [];
            foreach ($param['query'] as $key => $item) {
                $where[] = "$key = '$item'";
            }
            $where = "WHERE " . implode(" AND ", $where);
        }

        return $where;
    }

    /**
     * Read
     *
     * @param array $param
     * @return array
     */
    public function read($param)
    {
        if ($this->notVtigerClient()) {
            return $this->errorMissingVtigerClient(['done' => false]);
        }

        if (count($param['fields']) == 0) {
            return $this->errorVtigerMissingParam(['done' => false]);
        }

        if (empty($param['offset'])) {
            $param['offset'] = 0;
        }

        if (empty($param['limit'])) {
            $param['limit'] = $this->limitPerCall;
        }

        $result = [
            'count' => 0,
        ];

        try {
            $queryParam = $this->getVtigerReadQueryParam($param);
            $hasVtigerRelatedRecordFields = $this->hasVtigerRelatedRecordFields($param['fields']);
            $where = $this->getVtigerReadWhereCondition($param);

            $orderBy = 'ORDER BY modifiedtime ASC';
            if ($this->isVtigerInventoryModule($param['module'])) {
                $orderBy = '';
            }

            $dataLeft = $param['limit'];

            do {
                $nDataCall = $dataLeft - $this->limitPerCall <= 0 ? $dataLeft : $this->limitPerCall;

                if ($param['module'] == 'LineItem') {
                    $sql = 'readVtigerLineItemQuery()';
                    $query = $this->readVtigerLineItemQuery($param, $where, $orderBy, $nDataCall);
                } else {
                    $sql = "SELECT $queryParam FROM $param[module] $where $orderBy LIMIT $param[offset], $nDataCall;";
                    //file_put_contents('/var/www/html/var/logs/vtigercrm.0.log', __FILE__.':'.__LINE__."\n", FILE_APPEND);
                    $query = $this->getVtigerClient()->query($sql);
                }

                if (empty($query['success'])) {
                    return [
                        //'error' => 'Error: Request Failed! (' . ($query['error']['message'] ?? 'Error') . ' SQL: "'.$sql.'")',
                        'error' => 'Error: Request Failed! (' . ($query['error']['message'] ?? 'Error') . ')',
                        'count' => 0,
                    ];
                }

                if (empty($query['result'])) {
                    break;
                }

                $countResult = 0;
                foreach ($query['result'] as $value) {
                    if (!isset($result['values']) || !array_key_exists($value['id'], $result['values'])) {
                        $result['date_ref'] = $value['modifiedtime'];
                        $result['values'][$value['id']] = $value;
                        if ($hasVtigerRelatedRecordFields) {
                            $retrieveResponse = $this->getVtigerClient()->retrieve($value['id'], 1);
                            $result['values'][$value['id']] = empty($retrieveResponse['success']) ? $value : $retrieveResponse['result'];
                        }
                        if (in_array($param['rule']['mode'], ['0', 'S'])) {
                            $result['values'][$value['id']]['date_modified'] = $value['modifiedtime'];
                        } elseif ($param['rule']['mode'] == 'C') {
                            $result['values'][$value['id']]['date_modified'] = $value['createdtime'];
                        }
                        $result['count']++;
                        $countResult++;
                    }
                }

                $param['offset'] += $nDataCall;
                $dataLeft -= $nDataCall;
            } while ($dataLeft > 0 && $countResult >= $nDataCall);
        } catch (\Exception $e) {
            $result['error'] = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
        }

        return $result;
    }

    /**
     * @param $param
     * @param $where
     * @param $orderBy
     * @param $nDataCall
     *
     * @return array
     */
    protected function readVtigerLineItemQuery($param, $where, $orderBy, $nDataCall)
    {
        if (empty($this->moduleList)) {
            $this->setAllModulesPrefix();
        }

        /*
        //file_put_contents('/var/www/html/var/logs/vtigercrm.0.log', __FILE__.':'.__LINE__."\n", FILE_APPEND);
        $query = $this->getVtigerClient()->query("SELECT parent_id FROM $param[module];");

        $parentModules = [];
        foreach ($query['result'] as $parent) {
            $prefix = explode('x', $parent['parent_id'])[0];
            if (!array_key_exists($prefix, $parentModules)) {
                $parentModules[$prefix] = $this->moduleList[$prefix];
            }
        }
        */

        $entities = [];
        foreach ($this->moduleList as $prefix => $moduleName) {
            if (!in_array($moduleName, $this->inventoryModules)) {
                continue;
            }
            //file_put_contents('/var/www/html/var/logs/vtigercrm.0.log', __FILE__.':'.__LINE__."\n", FILE_APPEND);
            $query = $this->getVtigerClient()->query("SELECT id, modifiedtime, createdtime FROM $moduleName $where $orderBy LIMIT $param[offset], $nDataCall;");
            if (empty($query['success'])) {
                continue;
            }

            foreach ($query['result'] as $parentElement) {
                $retrieve = $this->getVtigerClient()->retrieve($parentElement['id']);
                foreach ($retrieve['result']['LineItems'] as $index => $lineItem) {
                    /*
                    if ($index == 0) {
                        continue;
                    }
                    */
                    $lineItem['parent_id'] = $parentElement['id'];
                    $lineItem['modifiedtime'] = $parentElement['modifiedtime'];
                    $lineItem['createdtime'] = $parentElement['createdtime'];
                    $entities[] = $lineItem;
                }
            }
        }

        return ['success' => true, 'result' => $entities];
    }

    /**
     * @param $param
     *
     * @return string
     */
    protected function getVtigerReadQueryParam($param)
    {
        $fields = $this->cleanVtigerRelatedRecordFields($param['fields']);

        $queryParam = implode(',', $fields ?? '') ?: '*';
        if ($queryParam != '*') {
            $requiredField = $this->required_fields[$param['module']] ?? $this->required_fields['default'];
            $queryParam = implode(',', $requiredField) . ',' . $queryParam;
            $queryParam = str_replace(['my_value,', 'my_value'], '', $queryParam);
        }
        $queryParam = rtrim($queryParam, ',');

        return $queryParam;
    }

    /**
     *
     * @param $param
     *
     * @return string
     */
    protected function getVtigerReadWhereCondition($param)
    {
        $dateRefValue = isset($param['date_ref']) && $param['date_ref'] ? $param['date_ref'] : null;
        $where = $dateRefValue ? "WHERE modifiedtime > '{$dateRefValue}'" : '';

        if (isset($param['query']) && $param['query']) {
            $where .= empty($where) ? 'WHERE ' : ' AND ';
            foreach ($param['query'] as $key => $item) {
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

        return $where;
    }

    /**
     * Create new record in target
     *
     * @param array $param
     * @return array
     */
    public function create($param)
    {
        if ($this->notVtigerClient()) {
            return $this->errorMissingVtigerClient();
        }

        $result = [];

        try {
            if ($param['module'] != 'LineItem') {
                $this->createVtigerStandardRecords($param, $result);
            } else {
                $this->createVtigerLineItemRecords($param, $result);
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
        }

        return $result;
    }

    /**
     * @param $param
     * @param $result
     */
    protected function createVtigerStandardRecords($param, &$result)
    {
        $subDocIdArray = [];

        foreach ($param['data'] as $idDoc => $data) {
            try {
                $data = $this->cleanRecord($param, $data);
                $data = $this->sanitizeVtigerLineItemData($param, $data, $subDocIdArray);
                $data = $this->sanitizeVtigerInventoryRecord($param, $data);

                $resultCreate = $this->getVtigerClient()->create($param['module'], $data);
                if (empty($resultCreate['success']) || empty($resultCreate['result']['id'])) {
                    throw new \Exception($resultCreate["error"]["message"] ?? "Error");
                }

                $result[$idDoc] = [
                    'id' => $resultCreate['result']['id'],
                    'error' => false,
                ];
            } catch (\Exception $e) {
                $result[$idDoc] = array(
                    'id' => '-1',
                    'error' => $e->getMessage()
                );
            }

            $this->updateSubDocumentsStatus($idDoc, $param, $result, $subDocIdArray);
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }
    }

    /**
     * Update status for document collected over $subDocIdArray.
     *
     * @param $idDoc
     * @param $param
     * @param $result
     * @param $subDocIdArray
     */
    protected function updateSubDocumentsStatus($idDoc, $param, $result, $subDocIdArray)
    {
        if (empty($result[$idDoc]['error']) && count($subDocIdArray) > 0) {
            foreach ($subDocIdArray as $idSubDoc => $valueSubDoc) {
                $this->updateDocumentStatus($idSubDoc, $valueSubDoc, $param);
            }
        }
    }

    /**
     * @param $param
     * @param $result
     */
    protected function createVtigerLineItemRecords($param, &$result)
    {
        $parents = $this->getAllDataParentsFromParam($param);
        $parents = $this->deleteAllLineItemsOnParents($parents);

        foreach ($parents as $parent) {
            $lineItems = $this->mapDataLineItemsByParent($param, $parent);
            if (empty($lineItems)) {
                continue;
            }

            $this->createVtigerLineItemsRecordsByParent($param, $lineItems, $parent, $result);
        }
    }

    /**
     * @param $param
     * @param $lineItems
     * @param $parent
     * @param $result
     */
    protected function createVtigerLineItemsRecordsByParent($param, $lineItems, $parent, &$result)
    {
        if (empty($parent["invoicestatus"])) {
            $parent["invoicestatus"] = "AutoCreated";
        }
        unset($parent["LineItems_FinalDetails"]);
        $parent['LineItems'] = [];
        foreach ($lineItems as $lineItem) {
            $parent['LineItems'][] = $lineItem;
        }

        $resultUpdate = $this->getVtigerClient()->update($parent["id"], $parent);

        if (empty($resultUpdate['success']) || empty($resultUpdate['result']['id'])) {
            foreach ($lineItems as $idDoc => $lineItem) {
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $resultUpdate['error']['message']
                ];
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }

            return;
        }

        $retrieve = $this->getVtigerClient()->retrieve($resultUpdate['result']['id']);
        if (empty($retrieve['success']) || empty($retrieve['result']['LineItems'])) {
            return;
        }

        $lineItemIndex = 0;
        foreach ($lineItems as $idDoc => $lineItem) {
            /*
            $lineItemMatched = false;
            $sequenceSpan = '';
            foreach ($retrieve['result']['LineItems'] as $retrieveLineItem) {
                $sequenceSpan .= $sequenceSpan ? ','.$retrieveLineItem['sequence_no'] : $retrieveLineItem['sequence_no'];
                if ($retrieveLineItem['sequence_no'] != $lineItem['sequence_no']) {
                    continue;
                }
                $lineItemMatched = true;
                $result[$idDoc] = [
                    'id' => $retrieveLineItem['id'],
                    'error' => false,
                ];
            }
            if (!$lineItemMatched) {
                $result[$idDoc] = [
                    'id' => -1,
                    'error' => "LineItem mismatch, problem with source sequence_no='$lineItem[sequence_no]' target spans over '$sequenceSpan'",
                ];
            }
            */
            $targetLineItemIndex = 0;
            foreach ($retrieve['result']['LineItems'] as $retrieveLineItem) {
                if ($lineItemIndex === $targetLineItemIndex) {
                    $result[$idDoc] = [
                        'id' => $retrieveLineItem['id'],
                        'error' => false,
                    ];
                }
                $targetLineItemIndex++;
            }
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            $lineItemIndex++;
        }
    }

    /**
     * @param $param
     * @return array
     */
    protected function getAllDataParentsFromParam($param)
    {
        $parents = [];
        foreach ($param['data'] as $idDoc => $data) {
            if (in_array($data['parent_id'], array_keys($parents))) {
                continue;
            }
            $parent = $this->getVtigerClient()->retrieve($data['parent_id']);
            if (empty($parent['success']) || empty($parent['result'])) {
                continue;
            }
            $parents[$data['parent_id']] = $parent['result'];
        }

        return $parents;
    }

    /**
     *
     * @param $param
     * @param $parent
     *
     * @return array
     */
    protected function mapDataLineItemsByParent($param, $parent)
    {
        $lineItems = [];
        foreach ($param['data'] as $idDoc => $data) {
            if ($data['parent_id'] == $parent['id']) {
                unset($data['target_id']);
                unset($data['parent_id']);
                $lineItems[$idDoc] = $data;
            }
        }

        return $lineItems;
    }

    /**
     * @param $parents
     *
     * @return mixed
     */
    protected function deleteAllLineItemsOnParents($parents)
    {
        foreach ($parents as &$parent) {
            while (count($parent['LineItems']) > 0) {
                $this->getVtigerClient()->delete($parent['LineItems'][0]['id']);
                $response = $this->getVtigerClient()->retrieve($parent['id']);
                if (empty($response['success'])) {
                    continue;
                }
                $parent['LineItems'] = $response['result']['LineItems'];
            }
        }

        unset($parent);

        return $parents;
    }

    /**
     * Update existing record in target
     *
     * @param array $param
     * @return array
     */
    public function update($param)
    {
        if ($this->notVtigerClient()) {
            return $this->errorMissingVtigerClient();
        }

        $result = [];
        $subDocIdArray = [];

        try {
            foreach ($param['data'] as $idDoc => $data) {
                $this->updateElementOnVtiger($idDoc, $data, $param, $result, $subDocIdArray);
                $this->updateSubDocumentsStatus($idDoc, $param, $result, $subDocIdArray);
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
        }

        return $result;
    }

    /**
     * Get lineitem fields for specific module.
     *
     * @param $module
     *
     * @return array
     */
    protected function getVtigerLineItemFields($module)
    {
        $lineItemFields = [];

        if (in_array($module, $this->inventoryModules, true)) {
            $describe = $this->getVtigerClient()->describe('LineItem');

            foreach ($describe['result']['fields'] as $field) {
                $lineItemFields[] = $field['name'];
            }
        }

        return $lineItemFields;
    }

    /**
     * Update element on Vtiger.
     *
     * @param $idDoc
     * @param $data
     * @param $param
     * @param $result
     * @param $subDocIdArray
     *
     * @return void
     */
    protected function updateElementOnVtiger($idDoc, $data, $param, &$result, &$subDocIdArray)
    {
        $data['id'] = $data['target_id'];

        try {
            $data = $this->cleanRecord($param, $data);
            $data = $this->sanitizeVtigerLineItemData($param, $data, $subDocIdArray);
            $data = $this->sanitizeVtigerInventoryRecord($param, $data);

            $resultUpdate = $this->getVtigerClient()->update($param['module'], $data);

            if (empty($resultUpdate['success']) || empty($resultUpdate['result']['id'])) {
                throw new \Exception($resultUpdate["error"]["message"] ?? "Error");
            }

            $result[$idDoc] = [
                'id' => $resultUpdate['result']['id'],
                'error' => false,
            ];
        } catch (\Exception $e) {
            $result[$idDoc] = [
                'id' => '-1',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     *
     *
     * @param $param
     * @param $data
     * @param $subDocIdArray
     *
     * @return mixed
     */
    protected function sanitizeVtigerLineItemData($param, $data, &$subDocIdArray)
    {
        if (empty($data['LineItem'])) {
            return $data;
        }

        foreach ($data['LineItem'] as $subIdDoc => $childRecord) {
            if (empty($data['productid']) && isset($childRecord['productid'])) {
                $data['productid'] = $childRecord['productid'];
            }
            $subDocIdArray[$subIdDoc] = array('id' => uniqid('', true));
            $childRecord = $this->cleanRecord($param, $childRecord);
            $data['LineItems'][] = $childRecord;
        }

        unset($data['LineItem']);

        return $data;
    }

    /**
     * Sanitize record for vtiger update.
     *
     * @param $param
     * @param $data
     *
     * @return array
     */
    protected function sanitizeVtigerInventoryRecord($param, $data)
    {
        $lineItemFields = $this->getVtigerLineItemFields($param['module']);

        if (empty($lineItemFields)) {
            return $data;
        }

        if ($this->isVtigerInventoryModule($param['module'])) {
            foreach ($data as $inventoryKey => $inventoryValue) {
                if (in_array($inventoryKey, $lineItemFields, true) && $inventoryKey != "id") {
                    $data["LineItems"][0][$inventoryKey] = $inventoryValue;
                }
            }
            if (!isset($data["LineItems"][0]["sequence_no"])) {
                $data["LineItems"][0]["sequence_no"] = 1;
            }

            $data["hdnTaxType"] = (($data["hdnTaxType"] ?? "") ?: "group");
        }

        return $data;
    }

    /**
     * Delete existing record in target.
     *
     * @param array $param
     * @return array
     */
    public function delete($param)
    {
        if ($this->notVtigerClient()) {
            return $this->errorMissingVtigerClient();
        }

        $result = [];

        try {
            if ($param['module'] == 'LineItem') {
                $result = $this->update($param);
            } else {
                foreach ($param['data'] as $idDoc => $data) {
                    $data = $this->checkDataBeforeDelete($param, $data);
                    if (empty($data['target_id'])) {
                        throw new \Exception('No target id found. Failed to delete the record.');
                    }

                    $this->deleteElementOnVtiger($idDoc, $data, $result);
                    $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
                }
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
        }

        return $result;
    }

    /**
     * @param $idDoc
     * @param $data
     * @param $result
     * @throws \Exception
     */
    protected function deleteElementOnVtiger($idDoc, $data, &$result)
    {
        $id = $data['target_id'];

        try {
            $resultDelete = $this->getVtigerClient()->delete($id);

            if (empty($resultDelete['success']) || empty($resultDelete['status']) || $resultDelete['status'] != 'successful') {
                throw new \Exception($resultDelete["error"]["message"] ?? "Error");
            }

            $result[$idDoc] = [
                'id' => $id,
                'error' => false,
            ];
        } catch (\Exception $e) {
            $result[$idDoc] = array(
                'id' => '-1',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Clean a record by removing all Myddleware fields.
     *
     * @param $param
     * @param $data
     *
     * @return mixed
     */
    protected function cleanRecord($param, $data)
    {
        $myddlewareFields = array('target_id', 'source_date_modified', 'id_doc_myddleware', 'Myddleware_element_id');

        foreach ($myddlewareFields as $myddlewareField) {
            if (array_key_exists($myddlewareField, $data)) {
                unset($data[$myddlewareField]);
            }
        }

        return $data;
    }

    /**
     * Get current RuleMode based on $module and $type.
     *
     * @param $module
     * @param $type
     *
     * @return string[]
     */
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

    /**
     * Get correct DateRef field based on RuleMode.
     *
     * @param $moduleSource
     * @param $RuleMode
     *
     * @return string
     *
     * @throws \Exception
     */
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

    /**
     * Build the direct link to the record (used in data transfer view).
     *
     * @param $rule
     * @param $document
     * @param $type
     *
     * @return string|void
     */
    public function getDirectLink($rule, $document, $type)
    {
        $url = $this->getConnectorParam($rule->getConnectorSource(), 'url');
        $module = $rule->getModuleSource();
        $recordId = $document->getSource();

        $recordIdArray = explode('x', $recordId);
        if (substr($url, 0, strlen("http")) !== "http") {
            $url = 'http://' . $url;
        }

        if (empty($recordIdArray[1])) {
            return;
        }

        return rtrim($url, '/') . '/index.php?module=' . $module . '&view=Detail&record=' . $recordIdArray[1];
    }

    /**
     * Return default error for missing vtiger client.
     *
     * @param array $extend
     * @return array
     */
    protected function errorMissingVtigerClient($extend = [])
    {
        return array_merge(['error' => 'Error: no VtigerClient setup'], $extend);
    }

    /**
     * Return default error for missing vtiger client.
     *
     * @param array $extend
     * @return array
     */
    protected function errorVtigerNoDataRetrieved($extend = [])
    {
        return array_merge(['error' => 'Error: No Data Retrieved'], $extend);
    }

    /**
     * Return default error for missing vtiger client.
     *
     * @param array $extend
     * @return array
     */
    protected function errorVtigerRequestFailed($extend = [])
    {
        return array_merge(['error' => 'Error: Request Failed!'], $extend);
    }

    /**
     * Return default error for missing vtiger client.
     *
     * @param array $extend
     * @return array
     */
    protected function errorVtigerMissingParam($extend = [])
    {
        return array_merge(['error' => 'Error: no Param Given'], $extend);
    }

    /**
     * Return true if $module is a vtiger inventory module.
     *
     * @param $module
     *
     * @return bool
     */
    protected function isVtigerInventoryModule($module)
    {
        return in_array($module, $this->inventoryModules, true);
    }

    /**
     * @param $fieldsList
     *
     * @return array
     */
    protected function cleanVtigerRelatedRecordFields($fieldsList)
    {
        $baseFields = [];
        foreach ($fieldsList as $field) {
            if (!preg_match('/__/', $field)) {
                $baseFields[] = $field;
            }
        }

        return $baseFields;
    }

    /**
     * @param $fieldsList
     *
     * @return array
     */
    protected function hasVtigerRelatedRecordFields($fieldsList)
    {
        foreach ($fieldsList as $field) {
            if (preg_match('/__/', $field)) {
                return true;
            }
        }

        return false;
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
