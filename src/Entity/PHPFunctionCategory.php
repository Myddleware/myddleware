<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PHPFunctionCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PHPFunctionCategoryRepository::class)
 */
class PHPFunctionCategory
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=PHPFunction::class, mappedBy="category")
     */
    private $phpFunctions;

    public function __construct()
    {
        $this->phpFunctions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, PHPFunction>
     */
    public function getPhpFunctions(): Collection
    {
        return $this->phpFunctions;
    }

    public function addPhpFunction(PHPFunction $phpFunction): self
    {
        if (!$this->phpFunctions->contains($phpFunction)) {
            $this->phpFunctions[] = $phpFunction;
            $phpFunction->setCategory($this);
        }

        return $this;
    }

    public function removePhpFunction(PHPFunction $phpFunction): self
    {
        if ($this->phpFunctions->removeElement($phpFunction)) {
            // set the owning side to null (unless already changed)
            if ($phpFunction->getCategory() === $this) {
                $phpFunction->setCategory(null);
            }
        }

        return $this;
    }
}
