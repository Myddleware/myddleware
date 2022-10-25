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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="solution")
 * @ORM\Entity(repositoryClass="App\Repository\SolutionRepository")
 */
class Solution
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="name", type="string", length=20,nullable=false)
     */
    private string $name;

    /**
     * @ORM\Column(name="active", type="integer", length=1,nullable=false)
     */
    private int $active;

    /**
     * @ORM\Column(name="source", type="integer", length=1,nullable=false)
     */
    private int $source;

    /**
     * @ORM\Column(name="target", type="integer", length=1,nullable=false)
     */
    private int $target;

    /**
     * @ORM\OneToMany(targetEntity="Connector", mappedBy="solution", cascade={"persist", "remove", "merge"})
     */
    private $connector;

    public function __construct()
    {
        $this->connector = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setActive(int $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getActive(): int
    {
        return $this->active;
    }

    public function setSource($source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getSource(): int
    {
        return $this->source;
    }

    public function setTarget($target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getTarget(): int
    {
        return $this->target;
    }

    public function addConnector(Connector $connector): self
    {
        $this->connector[] = $connector;

        return $this;
    }

    public function removeConnector(Connector $connector)
    {
        $this->connector->removeElement($connector);
    }

    public function getConnector()
    {
        return $this->connector;
    }
}
