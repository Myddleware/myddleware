<?php

/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com

 This file is part of Myddleware.

 Myddleware is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Myddleware is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

namespace App\Controller;

			   
use Exception;
use App\Entity\Log;
use App\Entity\Rule;
use App\Entity\Config;
use App\Entity\Document;
use App\Entity\Job;					
use Pagerfanta\Pagerfanta;
use App\Entity\WorkflowLog;
use App\Manager\JobManager;
use App\Entity\DocumentData;
use App\Entity\DocumentAudit;
use App\Service\SessionService;
use App\Manager\DocumentManager;
use App\Manager\SolutionManager;
use App\Entity\DocumentRelationship;
use Pagerfanta\Adapter\ArrayAdapter;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use App\Form\Type\DocumentCommentType;							
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Manager\ToolsManager;
use Doctrine\DBAL\Connection;


/**
 * @Route("/rule")
 */
class FluxController extends AbstractController
{
    protected Connection $connection;
    protected $params;
    private SessionService $sessionService;
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private JobManager $jobManager;
    private SolutionManager $solutionManager;
    private DocumentRepository $documentRepository;
    private ToolsManager $toolsManager;
    public function __construct(
        SessionService $sessionService,
        TranslatorInterface $translator,
        JobManager $jobManager,
        SolutionManager $solutionManager,
        DocumentRepository $documentRepository,
        EntityManagerInterface $entityManager,
        ToolsManager $toolsManager,
        Connection $connection
    ) {
        $this->sessionService = $sessionService;
        $this->translator = $translator;
        $this->jobManager = $jobManager;
        $this->solutionManager = $solutionManager;
        $this->documentRepository = $documentRepository;
        $this->entityManager = $entityManager;
        $this->toolsManager = $toolsManager;
        $this->connection = $connection;
        // Init parameters
        $configRepository = $this->entityManager->getRepository(Config::class);
        $configs = $configRepository->findAll();
        if (!empty($configs)) {
            foreach ($configs as $config) {
                $this->params[$config->getName()] = $config->getvalue();
            }
        }
    }

    /* ******************************************************
        * FLUX
        ****************************************************** */

    /**
     * @Route("/flux/error/{id}", name="flux_error_rule")
     */
    public function fluxErrorByRule($id): RedirectResponse
    {
        $em = $this->getDoctrine()->getManager();

        if ($this->getUser()->isAdmin()) {
            $list_fields_sql =
                ['id' => $id];
        } else {
            $list_fields_sql =
                [
                    'id' => $id,
                    'createdBy' => $this->getUser()->getId(),
                ];
        }
        // Detecte si la session est le support ---------

        // Infos des flux
        $rule = $em->getRepository(Rule::class)
            ->findBy($list_fields_sql);
        if ($rule) {
            $this->sessionService->setFluxFilterRuleName($rule[0]->getName());
            $this->sessionService->setFluxFilterGlobalStatus('Error');
            $this->sessionService->setFluxFilterWhere(['rule' => $rule[0]->getName(), 'gblstatus' => ['Error', 'Open']]);
        } else {
            $this->sessionService->removeFluxFilter();
        }

        return $this->redirect($this->generateUrl('document_list', ['search' => 1]));
    }

    /**
     * @Route("/flux/list/search-{search}", name="flux_list", defaults={"page"=1})
     * @Route("/flux/list/page-{page}", name="flux_list_page", requirements={"page"="\d+"})
     */
    public function fluxListAction(Request $request, int $page = 1, int $search = 1): Response
    {
        //--- Liste status traduction
        $lstStatusTwig = DocumentManager::lstStatus();
        foreach ($lstStatusTwig as $key => $value) {
            $lstStatus[$this->translator->trans($value)] = $key;
        }
        asort($lstStatus);
        //---

        //--- Liste Global status traduction
        $lstGblStatusTwig = DocumentManager::lstGblStatus();

        foreach ($lstGblStatusTwig as $key => $value) {
            $lstGblStatus[$this->translator->trans($value)] = $key;
        }
        asort($lstGblStatus);
        //---

        //--- Liste type translation
        $lstTypeTwig = DocumentManager::lstType();

        foreach ($lstTypeTwig as $key => $value) {
            $lstType[$this->translator->trans($value)] = $key;
        }
        //---

        $em = $this->getDoctrine()->getManager();

        if ($this->getUser()->isAdmin()) {
            $rule = $this->getDoctrine()
                ->getManager()
                ->getRepository(Rule::class)
                ->findBy(['deleted' => 0]);
        } else {
            $list_fields_sql =
                [
                    'createdBy' => $this->getUser()->getId(),
                ];

            $rule = $em->getRepository(Rule::class)->findBy($list_fields_sql);
        }
        // Detecte si la session est le support ---------

        // Liste des règles
        $lstRuleName = [];
        if ($rule) {
            foreach ($rule as $r) {
                $lstRuleName[$r->getName()] = $r->getName();
            }

            asort($lstRuleName);
        }

        $form = $this->createFormBuilder()

            /* ->add('date_create_start','text', array(
                'data'=> ($this->sessionService->isFluxFilterCDateCreateStartExist() ? $this->sessionService->getFluxFilterDateCreateStart() : false),
                'required'=> false,
                'attr' => array('class' => 'calendar')))

            ->add('date_create_end','text', array(
                'data'=> ($this->sessionService->isFluxFilterCDateCreateEndExist() ? $this->sessionService->getFluxFilterDateCreateEnd() : false),
                'required'=> false,
                'attr' => array('class' => 'calendar'))) */

            ->add('source_content', TextType::class, [
                'data' => ($this->sessionService->isFluxFilterCSourceContentExist() ? $this->sessionService->getFluxFilterSourceContent() : false),
                'required' => false,
            ])

            ->add('target_content', TextType::class, [
                'data' => ($this->sessionService->isFluxFilterCTargetContentExist() ? $this->sessionService->getFluxFilterTargetContent() : false),
                'required' => false,
            ])

            ->add('date_modif_start', TextType::class, [
                'data' => ($this->sessionService->isFluxFilterCDateModifStartExist() ? $this->sessionService->getFluxFilterDateModifStart() : false),
                'required' => false,
                'attr' => ['class' => 'calendar'],
            ])

            ->add('date_modif_end', TextType::class, [
                'data' => ($this->sessionService->isFluxFilterCDateModifEndExist() ? $this->sessionService->getFluxFilterDateModifEnd() : false),
                'required' => false,
                'attr' => ['class' => 'calendar'],
            ])

            ->add('rule', TextType::class, [
                'data' => ($this->sessionService->isFluxFilterCRuleExist() ? $this->sessionService->getFluxFilterRuleName() : false),
                'required' => false,
            ])

            ->add('rule', ChoiceType::class, [
                'choices' => $lstRuleName,
                'data' => ($this->sessionService->isFluxFilterCRuleExist() ? $this->sessionService->getFluxFilterRuleName() : false),
                'required' => false,
            ])

            ->add('status', ChoiceType::class, [
                'choices' => $lstStatus,
                'data' => ($this->sessionService->isFluxFilterCStatusExist() ? $this->sessionService->getFluxFilterStatus() : false),
                'required' => false,
            ])

            ->add('gblstatus', ChoiceType::class, [
                'choices' => $lstGblStatus,
                'data' => ($this->sessionService->isFluxFilterCGblStatusExist() ? $this->sessionService->getFluxFilterGlobalStatus() : false),
                'required' => false,
            ])

            ->add('type', ChoiceType::class, [
                'choices' => $lstType,
                'data' => ($this->sessionService->isFluxFilterTypeExist() ? $this->sessionService->getFluxFilterType() : false),
                'required' => false,
            ])

            ->add('source_id', TextType::class, [
                'data' => ($this->sessionService->isFluxFilterCSourceIdExist() ? $this->sessionService->getFluxFilterSourceId() : false),
                'required' => false,
            ])

            ->add('target_id', TextType::class, [
                'data' => ($this->sessionService->isFluxFilterCTargetIdExist() ? $this->sessionService->getFluxFilterTargetId() : false),
                'required' => false,
            ])

            ->add('click_filter', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary ms-4 mt-4',
                ],
                'label' => $this->translator->trans('list_flux.btn.filter'),
            ])

            ->getForm();

        $form->handleRequest($request);
        // condition d'affichage
        $data = [];
        if (!empty($this->sessionService->getFluxFilterWhere())) {
            $data['customWhere'] = $this->sessionService->getFluxFilterWhere();
            $this->sessionService->removeFluxFilter();
        }

        // Get the limit parameter
        $configRepository = $this->getDoctrine()->getManager()->getRepository(Config::class);
        $searchLimit = $configRepository->findOneBy(['name' => 'search_limit']);
        if (!empty($searchLimit)) {
            $limit = $searchLimit->getValue();
        }
        
        $conditions = 0;
        $doNotSearch = false;
        //---[ FORM ]-------------------------
        if ($form->get('click_filter')->isClicked()) {
            $data = $form->getData();
            $data['user'] = $this->getUser();
            $data['search'] = $search;
            $data['page'] = $page;

            if (!empty($data['source_content']) && is_string($data['source_content'])) {
                $this->sessionService->setFluxFilterSourceContent($data['source_content']);
            } else {
                $this->sessionService->removeFluxFilterSourceContent();
            }

            if (!empty($data['target_content']) && is_string($data['target_content'])) {
                $this->sessionService->setFluxFilterTargetContent($data['target_content']);
            } else {
                $this->sessionService->removeFluxFilterTargetContent();
            }

            if (!empty($data['date_modif_start']) && is_string($data['date_modif_start'])) {
                $this->sessionService->setFluxFilterDateModifStart($data['date_modif_start']);
            } else {
                $this->sessionService->removeFluxFilterDateModifStart();
            }

            if (!empty($data['date_modif_end']) && is_string($data['date_modif_end'])) {
                $this->sessionService->setFluxFilterDateModifEnd($data['date_modif_end']);
            } else {
                $this->sessionService->removeFluxFilterDateModifEnd();
            }

            if (!empty($data['rule']) && is_string($data['rule'])) {
                $this->sessionService->setFluxFilterRuleName($data['rule']);
            } else {
                $this->sessionService->removeFluxFilterRuleName();
            }

            if (!empty($data['status'])) {
                $this->sessionService->setFluxFilterStatus($data['status']);
            } else {
                $this->sessionService->removeFluxFilterStatus();
            }

            if (!empty($data['gblstatus'])) {
                $this->sessionService->setFluxFilterGlobalStatus($data['gblstatus']);
            } else {
                $this->sessionService->removeFluxFilterGblStatus();
            }

            if (!empty($data['type'])) {
                $this->sessionService->setFluxFilterType($data['type']);
            } else {
                $this->sessionService->removeFluxFilterType();
            }

            if (!empty($data['target_id'])) {
                $this->sessionService->setFluxFilterTargetId($data['target_id']);
            } else {
                $this->sessionService->removeFluxFilterTargetId();
            }

            if (!empty($data['source_id'])) {
                $this->sessionService->setFluxFilterSourceId($data['source_id']);
            } else {
                $this->sessionService->removeFluxFilterSourceId();
            }
       } // end clicked
        //---[ FORM ]-------------------------
        // In case of pagination
        else {
            $data['source_content']     = $this->sessionService->getFluxFilterSourceContent();
            $data['target_content']     = $this->sessionService->getFluxFilterTargetContent();
            $data['date_modif_start']   = $this->sessionService->getFluxFilterDateModifStart();
            $data['date_modif_end']     = $this->sessionService->getFluxFilterDateModifEnd();
            $data['rule']               = $this->sessionService->getFluxFilterRuleName();
            $data['status']             = $this->sessionService->getFluxFilterStatus();
            $data['gblstatus']          = $this->sessionService->getFluxFilterGlobalStatus();
            $data['type']               = $this->sessionService->getFluxFilterType();
            $data['target_id']          = $this->sessionService->getFluxFilterTargetId();
            $data['source_id']          = $this->sessionService->getFluxFilterSourceId();
            // No search if no filter and page = 1. 
			// We keep searching if someone searched without filter and clicke on another page
			if (
                    count(array_filter($data)) === 0
                AND $page == 1
            ) {
                $doNotSearch = true;
            }
        }

        if (!$doNotSearch) {
			$resultSearch = $this->searchDocuments($data, $page, $limit); 
		} else {
			$resultSearch = array();
		}

        $compact = $this->nav_pagination([
            'adapter_em_repository' => $resultSearch,
            'maxPerPage' => $this->params['pager'] ?? 25,
            'page' => $page,
        ], false);

        // Si tout se passe bien dans la pagination
        if ($compact) {
            // Si aucune règle
            if ($compact['nb'] < 1 && !intval($compact['nb'])) {
                $compact['entities'] = '';
                $compact['pager'] = '';
            }

            // affiche le bouton pour supprimer les filtres si les conditions proviennent du tableau de bord
            if ($this->sessionService->isFluxFilterCExist()) {
                $conditions = 1;
            }

            //Check the user timezone
            if ($timezone = '') {
                $timezone = 'UTC';
            } else {
                $timezone = $this->getUser()->getTimezone();
            }

            return $this->render(
                'Flux/list.html.twig',
                [
                    'nb' => $compact['nb'],
                    'entities' => $compact['entities'],
                    'pager' => $compact['pager'],
                    'form' => $form->createView(),
                    'condition' => $conditions,
                    'timezone' => $timezone,
                ]
            );
        }

        throw $this->createNotFoundException('Error');
    }

    
    protected function searchDocuments($data, $page = 1, $limit = 1000) {
        $join = '';
        $where = '';

        // Build the WHERE depending on $data
        // Source content
        if (!empty($data['source_content'])) {
            $join .= " INNER JOIN documentdata document_data_source ON document_data_source.doc_id = document.id ";
            $where .= " AND document_data_source.data LIKE :source_content 
                        AND document_data_source.type = 'S'";
        }
        // Target content
        if (!empty($data['target_content'])) {
            $join .= " INNER JOIN documentdata document_data_target ON document_data_target.doc_id = document.id ";
            $where .= " AND document_data_target.data LIKE :target_content 
                        AND document_data_target.type = 'T'";
        }
        // Date modified (start) 
        if (!empty($data['date_modif_start'])) {
            $where .= " AND document.date_modified >= :dateModifiedStart ";
        }
        // Date modified (end)
        if (!empty($data['date_modif_end'])) {
            $where .= " AND document.date_modified <= :dateModifiedEnd ";
        }
        // Rule
        if (
                !empty($data['rule'])
            OR !empty($data['customWhere']['rule'])
        ) {
            $where .= " AND rule.name = :ruleName ";
        }
        // Status
        if (!empty($data['status'])) {
            $where .= " AND document.status = :status ";
        }

        // customWhere can have several status (open and error from the error dashlet in the home page)
        if (!empty($data['customWhere']['gblstatus'])) {
            $i = 0;
            $where .= " AND ( ";
            foreach($data['customWhere']['gblstatus'] as $globalStatus) {
                $where .= " document.global_status = :gblstatus".$i." OR";
                $i++;
            }
            $where = rtrim($where, 'OR').' )';
        } elseif (!empty($data['gblstatus'])) {
            $where .= " AND document.global_status = :gblstatus ";
        }

        // Type
        if (!empty($data['type'])) {
            $where .= " AND document.type = :type ";
        }
        // Target ID
        if (!empty($data['target_id'])) {
            $where .= " AND document.target_id LIKE :target_id ";
        }
        // Source ID
        if (!empty($data['source_id'])) {
            $where .= " AND document.source_id LIKE :source_id ";
        }

        // Build query
        $query = "
            SELECT 
                document.id, 
                document.date_created, 
                document.date_modified, 
                document.status, 
                document.source_id, 
                document.target_id, 
                document.source_date_modified, 
                document.mode, 
                document.type, 
                document.attempt, 
                document.global_status, 
                rule.name as rule_name, 
                rule.id as rule_id 
            FROM document 
                INNER JOIN rule	
                    ON document.rule_id = rule.id "
                .$join. 
            " WHERE 
                    document.deleted = 0 "
                    .$where.
            " ORDER BY document.date_modified DESC"
            ." LIMIT ". $limit;
        $stmt = $this->getDoctrine()->getManager()->getConnection()->prepare($query);
        // Add parameters to the query
        // Source content
        if (!empty($data['source_content'])) {
            $stmt->bindValue(':source_content', "%".$data['source_content']."%");
        }
        // Target content
        if (!empty($data['target_content'])) {
            $stmt->bindValue(':target_content', "%".$data['target_content']."%");
        }
        // Date modified start
        if (!empty($data['date_modif_start'])) {
            $stmt->bindValue(':dateModifiedStart', str_replace(',','',$data['date_modif_start']));
        }
        // Date modified end
        if (!empty($data['date_modif_end'])) {
            $stmt->bindValue(':dateModifiedEnd', $data['date_modif_end']);
        }
        // Rule
        if (
                !empty($data['rule'])
             OR !empty($data['customWhere']['rule'])
         ) {
            $ruleFilter = trim((!empty($data['customWhere']['rule']) ? $data['customWhere']['rule'] : $data['rule']));
            $stmt->bindValue(':ruleName', $ruleFilter);
        }
        // Status
        if (!empty($data['status'])) {
            $stmt->bindValue(':status', $data['status']);
        }
        // customWhere can have several status (open and error from the error dashlet in the home page)
        if (!empty($data['customWhere']['gblstatus'])) {
            $i = 0;
            foreach($data['customWhere']['gblstatus'] as $globalStatus) {
                $stmt->bindValue(':gblstatus'.$i, $gblstatus);
                $i++;
            }
        } elseif (!empty($data['gblstatus'])) {
            $stmt->bindValue(':gblstatus', $data['gblstatus']);
        }
        // Type
        if (!empty($data['type'])) {
            $stmt->bindValue(':type', $data['type']);
        }
        // Target id
        if (!empty($data['target_id'])) {
            $stmt->bindValue(':target_id', $data['target_id']);
        }
        // Source id
        if (!empty($data['source_id'])) {
            $stmt->bindValue(':source_id', $data['source_id']);
        }
        // Run the query and return the results
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * @Route("/flux/list/delete/filter", name="flux_list_delete_filter")
     */
    public function fluxListDeleteFilter(): RedirectResponse
    {
        if ($this->sessionService->isFluxFilterExist()) {
            $this->sessionService->removeFluxFilter();
        }

        return $this->redirect($this->generateUrl('document_list', ['search' => 1]));
    }

/**
 * @Route("/flux/{id}/log/{logPage}", name="flux_info", defaults={"page"=1, "logPage"=1})
 * @Route("/flux/{id}/log/page-{page}/log-{logPage}", name="flux_info_page", requirements={"page"="\d+", "logPage"="\d+"})
 */
public function fluxInfo(Request $request, $id, $page, $logPage)

    {
        try {

            $documentPage = $request->attributes->get('page', 1);
            $logPage = $request->attributes->get('logPage', 1);

            $session = $request->getSession();
            $em = $this->getDoctrine()->getManager();

            $list_fields_sql = ['id' => $id];

            // Infos des flux
            $doc = $em->getRepository(Document::class)
                ->findBy($list_fields_sql);

            if ($doc[0]->getDeleted()) {
                $session->set('warning', [$this->translator->trans('error.document.deleted_flag')]);
            }
            if (!$this->getUser()->isAdmin()) {
                if (
                    empty($doc[0])
                    || $doc[0]->getCreatedBy() != $this->getUser()->getId()
                ) {
                    return $this->redirect($this->generateUrl('flux_list', ['search' => 1]));
                }
            }
            // Detecte si la session est le support ---------

            // Get rule object
            $rule = $em->getRepository(Rule::class)->find($doc[0]->getRule());

            // Loading tables source, target, history
            $sourceData = $this->listeFluxTable($id, 'S');
            $targetData = $this->listeFluxTable($id, 'T');
            $historyData = $this->listeFluxTable($id, 'H');

            $compact = $this->nav_pagination([
                'adapter_em_repository' => $em->getRepository(Document::class)->findBy(
                    ['source' => $doc[0]->getSource(), 'rule' => $doc[0]->getRule(), 'deleted' => 0],
                    ['dateModified' => 'DESC']
                ),
                'maxPerPage' => $this->params['pager'],
                'page' => $page,
            ], false);

            // POST DOCUMENT
            // Get the post documents (Document coming from a child rule)
            $postDocuments = $em->getRepository(Document::class)->findBy(
                ['parentId' => $id],
                ['dateCreated' => 'DESC'],    // order
                10                                // limit
            );
            // Get the rule name of every child doc
            $postDocumentsRule = [];
            foreach ($postDocuments as $postDocument) {
                $postDocumentsRule[$postDocument->getId()] = $em->getRepository(Rule::class)->find($postDocument->getRule())->getName();
            }

            // PARENT RELATE DOCUMENT
            // Document link to other document, the parent ones
            $parentRelationships = $em->getRepository(DocumentRelationship::class)->findBy(
                ['doc_id' => $doc[0]->getId()],
                ['dateCreated' => 'DESC'],        // order
                10                                    // limit
            );
            // Get the detail of documents related
            $i = 0;
            $parentDocuments = [];
            $parentDocumentsRule = [];
            foreach ($parentRelationships as $parentRelationship) {
                $parentDocuments[$i] = $em->getRepository(Document::class)->find($parentRelationship->getDocRelId());
                $parentDocuments[$i]->sourceField = $parentRelationship->getSourceField();
                // Get the rule name of every relate doc
                foreach ($parentDocuments as $parentDocument) {
                    $parentDocumentsRule[$parentDocument->getId()] = $em->getRepository(Rule::class)->find($parentDocument->getRule())->getName();
                }
                ++$i;
            }

            // CHILD RELATE DOCUMENT
            // Document link to other document, the child ones
            $childRelationships = $em->getRepository(DocumentRelationship::class)->findBy(
                ['doc_rel_id' => $doc[0]->getId()],
                ['dateCreated' => 'DESC'],            // order
                10                                        // limit
            );
            // Get the detail of documents related
            $i = 0;
            $childDocuments = [];
            $childDocumentsRule = [];
            foreach ($childRelationships as $childRelationship) {
                $childDocuments[$i] = $em->getRepository(Document::class)->find($childRelationship->getDocId());
                $childDocuments[$i]->sourceField = $childRelationship->getSourceField();
                // Get the rule name of every relate doc
                foreach ($childDocuments as $childDocument) {
                    $childDocumentsRule[$childDocument->getId()] = $em->getRepository(Rule::class)->find($childDocument->getRule())->getName();
                }
                ++$i;
            }

            // HISTORY DOCUMENT
            // Get the history documents (all document for the same source)
            $historyDocuments = $em->getRepository(Document::class)->findBy(['source' => $doc[0]->getSource(), 'rule' => $doc[0]->getRule(), 'deleted' => 0], ['dateModified' => 'DESC'], 10);
            // If only one record, the history is the current document, so we remove it => no parent
            if (1 == count($historyDocuments)) {
                $historyDocuments = [];
            }


            // Add custom button
            $name_solution_target = $rule->getConnectorTarget()->getSolution()->getName();
            $solution_target = $this->solutionManager->get($name_solution_target);
            $solution_target_btn = $solution_target->getDocumentButton($doc[0]->getId());

            $name_solution_source = $rule->getConnectorSource()->getSolution()->getName();
            $solution_source = $this->solutionManager->get($name_solution_source);
            $solution_source_btn = $solution_source->getDocumentButton($doc[0]->getId());
            $list_btn = array_merge($solution_target_btn, $solution_source_btn);

            // Generate direct link to the record in the source and target applications
            $sourceLink['direct_link'] = $solution_source->getDirectLink($rule, $doc[0], 'source');
            if (!empty($sourceLink['direct_link'])) {
                if (!empty($sourceData)) {
                    $sourceData = $sourceLink + $sourceData;
                } else {
                    $sourceData = $sourceLink;
                }
            }
            $targetLink['direct_link'] = $solution_target->getDirectLink($rule, $doc[0], 'target');
            if (!empty($targetLink['direct_link'])) {
                if (!empty($targetData)) {
                    $targetData = $targetLink + $targetData;
                } else {
                    $targetData = $targetLink;
                }
            }
            //Check the user timezone
            if ($timezone = '') {
                $timezone = 'UTC';
            } else {
                $timezone = $this->getUser()->getTimezone();
            }

            // Get the logs
            $logs = $em->getRepository(Log::class)
            ->findBy(
                ['document' => $id],
                ['id' => 'DESC']
            );

            // Set the parameters for document pagination
            $docParams = [
                'adapter_em_repository' => $em->getRepository(Document::class)->findBy(
                    ['source' => $doc[0]->getSource(), 'rule' => $doc[0]->getRule(), 'deleted' => 0],
                    ['dateModified' => 'DESC']
                ),
                'maxPerPage' => $this->params['pager'],
                'page' => $documentPage,
                'pageParameterName' => 'docPage'
            ];

            // Set the parameters for log pagination
            $logParams = [
                'adapter_em_repository' => $em->getRepository(Log::class)->findBy(
                    ['document' => $id],
                    ['id' => 'DESC']
                ),
                'maxPerPage' => $this->params['pager'],
                'page' => $logPage,
                'pageParameterName' => 'logPage'
            ];

// Get the paginated results for documents and logs
$documentPagination = $this->nav_pagination_documents($docParams, false);
$logPagination = $this->nav_pagination_logs($logParams, false);

// $formComment = $this->createForm(DocumentCommentType::class, null);


//             $formComment->handleRequest($request);
//             if ($formComment->isSubmitted() && $formComment->isValid()) {
//                 $comment = $formComment->getData()['comment'];
				
//                 // create new job
//                 $job = new Job();
//                 $job->setBegin(new \DateTime());
//                 $job->setEnd(new \DateTime());
//                 $job->setParam('notification');
//                 $job->setMessage('Comment log created. Comment: '.$comment);
//                 $job->setOpen(0);
//                 $job->setClose(0);
//                 $job->setCancel(0);
//                 $job->setManual(1);
//                 $job->setApi(0);
//                 $job->setError(0);
//                 $job->setStatus('End');
//                 $job->setId(uniqid(mt_rand(), true));
                
//                 $em->persist($job);
//                 $em->flush();
                
//                 // Add log to indicate this action
//                 $log = new Log();
//                 $log->setCreated(new \DateTime());
//                 $log->setType('I');
                
//                 $log->setRule($rule);
//                 $log->setJob($job);
//                 $log->setMessage($comment);
//                 $log->setDocument($doc[0]);
//                 $em->persist($log);
//                 $em->flush();
                
//                 $this->addFlash('success', 'Comment successfully added !');

//                 // Redirect the route to avoid resubmitting the form according to the PRG pattern
//                 return $this->redirectToRoute('flux_info', ['id' => $id]);
            // }

            // show the workflows logs from the table workflowlog related this document, we are looking for the field trigger_document_id in the table workflowlog
            $workflowLogs = $em->getRepository(WorkflowLog::class)->findBy(
                ['triggerDocument' => $id],
                ['id' => 'DESC']
            );

           // $firstParentDocumentId = $parentDocuments[0]->getId();

           if ($this->ruleHasLookups($rule)) {
            $lookupData = $this->getLookupData($rule, $sourceData);
            $mappedData = $this->extractFieldAndRule($lookupData);
            $extractedDirectLink = $this->extractDirectLink($sourceData);
            $linkedData = $this->generateLinkToSource($sourceData, $mappedData, $extractedDirectLink);
           }

            // Call the view
            return $this->render(
                'Flux/view/view.html.twig',
                [
                    'current_document' => $id,
                    'source' => $sourceData,
                    'linkedData' => $linkedData,
                    'target' => $targetData,
                    'history' => $historyData,
                    'doc' => $doc[0],
                    'nb' => $compact['nb'],
                    'entities' => $compact['entities'],
                    'pager' => $compact['pager'],
                    'rule' => $rule,
                    'post_documents' => $postDocuments,
                    'post_Documents_Rule' => $postDocumentsRule,
                    'nb_post_documents' => count($postDocuments),
                    'child_documents' => $childDocuments,
                    'child_Documents_Rule' => $childDocumentsRule,
                    'nb_child_documents' => count($childDocuments),
                    'parent_documents' => $parentDocuments,
                    'parent_Documents_Rule' => $parentDocumentsRule,
                    'nb_parent_documents' => count($parentDocuments),
                    //'firstParentDocumentId' => $firstParentDocumentId,
                    'history_documents' => $documentPagination['entities'],
                    'nb_history_documents' => $documentPagination['nb'],
                    'nb_logs' => $logPagination['nb'],
                    'ctm_btn' => $list_btn,
                    'read_record_btn' => $solution_source->getReadRecord(),
                    'timezone' => $timezone,
					// 'formComment' => $formComment->createView(),
                    'logs' => $logs,
                    'documentPagination' => $documentPagination,
                    'logPagination' => $logPagination,
                    'documentPage' => $documentPage,
                    'logPage' => $logPage,
                    'workflowLogs' => $workflowLogs,
                    'nb_workflow_logs' => count($workflowLogs),
                ]
            );
        } catch (Exception $e) {
            throw $this->createNotFoundException('Page not found.'.$e->getMessage().' '.$e->getFile().' '.$e->getLine());
        }
    }

    public function extractDirectLink($sourceData): string {
        $link = $sourceData['direct_link'];
        $extractedLeftPortionOfLink = explode('?', $link);
        $updatedLink = str_replace('index.php', '', $extractedLeftPortionOfLink[0]);
        return $updatedLink;
    }

    public function generateLinkToSource($sourceData, $mappedData, $extractedDirectLink): array {
        // for each element of the array, we will generate a link to the source record
        // we will use the rule to find the source module
        foreach ($mappedData as $item) {
            // get the rule of the item
            $rule = $this->getDoctrine()->getRepository(Rule::class)->find($item['rule']);
            $module = strtolower($rule->getModuleSource());
            $link = $extractedDirectLink . "#/" .$module.'/record/'. $sourceData[$item['field']];
            $result[$item['field']] = $link;
        }

        return $result;
    }

    function extractFieldAndRule($lookupData) {
        $result = [];
    
        foreach ($lookupData as $item) {
            if (isset($item['formula'])) {
                // Use regex to extract the field and rule from the formula
                if (preg_match('/lookup\(\{(.+?)\},"(.+?)"\)/', $item['formula'], $matches)) {
                    $result[] = [
                        'field' => $matches[1],
                        'rule' => $matches[2]
                    ];
                }
            }
        }
    
        return $result;
    }
    

    public function ruleHasLookups($rule): bool {
        // Prepare the SQL query to fetch rows from rulefield where rule_id matches
        $sql = 'SELECT formula FROM rulefield WHERE rule_id = :rule_id';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':rule_id', $rule->getId());
        $result = $stmt->executeQuery();
        
        // Fetch all results
        $results = $result->fetchAllAssociative();
        
        // Check if any formula contains the string "lookup"
        foreach ($results as $result) {
            if (!empty($result['formula']) && strpos($result['formula'], 'lookup') !== false) {
                return true;
            }
        }
        
        // Return false if no formula contains "lookup"
        return false;
    }

    public function getLookupData($rule, $sourceData): array {
        // in this function, we will want to obtain a link that leads to the actual data in the source solution
        // for instance, if we have a lookup on the field assigned_user_id, we will want to get a link to the user record in SuiteCRM following the formula lookup({assigned_user_id},"66b3539fde732")
        // it will be the module user because we will find in the rule 66b3539fde732 the associated module
        // Users

        // we start by getting the formula of the rule
        $sql = 'SELECT formula FROM rulefield WHERE rule_id = :rule_id';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':rule_id', $rule->getId());
        $result = $stmt->executeQuery();
        
        // Fetch all results
        $results = $result->fetchAllAssociative();
        $resultsNonEmpty = [];
        foreach ($results as $result) {
            if (!empty($result['formula'])) {
                $resultsNonEmpty[] = $result;
            }
        }

        return $resultsNonEmpty;
    }

    /**
     * @Route("/flux/save", name="flux_save")
     */
    public function fluxSave(Request $request)
    {
        if ('POST' == $request->getMethod()) {
            // Get the field and value from the request
            $fields = strip_tags($request->request->get('fields'));
            $value = strip_tags($request->request->get('value'));

            if (isset($value)) {
                // get the EntityManager
                $em = $this->getDoctrine()->getManager();
                // Get target data for the document
                $documentDataEntity = $em->getRepository(DocumentData::class)
                    ->findOneBy(
                        [
                            'doc_id' => $request->request->get('flux'),
                            'type' => 'T',
                        ]
                    );
                if (!empty($documentDataEntity)) {
                    $target = json_decode($documentDataEntity->getData(), true);
                    $beforeValue = $target[$fields];
                    // Change the value
                    $target[$fields] = $value;
                    // Save the modification
                    $documentDataEntity->setData(json_encode($target)); // Encode in JSON

                    // Insert in audit
                    $oneDocAudit = new DocumentAudit();
                    $oneDocAudit->setDoc($request->get('flux'));
                    $oneDocAudit->setDateModified(new \DateTime());
                    $oneDocAudit->setBefore($beforeValue);
                    $oneDocAudit->setAfter($value);
                    $oneDocAudit->setByUser($this->getUser()->getId());
                    $oneDocAudit->setName($fields);
                    $em->persist($oneDocAudit);
                    $em->flush();
                    echo $value;
                    exit;
                }
            }
        }
        throw $this->createNotFoundException('Failed to modify the field '.$fields);
    }

    /**
     * @Route("/flux/rerun/{id}", name="flux_rerun")
     */
    public function fluxRerun($id): RedirectResponse
    {
        try {
            if (!empty($id)) {
                $this->jobManager->actionMassTransfer('rerun', 'document', [$id]);
            }

            return $this->redirect($this->generateURL('flux_info', ['id' => $id]));
        } catch (Exception $e) {
            return $this->redirect($this->generateUrl('flux_list', ['search' => 1]));
        }
    }

    /**
     * @Route("/flux/cancel/{id}", name="flux_cancel")
     */
    public function fluxCancel($id): RedirectResponse
    {
        try {
            if (!empty($id)) {
                $this->jobManager->actionMassTransfer('cancel', 'document', [$id]);
            }

            return $this->redirect($this->generateURL('flux_info', ['id' => $id]));
        } catch (Exception $e) {
            return $this->redirect($this->generateUrl('flux_list', ['search' => 1]));
        }
    }

    /**
     * @Route("/flux/readrecord/{id}", name="flux_readrecord")
     */
    public function fluxReadRecord($id): RedirectResponse
    {
        try {
            if (!empty($id)) {
                // Get the rule id and the source_id from the document id
                $em = $this->getDoctrine()->getManager();
                $doc = $em->getRepository(Document::class)->find($id);
                if (!empty($doc)) {
                    if (!empty($doc->getSource())) {
                        $this->jobManager->runBackgroundJob('readrecord', [$doc->getRule(), 'id', $doc->getSource()]);
                    }
                }
										 
								
				 
            }

            return $this->redirect($this->generateURL('flux_info', ['id' => $id]));
        } catch (Exception $e) {
            return $this->redirect($this->generateUrl('flux_list', ['search' => 1]));
        }
    }

    /**
     * @Route("/flux/{id}/action/{method}/solution/{solution}", name="flux_btn_dyn")
     *
     * @throws Exception
     */
    public function fluxBtnDyn($method, $id, $solution): RedirectResponse
    {
        $solution_ws = $this->solutionManager->get(mb_strtolower($solution));
        $solution_ws->documentAction($id, $method);

        return $this->redirect($this->generateUrl('flux_info', ['id' => $id]));
    }

    /**
     * @Route("/flux/masscancel", name="flux_mass_cancel")
     */
    public function fluxMassCancelAction()
    {

        // if we are not premium, then return
        if (!$this->toolsManager->isPremium()) {
            exit;
        }

        if (isset($_POST['ids']) && count($_POST['ids']) > 0) {
            $this->jobManager->actionMassTransfer('cancel', 'document', $_POST['ids']);
        }

        exit;
    }

    /**
     * @Route("/flux/massrun", name="flux_mass_run")
     */
    public function fluxMassRunAction()
    {

        if (!$this->toolsManager->isPremium()) {
            exit;
        }

        if (isset($_POST['ids']) && count($_POST['ids']) > 0) {
            $this->jobManager->actionMassTransfer('rerun', 'document', $_POST['ids']);
        }

        exit;
    }

    /* *******************************************************
        * METHODES PRATIQUES
        ****************************************************** */

    // Crée la pagination avec le Bundle Pagerfanta en fonction d'une requete
    private function nav_pagination($params, $orm = true)
    {
        /*
            * adapter_em_repository = requete
            * maxPerPage = integer
            * page = page en cours
            */

        if (is_array($params)) {
            $compact = [];
            if ($orm) {
                $queryBuilder = $params['adapter_em_repository'];
                $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));
                $compact['pager'] = $pagerfanta;
            } else {
                $compact['pager'] = new Pagerfanta(new ArrayAdapter($params['adapter_em_repository']));
            }
            $maxPerPage = intval($params['maxPerPage']);
            $currentPage = intval($params['page']);
            $compact['pager']->setMaxPerPage($maxPerPage);
            try {
                $compact['pager']->setCurrentPage($currentPage);
                $compact['nb'] = $compact['pager']->getNbResults();
                $compact['entities'] = $compact['pager']->getCurrentPageResults();
            } catch (NotValidCurrentPageException $e) {
                throw $this->createNotFoundException('Page not found.'.$e->getMessage().' '.$e->getFile().' '.$e->getLine());
            }

            return $compact;
        }

        return false;
    }


    private function nav_pagination_documents($params, $orm = true)
    {
        /*
            * adapter_em_repository = requete
            * maxPerPage = integer
            * page = page en cours
            */
        if (is_array($params)) {

            $pageParameterName = $params['pageParameterName'];

            $compact = [];
            if ($orm) {
                $queryBuilder = $params['adapter_em_repository'];
                $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));
                $compact['pager'] = $pagerfanta;
            } else {
                $compact['pager'] = new Pagerfanta(new ArrayAdapter($params['adapter_em_repository']));
            }
            $maxPerPage = intval($params['maxPerPage']);
            $currentPage = intval($params['page']);
            $compact['pager']->setMaxPerPage($maxPerPage);
            try {
                $compact['pager']->setCurrentPage($currentPage);
                $compact['nb'] = $compact['pager']->getNbResults();
                $compact['entities'] = $compact['pager']->getCurrentPageResults();
                $compact['pageParameterName'] = $pageParameterName;
                
            } catch (NotValidCurrentPageException $e) {
                throw $this->createNotFoundException('Page not found.'.$e->getMessage().' '.$e->getFile().' '.$e->getLine());
            }

            return $compact;
        }

        return false;
    }


    private function nav_pagination_logs($params, $orm = true)
    {
        /*
            * adapter_em_repository = requete
            * maxPerPage = integer
            * page = page en cours
            */

        if (is_array($params)) {

            $pageParameterName = $params['pageParameterName'];

            $compact = [];
            if ($orm) {
                $queryBuilder = $params['adapter_em_repository'];
                $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));
                $compact['pager'] = $pagerfanta;
            } else {
                $compact['pager'] = new Pagerfanta(new ArrayAdapter($params['adapter_em_repository']));
            }
            $maxPerPage = intval($params['maxPerPage']);
            $currentPage = intval($params['page']);
            $compact['pager']->setMaxPerPage($maxPerPage);
            try {
                $compact['pager']->setCurrentPage($currentPage);
                $compact['nb'] = $compact['pager']->getNbResults();
                $compact['entities'] = $compact['pager']->getCurrentPageResults();
                $compact['pageParameterName'] = $pageParameterName;
            } catch (NotValidCurrentPageException $e) {
                throw $this->createNotFoundException('Page not found.'.$e->getMessage().' '.$e->getFile().' '.$e->getLine());
            }

            return $compact;
        }

        return false;
    }

    // Liste tous les flux d'un type
    private function listeFluxTable($id, $type)
    {
        try {
            // Get document data
            $documentDataEntity = $this->getDoctrine()->getManager()->getRepository(DocumentData::class)
                ->findOneBy(
                    [
                        'doc_id' => $id,
                        'type' => $type,
                    ]
                );
            if (!empty($documentDataEntity)) {
                $data = json_decode($documentDataEntity->getData(), true);
                // Boolean values aren't displayed properly, we convert them into string
                if (!empty($data)) {
                    foreach ($data as $key => $value) {
                        if (is_bool($value)) {
                            $data[$key] = (string) $value;
                        }
                    }
                }

                return $data;
            }

            return;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @Route("/document/unlock/{id}", name="document_view")
     */
    public function unlockDocument($id) {
        try{
            $this->jobManager->massAction('unlock', 'document', [$id], false, null, null);

            // add traduction
            $this->addFlash('success_unlock', 'Document déverrouillé avec succès.');
            return $this->redirect($this->generateURL('flux_info', ['id' => $id]));
        } catch (Exception $e) {
            // add traduction
            $this->addFlash('error_unlock', 'Erreur lors du déverrouillage du document.');
            return $this->redirect($this->generateUrl('flux_list', ['search' => 1]));
        }
    }

    /**
     * @Route("/flux/unlock", name="flux_mass_unlock")
     */
    public function unlockDocuments(Request $request) {
        try {

            if (!$this->toolsManager->isPremium()) {
                exit;
            }

            $ids = $request->request->get('ids', []);
            if (empty($ids)) {
                return new JsonResponse(['error' => 'No documents selected'], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->jobManager->massAction('unlock', 'document', $ids, false, null, null);
            if (!$result) {
                throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, "Unable to unlock documents.");
            }

            return new JsonResponse(['success' => true]);

        } catch (\Exception $e) {
            throw $this->createNotFoundException('Page not found.'.$e->getMessage().' '.$e->getFile().' '.$e->getLine());
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/read_job_lock/clear/{id}", name="clear_read_job_lock", methods={"POST"})
     */
    public function clearReadJobLock($id) {
        try {
            $this->jobManager->clearLock('rule', [$id]);

            return new JsonResponse(['status' => 'success', 'message' => 'Verrouillage effacé avec succès.']);
        } catch (Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => 'Erreur lors de la suppression du verrouillage.'], 500);
        }
    }
}
