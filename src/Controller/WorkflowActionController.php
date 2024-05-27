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
use App\Form\Type\WorkflowActionType;
use App\Repository\DocumentRepository;
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
     * @Route("/workflowAction")
     */
    class WorkflowActionController extends AbstractController
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
        }

        protected function getInstanceBdd()
        {
        }


    // public function to delet the workflow by id (set deleted to 1)
    /**
     * @Route("/delete/{id}", name="workflow_action_delete")
     */
    public function WorkflowActionDeleteAction(string $id, Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $workflowAction = $em->getRepository(WorkflowAction::class)->findBy(['id' => $id, 'deleted' => 0]);

            if ($workflowAction[0]) {
                $workflowAction[0]->setDeleted(1);
                $em->persist($workflowAction);
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

    // public function to set the workflow to active or inactive
    /**
     * @Route("/active/{id}", name="workflow_action_active")
     */
    public function WorkflowActionActiveAction(string $id, Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $workflowResult = $em->getRepository(WorkflowAction::class)->findBy(['id' => $id, 'deleted' => 0]);
            $workflow = $workflowResult[0];


            if ($workflow) {
                $workflow->setActive($workflow->getActive() == 1 ? 0 : 1);
                $em->persist($workflow);
                $em->flush();
                $this->addFlash('success', 'Workflow Action updated successfully');
            } else {
                $this->addFlash('error', 'Workflow Action not found');
            }

            return $this->redirectToRoute('workflow_list');
        } catch (Exception $e) {
            throw $this->createNotFoundException('Error : ' . $e);
        }
    }

    /**
     * @Route("/active_show/{id}", name="workflow_action_active_show")
     * function activate or deactivate an action in the show view
     */
    public function WorkflowActionActiveShowAction(string $id, Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $workflowResult = $em->getRepository(Workflow::class)->findBy(['id' => $id, 'deleted' => 0]);
            $workflow = $workflowResult[0];

            if ($workflow) {
            } else {
                $this->addFlash('error', 'Workflow Action not found');
            }
            
            
            return $this->redirectToRoute('workflow_show', ['id' => $id]);
        } catch (Exception $e) {
            throw $this->createNotFoundException('Error : ' . $e);
        }
    }

    // public function to create a new workflow
    /**
     * @Route("/new", name="workflow_action_create")
     */
    public function WorkflowActionCreateAction(Request $request)
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
                $this->addFlash('success', 'Action created successfully');

                return $this->redirectToRoute('workflow_list');
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
     * @Route("/new/{workflowId}", name="workflow_action_create_with_workflow")
     */
    public function WorkflowCreateActionWithWorkflow(string $workflowId, Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $workflow = $em->getRepository(Workflow::class)->find($workflowId);

            if (!$workflow) {
                throw $this->createNotFoundException('No workflow found for id '.$workflowId);
            }

            $workflowAction = new WorkflowAction();
            $workflowAction->setId(uniqid());
            $workflowAction->setWorkflow($workflow);
            $form = $this->createForm(WorkflowActionType::class, $workflowAction, [
                'entityManager' => $em,
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $workflowAction->setCreatedBy($this->getUser());
                $workflowAction->setModifiedBy($this->getUser());
                $workflowAction->setDateCreated(new \DateTime());
                $workflowAction->setDateModified(new \DateTime());
                $workflowAction->setDeleted(0);
                $em->persist($workflowAction);
                $em->flush();
                $this->addFlash('success', 'Workflow action created successfully');

                return $this->redirectToRoute('workflow_show', ['id' => $workflowId]);
            }

            return $this->render(
                'WorkflowAction/new.html.twig',
                [
                    'form' => $form->createView(),
                ]
            );
        } catch (Exception $e) {
            throw $this->createNotFoundException('Error : ' . $e);
        }
    }

    // public function to show the detail view of a single workflow
    /**
     * @Route("/showAction/{id}", name="workflow_action_show")
     */
    public function WorkflowActionShowAction(string $id, Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $workflow = $em->getRepository(WorkflowAction::class)->findBy(['id' => $id, 'deleted' => 0]);

            if ($workflow[0]) {
                return $this->render(
                    'WorkflowAction/show.html.twig',
                    [
                        'workflow' => $workflow[0],
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
     * @Route("/editWorkflowAction/{id}", name="workflow_action_edit")
     */
    public function WorkflowEditAction(string $id, Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $workflowAction = $em->getRepository(WorkflowAction::class)->findBy(['id' => $id, 'deleted' => 0]);

            if ($workflowAction[0]) {
                $form = $this->createForm(WorkflowActionType::class, $workflowAction[0], [
                    'entityManager' => $em,
                ]);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $workflowAction[0]->setModifiedBy($this->getUser());
                    $em->persist($workflowAction[0]);
                    $em->flush();
                    $this->addFlash('success', 'Action updated successfully');

                    return $this->redirectToRoute('workflow_action_show', ['id' => $workflowAction[0]->getId()]);
                }

                return $this->render(
                    'WorkflowAction/edit.html.twig',
                    [
                        'form' => $form->createView(),
                    ]
                );
            } else {
                $this->addFlash('error', 'Action not found');

                return $this->redirectToRoute('workflow_list');
            }
        } catch (Exception $e) {
            throw $this->createNotFoundException('Error : ' . $e);
        }
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

            //On passe l’adapter au bundle qui va s’occuper de la pagination
            if ($orm) {
                $compact['pager'] = new Pagerfanta(new QueryAdapter($params['adapter_em_repository']));
            } else {
                $compact['pager'] = new Pagerfanta(new ArrayAdapter($params['adapter_em_repository']));
            }

            //On définit le nombre d’article à afficher par page (que l’on a biensur définit dans le fichier param)
            $compact['pager']->setMaxPerPage($params['maxPerPage']);
            try {
                $compact['entities'] = $compact['pager']
                    //On indique au pager quelle page on veut
                    ->setCurrentPage($params['page'])
                    //On récupère les résultats correspondant
                    ->getCurrentPageResults();

                $compact['nb'] = $compact['pager']->getNbResults();
            } catch (\Pagerfanta\Exception\NotValidCurrentPageException $e) {
                //Si jamais la page n’existe pas on léve une 404
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


}