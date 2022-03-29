<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    private UserPasswordHasherInterface $hasher;

    public const FIRST_USER_REFERENCE = 'first-user';

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager)
    {
        if (!$this->databaseContainsUsers($manager)) {
            $user = new User();
            $user->setEmail('admin@myddleware.com');
            $user->setUsername('admin');
            $password = $this->hasher->hashPassword($user, 'thisPasswordMustBeChanged!');
            $user->setPassword($password);
            $user->setTimezone('GMT');
            $manager->persist($user);
            $manager->flush();
            // allows other fixtures to reference the User fixture via the constant
            $this->addReference(self::FIRST_USER_REFERENCE, $user);
        }
    }

    public static function getGroups(): array
    {
        return ['user', 'mydconfig'];
    }

    public function databaseContainsUsers(ObjectManager $manager): bool
    {
        $userRepository = $manager->getRepository(User::class);
        $numberOfUsers = count($userRepository->findAll());

        return 0 !== $numberOfUsers;
    }
}
