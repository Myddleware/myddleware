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
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Solution
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Myddleware\RegleBundle\Entity\SolutionRepository")
 */
class Solution
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
     * @ORM\Column(name="name", type="string", length=20,nullable=false)
     */
    private $name;


    /**
     * @var string
     *
     * @ORM\Column(name="active", type="integer", length=1,nullable=false)
     */
    private $active;

    /**
     * @var integer
     *
     * @ORM\Column(name="source", type="integer", length=1,nullable=false)
     */
    private $source;
	
    /**
     * @var integer
     *
     * @ORM\Column(name="target", type="integer", length=1,nullable=false)
     */
    private $target;	

     /**
     * @var ArrayCollection $connector
	 * @ORM\OneToMany(targetEntity="Connector", mappedBy="solution", cascade={"persist", "remove", "merge"})	
	 *
     */
    private $connector;


 
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->connector = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return Solution
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

    /**
     * Set active
     *
     * @param integer $active
     * @return Solution
     */
    public function setActive($active)
    {
        $this->active = $active;
    
        return $this;
    }

    /**
     * Get active
     *
     * @return integer 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set source
     *
     * @param integer $source
     * @return Solution
     */
    public function setSource($source)
    {
        $this->source = $source;
    
        return $this;
    }

    /**
     * Get source
     *
     * @return integer 
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set target
     *
     * @param integer $target
     * @return Solution
     */
    public function setTarget($target)
    {
        $this->target = $target;
    
        return $this;
    }

    /**
     * Get target
     *
     * @return integer 
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Add connector
     *
     * @param \Myddleware\RegleBundle\Entity\Connector $connector
     * @return Solution
     */
    public function addConnector(\Myddleware\RegleBundle\Entity\Connector $connector)
    {
        $this->connector[] = $connector;
    
        return $this;
    }

    /**
     * Remove connector
     *
     * @param \Myddleware\RegleBundle\Entity\Connector $connector
     */
    public function removeConnector(\Myddleware\RegleBundle\Entity\Connector $connector)
    {
        $this->connector->removeElement($connector);
    }

    /**
     * Get connector
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getConnector()
    {
        return $this->connector;
    }
}