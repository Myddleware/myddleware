<?php

declare(strict_types=1);

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

    NOTICE: please ensure you have correctly emptied your Mautic var/cache folder before
    using Myddleware to avoid issues when connecting to Mautic API
*********************************************************************************/

namespace App\Solutions;

use Exception;
use Mautic\Auth\ApiAuth;
use Mautic\Exception\ContextNotFoundException;
use Mautic\MauticApi;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class Mautic extends Solution
{
    protected $auth;

    // Modules name depending on the context (call to create date, result of a search, result of a creation/update)
    protected array $moduleParameters = [
        'contact' => ['plural' => 'contacts', 'resultKeyUpsert' => 'contact', 'resultSearch' => 'contacts'],
        'company' => ['plural' => 'companies', 'resultKeyUpsert' => 'company', 'resultSearch' => 'companies'],
        'segment' => ['plural' => 'segments', 'resultKeyUpsert' => 'list',    'resultSearch' => 'list'],
    ];

    protected array $requiredFields = [
        'default' => ['id', 'dateModified', 'dateAdded'],
        'company' => ['id'],
    ];

    protected array $fieldsDuplicate = [
        'contact' => ['email'],
    ];

    // Enable to read deletion and to delete data
    protected bool $sendDeletion = true;

    // If you have Mautic 2 or lower, you must change this parameter to your version number
    protected int $mauticVersion = 3;

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
                'type' => UrlType::class,
                'label' => 'solution.fields.url',
            ],
        ];
    }

    public function login($connectionParam): void
    {
        parent::login($connectionParam);
        try {
            // Add login/password
            $settings = [
                'userName' => $this->connectionParam['login'],
                'password' => $this->connectionParam['password'],
            ];

            // Ini api
            $initAuth = new ApiAuth();
            $auth = $initAuth->newAuth($settings, 'BasicAuth');
            $api = new MauticApi();

            // Get the current user to check the connection parameters
            $userApi = $api->newApi('users', $auth, $this->connectionParam['url']);
            $user = $userApi->getSelf();

            // Managed API return. The API call is OK if the user id is found
            if (!empty($user['id'])) {
                $this->auth = $auth;
                $this->isConnectionValid = true;
            } elseif (!empty($user['error']['message'])) {
                throw new Exception('Failed to login to Mautic. Code '.$user['error']['code'].' : '.$user['error']['message']);
            } else {
                throw new Exception('Failed to login to Mautic. No error message returned by the API.');
            }
        } catch (Exception $e) {
            $error = $e->getMessage().$e->getFile().$e->getLine();
            $this->logger->error($error);
        }
    }

    public function getSolutionModules($type = 'source'): array
    {
        $modules = [
            'contact' => 'Contacts',
            'company' => 'Companies',
            'companies__contact' => 'Add contact to company',
        ];
        if ('target' == $type) {
            $modules['segment'] = 'Segment';
            $modules['segments__contacts'] = 'Add contact to segment';
        }

        return $modules;
    }

    public function getModuleFields($module, $type = 'source', $param = null): array
    {
        parent::getModuleFields($module, $type);
        try {
            // Use Mautic call to get company and contact fields (custom field can exist)
            if (in_array($module, ['contact', 'company'])) {
                // Call Mautic to get the module fields
                $api = new MauticApi();
                $fieldApi = $api->newApi($module.'Fields', $this->auth, $this->connectionParam['url']);
                $fieldlist = $fieldApi->getList();
                if (!empty($fieldlist['fields'])) {
                    foreach ($fieldlist['fields'] as $field) {
                        if ('relate' == $field['type']) {
                            $this->moduleFields[$field['alias']] = [
                                'label' => $field['label'],
                                'type' => 'varchar(255)',
                                'type_bdd' => 'varchar(255)',
                                'required' => '',
                                'required_relationship' => !empty($field['isRequired']),
                                'relate' => true,
                            ];
                        } else {
                            $this->moduleFields[$field['alias']] = [
                                'label' => $field['label'],
                                'type' => ('text' == $field['type'] ? TextType::class : 'varchar(255)'),
                                'type_bdd' => ('text' == $field['type'] ? $field['type'] : 'varchar(255)'),
                                'required' => !empty($field['isRequired']),
                                'relate' => false,
                            ];
                            // manage dropdown lists
                            if (!empty($field['properties']['list'])) {
                                // For Mautic 2
                                if ($this->mauticVersion <= 2) {
                                    $options = explode('|', $field['properties']['list']);
                                // For Mautic 3
                                } else {
                                    $options = $field['properties']['list'];
                                }
                                foreach ($options as $option) {
                                    $this->moduleFields[$field['alias']]['option'][$option] = $option;
                                }
                            }
                        }
                    }
                }
            } else {
                $moduleFields = [];
                // Use Mautic metadata (field added manually in metadata file)
                require 'lib/mautic/metadata.php';
                if (!empty($moduleFields[$module])) {
                    $this->moduleFields = $moduleFields[$module];
                }
            }

            return $this->moduleFields;
        } catch (Exception $e) {
            $error = $e->getMessage().$e->getFile().$e->getLine();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    /**
     * @throws ContextNotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function createData($param): array
    {
        return match ($param['module']) {
            'companies__contact' => $this->manageRelationship('create', $param, 'company', 'contact'),
            'segments__contacts' => $this->manageRelationship('create', $param, 'segment', 'contact'),
            default => $this->createUpdate('create', $param),
        };
    }

    /**
     * @throws ContextNotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateData($param): array
    {
        return match ($param['module']) {
            'companies__contact' => $this->manageRelationship('create', $param, 'company', 'contact'),
            'segments__contacts' => $this->manageRelationship('create', $param, 'segment', 'contact'),
            default => $this->createUpdate('update', $param),
        };
    }

    /**
     * @throws ContextNotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteData($param): array
    {
        return match ($param['module']) {
            'companies__contact' => $this->manageRelationship('delete', $param, 'company', 'contact'),
            'segments__contacts' => $this->manageRelationship('delete', $param, 'segment', 'contact'),
            default => $this->deleteRecord($param),
        };
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws ContextNotFoundException
     */
    public function createUpdate($action, $param): array
    {
        $result = [];
        // Create API object depending on the module
        $api = new MauticApi();
        $moduleName = (!empty($this->moduleParameters[$param['module']]['plural']) ? $this->moduleParameters[$param['module']]['plural'] : $param['module']);
        $moduleResultKey = (!empty($this->moduleParameters[$param['module']]['resultKeyUpsert']) ? $this->moduleParameters[$param['module']]['resultKeyUpsert'] : $param['module']);
        $moduleApi = $api->newApi($moduleName, $this->auth, $this->connectionParam['url']);

        // Transformation du tableau d'entrée pour être compatible webservice Sugar
        foreach ($param['data'] as $idDoc => $data) {
            try {
                // Manage target id for update action
                $targetId = '';
                if ('update' == $action) {
                    if (empty($data['target_id'])) {
                        throw new Exception('Failed to update the record to Mautic. The target id is empty.');
                    }
                    $targetId = $data['target_id'];
                }

                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                // update the record to Mautic
                if ('update' == $action) {
                    $record = $moduleApi->edit($targetId, $data, true);
                // create the record to Mautic
                } else {
                    $record = $moduleApi->create($data);
                }
                // Manage return data from Mautic
                if (!empty($record[$moduleResultKey]['id'])) {
                    $result[$idDoc] = [
                        'id' => $record[$moduleResultKey]['id'],
                        'error' => false,
                    ];
                } elseif (!empty($record['error']['message'])) {
                    throw new Exception('Failed to '.$action.' the record to Mautic. Code '.$record['error']['code'].' : '.$record['error']['message']);
                } else {
                    throw new Exception('Failed to '.$action.' the record to Mautic. No error message returned by the API.');
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    /**
     * @throws ContextNotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function manageRelationship($action, $param, $module1, $module2): array
    {
        $result = [];
        $api = new MauticApi();
        $moduleName = (!empty($this->moduleParameters[$module1]['plural']) ? $this->moduleParameters[$module1]['plural'] : $param['module']);
        $moduleApi = $api->newApi($moduleName, $this->auth, $this->connectionParam['url']);

        // Transformation du tableau d'entrée pour être compatible webservice Sugar
        foreach ($param['data'] as $idDoc => $data) {
            try {
                // Check control before create
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                if (empty($data[$module1])) {
                    throw new Exception('Failed to manage the '.$module2.' to the '.$module1.' to Mautic because '.$module1.' is empty.');
                }
                if (empty($data['contact'])) {
                    throw new Exception('Failed to manage the '.$module2.' to the '.$module1.' to Mautic because '.$module2.' is empty.');
                }

                // Create relationship into Mautic
                if ('create' == $action) {
                    $record = $moduleApi->addContact($data[$module1], $data[$module2]);
                } elseif ('delete' == $action) {
                    $record = $moduleApi->removeContact($data[$module1], $data[$module2]);
                } else {
                    throw new Exception('Action '.$action.' unknown');
                }

                // Manage return data from Mautic
                if (!empty($record['success'])) {
                    $result[$idDoc] = [
                        'id' => $data[$module1].'_'.$data[$module2],
                        'error' => false,
                    ];
                } else {
                    throw new Exception('Failed to add the '.$module2.' to the '.$module1.' to Mautic.');
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    public function deleteRecord($param): array
    {
        $result = [];
        try {
            $api = new MauticApi();
            $moduleName = (!empty($this->moduleParameters[$param['module']]['plural']) ? $this->moduleParameters[$param['module']]['plural'] : $param['module']);
            $moduleApi = $api->newApi($moduleName, $this->auth, $this->connectionParam['url']);

            foreach ($param['data'] as $idDoc => $data) {
                try {
                    // Check control before delete
                    $data = $this->checkDataBeforeDelete($param, $data);
                    if (empty($data['target_id'])) {
                        throw new Exception('No target id found. Failed to delete the record.');
                    }
                    // remove record from Mautic
                    $record = $moduleApi->delete($data['target_id']);

                    // Manage return data from Mautic
                    if (
                        !empty($record[$param['module']])
                        and array_key_exists('id', $record[$param['module']])
                    ) {
                        $result[$idDoc] = [
                            'id' => $data['target_id'],
                            'error' => false,
                        ];
                    } elseif (!empty($record['error']['message'])) {
                        throw new Exception('Failed to delete the record to Mautic. Code '.$record['error']['code'].' : '.$record['error']['message']);
                    } else {
                        throw new Exception('Failed to delete the record to Mautic. No error message returned by the API.');
                    }
                } catch (Exception $e) {
                    $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => $error,
                    ];
                }
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }
        } catch (Exception $e) {
            $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            $result['error'] = $error;
        }

        return $result;
    }

    // Build the direct link to the record (used in data transfer view)
    public function getDirectLink($rule, $document, $type): ?string
    {
        try {
            // Get url, module and record ID depending on the type
            if ('source' == $type) {
                $url = $this->getConnectorParam($rule->getConnectorSource(), 'url');
                $module = $rule->getSourceModule();
                $recordId = $document->getSource();
            } else {
                $url = $this->getConnectorParam($rule->getConnectorTarget(), 'url');
                $module = $rule->getTargetModule();
                $recordId = $document->gettarget();
            }

            // Build the URL (delete if exists / to be sure to not have 2 / in a row)
            return rtrim($url, '/').'/s/'.$this->moduleParameters[$module]['plural'].'/view/'.$recordId;
        } catch (Exception $e) {
            $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return null;
        }
    }

    protected function checkDataBeforeCreate($param, $data, $idDoc): ?array
    {
        // Remove target_id field as it is a Myddleware field
        if (array_key_exists('target_id', $data)) {
            unset($data['target_id']);
        }

        return $data;
    }

    /**
     * @throws Exception
     */
    protected function dateTimeToMyddleware($dateTime): string
    {
        $date = new \DateTime($dateTime);

        return $date->format('Y-m-d H:i:s');
    }
}
