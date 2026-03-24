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
        $fieldHelper = new MauticFieldHelper();

        foreach ($moduleFields as $fieldKey => $fieldValue) {
            $normalizedFields[$fieldKey] = $fieldHelper->normalizeSingleField($fieldKey, $fieldValue);
        }

        return $normalizedFields;
    }

    public function prepareReadParameters(array $param, callable $cleanElementId, callable $addRequiredField): array
    {
        if (empty($param['limit'])) {
            $param['limit'] = 200;
        }

        if (empty($param['offset'])) {
            $param['offset'] = 0;
        }

        $param['fields'] = $cleanElementId($param['fields']);
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
            $queryParams['search'] = (new MauticFieldHelper())->normalizeSearchQuery($manualQuery);
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

    public function extractTargetRecordId(array &$payloadData): mixed
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
}
