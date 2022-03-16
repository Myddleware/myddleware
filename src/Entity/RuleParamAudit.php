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

/**
 * @ORM\Entity(repositoryClass="App\Repository\RuleParamAuditRepository")
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
     * @ORM\Column(name="rule_param_id", type="integer")
     */
    private $ruleParamId;

    /**
     * @ORM\Column(name="modified", type="datetime", nullable=false)
     */
    private $dateModified;

    /**
     * @ORM\Column(name="before_value", type="string", nullable=true)
     */
    private $before;

    /**
     * @ORM\Column(name="after_value", type="string", nullable=true)
     */
    private $after;

    /**
     * @ORM\Column(name="user", type="string", nullable=true)
     */
    private $byUser;

    /**
     * @ORM\Column(name="job_id", type="string", length=255, nullable=true)
     */
    private $job;

    public function getId(): int
    {
        return $this->id;
    }

    public function setRuleParamId(string $ruleParamId): self
    {
        $this->ruleParamId = $ruleParamId;

        return $this;
    }

    public function getRuleParamId(): string
    {
        return $this->ruleParamId;
    }

    public function setDateModified(DateTime $dateModified): self
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    public function getDateModified(): DateTime
    {
        return $this->dateModified;
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

    public function setByUser(string $byUser): self
    {
        $this->byUser = $byUser;

        return $this;
    }

    public function getByUser(): string
    {
        return $this->byUser;
    }

    public function setJob(string $job): self
    {
        $this->job = $job;

        return $this;
    }

    public function getJob(): string
    {
        return $this->job;
    }
}
