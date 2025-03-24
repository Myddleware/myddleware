<?php

namespace App\Service;

use Exception;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserManagerService implements UserManagerInterface
{
    private UserPasswordHasherInterface $encoder;

    public function __construct(UserPasswordHasherInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function encodePassword(UserInterface $user, string $plainPassword): string
    {
        return $this->encoder->hashPassword($user, $plainPassword);
    }

    public function checkPassword(UserInterface $user, string $raw): bool
    {
        return $this->encoder->isPasswordValid($user, $raw);
    }

    /**
     * @throws Exception
     */
    public function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
    }
}
