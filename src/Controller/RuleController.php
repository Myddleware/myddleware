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
use App\Entity\User;
use App\Entity\FuncCat;
use Doctrine\ORM\Query;
use App\Entity\Document;
use App\Entity\Solution;
use App\Entity\Variable;
use App\Entity\Workflow;
use App\Entity\Connector;
use App\Entity\Functions;
use App\Entity\RuleAudit;
use App\Entity\RuleField;
use App\Entity\RuleParam;
use App\Entity\RuleFilter;
use Pagerfanta\Pagerfanta;
use App\Form\ConnectorType;
use App\Manager\JobManager;
use App\Manager\HomeManager;
use App\Manager\RuleManager;
use Psr\Log\LoggerInterface;
use App\Manager\ToolsManager;
use Doctrine\DBAL\Connection;
use App\Entity\ConnectorParam;
use App\Entity\RuleParamAudit;
use App\Manager\FormulaManager;
use App\Service\SessionService;
use App\Entity\RuleRelationShip;
use App\Manager\DocumentManager;
use App\Manager\SolutionManager;
use App\Manager\TemplateManager;
use Symfony\Component\Yaml\Yaml;
use App\Repository\JobRepository;
use App\Repository\RuleRepository;
use App\Form\DuplicateRuleFormType;
use App\Service\RuleCleanupService;
use App\Repository\ConfigRepository;
use Illuminate\Encryption\Encrypter;
use App\Form\Type\RelationFilterType;
use App\Service\TwoFactorAuthService;
use App\Service\RuleDuplicateService;
use App\Service\RuleSimulationService;
use App\Repository\DocumentRepository;
use App\Repository\VariableRepository;
use App\Repository\RuleFieldRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

    /**
     * @Route("/rule")
     */
    class RuleController extends AbstractController
    {
        private FormulaManager $formuleManager;
        private SessionService $sessionService;
        private EntityManagerInterface $entityManager;
        private ToolsManager $tools;
        private TranslatorInterface $translator;
        private AuthorizationCheckerInterface $authorizationChecker;
        private JobManager $jobManager;
        private LoggerInterface $logger;
        private RuleCleanupService $ruleCleanupService;
        private RuleDuplicateService $ruleDuplicateService;
        private RuleSimulationService $ruleSimulationService;
        private RuleRepository $ruleRepository;
        private DocumentRepository $documentRepository;
        private SolutionManager $solutionManager;
        private RuleManager $ruleManager;
        private DocumentManager $documentManager;
        protected Connection $connection;
        // To allow sending a specific record ID to rule simulation
        protected $simulationQueryField;
        private TwoFactorAuthService $twoFactorAuthService;
        private RequestStack $requestStack;

        public function __construct(
            LoggerInterface $logger,
            RuleManager $ruleManager,
            FormulaManager $formuleManager,
            SolutionManager $solutionManager,
            DocumentManager $documentManager,
            SessionService $sessionService,
            EntityManagerInterface $entityManager,
            RuleRepository $ruleRepository,
            RuleFieldRepository $ruleFieldRepository,
            JobRepository $jobRepository,
            DocumentRepository $documentRepository,
            Connection $connection,
            TranslatorInterface $translator,
            RuleCleanupService $ruleCleanupService,
            RuleDuplicateService $ruleDuplicateService,
            RuleSimulationService $ruleSimulationService,
            AuthorizationCheckerInterface $authorizationChecker,
            HomeManager $home,
            ToolsManager $tools,
            JobManager $jobManager,
            TemplateManager $template,
            TwoFactorAuthService $twoFactorAuthService,
            RequestStack $requestStack
        ) {
            $this->logger = $logger;
            $this->ruleManager = $ruleManager;
            $this->formuleManager = $formuleManager;
            $this->solutionManager = $solutionManager;
            $this->documentManager = $documentManager;
            $this->sessionService = $sessionService;
            $this->entityManager = $entityManager;
            $this->ruleCleanupService = $ruleCleanupService;
            $this->ruleDuplicateService = $ruleDuplicateService;
            $this->ruleSimulationService = $ruleSimulationService;
            $this->ruleRepository = $ruleRepository;
            $this->documentRepository = $documentRepository;
            $this->connection = $connection;
            $this->translator = $translator;
            $this->authorizationChecker = $authorizationChecker;
            $this->tools = $tools;
            $this->jobManager = $jobManager;
            $this->twoFactorAuthService = $twoFactorAuthService;
            $this->requestStack = $requestStack;
        }

        protected function getInstanceBdd()
        {
        }

    /**
     * LIST OF RULES.
     * @return RedirectResponse|Response
     */
    #[Route('/list', name: 'regle_list', defaults: ['page' => 1])]
    #[Route('/list/page-{page}', name: 'regle_list_page', requirements: ['page' => '\d+'])]
    public function ruleListAction(Request $request, int $page = 1)
    {
        try {
            $ruleName = $request->query->get('rule_name');
            $this->getInstanceBdd();
            $pager = $this->tools->getParamValue('ruleListPager') ?? 20;

            $query = $this->entityManager->getRepository(Rule::class)->findListRuleByUser($this->getUser(), $ruleName);

            // Important: hydrate as array to keep compatibility with Twig expecting alias keys (solution_source, etc.)
            $query->setHydrationMode(Query::HYDRATE_ARRAY);

            // Build pagination around the Doctrine Query (keeps search filters and ordering intact)
            $adapter = new QueryAdapter($query, false, true);
            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage($pager); // Page size (configurable)
            $pagerfanta->setCurrentPage($page); // Current page from route

            $entities = iterator_to_array($pagerfanta->getCurrentPageResults());
            $nbResults = $pagerfanta->getNbResults();

            return $this->render('Rule/list.html.twig', [
                'nb_rule'  => $nbResults,
                'entities' => $entities,
                'pager'    => $pagerfanta,
            ]);

        } catch (\Pagerfanta\Exception\NotValidCurrentPageException $e) {
            throw $this->createNotFoundException("Cette page n'existe pas.");
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Error : ' . $e->getMessage());
        }
    }

    /**
     * DELETING A RULE.
     */
    #[Route('/delete/{id}', name: 'regle_delete', methods: ['GET', 'POST'])]
    public function deleteRule(Request $request, $id): RedirectResponse
    {
        $session = $request->getSession();

        // First, checking that the rule has document not deleted
        $docClose = $this->entityManager->getRepository(Document::class)->findOneBy(['rule' => $id, 'deleted' => 0]);

        // Return to the rule detail if a related non-deleted document exists
        if (!empty($docClose)) {
            $session->set('error', [$this->translator->trans('error.rule.delete_document')]);
            return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
        }

        // Then, checking that the rule has no document open or in error
        $docErrorOpen = $this->entityManager->getRepository(Document::class)->findOneBy(['rule' => $id, 'deleted' => 0, 'globalStatus' => ['Open', 'Error']]);

        // Return to rule detail if a document is open or in error
        if (!empty($docErrorOpen)) {
            $session->set('error', [$this->translator->trans('error.rule.delete_document_error_open')]);
            return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
        }

        // Checking if the rule is linked to another one
        $ruleRelationships = $this->entityManager->getRepository(RuleRelationShip::class)->findBy(['fieldId' => $id]);

        // If relationships exist and are not deleted, abort the deletion
        if (!empty($ruleRelationships)) {
            foreach ($ruleRelationships as $ruleRelationship) {
                if (empty($ruleRelationship->getDeleted())) {
                    $session->set('error', [$this->translator->trans('error.rule.delete_relationship_exists') . $ruleRelationship->getRule()]);
                    return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
                }
            }
        }

        // Build rule filter depending on user rights
        if ($this->getUser()->isAdmin()) {
            $list_fields_sql = ['id' => $id];
        } else {
            $list_fields_sql = [
                'id' => $id,
                'createdBy' => $this->getUser()->getId(),
            ];
        }

        // Only proceed if rule ID is valid
        if (isset($id)) {
            // Retrieve rule entity
            $rule = $this->entityManager->getRepository(Rule::class)->findOneBy($list_fields_sql);

            // If rule does not exist or doesn't belong to user, redirect
            if (empty($rule)) {
                return $this->redirect($this->generateUrl('regle_list'));
            }

            $this->getInstanceBdd();

            // Remove rule relationships (set as deleted)
            $ruleRelationships = $this->entityManager
                ->getRepository(RuleRelationShip::class)
                ->findBy(['rule' => $id]);

            if (!empty($ruleRelationships)) {
                foreach ($ruleRelationships as $ruleRelationship) {
                    $ruleRelationship->setDeleted(1);
                    $this->entityManager->persist($ruleRelationship);
                }
            }

            // Clean up related entities
            $this->ruleCleanupService->removeThisRuleItsRuleGroup($rule);
            $this->ruleCleanupService->deleteWorflowsFromThisRule($rule->getId());

            // Soft-delete the rule itself
            $rule->setDeleted(1);
            $rule->setActive(0);
            $this->entityManager->persist($rule);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('regle_list'));
        }
        return $this->redirect($this->generateUrl('regle_list'));
    }

    /**
     * DISPLAY FLUX.
     */
    #[Route('/displayflux/{id}', name: 'regle_displayflux', methods: ['GET'])]
    public function displayFlux($id): RedirectResponse
    {
        $rule = $this->entityManager
            ->getRepository(Rule::class)
            ->findOneBy([
                'id' => $id,
            ]);

        $this->sessionService->setFluxFilterWhere(['rule' => $rule->getName()]);
        $this->sessionService->setFluxFilterRuleName($rule->getName());

        return $this->redirect($this->generateUrl('document_list_page'));
    }

    /**
     * DUPLICATE RULE
     */
    #[Route('/duplic_rule/{id}', name: 'duplic_rule')]
    public function duplicateRule($id, Request $request, TranslatorInterface $translator)
    {
        try {
            $rule = $this->entityManager->getRepository(Rule::class)->findOneBy(['id' => $id]);
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
                    $this->addFlash('rule.duplicate.success', $translator->trans('duplicate_rule.success_duplicate'));
                }

                $this->ruleDuplicateService->duplicateWorkflows($id, $newRule);
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

    /**
     * ACTIVE RULE.
     */
    #[Route('/update/{id}', name: 'regle_update', methods: ['GET','POST'])]
    public function ruleUpdActive($id)
    {
        try {
            $this->getInstanceBdd();
            $rule = $this->entityManager->getRepository(Rule::class)->find($id);

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
     * MANUALLY EXECUTE RULE
     */
    #[Route('/exec/{id}', name: 'regle_exec', methods: ['GET','POST'])]
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
     * @param $id
     * EXECUTE BY ID
     * Function to take source document ids optain in a form and reading them and them only.
     */
    #[Route('/executebyid/{id}', name: 'run_by_id', methods: ['GET', 'POST'])]
    public function execRuleById($id, Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('id', TextareaType::class)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $documentIdString = $form->get('id')->getData();
            // replace line breaks with commas
            $documentIdString = str_replace(["\r\n", "\r", "\n"], ',', $documentIdString);
            // remove spaces
            $documentIdString = str_replace(' ', '', $documentIdString);
            //rtrim and ltrim to remove any leading or trailing commas
            $documentIdString = trim($documentIdString, ',');
            //rtrim and ltrim to remove any leading or trailing spaces or line break
            $documentIdString = trim($documentIdString, " \r\n\t");
            // We will get to the runrecord commmand using the ids from the form.
            $this->ruleExecAction($id, $documentIdString);
        }
        return $this->render('Rule/byIdForm.html.twig', [
            'formIdBatch' => $form->createView()
        ]);
    }

    /**
     * CANCEL ALL TRANSFERS FOR ONE RULE.
     */
    #[Route('/view/cancel/documents/{id}', name: 'rule_cancel_all_transfers', methods: ['GET','POST'])]
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
     */
    #[Route('/view/delete/documents/{id}', name: 'rule_delete_all_transfers', methods: ['GET','POST'])]
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
     * EDIT THE PARAMETERS OF A RULE.
     * @return JsonResponse|Response
     */
    #[Route('/update/params/{id}', name: 'path_fiche_params_update', methods: ['POST'])]
    public function ruleUpdParams($id, TranslatorInterface $translator)
    {
        try {
            $this->getInstanceBdd();
            if (isset($_POST['params']) && is_array($_POST['params'])) {
                foreach ($_POST['params'] as $p) {
                    $param = $this->entityManager->getRepository(RuleParam::class)->findOneBy(['rule' => $id, 'name' => $p['name']]);

                    // In a few case, the parameter could not exist, in this case we create it
                    if (empty($param)) {
                        // Create rule entity
                        $rule = $this->entityManager->getRepository(Rule::class)->findOneBy(['id' => $id]);
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
     * SIMULATE READING TO RETURN THE NUMBER OF POTENTIAL TRANSFERS.
     */
    #[Route('/simule/{id}', name: 'path_fiche_params_simulate', methods: ['GET'])]
    public function ruleSimulateTransfers(Rule $rule): Response
    {
        try {
            $this->getInstanceBdd();
            $count = $this->ruleSimulationService->simulateCount($rule);

            return new Response((string) $count);
        } catch (Exception $e) {
            $errorMessage = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
            return new Response(json_encode(['error' => $errorMessage]));
        }
    }

    /**
     * EDIT MODE FOR A RULE.
     */
    #[Route('/edit/{id}', name: 'regle_edit')]
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
            // Return to the rule detail view if a document is open or in error
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
            // If a session already exists, remove it
            if ($this->sessionService->isParamRuleExist($key)) {
                $this->sessionService->removeParamRule($key);
            }

            // Prepare session data
            if (!empty($rule->getDeleted())) {
                $session->set('error', [$this->translator->trans('error.rule.edit_rule_deleted')]);
                return $this->redirect($this->generateUrl('regle_open', ['id' => $rule->getId()]));
            }

            // Build session data
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

            // Rule parameters
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

            // Reload ----------------
            $ruleFields = $rule->getFields();

            // Get source module fields for field ID / label association (ticket 548)
            $solution_source_nom = $this->sessionService->getParamRuleSourceSolution($key);
            $solution_source = $this->solutionManager->get($solution_source_nom);

            $login = $solution_source->login($this->decrypt_params($this->sessionService->getParamRuleSource($key)));
            if (empty($solution_source->connexion_valide)) {
                throw new Exception('failed to login to the source application .' . (!empty($login['error']) ? $login['error'] : ''));
            }

            // SOURCE ----- Retrieve the list of source fields
            $sourceModule = $rule->getModuleSource();
            $sourceFieldsInfo = $solution_source->get_module_fields($sourceModule);

            // Fields and formulas of a rule
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
                                throw new Exception('failed to get the field ' . $field_source);
                            }
                        }
                        $fields[] = $array;
                    }
                }
                $this->sessionService->setParamRuleReloadFields($key, $fields);
            }

            // Rule relationships
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

            // Rule filters
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

            // Reload complete, redirect to step three
            return $this->redirect($this->generateUrl('regle_stepthree', ['id' => $rule->getId()]));
        } catch (Exception $e) {
            $this->sessionService->setCreateRuleError($key, $this->translator->trans('error.rule.update') . ' ' . $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine());
            $session->set('error', [$this->translator->trans('error.rule.update') . ' ' . $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine()]);

            return $this->redirect($this->generateUrl('regle_open', ['id' => $rule->getId()]));
        }
    }

    /**
     * DETAIL RULE.
     * @throws Exception
     */
    #[Route('/view/{id}', name: 'regle_open', methods: ['GET'])]
    public function ruleOpenAction($id, RuleRepository $ruleRepository, VariableRepository $variableRepository): Response
    {
        if ($this->getUser()->isAdmin()) {
            $list_fields_sql = ['id' => $id];
        } else {
            $list_fields_sql =
                ['id' => $id,
                    'createdBy' => $this->getUser()->getId(),
                ];
        }
        
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

        $varNamesSet = [];
        $pattern = '/\{?(mdwvar_[A-Za-z0-9_]+)\}?/';

        if ($Fields) {
            foreach ($Fields as $f) {
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
        }

        $variables = [];
        $varNames = array_keys($varNamesSet);

        if (!empty($varNames)) {
            // Doctrine gère IN(:names) avec un tableau
            $variables = $variableRepository->createQueryBuilder('v')
                ->where('v.name IN (:names)')
                ->setParameter('names', $varNames)
                ->orderBy('v.name', 'ASC')
                ->getQuery()
                ->getResult();
        }

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
            'variables' => $variables,
        ]);
    }

    /**
     * @return JsonResponse|Response
     * CREATION - STEP ONE - CONNEXION : jQuery ajax.
     */
    #[Route('/inputs', name: 'regle_inputs', methods: ['POST'], options: ['expose' => true])]
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

                        //Connector create DON'T REMOVE
                        return $this->render('Ajax/result_liste_inputs.html.twig', [
                            'form' => $form->createView(),
                            'parent' => $parent,
                        ]);
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

                        // before checking the number with get fields login, we need to check the difference between the number of fields login and the number of non required fields for the solution
                        $nonRequiredFields = $this->getNonRequiredFields();

                        // Vérification du nombre de champs
                        if (isset($param) && (count($param) == count($solution->getFieldsLogin()) || count($param) == count($solution->getFieldsLogin()) - count($nonRequiredFields))) {
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
     * @return JsonResponse
     */
    #[Route('/inputs/name_unique/', name: 'regle_inputs_name_unique', methods: ['POST'], options: ['expose' => true])]
    public function ruleNameUniq(Request $request): JsonResponse
    {
        $key = $this->sessionService->getParamRuleLastKey();

        if ('POST' == $request->getMethod()) {
            $this->getInstanceBdd();

            $rule = $this->entityManager->getRepository(Rule::class)->findOneBy(['name' => $request->request->get('name')]);

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
     * PAGE CREATE (Step 1 + Step 2 + Step 4)
     */
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
        ]);
    }



    #[Route('/create/list-connectors', name: 'regle_list_connectors', methods: ['GET'])]
    public function listConnectors(Request $request): Response
    {
        $solutionId = $request->query->getInt('solution_id');
        $solution = $this->entityManager->getRepository(Solution::class)->find($solutionId);
        $connectors = $this->entityManager->getRepository(Connector::class)->findActiveBySolution($solution);

        return $this->render('Rule/create/ajax_step1/_options_connectors.html.twig', [
            'connectors'   => $connectors,
            'solutionSlug' => strtolower($solution->getName()),
        ]);
    }

   #[Route('/create/list-module', name: 'regle_list_module', methods: ['GET'])]
    public function listModules(Request $request): Response
    {
        $connectorId = $request->query->getInt('id');
        $type        = $request->query->get('type', 'source');

        $connector = $this->entityManager->getRepository(Connector::class)->find($connectorId);
        if (!$connector || !$connector->getSolution()) {
            return $this->render('Rule/create/ajax_step1/_options_modules.html.twig', [
                'modules'       => [],
                'modulesFields' => [],
            ]);
        }

        $solutionName = $connector->getSolution()->getName();
        $solution     = $this->solutionManager->get(strtolower($solutionName));

        $connectorParams = $this->entityManager
            ->getRepository(ConnectorParam::class)
            ->findBy(['connector' => $connector]);

        $params = [];
        foreach ($connectorParams as $p) {
            $params[$p->getName()] = $p->getValue();
        }

        try {
            $solution->login($params);
        } catch (\Throwable $e) {
            // TODO: message d’erreur
            return $this->render('Rule/create/ajax_step1/_options_modules.html.twig', [
                'modules'       => [],
                'modulesFields' => [],
            ]);
        }

        $direction = ($type === 'cible') ? 'target' : 'source';
        $modules   = $solution->get_modules($direction) ?? [];
        $modulesFields = [];

        foreach ($modules as $moduleName => $moduleLabel) {
            try {
                $fields = $solution->get_module_fields($moduleName, $direction);

                if (!is_array($fields)) {
                    $fields = [];
                }
                $simpleFields = [];
                foreach ($fields as $fieldName => $def) {
                    $simpleFields[$fieldName] = $def['label'] ?? $fieldName;
                }

                $modulesFields[$moduleName] = $simpleFields;
            } catch (\Throwable $e) {
                $this->logger->error('get_module_fields failed for module '.$moduleName.' : '.$e->getMessage());
                $modulesFields[$moduleName] = [];
            }
        }

        return $this->render('Rule/create/ajax_step1/_options_modules.html.twig', [
            'modules'       => $modules,
            'modulesFields' => $modulesFields,
        ]);
    }


    #[Route('/create/filters', name: 'regle_step_filters', methods: ['GET'])]
    public function ruleStepFilters(Request $request): Response
    {
        $ruleKey = $this->sessionService->getParamRuleLastKey();
        $fieldsGrouped = [
            'Source Fields'   => [],
            'Target Fields'   => [],
            'Relation Fields' => [],
        ];

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

        $filters = [];

        if (!$ruleKey || !$this->sessionService->isParamRuleExist($ruleKey)) {
            return $this->render('Rule/create/ajax_step4/_options_fields_filters.html.twig', [
                'fieldsGrouped' => $fieldsGrouped,
                'operators'     => $operators,
                'filters'       => $filters,
                'ruleKey'       => $ruleKey,
            ]);
        }

        // 1) Récupérer ce que le JS envoie (solutions + modules)
        $sourceSolutionName = $request->query->get('src_solution_id')
            ?: $this->sessionService->getParamRuleSourceSolution($ruleKey);

        $targetSolutionName = $request->query->get('tgt_solution_id')
            ?: $this->sessionService->getParamRuleCibleSolution($ruleKey);

        $sourceModule = $request->query->get('src_module')
            ?: $this->sessionService->getParamRuleSourceModule($ruleKey);

        $targetModule = $request->query->get('tgt_module')
            ?: $this->sessionService->getParamRuleCibleModule($ruleKey);

        // 2) Essayer d'abord la session
        $sourceFields = $this->sessionService->getParamRuleSourceFields($ruleKey) ?? [];
        $targetFields = $this->sessionService->getParamRuleTargetFields($ruleKey) ?? [];

        // 3) Si c’est vide, on reconstruit comme dans l’ancienne step 3
        if ((empty($sourceFields) || empty($targetFields))
            && $sourceSolutionName && $targetSolutionName && $sourceModule && $targetModule
        ) {
            try {
                $solutionSource = $this->solutionManager->get($sourceSolutionName);
                $solutionTarget = $this->solutionManager->get($targetSolutionName);

                $paramsSource = $this->decrypt_params(
                    $this->sessionService->getParamRuleSource($ruleKey)
                );
                $paramsTarget = $this->decrypt_params(
                    $this->sessionService->getParamRuleCible($ruleKey)
                );

                $solutionSource->login($paramsSource);
                $solutionTarget->login($paramsTarget);

                // On protège les appels pour ne pas se reprendre le TypeError
                try {
                    $sourceFieldsTmp = $solutionSource->get_module_fields($sourceModule, 'source');
                } catch (\Throwable $e) {
                    $sourceFieldsTmp = [];
                    $this->logger->error('get_module_fields source failed: '.$e->getMessage());
                }

                try {
                    $targetFieldsTmp = $solutionTarget->get_module_fields($targetModule, 'target');
                } catch (\Throwable $e) {
                    $targetFieldsTmp = [];
                    $this->logger->error('get_module_fields target failed: '.$e->getMessage());
                }

                if (is_array($sourceFieldsTmp)) {
                    $sourceFields = $sourceFieldsTmp;
                    $this->sessionService->setParamRuleSourceFields($ruleKey, $sourceFieldsTmp);
                }

                if (is_array($targetFieldsTmp)) {
                    $targetFields = $targetFieldsTmp;
                    $this->sessionService->setParamRuleTargetFields($ruleKey, $targetFieldsTmp);
                }

            } catch (\Throwable $e) {
                $this->logger->error('Error rebuilding fields for filters: '.$e->getMessage());
            }
        }

        if (!empty($sourceFields)) {
            foreach ($sourceFields as $key => $value) {
                $label = $value['label'] ?? $key;
                $fieldsGrouped['Source Fields'][$key] = $label;

                if (!empty($value['relate'])) {
                    $fieldsGrouped['Relation Fields'][$key] = $label;
                }
            }
        }

        if (!empty($targetFields)) {
            foreach ($targetFields as $key => $value) {
                $fieldsGrouped['Target Fields'][$key] = $value['label'] ?? $key;
            }
        }

        // 5) Filtres existants (édition)
        $filters = $this->entityManager
            ->getRepository(\App\Entity\RuleFilter::class)
            ->findBy(['rule' => $ruleKey]);

        return $this->render('Rule/create/ajax_step4/_options_fields_filters.html.twig', [
            'fieldsGrouped' => $fieldsGrouped,
            'operators'     => $operators,
            'filters'       => $filters,
            'ruleKey'       => $ruleKey,
        ]);
    }

    #[Route('/create/list-duplicate-fields', name: 'regle_list_duplicate_fields', methods: ['GET'])]
    public function listDuplicateFields(Request $request): Response
    {
        $connectorId = $request->query->getInt('connector_id');
        $module      = trim((string) $request->query->get('module', ''));

        if ($connectorId <= 0 || $module === '') {
            return $this->render('Rule/create/ajax_step3/_options_duplicate_fields.html.twig', [
                'duplicates' => [],
            ]);
        }
        $connector = $this->entityManager->getRepository(Connector::class)->find($connectorId);
        if (!$connector || !$connector->getSolution()) {
            return $this->render('Rule/create/ajax_step3/_options_duplicate_fields.html.twig', [
                'duplicates' => [],
            ]);
        }

        $solutionName = strtolower($connector->getSolution()->getName());
        $solution     = $this->solutionManager->get($solutionName);
        $duplicates = $solution->getFieldsDuplicate($module) ?? [];

        return $this->render('Rule/create/ajax_step3/_options_duplicate_fields.html.twig', [
            'duplicates' => $duplicates,
        ]);
    }

    /**
     * CREATION - STEP THREE - SIMULATION DES DONNEES.
     *
     * @return Response
     */
    #[Route('/create/step3/simulation/', name: 'regle_simulation', methods: ['POST'])]
    public function ruleSimulation(Request $request): Response
    {
        $ruleKey = $this->sessionService->getParamRuleLastKey();

        if ('POST' == $request->getMethod() && $this->sessionService->isParamRuleExist($ruleKey)) {
            // retourne un tableau prêt à l'emploi
            $target = $this->createListeParamsRule(
                $request->request->get('champs'),   // Fields
                $request->request->get('formules'), // Formula
                ''                                  // Params flux
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
                    'after'  => [], // target
                    'data_source' => false,
                ]);
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
                    'module'     => $this->sessionService->getParamRuleSourceModule($ruleKey),
                    'fields'     => $sourcesfields,
                    'date_ref'   => '1970-01-01 00:00:00',  // date_ref is required for some application like Prestashop
                    'limit'      => 1,
                    'ruleParams' => $ruleParams,
                    'call_type'  => 'simulation',
                ]);
            } else {
                // Get source data
                $source = $solution_source->readData([
                    'module'     => $this->sessionService->getParamRuleSourceModule($ruleKey),
                    'fields'     => $sourcesfields,
                    'date_ref'   => '1970-01-01 00:00:00',  // date_ref is required for some application like Prestashop
                    'limit'      => 1,
                    'ruleParams' => $ruleParams,
                    'query'      => [(!empty($ruleParams['fieldId']) ? $ruleParams['fieldId'] : 'id') => $this->simulationQueryField],
                    'call_type'  => 'simulation',
                ]);

                // In case of wrong record ID input from user
                if (!empty($source['error'])) {
                    return $this->render('Rule/create/onglets/invalidrecord.html.twig');
                }
            }

            $before = [];
            $after  = [];
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
                                'formula'           => ((isset($k['formule'][0]) ? $k['formule'][0] : '')),
                                'related_rule'      => '',
                            ];

                            // Add rule id for simulation purpose when using lookup function
                            $this->documentManager->setRuleId($ruleKey);
                            // Add variables for simulation purpose
                            $variablesEntity = $this->entityManager->getRepository(Variable::class)->findAll();
                            if (!empty($variablesEntity)) {
                                foreach ($variablesEntity as $variable) {
                                    $variables[$variable->getName()] = $variable->getvalue();
                                }
                                $this->documentManager->setParam(array('variables' => $variables));
                            }
                            // Fix the document type for the simulation 
                            $this->documentManager->setDocumentType('C');
                            // Transformation
                            $response = $this->documentManager->getTransformValue($record, $target_fields);
                            if (!isset($response['message'])) {
                                $r['after'][$name_fields_target] = $this->documentManager->getTransformValue($record, $target_fields);
                            }
                            // If error during transformation, we send back the error
                            if (null == $r['after'][$name_fields_target] and !empty($response['message'])) {
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
                                'after'  => $r['after'],
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
                'after'  => $after,  // target
                'data_source' => (!empty($record) ? true : false),
                'params' => $this->sessionService->getParamRule($ruleKey),
                'simulationQueryField' => $this->simulationQueryField,
            ]);
        }
        throw $this->createNotFoundException('Error');
    }
    
    // #[Route('/create/filters', name: 'regle_step_filters', methods: ['GET'])]
    // public function ruleStepFilters(Request $request): Response
    // {
    //     die("test");
    //     $ruleKey = $this->sessionService->getParamRuleLastKey();

    //     // valeurs par défaut si rien en session
    //     $fieldsGrouped = [
    //         'Source Fields'   => [],
    //         'Target Fields'   => [],
    //         'Relation Fields' => [],
    //     ];

    //     $operators = [
    //         $this->translator->trans('filter.content')    => 'content',
    //         $this->translator->trans('filter.notcontent') => 'notcontent',
    //         $this->translator->trans('filter.begin')      => 'begin',
    //         $this->translator->trans('filter.end')        => 'end',
    //         $this->translator->trans('filter.gt')         => 'gt',
    //         $this->translator->trans('filter.lt')         => 'lt',
    //         $this->translator->trans('filter.equal')      => 'equal',
    //         $this->translator->trans('filter.different')  => 'different',
    //         $this->translator->trans('filter.gteq')       => 'gteq',
    //         $this->translator->trans('filter.lteq')       => 'lteq',
    //         $this->translator->trans('filter.in')         => 'in',
    //         $this->translator->trans('filter.notin')      => 'notin',
    //     ];

    //     $filters = [];

    //     if ($ruleKey && $this->sessionService->isParamRuleExist($ruleKey)) {

    //         $sourceFields = $this->sessionService->getParamRuleSourceFields($ruleKey) ?? [];
    //         $targetFields = $this->sessionService->getParamRuleTargetFields($ruleKey) ?? [];

    //         // on reconstruit comme dans l'ancien code
    //         $fieldsGrouped = [
    //             'Source Fields'   => [],
    //             'Target Fields'   => [],
    //             'Relation Fields' => [],
    //         ];

    //         die( $fieldsGrouped);
    //         foreach ($sourceFields as $key => $value) {
    //             $fieldsGrouped['Source Fields'][$key] = $value['label'] ?? $key;
    //             // si c’est un champ “relate”, on le met aussi dans relations
    //             if (!empty($value['relate'])) {
    //                 $fieldsGrouped['Relation Fields'][$key] = $value['label'] ?? $key;
    //             }
    //         }

    //         foreach ($targetFields as $key => $value) {
    //             $fieldsGrouped['Target Fields'][$key] = $value['label'] ?? $key;
    //         }

    //         // filtres déjà existants (édition)
    //         $filters = $this->entityManager->getRepository(\App\Entity\RuleFilter::class)->findBy(['rule' => $ruleKey]);
    //     }

    //     return $this->render('Rule/create/_filters.html.twig', [
    //         'fieldsGrouped' => $fieldsGrouped,
    //         'operators'     => $operators,
    //         'filters'       => $filters,
    //         'ruleKey'       => $ruleKey,
    //     ]);
    // }

     /**
     * Indique des informations concernant le champ envoyé en paramètre.
     */
    #[Route('/info/{type}/{field}/', name: 'path_info_field')]
    #[Route('/info', name: 'path_info_field_not_param')]
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
     */
    #[Route('/create/step3/formula/', name: 'regle_formula', methods: ['POST'])]
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
     */
    #[Route('/create/step3/formula/', name: 'regle_formula', methods: ['POST'])]
    public function ruleValidation(Request $request): JsonResponse
    {
        // On récupére l'EntityManager
        $this->getInstanceBdd();
        $this->entityManager->getConnection()->beginTransaction();
        try {
            // Decode the JSON params from the request
            $paramsRaw = $request->request->get('params');
            $decodedParams = json_decode($paramsRaw, true); // or directly use if already an array
            // Get rule id from params
            if (!empty($decodedParams)) {
                foreach ($decodedParams as $searchRuleId) {
                    if ('regleId' === $searchRuleId['name']) {
                        $ruleKey = $searchRuleId['value'];
                        break;
                    }
                }
            }
            $formattedParams = [];
            
            foreach ($decodedParams as $item) {
                if (isset($item['name']) && isset($item['value'])) {
                    $formattedParams[$item['name']] = is_numeric($item['value']) ? (int)$item['value'] : $item['value'];
                }
            }

            $params = $formattedParams;
            

            // retourne un tableau prêt à l'emploi
            $tab_new_rule = $this->createListeParamsRule(
                $request->request->get('champs'), // Fields
                $request->request->get('formules'), // Formula
                $params // Decoded params
            );
            unset($tab_new_rule['params']['regleId']); // delete id regle for gestion session

            $requestAll = $request->request->all();

            $duplicate = $requestAll['duplicate'] ?? [];

            // fields relate
            if (!empty($duplicate)) {
                // fix : Put the duplicate fields values in the old $tab_new_rule array
                $duplicateArray = implode(';', $duplicate);
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

            // unset description from param['RuleParam'] if it not a new rule
            if (!$this->sessionService->isParamRuleLastVersionIdEmpty($ruleKey)) {
                unset($param['RuleParam']['description']);
            }

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

            // find in the database the id of the solution with the name dynamicsbusiness
            $solutionDynamicsBusiness = $this->entityManager
                ->getRepository(Solution::class)
                ->findOneBy([
                    'name' => 'dynamicsbusiness',
                ]);



                if(!empty($solutionDynamicsBusiness) && $solutionDynamicsBusiness->getConnector()->count() > 0)
                {
                    // if $oneRule connector source get name == dynamicsbusiness then set param parentmoduleid
                    if ($oneRule->getConnectorSource()->getSolution()->getId() == $solutionDynamicsBusiness->getId()) {
                        // Check if parentmoduleid parameter already exists in the database
                        $existingParam = $this->entityManager->getRepository(RuleParam::class)
                            ->findOneBy([
                                'rule' => $oneRule->getId(),
                                'name' => 'parentmoduleid'
                            ]);

                        if (!$existingParam) {
                            $moduleSource = $oneRule->getModuleSource();

                            // destructure $moduleSource to get $companyId and $apiModuleName
                            list($companyId, $apiModuleName) = explode('_', $moduleSource, 2);

                            $p['parentmoduleid'] = $companyId;
                        }
                    }
                    
                    
                    // if $oneRule connector target get name == dynamicsbusiness then set param parentmoduleid
                    if ($oneRule->getConnectorTarget()->getSolution()->getId() == $solutionDynamicsBusiness->getId()) {
                        // Check if parentmoduleid parameter already exists in the database
                        $existingParam = $this->entityManager->getRepository(RuleParam::class)
                            ->findOneBy([
                                'rule' => $oneRule->getId(),
                                'name' => 'parentmoduleid'
                            ]);

                        if (!$existingParam) {
                            $moduleTarget = $oneRule->getModuleTarget();

                            // destructure $moduleSource to get $companyId and $apiModuleName
                            list($companyId, $apiModuleName) = explode('_', $moduleTarget, 2);

                            $p['parentmoduleid'] = $companyId;
                        }
                    }
                } // end if dynamics business not null
                    
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
     * from the id of the rule, we get the name of the rule
     */
    #[Route('/get-rule-name/{id}', name: 'get_rule_name', methods: ['GET'])]
    public function getRuleNameById($id): Response
    {
        $rule = $this->entityManager->getRepository(Rule::class)->find($id);
        return new Response($rule->getName());
    }

    /**
     * from the formula, we get the first part of the formula
     */
    #[Route('/get-first-part-of-lookup-formula/{formula}', name: 'get_first_part_of_lookup_formula')]
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
     */
    #[Route('/get-second-part-of-lookup-formula/{formula}', name: 'get_second_part_of_lookup_formula')]
    public function getSecondPartOfLookupFormula($formula): Response
    {
        // Extract everything after the rule ID until the end
        if (preg_match('/",\s*(.+)\)/', $formula, $matches)) {
            return new Response(', ' . $matches[1] . ')');
        }
        return new Response('');
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

    private function getNonRequiredFields()
    {
        $yamlFile = __DIR__ . '/../../assets/connector-non-required-fields.yaml';
        $yaml = Yaml::parseFile($yamlFile);
        return $yaml['non-required-fields'];
    }

    // /**
    //  * LISTE DES TEMPLATES.
    //  */
    // #[Route('/list/template', name: 'regle_template')]
    // public function listTemplateAction(): Response
    // {
    //     $key = $this->sessionService->getParamRuleLastKey();
    //     $solutionSourceName = $this->sessionService->getParamRuleSourceSolution($key);
    //     $solutionTargetName = $this->sessionService->getParamRuleCibleSolution($key);
    //     $templates = $this->template->getTemplates($solutionSourceName, $solutionTargetName);
    //     if (!empty($templates)) {
    //         $rows = '';
    //         foreach ($templates as $t) {
    //             $rows .= '<tr>
    //                     <td>
    //                         <span data-id="'.$t['name'].'">
    //                             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-card-list" viewBox="0 0 16 16">
    //                             <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z"/>
    //                             <path d="M5 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 5 8zm0-2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0 5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-1-5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zM4 8a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm0 2.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/>
    //                             </svg>
    //                         </span>
    //                     </td>
    //                     <td>'.$t['name'].'</td>
    //                     <td>'.$t['description'].'</td>
    //                 </tr>';
    //             }

    //             return new Response('<table class="table table-striped">
    //             <thead>
    //                 <tr>
    //                     <th>#</th>
    //                     <th>'.$this->translator->trans('animate.choice.name').'</th>
    //                     <th>'.$this->translator->trans('animate.choice.description').'</th>
    //                 </tr>
    //             </thead>
    //             <tbody>
	// 			'.$rows.'
    //             </tbody>
    //         </table>');
    //     }
    // }    

    /* ******************************************************
        * METHODES PRATIQUES
        ****************************************************** */

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
                $tab['params'][$k] = $p;
            }
        }

        return $tab;
    }

    #[Route('/rule/update_description', name: 'update_rule_description', methods: ['POST'])]
    public function updateDescription(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Extract form payload (fallback to empty string if missing)
        $ruleId = $request->request->get('ruleId');
        $description = (string) $request->request->get('description', '');
        $rule = $em->getRepository(Rule::class)->find($ruleId);
        if (!$rule) {
            return new JsonResponse(['error' => 'Rule not found'], Response::HTTP_NOT_FOUND);
        }

        // Fetch (or create) the "description" RuleParam for this rule
        $param = $em->getRepository(RuleParam::class)->findOneBy([
            'rule' => $rule,
            'name' => 'description',
        ]);

        if (!$param) {
            // If not found, instantiate and link it to the rule
            $param = new RuleParam();
            $param->setRule($rule);
            $param->setName('description');
            $em->persist($param);
        }
        if ($param->getValue() === $description) {
            return new JsonResponse(['ok' => true, 'unchanged' => true], Response::HTTP_OK);
        }

        // Update and flush changes
        $param->setValue($description);
        $em->flush();

        // Return a simple success JSON payload
        return new JsonResponse(['ok' => true], Response::HTTP_OK);
    }

    #[Route('/rule/update_name', name: 'update_rule_name', methods: ['POST'])]
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

    #[Route('/check-rule-name', name: 'check_rule_name', methods: ['GET'])]
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

    #[Route('/rulefield/{id}/comment', name: 'rulefield_update_comment', methods: ['POST'])]
    public function updateComment(RuleField $ruleField, Request $request, EntityManagerInterface $entityManager): Response
    {
        $comment = $request->request->get('comment');
        
        $ruleField->setComment($comment);
        $entityManager->persist($ruleField);
        $entityManager->flush();

        return new Response('Update successful', Response::HTTP_OK);
    }

    #[Route('/get-rules-for-lookup', name: 'get_rules_for_lookup', methods: ['GET'])]
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

    #[Route('/get-fields-for-rule', name: 'rule_get_fields_for_rule', methods: ['GET'])]
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

    #[Route('/get-lookup-rule-from-field-name', name: 'get_lookup_rule_from_field_name', methods: ['GET'])]
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
     */
    #[Route('/api/field-info/{type}/{field}/', name: 'api_field_info', methods: ['GET'])]
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