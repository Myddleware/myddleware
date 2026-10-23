<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com

 This file is part of Myddleware.

 Myddleware is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Myddleware is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
*********************************************************************************/

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Event received from a webhook. This table is a journal : a record is never updated after its creation.
 * Each rule reading webhooks keeps its own cursor (datereference) on the column received_at,
 * so several rules can consume the same event.
 */
#[ORM\Entity(repositoryClass: 'App\Repository\WebhookEventRepository')]
#[ORM\Table(name: 'webhookevent')]
#[ORM\Index(name: 'index_conn_module_received', columns: ['conn_id', 'module', 'received_at'])]
#[ORM\Index(name: 'index_received_at', columns: ['received_at'])]
#[ORM\UniqueConstraint(name: 'unique_conn_external_id', columns: ['conn_id', 'external_id'])]
class WebhookEvent
{
    #[ORM\Column(name: 'id', type: 'bigint')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Connector::class)]
    #[ORM\JoinColumn(name: 'conn_id', referencedColumnName: 'id', nullable: false)]
    private Connector $connector;

    #[ORM\Column(name: 'module', type: 'string', length: 100, nullable: false)]
    private string $module;

    #[ORM\Column(name: 'event_type', type: 'string', length: 100, nullable: true)]
    private ?string $eventType = null;

    #[ORM\Column(name: 'record_id', type: 'string', length: 255, nullable: false)]
    private string $recordId;

    // Id of the event in the source application, used to ignore the events sent several times
    #[ORM\Column(name: 'external_id', type: 'string', length: 255, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column(name: 'deletion', type: 'boolean', options: ['default' => 0])]
    private bool $deletion = false;

    #[ORM\Column(name: 'payload', type: 'text', nullable: true)]
    private ?string $payload = null;

    // Precision in microseconds to limit the events received at the same time (cursor of the rules)
    #[ORM\Column(name: 'received_at', type: 'datetime', nullable: false, columnDefinition: 'DATETIME(6) NOT NULL')]
    private DateTime $receivedAt;

    public function getId()
    {
        return $this->id;
    }

    public function getConnector(): Connector
    {
        return $this->connector;
    }

    public function setConnector(Connector $connector): self
    {
        $this->connector = $connector;

        return $this;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function setModule(string $module): self
    {
        $this->module = $module;

        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(?string $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getRecordId(): string
    {
        return $this->recordId;
    }

    public function setRecordId(string $recordId): self
    {
        $this->recordId = $recordId;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getDeletion(): bool
    {
        return $this->deletion;
    }

    public function setDeletion(bool $deletion): self
    {
        $this->deletion = $deletion;

        return $this;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function setPayload(?string $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getReceivedAt(): DateTime
    {
        return $this->receivedAt;
    }

    public function setReceivedAt(DateTime $receivedAt): self
    {
        $this->receivedAt = $receivedAt;

        return $this;
    }
}
