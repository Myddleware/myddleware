<?php

namespace App\Tests;

use Zenstruck\Foundry\Test\Factories;
use App\Factory\ConnectorFactory;
use Zenstruck\Foundry\Test\ResetDatabase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ConnectorFactoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function setUp(): void 
    {
        $kernel = self::bootKernel();
    }

    public function testItCreatesOneConnector(): void
    {
        $connector = ConnectorFactory::createOne();
        $this->assertIsInt($connector->getId());
        $this->assertNotNull($connector->getId());
        $this->assertContainsEquals('ROLE_ADMIN', $connector->getCreatedBy()->getRoles());
        $this->assertContainsEquals('ROLE_SUPER_ADMIN', $connector->getModifiedBy()->getRoles());
        $this->assertIsBool($connector->getDeleted());
        $this->assertIsString($connector->getName());
        $this->assertIsString($connector->getNameSlug());
        $this->assertSame($connector->getName(), $connector->getNameSlug());
    }

    public function testItCreatesManyConnectors(): void
    {
        $connectors = ConnectorFactory::createMany(25);
        $this->assertCount(25, $connectors);

    }
}
