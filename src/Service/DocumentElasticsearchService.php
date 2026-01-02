<?php

namespace App\Service;

use Elastica\Client;
use Elastica\Index;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Range;
use Elastica\Query\Term;
use Elastica\Query\Terms;
use Elastica\Query\Wildcard;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

class DocumentElasticsearchService
{
    private ?Client $client = null;
    private ?Index $index = null;
    private LoggerInterface $logger;
    private bool $configured = false;

    // Cache for availability check to avoid repeated HTTP requests
    private ?bool $availabilityCache = null;
    private ?float $availabilityCacheTime = null;
    private const AVAILABILITY_CACHE_TTL = 30.0; // Cache availability for 30 seconds
    private const MAX_RESULT_WINDOW = 1000000;

    public function __construct(
        ?string $elasticsearchUrl,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;

        // Only initialize if URL is provided and valid
        if (empty($elasticsearchUrl)) {
            $this->logger->info('Elasticsearch URL not configured, service disabled');
            return;
        }

        try {
            // Parse URL components - handle special characters in password
            $parsedUrl = parse_url($elasticsearchUrl);

            $scheme = $parsedUrl['scheme'] ?? 'http';
            $host = $parsedUrl['host'] ?? null;
            $port = $parsedUrl['port'] ?? 9200;
            $user = isset($parsedUrl['user']) ? urldecode($parsedUrl['user']) : null;
            $pass = isset($parsedUrl['pass']) ? urldecode($parsedUrl['pass']) : null;

            if (empty($host)) {
                $this->logger->warning('Invalid Elasticsearch URL: ' . $elasticsearchUrl);
                return;
            }

            // Build host URL for Elastica 8.x
            $hostUrl = $scheme . '://' . $host . ':' . $port;

            // Build client configuration for Elastica 8.x
            $config = [
                'hosts' => [$hostUrl],
            ];

            // Handle authentication
            if (!empty($user) && !empty($pass)) {
                $config['username'] = $user;
                $config['password'] = $pass;
            }

            // Handle HTTPS with self-signed certificates (for development)
            // Create a custom Symfony HTTP client with SSL verification disabled
            if ($scheme === 'https') {
                $httpClient = HttpClient::create([
                    'verify_peer' => false,
                    'verify_host' => false,
                ]);
                $psr18Client = new Psr18Client($httpClient);
                $config['transport_config'] = [
                    'http_client' => $psr18Client,
                ];
            }

            $this->logger->info('Connecting to Elasticsearch at ' . $hostUrl . ' with auth: ' . (!empty($user) ? 'yes' : 'no'));

            $this->client = new Client($config);

            $this->index = $this->client->getIndex('document');
            $this->configured = true;
            $this->logger->info('Elasticsearch service initialized successfully');
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize Elasticsearch client: ' . $e->getMessage());
        }
    }

    /**
     * Check if Elasticsearch is configured
     */
    public function isConfigured(): bool
    {
        return $this->configured;
    }

    /**
     * Check if Elasticsearch is available (with caching to avoid repeated HTTP requests)
     *
     * @param bool $useCache Whether to use cached result (default: true)
     */
    public function isAvailable(bool $useCache = true): bool
    {
        if (!$this->configured || $this->client === null) {
            return false;
        }

        // Check cache first
        if ($useCache && $this->availabilityCache !== null && $this->availabilityCacheTime !== null) {
            $elapsed = microtime(true) - $this->availabilityCacheTime;
            if ($elapsed < self::AVAILABILITY_CACHE_TTL) {
                return $this->availabilityCache;
            }
        }

        // Perform actual check
        try {
            $isAvailable = $this->client->getStatus()->getResponse()->isOk();
            $this->availabilityCache = $isAvailable;
            $this->availabilityCacheTime = microtime(true);
            return $isAvailable;
        } catch (\Exception $e) {
            $this->logger->warning('Elasticsearch is not available: ' . $e->getMessage());
            $this->availabilityCache = false;
            $this->availabilityCacheTime = microtime(true);
            return false;
        }
    }

    /**
     * Clear the availability cache (useful when you want to force a fresh check)
     */
    public function clearAvailabilityCache(): void
    {
        $this->availabilityCache = null;
        $this->availabilityCacheTime = null;
    }

    /**
     * Search documents using Elasticsearch
     * Returns an array with 'hits' (results) and 'total' (count)
     */
    public function searchDocuments(array $filters, int $from = 0, int $size = 25): array
    {
        if (!$this->configured || $this->index === null) {
            throw new \RuntimeException('Elasticsearch is not configured');
        }

        if (!$this->isAvailable()) {
            throw new \RuntimeException('Elasticsearch is not available');
        }

        $boolQuery = new BoolQuery();

        $boolQuery->addMust(new Term(['deleted' => false]));

        // Source content search (using wildcard on keyword subfield for LIKE behavior)
        if (!empty($filters['source_content'])) {
            $boolQuery->addMust(new Wildcard('source_content.keyword', '*' . $filters['source_content'] . '*'));
        }

        // Target content search (using wildcard on keyword subfield for LIKE behavior)
        if (!empty($filters['target_content'])) {
            $boolQuery->addMust(new Wildcard('target_content.keyword', '*' . $filters['target_content'] . '*'));
        }

        // Rule name filter
        if (!empty($filters['rule'])) {
            $boolQuery->addMust(new Term(['rule_name' => $filters['rule']]));
        }

        // Status filter
        if (!empty($filters['status'])) {
            $boolQuery->addMust(new Term(['status' => $filters['status']]));
        }

        // Global status filter
        if (!empty($filters['gblstatus'])) {
            $boolQuery->addMust(new Term(['global_status' => $filters['gblstatus']]));
        }

        // Multiple global statuses
        if (!empty($filters['customWhere']['gblstatus'])) {
            $boolQuery->addMust(new Terms('global_status', $filters['customWhere']['gblstatus']));
        }

        // Document type filter
        if (!empty($filters['type'])) {
            $boolQuery->addMust(new Term(['type' => $filters['type']]));
        }

        // Source ID filter (using wildcard for LIKE behavior)
        if (!empty($filters['source_id'])) {
            $boolQuery->addMust(new Wildcard('source_id', '*' . $filters['source_id'] . '*'));
        }

        // Target ID filter (using wildcard for LIKE behavior)
        if (!empty($filters['target_id'])) {
            $boolQuery->addMust(new Wildcard('target_id', '*' . $filters['target_id'] . '*'));
        }

        // Module source filter
        if (!empty($filters['module_source'])) {
            $boolQuery->addMust(new Term(['module_source' => $filters['module_source']]));
        }

        // Module target filter
        if (!empty($filters['module_target'])) {
            $boolQuery->addMust(new Term(['module_target' => $filters['module_target']]));
        }

        // Date range filters
        if (!empty($filters['date_modif_start']) || !empty($filters['date_modif_end'])) {
            $rangeParams = [];
            if (!empty($filters['date_modif_start'])) {
                $rangeParams['gte'] = str_replace(',', '', $filters['date_modif_start']);
            }
            if (!empty($filters['date_modif_end'])) {
                $rangeParams['lte'] = $filters['date_modif_end'];
            }
            $boolQuery->addMust(new Range('date_modified', $rangeParams));
        }

        // Reference (source_date_modified) filter
        if (!empty($filters['reference'])) {
            $operator = isset($filters['operators']['reference']) ? 'lte' : 'gte';
            $boolQuery->addMust(new Range('source_date_modified', [$operator => $filters['reference']]));
        }

        $query = new Query($boolQuery);

        // Set pagination
        $query->setFrom($from);
        $query->setSize($size);

        // Track total hits accurately (default only tracks up to 10,000)
        // This is required for proper pagination with large result sets
        $query->setTrackTotalHits(true);

        // Set sorting
        $sortField = 'date_modified';
        $sortOrder = 'desc';

        if (!empty($filters['sort_field'])) {
            // Map field names if needed
            $sortField = $filters['sort_field'];
        }

        if (!empty($filters['sort_order'])) {
            $sortOrder = strtolower($filters['sort_order']);
        }

        $query->setSort([
            $sortField => ['order' => $sortOrder]
        ]);

        try {
            $resultSet = $this->index->search($query);

            $hits = [];
            foreach ($resultSet as $result) {
                $hits[] = $result->getData();
            }

            return [
                'hits' => $hits,
                'total' => $resultSet->getTotalHits(),
            ];
        } catch (\Exception $e) {
            $this->logger->error('Elasticsearch search error: ' . $e->getMessage());
            throw new \RuntimeException('Search failed: ' . $e->getMessage());
        }
    }

    /**
     * Index a single document
     *
     * @param array $documentData Document data to index
     * @param bool $skipAvailabilityCheck Skip availability check if already verified by caller
     * @param bool $refresh Whether to refresh the index (expensive, avoid for real-time sync)
     */
    public function indexDocument(array $documentData, bool $skipAvailabilityCheck = false, bool $refresh = false): void
    {
        if (!$skipAvailabilityCheck && !$this->isAvailable()) {
            $this->logger->warning('Cannot index document: Elasticsearch is not available');
            return;
        }

        try {
            $document = new \Elastica\Document($documentData['id'], $documentData);
            $this->index->addDocument($document);
            if ($refresh) {
                $this->index->refresh();
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to index document: ' . $e->getMessage());
        }
    }

    /**
     * Bulk index documents
     *
     * @param array $documents Documents to index
     * @param bool $refresh Whether to refresh the index after bulk operation (default: true for bulk)
     */
    public function bulkIndexDocuments(array $documents, bool $refresh = true): void
    {
        if (!$this->isAvailable()) {
            $this->logger->warning('Cannot bulk index: Elasticsearch is not available');
            return;
        }

        try {
            $elasticaDocuments = [];
            foreach ($documents as $doc) {
                $elasticaDocuments[] = new \Elastica\Document($doc['id'], $doc);
            }

            $this->index->addDocuments($elasticaDocuments);
            if ($refresh) {
                $this->index->refresh();
            }

            $this->logger->info(sprintf('Indexed %d documents to Elasticsearch', count($documents)));
        } catch (\Exception $e) {
            $this->logger->error('Failed to bulk index documents: ' . $e->getMessage());
        }
    }

    /**
     * Delete a document from the index
     *
     * @param string $documentId Document ID to delete
     * @param bool $skipAvailabilityCheck Skip availability check if already verified by caller
     * @param bool $refresh Whether to refresh the index (expensive, avoid for real-time sync)
     */
    public function deleteDocument(string $documentId, bool $skipAvailabilityCheck = false, bool $refresh = false): void
    {
        if (!$skipAvailabilityCheck && !$this->isAvailable()) {
            return;
        }

        try {
            $this->index->deleteById($documentId);
            if ($refresh) {
                $this->index->refresh();
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete document from index: ' . $e->getMessage());
        }
    }

    /**
     * Delete the index if it exists
     */
    public function deleteIndex(): bool
    {
        if (!$this->configured || $this->index === null) {
            throw new \RuntimeException('Elasticsearch is not configured');
        }

        if (!$this->index->exists()) {
            return false;
        }

        $this->index->delete();
        $this->logger->info('Deleted Elasticsearch index');
        return true;
    }

    /**
     * Create or update index with mapping
     */
    public function createIndex(): void
    {
        if ($this->index->exists()) {
            $this->logger->info('Index already exists');
            return;
        }

        $mapping = [
            'properties' => [
                'id' => ['type' => 'keyword'],
                'rule_id' => ['type' => 'keyword'],
                'rule_name' => ['type' => 'keyword'],
                'status' => ['type' => 'keyword'],
                'global_status' => ['type' => 'keyword'],
                'source_id' => ['type' => 'keyword'],
                'target_id' => ['type' => 'keyword'],
                'date_created' => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
                'date_modified' => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
                'source_date_modified' => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
                'type' => ['type' => 'keyword'],
                'mode' => ['type' => 'keyword'],
                'attempt' => ['type' => 'integer'],
                'module_source' => ['type' => 'keyword'],
                'module_target' => ['type' => 'keyword'],
                'source_content' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                    'fields' => [
                        'keyword' => ['type' => 'keyword', 'ignore_above' => 32766]
                    ]
                ],
                'target_content' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                    'fields' => [
                        'keyword' => ['type' => 'keyword', 'ignore_above' => 32766]
                    ]
                ],
                'deleted' => ['type' => 'boolean'],
            ]
        ];

        // Settings to allow deep pagination (up to 1,000,000 results)
        // Default Elasticsearch max_result_window is 10,000
        $settings = [
            'index' => [
                'max_result_window' => self::MAX_RESULT_WINDOW,
            ]
        ];

        $this->index->create([
            'mappings' => $mapping,
            'settings' => $settings,
        ]);
        $this->logger->info('Created Elasticsearch index with mapping and max_result_window=' . self::MAX_RESULT_WINDOW);
    }

    /**
     * Update max_result_window setting on an existing index
     * Use this if the index was created before this setting was added
     */
    public function updateMaxResultWindow(int $maxResultWindow = self::MAX_RESULT_WINDOW): void
    {
        if (!$this->configured || $this->index === null) {
            throw new \RuntimeException('Elasticsearch is not configured');
        }

        if (!$this->index->exists()) {
            throw new \RuntimeException('Index does not exist. Use createIndex() first.');
        }

        try {
            $this->index->setSettings([
                'index' => [
                    'max_result_window' => $maxResultWindow,
                ]
            ]);
            $this->logger->info(sprintf('Updated Elasticsearch index max_result_window to %d', $maxResultWindow));
        } catch (\Exception $e) {
            $this->logger->error('Failed to update max_result_window: ' . $e->getMessage());
            throw new \RuntimeException('Failed to update max_result_window: ' . $e->getMessage());
        }
    }
}
