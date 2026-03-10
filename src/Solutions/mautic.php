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
    protected string $apiBase = ''; // {baseUrl}/api

    protected ?string $accessToken = null;
    protected int $tokenExpiresAt = 0;

    // Used by Myddleware for incremental reads
    protected array $required_fields = [
        'default' => ['id', 'dateModified'],
        'contacts' => ['id', 'dateModified', 'dateAdded'],
        'companies' => ['id', 'dateModified', 'dateAdded'],
        'segments' => ['id', 'dateModified', 'dateAdded'],
    ];

    // Suggested duplicate keys for upsert logic (if Myddleware uses it)
        // Duplicate check fields disabled for Mautic because Mautic search syntax differs by version.
    // This prevents Myddleware from queueing documents with Status Error_checking.
    // If you want upsert/duplicate detection, we can re-enable with a Mautic-specific search implementation.
    protected array $FieldsDuplicate = [
        'contacts' => [],
        'companies' => [],
        'segments' => [],
    ];

    // Login form parameters
    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
            // OAuth2 client_credentials (recommended)
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
            // Basic Auth (optional alternative)
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

    /**
     * Connect to Mautic.
     * - If client_id/client_secret present => OAuth2 client_credentials
     * - Else if login/password present => Basic Auth
     */
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);

        try {
            $this->baseUrl = rtrim((string) $this->paramConnexion['url'], '/');
            if (empty($this->baseUrl)) {
                throw new \Exception('Missing Mautic URL.');
            }
            $this->apiBase = $this->baseUrl.'/api';

            // Validate credentials by making a minimal API call
            // GET /contacts?limit=1&minimal=1
            $result = $this->call($this->apiBase.'/contacts?limit=1&minimal=1', 'GET');

            // Mautic returns {"total":..., "contacts": {...}} for this endpoint
            if (!is_array($result) || (!isset($result['total']) && !isset($result['contacts']))) {
                throw new \Exception('Login error: unexpected API response.');
            }

            $this->connexion_valide = true;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Modules available
    public function get_modules($type = 'source')
    {
        try {
            // Mautic works both as source and target for these objects
            return [
                'contacts' => 'Contacts',
                'companies' => 'Companies',
                'segments' => 'Segments',
            ];
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Fields of a module
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);

        try {
            // Remove Myddleware system fields, then add required fields
            $this->moduleFields = $this->addRequiredField($this->moduleFields, $module);

            switch ($module) {
                case 'contacts':
                    // List available fields
                    // GET /api/contacts/list/fields
                    $fields = $this->call($this->apiBase.'/contacts/list/fields', 'GET');
                    if (is_array($fields)) {
                        foreach ($fields as $field) {
                            if (!is_array($field) || empty($field['alias'])) {
                                continue;
                            }
                            $alias = $field['alias'];
                            $this->moduleFields[$alias] = [
                                'label' => $field['label'] ?? $alias,
                                'type' => $field['type'] ?? 'text',
                                'group' => $field['group'] ?? 'core',
                            ];
                        }
                    }
                    break;

                case 'companies':
                    // Company fields are returned inside the "fields" structure on GET company.
                    // We retrieve one company (minimal list) then build a field list from its fields metadata.
                    // If there are no companies yet, we fallback to /api/fields/company (available on many installs).
                    $companies = $this->call($this->apiBase.'/companies?limit=1&minimal=0', 'GET');
                    $company = null;
                    if (!empty($companies['companies']) && is_array($companies['companies'])) {
                        $first = reset($companies['companies']);
                        if (is_array($first)) {
                            $company = $first;
                        }
                    }
                    if (!empty($company['fields']) && is_array($company['fields'])) {
                        foreach ($company['fields'] as $group => $groupFields) {
                            if (!is_array($groupFields)) {
                                continue;
                            }
                            foreach ($groupFields as $alias => $meta) {
                                if (!is_array($meta)) {
                                    continue;
                                }
                                $this->moduleFields[$alias] = [
                                    'label' => $meta['label'] ?? $alias,
                                    'type' => $meta['type'] ?? 'text',
                                    'group' => $meta['group'] ?? $group,
                                ];
                            }
                        }
                    } else {
                        // Fallback (best effort)
                        try {
                            $fallback = $this->call($this->apiBase.'/fields/company', 'GET');
                            if (is_array($fallback)) {
                                $items = $fallback['fields'] ?? $fallback;
                                if (is_array($items)) {
                                    foreach ($items as $meta) {
                                        if (!is_array($meta) || empty($meta['alias'])) {
                                            continue;
                                        }
                                        $alias = $meta['alias'];
                                        $this->moduleFields[$alias] = [
                                            'label' => $meta['label'] ?? $alias,
                                            'type' => $meta['type'] ?? 'text',
                                            'group' => $meta['group'] ?? 'core',
                                        ];
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            // ignore fallback errors
                        }
                    }
                    break;

                case 'segments':
                    // Segments have a stable schema; expose a minimal set of fields.
                    // (You can expand later if you need more.)
                    $this->moduleFields = array_merge($this->moduleFields, [
                        'id' => ['label' => 'id', 'type' => 'int'],
                        'name' => ['label' => 'name', 'type' => 'text'],
                        'alias' => ['label' => 'alias', 'type' => 'text'],
                        'isPublished' => ['label' => 'isPublished', 'type' => 'bool'],
                        'dateAdded' => ['label' => 'dateAdded', 'type' => 'datetime'],
                        'dateModified' => ['label' => 'dateModified', 'type' => 'datetime'],
                    ]);
                    break;
            }

            // Normalize fields for Myddleware step3: each field meta MUST be an array with 'required'
            foreach ($this->moduleFields as $k => $v) {
                // Myddleware expects: $fields[$name] = ['required' => bool, ...]
                // Some connectors (and the Myddleware base class) can populate strings; normalize them.
                if (is_string($v)) {
                    $v = [
                        'label'    => $v,
                        'type'     => 'text',
                        'required' => false,
                    ];
                } elseif (!is_array($v)) {
                    // Fallback for unexpected types (null/int/bool)
                    $v = [
                        'label'    => (string) $k,
                        'type'     => 'text',
                        'required' => false,
                    ];
                }

                if (!isset($v['label']) || $v['label'] === '') {
                    $v['label'] = (string) $k;
                }

                if (!array_key_exists('required', $v)) {
                    $v['required'] = false;
                } else {
                    $v['required'] = (bool) $v['required'];
                }

                $this->moduleFields[$k] = $v;
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    /**
     * Myddleware: choose the reference field based on the rule mode.
     * - S / U => dateModified
     * - C     => dateAdded
     */
    public function getRefFieldName($param): string
    {
        if (in_array($param['ruleParams']['mode'], ['0', 'S', 'U'])) {
            return 'dateModified';
        } elseif ($param['ruleParams']['mode'] == 'C') {
            return 'dateAdded';
        }

        return 'dateModified';
    }

    /**
     * Read data.
     * Expects Myddleware's standard $param structure (module, fields, ruleParams, limit, offset, query...).
     *
     * Uses Mautic "advanced where conditions" for incremental reads:
     *   where[0][col]=dateModified&where[0][expr]=gte&where[0][val]=YYYY-mm-dd HH:ii:ss
     */
    public function readData($param): array
    {
        try {
            $result = ['count' => 0, 'values' => []];

            if (empty($param['limit'])) {
                $param['limit'] = 200;
            }
            if (empty($param['offset'])) {
                $param['offset'] = 0;
            }

            // Remove Myddleware system fields
            $param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
            // Add required fields
            $param['fields'] = $this->addRequiredField($param['fields'], $param['module']);

            $dateRef = $param['ruleParams']['datereference'] ?? null;
            $mode = $param['ruleParams']['mode'] ?? '0';

            $endpoint = match ($param['module']) {
                'contacts' => 'contacts',
                'companies' => 'companies',
                'segments' => 'segments',
                default => null,
            };

            if (empty($endpoint)) {
                throw new \Exception('Unsupported module '.$param['module']);
            }

            $qs = [
                'start' => (int) $param['offset'],
                'limit' => (int) $param['limit'],
                'minimal' => 1,
            ];

            // Apply filter if we are in incremental mode and no manual query is passed
            if (empty($param['query']) && !empty($dateRef)) {
                $refField = $this->getRefFieldName($param);
                // Mautic expects "Y-m-d H:i:s" for where[...][val]
                // Myddleware usually stores date_ref in Y-m-d H:i:s; we keep as-is.
                $qs['where[0][col]'] = $refField;
                $qs['where[0][expr]'] = 'gte';
                $qs['where[0][val]'] = $dateRef;
            }

            // If a manual search query is provided by Myddleware, normalize it for Mautic.
            // Myddleware can pass either a string (already a Mautic search string) OR an array like ['email' => 'a@b.com'].
            if (!empty($param['query'])) {
                if (is_array($param['query'])) {
                    $parts = [];
                    foreach ($param['query'] as $qk => $qv) {
                        if ($qv === null || $qv === '') {
                            continue;
                        }
                        // Mautic search syntax: field:value
                        $parts[] = $qk.':'.$qv;
                    }
                    $qs['search'] = implode(' AND ', $parts);
                } else {
                    $qs['search'] = (string) $param['query'];
                }
            }

            // Sort by reference field for deterministic pagination
            $orderBy = ($mode === 'C') ? 'dateAdded' : 'dateModified';
            $qs['orderBy'] = $orderBy;
            $qs['orderByDir'] = 'asc';

            // Field selection intentionally omitted for compatibility with Mautic 7 (the API does not accept select[] in all contexts).

            $url = $this->apiBase.'/'.$endpoint.'?'.$this->createUrlParam($qs);
            $data = $this->call($url, 'GET');

            // Normalize list payloads
            $listKey = $param['module'];
            if ($param['module'] === 'contacts') {
                $listKey = 'contacts';
            } elseif ($param['module'] === 'companies') {
                $listKey = 'companies';
            } elseif ($param['module'] === 'segments') {
                $listKey = 'lists'; // Mautic returns {"lists": {...}} for segments
            }

            $items = $data[$listKey] ?? [];
            if (!is_array($items)) {
                $items = [];
            }

            foreach ($items as $id => $row) {
                if (!is_array($row)) {
                    continue;
                }
                // Ensure ID is present in the row
                if (!isset($row['id'])) {
                    $row['id'] = is_numeric($id) ? (int) $id : $id;
                }
                $result['values'][] = $row;
            }

            $result['count'] = count($result['values']);
            $result['date_ref'] = $dateRef;

            return $result;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Create
    public function createData($param): array
    {
        $result = [];

        // Myddleware sends a batch of documents: $param['data'][<idDoc>] = <fields array>
        foreach ($param['data'] as $idDoc => $data) {
            try {
                $module = $param['module'];

                switch ($module) {
                    case 'contacts':
                        // POST /contacts/new
                        $res = $this->call($this->apiBase.'/contacts/new', 'POST', $data);
                        $payload = $this->formatWriteResponse($res, 'contact');
                        break;

                    case 'companies':
                        // POST /companies/new
                        $res = $this->call($this->apiBase.'/companies/new', 'POST', $data);
                        $payload = $this->formatWriteResponse($res, 'company');
                        break;

                    case 'segments':
                        // POST /segments/new
                        $res = $this->call($this->apiBase.'/segments/new', 'POST', $data);
                        $payload = $this->formatWriteResponse($res, 'list');
                        break;

                    default:
                        throw new \Exception('Unsupported module '.$module);
                }

                $id = $payload['id'] ?? null;
                if (empty($id)) {
                    throw new \Exception('Mautic create did not return an id.');
                }

                $result[$idDoc] = [
                    'id' => (string) $id,
                ];
            } catch (\Exception $e) {
                $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error('Mautic send error', [
                    'method' => __FUNCTION__,
                    'document_id' => $idDoc,
                    'module' => $param['module'] ?? null,
                    'payload' => $data,
                    'error' => $error,
                ]);

                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }

            // Update the transfer status in Myddleware (required for documents to progress past Ready_to_send)
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    // Update
    public function updateData($param): array
    {
        $result = [];

        foreach ($param['data'] as $idDoc => $data) {
            try {
                $module = $param['module'];

                // Myddleware puts the current target id inside the payload as 'target_id'
                $id = $data['target_id'] ?? ($data['id'] ?? null);
                if (!empty($data['target_id'])) {
                    unset($data['target_id']);
                }
                if (empty($id)) {
                    throw new \Exception('Missing record id for update.');
                }

                switch ($module) {
                    case 'contacts':
                        // PATCH /contacts/{id}/edit
                        $res = $this->call($this->apiBase.'/contacts/'.$id.'/edit', 'PATCH', $data);
                        $this->formatWriteResponse($res, 'contact');
                        break;

                    case 'companies':
                        $res = $this->call($this->apiBase.'/companies/'.$id.'/edit', 'PATCH', $data);
                        $this->formatWriteResponse($res, 'company');
                        break;

                    case 'segments':
                        $res = $this->call($this->apiBase.'/segments/'.$id.'/edit', 'PATCH', $data);
                        $this->formatWriteResponse($res, 'list');
                        break;

                    default:
                        throw new \Exception('Unsupported module '.$module);
                }

                $result[$idDoc] = [
                    'id' => (string) $id,
                ];
            } catch (\Exception $e) {
                $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error('Mautic send error', [
                    'method' => __FUNCTION__,
                    'document_id' => $idDoc,
                    'module' => $param['module'] ?? null,
                    'payload' => $data,
                    'error' => $error,
                ]);

                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }

            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    // Delete
    public function deleteData($param): array
    {
        $result = [];

        foreach ($param['data'] as $idDoc => $data) {
            try {
                $module = $param['module'];

                $id = $data['target_id'] ?? ($data['id'] ?? null);
                if (!empty($data['target_id'])) {
                    unset($data['target_id']);
                }
                if (empty($id)) {
                    throw new \Exception('Missing record id for delete.');
                }

                switch ($module) {
                    case 'contacts':
                        $this->call($this->apiBase.'/contacts/'.$id.'/delete', 'DELETE');
                        break;

                    case 'companies':
                        $this->call($this->apiBase.'/companies/'.$id.'/delete', 'DELETE');
                        break;

                    case 'segments':
                        $this->call($this->apiBase.'/segments/'.$id.'/delete', 'DELETE');
                        break;

                    default:
                        throw new \Exception('Unsupported module '.$module);
                }

                $result[$idDoc] = [
                    'id' => (string) $id,
                ];
            } catch (\Exception $e) {
                $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error('Mautic send error', [
                    'method' => __FUNCTION__,
                    'document_id' => $idDoc,
                    'module' => $param['module'] ?? null,
                    'payload' => $data,
                    'error' => $error,
                ]);

                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }

            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    // Optional: direct link to record in Mautic UI
    public function getDirectLink($rule, $document, $type)
    {
        return null;
    }

    // ----------------------------
    // HTTP helpers
    // ----------------------------

    /**
     * Main HTTP call method (curl), modelled after existing connectors (mailchimp/salesforce).
     */
    public function call($url, $method = 'GET', $data = null): array
    {
        // Ensure we have auth headers
        $headers = [
            'Accept: application/json',
        ];

        $authHeader = $this->buildAuthHeader();
        if (!empty($authHeader)) {
            $headers[] = $authHeader;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $raw = curl_exec($ch);

        if (false === $raw) {
            $err = $this->sanitizeForExceptionMessage(curl_error($ch));
            curl_close($ch);
            throw new \Exception('cURL error: '.$err);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Some endpoints might return empty body (e.g. delete)
        if ('' === trim((string) $raw)) {
            if ($status >= 400) {
                throw new \Exception('HTTP '.$this->sanitizeForExceptionMessage((string) $status));
            }
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            // Not JSON; expose raw
            if ($status >= 400) {
                throw new \Exception(
                    'HTTP '.$this->sanitizeForExceptionMessage((string) $status)
                    .' - '.$this->sanitizeForExceptionMessage($raw)
                );
            }
            return ['raw' => $raw];
        }

        if ($status >= 400) {
            $msg = $decoded['error_description']
                ?? ($decoded['error']['message'] ?? null)
                ?? ('HTTP '.$status);
            $detail = '';
            if (!empty($decoded)) {
                $detail = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                $detail = $raw;
            }
            if (is_string($detail) && strlen($detail) > 2000) {
                $detail = substr($detail, 0, 2000).'…';
            }
            throw new \Exception(
                'Mautic API error ('.$this->sanitizeForExceptionMessage((string) $status).'): '
                .$this->sanitizeForExceptionMessage($msg)
                .' | '.$this->sanitizeForExceptionMessage($method)
                .' '.$this->sanitizeForExceptionMessage($url)
                .' | Response: '.$this->sanitizeForExceptionMessage($detail)
            );
        }

        return $decoded;
    }

    private function buildAuthHeader(): ?string
    {
        // OAuth2 if client_id + client_secret present
        $clientId = trim((string) ($this->paramConnexion['client_id'] ?? ''));
        $clientSecret = trim((string) ($this->paramConnexion['client_secret'] ?? ''));

        if (!empty($clientId) && !empty($clientSecret)) {
            $token = $this->getOAuth2AccessToken($clientId, $clientSecret);
            return 'Authorization: Bearer '.$token;
        }

        // Basic Auth fallback
        $login = (string) ($this->paramConnexion['login'] ?? '');
        $password = (string) ($this->paramConnexion['password'] ?? '');
        if (!empty($login) && !empty($password)) {
            return 'Authorization: Basic '.base64_encode($login.':'.$password);
        }

        return null;
    }

    /**
     * OAuth2 client_credentials token retrieval.
     */
    private function getOAuth2AccessToken(string $clientId, string $clientSecret): string
    {
        $now = time();

        // Reuse token if still valid (60s leeway)
        if (!empty($this->accessToken) && $this->tokenExpiresAt > ($now + 60)) {
            return $this->accessToken;
        }

        $tokenUrl = $this->baseUrl.'/oauth/v2/token';
        $payload = http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $raw = curl_exec($ch);
        if (false === $raw) {
            $err = $this->sanitizeForExceptionMessage(curl_error($ch));
            curl_close($ch);
            throw new \Exception('OAuth token cURL error: '.$err);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \Exception('OAuth token response was not JSON.');
        }

        if ($status >= 400) {
            $msg = $decoded['error_description'] ?? ('HTTP '.$status);
            throw new \Exception(
                'OAuth token error ('.$this->sanitizeForExceptionMessage((string) $status).'): '
                .$this->sanitizeForExceptionMessage($msg)
            );
        }

        if (empty($decoded['access_token']) || empty($decoded['expires_in'])) {
            throw new \Exception('OAuth token response missing access_token/expires_in.');
        }

        $this->accessToken = (string) $decoded['access_token'];
        $this->tokenExpiresAt = $now + (int) $decoded['expires_in'];

        return $this->accessToken;
    }

    private function sanitizeForExceptionMessage($value): string
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Build URL parameters (same idea as mailchimp connector).
     */
    protected function createUrlParam(array $params): string
    {
        // Remove null/empty params (but keep "0")
        $clean = [];
        foreach ($params as $k => $v) {
            if ($v === null) {
                continue;
            }
            if ($v === '' && $v !== '0') {
                continue;
            }
            $clean[$k] = $v;
        }
        return http_build_query($clean);
    }

    private function camelToSnake(string $name): string
    {
        $s1 = preg_replace('/(.)([A-Z][a-z]+)/', '$1_$2', $name);
        $s2 = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $s1);
        return strtolower((string) $s2);
    }

    private function formatWriteResponse(array $res, string $rootKey): array
    {
        // Mautic returns { contact: {...} } or { company: {...} } or { list: {...} }
        if (!empty($res[$rootKey]) && is_array($res[$rootKey])) {
            return $res[$rootKey];
        }
        return $res;
    }
}