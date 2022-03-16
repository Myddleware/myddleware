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
 * @ORM\Entity(repositoryClass="App\Repository\LogRepository")
 * @ORM\Table(name="log", indexes={
 *  @ORM\Index(name="index_doc_id", columns={"doc_id"}),
 *  @ORM\Index(name="index_job_id", columns={"job_id"}),
 *  @ORM\Index(name="index_rule_id",columns={"rule_id"})
 *})
 */
class Log implements \Stringable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false)
     */
    private $dateCreated;

    /**
     * @ORM\Column(name="type", type="string", length=5, nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="msg", type="text", nullable=false)
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity="Rule", inversedBy="orders")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", nullable=true)
     */
    private $rule;

    /**
     * @ORM\ManyToOne(targetEntity="Document", inversedBy="logs")
     * @ORM\JoinColumn(name="doc_id", referencedColumnName="id", nullable=true)
     */
    private $document;

    /**
     * @ORM\Column(name="ref_doc_id", type="string", length=255, nullable=true)
     */
    private $ref;

    /**
     * @ORM\ManyToOne(targetEntity="Job", inversedBy="logs")
     * @ORM\JoinColumn(name="job_id", referencedColumnName="id", nullable=false)
     */
    private $job;

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

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setDocument(string $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getDocument(): string
    {
        return $this->document;
    }

    public function setRef(string $ref): self
    {
        $this->ref = $ref;

        return $this;
    }

    public function getRef(): string
    {
        return $this->ref;
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

    public function getRule(): ?Rule
    {
        return $this->rule;
    }

    public function setRule(?Rule $rule): self
    {
        $this->rule = $rule;

        return $this;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
