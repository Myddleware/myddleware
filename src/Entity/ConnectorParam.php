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
 * @ORM\Table(name="connectorparam")
 * @ORM\Entity(repositoryClass="App\Repository\ConnectorParamRepository")
 */
class ConnectorParam
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Connector", inversedBy="connectorParams")
     * @ORM\JoinColumn(name="conn_id", referencedColumnName="id")
     */
    private Connector $connector;

    /**
     * @ORM\Column(name="name", type="string", length=684)
     */
    private ?string $name;

    /**
     * @ORM\Column(name="value", type="string", length=684, nullable=true)
     */
    private $value;

    public function getId()
    {
        return $this->id;
    }

    public function setConnector($connector): self
    {
        $this->connector = $connector;

        return $this;
    }

    public function getConnector(): Connector
    {
        return $this->connector;
    }

    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }
}
