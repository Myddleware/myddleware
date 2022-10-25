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

class facebookcore extends solution
{
    protected string $baseUrl = 'https://graph.facebook.com';
    protected string $apiVersion = 'v11.0';
    protected $facebook;
    protected bool $readLast = false;

    protected array $required_fields = ['default' => ['id', 'created_time']];

    public function getFieldsLogin(): array
    {
        return [
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
            [
                'name' => 'useraccesstoken',
                'type' => PasswordType::class,
                'label' => 'solution.fields.useraccesstoken',
            ],
        ];
    }

    // Login to Facebook
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            // Create Facebook object
            $this->facebook = new \Facebook\Facebook([
                'app_id' => $this->paramConnexion['clientid'],
                'app_secret' => $this->paramConnexion['clientsecret'],
                'default_graph_version' => $this->apiVersion,
            ]);

            // Set the user access token
            $this->facebook->setDefaultAccessToken($this->paramConnexion['useraccesstoken']);

            // Test the access getting me info
            $response = $this->facebook->get('/me');
            $graphNode = $response->getGraphNode();
            $meId = $graphNode->getField('id');
            if (empty($meId)) {
                throw new \Exception('Failed to get the access token from Facebook');
            }
            $this->connexion_valide = true;

            return;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $error = 'Graph returned an error: '.$e->getMessage();
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $error = 'Facebook SDK returned an error: '.$e->getMessage();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        $this->logger->error($error);

        return ['error' => $error];
    }

    // Get available modules
    public function get_modules($type = 'source')
    {
        try {
            $modules = [];
            // Get the account's pages
            $responsePages = $this->facebook->get('me/accounts?fields=name,access_token&type=page');
            $bodyPages = $responsePages->getDecodedBody();
            if (!empty($bodyPages['data'])) {
                foreach ($bodyPages['data'] as $page) {
                    // Get the page's lead forms
                    $responseLeadForms = $this->facebook->get($page['id'].'/leadgen_forms', $page['access_token']);
                    $bodyForms = $responseLeadForms->getDecodedBody();
                    // Build the module list
                    if (!empty($bodyForms['data'])) {
                        foreach ($bodyForms['data'] as $form) {
                            $modules['leadform__'.$form['id']] = $page['name'].' - '.$form['name'];
                        }
                    }
                }
            }

            return $modules;
        } catch (\Exception $e) {
            return false;
        }
    }

    // Get the fields available for the module in input
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            $fields = [];

            // When the module is created with the module name and moduleId
            $moduleArray = explode('__', $module);
            if (!empty($moduleArray[1])) {
                $module = $moduleArray[0];
                $moduleId = $moduleArray[1];
            }

            // If lead form module, get the field list from questions call
            if ('leadform' == $module) {
                // Standard fields
                $this->moduleFields['id'] = ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0];
                $this->moduleFields['created_time'] = ['label' => 'Created time', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0];
                // Get fields depending on the form
                $responseFields = $this->facebook->get($moduleId.'?fields=questions');
                $bodyFields = $responseFields->getDecodedBody();
                $fields = $bodyFields['questions'];
            }

            if (!empty($fields)) {
                foreach ($fields as $field) {
                    $this->moduleFields[$field['key']] = ['label' => $field['label'], 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0];
                    if (!empty($field['options'])) {
                        foreach ($field['options'] as $option) {
                            $this->moduleFields[$field['key']]['option'][$option['key']] = $option['value'];
                        }
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

    // Permet de lire les données
    public function readData($param): array
    {
        try {
            $result = [];
            $result['error'] = '';
            $result['count'] = 0;
            if (empty($param['limit'])) {
                $param['limit'] = 100;
            }

            // Explode the module name when the module is created with the module name and moduleId
            $moduleArray = explode('__', $param['module']);
            if (!empty($moduleArray[1])) {
                $param['module'] = $moduleArray[0];
                $param['moduleId'] = $moduleArray[1];
            }

            // Get the reference field
            $dateRefField = $this->getRefFieldName($param['module'], $param['ruleParams']['mode']);

            // Add required fields
            $param['fields'] = $this->addRequiredField($param['fields'], $param['module'], $param['ruleParams']['mode']);

            // Check the lead form id is filled when the module is leadform
            if ('leadform' == $param['module']) {
                if (empty($param['moduleId'])) {
                    throw new \Exception('Failed to read lead form from Facebook because the lead form id is empty.');
                }
                // REad all leads as it isn't possible to filter data by date
                $response = $this->facebook->get('/'.$param['moduleId'].'/leads');
            } else {
                throw new \Exception('Module '.$param['module'].' unknown. Failed to read data from Facebook. ');
            }

            if (empty($response)) {
                throw new \Exception('No response from Facebook. ');
            }

            // Browse all records
            // Facebook returns first the most recent items, so we browse them until we reach the reference date
            $recordsEdge = $response->getGraphEdge();
            while (null != $recordsEdge) {
                foreach ($recordsEdge as $recordNode) {
                    $row = [];
                    $fieldData = [];

                    // Get field values from the lead form
                    if ('leadform' == $param['module']) {
                        $fieldData = $this->formatToArray($recordNode->getField('field_data'));
                    }

                    // For each fields used in Myddleware rule
                    foreach ($param['fields'] as $field) {
                        // if reference date, we convert it to Myddleware date format
                        if ($field == $dateRefField) {
                            $row[$field] = $recordNode->getField($field)->format('Y-m-d H:i:s');
                            $row['date_modified'] = $recordNode->getField($field)->format('Y-m-d H:i:s');
                        // If the field exists in the form (fieldData), we get the data from it
                        } elseif (
                                !empty($fieldData)
                            and isset($fieldData[$field])
                        ) {
                            $row[$field] = $fieldData[$field];
                        } else {
                            $row[$field] = $recordNode->getField($field);
                        }
                    }
                    // Saved the record only if the record reference date is greater than the rule reference date
                    // (important when we can't filter by date in Facebook call)
                    if (
                            !empty($row['date_modified'])
                        && $param['date_ref'] < $row['date_modified']
                    ) {
                        $result['values'][$row['id']] = $row;
                        ++$result['count'];
                        // The most recent record will be the first read, so we save the reference date only for the first record
                        if (empty($result['date_ref'])) {
                            $result['date_ref'] = $row['date_modified'];
                        }
                        // If read last, we just read one record
                        if ($this->readLast) {
                            break 2;
                        }
                    } else {
                        // Data are read from the most recent to the oldest.
                        // We stop the process once we reach a data withe a reference date < the rule reference date
                        break 2;
                    }
                }
                // Read next page
                $recordsEdge = $this->facebook->next($recordsEdge);
            }
            // If the number of record read is greater than the limit,
            // We read the result from the end to the beginning (oldest record first) and keep only the number of record expected
            if (
                    !empty($result['values'])
                and count($result['values']) > $param['limit']
            ) {
                $reverseValues = array_reverse($result['values'], true);
                $result['values'] = [];
                foreach ($reverseValues as $key => $value) {
                    if (
                            !empty($result['values'])
                        and count($result['values']) >= $param['limit']
                    ) {
                        break;
                    }
                    $result['values'][$key] = $value;
                    $result['date_ref'] = $value['date_modified'];
                }
                $result['count'] = count($result['values']);
            }
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $result['error'] = 'Graph returned an error: '.$e->getMessage();
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $result['error'] = 'Facebook SDK returned an error: '.$e->getMessage();
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    // Transform Facebook data structure to a json type key => value
    protected function formatToArray($fbDataObject)
    {
        if (!empty($fbDataObject)) {
            foreach ($fbDataObject as $field) {
                $data[$field->getField('name')] = $field->getField('values')->getField('0');
            }

            return $data;
        }
    }

    public function getRefFieldName($moduleSource, $RuleMode): string
    {
        // Only leads module for now
        return 'created_time';
    }
}

class facebook extends facebookcore
{
}
