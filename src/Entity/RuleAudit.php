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
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RuleAuditRepository")
 * @ORM\Table(name="ruleaudit", indexes={@ORM\Index(name="index_ruleid", columns={"rule_id"})})
 */
class RuleAudit
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Rule", inversedBy="audits")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", nullable=false)
     */
    private $rule;

    /**
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    private $dateCreated;

    /**
     * @ORM\Column(name="data", type="array", nullable=false)
     */
    private $data;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDateCreated(): DateTime
    {
        return $this->dateCreated;
    }

    public function setDateCreated(DateTime $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): self
    {
        $this->data = $data;

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
}
