<?php

declare(strict_types=1);
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

use App\Repository\SolutionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="solution")
 * @ORM\Entity(repositoryClass=SolutionRepository::class)
 */
class Solution implements \Stringable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=20,nullable=false)
     */
    private $name;

    /**
     * @ORM\Column(name="active", type="boolean",nullable=false)
     */
    private $active;

    /**
     * @ORM\Column(name="source", type="boolean",nullable=false)
     */
    private $source;

    /**
     * @ORM\Column(name="target", type="boolean",nullable=false)
     */
    private $target;

    /**
     * @ORM\OneToMany(targetEntity=Module::class, mappedBy="solution", orphanRemoval=true)
     */
    private $modules;

    /**
     * @ORM\OneToMany(targetEntity=Connector::class, mappedBy="solution", orphanRemoval=true, cascade={"persist", "remove", "merge"})
     */
    private $connectors;

    public function __construct()
    {
        $this->connectors = new ArrayCollection();
        $this->modules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setSource(bool $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getSource(): bool
    {
        return $this->source;
    }

    public function setTarget(bool $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getTarget(): bool
    {
        return $this->target;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getLogo(): ?string
    {
        return $this->name.'.png';
    }

    /**
     * @return Collection<int, Module>
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function addModule(Module $module): self
    {
        if (!$this->modules->contains($module)) {
            $this->modules[] = $module;
            $module->setSolution($this);
        }

        return $this;
    }

    public function removeModule(Module $module): self
    {
        if ($this->modules->removeElement($module)) {
            // set the owning side to null (unless already changed)
            if ($module->getSolution() === $this) {
                $module->setSolution(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Connector>
     */
    public function getConnectors(): Collection
    {
        return $this->connectors;
    }

    public function addConnector(Connector $connector): self
    {
        if (!$this->connectors->contains($connector)) {
            $this->connectors[] = $connector;
            $connector->setSolution($this);
        }

        return $this;
    }

    public function removeConnector(Connector $connector): self
    {
        if ($this->connectors->removeElement($connector)) {
            // set the owning side to null (unless already changed)
            if ($connector->getSolution() === $this) {
                $connector->setSolution(null);
            }
        }

        return $this;
    }
}
