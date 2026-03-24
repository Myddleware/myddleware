<?php

namespace App\Solutions\Support;

class DolibarrWriteHelper
{
    public function createOrUpdate(string $method, array $param, callable $prepareWriteData, callable $sendWriteRequest, callable $resolveWriteResultId, callable $updateDocumentStatus): array
    {
        $result = [];

        foreach ($param['data'] as $idDoc => $data) {
            try {
                $writeData = $prepareWriteData($method, $param, $data, $idDoc);
                $response = $sendWriteRequest($method, $param['module'], $writeData['recordId'], $writeData['payload']);
                $newId = $resolveWriteResultId($response, $writeData['recordId']);
                $result[$idDoc] = ['id' => $newId, 'error' => false];
            } catch (\Exception $exception) {
                $result[$idDoc] = ['id' => '-1', 'error' => $exception->getMessage()];
            }

            $updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }
}
