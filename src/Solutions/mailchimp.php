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

class mailchimp extends solution
{
    protected string $apiEndpoint = 'https://<dc>.api.mailchimp.com/3.0/';
    protected $apiKey;
    protected bool $verify_ssl = true;
    protected bool $update = false;
    const TIMEOUT = 60;

    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'apikey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.apikey',
            ],
        ];
    }

    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            // Get the api key
            $this->apiKey = $this->paramConnexion['apikey'];
            // Api key has to cointain "-"
            if (false === strpos($this->apiKey, '-')) {
                throw new \Exception('Invalid MailChimp API key supplied.');
            }
            // Add the dc in the endpoint
            [, $data_center] = explode('-', $this->apiKey);
            $this->apiEndpoint = str_replace('<dc>', $data_center, $this->apiEndpoint);
            // Call the root function to check the API
            $result = $this->call($this->apiEndpoint);
            if (empty($result['account_id'])) {
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

    // Renvoie les modules passés en paramètre
    public function get_modules($type = 'source')
    {
        try {
            if ('target' == $type) {
                $modules = [
                    'campaigns' => 'Campaigns',
                    'lists' => 'Lists',
                    'members' => 'Members',
                ];
            } else {
                return [];
            }

            return $modules;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Renvoie les champs du module passé en paramètre
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            require 'lib/mailchimp/metadata.php';

            if (!empty($moduleFields[$module])) {
                $this->moduleFields = array_merge($this->moduleFields, $moduleFields[$module]);
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function createUpdate($method, $param)
    {
        // Get module fields to check if the fiels is a boolean
        $this->get_module_fields($param['module'], 'target');

        // Tranform Myddleware data to Mailchimp data
        foreach ($param['data'] as $idDoc => $data) {
            try {
                // Check control before create
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                $dataMailchimp = [];

                foreach ($data as $key => $value) {
                    // We jump the filed target_id for creation
                    if ('target_id' == $key) {
                        continue;
                    }
                    // Transform data, for example for the type boolean : from 1 to true and from 0 to false
                    $value = $this->transformValueType($key, $value);

                    // Formattage des données à envoyer
                    $filedStructure = explode('__', $key);
                    if (!empty($filedStructure[1])) {
                        $dataMailchimp[$filedStructure[0]][$filedStructure[1]] = $value;
                    } elseif (!empty($filedStructure[0])) {
                        $dataMailchimp[$filedStructure[0]] = $value;
                    } else {
                        throw new \Exception('Field '.$filedStructure.' invalid');
                    }
                }

                // Send data to Mailchimp
                $urlParam = $this->createUrlParam($param, $data, $method);
                $resultMailchimp = $this->call($this->apiEndpoint.$urlParam, $method, $dataMailchimp);

                // Error management
                if (
                        !empty($resultMailchimp['status'])
                    && $resultMailchimp['status'] >= 400
                ) {
                    $errorMsg = '';
                    if (!empty($resultMailchimp['errors'])) {
                        foreach ($resultMailchimp['errors']  as $error) {
                            $errorMsg .= print_r($error, true).' ';
                        }
                    }
                    throw new \Exception((!empty($resultMailchimp['title']) ? $resultMailchimp['title'] : 'Error').' ('.$resultMailchimp['status'].'): '.(!empty($resultMailchimp['detail']) ? $resultMailchimp['detail'] : '').(!empty($errorMsg) ? ' => '.$errorMsg : ''));
                }
                // Save Mailchimp record ID to Myddleware
                if (!empty($resultMailchimp['id'])) {
                    $result[$idDoc] = [
                        'id' => $resultMailchimp['id'],
                        'error' => false,
                    ];
                } else {
                    throw new \Exception("Error webservice. There is no ID in the result of the function $param[module]. ");
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
            // Change the transfer status in Myddleware
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function createData($param): array
    {
        return $this->createUpdate('POST', $param);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateData($param): array
    {
        return $this->createUpdate('PATCH', $param);
    }

    // Transform data, for example for the type boolean : from 1 to true and from 0 to false
    protected function transformValueType($key, $value): bool
    {
        if (
                !empty($this->moduleFields[$key]['type'])
            && 'bool' == $this->moduleFields[$key]['type']
        ) {
            if (!empty($value)) {
                return true;
            }

            return false;
        }

        return $value;
    }

    // Create the url parameters depending the module

    /**
     * @throws \Exception
     */
    protected function createUrlParam($param, $data, $method): string
    {
        $urlParam = '';
        // Manage parameters for list
        if ('members' == $param['module']) {
            if (empty($data['list_id'])) {
                throw new \Exception('No list id in the data transfer. Failed to create or update member.');
            }

            $urlParam = 'lists/'.$data['list_id'].'/'.$param['module'];
        } else {
            $urlParam = $param['module'];
        }
        // Manage update param
        if ('PATCH' == $method) {
            if (empty($data['target_id'])) {
                throw new \Exception('No record ID in the data. Failed to update the record.');
            }
            $urlParam .= '/'.$data['target_id'];
        }

        return $urlParam;
    }

    /**
     * Performs the underlying HTTP request. Not very exciting.
     *
     * @param string $method The API method to be called
     * @param array $args Assoc array of parameters to be passed
     * @param mixed $url
     * @param mixed $timeout
     *
     * @return array Assoc array of decoded result
     * @throws \Exception
     */
    protected function call($url, $method = 'GET', $args = [], $timeout = self::TIMEOUT)
    {
        if (function_exists('curl_init') && function_exists('curl_setopt')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            $httpHeader = [
                'Accept: application/vnd.api+json',
                'Content-Type: application/vnd.api+json',
                'Authorization: apikey '.$this->apiKey,
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

            if (!empty($args)) {
                $jsonData = json_encode($args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);

            return $result ? json_decode($result, true) : false;
        }
        throw new \Exception('curl extension is missing!');
    }
}
