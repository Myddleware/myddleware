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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DocumentRepository")
 * @ORM\Table(name="document", indexes={
 *      @ORM\Index(name="index_ruleid_status", columns={"rule_id","status"}),
 *      @ORM\Index(name="index_parent_id", columns={"parent_id"}),
 *      @ORM\Index(name="global_status", columns={"global_status"}),
 *      @ORM\Index(name="source_id", columns={"source_id"}),
 *      @ORM\Index(name="target_id", columns={"target_id"}),
 *      @ORM\Index(name="rule_id", columns={"rule_id"}),
 *      @ORM\Index(name="date_modified", columns={"date_modified"})
 * })
 */
class Document
{
    /**
     * @ORM\Column(name="id", type="string", nullable=false)
     * @ORM\Id
     */
    private string $id;

    /**
     * @ORM\ManyToOne(targetEntity="Rule", inversedBy="documents")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", nullable=false)
     */
    private Rule $rule;

    /**
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private DateTime $dateCreated;

    /**
     * @ORM\Column(name="date_modified", type="datetime", nullable=false)
     */
    private DateTime $dateModified;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id", nullable=false)
     */
    private ?User $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="modified_by", referencedColumnName="id", nullable=false)
     */
    private ?User $modifiedBy;

    /**
     * @ORM\Column(name="status", type="string",  nullable=true, options={"default":NULL})
     */
    private ?string $status;

    /**
     * @ORM\Column(name="source_id", type="string", nullable=true, options={"default":NULL})
     */
    private ?string $source;

    /**
     * @ORM\Column(name="target_id", type="string",  nullable=true, options={"default":NULL})
     */
    private ?string $target;

    /**
     * @ORM\Column(name="source_date_modified", type="datetime",  nullable=true, options={"default":NULL})
     */
    private ?DateTime $sourceDateModified;

    /**
     * @ORM\Column(name="mode", type="string", length=1,  nullable=true, options={"default":NULL})
     */
    private ?string $mode;

    /**
     * @ORM\Column(name="type", type="string", length=1,  nullable=true, options={"default":NULL})
     */
    private ?string $type;

    /**
     * @ORM\Column(name="attempt", type="integer", length=5,  nullable=false, options={"default":0})
     */
    private int $attempt;

    /**
     * @ORM\Column(name="global_status", type="string",  nullable=false, options={"default":0})
     */
    private string $globalStatus;

    /**
     * @ORM\Column(name="parent_id", type="string", nullable=true, options={"default":NULL})
     */
    private ?string $parentId;

    /**
     * @ORM\Column(name="deleted", type="boolean", options={"default":0})
     */
    private int $deleted;

    /**
     * @ORM\OneToMany(targetEntity="DocumentData", mappedBy="doc_id")
     */
    private $datas;

    /**
     * @ORM\OneToMany(targetEntity="Log", mappedBy="document")
     */
    private $logs;

    public function __construct()
    {
        $this->datas = new ArrayCollection();
        $this->logs = new ArrayCollection();
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setRule($rule): self
    {
        $this->rule = $rule;

        return $this;
    }

    public function getRule(): Rule
    {
        return $this->rule;
    }

    public function setDateCreated($dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateCreated(): DateTime
    {
        return $this->dateCreated;
    }

    public function setDateModified($dateModified): self
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    public function getDateModified(): DateTime
    {
        return $this->dateModified;
    }

    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setSource($source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setTarget($target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setSourceDateModified($sourceDateModified): self
    {
        $this->sourceDateModified = $sourceDateModified;

        return $this;
    }

    public function getSourceDateModified(): DateTime
    {
        return $this->sourceDateModified;
    }

    public function setMode($mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setAttempt($attempt): self
    {
        $this->attempt = $attempt;

        return $this;
    }

    public function addAttempt(): self
    {
        ++$this->attempt;

        return $this;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }

    public function setGlobalStatus($globalStatus): self
    {
        $this->globalStatus = $globalStatus;

        return $this;
    }

    public function getGlobalStatus(): string
    {
        return $this->globalStatus;
    }

    public function setParentId($parentId): self
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function setDeleted($deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getDeleted(): int
    {
        return $this->deleted;
    }

    /**
     * @return Collection|DocumentData[]
     */
    public function getDatas(): Collection
    {
        return $this->datas;
    }

    public function addData(DocumentData $data): self
    {
        if (!$this->datas->contains($data)) {
            $this->datas[] = $data;
            $data->setDocId($this);
        }

        return $this;
    }

    public function removeData(DocumentData $data): self
    {
        if ($this->datas->removeElement($data)) {
            // set the owning side to null (unless already changed)
            if ($data->getDocId() === $this) {
                $data->setDocId(null);
            }
        }

        return $this;
    }

    public function getDataByType(string $type)
    {
        $documentData = $this->getDatas()->filter(function (DocumentData $documentData) use ($type) {
            return $type === $documentData->getType();
        })->first();

        if ($documentData instanceof DocumentData) {
            return json_decode($documentData->getData(), true);
        }
    }

    /**
     * @return Collection|Log[]
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(Log $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs[] = $log;
            $log->setDocument($this);
        }

        return $this;
    }

    public function removeLog(Log $log): self
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getDocument() === $this) {
                $log->setDocument(null);
            }
        }

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getModifiedBy(): ?User
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(?User $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }
}
