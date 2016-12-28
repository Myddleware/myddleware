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
 * RuleField
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Myddleware\RegleBundle\Entity\RuleFieldRepository")
 * @ORM\Table(indexes={@ORM\Index(name="Krule_id", columns={"rule_id"})})
 */
class RuleField
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
	* @ORM\Column(name="rule_id", type="string")
    */
    private $rule;	


    /**
     * @var string
     *
     * @ORM\Column(name="target_field_name", type="text", nullable=false)
	 * 
     */
    private $target;

    /**
     * @var string
     *
     * @ORM\Column(name="source_field_name", type="text", nullable=false)
	 * 
     */
    private $source;
	
    /**
     * @var string
     *
     * @ORM\Column(name="formula", type="text", nullable=true)
	 * 
     */ 
    private $formula;	
	

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
     * Set rule
     *
     * @param string $rule
     * @return RuleField
     */
    public function setRule($rule)
    {
        $this->rule = $rule;
    
        return $this;
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
     * Set target
     *
     * @param string $target
     * @return RuleField
     */
    public function setTarget($target)
    {
        $this->target = $target;
    
        return $this;
    }

    /**
     * Get target
     *
     * @return string 
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return RuleField
     */
    public function setSource($source)
    {
        $this->source = $source;
    
        return $this;
    }

    /**
     * Get source
     *
     * @return string 
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set formula
     *
     * @param string $formula
     * @return RuleField
     */
    public function setFormula($formula)
    {
        $this->formula = $formula;
    
        return $this;
    }

    /**
     * Get formula
     *
     * @return string 
     */
    public function getFormula()
    {
        return $this->formula;
    }
}