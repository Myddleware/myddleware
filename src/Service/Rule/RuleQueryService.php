<?php

namespace App\Service\Rule;

use App\Entity\Rule;
use App\Entity\Workflow;
use App\Manager\RuleManager;
use App\Manager\SolutionManager;
use App\Repository\VariableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RuleQueryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SolutionManager $solutionManager,
        private VariableRepository $variableRepository,
        private TranslatorInterface $translator,
        private RuleManager $ruleManager // Utilisé pour getFieldsParamView
    ) {
    }

    /**
     * Prépare toutes les données nécessaires pour la vue "Détail" (ruleOpenAction).
     *
     * @param Rule $rule
     * @return array Tableau contenant 'relate', 'connector', 'variables', 'params', 'filters', 'workflows', etc.
     */
public function prepareDataForView(Rule $rule): array
    {
        // 1. Gestion des Relations (RuleRelationship)
        $solutionCibleName = $rule->getConnectorTarget()->getSolution()->getName();
        $solutionCible = $this->solutionManager->get($solutionCibleName);
        $moduleCible = (string) $rule->getModuleTarget();

        $relationshipsData = [];
        foreach ($rule->getRelationsShip() as $r) {
            $linkedRuleName = '';
            if ($r->getFieldId()) {
                $linkedRule = $this->entityManager->getRepository(Rule::class)->find($r->getFieldId());
                $linkedRuleName = $linkedRule ? $linkedRule->getName() : '';
            }

            $relationshipsData[] = [
                'getFieldId' => $r->getFieldId(),
                'getFieldNameSource' => $r->getFieldNameSource(),
                'getFieldNameTarget' => $r->getFieldNameTarget(),
                'getErrorMissing' => $r->getErrorMissing(),
                'getErrorEmpty' => $r->getErrorEmpty(),
                'getParent' => $r->getParent(),
                'getName' => $linkedRuleName,
            ];
        }

        // 2. Infos Connecteurs
        $connectorInfo = $this->entityManager->getRepository(Rule::class)->infosConnectorByRule($rule->getId());
        $connectorData = $connectorInfo[0] ?? [];

        // 3. Extraction des Variables
        $variables = $this->extractVariablesFromRule($rule);

        // 4. Formatage des Paramètres (Params) - CORRECTION ICI
        $paramDefinitions = RuleManager::getFieldsParamView();
        
        // --- ETAPE CRITIQUE AJOUTÉE : Initialisation des valeurs par défaut ---
        // Pour éviter l'erreur Twig "Key id_bdd does not exist"
        foreach ($paramDefinitions as &$def) {
            $def['id_bdd'] = '';
            $def['value_bdd'] = '';
        }
        unset($def); // Sécurité après référence

        $extraParams = []; // Pour 'mode', 'bidirectional', etc.

        // Remplissage avec les données réelles de la BDD
        foreach ($rule->getParams() as $paramEntity) {
            $isStandard = false;
            
            // On cherche si ce paramètre BDD correspond à une définition standard
            foreach ($paramDefinitions as &$def) {
                if ($paramEntity->getName() === $def['name']) {
                    $def['id_bdd'] = $paramEntity->getId();
                    $def['value_bdd'] = $paramEntity->getValue();
                    $isStandard = true;
                    break;
                }
            }
            unset($def);
            
            // Gestion des paramètres spéciaux (non présents dans getFieldsParamView standard)
            if (!$isStandard) {
                if ($paramEntity->getName() === 'mode') {
                    $extraParams['mode'] = match ($paramEntity->getValue()) {
                        '0' => $this->translator->trans('create_rule.step3.syncdata.create_modify'),
                        'C' => $this->translator->trans('create_rule.step3.syncdata.create_only'),
                        'S' => $this->translator->trans('create_rule.step3.syncdata.search_only'),
                        default => $paramEntity->getValue(),
                    };
                } elseif ($paramEntity->getName() === 'bidirectional' && !empty($paramEntity->getValue())) {
                    $linkedRule = $this->entityManager->getRepository(Rule::class)->find($paramEntity->getValue());
                    $extraParams['bidirectional'] = $paramEntity->getValue();
                    $extraParams['bidirectionalName'] = $linkedRule ? $linkedRule->getName() : '';
                } elseif ($paramEntity->getName() === 'duplicate_fields' && !empty($paramEntity->getValue())) {
                    $extraParams['duplicate_fields'] = $paramEntity->getValue();
                } else {
                    $extraParams['customParams'][] = [
                        'id' => $paramEntity->getId(), // Utile pour l'édition inline
                        'name' => $paramEntity->getName(),
                        'value' => $paramEntity->getValue()
                    ];
                }
            }
        }

        // 5. Workflows
        $workflows = $this->entityManager->getRepository(Workflow::class)->findBy([
            'rule' => $rule->getId(), 
            'deleted' => 0
        ]);

        return [
            'relate' => $relationshipsData,
            'connector' => $connectorData,
            'variables' => $variables,
            'params' => $paramDefinitions,
            'params_suite' => $extraParams,
            'workflows' => $workflows,
            'hasWorkflows' => count($workflows) > 0,
            'parentRelationships' => $solutionCible->allowParentRelationship($moduleCible),
            'filters' => $rule->getFilters(),
            'fields' => $rule->getFields(),
        ];
    }

    /**
     * Prépare le JSON complet nécessaire pour l'initialisation de l'éditeur JS (Vue.js/React/jQuery).
     * Utiliser dans la méthode 'edit'.
     *
     * @param Rule $rule
     * @return string JSON string
     */
    public function prepareJsonForEdit(Rule $rule): string
    {
        // 1. Connexions source / target
        $sourceConnector = $rule->getConnectorSource();
        $targetConnector = $rule->getConnectorTarget();

        $connection = [
            'source' => [
                'solutionId'  => method_exists($sourceConnector, 'getSolution') ? $sourceConnector->getSolution()->getId() : null,
                'connectorId' => $sourceConnector?->getId(),
                'module'      => $rule->getModuleSource(),
            ],
            'target' => [
                'solutionId'  => method_exists($targetConnector, 'getSolution') ? $targetConnector->getSolution()->getId() : null,
                'connectorId' => $targetConnector?->getId(),
                'module'      => $rule->getModuleTarget(),
            ],
        ];

        // 2. Params
        $params = $rule->getParamsValues(); // Helper méthode supposée dans l'entité Rule
        $syncOptions = [
            'type'           => $params['mode']            ?? null,
            'duplicateField' => $params['duplicate_fields'] ?? null,
            'limit'          => $params['limit']           ?? null,
            'datereference'  => $params['datereference']   ?? null,
        ];

        // 3. Filtres
        $filters = [];
        foreach ($rule->getFilters() as $filter) {
            $filters[] = [
                'field'    => $filter->getTarget(),
                'operator' => $filter->getType(),
                'value'    => $filter->getValue(),
            ];
        }

        // 4. Mapping (Champs)
        $mapping = [];
        foreach ($rule->getFields() as $field) {
            $mapping[] = [
                'target'  => $field->getTarget(),
                'source'  => $field->getSource(),
                'formula' => $field->getFormula(),
                'comment' => $field->getComment(),
            ];
        }

        // 5. Construction finale
        $data = [
            'mode'        => 'edit',
            'id'          => $rule->getId(),
            'name'        => $rule->getName(),
            'connection'  => $connection,
            'syncOptions' => $syncOptions,
            'filters'     => $filters,
            'mapping'     => $mapping,
        ];

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * Helper pour extraire les variables Myddleware ({mdwvar_...}) utilisées dans une règle.
     */
    private function extractVariablesFromRule(Rule $rule): array
    {
        $varNamesSet = [];
        $pattern = '/\{?(mdwvar_[A-Za-z0-9_]+)\}?/';

        foreach ($rule->getFields() as $f) {
            $text = implode(' ', [
                (string) $f->getFormula(),
                (string) $f->getSource(),
                (string) $f->getTarget(),
                (string) $f->getComment(),
            ]);

            if (preg_match_all($pattern, $text, $m)) {
                foreach ($m[1] as $name) {
                    $varNamesSet[$name] = true; 
                }
            }
        }

        $varNames = array_keys($varNamesSet);
        
        if (empty($varNames)) {
            return [];
        }

        return $this->variableRepository->createQueryBuilder('v')
            ->where('v.name IN (:names)')
            ->setParameter('names', $varNames)
            ->orderBy('v.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}