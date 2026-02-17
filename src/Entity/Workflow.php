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
use Exception;
use Doctrine\Common\Collections\Criteria;

/**
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="App\Repository\WorkflowRepository")
 * @ORM\Table(name="workflow", indexes={@ORM\Index(name="index_rule_id", columns={"rule_id"})})
 */
class Workflow
{

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Rule", inversedBy="workflows")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", nullable=false)
     */
    private Rule $rule;

    /**
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private $dateCreated;

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
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    private string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private string $description;
	
	/**
     * @ORM\Column(name="`condition`", type="text", nullable=false)
     */
    private string $condition;

	/**
     * @ORM\Column(name="active", type="boolean", options={"default":1})
     */
    private bool $active;

    /**
     * @ORM\Column(name="`order`", type="integer", length=3, nullable=false, options={"default": 1})
     */
    private int $order;

	/**
     * @ORM\Column(name="deleted", type="boolean", options={"default":0})
     */
    private bool $deleted;
	
/**
 * @var Collection|WorkflowAction[]
 *
 * @ORM\OneToMany(targetEntity="WorkflowAction", mappedBy="workflow")
 */
private $workflowActions;
	
	/**
     * @var WorkflowLog[]
     *
     * @ORM\OneToMany(targetEntity="WorkflowLog", mappedBy="workflow")
     */
    private $workflowLogs;
	
	public function __construct()
    {
        $this->workflowActions = new ArrayCollection();
        $this->workflowLogs = new ArrayCollection();
        $this->dateCreated = new \DateTime();
        $this->dateModified = new \DateTime();
        $this->active = true;
        $this->deleted = false;
        $this->order = 1;
    }
	
    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getRule(): ?Rule
    {
        return $this->rule;
    }

    public function setRule(?Rule $rule): self
    {
        $this->rule = $rule;
        return $this;
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
	    
	public function setName($name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
	
	public function setDescription($description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
	
	public function getCondition(): string
    {
        return $this->condition;
    }

    public function setCondition($condition): self
    {
        $this->condition = $condition;
        return $this;
    }

	public function setOrder($order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getOrder(): string
    {
        return $this->order;
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

	public function setActive($active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }


/**
 * @return Collection|WorkflowAction[]
 */
public function getWorkflowActions(): Collection
{
    $criteria = Criteria::create()
        ->where(Criteria::expr()->neq('deleted', 1)) // Assuming 'deleted' is the field name and it's directly accessible.
        ->orderBy(['order' => Criteria::ASC]); // Adjust 'order' to your actual field name if different.

    return $this->workflowActions->matching($criteria);
}

    public function addWorkflowActions(WorkflowAction $workflowAction): self
    {
        if (!$this->workflowActions->contains($workflowAction)) {
            $this->workflowActions[] = $workflowAction;
            $workflow->setWorkflow($this);
        }
        return $this;
    }

    public function removeWorkflowAction(WorkflowAction $workflowAction): self
    {
        if ($this->workflowActions->removeElement($workflowAction)) {
            // set the owning side to null (unless already changed)
            if ($workflowAction->getWorkflow() === $this) {
                $workflowAction->setWorkflow(null);
            }
        }
        return $this;
    }
	
	/**
     * @return Collection|WorkflowLog[]
     */
    public function getWorkflowLogs(): Collection
    {
        return $this->workflowLogs;
    }

    public function addWorkflowLogs(WorkflowLog $workflowLog): self
    {
        if (!$this->workflowLogs->contains($workflowLog)) {
            $this->workflowLogs[] = $workflowLog;
            $workflow->setWorkflow($this);
        }
        return $this;
    }

    public function removeWorkflowLog(WorkflowLog $workflowLog): self
    {
        if ($this->workflowLogs->removeElement($workflowLog)) {
            // set the owning side to null (unless already changed)
            if ($workflowLog->getWorkflow() === $this) {
                $workflowLog->setWorkflow(null);
            }
        }
        return $this;
    }
}
