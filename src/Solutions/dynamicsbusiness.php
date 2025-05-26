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

use GuzzleHttp\Client;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class dynamicsbusiness extends solution
{
    protected $token;
    public bool $connexion_valide = false;
    protected array $moduleFields = [];

    protected bool $readDeletion = true;
    protected bool $sendDeletion = true;

    public string $parentModule = 'companies';
    public string $parentModuleId = '';

    public array $modules = [];
    public array $resultSaveIds = [];

    protected array $requiredFields = [
        ['id', 'lastModifiedDateTime']
    ];

    public function getFieldsLogin(): array
    {
        return [
            ['name' => 'tenant_id', 'type' => PasswordType::class, 'label' => 'solution.fields.tenant_id'],
            ['name' => 'client_id', 'type' => PasswordType::class, 'label' => 'solution.fields.client_id'],
            ['name' => 'client_secret', 'type' => PasswordType::class, 'label' => 'solution.fields.client_secret'],
            ['name' => 'environment', 'type' => TextType::class, 'label' => 'solution.fields.environment'],
        ];
    }

    public function login($paramConnexion)
    {
        parent::login($paramConnexion);

        try {

            $tenantId = $this->paramConnexion['tenant_id'];
            $clientId = $this->paramConnexion['client_id'];
            $clientSecret = $this->paramConnexion['client_secret'];

        $scope = 'https://api.businesscentral.dynamics.com/.default';
        $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";

        $client = $this->getApiClient();
        $response = $client->post($tokenUrl, [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => $scope,
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        if (isset($data['access_token'])) {
            $this->token = $data['access_token'];
            $this->connexion_valide = true;
        } else {
                throw new \Exception("Unable to retrieve access token");
            }
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    // Permet de récupérer tous les modules accessibles à l'utilisateur
    public function get_modules($type = 'source')
    {
        try {

            $result = [];
            $resultSaveIds = [];

        $companies = $this->getCompanies();
        if (empty($companies)) {
            throw new \Exception("Could not retrieve companies");
        }

        if (!$this->connexion_valide) {
            throw new \Exception("Connection not validated. Please ensure login parameters are correct and login() was successful.");
        }

        foreach ($companies as $companyId => $companyName) {
                $entityList = $this->getEntityListFromMetadata($companyId);
                if (empty($entityList)) {
                    throw new \Exception("No entities found for company {$companyName}");
                }

                foreach ($entityList as $moduleName) {
                    $displayModuleName = ucfirst($moduleName); 
                    
                    $displayString = "Company: {$companyName} _ Module: {$displayModuleName}";
                    $key = "{$companyId}_{$moduleName}";

                    $result[$key] = $displayString;
                    $resultSaveIds[$key] = $displayString; 
                }
        }

        if (count($result) < 10 && count($result) > 0) { // Log small results for review
            $this->logger->warning("Not enough modules found for company {$companyName}. Found " . count($result) . " modules.");
            $this->logger->debug("Module list for company {$companyName}:", ['modules' => $result]);
            throw new \Exception("Not enough modules found for company {$companyName}. Please check the logs for details.");
        }

        $this->modules = $result;
        $this->resultSaveIds = $resultSaveIds;
        return $result;

        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    public function get_module_fields($moduleKey, $type = 'source', $param = null): array
    {
        parent::get_module_fields($moduleKey, $type);
        try {
            if (strpos($moduleKey, '_') === false) {
                throw new \Exception("Module key '{$moduleKey}' is not in the expected 'companyId_apiModuleName' format.");
            }

            $tenantId = $this->paramConnexion['tenant_id'];
            $environment = $this->paramConnexion['environment'];
    
            list($companyId, $apiModuleName) = explode('_', $moduleKey, 2);
    
            $this->parentModuleId = $companyId;
    
            if (!$this->token) {
                throw new \Exception("Unable to retrieve access token.");
            }
    
            $url = $this->getBaseApiUrl() . "\$metadata";

            $client = $this->getApiClient();
            $headers = [
                'Authorization' => "Bearer {$this->token}",
                'Accept' => 'application/xml',
            ];
            $response = $client->get($url, ['headers' => $headers]);
            $responseBody = $response->getBody()->getContents();
    
            $xml = simplexml_load_string($responseBody);

            // register the namespaces in order to be able to use xpath so we can dynamically get the entity type
            $xml->registerXPathNamespace('edmx', 'http://docs.oasis-open.org/odata/ns/edmx');
            $xml->registerXPathNamespace('edm', 'http://docs.oasis-open.org/odata/ns/edm');

            $singularName = rtrim($apiModuleName, 's'); // crude but works for most core entities

            $entityTypes = $xml->xpath("/edmx:Edmx/edmx:DataServices/edm:Schema/edm:EntityType[@Name='{$singularName}']");
    
            if (!$entityTypes || empty($entityTypes[0])) {
                $this->logger->warning("No entity type found for module: {$singularName}");
                return [];
            }
    
            $fields = [];

            $entityTypes[0]->registerXPathNamespace('edm', 'http://docs.oasis-open.org/odata/ns/edm');

            foreach ($entityTypes[0]->xpath("edm:Property") as $property) {
                $name = (string) $property['Name'];
                $nullable = (string) $property['Nullable'];
            
                $fields[$name] = [
                    'label' => $name,
                    'type' => 'varchar(255)',
                    'type_bdd' => 'varchar(255)',
                    'required' => ($nullable === 'false') ? 1 : 0
                ];
            }
    
            return $fields;
    
        } catch (\Exception $e) {
            $error = $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }
    

    /**
     * Returns a Guzzle HTTP client instance
     */
    private function getApiClient(): Client
    {
        return new Client();
    }

    /**
     * Returns the standard headers for API requests
     */
    private function getApiHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ];
    }

    /**
     * Returns the base URL for API calls
     */
    private function getBaseApiUrl(): string
    {
        $tenantId = $this->paramConnexion['tenant_id'];
        $env = isset($this->paramConnexion['environment']) ? $this->paramConnexion['environment'] : 'production';
        return "https://api.businesscentral.dynamics.com/v2.0/{$tenantId}/{$env}/api/v2.0/";
    }


    public function getCompanies()
    {
        try {

            $client = $this->getApiClient();
            $headers = $this->getApiHeaders();

        $url = $this->getBaseApiUrl() . "companies";

        $response = $client->get($url, ['headers' => $headers]);
        $data = json_decode($response->getBody(), true);

        foreach ($data['value'] as $company) {
            $result[$company['id']] = $company['name'];
        }

            return $result;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    public function read($param): array
    {

        if(!empty($param['ruleParams']['limit'])) {
            $param['limit'] = $param['ruleParams']['limit'];
        }

        $client = $this->getApiClient();
        $headers = $this->getApiHeaders();

        $this->validateReadParameters($param);

        $module = $param['module'];
        list($companyId, $module) = explode('_', $module, 2);

        $parentmodule = $this->parentModule;
        $parentmoduleId = $param['ruleParams']['parentmoduleid'];

        $filterValue = '';

        // just like hubspot, we do an if not empty query else if not empty date_ref else all
        if (!empty($param['query'])) {
            foreach ($param['query'] as $key => $value) {
                $filterValue .= "{$key} eq {$value} and ";
            }

            // trim the last and
            $filterValue = rtrim($filterValue, ' and ');

        } else if (!empty($param['date_ref'])) {
            $dateRef = $this->dateTimeFromMyddleware($param['date_ref']);
            $filterValue = urlencode("lastModifiedDateTime gt {$dateRef}");
        } else {
            $dateRef = $this->dateTimeFromMyddleware($param['ruleParams']['datereference']);
            $filterValue = urlencode("lastModifiedDateTime gt {$dateRef}");
        }

        $url = $this->getBaseApiUrl() . "{$parentmodule}({$parentmoduleId})/{$module}?%24filter={$filterValue}";

        try {
            $response = $client->get($url, ['headers' => $headers]);
            $data = json_decode($response->getBody(), true);

            if (!isset($data['value']) || !is_array($data['value'])) {
                $this->logger->error("Invalid response format: missing or invalid 'value' array");
                return [];
            }

            $results = [];
            $nbRecords = 0;

            foreach ($data['value'] as $record) {
                // Stop the process if limit has been reached
                if (isset($param['limit']) && $nbRecords >= $param['limit']) {
                    break;
                }

                $result = [];

                // Dynamically map fields based on $param['fields']
                if (isset($param['fields']) && is_array($param['fields'])) {
                    foreach ($param['fields'] as $field) {
                        // Check if the field exists in the record
                        if (isset($record[$field])) {
                            $result[$field] = $record[$field];
                        } else {
                            // If field doesn't exist, set a default value or null
                            $result[$field] = null;
                        }
                    }

                    // Add date_modified from the record's lastModifiedDateTime
                    if (!empty($record['lastModifiedDateTime'])) {
                        $result['date_modified'] = $this->dateTimeToMyddleware($record['lastModifiedDateTime']);
                    }
                } else {
                    // Fallback to default fields if $param['fields'] is not set
                    $result['id'] = $record['id'] ?? null;
                    $result['displayName'] = $record['displayName'] ?? null;
                }

                if (!isset($result['id']) && !empty($record['id'])) {
                    $result['id'] = $record['id'];
                }

                $results[] = $result;
                $nbRecords++;
            }

            // put the actual data in a sub array called value
            $resultFinal = [];

            foreach($results as $value)
            {
                $recordId = $value['id'];
                $resultFinal[$recordId] = $value;
            }

            return $resultFinal;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    /**
     * @throws Exception
     */
    public function create($param, $record, $idDoc = null) {

    try {

        $client = $this->getApiClient();
        $headers = $this->getApiHeaders();

        $module = $param['module'];
        if (strpos($module, '_') !== false) {
            list($companyId, $module) = explode('_', $module, 2);
        }
        
        $parentmodule = $this->parentModule;
        $parentmoduleId = $param['ruleParams']['parentmoduleid'];
        
        $url = $this->getBaseApiUrl() . "{$parentmodule}({$parentmoduleId})/{$module}";
        
            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $record
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            if (!isset($data['id'])) {
                throw new \Exception('No ID returned from API response');
            }
            
            return $data['id'];
            
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    /**
     * @throws \Exception
     */
    protected function update($param, $data, $idDoc = null)
    {
    try {

        $client = $this->getApiClient();
        $headers = $this->getApiHeaders();

        $module = $param['module'];
        if (strpos($module, '_') !== false) {
            list($companyId, $module) = explode('_', $module, 2);
        }
        
        $parentmodule = $this->parentModule;
        $parentmoduleId = $param['ruleParams']['parentmoduleid'];
        $targetId = $data['target_id'];
        
        // Remove target_id from the data as it's not a valid field for the API
        unset($data['target_id']);
        
        $url = $this->getBaseApiUrl() . "{$parentmodule}({$parentmoduleId})/{$module}({$targetId})";
        
            // First get the current record to obtain its ETag
            $getResponse = $client->get($url, ['headers' => $headers]);
            
            // Get response body content once
            $responseBody = $getResponse->getBody()->getContents();
            $responseData = json_decode($responseBody, true);
            
            // Get ETag from response body
            $etag = $responseData['@odata.etag'] ?? null;
            
            if (!$etag) {
                throw new \Exception('Could not obtain ETag for the record. Response status: ' . $getResponse->getStatusCode());
            }
            
            // Add the ETag to the headers for the PATCH request
            $headers['If-Match'] = $etag;
            
            $response = $client->patch($url, [
                'headers' => $headers,
                'json' => $data
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            if (!isset($data['id'])) {
                throw new \Exception('No ID returned from API response');
            }
            
            return $data['id'];
            
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    /**
     * Validates the required parameters for the read method
     * 
     * @param array $param The parameters to validate
     * @throws \Exception If any required parameter is missing
     */
    private function validateReadParameters($param)
    {
        $requiredParams = [
            'tenant_id' => $this->paramConnexion['tenant_id'] ?? null,
            'parentmodule' => $this->parentModule ?? null,
            'parentmoduleId' => $param['ruleParams']['parentmoduleid'] ?? null,
            'module' => $param['module'] ?? null
        ];

        $missingParams = [];
        foreach ($requiredParams as $paramName => $value) {
            if (empty($value)) {
                $missingParams[] = $paramName;
            }
        }

        if (!empty($missingParams)) {
            throw new \Exception("Missing required parameters: " . implode(', ', $missingParams));
        }
    }

    protected function getEntityListFromMetadata($companyId): array
    {

    try {

        if (!$this->token) {
            throw new \Exception("Access token is not available. Please login first.");
        }

        $client = $this->getApiClient();
        $headers = $this->getApiHeaders();

        $tenantId = $this->paramConnexion['tenant_id'];
        $env = $this->paramConnexion['environment'] ?? 'production';

        // If companyId was essential for the URL, this would be an issue.
        // For this attempt, we are fetching global metadata for the tenant/environment.
        // $url = "https://api.businesscentral.dynamics.com/v2.0/{$tenantId}/{$env}/api/v2.0/companies({$companyId})/\$metadata"; // Original problematic URL
        $url = $this->getBaseApiUrl() . "\$metadata";

            $response = $client->get($url, ['headers' => $headers]);
            $statusCode = $response->getStatusCode();
            $xmlString = $response->getBody()->getContents();
            if (strlen($xmlString) < 500) { // Log small responses, they might be errors or empty
                throw new \Exception("Response invalid. Body (first 500 chars): " . substr($xmlString, 0, 500));
            }

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlString);

            if ($xml === false) {
                $errors = libxml_get_errors();
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = trim($error->message) . " (Line: {$error->line}, Column: {$error->column})";
                }
                libxml_clear_errors();
                $flatErrors = implode("; ", $errorMessages);
                throw new \Exception("Failed to parse metadata XML for company ID {$companyId}: " . $flatErrors);
            }

            $entityNames = [];
            
            $namespaces = $xml->getNamespaces(true);
            $xpathQuery = '';

            if (isset($namespaces['edm']) && $namespaces['edm'] === 'http://docs.oasis-open.org/odata/ns/edm') {
                 $xml->registerXPathNamespace('edm', 'http://docs.oasis-open.org/odata/ns/edm');
                 $xpathQuery = '//edm:EntitySet';
            } elseif (isset($namespaces['']) && $namespaces[''] === 'http://docs.oasis-open.org/odata/ns/edm') {
                 $xml->registerXPathNamespace('d', 'http://docs.oasis-open.org/odata/ns/edm');
                 $xpathQuery = '//d:EntitySet';
            } else {
                 throw new \Exception("EDM namespace not found or not standard. Trying local-name().");
            }
            
            if (!empty($xpathQuery)) {
                $entitySets = $xml->xpath($xpathQuery);
            } else {
                $entitySets = $xml->xpath("//*[local-name()='EntitySet']");
            }

            if ($entitySets === false) {
                 throw new \Exception("XPath query failed for company ID {$companyId}.");
            } else {
                 foreach ($entitySets as $entitySet) {
                    if (isset($entitySet['Name'])) {
                        $entityNames[] = (string)$entitySet['Name'];
                    }
                }
            }
            
            $uniqueEntityNames = array_unique($entityNames);
            return $uniqueEntityNames;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorMessage = "[Myddleware_DynamicsBusiness] getEntityListFromMetadata: Guzzle RequestException for company ID {$companyId}. Code: " . $e->getCode() . " Message: " . $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorMessage .= " Response: " . substr($responseBody, 0, 500) . (strlen($responseBody) > 500 ? '...' : '');
            }
            throw new \Exception("Error fetching metadata from API: " . $e->getCode() . " - " . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    /**
     * @throws \Exception
     */
    protected function delete($param, $data)
    {

    try {

        $client = $this->getApiClient();
        $headers = $this->getApiHeaders();
        
        $parentmodule = $this->parentModule;
        $parentmoduleId = $param['ruleParams']['parentmoduleid'];
        $targetId = $data['target_id'];

        $module = $param['module'];
        if (strpos($module, '_') !== false) {
            list($companyId, $module) = explode('_', $module, 2);
        }
        
        $url = $this->getBaseApiUrl() . "{$parentmodule}({$parentmoduleId})/{$module}({$targetId})";
        
            $response = $client->delete($url, [
                'headers' => $headers
            ]);
            
            if ($response->getStatusCode() === 204) { // 204 No Content is the standard response for successful deletion
                return $targetId;
            } else {
                throw new \Exception('Unexpected response status code: ' . $response->getStatusCode());
            }
            
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    // Function de conversion de datetime format solution à un datetime format Myddleware
    protected function dateTimeToMyddleware($dateTime)
    {
        if (empty($dateTime)) {
            throw new \Exception("Date time is empty");
        }
        $dto = new \DateTime($dateTime);
        // Return date with milliseconds
        return $dto->format('Y-m-d H:i:s');
    }

    // Function de conversion de datetime format Myddleware à un datetime format solution
    protected function dateTimeFromMyddleware($dateTime)
    {
        if (empty($dateTime)) {
            throw new \Exception("Date time is empty");
        }

        $dto = new \DateTime($dateTime);
        // Return date with milliseconds
        return $dto->format('Y-m-d\TH:i:s\Z');
    }

    public function getRefFieldName($param)
    {
        return "lastModifiedDateTime";
    }

}
?>
