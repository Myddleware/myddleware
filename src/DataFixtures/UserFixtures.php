<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setEmail('admin@myddleware.com');
        $password = $this->hasher->hashPassword($user, 'thisPasswordMustBeChanged!');
        $user->setPassword($password);
        $user->setTimezone('GMT');
        $manager->persist($user);
        $manager->flush();
    }
}
