<?php

namespace App\Tests\Solutions\DynamicsBusiness\Create;

use App\Solutions\dynamicsbusiness;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Dotenv\Dotenv;

// Load .env.test file
$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__, 4).'/.env.test', 'APP_ENV', 'test');

class CreateVendorTest extends KernelTestCase
{
    private dynamicsbusiness $dynamicsBusiness;
    private array $paramConnexion;
    private string $companyId;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $logger = $container->get('logger');
        $connection = $container->get('doctrine.dbal.default_connection');
        $parameterBag = $container->get('parameter_bag');
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $documentRepository = $container->get('App\Repository\DocumentRepository');
        $ruleRelationshipsRepository = $container->get('App\Repository\RuleRelationShipRepository');
        $formulaManager = $container->get('App\Manager\FormulaManager');

        $this->dynamicsBusiness = new dynamicsbusiness(
            $logger,
            $connection,
            $parameterBag,
            $entityManager,
            $documentRepository,
            $ruleRelationshipsRepository,
            $formulaManager
        );

        // Fetch connector with ID from the .env.test and build paramConnexion
        $connectorRepo = $entityManager->getRepository(\App\Entity\Connector::class);
        $connectorIdFromEnv = $_ENV['ID_CONNECTOR_DYNAMICS_TEST'];
        if (empty($connectorIdFromEnv)) {
            throw new \RuntimeException('ID_CONNECTOR_DYNAMICS_TEST environment variable is not set.');
        }
        $connector = $connectorRepo->find($connectorIdFromEnv);
        if (!$connector) {
            throw new \RuntimeException('Connector with ID '.$connectorIdFromEnv.' not found.');
        }
        $paramConnexion = [];
        foreach ($connector->getConnectorParams() as $param) {
            $paramConnexion[$param->getName()] = $param->getValue();
        }
        $this->paramConnexion = $paramConnexion;
        $this->dynamicsBusiness->login($this->paramConnexion);

        // Get company ID from $_ENV
        if (!isset($_ENV['COMPANY_ID'])) {
            throw new \RuntimeException('COMPANY_ID environment variable is not set.');
        }
        $this->companyId = $_ENV['COMPANY_ID'];
    }

    public function testCreateVendor()
    {
        // Generate a unique vendor number
        $vendorNumber = 'V' . date('YmdHis') . rand(1000, 9999);
        
        // Arrange
        $param = [
            'module' => 'vendors',
            'ruleParams' => [
                'parentmodule' => 'companies',
                'parentmoduleid' => $this->companyId
            ]
        ];
        
        $record = [
            'displayName' => 'Test Vendor ' . $vendorNumber,
            'number' => $vendorNumber,
            'email' => 'vendor.' . $vendorNumber . '@example.com',
            'vendorPostingGroup' => 'DOMESTIC'
        ];

        // Act
        $result = $this->dynamicsBusiness->create($param, $record);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertNotEmpty($result['id']);
    }
} 