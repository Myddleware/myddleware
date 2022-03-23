<?php

namespace App\Service;

use Exception;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserManagerService.
 */
class UserManagerService implements UserManagerInterface
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function encodePassword(UserInterface $user, string $plainPassword): string
    {
        return $this->encoder->encodePassword($user, $plainPassword);
    }

    public function checkPassword(UserInterface $user, string $raw): bool
    {
        return $this->encoder->isPasswordValid($user, $raw);
    }

    /**
     * Génération de token.
     *
     * @throws Exception
     */
    public function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
    }
}
