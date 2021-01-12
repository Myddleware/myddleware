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

require_once 'lib/shopapplication/Unirest.php';

class shopapplicationcore extends solution
{
    protected $url;
    protected $apiKey;
    protected $docIdList;
    protected $docIdListResult;
    protected $newChild;

    protected $required_fields = [
        'default' => ['id', 'date_modified', 'date_created'],
        'orders_delivery_address' => ['id'],
        'orders_billing_address' => ['id'],
        'customers_addresses' => ['id'],
        'orders_products' => ['id', 'date_added'],
        'products' => ['id', 'date_modified', 'date_added'],
        'products_options' => ['id'],
        'options' => ['id'],
        'options_values' => ['id'],
        'products_stock' => ['id'],
        'products_stock_entries' => ['id'],
        'categories' => ['id', 'date_modified', 'date_added'],
        'brands' => ['id', 'date_modified', 'date_added'],
    ];
    protected $FieldsDuplicate = ['customers' => ['email']];
    protected $IdByModule = [
        'customers_addresses' => 'address_id',
    ];

    // Structure of child module : module => childmodule => entry name and id name of the child array in the parent array
    protected $childModuleParameters = [
        'customers' => [
            'customers_addresses' => ['entry_name' => 'addresses', 'id_name' => 'address_id', 'type' => 'array'],
        ],
        'orders' => [
            'orders_products' => ['entry_name' => 'products', 'id_name' => 'id', 'type' => 'array'],
            'orders_delivery_address' => ['entry_name' => 'delivery_address', 'id_name' => 'id', 'type' => 'structure'],
            'orders_billing_address' => ['entry_name' => 'billing_address', 'id_name' => 'id', 'type' => 'structure'],
        ],
        'products' => [
            'products' => [
                'entry_name' => 'options',
                'id_name' => 'option_id',
                'type' => 'array',
                'products_options' => [
                    'id_name' => 'option_value_id',
                    'type' => 'array',
                ],
            ],
            'products_stock' => [
                'entry_name' => 'stock',
                'type' => 'array',
                'products_options' => [
                    'entry_name' => 'stock_options',
                    'id_name' => 'option_value_id',
                    'type' => 'array',
                    'options_values' => [
                        'id_name' => 'option_value_id',
                        'type' => 'array',
                    ],
                ],
                'products_stock_entries' => [
                    'entry_name' => 'stock_entries',
                    'type' => 'array',
                ],
            ],
        ],
        'options' => [
            'options_values' => ['entry_name' => 'values', 'id_name' => 'value_id', 'type' => 'array'],
        ],
    ];

    // there is some fictive module created to beused with Myddleware. Here the correspondence to find the real module to call
    protected $callModule = [
        'orders_delivery_address' => 'orders',
        'orders_billing_address' => 'orders',
    ];
    // Modules with language
    protected $moduleWithLanguage = ['products', 'categories', 'options', 'options_values'];

    // Connection parameters
    public function getFieldsLogin()
    {
        return [
            [
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
            [
                'name' => 'apikey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.apikey',
            ],
        ];
    }

    // getFieldsLogin()

    // Connexion to Shop-application
    public function login($paramConnexion)
    {
        // Call parent to set $paramConnexion in an attribut of the class
        parent::login($paramConnexion);
        try {
            // Delete the "/" at the end of the url if the user have added one
            $this->url = rtrim($this->paramConnexion['url'], '/').'/api/';
            $this->apiKey = '?key='.$this->paramConnexion['apikey'];
            // Try to access to the shop
            $result = $this->call(trim($this->url.$this->apiKey), 'get', '');
            // get the code, if 200 then success otherwise error
            $code = $result->__get('code');
            if ('200' != $code) {
                // Get the error message
                $body = $result->__get('body');
                throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
            }
            $this->connexion_valide = true;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // login($paramConnexion)*/

    public function get_modules($type = 'source')
    {
        return [
            'customers' => 'Customers',
            'customers_addresses' => 'Customers addresses',
            'orders' => 'Orders',
            'orders_products' => 'Orders products',
            'orders_delivery_address' => 'Orders delivery address',
            'orders_billing_address' => 'Orders billing address',
            'products' => 'Products',
            'products_options' => 'Products options',
            'options' => 'Options',
            'options_values' => 'Options values',
            'categories' => 'Categories',
            'brands' => 'Brands',
            'products_stock' => 'Products stock',
            'products_stock_options' => 'Products stock option',
            'products_stock_entries' => 'Products stock entry',
        ];
    }

    // get_modules()

    // Renvoie les champs du module passé en paramètre
    public function get_module_fields($module, $type = 'source')
    {
        require_once 'lib/shopapplication/metadata.php';
        parent::get_module_fields($module, $type);
        try {
            if (!empty($moduleFields[$module])) {
                $this->moduleFields = $moduleFields[$module];
            }

            if (!empty($fieldsRelate[$module])) {
                $this->fieldsRelate = $fieldsRelate[$module];
            }
            // Retrieve specific list
            if ('customers' == $module) {
                try {
                    // Get customer's groups
                    $urlApi = $this->url.'customers/groups'.$this->apiKey;
                    $return = $this->call($urlApi, 'get', '');
                    $code = $return->__get('code');
                    if ('200' == $code) {
                        $body = $return->__get('body');
                        if (!empty($body)) {
                            foreach ($body as $group) {
                                $this->fieldsRelate['group_id']['option'][$group->id] = $group->name;
                            }
                        }
                    }
                } catch (\Exception $e) {
                }
            }
            if ('orders' == $module) {
                try {
                    // Get order's status
                    $urlApi = $this->url.'orders/status'.$this->apiKey;
                    $return = $this->call($urlApi, 'get', '');
                    $code = $return->__get('code');
                    if ('200' == $code) {
                        $body = $return->__get('body');
                        if (!empty($body)) {
                            foreach ($body as $status) {
                                $this->moduleFields['status']['option'][$status->id] = $status->multilangual->fr->name;
                            }
                        }
                    }
                    // Get currencies
                    $urlApi = $this->url.'currencies'.$this->apiKey;
                    $return = $this->call($urlApi, 'get', '');
                    $code = $return->__get('code');
                    if ('200' == $code) {
                        $body = $return->__get('body');
                        if (!empty($body)) {
                            foreach ($body as $currency) {
                                $this->moduleFields['currency']['option'][$currency->code] = $currency->name;
                            }
                        }
                    }
                    // Get stores
                    /* $urlApi = $this->url.'stores'.$this->apiKey;
                    $return = $this->call($urlApi, 'get', '');
                    $code = $return->__get('code');
                    if ($code == '200') {
                        $body = $return->__get('body');
                        if (!empty($body)) {
                            foreach ($body as $currency) {
                                $this->moduleFields['currency']['option'][$currency->code] = $currency->name;
                            }
                        }
                    }  */
                } catch (\Exception $e) {
                }
            }
            if ('customers_addresses' == $module) {
                try {
                    // Get countries
                    $urlApi = $this->url.'countries'.$this->apiKey;
                    $return = $this->call($urlApi, 'get', '');
                    $code = $return->__get('code');
                    if ('200' == $code) {
                        $body = $return->__get('body');
                        if (!empty($body)) {
                            foreach ($body as $country) {
                                $this->moduleFields['country_id']['option'][$country->id] = $country->name;
                            }
                        }
                    }
                } catch (\Exception $e) {
                }
            }
            if ('categories' == $module) {
                try {
                    // Get store id
                    $urlApi = $this->url.'stores'.$this->apiKey;
                    $return = $this->call($urlApi, 'get', '');
                    $code = $return->__get('code');
                    if ('200' == $code) {
                        $body = $return->__get('body');
                        if (!empty($body)) {
                            foreach ($body as $store) {
                                $this->moduleFields['store_id']['option'][$store->id] = $store->name;
                            }
                        }
                    }
                } catch (\Exception $e) {
                }
            }
            // Ajout des champ relate au mapping des champs
            if (!empty($this->fieldsRelate)) {
                $this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage();

            return false;
        }
    }

    // get_module_fields($module)

    // Read one specific record
    public function read_last($param)
    {
        $result = [];
        try {
            // No history search for fictive modules we have created
            if (in_array($param['module'], ['options_values', 'products_options', 'products_stock_entries', 'orders_delivery_address', 'orders_billing_address'])) {
                $result['done'] = false;

                return $result;
            }
            // Add requiered fields
            $param['fields'] = $this->addRequiredField($param['fields'], $param['module']);
            // Remove fields that doesn't belong to shop application
            $param['fields'] = $this->cleanMyddlewareElementId($param['fields']);

            // Simulation : we get the last record
            if (empty($param['query'])) {
                $urlApi = $this->url.$param['module'].'/orderby/date_modified/desc/limit/1'.$this->apiKey;
            }
            // We try to get the history of the record
            elseif (!empty($param['query']['id'])) {
                $urlApi = $this->url.$param['module'].'/'.$param['query']['id'].$this->apiKey;
            }
            // search for duplicate date in the target module
            else {
                // Buid the search query
                $search = '';
                foreach ($param['query'] as $key => $value) {
                    $search .= '/filter/'.$key.'/equal/'.urlencode($value);
                }
                $urlApi = $this->url.$param['module'].$search.'/orderby/date_modified/desc/limit/1'.$this->apiKey;
            }
            // Try to access to the shop
            $return = $this->call($urlApi, 'get', '');

            $code = $return->__get('code');
            // If the call is a success
            if ('200' == $code) {
                $body = $return->__get('body');
                if (!empty($body)) {
                    // destroy the dimension because we can have only one record
                    $body = current($body);
                    foreach ($param['fields'] as $field) {
                        // Transform the field to an array in case it is a language fields
                        $filedArray = explode('__', $field);
                        $nbLevel = count($filedArray);
                        if (3 == $nbLevel) { // Language field
                            // We search the language field in the body
                            if (
                                    !empty($param['ruleParams']['language'])
                                 && !empty($body->$filedArray[0]->$param['ruleParams']['language']->$filedArray[2])
                            ) {
                                $result['values'][$field] = $body->$filedArray[0]->$param['ruleParams']['language']->$filedArray[2];
                            }
                        } else { // Other fields
                            $result['values'][$field] = $body->$field;
                        }
                    }
                    $result['done'] = true;
                }
                // If id is in query we should have a result
                elseif (!empty($param['query']['id'])) {
                    throw new \Exception('Failed to get the history of the record in the target solution. ');
                } else {
                    $result['done'] = false;
                }
            } else {
                // Get the error message
                $body = $return->__get('body');
                throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $result['done'] = -1;
        }

        return $result;
    }

    // Read one specific record
    public function read($param)
    {
        $result['count'] = 0;
        try {
            // Get the reference date
            $dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);

            // Add requiered fields
            $param['fields'] = $this->addRequiredField($param['fields'], $param['module']);
            // Remove fields that doesn't belong to shop application
            $param['fields'] = $this->cleanMyddlewareElementId($param['fields']);

            // We build the url (get all data after the reference date)
            $urlApi = $this->url.$param['module'].'/filter/'.$dateRefField.'/superior/'.urlencode($param['date_ref']).'/orderby/date_created/asc'.$this->apiKey;

            // Try to access to the shop
            $return = $this->call($urlApi, 'get', '');

            $code = $return->__get('code');
            // If the call is a success
            if ('200' == $code) {
                $body = $return->__get('body');
                if (!empty($body)) {
                    // For each record
                    foreach ($body as $id => $record) {
                        $row = [];
                        // For each fields
                        foreach ($param['fields'] as $field) {
                            if ($field == $dateRefField) {
                                $row['date_modified'] = $record->$field;
                                // Save the latest reference date
                                if (
                                        empty($result['date_ref'])
                                     || $record->$field > $result['date_ref']
                                ) {
                                    $result['date_ref'] = $record->$field;
                                }
                            } else {
                                // Transform the field to an array in case it is a language fields
                                $filedArray = explode('__', $field);
                                $nbLevel = count($filedArray);
                                if (3 == $nbLevel) { // Language field
                                    // We search the language field in the body
                                    if (
                                            !empty($param['ruleParams']['language'])
                                         && !empty($record->$filedArray[0]->$param['ruleParams']['language']->$filedArray[2])
                                    ) {
                                        $row[$field] = $record->$filedArray[0]->$param['ruleParams']['language']->$filedArray[2];
                                    }
                                } else { // Other fields
                                    $row[$field] = $record->$field;
                                }
                            }
                        }
                        $result['values'][$id] = $row;
                        ++$result['count'];
                    }
                }
            } else {
                // Get the error message
                $body = $return->__get('body');
                throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }

        return $result;
    }

    // Permet de créer un enregistrement
    public function create($param)
    {
        // For each record to send
        foreach ($param['data'] as $idDoc => $data) {
            try {
                $dataTosSend = '';
                // Check control before update
                $data = $this->checkDataBeforeCreate($param, $data);
                // Preparation of the post
                $dataTosSendTmp = $this->buildSendingData($param, $idDoc, $data, $this->childModuleParameters[$param['module']], 'C');

                // Add a dimension for the webservice
                $dataTosSend[] = $dataTosSendTmp;

                // Generate URL
                $urlApi = $this->url.$param['module'].$this->apiKey;

                // Creation of the record
                $return = $this->call($urlApi, 'post', $dataTosSend);

                // Get the response code
                $code = $return->__get('code');
                // Get the data from the response
                $body = $return->__get('body');

                // If the call is a success
                if ('200' == $code) {
                    // Could be in 200 with an error
                    if (!empty($body->errors)) {
                        throw new \Exception(print_r($body->errors, true));
                    }
                    // The record has been successfully created if the id exist
                    if (!empty($body[0]->id)) {
                        $result[$idDoc] = [
                            'id' => $body[0]->id,
                            'error' => false,
                        ];
                        // Set all id from the childs documents in the array $this->docIdList
                        if (!empty($this->docIdList)) {
                            $result = array_merge($this->docIdList, $result);
                        }
                    } else {
                        $result[$idDoc] = [
                            'id' => '-1',
                            'error' => '01',
                        ];
                    }
                } else {
                    // Set the error message
                    throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
                }
            } catch (\Exception $e) {
                $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
        }
        // Change document status
        if (!empty($result)) {
            foreach ($result as $key => $value) {
                $this->updateDocumentStatus($key, $value, $param);
            }
        }

        return $result;
    }

    // Permet de créer un enregistrement
    public function update($param)
    {
        // For each record to send
        foreach ($param['data'] as $idDoc => $data) {
            try {
                $dataTosSend = '';
                // Check control before update
                $data = $this->checkDataBeforeUpdate($param, $data);

                // Preparation of the put
                $dataTosSendTmp = $this->buildSendingData($param, $idDoc, $data, $this->childModuleParameters[$param['module']], 'U');

                // Add a dimension for the webservice
                $dataTosSend[] = $dataTosSendTmp;
                // Generate URL (we get the real module to call if the currente module is a fictive module created for Myddleware
                $urlApi = $this->url.(!empty($this->callModule[$param['module']]) ? $this->callModule[$param['module']] : $param['module']).$this->apiKey;

                // Creation of the record
                $return = $this->call($urlApi, 'put', $dataTosSend);

                // Get the response code
                $code = $return->__get('code');
                // Get the data from the response
                $body = $return->__get('body');

                // If the call is a success
                if ('200' == $code) {
                    // Could be in 200 with an error
                    if (!empty($body->errors)) {
                        throw new \Exception(print_r($body->errors, true));
                    }
                    // The record has been successfully created if the id exist
                    if (!empty($body[0]->id)) {
                        $result[$idDoc] = [
                            'id' => $body[0]->id,
                            'error' => false,
                        ];
                        // Set all id from the childs documents in the array $this->docIdList
                        if (!empty($this->docIdList)) {
                            $result = array_merge($this->docIdList, $result);
                        }
                    } else {
                        $result[$idDoc] = [
                            'id' => '-1',
                            'error' => '01',
                        ];
                    }
                } else {
                    // Set the error message
                    throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
                }
            } catch (\Exception $e) {
                $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
        }
        // Change document status
        if (!empty($result)) {
            foreach ($result as $key => $value) {
                $this->updateDocumentStatus($key, $value, $param);
            }
        }

        return $result;
    }

    // Generate the data to send in the create or update POST
    // Entry_name is the name of the entry in cas the function is call for a child data
    protected function buildSendingData($param, $idDoc, $data, $childModuleParameters, $mode, $entry_name = '', $level = [])
    {
        $lockChild = false;
        // Save all doc ID to change their status to send (child and parent document)
        $this->docIdList[$idDoc] = ['id' => '', 'error' => false];
        // For each fields of the record
        foreach ($data as $key => $value) {
            $fieldStructure = '';
            // Replace __ISO__ if the field contains __ISO__
            if (!empty($param['ruleParams']['language'])) {
                $key = str_replace('__ISO__', '__'.$param['ruleParams']['language'].'__', $key);
            }

            // Target id isn't a shop-application field (it is used by Myddleware)
            if ('target_id' == $key) {
                if ('U' == $mode) {
                    // If a specific id exist we get it otherwise we put the default value id
                    if (!empty($childModuleParameters['id_name'])) {
                        $dataTosSend[$childModuleParameters['id_name']] = $value;
                    } else {
                        $dataTosSend['id'] = $value;
                    }
                }
                continue;
            }
            if (is_array($value)) {
                if (empty($level[$param['module']][$key])) {
                    $level[$param['module']][$key] = 0;
                }
                ++$level[$param['module']][$key];
                foreach ($value as $idDocSubrecord => $subrecord) {
                    // Recursive call in case sub tab exist
                    // If there is no entry name found in the structure, we merge data inside the current level otherwise we add the array to the result
                    if (!empty($childModuleParameters[$key]['entry_name'])) {
                        // If thetype is an arry we create a new entry, otherwise it is a structure so we just add the struture
                        if ('array' == $childModuleParameters[$key]['type']) {
                            $dataTosSend[$childModuleParameters[$key]['entry_name']][] = $this->buildSendingData($param, $idDocSubrecord, $subrecord, $childModuleParameters[$key], $mode, $key, $level);
                        } else { // structure
                            $dataTosSend[$childModuleParameters[$key]['entry_name']] = $this->buildSendingData($param, $idDocSubrecord, $subrecord, $childModuleParameters[$key], $mode, $key, $level);
                        }
                    } else {
                        $dataChild = $this->buildSendingData($param, $idDocSubrecord, $subrecord, $childModuleParameters, $mode, $key, $level);
                        // We create a new record if the key are equals (we could have an sub array with several records)
                        // This record is save to create data in the previous level
                        if (empty(array_diff_key($dataChild, $dataTosSend))) {
                            $this->newChild[] = $dataChild;
                            $lockChild = true;
                        } else {
                            $dataTosSend = array_merge($dataTosSend, $dataChild);
                        }
                    }
                    // If we are at the correct level we can add the child generated in the recursive call
                    // if (!empty($this->newChild) && $level[$param['module']][$key] == $childModuleParameters['max_level']) {
                    if (!empty($this->newChild) && !$lockChild) {
                        foreach ($this->newChild as $child) {
                            // If the submodule is a new entry in the subarray (2 dimensions) otherwise we just create an array (1 dimension)
                            if (!empty($childModuleParameters['entry_name'])) {
                                // If thetype is an arry we create a new entry, otherwise it is a structure so we just add the struture
                                if ('array' == $childModuleParameters[$key]['type']) {
                                    $dataTosSend[$childModuleParameters['entry_name']][] = $child;
                                } else { // structure
                                    $dataTosSend[$childModuleParameters['entry_name']] = $child;
                                }
                            } else {
                                $dataTosSend[] = $child;
                            }
                        }
                        $this->newChild = [];
                    }
                }
            } else {
                // Change value and key if needed
                $newValue = $this->changeBeforeSend($value, $key, $param, $data, $mode, $entry_name, $level);
                $value = $newValue['value'];
                $key = $newValue['key'];
                // Structure transformation to an array id needed
                $fieldStructure = explode('__', $key);
                // We exclude Myddleware data
                if (!in_array($key, ['id_doc_myddleware', 'source_date_modified'])) {
                    $nbLevel = count($fieldStructure);
                    if (3 == $nbLevel) {
                        $dataTosSend[$fieldStructure[0]][$fieldStructure[1]][$fieldStructure[2]] = $value;
                    } elseif (2 == $nbLevel) {
                        $dataTosSend[$fieldStructure[0]][$fieldStructure[1]] = $value;
                    } else {
                        $dataTosSend[$key] = $value;
                    }
                }
            }
        }

        return $dataTosSend;
    }

    protected function changeBeforeSend($value, $key, $param, $data, $mode, $entry_name, $level)
    {
        // When we get the adresse from the customer module, we delete the prefix address_
        if (
                in_array($param['module'], ['orders_delivery_address', 'orders_billing_address'])
             && 'address_' == substr($key, 0, 8)
        ) {
            $key = str_replace('address_', '', $key);
        }

        return ['value' => $value, 'key' => $key];
    }

    // Force some module in child
    public function getFieldsParamUpd($type, $module)
    {
        $params = [];
        try {
            if ('target' == $type) {
                // If language is required for the module
                if (in_array($module, $this->moduleWithLanguage)) {
                    // Get languages
                    $urlApi = $this->url.'languages'.$this->apiKey;
                    $return = $this->call($urlApi, 'get', '');
                    $code = $return->__get('code');
                    if ('200' == $code) {
                        $body = $return->__get('body');
                        if (!empty($body)) {
                            $idParam = [
                                'id' => 'language',
                                'name' => 'language',
                                'type' => 'option',
                                'label' => 'Language',
                                'required' => true,
                            ];
                            foreach ($body as $language) {
                                $idParam['option'][$language->code] = $language->name;
                            }
                            $params[] = $idParam;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
        }

        return $params;
    }

    // Return the filed reference
    public function getDateRefName($moduleSource, $ruleMode)
    {
        if (in_array($RuleMode, ['0', 'S'])) {
            return 'date_modified';
        } elseif ('C' == $ruleMode) {
            return 'date_created';
        }
        throw new \Exception("Rule mode $RuleMode unknown.");
    }

    // The function return true if we can display the column parent in the rule view, relationship tab
    // We always display the parent column with shop-application
    public function allowParentRelationship($module)
    {
        return true;
    }

    protected function call($url, $method = 'get', $data = [])
    {
        if (function_exists('curl_init') && function_exists('curl_setopt')) {
            $response = \Unirest::$method(
                $url, // URL de destination
                ['Accept' => 'application/json'], // Type des données envoyées
                json_encode($data) // On encode nos données en JSON
            );

            return $response;
        }
        throw new \Exception('curl extension is missing!');
    }
}// class shopappcore

/* * * * * * * *  * * * * * *  * * * * * *
    si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/shopapplication.php';
if (file_exists($file)) {
    require_once $file;
} else {
    class shopapplication extends shopapplicationcore
    {
    }
}
