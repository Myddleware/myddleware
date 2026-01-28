<?php
/*********************************************************************************
 * This file is part of Myddleware.

* @package Myddleware
* @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
* @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
* @link http://www.myddleware.com

This file is part of Myddleware.

Myddleware is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Myddleware is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
*********************************************************************************/

namespace App\Manager;

use App\Entity\Connector;
use App\Entity\ConnectorParam;
use App\Entity\Rule;
use App\Entity\RuleField;
use App\Entity\RuleFilter;
use App\Entity\RuleParam;
use App\Entity\RuleRelationShip;
use App\Entity\User;
use App\Repository\ConnectorParamRepository;
use App\Repository\ConnectorRepository;
use Doctrine\DBAL\Connection as DriverConnection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

class TemplateManager
{
    protected string $lang;
    protected $solutionSourceName;
    protected $solutionTarget;
    protected $connectorSource;
    protected $connectorTarget;
    protected $idUser;
    protected string $templateDir;
    protected $rules;
    protected $ruleNameSlugArray;
    protected EntityManagerInterface $entityManager;
    protected $sourceSolution;
    protected $targetSolution;
    protected DriverConnection $connection;
    private LoggerInterface $logger;
    private string $projectDir;
    private SolutionManager $solutionManager;
    private RuleManager $ruleManager;
    private RequestStack $requestStack;
    private TranslatorInterface $translator;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        KernelInterface $kernel,
        SolutionManager $solutionManager,
        RuleManager $ruleManager,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        DriverConnection $dbalConnection
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->connection = $dbalConnection;
        $this->solutionManager = $solutionManager;
        $this->ruleManager = $ruleManager;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->projectDir = $kernel->getProjectDir();
        $this->templateDir = $this->projectDir.'/src/Templates/';
        $request = $requestStack->getCurrentRequest();
        $this->lang = $request ? $request->getLocale() : 'EN';
    }

    /**
     * Sort rule (rule parent first).
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function setRules($rules): array
    {
        $this->rules = $rules;
        $rulesString = trim(implode(',', $rules));
        $query = 'SELECT rule_id FROM ruleorder WHERE FIND_IN_SET(`rule_id`,:rules) ORDER BY ruleorder.order ASC';
        $stmt = $this->connection->prepare($query);
        $stmt->bindValue('rules', $rulesString);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    // Permet de lister les templates pour les connecteurs selectionnés
    public function getTemplates(string $solutionSourceName, string $solutionTarget): array
    {
        $templates = [];
        // Read in the directory template, we read files  corresponding to the selected solutions
        foreach (glob($this->templateDir.$solutionSourceName.'_'.$solutionTarget.'*.yml') as $filename) {
            $template = Yaml::parse(file_get_contents($filename));
            $templates[] = $template;
        }

        return $templates;
    }

    /**
     * Extract all the data of the rule.
     *
     * @throws Exception
     */
    public function extractRule($ruleId): array
    {
        $rule = $this->entityManager
                            ->getRepository(Rule::class)
                            ->find($ruleId);
        // General data
        $data['name'] = $rule->getName();
        $data['nameSlug'] = $rule->getNameSlug();
        $data['sourceSolution'] = $rule->getConnectorSource()->getSolution()->getName();
        $data['targetSolution'] = $rule->getConnectorTarget()->getSolution()->getName();
        $data['sourceModule'] = $rule->getModuleSource();
        $data['targetModule'] = $rule->getModuleTarget();

        // Save all rule name slug to be able to create relationship without keeping the rule id
        $this->ruleNameSlugArray[$rule->getId()] = $data['nameSlug'];

        // Rule fields
        $ruleFields = $rule->getFields();
        if ($ruleFields) {
            foreach ($ruleFields as $ruleField) {
                $field['target'] = $ruleField->getTarget();
                $field['source'] = $ruleField->getSource();
                $field['formula'] = $ruleField->getFormula();
                $data['fields'][] = $field;
            }
        }

        // Rule RelationShips
        $ruleRelationShips = $rule->getRelationsShip();
        if ($ruleRelationShips) {
            foreach ($ruleRelationShips as $ruleRelationShip) {
                $relationship['fieldNameSource'] = $ruleRelationShip->getFieldNameSource();
                $relationship['fieldNameTarget'] = $ruleRelationShip->getFieldNameTarget();
                if (!empty($this->ruleNameSlugArray[$ruleRelationShip->getFieldId()])) {
                    $relationship['fieldId'] = $this->ruleNameSlugArray[$ruleRelationShip->getFieldId()];
                } else {
                    throw new Exception('Failed to generate relationship between the rule '.$rule->getId().' and '.$ruleRelationShip->getFieldId());
                }
                $relationship['parent'] = $ruleRelationShip->getParent();
                $data['relationships'][] = $relationship;
            }
        }

        // Rule Filters
        $ruleFilters = $rule->getFilters();
        if ($ruleFilters) {
            foreach ($ruleFilters as $ruleFilter) {
                $filter['target'] = $ruleFilter->getTarget();
                $filter['type'] = $ruleFilter->getType();
                $filter['value'] = $ruleFilter->getValue();
                $data['filters'][] = $filter;
            }
        }

        // Rule Params
        $ruleParams = $rule->getParams();
        if ($ruleParams) {
            foreach ($ruleParams as $ruleParam) {
                $param['name'] = $ruleParam->getName();
                // If reference date we set it far in the past or to 0 if it is a numeric
                if ('datereference' == $param['name']) {
                    if (is_numeric($ruleParam->getValue())) {
                        $param['value'] = 0;
                    } else { // date
                        $param['value'] = '1970-01-01 00:00:00';
                    }
                } else {
                    $param['value'] = $ruleParam->getValue();
                }
                $data['params'][] = $param;
            }
        }

        return $data;
    }

    protected function initSolutions(
        $sourceSolution,
        $targetSolution,
        Connector $connectorSource,
        Connector $connectorTarget
    ) {
        /** @var ConnectorParamRepository $connectorParamRepository */
        $connectorParamRepository = $this->entityManager->getRepository(ConnectorParam::class);
        // Init source solution
        $connectorParams = $connectorParamRepository->findBy(['connector' => $connectorSource]);
        foreach ($connectorParams as $connectorParam) {
            $params[$connectorParam->getName()] = $connectorParam->getValue();
        }
        // Throw exception in the login in case the connection doesn't work
        $sourceSolution->login($params);

        // Init source solution
        $connectorParams = $connectorParamRepository->findBy(['connector' => $connectorTarget]);
        foreach ($connectorParams as $connectorParam) {
            $params[$connectorParam->getName()] = $connectorParam->getValue();
        }
        // Throw exception in the login in case the connection doesn't work
        $targetSolution->login($params);
    }

    /**
     * Permet de convertir un template en règle lorsque l'utilisateur valide la sélection du template.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function convertTemplate(
        string $ruleName,
        string $templateName,
        int $connectorSourceId,
        int $connectorTargetId,
        User $user
    ): array {
        /** @var ConnectorRepository $connectorRepository */
        $connectorRepository = $this->entityManager->getRepository(Connector::class);
        $connectorSource = $connectorRepository->find($connectorSourceId);
        $connectorTarget = $connectorRepository->find($connectorTargetId);

        // When the connector is set, we set the solution too
        $solutionSourceName = $connectorSource->getSolution()->getName();
        $solutionTargetName = $connectorTarget->getSolution()->getName();

        $this->entityManager->getConnection()->beginTransaction(); // -- BEGIN TRANSACTION
        try {
            $sourceSolution = $this->solutionManager->get($solutionSourceName);
            $targetSolution = $this->solutionManager->get($solutionTargetName);

            // Get the template array
            $template = Yaml::parse(file_get_contents($this->templateDir.$templateName.'.yml'));
            if (empty($template['rules'])) {
                throw new Exception('No rule found in the template. ');
            }

            // Login to the source and target solution
            $this->initSolutions($sourceSolution, $targetSolution, $connectorSource, $connectorTarget);

            // Get list of module for each solution for source and target
            $sourceModuleListSource = $sourceSolution->get_modules('source');
            $sourceModuleListTarget = $sourceSolution->get_modules('target');
            $targetModuleListSource = $targetSolution->get_modules('source');
            $targetModuleListTarget = $targetSolution->get_modules('target');
            $nbRule = 0;
            foreach ($template['rules'] as $rule) {
                // Rule creation
                // General data
                $ruleObject = new Rule();
                $ruleObject->setName((string) $ruleName.' - '.$rule['name']);
                $ruleObject->setNameSlug((string) $ruleName.'_'.$rule['nameSlug']);
                // It is possible that the templatte contains opposite rules, so we test it first. If solution are opposite, we set opposite connectors too
                if ($rule['sourceSolution'] == $solutionSourceName) {
                    $ruleObject->setConnectorSource($connectorSource);
                    // Check the access to the module
                    if (empty($sourceModuleListSource[$rule['sourceModule']])) {
                        throw new Exception('Module '.$rule['sourceModule'].' not found in '.$rule['sourceSolution'].'. Please make sure that you have access to this module. ');
                    }
                } elseif ($rule['sourceSolution'] == $solutionTargetName) {
                    $ruleObject->setConnectorSource($connectorTarget);
                    // Check the access to the module, in case we are in an opposite rule, we search in the target module list for source module
                    if (empty($targetModuleListSource[$rule['sourceModule']])) {
                        throw new Exception('Module '.$rule['sourceModule'].' not found in '.$rule['sourceSolution'].'. Please make sure that you have access to this module. ');
                    }
                } else {
                    throw new Exception('No correspondance between source solutions. ');
                }

                // The same for target connector
                if ($rule['targetSolution'] == $solutionTargetName) {
                    $ruleObject->setConnectorTarget($connectorTarget);
                    // Check the access to the module
                    if (empty($targetModuleListTarget[$rule['targetModule']])) {
                        throw new Exception('Module '.$rule['targetModule'].' not found in '.$rule['targetSolution'].'. Please make sure that you have access to this module. ');
                    }
                } elseif ($rule['targetSolution'] == $solutionSourceName) {
                    $ruleObject->setConnectorTarget($connectorSource);
                    // Check the access to the module, in case we are in an opposite rule, we search in the source module list for target module
                    if (empty($sourceModuleListTarget[$rule['targetModule']])) {
                        throw new Exception('Module '.$rule['targetModule'].' not found in '.$rule['targetSolution'].'. Please make sure that you have access to this module. ');
                    }
                } else {
                    throw new Exception('No correspondance between source solutions. ');
                }

                $ruleObject->setDateCreated(new \DateTime());
                $ruleObject->setDateModified(new \DateTime());
                $ruleObject->setCreatedBy($user);
                $ruleObject->setModifiedBy($user);
                $ruleObject->setModuleSource((string) $rule['sourceModule']);
                $ruleObject->setModuleTarget((string) $rule['targetModule']);
                $ruleObject->setDeleted(0);
                $ruleObject->setActive(!empty($rule['active']) ? 1 : 0);
                $this->entityManager->persist($ruleObject);

                // We save the rule to be able to create relationship in the orther rules
                $this->ruleNameSlugArray[$rule['nameSlug']] = $ruleObject->getId();

                // Get the field list for source and target solution
                if (
                    $rule['sourceSolution'] == $solutionSourceName
                    && $rule['targetSolution'] == $solutionTargetName
                ) {
                    $sourceFieldsList = $sourceSolution->get_module_fields($rule['sourceModule']);
                    $targetFieldsList = $targetSolution->get_module_fields($rule['targetModule']);
                } elseif (
                    $rule['sourceSolution'] == $solutionSourceName
                    && $rule['targetSolution'] == $solutionTargetName
                ) {
                    $sourceFieldsList = $targetSolution->get_module_fields($rule['sourceModule']);
                    $targetFieldsList = $sourceSolution->get_module_fields($rule['targetModule']);
                } else {
                    throw new Exception('No correspondance between source solutions. Failed to get the field list. ');
                }

                // Create rule fields
                if (!empty($rule['fields'])) {
                    foreach ($rule['fields'] as $field) {
                        // Check that all fields are available
                        // Check source, several fields can be store in source in cas of formula
                        $sourceFields = explode(';', $field['source']);
                        foreach ($sourceFields as $sourceField) {
                            if (
                                empty($sourceFieldsList[$sourceField])
                                && 'my_value' != $sourceField
                            ) {
                                throw new Exception('Field '.$sourceField.' not found in the module '.$rule['sourceModule'].'. Please make sure that you have access to this field. ');
                            }
                        }
                        // Check target field
                        if (
                            empty($targetFieldsList[$field['target']])
                            && 'my_value' != $field['target']
                        ) {
                            throw new Exception('Field '.$field['target'].' not found in the module '.$rule['targetModule'].'. Please make sure that you have access to this field. ');
                        }
                        $fieldObject = new RuleField();
                        $fieldObject->setRule($ruleObject);
                        $fieldObject->setTarget($field['target']);
                        $fieldObject->setSource($field['source']);

                        $formula = $field['formula'];
                        if (!empty($formula) && strpos($formula, 'lookup(') !== false) {
                            if (preg_match_all('/lookup\(\{[^}]+\},\s*"([^"]+)"/', $formula, $matches)) {
                                foreach ($matches[1] as $ruleSlug) {
                                    if (isset($this->ruleNameSlugArray[$ruleSlug])) {
                                        $ruleId = $this->ruleNameSlugArray[$ruleSlug];
                                        $formula = str_replace('"'.$ruleSlug.'"', '"'.$ruleId.'"', $formula);
                                    } else {
                                        throw new Exception('Rule with slug "'.$ruleSlug.'" referenced in lookup formula not found. Make sure the referenced rule is defined before this rule in the template.');
                                    }
                                }
                            }
                        }

                        $fieldObject->setFormula($formula);
                        $this->entityManager->persist($fieldObject);
                    }
                }

                // Create rule relationship
                if (!empty($rule['relationships'])) {
                    foreach ($rule['relationships'] as $relationship) {
                        // Check source field
                        if (
                            empty($sourceFieldsList[$relationship['fieldNameSource']])
                            && 'Myddleware_element_id' != $relationship['fieldNameSource']
                        ) {
                            throw new Exception('Field '.$relationship['fieldNameSource'].' not found in the module '.$rule['sourceModule'].'. Please make sure that you have access to this field. ');
                        }
                        // Check target field
                        if (
                            (
                                empty($relationship['parent'])
                                and empty($targetFieldsList[$relationship['fieldNameTarget']])
                                and 'Myddleware_element_id' != $relationship['fieldNameTarget']
                            )
                            // We check source field in case of child rule
                            or
                            (
                                !empty($relationship['parent'])
                                and empty($sourceFieldsList[$relationship['fieldNameTarget']])
                                and 'Myddleware_element_id' != $relationship['fieldNameSource']
                            )
                        ) {
                            throw new Exception('Field '.$relationship['fieldNameTarget'].' not found in the module '.(empty($relationship['parent']) ? $rule['targetModule'] : $rule['sourceModule']).'. Please make sure that you have access to this field. ');
                        }
                        $relationshipObjecy = new RuleRelationShip();
                        $relationshipObjecy->setRule($ruleObject);
                        $relationshipObjecy->setFieldNameSource($relationship['fieldNameSource']);
                        $relationshipObjecy->setFieldNameTarget($relationship['fieldNameTarget']);
                        // fieldId contains the nameSlug, we have to change it with the id of the relate rule
                        $relationshipObjecy->setFieldId($this->ruleNameSlugArray[$relationship['fieldId']]);
                        $relationshipObjecy->setParent($relationship['parent']);
                        $relationshipObjecy->setDeleted(0);
                        $relationshipObjecy->setErrorEmpty(0);
                        $relationshipObjecy->setErrorMissing(1);
                        $this->entityManager->persist($relationshipObjecy);
                    }
                }

                // Create rule filter
                if (!empty($rule['filters'])) {
                    foreach ($rule['filters'] as $filter) {
                        $ruleFilterObject = new RuleFilter();
                        $ruleFilterObject->setRule($ruleObject);
                        $ruleFilterObject->setTarget($filter['target']);
                        $ruleFilterObject->setType($filter['type']);
                        $ruleFilterObject->setValue($filter['value']);
                        $this->entityManager->persist($ruleFilterObject);
                    }
                }

                // Create rule param
                if (!empty($rule['params'])) {
                    foreach ($rule['params'] as $param) {
                        $ruleParamObject = new RuleParam();
                        $ruleParamObject->setRule($ruleObject);
                        $ruleParamObject->setName($param['name']);
                        $ruleParamObject->setValue($param['value']);
                        $this->entityManager->persist($ruleParamObject);
                    }
                }
                ++$nbRule;
            }

            // Commit the rules in the database
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit(); // -- COMMIT TRANSACTION
            // Set the message in Myddleware UI
            $this->requestStack->getSession()->set('info', [$this->translator->trans('messages.template.nb_rule').$nbRule, $this->translator->trans('messages.template.help')]);
        } catch (Exception $e) {
            // Rollback in case of error
            $this->entityManager->getConnection()->rollBack(); // -- ROLLBACK TRANSACTION
            $this->requestStack->getSession()->set('error', [$this->translator->trans('error.template.creation'), $e->getMessage()]);
            $error = 'Failed to generate rules : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['success' => false, 'message' => $error];
        }

        return ['success' => true];
    }
}
