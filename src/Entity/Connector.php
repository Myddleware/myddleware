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
     * @var int
     *
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
     * @var Solution
     *
     * @ORM\ManyToOne(targetEntity="Solution", inversedBy="connector")
     * @ORM\JoinColumn(name="sol_id", referencedColumnName="id")
     */
    private $solution;

    /**
     * @var ArrayCollection
     */
    private $rule;

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
     * @var int
     *
     * @ORM\Column(name="created_by", nullable=false)
     */
    private $createdBy;

    /**
     * @var int
     *
     * @ORM\Column(name="modified_by", nullable=false)
     */
    private $modifiedBy;

    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean", options={"default":0})
     */
    private $deleted;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->rule = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Connector
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set nameSlug.
     *
     * @param string $nameSlug
     *
     * @return Connector
     */
    public function setNameSlug($nameSlug)
    {
        $this->nameSlug = $nameSlug;

        return $this;
    }

    /**
     * Get nameSlug.
     *
     * @return string
     */
    public function getNameSlug()
    {
        return $this->nameSlug;
    }

    /**
     * Set dateCreated.
     *
     * @param DateTime $dateCreated
     *
     * @return Connector
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
     * @return Connector
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
     * Set createdBy.
     *
     * @param string $createdBy
     *
     * @return Connector
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy.
     *
     * @return string
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set modifiedBy.
     *
     * @param string $modifiedBy
     *
     * @return Connector
     */
    public function setModifiedBy($modifiedBy)
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    /**
     * Get modifiedBy.
     *
     * @return string
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }

    /**
     * Set solution.
     *
     * @return Connector
     */
    public function setSolution(Solution $solution)
    {
        $this->solution = $solution;

        return $this;
    }

    /**
     * Get solution.
     *
     * @return Solution
     */
    public function getSolution()
    {
        return $this->solution;
    }

    /**
     * Add rule.
     *
     * @return Connector
     */
    public function addRule(Rule $rule)
    {
        $this->rule[] = $rule;

        return $this;
    }

    /**
     * Remove rule.
     */
    public function removeRule(Rule $rule)
    {
        $this->rule->removeElement($rule);
    }

    /**
     * Get rule.
     *
     * @return Collection
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Add connectorParam.
     *
     * @return Connector
     */
    public function addConnectorParam(ConnectorParam $connectorParam)
    {
        $this->connectorParams[] = $connectorParam;

        return $this;
    }

    /**
     * Remove connectorParam.
     */
    public function removeConnectorParam(ConnectorParam $connectorParam)
    {
        $this->connectorParams->removeElement($connectorParam);
    }

    /**
     * Get connectorParams.
     *
     * @return Collection|ConnectorParam[]
     */
    public function getConnectorParams()
    {
        return $this->connectorParams;
    }

    /**
     * Set connectorParams.
     *
     * @return Collection
     *
     * @param mixed|null $connectorParams
     */
    public function setConnectorParams($connectorParams = null)
    {
        return $this->connectorParams = $connectorParams;
    }

    /**
     * Set deleted.
     *
     * @param int $deleted
     *
     * @return Rule
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
}
