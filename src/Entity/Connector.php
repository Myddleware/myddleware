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
use Doctrine\ORM\Mapping as ORM; // slug
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="connector")
 * @ORM\Entity(repositoryClass="App\Repository\ConnectorRepository")
 */
class Connector
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="ConnectorParam", mappedBy="connector")
     */
    private $connectorParams;

    /**
     * @ORM\ManyToOne(targetEntity="Solution", inversedBy="connector")
     * @ORM\JoinColumn(name="sol_id", referencedColumnName="id")
     */
    private $solution;


    //  TODO: FIX TYPE, NUMBER & RELATIONSHIPMAPPING (MANYTOONE) 
    // private $rule;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @Gedmo\Slug(fields={"name"}, separator="_", unique=true)
     * @ORM\Column(length=50, nullable=false, name="name_slug")
     */
    private $nameSlug;

    /**
     * TODO: FIX TYPE 
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private $dateCreated;

    /**
     * TODO: FIX TYPE
     * @ORM\Column(name="date_modified", type="datetime", nullable=false)
     */
    private $dateModified;

    /**
     * TODO: FIX TYPE 
     * @ORM\Column(name="created_by", nullable=false)
     */
    private $createdBy;

    /**
     * TODO: FIX TYPE 
     * @ORM\Column(name="modified_by", nullable=false)
     */
    private $modifiedBy;

    /**
     * @ORM\Column(name="deleted", type="boolean", options={"default":0})
     */
    private $deleted;


    public function __construct()
    {
        // $this->rule = new ArrayCollection();
    }

    public function getId(): int 
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setNameSlug(string $nameSlug): self
    {
        $this->nameSlug = $nameSlug;

        return $this;
    }

    public function getNameSlug(): string
    {
        return $this->nameSlug;
    }

    public function setDateCreated(DateTime $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateCreated(): DateTime
    {
        return $this->dateCreated;
    }

    public function setDateModified(DateTime $dateModified): self
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    public function getDateModified(): DateTime
    {
        return $this->dateModified;
    }

    public function setCreatedBy(string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function setModifiedBy(string $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    public function getModifiedBy(): string
    {
        return $this->modifiedBy;
    }

    public function setSolution(Solution $solution): self
    {
        $this->solution = $solution;

        return $this;
    }

    public function getSolution(): Solution
    {
        return $this->solution;
    }

    // public function addRule(Rule $rule): self
    // {
    //     $this->rule[] = $rule;

    //     return $this;
    // }

    // public function removeRule(Rule $rule)
    // {
    //     $this->rule->removeElement($rule);
    // }

    // public function getRule(): Collection
    // {
    //     return $this->rule;
    // }

    public function addConnectorParam(ConnectorParam $connectorParam): self
    {
        $this->connectorParams[] = $connectorParam;

        return $this;
    }

    public function removeConnectorParam(ConnectorParam $connectorParam)
    {
        $this->connectorParams->removeElement($connectorParam);
    }

    public function getConnectorParams():Collection
    {
        return $this->connectorParams;
    }

    public function setConnectorParams(mixed $connectorParams = null): Collection
    {
        return $this->connectorParams = $connectorParams;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getDeleted(): bool
    {
        return $this->deleted;
    }
}
