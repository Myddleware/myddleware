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
 * FuncCat
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Myddleware\RegleBundle\Entity\FuncCatRepository")
 */
class FuncCat
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
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;

     /**
     * @var ArrayCollection $functions
     *
     */
    private $functions;	

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->functions = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @param string $nameYml
     * @return FuncCat
     */
    public function setName($nameYml)
    {
        $this->name = $nameYml;
    
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
     * Add functions
     *
     * @param \Myddleware\RegleBundle\Entity\Functions $functions
     * @return FuncCat
     */
    public function addFunction(\Myddleware\RegleBundle\Entity\Functions $functions)
    {
        $this->functions[] = $functions;
    
        return $this;
    }

    /**
     * Remove functions
     *
     * @param \Myddleware\RegleBundle\Entity\Functions $functions
     */
    public function removeFunction(\Myddleware\RegleBundle\Entity\Functions $functions)
    {
        $this->functions->removeElement($functions);
    }

    /**
     * Get functions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getFunctions()
    {
        return $this->functions;
    }
}