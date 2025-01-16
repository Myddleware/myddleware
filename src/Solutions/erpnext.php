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

use DateTime;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class erpnext extends solution
{
    protected $token;
    protected $update;
    protected $organizationTimezoneOffset;
    protected int $limitCall = 100;
    protected array $required_fields = ['default' => ['name', 'creation', 'modified']];
    protected array $FieldsDuplicate = ['Contact' => ['last_name'],
        'Company' => ['company_name'],
        'Item' => ['item_code'],
    ];
    // Module list that allows to make parent relationships
    protected array $allowParentRelationship = ['Sales Invoice', 'Sales Order', 'Payment Entry', 'Item Attribute', 'Item', 'Payment', 'Assessment Result'];
    protected array $childModuleKey = [
        'Sales Invoice Item' => 'items',
        'Sales Order Item' => 'items',
        'Payment Entry Reference' => 'references',
        'Item Attribute Value' => 'item_attribute_values',
        'Item Variant Attribute' => 'attributes',
        'Sales Invoice Payment' => 'payments',
        'Assessment Result Detail' => 'details',
        'Sales Taxes and Charges' => 'taxes',
    ];
    // Get isTable parameter for each module
    protected array $isTableModule = [];

    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
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
        ];
    }

    // Login to Cirrus Shield
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            // Generate parameters to connect to Cirrus Shield
            $parameters = ['usr' => $this->paramConnexion['login'],
                'pwd' => $this->paramConnexion['password'],
            ];

            $url = $this->paramConnexion['url'].'/api/method/login';
            // Connect to ERPNext
            $result = $this->call($url, 'GET', $parameters);

            if (empty($result->message)) {
                throw new \Exception('Login error');
            }
            // Connection validation
            $this->connexion_valide = true;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Get the modules available
    public function get_modules($type = 'source')
    {
        try {
            // Get
            $url = $this->paramConnexion['url'].'/api/resource/DocType?limit_page_length=1000&fields=[%22name%22,%20%22istable%22]';
            $APImodules = $this->call($url, 'GET');
            if (!empty($APImodules->data)) {
                foreach ($APImodules->data as $APImodule) {
                    $modules[$APImodule->name] = $APImodule->name;
                    // Save istable parameter for each modules
                    $this->isTableModule[$APImodule->name] = $APImodule->istable;
                }
            }

            return $modules;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
            $this->logger->error($error);
            return $error;
        }
    }

    // Get the fields available for the module in input
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            // Call get modules to fill the isTableModule array and ge the module list.
            $modules = $this->get_modules();

            // Get the list field for a module
            $url = $this->paramConnexion['url'].'/api/method/frappe.desk.form.load.getdoctype?doctype='.rawurlencode($module);
            $recordList = $this->call($url, 'GET', '');
            // Format outpput data
            if (!empty($recordList->docs[0]->fields)) {
                foreach ($recordList->docs[0]->fields as $field) {
                    if (empty($field->label)) {
                        continue;
                    }
                    if (in_array($field->fieldtype, ['Link', 'Dynamic Link'])) {
                        $this->moduleFields[$field->fieldname] = [
                            'label' => $field->label,
                            'type' => 'varchar(255)',
                            'type_bdd' => 'varchar(255)',
                            'required' => '',
                            'required_relationship' => '',
                            'relate' => true,
                        ];
                    // Add field to manage dymamic links
                    } elseif (
                            'Table' == $field->fieldtype
                        and 'Dynamic Link' == $field->options
                    ) {
                        $this->moduleFields['link_doctype'] = [
                            'label' => 'Link Doc Type',
                            'type' => 'varchar(255)',
                            'type_bdd' => 'varchar(255)',
                            'required' => '',
                            'option' => $modules,
                            'relate' => false,
                        ];
                        $this->moduleFields['link_name'] = [
                            'label' => 'Link name',
                            'type' => 'varchar(255)',
                            'type_bdd' => 'varchar(255)',
                            'required' => '',
                            'required_relationship' => '',
                            'relate' => true,
                        ];
                    } else {
                        $this->moduleFields[$field->fieldname] = [
                            'label' => $field->label,
                            'type' => 'varchar(255)',
                            'type_bdd' => 'varchar(255)',
                            'required' => '',
                            'relate' => false,
                        ];
                        if (!empty($field->options)) {
                            $options = explode(chr(10), $field->options);
                            if (
                                !empty($options)
                                and count($options) > 1
                            ) {
                                foreach ($options as $option) {
                                    $this->moduleFields[$field->fieldname]['option'][$option] = $option;
                                }
                            }
                        }
                    }
                }
            } else {
                throw new \Exception('No data in the module '.$module.'. Failed to get the field list.');
            }

            //If the module is a table and the solution is used in target, we add 3 fields
            if (
                    'target' == $type
                and !empty($this->isTableModule[$module])
            ) {
                // Parenttype => relate module/DocType de la relation (eg for Sales Invoice Item, it will be Sales Invoice)
                $this->moduleFields['parenttype'] = [
                    'label' => 'Parent type',
                    'type' => 'varchar(255)',
                    'type_bdd' => 'varchar(255)',
                    'required' => '',
                    'option' => $modules,
                    'relate' => false,
                ];
                // Parentfield => field name in the parent module (eg. "items" in module Sales Invoice). We can't give the field list because we don't know the module selected yet
                $this->moduleFields['parentfield'] = [
                    'label' => 'Parent field',
                    'type' => 'varchar(255)',
                    'type_bdd' => 'varchar(255)',
                    'required' => '',
                    'relate' => false,
                ];
                // Parent => value of the parent field (eg SINV-00001 which is the "Sales Invoice" parent)
                $this->moduleFields['parent'] = [
                    'label' => 'Parent',
                    'type' => 'varchar(255)',
                    'type_bdd' => 'varchar(255)',
                    'required' => '',
                    'required_relationship' => '',
                    'relate' => true,
                ];
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());

            return [];
        }
    }

    public function read($param): array
    {
        try {
            $result = [];
            $data = [];
            // Get the reference date field name
            $dateRefField = $this->getRefFieldName($param);

            // Add 1 second to the date ref because the call to ERPNExt includes the date ref.. Otherwise we will always read the last record
            $date = new \DateTime($param['date_ref']);
            $date = date_modify($date, '+1 seconde');
            $param['date_ref'] = $date->format('Y-m-d H:i:s');

            // Build the query for ERPNext
            if (!empty($param['query'])) {
                foreach ($param['query'] as $key => $value) {
                    // The id field is name in ERPNext
                    if ('id' === $key) {
                        $key = 'name';
                    }
                    $filters_result[$key] = $value;
                }
                $filters = json_encode($filters_result);
                $data = ['filters' => $filters, 'fields' => '["*"]'];
            } else {
                $filters = '{"'.$dateRefField.'": [">", "'.$param['date_ref'].'"]}';
                $data = ['filters' => $filters, 'fields' => '["*"]'];
            }

            // Send the query
            $q = http_build_query($data);
            $url = $this->paramConnexion['url'].'/api/resource/'.rawurlencode($param['module']).'?'.$q;
            $resultQuery = $this->call($url, 'GET', '');

            // If no result
            if (empty($resultQuery)) {
                $result['error'] = 'Request error';
            } elseif (count($resultQuery->data) > 0) {
                $resultQuery = $resultQuery->data;
                foreach ($resultQuery as $key => $recordList) {
                    $record = null;
                    foreach ($param['fields'] as $field) {
                        $record[$field] = $recordList->$field;
                    }
                    // The name is the id in ERPNExt
                    $record['id'] = $recordList->name;
                    $result[] = $record; // last record
                }
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' '.$e->getLine();
        }

        return $result;
    }

    public function createData($param): array
    {
        return $this->createUpdate('create', $param);
    }

    public function updateData($param): array
    {
        return $this->createUpdate('update', $param);
    }

    /**
     * @param $method
     * @param $param
     *
     * @return array
     */
    public function createUpdate($method, $param): array
    {
        try {
            $result = [];
            $subDocIdArray = [];
            $url = $this->paramConnexion['url'].'/api/resource/'.rawurlencode($param['module']);
            if ('update' == $method) {
                $method = 'PUT';
            } else {
                $method = 'POST';
            }
            foreach ($param['data'] as $idDoc => $data) {
                try {
                    foreach ($data as $key => $value) {
                        // We don't send Myddleware fields
						if (
								'target_id' == $key
							and !empty($value)
						) {
							$url = $this->paramConnexion['url'].'/api/resource/'.rawurlencode($param['module']).'/'.rawurlencode($value);
							unset($data[$key]);
                        // if the data is a link
                        } elseif ('link_doctype' == $key) {
                            $data['links'] = [['link_doctype' => $data[$key], 'link_name' => $data['link_name']]];
                            unset($data[$key]);
                            unset($data['link_name']);
                        // If the data is a submodule (eg : invoice lines)
                        } elseif (is_array($value)) {
                            if (empty($this->childModuleKey[$key])) {
                                throw new \Exception('The childModuleKey is missing for the module '.$key);
                            }
                            if (!empty($value)) {
                                foreach ($value as $subIdDoc => $subData) {
                                    // Save the subIdoc to change the sub data transfer status
                                    $subDocIdArray[$subIdDoc] = ['id' => uniqid('', true)];
                                    foreach ($subData as $subKey => $subValue) {
                                        // We don't send Myddleware fields
                                        if (in_array($subKey, ['target_id', 'id_doc_myddleware', 'source_date_modified'])) {
                                            unset($subData[$subKey]);
                                        // if the data is a link
                                        } elseif ('link_doctype' == $subKey) {
                                            $subData['links'] = [['link_doctype' => $subData[$subKey], 'link_name' => $subData['link_name']]];
                                            unset($subData[$subKey]);
                                            unset($subData['link_name']);
                                        }
                                    }
                                    $data[$this->childModuleKey[$key]][] = $subData;
                                }
                            }
                            // Remove the original array
                            unset($data[$key]);
                        }
                    }
                    // Send data to ERPNExt
                    $resultQuery = $this->call($url, $method, ['data' => json_encode($data)]);

                    if (!empty($resultQuery->data->name)) {
                        // utf8_decode because the id could be a name with special characters
                        $result[$idDoc] = ['id' => utf8_decode($resultQuery->data->name), 'error' => ''];
                    } elseif (!empty($resultQuery)) {
                        throw new \Exception($resultQuery);
                    } else {
                        throw new \Exception('No result from ERPNext. ');
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
                    $subDocIdArray = [];
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
     * return the reference date field name
     * @throws \Exception
     */
    public function getRefFieldName($param): string
    {
        // Creation and modification mode
        if (in_array($param['ruleParams']['mode'], ['0', 'S'])) {
            return 'modified';
        // Creation mode only
        } elseif ('C' == $param['ruleParams']['mode']) {
            return 'creation';
        }
        throw new \Exception("$param[ruleParams][mode] is not a correct Rule mode.");
    }



    /**
     * Function de conversion de datetime format solution à un datetime format Myddleware
     * @throws \Exception
     */
    protected function dateTimeToMyddleware($dateTime)
    {
        $date = new \DateTime($dateTime);

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Function de conversion de datetime format Myddleware à un datetime format solution
     * @throws \Exception
     */
    protected function dateTimeFromMyddleware($dateTime)
    {
        $date = new \DateTime($dateTime);

        return $date->format('Y-m-d H:i:s.u');
    }

    // Build the direct link to the record (used in data transfer view)
    public function getDirectLink($rule, $document, $type): string
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
        return rtrim($url, '/').'/desk#Form/'.rawurlencode($module).'/'.rawurlencode($recordId);
    }

    /**
     * @param $url
     * @param string $method
     * @param array  $parameters
     * @param int    $timeout
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    protected function call($url, $method = 'GET', $parameters = [], $timeout = 300)
    {
        if (!function_exists('curl_init') or !function_exists('curl_setopt')) {
            throw new \Exception('curl extension is missing!');
        }
        $fileTmp = $this->parameterBagInterface->get('kernel.cache_dir').'/myddleware/solutions/erpnext/erpnext.txt';
        $fs = new Filesystem();
        try {
            $fs->mkdir(dirname($fileTmp));
        } catch (IOException $e) {
            throw new \Exception($this->tools->getTranslation(['messages', 'rule', 'failed_create_directory']));
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // common description bellow
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $fileTmp);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $fileTmp);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $response = curl_exec($ch);

        // if Traceback found, we have an error
        if (
                'GET' != $method
            and false !== strpos($response, 'Traceback')
        ) {
            // Extraction of the error from traceback'
            return substr($response, strpos($response, 'Traceback') - strlen($response));
        }
        curl_close($ch);

        $result = json_decode($response);
        // If result not a json, we send the result as it has been return (used for 301 error for example)
        if (empty($result)) {
            $result = $response;
        }

        return $result;
    }
}
