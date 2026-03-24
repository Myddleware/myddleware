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

use App\Solutions\Support\DolibarrApiHelper;
use App\Solutions\Support\DolibarrConnectorHelper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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

    protected array $metadataFields = [
        'thirdparties' => [
            'id' => ['label' => 'ID', 'type' => 'int', 'required' => 0],
            'rowid' => ['label' => 'Row ID', 'type' => 'int', 'required' => 0],
            'ref' => ['label' => 'Reference', 'type' => 'string', 'required' => 0],
            'nom' => ['label' => 'Name', 'type' => 'string', 'required' => 0],
            'email' => ['label' => 'Email', 'type' => 'string', 'required' => 0],
            'phone' => ['label' => 'Phone', 'type' => 'string', 'required' => 0],
            'town' => ['label' => 'Town', 'type' => 'string', 'required' => 0],
            'zip' => ['label' => 'Zip', 'type' => 'string', 'required' => 0],
            'status' => ['label' => 'Status', 'type' => 'int', 'required' => 0],
            'tms' => ['label' => 'Modified date', 'type' => 'datetime', 'required' => 0],
        ],
        'contacts' => [
            'id' => ['label' => 'ID', 'type' => 'int', 'required' => 0],
            'lastname' => ['label' => 'Last name', 'type' => 'string', 'required' => 0],
            'firstname' => ['label' => 'First name', 'type' => 'string', 'required' => 0],
            'email' => ['label' => 'Email', 'type' => 'string', 'required' => 0],
            'phone_mobile' => ['label' => 'Mobile phone', 'type' => 'string', 'required' => 0],
            'phone_pro' => ['label' => 'Business phone', 'type' => 'string', 'required' => 0],
            'socid' => ['label' => 'Third party ID', 'type' => 'int', 'required' => 0],
            'poste' => ['label' => 'Job title', 'type' => 'string', 'required' => 0],
            'statut' => ['label' => 'Status', 'type' => 'int', 'required' => 0],
            'tms' => ['label' => 'Modified date', 'type' => 'datetime', 'required' => 0],
        ],
        'products' => [
            'id' => ['label' => 'ID', 'type' => 'int', 'required' => 0],
            'ref' => ['label' => 'Reference', 'type' => 'string', 'required' => 0],
            'label' => ['label' => 'Label', 'type' => 'string', 'required' => 0],
            'description' => ['label' => 'Description', 'type' => 'string', 'required' => 0],
            'price' => ['label' => 'Price', 'type' => 'float', 'required' => 0],
            'price_ttc' => ['label' => 'Price incl. tax', 'type' => 'float', 'required' => 0],
            'status' => ['label' => 'Status', 'type' => 'int', 'required' => 0],
            'tms' => ['label' => 'Modified date', 'type' => 'datetime', 'required' => 0],
        ],
        'invoices' => [
            'id' => ['label' => 'ID', 'type' => 'int', 'required' => 0],
            'ref' => ['label' => 'Reference', 'type' => 'string', 'required' => 0],
            'socid' => ['label' => 'Third party ID', 'type' => 'int', 'required' => 0],
            'date' => ['label' => 'Invoice date', 'type' => 'date', 'required' => 0],
            'total_ht' => ['label' => 'Total excl. tax', 'type' => 'float', 'required' => 0],
            'total_ttc' => ['label' => 'Total incl. tax', 'type' => 'float', 'required' => 0],
            'statut' => ['label' => 'Status', 'type' => 'int', 'required' => 0],
            'tms' => ['label' => 'Modified date', 'type' => 'datetime', 'required' => 0],
        ],
        'orders' => [
            'id' => ['label' => 'ID', 'type' => 'int', 'required' => 0],
            'ref' => ['label' => 'Reference', 'type' => 'string', 'required' => 0],
            'socid' => ['label' => 'Third party ID', 'type' => 'int', 'required' => 0],
            'date_commande' => ['label' => 'Order date', 'type' => 'date', 'required' => 0],
            'total_ht' => ['label' => 'Total excl. tax', 'type' => 'float', 'required' => 0],
            'total_ttc' => ['label' => 'Total incl. tax', 'type' => 'float', 'required' => 0],
            'statut' => ['label' => 'Status', 'type' => 'int', 'required' => 0],
            'tms' => ['label' => 'Modified date', 'type' => 'datetime', 'required' => 0],
        ],
        'proposals' => [
            'id' => ['label' => 'ID', 'type' => 'int', 'required' => 0],
            'ref' => ['label' => 'Reference', 'type' => 'string', 'required' => 0],
            'socid' => ['label' => 'Third party ID', 'type' => 'int', 'required' => 0],
            'datep' => ['label' => 'Proposal date', 'type' => 'date', 'required' => 0],
            'fin_validite' => ['label' => 'Validity end', 'type' => 'date', 'required' => 0],
            'total_ht' => ['label' => 'Total excl. tax', 'type' => 'float', 'required' => 0],
            'total_ttc' => ['label' => 'Total incl. tax', 'type' => 'float', 'required' => 0],
            'statut' => ['label' => 'Status', 'type' => 'int', 'required' => 0],
            'tms' => ['label' => 'Modified date', 'type' => 'datetime', 'required' => 0],
        ],
        'tickets' => [
            'id' => ['label' => 'ID', 'type' => 'int', 'required' => 0],
            'ref' => ['label' => 'Reference', 'type' => 'string', 'required' => 0],
            'subject' => ['label' => 'Subject', 'type' => 'string', 'required' => 0],
            'message' => ['label' => 'Message', 'type' => 'string', 'required' => 0],
            'status' => ['label' => 'Status', 'type' => 'int', 'required' => 0],
            'tms' => ['label' => 'Modified date', 'type' => 'datetime', 'required' => 0],
        ],
        'projects' => [
            'id' => ['label' => 'ID', 'type' => 'int', 'required' => 0],
            'ref' => ['label' => 'Reference', 'type' => 'string', 'required' => 0],
            'title' => ['label' => 'Title', 'type' => 'string', 'required' => 0],
            'description' => ['label' => 'Description', 'type' => 'string', 'required' => 0],
            'date_start' => ['label' => 'Start date', 'type' => 'date', 'required' => 0],
            'date_end' => ['label' => 'End date', 'type' => 'date', 'required' => 0],
            'tms' => ['label' => 'Modified date', 'type' => 'datetime', 'required' => 0],
        ],
        'tasks' => [
            'id' => ['label' => 'ID', 'type' => 'int', 'required' => 0],
            'ref' => ['label' => 'Reference', 'type' => 'string', 'required' => 0],
            'label' => ['label' => 'Label', 'type' => 'string', 'required' => 0],
            'description' => ['label' => 'Description', 'type' => 'string', 'required' => 0],
            'fk_project' => ['label' => 'Project ID', 'type' => 'int', 'required' => 0],
            'dateo' => ['label' => 'Start date', 'type' => 'date', 'required' => 0],
            'datee' => ['label' => 'End date', 'type' => 'date', 'required' => 0],
            'progress' => ['label' => 'Progress', 'type' => 'int', 'required' => 0],
            'tms' => ['label' => 'Modified date', 'type' => 'datetime', 'required' => 0],
        ],
        'supplierorders' => [
            'id' => ['label' => 'ID', 'type' => 'int', 'required' => 0],
            'ref' => ['label' => 'Reference', 'type' => 'string', 'required' => 0],
            'socid' => ['label' => 'Supplier ID', 'type' => 'int', 'required' => 0],
            'date_commande' => ['label' => 'Order date', 'type' => 'date', 'required' => 0],
            'total_ht' => ['label' => 'Total excl. tax', 'type' => 'float', 'required' => 0],
            'total_ttc' => ['label' => 'Total incl. tax', 'type' => 'float', 'required' => 0],
            'statut' => ['label' => 'Status', 'type' => 'int', 'required' => 0],
            'tms' => ['label' => 'Modified date', 'type' => 'datetime', 'required' => 0],
        ],
        'supplierinvoices' => [
            'id' => ['label' => 'ID', 'type' => 'int', 'required' => 0],
            'ref' => ['label' => 'Reference', 'type' => 'string', 'required' => 0],
            'socid' => ['label' => 'Supplier ID', 'type' => 'int', 'required' => 0],
            'date' => ['label' => 'Invoice date', 'type' => 'date', 'required' => 0],
            'total_ht' => ['label' => 'Total excl. tax', 'type' => 'float', 'required' => 0],
            'total_ttc' => ['label' => 'Total incl. tax', 'type' => 'float', 'required' => 0],
            'statut' => ['label' => 'Status', 'type' => 'int', 'required' => 0],
            'tms' => ['label' => 'Modified date', 'type' => 'datetime', 'required' => 0],
        ],
    ];

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
            $this->verify_ssl = !array_key_exists('verify_ssl', $this->paramConnexion) || !empty($this->paramConnexion['verify_ssl']);
            $this->apiBase = $this->normalizeApiBase($this->paramConnexion['url']);

            if ($this->hasSuccessfulLoginProbe()) {
                $this->connexion_valide = true;

                return;
            }

            throw new \Exception('Login error. Check base URL, REST API module enabled, and API key permissions.');
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    protected function hasSuccessfulLoginProbe(): bool
    {
        $status = $this->callApi($this->apiBase.'status', 'GET');
        if (is_array($status) && !isset($status['error'])) {
            return true;
        }

        $probe = $this->callApi($this->apiBase.'thirdparties', 'GET', [
            'limit' => 1,
            'page' => 0,
            'sortfield' => 't.rowid',
            'sortorder' => 'ASC',
        ]);

        return is_array($probe) && !isset($probe['error']);
    }

    /**
     * Curated list of common Dolibarr modules.
     * You can expand this list as needed; the actual availability depends on enabled Dolibarr modules and user permissions.
     */
    public function get_modules($type = 'source')
    {
        unset($type);

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
        return (new DolibarrConnectorHelper())->getMetadataFields($this->metadataFields, $module);
    }

    protected function getSampleRecord(string $module): ?array
    {
        return (new DolibarrConnectorHelper())->getSampleRecord(fn (string $url, string $method, array $args) => $this->callApi($url, $method, $args), $this->apiBase, $module);
    }

    protected function buildFieldsFromSample(array $record): array
    {
        return (new DolibarrConnectorHelper())->buildFieldsFromSample($record, $this->moduleFields);
    }

    /**
     * Ensure each field definition contains keys expected by Myddleware mapping UI.
     * Some controllers expect 'required' to exist; default to 0 when missing.
     */
    protected function ensureFieldDefinitionDefaults(array $fields): array
    {
        return (new DolibarrConnectorHelper())->ensureFieldDefinitionDefaults($fields);
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
            $normalizedParam = $this->prepareReadParameters($param);
            $result = $this->createEmptyReadResult($normalizedParam);

            if ($this->hasSpecificRecordQuery($normalizedParam)) {
                return $this->readSpecificRecord($normalizedParam, $result);
            }

            return $this->readPagedRecords($normalizedParam, $result);
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
                $writeData = $this->prepareWriteData($method, $param, $data, $idDoc);
                $response = $this->sendWriteRequest($method, $param['module'], $writeData['recordId'], $writeData['payload']);
                $newId = $this->resolveWriteResultId($response, $writeData['recordId']);
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
        return (new DolibarrApiHelper())->callApi(
            fn (string $requestMethod, array $requestArgs, int $requestTimeout) => $this->buildRequestOptions($requestMethod, $requestArgs, $requestTimeout),
            fn (int $statusCode, string $content) => $this->normalizeApiResponse($statusCode, $content),
            $url,
            $method,
            $args,
            $timeout,
        );
    }

    protected function normalizeApiBase(string $url): string
    {
        return (new DolibarrConnectorHelper())->normalizeApiBase($url);
    }

    protected function filterFields(array $row, array $fields): array
    {
        return (new DolibarrConnectorHelper())->filterFields($row, $fields);
    }

    protected function buildSqlFiltersForDateRef(array $param): ?string
    {
        return (new DolibarrConnectorHelper())->buildSqlFiltersForDateRef($param, $this->dateRefFieldMap);
    }

    protected function formatDolibarrError(array $resp): string
    {
        return (new DolibarrConnectorHelper())->formatDolibarrError($resp);
    }
}
