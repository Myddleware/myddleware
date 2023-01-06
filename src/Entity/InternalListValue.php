<?php

namespace App\Entity;

use App\Repository\InternalListValueRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InternalListValueRepository::class)
 */
class InternalListValue
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity=InternalList::class)
     * @ORM\JoinColumn(name="list_id", nullable=false)
     */
    private ?InternalList $listId;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id", nullable=false)
     */
    private ?User $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="modified_by", referencedColumnName="id", nullable=false)
     */
    private ?User $modifiedBy;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTimeInterface $dateCreated;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTimeInterface $dateModified;

    /**
     * @ORM\Column(type="boolean", options={"default":0})
     */
    private ?bool $deleted;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $reference;

    /**
     * @ORM\Column(type="text")
     */
    private ?string $data;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $record_id;

    public function __construct()
    {
        $this->setDateCreated(new DateTime());
        $this->setDateModified(new DateTime());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getListId(): ?InternalList
    {
        return $this->listId;
    }

    public function setListId(?InternalList $listId): self
    {
        $this->listId = $listId;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getModifiedBy(): ?User
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(?User $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateModified(): ?\DateTimeInterface
    {
        return $this->dateModified;
    }

    public function setDateModified(\DateTimeInterface $dateModified): self
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getRecordId(): ?string
    {
        return $this->record_id;
    }

    public function setRecordId(string $record_id): self
    {
        $this->record_id = $record_id;

        return $this;
    }
}
