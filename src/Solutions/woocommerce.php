<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

namespace App\Solutions;

use Automattic\WooCommerce\Client;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class woocommercecore extends solution
{
    protected $apiUrlSuffix = '/wp-json/wc/v3/';
    protected $url;
    protected $consumerKey;
    protected $consumerSecret;
    protected $woocommerce;
    protected $callLimit = 100;       // WooCommerce API only allows 100 records per page read
    protected $delaySearch = '-1 month';
    protected $subModules = [
                                'line_items' => ['parent_module' => 'orders',
                                                      'parent_id' => 'order_id', ],
                            ];

    //Log in form parameters
    public function getFieldsLogin()
    {
        return [
                    [
                        'name' => 'url',
                        'type' => TextType::class,
                        'label' => 'solution.fields.url',
                    ],
                    [
                        'name' => 'consumerkey',
                        'type' => PasswordType::class,
                        'label' => 'solution.fields.consumerkey',
                    ],
                    [
                        'name' => 'consumersecret',
                        'type' => PasswordType::class,
                        'label' => 'solution.fields.consumersecret',
                    ],
                ];
    }

    // Logging in to Woocommerce
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            $this->woocommerce = new Client(
                $this->paramConnexion['url'],
                $this->paramConnexion['consumerkey'],
                $this->paramConnexion['consumersecret'],
                [
                    'wp_api' => true,
                    'version' => 'wc/v3',
                ]
                );
            if ($this->woocommerce->get('data')) {
                $this->connexion_valide = true;
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    public function get_modules($type = 'source')
    {
        return [
            'customers' => 'Customers',
            'orders' => 'Orders',
            'products' => 'Products',
            // 'reports' => 'Reports',
            // 'settings' => 'Settings',
            // 'shipping' => 'Shipping',  shipping/zones
            // 'taxes' => 'Taxes',
            // 'webhooks' => 'Webhooks',
            // 'shipping-methods' => 'Shipping Methods',
            'line_items' => 'Line Items',
            // 'payment_gateways' => 'Payment Gateways'
        ];
    }

    public function get_module_fields($module, $type = 'source', $param = null)
    {
        require 'lib/woocommerce/metadata.php';
        parent::get_module_fields($module, $type);
        try {
            if (!empty($moduleFields[$module])) {
                $this->moduleFields = array_merge($this->moduleFields, $moduleFields[$module]);
            }

            // if (!empty($fieldsRelate[$module])) {
            // 	$this->fieldsRelate = $fieldsRelate[$module];
            // }
            // // Includ relate fields into moduleFields to display them in the field mapping tab
            // if (!empty($this->fieldsRelate)) {
            // 	$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
            // }
            // include custom fields that could have been added with a plugin
            // (for instance Checkout Field Editor for WooCommerce allows you to create custom fields for your order forms)
            // the custom fields need to be added manually in src/Myddleware/RegleBundle/Custom/Solutions/woocommerce.php
            if (!empty($this->customFields)) {
                foreach ($this->customFields as $customModuleKey => $customModule) {
                    foreach ($customModule as $customField) {
                        if ($module === $customModuleKey) {
                            $this->moduleFields[$customField] = [
                                                                    'label' => ucfirst($customField),
                                                                    'type' => 'varchar(255)',
                                                                    'type_bdd' => 'varchar(255)',
                                                                    'required' => 0,
                                                                ];
                        }
                    }
                }
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());

            return false;
        }
    }

    // Read all fields, ordered by date_modified
    // $param => [[module],[rule], [date_ref],[ruleParams],[fields],[offset],[limit],[jobId],[manual]]
    // public function readData($param) {
    public function read($param)
    {
        try {
            $module = $param['module'];
            $result = [];
            // format the reference date
            $dateRefWooFormat = $this->dateTimeFromMyddleware($param['date_ref']);
            // Set the limit
            if (empty($param['limit'])) {
                $param['limit'] = $this->callLimit;
            }

            // adding query parameters into the request
            if (!empty($param['query'])) {
                $query = '';
                foreach ($param['query'] as $key => $value) {
                    if ('id' === $key) {
                        $query = strval('/'.$value);
                    } else {
                        // in case of query on sub module, we check if that the search field is the parent id
                        if (
                                !empty($this->subModules[$param['module']])
                            and $this->subModules[$param['module']]['parent_id'] == $key
                        ) {
                            $query = strval('/'.$value);
                        }
                    }
                }
            }

            //for submodules, we first send the parent module in the request before working on the submodule with convertResponse()
            if (!empty($this->subModules[$param['module']])) {
                $module = $this->subModules[$param['module']]['parent_module'];
            }

            $stop = false;
            $count = 0;
            $page = 1;
            do {
                //for specific requests (e.g. readrecord with an id)
                if (!empty($query)) {
                    $response = $this->woocommerce->get($module.$query, ['per_page' => $this->callLimit,
                                                                              'page' => $page, ]);
                    //when reading a specific record only we need to add a layer to the array
                    $records = $response;
                    $response = [];
                    $response[] = $records;
                } elseif ('customers' === $module) {
                    //orderby modified isn't available for customers in the API filters so we sort by creation date
                    $response = $this->woocommerce->get($module, ['orderby' => 'registered_date',
                                                                                    'order' => 'asc',
                                                                                    'per_page' => $this->callLimit,
                                                                                    'page' => $page, ]);
                //get all data, sorted by date_modified
                } else {
                    $response = $this->woocommerce->get($module, ['orderby' => 'modified',
                                                                                'per_page' => $this->callLimit,
                                                                                'page' => $page, ]);
                }
                if (!empty($response)) {
                    //used for submodules (e.g. line_items)
                    $response = $this->convertResponse($param, $response);
                    foreach ($response as $record) {
                        $row = [];
                        //either we read all from a date_ref or we read based on a query (readrecord)
                        if ($dateRefWooFormat < $record->date_modified || (!empty($query))) {
                            foreach ($param['fields'] as $field) {
                                // If we have a 2 dimensional array we break it down
                                $fieldStructure = explode('__', $field);
                                $fieldGroup = '';
                                $fieldName = '';
                                if (!empty($fieldStructure[1])) {
                                    $fieldGroup = $fieldStructure[0];
                                    $fieldName = $fieldStructure[1];
                                    $row[$field] = (!empty($record->$fieldGroup->$fieldName) ? $record->$fieldGroup->$fieldName : '');
                                } else {
                                    $row[$field] = (!empty($record->$field) ? $record->$field : '');
                                }
                            }
                            $row['id'] = $record->id;
                            ++$count;
                            $result[] = $row;
                        } else {
                            $stop = true;
                        }
                    }
                } else {
                    $stop = true;
                }
                if (!empty($query)) {
                    $stop = true;
                }
                ++$page;
            } while (!$stop && $count < $param['limit']);
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }

        return $result;
    }

    //for specific modules (e.g. : line_items)
    public function convertResponse($param, $response)
    {
        if (array_key_exists($param['module'], $this->subModules)) {
            $subModule = $param['module'];   //line_items
            $newResponse = [];
            if (!empty($response)) {
                foreach ($response as $key => $record) {
                    foreach ($record->$subModule as $subRecord) {
                        $subRecord->date_modified = $record->date_modified;
                        //we add the ID of the parent field in the data (e.g. : for line_items, we add order_id)
                        $parentFieldName = $this->subModules[$subModule]['parent_id'];
                        $subRecord->$parentFieldName = $record->id;
                        $newResponse[$subRecord->id] = $subRecord;
                    }
                }
            }

            return $newResponse;
        }

        return $response;
    }

    /**
     * Function create data.
     *
     * @param $param
     *
     * @return mixed
     */
    public function createData($param)
    {
        return $this->upsert('create', $param);
    }

    /**
     * Function update data.
     *
     * @param $param
     *
     * @return mixed
     */
    public function updateData($param)
    {
        return $this->upsert('update', $param);
    }

    public function upsert($method, $param)
    {
        foreach ($param['data'] as $idDoc => $data) {
            try {
                $result = [];
                $param['method'] = $method;
                $module = $param['module'];
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);

                if ('create' === $method) {
                    unset($data['target_id']);
                    $recordResult = $this->woocommerce->post($module, $data);
                } else {
                    $targetId = $data['target_id'];
                    unset($data['target_id']);
                    $recordResult = $this->woocommerce->put($module.'/'.$targetId, $data);
                }

                $response = $recordResult;
                if ($response) {
                    $record = $response;
                    if (!empty($record->id)) {
                        $result[$idDoc] = [
                                            'id' => $record->id,
                                            'error' => false,
                                    ];
                    } else {
                        throw new \Exception('Error during '.print_r($response));
                    }
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $result[$idDoc] = [
                                        'id' => '-1',
                                        'error' => $error,
                                        ];
            }
            // Modification du statut du flux
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    // Check data before create
    // Add a throw exeption if error

    // Check data before update
    // Add a throw exeption if error
    protected function checkDataBeforeUpdate($param, $data, $idDoc)
    {
        // Exception if the job has been stopped manually
        $this->isJobActive($param);

        return $data;
    }

    // Check data before update
    // Add a throw exeption if error
    protected function checkDataBeforeDelete($param, $data)
    {
        // Exception if the job has been stopped manually
        $this->isJobActive($param);

        return $data;
    }

    // Convert date to Myddleware format
    // 2020-07-08T12:33:06 to 2020-07-08 10:33:06
    protected function dateTimeToMyddleware($dateTime)
    {
        $dto = new \DateTime($dateTime);

        return $dto->format('Y-m-d H:i:s');
    }

    //convert from Myddleware format to Woocommerce format
    protected function dateTimeFromMyddleware($dateTime)
    {
        $dto = new \DateTime($dateTime);
        // Return date to UTC timezone
        return $dto->format('Y-m-d\TH:i:s');
    }

    // Renvoie le nom du champ de la date de référence en fonction du module et du mode de la règle
    public function getRefFieldName($moduleSource, $RuleMode)
    {
        return 'date_modified';
    }
}

class woocommerce extends woocommercecore
{
}
