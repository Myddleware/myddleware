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
use Exception;

/**
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="App\Repository\RuleGroupRepository")
 * @ORM\Table(name="rulegroup")
 */
class RuleGroup
{

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private $id;
	
    /**
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    private string $name;
    /**
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */

    private $dateCreated;

    /**
     * @ORM\Column(name="date_modified", type="datetime", nullable=false)
     */
    private DateTime $dateModified;
	
	/**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id", nullable=false)
     */
    private User $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="modified_by", referencedColumnName="id", nullable=false)
     */
    private User $modifiedBy;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private string $description;
	
	/**
     * @ORM\Column(name="deleted", type="boolean", options={"default":0})
     */
    private int $deleted;

	
    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
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
	    
	public function setName($name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
	
	public function setDescription($description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
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

}
