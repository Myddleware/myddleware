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

use App\Solutions\Support\MauticApiHelper;
use App\Solutions\Support\MauticConnectorHelper;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

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

    protected array $moduleConfiguration = [
        'contacts' => [
            'endpoint' => 'contacts',
            'item_key' => 'contact',
            'items_key' => 'contacts',
        ],
        'companies' => [
            'endpoint' => 'companies',
            'item_key' => 'company',
            'items_key' => 'companies',
        ],
        'segments' => [
            'endpoint' => 'segments',
            'item_key' => 'list',
            'items_key' => 'lists',
        ],
    ];

    protected array $metadataFields = [
        'segments' => [
            'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
            'name' => ['label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
            'alias' => ['label' => 'Alias', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
            'description' => ['label' => 'Description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
            'isPublished' => ['label' => 'Is published', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
            'isGlobal' => ['label' => 'Is global', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        ],
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
            if ('' === $this->baseUrl) {
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
        unset($type);

        try {
            return [
                'contacts' => 'Contacts',
                'companies' => 'Companies',
                'segments' => 'Segments',
            ];
        } catch (\Exception $exception) {
            $errorMessage = $exception->getMessage();
            $this->logger->error($errorMessage, ['exception_file' => $exception->getFile(), 'exception_line' => $exception->getLine()]);

            return ['error' => $errorMessage];
        }
    }

    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        unset($param);
        parent::get_module_fields($module, $type);

        try {
            $this->moduleFields = $this->addRequiredField($this->moduleFields, $module);
            $metadataFields = (new MauticConnectorHelper())->getMetadataFields($this->metadataFields, $module);

            if (!empty($metadataFields)) {
                $this->moduleFields = array_merge($this->moduleFields, $metadataFields);
            }

            $this->moduleFields = (new MauticConnectorHelper())->normalizeModuleFields($this->moduleFields);

            return $this->moduleFields;
        } catch (\Exception $exception) {
            $errorMessage = $exception->getMessage();
            $this->logger->error($errorMessage, ['exception_file' => $exception->getFile(), 'exception_line' => $exception->getLine()]);

            return ['error' => $errorMessage];
        }
    }

    public function getRefFieldName($param): string
    {
        if (in_array($param['ruleParams']['mode'], ['0', 'S', 'U'], true)) {
            return 'dateModified';
        }

        if ('C' === $param['ruleParams']['mode']) {
            return 'dateAdded';
        }

        return 'dateModified';
    }

    public function readData($param): array
    {
        try {
            $connectorHelper = new MauticConnectorHelper();
            $normalizedParameters = $connectorHelper->prepareReadParameters(
                $param,
                fn (array $fields) => $this->cleanMyddlewareElementId($fields),
                fn (array $fields, string $module) => $this->addRequiredField($fields, $module),
            );
            $endpointName = $connectorHelper->resolveEndpointName($this->moduleConfiguration, $normalizedParameters['module']);
            $queryParams = $connectorHelper->buildReadQueryParams($normalizedParameters, $this->getRefFieldName($normalizedParameters));
            $requestUrl = $this->apiBase.'/'.$endpointName.'?'.$connectorHelper->createUrlParam($queryParams);
            $responseData = $this->call($requestUrl, 'GET');

            return $connectorHelper->formatReadResult(
                $normalizedParameters,
                $responseData,
                $connectorHelper->resolveReadItemsKey($this->moduleConfiguration, $normalizedParameters['module']),
            );
        } catch (\Exception $exception) {
            $errorMessage = $exception->getMessage();
            $this->logger->error($errorMessage, ['exception_file' => $exception->getFile(), 'exception_line' => $exception->getLine()]);

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
        unset($rule, $document, $type);

        return null;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function call($url, $method = 'GET', $data = null): array
    {
        return (new MauticApiHelper())->call(
            (string) $url,
            (string) $method,
            $data,
            (new MauticApiHelper())->buildRequestHeaders($this->buildAuthHeader()),
            $this->logger,
        );
    }

    private function buildAuthHeader(): ?string
    {
        $clientId = trim((string) ($this->paramConnexion['client_id'] ?? ''));
        $clientSecret = trim((string) ($this->paramConnexion['client_secret'] ?? ''));

        if ('' !== $clientId && '' !== $clientSecret) {
            $accessToken = $this->getOAuth2AccessToken($clientId, $clientSecret);

            return 'Authorization: Bearer '.$accessToken;
        }

        $loginValue = (string) ($this->paramConnexion['login'] ?? '');
        $passwordValue = (string) ($this->paramConnexion['password'] ?? '');

        if ('' !== $loginValue && '' !== $passwordValue) {
            return 'Authorization: Basic '.base64_encode($loginValue.':'.$passwordValue);
        }

        return null;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function getOAuth2AccessToken(string $clientId, string $clientSecret): string
    {
        $tokenData = (new MauticApiHelper())->getOAuth2AccessToken(
            $this->baseUrl,
            $clientId,
            $clientSecret,
            time(),
            $this->accessToken,
            $this->tokenExpiresAt,
            $this->logger,
        );

        $this->accessToken = $tokenData['access_token'];
        $this->tokenExpiresAt = $tokenData['token_expires_at'];

        return $this->accessToken;
    }

    private function createRecord(string $moduleName, array $payloadData): array
    {
        $moduleConfiguration = $this->getModuleConfiguration($moduleName);
        $responseData = $this->call($this->apiBase.'/'.$moduleConfiguration['endpoint'].'/new', 'POST', $payloadData);

        return (new MauticConnectorHelper())->formatWriteResponse($responseData, $moduleConfiguration['item_key']);
    }

    private function updateRecord(string $moduleName, $recordId, array $payloadData): void
    {
        $moduleConfiguration = $this->getModuleConfiguration($moduleName);
        $responseData = $this->call($this->apiBase.'/'.$moduleConfiguration['endpoint'].'/'.$recordId.'/edit', 'PATCH', $payloadData);
        (new MauticConnectorHelper())->formatWriteResponse($responseData, $moduleConfiguration['item_key']);
    }

    private function deleteRecord(string $moduleName, $recordId): void
    {
        $moduleConfiguration = $this->getModuleConfiguration($moduleName);
        $this->call($this->apiBase.'/'.$moduleConfiguration['endpoint'].'/'.$recordId.'/delete', 'DELETE');
    }

    private function getModuleConfiguration(string $moduleName): array
    {
        try {
            return $this->getConnectorHelper()->getModuleConfiguration($this->moduleConfiguration, $moduleName);
        } catch (\Exception $exception) {
            $this->logger->error('Unsupported Mautic module', ['module' => $moduleName]);
            throw $exception;
        }
    }

    private function extractTargetRecordId(array &$payloadData, string $operationName)
    {
        try {
            return (new MauticConnectorHelper())->extractTargetRecordId($payloadData, $operationName);
        } catch (\Exception $exception) {
            $this->logger->error('Missing Mautic record id', ['operation' => $operationName]);
            throw $exception;
        }
    }

    private function buildWriteErrorResult(\Exception $exception, string $documentId, array $param, array $payloadData): array
    {
        $errorData = (new MauticApiHelper())->buildWriteErrorResult($exception, $documentId, $param, $payloadData);
        $this->logger->error('Mautic send error', $errorData['log_context']);

        return $errorData['result'];
    }
}
