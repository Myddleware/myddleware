<?php

namespace App\EventSubscribers;

use App\Event\DocumentEvent;
use App\Service\DocumentElasticsearchService;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to document events and syncs changes to Elasticsearch in real-time
 */
class DocumentElasticsearchSubscriber implements EventSubscriberInterface
{
    private ?DocumentElasticsearchService $elasticsearchService;
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(
        ?DocumentElasticsearchService $elasticsearchService,
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->elasticsearchService = $elasticsearchService;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvent::CREATED => ['onDocumentCreated', 0],
            DocumentEvent::UPDATED => ['onDocumentUpdated', 0],
            DocumentEvent::DELETED => ['onDocumentDeleted', 0],
        ];
    }

    /**
     * Handle document creation - index to Elasticsearch
     */
    public function onDocumentCreated(DocumentEvent $event): void
    {
        if (!$this->isElasticsearchAvailable()) {
            return;
        }

        try {
            $documentData = $this->fetchDocumentData($event->getDocumentId());
            if ($documentData) {
                // Skip availability check (already done above) and don't refresh (let ES refresh naturally)
                $this->elasticsearchService->indexDocument($documentData, true, false);
                $this->logger->debug('Indexed new document to Elasticsearch', ['id' => $event->getDocumentId()]);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to index document to Elasticsearch: ' . $e->getMessage(), [
                'document_id' => $event->getDocumentId()
            ]);
        }
    }

    /**
     * Handle document update - re-index to Elasticsearch
     */
    public function onDocumentUpdated(DocumentEvent $event): void
    {
        if (!$this->isElasticsearchAvailable()) {
            return;
        }

        try {
            $documentData = $this->fetchDocumentData($event->getDocumentId());
            if ($documentData) {
                // Skip availability check (already done above) and don't refresh (let ES refresh naturally)
                $this->elasticsearchService->indexDocument($documentData, true, false);
                $this->logger->debug('Updated document in Elasticsearch', ['id' => $event->getDocumentId()]);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to update document in Elasticsearch: ' . $e->getMessage(), [
                'document_id' => $event->getDocumentId()
            ]);
        }
    }

    /**
     * Handle document deletion - remove from Elasticsearch
     */
    public function onDocumentDeleted(DocumentEvent $event): void
    {
        if (!$this->isElasticsearchAvailable()) {
            return;
        }

        try {
            // Skip availability check (already done above) and don't refresh (let ES refresh naturally)
            $this->elasticsearchService->deleteDocument($event->getDocumentId(), true, false);
            $this->logger->debug('Deleted document from Elasticsearch', ['id' => $event->getDocumentId()]);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to delete document from Elasticsearch: ' . $e->getMessage(), [
                'document_id' => $event->getDocumentId()
            ]);
        }
    }

    /**
     * Check if Elasticsearch is available
     */
    private function isElasticsearchAvailable(): bool
    {
        if ($this->elasticsearchService === null) {
            return false;
        }

        try {
            return $this->elasticsearchService->isAvailable();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Fetch document data from MySQL to index in Elasticsearch
     */
    private function fetchDocumentData(string $documentId): ?array
    {
        $query = "
            SELECT
                document.id,
                document.rule_id,
                document.date_created,
                document.date_modified,
                document.status,
                document.source_id,
                document.target_id,
                document.source_date_modified,
                document.mode,
                document.type,
                document.attempt,
                document.global_status,
                document.deleted,
                rule.name as rule_name,
                rule.module_source,
                rule.module_target,
                source_data.data as source_content,
                target_data.data as target_content
            FROM document
            INNER JOIN rule ON document.rule_id = rule.id
            LEFT JOIN documentdata source_data ON document.id = source_data.doc_id AND source_data.type = 'S'
            LEFT JOIN documentdata target_data ON document.id = target_data.doc_id AND target_data.type = 'T'
            WHERE document.id = :documentId
        ";

        $stmt = $this->connection->prepare($query);
        $stmt->bindValue(':documentId', $documentId);
        $result = $stmt->executeQuery()->fetchAssociative();

        if (!$result) {
            return null;
        }

        // Prepare document for Elasticsearch
        $elasticDoc = [
            'id' => $result['id'],
            'rule_id' => $result['rule_id'],
            'rule_name' => $result['rule_name'],
            'status' => $result['status'],
            'global_status' => $result['global_status'],
            'source_id' => $result['source_id'],
            'target_id' => $result['target_id'],
            'date_created' => $result['date_created'],
            'date_modified' => $result['date_modified'],
            'source_date_modified' => $result['source_date_modified'],
            'type' => $result['type'],
            'mode' => $result['mode'],
            'attempt' => $result['attempt'],
            'module_source' => $result['module_source'],
            'module_target' => $result['module_target'],
            'deleted' => (bool) $result['deleted'],
        ];

        // Add source content if available (unserialize if needed)
        if (!empty($result['source_content'])) {
            $sourceContent = @unserialize($result['source_content']);
            $elasticDoc['source_content'] = is_string($sourceContent) ? $sourceContent : json_encode($sourceContent);
        }

        // Add target content if available (unserialize if needed)
        if (!empty($result['target_content'])) {
            $targetContent = @unserialize($result['target_content']);
            $elasticDoc['target_content'] = is_string($targetContent) ? $targetContent : json_encode($targetContent);
        }

        return $elasticDoc;
    }
}
