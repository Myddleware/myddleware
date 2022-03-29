<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Connector;
use App\DataFixtures\UserFixtures;
use App\DataFixtures\SolutionFixtures;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ConnectorFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    
    public function load(ObjectManager $manager): void
    {
        for ($i = 0 ; $i < 10 ; $i++) {
            $connector = new Connector();
            $manager->persist($connector);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
            SolutionFixtures::class
        ];
    }

    public static function getGroups(): array
    {
        return ['connector'];
    }
}
