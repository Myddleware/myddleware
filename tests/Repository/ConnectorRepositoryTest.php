<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Connector;
use App\Entity\Solution;
use App\Entity\User;
use App\Tests\DatabaseDependantTestCase;
use DateTimeImmutable;

class ConnectorRepositoryTest extends DatabaseDependantTestCase
{
    public function testCount()
    {
        $connectorRepository = $this->entityManager->getRepository(Connector::class);
        $connectors = count($connectorRepository->findAll());
        $this->assertEquals(10, $connectors);
    }

    public function testAConnectorCanBeAddedToDatabase(): void
    {
        // given
        $connector = $this->createAndAddConnectorToDatabase();
        $solution = $connector->getSolution();
        // when
        $this->entityManager->persist($connector);
        $this->entityManager->flush();
        $connectorRepository = $this->entityManager->getRepository(Connector::class);
        $connectorRecord = $connectorRepository->findOneBy(['name' => 'My Connector']);
        // then
        $this->assertEquals($solution, $connectorRecord->getSolution());
        $this->assertEquals('my_connector', $connectorRecord->getNameSlug());
    }

    // public function testAConnectorCanBeRemovedFromDatabase() :void
    // {
    //     // given
    //     $connector = $this->createAndAddConnectorToDatabase();
    //     // when
    //     // then
    // }

    // public function testAConnectorMustHaveASolutionRelationship(): void
    // {
    // }

    // public function testAConnectorMustHaveConnectionParameters(): void
    // {
    // }

    public function createAndAddConnectorToDatabase(): ?Connector
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find(1);
        $solutionRepository = $this->entityManager->getRepository(Solution::class);
        $solution = $solutionRepository->findOneBy(['name' => 'suitecrm']);
        $connector = new Connector();
        $connector->setName('My Connector');
        $connector->setCreatedBy($user);
        $connector->setDeleted(false);
        $connector->setModifiedBy($user);
        $connector->setCreatedAt(new DateTimeImmutable('now'));
        $connector->setUpdatedAt(new DateTimeImmutable('now'));
        $connector->setSolution($solution);

        return $connector;
    }
}
