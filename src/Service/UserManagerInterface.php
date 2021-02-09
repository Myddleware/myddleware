<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Interface UserManagerInterface
 * @package App\Service
 */
interface UserManagerInterface
{
    public function encodePassword(UserInterface $user, string $plainPassword): string;

    public function checkPassword(UserInterface $user, string $raw): bool;

    public function generateToken(): string;
}