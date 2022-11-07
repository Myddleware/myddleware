<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class ResetPassword
{
    /**
     * @Assert\Length(
     *     min = 8,
     *     max = 50,
     *     minMessage = "profile.type.password.length_min",
     *     maxMessage = "profile.type.password.length_max"
     * )
     */
    protected string $password;

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): ResetPassword
    {
        $this->password = $password;

        return $this;
    }
}
