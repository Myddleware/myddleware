<?php
/*********************************************************************************
 * This file is part of Myddleware.
 *
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2026  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
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

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Mautic 7 connector (REST API).
 *
 * Auth supported:
 *  - OAuth2 (client_credentials) via POST /oauth/v2/token (recommended)
 *  - Basic Auth (if enabled in Mautic configuration)
 *
 * Notes for Myddleware:
 *  - This class follows the same interface used by the provided connectors:
 *    getFieldsLogin(), login(), get_modules(), get_module_fields(), readData(),
 *    createData(), updateData(), deleteData().
 */
class mautic extends solution
{
    protected bool $sendDeletion = true;

    protected string $baseUrl = '';
    protected string $apiBase = '';

    protected ?string $accessToken = null;
    protected int $tokenExpiresAt = 0;

    protected array $required_fields = [
        'default' => ['id', 'dateModified'],
        'contacts' => ['id', 'dateModified', 'dateAdded'],
        'companies' => ['id', 'dateModified', 'dateAdded'],
        'segments' => ['id', 'dateModified', 'dateAdded'],
    ];

    protected array $FieldsDuplicate = [
        'contacts' => [],
        'companies' => [],
        'segments' => [],
    ];

    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
            [
                'name' => 'client_id',
                'type' => TextType::class,
                'label' => 'solution.fields.client_id',
            ],
            [
                'name' => 'client_secret',
                'type' => PasswordType::class,
                'label' => 'solution.fields.client_secret',
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

    public function login($paramConnexion)
    {
        parent::login($paramConnexion);

        try {
            $this->baseUrl = rtrim((string) $this->paramConnexion['url'], '/');
            if ($this->baseUrl === '') {
                throw new \Exception('Missing Mautic URL.');
            }

            $this->apiBase = $this->baseUrl.'/api';

            $result = $this->call($this->apiBase.'/contacts?limit=1&minimal=1', 'GET');

            if (!is_array($result) || (!isset($result['total']) && !isset($result['contacts']))) {
                throw new \Exception('Login error: unexpected API response.');
            }

            $this->connexion_valide = true;
        } catch (\Exception $exception) {
            $errorMessage = $exception->getMessage();
            $this->logger->error($errorMessage);

            return ['error' => $errorMessage];
        }
    }

    public function get_modules($type = 'source')
    {
        try {
            return [
                'contacts' => 'Contacts',
                'companies' => 'Companies',
                'segments' => 'Segments',
            ];
        } catch (\Exception $exception) {
            $errorMessage = $exception->getMessage().' '.$exception->getFile().' Line : ( '.$exception->getLine().' )';
            $this->logger->error($errorMessage);

            return ['error' => $errorMessage];
        }
    }

    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);

        try {
            $this->moduleFields = $this->addRequiredField($this->moduleFields, $module);
            $this->loadModuleFields($module);
            $this->moduleFields = $this->normalizeModuleFields($this->moduleFields);

            return $this->moduleFields;
        } catch (\Exception $exception) {
            $errorMessage = $exception->getMessage().' '.$exception->getFile().' Line : ( '.$exception->getLine().' )';
            $this->logger->error($errorMessage);

            return ['error' => $errorMessage];
        }
    }

    public function getRefFieldName($param): string
    {
        if (in_array($param['ruleParams']['mode'], ['0', 'S', 'U'], true)) {
            return 'dateModified';
        }

        if ($param['ruleParams']['mode'] === 'C') {
            return 'dateAdded';
        }

        return 'dateModified';
    }

    public function readData($param): array
    {
        try {
            $normalizedParameters = $this->prepareReadParameters($param);
            $endpointName = $this->resolveEndpointName($normalizedParameters['module']);
            $queryParams = $this->buildReadQueryParams($normalizedParameters);
            $requestUrl = $this->apiBase.'/'.$endpointName.'?'.$this->createUrlParam($queryParams);
            $responseData = $this->call($requestUrl, 'GET');

            return $this->formatReadResult($normalizedParameters, $responseData);
        } catch (\Exception $exception) {
            $errorMessage = $exception->getMessage().' '.$exception->getFile().' Line : ( '.$exception->getLine().' )';
            $this->logger->error($errorMessage);

            return ['error' => $errorMessage];
        }
    }

    public function createData($param): array
    {
        $result = [];

        foreach ($param['data'] as $documentId => $payloadData) {
            try {
                $moduleName = $param['module'];
                $responsePayload = $this->createRecord($moduleName, $payloadData);
                $recordId = $responsePayload['id'] ?? null;

                if (empty($recordId)) {
                    throw new \Exception('Mautic create did not return an id.');
                }

                $result[$documentId] = [
                    'id' => (string) $recordId,
                ];
            } catch (\Exception $exception) {
                $result[$documentId] = $this->buildWriteErrorResult($exception, $documentId, $param, $payloadData);
            }

            $this->updateDocumentStatus($documentId, $result[$documentId], $param);
        }

        return $result;
    }

    public function updateData($param): array
    {
        $result = [];

        foreach ($param['data'] as $documentId => $payloadData) {
            try {
                $moduleName = $param['module'];
                $recordId = $this->extractTargetRecordId($payloadData, 'update');

                $this->updateRecord($moduleName, $recordId, $payloadData);

                $result[$documentId] = [
                    'id' => (string) $recordId,
                ];
            } catch (\Exception $exception) {
                $result[$documentId] = $this->buildWriteErrorResult($exception, $documentId, $param, $payloadData);
            }

            $this->updateDocumentStatus($documentId, $result[$documentId], $param);
        }

        return $result;
    }

    public function deleteData($param): array
    {
        $result = [];

        foreach ($param['data'] as $documentId => $payloadData) {
            try {
                $moduleName = $param['module'];
                $recordId = $this->extractTargetRecordId($payloadData, 'delete');

                $this->deleteRecord($moduleName, $recordId);

                $result[$documentId] = [
                    'id' => (string) $recordId,
                ];
            } catch (\Exception $exception) {
                $result[$documentId] = $this->buildWriteErrorResult($exception, $documentId, $param, $payloadData);
            }

            $this->updateDocumentStatus($documentId, $result[$documentId], $param);
        }

        return $result;
    }

    public function getDirectLink($rule, $document, $type)
    {
        return null;
    }

    public function call($url, $method = 'GET', $data = null): array
    {
        $requestHeaders = $this->buildRequestHeaders();
        $httpMethod = strtoupper((string) $method);
        $requestBody = $this->prepareJsonBody($data, $httpMethod, $requestHeaders);

        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, $httpMethod);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 60);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $requestHeaders);

        if ($requestBody !== null) {
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $requestBody);
        }

        $rawResponse = curl_exec($curlHandle);

        if ($rawResponse === false) {
            $curlError = curl_error($curlHandle);
            curl_close($curlHandle);
            $safeError = self::escapeExceptionValue($curlError);
            throw new \Exception(sprintf('cURL error: %s', $safeError));
        }

        $statusCode = (int) curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        curl_close($curlHandle);

        return $this->parseApiResponse($rawResponse, $statusCode, $httpMethod, (string) $url);
    }

    private function buildAuthHeader(): ?string
    {
        $clientId = trim((string) ($this->paramConnexion['client_id'] ?? ''));
        $clientSecret = trim((string) ($this->paramConnexion['client_secret'] ?? ''));

        if ($clientId !== '' && $clientSecret !== '') {
            $accessToken = $this->getOAuth2AccessToken($clientId, $clientSecret);

            return 'Authorization: Bearer '.$accessToken;
        }

        $loginValue = (string) ($this->paramConnexion['login'] ?? '');
        $passwordValue = (string) ($this->paramConnexion['password'] ?? '');

        if ($loginValue !== '' && $passwordValue !== '') {
            return 'Authorization: Basic '.base64_encode($loginValue.':'.$passwordValue);
        }

        return null;
    }

    private function getOAuth2AccessToken(string $clientId, string $clientSecret): string
    {
        $currentTimestamp = time();

        if (!empty($this->accessToken) && $this->tokenExpiresAt > ($currentTimestamp + 60)) {
            return $this->accessToken;
        }

        $tokenUrl = $this->baseUrl.'/oauth/v2/token';
        $postPayload = http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $tokenUrl);
        curl_setopt($curlHandle, CURLOPT_POST, true);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 60);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $postPayload);

        $rawResponse = curl_exec($curlHandle);

        if ($rawResponse === false) {
            $curlError = curl_error($curlHandle);
            curl_close($curlHandle);
            $safeError = self::escapeExceptionValue($curlError);
            throw new \Exception(sprintf('OAuth token cURL error: %s', $safeError));
        }

        $statusCode = (int) curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        curl_close($curlHandle);

        $decodedResponse = json_decode($rawResponse, true);
        if (!is_array($decodedResponse)) {
            throw new \Exception('OAuth token response was not JSON.');
        }

        if ($statusCode >= 400) {
            $message = $decodedResponse['error_description'] ?? ('HTTP '.$statusCode);
            $safeStatus = self::escapeExceptionValue((string) $statusCode);
            $safeMessage = self::escapeExceptionValue($message);

            throw new \Exception(sprintf('OAuth token error (%s): %s', $safeStatus, $safeMessage));
        }

        if (empty($decodedResponse['access_token']) || empty($decodedResponse['expires_in'])) {
            throw new \Exception('OAuth token response missing access_token/expires_in.');
        }

        $this->accessToken = (string) $decodedResponse['access_token'];
        $this->tokenExpiresAt = $currentTimestamp + (int) $decodedResponse['expires_in'];

        return $this->accessToken;
    }

    protected function createUrlParam(array $params): string
    {
        $cleanParams = [];

        foreach ($params as $paramKey => $paramValue) {
            if ($paramValue === null) {
                continue;
            }

            if ($paramValue === '' && $paramValue !== '0') {
                continue;
            }

            $cleanParams[$paramKey] = $paramValue;
        }

        return http_build_query($cleanParams);
    }

    private function camelToSnake(string $name): string
    {
        $snakeStepOne = preg_replace('/(.)([A-Z][a-z]+)/', '$1_$2', $name);
        $snakeStepTwo = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', (string) $snakeStepOne);

        return strtolower((string) $snakeStepTwo);
    }

    private function formatWriteResponse(array $responseData, string $rootKey): array
    {
        if (!empty($responseData[$rootKey]) && is_array($responseData[$rootKey])) {
            return $responseData[$rootKey];
        }

        return $responseData;
    }

    private static function escapeExceptionValue($value): string
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function loadModuleFields(string $moduleName): void
    {
        if ($moduleName === 'contacts') {
            $this->loadContactFields();
            return;
        }

        if ($moduleName === 'companies') {
            $this->loadCompanyFields();
            return;
        }

        if ($moduleName === 'segments') {
            $this->loadSegmentFields();
        }
    }

    private function loadContactFields(): void
    {
        $fieldList = $this->call($this->apiBase.'/contacts/list/fields', 'GET');
        if (!is_array($fieldList)) {
            return;
        }

        foreach ($fieldList as $fieldMetadata) {
            if (!is_array($fieldMetadata) || empty($fieldMetadata['alias'])) {
                continue;
            }

            $fieldAlias = $fieldMetadata['alias'];
            $this->moduleFields[$fieldAlias] = [
                'label' => $fieldMetadata['label'] ?? $fieldAlias,
                'type' => $fieldMetadata['type'] ?? 'text',
                'group' => $fieldMetadata['group'] ?? 'core',
            ];
        }
    }

    private function loadCompanyFields(): void
    {
        $companyResponse = $this->call($this->apiBase.'/companies?limit=1&minimal=0', 'GET');
        $firstCompany = $this->extractFirstCompany($companyResponse);

        if (!empty($firstCompany['fields']) && is_array($firstCompany['fields'])) {
            $this->appendCompanyFieldsFromGroups($firstCompany['fields']);
            return;
        }

        $this->loadFallbackCompanyFields();
    }

    private function extractFirstCompany(array $companyResponse): ?array
    {
        if (empty($companyResponse['companies']) || !is_array($companyResponse['companies'])) {
            return null;
        }

        $firstCompany = reset($companyResponse['companies']);
        if (!is_array($firstCompany)) {
            return null;
        }

        return $firstCompany;
    }

    private function appendCompanyFieldsFromGroups(array $groupedFields): void
    {
        foreach ($groupedFields as $groupName => $groupFields) {
            if (!is_array($groupFields)) {
                continue;
            }

            foreach ($groupFields as $fieldAlias => $fieldMetadata) {
                if (!is_array($fieldMetadata)) {
                    continue;
                }

                $this->moduleFields[$fieldAlias] = [
                    'label' => $fieldMetadata['label'] ?? $fieldAlias,
                    'type' => $fieldMetadata['type'] ?? 'text',
                    'group' => $fieldMetadata['group'] ?? $groupName,
                ];
            }
        }
    }

    private function loadFallbackCompanyFields(): void
    {
        try {
            $fallbackResponse = $this->call($this->apiBase.'/fields/company', 'GET');
        } catch (\Exception $exception) {
            return;
        }

        if (!is_array($fallbackResponse)) {
            return;
        }

        $fieldItems = $fallbackResponse['fields'] ?? $fallbackResponse;
        if (!is_array($fieldItems)) {
            return;
        }

        foreach ($fieldItems as $fieldMetadata) {
            if (!is_array($fieldMetadata) || empty($fieldMetadata['alias'])) {
                continue;
            }

            $fieldAlias = $fieldMetadata['alias'];
            $this->moduleFields[$fieldAlias] = [
                'label' => $fieldMetadata['label'] ?? $fieldAlias,
                'type' => $fieldMetadata['type'] ?? 'text',
                'group' => $fieldMetadata['group'] ?? 'core',
            ];
        }
    }

    private function loadSegmentFields(): void
    {
        $this->moduleFields = array_merge($this->moduleFields, [
            'id' => ['label' => 'id', 'type' => 'int'],
            'name' => ['label' => 'name', 'type' => 'text'],
            'alias' => ['label' => 'alias', 'type' => 'text'],
            'isPublished' => ['label' => 'isPublished', 'type' => 'bool'],
            'dateAdded' => ['label' => 'dateAdded', 'type' => 'datetime'],
            'dateModified' => ['label' => 'dateModified', 'type' => 'datetime'],
        ]);
    }

    private function normalizeModuleFields(array $moduleFields): array
    {
        $normalizedFields = [];

        foreach ($moduleFields as $fieldKey => $fieldValue) {
            $normalizedFields[$fieldKey] = $this->normalizeSingleField($fieldKey, $fieldValue);
        }

        return $normalizedFields;
    }

    private function normalizeSingleField($fieldKey, $fieldValue): array
    {
        if (is_string($fieldValue)) {
            return [
                'label' => $fieldValue,
                'type' => 'text',
                'required' => false,
            ];
        }

        if (!is_array($fieldValue)) {
            return [
                'label' => (string) $fieldKey,
                'type' => 'text',
                'required' => false,
            ];
        }

        if (!isset($fieldValue['label']) || $fieldValue['label'] === '') {
            $fieldValue['label'] = (string) $fieldKey;
        }

        $fieldValue['required'] = array_key_exists('required', $fieldValue)
            ? (bool) $fieldValue['required']
            : false;

        return $fieldValue;
    }

    private function prepareReadParameters(array $param): array
    {
        if (empty($param['limit'])) {
            $param['limit'] = 200;
        }

        if (empty($param['offset'])) {
            $param['offset'] = 0;
        }

        $param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
        $param['fields'] = $this->addRequiredField($param['fields'], $param['module']);

        return $param;
    }

    private function resolveEndpointName(string $moduleName): string
    {
        $endpointMap = [
            'contacts' => 'contacts',
            'companies' => 'companies',
            'segments' => 'segments',
        ];

        if (!isset($endpointMap[$moduleName])) {
            throw new \Exception('Unsupported module '.$moduleName);
        }

        return $endpointMap[$moduleName];
    }

    private function buildReadQueryParams(array $param): array
    {
        $queryParams = [
            'start' => (int) $param['offset'],
            'limit' => (int) $param['limit'],
            'minimal' => 1,
        ];

        $dateReference = $param['ruleParams']['datereference'] ?? null;
        $manualQuery = $param['query'] ?? null;

        if (empty($manualQuery) && !empty($dateReference)) {
            $referenceField = $this->getRefFieldName($param);
            $queryParams['where[0][col]'] = $referenceField;
            $queryParams['where[0][expr]'] = 'gte';
            $queryParams['where[0][val]'] = $dateReference;
        }

        if (!empty($manualQuery)) {
            $queryParams['search'] = $this->normalizeSearchQuery($manualQuery);
        }

        $mode = $param['ruleParams']['mode'] ?? '0';
        $queryParams['orderBy'] = ($mode === 'C') ? 'dateAdded' : 'dateModified';
        $queryParams['orderByDir'] = 'asc';

        return $queryParams;
    }

    private function normalizeSearchQuery($query): string
    {
        if (!is_array($query)) {
            return (string) $query;
        }

        $queryParts = [];
        foreach ($query as $fieldName => $fieldValue) {
            if ($fieldValue === null || $fieldValue === '') {
                continue;
            }

            $queryParts[] = $fieldName.':'.$fieldValue;
        }

        return implode(' AND ', $queryParts);
    }

    private function formatReadResult(array $param, array $responseData): array
    {
        $itemsKey = $this->resolveReadItemsKey($param['module']);
        $rawItems = $responseData[$itemsKey] ?? [];

        if (!is_array($rawItems)) {
            $rawItems = [];
        }

        $values = [];
        foreach ($rawItems as $itemKey => $rowData) {
            if (!is_array($rowData)) {
                continue;
            }

            if (!isset($rowData['id'])) {
                $rowData['id'] = is_numeric($itemKey) ? (int) $itemKey : $itemKey;
            }

            $values[] = $rowData;
        }

        return [
            'count' => count($values),
            'values' => $values,
            'date_ref' => $param['ruleParams']['datereference'] ?? null,
        ];
    }

    private function resolveReadItemsKey(string $moduleName): string
    {
        $itemsKeyMap = [
            'contacts' => 'contacts',
            'companies' => 'companies',
            'segments' => 'lists',
        ];

        return $itemsKeyMap[$moduleName] ?? $moduleName;
    }

    private function createRecord(string $moduleName, array $payloadData): array
    {
        if ($moduleName === 'contacts') {
            $responseData = $this->call($this->apiBase.'/contacts/new', 'POST', $payloadData);
            return $this->formatWriteResponse($responseData, 'contact');
        }

        if ($moduleName === 'companies') {
            $responseData = $this->call($this->apiBase.'/companies/new', 'POST', $payloadData);
            return $this->formatWriteResponse($responseData, 'company');
        }

        if ($moduleName === 'segments') {
            $responseData = $this->call($this->apiBase.'/segments/new', 'POST', $payloadData);
            return $this->formatWriteResponse($responseData, 'list');
        }

        throw new \Exception('Unsupported module '.$moduleName);
    }

    private function updateRecord(string $moduleName, $recordId, array $payloadData): void
    {
        if ($moduleName === 'contacts') {
            $responseData = $this->call($this->apiBase.'/contacts/'.$recordId.'/edit', 'PATCH', $payloadData);
            $this->formatWriteResponse($responseData, 'contact');
            return;
        }

        if ($moduleName === 'companies') {
            $responseData = $this->call($this->apiBase.'/companies/'.$recordId.'/edit', 'PATCH', $payloadData);
            $this->formatWriteResponse($responseData, 'company');
            return;
        }

        if ($moduleName === 'segments') {
            $responseData = $this->call($this->apiBase.'/segments/'.$recordId.'/edit', 'PATCH', $payloadData);
            $this->formatWriteResponse($responseData, 'list');
            return;
        }

        throw new \Exception('Unsupported module '.$moduleName);
    }

    private function deleteRecord(string $moduleName, $recordId): void
    {
        if ($moduleName === 'contacts') {
            $this->call($this->apiBase.'/contacts/'.$recordId.'/delete', 'DELETE');
            return;
        }

        if ($moduleName === 'companies') {
            $this->call($this->apiBase.'/companies/'.$recordId.'/delete', 'DELETE');
            return;
        }

        if ($moduleName === 'segments') {
            $this->call($this->apiBase.'/segments/'.$recordId.'/delete', 'DELETE');
            return;
        }

        throw new \Exception('Unsupported module '.$moduleName);
    }

    private function extractTargetRecordId(array &$payloadData, string $operationName)
    {
        $recordId = $payloadData['target_id'] ?? ($payloadData['id'] ?? null);

        if (!empty($payloadData['target_id'])) {
            unset($payloadData['target_id']);
        }

        if (empty($recordId)) {
            throw new \Exception('Missing record id for '.$operationName.'.');
        }

        return $recordId;
    }

    private function buildWriteErrorResult(
        \Exception $exception,
        string $documentId,
        array $param,
        array $payloadData
    ): array {
        $errorMessage = $exception->getMessage().' '.$exception->getFile().' Line : ( '.$exception->getLine().' )';

        $this->logger->error('Mautic send error', [
            'method' => __FUNCTION__,
            'document_id' => $documentId,
            'module' => $param['module'] ?? null,
            'payload' => $payloadData,
            'error' => $errorMessage,
        ]);

        return [
            'id' => '-1',
            'error' => $errorMessage,
        ];
    }

    private function buildRequestHeaders(): array
    {
        $requestHeaders = [
            'Accept: application/json',
        ];

        $authHeader = $this->buildAuthHeader();
        if (!empty($authHeader)) {
            $requestHeaders[] = $authHeader;
        }

        return $requestHeaders;
    }

    private function prepareJsonBody($data, string $httpMethod, array &$requestHeaders): ?string
    {
        $methodsWithBody = ['POST', 'PUT', 'PATCH'];

        if (empty($data) || !in_array($httpMethod, $methodsWithBody, true)) {
            return null;
        }

        $requestHeaders[] = 'Content-Type: application/json';

        return json_encode($data);
    }

    private function parseApiResponse($rawResponse, int $statusCode, string $httpMethod, string $requestUrl): array
    {
        $trimmedResponse = trim((string) $rawResponse);

        if ($trimmedResponse === '') {
            return $this->parseEmptyApiResponse($statusCode);
        }

        $decodedResponse = json_decode((string) $rawResponse, true);
        if (!is_array($decodedResponse)) {
            return $this->parseNonJsonApiResponse((string) $rawResponse, $statusCode);
        }

        if ($statusCode >= 400) {
            $this->throwApiErrorException($decodedResponse, (string) $rawResponse, $statusCode, $httpMethod, $requestUrl);
        }

        return $decodedResponse;
    }

    private function parseEmptyApiResponse(int $statusCode): array
    {
        if ($statusCode >= 400) {
            $safeStatus = self::escapeExceptionValue((string) $statusCode);
            throw new \Exception(sprintf('HTTP %s', $safeStatus));
        }

        return [];
    }

    private function parseNonJsonApiResponse(string $rawResponse, int $statusCode): array
    {
        if ($statusCode >= 400) {
            $safeStatus = self::escapeExceptionValue((string) $statusCode);
            $safeRawResponse = self::escapeExceptionValue($rawResponse);
            throw new \Exception(sprintf('HTTP %s - %s', $safeStatus, $safeRawResponse));
        }

        return ['raw' => $rawResponse];
    }

    private function throwApiErrorException(
        array $decodedResponse,
        string $rawResponse,
        int $statusCode,
        string $httpMethod,
        string $requestUrl
    ): void {
        $errorMessage = $decodedResponse['error_description']
            ?? ($decodedResponse['error']['message'] ?? null)
            ?? ('HTTP '.$statusCode);

        $responseDetail = $this->buildApiErrorDetail($decodedResponse, $rawResponse);

        $safeStatus = self::escapeExceptionValue((string) $statusCode);
        $safeMessage = self::escapeExceptionValue($errorMessage);
        $safeMethod = self::escapeExceptionValue($httpMethod);
        $safeUrl = self::escapeExceptionValue($requestUrl);
        $safeDetail = self::escapeExceptionValue($responseDetail);

        throw new \Exception(sprintf(
            'Mautic API error (%s): %s | %s %s | Response: %s',
            $safeStatus,
            $safeMessage,
            $safeMethod,
            $safeUrl,
            $safeDetail
        ));
    }

    private function buildApiErrorDetail(array $decodedResponse, string $rawResponse): string
    {
        $responseDetail = !empty($decodedResponse)
            ? json_encode($decodedResponse, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : $rawResponse;

        if (is_string($responseDetail) && strlen($responseDetail) > 2000) {
            return substr($responseDetail, 0, 2000).'…';
        }

        return (string) $responseDetail;
    }
}