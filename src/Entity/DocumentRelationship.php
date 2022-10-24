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
    private int $id;

    /**
     * @ORM\Column(name="doc_id", type="string", length=100, nullable=false)
     */
    private string $doc_id;

    /**
     * @ORM\Column(name="doc_rel_id", type="string", length=100, nullable=false)
     */
    private string $doc_rel_id;

    /**
     * @ORM\Column(name="created_by", type="integer", nullable=false)
     */
    private int $createdBy;

    /**
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private DateTime $dateCreated;

    /**
     * @ORM\Column(name="source_field", type="string", nullable=false)
     */
    private string $sourceField;

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setDocId($doc_id): self
    {
        $this->doc_id = $doc_id;

        return $this;
    }

    public function getDocId(): string
    {
        return $this->doc_id;
    }

    public function setDocRelId($doc_rel_id): self
    {
        $this->doc_rel_id = $doc_rel_id;

        return $this;
    }

    public function getDocRelId(): string
    {
        return $this->doc_rel_id;
    }

    public function setRule($rule): self
    {
        $this->rule = $rule;

        return $this;
    }

    public function getRule()
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

    public function setCreatedBy($createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function setSourceField($sourceField): self
    {
        $this->sourceField = $sourceField;

        return $this;
    }

    public function getSourceField(): string
    {
        return $this->sourceField;
    }
}
