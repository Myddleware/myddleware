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
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Connector", inversedBy="connectorParams")
     * @ORM\JoinColumn(name="conn_id", referencedColumnName="id")
     */
    private $connector;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=684)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=684)
     */
    private $value;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set connector.
     *
     * @param int $connector
     *
     * @return ConnectorParam
     */
    public function setConnector($connector)
    {
        $this->connector = $connector;

        return $this;
    }

    /**
     * Get connector.
     *
     * @return int
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return ConnectorParam
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set value.
     *
     * @param string $value
     *
     * @return ConnectorParam
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
