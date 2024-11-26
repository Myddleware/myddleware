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

use Javanile\VtigerClient\VtigerClient;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class vtigercrm extends solution
{
    // Enable to delete data
    protected bool $sendDeletion = true;

    protected int $limitPerCall = 100;

    protected array $required_fields = [
        'default' => [
            'id',
            'modifiedtime',
            'createdtime',
        ],
    ];

    protected array $FieldsDuplicate = [
        'Contacts' => ['email', 'lastname'],
        'CompanyDetails' => ['organizationname'],
        'Accounts' => ['accountname'],
        'Leads' => ['email', 'lastname'],
        'default' => [''],
    ];

    protected array $exclude_module_list = [
        'default' => ['Users', 'Documents'],
        'target' => [],
        'source' => [],
    ];

    protected array $exclude_field_list = [
        'default' => [
            'default' => [
                'id',
            ],
            'source' => [
                'id',
            ],
            'target' => [
                'id',
                'modifiedby',
                'modifiedtime',
                'createdtime',
            ],
        ],
    ];

    protected array $inventoryModules = [
        'Invoice',
        'SalesOrder',
        'Quotes',
        'PurchaseOrder',
        'GreenTimeControl',
        'DDT',
    ];

    // Module list that allows to make parent relationships
    protected array $allowParentRelationship = ['Quotes', 'SalesOrder'];

    /** @var array */
    protected array $moduleList;

    /** @var VtigerClient */
    protected VtigerClient $vtigerClient;

    /**
     * Make the login.
     *
     * @param array $paramConnexion
     *
     * @return array|void
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

    public function logout(): bool
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
     * Return the login fields.
     *
     * @return array
     */
    public function getFieldsLogin(): array
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
     *
     * @return array|bool
     */
    public function get_modules($type = 'source')
    {
        try {
            if (empty($this->vtigerClient)) {
                return false;
            }

            $result = $this->vtigerClient->listTypes();

            if (!$result['success'] || ($result['success'] && 0 == count($result['result']))) {
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
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    public function setModulePrefix($moduleName = null)
    {
        if (empty($moduleName)) {
            $result = $this->vtigerClient->listTypes();

            if (!$result['success'] || ($result['success'] && 0 == count($result['result']))) {
                return false;
            }

            foreach ($result['result']['types'] as $moduleName) {
                $describe = $this->vtigerClient->describe($moduleName);
                if ($describe['success'] && 0 != count($describe['result'])) {
                    $this->moduleList[$describe['result']['idPrefix']] = $moduleName;
                }
            }
        } else {
            $describe = $this->vtigerClient->describe($moduleName);
            if ($describe['success'] && 0 != count($describe['result'])) {
                $this->moduleList[$describe['result']['idPrefix']] = $moduleName;

                return true;
            }

            return false;
        }
    }

    /**
     * Return the fields for a specific module without the specified ones.
     *
     * @param string $module
     * @param string $type
     *
     * @return array|bool
     */
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            if (empty($this->vtigerClient)) {
                return false;
            }

            $describe = $this->vtigerClient->describe($module);

            if (!$describe['success'] || ($describe['success'] && 0 == count($describe['result']))) {
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
                    if ('reference' == $field['type']['name'] || 'owner' == $field['type']['name']) {
                        $this->moduleFields[$field['name']] = [
                            'label' => $field['label'],
                            'required' => $field['mandatory'],
                            'type' => 'varchar(127)', // ? Settare il type giusto?
                            'type_bdd' => 'varchar(127)',
                            'required_relationship' => 0,
                            'relate' => true,
                        ];
                    } else {
                        $this->moduleFields[$field['name']] = [
                            'label' => $field['label'],
                            'required' => $field['mandatory'],
                            'type' => 'varchar(127)', // ? Settare il type giusto?
                            'type_bdd' => 'varchar(127)',
                            'relate' => false,
                        ];
                        if ('picklist' == $field['type']['name'] || 'multipicklist' == $field['type']['name']) {
                            foreach ($field['type']['picklistValues'] as $option) {
                                $this->moduleFields[$field['name']]['option'][$option['value']] = $option['label'];
                            }
                        }
                    }
                }
            }

            return $this->moduleFields ?: false;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());

            return false;
        }
    }

    /**
     * Read.
     *
     * @param array $param
     *
     * @return array
     */
    public function readData($param): array
    {
        try {
            if (empty($this->vtigerClient)) {
                return [
                    'error' => 'Error: no VtigerClient setup',
                    'done' => false,
                ];
            }

            if (0 == count($param['fields'])) {
                return [
                    'error' => 'Error: no Param Given',
                    'done' => false,
                ];
            }

            if (empty($param['offset'])) {
                $param['offset'] = 0;
            }
            if (empty($param['limit'])) {
                $param['limit'] = 100;
            }

            $queryParam = implode(',', $param['fields'] ?? '') ?: '*';
            if ('*' != $queryParam) {
                $requiredField = $this->required_fields[$param['module']] ?? $this->required_fields['default'];
                $queryParam = implode(',', $requiredField).','.$queryParam;
                $queryParam = str_replace(['my_value,', 'my_value'], '', $queryParam);
            }
            $queryParam = rtrim($queryParam, ',');

            $where = !empty($param['date_ref']) ? "WHERE modifiedtime > '$param[date_ref]'" : '';
            if (!empty($param['query'])) {
                $where .= empty($where) ? 'WHERE ' : ' AND ';
                foreach ($param['query'] as $key => $item) {
                    // if id we don't add other filter, we keep only id
                    if ('id' == $key) {
                        $where = " WHERE id = '$item' ";
                        break;
                    }
                    if ("'" === substr($where, -strlen("'"))) {
                        $where .= ' AND ';
                    }
                    $where .= "$key = '$item'";
                }
            }

            /** @var array $result */
            $result = [
                'count' => 0,
            ];

            $orderby = 'ORDER BY modifiedtime ASC';
            if (in_array($param['module'], $this->inventoryModules, true)) {
                $orderby = '';
            }

            $dataLeft = $param['limit'];
            do {
                $nDataCall = $dataLeft - $this->limitPerCall <= 0 ? $dataLeft : $this->limitPerCall;
                // TODO: Considerare di implementare Sync API in VtigerClient
                if ('LineItem' == $param['module']) {
                    if (empty($this->moduleList)) {
                        $this->setModulePrefix();
                    }

                    $query = $this->vtigerClient->query("SELECT parent_id FROM $param[module];");

                    $parentModules = [];
                    foreach ($query['result'] as $parent) {
                        $prefix = explode('x', $parent['parent_id'])[0];
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

                        foreach ($query['result'] as $parentElement) {
                            $retrive = $this->vtigerClient->retrieve($parentElement['id']);
                            foreach ($retrive['result']['LineItems'] as $index => $lineitem) {
                                if (0 == $index) {
                                    continue;
                                }
                                $lineitem['parent_id'] = $parentElement['id'];
                                $lineitem['modifiedtime'] = $parentElement['modifiedtime'];
                                $lineitem['createdtime'] = $parentElement['createdtime'];
                                $entitys[] = $lineitem;
                            }
                        }
                    }

                    $query = ['success' => true, 'result' => $entitys];
                } else {
                    $query = $this->vtigerClient->query("SELECT $queryParam FROM $param[module] $where $orderby LIMIT $param[offset], $nDataCall;");
                }
                if (empty($query) || (!empty($query) && !$query['success'])) {
                    return [
                        'error' => 'Error: Request Failed! ('.($query['error']['message'] ?? 'Error').')',
                        'count' => 0,
                    ];
                }

                if (0 == count($query['result'])) {
                    break;
                }

                $countResult = 0;
                $entitys = $query['result'];
                foreach ($entitys as $value) {
                    if (!isset($result['values']) || !array_key_exists($value['id'], $result['values'])) {
                        $result['date_ref'] = $value['modifiedtime'];
                        $result['values'][$value['id']] = $value;
                        if (in_array($param['ruleParams']['mode'], ['0', 'S'])) {
                            $result['values'][$value['id']]['date_modified'] = $value['modifiedtime'];
                        } elseif ('C' == $param['ruleParams']['mode']) {
                            $result['values'][$value['id']]['date_modified'] = $value['createdtime'];
                        }
                        ++$result['count'];
                        ++$countResult;
                    }
                }

                $param['offset'] += $nDataCall;
                $dataLeft -= $nDataCall;
            } while ($dataLeft > 0 && $countResult >= $nDataCall);
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' '.$e->getLine();
        }

        return $result;
    }

    /**
     * Create new record in target.
     *
     * @param array $param
     *
     * @return array
     */
    public function createData($param): array
    {
        try {
            $subDocIdArray = [];
            if (empty($this->vtigerClient)) {
                return ['error' => 'Error: no VtigerClient setup'];
            }

            $result = [];

            $lineItemFields = [];
            if (in_array($param['module'], $this->inventoryModules, true)) {
                $describe = $this->vtigerClient->describe('LineItem');

                foreach ($describe['result']['fields'] as $field) {
                    $lineItemFields[] = $field['name'];
                }
            }

            foreach ($param['data'] as $idDoc => $data) {
                try {
                    // Clean record by removing Myddleware fields (ex : target_id)
                    $data = $this->cleanRecord($param, $data);

                    // In case of LineItem (sub array in the data array => an order can have seeral orderItems),
                    // We transform the lineItem array into a LineItems array with the right format
                    if (!empty($data['LineItem'])) {
                        foreach ($data['LineItem'] as $subIdDoc => $childRecord) {
                            // Save the subIdoc to change the sub data transfer status
                            $subDocIdArray[$subIdDoc] = ['id' => uniqid('', true)];
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
                            if (in_array($inventorykey, $lineItemFields, true) && 'id' != $inventorykey) {
                                $data['LineItems'][0][$inventorykey] = $inventoryValue;
                            }
                        }
                        if (!isset($data['LineItems'][0]['sequence_no'])) {
                            $data['LineItems'][0]['sequence_no'] = 1;
                        }
                    }

                    $resultCreate = $this->vtigerClient->create($param['module'], $data);

                    if (!empty($resultCreate) && $resultCreate['success'] && !empty($resultCreate['result'])) {
                        if ('LineItem' == $param['module']) {
                            if (empty($this->moduleList)) {
                                $this->setModulePrefix();
                            }
                            $parent = $this->vtigerClient->retrieve($resultCreate['result']['parent_id']);
                            $parent = $parent['result'];
                            if (!isset($parent['invoicestatus']) || empty($parent['invoicestatus'])) {
                                $parent['invoicestatus'] = 'AutoCreated';
                            }
                            unset($parent['LineItems_FinalDetails']);
                            $r = $this->vtigerClient->update($this->moduleList[explode('x', $parent['id'])[0]], $parent);
                        }
                        $result[$idDoc] = [
                            'id' => $resultCreate['result']['id'],
                            'error' => false,
                        ];
                    } else {
                        throw new \Exception($resultCreate['error']['message'] ?? 'Error');
                    }
                } catch (\Exception $e) {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => $e->getMessage(),
                    ];
                }
                // Transfert status update
                if (
                        !empty($subDocIdArray)
                    and empty($result[$idDoc]['error'])
                ) {
                    foreach ($subDocIdArray as $idSubDoc => $valueSubDoc) {
                        $this->updateDocumentStatus($idSubDoc, $valueSubDoc, $param);
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
     * Update exist record in target.
     *
     * @param array $param
     *
     * @return array
     */
    public function updateData($param): array
    {
        try {
            $subDocIdArray = [];
            if (empty($this->vtigerClient)) {
                return ['error' => 'Error: no VtigerClient setup'];
            }

            $result = [];

            $lineItemFields = [];
            if (in_array($param['module'], $this->inventoryModules, true)) {
                $describe = $this->vtigerClient->describe('LineItem');

                foreach ($describe['result']['fields'] as $field) {
                    $lineItemFields[] = $field['name'];
                }
            }

            foreach ($param['data'] as $idDoc => $data) {
                try {
                    $data['id'] = $data['target_id'];
                    // Clean record by removing Myddleware fields (ex : target_id)
                    $data = $this->cleanRecord($param, $data);

                    // In case of LineItem (sub array in the data array => an order can have seeral orderItems),
                    // We transform the lineItem array into a LineItems array with the right format
                    if (!empty($data['LineItem'])) {
                        foreach ($data['LineItem'] as $subIdDoc => $childRecord) {
                            // Save the subIdoc to change the sub data transfer status
                            $subDocIdArray[$subIdDoc] = ['id' => uniqid('', true)];
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
                            if (in_array($inventorykey, $lineItemFields, true) && 'id' != $inventorykey) {
                                $data['LineItems'][0][$inventorykey] = $inventoryValue;
                            }
                        }
                        if (!isset($data['LineItems'][0]['sequence_no'])) {
                            $data['LineItems'][0]['sequence_no'] = 1;
                        }
                    }

                    $resultUpdate = $this->vtigerClient->update($param['module'], $data);

                    if (!empty($resultUpdate) && $resultUpdate['success'] && !empty($resultUpdate['result'])) {
                        if ('LineItem' == $param['module']) {
                            if (empty($this->moduleList)) {
                                $this->setModulePrefix();
                            }
                            $parent = $this->vtigerClient->retrieve($resultUpdate['result']['parent_id']);
                            $parent = $parent['result'];
                            if (!isset($parent['invoicestatus']) || empty($parent['invoicestatus'])) {
                                $parent['invoicestatus'] = 'AutoCreated';
                            }
                            unset($parent['LineItems_FinalDetails']);
                            $r = $this->vtigerClient->update($this->moduleList[explode('x', $parent['id'])[0]], $parent);
                        }
                        $result[$idDoc] = [
                            'id' => $resultUpdate['result']['id'],
                            'error' => false,
                        ];
                    } else {
                        throw new \Exception($resultUpdate['error']['message'] ?? 'Error');
                    }
                } catch (\Exception $e) {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => $e->getMessage(),
                    ];
                }
                // Transfert status update
                if (
                        !empty($subDocIdArray)
                    and empty($result[$idDoc]['error'])
                ) {
                    foreach ($subDocIdArray as $idSubDoc => $valueSubDoc) {
                        $this->updateDocumentStatus($idSubDoc, $valueSubDoc, $param);
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

    // Function to delete a record
    public function deleteData($param): array
    {
        try {
            // For every document
            foreach ($param['data'] as $idDoc => $data) {
                try {
                    // Check control before delete
                    $data = $this->checkDataBeforeDelete($param, $data);
                    if (empty($data['target_id'])) {
                        throw new \Exception('No target id found. Failed to delete the record.');
                    }
                    // Delete the record
                    $resultDelete = $this->vtigerClient->delete($data['target_id']);
                    if (empty($resultDelete['success'])) {
                        throw new \Exception($resultDelete['error']['message'] ?? 'Error');
                    }
                    // Generate return for Myddleware
                    $result[$idDoc] = [
                                            'id' => $data['target_id'],
                                            'error' => false,
                                        ];
                } catch (\Exception $e) {
                    $error = 'Error : '.$e->getMessage();
                    $result[$idDoc] = [
                            'id' => '-1',
                            'error' => $error,
                    ];
                }
                // Status modification for the transfer
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }
        } catch (\Exception $e) {
            $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
            ];
        }

        return $result;
    }

    // Clean a record by removing all Myddleware fields
    protected function cleanRecord($param, $data)
    {
        $myddlewareFields = ['target_id'];
        foreach ($myddlewareFields as $myddlewareField) {
            if (array_key_exists($myddlewareField, $data)) {
                unset($data[$myddlewareField]);
            }
        }

        return $data;
    }

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

        // Get the record id, id format <key>x<recordId>
        $recordIdArray = explode('x', $recordId);
        if (!empty($recordIdArray[1])) {
            // Build the URL (delete if exists / to be sure to not have 2 / in a row)
            return rtrim($url, '/').'/index.php?module='.$module.'&view=Detail&record='.$recordIdArray[1];
        }
    }
}
