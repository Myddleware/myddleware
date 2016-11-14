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

use Gedmo\Mapping\Annotation as Gedmo; // slug
use Doctrine\ORM\Mapping as ORM;

/**
 * Connector
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Myddleware\RegleBundle\Entity\ConnectorRepository")
 */
class Connector
{
    /**
     * @var integer
     *
     * @ORM\Column(name="conn_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
	

    /**
     * @var Solution $solution
     *
     * @ORM\ManyToOne(targetEntity="Solution", inversedBy="connector")
     * @ORM\JoinColumn(name="sol_id", referencedColumnName="sol_id")
     *
     */
    private $solution;
	
	
     /**
     * @var ArrayCollection $rule
     *
     */
    private $rule;
	

    /**
     * @var string
     *
     * @ORM\Column(name="conn_lbl", type="string", length=50, nullable=false)
	 * 
     */
    private $label;

   /**
	 * @Gedmo\Slug(fields={"label"}, separator="_", unique=true)
	 * @ORM\Column(length=50, nullable=false, name="conn_lbl_slug")
	 */
	private $labelSlug;

    /**
     * @var datetime
     *
	 * 
     * @ORM\Column(name="conn_date_created", type="datetime", nullable=false)
	 * 
     */
    private $dateCreated;

    /**
     * @var datetime
     *
     * @ORM\Column(name="conn_date_modified", type="datetime", nullable=false)
	 * 
     */
    private $dateModified;

    /**
     * @var integer
     *
	 * @ORM\Column(name="conn_created_by", nullable=false)
	 * 
     */
    private $createdBy;

    /**
     * @var integer
     *
     * @ORM\Column(name="conn_modified_by", nullable=false)
	 * 
     */
    private $modifiedBy;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->rule = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
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
     * Set label
     *
     * @param string $label
     * @return Connector
     */
    public function setLabel($label)
    {
        $this->label = $label;
    
        return $this;
    }

    /**
     * Get label
     *
     * @return string 
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set labelSlug
     *
     * @param string $labelSlug
     * @return Connector
     */
    public function setLabelSlug($labelSlug)
    {
        $this->labelSlug = $labelSlug;
    
        return $this;
    }

    /**
     * Get labelSlug
     *
     * @return string 
     */
    public function getLabelSlug()
    {
        return $this->labelSlug;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return Connector
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
     * @return Connector
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
     * @param string $createdBy
     * @return Connector
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    
        return $this;
    }

    /**
     * Get createdBy
     *
     * @return string 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set modifiedBy
     *
     * @param string $modifiedBy
     * @return Connector
     */
    public function setModifiedBy($modifiedBy)
    {
        $this->modifiedBy = $modifiedBy;
    
        return $this;
    }

    /**
     * Get modifiedBy
     *
     * @return string 
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }

    /**
     * Set solution
     *
     * @param \Myddleware\RegleBundle\Entity\Solution $solution
     * @return Connector
     */
    public function setSolution(\Myddleware\RegleBundle\Entity\Solution $solution)
    {
        $this->solution = $solution;
    
        return $this;
    }

    /**
     * Get solution
     *
     * @return \Myddleware\RegleBundle\Entity\Solution 
     */
    public function getSolution()
    {
        return $this->solution;
    }

    /**
     * Add rule
     *
     * @param \Myddleware\RegleBundle\Entity\Rule $rule
     * @return Connector
     */
    public function addRule(\Myddleware\RegleBundle\Entity\Rule $rule)
    {
        $this->rule[] = $rule;
    
        return $this;
    }

    /**
     * Remove rule
     *
     * @param \Myddleware\RegleBundle\Entity\Rule $rule
     */
    public function removeRule(\Myddleware\RegleBundle\Entity\Rule $rule)
    {
        $this->rule->removeElement($rule);
    }

    /**
     * Get rule
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRule()
    {
        return $this->rule;
    }
}