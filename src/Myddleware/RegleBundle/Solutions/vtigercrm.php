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


    protected $FieldsDuplicate = [
                                    'default' => []
                                ];

    protected $exclude_module_list = [
                                        'default'    => ['Users'],
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

    /** @var VtigerClient */
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
            $result = $client->login($this->paramConnexion['username'], $this->paramConnexion['accesskey']);

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
            }
        }

        return $options ?: false;
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
                                                'type' => 'varchar(127)',
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
                            $this->moduleFields[$field['name']]["option"][$option["label"]] = $option["value"];
                        }
                    }
                }
                if ($field['mandatory'] && !array_key_exists($module, $this->FieldsDuplicate)) {
                    if ($field['name'] != "assigned_user_id") {
                        $this->FieldsDuplicate[$module][] = $field['name'];
                    }
                }
            }
        }

        if (!empty($this->fieldsRelate)) {
            $this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
        }

        return $this->moduleFields ?: false;
    }

    /**
     * Read Last
     *
     * @param array $param
     * @return array
     */
    public function read_last($param)
    {
        if (empty($this->vtigerClient)) {
            return [
                'error' => 'Error: no VtigerClient setup',
                'done'  => -1,
            ];
        }

        /*
        if (count($param['fields']) == 0) {
            return [
                'error' => 'Error: no Param Given',
                'done'  => -1,
            ];
        }
        */

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
        $query = $this->vtigerClient->query("SELECT $queryParam FROM $param[module] $where ORDER BY modifiedtime DESC LIMIT 0,1;");

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
            $where = empty($where) ? 'WHERE ' : ' AND ';
            foreach ($param['query'] as $key => $item) {
                if (substr($where, -strlen("'")) === "'") {
                    $where .= ' AND ';
                }
                $where .= "$key = '$item'";
            }
        }
        

        $result = [
            'count' => 0,
        ];

        $dataLeft = $param["limit"];
        do {
            $nDataCall = $dataLeft - $this->limitPerCall <= 0 ? $dataLeft : $this->limitPerCall;
            // TODO: Considerare di implementare Sync API in VtigerClient
            $query = $this->vtigerClient->query("SELECT $queryParam FROM $param[module] $where ORDER BY modifiedtime ASC LIMIT $param[offset], $nDataCall;");

            if (empty($query) || (!empty($query) && !$query['success'])) {
                return [
                            'error' => 'Error: Request Failed!',
                            'count' => 0,
                        ];
            }

            if (count($query['result']) == 0) {
                break;
            }

            foreach ($query['result'] as $value) {
                $result['date_ref'] = $value['modifiedtime'];

                $result['values'][$value['id']] = $value;
                if(in_array($param['rule']['mode'], ["0", "S"])) {
                    $result['values'][$value['id']]["date_modified"] = $value['modifiedtime'];
                } else if ($param['rule']['mode'] == "C") {
                    $result['values'][$value['id']]["date_modified"] = $value['createdtime'];
                }
            }

            $result['count'] += count($query['result']);
            $param["offset"] += $nDataCall;

            $dataLeft -= $nDataCall;

        } while ($dataLeft > 0 && count($query['result']) >= $nDataCall);

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
        if (empty($this->vtigerClient)) {
            return ['error' => 'Error: no VtigerClient setup'];
        }

        $result = [];

        foreach ($param['data'] as $idDoc => $data) {
            unset($data['target_id']);
            $resultCreate = $this->vtigerClient->create($param['module'], $data);

            if (!empty($resultCreate) && $resultCreate['success'] && !empty($resultCreate['result'])) {
                $result[$idDoc] = [
                                    'id'    => $resultCreate['result']['id'],
                                    'error' => false,
                                ];
            } else {
                $result[$idDoc] = [
                                    'id'    => '-1',
                                    'error' => 'Errore',
                                ];
            }

            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
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
        if (empty($this->vtigerClient)) {
            return ['error' => 'Error: no VtigerClient setup'];
        }

        $result = [];

        foreach ($param['data'] as $idDoc => $data) {
            $data['id'] = $data['target_id'];
            unset($data['target_id']);
            $resultUpdate = $this->vtigerClient->update($param['module'], $data);

            if (!empty($resultUpdate) && $resultUpdate['success'] && !empty($resultUpdate['result'])) {
                $result[$idDoc] = [
                                    'id'    => $resultUpdate['result']['id'],
                                    'error' => false,
                                ];
            } else {
                $result[$idDoc] = [
                                    'id'    => '-1',
                                    'error' => 'Errore',
                                ];
            }

            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    // Permet de supprimer un enregistrement
    public function delete($id)
    {
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
