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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RuleRelationShipRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="rulerelationship", indexes={@ORM\Index(name="Krule_id", columns={"rule_id"})})
 */
class RuleRelationShip
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Rule", inversedBy="relationsShip")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", nullable=false)
     */
    private ?Rule $rule;

    /**
     * @ORM\Column(name="field_name_source", type="string", length=100, nullable=false)
     */
    private string $fieldNameSource;

    /**
     * @ORM\Column(name="field_name_target", type="string", length=100, nullable=false)
     */
    private string $fieldNameTarget;

    /**
     * @ORM\Column(name="field_id", type="string", length=100, nullable=true)
     */
    private string $fieldId;

    /**
     * @ORM\Column(name="parent", type="boolean", nullable=true)
     */
    private bool $parent;

    /**
     * @ORM\Column(name="deleted", type="boolean", options={"default":0})
     */
    private bool $deleted;

    /**
     * @ORM\Column(name="errorEmpty", type="boolean", options={"default":0})
     */
    private bool $errorEmpty;

    /**
     * @ORM\Column(name="errorMissing", type="boolean", options={"default":1})
     */
    private bool $errorMissing;

    public function getId(): int
    {
        return $this->id;
    }

    public function setFieldNameSource($fieldNameSource): self
    {
        $this->fieldNameSource = $fieldNameSource;

        return $this;
    }

    public function getFieldNameSource(): string
    {
        return $this->fieldNameSource;
    }

    public function setFieldNameTarget($fieldNameTarget): self
    {
        $this->fieldNameTarget = $fieldNameTarget;

        return $this;
    }

    public function getFieldNameTarget(): string
    {
        return $this->fieldNameTarget;
    }

    public function setFieldId($fieldId): self
    {
        $this->fieldId = $fieldId;

        return $this;
    }

    public function getFieldId(): string
    {
        return $this->fieldId;
    }

    public function setParent($parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): bool
    {
        return $this->parent;
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

    public function getRule(): ?Rule
    {
        return $this->rule;
    }

    public function setRule(?Rule $rule): self
    {
        $this->rule = $rule;

        return $this;
    }

    public function getErrorEmpty(): bool
    {
        return $this->errorEmpty;
    }

    public function setErrorEmpty($errorEmpty): self
    {
        $this->errorEmpty = $errorEmpty;

        return $this;
    }

    public function getErrorMissing(): bool
    {
        return $this->errorMissing;
    }

    public function setErrorMissing($errorMissing): self
    {
        $this->errorMissing = $errorMissing;

        return $this;
    }
}
