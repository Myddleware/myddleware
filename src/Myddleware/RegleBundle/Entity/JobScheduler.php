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
 * JobScheduler
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Myddleware\RegleBundle\Entity\ConnectorRepository")
 */
class JobScheduler
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
     * @ORM\Column(name="date_modified", columnDefinition="DATETIME on update CURRENT_TIMESTAMP"))
	 * 
     */
    private $dateModified;
	
    /**
     * @var integer
     *
	 * @ORM\Column(name="created_by", type="integer", nullable=false, options={"default":1})
	 * 
     */
    private $createdBy;	

    /**
     * @var integer
     *
     * @ORM\Column(name="modified_by", type="integer", nullable=false, options={"default":1})
	 * 
     */
    private $modifiedBy;
	
    /**
     * @var string
     *
     * @ORM\Column(name="command", type="string", length=50, nullable=false)
	 * 
     */
    private $command;

	 /**
     * @var string
     *
     * @ORM\Column(name="paramName1", type="string", length=50, nullable=false)
	 * 
     */
    private $paramName1;

	/**
     * @var string
     *
     * @ORM\Column(name="paramValue1", type="string", length=50, nullable=false)
	 * 
     */
    private $paramValue1;

	 /**
     * @var string
     *
     * @ORM\Column(name="paramName2", type="string", length=50, nullable=false)
	 * 
     */
    private $paramName2;
	
	 /**
     * @var string
     *
     * @ORM\Column(name="paramValue2", type="string", length=50, nullable=false)
	 * 
     */
    private $paramValue2;
	
	/**
     * @var integer
     *
     * @ORM\Column(name="period", type="integer", length=6,  nullable=false, options={"default":5})
	 * 
     */
    private $period;
	
    /**
     * @var datetime
     *
	 * 
     * @ORM\Column(name="lastRun", type="datetime", nullable=true)
	 * 
     */
    private $lastRun;
	
    /**
     * @var boolean
     *
	 * @ORM\Column(name="active", type="boolean", options={"default":1})
	 * 
     */
    private $active;  
	
    /**
     * @var integer
     *
     * @ORM\Column(name="jobOrder", type="integer", length=3, nullable=true)
	 * 
     */
    private $jobOrder;
	
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
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return JobScheduler
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
     * @return JobScheduler
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
     * @return JobScheduler
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
     * @return JobScheduler
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
     * Set command
     *
     * @param string $command
     * @return JobScheduler
     */
    public function setCommand($command)
    {
        $this->command = $command;
    
        return $this;
    }

    /**
     * Get command
     *
     * @return string 
     */
    public function getCommand()
    {
        return $this->command;
    }
	
	/**
     * Set paramName1
     *
     * @param string $paramName1
     * @return JobScheduler
     */
    public function setParamName1($paramName1)
    {
        $this->paramName1 = $paramName1;
    
        return $this;
    }

    /**
     * Get paramName1
     *
     * @return string 
     */
    public function getParamName1()
    {
        return $this->paramName1;
    }
	
	/**
     * Set paramValue1
     *
     * @param string $paramValue1
     * @return JobScheduler
     */
    public function setParamValue1($paramValue1)
    {
        $this->paramValue1 = $paramValue1;
    
        return $this;
    }

    /**
     * Get paramValue1
     *
     * @return string 
     */
    public function getParamValue1()
    {
        return $this->paramValue1;
    }
	
	/**
     * Set paramName2
     *
     * @param string $paramName2
     * @return JobScheduler
     */
    public function setParamName2($paramName2)
    {
        $this->paramName2 = $paramName2;
    
        return $this;
    }

    /**
     * Get paramName2
     *
     * @return string 
     */
    public function getParamName2()
    {
        return $this->paramName2;
    }
	
	/**
     * Set paramValue2
     *
     * @param string $paramValue2
     * @return JobScheduler
     */
    public function setParamValue2($paramValue2)
    {
        $this->paramValue2 = $paramValue2;
    
        return $this;
    }

    /**
     * Get paramValue2
     *
     * @return string 
     */
    public function getParamValue2()
    {
        return $this->paramValue2;
    }
		
	/**
     * Set period
     *
     * @param string $period
     * @return JobScheduler
     */
    public function setPeriod($period)
    {
        $this->period = $period;
    
        return $this;
    }

    /**
     * Get period
     *
     * @return string 
     */
    public function getPeriod()
    {
        return $this->period;
    }
	
	/**
     * Set lastRun
     *
     * @param string $lastRun
     * @return JobScheduler
     */
    public function setLastRun($lastRun)
    {
        $this->lastRun = $lastRun;
    
        return $this;
    }

    /**
     * Get lastRun
     *
     * @return string 
     */
    public function getLastRun()
    {
        return $this->lastRun;
    }
	
	/**
     * Set active
     *
     * @param string $active
     * @return JobScheduler
     */
    public function setActive($active)
    {
        $this->active = $active;
    
        return $this;
    }

    /**
     * Get active
     *
     * @return string 
     */
    public function getActive()
    {
        return $this->active;
    }
	
	/**
     * Set jobOrder
     *
     * @param string $jobOrder
     * @return JobScheduler
     */
    public function setJobOrder($jobOrder)
    {
        $this->jobOrder = $jobOrder;
    
        return $this;
    }

    /**
     * Get jobOrder
     *
     * @return string 
     */
    public function getJobOrder()
    {
        return $this->jobOrder;
    }

}
