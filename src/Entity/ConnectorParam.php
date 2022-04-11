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

use App\Entity\Solution;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ConnectorParamRepository;

/**
 * @ORM\Table(name="connectorparam")
 * @ORM\Entity(repositoryClass=ConnectorParamRepository::class)
 */
class ConnectorParam implements \Stringable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Connector::class, inversedBy="connectorParams")
     * @ORM\JoinColumn(name="conn_id", referencedColumnName="id")
     */
    private $connector;

    /**
     * @ORM\Column(name="name", type="string", length=684)
     */
    private $name;

    /**
     * @ORM\Column(name="value", type="string", length=684)
     */
    private $value;

    private $solution;

    public function getId(): int
    {
        return $this->id;
    }

    public function setConnector(Connector $connector): self
    {
        $this->connector = $connector;

        return $this;
    }

    public function getConnector(): Connector
    {
        return $this->connector;
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

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getSolution(): ?Solution
    {
        return $this->solution;
    }

    public function setSolution(?Solution $solution): self
    {
        $this->solution = $solution;

        return $this;
    }
}
