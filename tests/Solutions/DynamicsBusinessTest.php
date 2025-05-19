<?php

namespace App\Tests\Solutions;

use App\Solutions\dynamicsbusiness;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Dotenv\Dotenv;

// Load .env.test file
$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__, 2).'/.env.test', 'APP_ENV', 'test');

// Debug: Print environment variables and file path
// var_dump([
//     'ENV_FILE_PATH' => dirname(__DIR__, 2).'/.env.test',
//     'DATABASE_URL' => getenv('DATABASE_URL'),
//     'APP_ENV' => getenv('APP_ENV'),
//     'COMPANY_ID' => getenv('COMPANY_ID')
// ]);

class DynamicsBusinessTest extends KernelTestCase
{
    private dynamicsbusiness $dynamicsBusiness;
    private array $paramConnexion;
    private string $companyId;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        // Debug: Print environment variables from $_ENV
        // var_dump([
        //     'DATABASE_URL' => $_ENV['DATABASE_URL'] ?? 'not set',
        //     'APP_ENV' => $_ENV['APP_ENV'] ?? 'not set',
        //     'COMPANY_ID' => $_ENV['COMPANY_ID'] ?? 'not set'
        // ]);

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

    public function testCreateCustomer()
    {
        // Arrange
        $param = [
            'module' => 'customers',
            'ruleParams' => [
                'parentmodule' => 'companies',
                'parentmoduleid' => $this->companyId
            ]
        ];
        
        $record = [
            'displayName' => 'Test Customer',
            'number' => 'C00001',
            'email' => 'test@example.com'
        ];

        // Act
        $result = $this->dynamicsBusiness->create($param, $record);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertNotEmpty($result['id']);
    }

    public function testCreateVendor()
    {
        // Arrange
        $param = [
            'module' => 'vendors',
            'ruleParams' => [
                'parentmodule' => 'companies',
                'parentmoduleid' => $this->companyId
            ]
        ];
        
        $record = [
            'displayName' => 'Test Vendor',
            'number' => 'V00001',
            'email' => 'vendor@example.com'
        ];

        // Act
        $result = $this->dynamicsBusiness->create($param, $record);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertNotEmpty($result['id']);
    }

    public function testCreateSalesOrder()
    {
        // Arrange
        $param = [
            'module' => 'salesorders',
            'ruleParams' => [
                'parentmodule' => 'companies',
                'parentmoduleid' => $this->companyId
            ]
        ];
        
        $record = [
            'customerNumber' => 'C00001',
            'orderDate' => '2024-03-20',
            'currencyCode' => 'USD'
        ];

        // Act
        $result = $this->dynamicsBusiness->create($param, $record);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertNotEmpty($result['id']);
    }

    public function testCreateWithInvalidModule()
    {
        // Arrange
        $param = [
            'module' => 'invalid_module',
            'ruleParams' => [
                'parentmodule' => 'companies',
                'parentmoduleid' => $this->companyId
            ]
        ];
        
        $record = [
            'displayName' => 'Test Record'
        ];

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Module invalid_module unknown');

        // Act
        $this->dynamicsBusiness->create($param, $record);
    }
} 