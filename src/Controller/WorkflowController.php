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

            

            // $workflowLogs = $em->getRepository(WorkflowLog::class)->findBy(
            //     ['triggerDocument' => $id],
            //     ['id' => 'DESC']
            // );

            $session = $request->getSession();
            $em = $this->getDoctrine()->getManager();

            $entities = $em->getRepository(Workflow::class)->findAll();

            // List of task limited to 1000 and rder by status (start first) and date begin
            $compact = $this->nav_pagination([
                'adapter_em_repository' => $entities,
                'maxPerPage' => $this->params['pager'] ?? 25,
                'page' => $page,
            ], false);


                    return $this->render(
                        'Workflow/list.html.twig',
                        [
                            'entities' => $entities,
                            'nb_workflow' => count($entities),
                            'pager' => $compact['pager'],
                        ]
                    );
                throw $this->createNotFoundException('Error');
            
        } catch (Exception $e) {
            throw $this->createNotFoundException('Error : ' . $e);
        }
    }

    /**
     * @Route("/list/workflow/{ruleId}", name="workflow_list_by_rule", defaults={"page"=1})
     * @Route("/list/workflow/{ruleId}/page-{page}", name="workflow_list_by_rule_page", requirements={"page"="\d+"})
     */
    public function WorkflowListByRuleAction(string $ruleId, int $page = 1, Request $request)
    {
        try {
            $session = $request->getSession();
            $em = $this->getDoctrine()->getManager();

            // Get workflows filtered by rule id
            $entities = $em->getRepository(Workflow::class)->findBy(['rule' => $ruleId]);

            // List of task limited to 1000 and order by status (start first) and date begin
            $compact = $this->nav_pagination([
                'adapter_em_repository' => $entities,
                'maxPerPage' => $this->params['pager'] ?? 25,
                'page' => $page,
            ], false);

            return $this->render(
                'Workflow/list.html.twig',
                [
                    'entities' => $entities,
                    'nb_workflow' => count($entities),
                    'pager' => $compact['pager'],
                ]
            );
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