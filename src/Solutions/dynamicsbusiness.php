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

/**
 * Class dynamicsbusiness
 * Handles integration with Microsoft Dynamics Business Central API
 * Manages authentication, data retrieval, and CRUD operations
 */
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

    /**
     * Returns the form fields required for login configuration
     * 
     * @return array Array of form field configurations
     */
    public function getFieldsLogin(): array
    {
        return [
            ['name' => 'tenant_id', 'type' => PasswordType::class, 'label' => 'solution.fields.tenant_id'],
            ['name' => 'client_id', 'type' => PasswordType::class, 'label' => 'solution.fields.client_id'],
            ['name' => 'client_secret', 'type' => PasswordType::class, 'label' => 'solution.fields.client_secret'],
            ['name' => 'environment', 'type' => TextType::class, 'label' => 'solution.fields.environment'],
        ];
    }

    /**
     * Authenticates with the Dynamics Business Central API
     * Retrieves and stores the access token for subsequent API calls
     * 
     * @param array $paramConnexion Connection parameters containing tenant_id, client_id, client_secret, and environment
     * @return array|bool Returns true on success, array with error on failure
     * @throws \Exception If authentication fails or token cannot be retrieved
     */
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

    /**
     * Retrieves all available modules (entities) for the connected user
     * Groups modules by company for better organization
     * 
     * @param string $type The type of modules to retrieve (source/target)
     * @return array Associative array of module names and their display names
     * @throws \Exception If modules cannot be retrieved or connection is invalid
     */
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

    /**
     * Retrieves field definitions for a specific module
     * Fetches metadata from the API to determine available fields and their properties
     * 
     * @param string $moduleKey Module identifier in format 'companyId_apiModuleName'
     * @param string $type The type of fields to retrieve (source/target)
     * @param array|null $param Additional parameters for field retrieval
     * @return array Array of field definitions with their properties
     * @throws \Exception If module key is invalid or metadata cannot be retrieved
     */
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
     * Creates a new Guzzle HTTP client instance for API calls
     * 
     * @return Client Configured Guzzle HTTP client
     */
    private function getApiClient(): Client
    {
        return new Client();
    }

    /**
     * Returns standard headers required for API requests
     * Includes authentication token and content type
     * 
     * @return array Array of HTTP headers
     */
    private function getApiHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ];
    }

    /**
     * Constructs the base URL for API calls
     * Includes tenant ID and environment configuration
     * 
     * @return string Base URL for API endpoints
     */
    private function getBaseApiUrl(): string
    {
        $tenantId = $this->paramConnexion['tenant_id'];
        $env = isset($this->paramConnexion['environment']) ? $this->paramConnexion['environment'] : 'production';
        return "https://api.businesscentral.dynamics.com/v2.0/{$tenantId}/{$env}/api/v2.0/";
    }


    /**
     * Retrieves list of available companies from the API
     * 
     * @return array Associative array of company IDs and names
     * @throws \Exception If companies cannot be retrieved
     */
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

    /**
     * Reads records from a specified module
     * Supports filtering by query parameters or date reference
     * 
     * @param array $param Parameters for the read operation including module, filters, and limits
     * @return array Array of records with their field values
     * @throws \Exception If read operation fails or parameters are invalid
     */
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
     * Creates a new record in the specified module
     * 
     * @param array $param Parameters for the create operation
     * @param array $record Data to be created
     * @param string|null $idDoc Document ID for tracking
     * @return string|array Created record ID or error array
     * @throws \Exception If creation fails
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
     * Updates an existing record in the specified module
     * Handles ETag for optimistic concurrency control
     * 
     * @param array $param Parameters for the update operation
     * @param array $data Data to be updated
     * @param string|null $idDoc Document ID for tracking
     * @return string|array Updated record ID or error array
     * @throws \Exception If update fails or ETag cannot be retrieved
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
            
            // Get ETag from response body, it is used by the PATCH request to update the record and that's how we do optimistic concurrency control
            $etag = $responseData['@odata.etag'] ?? null;
            
            if (!$etag) {
                throw new \Exception('Could not obtain ETag for the record. Response status: ' . $getResponse->getStatusCode());
            }
            
            // Add the ETag to the headers for the PATCH request
            $headers['If-Match'] = $etag;
            

            // this is where the optimisitc concurrency is happening, we assume that conflicts are rare and only check for conflicts when actually saving the data. if the header matches, the data has not been modified since the last read so we can save the data.
            $response = $client->patch($url, [
                'headers' => $headers,
                'json' => $data
            ]);

            
            // When updating, the ETag is sent in the If-Match header.
            // Example:
            // User A reads a record (gets ETag "123")
            // User B reads the same record (gets ETag "123")
            // User A updates the record (sends ETag "123" - succeeds)
            // User B tries to update the record (sends ETag "123" - fails because the record was modified)
            // This prevents User B from accidentally overwriting User A's changes. If the ETag doesn't match, the update fails.
            // The alternative (pessimistic concurrency control) would be to lock the record when User A starts reading it, preventing User B from even reading it until User A is done. This is more restrictive and can lead to performance issues in high-concurrency situations.
            
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
     * Validates required parameters for read operations
     * Ensures all necessary parameters are present and valid
     * 
     * @param array $param Parameters to validate
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

    /**
     * Retrieves list of available entities from API metadata
     * Parses XML metadata to extract entity definitions
     * 
     * This function fetches the OData metadata from Dynamics Business Central API,
     * which contains the complete schema of all available entities (tables/objects)
     * that can be accessed through the API. The metadata is in XML format and follows
     * the OData specification.
     * 
     * Process:
     * 1. Fetches metadata XML from the API
     * 2. Validates the response is not empty/error
     * 3. Parses XML using SimpleXML
     * 4. Extracts entity names using XPath queries
     * 5. Handles different XML namespace scenarios
     * 
     * @param string $companyId Company identifier (used for error messages only)
     * @return array List of available entity names
     * @throws \Exception If metadata cannot be retrieved or parsed
     */
    protected function getEntityListFromMetadata($companyId): array
    {
        try {
            // Ensure we have a valid authentication token before making API calls
            if (!$this->token) {
                throw new \Exception("Access token is not available. Please login first.");
            }

            // Initialize HTTP client and set up headers for the API request
            $client = $this->getApiClient();
            $headers = $this->getApiHeaders();

            // Get tenant and environment configuration for the API URL
            $tenantId = $this->paramConnexion['tenant_id'];
            $env = $this->paramConnexion['environment'] ?? 'production';

        // If companyId was essential for the URL, this would be an issue.
        // For this attempt, we are fetching global metadata for the tenant/environment.
        // $url = "https://api.businesscentral.dynamics.com/v2.0/{$tenantId}/{$env}/api/v2.0/companies({$companyId})/\$metadata"; // Original problematic URL
        $url = $this->getBaseApiUrl() . "\$metadata";

            // Make the API request to fetch metadata
            $response = $client->get($url, ['headers' => $headers]);
            $statusCode = $response->getStatusCode();
            $xmlString = $response->getBody()->getContents();

            // Validate response size - small responses likely indicate errors
            // OData metadata is typically large, so responses under 500 chars are suspicious
            if (strlen($xmlString) < 500) {
                throw new \Exception("Response invalid. Body (first 500 chars): " . substr($xmlString, 0, 500));
            }

            // Enable internal error handling for XML parsing
            // This allows us to capture and format XML parsing errors
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlString);

            // Handle XML parsing errors with detailed messages
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
            
            // Handle different XML namespace scenarios
            // OData metadata can use different namespace prefixes, so we need to handle both standard and custom cases
            $namespaces = $xml->getNamespaces(true);
            $xpathQuery = '';

            // Check for standard EDM namespace first
            if (isset($namespaces['edm']) && $namespaces['edm'] === 'http://docs.oasis-open.org/odata/ns/edm') {
                $xml->registerXPathNamespace('edm', 'http://docs.oasis-open.org/odata/ns/edm');
                $xpathQuery = '//edm:EntitySet';
            } 
            // Fallback to default namespace if standard not found
            elseif (isset($namespaces['']) && $namespaces[''] === 'http://docs.oasis-open.org/odata/ns/edm') {
                $xml->registerXPathNamespace('d', 'http://docs.oasis-open.org/odata/ns/edm');
                $xpathQuery = '//d:EntitySet';
            } 
            // Last resort: use local-name() to find EntitySet elements regardless of namespace
            else {
                throw new \Exception("EDM namespace not found or not standard. Trying local-name().");
            }
            
            // Execute XPath query to find all EntitySet elements
            // EntitySet elements represent available entities in the API
            if (!empty($xpathQuery)) {
                $entitySets = $xml->xpath($xpathQuery);
            } else {
                $entitySets = $xml->xpath("//*[local-name()='EntitySet']");
            }

            // Validate XPath query results
            if ($entitySets === false) {
                throw new \Exception("XPath query failed for company ID {$companyId}.");
            } else {
                // Extract entity names from EntitySet elements
                // Each EntitySet has a Name attribute that represents an available entity
                foreach ($entitySets as $entitySet) {
                    if (isset($entitySet['Name'])) {
                        $entityNames[] = (string)$entitySet['Name'];
                    }
                }
            }
            
            // Remove any duplicate entity names and return the list
            $uniqueEntityNames = array_unique($entityNames);
            return $uniqueEntityNames;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Handle HTTP request errors with detailed error messages
            // Include response body in error message if available
            $errorMessage = "[Myddleware_DynamicsBusiness] getEntityListFromMetadata: Guzzle RequestException for company ID {$companyId}. Code: " . $e->getCode() . " Message: " . $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorMessage .= " Response: " . substr($responseBody, 0, 500) . (strlen($responseBody) > 500 ? '...' : '');
            }
            throw new \Exception("Error fetching metadata from API: " . $e->getCode() . " - " . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            // Handle all other exceptions
            // Log the error and return it in a format consistent with the application's error handling
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    /**
     * Deletes a record from the specified module
     * 
     * @param array $param Parameters for the delete operation
     * @param array $data Data containing the record to delete
     * @return string|array Deleted record ID or error array
     * @throws \Exception If deletion fails
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

    /**
     * Converts Dynamics Business Central datetime format to Myddleware format
     * 
     * @param string $dateTime Datetime in Dynamics format
     * @return string Datetime in Myddleware format
     * @throws \Exception If datetime is empty or invalid
     */
    protected function dateTimeToMyddleware($dateTime)
    {
        if (empty($dateTime)) {
            throw new \Exception("Date time is empty");
        }
        $dto = new \DateTime($dateTime);
        // Return date with milliseconds
        return $dto->format('Y-m-d H:i:s');
    }

    /**
     * Converts Myddleware datetime format to Dynamics Business Central format
     * 
     * @param string $dateTime Datetime in Myddleware format
     * @return string Datetime in Dynamics format
     * @throws \Exception If datetime is empty or invalid
     */
    protected function dateTimeFromMyddleware($dateTime)
    {
        if (empty($dateTime)) {
            throw new \Exception("Date time is empty");
        }

        $dto = new \DateTime($dateTime);
        // Return date with milliseconds
        return $dto->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Returns the field name used for reference tracking
     * 
     * @param array $param Parameters for the operation
     * @return string Field name used for reference tracking
     */
    public function getRefFieldName($param)
    {
        return "lastModifiedDateTime";
    }

}
?>
