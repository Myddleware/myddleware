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

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="solution")
 * @ORM\Entity(repositoryClass="App\Repository\SolutionRepository")
 */
class Solution
{
    /**
     * @var int
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
     * @var int
     *
     * @ORM\Column(name="source", type="integer", length=1,nullable=false)
     */
    private $source;

    /**
     * @var int
     *
     * @ORM\Column(name="target", type="integer", length=1,nullable=false)
     */
    private $target;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Connector", mappedBy="solution", cascade={"persist", "remove", "merge"})
     */
    private $connector;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->connector = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Solution
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set active.
     *
     * @param int $active
     *
     * @return Solution
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active.
     *
     * @return int
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set source.
     *
     * @param int $source
     *
     * @return Solution
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return int
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set target.
     *
     * @param int $target
     *
     * @return Solution
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target.
     *
     * @return int
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Add connector.
     *
     * @return Solution
     */
    public function addConnector(Connector $connector)
    {
        $this->connector[] = $connector;

        return $this;
    }

    /**
     * Remove connector.
     */
    public function removeConnector(Connector $connector)
    {
        $this->connector->removeElement($connector);
    }

    /**
     * Get connector.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getConnector()
    {
        return $this->connector;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getLogo(): string 
    {
        return $this->name.'.png';
    }
}
