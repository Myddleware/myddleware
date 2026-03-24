<?php

namespace App\Solutions\Support;

class MauticFieldHelper
{
    public function normalizeSingleField(mixed $fieldKey, mixed $fieldValue): array
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

    public function normalizeSearchQuery(mixed $query): string
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
