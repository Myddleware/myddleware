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

/**
 * Documents
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\Table(indexes={@ORM\Index(name="index_ruleid_status", columns={"rule_id","status"})})
 */ 
class Documents
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", nullable=false)
     * @ORM\Id
     */
    private $id;
	
    /**
     * @var string
     *
	 * 
     * @ORM\Column(name="rule_id", type="string", nullable=false)
	 * 
     */
    private $rule;

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
	 * 
     * @ORM\Column(name="status", type="string", nullable=false)
	 * 
     */
    private $status;

    /**
	* @var string
    *
	* @ORM\Column(name="source_id", type="string", nullable=false)
	* 
    */
    private $source;		

    /**
	* @var string
    *
	* @ORM\Column(name="target_id", type="string", nullable=false)
    */
    private $target;	

    /**
     * @var datetime
     *
     * @ORM\Column(name="source_date_modified", type="datetime", nullable=false)
	 * 
     */
    private $sourceDateModified;
	
    /**
	* @var string
    *
	* @ORM\Column(name="mode", type="string", length=1, nullable=false)
    */
    private $mode;	
	
    /**
	* @var string
    *
	* @ORM\Column(name="type", type="string", length=1, nullable=false)
    */
    private $type;
	
    /**
     * @var integer
     *
	 * @ORM\Column(name="attempt", type="integer", length=5, nullable=false)
	 * 
     */
    private $attempt;
	
    /**
     * @var integer
     *
	 * @ORM\Column(name="global_status", type="string", nullable=false)
	 * 
     */
    private $globalStatus;	
	
	/**
     * @var string
     *
     * @ORM\Column(name="parent_id", type="string", nullable=false)
     */
    private $parent_id;	

    /**
     * Set id
     *
     * @param string $id
     * @return Documents
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
     * Set rule
     *
     * @param string $rule
     * @return Documents
     */
    public function setRule($rule)
    {
        $this->rule = $rule;
    
        return $this;
    }

    /**
     * Get rule
     *
     * @return string 
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return Documents
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
     * @return Documents
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
     * @return Documents
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
     * @return Documents
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
     * Set status
     *
     * @param string $status
     * @return Documents
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return Documents
     */
    public function setSource($source)
    {
        $this->source = $source;
    
        return $this;
    }

    /**
     * Get source
     *
     * @return string 
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set target
     *
     * @param string $target
     * @return Documents
     */
    public function setTarget($target)
    {
        $this->target = $target;
    
        return $this;
    }

    /**
     * Get target
     *
     * @return string 
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set sourceDateModified
     *
     * @param \DateTime $sourceDateModified
     * @return Documents
     */
    public function setSourceDateModified($sourceDateModified)
    {
        $this->sourceDateModified = $sourceDateModified;
    
        return $this;
    }

    /**
     * Get sourceDateModified
     *
     * @return \DateTime 
     */
    public function getSourceDateModified()
    {
        return $this->sourceDateModified;
    }

    /**
     * Set mode
     *
     * @param string $mode
     * @return Documents
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    
        return $this;
    }

    /**
     * Get mode
     *
     * @return string 
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Documents
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set attempt
     *
     * @param integer $attempt
     * @return Documents
     */
    public function setAttempt($attempt)
    {
        $this->attempt = $attempt;
    
        return $this;
    }

    /**
     * Get attempt
     *
     * @return integer 
     */
    public function getAttempt()
    {
        return $this->attempt;
    }

    /**
     * Set globalStatus
     *
     * @param string $globalStatus
     * @return Documents
     */
    public function setGlobalStatus($globalStatus)
    {
        $this->globalStatus = $globalStatus;
    
        return $this;
    }

    /**
     * Get globalStatus
     *
     * @return string 
     */
    public function getGlobalStatus()
    {
        return $this->globalStatus;
    }
	
	/**
     * Set parentId
     *
     * @param string $parent_id
     * @return Documents
     */
    public function setParentId($parent_id)
    {
        $this->parent_id = $parent_id;
    
        return $this;
    }

    /**
     * Get parentId
     *
     * @return string 
     */
    public function getParentId()
    {
        return $this->parent_id;
    }
}