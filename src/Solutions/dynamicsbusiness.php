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

    public function login($paramConnexion)
    {
        parent::login($paramConnexion);

        $tenantId = $this->paramConnexion['tenant_id'];
        $clientId = $this->paramConnexion['client_id'];
        $clientSecret = $this->paramConnexion['client_secret'];

        $scope = 'https://api.businesscentral.dynamics.com/.default';
        $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";

        $client = new Client();
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
    }

    public function getFieldsLogin(): array
    {
        return [
            ['name' => 'tenant_id', 'type' => PasswordType::class, 'label' => 'solution.fields.tenant_id'],
            ['name' => 'client_id', 'type' => PasswordType::class, 'label' => 'solution.fields.client_id'],
            ['name' => 'client_secret', 'type' => PasswordType::class, 'label' => 'solution.fields.client_secret'],
            ['name' => 'environment', 'type' => TextType::class, 'label' => 'solution.fields.environment'],
        ];
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
    
            $_SESSION['parentmodule'] = "companies";
            $_SESSION['parentmoduleid'] = $companyId;
    
            $accessToken = $this->token;
            if (!$accessToken) {
                throw new \Exception("Unable to retrieve access token.");
            }
    
            $url = "https://api.businesscentral.dynamics.com/v2.0/{$tenantId}/{$environment}/api/v2.0/\$metadata";

    
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer {$accessToken}",
                "Accept: application/xml"
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new \Exception('Curl error: ' . curl_error($ch));
            }
            curl_close($ch);
    
            $xml = simplexml_load_string($response);

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
                $edmType = (string) $property['Type'];
                $nullable = (string) $property['Nullable'];
            
                // Map Edm types to SQL types (very basic mapping)
                switch ($edmType) {
                    case 'Edm.Guid':
                    case 'Edm.String':
                        $type = 'varchar(255)';
                        break;
                    case 'Edm.Int32':
                    case 'Edm.Int64':
                        $type = 'int(11)';
                        break;
                    case 'Edm.Boolean':
                        $type = 'tinyint(1)';
                        break;
                    case 'Edm.Decimal':
                    case 'Edm.Double':
                        $type = 'decimal(18,2)';
                        break;
                    case 'Edm.DateTimeOffset':
                        $type = 'datetime';
                        break;
                    default:
                        $type = 'varchar(255)'; // fallback for unknown or complex types
                        break;
                }
            
                $fields[$name] = [
                    'label' => $name,
                    'type' => $type,
                    'type_bdd' => $type,
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

    public function getOneCustomer($companyId, $customerId)
    {
        $client = $this->getApiClient();
        $headers = $this->getApiHeaders();

        $url = $this->getBaseApiUrl() . "companies({$companyId})/customers({$customerId})";

        $response = $client->get($url, ['headers' => $headers]);
        $data = json_decode($response->getBody(), true);

        return $data;
    }

    // Permet de récupérer tous les modules accessibles à l'utilisateur
    public function get_modules($type = 'source')
    {
        error_log("[Myddleware_DynamicsBusiness] get_modules: Called with type: {$type}");

        $result = [];
        $resultSaveIds = [];

        $companies = $this->getCompanies();
        error_log("[Myddleware_DynamicsBusiness] get_modules: getCompanies() returned: " . count($companies) . " companies.");
        if (empty($companies)) {
            error_log("[Myddleware_DynamicsBusiness] get_modules: No companies found. Returning empty result.");
        }

        if (!$this->connexion_valide) {
            error_log("[Myddleware_DynamicsBusiness] get_modules: Connection not validated. login() might have failed or not been called.");
            throw new \Exception("Connection not validated. Please ensure login parameters are correct and login() was successful.");
        }

        foreach ($companies as $companyId => $companyName) {
            error_log("[Myddleware_DynamicsBusiness] get_modules: Processing company ID: {$companyId}, Name: {$companyName}");
            try {
                $entityList = $this->getEntityListFromMetadata($companyId);
                error_log("[Myddleware_DynamicsBusiness] get_modules: getEntityListFromMetadata for company ID {$companyId} returned: " . count($entityList) . " entities.");
                if (count($entityList) > 0) {
                     error_log("[Myddleware_DynamicsBusiness] get_modules: Entities for company ID {$companyId}: " . implode(", ", $entityList));
                }

                foreach ($entityList as $moduleName) {
                    error_log("[Myddleware_DynamicsBusiness] get_modules: Adding module '{$moduleName}' for company '{$companyName}'.");
                    $displayModuleName = ucfirst($moduleName); 
                    
                    $displayString = "Company: {$companyName} _ Module: {$displayModuleName}";
                    $key = "{$companyId}_{$moduleName}";

                    $result[$key] = $displayString;
                    $resultSaveIds[$key] = $displayString; 
                }
            } catch (\Exception $e) {
                $errorMessage = "[Myddleware_DynamicsBusiness] get_modules: Failed to get modules for company '{$companyName}' (ID: {$companyId}). Exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine();
                error_log($errorMessage);
                $this->logger->error($errorMessage);
            }
        }

        error_log("[Myddleware_DynamicsBusiness] get_modules: Final result count: " . count($result));
        if (count($result) < 10 && count($result) > 0) { // Log small results for review
            error_log("[Myddleware_DynamicsBusiness] get_modules: Final result content: " . print_r($result, true));
        }

        $_SESSION['modules'] = $result;
        $_SESSION['resultSaveIds'] = $resultSaveIds;
        error_log("[Myddleware_DynamicsBusiness] get_modules: Modules saved to session.");
        return $result;

    }

    public function getCustomers($companyId)
    {
        $client = $this->getApiClient();
        $headers = $this->getApiHeaders();

        $url = $this->getBaseApiUrl() . "companies({$companyId})/customers";

        $response = $client->get($url, ['headers' => $headers]);
        $data = json_decode($response->getBody(), true);

        $result = [];
        foreach ($data['value'] as $customer) {
            $result[$customer['id']] = $customer['displayName'];
        }

        return $result;
    }

    public function getCompanies()
    {
        $client = $this->getApiClient();
        $headers = $this->getApiHeaders();

        $url = $this->getBaseApiUrl() . "companies";

        $response = $client->get($url, ['headers' => $headers]);
        $data = json_decode($response->getBody(), true);

        foreach ($data['value'] as $company) {
            $result[$company['id']] = $company['name'];
        }

        return $result;
    }

    public function readData($param): array
    {
        $client = $this->getApiClient();
        $headers = $this->getApiHeaders();

        $this->validateReadParameters($param);

        $module = $param['module'];
        list($companyId, $module) = explode('_', $module, 2);

        $parentmodule = $param['ruleParams']['parentmodule'];
        $parentmoduleId = $param['ruleParams']['parentmoduleid'];

        $url = $this->getBaseApiUrl() . "{$parentmodule}({$parentmoduleId})/{$module}";

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
                        $result['date_modified'] = date('Y-m-d H:i:s', strtotime($record['lastModifiedDateTime']));
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

            $countResult = count($results);

            // put the actual data in a sub array called value
            $resultFinal = [];
            $resultFinal['values'] = $results;

            // put the date modified of the LAST element to set the date_ref
            if ($countResult > 0) {
                $resultFinal['date_ref'] = date('Y-m-d H:i:s', strtotime($results[$countResult - 1]['date_modified']));
            }

            // for the simulation, get the count of results
            $resultFinal['count'] = $countResult;

            return $resultFinal;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return [];
        }
    }

    public function read($param)
    {
        $client = $this->getApiClient();
        $headers = $this->getApiHeaders();

        $this->validateReadParameters($param);

        $tenantId = $this->paramConnexion['tenant_id'];
        $env = isset($this->paramConnexion['environment']) ? $this->paramConnexion['environment'] : 'production';

        $module = $param['module'];

        list($companyId, $module) = explode('_', $module, 2);

        $parentmodule = $param['ruleParams']['parentmodule'];
        $parentmoduleId = $param['ruleParams']['parentmoduleid'];


            $url = "https://api.businesscentral.dynamics.com/v2.0/{$tenantId}/{$env}/api/v2.0/{$parentmodule}({$parentmoduleId})/{$module}";

            if (isset($param['query']['id'])) {
                $url .= "({$param['query']['id']})";
            }

            try {
                $response = $client->get($url, ['headers' => $headers]);
                $data = json_decode($response->getBody(), true);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                return [];
            }

        $results = [];
        $result = [];
        $nbRecords = 0;

            try {
            
            // Dynamically map fields based on $param['fields']
            if (isset($param['fields']) && is_array($param['fields'])) {
                foreach ($param['fields'] as $field) {
                    // Check if the field exists in the data
                    if (isset($data[$field])) {
                        $result[$field] = $data[$field];
                    } else {
                        // If field doesn't exist, set a default value or null
                        $result[$field] = null;
                    }
                }

                // convert "lastModifiedDateTime": "2025-03-24T02:05:51Z" to a string in this format: 2025-04-02 09:28:27
                $result['date_modified'] = date('Y-m-d H:i:s', strtotime($data['lastModifiedDateTime']));
            } else {
                // Fallback to default fields if $param['fields'] is not set
                $result['id'] = $data['id'] ?? null;
                    $result['displayName'] = $data['displayName'] ?? null;
                }

            if (!isset($result['id']) && !empty($data['id']) && !empty($param['query']['id'])) {
                $result['id'] = $data['id'];
            }
            
            $results[] = $result;
            $nbRecords++;

            // Check if we've reached the limit
            if (isset($param['limit']) && $nbRecords >= $param['limit']) {
                return array_slice($results, 0, $param['limit']);
            }

            return $results;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return [];
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
            'parentmodule' => $param['ruleParams']['parentmodule'] ?? null,
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
        error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: Called for company ID: " . $companyId . " - Note: Company ID is not used in the metadata URL in this version, fetching global service metadata.");

        if (!$this->token) {
            error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: Access token is not available.");
            throw new \Exception("Access token is not available. Please login first.");
        }

        $client = $this->getApiClient();
        $headers = $this->getApiHeaders();

        $tenantId = $this->paramConnexion['tenant_id'];
        $env = $this->paramConnexion['environment'] ?? 'production';

        // If companyId was essential for the URL, this would be an issue.
        // For this attempt, we are fetching global metadata for the tenant/environment.
        // $url = "https://api.businesscentral.dynamics.com/v2.0/{$tenantId}/{$env}/api/v2.0/companies({$companyId})/\$metadata"; // Original problematic URL
        $url = "https://api.businesscentral.dynamics.com/v2.0/{$tenantId}/{$env}/api/v2.0/\$metadata";
        error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: Requesting global service metadata URL: " . $url);

        try {
            $response = $client->get($url, ['headers' => $headers]);
            $statusCode = $response->getStatusCode();
            $xmlString = $response->getBody()->getContents();
            error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: API call successful. Status: {$statusCode}. Response size: " . strlen($xmlString) . " bytes.");
            if (strlen($xmlString) < 500) { // Log small responses, they might be errors or empty
                error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: Response body (first 500 chars): " . substr($xmlString, 0, 500));
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
                error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: Failed to parse metadata XML for company ID {$companyId}. Errors: " . $flatErrors);
                throw new \Exception("Failed to parse metadata XML for company ID {$companyId}: " . $flatErrors);
            }
            error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: XML parsed successfully for company ID {$companyId}.");

            $entityNames = [];
            
            $namespaces = $xml->getNamespaces(true);
            error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: XML Namespaces: " . print_r($namespaces, true));
            $xpathQuery = '';

            if (isset($namespaces['edm']) && $namespaces['edm'] === 'http://docs.oasis-open.org/odata/ns/edm') {
                 $xml->registerXPathNamespace('edm', 'http://docs.oasis-open.org/odata/ns/edm');
                 $xpathQuery = '//edm:EntitySet';
                 error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: Using XPath with 'edm' namespace.");
            } elseif (isset($namespaces['']) && $namespaces[''] === 'http://docs.oasis-open.org/odata/ns/edm') {
                 $xml->registerXPathNamespace('d', 'http://docs.oasis-open.org/odata/ns/edm');
                 $xpathQuery = '//d:EntitySet';
                 error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: Using XPath with default 'd' namespace for EDM.");
            } else {
                 error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: EDM namespace not found or not standard. Trying local-name().");
            }
            
            if (!empty($xpathQuery)) {
                $entitySets = $xml->xpath($xpathQuery);
            } else {
                $entitySets = $xml->xpath("//*[local-name()='EntitySet']");
                error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: Executed XPath with local-name(): " . count($entitySets) . " potential EntitySet nodes found.");
            }

            if ($entitySets === false) {
                 error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: XPath query failed for company ID {$companyId}.");
            } else {
                 error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: XPath query resulted in " . count($entitySets) . " EntitySet nodes for company ID {$companyId}.");
                 foreach ($entitySets as $entitySet) {
                    if (isset($entitySet['Name'])) {
                        $entityNames[] = (string)$entitySet['Name'];
                    }
                }
            }
            
            $uniqueEntityNames = array_unique($entityNames);
            error_log("[Myddleware_DynamicsBusiness] getEntityListFromMetadata: Found " . count($uniqueEntityNames) . " unique entity names for company ID {$companyId}: " . implode(", ", $uniqueEntityNames));
            return $uniqueEntityNames;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorMessage = "[Myddleware_DynamicsBusiness] getEntityListFromMetadata: Guzzle RequestException for company ID {$companyId}. Code: " . $e->getCode() . " Message: " . $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorMessage .= " Response: " . substr($responseBody, 0, 500) . (strlen($responseBody) > 500 ? '...' : '');
            }
            error_log($errorMessage);
            $this->logger->error($errorMessage); // Assuming $this->logger also uses error_log or similar
            throw new \Exception("Error fetching metadata from API: " . $e->getCode() . " - " . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            $errorMessage = "[Myddleware_DynamicsBusiness] getEntityListFromMetadata: General Exception for company ID {$companyId}. Message: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine();
            error_log($errorMessage);
            $this->logger->error($errorMessage);
            throw $e; 
        }
    }
}
?>
