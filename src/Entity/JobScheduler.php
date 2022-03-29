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

/**
 * @ORM\Table(name="jobscheduler")
 * @ORM\Entity(repositoryClass="App\Repository\JobSchedulerRepository")
 */
class JobScheduler implements \Stringable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="command", type="string", length=50, nullable=false)
     */
    private $command;

    /**
     * @ORM\Column(name="paramName1", type="string", length=50, nullable=true)
     */
    private $paramName1;

    /**
     * @var string
     *
     * @ORM\Column(name="paramValue1", type="text", nullable=true)
     */
    private $paramValue1;

    /**
     * @ORM\Column(name="paramName2", type="string", length=50, nullable=true)
     */
    private $paramName2;

    /**
     * @var string
     *
     * @ORM\Column(name="paramValue2", type="text", nullable=true)
     */
    private $paramValue2;

    /**
     * @ORM\Column(name="period", type="integer", length=6,  nullable=false, options={"default":5})
     */
    private $period;

    /**
     * @ORM\Column(name="lastRun", type="datetime", nullable=true)
     */
    private $lastRun;

    /**
     * @ORM\Column(name="active", type="boolean", options={"default":true})
     */
    private $active;

    /**
     * @ORM\Column(name="jobOrder", type="integer", length=3, nullable=true)
     */
    private $jobOrder;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $modifiedBy;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setCommand(string $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setParamName1(string $paramName1): self
    {
        $this->paramName1 = $paramName1;

        return $this;
    }

    public function getParamName1(): string
    {
        return $this->paramName1;
    }

    public function setParamValue1(string $paramValue1): self
    {
        $this->paramValue1 = $paramValue1;

        return $this;
    }

    public function getParamValue1(): string
    {
        return $this->paramValue1;
    }

    public function setParamName2(string $paramName2): self
    {
        $this->paramName2 = $paramName2;

        return $this;
    }

    public function getParamName2(): string
    {
        return $this->paramName2;
    }

    public function setParamValue2(string $paramValue2): self
    {
        $this->paramValue2 = $paramValue2;

        return $this;
    }

    public function getParamValue2(): string
    {
        return $this->paramValue2;
    }

    public function setPeriod(int $period): self
    {
        $this->period = $period;

        return $this;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function setLastRun(string $lastRun): self
    {
        $this->lastRun = $lastRun;

        return $this;
    }

    public function getLastRun(): string
    {
        return $this->lastRun;
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

    public function setJobOrder(int $jobOrder): self
    {
        $this->jobOrder = $jobOrder;

        return $this;
    }

    public function getJobOrder(): int
    {
        return $this->jobOrder;
    }

    public function __toString(): string
    {
        return $this->id;
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
}
