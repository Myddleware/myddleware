<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Connector;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ConnectorFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10; ++$i) {
            $connector = new Connector();
            $connector->setName('fake_connector');
            $connector->setNameSlug('fake_connector');
            $connector->setSolution('fake_connector');
            // $connector->setConnectorParams('fake_connector');
            $connector->setDeleted(false);
            $connector->setCreatedBy($this->getReference(UserFixtures::FIRST_USER_REFERENCE));
            $connector->setModifiedBy($this->getReference(UserFixtures::FIRST_USER_REFERENCE));
            $connector->setCreatedAt(new DateTimeImmutable());
            $connector->setUpdatedAt(new DateTimeImmutable());
            $manager->persist($connector);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['connector'];
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
            SolutionFixtures::class,
        ];
    }
}
