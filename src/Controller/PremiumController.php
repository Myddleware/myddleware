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
 * @Route("/premium")
 */
class PremiumController extends AbstractController
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



/**
     * PAGE ACHAT PREMIUM.
     *
     * @return RedirectResponse|Response
     *
     * @Route("/list", name="premium_list", defaults={"page"=1})
     * @Route("/list/page-{page}", name="premium_list_page", requirements={"page"="\d+"})
     */
    public function PremiumListAction(int $page = 1, Request $request)
    {
        try {
            
            


            // Si ce n'est pas une requête AJAX, rendre la page complète
            return $this->render(
                'premium/list.html.twig',
                [
                    'isPremium' => true,
                ]
            );
        } catch (Exception $e) {
            throw $this->createNotFoundException('Erreur : ' . $e->getMessage());
        }
    }




}
