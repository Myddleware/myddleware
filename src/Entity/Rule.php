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

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RuleRepository")
 * @ORM\HasLifecycleCallbacks()
 *
 * @ORM\Table(name="rule", indexes={
 *	 @ORM\Index(name="Krule_name", columns={"name"}),
 *	 @ORM\Index(name="index_read_job_lock", columns={"read_job_lock"})
 * })
 */
class Rule
{
    /**
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     */
    private string $id;

    /**
     * @ORM\ManyToOne(targetEntity="Connector")
     * @ORM\JoinColumn(name="conn_id_source", referencedColumnName="id")
     */
    private Connector $connectorSource;

    /**
     * @ORM\ManyToOne(targetEntity="Connector")
     * @ORM\JoinColumn(name="conn_id_target", referencedColumnName="id")
     */
    private Connector $connectorTarget;

    /**
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private DateTime $dateCreated;

    /**
     * @ORM\Column(name="date_modified", type="datetime", nullable=false)
     */
    private DateTime $dateModified;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id", nullable=false)
     */
    private User $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="modified_by", referencedColumnName="id", nullable=false)
     */
    private User $modifiedBy;

    /**
     * @ORM\Column(name="module_source", type="string", nullable=false)
     */
    private string $moduleSource;

    /**
     * @ORM\Column(name="module_target", type="string", nullable=false)
     */
    private string $moduleTarget;

    /**
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private bool $active;

    /**
     * @ORM\Column(name="deleted", type="boolean", options={"default":0})
     */
    private bool $deleted;

    /**
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private string $name;

    /**
     * @Gedmo\Slug(fields={"name"}, separator="_")
     * @ORM\Column(length=50, nullable=false, name="name_slug")
     */
    private string $nameSlug;

    /**
     * @var RuleParam[]
     *
     * @ORM\OneToMany(targetEntity="RuleParam", mappedBy="rule")
     */
    private $params;

    /**
     * @var RuleRelationShip[]
     *
     * @ORM\OneToMany(targetEntity="RuleRelationShip", mappedBy="rule")
     */
    private $relationsShip;

    /**
     * @var RuleOrder[]
     *
     * @ORM\OneToMany(targetEntity="RuleOrder", mappedBy="rule")
     */
    private $orders;

    /**
     * @var RuleFilter[]
     *
     * @ORM\OneToMany(targetEntity="RuleFilter", mappedBy="rule")
     */
    private $filters;

    /**
     * @var RuleField[]
     *
     * @ORM\OneToMany(targetEntity="RuleField", mappedBy="rule")
     */
    private $fields;

    /**
     * @var RuleAudit[]
     *
     * @ORM\OneToMany(targetEntity="RuleAudit", mappedBy="rule")
     */
    private $audits;

     /**
     * @var Workflow[]
     *
     * @ORM\OneToMany(targetEntity="Workflow", mappedBy="rule")
     */
    private $workflows;

    /**
     * @var Document[]
     *
     * @ORM\OneToMany(targetEntity="Document", mappedBy="rule")
     * @ORM\OrderBy({"sourceDateModified" : "ASC"})
     */
    private $documents;

    /**
     * @ORM\ManyToOne(targetEntity="RuleGroup", inversedBy="rules")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", nullable=true)
     */
    private ?RuleGroup $group = null;
	
	/**
     * @ORM\Column(name="read_job_lock", type="string", length=23, nullable=true, options={"default":NULL})
     */
    private string $readJobLock;

    public function __construct()
    {
        $this->params = new ArrayCollection();
        $this->relationsShip = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->filters = new ArrayCollection();
        $this->fields = new ArrayCollection();
        $this->audits = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->workflows = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist()
     */
    public function preSave()
    {
        $this->id = uniqid();
    }

    public function setId($id): Rule
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setDateCreated($dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateCreated(): DateTime
    {
        return $this->dateCreated;
    }

    public function setDateModified($dateModified): self
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    public function getDateModified(): DateTime
    {
        return $this->dateModified;
    }

    public function setModuleSource($moduleSource): self
    {
        $this->moduleSource = $moduleSource;

        return $this;
    }

    public function getModuleSource(): string
    {
        return $this->moduleSource;
    }

    public function setModuleTarget($moduleTarget): self
    {
        $this->moduleTarget = $moduleTarget;

        return $this;
    }

    public function getModuleTarget(): string
    {
        return $this->moduleTarget;
    }

    public function setActive($active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setDeleted($deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getDeleted(): bool
    {
        return $this->deleted;
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

    public function setNameSlug($nameSlug): self
    {
        $this->nameSlug = $nameSlug;

        return $this;
    }

    public function getNameSlug(): string
    {
        return $this->nameSlug;
    }
	
	public function setReadJobLock($readJobLock): self
    {
        $this->readJobLock = $readJobLock;
        return $this;
    }

    public function getReadJobLock(): string
    {
        if (empty($this->readJobLock)) {
            return '';
        }
        return $this->readJobLock;
    }

    public function setConnectorSource(Connector $connectorSource): self
    {
        $this->connectorSource = $connectorSource;

        return $this;
    }

    public function getConnectorSource(): Connector
    {
        return $this->connectorSource;
    }

    public function setConnectorTarget(Connector $connectorTarget): self
    {
        $this->connectorTarget = $connectorTarget;

        return $this;
    }

    public function getConnectorTarget(): Connector
    {
        return $this->connectorTarget;
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

    public function getFormulaByFieldName(string $fieldName): string
    {
        $formula = "";
        foreach ($this->getFields() as $ruleField) {
            if ($ruleField->getSource() === $fieldName) {
                $formula = $ruleField->getFormula();
            }
        }
        return $formula;
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

    /**
     * @return Collection|RuleRelationShip[]
     */
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

    /**
     * @return Collection|RuleOrder[]
     */
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

    /**
     * @return Collection|RuleFilter[]
     */
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

    /**
     * @return Collection|RuleAudit[]
     */
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

    /**
     * @return Collection|RuleField[]
     */
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

	/**
     * @return Collection|Workflow[]
     */
    public function getWorkflows(): Collection
    {
        return $this->workflows;
    }

    public function addWorkflows(Workflow $workflow): self
    {
        if (!$this->workflows->contains($workflow)) {
            $this->workflows[] = $workflow;
            $workflow->setRule($this);
        }
        return $this;
    }

    public function removeWorkflow(Workflow $workflow): self
    {
        if ($this->workflows->removeElement($workflow)) {
            // set the owning side to null (unless already changed)
            if ($workflow->getRule() === $this) {
                $workflow->setRule(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection|Document[]
     */
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

    /**
     * @return Collection|RuleRelationShip[]
     */
    public function getChildRules()
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

    public function __toString()
    {
        return $this->id;
    }

    public static function getNameTest(): array
    {

        return ['ruleName', 'rule'];
    }

    public function isNameSet(): bool
    {
        // test with isset if the name property is set
        return isset($this->name);
    }

    public function isModuleSourceSet(): bool
    {
        return isset($this->moduleSource);
    }

    public function isModuleTargetSet(): bool
    {
        return isset($this->moduleTarget);
    }
	
	public function setGroup(?RuleGroup $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function getGroup(): ?RuleGroup
    {
        return $this->group;
    }

}
