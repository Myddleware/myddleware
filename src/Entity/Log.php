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
class Log
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="created", type="datetime", nullable=false)
     */
    private DateTime $created;

    /**
     * @ORM\Column(name="type", type="string", length=5, nullable=false)
     */
    private string $type;

    /**
     * @ORM\Column(name="msg", type="text", nullable=false)
     */
    private string $message;

    /**
     * @ORM\ManyToOne(targetEntity="Rule", inversedBy="orders")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id", nullable=true)
     */
    private ?Rule $rule;

    /**
     * @ORM\ManyToOne(targetEntity="Document", inversedBy="logs")
     * @ORM\JoinColumn(name="doc_id", referencedColumnName="id", nullable=true)
     */
    private ?Document $document;

    /**
     * @ORM\Column(name="ref_doc_id", type="string", length=255, nullable=true)
     */
    private ?string $ref;

    /**
     * @ORM\ManyToOne(targetEntity="Job", inversedBy="logs")
     * @ORM\JoinColumn(name="job_id", referencedColumnName="id", nullable=false)
     */
    private Job $job;

    public function getId(): int
    {
        return $this->id;
    }

    public function setCreated($created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setMessage($message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setDocument($document): self
    {
        $this->document = $document;

        return $this;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setRef($ref): self
    {
        $this->ref = $ref;

        return $this;
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function setJob($job): self
    {
        $this->job = $job;

        return $this;
    }

    public function getJob(): Job
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
}
