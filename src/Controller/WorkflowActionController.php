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
use App\Form\Type\WorkflowActionType;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
     * @Route("/deleteAction/{id}", name="workflow_action_delete")
     */
    public function WorkflowActionDeleteAction(string $id, Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $workflowActionResult = $em->getRepository(WorkflowAction::class)->findBy(['id' => $id, 'deleted' => 0]);
            $workflowAction = $workflowActionResult[0];

            if ($workflowAction) {
                $this->saveWorkflowAudit($workflowAction->getWorkflow()->getId());
                $workflowAction->setDeleted(1);
                $em->persist($workflowAction);
                $em->flush();
                $this->addFlash('success', 'Action deleted successfully');
            } else {
                $this->addFlash('error', 'Action not found');
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
     * @Route("/action_active_show/{id}", name="workflow_action_active_show")
     * function activate or deactivate an action in the show view
     */
    public function WorkflowActionActiveShowAction(string $id, Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $workflowActionResult = $em->getRepository(WorkflowAction::class)->findBy(['id' => $id, 'deleted' => 0]);
            $workflowAction = $workflowActionResult[0];

            if ($workflowAction) {
                $workflowAction->setActive($workflowAction->getActive() == 1 ? 0 : 1);
                $em->persist($workflowAction);
                $em->flush();
                $this->addFlash('success', 'Workflow Action updated successfully');
            } else {
                $this->addFlash('error', 'Workflow Action not found');
            }
            
            
            return $this->redirectToRoute('workflow_action_show', ['id' => $id]);
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
            // set date created
            $workflowAction->setCreatedBy($this->getUser());
            $workflowAction->setModifiedBy($this->getUser());
            $workflowAction->setDateCreated(new \DateTime());
            $workflowAction->setDateModified(new \DateTime());
            $workflowAction->setDeleted(0);

            if ($workflowAction) {
                $arguments = $workflowAction->getArguments();


                $possiblesStatusesWithIntegers = DocumentRepository::findStatusType($em);

                // change the array so that for each element, the key is the integer and should be replaced by the value, so the key and the value are both the value, a string
                $StringStatus = [];
                foreach ($possiblesStatusesWithIntegers as $key => $value) {
                    $StringStatus[$key] = $key;

                }

                    // to get the searchValue, we need to get the rule, and from the rule we need to get the list of the source fields. The possible choisces are the source fields of the rule. The searchValue is a choicetype
                    // step 1: get the workflow of the action
                    $ruleForSearchValue = $workflowAction->getWorkflow()->getRule();
                    // step 2: get the source fields of the rule
                    $sourceFields = $ruleForSearchValue->getSourceFields();

                    // step 3: modify the source field so that for each, the key is the value and the value is the value
                    foreach ($sourceFields as $key => $value) {
                        $sourceFields[$value] = $value;
                        unset($sourceFields[$key]);
                    }

                    $sourceSearchValue = [];
                    // create an array of all the rules

                    $rules = $em->getRepository(Rule::class)->findBy(['active' => true]);

                    // fill the array with the source fields of each rule
                    foreach ($rules as $rule) {
                        // $sourceSearchValue[$rule->getId()] = $rule->getSourceFields();
                        $ruleSourceFields = $rule->getSourceFields();
                        foreach ($ruleSourceFields as $key => $value) {
                            $ruleSourceFields[$value] = $value;
                            unset($ruleSourceFields[$key]);
                        }
                        $sourceSearchValue[$rule->getId()] = $ruleSourceFields;
                    }



                // Create a new array to hold the form data
                $formData = [
                    'name' => null,
                    'description' => null,
                    'Workflow' => $workflowAction->getWorkflow(),
                    'action' => null,
                    'status' => null,
                    'ruleId' => null,
                    'searchField' => null,
                    'searchValue' => null,
                    'order' => null,
                    'active' => null,
                    'to' => null,
                    'subject' => null,
                    'message' => null,
                    'rerun' => null
                    // Add other WorkflowAction fields here as needed
                ];
            
                $form = $this->createFormBuilder($formData)
                    ->add('name', TextType::class, [
                        'label' => 'Action Name',
                        'required' => true,
                    ])
                    ->add('description', TextType::class, ['label' => 'Description'])
                    ->add('Workflow', EntityType::class, [
                        'class' => Workflow::class,
                        'choices' => $em->getRepository(Workflow::class)->findBy(['deleted' => 0]),
                        'choice_label' => 'name',
                        'choice_value' => 'id',
                        'constraints' => [
                            new NotBlank(),
                        ],
                    ])
                    ->add('action', ChoiceType::class, [
                        'label' => 'Action',
                        'choices' => [
                            'updateStatus' => 'updateStatus',
                            'generateDocument' => 'generateDocument',
                            'sendNotification' => 'sendNotification',
                            'generateDocument' => 'generateDocument',
                            'transformDocument' => 'transformDocument',
                        ],
                    ])
                    ->add('ruleId', EntityType::class, [
                        'class' => Rule::class,
                        'choices' => $em->getRepository(Rule::class)->findBy(['active' => true]),
                        'choice_label' => 'name',
                        'choice_value' => 'id',
                        'required' => false,
                        'label' => 'Rule',
                    ])
                    ->add('status', ChoiceType::class, [
                        'label' => 'Status',
                        'choices' => $StringStatus,
                        'required' => false
                    ])
                    ->add('to', TextType::class, ['label' => 'To', 'mapped' => false, 'required' => false])
                    ->add('subject', TextType::class, ['label' => 'Subject', 'mapped' => false, 'required' => false])
                    ->add('message', TextareaType::class, ['required' => false])
                    ->add('searchField', ChoiceType::class, [
                        'label' => 'searchField',
                        'choices' => $sourceSearchValue,
                        'required' => false
                    ])
                    ->add('searchValue', ChoiceType::class, [
                        'label' => 'searchValue',
                        'choices' => $sourceFields,
                        'required' => false
                    ])
                    ->add('rerun', ChoiceType::class, [
                        'label' => 'Rerun',
                        'choices' => [
                            'Yes' => true,
                            'No' => false,
                        ],
                        'required' => false
                    ])



                    ->add('order', IntegerType::class, [
                        'label' => 'Order',
                        'constraints' => [
                            new Range([
                                'min' => 0,
                                'max' => 50,
                                'notInRangeMessage' => 'You must enter a number between {{ min }} and {{ max }}.',
                            ]),
                        ],
                    ])
                    ->add('active', ChoiceType::class, [
                        'label' => 'Active',
                        'choices' => [
                            'Yes' => 1,
                            'No' => 0,
                        ],
                    ])
                    ->add('submit', SubmitType::class, ['label' => 'Save'])
                    ->getForm();    
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {



                    $workflowAction->setModifiedBy($this->getUser());

                    $action = $form->get('action')->getData();
                    $workflowAction->setAction($action);

                    $name = $form->get('name')->getData();
                    $workflowAction->setName($name);

                    $description = $form->get('description')->getData();
                    $workflowAction->setDescription($description);

                    $workflow = $form->get('Workflow')->getData();
                    $workflowAction->setWorkflow($workflow);

                    $order = $form->get('order')->getData();
                    $workflowAction->setOrder($order);

                    $active = $form->get('active')->getData();
                    $workflowAction->setActive($active);

                    
                    // get the to, the subject, and the message using getdata
                    $arguments = [];
                    $to = $form->get('to')->getData();
                    if (!empty($to)) {
                        $arguments['to'] = $to;
                    }

                    $subject = $form->get('subject')->getData();
                    if (!empty($subject)) {
                        $arguments['subject'] = $subject;
                    }

                    $message = $form->get('message')->getData();
                    if (!empty($message)) {
                        $arguments['message'] = $message;
                    }

                    $rule = $form->get('ruleId')->getData();
                    if ($rule !== null) {
                        $ruleIdForArgument = $rule->getId();
                        $arguments['ruleId'] = $ruleIdForArgument;
                    }
                    
                    // set the status
                    $status = $form->get('status')->getData();
                    if (!empty($status)) {
                        //since status is a integer, we have to map it to possible statuses name
                        $arguments['status'] = $status;
                    }
                    
                    // set the searchField
                    $searchField = $form->get('searchField')->getData();
                    if (!empty($searchField)) {
                        $arguments['searchField'] = $searchField;
                    }
                    
                    // set the searchValue
                    $searchValue = $form->get('searchValue')->getData();
                    if (!empty($searchValue)) {
                        $arguments['searchValue'] = $searchValue;
                    }
                    
                    $rerun = $form->get('rerun')->getData();
                    if (!empty($rerun)) {
                        $arguments['rerun'] = $rerun;
                    }
                    
                    $workflowAction->setArguments($arguments);
                    $em->persist($workflowAction);
                    $em->flush();

                    $this->emptyArgumentsBasedOnAction($workflowAction->getId());

                    $this->saveWorkflowAudit($workflowAction->getWorkflow()->getId());

                    $this->addFlash('success', 'Action updated successfully');

                    return $this->redirectToRoute('workflow_action_show', ['id' => $workflowAction->getId()]);
                }

                return $this->render(
                    'WorkflowAction/new.html.twig',
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

    // public function to show the detail view of a single workflow
    /**
     * @Route("/showAction/{id}", name="workflow_action_show")
     */
    public function WorkflowActionShowAction(string $id, Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $workflow = $em->getRepository(WorkflowAction::class)->findBy(['id' => $id, 'deleted' => 0]);

            // get the workflow logs of this action
            $workflowLogs = $em->getRepository(WorkflowLog::class)->findBy(['action' => $id]);

            if ($workflow[0]) {
                $nb_workflow = count($workflowLogs);
                return $this->render(
                    'WorkflowAction/show.html.twig',
                    [
                        'workflow' => $workflow[0],
                        'workflowLogs' => $workflowLogs,
                        'nb_workflow' => $nb_workflow
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
    public function WorkflowActionEditAction(string $id, Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $workflowActionArray = $em->getRepository(WorkflowAction::class)->findBy(['id' => $id, 'deleted' => 0]);
            $workflowAction = $workflowActionArray[0];

            if ($workflowAction) {
                $this->emptyArgumentsBasedOnAction($id);
                $arguments = $workflowAction->getArguments();


                $possiblesStatusesWithIntegers = DocumentRepository::findStatusType($em);

                // change the array so that for each element, the key is the integer and should be replaced by the value, so the key and the value are both the value, a string
                $StringStatus = [];
                foreach ($possiblesStatusesWithIntegers as $key => $value) {
                    $StringStatus[$key] = $key;

                }

                    // to get the searchValue, we need to get the rule, and from the rule we need to get the list of the source fields. The possible choisces are the source fields of the rule. The searchValue is a choicetype
                    // step 1: get the workflow of the action
                    $ruleForSearchValue = $workflowAction->getWorkflow()->getRule();
                    // step 2: get the source fields of the rule
                    $sourceFields = $ruleForSearchValue->getSourceFields();

                    // step 3: modify the source field so that for each, the key is the value and the value is the value
                    foreach ($sourceFields as $key => $value) {
                        $sourceFields[$value] = $value;
                        unset($sourceFields[$key]);
                    }

                    $sourceSearchValue = [];
                    // create an array of all the rules

                    $rules = $em->getRepository(Rule::class)->findBy(['active' => true]);

                    // fill the array with the source fields of each rule
                    foreach ($rules as $rule) {
                        // $sourceSearchValue[$rule->getId()] = $rule->getSourceFields();
                        $ruleSourceFields = $rule->getSourceFields();
                        foreach ($ruleSourceFields as $key => $value) {
                            $ruleSourceFields[$value] = $value;
                            unset($ruleSourceFields[$key]);
                        }
                        $sourceSearchValue[$rule->getId()] = $ruleSourceFields;
                    }



                // Create a new array to hold the form data
                $formData = [
                    'name' => $workflowAction->getName(),
                    'description' => $workflowAction->getDescription(),
                    'Workflow' => $workflowAction->getWorkflow(),
                    'action' => $workflowAction->getAction(),
                    'status' => isset($arguments['status']) ? $StringStatus[$arguments['status']] : null,
                    'rule' => $workflowAction->getWorkflow()->getRule() ?? null,
                    'searchField' => $arguments['searchField'] ?? null,
                    'searchValue' => $arguments['searchValue'] ?? null,
                    'order' => $workflowAction->getOrder(),
                    'active' => $workflowAction->getActive(),
                    'to' => $arguments['to'] ?? null,
                    'subject' => $arguments['subject'] ?? null,
                    'message' => $arguments['message'] ?? null,
                    'rerun' => $arguments['rerun'] ?? false
                    // Add other WorkflowAction fields here as needed
                ];
            
                $form = $this->createFormBuilder($formData)
                    ->add('name', TextType::class, [
                        'label' => 'Action Name',
                        'required' => true,
                    ])
                    ->add('description', TextType::class, ['label' => 'Description'])
                    ->add('Workflow', EntityType::class, [
                        'class' => Workflow::class,
                        'choices' => $em->getRepository(Workflow::class)->findBy(['deleted' => 0]),
                        'choice_label' => 'name',
                        'choice_value' => 'id',
                        'constraints' => [
                            new NotBlank(),
                        ],
                    ])
                    ->add('action', ChoiceType::class, [
                        'label' => 'Action',
                        'choices' => [
                            'updateStatus' => 'updateStatus',
                            'generateDocument' => 'generateDocument',
                            'sendNotification' => 'sendNotification',
                            'generateDocument' => 'generateDocument',
                            'transformDocument' => 'transformDocument',
                        ],
                    ])
                    ->add('ruleId', EntityType::class, [
                        'class' => Rule::class,
                        'choices' => $em->getRepository(Rule::class)->findBy(['active' => true]),
                        'choice_label' => 'name',
                        'choice_value' => 'id',
                        'required' => false,
                        'label' => 'Rule',
                    ])
                    ->add('status', ChoiceType::class, [
                        'label' => 'Status',
                        'choices' => $StringStatus,
                        'required' => false
                    ])
                    ->add('to', TextType::class, ['label' => 'To', 'mapped' => false, 'required' => false])
                    ->add('subject', TextType::class, ['label' => 'Subject', 'mapped' => false, 'required' => false])
                    ->add('message', TextareaType::class, ['required' => false])
                    ->add('searchField', ChoiceType::class, [
                        'label' => 'searchField',
                        'choices' => $sourceSearchValue,
                        'required' => false
                    ])
                    ->add('searchValue', ChoiceType::class, [
                        'label' => 'searchValue',
                        'choices' => $sourceFields,
                        'required' => false
                    ])
                    ->add('rerun', ChoiceType::class, [
                        'label' => 'Rerun',
                        'choices' => [
                            'Yes' => true,
                            'No' => false,
                        ],
                        'required' => false
                    ])



                    ->add('order', IntegerType::class, [
                        'label' => 'Order',
                        'constraints' => [
                            new Range([
                                'min' => 0,
                                'max' => 50,
                                'notInRangeMessage' => 'You must enter a number between {{ min }} and {{ max }}.',
                            ]),
                        ],
                    ])
                    ->add('active', ChoiceType::class, [
                        'label' => 'Active',
                        'choices' => [
                            'Yes' => 1,
                            'No' => 0,
                        ],
                    ])
                    ->add('submit', SubmitType::class, ['label' => 'Save'])
                    ->getForm();    
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {



                    $workflowAction->setModifiedBy($this->getUser());

                    $action = $form->get('action')->getData();
                    $workflowAction->setAction($action);

                    $name = $form->get('name')->getData();
                    $workflowAction->setName($name);

                    $description = $form->get('description')->getData();
                    $workflowAction->setDescription($description);

                    $workflow = $form->get('Workflow')->getData();
                    $workflowAction->setWorkflow($workflow);

                    $order = $form->get('order')->getData();
                    $workflowAction->setOrder($order);

                    $active = $form->get('active')->getData();
                    $workflowAction->setActive($active);

                    
                    // get the to, the subject, and the message using getdata
                    $arguments = [];
                    $to = $form->get('to')->getData();
                    if (!empty($to)) {
                        $arguments['to'] = $to;
                    }

                    $subject = $form->get('subject')->getData();
                    if (!empty($subject)) {
                        $arguments['subject'] = $subject;
                    }

                    $message = $form->get('message')->getData();
                    if (!empty($message)) {
                        $arguments['message'] = $message;
                    }

                    $rule = $form->get('ruleId')->getData();
                    if ($rule !== null) {
                        $ruleIdForArgument = $rule->getId();
                        $arguments['ruleId'] = $ruleIdForArgument;
                    }
                    
                    // set the status
                    $status = $form->get('status')->getData();
                    if (!empty($status)) {
                        //since status is a integer, we have to map it to possible statuses name
                        $arguments['status'] = $status;
                    }
                    
                    // set the searchField
                    $searchField = $form->get('searchField')->getData();
                    if (!empty($searchField)) {
                        $arguments['searchField'] = $searchField;
                    }
                    
                    // set the searchValue
                    $searchValue = $form->get('searchValue')->getData();
                    if (!empty($searchValue)) {
                        $arguments['searchValue'] = $searchValue;
                    }
                    
                    $rerun = $form->get('rerun')->getData();
                    if (!empty($rerun)) {
                        $arguments['rerun'] = $rerun;
                    }
                    
                    $workflowAction->setArguments($arguments);
                    $em->persist($workflowAction);
                    $em->flush();

                    $this->emptyArgumentsBasedOnAction($id);

                    $this->saveWorkflowAudit($workflowAction->getWorkflow()->getId());

                    $this->addFlash('success', 'Action updated successfully');

                    return $this->redirectToRoute('workflow_action_show', ['id' => $workflowAction->getId()]);
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

    // public function to empty the arguments based on the action
    public function emptyArgumentsBasedOnAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $workflowActionArray = $em->getRepository(WorkflowAction::class)->findBy(['id' => $id, 'deleted' => 0]);
        $workflowAction = $workflowActionArray[0];

        if ($workflowAction) {
            $arguments = $workflowAction->getArguments();
            $action = $workflowAction->getAction();

            if ($action == 'updateStatus') {
                unset($arguments['to'], $arguments['subject'], $arguments['message'], $arguments['searchField'], $arguments['searchValue']);
            } elseif ($action == 'generateDocument') {
                unset($arguments['to'], $arguments['subject'], $arguments['message'], $arguments['status']);
            } elseif ($action == 'sendNotification') {
                unset($arguments['status'], $arguments['searchField'], $arguments['searchValue']);
            } elseif ($action == 'transformDocument') {
                unset($arguments['to'], $arguments['subject'], $arguments['message'], $arguments['status'], $arguments['searchField'], $arguments['searchValue']);
            }

            $workflowAction->setArguments($arguments);
            $em->persist($workflowAction);
            $em->flush();
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
    
            $actionsArray = array_map(function($action) {
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
                            'rule' => $workflow->getRule()->getId(),
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
