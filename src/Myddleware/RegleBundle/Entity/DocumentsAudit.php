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
 * DocumentsAudit
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity
 */ 
class DocumentsAudit
{
    /**
     * @var string
     *
     * @ORM\Column(name="doca_id", type="string")
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
     * @var string
     *
	 * 
     * @ORM\Column(name="doc_id", type="string", nullable=false)
	 * 
     */
    private $doc;

    /**
     * @var datetime
     *
     * @ORM\Column(name="doca_modified", type="datetime", nullable=false)
	 * 
     */
    private $dateModified;
	
    /**
     * @var integer
     *
	 * @ORM\Column(name="doca_before", type="string", nullable=true)
	 * 
     */
    private $before;	

    /**
     * @var integer
     *
     * @ORM\Column(name="doca_after", type="string", nullable=true)
	 * 
     */
    private $after;

    /**
     * @var string
     *
	 * 
     * @ORM\Column(name="doca_user", type="string", nullable=false)
	 * 
     */
    private $byUser;


    /**
     * @var string
     *
	 * 
     * @ORM\Column(name="doca_name", type="string", nullable=false)
	 * 
     */
    private $name;
				


    /**
     * Set id
     *
     * @param string $id
     * @return DocumentsAudit
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
     * Set doc
     *
     * @param string $doc
     * @return DocumentsAudit
     */
    public function setDoc($doc)
    {
        $this->doc = $doc;
    
        return $this;
    }

    /**
     * Get doc
     *
     * @return string 
     */
    public function getDoc()
    {
        return $this->doc;
    }

    /**
     * Set dateModified
     *
     * @param \DateTime $dateModified
     * @return DocumentsAudit
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
     * @return DocumentsAudit
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
     * @return DocumentsAudit
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
     * @return DocumentsAudit
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
     * Set name
     *
     * @param string $name
     * @return DocumentsAudit
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
}