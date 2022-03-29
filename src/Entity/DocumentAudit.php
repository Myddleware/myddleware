<?php

declare(strict_types=1);
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

use App\Repository\DocumentAuditRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="documentaudit")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass=DocumentAuditRepository::class)
 */
class DocumentAudit
{
    /**
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\PrePersist()
     */
    public function preSave()
    {
        $this->id = uniqid();
    }

    /**
     * @ORM\Column(name="before_value", type="string", nullable=true)
     */
    private $before;

    /**
     * @ORM\Column(name="after_value", type="string", nullable=true)
     */
    private $after;

    /**
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="documentAudits")
     * @ORM\JoinColumn(nullable=false)
     */
    private $modifiedBy;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=Document::class, inversedBy="documentAudits")
     * @ORM\JoinColumn(nullable=false)
     */
    private $document;

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setBefore(?string $before): self
    {
        $this->before = $before;

        return $this;
    }

    public function getBefore(): ?string
    {
        return $this->before;
    }

    public function setAfter(?string $after): self
    {
        $this->after = $after;

        return $this;
    }

    public function getAfter(): ?string
    {
        return $this->after;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): self
    {
        $this->document = $document;

        return $this;
    }
}
