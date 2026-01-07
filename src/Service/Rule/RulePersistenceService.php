<?php

namespace App\Service\Rule;

use App\Entity\Rule;
use App\Entity\RuleField;
use App\Entity\RuleFilter;
use App\Entity\RuleParam;
use App\Entity\RuleParamAudit;
use App\Entity\RuleRelationShip;
use App\Entity\User;
use App\Repository\RuleRepository;
use App\Service\RuleCleanupService;
use App\Service\RuleDuplicateService;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;

class RulePersistenceService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Connection $connection,
        private RuleCleanupService $ruleCleanupService,
        private RuleDuplicateService $ruleDuplicateService,
        private RuleRepository $ruleRepository
    ) {
    }

    /**
     * Gère la création et la mise à jour complète d'une règle via DBAL (Transactionnel).
     *
     * @param array $data Les données brutes du formulaire/request
     * @param User|null $user L'utilisateur effectuant l'action
     * @return array ['id' => string, 'is_edit' => bool]
     * @throws \Throwable
     */
    public function saveRule(array $data, ?User $user): array
    {
        // 1. Extraction et validation basique des données
        $name = trim((string) ($data['name'] ?? ''));
        $srcConnectorId = (int) ($data['src_connector_id'] ?? 0);
        $tgtConnectorId = (int) ($data['tgt_connector_id'] ?? 0);
        $srcModule = (string) ($data['src_module'] ?? '');
        $tgtModule = (string) ($data['tgt_module'] ?? '');
        $syncMode = (string) ($data['sync_mode'] ?? '0');
        $ruleIdFromRequest = (string) ($data['rule_id'] ?? '');
        
        $isEdit = $ruleIdFromRequest !== '';
        $bidirectionalId = trim((string) ($data['bidirectional'] ?? ''));
        $duplicateField = trim((string) ($data['duplicate_field'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));

        // Parsing des tableaux (champs et formules)
        $rawFields = $data['champs'] ?? [];
        $rawFormulas = $data['formules'] ?? [];
        
        $filters = [];
        $filtersJson = $data['filters'] ?? '';
        if (is_string($filtersJson) && $filtersJson !== '') {
            try {
                $filters = json_decode($filtersJson, true, 512, JSON_THROW_ON_ERROR) ?: [];
            } catch (\Throwable $e) {
                $filters = [];
            }
        }

        if ($name === '' || !$srcConnectorId || !$tgtConnectorId || $srcModule === '' || $tgtModule === '') {
            throw new InvalidArgumentException("Données manquantes pour la sauvegarde de la règle.");
        }

        // 2. Préparation des données techniques
        $ruleId = $isEdit ? $ruleIdFromRequest : substr(uniqid('', true), 0, 13);
        $now = new DateTimeImmutable();
        $nowStr = $now->format('Y-m-d H:i:s');
        $midnight = $now->setTime(0, 0)->format('Y-m-d 00:00:00');
        
        // Création du slug
        $tmp = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        $nameSlug = strtolower(preg_replace('~[^a-zA-Z0-9]+~', '_', $tmp)) ?: 'rule';
        
        $userId = (int) ($user?->getId() ?? 1);

        // 3. Exécution Transactionnelle (DBAL)
        $this->connection->transactional(function (Connection $conn) use (
            $ruleId, $nowStr, $midnight, $userId, $name, $nameSlug,
            $srcConnectorId, $tgtConnectorId, $srcModule, $tgtModule,
            $rawFields, $rawFormulas, $filters, $syncMode, $isEdit,
            $bidirectionalId, $duplicateField, $description
        ) {
            // A. Gestion de la table 'rule'
            if ($isEdit) {
                $conn->update('rule', [
                    'conn_id_source'  => $srcConnectorId,
                    'conn_id_target'  => $tgtConnectorId,
                    'modified_by'     => $userId,
                    'date_modified'   => $nowStr,
                    'module_source'   => $srcModule,
                    'module_target'   => $tgtModule,
                    'name'            => $name,
                    'name_slug'       => $nameSlug,
                ], ['id' => $ruleId]);

                // Nettoyage avant réinsertion
                $conn->delete('rulefield', ['rule_id' => $ruleId]);
                $conn->delete('rulefilter', ['rule_id' => $ruleId]);
                $conn->delete('ruleparam', ['rule_id' => $ruleId]);
            } else {
                $conn->insert('rule', [
                    'id'             => $ruleId,
                    'conn_id_source' => $srcConnectorId,
                    'conn_id_target' => $tgtConnectorId,
                    'created_by'     => $userId,
                    'modified_by'    => $userId,
                    'group_id'       => null,
                    'date_created'   => $nowStr,
                    'date_modified'  => $nowStr,
                    'module_source'  => $srcModule,
                    'module_target'  => $tgtModule,
                    'active'         => 0,
                    'deleted'        => 0,
                    'name'           => $name,
                    'name_slug'      => $nameSlug,
                    'read_job_lock'  => null,
                ]);

                // Initialisation de l'ordre
                $conn->executeStatement(
                    'INSERT INTO `ruleorder` (`rule_id`, `order`) VALUES (?, ?)',
                    [$ruleId, 1]
                );
            }

            // B. Insertion des champs (Mapping)
            $contentFields = ['name' => []];
            foreach ($rawFields as $targetField => $srcs) {
                $srcs = array_values(array_unique(array_filter((array)$srcs)));
                $formula = (!empty($rawFormulas[$targetField][0])) ? (string)$rawFormulas[$targetField][0] : '';

                $conn->insert('rulefield', [
                    'rule_id'           => $ruleId,
                    'target_field_name' => (string) $targetField,
                    'source_field_name' => implode(';', $srcs) ?: 'my_value',
                    'formula'           => $formula !== '' ? $formula : null,
                    'comment'           => null,
                ]);

                // Préparation pour l'audit
                $contentFields['name'][(string)$targetField]['champs'] = array_values((array)$srcs);
            }

            // C. Insertion des filtres
            foreach ($filters as $f) {
                $field = (string)($f['field'] ?? '');
                $op    = (string)($f['operator'] ?? '');
                $val   = (string)($f['value'] ?? '');
                if ($field === '' || $op === '') continue;

                $conn->insert('rulefilter', [
                    'rule_id' => $ruleId,
                    'target'  => $field,
                    'type'    => $op,
                    'value'   => $val,
                ]);
            }

            // D. Insertion des paramètres par défaut
            $conn->insert('ruleparam', ['rule_id' => $ruleId, 'name' => 'limit',         'value' => '100']);
            $conn->insert('ruleparam', ['rule_id' => $ruleId, 'name' => 'datereference', 'value' => $midnight]);
            $conn->insert('ruleparam', ['rule_id' => $ruleId, 'name' => 'mode',          'value' => (string)$syncMode]);

            // Insertion du champ duplicate_fields si défini
            if ($duplicateField !== '') {
                $conn->insert('ruleparam', ['rule_id' => $ruleId, 'name' => 'duplicate_fields', 'value' => $duplicateField]);
            }

            if ($description !== '') {
                $conn->insert('ruleparam', ['rule_id' => $ruleId, 'name' => 'description', 'value' => $description]);
            }

            // E. Gestion Bidirectionnelle
            // Nettoyage préalable (cas d'update)
            $conn->delete('ruleparam', ['name' => 'bidirectional', 'value' => $ruleId]);

            if ($bidirectionalId !== '') {
                // Créer le lien sur la règle courante
                $conn->insert('ruleparam', [
                    'rule_id' => $ruleId,
                    'name'    => 'bidirectional',
                    'value'   => $bidirectionalId,
                ]);

                // Vérifier et créer/mettre à jour le lien sur la règle opposée
                $opposite = $conn->fetchAssociative(
                    'SELECT id, value FROM ruleparam WHERE rule_id = ? AND name = ?',
                    [$bidirectionalId, 'bidirectional']
                );

                if ($opposite === false) {
                    $conn->insert('ruleparam', [
                        'rule_id' => $bidirectionalId,
                        'name'    => 'bidirectional',
                        'value'   => $ruleId,
                    ]);
                } elseif ($opposite['value'] !== $ruleId) {
                    $conn->update('ruleparam', [
                        'value' => $ruleId,
                    ], ['id' => $opposite['id']]);
                }
            }

            // F. Audit (RuleAudit)
            $auditPayload = [
                'ruleName'      => $nameSlug,
                'limit'         => '100',
                'datereference' => $midnight,
                'content'       => [
                    'fields' => ['name' => $contentFields['name']],
                    'params' => ['mode' => (int)$syncMode],
                ],
                'filters'       => array_values(array_map(fn($f) => [
                    'target' => (string)($f['field'] ?? ''),
                    'filter' => (string)($f['operator'] ?? ''),
                    'value'  => (string)($f['value'] ?? ''),
                ], $filters)),
                'relationships' => null,
            ];

            $conn->insert('ruleaudit', [
                'rule_id'      => $ruleId,
                'created_by'   => $userId,
                'date_created' => $nowStr,
                'data'         => serialize(json_encode($auditPayload, JSON_UNESCAPED_UNICODE)),
            ]);
        });

        return [
            'id' => $ruleId,
            'is_edit' => $isEdit
        ];
    }

    /**
     * Supprime une règle (Soft Delete) après vérifications.
     * * @throws Exception Si la règle ne peut pas être supprimée
     */
    public function deleteRule(Rule $rule): void
    {
        $id = $rule->getId();

        // 1. Vérification Document non supprimé
        $docClose = $this->entityManager->getRepository(\App\Entity\Document::class)
            ->findOneBy(['rule' => $id, 'deleted' => 0]);

        if ($docClose) {
            throw new Exception('error.rule.delete_document');
        }

        // 2. Vérification Document ouvert/erreur
        $docErrorOpen = $this->entityManager->getRepository(\App\Entity\Document::class)
            ->findOneBy(['rule' => $id, 'deleted' => 0, 'globalStatus' => ['Open', 'Error']]);

        if ($docErrorOpen) {
            throw new Exception('error.rule.delete_document_error_open');
        }

        // 3. Vérification Relations
        $ruleRelationships = $this->entityManager->getRepository(RuleRelationShip::class)
            ->findBy(['fieldId' => $id]);

        if (!empty($ruleRelationships)) {
            foreach ($ruleRelationships as $rel) {
                if (empty($rel->getDeleted())) {
                    // On lance une exception avec le nom de la règle liée pour l'affichage
                    throw new Exception('error.rule.delete_relationship_exists|' . $rel->getRule());
                }
            }
        }

        // 4. Exécution de la suppression
        // Marquer les relations sortantes comme supprimées
        $myRelationships = $this->entityManager->getRepository(RuleRelationShip::class)
            ->findBy(['rule' => $id]);

        foreach ($myRelationships as $rel) {
            $rel->setDeleted(1);
            $this->entityManager->persist($rel);
        }

        // Nettoyage des entités liées (appel au service existant)
        $this->ruleCleanupService->removeThisRuleItsRuleGroup($rule);
        $this->ruleCleanupService->deleteWorflowsFromThisRule($rule->getId());

        // Soft-delete de la règle
        $rule->setDeleted(true);
        $rule->setActive(false);

        $this->entityManager->persist($rule);
        $this->entityManager->flush();
    }

    /**
     * Duplique une règle existante.
     */
    public function duplicateRule(Rule $originalRule, array $data, User $user): void
    {
        $now = new DateTime();
        $newRule = new Rule();

        $newName = $data['name'];
        $sourceConnector = $data['connectorSource'];
        $targetConnector = $data['connectorTarget'];

        // Copie de l'entête
        $newRule->setName($newName)
            ->setCreatedBy($user)
            ->setConnectorSource($sourceConnector)
            ->setConnectorTarget($targetConnector)
            ->setDateCreated($now)
            ->setDateModified($now)
            ->setModifiedBy($user)
            ->setModuleSource($originalRule->getModuleSource())
            ->setModuleTarget($originalRule->getModuleTarget())
            ->setDeleted(false)
            ->setActive(false)
            ->setNameSlug($newName); // Devrait être slugifié idéalement, mais on garde la logique du controller

        // Copie des Paramètres
        foreach ($originalRule->getParams() as $param) {
            $newParam = new RuleParam();
            $newParam->setRule($newRule)
                ->setName($param->getName())
                ->setValue($param->getValue());
            $this->entityManager->persist($newParam);
        }

        // Copie des Relations
        foreach ($originalRule->getRelationsShip() as $rel) {
            $newRel = new RuleRelationShip();
            $newRel->setRule($newRule)
                ->setFieldNameSource($rel->getFieldNameSource())
                ->setFieldNameTarget($rel->getFieldNameTarget())
                ->setFieldId($rel->getFieldId())
                ->setParent($rel->getParent())
                ->setDeleted(0)
                ->setErrorEmpty($rel->getErrorEmpty())
                ->setErrorMissing($rel->getErrorMissing());
            $this->entityManager->persist($newRel);
        }

        // Copie des Filtres
        foreach ($originalRule->getFilters() as $filter) {
            $newFilter = new RuleFilter();
            $newFilter->setRule($newRule)
                ->setTarget($filter->getTarget())
                ->setType($filter->getType())
                ->setValue($filter->getValue());
            $this->entityManager->persist($newFilter);
        }

        // Copie des Champs (Mapping)
        foreach ($originalRule->getFields() as $field) {
            $newField = new RuleField();
            $newField->setRule($newRule)
                ->setTarget($field->getTarget())
                ->setSource($field->getSource())
                ->setFormula($field->getFormula());
            $this->entityManager->persist($newField);
        }

        $this->entityManager->persist($newRule);
        $this->entityManager->flush();

        // Duplication des workflows via le service dédié
        $this->ruleDuplicateService->duplicateWorkflows($originalRule->getId(), $newRule);
    }

    /**
     * Active ou désactive une règle.
     * @return int Le nouveau statut (0 ou 1)
     */
    public function toggleActive(Rule $rule): int
    {
        $newStatus = $rule->getActive() ? 0 : 1;
        $rule->setActive($newStatus);
        
        $this->entityManager->persist($rule);
        $this->entityManager->flush();

        return $newStatus;
    }

    /**
     * Met à jour le nom d'une règle.
     */
    public function updateName(Rule $rule, string $newName): void
    {
        if (empty($newName) || $newName === '0' || $newName === $rule->getName()) {
            return;
        }

        $rule->setName($newName);
        // Génération du slug simple comme dans le controller
        $nameSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $newName), '_'));
        $rule->setNameSlug($nameSlug);

        $this->entityManager->flush();
    }

    /**
     * Met à jour la description (stockée comme un RuleParam).
     */
    public function updateDescription(Rule $rule, string $description): void
    {
        $param = $this->entityManager->getRepository(RuleParam::class)->findOneBy([
            'rule' => $rule,
            'name' => 'description',
        ]);

        if (!$param) {
            $param = new RuleParam();
            $param->setRule($rule);
            $param->setName('description');
            $this->entityManager->persist($param);
        }

        if ($param->getValue() !== $description) {
            $param->setValue($description);
            $this->entityManager->flush();
        }
    }
    
    /**
     * Met à jour le commentaire d'un champ de règle.
     */
    public function updateFieldComment(RuleField $ruleField, string $comment): void
    {
        $ruleField->setComment($comment);
        $this->entityManager->persist($ruleField);
        $this->entityManager->flush();
    }
    
    /**
     * Met à jour un paramètre spécifique (utilisé pour l'AJAX d'édition inline).
     */
    public function updateParamValue(Rule $rule, string $paramName, string $value, User $user): void
    {
        $repo = $this->entityManager->getRepository(RuleParam::class);
        $param = $repo->findOneBy(['rule' => $rule, 'name' => $paramName]);

        if (!$param) {
            $param = new RuleParam();
            $param->setRule($rule);
            $param->setName($paramName);
            $param->setValue($value);
            $this->entityManager->persist($param);
        } else {
            // Audit de modification
            if ($value != $param->getValue()) {
                $audit = new RuleParamAudit();
                $audit->setRuleParamId($param->getId());
                $audit->setDateModified(new DateTime());
                $audit->setBefore($param->getValue());
                $audit->setAfter($value);
                $audit->setByUser($user->getId());
                $this->entityManager->persist($audit);
                
                $param->setValue($value);
            }
        }
        
        $this->entityManager->flush();
    }
}