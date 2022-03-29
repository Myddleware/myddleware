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

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\DocumentRelationshipRepository")
 * @ORM\Table(name="documentrelationship", indexes={
 *  @ORM\Index(name="index_doc_id", columns={"doc_id"}),
 *  @ORM\Index(name="index_doc_rel_id", columns={"doc_rel_id"}),
 *})
 */
class DocumentRelationship
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="doc_id", type="string", length=100, nullable=false)
     */
    private $doc_id;

    /**
     * @ORM\Column(name="doc_rel_id", type="string", length=100, nullable=false)
     */
    private $doc_rel_id;

    /**
     * @ORM\Column(name="source_field", type="string", nullable=false)
     */
    private $sourceField;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $createdBy;

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setDocId(string $doc_id): self
    {
        $this->doc_id = $doc_id;

        return $this;
    }

    public function getDocId(): ?string
    {
        return $this->doc_id;
    }

    public function setDocRelId(string $doc_rel_id): self
    {
        $this->doc_rel_id = $doc_rel_id;

        return $this;
    }

    public function getDocRelId(): ?string
    {
        return $this->doc_rel_id;
    }

    public function setRule(?string $rule): self
    {
        $this->rule = $rule;

        return $this;
    }

    public function getRule(): ?string
    {
        return $this->rule;
    }

    public function setSourceField(string $sourceField): self
    {
        $this->sourceField = $sourceField;

        return $this;
    }

    public function getSourceField(): ?string
    {
        return $this->sourceField;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

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
}
