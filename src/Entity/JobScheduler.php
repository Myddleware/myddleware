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
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private $dateCreated;

    /**
     * @ORM\Column(name="date_modified", type="datetime")
     */
    private $dateModified;

    /**
     * @ORM\Column(name="created_by", type="integer", nullable=false, options={"default":1})
     */
    private $createdBy;

    /**
     * @ORM\Column(name="modified_by", type="integer", nullable=false, options={"default":1})
     */
    private $modifiedBy;

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
     * @ORM\Column(name="active", type="boolean", options={"default":1})
     */
    private $active;

    /**
     * @ORM\Column(name="jobOrder", type="integer", length=3, nullable=true)
     */
    private $jobOrder;

    public function getId(): int
    {
        return $this->id;
    }

    public function setDateCreated(DateTime $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateCreated(): DateTime
    {
        return $this->dateCreated;
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

    public function setCreatedBy(int $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function setModifiedBy(int $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    public function getModifiedBy(): int
    {
        return $this->modifiedBy;
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

    public function setPeriod(string $period): self
    {
        $this->period = $period;

        return $this;
    }

    public function getPeriod(): string
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

    public function setActive(string $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getActive(): string
    {
        return $this->active;
    }

    public function setJobOrder(string $jobOrder): self
    {
        $this->jobOrder = $jobOrder;

        return $this;
    }

    public function getJobOrder(): string
    {
        return $this->jobOrder;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
