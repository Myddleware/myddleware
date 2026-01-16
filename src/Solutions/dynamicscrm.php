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
 * Class dynamicscrm
 * Handles integration with Microsoft Dynamics 365 CRM / Dataverse API
 * Manages authentication, data retrieval, and CRUD operations
 */
class dynamicscrm extends solution
{
    protected $token;
    public bool $connexion_valide = false;
    protected array $moduleFields = [];

    protected bool $readDeletion = true;
    protected bool $sendDeletion = true;

    public array $modules = [];
    public array $resultSaveIds = [];

    protected array $requiredFields = [
        ['id', 'modifiedon']
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
            ['name' => 'org_url', 'type' => TextType::class, 'label' => 'solution.fields.org_url'],
        ];
    }

    /**
     * Authenticates with the Dynamics 365 CRM / Dataverse API
     * Retrieves and stores the access token for subsequent API calls
     *
     * @param array $paramConnexion Connection parameters containing tenant_id, client_id, client_secret, and org_url
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
            $orgUrl = rtrim($this->paramConnexion['org_url'], '/');

            // Dynamics CRM uses the organization URL as the scope
            $scope = "{$orgUrl}/.default";
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
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorMessage = $responseBody;
            }
            $error = $errorMessage.' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    /**
     * Retrieves all available modules (entities) for the connected organization
     *
     * @param string $type The type of modules to retrieve (source/target)
     * @return array Associative array of module names and their display names
     * @throws \Exception If modules cannot be retrieved or connection is invalid
     */
    public function get_modules($type = 'source')
    {
        try {
            $result = [];

            if (!$this->connexion_valide) {
                throw new \Exception("Connection not validated. Please ensure login parameters are correct and login() was successful.");
            }

            $entityList = $this->getEntityListFromMetadata();
            if (empty($entityList)) {
                throw new \Exception("No entities found");
            }

            foreach ($entityList as $entity) {
                $logicalName = $entity['LogicalName'];
                $displayName = $entity['DisplayName'] ?? ucfirst($logicalName);
                $result[$logicalName] = $displayName;
            }

            if (count($result) < 10 && count($result) > 0) {
                $this->logger->warning("Not enough modules found. Found " . count($result) . " modules.");
                throw new \Exception("Not enough modules found. Please check the logs for details.");
            }

            $this->modules = $result;
            return $result;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorMessage = $responseBody;
            }
            $error = $errorMessage.' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    /**
     * Retrieves field definitions for a specific module (entity)
     * Fetches metadata from the API to determine available fields and their properties
     *
     * @param string $module Entity logical name
     * @param string $type The type of fields to retrieve (source/target)
     * @param array|null $param Additional parameters for field retrieval
     * @return array Array of field definitions with their properties
     * @throws \Exception If module is invalid or metadata cannot be retrieved
     */
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            if (!$this->token) {
                throw new \Exception("Unable to retrieve access token.");
            }

            $url = $this->getBaseApiUrl() . "EntityDefinitions(LogicalName='{$module}')/Attributes";

            $client = $this->getApiClient();
            $headers = $this->getApiHeaders();
            $response = $client->get($url, ['headers' => $headers]);
            $data = json_decode($response->getBody(), true);

            $fields = [];

            if (isset($data['value']) && is_array($data['value'])) {
                foreach ($data['value'] as $attribute) {
                    $name = $attribute['LogicalName'] ?? null;
                    if (!$name) {
                        continue;
                    }

                    // Skip system/internal attributes that cannot be used
                    if (isset($attribute['IsValidForRead']) && !$attribute['IsValidForRead']) {
                        continue;
                    }

                    $displayName = $attribute['DisplayName']['UserLocalizedLabel']['Label'] ?? $name;
                    $requiredLevel = $attribute['RequiredLevel']['Value'] ?? 'None';

                    $fields[$name] = [
                        'label' => $displayName,
                        'type' => 'varchar(255)',
                        'type_bdd' => 'varchar(255)',
                        'required' => ($requiredLevel === 'ApplicationRequired' || $requiredLevel === 'SystemRequired') ? 1 : 0
                    ];
                }
            }

            return $fields;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorMessage = $responseBody;
            }
            $error = $errorMessage . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
            $this->logger->error($error);
            return ['error' => $error];
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
            'OData-MaxVersion' => '4.0',
            'OData-Version' => '4.0',
        ];
    }

    /**
     * Constructs the base URL for API calls
     * Uses the organization URL from connection parameters
     *
     * @return string Base URL for API endpoints
     */
    private function getBaseApiUrl(): string
    {
        $orgUrl = rtrim($this->paramConnexion['org_url'], '/');
        return "{$orgUrl}/api/data/v9.2/";
    }

    /**
     * Gets the plural name (EntitySetName) for an entity
     *
     * @param string $logicalName Entity logical name
     * @return string Entity set name for API calls
     */
    private function getEntitySetName(string $logicalName): string
    {
        try {
            $url = $this->getBaseApiUrl() . "EntityDefinitions(LogicalName='{$logicalName}')?". '$select=EntitySetName';
            $client = $this->getApiClient();
            $headers = $this->getApiHeaders();
            $response = $client->get($url, ['headers' => $headers]);
            $data = json_decode($response->getBody(), true);

            return $data['EntitySetName'] ?? $logicalName . 's';
        } catch (\Exception $e) {
            $this->logger->warning("Could not get EntitySetName for {$logicalName}, using default: " . $e->getMessage());
            return $logicalName . 's';
        }
    }

    /**
     * Gets the primary key field name for an entity
     *
     * @param string $logicalName Entity logical name
     * @return string Primary key field name
     */
    private function getPrimaryIdAttribute(string $logicalName): string
    {
        try {
            $url = $this->getBaseApiUrl() . "EntityDefinitions(LogicalName='{$logicalName}')?". '$select=PrimaryIdAttribute';
            $client = $this->getApiClient();
            $headers = $this->getApiHeaders();
            $response = $client->get($url, ['headers' => $headers]);
            $data = json_decode($response->getBody(), true);

            return $data['PrimaryIdAttribute'] ?? $logicalName . 'id';
        } catch (\Exception $e) {
            $this->logger->warning("Could not get PrimaryIdAttribute for {$logicalName}, using default: " . $e->getMessage());
            return $logicalName . 'id';
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
        if (!empty($param['ruleParams']['limit'])) {
            $param['limit'] = $param['ruleParams']['limit'];
        }

        $client = $this->getApiClient();
        $headers = $this->getApiHeaders();

        $module = $param['module'];
        $entitySetName = $this->getEntitySetName($module);
        $primaryIdAttribute = $this->getPrimaryIdAttribute($module);

        $filterValue = '';

    if (!empty($param['query'])) {
        foreach ($param['query'] as $key => $value) {
            // If the query uses 'id', translate it to the entity's actual ID field (e.g., accountid)
            $field = ($key === 'id') ? $primaryIdAttribute : $key;
            $filterValue .= "{$field} eq '{$value}' and ";
        }
        $filterValue = rtrim($filterValue, ' and ');
    } else if (!empty($param['date_ref'])) {
            $dateRef = $this->dateTimeFromMyddleware($param['date_ref']);
            $filterValue = "modifiedon gt {$dateRef}";
        } else if (!empty($param['ruleParams']['datereference'])) {
            $dateRef = $this->dateTimeFromMyddleware($param['ruleParams']['datereference']);
            $filterValue = "modifiedon gt {$dateRef}";
        }

        $url = $this->getBaseApiUrl() . $entitySetName;
        if (!empty($filterValue)) {
            $url .= '?$filter=' . urlencode($filterValue);
        }

        // Add ordering by modifiedon
        // $url .= (strpos($url, '?') !== false ? '&' : '?') . '$orderby=modifiedon asc';
        $url .= (strpos($url, '?') !== false ? '&' : '?') . '$orderby=modifiedon%20asc';

        try {
            $response = $client->get($url, ['headers' => $headers]);
            $data = json_decode($response->getBody(), true);

            if (!isset($data['value']) || !is_array($data['value'])) {
                $this->logger->error("Invalid response format: missing or invalid 'value' array");
                return [];
            }

            $resultFinal = [];
            $nbRecords = 0;

            foreach ($data['value'] as $record) {
                if (isset($param['limit']) && $nbRecords >= $param['limit']) {
                    break;
                }

                $result = [];
                $recordId = $record[$primaryIdAttribute] ?? null;

                if (empty($recordId)) {
                    $this->logger->warning("Record missing primary ID field: " . json_encode($record));
                    continue;
                }

                if (isset($param['fields']) && is_array($param['fields'])) {
                    foreach ($param['fields'] as $field) {
                        if (isset($record[$field])) {
                            $result[$field] = $record[$field];
                        } else {
                            $result[$field] = null;
                        }
                    }

                    if (!empty($record['modifiedon'])) {
                        $result['date_modified'] = $this->dateTimeToMyddleware($record['modifiedon']);
                    }
                } else {
                    $result['id'] = $recordId;
                }

                if (!isset($result['id'])) {
                    $result['id'] = $recordId;
                }

                $resultFinal[$result['id']] = $result;
                $nbRecords++;
            }

            return $resultFinal;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorMessage = $responseBody;
            }
            $error = $errorMessage.' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
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
    public function create($param, $record, $idDoc = null)
    {
        try {
            $client = $this->getApiClient();
            $headers = $this->getApiHeaders();
            $headers['Content-Type'] = 'application/json';
            $headers['Prefer'] = 'return=representation';

            $module = $param['module'];
            $entitySetName = $this->getEntitySetName($module);
            $primaryIdAttribute = $this->getPrimaryIdAttribute($module);

            $url = $this->getBaseApiUrl() . $entitySetName;

            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $record
            ]);

            $data = json_decode($response->getBody(), true);

            if (!isset($data[$primaryIdAttribute])) {
                throw new \Exception('No ID returned from API response');
            }

            return $data[$primaryIdAttribute];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorMessage = $responseBody;
            }
            $error = $errorMessage.' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    /**
     * Updates an existing record in the specified module
     *
     * Uses optimistic concurrency control via ETags when available
     *
     * @param array $param Parameters for the update operation
     * @param array $data Data to be updated
     * @param string|null $idDoc Document ID for tracking
     * @return string|array Updated record ID or error array
     * @throws \Exception If update fails
     */
    protected function update($param, $data, $idDoc = null)
    {
        try {
            $client = $this->getApiClient();
            $headers = $this->getApiHeaders();
            $headers['Content-Type'] = 'application/json';
            $headers['Prefer'] = 'return=representation';

            $module = $param['module'];
            $entitySetName = $this->getEntitySetName($module);
            $primaryIdAttribute = $this->getPrimaryIdAttribute($module);
            $targetId = $data['target_id'];

            unset($data['target_id']);

            $url = $this->getBaseApiUrl() . "{$entitySetName}({$targetId})";

            // Get current record for ETag (optimistic concurrency)
            $getResponse = $client->get($url, ['headers' => $this->getApiHeaders()]);
            $etag = $getResponse->getHeader('ETag')[0] ?? null;

            if ($etag) {
                $headers['If-Match'] = $etag;
            }

            $response = $client->patch($url, [
                'headers' => $headers,
                'json' => $data
            ]);

            $responseData = json_decode($response->getBody(), true);

            if (!isset($responseData[$primaryIdAttribute])) {
                return $targetId;
            }

            return $responseData[$primaryIdAttribute];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorMessage = $responseBody;
            }
            $error = $errorMessage.' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        } catch (\Exception $e) {
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

            $module = $param['module'];
            $entitySetName = $this->getEntitySetName($module);
            $targetId = $data['target_id'];

            $url = $this->getBaseApiUrl() . "{$entitySetName}({$targetId})";

            $response = $client->delete($url, [
                'headers' => $headers
            ]);

            if ($response->getStatusCode() === 204) {
                return $targetId;
            } else {
                throw new \Exception('Unexpected response status code: ' . $response->getStatusCode());
            }

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorMessage = $responseBody;
            }
            $error = $errorMessage.' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    /**
     * Retrieves list of available entities from API metadata
     *
     * @return array List of available entities with their logical names and display names
     * @throws \Exception If metadata cannot be retrieved
     */
    protected function getEntityListFromMetadata(): array
    {
        try {
            if (!$this->token) {
                throw new \Exception("Access token is not available. Please login first.");
            }

            $client = $this->getApiClient();
            $headers = $this->getApiHeaders();

            // Get entity definitions with select for efficiency
            $url = $this->getBaseApiUrl() . 'EntityDefinitions?$select=LogicalName,DisplayName,IsValidForAdvancedFind';

            $response = $client->get($url, ['headers' => $headers]);
            $data = json_decode($response->getBody(), true);

            $entities = [];

            if (isset($data['value']) && is_array($data['value'])) {
                foreach ($data['value'] as $entity) {
                    // Only include entities that are valid for advanced find (user-accessible)
                    if (isset($entity['IsValidForAdvancedFind']) && $entity['IsValidForAdvancedFind']) {
                        $logicalName = $entity['LogicalName'];
                        $displayName = $entity['DisplayName']['UserLocalizedLabel']['Label'] ?? ucfirst($logicalName);

                        $entities[] = [
                            'LogicalName' => $logicalName,
                            'DisplayName' => $displayName
                        ];
                    }
                }
            }

            return $entities;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorMessage = $responseBody;
            }
            $error = $errorMessage.' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    /**
     * Converts Dynamics CRM datetime format to Myddleware format
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
        return $dto->format('Y-m-d H:i:s.v');
    }

    /**
     * Converts Myddleware datetime format to Dynamics CRM format
     *
     * @param string $dateTime Datetime in Myddleware format
     * @return string Datetime in Dynamics format (ISO 8601)
     * @throws \Exception If datetime is empty or invalid
     */
    protected function dateTimeFromMyddleware($dateTime)
    {
        if (empty($dateTime)) {
            throw new \Exception("Date time is empty");
        }

        $dto = new \DateTime($dateTime);
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
        return "modifiedon";
    }
}
?>
