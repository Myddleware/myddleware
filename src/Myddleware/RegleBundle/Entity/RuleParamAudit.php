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
 * RuleParamAudit
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\Table(indexes={
 *  @ORM\Index(name="index_job_id", columns={"job_id"}),
 *  @ORM\Index(name="index_rule_param_id", columns={"rule_param_id"})
 *})
 */ 
class RuleParamAudit
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
	 * @ORM\PrePersist()
	 */
	public function preSave() {
	    $this->id = uniqid();
	}
	
    /**
     * @var integer
     *
	 * 
     * @ORM\Column(name="rule_param_id", type="integer")
	 * 
     */
    private $ruleParamId;

    /**
     * @var datetime
     *
     * @ORM\Column(name="modified", type="datetime", nullable=false)
	 * 
     */
    private $dateModified;
	
    /**
     * @var integer
     *
	 * @ORM\Column(name="before_value", type="string", nullable=true)
	 * 
     */
    private $before;	

    /**
     * @var integer
     *
     * @ORM\Column(name="after_value", type="string", nullable=true)
	 * 
     */
    private $after;

    /**
     * @var string
     *
	 * 
     * @ORM\Column(name="user", type="string", nullable=true)
	 * 
     */
    private $byUser;


    /**
     * @var string
     *
     * @ORM\Column(name="job_id", type="string", length=255, nullable=true)
	 * 
     */
    private $job;	


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
     * Set ruleParamId
     *
     * @param string $ruleParamId
     * @return RuleParamAudit
     */
    public function setRuleParamId($ruleParamId)
    {
        $this->ruleParamId = $ruleParamId;
    
        return $this;
    }

    /**
     * Get ruleParamId
     *
     * @return string 
     */
    public function getDoc()
    {
        return $this->ruleParamId;
    }

    /**
     * Set dateModified
     *
     * @param \DateTime $dateModified
     * @return RuleParamAudit
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
     * Set before
     *
     * @param string $before
     * @return RuleParamAudit
     */
    public function setBefore($before)
    {
        $this->before = $before;
    
        return $this;
    }

    /**
     * Get before
     *
     * @return string 
     */
    public function getBefore()
    {
        return $this->before;
    }

    /**
     * Set after
     *
     * @param string $after
     * @return RuleParamAudit
     */
    public function setAfter($after)
    {
        $this->after = $after;
    
        return $this;
    }

    /**
     * Get after
     *
     * @return string 
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * Set byUser
     *
     * @param string $byUser
     * @return RuleParamAudit
     */
    public function setByUser($byUser)
    {
        $this->byUser = $byUser;
    
        return $this;
    }

    /**
     * Get byUser
     *
     * @return string 
     */
    public function getByUser()
    {
        return $this->byUser;
    }

    /**
     * Set job
     *
     * @param string $job
     * @return Log
     */
    public function setJob($job)
    {
        $this->job = $job;
    
        return $this;
    }

    /**
     * Get job
     *
     * @return string 
     */
    public function getJob()
    {
        return $this->job;
    }
}