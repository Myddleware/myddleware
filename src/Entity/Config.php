<?php

namespace App\Entity;

use App\Repository\ConfigRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConfigRepository::class)
 */
class Config
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $allowInstall;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAllowInstall(): ?bool
    {
        return $this->allowInstall;
    }

    public function setAllowInstall(?bool $allowInstall): self
    {
        $this->allowInstall = $allowInstall;

        return $this;
    }
}
