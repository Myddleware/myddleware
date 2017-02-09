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
 * RuleAudit
 *
 * @ORM\Table()
 * @ORM\Entity 
 * @ORM\Table(indexes={@ORM\Index(name="index_ruleid", columns={"rule_id"})}) 
 */
class RuleAudit
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
     * @var array
     *
     * @ORM\Column(name="data", type="array", nullable=false)
	 * 
     */
    private $data;
  

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
     * Get rule
     *
     * @return string 
     */
    public function getRule()
    {
        return $this->rule;
    }
	
    /**
     * Set rule
     *
     * @param string $rule
     * @return RuleAudit
     */
    public function setRule($rule)
    {
        $this->rule = $rule;  
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
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return RuleAudit
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated; 
        return $this;
    }
	
    /**
     * Get data
     *
     * @return string 
     */
    public function getData()
    {
        return $this->data;
    }
 	
	/**
     * Set data
     *
     * @param string $data
     * @return RuleAudit
     */
    public function setData($data)
    {
        $this->data = $data;
    
        return $this;
    }
}