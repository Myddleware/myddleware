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

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // slug

/**
 * @ORM\Entity(repositoryClass="App\Repository\RuleRepository")
 * @ORM\HasLifecycleCallbacks()
 *
 * @ORM\Table(name="rule", indexes={@ORM\Index(name="Krule_name", columns={"name"})})
 */
class Rule
{
    /**
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Connector::class, inversedBy="rulesWhereIsSource")
     * @ORM\JoinColumn(nullable=false, name="conn_id_source")
     */
    private $connectorSource;

    /**
     * @ORM\ManyToOne(targetEntity=Connector::class, inversedBy="rulesWhereIsTarget")
     * @ORM\JoinColumn(nullable=false, name="conn_id_target")
     */
    private $connectorTarget;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id", nullable=false)
     */
    private $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="modified_by", referencedColumnName="id", nullable=false)
     */
    private $modifiedBy;

    /**
     * @ORM\Column(name="module_source", type="string", nullable=false)
     */
    private $moduleSource;

    /**
     * @ORM\Column(name="module_target", type="string", nullable=false)
     */
    private $moduleTarget;

    /**
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;

    /**
     * @ORM\Column(name="deleted", type="boolean", options={"default":0})
     */
    private $deleted;

    /**
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @Gedmo\Slug(fields={"name"}, separator="_")
     * @ORM\Column(length=50, nullable=false, name="name_slug")
     */
    private $nameSlug;

    /**
     * @ORM\OneToMany(targetEntity="RuleParam", mappedBy="rule")
     */
    private $params;

    /**
     * @ORM\OneToMany(targetEntity="RuleRelationShip", mappedBy="rule")
     */
    private $relationsShip;

    /**
     * @ORM\OneToMany(targetEntity="RuleOrder", mappedBy="rule")
     */
    private $orders;

    /**
     * @ORM\OneToMany(targetEntity="RuleFilter", mappedBy="rule")
     */
    private $filters;

    /**
     * @ORM\OneToMany(targetEntity="RuleField", mappedBy="rule")
     */
    private $fields;

    /**
     * @ORM\OneToMany(targetEntity="RuleAudit", mappedBy="rule")
     */
    private $audits;

    /**
     * @ORM\OneToMany(targetEntity="Document", mappedBy="rule")
     * @ORM\OrderBy({"sourceDateModified" : "ASC"})
     */
    private $documents;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $updatedAt;

    public function __construct()
    {
        $this->params = new ArrayCollection();
        $this->relationsShip = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->filters = new ArrayCollection();
        $this->fields = new ArrayCollection();
        $this->audits = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist()
     */
    public function preSave()
    {
        $this->id = uniqid();
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setModuleSource(string $moduleSource): self
    {
        $this->moduleSource = $moduleSource;

        return $this;
    }

    public function getModuleSource(): string
    {
        return $this->moduleSource;
    }

    public function setModuleTarget(string $moduleTarget): self
    {
        $this->moduleTarget = $moduleTarget;

        return $this;
    }

    public function getModuleTarget(): string
    {
        return $this->moduleTarget;
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

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setNameSlug(string $nameSlug): self
    {
        $this->nameSlug = $nameSlug;

        return $this;
    }

    public function getNameSlug(): string
    {
        return $this->nameSlug;
    }

    public function setConnectorSource(?Connector $connectorSource): self
    {
        $this->connectorSource = $connectorSource;

        return $this;
    }

    public function getConnectorSource(): ?Connector
    {
        return $this->connectorSource;
    }

    public function getConnectorTarget(): ?Connector
    {
        return $this->connectorTarget;
    }

    public function setConnectorTarget(?Connector $connectorTarget): self
    {
        $this->connectorTarget = $connectorTarget;

        return $this;
    }

    public function getParams(): Collection
    {
        return $this->params;
    }

    public function getParamsValues(): array
    {
        $return = [];
        foreach ($this->getParams() as $ruleParam) {
            $return[$ruleParam->getName()] = ltrim($ruleParam->getValue());
        }

        return $return;
    }

    public function getParamByName(string $name): ?RuleParam
    {
        $ruleParam = $this->getParams()->filter(function (RuleParam $ruleParam) use ($name) {
            return $name === $ruleParam->getName();
        })->first();

        if ($ruleParam instanceof RuleParam) {
            return $ruleParam;
        }

        return null;
    }

    public function addParam(RuleParam $param): self
    {
        if (!$this->params->contains($param)) {
            $this->params[] = $param;
            $param->setRule($this);
        }

        return $this;
    }

    public function removeParam(RuleParam $param): self
    {
        if ($this->params->removeElement($param)) {
            // set the owning side to null (unless already changed)
            if ($param->getRule() === $this) {
                $param->setRule(null);
            }
        }

        return $this;
    }

    public function getRelationsShip(): Collection
    {
        return $this->relationsShip;
    }

    public function isParent(): bool
    {
        foreach ($this->getRelationsShip() as $ruleRelationShip) {
            if ($ruleRelationShip->getParent()) {
                return true;
            }
        }

        return false;
    }

    public function addRelationsShip(RuleRelationShip $relationsShip): self
    {
        if (!$this->relationsShip->contains($relationsShip)) {
            $this->relationsShip[] = $relationsShip;
            $relationsShip->setRule($this);
        }

        return $this;
    }

    public function removeRelationsShip(RuleRelationShip $relationsShip): self
    {
        if ($this->relationsShip->removeElement($relationsShip)) {
            // set the owning side to null (unless already changed)
            if ($relationsShip->getRule() === $this) {
                $relationsShip->setRule(null);
            }
        }

        return $this;
    }

    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(RuleOrder $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->setRule($this);
        }

        return $this;
    }

    public function removeOrder(RuleOrder $order): self
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getRule() === $this) {
                $order->setRule(null);
            }
        }

        return $this;
    }

    public function getFilters(): Collection
    {
        return $this->filters;
    }

    public function addFilter(RuleFilter $filter): self
    {
        if (!$this->filters->contains($filter)) {
            $this->filters[] = $filter;
            $filter->setRule($this);
        }

        return $this;
    }

    public function removeFilter(RuleFilter $filter): self
    {
        if ($this->filters->removeElement($filter)) {
            // set the owning side to null (unless already changed)
            if ($filter->getRule() === $this) {
                $filter->setRule(null);
            }
        }

        return $this;
    }

    public function getAudits(): Collection
    {
        return $this->audits;
    }

    public function addAudit(RuleAudit $audit): self
    {
        if (!$this->audits->contains($audit)) {
            $this->audits[] = $audit;
            $audit->setRule($this);
        }

        return $this;
    }

    public function removeAudit(RuleAudit $audit): self
    {
        if ($this->audits->removeElement($audit)) {
            // set the owning side to null (unless already changed)
            if ($audit->getRule() === $this) {
                $audit->setRule(null);
            }
        }

        return $this;
    }

    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function addField(RuleField $field): self
    {
        if (!$this->fields->contains($field)) {
            $this->fields[] = $field;
            $field->setRule($this);
        }

        return $this;
    }

    public function removeField(RuleField $field): self
    {
        if ($this->fields->removeElement($field)) {
            // set the owning side to null (unless already changed)
            if ($field->getRule() === $this) {
                $field->setRule(null);
            }
        }

        return $this;
    }

    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function getDocumentsByStatus($status): Collection
    {
        return $this->getDocuments()->filter(function (Document $document) use ($status) {
            return $status === $document->getStatus() && false === $document->getDeleted();
        });
    }

    public function addDocument(Document $document): self
    {
        if (!$this->documents->contains($document)) {
            $this->documents[] = $document;
            $document->setRule($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): self
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getRule() === $this) {
                $document->setRule(null);
            }
        }

        return $this;
    }

    public function getChildRules(): Collection
    {
        return $this->getRelationsShip()->filter(function (RuleRelationShip $ruleRelationShip) {
            return true === $ruleRelationShip->getParent();
        });
    }

    public function isChild(): bool
    {
        return $this->getChildRules()->count() > 0;
    }

    public function getSourceFields(): array
    {
        $items = [];
        foreach ($this->getFields() as $ruleField) {
            // Plusieurs champs source peuvent être utilisé pour un seul champ cible
            $fields = explode(';', $ruleField->getSource());
            foreach ($fields as $field) {
                $items[] = ltrim($field);
            }
        }
        // Lecture des relations de la règle
        if ($this->getRelationsShip()->count()) {
            foreach ($this->getRelationsShip() as $ruleRelationship) {
                $items[] = ltrim($ruleRelationship->getFieldNameSource());
            }
        }

        return array_unique($items);
    }

    public function getTargetFields(): array
    {
        $items = [];
        foreach ($this->getFields() as $ruleField) {
            $items[] = ltrim($ruleField->getSource());
        }
        // Lecture des relations de la règle
        if ($this->getRelationsShip()->count()) {
            foreach ($this->getRelationsShip() as $ruleRelationship) {
                $items[] = ltrim($ruleRelationship->getFieldNameTarget());
            }
        }

        return array_unique($items);
    }

    public function getFieldsArray(): array
    {
        $items = [];
        foreach ($this->getFields() as $ruleField) {
            $items[] = [
                'source_field_name' => $ruleField->getSource(),
                'target_field_name' => $ruleField->getTarget(),
            ];
        }

        return array_unique($items);
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

    public function __toString(): string
    {
        return $this->name;
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
}
