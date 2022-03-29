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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RuleParamRepository")
 * @ORM\Table(name="ruleparam", indexes={@ORM\Index(name="Krule_id", columns={"rule_id"})})
 */
class RuleParam
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Rule", inversedBy="params")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", nullable=false)
     */
    private $rule;

    /**
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=false)
     */
    private $value;

    /**
     * @ORM\OneToMany(targetEntity=RuleParamAudit::class, mappedBy="ruleParam")
     */
    private $ruleParamAudits;

    public function __construct()
    {
        $this->ruleParamAudits = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
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

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
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

    /**
     * @return Collection<int, RuleParamAudit>
     */
    public function getRuleParamAudits(): Collection
    {
        return $this->ruleParamAudits;
    }

    public function addRuleParamAudit(RuleParamAudit $ruleParamAudit): self
    {
        if (!$this->ruleParamAudits->contains($ruleParamAudit)) {
            $this->ruleParamAudits[] = $ruleParamAudit;
            $ruleParamAudit->setRuleParam($this);
        }

        return $this;
    }

    public function removeRuleParamAudit(RuleParamAudit $ruleParamAudit): self
    {
        if ($this->ruleParamAudits->removeElement($ruleParamAudit)) {
            // set the owning side to null (unless already changed)
            if ($ruleParamAudit->getRuleParam() === $this) {
                $ruleParamAudit->setRuleParam(null);
            }
        }

        return $this;
    }
}
