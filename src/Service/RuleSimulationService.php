<?php

namespace App\Service;

use App\Entity\ConnectorParam;
use App\Entity\Rule;
use App\Manager\SolutionManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class RuleSimulationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SolutionManager $solutionManager,
    ) {}

    /**
     * SIMULATE READING TO RETURN THE NUMBER OF POTENTIAL TRANSFERS.
     * Returns an integer (0 if none), throws an Exception in case of a reading error.
     */
    public function simulateCount(Rule $rule): int
    {
        // Get the rule reference date parameter
        $param['date_ref'] = $rule->getParamByName('datereference')->getValue();

        // Get the rule limit parameter if it exists
        $limitParam = $rule->getParamByName('limit');
        if ($limitParam) {
            $param['limit'] = $limitParam->getValue();
        }

        // Retrieve all rule parameters
        $connectorParams = $rule->getParams();
        foreach ($connectorParams as $connectorParam) {
            $param['ruleParams'][$connectorParam->getName()] = $connectorParam->getValue();
        }

        $param['fields'] = [];
        // Extract source fields defined in the rule
        foreach ($rule->getFields() as $ruleField) {
            // A single field may contain multiple sources if a formula is used
            $sources = explode(';', $ruleField->getSource());
            foreach ($sources as $source) {
                // Ignore empty values or formula placeholders like "my_value"
                $source = trim($source);
                if ($source === '' || $source === 'my_value') {
                    continue;
                }
                $param['fields'][] = $source;
            }
        }

        // Remove duplicate fields to avoid redundant data requests
        if (!empty($param['fields'])) {
            $param['fields'] = array_values(array_unique($param['fields']));
        } else {
            // If no valid source fields are found, remove the 'fields' key entirely
            unset($param['fields']);
        }

        // Define the source module name
        $param['module'] = (string) $rule->getModuleSource();

        // Get the source solution name
        $solution_source_nom = $rule->getConnectorSource()->getSolution()->getName();

        // Build connector source configuration
        $connectorParamsSource = $this->entityManager
            ->getRepository(ConnectorParam::class)
            ->findBy(['connector' => $rule->getConnectorSource()]);

        $connectorSource['solution'] = $rule->getConnectorSource()->getSolution()->getName();
        foreach ($connectorParamsSource as $connector) {
            $connectorSource[$connector->getName()] = $connector->getValue();
        }

        // Initialize and authenticate the source solution
        $solution_source = $this->solutionManager->get($solution_source_nom);
        $solution_source->login($connectorSource);

        // Get the rule execution mode (default: 0)
        $param['ruleParams']['mode'] = $rule->getParamByName('mode')->getValue();
        if (empty($param['ruleParams']['mode'])) {
            $param['ruleParams']['mode'] = '0';
        }

        // Set reading parameters
        $param['offset'] = '0';
        $param['call_type'] = 'read';

        // Perform the read operation from the source system
        $result = $solution_source->readData($param);

        // Handle reading errors from the connector
        if (!empty($result['error'])) {
            throw new Exception('Reading Issue: ' . $result['error']);
        }

        // Return the count of potential records or 0 if none
        return (int) ($result['count'] ?? 0);
    }
}