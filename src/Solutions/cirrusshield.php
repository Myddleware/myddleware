<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

class cirrusshieldcore extends solution
{
    protected string $url = 'https://www.cirrus-shield.net/RestApi/';
    protected $token;
    protected $update;
    protected $organizationTimezoneOffset;
    protected int $limitCall = 1;
    protected array $required_fields = ['default' => ['Id', 'CreationDate', 'ModificationDate']];
    protected array $FieldsDuplicate = ['Contact' => ['Email', 'Name'],
        'default' => ['Name'],
    ];

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
        ];
    }

    // Login to Cirrus Shield
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            // Generate parameters to connect to Cirrus Shield
            $login = ['Username' => $this->paramConnexion['login'],
                'password' => $this->paramConnexion['password'],
            ];
            $url = sprintf('%s?%s', $this->url.'AuthToken', http_build_query($login));

            // Get the token
            $this->token = $this->call($url, 'login');
            if (empty($this->token)) {
                throw new \Exception('login error');
            }

            // Connection validation
            $this->connexion_valide = true;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
            $this->logger->error($error);
        }
    }


    // Get the modules available
    public function get_modules($type = 'source')
    {
        try {
            $apiModules = $this->call($this->url.'DescribeAll?authToken='.$this->token);
            if (!empty($apiModules)) {
                foreach ($apiModules as $apiModule) {
                    $modules[$apiModule['APIName']] = $apiModule['Label'];
                }
            }

            return $modules;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    // Get the fields available for the module in input
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            $apiFields = $this->call($this->url.'Describe/'.$module.'?authToken='.$this->token);
            if (!empty($apiFields['Fields'])) {
                // Add each field in the right list (relate fields or normal fields)
                foreach ($apiFields['Fields'] as $field) {
                    // Field not editable can't be display on the target side
                    if (
                            empty($field['IsEditable'])
                        and 'target' == $type
                    ) {
                        continue;
                    }
                    // If the fields is a relationship
                    if ('LookupRelationship' == $field['DataType']) {
                        $this->moduleFields[$field['Name']] = [
                            'label' => $field['Label'],
                            'type' => 'varchar(36)',
                            'type_bdd' => 'varchar(36)',
                            'required' => $field['IsRequired'],
                            'required_relationship' => $field['IsRequired'],
                            'relate' => true,
                        ];
                    } else {
                        $this->moduleFields[$field['Name']] = [
                            'label' => $field['Label'],
                            'type' => $field['DataType'],
                            'type_bdd' => $field['DataType'],
                            'required' => $field['IsRequired'],
                            'relate' => false,
                        ];
                        // Add list of values
                        if (!empty($field['PicklistValues'])) {
                            foreach ($field['PicklistValues'] as $value) {
                                $this->moduleFields[$field['Name']]['option'][$value['Name']] = $value['Label'];
                            }
                        }
                    }
                }
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());
            return [];
        }
    }


    public function readData($param): array
    {
        try {
            $result['date_ref'] = $param['date_ref'];
            $result['count'] = 0;
            // Add required fields
            $param['fields'] = $this->addRequiredField($param['fields'], $param['module'], $param['ruleParams']['mode']);
            // Remove Myddleware 's system fields
            $param['fields'] = $this->cleanMyddlewareElementId($param['fields']);

            // Get the reference date field name
            $dateRefField = $this->getRefFieldName($param['module'], $param['ruleParams']['mode']);

            // Get the organization timezone
            if (empty($this->organizationTimezoneOffset)) {
                $this->getOrganizationTimezone();
                // If the organization timezone is still empty, we generate an error
                if (empty($this->organizationTimezoneOffset)) {
                    throw new \Exception('Failed to get the organization Timezone. This timezone is requierd to save the reference date.');
                }
            }

            $query = 'SELECT ';
            // Build the SELECT
            if (!empty($param['fields'])) {
                foreach ($param['fields'] as $field) {
                    $query .= $field.',';
                }
                // Delete the last coma
                $query = rtrim($query, ',');
            } else {
                $query .= ' * ';
            }

            // Add the FROM
            $query .= ' FROM '.$param['module'].' ';

            // Generate the WHERE
            // if a specific query is requeted we don't use date_ref (used for child document)
            if (!empty($param['query'])) {
                $query .= ' WHERE ';
                $first = true;
                foreach ($param['query'] as $key => $value) {
                    // Add the AND only if we are not on the first condition
                    if ($first) {
                        $first = false;
                    } else {
                        $query .= ' AND ';
                    }
                    // The field id in Cirrus shield as a capital letter for the I, not in Myddleware
                    if ('id' == $key) {
                        $key = 'Id';
                    }
                    // Add the condition
                    $query .= $key." = '".$value."' ";
                }
                // Function called as a standard read, we use the reference date
            } else {
                $query .= ' WHERE '.$dateRefField.' > '.$param['date_ref'].$this->organizationTimezoneOffset;
                // $query .= " WHERE ".$dateRefField." > 2017-03-30 14:42:35-05 ";
            }

            // Buid the parameters to call the solution
            $selectparam = ['authToken' => $this->token,
                'selectQuery' => $query,
            ];
            $url = sprintf('%s?%s', $this->url.'Query', http_build_query($selectparam));
            $resultQuery = $this->call($url);

            // If the query return an error
            if (!empty($resultQuery['Message'])) {
                throw new \Exception($resultQuery['Message']);
            }
            // If no result
            if (!empty($resultQuery[$param['module']])) {
                // If only one record, we add a dimension to be able to use the foreach below
                if (empty($resultQuery[$param['module']][0])) {
                    $tmp[$param['module']][0] = $resultQuery[$param['module']];
                    $resultQuery = $tmp;
                }
                // For each records
                foreach ($resultQuery[$param['module']] as $record) {
                    // For each fields expected
                    foreach ($param['fields'] as $field) {
                        if ('id' == $field) {
                            $field = 'Id';
                        }
                        // We check the lower case because the result of the webservice return sfield without capital letter (first_name instead of First_Name)
                        if (isset($record[$field])) {
                            // If we are on the date ref field, we add the entry date_modified (put to lower case because ModificationDate in the where is modificationdate int the select
                            if ($field == $dateRefField) {
                                $row['date_modified'] = $record[$field];
                            } elseif ('Id' == $field) {
                                $row['id'] = $record[$field];
                            } else {
                                // Cirrus return an array when the data is empty
                                if (is_array($record[$field])) {
                                    $row[$field] = '';
                                } else {
                                    $row[$field] = $record[$field];
                                }
                            }
                        }
                    }
                    if (
                            !empty($record[$dateRefField])
                        && $result['date_ref'] < $record[$dateRefField]
                    ) {
                        // Transform the date with the organization timezone
                        $dateRef = new \DateTime($record[$dateRefField]);
                        $dateRef->modify($this->organizationTimezoneOffset.' hours');
                        $result['date_ref'] = $dateRef->format('Y-m-d H:i:s');
                    }
                    $result['values'][$row['id']] = $row;
                    ++$result['count'];
                    $row = [];
                }
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }

        return $result;
    }

    // Create data in the target solution
    public function createData($param): array
    {
        try {
            $i = 0;
            $nb_record = count($param['data']);
            // XML creation (for the first call)
            $xmlData = '<Data>';
            foreach ($param['data'] as $idDoc => $data) {
                ++$i;
                // Check control before create
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                $xmlData .= '<'.$param['module'].'>';

                // Save the idoc to manage result in case of mass upsert
                if ($this->limitCall > 1) {
                    $xmlData .= '<OrderId>'.$idDoc.'</OrderId>';
                }

                foreach ($data as $key => $value) {
                    // Field only used for the update and contains the ID of the record in the target solution
                    if ('target_id' == $key) {
                        // If updade then we change the key in Id
                        if (!empty($value)) {
                            $key = 'Id';
                        } else { // If creation, we skip this field
                            continue;
                        }
                    }
                    $xmlData .= '<'.$key.'>'.$value.'</'.$key.'>';
                }
                $xmlData .= '</'.$param['module'].'>';

                // If we have finished to read all data or if the package is full we send the data to Sallesforce
                if (
                        $i == $nb_record
                     || 0 == $i % $this->limitCall
                ) {
                    $xmlData .= '</Data>';

                    // Set parameters to send data to the target solution (creation or modification)
                    $selectparam = ['authToken' => $this->token,
                        'action' => ($this->update ? 'update' : 'insert'),
                        'matchingFieldName' => 'Id',
                        'useExternalId' => 'false',
                    ];
                    $url = sprintf('%s?%s', $this->url.'DataAction/'.$param['module'], http_build_query($selectparam));
                    // Send data to the target solution
                    $resultCall = $this->call($url, 'POST', urlencode($xmlData));
                    if (empty($resultCall)) {
                        throw new \Exception('Result from Cirrus Shield empty');
                    }
                    if (!empty($resultCall['Message'])) {
                        throw new \Exception($resultCall['Message']);
                    }
                    // XML initialisation (for the next call)
                    $xmlData = '<Data>';

                    // If only one result, we add a dimension
                    if (isset($resultCall[$param['module']]['Success'])) {
                        $resultCall[$param['module']] = [$resultCall[$param['module']]];
                    }

                    // Manage results
                    if (!empty($resultCall[$param['module']])) {
                        foreach ($resultCall[$param['module']] as $record) {
                            // General error
                            if (!empty($record['Message'])) {
                                throw new \Exception($record['Message']);
                            }
                            // We use orderId as id document only when we execute to mass upsert. In this case this field orderid has to be created in Cirrus
                            if (!empty($record['orderid'])) {
                                $idDoc = $record['orderid'];
                            }

                            // Error managment for the record creation
                            if (!empty($record['Success'])) {
                                if ('False' == $record['Success']) {
                                    $result[$idDoc] = [
                                        'id' => '-1',
                                        'error' => $record['ErrorMessage'],
                                    ];
                                } else {
                                    $result[$idDoc] = [
                                        'id' => $record['GUID'],
                                        'error' => false,
                                    ];
                                }
                            } else {
                                $result[$idDoc] = [
                                    'id' => '-1',
                                    'error' => 'No success flag returned by Cirrus Shield',
                                ];
                            }
                            // Transfert status update
                            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
            $result['error'] = $error;
        }

        return $result;
    }

    // Cirrus Shield use the same function for record's creation and modification
    public function updateData($param): array
    {
        $this->update = true;
        $result = $this->createData($param);
        $this->update = false;

        return $result;
    }

    /**
     * retrun the reference date field name
     * @throws \Exception
     */
    public function getRefFieldName($moduleSource, $RuleMode): string
    {
        // Creation and modification mode
        if (in_array($RuleMode, ['0', 'S'])) {
            return 'ModificationDate';
        // Creation mode only
        } elseif ('C' == $RuleMode) {
            return 'CreationDate';
        }
        throw new \Exception("$RuleMode is not a correct Rule mode.");
    }

    /**
     * @throws \Exception
     */
    protected function getOrganizationTimezone()
    {
        // Get the organization in Cirrus
        $query = 'SELECT DefaultTimeZoneSidKey FROM Organization';
        // Buid the parameters to call the solution
        $selectparam = ['authToken' => $this->token,
            'selectQuery' => $query,
        ];
        $url = sprintf('%s?%s', $this->url.'Query', http_build_query($selectparam));
        $resultQuery = $this->call($url);
        if (empty($resultQuery['Organization']['DefaultTimeZoneSidKey'])) {
            throw new \Exception('Failed to retrieve the organisation timezone : no organization found '.$resultQuery['Organization']['DefaultTimeZoneSidKey'].'. ');
        }

        // Get the list of timeZone  Cirrus
        $organizationFields = $this->call($this->url.'Describe/Organization?authToken='.$this->token);

        if (!empty($organizationFields['Fields'])) {
            // Get the content of the field DefaultTimeZoneSidKey
            $timezoneFieldKey = array_search('DefaultTimeZoneSidKey', array_column($organizationFields['Fields'], 'Name'));
            if (!empty($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'])) {
                // Get the key of the timezone of the organization
                $timezoneOrganizationKey = array_search($resultQuery['Organization']['DefaultTimeZoneSidKey'], array_column($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'], 'Name'));
                if (!empty($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'][$timezoneOrganizationKey]['Label'])) {
                    // Get the offset of the timezone formatted like (GMT-05:00) Eastern Standard Time (America/New_York)
                    $this->organizationTimezoneOffset = substr($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'][$timezoneOrganizationKey]['Label'], strpos($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'][$timezoneOrganizationKey]['Label'], 'GMT') + 3, 3);
                }
            }
        }
        // Error management
        if (empty($this->organizationTimezoneOffset)) {
            throw new \Exception('Failed to retrieve the organisation timezone : no timezone found for the value ');
        }
    }

    /**
     * @throws \Exception
     */
    protected function call($url, $method = 'GET', $xmlData = '', $timeout = 300)
    {
        if (function_exists('curl_init') && function_exists('curl_setopt')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Some additional parameters are required for POST
            if ('POST' == $method) {
                $headers = [
                    'Content-Type: application/x-www-form-urlencoded',
                    'charset=utf-8',
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POSTFIELDS, '='.$xmlData);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                curl_setopt($ch, CURLOPT_SSLVERSION, 6);
                curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            }
            $result = curl_exec($ch);
            curl_close($ch);
            // The login function return a string not an XML
            if ('login' == $method) {
                return $result ? json_decode($result, true) : false;
            }
            if (@simplexml_load_string($result)) {
                $xml = simplexml_load_string($result);
                $json = json_encode($xml);

                return json_decode($json, true);
                // The result can be a json directly, in case of an error of query call (read last for example)
            }

            return json_decode($result, true);
        }
        throw new \Exception('curl extension is missing!');
    }
}

class cirrusshield extends cirrusshieldcore
{
}
