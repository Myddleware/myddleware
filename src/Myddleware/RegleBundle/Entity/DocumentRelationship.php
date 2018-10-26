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
 * DocumentAudit
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity
 * @ORM\Table(indexes={
 *  @ORM\Index(name="index_doc_id", columns={"doc_id"}),
 *  @ORM\Index(name="index_doc_rel_id", columns={"doc_rel_id"}),
 *}) 
 */ 
class DocumentRelationship
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
     * @var string
     *
     * @ORM\Column(name="doc_id", type="string", length=100, nullable=false)
	 * 
     */
    private $doc_id;
	

	/**
     * @var string
     *
     * @ORM\Column(name="doc_rel_id", type="string", length=100, nullable=false)
	 * 
     */
    private $doc_rel_id;
	
	
	
    /**
     * @var integer
     *
	 * @ORM\Column(name="created_by", type="integer", nullable=false)
	 * 
     */
    private $createdBy;

	
	/**
     * @var datetime
     *
	 * 
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
	 * 
     */
    private $dateCreated;
	

    /**
     * @var string
     *
	 * 
     * @ORM\Column(name="source_field", type="string", nullable=false)
	 * 
     */
    private $sourceField;
				


    /**
     * Set id
     *
     * @param string $id
     * @return DocumentRelationship
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
     * Set doc_id
     *
     * @param string $doc_id
     * @return DocumentRelationship
     */
    public function setDocId($doc_id)
    {
        $this->doc_id = $doc_id;
    
        return $this;
    }

    /**
     * Get doc_id
     *
     * @return string 
     */
    public function getDocId()
    {
        return $this->doc_id;
    }


	/**
     * Set doc_rel_id
     *
     * @param string $doc_rel_id
     * @return DocumentRelationship
     */
    public function setDocRelId($doc_rel_id)
    {
        $this->doc_rel_id = $doc_rel_id;
    
        return $this;
    }

    /**
     * Get doc_rel_id
     *
     * @return string 
     */
    public function getDocRelId()
    {
        return $this->doc_rel_id;
    }
	
	/**
     * Set rule
     *
     * @param string $rule
     * @return DocumentRelationship
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
     * @return DocumentRelationship
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
     * Set createdBy
     *
     * @param integer $createdBy
     * @return DocumentRelationship
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
     * Set sourceField
     *
     * @param string $sourceField
     * @return DocumentRelationship
     */
    public function setSourceField($sourceField)
    {
        $this->sourceField = $sourceField;
    
        return $this;
    }

    /**
     * Get sourceField
     *
     * @return string 
     */
    public function getSourceField()
    {
        return $this->sourceField;
    }
}