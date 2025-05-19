<?php

namespace App\Tests\Solutions;

use App\Solutions\dynamicsbusiness;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Dotenv\Dotenv;

// Load .env.test file
$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__, 2).'/.env.test', 'APP_ENV', 'test');

class DynamicsBusinessDeleteTest extends KernelTestCase
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

    public function testDeleteCustomer()
    {

        $CustomerIdTestFromEnv = $_ENV['CUSTOMER_ID_TEST'];

        if (empty($CustomerIdTestFromEnv)) {
            throw new \RuntimeException('CUSTOMER_ID_TEST environment variable is not set.');
        }

        // Arrange
        $param = [
            'module' => 'customers',
            'ruleParams' => [
                'parentmodule' => 'companies',
                'parentmoduleid' => $this->companyId
            ],
            'data' => [
                'doc1' => [
                    'target_id' => $CustomerIdTestFromEnv
                ]
            ]
        ];

        // put the job id in the params
        $param = $this->InsertJobIdInParams($param);

        // Act
        $result = $this->dynamicsBusiness->deleteData($param);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('doc1', $result);
        $this->assertArrayHasKey('id', $result['doc1']);
        $this->assertArrayHasKey('error', $result['doc1']);
        $this->assertFalse($result['doc1']['error']);
    }

    public function InsertJobIdInParams($param)
    {
        $jobIdTestFromEnv = $_ENV['JOB_ID_TEST'];
        if (empty($jobIdTestFromEnv)) {
            throw new \RuntimeException('JOB_ID_TEST environment variable is not set.');
        }
        $param["jobId"] = $jobIdTestFromEnv;
        return $param;
    }

    public function testDeleteNonExistentCustomer()
    {
        // Arrange
        $param = [
            'module' => 'customers',
            'ruleParams' => [
                'parentmodule' => 'companies',
                'parentmoduleid' => $this->companyId
            ],
            'data' => [
                'doc1' => [
                    'target_id' => 'non-existent-id'
                ]
            ]
        ];

        // Act
        $result = $this->dynamicsBusiness->deleteData($param);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('doc1', $result);
        $this->assertArrayHasKey('id', $result['doc1']);
        $this->assertArrayHasKey('error', $result['doc1']);
        $this->assertEquals('-1', $result['doc1']['id']);
        $this->assertStringContainsString('Error', $result['doc1']['error']);
    }
} 