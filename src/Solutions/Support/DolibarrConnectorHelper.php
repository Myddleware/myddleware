<?php

namespace App\Solutions\Support;

class DolibarrConnectorHelper
{
    public function getMetadataFields(array $metadataFields, string $module): array
    {
        return !empty($metadataFields[$module]) && is_array($metadataFields[$module])
            ? $metadataFields[$module]
            : [];
    }

    public function getSampleRecord(callable $callApi, string $apiBase, string $module): ?array
    {
        $probe = $callApi($apiBase.$module, 'GET', [
            'limit' => 1,
            'page' => 0,
            'sortfield' => 't.rowid',
            'sortorder' => 'ASC',
        ]);

        if (!is_array($probe)) {
            return null;
        }

        if (!empty($probe[0]) && is_array($probe[0])) {
            return $probe[0];
        }

        if (!empty($probe['data'][0]) && is_array($probe['data'][0])) {
            return $probe['data'][0];
        }

        return null;
    }

    public function buildFieldsFromSample(array $record, array $moduleFields): array
    {
        $discoveredFields = [];

        foreach ($record as $key => $value) {
            if (isset($moduleFields[$key])) {
                continue;
            }

            $discoveredFields[$key] = [
                'label' => $key,
                'type' => $this->guessFieldType($value),
                'required' => 0,
            ];
        }

        return $discoveredFields;
    }

    public function ensureFieldDefinitionDefaults(array $fields): array
    {
        foreach ($fields as $key => $def) {
            if (!is_array($def)) {
                $fields[$key] = [
                    'label' => (string) $def,
                    'type' => 'string',
                    'required' => 0,
                ];

                continue;
            }

            $fields[$key]['label'] = $def['label'] ?? $key;
            $fields[$key]['type'] = $def['type'] ?? 'string';
            $fields[$key]['required'] = $def['required'] ?? 0;
        }

        return $fields;
    }

    public function normalizeApiBase(string $url): string
    {
        $url = trim($url);

        if (preg_match('#/api/index\\.php/?$#', $url)) {
            return rtrim($url, '/').'/';
        }

        if (preg_match('#/api/index\\.php/#', $url)) {
            $position = strpos($url, '/api/index.php/');

            return substr($url, 0, $position + strlen('/api/index.php/'));
        }

        return rtrim($url, '/').'/api/index.php/';
    }

    public function filterFields(array $row, array $fields): array
    {
        if (empty($fields)) {
            return $row;
        }

        $output = [];
        foreach ($fields as $fieldName) {
            if (array_key_exists($fieldName, $row)) {
                $output[$fieldName] = $row[$fieldName];
            }
        }

        if (!empty($row['id']) && !isset($output['id'])) {
            $output['id'] = $row['id'];
        }

        if (!empty($row['rowid']) && !isset($output['id'])) {
            $output['id'] = $row['rowid'];
        }

        return $output;
    }

    public function buildSqlFiltersForDateRef(array $param, array $dateRefFieldMap): ?string
    {
        if (empty($param['ruleParams']['datereference'])) {
            return null;
        }

        $sqlField = $dateRefFieldMap[$param['module']] ?? null;
        if (empty($sqlField)) {
            return null;
        }

        try {
            $dateTime = new \DateTime($param['ruleParams']['datereference']);
        } catch (\Exception) {
            return null;
        }

        return '('.$sqlField.":>=:'".$dateTime->format('Y-m-d')."')";
    }

    public function formatDolibarrError(array $resp): string
    {
        if (!isset($resp['error'])) {
            return 'Unknown Dolibarr error';
        }

        if (is_string($resp['error'])) {
            return $resp['error'];
        }

        $code = $resp['error']['code'] ?? '';
        $message = $resp['error']['message'] ?? 'Unknown error';

        return trim(('' !== $code ? '['.$code.'] ' : '').$message);
    }

    private function guessFieldType(mixed $value): string
    {
        return match (true) {
            is_int($value) => 'int',
            is_float($value) => 'float',
            is_bool($value) => 'bool',
            is_array($value) => 'array',
            default => 'string',
        };
    }
}
