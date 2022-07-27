<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * The hashed password.
     */
    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Timezone()]
    private ?string $timezone;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank()]
    #[Assert\Email()]
    private ?string $email;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 180, unique: true, nullable: true)]
    private string $username;

    #[ORM\OneToMany(mappedBy: 'modifiedBy', targetEntity: DocumentAudit::class)]
    private $documentAudits;

    #[ORM\OneToMany(mappedBy: 'modifiedBy', targetEntity: RuleParamAudit::class)]
    private $ruleParamAudits;

    public function __construct()
    {
        $this->documentAudits = new ArrayCollection();
        $this->ruleParamAudits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->username ? $this->username : $this->email;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone = 'UTC'): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function __toString(): string
    {
        return $this->email;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return Collection<int, DocumentAudit>
     */
    public function getDocumentAudits(): Collection
    {
        return $this->documentAudits;
    }

    public function addDocumentAudit(DocumentAudit $documentAudit): self
    {
        if (!$this->documentAudits->contains($documentAudit)) {
            $this->documentAudits[] = $documentAudit;
            $documentAudit->setModifiedBy($this);
        }

        return $this;
    }

    public function removeDocumentAudit(DocumentAudit $documentAudit): self
    {
        if ($this->documentAudits->removeElement($documentAudit)) {
            // set the owning side to null (unless already changed)
            if ($documentAudit->getModifiedBy() === $this) {
                $documentAudit->setModifiedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RuleParamAudit>
     */
    public function getRuleParamAudits(): Collection
    {
        return $this->ruleParamAudits;
    }

    public function addRuleParamAudit(RuleParamAudit $ruleParamAudit): self
    {
        if (!$this->ruleParamAudits->contains($ruleParamAudit)) {
            $this->ruleParamAudits[] = $ruleParamAudit;
            $ruleParamAudit->setModifiedBy($this);
        }

        return $this;
    }

    public function removeRuleParamAudit(RuleParamAudit $ruleParamAudit): self
    {
        if ($this->ruleParamAudits->removeElement($ruleParamAudit)) {
            // set the owning side to null (unless already changed)
            if ($ruleParamAudit->getModifiedBy() === $this) {
                $ruleParamAudit->setModifiedBy(null);
            }
        }

        return $this;
    }

    public function getIsVerified(): ?bool
    {
        return $this->isVerified;
    }
}
