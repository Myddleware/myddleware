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


    protected Connection $connection;
    // To allow sending a specific record ID to rule simulation
    protected $simulationQueryField;
    private ConfigRepository $configRepository;

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
        ParameterBagInterface $params
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
    }

    protected function getInstanceBdd() {}


    /* ******************************************************
         * RULE
         ****************************************************** */

/**
     * LISTE DES WORKFLOWs.
     *
     * @return RedirectResponse|Response
     *
     * @Route("/list", name="workflow_list", defaults={"page"=1})
     * @Route("/list/page-{page}", name="workflow_list_page", requirements={"page"="\d+"})
     */
    public function WorkflowListAction(int $page = 1, Request $request)
    {
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

    /**
     * @Route("/list/workflow/{ruleId}", name="workflow_list_by_rule", defaults={"page"=1})
     * @Route("/list/workflow/{ruleId}/page-{page}", name="workflow_list_by_rule_page", requirements={"page"="\d+"})
     */
    public function WorkflowListByRuleAction(string $ruleId, int $page = 1, Request $request)
    {
        try {
            // Récupération des workflows par règle
            $workflows = $this->entityManager->getRepository(Workflow::class)->findBy(
                ['rule' => $ruleId, 'deleted' => 0],
                ['order' => 'ASC']
            );

            // Pagination avec ArrayAdapter
            $adapter = new ArrayAdapter($workflows);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage(15);
            $pager->setCurrentPage($page);

            // Rendu des workflows paginés
            return $this->render('Workflow/list.html.twig', [
                'entities' => $pager->getCurrentPageResults(),
                'nb_workflow' => $pager->getNbResults(),
                'pager_workflow_list' => $pager,
            ]);
        } catch (Exception $e) {
            throw $this->createNotFoundException('Erreur : ' . $e->getMessage());
        }
    }


    // public function to delet the workflow by id (set deleted to 1)
    /**
     * @Route("/delete/{id}", name="workflow_delete")
     */
    public function WorkflowDeleteAction(string $id, Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
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

        $em = $this->getDoctrine()->getManager();
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
        try {
            $em = $this->getDoctrine()->getManager();
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
        try {
            $em = $this->getDoctrine()->getManager();
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

    // public function to create a new workflow
    /**
     * @Route("/new", name="workflow_create")
     */
    public function WorkflowCreateAction(Request $request)
    {
        try {

            $rules = RuleRepository::findActiveRulesNames($this->entityManager);

            $em = $this->getDoctrine()->getManager();
            $workflow = new Workflow();
            $workflow->setId(uniqid());
            $form = $this->createForm(WorkflowType::class, $workflow, [
                'entityManager' => $em,
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
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
     * @Route("/show/{id}", name="workflow_show", defaults={"page"=1})
     * @Route("/show/{id}/page-{page}", name="workflow_show_page", requirements={"page"="\d+"})
     */
    public function WorkflowShowAction(string $id, Request $request, int $page): Response
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $workflow = $em->getRepository(Workflow::class)->findBy(['id' => $id, 'deleted' => 0]);

            $workflowLogs = $em->getRepository(WorkflowLog::class)->findBy(
                ['workflow' => $id],
                ['dateCreated' => 'DESC']
            );
            $query = $this->workflowLogRepository->findLogsByWorkflowId($id);

            $adapter = new QueryAdapter($query);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage(10);
            $pager->setCurrentPage($page);

            if ($workflow[0]) {
                $nb_workflow = count($workflowLogs);
                return $this->render(
                    'Workflow/show.html.twig',
                    [
                        'workflow' => $workflow[0],
                        'workflowLogs' => $workflowLogs,
                        'nb_workflow' => $nb_workflow,
                        'pager' => $pager,
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


    // public function to edit a workflow
    /**
     * @Route("/edit/{id}", name="workflow_edit")
     */
    public function WorkflowEditAction(string $id, Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $workflowArray = $em->getRepository(Workflow::class)->findBy(['id' => $id, 'deleted' => 0]);
            $workflow = $workflowArray[0];

            if ($workflow) {
                $form = $this->createForm(WorkflowType::class, $workflow, [
                    'entityManager' => $em,
                    'entity' => $workflow,
                ]);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $workflow->setModifiedBy($this->getUser());
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
