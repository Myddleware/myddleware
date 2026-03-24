<?php

namespace App\Solutions\Support;

class MauticConnectorHelper
{
    public function getMetadataFields(array $metadataFields, string $moduleName): array
    {
        return !empty($metadataFields[$moduleName]) && is_array($metadataFields[$moduleName])
            ? $metadataFields[$moduleName]
            : [];
    }

    public function normalizeModuleFields(array $moduleFields): array
    {
        $normalizedFields = [];

        foreach ($moduleFields as $fieldKey => $fieldValue) {
            $normalizedFields[$fieldKey] = $this->normalizeSingleField($fieldKey, $fieldValue);
        }

        return $normalizedFields;
    }

    public function prepareReadParameters(array $param, callable $cleanMyddlewareElementId, callable $addRequiredField): array
    {
        if (empty($param['limit'])) {
            $param['limit'] = 200;
        }

        if (empty($param['offset'])) {
            $param['offset'] = 0;
        }

        $param['fields'] = $cleanMyddlewareElementId($param['fields']);
        $param['fields'] = $addRequiredField($param['fields'], $param['module']);

        return $param;
    }

    public function resolveEndpointName(array $moduleConfiguration, string $moduleName): string
    {
        return $this->getModuleConfiguration($moduleConfiguration, $moduleName)['endpoint'];
    }

    public function getModuleConfiguration(array $moduleConfiguration, string $moduleName): array
    {
        if (!isset($moduleConfiguration[$moduleName])) {
            throw new \Exception('Unsupported Mautic module.');
        }

        return $moduleConfiguration[$moduleName];
    }

    public function buildReadQueryParams(array $param, string $referenceField): array
    {
        $queryParams = [
            'start' => (int) $param['offset'],
            'limit' => (int) $param['limit'],
            'minimal' => 1,
        ];

        $dateReference = $param['ruleParams']['datereference'] ?? null;
        $manualQuery = $param['query'] ?? null;

        if (empty($manualQuery) && !empty($dateReference)) {
            $queryParams['where[0][col]'] = $referenceField;
            $queryParams['where[0][expr]'] = 'gte';
            $queryParams['where[0][val]'] = $dateReference;
        }

        if (!empty($manualQuery)) {
            $queryParams['search'] = $this->normalizeSearchQuery($manualQuery);
        }

        $mode = $param['ruleParams']['mode'] ?? '0';
        $queryParams['orderBy'] = ('C' === $mode) ? 'dateAdded' : 'dateModified';
        $queryParams['orderByDir'] = 'asc';

        return $queryParams;
    }

    public function formatReadResult(array $param, array $responseData, string $itemsKey): array
    {
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

    public function resolveReadItemsKey(array $moduleConfiguration, string $moduleName): string
    {
        return $this->getModuleConfiguration($moduleConfiguration, $moduleName)['items_key'];
    }

    public function createUrlParam(array $params): string
    {
        $cleanParams = [];

        foreach ($params as $paramKey => $paramValue) {
            if (null === $paramValue) {
                continue;
            }

            if ('' === $paramValue && '0' !== $paramValue) {
                continue;
            }

            $cleanParams[$paramKey] = $paramValue;
        }

        return http_build_query($cleanParams);
    }

    public function formatWriteResponse(array $responseData, string $rootKey): array
    {
        return !empty($responseData[$rootKey]) && is_array($responseData[$rootKey])
            ? $responseData[$rootKey]
            : $responseData;
    }

    public function extractTargetRecordId(array &$payloadData, string $operationName): mixed
    {
        $recordId = $payloadData['target_id'] ?? ($payloadData['id'] ?? null);

        if (!empty($payloadData['target_id'])) {
            unset($payloadData['target_id']);
        }

        if (empty($recordId)) {
            throw new \Exception('Missing Mautic record id for write operation.');
        }

        return $recordId;
    }

    private function normalizeSingleField(mixed $fieldKey, mixed $fieldValue): array
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

        if (!isset($fieldValue['label']) || '' === $fieldValue['label']) {
            $fieldValue['label'] = (string) $fieldKey;
        }

        $fieldValue['required'] = array_key_exists('required', $fieldValue)
            ? (bool) $fieldValue['required']
            : false;

        return $fieldValue;
    }

    private function normalizeSearchQuery(mixed $query): string
    {
        if (!is_array($query)) {
            return (string) $query;
        }

        $queryParts = [];
        foreach ($query as $fieldName => $fieldValue) {
            if (null === $fieldValue || '' === $fieldValue) {
                continue;
            }

            $queryParts[] = $fieldName.':'.$fieldValue;
        }

        return implode(' AND ', $queryParts);
    }
}
