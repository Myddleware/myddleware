<?php

namespace App\Service;

use Symfony\Component\Security\Core\User\UserInterface;

interface UserManagerInterface
{
    public function encodePassword(UserInterface $user, string $plainPassword): string;

    public function checkPassword(UserInterface $user, string $raw): bool;

    public function generateToken(): string;
}
