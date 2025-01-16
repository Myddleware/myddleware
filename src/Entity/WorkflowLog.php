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
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="App\Repository\WorkflowLogRepository")
 * @ORM\Table(name="workflowlog", indexes={@ORM\Index(name="index_workflow_id", columns={"workflow_id"})})
 * @ORM\Table(name="workflowlog", indexes={@ORM\Index(name="index_job_id", columns={"job_id"})})
 */
class WorkflowLog
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Workflow", inversedBy="workflowLogs")
     * @ORM\JoinColumn(name="workflow_id", referencedColumnName="id", nullable=false)
     */
    private Workflow $workflow;
	
	/**
     * @ORM\ManyToOne(targetEntity="Job", inversedBy="workflowLogs")
     * @ORM\JoinColumn(name="job_id", referencedColumnName="id", nullable=false)
     */
    private Job $job;
	
	/**
     * @ORM\ManyToOne(targetEntity="Document", inversedBy="triggerDocuments")
     * @ORM\JoinColumn(name="trigger_document_id", referencedColumnName="id", nullable=false)
     */
    private Document $triggerDocument;

	/**
     * @ORM\ManyToOne(targetEntity="Document", inversedBy="generateDocuments")
     * @ORM\JoinColumn(name="generate_document_id", referencedColumnName="id", nullable=true)
     */
    private ?Document $generateDocument= null;

	/**
     * @ORM\ManyToOne(targetEntity="WorkflowAction")
     * @ORM\JoinColumn(name="action_id", referencedColumnName="id", nullable=true)
     */
    private WorkflowAction $action;
	
	/**
     * @ORM\Column(name="status", type="string",  nullable=true, options={"default":NULL})
     */
    private ?string $status;
	
	/**
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private DateTime $dateCreated;
	
    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id", nullable=true)
     */
    private ?User $createdBy = null;
	
    /**
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private ?string $message = null;


    public function getId(): int
    {
        return $this->id;
    }

    public function getWorkflow(): ?Workflow
    {
        return $this->workflow;
    }

    public function setWorkflow(?Workflow $workflow): self
    {
        $this->workflow = $workflow;
        return $this;
    }
	
	public function getJob(): ?Job
    {
        return $this->job;
    }

    public function setJob(?Job $job): self
    {
        $this->job = $job;
        return $this;
    }
	
	public function getTriggerDocument(): ?Document
    {
        return $this->triggerDocument;
    }

    public function setTriggerDocument(?Document $document): self
    {
        $this->triggerDocument = $document;
        return $this;
    }
	
	public function getGenerateDocument(): ?Document
    {
        return $this->generateDocument;
    }

    public function setGenerateDocument(?Document $document): self
    {
        $this->generateDocument = $document;
        return $this;
    }
	
	public function setStatus($status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
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

    
	public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }
	
	public function getAction(): WorkflowAction
    {
        return $this->action;
    }

    public function setAction($action): self
    {
        $this->action = $action;
        return $this;
    }
	
	public function setMessage($message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
