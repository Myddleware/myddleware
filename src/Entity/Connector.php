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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="connector")
 * @ORM\Entity(repositoryClass="App\Repository\ConnectorRepository")
 */
class Connector implements \Stringable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private ?string $name = null;

    /**
     * @ORM\OneToMany(targetEntity="ConnectorParam", mappedBy="connector", cascade={"persist"})
     */
    private $connectorParams;

    /**
     * @Gedmo\Slug(fields={"name"}, separator="_", unique=true)
     * @ORM\Column(length=50, nullable=false, name="name_slug")
     */
    private ?string $nameSlug;

    /**
     * @ORM\Column(name="deleted", type="boolean", options={"default":false})
     */
    private ?bool $deleted;

    /**
     * @ORM\OneToMany(targetEntity=Rule::class, mappedBy="connectorSource", orphanRemoval=true)
     */
    private $rulesWhereIsSource;

    /**
     * @ORM\OneToMany(targetEntity=Rule::class, mappedBy="connectorTarget", orphanRemoval=true)
     */
    private $rulesWhereIsTarget;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false, name="created_by")
     */
    private ?User $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false, name="modified_by")
     */
    private ?User $modifiedBy;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private ?\DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private ?\DateTimeImmutable $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=Solution::class, inversedBy="connectors")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Solution $solution;

    public function __construct()
    {
        $this->rulesWhereIsTarget = new ArrayCollection();
        $this->rulesWhereIsSource = new ArrayCollection();
        $this->connectorParams = new ArrayCollection();
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

    public function setNameSlug(string $nameSlug): self
    {
        $this->nameSlug = $nameSlug;

        return $this;
    }

    public function getNameSlug(): ?string
    {
        return $this->nameSlug;
    }

    public function getSolution(): ?Solution
    {
        return $this->solution;
    }

    public function setSolution(?Solution $solution): self
    {
        $this->solution = $solution;

        return $this;
    }

    public function addConnectorParam(ConnectorParam $connectorParam): self
    {
        $this->connectorParams[] = $connectorParam;

        return $this;
    }

    public function removeConnectorParam(ConnectorParam $connectorParam)
    {
        $this->connectorParams->removeElement($connectorParam);
    }

    public function getConnectorParams(): ?Collection
    {
        return $this->connectorParams;
    }

    public function setConnectorParams(mixed $connectorParams = null): Collection
    {
        return $this->connectorParams = $connectorParams;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    /**
     * @return Collection<int, Rule>
     */
    public function getRulesWhereIsSource(): ?Collection
    {
        return $this->rulesWhereIsSource;
    }

    public function addRuleWhereIsSource(Rule $rule): self
    {
        if (!$this->rulesWhereIsSource->contains($rule)) {
            $this->rulesWhereIsSource[] = $rule;
            $rule->setConnectorSource($this);
        }

        return $this;
    }

    public function removeRuleWhereIsSource(Rule $rule): self
    {
        if ($this->rulesWhereIsSource->removeElement($rule)) {
            // set the owning side to null (unless already changed)
            if ($rule->getConnectorSource() === $this) {
                $rule->setConnectorSource(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Rule>
     */
    public function getRulesWhereIsTarget(): ?Collection
    {
        return $this->rulesWhereIsTarget;
    }

    public function addRulesWhereIsTarget(Rule $rulesWhereIsTarget): self
    {
        if (!$this->rulesWhereIsTarget->contains($rulesWhereIsTarget)) {
            $this->rulesWhereIsTarget[] = $rulesWhereIsTarget;
            $rulesWhereIsTarget->setConnectorTarget($this);
        }

        return $this;
    }

    public function removeRulesWhereIsTarget(Rule $rulesWhereIsTarget): self
    {
        if ($this->rulesWhereIsTarget->removeElement($rulesWhereIsTarget)) {
            // set the owning side to null (unless already changed)
            if ($rulesWhereIsTarget->getConnectorTarget() === $this) {
                $rulesWhereIsTarget->setConnectorTarget(null);
            }
        }

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getModifiedBy(): ?User
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(?User $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function addRulesWhereIsSource(Rule $rulesWhereIsSource): self
    {
        if (!$this->rulesWhereIsSource->contains($rulesWhereIsSource)) {
            $this->rulesWhereIsSource[] = $rulesWhereIsSource;
            $rulesWhereIsSource->setConnectorSource($this);
        }

        return $this;
    }

    public function removeRulesWhereIsSource(Rule $rulesWhereIsSource): self
    {
        if ($this->rulesWhereIsSource->removeElement($rulesWhereIsSource)) {
            // set the owning side to null (unless already changed)
            if ($rulesWhereIsSource->getConnectorSource() === $this) {
                $rulesWhereIsSource->setConnectorSource(null);
            }
        }

        return $this;
    }
}
