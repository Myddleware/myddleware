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
use App\Entity\DocumentData;
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
use App\Repository\WorkflowActionRepository;
use App\Repository\WorkflowLogRepository;
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
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


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
        ParameterBagInterface $paramsprivate,
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


    // public function to delet the workflow by id (set deleted to 1)
    /**
     * @Route("/deleteAction/{id}", name="workflow_action_delete", methods={"POST", "DELETE"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function WorkflowActionDeleteAction(string $id, Request $request)
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        try {
            $em = $this->entityManager;
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
        if (!$this->tools->isPremium()) {
            return new JsonResponse(['error' => 'Premium required'], 403);
        }

        try {
            $em = $this->entityManager;
            $workflowResult = $em->getRepository(WorkflowAction::class)->findBy(['id' => $id, 'deleted' => 0]);
            
            if (!empty($workflowResult)) {
                $workflow = $workflowResult[0];
                $workflow->setActive($workflow->getActive() == 1 ? 0 : 1);
                $em->persist($workflow);
                $em->flush();
                
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Workflow Action updated successfully',
                    'active' => $workflow->getActive()
                ]);
            } 
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Workflow Action not found'
            ], 404);
            
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @Route("/action_active_show/{id}", name="workflow_action_active_show")
     * function activate or deactivate an action in the show view
     */
    public function WorkflowActionActiveShowAction(string $id, Request $request)
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        try {
            $em = $this->entityManager;
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
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        try {
            $em = $this->entityManager;
            $workflow = $em->getRepository(Workflow::class)->find($workflowId);

            if (!$workflow) {
                throw $this->createNotFoundException('No workflow found for id ' . $workflowId);
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

            $ruleDefault = $workflow->getRule();
            $allRules    = $em->getRepository(Rule::class)->findBy(['deleted' => 0], ['name' => 'ASC']);

            if ($workflowAction) {
                $arguments = $workflowAction->getArguments();

                // change the array so that for each element, the key is the integer and should be replaced by the value, so the key and the value are both the value, a string
                $StringStatus = [];
                foreach ($this->documentManager->lstStatus() as $key => $value) {
                    $StringStatus[$key] = $key;
                }


                // to get the searchValue, we need to get the rule, and from the rule we need to get the list of the source fields. The possible choisces are the source fields of the rule. The searchValue is a choicetype
                // step 1: get the workflow of the action
                $ruleForSearchValue = $workflowAction->getWorkflow()->getRule();
                // step 2: get the source fields of the rule
                $sourceFields = $ruleForSearchValue->getSourceFields();
                // Find the 'Id' field and move it to the beginning
                $idKey = array_search('Id', $sourceFields);
                if ($idKey === false) {
                    $idKey = array_search('id', $sourceFields);
                }
                
                if ($idKey !== false) {
                    // Remove Id from its current position
                    unset($sourceFields[$idKey]);
                    // Add it to the beginning
                    $sourceFields = [$idKey => 'id'] + $sourceFields;
                }

                // step 3: modify the source field so that for each, the key is the value and the value is the value
                foreach ($sourceFields as $key => $value) {
                    $sourceFields[$value] = $value;
                    unset($sourceFields[$key]);
                }

                $sourceSearchValue = [];
                // create an array of all the rules

                $rules = $em->getRepository(Rule::class)->findBy(['deleted' => 0]);

                // fill the array with the source fields of each rule
                foreach ($rules as $rule) {
                    // $sourceSearchValue[$rule->getId()] = $rule->getSourceFields();
                    $ruleSourceFields = $rule->getSourceFields();
                    foreach ($ruleSourceFields as $key => $value) {
                        $ruleSourceFields[$value] = $value;
                        unset($ruleSourceFields[$key]);
                    }
                    $ruleSourceFields['Name'] = 'Name';
                    $sourceSearchValue[$rule->getName()] = $ruleSourceFields;
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
                    'searchValue' => 'id',
                    'order' => null,
                    'active' => null,
                    'to' => null,
                    'subject' => null,
                    'message' => null,
                    'rerun' => null,
                    'targetFields' => null,
                    'targetFieldValues' => null,
                    'multipleRuns' => null,
                    // Add other WorkflowAction fields here as needed
                ];

                $form = $this->createFormBuilder($formData, ['allow_extra_fields' => true])
                        ->add('name', TextType::class, [
                            'label'      => 'view_edit_workflow_action.action_name',
                            'required'   => true,
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-control'
                            ],
                        ])
                        ->add('description', TextareaType::class, [
                            'label'      => 'view_edit_workflow_action.description',
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-control', 'rows' => 4
                            ],
                        ])
                        ->add('Workflow', EntityType::class, [
                            'class'        => Workflow::class,
                            'choices'      => $em->getRepository(Workflow::class)->findBy(['deleted' => 0]),
                            'choice_label' => 'name',
                            'choice_value' => 'id',
                            'constraints'  => [new NotBlank()],
                            'label'        => 'view_edit_workflow_action.workflow',
                            'row_attr'     => [
                                'class' => 'mb-3'
                            ],
                            'label_attr'   => [
                                'class' => 'form-label'
                            ],
                            'attr'         => [
                                'class' => 'form-select'
                            ],
                            'placeholder'  => 'view_edit_workflow_action.select_workflow',
                        ])
                        ->add('action', ChoiceType::class, [
                            'label'      => 'view_edit_workflow_action.action',
                            'choices'    => [
                                'view_edit_workflow_action.updateStatus'     => 'updateStatus',
                                'view_edit_workflow_action.generateDocument' => 'generateDocument',
                                'view_edit_workflow_action.sendNotification' => 'sendNotification',
                                'view_edit_workflow_action.transformDocument'=> 'transformDocument',
                                'view_edit_workflow_action.rerun'            => 'rerun',
                                'view_edit_workflow_action.changeData'       => 'changeData',
                                'view_edit_workflow_action.updateType'       => 'updateType',
                            ],
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-select'
                            ],
                            'placeholder'=> 'view_edit_workflow_action.select_action',
                        ])
                        ->add('ruleChangeData', EntityType::class, [
                            'class'        => Rule::class,
                            'choices'      => $ruleDefault ? [$ruleDefault] : [],
                            'choice_label' => 'name',
                            'choice_value' => 'id',
                            'label'        => false,
                            'data'         => $ruleDefault,
                            'mapped'       => false,
                            'disabled'     => true,
                            'required'     => false,
                            'attr'         => [
                                'id'    => 'form_ruleChangeData',
                                'class' => 'd-none form-select',
                            ],
                            'row_attr'     => [
                                'id'    => 'rule-change-container',
                                'class' => 'mb-3',
                            ],
                        ])
                        ->add('ruleGenerate', EntityType::class, [
                            'class'        => Rule::class,
                            'choices'      => $allRules,
                            'choice_label' => 'name',
                            'choice_value' => 'id',
                            'required'     => false,
                            'label'        => 'view_edit_workflow_action.generating_rule',
                            'placeholder'  => 'view_edit_workflow_action.select_rule',
                            'attr'         => [
                                'id' => 'form_ruleGenerate',
                                'class' => 'form-select'
                            ],
                            'row_attr'     => [
                                'id' => 'rule-generate-container',
                                'class' => 'mb-3'
                            ],
                            'label_attr'   => [
                                'class' => 'form-label'
                            ],
                        ])
                        ->add('status', ChoiceType::class, [
                            'label'      => 'view_edit_workflow_action.status',
                            'choices'    => $StringStatus,
                            'required'   => false,
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-select'
                            ],
                            'placeholder'=> 'view_edit_workflow_action.select_status',
                        ])
                        ->add('to', TextType::class, [
                            'label'      => 'view_edit_workflow_action.to',
                            'mapped'     => true,
                            'required'   => false,
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-control'
                            ],
                        ])
                        ->add('subject', TextType::class, [
                            'label'      => 'view_edit_workflow_action.subject',
                            'required'   => false,
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-control'
                            ],
                        ])
                        ->add('message', TextareaType::class, [
                            'label'      => 'view_edit_workflow_action.message',
                            'required'   => false,
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-control',
                                'rows' => 5
                            ],
                        ])
                        ->add('searchField', ChoiceType::class, [
                            'label'      => 'view_edit_workflow_action.matching_field_from_generating_rule',
                            'choices'    => $sourceSearchValue,
                            'required'   => false,
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-select'
                            ],
                            'placeholder'=> 'view_edit_workflow_action.select_field',
                        ])
                        ->add('searchValue', ChoiceType::class, [
                            'label'      => 'view_edit_workflow_action.matching_field_from_current_rule',
                            'choices'    => $sourceFields,
                            'required'   => false,
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-select'
                            ],
                            'placeholder'=> 'view_edit_workflow_action.select_field',
                        ])
                        ->add('rerun', ChoiceType::class, [
                            'label'      => 'view_edit_workflow_action.rerun_label',
                            'choices'    => [
                                'view_edit_workflow_action.yes' => true,
                                'view_edit_workflow_action.no' => false
                            ],
                            'required'   => false,
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-select'
                            ],
                        ])
                        ->add('documentType', ChoiceType::class, [
                            'label'      => 'view_edit_workflow_action.document_type',
                            'choices'    => [
                                'view_edit_workflow_action.type_c' => 'C',
                                'view_edit_workflow_action.type_u' => 'U',
                                'view_edit_workflow_action.type_d' => 'D',
                                'view_edit_workflow_action.type_s' => 'S'
                            ],
                            'mapped'     => false,
                            'required'   => false,
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-select'
                            ],
                            'placeholder'=> 'view_edit_workflow_action.select_type',
                        ])
                        ->add('targetField', ChoiceType::class, [
                            'label'      => 'view_edit_workflow_action.target_field',
                            'choices'    => [],
                            'required'   => false,
                            'mapped'     => false,
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-select'
                            ],
                            'placeholder'=> 'view_edit_workflow_action.select_field',
                        ])
                        ->add('targetFieldValues', CollectionType::class, [
                            'entry_type'    => TextType::class,
                            'allow_add'     => true,
                            'allow_delete'  => true,
                            'mapped'        => false,
                            'row_attr'      => [
                                'class' => 'mb-3'
                            ],
                            'label_attr'    => [
                                'class' => 'form-label'
                            ],
                            'entry_options' => [
                                'attr'       => [
                                    'class' => 'form-control mb-2'
                                ],
                                'label'      => false,
                            ],
                            'attr'          => [
                                'class' => 'd-block'
                            ],
                        ])
                        ->add('order', IntegerType::class, [
                            'label'      => 'view_edit_workflow_action.order',
                            'constraints'=> [new Range([
                                'min' => 0, 'max' => 50,
                                'notInRangeMessage' => 'You must enter a number between {{ min }} and {{ max }}.',
                            ])],
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-control'
                            ],
                        ])
                        ->add('active', ChoiceType::class, [
                            'label'      => 'view_edit_workflow_action.active',
                            'choices'    => [
                                'view_edit_workflow_action.yes' => 1,
                                'view_edit_workflow_action.no' => 0
                            ],
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-select'
                            ],
                        ])
                        ->add('multipleRuns', ChoiceType::class, [
                            'label'      => 'view_edit_workflow_action.multiple_runs',
                            'choices'    => [
                                'view_edit_workflow_action.yes' => 1,
                                'view_edit_workflow_action.no' => 0
                            ],
                            'required'   => false,
                            'row_attr'   => [
                                'class' => 'mb-3'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                            'attr'       => [
                                'class' => 'form-select'
                            ],
                        ])
                        ->add('submit', SubmitType::class, [
                            'label' => 'Save',
                            'attr'  => [
                                'class' => 'btn btn-success mt-2'
                            ],
                            'row_attr' => [
                                'class' => 'mt-2'
                            ],
                        ])
                        ->getForm();

                $form->handleRequest($request);

                if ($form->isSubmitted()) {

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

                    $multipleRuns = $form->get('multipleRuns')->getData();
                    $workflowAction->setMultipleRuns($multipleRuns ?? 0);

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

                    if ($action === 'generateDocument') {
                        $genRule = $form->get('ruleGenerate')->getData();
                        if ($genRule) {
                            $arguments['ruleId'] = $genRule->getId();
                        }
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

                    $documentType = $form->get('documentType')->getData();
                    if (!empty($documentType)) {
                        $arguments['type'] = $documentType;
                    }

                    $formData = $request->request->all();
                    $targetFields = $formData['targetFields'] ?? [];
                    $targetFieldValues = $formData['targetFieldValues'] ?? [];

                    if (!empty($targetFields) && !empty($targetFieldValues)) {
                        foreach ($targetFields as $index => $targetField) {
                            if (isset($targetFieldValues[$index])) {
                                $arguments['fields'][$targetField] = $targetFieldValues[$index];
                            }
                        }
                    }
                    $workflowAction->setArguments($arguments);
                    $em->persist($workflowAction);
                    $em->flush();

                    $this->emptyArgumentsBasedOnAction($workflowAction->getId());

                    $this->saveWorkflowAudit($workflowAction->getWorkflow()->getId());

                    $this->addFlash('success', 'Action updated successfully');

                    return $this->redirectToRoute('workflow_action_show', ['id' => $workflowAction->getId()]);
                }

                $workflows = $em->getRepository(Workflow::class)->findBy(['deleted' => 0]);

                return $this->render(
                    'WorkflowAction/new.html.twig',
                    [
                        'form' => $form->createView(),
                        'workflows' => $workflows,
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

    /**
     * @Route("/get-target-fields/{ruleId}", name="get_target_fields", methods={"GET"})
     */
    public function getTargetFields(string $ruleId, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        $ruleFields = $em->getRepository(RuleField::class)->findBy(['rule' => $ruleId]);

        if (!$ruleFields) {
            return new JsonResponse(['fields' => []], 404);
        }

        // get the target fields
        $fields = [];
        foreach ($ruleFields as $ruleField) {
            $fields[] = $ruleField->getTarget();
        }

        return new JsonResponse(['fields' => $fields]);
    }

    // public function to show the detail view of a single workflow
    /**
     * @Route("/showAction/{id}", name="workflow_action_show", defaults={"page"=1})
     * @Route("/showAction/{id}/page-{page}", name="workflow_action_show_page", requirements={"page"="\d+"})
     */
    public function WorkflowActionShowAction(string $id, Request $request, int $page): Response
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        try {
            $em = $this->entityManager;
            $workflow = $em->getRepository(WorkflowAction::class)->findOneBy(['id' => $id, 'deleted' => 0]);
            $workflowLogs = $em->getRepository(WorkflowLog::class)->findBy(['action' => $id]);
            if (!$workflow) {
                $this->addFlash('error', 'Workflow Action not found');
                return $this->redirectToRoute('workflow_list');
            }

            $workflowLogs = $em->getRepository(WorkflowLog::class)->findBy(
                ['action' => $workflow],
                ['dateCreated' => 'DESC']
            );

            $adapter = new ArrayAdapter($workflowLogs);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage(10);
            $pager->setCurrentPage($page);

            if ($workflow) {
                $nb_workflow = count($workflowLogs);
                return $this->render(
                    'WorkflowAction/show.html.twig',
                    [
                        'workflow' => $workflow,
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

    /**
     * @Route("/showAction/{id}/logs", name="workflow_action_show_logs", defaults={"page"=1})
     * @Route("/showAction/{id}/logs/page-{page}", name="workflow_action_show_logs_page", requirements={"page"="\d+"})
     */
    public function WorkflowActionShowLogs(string $id, Request $request, int $page): Response
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

       try {
            $em = $this->entityManager;
            $workflowAction = $em->getRepository(WorkflowAction::class)->findOneBy(['id' => $id, 'deleted' => 0]);

            if (!$workflowAction) {
                if ($request->isXmlHttpRequest()) {
                    return $this->render('WorkflowAction/_workflowaction_logs_table.html.twig', [
                        'error' => 'Workflow Action not found'
                    ]);
                }
                $this->addFlash('error', 'Workflow Action not found');
                return $this->redirectToRoute('workflow_list');
            }

            $conf = $this->configRepository->findOneBy(['name' => 'search_limit']);
            $limit = $conf ? (int) $conf->getValue() : null;

            $query = $this->workflowLogRepository->findLogsByActionId($id);
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
                return $this->render('WorkflowAction/_workflowaction_logs_table.html.twig', [
                    'workflowLogs' => $workflowLogs,
                    'nb_workflow' => $nb_workflow,
                    'pager' => $pager,
                    'workflowAction' => $workflowAction,
                ]);
            }
            return $this->redirectToRoute('workflow_action_show', ['id' => $id]);
        } catch (Exception $e) {
            error_log('WorkflowActionShowLogs Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            if ($request->isXmlHttpRequest()) {
                return $this->render('WorkflowAction/_workflowaction_logs_table.html.twig', [
                    'workflowLogs' => [],
                    'nb_workflow' => 0,
                    'pager' => null,
                    'workflowAction' => null,
                    'error' => 'Error loading logs: ' . $e->getMessage()
                ]);
            }
            throw $this->createNotFoundException('Error: ' . $e->getMessage());
        }
    }

    // public function to edit a workflow
    /**
     * @Route("/editWorkflowAction/{id}", name="workflow_action_edit")
     */
    public function WorkflowActionEditAction(string $id, Request $request)
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        try {
            $em = $this->entityManager;
            $workflowActionArray = $em->getRepository(WorkflowAction::class)->findBy(['id' => $id, 'deleted' => 0]);
            $workflowAction = $workflowActionArray[0];

            if ($workflowAction) {
                $this->emptyArgumentsBasedOnAction($id);
                $arguments = $workflowAction->getArguments();

                // change the array so that for each element, the key is the integer and should be replaced by the value, so the key and the value are both the value, a string
                $StringStatus = [];
                foreach ($this->documentManager->lstStatus() as $key => $value) {
                    $StringStatus[$key] = $key;
                }

                // to get the searchValue, we need to get the rule, and from the rule we need to get the list of the source fields. The possible choisces are the source fields of the rule. The searchValue is a choicetype
                // step 1: get the workflow of the action
                $ruleForSearchValue = $workflowAction->getWorkflow()->getRule();
                // step 2: get the source fields of the rule
                $sourceFields = $ruleForSearchValue->getSourceFields();
                // Find the 'Id' field and move it to the beginning
                $idKey = array_search('Id', $sourceFields);
                if ($idKey === false) {
                    $idKey = array_search('id', $sourceFields);
                }
                
                if ($idKey !== false) {
                    // Remove Id from its current position
                    unset($sourceFields[$idKey]);
                    // Add it to the beginning
                    $sourceFields = [$idKey => 'id'] + $sourceFields;
                }

                // step 3: modify the source field so that for each, the key is the value and the value is the value
                foreach ($sourceFields as $key => $value) {
                    $sourceFields[$value] = $value;
                    unset($sourceFields[$key]);
                }

                $sourceSearchValue = [];
                // create an array of all the rules

                $rules       = $em->getRepository(Rule::class)->findBy(['deleted' => 0]);
                $ruleDefault = $workflowAction->getWorkflow()->getRule();
                $allRules    = $em->getRepository(Rule::class)->findBy(['deleted' => 0], ['name' => 'ASC']);

                // fill the array with the source fields of each rule
                foreach ($rules as $rule) {
                    // $sourceSearchValue[$rule->getId()] = $rule->getSourceFields();
                    $ruleSourceFields = $rule->getSourceFields();
                    foreach ($ruleSourceFields as $key => $value) {
                        $ruleSourceFields[$value] = $value;
                        unset($ruleSourceFields[$key]);
                    }
                    $ruleSourceFields['name'] = 'name';
                    $sourceSearchValue[$rule->getName()] = $ruleSourceFields;
                }

                // Create a new array to hold the form data
                $formData = [
                    'name' => $workflowAction->getName(),
                    'description' => $workflowAction->getDescription(),
                    'Workflow' => $workflowAction->getWorkflow(),
                    'action' => $workflowAction->getAction(),
                    'status' => isset($arguments['status']) ? $StringStatus[$arguments['status']] : null,
                    'ruleId' => isset($arguments['ruleId']) ? $arguments['ruleId'] : null,
                    'searchField' => $arguments['searchField'] ?? null,
                    'searchValue' => $arguments['searchValue'] ?? null,
                    'order' => $workflowAction->getOrder(),
                    'active' => $workflowAction->getActive(),
                    'to' => $arguments['to'] ?? null,
                    'subject' => $arguments['subject'] ?? null,
                    'message' => $arguments['message'] ?? null,
                    'multipleRuns' => $workflowAction->getMultipleRuns(),
                    'rerun' => $arguments['rerun'] ?? 0,
                    'documentType' => $arguments['type'] ?? null
                    // Add other WorkflowAction fields here as needed
                ];

                $form = $this->createFormBuilder($formData, ['allow_extra_fields' => true])
                    ->add('name', TextType::class, [
                        'label'      => 'view_edit_workflow_action.action_name',
                        'required'   => true,
                        'label_attr' => [
                            'class' => 'form-label'
                        ],
                        'row_attr'   => [
                            'class' => 'mb-3'
                        ],
                        'attr'       => [
                            'class' => 'form-control'
                        ],
                    ])
                    ->add('description', TextareaType::class, [
                        'label'      => 'view_edit_workflow_action.description',
                        'label_attr' => [
                            'class' => 'form-label'
                        ],
                        'row_attr'   => [
                            'class' => 'mb-3'
                        ],
                        'attr'       => [
                            'class' => 'form-control',
                            'rows'  => 3,
                        ],
                    ])
                    ->add('Workflow', EntityType::class, [
                        'class'        => Workflow::class,
                        'choices'      => $em->getRepository(Workflow::class)->findBy(['deleted' => 0]),
                        'choice_label' => 'name',
                        'choice_value' => 'id',
                        'constraints'  => [new NotBlank()],
                        'label'        => 'view_edit_workflow_action.workflow',
                        'label_attr'   => [
                            'class' => 'form-label'
                        ],
                        'row_attr'     => [
                            'class' => 'mb-3'
                        ],
                        'attr'         => [
                            'class' => 'form-select'
                        ],
                    ])
                    ->add('action', ChoiceType::class, [
                        'label'      => 'view_edit_workflow_action.action',
                        'choices'    => [
                            'view_edit_workflow_action.updateStatus'     => 'updateStatus',
                            'view_edit_workflow_action.generateDocument' => 'generateDocument',
                            'view_edit_workflow_action.sendNotification' => 'sendNotification',
                            'view_edit_workflow_action.transformDocument'=> 'transformDocument',
                            'view_edit_workflow_action.rerun'            => 'rerun',
                            'view_edit_workflow_action.changeData'       => 'changeData',
                            'view_edit_workflow_action.updateType'       => 'updateType',
                        ],
                        'label_attr' => [
                            'class' => 'form-label'
                        ],
                        'row_attr'   => [
                            'class' => 'mb-3'
                        ],
                        'attr'       => [
                            'class' => 'form-select'
                        ],
                    ])
                    ->add('ruleId', EntityType::class, [
                        'class'        => Rule::class,
                        'choices'      => $em->getRepository(Rule::class)->findBy(['deleted' => 0]),
                        'choice_label' => 'name',
                        'choice_value' => 'id',
                        'required'     => false,
                        'label'        => 'Rule',
                        'data'         => $formData['ruleId'] ? $em->getRepository(Rule::class)->find($formData['ruleId']) : null,
                        'label_attr'   => [
                            'class' => 'form-label'
                        ],
                        'row_attr'     => [
                            'class' => 'mb-3'
                        ],
                        'attr'         => [
                            'class' => 'form-select'
                        ],
                    ])
                    ->add('ruleChangeData', EntityType::class, [
                        'class'        => Rule::class,
                        'choices'      => $ruleDefault ? [$ruleDefault] : [],
                        'choice_label' => 'name',
                        'choice_value' => 'id',
                        'data'         => $ruleDefault,
                        'label'        => false,
                        'mapped'       => false,
                        'disabled'     => true,
                        'required'     => false,
                        'attr'         => [
                            'id'    => 'form_ruleChangeData',
                            'class' => 'd-none form-select',
                        ],
                        'row_attr'     => [
                            'id'    => 'rule-change-container',
                            'class' => 'mb-3',
                        ],
                    ])
                    ->add('ruleGenerate', EntityType::class, [
                        'class'        => Rule::class,
                        'choices'      => $allRules,
                        'choice_label' => 'name',
                        'choice_value' => 'id',
                        'required'     => false,
                        'label'        => 'view_edit_workflow_action.generating_rule',
                        'placeholder'  => 'view_edit_workflow_action.select_rule',
                        'data'         => !empty($arguments['ruleId'])
                                            ? $em->getRepository(Rule::class)->find($arguments['ruleId'])
                                            : null,
                        'label_attr'   => [
                            'class' => 'form-label'
                        ],
                        'row_attr'     => [
                            'id'    => 'rule-generate-container',
                            'class' => 'mb-3',
                        ],
                        'attr'         => [
                            'id'    => 'form_ruleGenerate',
                            'class' => 'form-select',
                        ],
                    ])
                    ->add('status', ChoiceType::class, [
                        'label'      => 'view_edit_workflow_action.status',
                        'choices'    => $StringStatus,
                        'required'   => false,
                        'label_attr' => [
                            'class' => 'form-label'
                        ],
                        'row_attr'   => [
                            'class' => 'mb-3'
                        ],
                        'attr'       => [
                            'class' => 'form-select'
                        ],
                    ])
                    ->add('to', TextareaType::class, [
                        'label'      => 'view_edit_workflow_action.to',
                        'required'   => false,
                        'mapped'   => true,
                        'label_attr' => [
                            'class' => 'form-label'
                        ],
                        'row_attr'   => [
                            'class' => 'mb-3'
                        ],
                        'attr'       => [
                            'class' => 'form-control',
                            'rows'  => 1,
                            'cols'  => 7,
                        ],
                    ])
                    ->add('subject', TextType::class, [
                        'label'      => 'view_edit_workflow_action.subject',
                        'required'   => false,
                        'label_attr' => [
                            'class' => 'form-label'
                        ],
                        'row_attr'   => [
                            'class' => 'mb-3'
                        ],
                        'attr'       => [
                            'class' => 'form-control'
                        ],
                    ])
                    ->add('message', TextareaType::class, [
                        'required'   => false,
                        'label'      => 'view_edit_workflow_action.message',
                        'label_attr' => [
                            'class' => 'form-label'
                        ],
                        'row_attr'   => [
                            'class' => 'mb-3'
                        ],
                        'attr'       => [
                            'class' => 'form-control',
                            'rows'  => 2,
                            'cols'  => 5,
                        ],
                    ])
                    ->add('searchField', ChoiceType::class, [
                        'label'      => 'view_edit_workflow_action.matching_field_from_generating_rule',
                        'choices'    => $sourceSearchValue,
                        'required'   => false,
                        'label_attr' => [
                            'class' => 'form-label'
                        ],
                        'row_attr'   => [
                            'class' => 'mb-3'
                        ],
                        'attr'       => [
                            'class' => 'form-select'
                        ],
                    ])
                    ->add('searchValue', ChoiceType::class, [
                        'label'      => 'view_edit_workflow_action.matching_field_from_current_rule',
                        'choices'    => $sourceFields,
                        'required'   => false,
                        'label_attr' => [
                            'class' => 'form-label'
                        ],
                        'row_attr'   => [
                            'class' => 'mb-3'
                        ],
                        'attr'       => [
                            'class' => 'form-select'
                        ],
                    ])
                    ->add('rerun', ChoiceType::class, [
                        'label'      => 'view_edit_workflow_action.rerun_label',
                        'choices'    => [
                            'view_edit_workflow_action.yes' => true,
                            'view_edit_workflow_action.no'  => false,
                        ],
                        'required'   => false,
                        'label_attr' => [
                            'class' => 'form-label'
                        ],
                        'row_attr'   => [
                            'class' => 'mb-3'
                        ],
                        'attr'       => [
                            'class' => 'form-select'
                        ],
                    ])
                    ->add('documentType', ChoiceType::class, [
                        'label'      => 'view_edit_workflow_action.document_type',
                        'choices'    => [
                            'view_edit_workflow_action.type_c' => 'C',
                            'view_edit_workflow_action.type_u' => 'U',
                            'view_edit_workflow_action.type_d' => 'D',
                            'view_edit_workflow_action.type_s' => 'S',
                        ],
                        'mapped'     => true,
                        'required'   => false,
                        'label_attr' => [
                            'class' => 'form-label'
                        ],
                        'row_attr'   => [
                            'class' => 'mb-3'
                        ],
                        'attr'       => [
                            'class' => 'form-select'
                        ],
                    ])
                    ->add('targetFields', CollectionType::class, [
                        'entry_type'    => TextType::class,
                        'allow_add'     => true,
                        'allow_delete'  => true,
                        'mapped'        => false,
                        'row_attr'      => [
                            'class' => 'mb-3'
                        ],
                        'entry_options' => [
                            'attr'       => [
                                'class' => 'form-control mb-2'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                        ],
                    ])
                    ->add('targetFieldValues', CollectionType::class, [
                        'entry_type'    => TextType::class,
                        'allow_add'     => true,
                        'allow_delete'  => true,
                        'mapped'        => false,
                        'prototype'     => false,
                        'row_attr'      => [
                            'class' => 'mb-3'
                        ],
                        'entry_options' => [
                            'attr'       => [
                                'class' => 'form-control mb-2'
                            ],
                            'label_attr' => [
                                'class' => 'form-label'
                            ],
                        ],
                    ])
                    ->add('order', IntegerType::class, [
                        'label'       => 'view_edit_workflow_action.order',
                        'constraints' => [
                            new Range([
                                'min'               => 0,
                                'max'               => 50,
                                'notInRangeMessage' => 'You must enter a number between {{ min }} and {{ max }}.',
                            ]),
                        ],
                        'label_attr'  => [
                            'class' => 'form-label'
                        ],
                        'row_attr'    => [
                            'class' => 'mb-3'
                        ],
                        'attr'        => [
                            'class' => 'form-control'
                        ],
                    ])
                    ->add('active', ChoiceType::class, [
                        'label'      => 'view_edit_workflow_action.active',
                        'choices'    => [
                            'view_edit_workflow_action.yes' => 1,
                            'view_edit_workflow_action.no'  => 0,
                        ],
                        'label_attr' => [
                            'class' => 'form-label'
                        ],
                        'row_attr'   => [
                            'class' => 'mb-3'
                        ],
                        'attr'       => [
                            'class' => 'form-select'
                        ],
                    ])
                    ->add('multipleRuns', ChoiceType::class, [
                        'label'      => 'view_edit_workflow_action.multiple_runs',
                        'choices'    => [
                            'view_edit_workflow_action.yes' => 1,
                            'view_edit_workflow_action.no'  => 0,
                        ],
                        'required'   => false,
                        'label_attr' => [
                            'class' => 'form-label'
                        ],
                        'row_attr'   => [
                            'class' => 'mb-3'
                        ],
                        'attr'       => [
                            'class' => 'form-select'
                        ],
                    ])
                    ->add('submit', SubmitType::class, [
                        'label' => 'Save',
                        'attr'  => [
                            'class' => 'btn btn-success mt-2'
                        ],
                    ])
                    ->getForm();
                $form->handleRequest($request);

                if ($form->isSubmitted()) {

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

                    $multipleRuns = $form->get('multipleRuns')->getData();
                    $workflowAction->setMultipleRuns($multipleRuns ?? 0);

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

                    if ($action === 'generateDocument') {
                        $genRule = $form->get('ruleGenerate')->getData();
                        if ($genRule) {
                            $arguments['ruleId'] = $genRule->getId();
                        }
                    } elseif ($action === 'changeData') {
                        $arguments['ruleId'] = $workflowAction->getWorkflow()->getRule()->getId();
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
                    if ($action === 'generateDocument' && $rerun !== null && $rerun !== '') {
                        $arguments['rerun'] = (bool) $rerun;
                    } else {
                        unset($arguments['rerun']);
                    }

                    $documentType = $form->get('documentType')->getData();
                    if (!empty($documentType)) {
                        $arguments['type'] = $documentType;
                    }

                    $formData = $request->request->all();

                    $targetFields = $formData['targetFields'] ?? null;
                    $targetFieldValues = $formData['targetFieldValues'] ?? null;

                    if (!empty($targetFields) && is_array($targetFields) && !empty($targetFieldValues) && is_array($targetFieldValues)) {
                        foreach ($targetFields as $index => $targetField) {
                            if (isset($targetFieldValues[$index]) && !empty($targetField)) {
                                $arguments['fields'][$targetField] = $targetFieldValues[$index];
                            }
                        }
                    }

                    $workflowAction->setArguments($arguments);
                    $em->persist($workflowAction);
                    $em->flush();

                    $this->emptyArgumentsBasedOnAction($id);

                    $this->saveWorkflowAudit($workflowAction->getWorkflow()->getId());

                    $this->addFlash('success', 'Action updated successfully');

                    return $this->redirectToRoute('workflow_action_show', ['id' => $workflowAction->getId()]);
                }

                $targetFieldsData = [];
                if (!empty($arguments) && count($arguments) > 0) {
                    // Handle fields property specifically
                    if (isset($arguments['fields']) && is_array($arguments['fields'])) {
                        foreach ($arguments['fields'] as $field => $value) {
                            $targetFieldsData[] = [
                                'field' => $field,
                                'value' => $value,
                            ];
                        }
                    }
                }
                return $this->render(
                    'WorkflowAction/edit.html.twig',
                    [
                        'form' => $form->createView(),
                        'targetFieldsData' => $targetFieldsData,
                        'workflowAction' => $workflowAction, 
                        'workflows' => $em->getRepository(Workflow::class)->findBy(['deleted' => 0]),
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

        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        $em = $this->entityManager;
        $workflowActionArray = $em->getRepository(WorkflowAction::class)->findBy(['id' => $id, 'deleted' => 0]);
        $workflowAction = $workflowActionArray[0];

        if ($workflowAction) {
            $arguments = $workflowAction->getArguments();
            $action = $workflowAction->getAction();

            if ($action == 'updateStatus') {
                unset($arguments['to'], $arguments['subject'], $arguments['searchField'], $arguments['searchValue'], $arguments['type']);
            } elseif ($action == 'generateDocument') {
                unset($arguments['to'], $arguments['subject'], $arguments['message'], $arguments['status'], $arguments['type']);
            } elseif ($action == 'sendNotification') {
                unset($arguments['status'], $arguments['searchField'], $arguments['searchValue'], $arguments['type']);
            } elseif ($action == 'transformDocument') {
                unset($arguments['to'], $arguments['subject'], $arguments['message'], $arguments['status'], $arguments['searchField'], $arguments['searchValue'], $arguments['type']);
            } elseif ($action == 'rerun') {
                unset($arguments['to'], $arguments['subject'], $arguments['message'], $arguments['status'], $arguments['searchField'], $arguments['searchValue'], $arguments['type']);
            } elseif ($action == 'changeData') {
                unset($arguments['to'], $arguments['subject'], $arguments['message'], $arguments['status'], $arguments['searchField'], $arguments['searchValue'], $arguments['type'], $arguments['rerun']);
            } elseif ($action == 'updateType') {
                unset($arguments['to'], $arguments['subject'], $arguments['message'], $arguments['status'], $arguments['searchField'], $arguments['searchValue'], $arguments['ruleId'], $arguments['rerun']);
            }

            $workflowAction->setArguments($arguments);
            $em->persist($workflowAction);
            $em->flush();
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

    // /**
    //  * @Route("/update-field-value", name="update_field_value", methods={"POST"})
    //  */
    // public function updateFieldValue(Request $request, EntityManagerInterface $em): JsonResponse
    // {
    //     $targetField = $request->request->get('targetField');
    //     $newValue = $request->request->get('newValue');
    //     $docId = 'oihjkjn';

    //     if ($targetField && $newValue) {
    //         $documentData = new DocumentData();
    //         $documentData->setDocId($docId);
    //         $documentData->setType('S');
    //         $documentData->setData(json_encode([$targetField => $newValue]));

    //         $em->persist($documentData);
    //         $em->flush();

    //         return new JsonResponse(['message' => 'Mise à jour réussie']);
    //     }

    //     return new JsonResponse(['error' => 'Données invalides'], 400);
    // }

        /**
     * @Route("/workflow/{id}/actions/partial", name="workflow_actions_partial")
     */
    public function actionLogsPartial(EntityManagerInterface $em, string $id): Response
    {
        $workflow = $em->getRepository(WorkflowAction::class)->findOneBy(['id' => $id, 'deleted' => 0]);

        if (!$workflow) {
            throw $this->createNotFoundException('WorkflowAction not found');
        }

        $logs = $em->getRepository(WorkflowLog::class)->findBy(
            ['action' => $workflow],
            ['dateCreated' => 'DESC']
        );

        return $this->render('workflowAction/_partial_action_logs.html.twig', [
            'logs' => $logs,
        ]);
    }
}
