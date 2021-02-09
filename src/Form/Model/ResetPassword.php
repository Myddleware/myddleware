<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ResetPassword
 * @package App\Form\Model
 */
class ResetPassword
{
    /**
     * @var string
     * 
     * @Assert\Length(
     *     min = 8,
     *     max = 50,
     *     minMessage = "profile.type.password.length_min",
     *     maxMessage = "profile.type.password.length_max"
     * )
     */
    protected $password;

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword(string $password)
    {
        $this->password = $password;

        return $this;
    }
}