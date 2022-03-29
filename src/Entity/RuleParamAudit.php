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

use Doctrine\ORM\Mapping as ORM;
use App\Repository\RuleParamAuditRepository;

/**
 * @ORM\Entity(repositoryClass=RuleParamAuditRepository::class)
 * @ORM\Table(name="ruleparamaudit", indexes={
 *  @ORM\Index(name="index_job_id", columns={"job_id"}),
 *  @ORM\Index(name="index_rule_param_id", columns={"rule_param_id"})
 *})
 */
class RuleParamAudit
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\PrePersist()
     */
    public function preSave()
    {
        $this->id = uniqid();
    }

    /**
     * @ORM\Column(name="before_value", type="string", nullable=true)
     */
    private $before;

    /**
     * @ORM\Column(name="after_value", type="string", nullable=true)
     */
    private $after;

    /**
     * @ORM\ManyToOne(targetEntity=RuleParam::class, inversedBy="ruleParamAudits")
     * @ORM\JoinColumn(nullable=false)
     */
    private $ruleParam;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="ruleParamAudits")
     * @ORM\JoinColumn(nullable=false)
     */
    private $modifiedBy;

    /**
     * @ORM\ManyToOne(targetEntity=Job::class, inversedBy="ruleParamAudits")
     */
    private $job;

    public function getId(): int
    {
        return $this->id;
    }

    public function setBefore(string $before): self
    {
        $this->before = $before;

        return $this;
    }

    public function getBefore(): string
    {
        return $this->before;
    }

    public function setAfter(string $after): self
    {
        $this->after = $after;

        return $this;
    }

    public function getAfter(): string
    {
        return $this->after;
    }

    public function getRuleParam(): ?RuleParam
    {
        return $this->ruleParam;
    }

    public function setRuleParam(?RuleParam $ruleParam): self
    {
        $this->ruleParam = $ruleParam;

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

    public function getModifiedBy(): ?User
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(?User $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;

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
}
