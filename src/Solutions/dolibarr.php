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
    protected array $dateRefSqlFieldByModule = [
        // most endpoints use table alias "t"
        'thirdparties' => 't.tms',
        'contacts'     => 't.tms',
        'products'     => 't.tms',
        'invoices'     => 't.tms',
        'orders'       => 't.tms',
        'proposals'    => 't.tms',
        'tickets'      => 't.tms',
        'projects'     => 't.tms',
        'tasks'        => 't.tms',
    ];

    public function getFieldsLogin(): array
    {
        return [
            [
                'name'  => 'url',
                'type'  => TextType::class,
                'label' => 'solution.fields.url',
            ],
            [
                'name'  => 'apikey',
                'type'  => PasswordType::class,
                'label' => 'solution.fields.apikey',
            ],
            [
                'name'        => 'entity',
                'type'        => TextType::class,
                'label'       => 'solution.fields.entity',
                'required'    => false,
                'help'        => 'Optional (multi-company): send as HTTP header DOLAPIENTITY',
            ],
            [
                'name'        => 'verify_ssl',
                'type'        => CheckboxType::class,
                'label'       => 'solution.fields.verify_ssl',
                'required'    => false,
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
            $ok = false;

            $status = $this->callApi($this->apiBase.'status', 'GET');
            if (is_array($status) && !isset($status['error'])) {
                $ok = true;
            } else {
                $probe = $this->callApi($this->apiBase.'thirdparties', 'GET', [
                    'limit' => 1,
                    'page' => 0,
                    'sortfield' => 't.rowid',
                    'sortorder' => 'ASC',
                ]);
                if (is_array($probe) && !isset($probe['error'])) {
                    $ok = true;
                }
            }

            if (!$ok) {
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
                'contacts'     => 'Contacts',
                'products'     => 'Products / Services',
                'invoices'     => 'Customer invoices',
                'orders'       => 'Customer orders',
                'proposals'    => 'Proposals (Commercial)',
                'tickets'      => 'Tickets',
                'projects'     => 'Projects',
                'tasks'        => 'Tasks',
                'supplierorders' => 'Supplier orders',
                'supplierinvoices' => 'Supplier invoices',
            ];

            // Some solutions restrict read vs write by type; keep same list by default.
            return $modules;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
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
        $this->moduleFields = parent::get_module_fields($module, $type);

        $this->moduleFields = $this->ensureFieldDefinitionDefaults($this->moduleFields);

        try {
            // Already cached?
            if (!empty($this->moduleFields) && count($this->moduleFields) > 1) {
                return $this->ensureFieldDefinitionDefaults($this->moduleFields);
            }

            // Probe 1 record to infer keys
            $probe = $this->callApi($this->apiBase.$module, 'GET', [
                'limit' => 1,
                'page' => 0,
                'sortfield' => 't.rowid',
                'sortorder' => 'ASC',
            ]);

            $record = null;
            if (is_array($probe)) {
                // list endpoints usually return an array of objects
                if (!empty($probe[0]) && is_array($probe[0])) {
                    $record = $probe[0];
                } elseif (!empty($probe['data'][0]) && is_array($probe['data'][0])) {
                    // some modules might wrap in "data"
                    $record = $probe['data'][0];
                }
            }

            if (empty($record)) {
                // If module is empty, keep only Myddleware system fields; user can still map target fields manually.
                return $this->ensureFieldDefinitionDefaults($this->moduleFields);
            }

            foreach ($record as $key => $value) {
                if (isset($this->moduleFields[$key])) {
                    continue;
                }

                $typeGuess = 'string';
                if (is_int($value)) {
                    $typeGuess = 'int';
                } elseif (is_float($value)) {
                    $typeGuess = 'float';
                } elseif (is_bool($value)) {
                    $typeGuess = 'bool';
                } elseif (is_array($value)) {
                    $typeGuess = 'array';
                }

                $this->moduleFields[$key] = [
                    'label' => $key,
                    'type'  => $typeGuess,
                    'required' => 0,
                ];
            }

            return $this->ensureFieldDefinitionDefaults($this->moduleFields);
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
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
                $id = $param['query']['id'];
                $row = $this->callApi($this->apiBase.$param['module'].'/'.$id, 'GET');

                if (!is_array($row) || isset($row['error'])) {
                    return $result;
                }

                $row = $this->filterFields($row, $param['fields']);
                $row['id'] = $id;
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
                    'page'  => $page,
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
                        if ($k === 'id') {
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

                $nb = 0;
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
                    $nb++;
                }

                $result['count'] += $nb;

                // Stop if we got less than limit (no more pages)
                if ($nb < (int) $param['limit']) {
                    $stop = true;
                } else {
                    $page++;
                }
            } while (!$stop);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
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
                $id = !empty($data['target_id']) ? $data['target_id'] : $data['id'];

                $resp = $this->callApi($this->apiBase.$param['module'].'/'.$id, 'DELETE');

                // Dolibarr often returns {"success":{"code":200,"message":"..."}}
                if (is_array($resp) && isset($resp['error'])) {
                    throw new \Exception($this->formatDolibarrError($resp));
                }

                $result[$idDoc] = ['id' => $id, 'error' => false];
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
                $id = null;
                if ($method !== 'POST') {
                    $id = !empty($data['target_id']) ? $data['target_id'] : (!empty($data['id']) ? $data['id'] : null);
                    if (empty($id)) {
                        throw new \Exception('Missing target_id for update.');
                    }
                }

                // Remove Myddleware system fields
                unset($data['target_id']);

                // Send request
                $url = $this->apiBase.$param['module'].($id ? '/'.$id : '');
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
                    $newId = $id ?: '-1';
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
     * Low-level HTTP call helper (curl).
     *
     * - For GET with $args, parameters are appended to query string.
     * - For POST/PUT, payload is JSON.
     */
    protected function callApi(string $url, string $method = 'GET', array $args = [], int $timeout = 60)
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new \Exception('curl extension is missing!');
        }

        $ch = curl_init();

        $headers = [
            'Accept: application/json',
            'DOLAPIKEY: '.$this->apiKey,
        ];
        if (!empty($this->apiEntity)) {
            $headers[] = 'DOLAPIENTITY: '.$this->apiEntity;
        }

        $method = strtoupper($method);

        if ($method === 'GET' && !empty($args)) {
            $url = sprintf('%s?%s', $url, http_build_query($args));
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            // Dolibarr can require JSON content-type even for some POST sub-actions.
            $headers[] = 'Content-Type: application/json';

            if (!empty($args) && $method !== 'GET') {
                $jsonData = json_encode($args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            } elseif ($method === 'POST') {
                // Some endpoints reject empty body for POST; use empty JSON object.
                curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // SSL verification (default true)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verify_ssl ? 2 : 0);

        $raw = curl_exec($ch);

        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \Exception('cURL error: '.$err);
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Some Dolibarr endpoints return plain integer (id) for create
        $trim = trim($raw);
        if ($trim !== '' && ctype_digit($trim)) {
            return (int) $trim;
        }

        $decoded = json_decode($raw, true);

        // If JSON decode fails, return structured error
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return [
                'error' => [
                    'code' => $httpCode,
                    'message' => 'Invalid JSON response: '.json_last_error_msg(),
                    'raw' => $raw,
                ],
            ];
        }

        // Normalize HTTP errors
        if ($httpCode >= 400 && (empty($decoded) || !isset($decoded['error']))) {
            return [
                'error' => [
                    'code' => $httpCode,
                    'message' => 'HTTP error '.$httpCode,
                    'raw' => $decoded ?: $raw,
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
        $sqlField = $this->dateRefSqlFieldByModule[$module] ?? null;
        if (empty($sqlField)) {
            return null;
        }

        // Myddleware date_ref is usually a datetime string; Dolibarr doc confirms ISO date format is supported.
        // To stay compatible with most Dolibarr endpoints, we reduce to YYYY-MM-DD.
        $date = $param['ruleParams']['datereference'];
        try {
            $dt = new \DateTime($date);
            $iso = $dt->format('Y-m-d');
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
        return trim(($code !== '' ? '['.$code.'] ' : '').$message);
    }
}
