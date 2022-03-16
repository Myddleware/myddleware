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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\JobRepository")
 * @ORM\Table(name="job", indexes={
 *  @ORM\Index(name="index_status", columns={"status"})
 *})
 */
class Job implements \Stringable
{
    /**
     * @ORM\Column(name="id", type="string", length=255, nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\Column(name="status", type="string", length=50, nullable=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="param", type="text", nullable=false)
     */
    private $param;

    /**
     * @ORM\Column(name="begin", type="datetime", nullable=false)
     */
    private $begin;

    /**
     * @ORM\Column(name="end", type="datetime",  nullable=true, options={"default":NULL})
     */
    private $end;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text",  nullable=true, options={"default":NULL})
     */
    private $message;

    /**
     * @ORM\Column(name="open", type="integer", length=6,  nullable=true, options={"default":0})
     */
    private $open;

    /**
     * @ORM\Column(name="close", type="integer", length=6,  nullable=true, options={"default":0})
     */
    private $close;

    /**
     * @ORM\Column(name="cancel", type="integer", length=6,  nullable=true, options={"default":0})
     */
    private $cancel;

    /**
     * @ORM\Column(name="manual", type="boolean",  nullable=true, options={"default":0})
     */
    private $manual;

    /**
     * @ORM\Column(name="api", type="boolean",  nullable=true, options={"default":0})
     */
    private $api;

    /**
     * @ORM\Column(name="error", type="integer", length=6,  nullable=true, options={"default":0})
     */
    private $error;

    /**
     * @ORM\OneToMany(targetEntity="Log", mappedBy="job")
     */
    private $logs;

    public function __construct()
    {
        $this->begin = new DateTime();
        $this->logs = new ArrayCollection();
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setParam(string $param): self
    {
        $this->param = $param;

        return $this;
    }

    public function getParam(): string
    {
        return $this->param;
    }

    public function setBegin(DateTime $begin): self
    {
        $this->begin = $begin;

        return $this;
    }

    public function getBegin(): DateTime
    {
        return $this->begin;
    }

    public function setEnd(DateTime $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getEnd(): DateTime
    {
        return $this->end;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): string
    {
        // Don't show ip address
        $patterns = "/[0-9]{0,3}\.[0-9]{0,3}\.[0-9]{0,3}\.[0-9]{0,3}/";
        $replacements = 'XXX.XXX.XXX.XXX';

        return preg_replace($patterns, $replacements, $this->message);
    }

    public function setOpen(int $open): self
    {
        $this->open = $open;

        return $this;
    }

    public function getOpen(): int
    {
        return $this->open;
    }

    public function setClose(int $close): self
    {
        $this->close = $close;

        return $this;
    }

    public function getClose(): int
    {
        return $this->close;
    }

    public function setCancel(int $cancel): self
    {
        $this->cancel = $cancel;

        return $this;
    }

    public function getCancel(): int
    {
        return $this->cancel;
    }

    public function setManual(int $manual): self
    {
        $this->manual = $manual;

        return $this;
    }

    public function getManual(): int
    {
        return $this->manual;
    }

    public function setApi(int $api): self
    {
        $this->api = $api;

        return $this;
    }

    public function getApi(): int
    {
        return $this->api;
    }

    public function setError(int $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function getError(): int
    {
        return $this->error;
    }

    /**
     * @return Collection|Log[]
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(Log $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs[] = $log;
            $log->setJob($this);
        }

        return $this;
    }

    public function removeLog(Log $log): self
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getJob() === $this) {
                $log->setJob(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
