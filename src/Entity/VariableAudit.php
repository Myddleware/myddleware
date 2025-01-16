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
 * @ORM\Entity(repositoryClass="App\Repository\VariableAuditRepository")
 * @ORM\Table(name="variableaudit", indexes={
 *  @ORM\Index(name="index_variable_id", columns={"variable_id"})
 *})
 */
class VariableAudit
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
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
     * @var int
     *
     * @ORM\Column(name="variable_id", type="integer")
     */
    private $variableId;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="modified", type="datetime", nullable=false)
     */
    private $dateModified;

    /**
     * @var int
     *
     * @ORM\Column(name="before_value", type="string", nullable=true)
     */
    private $before;

    /**
     * @var int
     *
     * @ORM\Column(name="after_value", type="string", nullable=true)
     */
    private $after;

    /**
     * @var string
     *
     * @ORM\Column(name="user", type="string", nullable=true)
     */
    private $byUser;


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
     * Set variableId.
     *
     * @param string $variableId
     *
     * @return VariableAudit
     */
    public function setVariableId($variableId)
    {
        $this->variableId = $variableId;

        return $this;
    }

    /**
     * Get variableId.
     *
     * @return string
     */
    public function getDoc()
    {
        return $this->variableId;
    }

    /**
     * Set dateModified.
     *
     * @param DateTime $dateModified
     *
     * @return VariableAudit
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
     * Set before.
     *
     * @param string $before
     *
     * @return VariableAudit
     */
    public function setBefore($before)
    {
        $this->before = $before;

        return $this;
    }

    /**
     * Get before.
     *
     * @return string
     */
    public function getBefore()
    {
        return $this->before;
    }

    /**
     * Set after.
     *
     * @param string $after
     *
     * @return VariableAudit
     */
    public function setAfter($after)
    {
        $this->after = $after;

        return $this;
    }

    /**
     * Get after.
     *
     * @return string
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * Set byUser.
     *
     * @param string $byUser
     *
     * @return VariableAudit
     */
    public function setByUser($byUser)
    {
        $this->byUser = $byUser;

        return $this;
    }

    /**
     * Get byUser.
     *
     * @return string
     */
    public function getByUser()
    {
        return $this->byUser;
    }
	
}
