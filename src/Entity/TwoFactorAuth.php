<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 *
 * This file is part of Myddleware.
 *
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

namespace App\Entity;

use App\Repository\TwoFactorAuthRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TwoFactorAuthRepository::class)
 * @ORM\Table(name="two_factor_auth")
 */
class TwoFactorAuth
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = false;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $verificationCode;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $codeExpiresAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $preferredMethod = 'email';

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $failedAttempts = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $blockedUntil;

    /**
     * @ORM\Column(type="boolean")
     */
    private $rememberDevice = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $rememberToken;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(?string $verificationCode): self
    {
        $this->verificationCode = $verificationCode;

        return $this;
    }

    public function getCodeExpiresAt(): ?\DateTimeInterface
    {
        return $this->codeExpiresAt;
    }

    public function setCodeExpiresAt(?\DateTimeInterface $codeExpiresAt): self
    {
        $this->codeExpiresAt = $codeExpiresAt;

        return $this;
    }

    public function isCodeExpired(): bool
    {
        if (!$this->codeExpiresAt) {
            return true;
        }

        return $this->codeExpiresAt < new DateTime();
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getPreferredMethod(): ?string
    {
        return $this->preferredMethod;
    }

    public function setPreferredMethod(?string $preferredMethod): self
    {
        $this->preferredMethod = $preferredMethod;

        return $this;
    }

    public function getFailedAttempts(): ?int
    {
        return $this->failedAttempts;
    }

    public function setFailedAttempts(?int $failedAttempts): self
    {
        $this->failedAttempts = $failedAttempts;

        return $this;
    }

    public function incrementFailedAttempts(): self
    {
        $this->failedAttempts = ($this->failedAttempts ?? 0) + 1;

        return $this;
    }

    public function resetFailedAttempts(): self
    {
        $this->failedAttempts = 0;
        $this->blockedUntil = null;

        return $this;
    }

    public function getBlockedUntil(): ?\DateTimeInterface
    {
        return $this->blockedUntil;
    }

    public function setBlockedUntil(?\DateTimeInterface $blockedUntil): self
    {
        $this->blockedUntil = $blockedUntil;

        return $this;
    }

    public function isBlocked(): bool
    {
        if (!$this->blockedUntil) {
            return false;
        }

        return $this->blockedUntil > new DateTime();
    }

    public function isRememberDevice(): ?bool
    {
        return $this->rememberDevice;
    }

    public function setRememberDevice(bool $rememberDevice): self
    {
        $this->rememberDevice = $rememberDevice;

        return $this;
    }

    public function getRememberToken(): ?string
    {
        return $this->rememberToken;
    }

    public function setRememberToken(?string $rememberToken): self
    {
        $this->rememberToken = $rememberToken;

        return $this;
    }
} 