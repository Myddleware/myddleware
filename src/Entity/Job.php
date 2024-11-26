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
use Shapecode\Bundle\CronBundle\Entity\CronJob;

/**
 * @ORM\Entity(repositoryClass="App\Repository\JobRepository")
 * @ORM\Table(name="job", indexes={
 *  @ORM\Index(name="index_status_begin", columns={"status","begin"})
 *})
 */
class Job
{
    /**
     * @ORM\Column(name="id", type="string", length=255, nullable=false)
     * @ORM\Id
     */
    private string $id;

    /**
     * @ORM\Column(name="status", type="string", length=50, nullable=false)
     */
    private string $status;

    /**
     * @ORM\Column(name="param", type="text", nullable=false)
     */
    private string $param;

    /**
     * @ORM\Column(name="begin", type="datetime", nullable=false)
     */
    private DateTime $begin;

    /**
     * @ORM\Column(name="end", type="datetime",  nullable=true, options={"default":NULL})
     */
    private ?DateTime $end;

    /**
     * @ORM\Column(name="message", type="text",  nullable=true, options={"default":NULL})
     */
    private ?string $message;

    /**
     * @ORM\Column(name="open", type="integer", length=6,  nullable=true, options={"default":0})
     */
    private ?int $open;

    /**
     * @ORM\Column(name="close", type="integer", length=6,  nullable=true, options={"default":0})
     */
    private ?int $close;

    /**
     * @ORM\Column(name="cancel", type="integer", length=6,  nullable=true, options={"default":0})
     */
    private ?int $cancel;

    /**
     * @ORM\Column(name="manual", type="boolean",  nullable=true, options={"default":0})
     */
    private ?bool $manual;

    /**
     * @ORM\Column(name="api", type="boolean",  nullable=true, options={"default":0})
     */
    private ?bool $api;

    /**
     * @ORM\Column(name="error", type="integer", length=6,  nullable=true, options={"default":0})
     */
    private ?int $error;

    /**
     * @ORM\OneToMany(targetEntity="Log", mappedBy="job")
     */
    private $logs;
	
	/**
     * @var WorkflowLog[]
     *
     * @ORM\OneToMany(targetEntity="WorkflowLog", mappedBy="job")
     */
    private $workflowLogs;
	
	/**
     * @ORM\ManyToOne(targetEntity="Shapecode\Bundle\CronBundle\Entity\CronJob", inversedBy="jobs")
     * @ORM\JoinColumn(name="cron_job_id", referencedColumnName="id", nullable=true)
     */
    private CronJob $cronJob;
	

    public function __construct()
    {
        $this->begin = new DateTime();
        $this->logs = new ArrayCollection();
        $this->workflowLogs = new ArrayCollection();
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setParam($param): self
    {
        $this->param = $param;

        return $this;
    }

    public function getParam(): string
    {
        return $this->param;
    }

    public function setBegin($begin): self
    {
        $this->begin = $begin;

        return $this;
    }

    public function getBegin(): DateTime
    {
        return $this->begin;
    }

    public function setEnd($end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    public function setMessage($message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage()
    {
        // Don't show ip address
        $patterns = "/[0-9]{0,3}\.[0-9]{0,3}\.[0-9]{0,3}\.[0-9]{0,3}/";
        $replacements = 'XXX.XXX.XXX.XXX';

        return preg_replace($patterns, $replacements, $this->message);
    }

    public function setOpen($open): self
    {
        $this->open = $open;

        return $this;
    }

    public function getOpen(): ?int
    {
        return $this->open;
    }

    public function setClose($close): self
    {
        $this->close = $close;

        return $this;
    }

    public function getClose(): ?int
    {
        return $this->close;
    }

    public function setCancel($cancel): self
    {
        $this->cancel = $cancel;

        return $this;
    }

    public function getCancel(): ?int
    {
        return $this->cancel;
    }

    public function setManual($manual): self
    {
        $this->manual = $manual;

        return $this;
    }

    public function getManual(): ?bool
    {
        return $this->manual;
    }

    public function setApi($api): self
    {
        $this->api = $api;

        return $this;
    }

    public function getApi(): ?bool
    {
        return $this->api;
    }

    public function setError($error): self
    {
        $this->error = $error;

        return $this;
    }

    public function getError(): ?int
    {
        return $this->error;
    }

    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(Log $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs[] = $log;
            $log->setJob($this);
        }

        return $this;
    }

    public function removeLog(Log $log): self
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getJob() === $this) {
                $log->setJob(null);
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
            $job->setJob($this);
        }
        return $this;
    }

    public function removeWorkflowLog(WorkflowLog $workflowLog): self
    {
        if ($this->workflowLogs->removeElement($workflowLog)) {
            // set the owning side to null (unless already changed)
            if ($workflowLog->getWorkflow() === $this) {
                $workflowLog->setJob(null);
            }
        }
        return $this;
    }
	
	public function setCronJob($cronJob): self
    {
		if ($cronJob != null) {
			$this->cronJob = $cronJob;
		}
        return $this;
    }

    public function getCronJob(): ?bool
    {
        return $this->cronJob;
    }
}
