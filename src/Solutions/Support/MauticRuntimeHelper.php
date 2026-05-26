<?php

namespace App\Solutions\Support;

use Psr\Log\LoggerInterface;

class MauticRuntimeHelper
{
    public function buildAuthHeader(array $paramConnexion, callable $getOAuth2AccessToken): ?string
    {
        $clientId = trim((string) ($paramConnexion['client_id'] ?? ''));
        $clientSecret = trim((string) ($paramConnexion['client_secret'] ?? ''));

        if ('' !== $clientId && '' !== $clientSecret) {
            return 'Authorization: Bearer '.$getOAuth2AccessToken($clientId, $clientSecret);
        }

        $loginValue = (string) ($paramConnexion['login'] ?? '');
        $passwordValue = (string) ($paramConnexion['password'] ?? '');

        if ('' !== $loginValue && '' !== $passwordValue) {
            return 'Authorization: Basic '.base64_encode($loginValue.':'.$passwordValue);
        }

        return null;
    }

    public function getModuleConfiguration(array $moduleConfiguration, string $moduleName, LoggerInterface $logger): array
    {
        try {
            return (new MauticConnectorHelper())->getModuleConfiguration($moduleConfiguration, $moduleName);
        } catch (\Exception $exception) {
            $logger->error('Unsupported Mautic module', ['module' => $moduleName]);
            throw $exception;
        }
    }

    public function extractTargetRecordId(array &$payloadData, string $operationName, LoggerInterface $logger)
    {
        try {
            return (new MauticConnectorHelper())->extractTargetRecordId($payloadData);
        } catch (\Exception $exception) {
            $logger->error('Missing Mautic record id', ['operation' => $operationName]);
            throw $exception;
        }
    }

    public function buildWriteErrorResult(\Exception $exception, string $documentId, array $param, array $payloadData, LoggerInterface $logger): array
    {
        $errorData = (new MauticApiHelper())->buildWriteErrorResult($exception, $documentId, $param, $payloadData);
        $logger->error('Mautic send error', $errorData['log_context']);

        return $errorData['result'];
    }
}
