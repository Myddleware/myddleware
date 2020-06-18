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
 * Job
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\Table(indexes={
 *  @ORM\Index(name="index_status", columns={"status"})
 *}) 
 */ 
class Job
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=255, nullable=false)
     * @ORM\Id
     */
    private $id;
	
    /**
     * @var string
     *
	 * 
     * @ORM\Column(name="status", type="string", length=50, nullable=false)
	 * 
     */
    private $status;

    /**
     * @var string
     *
	 * 
     * @ORM\Column(name="param", type="text", nullable=false)
	 * 
     */
    private $param;

    /**
     * @var datetime
     *
	 * @ORM\Column(name="begin", type="datetime", nullable=false)
	 * 
     */
    private $begin;

    /**
     * @var datetime
     *
	 * @ORM\Column(name="end", type="datetime",  nullable=true, options={"default":NULL})
	 * 
     */
    private $end;
	

    /**
     * @var string
     *
	 * @ORM\Column(name="message", type="text",  nullable=true, options={"default":NULL})
	 * 
     */
    private $message;	
	

    /**
     * @var integer
     *
	 * @ORM\Column(name="open", type="integer", length=6,  nullable=true, options={"default":0})
	 * 
     */
    private $open;
	
    /**
     * @var integer
     *
	 * @ORM\Column(name="close", type="integer", length=6,  nullable=true, options={"default":0})
	 * 
     */
    private $close;	

    /**
     * @var integer
     *
	 * @ORM\Column(name="cancel", type="integer", length=6,  nullable=true, options={"default":0})
	 * 
     */
    private $cancel;
	
     /**
     * @var boolean
     *
	 * @ORM\Column(name="manual", type="boolean",  nullable=true, options={"default":0})
	 * 
     */
    private $manual;
	
    /**
     * @var boolean
     *
	 * @ORM\Column(name="api", type="boolean",  nullable=true, options={"default":0})
	 * 
     */
    private $api;
	
    /**
     * @var integer
     *
	 * @ORM\Column(name="error", type="integer", length=6,  nullable=true, options={"default":0})
	 * 
     */
    private $error;	


    /**
     * Set id
     *
     * @param string $id
     * @return Job
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
     * Set status
     *
     * @param string $status
     * @return Job
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
     * Set param
     *
     * @param string $param
     * @return Job
     */
    public function setParam($param)
    {
        $this->param = $param;
    
        return $this;
    }

    /**
     * Get param
     *
     * @return string 
     */
    public function getParam()
    {
        return $this->param;
    }

    /**
     * Set begin
     *
     * @param \DateTime $begin
     * @return Job
     */
    public function setBegin($begin)
    {
        $this->begin = $begin;
    
        return $this;
    }

    /**
     * Get begin
     *
     * @return \DateTime 
     */
    public function getBegin()
    {
        return $this->begin;
    }

    /**
     * Set end
     *
     * @param \DateTime $end
     * @return Job
     */
    public function setEnd($end)
    {
        $this->end = $end;
    
        return $this;
    }

    /**
     * Get end
     *
     * @return \DateTime 
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Job
     */
    public function setMessage($message)
    {
        $this->message = $message;
    
        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        # Don't show ip address
        $patterns = "/[0-9]{0,3}\.[0-9]{0,3}\.[0-9]{0,3}\.[0-9]{0,3}/";
        $replacements = "XXX.XXX.XXX.XXX";

        return preg_replace($patterns, $replacements, $this->message);
    }

    /**
     * Set open
     *
     * @param integer $open
     * @return Job
     */
    public function setOpen($open)
    {
        $this->open = $open;
    
        return $this;
    }

    /**
     * Get open
     *
     * @return integer 
     */
    public function getOpen()
    {
        return $this->open;
    }

    /**
     * Set close
     *
     * @param integer $close
     * @return Job
     */
    public function setClose($close)
    {
        $this->close = $close;
    
        return $this;
    }

    /**
     * Get close
     *
     * @return integer 
     */
    public function getClose()
    {
        return $this->close;
    }

    /**
     * Set cancel
     *
     * @param integer $cancel
     * @return Job
     */
    public function setCancel($cancel)
    {
        $this->cancel = $cancel;
    
        return $this;
    }

    /**
     * Get cancel
     *
     * @return integer 
     */
    public function getCancel()
    {
        return $this->cancel;
    }

	/**
     * Set manual
     *
     * @param integer $manual
     * @return Job
     */
    public function setManual($manual)
    {
        $this->manual = $manual;
    
        return $this;
    }

    /**
     * Get manual
     *
     * @return integer 
     */
    public function getManual()
    {
        return $this->manual;
    }
	
	/**
     * Set api
     *
     * @param integer $api
     * @return Job
     */
    public function setApi($api)
    {
        $this->api = $api;
    
        return $this;
    }

    /**
     * Get api
     *
     * @return integer 
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * Set error
     *
     * @param integer $error
     * @return Job
     */
    public function setError($error)
    {
        $this->error = $error;
    
        return $this;
    }

    /**
     * Get error
     *
     * @return integer 
     */
    public function getError()
    {
        return $this->error;
    }
}