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
use App\Entity\Document;
use App\Entity\Solution;
use App\Entity\Workflow;
use App\Entity\Connector;
use App\Entity\Functions;
use App\Entity\RuleAudit;
use App\Entity\RuleField;
use App\Entity\RuleParam;
use App\Entity\RuleFilter;
use Pagerfanta\Pagerfanta;
use App\Entity\WorkflowLog;
use App\Form\ConnectorType;
use App\Manager\JobManager;
use App\Manager\HomeManager;
use App\Manager\RuleManager;
use Doctrine\ORM\Mapping\Id;
use Psr\Log\LoggerInterface;
use App\Entity\WorkflowAudit;
use App\Manager\ToolsManager;
use Doctrine\DBAL\Connection;
use App\Entity\ConnectorParam;
use App\Entity\RuleParamAudit;
use App\Entity\WorkflowAction;
use App\Form\Type\WorkflowType;
use App\Manager\FormulaManager;
use App\Service\SessionService;
use App\Entity\RuleRelationShip;
use App\Manager\DocumentManager;
use App\Manager\SolutionManager;
use App\Manager\TemplateManager;
use App\Repository\JobRepository;
use App\Repository\RuleRepository;
use App\Form\DuplicateRuleFormType;
use App\Repository\ConfigRepository;
use Illuminate\Encryption\Encrypter;
use Pagerfanta\Adapter\ArrayAdapter;
use App\Form\Type\RelationFilterType;
use App\Repository\DocumentRepository;
use App\Repository\WorkflowRepository;
use App\Repository\WorkflowLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


/**
 * @Route("/workflow")
 */
class WorkflowController extends AbstractController
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
    private WorkflowLogRepository $workflowLogRepository;
    private ConfigRepository $configRepository;


    protected Connection $connection;
    // To allow sending a specific record ID to rule simulation
    protected $simulationQueryField;

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
        WorkflowLogRepository $workflowLogRepository,
        ParameterBagInterface $params,
        ConfigRepository $configRepository
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
        $this->workflowLogRepository = $workflowLogRepository;
        $this->configRepository = $configRepository;
    }

    protected function getInstanceBdd() {}


    /* ******************************************************
         * RULE
         ****************************************************** */

/**
     * LISTE DES WORKFLOWs.
     *
     * @return RedirectResponse|Response
     */
    #[Route('/list', name: 'workflow_list', defaults: ['page' => 1])]
    #[Route('/list/page-{page}', name: 'workflow_list_page', requirements: ['page' => '\d+'])]
    public function WorkflowListAction(Request $request, int $page = 1)
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        try {
            
            // Récupérer les filtres depuis la requête
            $workflowName = $request->query->get('workflow_name');
            $ruleName = $request->query->get('rule_name');

            // Utilisation de findBy pour récupérer les workflows
            $criteria = ['deleted' => 0];
            $orderBy = ['order' => 'ASC'];
            $workflows = $this->entityManager->getRepository(Workflow::class)->findBy($criteria, $orderBy);

            if ($workflowName) {
                $workflows = array_filter($workflows, function($workflow) use ($workflowName) {
                    return stripos($workflow->getName(), $workflowName) !== false;
                });
            }

            if ($ruleName) {
                $workflows = array_filter($workflows, function($workflow) use ($ruleName) {
                    return stripos($workflow->getRule()->getName(), $ruleName) !== false;
                });
            }

            // Pagination avec ArrayAdapter car findBy retourne un tableau
            $adapter = new ArrayAdapter($workflows);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage(15);
            $pager->setCurrentPage($page);

            // Si la requête est AJAX, rendre uniquement la table des workflows
            if ($request->isXmlHttpRequest()) {
                return $this->render('Workflow/_workflow_table.html.twig', [
                    'entities' => $pager->getCurrentPageResults(),
                    'pager' => $pager,
                ]);
            }

            // Si ce n'est pas une requête AJAX, rendre la page complète
            return $this->render(
                'Workflow/list.html.twig',
                [
                    'entities' => $pager->getCurrentPageResults(),
                    'nb_workflow' => $pager->getNbResults(),
                    'pager_workflow_list' => $pager,
                ]
            );
        } catch (Exception $e) {
            throw $this->createNotFoundException('Erreur : ' . $e->getMessage());
        }
    }


    // public function to delet the workflow by id (set deleted to 1)
    /**
     * @Route("/delete/{id}", name="workflow_delete", methods={"POST", "DELETE"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function WorkflowDeleteAction(string $id, Request $request)
    {

        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        try {


            $em = $this->entityManager;
            $workflowSearchResult = $em->getRepository(Workflow::class)->findBy(['id' => $id, 'deleted' => 0]);
            $workflow = $workflowSearchResult[0];


            if ($workflow) {
                $this->saveWorkflowAudit($workflow->getId());
                $workflow->setDeleted(1);
                $em->persist($workflow);
                $em->flush();
                $this->addFlash('success', 'Workflow deleted successfully');
            } else {
                $this->addFlash('error', 'Workflow not found');
            }

            return $this->redirectToRoute('workflow_list');
        } catch (Exception $e) {
            throw $this->createNotFoundException('Error : ' . $e);
        }
    }

    // public function to save the workflowAudit to the database
    public function saveWorkflowAudit($workflowId)
    {

        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        $em = $this->entityManager;
        $workflowArray = $em->getRepository(Workflow::class)->findBy(['id' => $workflowId, 'deleted' => 0]);
        $workflow = $workflowArray[0];

        // get all the actions of the workflow
        $actions = $workflow->getWorkflowActions();

        $actionsArray = array_map(function ($action) {
            return [
                'id' => $action->getId(),
                'workflow' => $action->getWorkflow()->getId(),
                'dateCreated' => $action->getDateCreated()->format('Y-m-d H:i:s'),
                'dateModified' => $action->getDateModified()->format('Y-m-d H:i:s'),
                'createdBy' => $action->getCreatedBy()->getUsername(),
                'modifiedBy' => $action->getModifiedBy()->getUsername(),
                'name' => $action->getName(),
                'action' => $action->getAction(),
                'description' => $action->getDescription(),
                'order' => $action->getOrder(),
                'active' => $action->getActive(),
                'deleted' => $action->getDeleted(),
                'arguments' => $action->getArguments(),
            ];
        }, $actions->toArray());

        // Encode every workflow parameters
        $workflowdata = json_encode(
            [
                'workflowName' => $workflow->getName(),
                'ruleId' => $workflow->getRule()->getId(),
                'created_by' => $workflow->getCreatedBy()->getUsername(),
                'workflowDescription' => $workflow->getDescription(),
                'condition' => $workflow->getCondition(),
                'active' => $workflow->getActive(),
                'dateCreated' => $workflow->getDateCreated()->format('Y-m-d H:i:s'),
                'dateModified' => $workflow->getDateModified()->format('Y-m-d H:i:s'),
                'actions' => $actionsArray,
            ]
        );
        // Save the workflow audit
        $oneworkflowAudit = new WorkflowAudit();
        $oneworkflowAudit->setworkflow($workflow);
        $oneworkflowAudit->setDateCreated(new \DateTime());
        $oneworkflowAudit->setData($workflowdata);
        $this->entityManager->persist($oneworkflowAudit);
        $this->entityManager->flush();
    }

    // public function to set the workflow to active or inactive
    /**
     * @Route("/active/{id}", name="workflow_active")
     */
    public function WorkflowActiveAction(string $id, Request $request)
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        try {

            
            $em = $this->entityManager;
            $workflowResult = $em->getRepository(Workflow::class)->findBy(['id' => $id, 'deleted' => 0]);
            $workflow = $workflowResult[0];


            if ($workflow) {
                $workflow->setActive($workflow->getActive() == 1 ? 0 : 1);
                $em->persist($workflow);
                $em->flush();
                $this->addFlash('success', 'Workflow updated successfully');
            } else {
                $this->addFlash('error', 'Workflow not found');
            }

            return $this->redirectToRoute('workflow_list');
        } catch (Exception $e) {
            throw $this->createNotFoundException('Error : ' . $e);
        }
    }

    /**
     * @Route("/active_show/{id}", name="workflow_active_show")
     */
    public function WorkflowActiveShowAction(string $id, Request $request)
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        try {

            
            $em = $this->entityManager;
            $workflowResult = $em->getRepository(Workflow::class)->findBy(['id' => $id, 'deleted' => 0]);
            $workflow = $workflowResult[0];

            if ($workflow) {
                $workflow->setActive($workflow->getActive() == 1 ? 0 : 1);
                $em->persist($workflow);
                $em->flush();
                $this->addFlash('success', 'Workflow updated successfully');
            } else {
                $this->addFlash('error', 'Workflow not found');
            }

            return $this->redirectToRoute('workflow_show', ['id' => $id]);
        } catch (Exception $e) {
            throw $this->createNotFoundException('Error : ' . $e);
        }
    }

    // public function to toggle the workflow to active or inactive
    #[Route('/workflow/toggle/{id}', name: 'workflow_toggle', methods: ['POST'])]
    public function toggleWorkflow(Request $request, EntityManagerInterface $em, WorkflowRepository $workflowRepository, string $id): JsonResponse
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        $workflow = $workflowRepository->find($id);

        if (!$workflow) {
            return new JsonResponse(['status' => 'error', 'message' => 'Workflow not found'], 404);
        }

        $workflow->setActive(!$workflow->getActive());
        $workflow->setDateModified(new \DateTime());

        try {
            $em->persist($workflow);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => 'Erreur lors de la sauvegarde du workflow'], 500);
        }

        return new JsonResponse(['status' => 'success', 'active' => $workflow->getActive()]);
    }

    // public function to create a new workflow from a rule
    /**
    * @Route("/new/{rule_id}", name="workflow_create_from_rule")
    */
    public function WorkflowCreateFromRuleAction(Request $request, $rule_id, TranslatorInterface $translator)
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        try {

            $em = $this->entityManager;
            $workflow = new Workflow();
            $workflow->setId(uniqid());
            // set rule to the workflow
            $rule = $em->getRepository(Rule::class)->find($rule_id);
            $workflow->setRule($rule);
            $form = $this->createForm(WorkflowType::class, $workflow, [
                'entityManager' => $em,
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                // get the workflow name
                $workflowName = $workflow->getName();
                // check if the workflow name already exists
                $workflowExists = $em->getRepository(Workflow::class)->findOneBy(['name' => $workflowName, 'deleted' => 0]);
                if ($workflowExists) {
                    $this->addFlash('error_workflow_name', $translator->trans('edit_workflow.name_already_exists'));
                    return $this->redirectToRoute('workflow_create_from_rule', ['rule_id' => $rule_id]);
                }
                $workflow->setCreatedBy($this->getUser());
                $workflow->setModifiedBy($this->getUser());
                $em->persist($workflow);
                $em->flush();

                // Save the workflow audit
                $this->saveWorkflowAudit($workflow->getId());

                $this->addFlash('success', 'Workflow created successfully');

                return $this->redirectToRoute('workflow_show', ['id' => $workflow->getId()]);
            }

            return $this->render(
                'Workflow/new.html.twig',
                [
                    'form' => $form->createView(),
                ]
            );
        } catch (Exception $e) {
            throw $this->createNotFoundException('Error : ' . $e);
        }
    }

    
    // public function to create a new workflow
    /**
     * @Route("/new", name="workflow_create")
     */
    public function WorkflowCreateAction(Request $request, TranslatorInterface $translator)
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        try {
            $rules = RuleRepository::findActiveRulesNames($this->entityManager);

            $em = $this->entityManager;
            $workflow = new Workflow();
            $workflow->setId(uniqid());
            $form = $this->createForm(WorkflowType::class, $workflow, [
                'entityManager' => $em,
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // get the workflow name
                $workflowName = $workflow->getName();
                // check if the workflow name already exists
                $workflowExists = $em->getRepository(Workflow::class)->findOneBy(['name' => $workflowName, 'deleted' => 0]);
                if ($workflowExists) {
                    $this->addFlash('error_workflow_name', $translator->trans('edit_workflow.name_already_exists'));
                    return $this->redirectToRoute('workflow_create');
                }
                $workflow->setCreatedBy($this->getUser());
                $workflow->setModifiedBy($this->getUser());
                $em->persist($workflow);
                $em->flush();

                // Save the workflow audit
                $this->saveWorkflowAudit($workflow->getId());

                $this->addFlash('success', 'Workflow created successfully');

                return $this->redirectToRoute('workflow_show', ['id' => $workflow->getId()]);
            }

            return $this->render(
                'Workflow/new.html.twig',
                [
                    'form' => $form->createView(),
                ]
            );
        } catch (Exception $e) {
            throw $this->createNotFoundException('Error : ' . $e);
        }
    }

    /**
     * @Route("/show/{id}", name="workflow_show")
     */
    public function WorkflowShowAction(string $id, Request $request): Response
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        try {
            $em = $this->entityManager;
            $workflow = $em->getRepository(Workflow::class)->findBy(['id' => $id, 'deleted' => 0]);

            if (!empty($workflow) && !empty($workflow[0])) {
                return $this->render(
                    'Workflow/_workflow_logs_table.html.twig',
                    [
                        'workflow' => $workflow[0],
                    ]
                );
            } else {
                // For direct navigation, redirect to the main workflow page
                return $this->redirectToRoute('workflow_show', ['id' => $id]);
            }
        } catch (Exception $e) {
            error_log('WorkflowShowLogs Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            if ($request->isXmlHttpRequest()) {
                return $this->render(
                    'Workflow/_workflow_logs_table.html.twig',
                    [
                        'workflowLogs' => [],
                        'nb_workflow' => 0,
                        'pager' => null,
                        'workflow' => null,
                        'error' => 'Error loading logs: ' . $e->getMessage()
                    ]
                );
            } else {
                throw $this->createNotFoundException('Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * @Route("/show/{id}/logs", name="workflow_show_logs", defaults={"page"=1})
     * @Route("/show/{id}/logs/page-{page}", name="workflow_show_logs_page", requirements={"page"="\d+"})
     */
    public function WorkflowShowLogs(string $id, Request $request, int $page): Response
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        error_log("kain");

        try {
            $em = $this->entityManager;
            $workflow = $em->getRepository(Workflow::class)->findBy(['id' => $id, 'deleted' => 0]);

            if (empty($workflow)) {
                if ($request->isXmlHttpRequest()) {
                    return $this->render('Workflow/_workflow_logs_table.html.twig', [
                        'error' => 'Workflow not found'
                    ]);
                }
                $this->addFlash('error', 'Workflow not found');
                return $this->redirectToRoute('workflow_list');
            }

            $conf = $this->configRepository->findOneBy(['name' => 'search_limit']);
            $limit = $conf ? (int) $conf->getValue() : null;

            $query = $this->workflowLogRepository->findLogsByWorkflowId($id);
            if ($limit !== null && $limit > 0) {
                $query->setMaxResults($limit);
            }
            $logs = $query->getResult();

            $pager = new Pagerfanta(new ArrayAdapter($logs));
            $pager->setMaxPerPage(20);
            $pager->setCurrentPage($page);

            $workflowLogs = iterator_to_array($pager->getCurrentPageResults());
            $nb_workflow = count($logs);

            if ($request->isXmlHttpRequest()) {
                return $this->render('Workflow/_workflow_logs_table.html.twig', [
                    'workflowLogs' => $workflowLogs,
                    'nb_workflow' => $nb_workflow,
                    'pager' => $pager,
                    'workflow' => $workflow[0],
                ]);
            }

            return $this->redirectToRoute('workflow_show', ['id' => $id]);
        } catch (Exception $e) {
            error_log('WorkflowShowLogs Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            if ($request->isXmlHttpRequest()) {
                return $this->render('Workflow/_workflow_logs_table.html.twig', [
                    'workflowLogs' => [],
                    'nb_workflow' => 0,
                    'pager' => null,
                    'workflow' => null,
                    'error' => 'Error loading logs: ' . $e->getMessage()
                ]);
            }
            throw $this->createNotFoundException('Error: ' . $e->getMessage());
        }

    }


    // public function to edit a workflow
    /**
     * @Route("/edit/{id}", name="workflow_edit")
     */
    public function WorkflowEditAction(string $id, Request $request, TranslatorInterface $translator)
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        try {

            $em = $this->entityManager;
            $workflowArray = $em->getRepository(Workflow::class)->findBy(['id' => $id, 'deleted' => 0]);
            $workflow = $workflowArray[0];

            if ($workflow) {
                $form = $this->createForm(WorkflowType::class, $workflow, [
                    'entityManager' => $em,
                    'entity' => $workflow,
                ]);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    // get the workflow name
                    $workflowName = $workflow->getName();
                    // check if any OTHER workflow has this name (different ID)
                    $workflowExists = $em->getRepository(Workflow::class)->findOneBy([
                        'name' => $workflowName,
                        'deleted' => 0
                    ]);
                    if ($workflowExists && $workflowExists->getId() !== $id) {
                        $this->addFlash('error_workflow_name', $translator->trans('edit_workflow.name_already_exists'));
                        return $this->redirectToRoute('workflow_edit', ['id' => $id]);
                    }
                    $workflow->setModifiedBy($this->getUser());
                    $workflow->setDateModified(new \DateTime());
                    $em->persist($workflow);
                    $em->flush();
                    $this->addFlash('success', 'Workflow updated successfully');

                    $this->saveWorkflowAudit($workflow->getId());

                    return $this->redirectToRoute('workflow_show', ['id' => $workflow->getId()]);
                }

                return $this->render(
                    'Workflow/edit.html.twig',
                    [
                        'form' => $form->createView(),
                        'workflow' => $workflow,
                    ]
                );
            } else {
                $this->addFlash('error', 'Workflow not found');

                return $this->redirectToRoute('workflow_list');
            }
        } catch (Exception $e) {
            throw $this->createNotFoundException('Error : ' . $e);
        }
    }
}
