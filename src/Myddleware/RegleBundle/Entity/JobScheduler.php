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
     * @ORM\Column(name="command", type="string", length=50, nullable=false)
	 * 
     */
    private $command;

	 /**
     * @var string
     *
     * @ORM\Column(name="param1", type="string", length=50, nullable=false)
	 * 
     */
    private $param1;

	 /**
     * @var string
     *
     * @ORM\Column(name="param2", type="string", length=50, nullable=false)
	 * 
     */
    private $param2;
	
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
     * @ORM\Column(name="lastRun", type="datetime")
	 * 
     */
    private $lastRun;

    
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
     * Set param1
     *
     * @param string $param1
     * @return JobScheduler
     */
    public function setParam1($param1)
    {
        $this->param1 = $param1;
    
        return $this;
    }

    /**
     * Get param1
     *
     * @return string 
     */
    public function getParam1()
    {
        return $this->param1;
    }
	
	/**
     * Set param2
     *
     * @param string $param2
     * @return JobScheduler
     */
    public function setParam2($param2)
    {
        $this->param2 = $param2;
    
        return $this;
    }

    /**
     * Get param2
     *
     * @return string 
     */
    public function getParam2()
    {
        return $this->param2;
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

}
