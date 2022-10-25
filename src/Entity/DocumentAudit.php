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
 * @ORM\Table(name="documentaudit")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\DocumentAuditRepository")
 */
class DocumentAudit
{
    /**
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     */
    private string $id;

    /**
     * @ORM\PrePersist()
     */
    public function preSave()
    {
        $this->id = uniqid();
    }

    /**
     * @ORM\Column(name="doc_id", type="string", nullable=false)
     */
    private string $doc;

    /**
     * @ORM\Column(name="modified", type="datetime", nullable=false)
     */
    private DateTime $dateModified;

    /**
     * @ORM\Column(name="before_value", type="string", nullable=true)
     */
    private string $before;

    /**
     * @ORM\Column(name="after_value", type="string", nullable=true)
     */
    private string $after;

    /**
     * @ORM\Column(name="user", type="string", nullable=false)
     */
    private string $byUser;

    /**
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private string $name;

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setDoc($doc): self
    {
        $this->doc = $doc;

        return $this;
    }

    public function getDoc(): string
    {
        return $this->doc;
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

    public function setBefore($before): self
    {
        $this->before = $before;

        return $this;
    }

    public function getBefore(): string
    {
        return $this->before;
    }

    public function setAfter($after): self
    {
        $this->after = $after;

        return $this;
    }

    public function getAfter(): string
    {
        return $this->after;
    }

    public function setByUser($byUser): self
    {
        $this->byUser = $byUser;

        return $this;
    }

    public function getByUser(): string
    {
        return $this->byUser;
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
}
