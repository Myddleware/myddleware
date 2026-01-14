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

use Exception;
use App\Entity\Rule;
use App\Entity\RuleField;
use App\Entity\Solution;
use App\Entity\Functions;
use App\Entity\User;
use App\Entity\Workflow;
use App\Manager\RuleManager;
use App\Manager\ToolsManager;
use App\Manager\TemplateManager;
use App\Manager\SolutionManager;
use App\Manager\FormulaManager;
use App\Service\SessionService;
use App\Service\TwoFactorAuthService;
// Nos nouveaux services
use App\Service\Rule\RulePersistenceService;
use App\Service\Rule\RuleQueryService;
use App\Service\Rule\RuleStepService;
use App\Service\RuleSimulationService;
use App\Repository\RuleRepository;
use App\Repository\DocumentRepository;
use App\Repository\SolutionRepository;
use App\Repository\VariableRepository;
use App\Form\DuplicateRuleFormType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

#[Route("/rule")]
class RuleController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private SessionService $sessionService,
        private ToolsManager $tools,
        private RuleManager $ruleManager,
        private TemplateManager $templateManager,
        private FormulaManager $formuleManager,
        private SolutionManager $solutionManager, // Gardé pour les helpers legacy
        private TwoFactorAuthService $twoFactorAuthService,
        private RuleRepository $ruleRepository,
        private DocumentRepository $documentRepository,
        // Nouveaux Services Refactorisés
        private RulePersistenceService $rulePersistenceService,
        private RuleQueryService $ruleQueryService,
        private RuleStepService $ruleStepService,
        private RuleSimulationService $ruleSimulationService
    ) {}

    // =========================================================================
    // SECTION 1 : LISTING & CRUD (PersistenceService)
    // =========================================================================

    #[Route('/list', name: 'regle_list', defaults: ['page' => 1])]
    #[Route('/list/page-{page}', name: 'regle_list_page', requirements: ['page' => '\d+'])]
    public function ruleListAction(Request $request, int $page = 1): Response
    {
        try {
            $ruleName = $request->query->get('rule_name');
            $limit = $this->tools->getParamValue('ruleListPager') ?? 20;

            $query = $this->entityManager->getRepository(Rule::class)->findListRuleByUser($this->getUser(), $ruleName);
            $query->setHydrationMode(Query::HYDRATE_ARRAY);

            $adapter = new QueryAdapter($query, false, true);
            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage($limit);
            $pagerfanta->setCurrentPage($page);

            return $this->render('Rule/list.html.twig', [
                'nb_rule'  => $pagerfanta->getNbResults(),
                'entities' => iterator_to_array($pagerfanta->getCurrentPageResults()),
                'pager'    => $pagerfanta,
            ]);
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Error : ' . $e->getMessage());
        }
    }

    #[Route('/delete/{id}', name: 'regle_delete', methods: ['GET', 'POST'])]
    public function deleteRule(Rule $rule): RedirectResponse
    {
        if (!$this->getUser()->isAdmin() && $rule->getCreatedBy()->getId() !== $this->getUser()->getId()) {
            return $this->redirect($this->generateUrl('regle_list'));
        }

        try {
            $this->rulePersistenceService->deleteRule($rule);
            $this->addFlash('rule.delete.success', $this->translator->trans('rule.delete.success'));
            return $this->redirect($this->generateUrl('regle_list'));
        } catch (Exception $e) {
            $parts = explode('|', $e->getMessage());
            $msg = $this->translator->trans($parts[0]) . (isset($parts[1]) ? ' ' . $parts[1] : '');
            $this->addFlash('rule.error', $msg);
            return $this->redirect($this->generateUrl('regle_list'));
        }
    }

    #[Route('/duplic_rule/{id}', name: 'duplic_rule')]
    public function duplicateRule(Rule $rule, Request $request): Response
    {
        try {
            $newRule = new Rule();
            // Récupération sécurisée des IDs solutions
            $srcSolId = $rule->getConnectorSource()?->getSolution()?->getId();
            $tgtSolId = $rule->getConnectorTarget()?->getSolution()?->getId();

            $form = $this->createForm(DuplicateRuleFormType::class, $newRule, [
                'solution' => ['source' => $srcSolId, 'target' => $tgtSolId]
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = [
                    'name' => $form->get('name')->getData(),
                    'connectorSource' => $form->get('connectorSource')->getData(),
                    'connectorTarget' => $form->get('connectorTarget')->getData(),
                ];
                
                $this->rulePersistenceService->duplicateRule($rule, $data, $this->getUser());
                $this->addFlash('rule.duplicate.success', $this->translator->trans('duplicate_rule.success_duplicate'));
                
                return $this->redirect($this->generateUrl('regle_list'));
            }

            return $this->render('Rule/create/duplic.html.twig', [
                'rule' => $rule,
                'connectorSourceUser' => $rule->getConnectorSource()?->getName(),
                'connectorTarget' => $rule->getConnectorTarget()?->getName(),
                'form' => $form->createView(),
            ]);
        } catch (Exception $e) {
            return new JsonResponse($e->getMessage());
        }
    }

    #[Route('/update/{id}', name: 'regle_update', methods: ['GET', 'POST'])]
    public function ruleUpdActive(Rule $rule): Response
    {
        try {
            $r = $this->rulePersistenceService->toggleActive($rule);
            return new Response((string)$r);
        } catch (Exception $e) {
            return new JsonResponse($e->getMessage());
        }
    }

    // =========================================================================
    // SECTION 2 : WIZARD & CREATION (WizardService + PersistenceService)
    // =========================================================================

    #[Route('/create', name: 'regle_stepone_animation', methods: ['GET'])]
    public function create(): Response
    {
        $solutions = $this->entityManager->getRepository(Solution::class)->findBy(['active' => 1], ['name' => 'ASC']);
        $ruleKey = $this->sessionService->getParamRuleLastKey();
        $lstFunctions = $this->entityManager->getRepository(Functions::class)->findAll();

        return $this->render('Rule/create/index.html.twig', [
            'solutions' => $solutions,
            'ruleKey'   => $ruleKey,
            'lst_functions' => $lstFunctions,
            'rule' => null
        ]);
    }

    #[Route('/rule/create/save', name: 'rule_create_save', methods: ['POST'])]
    public function ruleCreateSave(Request $request): JsonResponse
    {
        try {
            $result = $this->rulePersistenceService->saveRule($request->request->all(), $this->getUser());
            
            $msg = $result['is_edit'] ? 'edit_rule.success' : 'create_rule.success';
            $flash = $result['is_edit'] ? 'rule.edit.success' : 'rule.create.success';
            $this->addFlash($flash, $this->translator->trans($msg));

            return new JsonResponse([
                'ok' => true,
                'id' => $result['id'],
                'redirect' => $this->generateUrl('regle_open', ['id' => $result['id']]),
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/inputs', name: 'regle_inputs', methods: ['POST'], options: ['expose' => true])]
    public function ruleInputs(Request $request): Response
    {
        try {
            $result = $this->ruleStepService->handleConnectionInput($request->request->all());
            
            if (isset($result['type']) && $result['type'] === 'form') {
                return $this->render('Ajax/result_liste_inputs.html.twig', [
                    'form' => $result['form'],
                    'parent' => $result['parent'],
                ]);
            }
            return new JsonResponse($result);
        } catch (Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    #[Route('/inputs/name_unique/', name: 'regle_inputs_name_unique', methods: ['POST'], options: ['expose' => true])]
    public function ruleNameUniq(Request $request): JsonResponse
    {
        try {
            $exists = $this->ruleStepService->checkNameUniqueness((string)$request->request->get('name'));
            return new JsonResponse($exists);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/create/list-connectors', name: 'regle_list_connectors', methods: ['GET'])]
    public function listConnectors(Request $request): Response
    {
        $solutionId = $request->query->getInt('solution_id');
        $connectors = $this->ruleStepService->getActiveConnectors($solutionId);
        $solution = $this->entityManager->getRepository(Solution::class)->find($solutionId);

        return $this->render('Rule/create/ajax_step1/_options_connectors.html.twig', [
            'connectors' => $connectors,
            'solutionSlug' => $solution ? strtolower($solution->getName()) : '',
        ]);
    }

    #[Route('/create/list-module', name: 'regle_list_module', methods: ['GET'])]
    public function listModules(Request $request): Response
    {
        try {
            $data = $this->ruleStepService->getAvailableModules($request->query->getInt('id'), $request->query->get('type', 'source'));
            return $this->render('Rule/create/ajax_step1/_options_modules.html.twig', [
                'modules' => $data['modules'],
            ]);
        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 400);
        }
    }

    #[Route('/create/module-fields', name: 'regle_module_fields', methods: ['GET'])]
    public function getModuleFields(Request $request): JsonResponse
    {
        try {
            $fields = $this->ruleStepService->getModuleFields(
                $request->query->getInt('connector_id'),
                $request->query->get('module'),
                $request->query->get('type', 'source')
            );
            return new JsonResponse(['fields' => $fields]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/create/filters', name: 'regle_step_filters', methods: ['GET'])]
    public function ruleStepFilters(Request $request): Response
    {
        $fieldsGrouped = $this->ruleStepService->getFieldsForFilters($request->query->all());
        
        $operators = [
            $this->translator->trans('filter.content')    => 'content',
            $this->translator->trans('filter.notcontent') => 'notcontent',
            $this->translator->trans('filter.begin')      => 'begin',
            $this->translator->trans('filter.end')        => 'end',
            $this->translator->trans('filter.gt')         => 'gt',
            $this->translator->trans('filter.lt')         => 'lt',
            $this->translator->trans('filter.equal')      => 'equal',
            $this->translator->trans('filter.different')  => 'different',
            $this->translator->trans('filter.gteq')       => 'gteq',
            $this->translator->trans('filter.lteq')       => 'lteq',
            $this->translator->trans('filter.in')         => 'in',
            $this->translator->trans('filter.notin')      => 'notin',
        ];

        // Charger les filtres existants si mode edit (rule_id présent)
        $filters = [];
        if ($ruleId = $request->query->get('rule_id')) {
            $filters = $this->entityManager->getRepository(\App\Entity\RuleFilter::class)->findBy(['rule' => $ruleId]);
        }

        return $this->render('Rule/create/ajax_step4/_options_fields_filters.html.twig', [
            'fieldsGrouped' => $fieldsGrouped,
            'operators'     => $operators,
            'filters'       => $filters,
            'ruleKey'       => $ruleId,
        ]);
    }

    #[Route('/create/params/fields', name: 'regle_params_fields', methods: ['GET'])]
    public function getParamsFields(Request $request): Response
    {
        try {
            $data = $this->ruleStepService->getStep3Params(
                (int)$request->query->get('src_connector'),
                (int)$request->query->get('tgt_connector'),
                (string)$request->query->get('src_module'),
                (string)$request->query->get('tgt_module')
            );

            return $this->render('Rule/create/ajax_step3/_options_params.html.twig', [
                'rule_params'      => $data['rule_params'],
                'duplicate_target' => $data['duplicate_target'],
            ]);
        } catch (\Throwable $e) {
            return new Response('<div class="alert alert-danger">' . $e->getMessage() . '</div>', 500);
        }
    }

    #[Route('/create/step3/formula/', name: 'regle_formula', methods: ['POST'])]
    public function ruleVerifFormula(Request $request): JsonResponse
    {
        $this->formuleManager->init($request->request->get('formula'));
        $this->formuleManager->generateFormule();
        return new JsonResponse($this->formuleManager->parse['error']);
    }

    // =========================================================================
    // SECTION 3 : SIMULATION (SimulationService)
    // =========================================================================

    #[Route('/create/step3/simulation/', name: 'regle_simulation', methods: ['POST'])]
    public function ruleSimulation(Request $request): Response
    {
        $ruleKey = $this->sessionService->getParamRuleLastKey();
        $simulationData = $this->ruleSimulationService->simulatePreview($request->request->all(), $ruleKey);

        if (isset($simulationData['error'])) {
            return new Response(json_encode(['error' => $simulationData['error']]), 400);
        }

        $paramsForView = [
            'source' => ['solution' => $request->request->get('src_solution_name'), 'module' => $request->request->get('src_module')],
            'cible'  => ['solution' => $request->request->get('tgt_solution_name'), 'module' => $request->request->get('tgt_module')],
        ];

        return $this->render('Rule/_simulation_tab.html.twig', array_merge($simulationData, [
            'params' => $paramsForView
        ]));
    }

    #[Route('/simule/{id}', name: 'path_fiche_params_simulate', methods: ['GET'])]
    public function ruleSimulateTransfers(Rule $rule): Response
    {
        try {
            $count = $this->ruleSimulationService->simulateCount($rule);
            return new Response((string) $count);
        } catch (Exception $e) {
            return new Response(json_encode(['error' => $e->getMessage()]));
        }
    }

    // =========================================================================
    // SECTION 4 : VIEW & EDIT (QueryService)
    // =========================================================================

    #[Route('/{id}/edit', name: 'rule_edit', methods: ['GET'])]
    public function edit(Rule $rule): Response
    {
        if ($rule->getDeleted()) {
            throw $this->createNotFoundException(sprintf('Rule "%s" has been deleted', $rule->getId()));
        }

        $initialRuleJson = $this->ruleQueryService->prepareJsonForEdit($rule);
        $lst_functions = $this->entityManager->getRepository(Functions::class)->findAll();
        $solutions = $this->entityManager->getRepository(Solution::class)->findBy(['active' => 1], ['name' => 'ASC']);

        return $this->render('Rule/create/index.html.twig', [
            'initialRuleJson' => $initialRuleJson,
            'rule'            => $rule,
            'lst_functions'   => $lst_functions,
            'solutions'       => $solutions,
        ]);
    }

    #[Route('/view/{id}', name: 'regle_open', methods: ['GET'])]
    public function ruleOpenAction(Rule $rule): Response
    {
        if (!$this->getUser()->isAdmin() && $rule->getCreatedBy()->getId() !== $this->getUser()->getId()) {
             throw $this->createNotFoundException('Access denied');
        }

        $viewData = $this->ruleQueryService->prepareDataForView($rule);

        return $this->render('Rule/edit/fiche.html.twig', array_merge([
            'rule' => $rule,
            'id'   => $rule->getId(),
        ], $viewData));
    }

    // =========================================================================
    // SECTION 5 : UPDATES AJAX (PersistenceService)
    // =========================================================================

    #[Route('/update/params/{id}', name: 'path_fiche_params_update', methods: ['POST'])]
    public function ruleUpdParams(Rule $rule, Request $request): Response
    {
        $params = $request->request->all('params');
        if (is_array($params)) {
            foreach ($params as $p) {
                if (isset($p['name'], $p['value'])) {
                    $this->rulePersistenceService->updateParamValue($rule, $p['name'], $p['value'], $this->getUser());
                }
            }
        }
        return new Response('1');
    }

    #[Route('/rule/update_name', name: 'update_rule_name', methods: ['POST'])]
    public function updateRuleName(Request $request): Response
    {
        $ruleId = $request->request->get('ruleId');
        $name = $request->request->get('ruleName');
        $rule = $this->entityManager->getRepository(Rule::class)->find($ruleId);
        
        if ($rule) {
            $this->rulePersistenceService->updateName($rule, (string)$name);
            return new Response('Update successful', Response::HTTP_OK);
        }
        return new Response('Rule not found', 404);
    }

    #[Route('/rule/update_description', name: 'update_rule_description', methods: ['POST'])]
    public function updateDescription(Request $request): JsonResponse
    {
        $ruleId = $request->request->get('ruleId');
        $desc = (string) $request->request->get('description', '');
        $rule = $this->entityManager->getRepository(Rule::class)->find($ruleId);
        
        if ($rule) {
            $this->rulePersistenceService->updateDescription($rule, $desc);
            return new JsonResponse(['ok' => true]);
        }
        return new JsonResponse(['error' => 'Rule not found'], 404);
    }

    #[Route('/check-rule-name', name: 'check_rule_name', methods: ['GET'])]
    public function checkRuleName(Request $request): JsonResponse
    {
        $name = $request->query->get('ruleName');
        $ruleId = $request->query->get('ruleId');
        $existingRule = $this->entityManager->getRepository(Rule::class)->findOneBy(['name' => $name]);

        if ($existingRule && $existingRule->getId() !== $ruleId) {
            return new JsonResponse(['exists' => true]);
        }
        return new JsonResponse(['exists' => false]);
    }

    #[Route('/rulefield/{id}/comment', name: 'rulefield_update_comment', methods: ['POST'])]
    public function updateComment(RuleField $ruleField, Request $request): Response
    {
        $this->rulePersistenceService->updateFieldComment($ruleField, (string)$request->request->get('comment'));
        return new Response('Update successful', Response::HTTP_OK);
    }

    // =========================================================================
    // SECTION 7 : EXECUTION & AJAX LOOKUPS (Legacy Helpers)
    // =========================================================================

    /**
     * CANCEL ALL TRANSFERS FOR ONE RULE.
     */
    #[Route('/view/cancel/documents/{id}', name: 'rule_cancel_all_transfers', methods: ['GET', 'POST'])]
    public function cancelRuleTransfers(string $id): RedirectResponse
    {
        try {
            $this->ruleManager->setRule($id);
            // 'runMyddlewareJob' avec l'option 'cancelDocumentJob'
            $this->ruleManager->actionRule('runMyddlewareJob', 'cancelDocumentJob');
            
            $this->addFlash('success', $this->translator->trans('Transfers cancelled successfully')); // (Ajoutez la clé de trad si elle existe)
        } catch (Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
    }

    /**
     * DELETE ALL TRANSFERS FOR ONE RULE.
     */
    #[Route('/view/delete/documents/{id}', name: 'rule_delete_all_transfers', methods: ['GET', 'POST'])]
    public function deleteRuleTransfers(string $id): RedirectResponse
    {
        try {
            $this->ruleManager->setRule($id);
            // 'runMyddlewareJob' avec l'option 'deleteDocumentJob'
            $this->ruleManager->actionRule('runMyddlewareJob', 'deleteDocumentJob');
            
            $this->addFlash('success', $this->translator->trans('Transfers deleted successfully'));
        } catch (Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
    }

    #[Route('/exec/{id}', name: 'regle_exec', methods: ['GET','POST'])]
    public function ruleExecAction($id, $documentId = null): RedirectResponse
    {
        try {
            $this->ruleManager->setRule($id);
            if ($documentId !== null) {
                $this->ruleManager->actionRule('runRuleByDocId', 'execrunRuleByDocId', $documentId);
            } elseif ($id === 'ALL' || $id === 'ERROR') {
                $this->ruleManager->actionRule($id);
                return $this->redirect($this->generateUrl('regle_list'));
            } else {
                $this->ruleManager->actionRule('runMyddlewareJob');
            }
            return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->redirect($this->generateUrl('regle_list'));
        }
    }

    #[Route('/executebyid/{id}', name: 'run_by_id', methods: ['GET', 'POST'])]
    public function execRuleById($id, Request $request): Response
    {
        $form = $this->createFormBuilder()->add('id', TextareaType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ids = str_replace(["\r\n", "\r", "\n", " "], [',', ',', ',', ''], $form->get('id')->getData());
            return $this->ruleExecAction($id, trim($ids, ", \r\n\t"));
        }
        return $this->render('Rule/byIdForm.html.twig', ['formIdBatch' => $form->createView()]);
    }

    #[Route('/displayflux/{id}', name: 'regle_displayflux', methods: ['GET'])]
    public function displayFlux(Rule $rule): RedirectResponse
    {
        $this->sessionService->setFluxFilterWhere(['rule' => $rule->getName()]);
        $this->sessionService->setFluxFilterRuleName($rule->getName());
        return $this->redirect($this->generateUrl('document_list_page'));
    }

    #[Route('/get-rule-name/{id}', name: 'get_rule_name', methods: ['GET'])]
    public function getRuleNameById(Rule $rule): Response
    {
        return new Response($rule->getName());
    }

    #[Route('/get-first-part-of-lookup-formula/{formula}', name: 'get_first_part_of_lookup_formula')]
    public function getFirstPartOfLookupFormula($formula): Response
    {
        if (preg_match('/lookup\(\{[^}]+\},\s*/', $formula, $matches)) {
            return new Response($matches[0]);
        }
        return new Response('');
    }

    #[Route('/get-second-part-of-lookup-formula/{formula}', name: 'get_second_part_of_lookup_formula')]
    public function getSecondPartOfLookupFormula($formula): Response
    {
        if (preg_match('/",\s*(.+)\)/', $formula, $matches)) {
            return new Response(', ' . $matches[1] . ')');
        }
        return new Response('');
    }

    #[Route('/get-rules-for-lookup', name: 'get_rules_for_lookup', methods: ['GET'])]
    public function getRulesForLookup(Request $request): JsonResponse
    {
        $sourceConnector = $request->query->getInt('arg1', 0);
        $targetConnector = $request->query->getInt('arg2', 0);

        $rulesNormal = $this->entityManager->getRepository(Rule::class)->findBy([
            'deleted' => 0,
            'connectorSource' => $sourceConnector,
            'connectorTarget' => $targetConnector
        ]);

        // Find rules with reversed connectors (target -> source) for reverse lookup
        $rulesReversed = $this->entityManager->getRepository(Rule::class)->findBy([
            'deleted' => 0,
            'connectorSource' => $targetConnector,
            'connectorTarget' => $sourceConnector
        ]);

        // Merge both results and remove duplicates
        $allRules = array_merge($rulesNormal, $rulesReversed);

        // exclude the current rule
        $currentRuleId = null;
        $referer = $request->headers->get('referer');
        if ($referer) {
            $parts = explode('/rule/', $referer);
            if (isset($parts[1])) {
                $subParts = explode('/', $parts[1]);
                $currentRuleId = $subParts[0];
            }
        }
        if ($currentRuleId) {
            $allRules = array_filter($allRules, fn($r) => $r->getId() !== $currentRuleId);
        }

        // Remove duplicates based on rule ID
        $uniqueRules = [];
        $seenIds = [];
        foreach ($allRules as $rule) {
            if (!in_array($rule->getId(), $seenIds)) {
                $uniqueRules[] = $rule;
                $seenIds[] = $rule->getId();
            }
        }

        return new JsonResponse(array_map(fn($r) => ['id' => $r->getId(), 'name' => $r->getName()], $uniqueRules));
    }

    #[Route('/get-fields-for-rule', name: 'rule_get_fields_for_rule', methods: ['GET'])]
    public function getFieldsForRule(): JsonResponse
    {
        $fields = $this->entityManager->getRepository(RuleField::class)->findAll();
        $data = array_map(fn($f) => [
            'id' => $f->getId(),
            'name' => $f->getTarget(),
            'rule' => $f->getRule()->getName(),
            'rule_id' => $f->getRule()->getId()
        ], $fields);
        return new JsonResponse($data);
    }

    #[Route('/get-lookup-rule-from-field-name', name: 'get_lookup_rule_from_field_name', methods: ['GET'])]
    public function getLookupRuleFromFieldName(Request $request): JsonResponse
    {
        $currentRule = $this->entityManager->getRepository(Rule::class)->find($request->query->get('currentRule'));
        $formula = $currentRule?->getFormulaByFieldName($request->query->get('lookupfieldName'));

        if (empty($formula)) return new JsonResponse(['rule' => '']);

        // Extraction ID règle liée (simple regex plus robuste que explode)
        if (preg_match('/lookup\([^{]*\{[^}]+\},\s*"([^"]+)"/', $formula, $m)) {
             $lookupRuleId = $m[1];
             $lookupRule = $this->entityManager->getRepository(Rule::class)->find($lookupRuleId);
             return new JsonResponse(['rule' => $lookupRule?->getName() ?? '']);
        }
        return new JsonResponse(['rule' => '']);
    }

    #[Route('/api/field-info/{type}/{field}/', name: 'api_field_info', methods: ['GET'])]
    public function getFieldInfo(Request $request, $field, $type): JsonResponse
    {
        $session = $request->getSession();
        $myddlewareSession = $session->get('myddlewareSession');
        // Refresh session
        $session->set('myddlewareSession', $myddlewareSession);
        
        $fieldInfo = ['field' => '', 'name' => ''];
        
        if (!empty($field) && isset($myddlewareSession['param']['rule']) && $field !== 'my_value') {
            $ruleData = $myddlewareSession['param']['rule'];
            
            // Check direct access
            if (isset($ruleData[0][$type]['fields'][$field])) {
                $rawField = $ruleData[0][$type]['fields'][$field];
            } elseif (isset($ruleData['key']) && isset($ruleData[$ruleData['key']][$type]['fields'][$field])) {
                $rawField = $ruleData[$ruleData['key']][$type]['fields'][$field];
            } else {
                // Check submodules
                $rawField = null;
                if (isset($ruleData[0][$type]['fields'])) {
                    foreach ($ruleData[0][$type]['fields'] as $subModule) {
                        if (is_array($subModule) && isset($subModule[$field])) {
                            $rawField = $subModule[$field];
                            break;
                        }
                    }
                }
            }

            if ($rawField) {
                $fieldInfo = [
                    'field' => $rawField,
                    'name' => htmlentities(trim($field))
                ];
            }
        }
        return new JsonResponse($fieldInfo);
    }

    #[Route('/assets/solution-icon/{slug}', name: 'rule_solution_icon', methods: ['GET'])]
    public function getSolutionIcon(string $slug): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . '/assets/images/solution/' . $slug . '.png';
        return new BinaryFileResponse($filePath);
    }
}