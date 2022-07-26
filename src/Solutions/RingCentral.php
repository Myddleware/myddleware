<?php

declare(strict_types=1);

/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
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

use DateTime;
use Exception;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RingCentral extends Solution
{
    public const SERVER_PRODUCTION = 'https://platform.ringcentral.com';
    public const SERVER_SANDBOX = 'https://platform.devtest.ringcentral.com';

    public const ACCESS_TOKEN_TTL = 3600; // 60 minutes
    public const REFRESH_TOKEN_TTL = 604800; // 1 week
    public const TOKEN_ENDPOINT = '/restapi/oauth/token';
    public const REVOKE_ENDPOINT = '/restapi/oauth/revoke';
    public const AUTHORIZE_ENDPOINT = '/restapi/oauth/authorize';
    public const API_VERSION = '/v1.0';

    protected $apiKey;

    protected $token;

    protected $server;

    protected int $callLimit = 100;

    protected int $readLimit = 1000;
    
    protected array $moduleFields;
    
    protected array $requiredFields = [
        'default' => ['id'],
        'call-log' => ['id', 'startTime'],
        'message-store' => ['id', 'lastModifiedTime'],
        'presence' => ['id', 'date_modified'],
    ];

    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'username',
                'type' => TextType::class,
                'label' => 'solution.fields.username',
            ],
            [
                'name' => 'password',
                'type' => PasswordType::class,
                'label' => 'solution.fields.password',
            ],
            [
                'name' => 'apikey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.apikey',
            ],
            [
                'name' => 'apikeysecret',
                'type' => PasswordType::class,
                'label' => 'solution.fields.apikeysecret',
            ],
            [
                'name' => 'sandbox',
                'type' => TextType::class,
                'label' => 'solution.fields.sandbox',
            ],
        ];
    }

    public function login($connectionParam): void
    {
        parent::login($connectionParam);
        try {
            if (empty($this->connectionParam['sandbox'])) {
                $this->server = self::SERVER_PRODUCTION;
            } else {
                $this->server = self::SERVER_SANDBOX;
            }

            // Call to get the token
            $this->apiKey = base64_encode($this->connectionParam['apikey'].':'.$this->connectionParam['apikeysecret']);
            $this->token = $this->makeRequest($this->server, $this->apiKey, self::TOKEN_ENDPOINT, null, 'POST', 'username='.$this->connectionParam['username'].'&password='.$this->connectionParam['password'].'&grant_type=password');
            if (!empty($this->token)) {
                if (!empty($this->token->access_token)) {
                    $this->isConnectionValid = true;
                } elseif (!empty($this->token->error)) {
                    throw new Exception($this->token->error.(!empty($this->token->error_description) ? ': '.$this->token->error_description : ''));
                } else {
                    throw new Exception('Result from Ring Central : '.print_r($this->token, true));
                }
            } else {
                throw new Exception('No response from Ring Central. ');
            }
        } catch (Exception $e) {
            $error = $e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine();
            $this->logger->error($error);
        }
    }

    public function getModules($type = 'source'): array
    {
        return [
            'call-log' => 'Call log',
            'message-store' => 'Messages',
            'presence' => 'Presence',
        ];
    }

    public function getModuleFields($module, $type = 'source', $param = null): ?array
    {
        $moduleFields = [];
        parent::getModuleFields($module, $type);
        try {
            require 'lib/ringcentral/metadata.php';
            if (!empty($moduleFields[$module])) {
                $this->moduleFields = array_merge($this->moduleFields, $moduleFields[$module]);
            }

            return $this->moduleFields;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage().$e->getFile().$e->getLine());

            return null;
        }
    }

    public function readData($param): array
    {
        try {
            // Init the result date ref even if the date_ref isn't updated here. Indeed, the date ref is requiered in output of this function.
            $result['date_ref'] = $param['date_ref'];
            $result['count'] = 0;
            // If extensionID equal ALL, we get all extension ID and search for them
            if ('ALL' == strtoupper($param['ruleParams']['extensionId'])) {
                $pageNum = 1;
                do {
                    $extensions = $this->makeRequest($this->server, $this->token->access_token, '/restapi'.self::API_VERSION.'/account/~/extension?perPage='.$this->callLimit.'&page='.$pageNum);
                    if (!empty($extensions)) {
                        foreach ($extensions->records as $extension) {
                            $extensionIds[] = $extension->id;
                        }
                    }
                    ++$pageNum;
                } while (count($extensions->records) == $this->callLimit);
            // /restapi/v1.0/account/{accountId}/extension
            } elseif (!empty($param['ruleParams']['extensionId'])) {
                $extensionIds = explode(';', $param['ruleParams']['extensionId']);
            } else {
                $extensionIds[] = '~';
            }
            if (empty($extensionIds)) {
                throw new Exception('Failed to get the extension ID. Failed to read data from RingCentral. Please make sur the rule parameter Extension ID is correct.');
            }
            $result['count'] = 0;
            $i = 0;
            foreach ($extensionIds as $extensionId) {
                // Each extension could have its own reference date. If empty, we take the rule reference date
                if (!empty($param['ruleParams'][$extensionId])) {
                    $dateRefExt[$extensionId] = $param['ruleParams'][$extensionId];
                } else {
                    $dateRefExt[$extensionId] = $param['date_ref'];
                }
                // Add required fields
                $param['fields'] = $this->addRequiredField($param['fields'], $param['module'], $param['ruleParams']['mode']);
                // Remove Myddleware 's system fields
                $param['fields'] = $this->cleanMyddlewareElementId($param['fields']);

                // Get the reference date field name
                $dateRefField = $this->getRefFieldName($param['module'], $param['ruleParams']['mode']);
                $dateRef = $this->dateTimeFromMyddleware($dateRefExt[$extensionId]);
                $pageNum = 1;

                // Call RingCEntral
                do {
                    $nbRecord = 0;
                    $records = $this->makeRequest($this->server, $this->token->access_token, '/restapi'.self::API_VERSION.'/account/~/extension/'.$extensionId.'/'.$param['module'].'?dateFrom='.$dateRef.'&perPage='.$this->callLimit.'&page='.$pageNum);
                    ++$pageNum;
                    // Error managment
                    if (!empty($records->errorCode)) {
                        throw new Exception($records->errorCode.(!empty($records->message) ? ': '.$records->message : ''));
                    }

                    // Transform result by adding a dimension for the presence module (only one record for each call)
                    if ('presence' == $param['module']) {
                        $recordsObj = new \stdClass();
                        // No/date ref id in the presence module
                        $records->id = uniqid('', true).'_'.$records->extension->extensionNumber;
                        $records->$dateRefField = date('Y-m-d H:i:s');
                        $recordsObj->records = [$records];
                        $records = $recordsObj;
                    }
                    if (!empty($records->records)) {
                        $nbRecord = count($records->records);
                        // For each records
                        foreach ($records->records as $record) {
                            // For each fields expected
                            foreach ($param['fields'] as $field) {
                                // The field could be a structure from_phoneNumber for example
                                $fieldStructure = explode('__', $field);
                                // If 2 dimensions
                                if (!empty($fieldStructure[1])) {
                                    // Convert data to string
                                    $entryName = $fieldStructure[0];
                                    $subEntryName = $fieldStructure[1];

                                    // If the field is empty, Ringcentral return nothing but we need to set the field empty in Myddleware
                                    $record->$field = ($record->$entryName->$subEntryName ?? '');
                                }
                                if (isset($record->$field)) {
                                    if ($field == $dateRefField) {
                                        $dateMyddlewareFormat = $this->dateTimeToMyddleware($record->$field);
                                        // Add one second to the date modified (and so reference date) to not read the last record 2 times
                                        // Read function for Ring central allows only ">=" not ">"
                                        $date = new DateTime($dateMyddlewareFormat);
                                        $date = date_modify($date, '+1 seconde');
                                        $row['date_modified'] = $date->format('Y-m-d H:i:s');
                                    }
                                    $row[$field] = $record->$field;
                                } else {
                                    $row[$field] = '';
                                }
                            }
                            // date ref management
                            if (
                                !empty($row['date_modified'])
                                && $dateRefExt[$extensionId] <= $row['date_modified']
                            ) {
                                $dateRefExt[$extensionId] = $row['date_modified'];
                            }
                            $result['values'][$record->id] = $row;
                            ++$result['count'];
                            $row = [];
                        }
                        // Create the date_ref parameter for the extension
                        $result['ruleParams'][] = ['name' => $extensionId, 'value' => $dateRefExt[$extensionId]];
                    }
                    ++$i;
                    // Ring central allows only around 10 calls per minute
                    if (0 == $i % 10) {
                        sleep(65);
                    }
                } while ($nbRecord == $this->callLimit);
                // Limit the call around readLimit
                if ($result['count'] > $this->readLimit) {
                    break;
                }
            }
        } catch (Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }

        return $result;
    }

    public function getRefFieldName($moduleSource, $ruleMode): string
    {
        if ('call-log' == $moduleSource) {
            return 'startTime';
        } elseif ('message-store' == $moduleSource) {
            return 'lastModifiedTime';
        } elseif ('presence' == $moduleSource) {
            return 'date_modified';
        }

        return '';
    }

    // Add the filed extensionId on the rule
    public function getFieldsParamUpd($type, $module): array
    {
        $params[] = [
            'id' => 'extensionId',
            'name' => 'extensionId',
            'type' => TextType::class,
            'label' => 'Extension Id',
            'required' => false,
        ];

        return $params;
    }

    /**
     * @throws Exception
     */
    protected function dateTimeFromMyddleware(string $dateTime): string
    {
        try {
            if (empty($dateTime)) {
                throw new Exception('Date empty. Failed to send data. ');
            }
            if (date_create_from_format('Y-m-d H:i:s', $dateTime)) {
                $date = date_create_from_format('Y-m-d H:i:s', $dateTime);
            } else {
                $date = date_create_from_format('Y-m-d', $dateTime);
                if ($date) {
                    $date->setTime(0, 0, 0);
                } else {
                    throw new Exception('Wrong format for your date. Please check your date format. Contact us for help.');
                }
            }

            return $date->format('Y-m-d\TH:i:s.Z\Z');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @throws Exception
     */
    protected function dateTimeToMyddleware(string $dateTime): string
    {
        $date = new DateTime($dateTime);

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @throws Exception
     */
    public function makeRequest($server, $token, $path, $args = null, $method = 'GET', $data = null)
    {
        if (function_exists('curl_init') && function_exists('curl_setopt')) {
            // The URL to use
            $ch = curl_init($server.$path);
            // Make sure params is empty or an array
            if (!empty($args)) {
                $value = json_encode($args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $value);
            }
            // Set authorization header properly
            $authPath = '/oauth\/token/';
            if (1 !== preg_match($authPath, $path)) {
                $authHeader = 'Authorization: Bearer '.$token;
                if ('POST' == $method && 'array' !== gettype($data)) {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                    $data_string = json_encode($data);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                }
            } else {
                $authHeader = 'Authorization: Basic '.$token;
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, [$authHeader]);
            $result = curl_exec($ch);
            curl_close($ch);

            return $result ? json_decode($result) : false;
        }
        throw new Exception('curl extension is missing!');
    }
}
