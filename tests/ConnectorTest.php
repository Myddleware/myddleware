<?php

namespace App\Tests;

use App\Entity\Connector;
use App\Entity\Solution;
use App\Entity\User;
use DateTimeImmutable;

class ConnectorTest extends DatabaseDependantTestCase
{
    public function testAConnectorCanBeAddedToDatabase(): void
    {
        // given
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find(1);
        $solutionRepository = $this->entityManager->getRepository(Solution::class);
        $solution = $solutionRepository->findOneBy(['name' => 'suitecrm']);
        $connector = new Connector();
        $connector->setName('My Connector');
        $connector->setCreatedBy($user);
        $connector->setDeleted(0);
        $connector->setModifiedBy($user);
        $connector->setCreatedAt(new DateTimeImmutable('now'));
        $connector->setUpdatedAt(new DateTimeImmutable('now'));
        $connector->setSolution($solution);
        // when
        $this->entityManager->persist($connector);
        $this->entityManager->flush();
        $connectorRepository = $this->entityManager->getRepository(Connector::class);
        $connectorRecord = $connectorRepository->findOneBy(['name' => 'My Connector']);
        // then
        $this->assertEquals($solution, $connectorRecord->getSolution());
        $this->assertEquals('my_connector', $connectorRecord->getNameSlug());
    }

    public function testAConnectorMustHaveASolutionRelationship(): void
    {
    }

    public function testAConnectorMustHaveConnectionParameters(): void
    {
    }
}
