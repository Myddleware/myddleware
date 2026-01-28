<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base event for document operations
 * Used for Elasticsearch real-time sync
 */
class DocumentEvent extends Event
{
    public const CREATED = 'document.created';
    public const UPDATED = 'document.updated';
    public const DELETED = 'document.deleted';

    private string $documentId;
    private array $documentData;

    public function __construct(string $documentId, array $documentData = [])
    {
        $this->documentId = $documentId;
        $this->documentData = $documentData;
    }

    public function getDocumentId(): string
    {
        return $this->documentId;
    }

    public function getDocumentData(): array
    {
        return $this->documentData;
    }

    public function setDocumentData(array $documentData): void
    {
        $this->documentData = $documentData;
    }
}
