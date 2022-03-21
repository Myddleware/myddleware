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
 * Document.
 *
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
     * @var string
     *
     * @ORM\Column(name="id", type="string", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var Rule
     *
     * @ORM\ManyToOne(targetEntity="Rule", inversedBy="documents")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", nullable=false)
     */
    private $rule;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private $dateCreated;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_modified", type="datetime", nullable=false)
     */
    private $dateModified;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id", nullable=false)
     */
    private $createdBy;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="modified_by", referencedColumnName="id", nullable=false)
     */
    private $modifiedBy;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string",  nullable=true, options={"default":NULL})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="source_id", type="string", nullable=true, options={"default":NULL})
     */
    private $source;

    /**
     * @var string
     *
     * @ORM\Column(name="target_id", type="string",  nullable=true, options={"default":NULL})
     */
    private $target;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="source_date_modified", type="datetime",  nullable=true, options={"default":NULL})
     */
    private $sourceDateModified;

    /**
     * @var string
     *
     * @ORM\Column(name="mode", type="string", length=1,  nullable=true, options={"default":NULL})
     */
    private $mode;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=1,  nullable=true, options={"default":NULL})
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(name="attempt", type="integer", length=5,  nullable=false, options={"default":0})
     */
    private $attempt;

    /**
     * @var int
     *
     * @ORM\Column(name="global_status", type="string",  nullable=false, options={"default":0})
     */
    private $globalStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="parent_id", type="string", nullable=true, options={"default":NULL})
     */
    private $parentId;

    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean", options={"default":0})
     */
    private $deleted;

    /**
     * @var DocumentData[]
     *
     * @ORM\OneToMany(targetEntity="DocumentData", mappedBy="doc_id")
     */
    private $datas;

    /**
     * @var Log[]
     *
     * @ORM\OneToMany(targetEntity="Log", mappedBy="document")
     */
    private $logs;

    public function __construct()
    {
        $this->datas = new ArrayCollection();
        $this->logs = new ArrayCollection();
    }

    /**
     * Set id.
     *
     * @param string $id
     *
     * @return Document
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set rule.
     *
     * @param string $rule
     *
     * @return Document
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * Get rule.
     *
     * @return string
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Set dateCreated.
     *
     * @param DateTime $dateCreated
     *
     * @return Document
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated.
     *
     * @return DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set dateModified.
     *
     * @param DateTime $dateModified
     *
     * @return Document
     */
    public function setDateModified($dateModified)
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    /**
     * Get dateModified.
     *
     * @return DateTime
     */
    public function getDateModified()
    {
        return $this->dateModified;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return Document
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set source.
     *
     * @param string $source
     *
     * @return Document
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set target.
     *
     * @param string $target
     *
     * @return Document
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target.
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set sourceDateModified.
     *
     * @param DateTime $sourceDateModified
     *
     * @return Document
     */
    public function setSourceDateModified($sourceDateModified)
    {
        $this->sourceDateModified = $sourceDateModified;

        return $this;
    }

    /**
     * Get sourceDateModified.
     *
     * @return DateTime
     */
    public function getSourceDateModified()
    {
        return $this->sourceDateModified;
    }

    /**
     * Set mode.
     *
     * @param string $mode
     *
     * @return Document
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Get mode.
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Document
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set attempt.
     *
     * @param int $attempt
     *
     * @return Document
     */
    public function setAttempt($attempt)
    {
        $this->attempt = $attempt;

        return $this;
    }

    public function addAttempt()
    {
        ++$this->attempt;

        return $this;
    }

    /**
     * Get attempt.
     *
     * @return int
     */
    public function getAttempt()
    {
        return $this->attempt;
    }

    /**
     * Set globalStatus.
     *
     * @param string $globalStatus
     *
     * @return Document
     */
    public function setGlobalStatus($globalStatus)
    {
        $this->globalStatus = $globalStatus;

        return $this;
    }

    /**
     * Get globalStatus.
     *
     * @return string
     */
    public function getGlobalStatus()
    {
        return $this->globalStatus;
    }

    /**
     * Set parentId.
     *
     * @param string $parentId
     *
     * @return Document
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId.
     *
     * @return string
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set deleted.
     *
     * @param int $deleted
     *
     * @return Document
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted.
     *
     * @return int
     */
    public function getDeleted()
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
