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

namespace Myddleware\RegleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // slug
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity; // unique

/**
 * Rule
 * 
 * @ORM\Entity(repositoryClass="Myddleware\RegleBundle\Entity\RuleRepository")
 * @ORM\HasLifecycleCallbacks()
 *
 * @ORM\Table(indexes={@ORM\Index(name="Krule_name", columns={"name"})})
 */
 
class Rule
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     */
    private $id;

	/**
	 * @ORM\PrePersist()
	 */
	public function preSave() {
	    $this->id = uniqid();
	}

    /**
     * @var Connector $connectorSource
     *
     * @ORM\ManyToOne(targetEntity="Connector")
     * @ORM\JoinColumn(name="conn_id_source", referencedColumnName="id")	 
     * 
     */
    private $connectorSource;

    /**
     * @var Connector $connectorTarget
     *
     * @ORM\ManyToOne(targetEntity="Connector")
     * @ORM\JoinColumn(name="conn_id_target", referencedColumnName="id")	 
     * 	 
     */
    private $connectorTarget;

    /**
     * @var datetime
     *
	 * 
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
	 * 
     */
    private $dateCreated;

    /**
     * @var datetime
     *
     * @ORM\Column(name="date_modified", type="datetime", nullable=false)
	 * 
     */
    private $dateModified;

    /**
     * @var integer
     *
	 * @ORM\Column(name="created_by", type="integer", nullable=false)
	 * 
     */
    private $createdBy;

    /**
     * @var integer
     *
     * @ORM\Column(name="modified_by", type="integer", nullable=false)
	 * 
     */
    private $modifiedBy;
	
    /**
	* @var string
    *
	* @ORM\Column(name="module_source", type="string", nullable=false)
	* 
    */
    private $moduleSource;		

    /**
	* @var string
    *
	* @ORM\Column(name="module_target", type="string", nullable=false)
    */
    private $moduleTarget;		


    /**
     * @var boolean
     *
	 * @ORM\Column(name="active", type="boolean", nullable=false)
	 * 
     */
    private $active;  
	
    /**
     * @var boolean
     *
	 * @ORM\Column(name="deleted", type="boolean", options={"default":0})
	 * 
     */
    private $deleted; 	
	  
    /**
     * @var string
     *
	 * @ORM\Column(name="name", type="string", length=50, nullable=false)
	 * 
     */
    private $name;  
	
   /**
	 * @Gedmo\Slug(fields={"name"}, separator="_")
	 * @ORM\Column(length=50, nullable=false, name="name_slug")
	 */
	private $nameSlug;	


    /**
     * Set id
     *
     * @param string $id
     * @return Rule
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return Rule
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set dateModified
     *
     * @param \DateTime $dateModified
     * @return Rule
     */
    public function setDateModified($dateModified)
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    /**
     * Get dateModified
     *
     * @return \DateTime 
     */
    public function getDateModified()
    {
        return $this->dateModified;
    }

    /**
     * Set createdBy
     *
     * @param integer $createdBy
     * @return Rule
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return integer 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set modifiedBy
     *
     * @param integer $modifiedBy
     * @return Rule
     */
    public function setModifiedBy($modifiedBy)
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    /**
     * Get modifiedBy
     *
     * @return integer 
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }

    /**
     * Set moduleSource
     *
     * @param string $moduleSource
     * @return Rule
     */
    public function setModuleSource($moduleSource)
    {
        $this->moduleSource = $moduleSource;

        return $this;
    }

    /**
     * Get moduleSource
     *
     * @return string 
     */
    public function getModuleSource()
    {
        return $this->moduleSource;
    }

    /**
     * Set moduleTarget
     *
     * @param string $moduleTarget
     * @return Rule
     */
    public function setModuleTarget($moduleTarget)
    {
        $this->moduleTarget = $moduleTarget;

        return $this;
    }

    /**
     * Get moduleTarget
     *
     * @return string 
     */
    public function getModuleTarget()
    {
        return $this->moduleTarget;
    }

    /**
     * Set active
     *
     * @param integer $active
     * @return Rule
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return integer 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set deleted
     *
     * @param integer $deleted
     * @return Rule
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return integer 
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Rule
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set nameSlug
     *
     * @param string $nameSlug
     * @return Rule
     */
    public function setNameSlug($nameSlug)
    {
        $this->nameSlug = $nameSlug;

        return $this;
    }

    /**
     * Get nameSlug
     *
     * @return string 
     */
    public function getNameSlug()
    {
        return $this->nameSlug;
    }

    /**
     * Set connectorSource
     *
     * @param \Myddleware\RegleBundle\Entity\Connector $connectorSource
     * @return Rule
     */
    public function setConnectorSource(\Myddleware\RegleBundle\Entity\Connector $connectorSource)
    {
        $this->connectorSource = $connectorSource;

        return $this;
    }

    /**
     * Get connectorSource
     *
     * @return \Myddleware\RegleBundle\Entity\Connector 
     */
    public function getConnectorSource()
    {
        return $this->connectorSource;
    }

    /**
     * Set connectorTarget
     *
     * @param \Myddleware\RegleBundle\Entity\Connector $connectorTarget
     * @return Rule
     */
    public function setConnectorTarget(\Myddleware\RegleBundle\Entity\Connector $connectorTarget)
    {
        $this->connectorTarget = $connectorTarget;

        return $this;
    }

    /**
     * Get connectorTarget
     *
     * @return \Myddleware\RegleBundle\Entity\Connector 
     */
    public function getConnectorTarget()
    {
        return $this->connectorTarget;
    }
}
