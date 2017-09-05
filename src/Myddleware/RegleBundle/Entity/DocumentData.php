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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity; // unique

use Doctrine\ORM\Mapping as ORM;

/**
 * DocumentData
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Myddleware\RegleBundle\Entity\DocumentDataRepository")
 * @ORM\Table(indexes={
 *  @ORM\Index(name="index_doc_id", columns={"doc_id"}),
 *  @ORM\Index(name="index_job_id_type", columns={"doc_id","type"})
 *})
 */
class DocumentData
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
     * @ORM\ManyToOne(targetEntity="Document", inversedBy="id")
     * @ORM\JoinColumn(name="doc_id", referencedColumnName="id")
	 * 
     */
    private $doc_id;


	/**
	 * @var string
     *
	 * @ORM\Column(name="type", type="string", length=1, nullable=false)
     */ 
    private $type;

  
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
     * Set doc_id
     *
     * @param string $doc_id
     * @return DocumentData
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
     * Set type
     *
     * @param string $type
     * @return DocumentData
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
     * Set data
     *
     * @param string $data
     * @return DocumentData
     */
    public function setData($data)
    {
        $this->data = $data;
    
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

}