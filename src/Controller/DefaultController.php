<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 *
 * This file is part of Myddleware.
 *
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

namespace App\Controller;

use App\Entity\Connector;
use App\Entity\ConnectorParam;
use App\Entity\Document;
use App\Entity\FuncCat;
use App\Entity\Functions;
use App\Entity\Rule;
use App\Entity\RuleAudit;
use App\Entity\RuleField;
use App\Entity\RuleFilter;
use App\Entity\RuleParam;
use App\Entity\RuleParamAudit;
use App\Entity\RuleRelationShip;
use App\Entity\Solution;
use App\Entity\User;
use App\Entity\Variable;
use App\Form\ConnectorType;
use App\Form\DuplicateRuleFormType;
use App\Manager\DocumentManager;
use App\Manager\FormulaManager;
use App\Manager\HomeManager;
use App\Manager\JobManager;
use App\Manager\RuleManager;
use App\Manager\SolutionManager;
use App\Manager\TemplateManager;
use App\Manager\ToolsManager;
use App\Repository\ConfigRepository;
use App\Repository\DocumentRepository;
use App\Repository\JobRepository;
use App\Repository\RuleRepository;
use App\Service\SessionService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Id;
use Exception;
use Illuminate\Encryption\Encrypter;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Form\Type\RelationFilterType;
use App\Entity\Workflow;
use App\Entity\WorkflowAction;
use App\Service\TwoFactorAuthService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
    /**
     * @Route("/rule")
     */
    class DefaultController extends AbstractController
    {
        private FormulaManager $formuleManager;
        private SessionService $sessionService;
        private ParameterBagInterface $params;
        private EntityManagerInterface $entityManager;
        private HomeManager $home;
        private ToolsManager $tools;
        private TranslatorInterface $translator;
        private AuthorizationCheckerInterface $authorizationChecker;
        private JobManager $jobManager;
        private LoggerInterface $logger;
        private TemplateManager $template;
        private RuleRepository $ruleRepository;
        private JobRepository $jobRepository;
        private DocumentRepository $documentRepository;
        private SolutionManager $solutionManager;
        private RuleManager $ruleManager;
        private DocumentManager $documentManager;
        protected Connection $connection;
        // To allow sending a specific record ID to rule simulation
        protected $simulationQueryField;
        private ConfigRepository $configRepository;
        private TwoFactorAuthService $twoFactorAuthService;


        public function __construct(
            LoggerInterface $logger,
            RuleManager $ruleManager,
            FormulaManager $formuleManager,
            SolutionManager $solutionManager,
            DocumentManager $documentManager,
            SessionService $sessionService,
            EntityManagerInterface $entityManager,
            RuleRepository $ruleRepository,
            JobRepository $jobRepository,
            DocumentRepository $documentRepository,
            Connection $connection,
            TranslatorInterface $translator,
            AuthorizationCheckerInterface $authorizationChecker,
            HomeManager $home,
            ToolsManager $tools,
            JobManager $jobManager,
            TemplateManager $template,
            ParameterBagInterface $params,
            TwoFactorAuthService $twoFactorAuthService
        ) {
            $this->logger = $logger;
            $this->ruleManager = $ruleManager;
            $this->formuleManager = $formuleManager;
            $this->solutionManager = $solutionManager;
            $this->documentManager = $documentManager;
            $this->sessionService = $sessionService;
            $this->entityManager = $entityManager;
            $this->ruleRepository = $ruleRepository;
            $this->jobRepository = $jobRepository;
            $this->documentRepository = $documentRepository;
            $this->connection = $connection;
            $this->translator = $translator;
            $this->authorizationChecker = $authorizationChecker;
            $this->home = $home;
            $this->tools = $tools;
            $this->jobManager = $jobManager;
            $this->template = $template;
            $this->twoFactorAuthService = $twoFactorAuthService;
        }

        protected function getInstanceBdd()
        {
        }

    /* ******************************************************
         * RULE
         ****************************************************** */

    /**
     * LISTE DES REGLES.
     *
     * @return RedirectResponse|Response
     */
    #[Route('/list', name: 'regle_list', defaults: ['page' => 1])]
    #[Route('/list/page-{page}', name: 'regle_list_page', requirements: ['page' => '\d+'])]
    public function ruleListAction(Request $request, int $page = 1)
    {
        try {
            $ruleName = $request->query->get('rule_name');
            
            // Initialize compact array early
            $compact = [
                'nb' => 0,
                'entities' => '',
                'pager' => ''
            ];

            $this->getInstanceBdd();
            $pager = $this->tools->getParamValue('ruleListPager');

            // Get rules based on search or not
            if ($ruleName) {
                $intermediateResult = $this->entityManager->getRepository(Rule::class)
                    ->findListRuleByUser($this->getUser(), $ruleName);
            } else {
                $intermediateResult = $this->entityManager->getRepository(Rule::class)
                    ->findListRuleByUser($this->getUser());
            }

            // Only do pagination if we have results
            if (!empty($intermediateResult)) {
                $compact = $this->nav_pagination([
                    'adapter_em_repository' => $intermediateResult,
                    'maxPerPage' => isset($pager) ? $pager : 20,
                    'page' => $page,
                ]);

                
            }
            
            $finalNbRules = $compact['nb'];
            // if compact nb is 0 set final enitites to empty array
            if ($finalNbRules === 0) {
                $finalEntities = [];
            } else {
                $finalEntities = $compact['entities'];
            }

            // Render the template with results (even if empty)
            return $this->render(
                'Rule/list.html.twig',
                [
                    'nb_rule' => $finalNbRules,
                    'entities' => $finalEntities,
                    'pager' => $compact['pager'],
                ]
            );

        } catch (Exception $e) {
            throw $this->createNotFoundException('Error : ' . $e);
        }
    }

        /**
         * SUPPRESSION D'UNE REGLE.
         *
         * @Route("/delete/{id}", name="regle_delete")
         */
        public function deleteRule(Request $request, $id): RedirectResponse
        {
            $session = $request->getSession();

            // First, checking that the rule has document not deleted
            $docClose = $this->entityManager
                ->getRepository(Document::class)
                ->findOneBy([
                    'rule' => $id,
                    'deleted' => 0,
                ]
                );
            // Return to the view detail for the rule if we found a document close
            if (!empty($docClose)) {
                $session->set('error', [$this->translator->trans('error.rule.delete_document')]);

                return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
            }

            // Then, checking that the rule has no document open or in error
            $docErrorOpen = $this->entityManager
                ->getRepository(Document::class)
                ->findOneBy([
                    'rule' => $id,
                    'deleted' => 0,
                    'globalStatus' => ['Open', 'Error'],
                ]
                );
            // Return to the view detail of the rule if we found a document open or in error
            if (!empty($docErrorOpen)) {
                $session->set('error', [$this->translator->trans('error.rule.delete_document_error_open')]);

                return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
            }

            // Checking if the rule is linked to an other one
            $ruleRelationships = $this->entityManager
                ->getRepository(RuleRelationShip::class)
                ->findBy(['fieldId' => $id]);

            // Return to the view detail of the rule if a rule relate to this one exists and is not deleted
            if (!empty($ruleRelationships)) {
                foreach ($ruleRelationships as $ruleRelationship) {
                    if (empty($ruleRelationship->getDeleted())) {
                        $session->set('error', [$this->translator->trans('error.rule.delete_relationship_exists').$ruleRelationship->getRule()]);

                        return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
                    }
                }
            }

            if ($this->getUser()->isAdmin()) {
                $list_fields_sql =
                    ['id' => $id,
                    ];
            } else {
                $list_fields_sql =
                    [
                        'id' => $id,
                        'createdBy' => $this->getUser()->getId(),
                    ];
            }
            // Detecte si la session est le support ---------

            if (isset($id)) {
                // Récupère la règle en fonction de son id
                $rule = $this->entityManager
                    ->getRepository(Rule::class)
                    ->findBy($list_fields_sql);

                $rule = $rule[0];

                // si je supprime une règle qui ne m'appartient pas alors redirection
                if (empty($rule)) {
                    return $this->redirect($this->generateUrl('regle_list'));
                }

                // On récupére l'EntityManager
                $this->getInstanceBdd();

                // Remove the rule relationships
                $ruleRelationships = $this->entityManager
                    ->getRepository(RuleRelationShip::class)
                    ->findBy(['rule' => $id]);

                if (!empty($ruleRelationships)) {
                    foreach ($ruleRelationships as $ruleRelationship) {
                        $ruleRelationship->setDeleted(1);
                        $this->entityManager->persist($ruleRelationship);
                    }
                }

                $rule->setDeleted(1);
                $rule->setActive(0);
                $this->entityManager->persist($rule);
                $this->entityManager->flush();

                return $this->redirect($this->generateUrl('regle_list'));
            }
            return $this->redirect($this->generateUrl('regle_list'));
        }

        /**
         * @Route("/displayflux/{id}", name="regle_displayflux")
         */
        public function displayFlux($id): RedirectResponse
        {
            $rule = $this->entityManager
                ->getRepository(Rule::class)
                ->findOneBy([
                    'id' => $id,
                ]
                );

            $this->sessionService->setFluxFilterWhere(['rule' => $rule->getName()]);
            $this->sessionService->setFluxFilterRuleName($rule->getName());

            return $this->redirect($this->generateUrl('document_list_page'));
        }

        /**
         * @Route("/duplic_rule/{id}", name="duplic_rule")
         */
        public function duplicateRule($id, Request $request, TranslatorInterface $translator)
        {
            try {
                $rule = $this->entityManager
                ->getRepository(Rule::class)
                ->findOneBy([
                    'id' => $id,
                ]);
                $newRule = new Rule();
                $connectorSource = $rule->getconnectorSource()->getName();
                $connectorTarget = $rule->getconnectorTarget()->getName();

                //solution id current rule
                $currentRuleSolutionSourceId = $rule->getConnectorSource()->getSolution()->getId();
                $currentRuleSolutionTargetId = $rule->getConnectorTarget()->getSolution()->getId();

                // Create the form
                $form = $this->createForm(DuplicateRuleFormType::class, $newRule, ['solution' => ['source' => $currentRuleSolutionSourceId, 'target' => $currentRuleSolutionTargetId]]);

                $form->handleRequest($request);
                //Sends new data if validated and submit
                if ($form->isSubmitted() && $form->isValid()) {
                    $now = new \DateTime();
                    $user = $this->getUser();
                    $newRuleName = $form->get('name')->getData();
                    $newRuleSource = $form->get('connectorSource')->getData();
                    $newRuleTarget = $form->get('connectorTarget')->getData();

                    if (isset($newRuleName)) {
                        // Set the rule header data
                        $newRule->setName($newRuleName)
                            ->setCreatedBy($user)
                            ->setConnectorSource($newRuleSource)
                            ->setConnectorTarget($newRuleTarget)
                            ->setDateCreated($now)
                            ->setDateModified($now)
                            ->setModifiedBy($user)
                            ->setModuleSource($rule->getModuleSource())
                            ->setModuleTarget($rule->getModuleTarget())
                            ->setDeleted(false)
                            ->setActive(false)
                            ->setNameSlug($newRuleName);

                        // Set the rule parameters
                        foreach ($rule->getParams() as $param) {
                            $paramNewRule = new RuleParam();
                            $paramNewRule->setRule($newRule);
                            $paramNewRule->setName($param->getName());
                            $paramNewRule->setValue($param->getValue());
                            $this->entityManager->persist($paramNewRule);
                        }

                        // Set the rule relationships
                        foreach ($rule->getRelationsShip() as $relationship) {
                            $relationsShipNewRule = new RuleRelationShip();
                            $relationsShipNewRule->setRule($newRule);
                            $relationsShipNewRule->setFieldNameSource($relationship->getFieldNameSource());
                            $relationsShipNewRule->setFieldNameTarget($relationship->getFieldNameTarget());
                            $relationsShipNewRule->setFieldId($relationship->getFieldId());
                            $relationsShipNewRule->setParent($relationship->getParent());
                            $relationsShipNewRule->setDeleted(0);
                            $relationsShipNewRule->setErrorEmpty($relationship->getErrorEmpty());
                            $relationsShipNewRule->setErrorMissing($relationship->getErrorMissing());
                            $this->entityManager->persist($relationsShipNewRule);
                        }

                        // Set the rule filters
                        foreach ($rule->getFilters() as $filter) {
                            $filterNewRule = new RuleFilter();
                            $filterNewRule->setRule($newRule);
                            $filterNewRule->setTarget($filter->getTarget());
                            $filterNewRule->setType($filter->getType());
                            $filterNewRule->setValue($filter->getValue());
                            $this->entityManager->persist($filterNewRule);
                        }

                        // Set the rule fields
                        foreach ($rule->getFields() as $field) {
                            $fieldNewRule = new RuleField();
                            $fieldNewRule->setRule($newRule);
                            $fieldNewRule->setTarget($field->getTarget());
                            $fieldNewRule->setSource($field->getSource());
                            $fieldNewRule->setFormula($field->getFormula());
                            $this->entityManager->persist($fieldNewRule);
                        }

                        // Save the new rule in the database
                        $this->entityManager->persist($newRule);
                        $this->entityManager->flush();
                        $success = $translator->trans('duplicate_rule.success_duplicate');
                        $this->addFlash('success', $success);
                    }

                    $this->duplicateWorkflows($id, $newRule);

                    return $this->redirect($this->generateURL('regle_list'));
                }

                return $this->render('Rule/create/duplic.html.twig', [
                    'rule' => $rule,
                    'connectorSourceUser' => $connectorSource,
                    'connectorTarget' => $connectorTarget,
                    'form' => $form->createView(),
                ]);
            } catch (Exception $e) {
                return new JsonResponse($e->getMessage());
            }
        }

        public function duplicateWorkflows($id, Rule $newRule)
        {
            // start by getting the rule fromthe id
            $rule = $this->entityManager
                ->getRepository(Rule::class)
                ->findOneBy([
                    'id' => $id,
                ]);

            // then get all the workflows linked to this rule
            $workflows = $rule->getWorkflows();

            // then duplicate each workflow, create a new one with the same name and link it to the new rule
            foreach ($workflows as $workflow) {
                $newWorkflow = new Workflow();
                $newWorkflow->setId(uniqid());
                $ruleName = substr($newRule->getName(), 0, 5);
                $workflowName = $workflow->getName();
                $newWorkflow->setName($workflowName. "-duplicate-".$ruleName);
                $newWorkflow->setRule($newRule);
                $newWorkflow->setDeleted(false);
                $newWorkflow->setCreatedBy($this->getUser());
                $newWorkflow->setModifiedBy($this->getUser());
                $newWorkflow->setDateCreated(new \DateTime());
                $newWorkflow->setDateModified(new \DateTime());
                $newWorkflow->setCondition($workflow->getCondition());
                $newWorkflow->setDescription($workflow->getDescription());
                $newWorkflow->setActive($workflow->getActive());
                $newWorkflow->setOrder($workflow->getOrder());
                $this->entityManager->persist($newWorkflow);

                $this->entityManager->flush();

                $this->duplicateWorkflowActions($workflow, $newWorkflow);
            }


        }

        public function duplicateWorkflowActions(Workflow $workflow, Workflow $newWorkflow): void
        {
            // duplicate the actions of the workflow
            $actions = $workflow->getWorkflowActions();
            foreach ($actions as $action) {
                $newAction = new WorkflowAction();
                $newAction->setId(uniqid());
                $newAction->setWorkflow($newWorkflow);
                $newAction->setCreatedBy($this->getUser());
                $newAction->setModifiedBy($this->getUser());
                $newAction->setDateCreated(new \DateTime());
                $newAction->setDateModified(new \DateTime());
                $newAction->setName($action->getName());
                $newAction->setAction($action->getAction());
                $newAction->setDescription($action->getDescription());
                $newAction->setOrder($action->getOrder());
                $newAction->setArguments($action->getArguments());
                $newAction->setDeleted(false);
                $newAction->setActive($action->getActive());
                $this->entityManager->persist($newAction);
            }

            $this->entityManager->flush();
        }

        /**
         * ACTIVE UNE REGLE.
         *
         * @Route("/update/{id}", name="regle_update")
         */
        public function ruleUpdActive($id)
        {
            try {
                // On récupére l'EntityManager
                $this->getInstanceBdd();

                $rule = $this->entityManager
                    ->getRepository(Rule::class)
                    ->find($id);

                if ($rule->getActive()) {
                    $r = 0;
                    $rule->setActive($r);
                } else {
                    $r = 1;
                    $rule->setActive($r);
                }

                $this->entityManager->persist($rule);
                $this->entityManager->flush();

                return new Response($r);
            } catch (Exception $e) {
                return new JsonResponse($e->getMessage());
            }
        }

        /**
         * Executer une règle manuellement.
         *
         * @Route("/exec/{id}", name="regle_exec")
         */
        public function ruleExecAction($id, $documentId = null)
        {
            // We added a doc id to this function to carry the document ids in case of a run rule by doc id.
            // In every case except our mass run by doc id, $documentId will be null so we keep the usual behaviour of the function untouched. 
            try {
                $this->ruleManager->setRule($id);

                if ($documentId !== null) {
                    $this->ruleManager->actionRule('runRuleByDocId', 'execrunRuleByDocId', $documentId);

                } elseif ('ALL' == $id) {
                    $this->ruleManager->actionRule('ALL');

                    return $this->redirect($this->generateUrl('regle_list'));
                } elseif ('ERROR' == $id) {
                    $this->ruleManager->actionRule('ERROR');

                    return $this->redirect($this->generateUrl('regle_list'));
                }
                if ($documentId === null){

                    $this->ruleManager->actionRule('runMyddlewareJob');
                }

                return $this->redirect($this->generateURL('regle_open', ['id' => $id]));
            } catch (Exception $e) {
                $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());

                return $this->redirect($this->generateUrl('regle_list'));
            }
        }

        /**
         * CANCEL ALL TRANSFERS FOR ONE RULE.
         *
         * @Route("/view/cancel/documents/{id}", name="rule_cancel_all_transfers")
         */
        public function cancelRuleTransfers($id)
        {
            try {
                $this->ruleManager->setRule($id);
                $result = $this->ruleManager->actionRule('runMyddlewareJob', 'cancelDocumentJob');
            } catch (\Exception $e) {
                return $e->getMessage();
            }

            return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
        }

        /**
         * DELETE ALL TRANSFERS FOR ONE RULE.
         *
         * @Route("/view/delete/documents/{id}", name="rule_delete_all_transfers")
         */
        public function deleteRuleTransfers($id)
        {
            try {
                $this->ruleManager->setRule($id);
                $result = $this->ruleManager->actionRule('runMyddlewareJob', 'deleteDocumentJob');
            } catch (\Exception $e) {
                return $e->getMessage();
            }

            return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
        }

        /**
         * MODIFIE LES PARAMETRES D'UNE REGLE.
         * @return JsonResponse|Response
         * @Route("/update/params/{id}", name="path_fiche_params_update")
         */
        public function ruleUpdParams($id)
        {
            try {
                // On récupére l'EntityManager
                $this->getInstanceBdd();
                if (isset($_POST['params']) && is_array($_POST['params'])) {
                    foreach ($_POST['params'] as $p) {
                        $param = $this->entityManager->getRepository(RuleParam::class)
                            ->findOneBy([
                                'rule' => $id,
                                'name' => $p['name'],
                            ]
                            );

                        // In a few case, the parameter could not exist, in this case we create it
                        if (empty($param)) {
                            // Create rule entity
                            $rule = $this->entityManager
                                            ->getRepository(Rule::class)
                                            ->findOneBy([
                                                'id' => $id,
                                            ]
                                            );
                            $param = new RuleParam();
                            $param->setRule($rule);
                            $param->setName($p['name']);
                            $param->setValue($p['value']);
                        } else {
                            // Save param modification in the audit table
                            if ($p['value'] != $param->getValue()) {
                                $paramAudit = new RuleParamAudit();
                                $paramAudit->setRuleParamId($p['id']);
                                $paramAudit->setDateModified(new \DateTime());
                                $paramAudit->setBefore($param->getValue());
                                $paramAudit->setAfter($p['value']);
                                $paramAudit->setByUser($this->getUser()->getId());
                                $this->entityManager->persist($paramAudit);
                            }
                            $param->setValue($p['value']);
                        }
                        $this->entityManager->persist($param);
                        $this->entityManager->flush();
                    }
                }

                return new Response(1);
            } catch (Exception $e) {
                return new JsonResponse($e->getMessage());
            }
        }

        /**
         * SIMULE LA LECTURE POUR RETOURNER LE NOMBRE DE TRANSFERS POTENTIELS.
         * @Route("/simule/{id}", name="path_fiche_params_simulate")
         */
        public function ruleSimulateTransfers(Rule $rule): Response
        {
            try {
                // On récupére l'EntityManager
                $this->getInstanceBdd();

                // Get the rule reference
                $param['date_ref'] = $rule->getParamByName('datereference')->getValue();
                // Get the rule limit
                $limitParam = $rule->getParamByName('limit');
                if ($limitParam) {
                    $param['limit'] = $limitParam->getValue();
                }
                // Get the other rule params
                $connectorParams = $rule->getParams();
                foreach ($connectorParams as $connectorParam) {
                    $param['ruleParams'][$connectorParam->getName()] = $connectorParam->getValue();
                }

                $param['fields'] = [];
                // Extraction des champs sources
                foreach ($rule->getFields() as $ruleField) {
                    // It could be several fields in a source when there is a formula
                    $sources = explode(';', $ruleField->getSource());
                    foreach ($sources as $source) {
                        $param['fields'][] = $source;
                    }
                }

                // Module source
                $param['module'] = (string) $rule->getModuleSource();

                // Solution source
                $solution_source_nom = $rule->getConnectorSource()->getSolution()->getName();

                // Connector source -------------------
                $connectorParamsSource = $this->entityManager
                                                ->getRepository(ConnectorParam::class)
                                                ->findBy(['connector' => $rule->getConnectorSource()]);
                $connectorSource['solution'] = $rule->getConnectorSource()->getSolution()->getName();
                foreach ($connectorParamsSource as $connector) {
                    $connectorSource[$connector->getName()] = $connector->getValue();
                }

                $solution_source = $this->solutionManager->get($solution_source_nom);
                $solution_source->login($connectorSource);

                // Rule Mode
                $param['ruleParams']['mode'] = $rule->getParamByName('mode')->getValue();

                if (empty($param['ruleParams']['mode'])) {
                    $param['ruleParams']['mode'] = '0';
                }

                $param['offset'] = '0';
                $param['call_type'] = 'read';
                $result = $solution_source->readData($param);
                if (!empty($result['error'])) {
                    throw new Exception('Reading Issue: '.$result['error']);
                }
                if (isset($result['count'])) {
                    return new Response($result['count']);
                }

                return new Response(0);
            } catch (Exception $e) {
                $errorMessage = $e->getMessage().' '.$e->getFile().' '.$e->getLine();

                return new Response(json_encode(['error' => $errorMessage]));
            }
        }

        /**
         * MODE EDITION D'UNE REGLE.
         *
         * @Route("/edit/{id}", name="regle_edit")
         */
        public function ruleEditAction(Request $request, Rule $rule): RedirectResponse
        {
            $session = $request->getSession();

            try {
                // First, checking that the rule has no document open or in error
                $docErrorOpen = $this->entityManager
                    ->getRepository(Document::class)
                    ->findOneBy([
                        'rule' => $rule,
                        'deleted' => 0,
                        'globalStatus' => ['Open', 'Error'],
                    ]);
                // Return to the view detail fo the rule if we found a document open or in error
                if (!empty($docErrorOpen)) {
                    if ($this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
                        $session->set('warning', [$this->translator->trans('error.rule.edit_document_error_open_admin')]);
                    } else {
                        $session->set('error', [$this->translator->trans('error.rule.edit_document_error_open')]);

                        return $this->redirect($this->generateUrl('regle_open', ['id' => $rule->getId()]));
                    }
                }

                $this->sessionService->setParamRuleLastKey($rule->getId());
                $key = $this->sessionService->getParamRuleLastKey();
                //--
                // si une session existe alors on la supprime
                if ($this->sessionService->isParamRuleExist($key)) {
                    $this->sessionService->removeParamRule($key);
                }

                // préparation des sessions
                if (!empty($rule->getDeleted())) {
                    $session->set('error', [$this->translator->trans('error.rule.edit_rule_deleted')]);

                    return $this->redirect($this->generateUrl('regle_open', ['id' => $rule->getId()]));
                }

                // composition des sessions
                $this->sessionService->setParamRuleNameValid($key, true);
                $this->sessionService->setParamRuleName($key, $rule->getName());
                $this->sessionService->setParamRuleConnectorSourceId($key, (string) $rule->getConnectorSource()->getId());
                $this->sessionService->setParamRuleConnectorCibleId($key, (string) $rule->getConnectorTarget()->getId());
                $this->sessionService->setParamRuleLastId($key, $rule->getId());

                // Connector source -------------------
                $connectorParamsSource = $this->entityManager
                    ->getRepository(ConnectorParam::class)
                    ->findByConnector([$rule->getConnectorSource()]);

                $this->sessionService->setParamRuleSourceSolution($key, $rule->getConnectorSource()->getSolution()->getName());

                foreach ($connectorParamsSource as $connector) {
                    $this->sessionService->setParamRuleSourceConnector($key, $connector->getName(), $connector->getValue());
                }
                // Connector source -------------------

                // Connector target -------------------
                $connectorParamsTarget = $this->entityManager
                    ->getRepository(ConnectorParam::class)
                    ->findByConnector([$rule->getConnectorTarget()]);

                $this->sessionService->setParamRuleCibleSolution($key, $rule->getConnectorTarget()->getSolution()->getName());

                foreach ($connectorParamsTarget as $connector) {
                    $this->sessionService->setParamRuleCibleConnector($key, $connector->getName(), $connector->getValue());
                }
                // Connector target -------------------

                // Paramètre d'une règle
                if ($rule->getParams()->count()) {
                    $params = [];
                    foreach ($rule->getParams() as $ruleParamsObj) {
                        $params[] = [
                            'name' => $ruleParamsObj->getName(),
                            'value' => $ruleParamsObj->getValue(),
                        ];
                    }
                    $this->sessionService->setParamRuleReloadParams($key, $params);
                }

                // Modules --
                $this->sessionService->setParamRuleSourceModule($key, $rule->getModuleSource());
                $this->sessionService->setParamRuleCibleModule($key, $rule->getModuletarget());
                // Modules --

                // reload ---------------
                $ruleFields = $rule->getFields();

                // get_modules_fields en source pour avoir l'association fieldid / libellé (ticket 548)
                $solution_source_nom = $this->sessionService->getParamRuleSourceSolution($key);
                $solution_source = $this->solutionManager->get($solution_source_nom);

                $login = $solution_source->login($this->decrypt_params($this->sessionService->getParamRuleSource($key)));
                if (empty($solution_source->connexion_valide)) {
                    throw new Exception('failed to login to the source application .'.(!empty($login['error']) ? $login['error'] : ''));
                }

                // SOURCE ----- Récupère la liste des champs source
                // O récupère le module de la règle
                $sourceModule = $rule->getModuleSource();
                $sourceFieldsInfo = $solution_source->get_module_fields($sourceModule);

                // Champs et formules d'une règle
                if ($ruleFields) {
					$fields = array();
                    foreach ($ruleFields as $ruleFieldsObj) {
                        $array = [
                            'target' => $ruleFieldsObj->getTarget(),
                            'source' => [],
                            'formula' => $ruleFieldsObj->getFormula(),
                        ];
                        $fields_source = explode(';', $ruleFieldsObj->getSource());

                        if (!empty($fields_source)) {
                            foreach ($fields_source as $field_source) {
                                if ('my_value' == $field_source) {
                                    $array['source'][$field_source] = 'my_value';
                                } elseif (isset($sourceFieldsInfo[$field_source])) {
                                    $array['source'][$field_source] = $sourceFieldsInfo[$field_source]['label'];
                                } else {
                                    if (!empty($sourceFieldsInfo)) {
                                        foreach ($sourceFieldsInfo as $multiModule) {
                                            if (isset($multiModule[$field_source])) {
                                                $array['source'][$field_source] = $multiModule[$field_source]['label'];
                                            }
                                        }
                                    }
                                }
                                if (!isset($array['source'][$field_source])) {
                                    throw new Exception('failed to get the field '.$field_source);
                                }
                            }
                            $fields[] = $array;
                        }
                    }
                    $this->sessionService->setParamRuleReloadFields($key, $fields);
                }

                // Relations d'une règle
                if ($rule->getRelationsShip()->count()) {
                    foreach ($rule->getRelationsShip() as $ruleRelationShipsObj) {
                        $relate[] = [
                            'source' => $ruleRelationShipsObj->getFieldNameSource(),
                            'target' => $ruleRelationShipsObj->getFieldNameTarget(),
                            'errorMissing' => (!empty($ruleRelationShipsObj->getErrorMissing()) ? '1' : '0'),
                            'errorEmpty' => (!empty($ruleRelationShipsObj->getErrorEmpty()) ? '1' : '0'),
                            'id' => $ruleRelationShipsObj->getFieldId(),
                            'parent' => $ruleRelationShipsObj->getParent(),
                        ];
                    }
                    $this->sessionService->setParamRuleReloadRelate($key, $relate);
                }

                // Filter
                if ($rule->getFilters()->count()) {
                    foreach ($rule->getFilters() as $ruleFilters) {
                        $filter[] = [
                            'target' => $ruleFilters->getTarget(),
                            'type' => $ruleFilters->getType(),
                            'value' => $ruleFilters->getValue(),
                        ];
                    }
                }

                $this->sessionService->setParamRuleReloadFilter($key, ((isset($filter)) ? $filter : []));

                // reload ---------------
                return $this->redirect($this->generateUrl('regle_stepthree', ['id' => $rule->getId()]));
            } catch (Exception $e) {
                $this->sessionService->setCreateRuleError($key, $this->translator->trans('error.rule.update').' '.$e->getMessage().' '.$e->getFile().' '.$e->getLine());
                $session->set('error', [$this->translator->trans('error.rule.update').' '.$e->getMessage().' '.$e->getFile().' '.$e->getLine()]);

                return $this->redirect($this->generateUrl('regle_open', ['id' => $rule->getId()]));
            }
        }

        /**
         * from the id of the rule, we get the name of the rule
         * @Route("/get-rule-name/{id}", name="get_rule_name")
         */
        public function getRuleNameById($id): Response
        {
            $rule = $this->entityManager->getRepository(Rule::class)->find($id);
            return new Response($rule->getName());
        }

        /**
         * from the formula, we get the first part of the formula
         * @Route("/get-first-part-of-lookup-formula/{formula}", name="get_first_part_of_lookup_formula")
         */
        public function getFirstPartOfLookupFormula($formula): Response
        {
            // Extract everything up to the first quote mark after the lookup (excluding the quote)
            if (preg_match('/lookup\(\{[^}]+\},\s*/', $formula, $matches)) {
                return new Response($matches[0]);
            }
            return new Response('');
        }

        /**
         * from the formula, we get the second part of the formula
         * @Route("/get-second-part-of-lookup-formula/{formula}", name="get_second_part_of_lookup_formula")
         */
        public function getSecondPartOfLookupFormula($formula): Response
        {
            // Extract everything after the rule ID until the end
            if (preg_match('/",\s*(.+)\)/', $formula, $matches)) {
                return new Response(', ' . $matches[1] . ')');
            }
            return new Response('');
        }

        /**
         * FICHE D'UNE REGLE.
         * @Route("/view/{id}", name="regle_open")
         * @throws Exception
         */
        public function ruleOpenAction($id): Response
        {
            if ($this->getUser()->isAdmin()) {
                $list_fields_sql = ['id' => $id];
            } else {
                $list_fields_sql =
                    ['id' => $id,
                        'createdBy' => $this->getUser()->getId(),
                    ];
            }
            // Detecte si la session est le support ---------

            // Infos de la regle
            /** @var Rule $rule */
            $rule = $this->entityManager->getRepository(Rule::class)->findOneBy($list_fields_sql);
            if (!$rule) {
                throw $this->createNotFoundException('Couldn\'t find specified rule in database');
            }

            // Liste des relations
            $rule_relationships = $rule->getRelationsShip();
            $solution_cible_nom = $rule->getConnectorTarget()->getSolution()->getName();
            $solution_cible = $this->solutionManager->get($solution_cible_nom);
            $moduleCible = (string) $rule->getModuleTarget();

            $tab_rs = [];
            $i = 0;
            foreach ($rule_relationships as $r) {
                $tab_rs[$i]['getFieldId'] = $r->getFieldId();
                $tab_rs[$i]['getFieldNameSource'] = $r->getFieldNameSource();
                $tab_rs[$i]['getFieldNameTarget'] = $r->getFieldNameTarget();
                $tab_rs[$i]['getErrorMissing'] = $r->getErrorMissing();
                $tab_rs[$i]['getErrorEmpty'] = $r->getErrorEmpty();
                $tab_rs[$i]['getParent'] = $r->getParent();

                $ruleTmp = $this->entityManager->getRepository(Rule::class)
                    ->findOneBy([
                        'id' => $r->getFieldId(),
                    ]
                    );

                $tab_rs[$i]['getName'] = $ruleTmp->getName();
                ++$i;
            }

            // Infos connecteurs & solutions
            $ruleRepo = $this->entityManager->getRepository(Rule::class);
            $connector = $ruleRepo->infosConnectorByRule($rule->getId());

            // Changement de référence pour certaines solutions
            $autorization_source = $connector[0]['solution_source'];
            $autorization_module_trans = mb_strtolower($rule->getModuleSource());

            $Params = $rule->getParams();
            $Fields = $rule->getFields();
            $Filters = $rule->getFilters();
            $ruleParam = RuleManager::getFieldsParamView();
            $params_suite = [];
            if ($Params) {
                foreach ($Params as $field) {
                    $standardField = false;
                    foreach ($ruleParam as $index => $value) {
                        // Init the parameter in case it doesn't exist in the database yet
                        if (!isset($ruleParam[$index]['id_bdd'])) {
                            $ruleParam[$index]['id_bdd'] = '';
                            $ruleParam[$index]['value_bdd'] = '';
                        }
                        if ($field->getName() == $value['name']) {
                            $ruleParam[$index]['id_bdd'] = $field->getId();
                            $ruleParam[$index]['value_bdd'] = $field->getValue();
                            $standardField = true;
                            break;
                        }
                    }
                    if (!$standardField) {
                        if ('mode' == $field->getName()) {
                            // We send the translation of the mode to the view
                            switch ($field->getValue()) {
                                case '0':
                                    $params_suite['mode'] = $this->tools->getTranslation(['create_rule', 'step3', 'syncdata', 'create_modify']);
                                    break;
                                case 'C':
                                    $params_suite['mode'] = $this->tools->getTranslation(['create_rule', 'step3', 'syncdata', 'create_only']);
                                    break;
                                case 'S':
                                    $params_suite['mode'] = $this->tools->getTranslation(['create_rule', 'step3', 'syncdata', 'search_only']);
                                    break;
                                default:
                                    $params_suite['mode'] = $field->getValue();
                            }
                        } elseif ('bidirectional' == $field->getName()) {
                            if (!empty($field->getValue())) {
                                $ruleBidirectional = $this->entityManager->getRepository(Rule::class)
                                    ->findOneBy([
                                        'id' => $field->getValue(),
                                    ]
                                    );
                                // Send the name and the id of the opposite rule to the view
                                $params_suite['bidirectional'] = $field->getValue();
                                $params_suite['bidirectionalName'] = $ruleBidirectional->getName();
                            }
                        } else {
                            $params_suite['customParams'][] = ['name' => $field->getName(), 'value' => $field->getValue()];
                        }
                    }
                }
            }

            // get the workflows of the rule, if there are none then set hasWorkflows to false. If there is at least one then set it to true. to get the workflows we use the entity manager and filter by the rule id
            $hasWorkflows = $this->entityManager->getRepository(Workflow::class)->findBy(['rule' => $rule->getId(), 'deleted' => 0]) ? true : false;
            
            if ($hasWorkflows) {
                $workflows = $this->entityManager->getRepository(Workflow::class)->findBy(['rule' => $rule->getId(), 'deleted' => 0]);
                
            } else {
                $workflows = [];
            }

            return $this->render('Rule/edit/fiche.html.twig', [
                'rule' => $rule,
                'connector' => $connector[0],
                'fields' => $Fields,
                'relate' => $tab_rs,
                'parentRelationships' => $solution_cible->allowParentRelationship($moduleCible),
                'params' => $ruleParam,
                'filters' => $Filters,
                'params_suite' => $params_suite,
                'id' => $id,
                'hasWorkflows' => $hasWorkflows,
                'workflows' => $workflows,
            ]
            );
        }

        /**
         * @return JsonResponse|Response
         * CREATION - STEP ONE - CONNEXION : jQuery ajax.
         * @Route("/inputs", name="regle_inputs", methods={"POST"}, options={"expose"=true})
         */
        public function ruleInputs(Request $request)
        {
            try {
                $ruleKey = $this->sessionService->getParamRuleLastKey();
                // Retourne la liste des inputs pour la connexion
                if (1 == $request->request->get('mod')) {
                    if (is_string($request->request->get('solution')) && is_string($request->request->get('parent'))) {
                        if (preg_match("#[\w]#", $request->request->get('solution')) && preg_match("#[\w]#", $request->request->get('parent'))) {
                            $classe = strtolower($request->request->get('solution'));
                            $parent = $request->request->get('parent');
                            $solution = $this->entityManager->getRepository(Solution::class)->findOneBy(['name' => $classe]);
                            $connector = new Connector();
                            $connector->setSolution($solution);
                            $fieldsLogin = [];
                            if (null !== $connector->getSolution()) {
                                $fieldsLogin = $this->solutionManager->get($connector->getSolution()->getName())->getFieldsLogin();
                            }
                            $form = $this->createForm(ConnectorType::class, $connector, [
                                'action' => $this->generateUrl('regle_connector_insert'),
                                'attr' => [
                                    'fieldsLogin' => $fieldsLogin,
                                    'secret' => $this->getParameter('secret'),
                                ],
                            ]);

                            return $this->render('Ajax/result_liste_inputs.html.twig', [
                                'form' => $form->createView(),
                                'parent' => $parent,
                            ]
                            );
                        }
                    }
                } // Vérifie si la connexion peut se faire ou non
                elseif (2 == $request->request->get('mod') || 3 == $request->request->get('mod')) {
                    // Connector
                    if (2 == $request->request->get('mod')) {
                        if (preg_match("#[\w]#", $request->request->get('champs')) && preg_match("#[\w]#", $request->request->get('parent')) && preg_match("#[\w]#", $request->request->get('solution'))) {
                            $classe = strtolower($request->request->get('solution'));
                            $solution = $this->solutionManager->get($classe);

                            // établi un tableau params
                            $champs = explode(';', $request->request->get('champs'));

                            if ($champs) {
                                foreach ($champs as $key) {
                                    $input = explode('::', $key);
                                    if (!empty($input[0])) {
                                        if (!empty($input[1]) || is_numeric($input[1])) {
                                            $param[$input[0]] = trim($input[1]);
                                            $this->sessionService->setParamConnectorParentType($request->request->get('parent'), $input[0], trim($input[1]));
                                        }
                                    }
                                }
                            }
                            $this->sessionService->setParamConnectorParentType($request->request->get('parent'), 'solution', $classe);

                            // Vérification du nombre de champs
                            if (isset($param) && (count($param) == count($solution->getFieldsLogin()))) {
                                $result = $solution->login($param);
                                $r = $solution->connexion_valide;

                                if (!empty($r)) {
                                    return new JsonResponse(['success' => true]); // Connexion valide
                                }
                                $this->sessionService->removeParamRule($ruleKey);

                                return new JsonResponse(['success' => false, 'message' => $this->translator->trans($result['error'])]); // Erreur de connexion
                            }

                            return new JsonResponse(['success' => false, 'message' => $this->translator->trans('Connection error')]); // Erreur pas le même nombre de champs
                        } else {
                            // Either parent, solution or champs is empty (from AJAX request sent in verif(div_clock) function in regle.js)
                            return new JsonResponse(['success' => false, 'message' => $this->translator->trans('create_connector.form_error')]);
                        }
                    } // Rule
                    elseif (3 == $request->request->get('mod')) {
                        // 0 : solution
                        // 1 : id connector
                        $params = explode('_', $request->request->get('solution'));

                        // Deux params obligatoires
                        if (2 == count($params) && intval($params[1]) && is_string($params[0])) {
                            $this->sessionService->removeParamParentRule($ruleKey, $request->request->get('parent'));
                            $classe = strtolower($params[0]);
                            $solution = $this->solutionManager->get($classe);

                            $connector = $this->entityManager
                                ->getRepository(Connector::class)
                                ->find($params[1]);

                            $connector_params = $this->entityManager
                                ->getRepository(ConnectorParam::class)
                                ->findBy(['connector' => $connector]);

                            if ($connector_params) {
                                foreach ($connector_params as $key) {
                                    $this->sessionService->setParamConnectorParentType($request->request->get('parent'), $key->getName(), $key->getValue());
                                }
                            }

                            $this->sessionService->setParamRuleName($ruleKey, $request->request->get('name'));

                            // Affectation id connector
                            $this->sessionService->setParamRuleConnectorParent($ruleKey, $request->request->get('parent'), $params[1]);

                            $result = $solution->login($this->decrypt_params($this->sessionService->getParamParentRule($ruleKey, $request->request->get('parent'))));
                            $this->sessionService->setParamRuleParentName($ruleKey, $request->request->get('parent'), 'solution', $classe);

                            $r = $solution->connexion_valide;
                            if (!empty($r)) {
                                return new JsonResponse(['success' => true]); // Connexion valide
                            }

                            return new JsonResponse(['success' => false, 'message' => $this->translator->trans($result['error'])]); // Erreur de connexion

                            exit;

                            return $this->render('Ajax/result_connexion.html.twig', []
                            );
                        }

                        return new JsonResponse(['success' => false, 'message' => $this->translator->trans('Connection error')]);
                    }
                } else {
                    $this->logger->error("Error: Not Found Exception");
                    throw $this->createNotFoundException('Error');
                }
                return new JsonResponse(['success' => false]);
            } catch (Exception $e) {
                return new JsonResponse(['success' => false, 'message' => $e->getMessage().' '.$e->getLine().' '.$e->getFile()]);
            }
        }

        /**
         * CREATION - STEP ONE - VERIF ALIAS RULE.
         *
         * @Route("/inputs/name_unique/", name="regle_inputs_name_unique", methods={"POST"}, options={"expose"=true})
         */
        public function ruleNameUniq(Request $request): JsonResponse
        {
            $key = $this->sessionService->getParamRuleLastKey();

            if ('POST' == $request->getMethod()) {
                $this->getInstanceBdd();

                // Cherche si la règle existe en fonction de son nom
                $rule = $this->entityManager->getRepository(Rule::class)
                    ->findOneBy([
                        'name' => $request->request->get('name'),
                    ]
                    );

                // 0 existe pas 1 existe
                if (null == $rule) {
                    $existRule = 0;
                    $this->sessionService->setParamRuleNameValid($key, true);
                    $this->sessionService->setParamRuleName($key, $request->request->get('name'));
                } else {
                    $existRule = 1;
                    $this->sessionService->setParamRuleNameValid($key, false);
                }

                return new JsonResponse($existRule);
            }
            throw $this->createNotFoundException('Error');
        }

        /**
         * CREATION - STEP TWO - CHOIX MODULES.
         *
         * @return RedirectResponse|Response
         *
         * @Route("/create/step2/", name="regle_steptwo", methods={"POST"})
         */
        public function ruleStepTwo(Request $request)
        {
            $session = $request->getSession();
            $myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
            // We always add data again in session because these data are removed after the call of the get
            $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
            // si le nom de la règle est inferieur à 3 caractères :
            if (!isset($myddlewareSession['param']['rule']['source']['solution']) || strlen($myddlewareSession['param']['rule']['rulename']) < 3 || false == $myddlewareSession['param']['rule']['rulename_valide']) {
                $myddlewareSession['error']['create_rule'] = $this->translator->trans('error.rule.valid');
                $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);

                return $this->redirect($this->generateUrl('regle_stepone_animation'));
                exit;
            }

            try {
                // ---------------- SOURCE ----------------------------
                $solution_source_nom = $myddlewareSession['param']['rule']['source']['solution'];
                $solution_source = $this->solutionManager->get($solution_source_nom);

                $sourceConnection = $solution_source->login($this->decrypt_params($myddlewareSession['param']['rule']['source']));

                if (empty($solution_source->connexion_valide)) {
                    $myddlewareSession['error']['create_rule'] = $this->translator->trans('error.rule.source_module_connect').' '.(!empty($sourceConnection['error']) ? $sourceConnection['error'] : 'No message returned by '.$solution_source_nom);
                    $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);

                    return $this->redirect($this->generateUrl('regle_stepone_animation'));
                    exit;
                }

                $liste_modules_source = ToolsManager::composeListHtml($solution_source->get_modules('source'), $this->translator->trans('create_rule.step2.choice_module'));
                if (!$liste_modules_source) {
                    $myddlewareSession['error']['create_rule'] = $this->translator->trans('error.rule.source_module_load_list');
                    $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);

                    return $this->redirect($this->generateUrl('regle_stepone_animation'));
                    exit;
                }

                // ---------------- /SOURCE ----------------------------
            } catch (Exception $e) {
                $myddlewareSession['error']['create_rule'] = $this->translator->trans('error.rule.source_module_all');
                $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);

                return $this->redirect($this->generateUrl('regle_stepone_animation'));
                exit;
            }

            try {
                // ---------------- TARGET ----------------------------
                // Si la solution est la même que la précèdente on récupère les infos
                if ($myddlewareSession['param']['rule']['source']['solution'] == $myddlewareSession['param']['rule']['cible']['solution']) {
                    $solution_cible = $solution_source;
                    $solution_cible_nom = $solution_source_nom;
                } else {
                    $solution_cible_nom = $myddlewareSession['param']['rule']['cible']['solution'];
                    $solution_cible = $this->solutionManager->get($solution_cible_nom);
                }
                $targetConnection = $solution_cible->login($this->decrypt_params($myddlewareSession['param']['rule']['cible']));

                if (empty($solution_cible->connexion_valide)) {
                    $myddlewareSession['error']['create_rule'] = $this->translator->trans('error.rule.target_module_connect').' '.(!empty($targetConnection['error']) ? $targetConnection['error'] : 'No message returned by '.$solution_cible_nom);
                    $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);

                    return $this->redirect($this->generateUrl('regle_stepone_animation'));
                    exit;
                }

                $liste_modules_cible = ToolsManager::composeListHtml($solution_cible->get_modules('target'), $this->translator->trans('create_rule.step2.choice_module'));

                if (!$liste_modules_cible) {
                    $myddlewareSession['error']['create_rule'] = $this->translator->trans('error.rule.target_module_load_list');
                    $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);

                    return $this->redirect($this->generateUrl('regle_stepone_animation'));
                    exit;
                }
                // ---------------- /TARGET ----------------------------
            } catch (Exception $e) {
                $myddlewareSession['error']['create_rule'] = $this->translator->trans('error.rule.target_module_all');
                $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);

                return $this->redirect($this->generateUrl('regle_stepone_animation'));
                exit;
            }

            return $this->render('Rule/create/step2.html.twig', [
                'solution_source' => $solution_source_nom,
                'solution_cible' => $solution_cible_nom,
                'liste_modules_source' => $liste_modules_source,
                'liste_modules_cible' => $liste_modules_cible,
                'params' => $myddlewareSession['param']['rule'],
            ]
            );
        }

        /**
         * CREATION - STEP THREE - SIMULATION DES DONNEES.
         *
         * @Route("/create/step3/simulation/", name="regle_simulation", methods={"POST"})
         */
        public function ruleSimulation(Request $request): Response
        {
            $ruleKey = $this->sessionService->getParamRuleLastKey();

            if ('POST' == $request->getMethod() && $this->sessionService->isParamRuleExist($ruleKey)) {
                // retourne un tableau prêt à l'emploi
                $target = $this->createListeParamsRule(
                    $request->request->get('champs'), // Fields
                    $request->request->get('formules'), // Formula
                    '' // Params flux
                );

                $solution_source_nom = $this->sessionService->getParamRuleSourceSolution($ruleKey);
                $solution_source = $this->solutionManager->get($solution_source_nom);
                $solution_source->login($this->sessionService->getParamRuleSource($ruleKey));
                $tab_simulation = [];
                $sourcesfields = [];

                // récupération de tous les champs
                if (isset($target['fields']) && count($target['fields']) > 0) {
                    foreach ($target['fields'] as $f) {
                        if (isset($f)) {
                            foreach ($f as $name_fields_target => $k) {
                                if (isset($k['champs'])) {
                                    $sourcesfields = array_merge($k['champs'], $sourcesfields);
                                }
                            }
                        }
                    }
                } else {
                    // ici pour les règles avec des relations uniquement
                    return $this->render('Rule/create/onglets/simulation_tab.html.twig', [
                        'before' => [], // source
                        'after' => [], // target
                        'data_source' => false,
                    ]
                    );
                }

                // Add rule param if exist (the aren't exist in rule creation)
                $ruleParams = [];
                $ruleParamsResult = $this->entityManager->getRepository(RuleParam::class)->findBy(['rule' => $ruleKey]);
                if (!empty($ruleParamsResult)) {
                    foreach ($ruleParamsResult as $ruleParamsObj) {
                        $ruleParams[$ruleParamsObj->getName()] = $ruleParamsObj->getValue();
                    }
                }
                // The mode is empty when we create the rule, so we set a default value
                if (empty($ruleParams['ruleParams']['mode'])) {
                    $ruleParams['mode'] = '0';
                }

                // Get result from AJAX request in regle.js
                $form = $request->request->all();
                if (isset($form['query'])) {
                    $this->simulationQueryField = $form['query'];
                }

                // Avoid sending query on specific record ID if the user didn't actually input something
                if (empty($this->simulationQueryField)) {
                    // Get source data
                    $source = $solution_source->readData([
                        'module' => $this->sessionService->getParamRuleSourceModule($ruleKey),
                        'fields' => $sourcesfields,
                        'date_ref' => '1970-01-01 00:00:00',  // date_ref is required for some application like Prestashop
                        'limit' => 1,
                        'ruleParams' => $ruleParams,
                        'call_type' => 'simulation',
                        ]);
                } else {
                    // Get source data
                    $source = $solution_source->readData([
                            'module' => $this->sessionService->getParamRuleSourceModule($ruleKey),
                            'fields' => $sourcesfields,
                            'date_ref' => '1970-01-01 00:00:00',  // date_ref is required for some application like Prestashop
                            'limit' => 1,
                            'ruleParams' => $ruleParams,
                            'query' => [(!empty($ruleParams['fieldId']) ? $ruleParams['fieldId'] : 'id') => $this->simulationQueryField],
                            'call_type' => 'simulation',
                        ]);

                    // In case of wrong record ID input from user
                    if (!empty($source['error'])) {
                        return $this->render('Rule/create/onglets/invalidrecord.html.twig');
                    }
                }

                $before = [];
                $after = [];
                if (!empty($source['values'])) {
                    $record = current($source['values']); // Remove a dimension to the array because we need only one record
                    if (!empty($record)) {
                        foreach ($target['fields'] as $f) {
                            foreach ($f as $name_fields_target => $k) {
                                $r['after'] = [];
                                // Préparation pour transformation
                                $name = trim($name_fields_target);
                                $target_fields = [
                                    'target_field_name' => $name,
                                    'source_field_name' => ((isset($k['champs'])) ? implode(';', $k['champs']) : 'my_value'),
                                    'formula' => ((isset($k['formule'][0]) ? $k['formule'][0] : '')),
                                    'related_rule' => '',
                                ];

								// Add rule id for simulation purpose when using lookup function
								$this->documentManager->setRuleId($ruleKey);
								// Add variables for simulation purpose
								$variablesEntity = $this->entityManager->getRepository(Variable::class)->findAll();
								if (!empty($variablesEntity)) {
									foreach ($variablesEntity as $variable) {
										$variables[$variable->getName()] = $variable->getvalue();
									}
									$this->documentManager->setParam(array('variables'=>$variables));
								}
								// Fix the document type for the simulation 
								$this->documentManager->setDocumentType('C');
                                // Transformation
								$response = $this->documentManager->getTransformValue($record, $target_fields);
                                if (!isset($response['message'])) {
                                    $r['after'][$name_fields_target] = $this->documentManager->getTransformValue($record, $target_fields);
                                }
                                // If error during transformation, we send back the error
                                if (
                                    null == $r['after'][$name_fields_target]
                                    and !empty($response['message'])
                                ) {
                                    $r['after'][$name_fields_target] = $response['message'];
                                }

                                $k['fields'] = [];
                                if (empty($k['champs'])) {
                                    $k['fields']['Formula'] = ((isset($k['formule'][0]) ? $k['formule'][0] : ''));
                                } else {
                                    foreach ($k['champs'] as $fields) {
                                        // Fields couldn't be return. For example Magento return only field not empty
                                        if (!empty($record[$fields])) {
                                            $k['fields'][$fields] = $record[$fields];
                                        } else {
                                            $k['fields'][$fields] = '';
                                        }
                                    }
                                }

                                $tab_simulation[] = [
                                    'after' => $r['after'],
                                    'before' => $k['fields'],
                                ];
                            }
                        }
                        $after = [];
                        // Préparation pour tableau template
                        foreach ($tab_simulation as $key => $value) {
                            foreach ($value as $k => $v) {
                                if ('before' == $k) {
                                    $before[] = $v;
                                } else {
                                    foreach ($v as $key => $value) {
                                        // if value does not contains the substring "mdw_no_send_field"
                                        if (strpos($value, 'mdw_no_send_field') === false) {
                                            $after[] = $v;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                return $this->render('Rule/create/onglets/simulation_tab.html.twig', [
                    'before' => $before, // source
                    'after' => $after, // target
                    'data_source' => (!empty($record) ? true : false),
                    'params' => $this->sessionService->getParamRule($ruleKey),
                    'simulationQueryField' => $this->simulationQueryField,
                ]
                );
            }
            throw $this->createNotFoundException('Error');
        }

        /**
         * CREATION - STEP THREE - CHOIX DES CHAMPS - MAPPING DES CHAMPS.
         *
         * @return RedirectResponse|Response
         *
         * @Route("/create/step3/{id}", name="regle_stepthree", defaults={"id"=0})
         */
        public function ruleStepThree(Request $request)
        {
            $this->getInstanceBdd();
            $ruleKey = $request->get('id');

            // Test que l'ordre des étapes
            if (!$this->sessionService->isParamRuleExist($ruleKey)) {
                $this->sessionService->setCreateRuleError($ruleKey, $this->translator->trans('error.rule.order'));

                return $this->redirect($this->generateUrl('regle_stepone_animation'));
                exit;
            }

            // Contrôle si la nouvelle règle peut-être valide
            if ($this->sessionService->isRuleNameLessThanXCharacters($ruleKey, 3)) {
                $this->sessionService->setCreateRuleError($ruleKey, $this->translator->trans('error.rule.valid'));

                return $this->redirect($this->generateUrl('regle_stepone_animation'));
                exit;
            }

            try {
                // ---- Mode update ----
                if (!$this->sessionService->isParamRuleSourceModuleExist($ruleKey) && !$this->sessionService->isParamRuleCibleModuleExist($ruleKey)) {
                    // RELOAD : Chargement des données d'une règle en édition
                    $this->sessionService->setParamRuleSourceModule($ruleKey, $request->request->get('source_module'));
                    $this->sessionService->setParamRuleCibleModule($ruleKey, $request->request->get('cible_module'));
                }
                // ---- Mode update ----

                // Get all data from the target solution first
                $solution_cible = $this->solutionManager->get($this->sessionService->getParamRuleCibleSolution($ruleKey));

                // TARGET ------------------------------------------------------------------
                // We retriev first all data from the target application and the from the source application
                // We can't do both solution in the same time because we could have a bug when these 2 solutions are the same (service are shared by default in Symfony)
                $targetConnection = $solution_cible->login($this->decrypt_params($this->sessionService->getParamRuleCible($ruleKey)));

                if (false == $solution_cible->connexion_valide) {
                    $this->sessionService->setCreateRuleError($ruleKey, $this->translator->trans('error.rule.target_module_connect').' '.(!empty($targetConnection['error']) ? $targetConnection['error'] : 'No message returned by '.$this->sessionService->getParamRuleCibleSolution($ruleKey)));

                    return $this->redirect($this->generateUrl('regle_stepone_animation'));
                    exit;
                }

                if ($request->request->get('cible_module')) {
                    $module['cible'] = $request->request->get('cible_module'); // mode create <<----
                } else {
                    $module['cible'] = $this->sessionService->getParamRuleCibleModule($ruleKey); // mode update <<----
                }

                // Récupère la liste des paramètres cible
                $ruleParamsTarget = $solution_cible->getFieldsParamUpd('target', $module['cible']);

                // Récupère la liste des champs cible
                $ruleFieldsTarget = $solution_cible->get_module_fields($module['cible'], 'target');

                // Récupération de tous les modes de règle possibles pour la cible et la source
                $targetMode = $solution_cible->getRuleMode($module['cible'], 'target');

                $fieldMappingAdd = $solution_cible->getFieldMappingAdd($module['cible']);

                $allowParentRelationship = $solution_cible->allowParentRelationship($this->sessionService->getParamRuleCibleModule($ruleKey));

                // Champs pour éviter les doublons
                $fieldsDuplicateTarget = $solution_cible->getFieldsDuplicate($this->sessionService->getParamRuleCibleModule($ruleKey));

                // SOURCE ------------------------------------------------------------------
                // Connexion au service de la solution source
                $solution_source = $this->solutionManager->get($this->sessionService->getParamRuleSourceSolution($ruleKey));
                $sourceConnection = $solution_source->login($this->decrypt_params($this->sessionService->getParamRuleSource($ruleKey)));

                // Contrôle que la connexion est valide
                if (false == $solution_source->connexion_valide) {
                    $this->sessionService->setCreateRuleError($ruleKey, $this->translator->trans('error.rule.source_module_connect').' '.(!empty($sourceConnection['error']) ? $sourceConnection['error'] : 'No message returned by '.$this->sessionService->getParamRuleSourceSolution($ruleKey)));

                    return $this->redirect($this->generateUrl('regle_stepone_animation'));
                    exit;
                }
                $modules = $solution_source->get_modules('source');
                if ($request->request->get('source_module')) {
                    $module['source'] = $request->request->get('source_module'); // mode create <<----
                } else {
                    $module['source'] = $this->sessionService->getParamRuleSourceModule($ruleKey); // mode update <<----
                }

                // Met en mémoire la façon de traiter la date de référence
                $this->sessionService->setParamRuleSourceDateReference($ruleKey, $solution_source->referenceIsDate($module['source']));

                // Ajoute des champs source pour la validation
                $ruleParamsSource = $solution_source->getFieldsParamUpd('source', $module['source']);

                // Add parameters to be able to read rules linked
                $param['connectorSourceId'] = $this->sessionService->getParamRuleConnectorSourceId($ruleKey);
                $param['connectorTargetId'] = $this->sessionService->getParamRuleConnectorCibleId($ruleKey);
                $param['ruleName'] = $this->sessionService->getParamRuleName($ruleKey);

                // Récupère la liste des champs source
                $ruleFieldsSource = $solution_source->get_module_fields($module['source'], 'source', $param);

                if ($ruleFieldsSource) {
                    $this->sessionService->setParamRuleSourceFields($ruleKey, $ruleFieldsSource);

                    // Erreur champs, pas de données sources (Exemple: GotoWebinar)

                    if ($this->sessionService->isParamRuleSourceFieldsErrorExist($ruleKey) && null != $this->sessionService->getParamRuleSourceFieldsError($ruleKey)) {
                        $this->sessionService->setCreateRuleError($ruleKey, $this->sessionService->getParamRuleSourceFieldsError($ruleKey));

                        return $this->redirect($this->generateUrl('regle_stepone_animation'));
                        exit;
                    }

                    foreach ($ruleFieldsSource as $t => $k) {
                        $source['table'][$module['source']][$t] = $k['label'];
                    }
                    // Tri des champs sans tenir compte de la casse
                    ksort($source['table'][$module['source']], SORT_NATURAL | SORT_FLAG_CASE);
                }

                // SOURCE ----- Récupère la liste des champs source

                // Type de synchronisation
                // Récupération de tous les modes de règle possibles pour la source
                $sourceMode = $solution_source->getRuleMode($module['source'], 'source');
                // Si la target à le type S (search) alors on l'ajoute à la source pour qu'il soit préservé par l'intersection
                if (array_key_exists('S', $targetMode)) {
                    $sourceMode['S'] = 'search_only';
                }
                $intersectMode = array_intersect($targetMode, $sourceMode);
                // Si jamais l'intersection venait à être vide (ce qui ne devrait jamais arriver) on met par défaut le mode CREATE
                if (empty($intersectMode)) {
                    $intersectMode['C'] = 'create_only';
                }
                // If duplicate field exist for the target solution, we allow search rule type
                if (!empty($fieldsDuplicateTarget)) {
                    $intersectMode['S'] = 'search_only';
                }
                $this->sessionService->setParamRuleCibleMode($ruleKey, $intersectMode);

                // Préparation des champs cible
                $cible['table'] = [];

                if ($ruleFieldsTarget) {
                    $this->sessionService->setParamRuleTargetFields($ruleKey, $ruleFieldsTarget);

                    $tmp = $ruleFieldsTarget;

                    $normal = [];
                    $required = [];
                    foreach ($ruleFieldsTarget as $t => $k) {
                        if (isset($k['required']) && true == $k['required']) {
                            $required[] = $t;
                        } else {
                            $normal[] = $t;
                        }
                    }

                    asort($required);
                    asort($normal);

                    $alpha = array_merge($required, $normal);
                    $field_target_alpha = [];
                    foreach ($alpha as $name_fields) {
                        $field_target_alpha[$name_fields] = $tmp[$name_fields]['required'];
                    }

                    $cible['table'][$module['cible']] = $field_target_alpha;
                } else {
                    $cible['table'][$module['cible']] = []; // rev 1.1.1
                }

                // On ajoute des champs personnalisés à notre mapping
                if ($fieldMappingAdd && $this->sessionService->isParamRuleLastVersionIdExist($ruleKey)) {
                    $ruleFields = $this->entityManager
                        ->getRepository(RuleField::class)
                        ->findBy(['rule' => $this->sessionService->getParamRuleLastId($ruleKey)]);

                    $tmp = [];
                    foreach ($ruleFields as $fields) {
                        $tmp[$fields->getTarget()] = 0;
                    }

                    foreach ($cible['table'][$module['cible']] as $k => $value) {
                        $tmp[$k] = $value;
                    }

                    $cible['table'][$module['cible']] = $tmp;

                    ksort($cible['table'][$module['cible']]);
                }

                // -------------------	TARGET
                $lst_relation_target = [];
                $lst_relation_target_alpha = [];
                if ($ruleFieldsTarget) {
                    foreach ($ruleFieldsTarget as $key => $value) {
                        // Only relationship fields
                        if (empty($value['relate'])) {
                            continue;
                        }
                        $lst_relation_target[] = $key;
                    }

                    asort($lst_relation_target);

                    foreach ($lst_relation_target as $name_relate) {
                        $lst_relation_target_alpha[$name_relate]['required'] = (!empty($ruleFieldsTarget[$name_relate]['required_relationship']) ? 1 : 0);
                        $lst_relation_target_alpha[$name_relate]['name'] = $name_relate;
                        $lst_relation_target_alpha[$name_relate]['label'] = (!empty($ruleFieldsTarget[$name_relate]['label']) ? $ruleFieldsTarget[$name_relate]['label'] : $name_relate);
                    }
                }

                // -------------------	SOURCE
                // Liste des relations SOURCE
                $lst_relation_source = [];
                $lst_relation_source_alpha = [];
                $choice_source = [];
                if ($ruleFieldsSource) {
                    foreach ($ruleFieldsSource as $key => $value) {
                        if (empty($value['relate'])) {
                            continue;	// We keep only relationship fields
                        }
                        $lst_relation_source[] = $key;
                    }

                    asort($lst_relation_source);
                    foreach ($lst_relation_source as $name_relate) {
                        $lst_relation_source_alpha[$name_relate]['label'] = $ruleFieldsSource[$name_relate]['label'];
                    }

                    // préparation de la liste en html
                    foreach ($lst_relation_source_alpha as $key => $value) {
                        $choice_source[$key] = (!empty($value['label']) ? $value['label'] : $key);
                    }
                }

                if (!isset($source['table'])) {
                    $source['table'][$this->sessionService->getParamRuleSourceModule($ruleKey)] = [];
                }

                // -- Relation
                // Rule list with the same connectors (both directions) to get the relate ones
                $ruleRepo = $this->entityManager->getRepository(Rule::class);
                $ruleListRelation = $ruleRepo->createQueryBuilder('r')
                    ->select('r.id, r.name, r.moduleSource')
                    ->where('(
												r.connectorSource= ?1 
											AND r.connectorTarget= ?2
											AND r.name != ?3
											AND r.deleted = 0
										)
									OR (
												r.connectorTarget= ?1
											AND r.connectorSource= ?2
											AND r.name != ?3
											AND r.deleted = 0
									)')
                    ->setParameter(1, (int) $this->sessionService->getParamRuleConnectorSourceId($ruleKey))
                    ->setParameter(2, (int) $this->sessionService->getParamRuleConnectorCibleId($ruleKey))
                    ->setParameter(3, $this->sessionService->getParamRuleName($ruleKey))
                    ->getQuery()
                    ->getResult();

                //Verson 1.1.1 : possibilité d'ajouter des relations custom en fonction du module source
                $ruleListRelationSourceCustom = $solution_source->get_rule_custom_relationship($this->sessionService->getParamRuleSourceModule($ruleKey), 'source');
                if (!empty($ruleListRelationSourceCustom)) {
                    $ruleListRelation = array_merge($ruleListRelation, $ruleListRelationSourceCustom);
                }

                $choice = [];
                $control = [];

                foreach ($ruleListRelation as $key => $value) {
                    if (!in_array($value['name'], $control)) {
                        $choice[$value['id']] = $value['name'];
                        $control[] = $value['name'];
                    }
                }
                asort($choice);

                // -------------------	Parent relation
                // Search if we can send document merged with the target solution
                $lstParentFields = [];
                if ($allowParentRelationship) {
                    if (!empty($ruleListRelation)) {
                        // We get all relate fields from every source module
                        foreach ($ruleListRelation as $ruleRelation) {
                            // Get the relate fields from the source module of related rules
                            $ruleFieldsSource = $solution_source->get_module_fields($ruleRelation['moduleSource'], 'source');
                            if (!empty($ruleFieldsSource)) {
                                foreach ($ruleFieldsSource as $key => $sourceRelateField) {
                                    if (empty($sourceRelateField['relate'])) {
                                        continue;	// Only relationship fields
                                    }
                                    $lstParentFields[$key] = $ruleRelation['name'].' - '.$sourceRelateField['label'];
                                }
                            }
                        }
                        // We allow  to search by the id of the module
                        $lstParentFields['Myddleware_element_id'] = $this->translator->trans('create_rule.step3.relation.record_id');
                    }
                    // No parent relation if no rule to link or no fields related
                    if (empty($lstParentFields)) {
                        $allowParentRelationship = false;
                    }
                }

                // On récupére l'EntityManager
                $this->getInstanceBdd();

                // Récupère toutes les catégories
                $lstCategory = $this->entityManager->getRepository(FuncCat::class)
                    ->findAll();

                // Récupère toutes les functions
                $lstFunctions = $this->entityManager->getRepository(Functions::class)
                    ->findAll();

                // Les filtres
                $lst_filter = [
                    $this->translator->trans('filter.content') => 'content',
                    $this->translator->trans('filter.notcontent') => 'notcontent',
                    $this->translator->trans('filter.begin') => 'begin',
                    $this->translator->trans('filter.end') => 'end',
                    $this->translator->trans('filter.gt') => 'gt',
                    $this->translator->trans('filter.lt') => 'lt',
                    $this->translator->trans('filter.equal') => 'equal',
                    $this->translator->trans('filter.different') => 'different',
                    $this->translator->trans('filter.gteq') => 'gteq',
                    $this->translator->trans('filter.lteq') => 'lteq',
                    $this->translator->trans('filter.in') => 'in',
                    $this->translator->trans('filter.notin') => 'notin',
                ];
                

                //Behavior filters
                $lst_errorMissing = [
                    '0' => $this->translator->trans('create_rule.step3.relation.no'),
                    '1' => $this->translator->trans('create_rule.step3.relation.yes'),
                ];

                $lst_errorEmpty = [
                    '0' => $this->translator->trans('create_rule.step3.relation.no'),
                    '1' => $this->translator->trans('create_rule.step3.relation.yes'),
                ];
                // paramètres de la règle
                $rule_params = array_merge($ruleParamsSource, $ruleParamsTarget);

                // récupération des champs de type liste --------------------------------------------------

                // -----[ SOURCE ]-----
                if ($this->sessionService->isParamRuleSourceFieldsExist($ruleKey)) {
                    foreach ($this->sessionService->getParamRuleSourceFields($ruleKey) as $field => $fields_tab) {
                        if (array_key_exists('option', $fields_tab)) {
                            $formule_list['source'][$field] = $fields_tab;
                        }
                    }
                }

                if (isset($formule_list['source']) && count($formule_list['source']) > 0) {
                    foreach ($formule_list['source'] as $field => $fields_tab) {
                        foreach ($fields_tab['option'] as $field_name => $fields) {
                            if (!empty($fields)) {
                                $formule_list['source'][$field]['option'][$field_name] = $field_name.' ( '.$fields.' )';
                            }
                        }
                    }
                }

                $html_list_source = '';
                if (isset($formule_list['source'])) {
                    foreach ($formule_list['source'] as $field => $fields_tab) {
                        $html_list_source .= '<optgroup label="'.$field.'">';
                        $html_list_source .= ToolsManager::composeListHtml($fields_tab['option']);
                        $html_list_source .= '</optgroup>';
                    }
                }


                // -----[ TARGET ]-----
                if ($this->sessionService->isParamRuleTargetFieldsExist($ruleKey)) {
                    foreach ($this->sessionService->getParamRuleTargetFields($ruleKey) as $field => $fields_tab) {
                        if (array_key_exists('option', $fields_tab)) {
                            $formule_list['target'][$field] = $fields_tab;
                        }
                    }
                }

                if (isset($formule_list['target']) && count($formule_list['target']) > 0) {
                    foreach ($formule_list['target'] as $field => $fields_tab) {
                        foreach ($fields_tab['option'] as $field_name => $fields) {
                            if (!empty($fields)) {
                                $formule_list['target'][$field]['option'][$field_name] = $field_name.' ( '.$fields.' )';
                            }
                        }
                    }
                }

                $html_list_target = '';
                if (isset($formule_list['target'])) {
                    foreach ($formule_list['target'] as $field => $fields_tab) {
                        $html_list_target .= '<optgroup label="'.$field.'">';
                        $html_list_target .= ToolsManager::composeListHtml($fields_tab['option']);
                        $html_list_target .= '</optgroup>';
                    }
                }

                // récupération des champs de type liste --------------------------------------------------

                // Type de synchronisation de données rev 1.06 --------------------------
                if ($this->sessionService->isParamRuleCibleModuleExist($ruleKey)) {
                    $mode_translate = [];
                    foreach ($this->sessionService->getParamRuleCibleMode($ruleKey) as $key => $value) {
                        $mode_translate[$key] = $this->translator->trans('create_rule.step3.syncdata.'.$value);
                    }

                    $mode =
                        [
                            [
                                'id' => 'mode',
                                'name' => 'mode',
                                'required' => false,
                                'type' => 'option',
                                'label' => $this->translator->trans('create_rule.step3.syncdata.label'),
                                'option' => $mode_translate,
                            ],
                        ];

                    $rule_params = array_merge($rule_params, $mode);
                }
                // Type de synchronisation de données rev 1.06 --------------------------

                //  rev 1.07 --------------------------
                $bidirectional_params['connector']['source'] = $this->sessionService->getParamRuleConnectorSourceId($ruleKey);
                $bidirectional_params['connector']['cible'] = $this->sessionService->getParamRuleConnectorCibleId($ruleKey);
                $bidirectional_params['module']['source'] = $module['source'];
                $bidirectional_params['module']['cible'] = $module['cible'];

                $bidirectional = RuleManager::getBidirectionalRules($this->connection, $bidirectional_params, $solution_source, $solution_cible);
                if ($bidirectional) {
                    $rule_params = array_merge($rule_params, $bidirectional);
                }

                // Add param to allow deletion (need source and target application ok to enable deletion)
                if (
                    true == $solution_source->getReadDeletion($module['source'])
                    and true == $solution_cible->getSendDeletion($module['cible'])
                ) {
                    $deletion = [
                        [
                            'id' => 'deletion',
                            'name' => 'deletion',
                            'required' => false,
                            'type' => 'option',
                            'label' => $this->translator->trans('create_rule.step3.deletion.label'),
                            'option' => [0 => '', 1 => $this->translator->trans('create_rule.step3.deletion.yes')],
                        ],
                    ];
                    $rule_params = array_merge($rule_params, $deletion);
                } else {
                    // If the deletion is disable (database in source OK but target application non OK), we remove the deletion list field of database connector
                    $keyDeletionField = array_search('deletionField', array_column($rule_params, 'id'));
                    if (!empty($keyDeletionField)) {
                        unset($rule_params[$keyDeletionField]);
                    }
                }

                // get the array of array $ruleFieldsSource and for each value, get the label only and add it to the array $listOfSourceFieldsLabels
                $listOfSourceFieldsLabels = [
                    'Source Fields' => [],
                    'Target Fields' => [],
                    'Relation Fields' => [],
                ];
                foreach ($ruleFieldsSource as $key => $value) {
                    $listOfSourceFieldsLabels['Source Fields'][$key] = $value['label'];
                }

                // get the array of array $ruleFieldsTarget and for each value, get the label only and add it to the array $listOfSourceFieldsLabels
                foreach ($ruleFieldsTarget as $key => $value) {
                    $listOfSourceFieldsLabels['Target Fields'][$key] = $value['label'];
                }

                foreach ($lst_relation_source_alpha as $key => $value) {
                    $listOfSourceFieldsLabels['Relation Fields'][$key] = $value['label'];
                }
                

                $form_all_related_fields = $this->createForm(RelationFilterType::class, null, [
                    'field_choices' => $listOfSourceFieldsLabels,
                    'another_field_choices' => $lst_filter
                ]);
                
                $filters = $this->entityManager->getRepository(RuleFilter::class)
                        ->findBy(['rule' => $ruleKey]);

                // we want to make a request that fetches all the rule names and ids, so we can display them in the form
                $ruleRepo = $this->entityManager->getRepository(Rule::class);
                $ruleListRelation = $ruleRepo->createQueryBuilder('r')
                    ->select('r.id, r.name, r.moduleSource')
                    ->where('(
												r.connectorSource= ?1 
											AND r.connectorTarget= ?2
											AND r.name != ?3
											AND r.deleted = 0
										)
									OR (
												r.connectorTarget= ?1
											AND r.connectorSource= ?2
											AND r.name != ?3
											AND r.deleted = 0
									)')
                    ->setParameter(1, (int) $this->sessionService->getParamRuleConnectorSourceId($ruleKey))
                    ->setParameter(2, (int) $this->sessionService->getParamRuleConnectorCibleId($ruleKey))
                    ->setParameter(3, $this->sessionService->getParamRuleName($ruleKey))
                    ->getQuery()
                    ->getResult();

                // from the result ruleListRelation we create an array with the rule name as the key and the rule id as the value
                $ruleListRelation = array_reduce($ruleListRelation, function ($carry, $item) {
                    $carry[$item['name']] = $item['id'];
                    return $carry;
                }, []);

                $html_list_rules = '';
                if (!empty($ruleListRelation)) {
                    foreach ($ruleListRelation as $ruleName => $ruleId) {
                        $html_list_rules .= '<option value="'.$ruleId.'">'.$ruleName.'</option>';
                    }
                }

                // get the full rule object
                $rule = $this->entityManager->getRepository(Rule::class)->find($ruleKey);

                //  rev 1.07 --------------------------
                $result = [
                    'rule' => $rule,
                    'filters' => $filters,
                    'source' => $source['table'],
                    'cible' => $cible['table'],
                    'rule_params' => $rule_params,
                    'lst_relation_target' => $lst_relation_target_alpha,
                    'lst_relation_source' => $choice_source,
                    'lst_rule' => $choice,
                    'lst_category' => $lstCategory,
                    'lst_functions' => $lstFunctions,
                    'lst_filter' => $lst_filter,
                    'form_all_related_fields' => $form_all_related_fields->createView(),
                    'lst_errorMissing' => $lst_errorMissing,
                    'lst_errorEmpty' => $lst_errorEmpty,
                    'params' => $this->sessionService->getParamRule($ruleKey),
                    'duplicate_target' => $fieldsDuplicateTarget,
                    'opt_target' => $html_list_target,
                    'opt_source' => $html_list_source,
                    'html_list_rules' => $html_list_rules,
                    'fieldMappingAddListType' => $fieldMappingAdd,
                    'parentRelationships' => $allowParentRelationship,
                    'lst_parent_fields' => $lstParentFields,
                    'regleId' => $ruleKey,
                    'simulationQueryField' => $this->simulationQueryField,
                ];

                foreach ($result['source'] as $module => $fields) {
                    foreach ($fields as $fieldNameEncoded => $fieldValue) {
                        // Decode the field name
                        $fieldNameDecoded = urldecode($fieldNameEncoded);

                        // Optionally, clean up the field name by removing or replacing unwanted characters
                        $fieldNameCleaned = $fieldNameDecoded; // Adjust as needed

                        // Clean the field value
                        // Example: Trim whitespace and remove special characters
                        // Adjust the cleaning logic as per your requirements
                        $fieldValueCleaned = trim($fieldValue); // Trimming whitespace
                        // For more aggressive cleaning, uncomment and adjust the following line
                        // $fieldValueCleaned = preg_replace('/[^\x20-\x7E]/', '', $fieldValueCleaned);

                        // Check if any cleaning was necessary for the field name
                        if ($fieldNameCleaned !== $fieldNameEncoded || $fieldValue !== $fieldValueCleaned) {
                            // Remove the old key
                            unset($result['source'][$module][$fieldNameEncoded]);

                            // Add the cleaned field name with its cleaned value
                            $result['source'][$module][$fieldNameCleaned] = $fieldValueCleaned;
                        }
                    }
                }

                $result = $this->tools->beforeRuleEditViewRender($result);

                // Formatage des listes déroulantes :
                $result['lst_relation_source'] = ToolsManager::composeListHtml($result['lst_relation_source'], $this->translator->trans('create_rule.step3.relation.fields'));
                $result['lst_parent_fields'] = ToolsManager::composeListHtml($result['lst_parent_fields'], ' ');
                $result['lst_rule'] = ToolsManager::composeListHtml($result['lst_rule'], $this->translator->trans('create_rule.step3.relation.fields'));
                $result['lst_filter'] = ToolsManager::composeListHtml($result['lst_filter'], $this->translator->trans('create_rule.step3.relation.fields'));
                $result['lst_errorMissing'] = ToolsManager::composeListHtml($result['lst_errorMissing'], '', '1');
                $result['lst_errorEmpty'] = ToolsManager::composeListHtml($result['lst_errorEmpty'], '', '0');

                // Modify this section where $html_list_source is built
                $source_groups = [];
                $source_values = [];
                if (isset($formule_list['source'])) {
                    foreach ($formule_list['source'] as $field => $fields_tab) {
                        // Store group names
                        $source_groups[$field] = $field;
                        
                        // Store values for each group
                        $source_values[$field] = [];
                        foreach ($fields_tab['option'] as $value => $label) {
                            $source_values[$field][$value] = $label;
                        }
                    }
                }

                // Pass these to the template instead of $html_list_source
                $result['source_groups'] = $source_groups;
                $result['source_values'] = $source_values;
 
                // Do the same for target
                $target_groups = [];
                $target_values = [];
                if (isset($formule_list['target'])) {
                    foreach ($formule_list['target'] as $field => $fields_tab) {
                        // Store group names
                        $target_groups[$field] = $field;
                        
                        // Store values for each group
                        $target_values[$field] = [];
                        foreach ($fields_tab['option'] as $value => $label) {
                            $target_values[$field][$value] = $label;
                        }
                    }
                }
 
                // Pass target data to template
                $result['target_groups'] = $target_groups;
                $result['target_values'] = $target_values;

                return $this->render('Rule/create/step3.html.twig', $result);

                // ----------------
            } catch (Exception $e) {
                $this->logger->error($e->getMessage().' ('.$e->getFile().' line '.$e->getLine());
                $this->sessionService->setCreateRuleError($ruleKey, $this->translator->trans('error.rule.mapping').' : '.$e->getMessage().' ('.$e->getFile().' line '.$e->getLine().')');
                return $this->redirect($this->generateUrl('regle_stepone_animation'));
            }
        }

        /**
         * Indique des informations concernant le champ envoyé en paramètre.
         * @Route("/info/{type}/{field}/", name="path_info_field", methods={"GET"})
         * @Route("/info", name="path_info_field_not_param")
         */
        public function infoField(Request $request, $field, $type): Response
        {
            $session = $request->getSession();
            $myddlewareSession = $session->get('myddlewareSession');
            // We always add data again in session because these data are removed after the call of the get
            $session->set('myddlewareSession', $myddlewareSession);
            if (isset($field) && !empty($field) && isset($myddlewareSession['param']['rule']) && 'my_value' != $field) {
                if (isset($myddlewareSession['param']['rule'][0][$type]['fields'][$field])) {
                    return $this->render('Rule/create/onglets/info.html.twig', [
                        'field' => $myddlewareSession['param']['rule'][0][$type]['fields'][$field],
                        'name' => htmlentities(trim($field)),
                    ]
                    );
                // SuiteCRM connector uses this structure instead
                } elseif (isset($myddlewareSession['param']['rule']['key'])) {
                    $ruleKey = $myddlewareSession['param']['rule']['key'];

                    return $this->render('Rule/create/onglets/info.html.twig', [
                        'field' => $myddlewareSession['param']['rule'][$ruleKey][$type]['fields'][$field],
                        'name' => htmlentities(trim($field)),
                    ]
                    );
                } else {
                    // Possibilité de Mutlimodules
                    foreach ($myddlewareSession['param']['rule'][0][$type]['fields'] as $subModule) { // Ce foreach fonctionnera toujours
                        if (isset($subModule[$field])) { // On teste si ça existe pour éviter une erreur PHP éventuelle
                            return $this->render('Rule/create/onglets/info.html.twig', [
                                'field' => $subModule[$field],
                                'name' => htmlentities(trim($field)),
                            ]
                            );
                        }
                    }
                }
                // On retourne vide si on l'a pas trouvé précédemment
                return $this->render('Rule/create/onglets/info.html.twig', [
                    'field' => '',
                ]
                );
            }

            return $this->render('Rule/create/onglets/info.html.twig', [
                'field' => '',
            ]
            );
        }

        /**
         * CREATION - STEP THREE - VERIF DES FORMULES.
         *
         * @Route("/create/step3/formula/", name="regle_formula", methods={"POST"})
         */
        public function ruleVerifFormula(Request $request): JsonResponse
        {
            if ('POST' == $request->getMethod()) {
                // Mise en place des variables
                $this->formuleManager->init($request->request->get('formula')); // mise en place de la règle dans la classe
                $this->formuleManager->generateFormule(); // Genère la nouvelle formule à la forme PhP

                return new JsonResponse($this->formuleManager->parse['error']);
            }
            throw $this->createNotFoundException('Error');
        }

        /**
         * CREATION - STEP THREE - Validation du formulaire.
         *
         * @Route("/create/step3/validation/", name="regle_validation", methods={"POST"})
         */
        public function ruleValidation(Request $request): JsonResponse
        {
            // On récupére l'EntityManager
            $this->getInstanceBdd();
            $this->entityManager->getConnection()->beginTransaction();
            try {
                // Decode the JSON params from the request
                $paramsRaw = $request->request->get('params');
                $params = json_decode($paramsRaw, true); // true = associative array

                // Get rule id from params
                if (!empty($params)) {
                    foreach ($params as $searchRuleId) {
                        if ('regleId' === $searchRuleId['name']) {
                            $ruleKey = $searchRuleId['value'];
                            break;
                        }
                    }
                }

                // retourne un tableau prêt à l'emploi
                $tab_new_rule = $this->createListeParamsRule(
                    $request->request->get('champs'), // Fields
                    $request->request->get('formules'), // Formula
                    $params // Decoded params
                );
                unset($tab_new_rule['params']['regleId']); // delete id regle for gestion session

                // fields relate
                if (!empty($request->request->get('duplicate'))) {
                    // fix : Put the duplicate fields values in the old $tab_new_rule array
                    $duplicateArray = implode(';', $request->request->get('duplicate'));
                    $tab_new_rule['params']['rule']['duplicate_fields'] = $duplicateArray;
                    $this->sessionService->setParamParentRule($ruleKey, 'duplicate_fields', $duplicateArray);
                }
                // si le nom de la règle est inferieur à 3 caractères :
                if (strlen($this->sessionService->getParamRuleName($ruleKey)) < 3 || false == $this->sessionService->getParamRuleNameValid($ruleKey)) {
                    return new JsonResponse(0);
                }

                //------------ Create rule
                $connector_source = $this->entityManager
                    ->getRepository(Connector::class)
                    ->find($this->sessionService->getParamRuleConnectorSourceId($ruleKey));

                $connector_target = $this->entityManager
                    ->getRepository(Connector::class)
                    ->find($this->sessionService->getParamRuleConnectorCibleId($ruleKey));

                $param = RuleManager::getFieldsParamDefault();

                // Get the id of the rule if we edit a rule
                // Generate Rule object (create a new one or instanciate the existing one
                if (!$this->sessionService->isParamRuleLastVersionIdEmpty($ruleKey)) {
                    $oneRule = $this->entityManager->getRepository(Rule::class)->find($this->sessionService->getParamRuleLastId($ruleKey));
                    $oneRule->setDateModified(new \DateTime());
                    $oneRule->setModifiedBy($this->getUser());
                } else {
                    $oneRule = new Rule();
                    $oneRule->setConnectorSource($connector_source);
                    $oneRule->setConnectorTarget($connector_target);
                    $oneRule->setDateCreated(new \DateTime());
                    $oneRule->setDateModified(new \DateTime());
                    $oneRule->setCreatedBy($this->getUser());
                    $oneRule->setModifiedBy($this->getUser());
                    $oneRule->setModuleSource($this->sessionService->getParamRuleSourceModule($ruleKey));
                    $oneRule->setModuleTarget($this->sessionService->getParamRuleCibleModule($ruleKey));
                    $oneRule->setDeleted(0);
                    $oneRule->setActive((int) $param['active']);
                    $oneRule->setName($this->sessionService->getParamRuleName($ruleKey));
                }
                $this->entityManager->persist($oneRule);
                // On fait le flush pour obtenir le nameSlug. En cas de problème on fait un remove dans le catch
                $this->entityManager->flush();
                $this->sessionService->setRuleId($ruleKey, $oneRule->getId());
                $nameRule = $oneRule->getNameSlug();

                // BEFORE SAVE rev 1.08 ----------------------
                $relationshipsBeforeSave = $request->request->get('relations');
                $before_save = $this->ruleManager->beforeSave($this->solutionManager,
                    ['ruleName' => $nameRule,
                        'RuleId' => $oneRule->getId(),
                        'connector' => $this->sessionService->getParamParentRule($ruleKey, 'connector'),
                        'content' => $tab_new_rule,
                        'relationships' => $relationshipsBeforeSave,
                        'module' => [
                            'source' => [
                                'solution' => $this->sessionService->getParamRuleSourceSolution($ruleKey),
                                'name' => $this->sessionService->getParamRuleSourceModule($ruleKey),
                            ],
                            'target' => [
                                'solution' => $this->sessionService->getParamRuleCibleSolution($ruleKey),
                                'name' => $this->sessionService->getParamRuleCibleModule($ruleKey),
                            ],
                        ],
                    ]
                );
                if (!$before_save['done']) {
                    throw new Exception($before_save['message']);
                }
                // Si le retour du beforeSave contient des paramètres alors on les ajoute à la règle avant sauvegarde
                if (!empty($before_save['params'])) {
                    if (empty($tab_new_rule['params'])) {
                        $tab_new_rule['params'] = $before_save['params'];
                    } else {
                        $tab_new_rule['params'] = array_merge($tab_new_rule['params'], $before_save['params']);
                    }
                }

                // Check if search rule then duplicate field shouldn't be empty
                if (
                    'S' == $tab_new_rule['params']['mode']
                    and empty($tab_new_rule['params']['rule']['duplicate_fields'])
                ) {
                    throw new Exception($this->translator->trans('Failed to save the rule. If you choose to retrieve data with your rule, you have to select at least one duplicate field.'));
                }

                // Edit mode
                if (!$this->sessionService->isParamRuleLastVersionIdEmpty($ruleKey)) {
                    foreach ($oneRule->getFields() as $ruleField) {
                        $this->entityManager->remove($ruleField);
                        $this->entityManager->flush();
                    }

                    foreach ($oneRule->getRelationsShip() as $ruleRelationShip) {
                        $this->entityManager->remove($ruleRelationShip);
                        $this->entityManager->flush();
                    }

                    foreach ($oneRule->getFilters() as $ruleFilter) {
                        $this->entityManager->remove($ruleFilter);
                        $this->entityManager->flush();
                    }

                    // Rule Params
                    foreach ($oneRule->getParams() as $ruleParam) {
                        // Save reference date
                        if ('datereference' == $ruleParam->getName()) {
                            $date_reference = $ruleParam->getValue();
                        }
                        if ('limit' === $ruleParam->getName()) {
                            $limit = $ruleParam->getValue();
                        }
                        if (in_array($ruleParam->getName(), $this->tools->getRuleParam())) {
                            $this->entityManager->remove($ruleParam);
                            $this->entityManager->flush();
                        }
                    }
                } // Create mode
                else {
                    if ($this->sessionService->isParamRuleSourceDateReference($ruleKey) && $this->sessionService->getParamRuleSourceDateReference($ruleKey)) {
                        $date_reference = date('Y-m-d 00:00:00');
                    } else {
                        $date_reference = '';
                    }
                }

                //------------------------------- Create rule params -------------------
                if (isset($tab_new_rule['params']) || isset($param['RuleParam'])) {
                    if (!isset($tab_new_rule['params'])) {
                        $p = $param['RuleParam'];
                    } else {
                        $p = array_merge($param['RuleParam'], $tab_new_rule['params']);
                    }

                    $bidirectional = '';
                    foreach ($p as $key => $value) {
                        // Value could be empty, for bidirectional parameter for example (we don't test empty because mode could be equal 0)
                        if ('' == $value) {
                            continue;
                        }
                        $oneRuleParam = new RuleParam();
                        $oneRuleParam->setRule($oneRule);

                        // si tableau de doublon
                        if ('rule' == $key) {
                            $oneRuleParam->setName('duplicate_fields');
                            $oneRuleParam->setValue($value['duplicate_fields']);
                        } else {
                            $oneRuleParam->setName($key);
                            if ('datereference' == $key) {
                                // date de référence change en fonction create ou update
                                $oneRuleParam->setValue($date_reference);
                            // Limit change according to create or update
                            } elseif ('limit' == $key) {
                                // Set default value 100 for limit
                                if (empty($limit)) {
                                    $limit = 100;
                                }
                                $oneRuleParam->setValue($limit);
                            } else {
                                $oneRuleParam->setValue($value);
                            }
                        }
                        // Save the parameter
                        if ('bidirectional' == $key) {
                            $bidirectional = $value;
                        }
                        $this->entityManager->persist($oneRuleParam);
                        $this->entityManager->flush();
                    }

                    // If a bidirectional parameter exist, we check if the opposite one exists too
                    if (!empty($bidirectional)) {
                        // Update the opposite rule if birectional rule
                        $ruleParamBidirectionalOpposite = $this->entityManager->getRepository(RuleParam::class)
                            ->findOneBy([
                                'rule' => $bidirectional,
                                'name' => 'bidirectional',
                                'value' => $oneRule->getId(),
                            ]);
                        $bidirectionalRule = $this->ruleRepository->find($bidirectional);
                        // If the bidirectional parameter doesn't exist on the opposite rule we create it
                        if (empty($ruleParamBidirectionalOpposite)) {
                            $ruleParamBidirectionalOpposite = new RuleParam();
                            $ruleParamBidirectionalOpposite->setRule($bidirectionalRule);
                            $ruleParamBidirectionalOpposite->setName('bidirectional');
                            $ruleParamBidirectionalOpposite->setValue($oneRule->getId());
                            $this->entityManager->persist($ruleParamBidirectionalOpposite);
                        }
                    } else {
                        // If no bidirectional parameter on the rule and if the bidirectional parametr exist on an opposite rule, we delete it
                        $ruleParamBidirectionalDelete = $this->entityManager->getRepository(RuleParam::class)
                            ->findOneBy([
                                'value' => $oneRule->getId(),
                                'name' => 'bidirectional',
                            ]);
                        if (!empty($ruleParamBidirectionalDelete)) {
                            $this->entityManager->remove($ruleParamBidirectionalDelete);
                            $this->entityManager->flush();
                        }
                    }
                }

                //------------------------------- Create rule fields -------------------
                $debug = [];

                if (isset($tab_new_rule['fields'])) {
                    foreach ($tab_new_rule['fields']['name'] as $field_target => $c) {
                        $field_source = '';
                        if (isset($c['champs'])) {
                            foreach ($c['champs'] as $name) {
                                $field_source .= $name.';';
                            }
                            $field_source = trim($field_source, ';');
                        }

                        // Formula
                        $formule = '';
                        if (isset($c['formule'])) {
                            foreach ($c['formule'] as $name) {
                                $formule .= $name.' ';
                                $debug[] = $name.' ';
                            }
                        }

                        // Insert
                        $oneRuleField = new RuleField();
                        $oneRuleField->setRule($oneRule);
                        $oneRuleField->setTarget(trim($field_target));
                        $oneRuleField->setSource(((!empty($field_source)) ? $field_source : 'my_value'));
                        $oneRuleField->setFormula(((!empty($formule)) ? trim($formule) : null));
                        $this->entityManager->persist($oneRuleField);
                        $this->entityManager->flush();
                    }
                }

                //------------------------------- RELATIONSHIPS -------------------
                $tabRelationShips = [];
                if (!is_null($request->request->get('relations'))) {
                    foreach ($request->request->get('relations') as $rel) {
                        if (
                            !empty($rel['rule'])
                            && !empty($rel['source'])
                        ) {
                            // Creation dans la table RelationShips
                            $oneRuleRelationShip = new RuleRelationShip();
                            $oneRuleRelationShip->setRule($oneRule);
                            $oneRuleRelationShip->setFieldNameSource($rel['source']);
                            $oneRuleRelationShip->setFieldNameTarget($rel['target']);
                            // No error empty or missing for parent relationship, we set default values
                            if (!empty($rel['parent'])) {
                                $rel['errorEmpty'] = '0';
                                $rel['errorMissing'] = '1';
                            }
                            $oneRuleRelationShip->setErrorEmpty($rel['errorEmpty']);
                            $oneRuleRelationShip->setErrorMissing($rel['errorMissing']);
                            $oneRuleRelationShip->setFieldId($rel['rule']);
                            $oneRuleRelationShip->setParent($rel['parent']);
                            $oneRuleRelationShip->setDeleted(0);
                            // We don't create the field target if the relatiobnship is a parent one
                            // We only use this field to search in the source application, not to send the data to the target application.
                            if (empty($rel['parent'])) {
                                $tabRelationShips['target'][] = $rel['target'];
                            }
                            $tabRelationShips['source'][] = $rel['source'];
                            $this->entityManager->persist($oneRuleRelationShip);
                            $this->entityManager->flush();
                        }
                    }
                }

                //------------------------------- RuleFilter ------------------------
                // Get all request data and extract the filter
                $requestData = $request->request->all();
                $filtersRaw = $requestData['filter'] ?? null;

                // Handle both JSON string and array cases
                $filters = is_string($filtersRaw) ? json_decode($filtersRaw, true) : $filtersRaw;

                if (!empty($filters)) {
                    foreach ($filters as $filterData) {
                        $oneRuleFilter = new RuleFilter();
                        $oneRuleFilter->setTarget($filterData['target']);
                        $oneRuleFilter->setRule($oneRule);
                        $oneRuleFilter->setType($filterData['filter']);
                        $oneRuleFilter->setValue($filterData['value']);
                        $this->entityManager->persist($oneRuleFilter);
                    }
                    $this->entityManager->flush();
                }

                // --------------------------------------------------------------------------------------------------
                // Order all rules
                $this->jobManager->orderRules();

                // --------------------------------------------------------------------------------------------------
                // Create rule history in order to follow all modifications
                // Encode every rule parameters
                $ruledata = json_encode(
                    [
                        'ruleName' => $nameRule,
                        'limit' => $limit,
                        'datereference' => $date_reference,
                        'content' => $tab_new_rule,
                        'filters' => $filters,
                        'relationships' => $relationshipsBeforeSave,
                    ]
                );
                // Save the rule audit
                $oneRuleAudit = new RuleAudit();
                $oneRuleAudit->setRule($oneRule);
                $oneRuleAudit->setDateCreated(new \DateTime());
                $oneRuleAudit->setData($ruledata);
                $oneRuleAudit->setCreatedBy($this->getUser());
                $this->entityManager->persist($oneRuleAudit);
                $this->entityManager->flush();

                // notification
                $solution_source = $this->solutionManager->get($this->sessionService->getParamRuleSourceSolution($ruleKey));
                $solution_source->setMessageCreateRule($this->sessionService->getParamRuleSourceModule($ruleKey));

                $solution_target = $this->solutionManager->get($this->sessionService->getParamRuleCibleSolution($ruleKey));
                $solution_target->setMessageCreateRule($this->sessionService->getParamRuleCibleModule($ruleKey));
                // notification

                // --------------------------------------------------------------------------------------------------

                // Détection règle root ou child rev 1.08 ----------------------
                // On réactualise les paramètres
                $tab_new_rule['content']['params'] = $p;
                $this->ruleManager->afterSave($this->solutionManager,
                                                [
                                                    'ruleId' => $oneRule->getId(),
                                                    'ruleName' => $nameRule,
                                                    'oldRule' => ($this->sessionService->isParamRuleLastVersionIdEmpty($ruleKey)) ? '' : $this->sessionService->getParamRuleLastId($ruleKey),
                                                    'datereference' => $date_reference,
                                                    'limit' => $limit,
                                                    'connector' => $this->sessionService->getParamParentRule($ruleKey, 'connector'),
                                                    'content' => $tab_new_rule,
                                                    'relationships' => $relationshipsBeforeSave,
                                                    'module' => [
                                                        'source' => [
                                                            'solution' => $this->sessionService->getParamRuleSourceSolution($ruleKey),
                                                            'name' => $this->sessionService->getParamRuleSourceModule($ruleKey),
                                                        ],
                                                        'target' => [
                                                            'solution' => $this->sessionService->getParamRuleCibleSolution($ruleKey),
                                                            'name' => $this->sessionService->getParamRuleCibleModule($ruleKey),
                                                        ],
                                                    ],
                                                ],
                                                $this->requestStack
                );
                if ($this->sessionService->isParamRuleExist($ruleKey)) {
                    $this->sessionService->removeParamRule($ruleKey);
                }
                $this->entityManager->getConnection()->commit();
                
                $rule_id = $oneRule->getId();
                $response = ['status' => 1, 'id' => $rule_id];

            } catch (Exception $e) {
                $this->entityManager->getConnection()->rollBack();
                $this->logger->error('2;'.htmlentities($e->getMessage().' ('.$e->getFile().' line '.$e->getLine().')'));
                $response = '2;'.htmlentities($e->getMessage().' ('.$e->getFile().' line '.$e->getLine().')');
            }

            $this->entityManager->close();
            return new JsonResponse($response);
        }

        /**
         * TABLEAU DE BORD.
         */
        #[Route('/panel', name: 'regle_panel')]
        public function panel(Request $request): Response
        {

            $session = $request->getSession();

            // Check if the user has completed 2FA
            $user = $this->getUser();
            $twoFactorAuth = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);
            
            $this->logger->debug('User authenticated, checking 2FA status in panel method');
            if ($twoFactorAuth->isEnabled() && !$session->get('two_factor_auth_complete', false)) {
                $this->logger->debug('2FA is enabled for user and not completed');
                
                // Check if the user has a remember cookie
                $rememberedAuth = $this->twoFactorAuthService->checkRememberCookie($request);
                if ($rememberedAuth && $rememberedAuth->getUser()->getId() === $user->getId()) {
                    // If the user has a valid remember cookie, mark as complete
                    $session->set('two_factor_auth_complete', true);
                    $this->logger->debug('User has valid remember cookie, marking 2FA as complete');
                } else {
                    // Otherwise, redirect to verification
                    $this->logger->debug('Redirecting to verification page');
                    return $this->redirectToRoute('two_factor_auth_verify');
                }
            }

            $language = $request->getLocale();

            $this->getInstanceBdd();
            $solution = $this->entityManager->getRepository(Solution::class)
                ->solutionActive();
            $lstArray = [];
            if ($solution) {
                foreach ($solution as $s) {
                    $lstArray[] = $s->getName();
                }
            }

            /** @var User $user */
            $user = $this->getUser();

            $countNbDocuments = $this->documentRepository->countNbDocuments();

            return $this->render('Home/index.html.twig', [
                'errorByRule' => $this->ruleRepository->errorByRule($user),
                'solutions' => $lstArray,
                'locale' => $language,
                'countNbDocuments' => $countNbDocuments,
            ]
            );
        }

        /**
         * ANIMATION
         * No more submodule in Myddleware. We return a response 0 for the js (animation.js.
         *
         * @Route("/submodules", name="regle_submodules", methods={"POST"})
         */
        public function listSubModulesAction(): Response
        {
            return new Response(0);
        }

        /**
         * VALIDATION DE L'ANIMATION.
         *
         * @Route("/validation", name="regle_validation_animation")
         */
        public function validationAnimationAction(Request $request): Response
        {
            $key = $this->sessionService->getParamRuleLastKey();

            try {
                $choiceSelect = $request->get('choice_select', null);
                if (null != $choiceSelect) {
                    if ('module' == $choiceSelect) {
                        // si le nom de la règle est inferieur à 3 caractères :
                        if (empty($this->sessionService->getParamRuleSourceSolution($key)) || strlen($this->sessionService->getParamRuleName($key)) < 3) {
                            $this->sessionService->setParamRuleNameValid($key, false);
                        } else {
                            $this->sessionService->setParamRuleNameValid($key, true);
                        }
                        $this->sessionService->setParamRuleSourceModule($key, $request->get('module_source'));
                        $this->sessionService->setParamRuleCibleModule($key, $request->get('module_target'));

                        return new Response('module');
                    } elseif ('template' == $choiceSelect) {
                        // Rule creation with the template selected in parameter
                        $ruleName = $request->get('name');
                        $templateName = $request->get('template');

                        $connectorSourceId = (int) $this->sessionService->getParamRuleConnectorSourceId($key);
                        $connectorTargetId = (int) $this->sessionService->getParamRuleConnectorCibleId($key);
                        /** @var User $user */
                        $user = $this->getUser();
                        try {
                            $this->template->convertTemplate($ruleName, $templateName, $connectorSourceId, $connectorTargetId, $user);
                        } catch (Exception $e) {
                            $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());

                            return new Response('error');
                        }
                        // Sort the rules
                        $this->jobManager->orderRules();
                        // We return to the list of rule even in case of error (session messages will be displyed in the UI)/: See animation.js function animConfirm
                        return new Response('template');
                    }

                    return new Response(0);
                }

                return new Response(0);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());

                return new Response($e->getMessage());
            }
        }

        /**
         * LISTE DES TEMPLATES.
         *
         * @Route("/list/template", name="regle_template")
         */
        public function listTemplateAction(): Response
        {
            $key = $this->sessionService->getParamRuleLastKey();
            $solutionSourceName = $this->sessionService->getParamRuleSourceSolution($key);
            $solutionTargetName = $this->sessionService->getParamRuleCibleSolution($key);
            $templates = $this->template->getTemplates($solutionSourceName, $solutionTargetName);
            if (!empty($templates)) {
                $rows = '';
                foreach ($templates as $t) {
                    $rows .= '<tr>
                            <td>
                                <span data-id="'.$t['name'].'">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-card-list" viewBox="0 0 16 16">
                                    <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z"/>
                                    <path d="M5 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 5 8zm0-2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0 5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-1-5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zM4 8a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm0 2.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/>
                                    </svg>
                                </span>
                            </td>
                            <td>'.$t['name'].'</td>
                            <td>'.$t['description'].'</td>
                        </tr>';
                }

                return new Response('<table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>'.$this->translator->trans('animate.choice.name').'</th>
                        <th>'.$this->translator->trans('animate.choice.description').'</th>
                    </tr>
                </thead>
                <tbody>
				'.$rows.'
                </tbody>
            </table>');
        }
    }

        /**
         * CREATION - STEP ONE - ANIMATION.
         *
         * @Route("/create", name="regle_stepone_animation")
         */
        public function ruleStepOneAnimation(): Response
        {
            if ($this->sessionService->isConnectorExist()) {
                $this->sessionService->removeMyddlewareConnector();
            }

            // New Rule
            $this->sessionService->setParamRuleLastKey(0);

            $key = $this->sessionService->getParamRuleLastKey();

            // Détecte s'il existe des erreurs
            if ($this->sessionService->isErrorNotEmpty($key, SessionService::ERROR_CREATE_RULE_INDEX)) {
                $error = $this->sessionService->getCreateRuleError($key);
                $this->sessionService->removeError($key, SessionService::ERROR_CREATE_RULE_INDEX);
            } else {
                $error = false;
            }

            // Liste source : solution avec au moins 1 connecteur
            $this->getInstanceBdd();

            $solutionSource = $this->entityManager->getRepository(Solution::class)
                ->solutionConnector('source', $this->getUser()->isAdmin(), $this->getUser()->getId());

            if (!empty($solutionSource)) {
                foreach ($solutionSource as $s) {
                    $source[] = $s->getName();
                }
                $this->sessionService->setParamConnectorSolutionSource($key, $source);
            }

            // Liste target : solution avec au moins 1 connecteur
            $solutionTarget = $this->entityManager->getRepository(Solution::class)
                ->solutionConnector('target', $this->getUser()->isAdmin(), $this->getUser()->getId());

            if (!empty($solutionTarget)) {
                foreach ($solutionTarget as $t) {
                    $target[] = $t->getName();
                }
                $this->sessionService->setParamConnectorSolutionTarget($key, $target);
            }

            return $this->render('Rule/create/step1simply.html.twig', [
                'source' => $solutionSource,
                'target' => $solutionTarget,
                'error' => $error,
            ]
            );
        }

        /**
         * LISTE DES MODULES POUR ANIMATION.
         * @throws Exception
         * @Route("/list/module", name="regle_list_module")
         */
        public function ruleListModule(Request $request): Response
        {
            try {
                $id_connector = $request->get('id');
                $type = $request->get('type');
                $key = $this->sessionService->getParamRuleLastKey(); // It's a new rule, last key = 0

                // Control the request
                if (!in_array($type, ['source', 'cible']) || !is_numeric($id_connector)) {
                    throw $this->createAccessDeniedException();
                }
                $id_connector = (int) $id_connector;

                $this->getInstanceBdd();
                $connector = $this->entityManager->getRepository(Connector::class)
                    ->find($id_connector); // infos connector

                $connectorParams = $this->entityManager->getRepository(ConnectorParam::class)
                    ->findBy(['connector' => $id_connector]);    // infos params connector

                foreach ($connectorParams as $p) {
                    $this->sessionService->setParamRuleParentName($key, $type, $p->getName(), $p->getValue()); // params connector
                }
                $this->sessionService->setParamRuleConnectorParent($key, $type, $id_connector); // id connector
                $this->sessionService->setParamRuleParentName($key, $type, 'solution', $connector->getSolution()->getName()); // nom de la solution

                $solution = $this->solutionManager->get($this->sessionService->getParamRuleParentName($key, $type, 'solution'));

                $params_connexion = $this->decrypt_params($this->sessionService->getParamParentRule($key, $type));
                $params_connexion['idConnector'] = $id_connector;

                $solution->login($params_connexion);

                $t = (('source' == $type) ? 'source' : 'target');

                $liste_modules = ToolsManager::composeListHtml($solution->get_modules($t), $this->translator->trans('create_rule.step1.choose_module'));

                return new Response($liste_modules);
            } catch (Exception $e) {
                $error = $e->getMessage().' '.$e->getLine().' '.$e->getFile();
                $this->logger->error($error);
                return new Response('<option value="">Aucun module pour ce connecteur</option>');
            }
        }

        /* ******************************************************
         * METHODES PRATIQUES
         ****************************************************** */

        // CREATION REGLE - STEP ONE : Liste des connecteurs pour un user
        private function connectorsList($type): string
        {
            $this->getInstanceBdd();
            $solutionRepo = $this->entityManager->getRepository(Connector::class);
            $solution = $solutionRepo->findAllConnectorByUser($this->getUser()->getId(), $type); // type = source ou target
            $lstArray = [];
            if ($solution) {
                foreach ($solution as $s) {
                    $lstArray[$s['name'].'_'.$s['id_connector']] = ucfirst($s['label']);
                }
            }

            return ToolsManager::composeListHtml($lstArray, $this->translator->trans('create_rule.step1.list_empty'));
        }

        // CREATION REGLE - STEP THREE - Retourne les paramètres dans un bon format de tableau
        private function createListeParamsRule($fields, $formula, $params): array
        {
            $phrase_placeholder = $this->translator->trans('rule.step3.placeholder');
            $tab = [];

            // FIELDS ------------------------------------------
            if ($fields) {
                $champs = explode(';', $fields);
                foreach ($champs as $champ) {
                    $chp = explode('[=]', $champ);

                    if ($chp[0]) {
                        if ($phrase_placeholder != $chp[1] && 'my_value' != $chp[1]) {
                            $tab['fields']['name'][$chp[0]]['champs'][] = $chp[1];
                        }
                    }
                }
            }

            // FORMULA -----------------------------------------
            if ($formula) {
                $formules = explode(';', $formula);

                foreach ($formules as $formule) {
                    $chp = explode('[=]', $formule);
                    if ($chp[0]) {
                        if (!empty($chp[1])) {
                            $tab['fields']['name'][$chp[0]]['formule'][] = $chp[1];
                        }
                    }
                }
            }

            // PARAMS -----------------------------------------
            if ($params) {
                foreach ($params as $k => $p) {
                    $tab['params'][$p['name']] = $p['value'];
                }
            }

            return $tab;
        }

        // Crée la pagination avec le Bundle Pagerfanta en fonction d'une requete
        private function nav_pagination($params, $orm = true)
        {
            /*
             * adapter_em_repository = requete
             * maxPerPage = integer
             * page = page en cours
             */

            if (is_array($params)) {
                /* DOC :
                 * $pager->setCurrentPage($page);
                    $pager->getNbResults();
                    $pager->getMaxPerPage();
                    $pager->getNbPages();
                    $pager->haveToPaginate();
                    $pager->hasPreviousPage();
                    $pager->getPreviousPage();
                    $pager->hasNextPage();
                    $pager->getNextPage();
                    $pager->getCurrentPageResults();
                */

                $compact = [];

                //On passe l'adapter au bundle qui va s'occuper de la pagination
                if ($orm) {
                    $compact['pager'] = new Pagerfanta(new QueryAdapter($params['adapter_em_repository']));
                } else {
                    $compact['pager'] = new Pagerfanta(new ArrayAdapter($params['adapter_em_repository']));
                }

                //On définit le nombre d'article à afficher par page (que l'on a biensur définit dans le fichier param)
                $compact['pager']->setMaxPerPage($params['maxPerPage']);
                try {
                    $compact['entities'] = $compact['pager']
                        //On indique au pager quelle page on veut
                        ->setCurrentPage($params['page'])
                        //On récupère les résultats correspondant
                        ->getCurrentPageResults();

                    $compact['nb'] = $compact['pager']->getNbResults();
                } catch (\Pagerfanta\Exception\NotValidCurrentPageException $e) {
                    //Si jamais la page n'existe pas on léve une 404
                    throw $this->createNotFoundException("Cette page n'existe pas.");
                }

                return $compact;
            }

            return false;
        }

        // Décrypte les paramètres de connexion d'une solution
        private function decrypt_params($tab_params)
        {
            // Instanciate object to decrypte data
            $encrypter = new Encrypter(substr($this->getParameter('secret'), -16));
            if (is_array($tab_params)) {
                $return_params = [];
                foreach ($tab_params as $key => $value) {
                    if (
                        is_string($value)
                        && !in_array($key, ['solution', 'module']) // Soe data aren't crypted
                    ) {
                        $return_params[$key] = $encrypter->decrypt($value);
                    }
                }

                return $return_params;
            }

            return $encrypter->decrypt($tab_params);
        }


        // Function to take source document ids optain in a form and reading them and them only.

        /**
         * @param $id
         * 
         * @Route("/executebyid/{id}", name="run_by_id")
         */
    public function execRuleById($id, Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('id', TextareaType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $documentIdString = $form->get('id')->getData();

            // We will get to the runrecord commmand using the ids from the form.
            $this->ruleExecAction($id, $documentIdString);
        }
        return $this->render('Rule/byIdForm.html.twig', [
            'formIdBatch' => $form->createView()
        ]);
    }

    /**
     * @Route("/rule/update_description", name="update_rule_description", methods={"POST"})
     */
    public function updateDescription(Request $request): Response
    {
        $ruleId = $request->request->get('ruleId');
        $description = $request->request->get('description');
        $entityManager = $this->entityManager;
        $descriptionOriginal = $entityManager->getRepository(RuleParam::class)->findOneBy([
            'rule' => $ruleId,
            'name' => 'description'
        ]);

        if ($description === '0' || empty($description) || $description === $descriptionOriginal->getValue()) {
            return $this->redirect($this->generateUrl('regle_open', ['id' => $ruleId]));
        }

        $rule = $entityManager->getRepository(RuleParam::class)->findOneBy(['rule' => $ruleId]);

        if (!$rule) {
            throw $this->createNotFoundException('Couldn\'t find specified rule in database');
        }

        // Retrieve the RuleParam with the name "description" and the same rule as the previously retrieved entity
        $descriptionRuleParam = $entityManager->getRepository(RuleParam::class)->findOneBy([
            'rule' => $rule->getRule(),
            'name' => 'description'
        ]);

        // Check if the description entity was found
        if (!$descriptionRuleParam) {
            throw $this->createNotFoundException('Couldn\'t find description rule parameter');
        }

        // Update the value of the description
        $descriptionRuleParam->setValue($description);
        $entityManager->flush();

        return new Response('', Response::HTTP_OK);
    }

    /**
     * @Route("/rule/update_name", name="update_rule_name", methods={"POST"})
     */
    public function updateRuleName(Request $request): Response
    {
        $ruleId = $request->request->get('ruleId');
        $name = $request->request->get('ruleName');
        $entityManager = $this->entityManager;
        $rule = $entityManager->getRepository(Rule::class)->find($ruleId);

        if (!$rule) {
            throw $this->createNotFoundException('Couldn\'t find specified rule in the database');
        }

        if ($name === '0' || empty($name) || $name === $rule->getName()) {
            return $this->redirect($this->generateUrl('regle_open', ['id' => $ruleId]));
        }

        $rule->setName($name);
        $nameSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $name), '_'));
        $rule->setNameSlug($nameSlug);

        $entityManager->flush();

        return new Response('Update successful', Response::HTTP_OK);
    }

    /**
     * @Route("/check-rule-name", name="check_rule_name", methods={"GET"})
     */
    public function checkRuleName(Request $request): JsonResponse
    {
        $name = $request->query->get('ruleName');
        $ruleId = $request->query->get('ruleId');
        $entityManager = $this->entityManager;

        $ruleRepository = $entityManager->getRepository(Rule::class);

        $existingRule = $ruleRepository->findOneBy(['name' => $name]);
        
        if ($existingRule && $existingRule->getId() !== $ruleId) {
            return new JsonResponse(['exists' => true]);
        }

        return new JsonResponse(['exists' => false]);
    }

    /**
     * @Route("/rulefield/{id}/comment", name="rulefield_update_comment", methods={"POST"})
     */
    public function updateComment(RuleField $ruleField, Request $request, EntityManagerInterface $entityManager): Response
    {
        $comment = $request->request->get('comment');
        
        $ruleField->setComment($comment);
        $entityManager->persist($ruleField);
        $entityManager->flush();

        return new Response('Update successful', Response::HTTP_OK);
    }

    /**
     * @Route("/get-rules-for-lookup", name="get_rules_for_lookup", methods={"GET"})
     */
    public function getRulesForLookup(Request $request): JsonResponse
    {
        // Get the arguments from the request
        $arg1 = $request->query->getInt('arg1', 0);
        $arg2 = $request->query->getInt('arg2', 0);
        
        // Use the arguments in your query
        $rules = $this->entityManager->getRepository(Rule::class)
            ->findBy([
                'deleted' => 0,
                'connectorSource' => $arg1, // Using arg1 instead of hardcoded 39
                'connectorTarget' => $arg2
            ]);
        
        $ruleData = array_map(function($rule) use ($arg2) {
            return [
                'id' => $rule->getId(),
                'name' => $rule->getName(),
            ];
        }, $rules);
        
        return new JsonResponse($ruleData);
    }

    /**
     * @Route("/get-fields-for-rule", name="rule_get_fields_for_rule", methods={"GET"})
     */
    public function getFieldsForRule(): JsonResponse
    {
        $fields = $this->entityManager->getRepository(RuleField::class)->findAll();
             
        $fieldData = array_map(function($field) {
            return [
                'id' => $field->getId(),
                'name' => $field->getTarget(),
                'rule' => $field->getRule()->getName(),
                'rule_id' => $field->getRule()->getId()
            ];
        }, $fields);
         
        return new JsonResponse($fieldData);
    }

    /**
     * @Route("/get-lookup-rule-from-field-name", name="get_lookup_rule_from_field_name", methods={"GET"})
     */
    public function getLookupRuleFromFieldName(Request $request): JsonResponse
    {
        $fieldName = $request->query->get('lookupfieldName');
        $currentRuleId = $request->query->get('currentRule');
        $entityManager = $this->entityManager;
        $currentRule = $entityManager->getRepository(Rule::class)->findOneBy(['id' => $currentRuleId]);

        // from the current rule, get the formulas
        $formula = $currentRule->getFormulaByFieldName($fieldName);

        if (empty($formula)) {
            return new JsonResponse(['rule' => '']);
        }

        // in the formula, we get the lookup rule id
        $lookupRuleId = $this->getLookupRuleIdFromFormula($formula);

        // from the lookup rule id, we get the lookup rule
        $lookupRule = $entityManager->getRepository(Rule::class)->findOneBy(['id' => $lookupRuleId]);

        // from the lookup rule, we get the lookup rule name
        $lookupRuleName = $lookupRule->getName();

        return new JsonResponse(['rule' => $lookupRuleName]);
    }

    public function getLookupRuleIdFromFormula(string $formula): string
    {
        // from the formula, we get the lookup rule id

        // lookup({assigned_user_id}, "67acc3f4a9f0c", 0, 1) this is an example of a formula, from the field name assigned_user_id, we get the lookup rule id which is 67acc3f4a9f0c

        $lookupRuleId = explode(',', $formula)[1];

        // since there can be extra spaces or such, as a result we had " "67acc3f4a9f0c""
        // we need to remove the spaces
        $lookupRuleId = trim($lookupRuleId, '"');

        $lookupRuleId = trim($lookupRuleId, ' ');

        // remove the extra double quotes
        $lookupRuleId = str_replace('"', '', $lookupRuleId);

        return $lookupRuleId;
    }
    

    /**
     * Returns field information as JSON
     * @Route("/api/field-info/{type}/{field}/", name="api_field_info", methods={"GET"})
     */
    public function getFieldInfo(Request $request, $field, $type): JsonResponse
    {
        $session = $request->getSession();
        $myddlewareSession = $session->get('myddlewareSession');
        // We always add data again in session because these data are removed after the call of the get
        $session->set('myddlewareSession', $myddlewareSession);
        
        $fieldInfo = ['field' => '', 'name' => ''];
        
        if (isset($field) && !empty($field) && isset($myddlewareSession['param']['rule']) && 'my_value' != $field) {
            if (isset($myddlewareSession['param']['rule'][0][$type]['fields'][$field])) {
                $fieldInfo = [
                    'field' => $myddlewareSession['param']['rule'][0][$type]['fields'][$field],
                    'name' => htmlentities(trim($field))
                ];
            // SuiteCRM connector uses this structure instead
            } elseif (isset($myddlewareSession['param']['rule']['key'])) {
                $ruleKey = $myddlewareSession['param']['rule']['key'];
                $fieldInfo = [
                    'field' => $myddlewareSession['param']['rule'][$ruleKey][$type]['fields'][$field],
                    'name' => htmlentities(trim($field))
                ];
            } else {
                // Possibilité de Mutlimodules
                foreach ($myddlewareSession['param']['rule'][0][$type]['fields'] as $subModule) {
                    if (isset($subModule[$field])) {
                        $fieldInfo = [
                            'field' => $subModule[$field],
                            'name' => htmlentities(trim($field))
                        ];
                        break;
                    }
                }
            }
        }

        return new JsonResponse($fieldInfo);
    }
}
