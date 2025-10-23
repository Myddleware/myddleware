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
use App\Entity\RuleGroup;
use App\Entity\RuleParam;
use App\Entity\RuleFilter;
use Pagerfanta\Pagerfanta;
use App\Form\ConnectorType;
use App\Manager\JobManager;
use App\Manager\HomeManager;
use App\Manager\RuleManager;
use Doctrine\ORM\Mapping\Id;
use Psr\Log\LoggerInterface;
use App\Manager\ToolsManager;
use Doctrine\DBAL\Connection;
use App\Entity\ConnectorParam;
use App\Entity\RuleParamAudit;
use App\Entity\WorkflowAction;
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
use Pagerfanta\Adapter\ArrayAdapter;
use App\Form\Type\RelationFilterType;
use App\Service\TwoFactorAuthService;
use App\Repository\DocumentRepository;
use App\Repository\VariableRepository;
use App\Repository\RuleFieldRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use App\Service\RuleDuplicateService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
        private ParameterBagInterface $params;
        private EntityManagerInterface $entityManager;
        private HomeManager $home;
        private ToolsManager $tools;
        private TranslatorInterface $translator;
        private AuthorizationCheckerInterface $authorizationChecker;
        private JobManager $jobManager;
        private LoggerInterface $logger;
        private TemplateManager $template;
        private RuleCleanupService $ruleCleanupService;
        private RuleDuplicateService $ruleDuplicateService;
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
        private RuleFieldRepository $ruleFieldRepository;

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
            AuthorizationCheckerInterface $authorizationChecker,
            HomeManager $home,
            ToolsManager $tools,
            JobManager $jobManager,
            TemplateManager $template,
            ParameterBagInterface $params,
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
            $this->ruleRepository = $ruleRepository;
            $this->ruleFieldRepository = $ruleFieldRepository;
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
                    $this->addFlash('rule.duplicate.success', $translator->trans('rule.success_edit_params'));
                }
            }
            return new Response(1);
        } catch (Exception $e) {
            return new JsonResponse($e->getMessage());
        }
    }

}