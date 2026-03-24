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

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Dolibarr connector (REST API).
 *
 * API base path is typically: https://<host>/api/index.php/
 * Auth header: DOLAPIKEY: <apikey>  (and optional DOLAPIENTITY for multi-company)
 *
 * Notes:
 * - Dolibarr list endpoints support common params: sortfield, sortorder, limit, page, sqlfilters.
 * - Page numbering starts at 0.
 * - This connector provides:
 *     - Login test (GET /status if available, fallback to /thirdparties?limit=1)
 *     - A curated list of common modules
 *     - Field discovery by sampling 1 record from the module (best-effort; can be replaced by a static metadata file)
 *     - CRUD for modules that follow the canonical pattern:
 *         GET    /<module>           (list)
 *         GET    /<module>/{id}      (one)
 *         POST   /<module>           (create) -> returns id (int) or object with id
 *         PUT    /<module>/{id}      (update)
 *         DELETE /<module>/{id}      (delete)
 */
class dolibarr extends solution
{
    // Enable delete flows (Dolibarr supports DELETE)
    protected bool $sendDeletion = true;
    protected bool $readDeletion = false;

    protected string $apiBase = '';            // Normalized base url ending with /api/index.php/
    protected string $apiKey = '';
    protected ?string $apiEntity = null;

    // Default paging
    protected int $defaultLimit = 100;
    protected int $timeout = 60;
    protected bool $verify_ssl = true;

    /**
     * Best-effort mapping of a "reference date" field to build sqlfilters.
     * You can refine this mapping if you need strict incremental sync.
     * Values are SQL aliases used by Dolibarr APIs (see explorer examples).
     */
    protected array $dateRefFieldMap = [
        // most endpoints use table alias "t"
        'thirdparties' => 't.tms',
        'contacts' => 't.tms',
        'products' => 't.tms',
        'invoices' => 't.tms',
        'orders' => 't.tms',
        'proposals' => 't.tms',
        'tickets' => 't.tms',
        'projects' => 't.tms',
        'tasks' => 't.tms',
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
                'name' => 'apikey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.apikey',
            ],
            [
                'name' => 'entity',
                'type' => TextType::class,
                'label' => 'solution.fields.entity',
                'required' => false,
                'help' => 'Optional (multi-company): send as HTTP header DOLAPIENTITY',
            ],
            [
                'name' => 'verify_ssl',
                'type' => CheckboxType::class,
                'label' => 'solution.fields.verify_ssl',
                'required' => false,
            ],
        ];
    }

    public function login($paramConnexion)
    {
        parent::login($paramConnexion);

        try {
            if (empty($this->paramConnexion['url'])) {
                throw new \Exception('Missing URL.');
            }
            if (empty($this->paramConnexion['apikey'])) {
                throw new \Exception('Missing API key.');
            }

            $this->apiKey = $this->paramConnexion['apikey'];
            $this->apiEntity = !empty($this->paramConnexion['entity']) ? (string) $this->paramConnexion['entity'] : null;

            // SSL verify default = true
            $this->verify_ssl = true;
            if (array_key_exists('verify_ssl', $this->paramConnexion)) {
                // Symfony checkbox may return "1" or true/false
                $this->verify_ssl = !empty($this->paramConnexion['verify_ssl']);
            }

            $this->apiBase = $this->normalizeApiBase($this->paramConnexion['url']);

            // Prefer /status if available, else fallback to a cheap list call.
            $loginSuccessful = false;

            $status = $this->callApi($this->apiBase.'status', 'GET');
            if (is_array($status) && !isset($status['error'])) {
                $loginSuccessful = true;
            } else {
                $probe = $this->callApi($this->apiBase.'thirdparties', 'GET', [
                    'limit' => 1,
                    'page' => 0,
                    'sortfield' => 't.rowid',
                    'sortorder' => 'ASC',
                ]);
                if (is_array($probe) && !isset($probe['error'])) {
                    $loginSuccessful = true;
                }
            }

            if (!$loginSuccessful) {
                throw new \Exception('Login error. Check base URL, REST API module enabled, and API key permissions.');
            }

            $this->connexion_valide = true;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    /**
     * Curated list of common Dolibarr modules.
     * You can expand this list as needed; the actual availability depends on enabled Dolibarr modules and user permissions.
     */
    public function get_modules($type = 'source')
    {
        try {
            // Dolibarr REST API supports read & write; allow both.
            $modules = [
                'thirdparties' => 'Third parties (Customers/Suppliers)',
                'contacts' => 'Contacts',
                'products' => 'Products / Services',
                'invoices' => 'Customer invoices',
                'orders' => 'Customer orders',
                'proposals' => 'Proposals (Commercial)',
                'tickets' => 'Tickets',
                'projects' => 'Projects',
                'tasks' => 'Tasks',
                'supplierorders' => 'Supplier orders',
                'supplierinvoices' => 'Supplier invoices',
            ];

            // Some solutions restrict read vs write by type; keep same list by default.
            return $modules;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error, ['exception_file' => $e->getFile(), 'exception_line' => $e->getLine()]);

            return ['error' => $error];
        }
    }

    /**
     * Field discovery.
     *
     * Dolibarr does not expose a universal "describe schema" endpoint across all modules.
     * To keep this connector self-contained, we build a best-effort field list by sampling one record.
     *
     * If you prefer static metadata (more reliable types/labels), replace this by:
     *   require 'lib/dolibarr/metadata.php';
     */
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        unset($param);
        $this->moduleFields = parent::get_module_fields($module, $type);
        $this->moduleFields = $this->ensureFieldDefinitionDefaults($this->moduleFields);

        try {
            if (!empty($this->moduleFields) && count($this->moduleFields) > 1) {
                return $this->ensureFieldDefinitionDefaults($this->moduleFields);
            }

            $metadataFields = $this->getMetadataFields($module);
            if (!empty($metadataFields)) {
                $this->moduleFields = array_merge($this->moduleFields, $metadataFields);

                return $this->ensureFieldDefinitionDefaults($this->moduleFields);
            }

            $sampleRecord = $this->getSampleRecord($module);
            if (empty($sampleRecord)) {
                return $this->ensureFieldDefinitionDefaults($this->moduleFields);
            }

            $this->moduleFields = array_merge($this->moduleFields, $this->buildFieldsFromSample($sampleRecord));

            return $this->ensureFieldDefinitionDefaults($this->moduleFields);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error, ['exception_file' => $e->getFile(), 'exception_line' => $e->getLine()]);

            return ['error' => $error];
        }
    }

    protected function getMetadataFields(string $module): array
    {
        $moduleFields = [];
        $metadataFile = __DIR__.'/lib/dolibarr/metadata.php';

        if (!file_exists($metadataFile)) {
            return [];
        }

        require $metadataFile;

        if (!empty($moduleFields[$module]) && is_array($moduleFields[$module])) {
            return $moduleFields[$module];
        }

        return [];
    }

    protected function getSampleRecord(string $module): ?array
    {
        $probe = $this->callApi($this->apiBase.$module, 'GET', [
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

    protected function buildFieldsFromSample(array $record): array
    {
        $discoveredFields = [];

        foreach ($record as $key => $value) {
            if (isset($this->moduleFields[$key])) {
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

    protected function guessFieldType($value): string
    {
        if (is_int($value)) {
            return 'int';
        }

        if (is_float($value)) {
            return 'float';
        }

        if (is_bool($value)) {
            return 'bool';
        }

        if (is_array($value)) {
            return 'array';
        }

        return 'string';
    }

    /**
     * Ensure each field definition contains keys expected by Myddleware mapping UI.
     * Some controllers expect 'required' to exist; default to 0 when missing.
     */
    protected function ensureFieldDefinitionDefaults(array $fields): array
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

    /**
     * Read records from Dolibarr.
     *
     * Supported modes:
     * - Specific record: $param['query']['id'] => GET /<module>/{id}
     * - List: GET /<module> with pagination and optional sqlfilters for incremental sync
     *
     * Output format: array of records (Myddleware standard)
     */
    public function readData($param): array
    {
        try {
            $result = [];
            $result['count'] = 0;
            $result['date_ref'] = $param['ruleParams']['datereference'];

            if (empty($param['limit'])) {
                $param['limit'] = $this->defaultLimit;
            }

            // Remove Myddleware's system fields
            $param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
            // Add required fields (including id)
            $param['fields'] = $this->addRequiredField($param['fields'], $param['module']);

            // Specific record by id
            if (!empty($param['query']) && !empty($param['query']['id'])) {
                $recordId = $param['query']['id'];
                $row = $this->callApi($this->apiBase.$param['module'].'/'.$recordId, 'GET');

                if (!is_array($row) || isset($row['error'])) {
                    return $result;
                }

                $row = $this->filterFields($row, $param['fields']);
                $row['id'] = $recordId;
                $result['values'][] = $row;
                $result['count'] = 1;

                return $result;
            }

            // List mode
            $page = 0; // Dolibarr pages start at 0
            $stop = false;

            do {
                $query = [
                    'limit' => $param['limit'],
                    'page' => $page,
                    'sortfield' => !empty($param['sortfield']) ? $param['sortfield'] : 't.rowid',
                    'sortorder' => !empty($param['sortorder']) ? $param['sortorder'] : 'ASC',
                ];

                // Incremental sync (best-effort): build sqlfilters using date_ref if query isn't forced
                if (empty($param['query'])) {
                    $sqlfilters = $this->buildSqlFiltersForDateRef($param);
                    if (!empty($sqlfilters)) {
                        $query['sqlfilters'] = $sqlfilters;
                    }
                }

                // Optional extra filters passed by rule
                if (!empty($param['query']) && is_array($param['query'])) {
                    foreach ($param['query'] as $k => $v) {
                        if ('id' === $k) {
                            continue;
                        }
                        $query[$k] = $v;
                    }
                }

                $rows = $this->callApi($this->apiBase.$param['module'], 'GET', $query);

                if (!is_array($rows) || isset($rows['error'])) {
                    // Stop on error to avoid infinite loops; Myddleware will log the exception upstream
                    $stop = true;
                    break;
                }

                $recordCount = 0;
                foreach ($rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    // Keep only mapped fields, but always include id
                    $one = $this->filterFields($row, $param['fields']);
                    if (!empty($row['id'])) {
                        $one['id'] = $row['id'];
                    } elseif (!empty($row['rowid'])) {
                        $one['id'] = $row['rowid'];
                    }
                    $result['values'][] = $one;
                    ++$recordCount;
                }

                $result['count'] += $recordCount;

                // Stop if we got less than limit (no more pages)
                if ($recordCount < (int) $param['limit']) {
                    $stop = true;
                } else {
                    ++$page;
                }
            } while (!$stop);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Dolibarr read error', ['error' => $e->getMessage(), 'exception_file' => $e->getFile(), 'exception_line' => $e->getLine()]);
            throw $e;
        }
    }

    /**
     * @throws \\Doctrine\\DBAL\Exception
     */
    public function createData($param): array
    {
        return $this->createOrUpdate('POST', $param);
    }

    /**
     * @throws \\Doctrine\\DBAL\Exception
     */
    public function updateData($param): array
    {
        return $this->createOrUpdate('PUT', $param);
    }

    /**
     * @throws \\Doctrine\\DBAL\Exception
     */
    public function deleteData($param): array
    {
        $result = [];
        foreach ($param['data'] as $idDoc => $data) {
            try {
                if (empty($data['target_id']) && empty($data['id'])) {
                    throw new \Exception('Missing target_id for deletion.');
                }
                $recordId = !empty($data['target_id']) ? $data['target_id'] : $data['id'];

                $resp = $this->callApi($this->apiBase.$param['module'].'/'.$recordId, 'DELETE');

                // Dolibarr often returns {"success":{"code":200,"message":"..."}}
                if (is_array($resp) && isset($resp['error'])) {
                    throw new \Exception($this->formatDolibarrError($resp));
                }

                $result[$idDoc] = ['id' => $recordId, 'error' => false];
            } catch (\Exception $e) {
                $result[$idDoc] = ['id' => '-1', 'error' => $e->getMessage()];
            }
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    /**
     * Shared create/update logic.
     *
     * - POST /<module> to create
     * - PUT  /<module>/{id} to update
     */
    protected function createOrUpdate(string $method, array $param): array
    {
        $result = [];

        foreach ($param['data'] as $idDoc => $data) {
            try {
                // Check control before create/update
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);

                // Determine record id for update
                $recordId = null;
                if ('POST' !== $method) {
                    $recordId = !empty($data['target_id']) ? $data['target_id'] : (!empty($data['id']) ? $data['id'] : null);
                    if (empty($recordId)) {
                        throw new \Exception('Missing target_id for update.');
                    }
                }

                // Remove Myddleware system fields
                unset($data['target_id']);

                // Send request
                $url = $this->apiBase.$param['module'].($recordId ? '/'.$recordId : '');
                $resp = $this->callApi($url, $method, $data);

                if (is_array($resp) && isset($resp['error'])) {
                    throw new \Exception($this->formatDolibarrError($resp));
                }

                // Create often returns integer id, update often returns object
                $newId = null;
                if (is_int($resp) || (is_string($resp) && ctype_digit($resp))) {
                    $newId = (string) $resp;
                } elseif (is_array($resp)) {
                    if (!empty($resp['id'])) {
                        $newId = (string) $resp['id'];
                    } elseif (!empty($resp['rowid'])) {
                        $newId = (string) $resp['rowid'];
                    }
                }

                if (empty($newId)) {
                    // Some endpoints return 200 with a message; fall back to update id.
                    $newId = $recordId ?: '-1';
                }

                $result[$idDoc] = ['id' => $newId, 'error' => false];
            } catch (\Exception $e) {
                $result[$idDoc] = ['id' => '-1', 'error' => $e->getMessage()];
            }

            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    /**
     * Low-level HTTP call helper.
     *
     * - For GET with $args, parameters are appended to query string.
     * - For POST/PUT/PATCH/DELETE, payload is JSON.
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function callApi(string $url, string $method = 'GET', array $args = [], int $timeout = 60)
    {
        $headers = [
            'Accept' => 'application/json',
            'DOLAPIKEY' => $this->apiKey,
        ];
        if (!empty($this->apiEntity)) {
            $headers['DOLAPIENTITY'] = $this->apiEntity;
        }

        $method = strtoupper($method);
        $requestOptions = [
            'headers' => $headers,
            'timeout' => $timeout,
            'verify_peer' => $this->verify_ssl,
            'verify_host' => $this->verify_ssl,
        ];

        if ('GET' === $method && !empty($args)) {
            $url = sprintf('%s?%s', $url, http_build_query($args));
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $headers['Content-Type'] = 'application/json';
            $requestOptions['headers'] = $headers;

            if (!empty($args)) {
                $requestOptions['body'] = json_encode($args);
            } elseif ('POST' === $method) {
                $requestOptions['body'] = '{}';
            }
        }

        $client = HttpClient::create();
        $response = $client->request($method, $url, $requestOptions);
        $httpCode = $response->getStatusCode();
        $rawResponse = $response->getContent(false);

        $trimmedResponse = trim($rawResponse);
        if ('' !== $trimmedResponse && ctype_digit($trimmedResponse)) {
            return (int) $trimmedResponse;
        }

        $decoded = json_decode($rawResponse, true);

        if (null === $decoded && JSON_ERROR_NONE !== json_last_error()) {
            return [
                'error' => [
                    'code' => $httpCode,
                    'message' => 'Invalid JSON response: '.json_last_error_msg(),
                    'raw' => $rawResponse,
                ],
            ];
        }

        if ($httpCode >= 400 && (empty($decoded) || !isset($decoded['error']))) {
            return [
                'error' => [
                    'code' => $httpCode,
                    'message' => 'HTTP error '.$httpCode,
                    'raw' => $decoded ?: $rawResponse,
                ],
            ];
        }

        return $decoded;
    }

    protected function normalizeApiBase(string $url): string
    {
        $url = trim($url);

        // If user provides the UI root, we try to append /api/index.php/
        // If user provides .../api/index.php, we normalize with trailing slash.
        if (preg_match('#/api/index\\.php/?$#', $url)) {
            $url = rtrim($url, '/').'/';
        } elseif (preg_match('#/api/index\\.php/#', $url)) {
            // already contains the api entrypoint somewhere; just ensure it ends with /.
            $pos = strpos($url, '/api/index.php/');
            $url = substr($url, 0, $pos + strlen('/api/index.php/'));
        } else {
            $url = rtrim($url, '/').'/api/index.php/';
        }

        return $url;
    }

    protected function filterFields(array $row, array $fields): array
    {
        if (empty($fields)) {
            return $row;
        }
        $out = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $row)) {
                $out[$f] = $row[$f];
            }
        }
        // Always keep id if present
        if (!empty($row['id']) && !isset($out['id'])) {
            $out['id'] = $row['id'];
        } elseif (!empty($row['rowid']) && !isset($out['id'])) {
            $out['id'] = $row['rowid'];
        }

        return $out;
    }

    protected function buildSqlFiltersForDateRef(array $param): ?string
    {
        if (empty($param['ruleParams']['datereference'])) {
            return null;
        }

        $module = $param['module'];
        $sqlField = $this->dateRefFieldMap[$module] ?? null;
        if (empty($sqlField)) {
            return null;
        }

        // Myddleware date_ref is usually a datetime string; Dolibarr doc confirms ISO date format is supported.
        // To stay compatible with most Dolibarr endpoints, we reduce to YYYY-MM-DD.
        $date = $param['ruleParams']['datereference'];
        try {
            $dateTime = new \DateTime($date);
            $iso = $dateTime->format('Y-m-d');
        } catch (\Exception $e) {
            // If parsing fails, do not apply filter.
            return null;
        }

        // Example from Dolibarr wiki: (t.nom:=:'Acme Inc')
        // Here: (t.tms:>=:'2026-03-03')
        return '('.$sqlField.":>=:'".$iso."')";
    }

    protected function formatDolibarrError(array $resp): string
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
}
