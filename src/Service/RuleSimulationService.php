<?php

namespace App\Service;

use App\Entity\Rule;
use App\Manager\DocumentManager;
use App\Manager\SolutionManager;
use App\Repository\SolutionRepository;
use App\Repository\VariableRepository;
use App\Service\ConnectorService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Encryption\Encrypter;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RuleSimulationService
{
    private string $secret;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SolutionManager $solutionManager,
        private readonly DocumentManager $documentManager,
        private readonly ConnectorService $connectorService,
        private readonly SolutionRepository $solutionRepository,
        private readonly VariableRepository $variableRepository,
        private readonly LoggerInterface $logger,
        ParameterBagInterface $params
    ) {
        $this->secret = $params->get('secret');
    }

    /**
     * Simulation 1 : Compte (Lecture seule)
     */
    public function simulateCount(Rule $rule): int
    {
        try {
            $params = [
                'date_ref'   => $rule->getParamByName('datereference')?->getValue(),
                'limit'      => $rule->getParamByName('limit')?->getValue(),
                'module'     => (string) $rule->getModuleSource(),
                'fields'     => [],
                'ruleParams' => [],
            ];

            foreach ($rule->getParams() as $ruleParam) {
                $params['ruleParams'][$ruleParam->getName()] = $ruleParam->getValue();
            }

            if (empty($params['ruleParams']['mode'])) {
                $params['ruleParams']['mode'] = '0';
            }

            foreach ($rule->getFields() as $ruleField) {
                $sources = explode(';', $ruleField->getSource());
                foreach ($sources as $source) {
                    if (!empty($source) && !in_array($source, $params['fields'])) {
                        $params['fields'][] = trim($source);
                    }
                }
            }

            $connectorSource = $rule->getConnectorSource();
            $solutionInstance = $this->solutionManager->get($connectorSource->getSolution()->getName());
            
            $rawParams = $this->connectorService->resolveParams($connectorSource->getId());
            $loginParams = $this->decryptParams($rawParams);
            $solutionInstance->login($loginParams);

            $params['offset'] = '0';
            $params['call_type'] = 'read';

            $result = $solutionInstance->readData($params);

            if (!empty($result['error'])) {
                throw new Exception('Reading Issue: ' . $result['error']);
            }

            return (int) ($result['count'] ?? 0);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage().' ('.$e->getFile().' line '.$e->getLine());
            throw new Exception($e->getMessage());
    }
}

/**
 * Simulation 2 : Prévisualisation (Wizard)
 * Récupère un enregistrement source et simule la transformation des données.
 * @param array $requestData Données de la requête (champs, formules, src_module, src_connector_id, query, etc.)
 * @param string|null $ruleKey ID de la règle persistée (si en édition)
 * @return array
 */
public function simulatePreview(array $requestData, ?string $ruleKey = null): array
{
    // --- DEBUG POINT 1: Données d'entrée et ID de Règle ---

    // 1. Parsing du Mapping
    $mapping = $this->parseMapping($requestData);
    if (empty($mapping)) {
        return ['before' => [], 'after' => [], 'data_source' => false];
    }

    // 2. Connexion à la Solution Source
    $solutionSourceName = $requestData['src_solution_name']
        ?? $this->solutionRepository->resolveName($requestData['src_solution_id'] ?? null);

    if (empty($solutionSourceName)) {
        return ['error' => 'Missing source solution.'];
    }

    try {
        $solutionSource = $this->solutionManager->get((string)$solutionSourceName);
        $connectorId = $requestData['src_connector_id'] ?? null;

        $loginParam = $this->connectorService->resolveParams($connectorId);
        if (empty($loginParam)) {
            throw new Exception('Missing connection params');
        }

        // Décryptage obligatoire
        $decryptedLoginParam = $this->decryptParams($loginParam);
        $solutionSource->login($decryptedLoginParam);

    } catch (\Throwable $e) {
        $this->logger->error('Simulation Connection failed: ' . $e->getMessage());
        return ['error' => 'Connection failed: ' . $e->getMessage()];
    }

    // 3. Préparation des paramètres de lecture

    // a) Récupération des champs sources nécessaires pour la lecture
    $sourceFields = [];
    foreach ($mapping as $cfg) {
        if (!empty($cfg['champs'])) {
            $sourceFields = array_merge($sourceFields, $cfg['champs']);
        }
    }
    $sourceFields = array_values(array_unique($sourceFields));

    if (empty($sourceFields)) {
        return ['before' => [], 'after' => [], 'data_source' => false];
    }

    $sourceModule = $requestData['src_module'] ?? '';
    $ruleParams = ['mode' => '0']; // Mode par défaut

    // b) Récupération des RuleParams existants (CRUCIAL pour 'fieldId' et autres)
    if ($ruleKey) {
        $ruleParamsResult = $this->entityManager->getRepository(\App\Entity\RuleParam::class)->findBy(['rule' => $ruleKey]);
        foreach ($ruleParamsResult as $ruleParamsObj) {
            $ruleParams[$ruleParamsObj->getName()] = $ruleParamsObj->getValue();
        }
        if (empty($ruleParams['mode'])) {
            $ruleParams['mode'] = '0';
        }
    }

    // c) Configuration des paramètres de lecture
    $readParams = [
        'module'     => (string) $sourceModule,
        'fields'     => $sourceFields,
        'date_ref'   => '1970-01-01 00:00:00',
        'limit'      => 1,
        'ruleParams' => $ruleParams,
        'call_type'  => 'simulation',
    ];

    $queryVal = $requestData['query'] ?? null;
    if (!empty($queryVal)) {
        $fieldIdName = $ruleParams['fieldId'] ?? 'id';
        $readParams['query'] = [$fieldIdName => trim($queryVal)];
    }

    // --- DEBUG POINT 2: Paramètres finaux envoyés à readData ---


    // 4. Lecture des données source
    try {
        $sourceData = $solutionSource->readData($readParams);
        
        // --- DEBUG POINT 3: Résultat de readData (SI SANS ERREUR) ---

    } catch (\Throwable $e) {
        $this->logger->error('Simulation Read Error: ' . $e->getMessage());
        
        // --- DEBUG POINT 3b: Erreur de lecture (SI EXCEPTION) ---
        
        return ['error' => 'Read Error: ' . $e->getMessage()];
    }

    if (!empty($sourceData['error'])) {
        // --- DEBUG POINT 4: Erreur retournée par le connecteur dans le tableau de résultats ---
        
        return ['error' => $sourceData['error']];
    }

    // 5. Transformation
    $record = $sourceData['values'][$queryVal] ?? null;

    if (!$record) {
        return [
            'before' => [], 'after' => [], 'data_source' => false,
            'simulationQueryField' => $queryVal,
            'message' => 'No record found.'
        ];
    }

    return $this->transformRecord($record, $mapping, $ruleKey, $queryVal);
}

    private function transformRecord(array $record, array $mapping, ?string $ruleKey, ?string $queryVal): array
    {
        if ($ruleKey) $this->documentManager->setRuleId($ruleKey);

        $variablesEntity = $this->variableRepository->findAll();
        $variables = [];
        foreach ($variablesEntity as $v) $variables[$v->getName()] = $v->getValue();
        $this->documentManager->setParam(['variables' => $variables]);
        $this->documentManager->setDocumentType('C'); 

        $before = [];
        $after = [];

        foreach ($mapping as $tgtName => $cfg) {
            $tgtName = trim((string)$tgtName);
            
            $transformConfig = [
                'target_field_name' => $tgtName,
                'source_field_name' => (!empty($cfg['champs']) ? implode(';', $cfg['champs']) : 'my_value'),
                'formula'           => $cfg['formule'][0] ?? '',
                'related_rule'      => '',
            ];

            $response = $this->documentManager->getTransformValue($record, $transformConfig);
            $afterVal = (is_array($response) && isset($response['message'])) ? $response['message'] : $response;

            $fieldsBefore = [];
            if (empty($cfg['champs'])) {
                $fieldsBefore['Formula'] = $cfg['formule'][0] ?? '';
            } else {
                foreach ($cfg['champs'] as $fld) $fieldsBefore[$fld] = $record[$fld] ?? '';
            }

            $isValid = true;
            if (is_string($afterVal) && str_contains($afterVal, 'mdw_no_send_field')) $isValid = false;

            $before[] = $fieldsBefore;
            if ($isValid) $after[] = [$tgtName => $afterVal];
        }

        return [
            'before' => $before,
            'after'  => $after,
            'data_source' => true,
            'simulationQueryField' => $queryVal
        ];
    }

    private function parseMapping(array $data): array
    {
        $rawFields = $data['champs'] ?? [];
        $rawFormulas = $data['formules'] ?? [];

        if (empty($rawFields) && is_string($data['champs'] ?? null)) {
            $rawFields = [];
            foreach (explode(';', $data['champs']) as $pair) {
                [$tgt, $src] = array_pad(explode('[=]', $pair, 2), 2, null);
                if ($tgt && $src && $src !== 'my_value') $rawFields[$tgt][] = $src;
            }
        }
        if (empty($rawFormulas) && is_string($data['formules'] ?? null)) {
            $rawFormulas = [];
            foreach (explode(';', $data['formules']) as $pair) {
                [$tgt, $f] = array_pad(explode('[=]', $pair, 2), 2, null);
                if ($tgt && $f !== null && $f !== '') $rawFormulas[$tgt][] = $f;
            }
        }

        $mapping = [];
        if (is_array($rawFields)) {
            foreach ($rawFields as $tgt => $srcs) {
                $mapping[$tgt]['champs'] = array_values(array_unique(array_filter((array)$srcs)));
            }
        }
        if (is_array($rawFormulas)) {
            foreach ($rawFormulas as $tgt => $fl) {
                $mapping[$tgt]['formule'] = array_values(array_filter((array)$fl, fn($v) => $v !== ''));
            }
        }
        return $mapping;
    }

    private function decryptParams($tab_params)
    {
        $key = substr($this->secret, -16);
        $encrypter = new Encrypter($key);

        if (is_array($tab_params)) {
            $return_params = [];
            foreach ($tab_params as $key => $value) {
                if (is_string($value) && !in_array($key, ['solution', 'module'])) {
                    try {
                        $return_params[$key] = $encrypter->decrypt($value);
                    } catch (\Exception $e) {
                        $return_params[$key] = $value;
                    }
                } else {
                    $return_params[$key] = $value;
                }
            }
            return $return_params;
        }

        try {
            return $encrypter->decrypt($tab_params);
        } catch (\Exception $e) {
            return $tab_params;
        }
    }
}