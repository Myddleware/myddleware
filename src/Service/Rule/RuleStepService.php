<?php

namespace App\Service\Rule;

use App\Entity\Connector;
use App\Entity\ConnectorParam;
use App\Entity\Solution;
use App\Form\ConnectorType;
use App\Manager\SolutionManager;
use App\Repository\ConnectorRepository;
use App\Repository\SolutionRepository;
use App\Manager\RuleManager;
use App\Service\ConnectorService;
use App\Service\SessionService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Encryption\Encrypter;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

class RuleStepService
{
    private string $secret;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SolutionManager $solutionManager,
        private SessionService $sessionService,
        private ConnectorService $connectorService,
        private ConnectorRepository $connectorRepository,
        private SolutionRepository $solutionRepository,
        private TranslatorInterface $translator,
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private LoggerInterface $logger,
        private Connection $connection,
        ParameterBagInterface $params
    ) {
        $this->secret = $params->get('secret');
    }

    /**
     * Gère la logique complexe de l'étape 1 (Choix/Validation Connexion).
     * Remplace la méthode `ruleInputs`.
     */
    public function handleConnectionInput(array $requestData): array
    {
        $mod = (int) ($requestData['mod'] ?? 0);
        $ruleKey = $this->sessionService->getParamRuleLastKey();

        // CAS 1 : Affichage du formulaire de création de connecteur
        if ($mod === 1) {
            return $this->processNewConnectorForm($requestData);
        }

        // CAS 2 : Validation d'un nouveau connecteur (Test de connexion)
        if ($mod === 2) {
            return $this->validateNewConnector($requestData, $ruleKey);
        }

        // CAS 3 : Sélection d'un connecteur existant
        if ($mod === 3) {
            return $this->selectExistingConnector($requestData, $ruleKey);
        }

        throw new \InvalidArgumentException('Invalid mode');
    }

    /**
     * Récupère la liste des connecteurs actifs pour une solution donnée.
     */
    public function getActiveConnectors(int $solutionId): array
    {
        $solution = $this->entityManager->getRepository(Solution::class)->find($solutionId);
        if (!$solution) {
            return [];
        }
        return $this->connectorRepository->findActiveBySolution($solution);
    }

    /**
     * Récupère la liste des modules disponibles pour un connecteur.
     */
    public function getAvailableModules(int $connectorId, string $type): array
    {
        $connector = $this->connectorRepository->find($connectorId);

        if (!$connector || !$connector->getSolution()) {
            throw new \Exception('Connector not found');
        }

        $solutionName = $connector->getSolution()->getName();
        $solution = $this->solutionManager->get(strtolower($solutionName));

        // Récupération et tentative de connexion
        $params = $this->connectorService->resolveParams($connectorId);
        
        try {
            $solution->login($params);
            if (property_exists($solution, 'connexion_valide') && $solution->connexion_valide === false) {
                 throw new \Exception("Login failed");
            }
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }

        $direction = ($type === 'cible') ? 'target' : 'source';
        $modules = $solution->get_modules($direction) ?? [];
        
        $modulesFields = [];
        foreach ($modules as $moduleName => $moduleLabel) {
            try {
                $fields = $solution->get_module_fields($moduleName, $direction);
                $simpleFields = [];
                if (is_array($fields)) {
                    foreach ($fields as $fieldName => $def) {
                        $simpleFields[$fieldName] = $def['label'] ?? $fieldName;
                    }
                }
                $modulesFields[$moduleName] = $simpleFields;
            } catch (\Throwable $e) {
                $modulesFields[$moduleName] = [];
            }
        }

        return [
            'modules' => $modules,
            'modulesFields' => $modulesFields
        ];
    }

    /**
     * Récupère les champs disponibles pour l'étape des filtres/mapping.
     */
    public function getFieldsForFilters(array $params): array
    {
        $fieldsGrouped = [
            'Source Fields'   => [],
            'Target Fields'   => [],
            'Relation Fields' => [],
        ];

        $srcSolutionName = $this->solutionRepository->resolveName($params['src_solution_name'] ?? $params['src_solution_id']);
        $tgtSolutionName = $this->solutionRepository->resolveName($params['tgt_solution_name'] ?? $params['tgt_solution_id']);

        if (!$srcSolutionName || !$tgtSolutionName) {
            return $fieldsGrouped;
        }

        $srcParams = $this->connectorService->resolveParams($params['src_connector_id']);
        $tgtParams = $this->connectorService->resolveParams($params['tgt_connector_id']);

        try {
            $solutionSource = $this->solutionManager->get($srcSolutionName);
            $solutionTarget = $this->solutionManager->get($tgtSolutionName);

            $solutionSource->login($srcParams);
            $solutionTarget->login($tgtParams);

            // Champs Source
            try {
                $sourceFields = $solutionSource->get_module_fields($params['src_module'], 'source') ?? [];
                foreach ($sourceFields as $key => $value) {
                    $label = is_array($value) ? ($value['label'] ?? $key) : (string)$key;
                    $fieldsGrouped['Source Fields'][$key] = $label;

                    if (is_array($value) && !empty($value['relate'])) {
                        $fieldsGrouped['Relation Fields'][$key] = $label;
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error("Error getting source fields: " . $e->getMessage());
            }

            // Champs Cible
            try {
                $targetFields = $solutionTarget->get_module_fields($params['tgt_module'], 'target') ?? [];
                foreach ($targetFields as $key => $value) {
                    $label = is_array($value) ? ($value['label'] ?? $key) : (string)$key;
                    $fieldsGrouped['Target Fields'][$key] = $label;
                }
            } catch (\Throwable $e) {
                $this->logger->error("Error getting target fields: " . $e->getMessage());
            }

        } catch (\Throwable $e) {
            $this->logger->error('Fatal error in getFieldsForFilters: ' . $e->getMessage());
        }

        return $fieldsGrouped;
    }

    // --- Private Helper Methods for handleConnectionInput ---

    private function processNewConnectorForm(array $data): array
    {
        if (!preg_match("#[\w]#", $data['solution']) || !preg_match("#[\w]#", $data['parent'])) {
             throw new \InvalidArgumentException('Invalid parameters');
        }

        $classe = strtolower($data['solution']);
        $solution = $this->entityManager->getRepository(Solution::class)->findOneBy(['name' => $classe]);
        
        $connector = new Connector();
        $connector->setSolution($solution);
        
        $fieldsLogin = [];
        if ($solution) {
            $fieldsLogin = $this->solutionManager->get($solution->getName())->getFieldsLogin();
        }

        $form = $this->formFactory->create(ConnectorType::class, $connector, [
            'action' => $this->router->generate('regle_connector_insert'),
            'attr' => [
                'fieldsLogin' => $fieldsLogin,
                'secret' => $this->secret,
            ],
        ]);

        return [
            'type' => 'form',
            'form' => $form->createView(),
            'parent' => $data['parent']
        ];
    }

    private function validateNewConnector(array $data, ?string $ruleKey): array
    {
        $classe = strtolower($data['solution']);
        $solution = $this->solutionManager->get($classe);
        $param = [];

        // Parsing des champs envoyés (format "key::value;key2::value2")
        $champs = explode(';', $data['champs']);
        if ($champs) {
            foreach ($champs as $key) {
                $input = explode('::', $key);
                if (!empty($input[0]) && (isset($input[1]) || is_numeric($input[1]))) {
                    $param[$input[0]] = trim($input[1]);
                    $this->sessionService->setParamConnectorParentType($data['parent'], $input[0], trim($input[1]));
                }
            }
        }
        $this->sessionService->setParamConnectorParentType($data['parent'], 'solution', $classe);

        // Validation du nombre de champs
        $nonRequiredFields = $this->getNonRequiredFields();
        $requiredCount = count($solution->getFieldsLogin());
        
        // Ajustement approximatif pour la validation (logique héritée)
        $isValidCount = (count($param) == $requiredCount || count($param) == ($requiredCount - count($nonRequiredFields)));

        if ($isValidCount) {
            $result = $solution->login($param);
            if (!empty($solution->connexion_valide)) {
                return ['success' => true];
            }
            
            $this->sessionService->removeParamRule($ruleKey);
            return ['success' => false, 'message' => $result['error'] ?? 'Connection failed'];
        }

        return ['success' => false, 'message' => 'create_connector.form_error'];
    }

    private function selectExistingConnector(array $data, ?string $ruleKey): array
    {
        // Format attendu: "solutionName_connectorId"
        $params = explode('_', $data['solution']);

        if (count($params) !== 2 || !intval($params[1])) {
             throw new \InvalidArgumentException('Invalid selection format');
        }

        $this->sessionService->removeParamParentRule($ruleKey, $data['parent']);
        $classe = strtolower($params[0]);
        $solution = $this->solutionManager->get($classe);
        $connectorId = $params[1];

        $connector = $this->connectorRepository->find($connectorId);
        $connectorParams = $this->entityManager->getRepository(ConnectorParam::class)->findBy(['connector' => $connector]);

        if ($connectorParams) {
            foreach ($connectorParams as $p) {
                $this->sessionService->setParamConnectorParentType($data['parent'], $p->getName(), $p->getValue());
            }
        }

        $this->sessionService->setParamRuleName($ruleKey, $data['name']);
        $this->sessionService->setParamRuleConnectorParent($ruleKey, $data['parent'], $connectorId);
        
        // Déchiffrement et connexion
        $decryptedParams = $this->decryptParams($this->sessionService->getParamParentRule($ruleKey, $data['parent']));
        $this->sessionService->setParamRuleParentName($ruleKey, $data['parent'], 'solution', $classe);

        $result = $solution->login($decryptedParams);

        if (!empty($solution->connexion_valide)) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => $result['error'] ?? 'Connection error'];
    }

    private function decryptParams($tab_params)
    {
        $encrypter = new Encrypter(substr($this->secret, -16));
        if (is_array($tab_params)) {
            $return_params = [];
            foreach ($tab_params as $key => $value) {
                if (is_string($value) && !in_array($key, ['solution', 'module'])) {
                    $return_params[$key] = $encrypter->decrypt($value);
                }
            }
            return $return_params;
        }
        return $encrypter->decrypt($tab_params);
    }

    private function getNonRequiredFields()
    {
        $yamlFile = __DIR__ . '/../../assets/connector-non-required-fields.yaml';
        if (!file_exists($yamlFile)) return [];
        
        $yaml = Yaml::parseFile($yamlFile);
        return $yaml['non-required-fields'] ?? [];
    }

    /**
     * Vérifie si un nom de règle est unique.
     * Retourne 0 si le nom est libre (n'existe pas), 1 s'il est pris.
     */
    public function checkNameUniqueness(string $name): int
    {
        $ruleKey = $this->sessionService->getParamRuleLastKey();
        
        // On cherche si une règle existe déjà avec ce nom
        $existingRule = $this->entityManager->getRepository(\App\Entity\Rule::class)->findOneBy(['name' => $name]);

        if ($existingRule === null) {
            // Le nom n'existe pas, c'est bon (0)
            $this->sessionService->setParamRuleNameValid($ruleKey, true);
            $this->sessionService->setParamRuleName($ruleKey, $name);
            return 0; 
        }

        // Le nom existe déjà (1)
        $this->sessionService->setParamRuleNameValid($ruleKey, false);
        return 1;
    }

    /**
     * Récupère les paramètres de configuration pour l'étape 3 (Mode, Limite, Suppression, Bidirectionnel).
     */
    public function getStep3Params(int $srcConnectorId, int $tgtConnectorId, string $srcModule, string $tgtModule): array
    {
        if (!$srcConnectorId || !$tgtConnectorId || empty($srcModule) || empty($tgtModule)) {
            throw new \InvalidArgumentException('Missing parameters');
        }

        $srcConnector = $this->connectorRepository->find($srcConnectorId);
        $tgtConnector = $this->connectorRepository->find($tgtConnectorId);

        if (!$srcConnector || !$tgtConnector) {
            throw new \Exception('Connector not found');
        }

        $srcSolutionName = $srcConnector->getSolution()->getName();
        $tgtSolutionName = $tgtConnector->getSolution()->getName();

        $srcSolution = $this->solutionManager->get($srcSolutionName);
        $tgtSolution = $this->solutionManager->get($tgtSolutionName);

        // Login
        $srcSolution->login($this->connectorService->resolveParams($srcConnectorId));
        $tgtSolution->login($this->connectorService->resolveParams($tgtConnectorId));

        // Récupération des champs de paramétrage spécifiques aux solutions
        $sourceParams = (array) $srcSolution->getFieldsParamUpd('source', $srcModule);
        $targetParams = (array) $tgtSolution->getFieldsParamUpd('target', $tgtModule);
        $ruleParams = array_merge($sourceParams, $targetParams);

        // Calcul du Mode (Intersection des capacités)
        $sourceMode = (array) $srcSolution->getRuleMode($srcModule, 'source');
        $targetMode = (array) $tgtSolution->getRuleMode($tgtModule, 'target');

        // Si la cible supporte la recherche (S), alors la source supporte aussi la recherche (S) dans ce contexte
        if (array_key_exists('S', $targetMode)) {
            $sourceMode['S'] = 'search_only';
        }
        
        $intersectMode = array_intersect_key($targetMode, $sourceMode);
        if (empty($intersectMode)) {
            $intersectMode = ['C' => 'create_only'];
        }

        // Gestion des doublons
        $fieldsDuplicateTarget = $tgtSolution->getFieldsDuplicate($tgtModule) ?? [];
        if (!empty($fieldsDuplicateTarget)) {
            // Si gestion de doublon possible, on active le mode Search
            $intersectMode['S'] = 'search_only';
        }

        // Traduction des modes
        $modeTranslate = [];
        foreach ($intersectMode as $key => $value) {
            $modeTranslate[$key] = $this->translator->trans('create_rule.step3.syncdata.' . $value);
        }

        // Ajout du champ Mode à la liste des paramètres
        $ruleParams[] = [
            'id'       => 'mode',
            'name'     => 'mode',
            'required' => true,
            'type'     => 'option',
            'label'    => $this->translator->trans('create_rule.step3.syncdata.label'),
            'option'   => $modeTranslate,
            'value'    => ''
        ];

        // Gestion de la Suppression (Si les deux solutions le supportent)
        $readDeletion = $srcSolution->getReadDeletion($srcModule) ?? false;
        $sendDeletion = $tgtSolution->getSendDeletion($tgtModule) ?? false;

        if ($readDeletion && $sendDeletion) {
            $ruleParams[] = [
                'id'       => 'deletion',
                'name'     => 'deletion',
                'required' => false,
                'type'     => 'option',
                'label'    => $this->translator->trans('create_rule.step3.deletion.label'),
                'option'   => [
                    0 => $this->translator->trans('create_rule.step3.deletion.no'),
                    1 => $this->translator->trans('create_rule.step3.deletion.yes'),
                ],
            ];
        }

        // Gestion Bidirectionnelle
        // On récupère les règles inverses potentielles
        $bidirectionalParams = [
            'connector' => ['source' => $srcConnectorId, 'cible' => $tgtConnectorId],
            'module'    => ['source' => $srcModule, 'cible' => $tgtModule]
        ];

        // Appel à la méthode statique existante de RuleManager (comme dans ton ancien code)
        // Ou réécriture de la requête SQL ici. Pour simplifier, on utilise RuleManager.
        $bidirectional = RuleManager::getBidirectionalRules(
            $this->connection, 
            $bidirectionalParams, 
            $srcSolution, 
            $tgtSolution
        );

        if (!empty($bidirectional)) {
            $ruleParams = array_merge($ruleParams, $bidirectional);
        }

        return [
            'rule_params'      => array_values($ruleParams),
            'duplicate_target' => $fieldsDuplicateTarget,
        ];
    }
}