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
 * RuleRelationShip
 * 
 * @ORM\Entity(repositoryClass="Myddleware\RegleBundle\Entity\RuleRelationShipRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(indexes={@ORM\Index(name="Krule_id", columns={"rule_id"})})
 */
 
class RuleRelationShip
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

 
    /**
     * @var string
     *
     * @ORM\Column(name="rule_id", type="string", length=100, nullable=false)
	 * 
     */
    private $rule;

    /**
     * @var string
     *
     * @ORM\Column(name="field_name_source", type="string", length=100, nullable=false)
	 * 
     */
    private $fieldNameSource;
	
    /**
     * @var string
     *
     * @ORM\Column(name="field_name_target", type="string", length=100, nullable=false)
	 * 
     */
    private $fieldNameTarget;	
	

    /**
     * @var integer
     *
	 * @ORM\Column(name="field_id", type="string", length=100, nullable=true)
	 * 
     */
    private $fieldId;     

	/**
     * @var boolean
     *
	 * @ORM\Column(name="parent", type="boolean", nullable=true)
	 * 
     */
    private $parent; 	
	
	/**
     * @var boolean
     *
	 * @ORM\Column(name="deleted", type="boolean", options={"default":0})
	 * 
     */
    private $deleted; 

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set rule
     *
     * @param string $rule
     * @return RuleRelationShip
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
     * Set fieldNameSource
     *
     * @param string $fieldNameSource
     * @return RuleRelationShip
     */
    public function setFieldNameSource($fieldNameSource)
    {
        $this->fieldNameSource = $fieldNameSource;
    
        return $this;
    }

    /**
     * Get fieldNameSource
     *
     * @return string 
     */
    public function getFieldNameSource()
    {
        return $this->fieldNameSource;
    }

    /**
     * Set fieldNameTarget
     *
     * @param string $fieldNameTarget
     * @return RuleRelationShip
     */
    public function setFieldNameTarget($fieldNameTarget)
    {
        $this->fieldNameTarget = $fieldNameTarget;
    
        return $this;
    }

    /**
     * Get fieldNameTarget
     *
     * @return string 
     */
    public function getFieldNameTarget()
    {
        return $this->fieldNameTarget;
    }

    /**
     * Set fieldId
     *
     * @param string $fieldId
     * @return RuleRelationShip
     */
    public function setFieldId($fieldId)
    {
        $this->fieldId = $fieldId;
    
        return $this;
    }

    /**
     * Get fieldId
     *
     * @return string 
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }
	
	/**
     * Set parent
     *
     * @param string $parent
     * @return RuleRelationShip
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    
        return $this;
    }

    /**
     * Get parent
     *
     * @return string 
     */
    public function getParent()
    {
        return $this->parent;
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

}