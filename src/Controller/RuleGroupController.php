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
use App\Entity\Rulegroup;
use App\Entity\Connector;
use App\Entity\Functions;
use App\Entity\RuleAudit;
use App\Entity\RuleField;
use App\Entity\RuleParam;
use App\Entity\RuleFilter;
use Pagerfanta\Pagerfanta;
use App\Entity\RulegroupLog;
use App\Form\ConnectorType;
use App\Manager\JobManager;
use App\Manager\HomeManager;
use App\Manager\RuleManager;
use Doctrine\ORM\Mapping\Id;
use Psr\Log\LoggerInterface;
use App\Entity\RulegroupAudit;
use App\Manager\ToolsManager;
use Doctrine\DBAL\Connection;
use App\Entity\ConnectorParam;
use App\Entity\RuleParamAudit;
use App\Entity\RulegroupAction;
use App\Form\Type\RulegroupType;
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
use App\Repository\RuleGroupRepository;
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
 * @Route("/rulegroup")
 */
class RuleGroupController extends AbstractController
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
    private RulegroupRepository $RuleGroupRepository;

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
        RulegroupRepository $RuleGroupRepository,
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
        $this->RuleGroupRepository = $RuleGroupRepository;
    }

    protected function getInstanceBdd() {}


    /* ******************************************************
         * RULE
         ****************************************************** */

/**
     * LISTE DES RuleGroups.
     *
     * @return RedirectResponse|Response
     *
     * @Route("/list", name="rulegroup_list", defaults={"page"=1})
     * @Route("/list/page-{page}", name="rulegroup_list_page", requirements={"page"="\d+"})
     */
    public function RulegroupListAction(int $page = 1, Request $request)
    {
        try {
            
            // Récupérer les filtres depuis la requête
            $rulegroupName = $request->query->get('rulegroup_name');
            $ruleName = $request->query->get('rule_name');

            // Utilisation de findBy pour récupérer les rulegroups
            $criteria = ['deleted' => 0];
            $orderBy = ['order' => 'ASC'];
            $rulegroups = $this->entityManager->getRepository(Rulegroup::class)->findBy($criteria, $orderBy);

            if ($rulegroupName) {
                $rulegroups = array_filter($rulegroups, function($rulegroup) use ($rulegroupName) {
                    return stripos($rulegroup->getName(), $rulegroupName) !== false;
                });
            }

            if ($ruleName) {
                $rulegroups = array_filter($rulegroups, function($rulegroup) use ($ruleName) {
                    return stripos($rulegroup->getRule()->getName(), $ruleName) !== false;
                });
            }

            // Pagination avec ArrayAdapter car findBy retourne un tableau
            $adapter = new ArrayAdapter($rulegroups);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage(15);
            $pager->setCurrentPage($page);

            // Si la requête est AJAX, rendre uniquement la table des rulegroups
            if ($request->isXmlHttpRequest()) {
                return $this->render('Rulegroup/_rulegroup_table.html.twig', [
                    'entities' => $pager->getCurrentPageResults(),
                    'pager' => $pager,
                ]);
            }

            // Si ce n'est pas une requête AJAX, rendre la page complète
            return $this->render(
                'Rulegroup/list.html.twig',
                [
                    'entities' => $pager->getCurrentPageResults(),
                    'nb_rulegroup' => $pager->getNbResults(),
                    'pager_rulegroup_list' => $pager,
                ]
            );
        } catch (Exception $e) {
            throw $this->createNotFoundException('Erreur : ' . $e->getMessage());
        }
    }

    // /**
    //  * @Route("/list/rulegroup/{ruleId}", name="rulegroup_list_by_rule", defaults={"page"=1})
    //  * @Route("/list/rulegroup/{ruleId}/page-{page}", name="rulegroup_list_by_rule_page", requirements={"page"="\d+"})
    //  */
    // public function RulegroupListByRuleAction(string $ruleId, int $page = 1, Request $request)
    // {
    //     try {


    //         // Récupération des rulegroups par règle
    //         $rulegroups = $this->entityManager->getRepository(Rulegroup::class)->findBy(
    //             ['rule' => $ruleId, 'deleted' => 0],
    //             ['order' => 'ASC']
    //         );

    //         // Pagination avec ArrayAdapter
    //         $adapter = new ArrayAdapter($rulegroups);
    //         $pager = new Pagerfanta($adapter);
    //         $pager->setMaxPerPage(15);
    //         $pager->setCurrentPage($page);

    //         // Rendu des rulegroups paginés
    //         return $this->render('Rulegroup/list.html.twig', [
    //             'entities' => $pager->getCurrentPageResults(),
    //             'nb_rulegroup' => $pager->getNbResults(),
    //             'pager_rulegroup_list' => $pager,
    //         ]);
    //     } catch (Exception $e) {
    //         throw $this->createNotFoundException('Erreur : ' . $e->getMessage());
    //     }
    // }


    // // public function to delet the rulegroup by id (set deleted to 1)
    // /**
    //  * @Route("/delete/{id}", name="rulegroup_delete")
    //  */
    // public function RulegroupDeleteAction(string $id, Request $request)
    // {
    //     try {


    //         $em = $this->getDoctrine()->getManager();
    //         $rulegroupSearchResult = $em->getRepository(Rulegroup::class)->findBy(['id' => $id, 'deleted' => 0]);
    //         $rulegroup = $rulegroupSearchResult[0];


    //         if ($rulegroup) {
    //             $this->saveRulegroupAudit($rulegroup->getId());
    //             $rulegroup->setDeleted(1);
    //             $em->persist($rulegroup);
    //             $em->flush();
    //             $this->addFlash('success', 'Rulegroup deleted successfully');
    //         } else {
    //             $this->addFlash('error', 'Rulegroup not found');
    //         }

    //         return $this->redirectToRoute('rulegroup_list');
    //     } catch (Exception $e) {
    //         throw $this->createNotFoundException('Error : ' . $e);
    //     }
    // }

    // // public function to save the rulegroupAudit to the database
    // public function saveRulegroupAudit($rulegroupId)
    // {

        

    //     $em = $this->getDoctrine()->getManager();
    //     $rulegroupArray = $em->getRepository(Rulegroup::class)->findBy(['id' => $rulegroupId, 'deleted' => 0]);
    //     $rulegroup = $rulegroupArray[0];

    //     // get all the actions of the rulegroup
    //     $actions = $rulegroup->getRulegroupActions();

    //     $actionsArray = array_map(function ($action) {
    //         return [
    //             'id' => $action->getId(),
    //             'rulegroup' => $action->getRulegroup()->getId(),
    //             'dateCreated' => $action->getDateCreated()->format('Y-m-d H:i:s'),
    //             'dateModified' => $action->getDateModified()->format('Y-m-d H:i:s'),
    //             'createdBy' => $action->getCreatedBy()->getUsername(),
    //             'modifiedBy' => $action->getModifiedBy()->getUsername(),
    //             'name' => $action->getName(),
    //             'action' => $action->getAction(),
    //             'description' => $action->getDescription(),
    //             'order' => $action->getOrder(),
    //             'active' => $action->getActive(),
    //             'deleted' => $action->getDeleted(),
    //             'arguments' => $action->getArguments(),
    //         ];
    //     }, $actions->toArray());

    //     // Encode every rulegroup parameters
    //     $rulegroupdata = json_encode(
    //         [
    //             'rulegroupName' => $rulegroup->getName(),
    //             'ruleId' => $rulegroup->getRule()->getId(),
    //             'created_by' => $rulegroup->getCreatedBy()->getUsername(),
    //             'rulegroupDescription' => $rulegroup->getDescription(),
    //             'condition' => $rulegroup->getCondition(),
    //             'active' => $rulegroup->getActive(),
    //             'dateCreated' => $rulegroup->getDateCreated()->format('Y-m-d H:i:s'),
    //             'dateModified' => $rulegroup->getDateModified()->format('Y-m-d H:i:s'),
    //             'actions' => $actionsArray,
    //         ]
    //     );
    //     // Save the rulegroup audit
    //     $onerulegroupAudit = new RulegroupAudit();
    //     $onerulegroupAudit->setrulegroup($rulegroup);
    //     $onerulegroupAudit->setDateCreated(new \DateTime());
    //     $onerulegroupAudit->setData($rulegroupdata);
    //     $this->entityManager->persist($onerulegroupAudit);
    //     $this->entityManager->flush();
    // }

    // // public function to set the rulegroup to active or inactive
    // /**
    //  * @Route("/active/{id}", name="rulegroup_active")
    //  */
    // public function RulegroupActiveAction(string $id, Request $request)
    // {
    //     try {

            
    //         $em = $this->getDoctrine()->getManager();
    //         $rulegroupResult = $em->getRepository(Rulegroup::class)->findBy(['id' => $id, 'deleted' => 0]);
    //         $rulegroup = $rulegroupResult[0];


    //         if ($rulegroup) {
    //             $rulegroup->setActive($rulegroup->getActive() == 1 ? 0 : 1);
    //             $em->persist($rulegroup);
    //             $em->flush();
    //             $this->addFlash('success', 'Rulegroup updated successfully');
    //         } else {
    //             $this->addFlash('error', 'Rulegroup not found');
    //         }

    //         return $this->redirectToRoute('rulegroup_list');
    //     } catch (Exception $e) {
    //         throw $this->createNotFoundException('Error : ' . $e);
    //     }
    // }

    // /**
    //  * @Route("/active_show/{id}", name="rulegroup_active_show")
    //  */
    // public function RulegroupActiveShowAction(string $id, Request $request)
    // {
    //     try {

            
    //         $em = $this->getDoctrine()->getManager();
    //         $rulegroupResult = $em->getRepository(Rulegroup::class)->findBy(['id' => $id, 'deleted' => 0]);
    //         $rulegroup = $rulegroupResult[0];

    //         if ($rulegroup) {
    //             $rulegroup->setActive($rulegroup->getActive() == 1 ? 0 : 1);
    //             $em->persist($rulegroup);
    //             $em->flush();
    //             $this->addFlash('success', 'Rulegroup updated successfully');
    //         } else {
    //             $this->addFlash('error', 'Rulegroup not found');
    //         }

    //         return $this->redirectToRoute('rulegroup_show', ['id' => $id]);
    //     } catch (Exception $e) {
    //         throw $this->createNotFoundException('Error : ' . $e);
    //     }
    // }

    // // public function to toggle the rulegroup to active or inactive
    // #[Route('/rulegroup/toggle/{id}', name: 'rulegroup_toggle', methods: ['POST'])]
    // public function toggleRulegroup(Request $request, EntityManagerInterface $em, RulegroupRepository $rulegroupRepository, string $id): JsonResponse
    // {


    //     $rulegroup = $rulegroupRepository->find($id);

    //     if (!$rulegroup) {
    //         return new JsonResponse(['status' => 'error', 'message' => 'Rulegroup not found'], 404);
    //     }

    //     $rulegroup->setActive(!$rulegroup->getActive());
    //     $rulegroup->setDateModified(new \DateTime());

    //     try {
    //         $em->persist($rulegroup);
    //         $em->flush();
    //     } catch (\Exception $e) {
    //         return new JsonResponse(['status' => 'error', 'message' => 'Erreur lors de la sauvegarde du rulegroup'], 500);
    //     }

    //     return new JsonResponse(['status' => 'success', 'active' => $rulegroup->getActive()]);
    // }

    // // public function to create a new rulegroup
    // /**
    //  * @Route("/new", name="rulegroup_create")
    //  */
    // public function RulegroupCreateAction(Request $request)
    // {
    //     try {


    //         $rules = RuleRepository::findActiveRulesNames($this->entityManager);

    //         $em = $this->getDoctrine()->getManager();
    //         $rulegroup = new Rulegroup();
    //         $rulegroup->setId(uniqid());
    //         $form = $this->createForm(RulegroupType::class, $rulegroup, [
    //             'entityManager' => $em,
    //         ]);
    //         $form->handleRequest($request);

    //         if ($form->isSubmitted() && $form->isValid()) {
    //             $rulegroup->setCreatedBy($this->getUser());
    //             $rulegroup->setModifiedBy($this->getUser());
    //             $em->persist($rulegroup);
    //             $em->flush();

    //             // Save the rulegroup audit
    //             $this->saveRulegroupAudit($rulegroup->getId());

    //             $this->addFlash('success', 'Rulegroup created successfully');

    //             return $this->redirectToRoute('rulegroup_show', ['id' => $rulegroup->getId()]);
    //         }

    //         return $this->render(
    //             'Rulegroup/new.html.twig',
    //             [
    //                 'form' => $form->createView(),
    //             ]
    //         );
    //     } catch (Exception $e) {
    //         throw $this->createNotFoundException('Error : ' . $e);
    //     }
    // }

    // /**
    //  * @Route("/show/{id}", name="rulegroup_show", defaults={"page"=1})
    //  * @Route("/show/{id}/page-{page}", name="rulegroup_show_page", requirements={"page"="\d+"})
    //  */
    // public function RulegroupShowAction(string $id, Request $request, int $page): Response
    // {
    //     try {

            
    //         $em = $this->getDoctrine()->getManager();
    //         $rulegroup = $em->getRepository(Rulegroup::class)->findBy(['id' => $id, 'deleted' => 0]);

    //         $rulegroupLogs = $em->getRepository(RulegroupLog::class)->findBy(
    //             ['rulegroup' => $id],
    //             ['dateCreated' => 'DESC']
    //         );
    //         $query = $this->rulegroupLogRepository->findLogsByRulegroupId($id);

    //         $adapter = new QueryAdapter($query);
    //         $pager = new Pagerfanta($adapter);
    //         $pager->setMaxPerPage(10);
    //         $pager->setCurrentPage($page);

    //         if ($rulegroup[0]) {
    //             $nb_rulegroup = count($rulegroupLogs);
    //             return $this->render(
    //                 'Rulegroup/show.html.twig',
    //                 [
    //                     'rulegroup' => $rulegroup[0],
    //                     'rulegroupLogs' => $rulegroupLogs,
    //                     'nb_rulegroup' => $nb_rulegroup,
    //                     'pager' => $pager,
    //                 ]
    //             );
    //         } else {
    //             $this->addFlash('error', 'Rulegroup not found');
    //             return $this->redirectToRoute('rulegroup_list');
    //         }
    //     } catch (Exception $e) {
    //         throw $this->createNotFoundException('Error : ' . $e);
    //     }
    // }


    // // public function to edit a rulegroup
    // /**
    //  * @Route("/edit/{id}", name="rulegroup_edit")
    //  */
    // public function RulegroupEditAction(string $id, Request $request)
    // {
    //     try {

            
    //         $em = $this->getDoctrine()->getManager();
    //         $rulegroupArray = $em->getRepository(Rulegroup::class)->findBy(['id' => $id, 'deleted' => 0]);
    //         $rulegroup = $rulegroupArray[0];

    //         if ($rulegroup) {
    //             $form = $this->createForm(RulegroupType::class, $rulegroup, [
    //                 'entityManager' => $em,
    //                 'entity' => $rulegroup,
    //             ]);
    //             $form->handleRequest($request);

    //             if ($form->isSubmitted() && $form->isValid()) {
    //                 $rulegroup->setModifiedBy($this->getUser());
    //                 $em->persist($rulegroup);
    //                 $em->flush();
    //                 $this->addFlash('success', 'Rulegroup updated successfully');

    //                 $this->saveRulegroupAudit($rulegroup->getId());

    //                 return $this->redirectToRoute('rulegroup_show', ['id' => $rulegroup->getId()]);
    //             }

    //             return $this->render(
    //                 'Rulegroup/edit.html.twig',
    //                 [
    //                     'form' => $form->createView(),
    //                     'rulegroup' => $rulegroup,
    //                 ]
    //             );
    //         } else {
    //             $this->addFlash('error', 'Rulegroup not found');

    //             return $this->redirectToRoute('rulegroup_list');
    //         }
    //     } catch (Exception $e) {
    //         throw $this->createNotFoundException('Error : ' . $e);
    //     }
    // }
}