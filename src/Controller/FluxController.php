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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

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
        $em = $this->entityManager;

        if ($this->getUser()->isAdmin()) {
            $list_fields_sql =
                [
					'id' => $id,
					'deleted' => 0,
				];
        } else {
            $list_fields_sql =
                [
                    'id' => $id,
					'deleted' => 0,
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
            $this->sessionService->setFluxFilterWhere(['rule' => $rule[0]->getName(), 'gblstatus' => ['Error']]);
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

        $em = $this->entityManager;

        if ($this->getUser()->isAdmin()) {
            $rule = $this->entityManager
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
        $configRepository = $this->entityManager->getRepository(Config::class);
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
        $stmt = $this->entityManager->getConnection()->prepare($query);
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
            $em = $this->entityManager;

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
                    || !$doc[0]->getCreatedBy()
                    || $doc[0]->getCreatedBy()->getId() != $this->getUser()->getId()
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

           if ($this->ruleHasLookups($rule)
        && $rule->getConnectorSource()->getSolution()->getName() === 'suitecrm'
           ) {
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
                    'linkedData' => $linkedData ?? null,
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

        if (!isset($sourceData['direct_link'])) {
            return '';
        }
        $link = $sourceData['direct_link'];
        $extractedLeftPortionOfLink = explode('#', $link);
        $updatedLink = str_replace('index.php', '', $extractedLeftPortionOfLink[0]);

        return $updatedLink;
    }

    public function generateLinkToSource($sourceData, $mappedData, $extractedDirectLink): array {

$result = [];

        // for each element of the array, we will generate a link to the source record
        // we will use the rule to find the source module
        foreach ($mappedData as $item) {
            // get the rule of the item
            $rule = $this->entityManager->getRepository(Rule::class)->find($item['rule']);
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
                // Updated regex to handle lookup formulas with optional parameters
                if (preg_match('/lookup\(\{(.+?)\},\s*"(.+?)"(?:\s*,\s*[^,\)]*)*\)/', $item['formula'], $matches)) {
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
            $fields = trim(strip_tags($request->request->get('fields')));
            $value = strip_tags($request->request->get('value'));

            if (isset($value)) {
                // get the EntityManager
                $em = $this->entityManager;
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

            return $this->redirect($this->generateURL('flux_modern', ['id' => $id]));
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

            return $this->redirect($this->generateURL('flux_modern', ['id' => $id]));
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
                $em = $this->entityManager;
                $doc = $em->getRepository(Document::class)->find($id);
                if (!empty($doc)) {
                    if (!empty($doc->getSource())) {
                        $this->jobManager->runBackgroundJob('readrecord', [$doc->getRule(), 'id', $doc->getSource()]);
                    }
                }
										 
								
				 
            }

            return $this->redirect($this->generateURL('flux_modern', ['id' => $id]));
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

        return $this->redirect($this->generateUrl('flux_modern', ['id' => $id]));
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

        // added condition where $_SERVER["HTTP_REFERER"] contains the substring flux
        if (isset($_POST['ids']) && count($_POST['ids']) > 0 && strpos($_SERVER["HTTP_REFERER"], 'flux') !== false) {
            $taskId = $this->jobManager->actionMassTransfer('cancel', 'document', $_POST['ids']);
            // Return the task ID so the frontend can create a direct link
            echo $taskId;
        } else {
            // default behaviour
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
            $documentDataEntity = $this->entityManager->getRepository(DocumentData::class)
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

            return $this->redirect($this->generateURL('flux_modern', ['id' => $id]));
        } catch (Exception $e) {
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
                // Return a success message indicating no documents were unlocked
                return new JsonResponse(['success' => true, 'message' => 'No documents were unlocked because they were already unlocked.']);
            }

            return new JsonResponse(['success' => true, 'message' => 'Documents unlocked successfully.']);

        } catch (\Exception $e) {
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

    /**
     * Modern JavaScript-based flux page
     * 
     * @Route("/flux/modern/{id}", name="flux_modern", defaults={"id"=null})
     */
    public function fluxModern(?string $id = null): Response
    {
        return $this->render('Flux/flux-js.html.twig');
    }

    /**
     * @Route("/api/flux/info/{id}", name="api_flux_info", methods={"GET"})
     */
    public function getFluxInfo(Request $request, ?string $id = null): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        // Get current locale
        $locale = $request->getLocale();
        
        // Get translations for the current locale
        $translations = [
            'flux' => [
                'title' => $this->translator->trans('view_flux.title'),
                'sections' => [
                    'general' => $this->translator->trans('view_flux.sections.general'),
                    'logs' => $this->translator->trans('view_flux.sections.logs'),
                    'mapping' => $this->translator->trans('view_flux.sections.mapping')
                ],
                'fields' => [
                    'name' => $this->translator->trans('form.name'),
                    'source' => $this->translator->trans('form.source'),
                    'target' => $this->translator->trans('form.target'),
                    'status' => $this->translator->trans('form.status')
                ],
                'buttons' => [
                    'save' => $this->translator->trans('view_flux.button.save'),
                    'download_logs' => $this->translator->trans('view_flux.button.download_logs'),
                    'empty_logs' => $this->translator->trans('view_flux.button.empty_logs')
                ]
            ]
        ];

        // If an ID is provided, get the specific flux data
        $fluxData = null;
        if ($id) {
            // Add your logic here to fetch the specific flux data
            // This is just a placeholder structure
            $fluxData = [
                'id' => $id,
                'name' => 'Sample Flux',
                'source' => 'Source System',
                'target' => 'Target System',
                'status' => 'active'
            ];
        }

        return new JsonResponse([
            'translations' => $translations,
            'fluxData' => $fluxData,
            'currentLocale' => $locale
        ]);
    }

    // function to get the rule name from the document id
    /**
     * @Route("/api/flux/rule-get/{id}", name="api_flux_rule", methods={"GET"})
     */
    public function getRuleName($id): JsonResponse {
        try {
            // Log the incoming request
            error_log("getRuleName called with document ID: " . $id);
            
            // Validate the document ID
            if (empty($id)) {
                error_log("getRuleName: Empty document ID provided");
                return new JsonResponse(['error' => 'Document ID is required'], 400);
            }
            
            // Find the document by ID (not the rule directly)
            $document = $this->entityManager->getRepository(Document::class)->find($id);
            
            if (!$document) {
                error_log("getRuleName: Document not found with ID: " . $id);
                return new JsonResponse(['error' => 'Document not found'], 404);
            }
            
            // Get the rule from the document
            $rule = $document->getRule();
            
            if (!$rule) {
                error_log("getRuleName: No rule associated with document ID: " . $id);
                return new JsonResponse(['error' => 'No rule associated with this document'], 404);
            }
            
            $ruleName = $rule->getName();
            error_log("getRuleName: Successfully found rule name: " . $ruleName . " for document ID: " . $id);
            
            return new JsonResponse([
                'success' => true,
                'rule_name' => $ruleName,
                'rule_id' => $rule->getId(),
                'document_id' => $id
            ]);
            
        } catch (\Exception $e) {
            error_log("getRuleName: Exception occurred: " . $e->getMessage());
            error_log("getRuleName: Stack trace: " . $e->getTraceAsString());
            return new JsonResponse(['error' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Comprehensive document data API endpoint
     * @Route("/api/flux/document-data/{id}", name="api_flux_document_data", methods={"GET"})
     */
    public function getDocumentData($id): JsonResponse {
        try {
            // error_log("getDocumentData called with document ID: " . $id);
            
            // Validate the document ID
            if (empty($id)) {
                error_log("getDocumentData: Empty document ID provided");
                return new JsonResponse(['error' => 'Document ID is required'], 400);
            }
            
            // Find the document by ID
            $document = $this->entityManager->getRepository(Document::class)->find($id);
            
            if (!$document) {
                error_log("getDocumentData: Document not found with ID: " . $id);
                return new JsonResponse(['error' => 'Document not found'], 404);
            }
            
            // Get the rule from the document
            $rule = $document->getRule();
            
            if (!$rule) {
                error_log("getDocumentData: No rule associated with document ID: " . $id);
                return new JsonResponse(['error' => 'No rule associated with this document'], 404);
            }
            
            // Get status display information
            $statusInfo = $this->getDocumentStatusInfo($document);
            
            // Get document data (source, target, history)
            $sourceData = $this->listeFluxTable($id, 'S');
            $targetData = $this->listeFluxTable($id, 'T');
            $historyData = $this->listeFluxTable($id, 'H');
            
            // Generate direct links to the record in the source and target applications
            $sourceDirectLink = null;
            $targetDirectLink = null;
            try {
                $sourceSolutionName = $rule->getConnectorSource()->getSolution()->getName();
                $allowedSolutions = ['suitecrm', 'airtable', 'sugarcrm'];
                
                if (in_array(strtolower($sourceSolutionName), $allowedSolutions)) {
                    $sourceSolution = $this->solutionManager->get($sourceSolutionName);
                    $sourceDirectLink = $sourceSolution->getDirectLink($rule, $document, 'source');
                }
                
                $targetSolutionName = $rule->getConnectorTarget()->getSolution()->getName();
                if (in_array(strtolower($targetSolutionName), $allowedSolutions)) {
                    $targetSolution = $this->solutionManager->get($targetSolutionName);
                    $targetDirectLink = $targetSolution->getDirectLink($rule, $document, 'target');
                }
            } catch (\Exception $e) {
                error_log("getDocumentData: Error generating direct links: " . $e->getMessage());
            }
            
            // Get error message from logs if status is error
            $errorMessage = null;
            if ($document->getGlobalStatus() === 'Error') {
                $errorMessage = $this->getLatestErrorMessage($document);
            }
            
            // Get solution information for logos
            $sourceSolution = null;
            $targetSolution = null;
            try {
                $sourceSolution = $rule->getConnectorSource()->getSolution()->getName();
                $targetSolution = $rule->getConnectorTarget()->getSolution()->getName();
                error_log("getDocumentData: Found solutions - Source: " . $sourceSolution . ", Target: " . $targetSolution);
            } catch (\Exception $e) {
                error_log("getDocumentData: Error getting solution names: " . $e->getMessage());
            }
            
            // Prepare comprehensive response
            $responseData = [
                // Rule information
                'rule_name' => $rule->getName(),
                'rule_id' => $rule->getId(),
                'rule_url' => null, // Could be constructed if needed
                
                // Solution information for logos
                'source_solution' => $sourceSolution,
                'target_solution' => $targetSolution,
                
                // Document basic info
                'document_id' => $id,
                'status' => $document->getStatus(),
                'global_status' => $document->getGlobalStatus(),
                'type' => $document->getType(),
                'mode' => $document->getMode(),
                'attempt' => $document->getAttempt(),
                'max_attempts' => null, // Could be fetched from rule config if available
                
                // Status display info with colors
                'status_label' => $statusInfo['status'],
                'status_class' => $statusInfo['status_class'],
                'global_status_label' => $statusInfo['global_status'],
                'global_status_class' => $statusInfo['global_status_class'],
                
                // Type display info
                'type_label' => $this->getTypeLabel($document->getType()),

                // Dates and reference - convert to user's timezone before formatting
                'creation_date' => $this->formatDateInUserTimezone($document->getDateCreated()),
                'modification_date' => $this->formatDateInUserTimezone($document->getDateModified()),
                'reference' => $document->getSourceDateModified() ? $this->formatDateInUserTimezone($document->getSourceDateModified()) : null,

                // Pass user timezone and date format for client-side formatting
                'user_timezone' => $this->getUser()->getTimezone(),
                'user_date_format' => $this->getUser()->getDateFormat(),
                
                // IDs
                'source_id' => $document->getSource(),
                'target_id' => $document->getTarget(),
                'parent_id' => $document->getParentId(),
                
                // Data sections
                'source_data' => $sourceData,
                'target_data' => $targetData,
                'history_data' => $historyData,
                
                // Direct links to the records in source and target applications
                'source_direct_link' => $sourceDirectLink,
                'target_direct_link' => $targetDirectLink,
                
                // Error handling
                'error_message' => $errorMessage,
                'logs' => null, // Could be added if needed
                
                // Additional metadata
                'deleted' => $document->getDeleted(),
                'workflow_error' => $document->getWorkflowError(),
                'job_lock' => $document->getJobLock()
            ];
            
            // error_log("getDocumentData: Successfully retrieved comprehensive data for document ID: " . $id);
            
            return new JsonResponse([
                'success' => true,
                'data' => $responseData
            ]);
            
        } catch (\Exception $e) {
            error_log("getDocumentData: Exception occurred: " . $e->getMessage());
            error_log("getDocumentData: Stack trace: " . $e->getTraceAsString());
            return new JsonResponse(['error' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper method to format a DateTime object in the user's timezone
     * @param \DateTime $date - The date to format
     * @return string - Formatted date string in Y-m-d H:i:s format
     */
    private function formatDateInUserTimezone(\DateTime $date): string {
        $userTimezone = $this->getUser()->getTimezone();

        // Clone the date to avoid modifying the original
        $dateInUserTz = clone $date;

        // Convert to user's timezone
        $dateInUserTz->setTimezone(new \DateTimeZone($userTimezone));

        // Return formatted string
        return $dateInUserTz->format('Y-m-d H:i:s');
    }

    /**
     * Helper method to get document status display information
     */
    private function getDocumentStatusInfo($document): array {
        $status = $document->getStatus();
        $globalStatus = $document->getGlobalStatus();
        
        // Get status info with color coding
        $statusInfo = $this->getStatusDisplayInfo($status);
        $globalStatusInfo = $this->getStatusDisplayInfo($globalStatus);
        
        return [
            'status' => $statusInfo['status'],
            'status_class' => $statusInfo['status_class'],
            'global_status' => $globalStatusInfo['status'],
            'global_status_class' => $globalStatusInfo['status_class']
        ];
    }

    /**
     * Helper method to get status display info with proper color coding
     */
    private function getStatusDisplayInfo($statusValue): array {
        // Normalize status value for comparison
        $statusLower = strtolower(trim($statusValue));
        
        // Yellow statuses: cancel, filter, no send, error expected
        if (in_array($statusLower, ['c', 'cancel', 'cancelled', 'filter', 'no_send', 'error_expected', 'cancel !'])) {
            return [
                'status' => $this->getStatusLabel($statusValue),
                'status_class' => 'status-yellow'
            ];
        }
        
        // Green statuses: send, sent, success
        if (in_array($statusLower, ['found', 'close', 's', 'send', 'sent', 'success', 'send ✓'])) {
            return [
                'status' => $this->getStatusLabel($statusValue),
                'status_class' => 'status-green'
            ];
        }
        
        // Red statuses: error, failed, ko, predecessor_ko
        if (in_array($statusLower, ['not_found', 'e', 'error', 'failed', 'ko', 'predecessor_ko', 'error ✗']) || 
            strpos($statusLower, 'error') !== false || 
            strpos($statusLower, 'ko') !== false ||
            strpos($statusLower, 'fail') !== false) {
            return [
                'status' => $this->getStatusLabel($statusValue),
                'status_class' => 'status-red'
            ];
        }
        
        // Blue statuses: all others (new, transform, open, etc.)
        return [
            'status' => $this->getStatusLabel($statusValue),
            'status_class' => 'status-blue'
        ];
    }

    /**
     * Helper method to get human-readable status labels
     */
    private function getStatusLabel($statusValue): string {
        // Map common status codes to readable labels with icons
        $statusLabels = [
            'S' => 'Send ✓',
            'C' => 'Cancel !',
            'E' => 'Error ✗',
            'T' => 'Transform ✓',
            'N' => 'New',
            'O' => 'Open',
            'Error' => 'Error ✗',
            'Open' => 'Open',
            'Close' => 'Close ✓'
        ];
        
        return $statusLabels[$statusValue] ?? $statusValue;
    }

    /**
     * Helper method to get latest error message from logs
     */
    private function getLatestErrorMessage($document): ?string {
        $logs = $document->getLogs();
        
        foreach ($logs as $log) {
            if ($log->getType() === 'E') { // Error type
                return $log->getMessage();
            }
        }
        
        return null;
    }

    /**
     * Helper method to get type label
     */
    private function getTypeLabel($type): string {
        // Map common type codes to readable labels with icons
        $typeLabels = [
            'S' => 'Send',
            'C' => 'Cancel',
            'E' => 'Error',
            'T' => 'Transform',
            'N' => 'New',
            'O' => 'Open',
            'Error' => 'Error',
            'Open' => 'Open',
            'Close' => 'Close'
        ];
        
        return $typeLabels[$type] ?? $type;
    }

    /**
     * Document history API endpoint
     * @Route("/api/flux/document-history/{id}", name="api_flux_document_history", methods={"GET"})
     */
    public function getDocumentHistory($id): JsonResponse {
        try {
            // error_log("getDocumentHistory called with document ID: " . $id);
            
            // Validate the document ID
            if (empty($id)) {
                error_log("getDocumentHistory: Empty document ID provided");
                return new JsonResponse(['error' => 'Document ID is required'], 400);
            }
            
            // Find the document by ID
            $document = $this->entityManager->getRepository(Document::class)->find($id);
            
            if (!$document) {
                error_log("getDocumentHistory: Document not found with ID: " . $id);
                return new JsonResponse(['error' => 'Document not found'], 404);
            }
            
            // Get the rule from the document
            $rule = $document->getRule();
            
            if (!$rule) {
                // error_log("getDocumentHistory: No rule associated with document ID: " . $id);
                return new JsonResponse(['error' => 'No rule associated with this document'], 404);
            }
            
            // Get history documents (all documents for the same source and rule)
            $historyDocuments = $this->entityManager->getRepository(Document::class)->findBy(
                [
                    'source' => $document->getSource(), 
                    'rule' => $document->getRule(), 
                    'deleted' => 0
                ], 
                ['dateModified' => 'DESC']
            );
            
            // If only one record, the history is the current document, so we remove it => no history
            if (1 == count($historyDocuments)) {
                $historyDocuments = [
                    $document
                ];
            }
            
            // Build the response data
            $historyData = [];
            foreach ($historyDocuments as $histDoc) {
                $statusInfo = $this->getDocumentStatusInfo($histDoc);

                $historyData[] = [
                    'docId' => $histDoc->getId(),
                    'name' => $rule->getName(),
                    'ruleId' => $rule->getId(),
                    'sourceId' => $histDoc->getSource(),
                    'targetId' => $histDoc->getTarget(),
                    'modificationDate' => $this->formatDateInUserTimezone($histDoc->getDateModified()),
                    'type' => $histDoc->getType(),
                    'status' => $statusInfo['status'],
                    'statusClass' => $statusInfo['status_class']
                ];
            }
            
            // error_log("getDocumentHistory: Successfully retrieved " . count($historyData) . " history documents for document ID: " . $id);
            
            return new JsonResponse([
                'success' => true,
                'data' => $historyData
            ]);
            
        } catch (\Exception $e) {
            // error_log("getDocumentHistory: Exception occurred: " . $e->getMessage());
            // error_log("getDocumentHistory: Stack trace: " . $e->getTraceAsString());
            return new JsonResponse(['error' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Document parent documents API endpoint
     * @Route("/api/flux/document-parents/{id}", name="api_flux_document_parents", methods={"GET"})
     */
    public function getDocumentParents($id): JsonResponse {
        try {
            if (empty($id)) {
                return new JsonResponse(['error' => 'Document ID is required'], 400);
            }
            
            if (!$this->entityManager->getRepository(Document::class)->find($id)) {
                return new JsonResponse(['error' => 'Document not found'], 404);
            }
            
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select([
                   'dr.sourceField',
                   'd.id as docId',
                   'd.source',
                   'd.target', 
                   'd.dateModified',
                   'd.type',
                   'COALESCE(r.name, :defaultRuleName) as ruleName',
                   'r.id as ruleId'
               ])
               ->from(DocumentRelationship::class, 'dr')
               ->innerJoin(Document::class, 'd', 'WITH', 'd.id = dr.doc_rel_id')
               ->leftJoin('d.rule', 'r')
               ->where($qb->expr()->eq('dr.doc_id', ':docId'))
               ->addOrderBy('dr.dateCreated', 'DESC')
               ->addOrderBy('dr.id', 'DESC')
               ->setMaxResults(10)
               ->setParameters([
                   'docId' => $id,
                   'defaultRuleName' => 'Unknown Rule'
               ]);
            
            $results = $qb->getQuery()->getArrayResult();
            
            $parentData = [];
            foreach ($results as $result) {
                if ($result['docId']) {
                    $parentDocument = $this->entityManager->getRepository(Document::class)->find($result['docId']);
                    $statusInfo = $this->getDocumentStatusInfo($parentDocument);

                    $parentData[] = [
                        'docId' => $result['docId'],
                        'name' => $result['ruleName'],
                        'ruleId' => $result['ruleId'],
                        'sourceId' => $result['source'],
                        'targetId' => $result['target'],
                        'modificationDate' => $this->formatDateInUserTimezone($result['dateModified']),
                        'type' => $result['type'],
                        'status' => $statusInfo['status'],
                        'statusClass' => $statusInfo['status_class'],
                        'sourceField' => $result['sourceField']
                    ];
                }
            }
            
            return new JsonResponse([
                'success' => true,
                'data' => $parentData
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Document child documents API endpoint
     * @Route("/api/flux/document-children/{id}", name="api_flux_document_children", methods={"GET"})
     */
    public function getDocumentChildren($id): JsonResponse {
        try {
            if (empty($id)) {
                return new JsonResponse(['error' => 'Document ID is required'], 400);
            }
            
            if (!$this->entityManager->getRepository(Document::class)->find($id)) {
                return new JsonResponse(['error' => 'Document not found'], 404);
            }
            
            $childData = [];
            
            // Related children via DocumentRelationship (same as fluxInfo)
            $relatedChildrenQb = $this->entityManager->createQueryBuilder();
            $relatedChildrenQb->select([
                    'd.id as docId',
                    'd.source',
                    'd.target',
                    'd.dateModified',
                    'd.type',
                    'COALESCE(r.name, :defaultRuleName) as ruleName',
                    'r.id as ruleId',
                    'dr.sourceField'
                ])
                ->from(DocumentRelationship::class, 'dr')
                ->innerJoin(Document::class, 'd', 'WITH', 'd.id = dr.doc_id')
                ->leftJoin('d.rule', 'r')
                ->where($relatedChildrenQb->expr()->eq('dr.doc_rel_id', ':docRelId'))
                ->addOrderBy('dr.dateCreated', 'DESC')
                ->addOrderBy('dr.id', 'DESC')
                ->setMaxResults(10)
                ->setParameters([
                    'docRelId' => $id,
                    'defaultRuleName' => 'Unknown Rule'
                ]);
            
            // Execute query
            $relatedResults = $relatedChildrenQb->getQuery()->getArrayResult();
            
            // Process related children
            foreach ($relatedResults as $result) {
                if ($result['docId']) {
                    $childDocument = $this->entityManager->getRepository(Document::class)->find($result['docId']);
                    $statusInfo = $this->getDocumentStatusInfo($childDocument);

                    $childData[] = [
                        'docId' => $result['docId'],
                        'name' => $result['ruleName'],
                        'ruleId' => $result['ruleId'],
                        'sourceId' => $result['source'],
                        'targetId' => $result['target'],
                        'modificationDate' => $this->formatDateInUserTimezone($result['dateModified']),
                        'type' => $result['type'],
                        'status' => $statusInfo['status'],
                        'statusClass' => $statusInfo['status_class'],
                        'sourceField' => $result['sourceField']
                    ];
                }
            }
            
            return new JsonResponse([
                'success' => true,
                'data' => $childData
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Document post documents API endpoint
     * @Route("/api/flux/document-posts/{id}", name="api_flux_document_posts", methods={"GET"})
     */
    public function getDocumentPosts($id): JsonResponse {
        try {
            if (empty($id)) {
                return new JsonResponse(['error' => 'Document ID is required'], 400);
            }

            if (!$this->entityManager->getRepository(Document::class)->find($id)) {
                return new JsonResponse(['error' => 'Document not found'], 404);
            }

            // Get post documents where parentId equals the current document ID
            $postDocuments = $this->entityManager->getRepository(Document::class)->findBy(
                ['parentId' => $id],
                ['dateCreated' => 'DESC'],
                10
            );

            $postData = [];
            foreach ($postDocuments as $postDoc) {
                $statusInfo = $this->getDocumentStatusInfo($postDoc);
                $rule = $postDoc->getRule();

                $postData[] = [
                    'docId' => $postDoc->getId(),
                    'name' => $rule ? $rule->getName() : 'Unknown Rule',
                    'ruleId' => $rule ? $rule->getId() : null,
                    'sourceId' => $postDoc->getSource(),
                    'targetId' => $postDoc->getTarget(),
                    'modificationDate' => $this->formatDateInUserTimezone($postDoc->getDateModified()),
                    'type' => $postDoc->getType(),
                    'status' => $statusInfo['status'],
                    'statusClass' => $statusInfo['status_class']
                ];
            }

            return new JsonResponse([
                'success' => true,
                'data' => $postData
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Document logs API endpoint
     * @Route("/api/flux/document-logs/{id}", name="api_flux_document_logs", methods={"GET"})
     */
    public function getDocumentLogs($id): JsonResponse {
        try {
            error_log("getDocumentLogs called with document ID: " . $id);
            
            // Validate the document ID
            if (empty($id)) {
                error_log("getDocumentLogs: Empty document ID provided");
                return new JsonResponse(['error' => 'Document ID is required'], 400);
            }
            
            // Find the document by ID
            $document = $this->entityManager->getRepository(Document::class)->find($id);
            
            if (!$document) {
                error_log("getDocumentLogs: Document not found with ID: " . $id);
                return new JsonResponse(['error' => 'Document not found'], 404);
            }
            
            // Get the logs for this document
            $logs = $this->entityManager->getRepository(Log::class)->findBy(
                ['document' => $id],
                ['id' => 'DESC'], // Most recent first
                50 // Limit to 50 most recent logs
            );
            
            error_log("getDocumentLogs: Found " . count($logs) . " logs for document ID: " . $id);
            
            // Build the response data
            $logsData = [];
            foreach ($logs as $log) {
                $job = $log->getJob();
                
                // Format the log type with icon
                $typeFormatted = $this->formatLogType($log->getType());
                
                $logsData[] = [
                    'id' => $log->getId(),
                    'reference' => $log->getRef() ?: '',
                    'job' => $job ? $job->getId() : '',
                    'creationDate' => $this->formatDateInUserTimezone($log->getCreated()),
                    'type' => $typeFormatted,
                    'message' => $log->getMessage() ?: 'No message',
                    'rawType' => $log->getType() // For frontend styling
                ];
            }
            
            error_log("getDocumentLogs: Successfully processed " . count($logsData) . " logs for document ID: " . $id);
            
            return new JsonResponse([
                'success' => true,
                'data' => $logsData
            ]);
            
        } catch (\Exception $e) {
            error_log("getDocumentLogs: Exception occurred: " . $e->getMessage());
            error_log("getDocumentLogs: Stack trace: " . $e->getTraceAsString());
            return new JsonResponse(['error' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * User permissions API endpoint
     * @Route("/api/flux/user-permissions", name="api_flux_user_permissions", methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function getUserPermissions(): JsonResponse {
        try {
            $user = $this->getUser();
            
            if (!$user) {
                return new JsonResponse(['error' => 'User not authenticated'], 401);
            }
            
            $permissions = [
                'is_super_admin' => $user->hasRole('ROLE_SUPER_ADMIN'),
                'is_admin' => $user->hasRole('ROLE_ADMIN'),
                'roles' => $user->getRoles(),
                'username' => $user->getUsername()
            ];
            
            return new JsonResponse([
                'success' => true,
                'permissions' => $permissions
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper method to format log types with icons and colors
     */
    private function formatLogType($type): string {
        switch (strtoupper($type)) {
            case 'S':
                return 'S ✓'; // Success - green
            case 'E':
                return 'E ✗'; // Error - red  
            case 'W':
                return 'W ⚠'; // Warning - yellow
            case 'I':
                return 'I ℹ'; // Info - blue
            default:
                return $type; // Return original if unknown
        }
    }

    /**
     * Update a specific field in document target data
     * @Route("/flux/update-field", name="flux_update_field", methods={"POST"})
     */
    public function updateField(Request $request): JsonResponse {
        try {
            // Check if user is admin
            if (!$this->getUser() || !$this->getUser()->isAdmin()) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Insufficient permissions. Admin access required.'
                ], 403);
            }

            // Get JSON data from request
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ], 400);
            }

            // Validate required fields
            if (empty($data['documentId']) || empty($data['fieldName']) || !isset($data['fieldValue'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Missing required fields: documentId, fieldName, fieldValue'
                ], 400);
            }

            $documentId = $data['documentId'];
            $fieldName = trim($data['fieldName']);
            $fieldValue = $data['fieldValue']; // Allow empty string

            error_log("Updating field: Document ID = $documentId, Field = $fieldName, Value = $fieldValue");

            // Get the document
            $document = $this->entityManager->getRepository(Document::class)->find($documentId);
            
            if (!$document) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Document not found'
                ], 404);
            }

            // Get target data for the document
            $documentDataEntity = $this->entityManager->getRepository(DocumentData::class)
                ->findOneBy([
                    'doc_id' => $documentId,
                    'type' => 'T',
                ]);
                
            if (!$documentDataEntity) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Target data not found for this document'
                ], 404);
            }

            // Decode current target data
            $targetData = json_decode($documentDataEntity->getData(), true);
            
            if ($targetData === null) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid target data format'
                ], 500);
            }

            // Check if field exists in target data
            if (!array_key_exists($fieldName, $targetData)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => "Field '$fieldName' not found in target data"
                ], 404);
            }

            // Store the old value for audit
            $oldValue = $targetData[$fieldName];
            
            // Update the field value
            $targetData[$fieldName] = $fieldValue;
            
            // Save the updated data back to the entity
            $documentDataEntity->setData(json_encode($targetData));
            
            // Create audit record
            $documentAudit = new DocumentAudit();
            $documentAudit->setDoc($documentId);
            $documentAudit->setDateModified(new \DateTime());
            $documentAudit->setBefore($oldValue);
            $documentAudit->setAfter($fieldValue);
            $documentAudit->setByUser($this->getUser()->getId());
            $documentAudit->setName($fieldName);
            
            $this->entityManager->persist($documentAudit);
            $this->entityManager->flush();

            error_log("Field updated successfully: $fieldName changed from '$oldValue' to '$fieldValue'");

            return new JsonResponse([
                'success' => true,
                'message' => 'Field updated successfully',
                'data' => [
                    'fieldName' => $fieldName,
                    'oldValue' => $oldValue,
                    'newValue' => $fieldValue
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Error updating field: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Document workflow logs API endpoint
     * @Route("/api/flux/document-workflow-logs/{id}", name="api_flux_document_workflow_logs", methods={"GET"})
     */
    public function getDocumentWorkflowLogs($id): JsonResponse {
        try {
            error_log("getDocumentWorkflowLogs called with document ID: " . $id);
            
            $em = $this->entityManager;
            
            // Get workflow logs for this trigger document
            $workflowLogs = $em->getRepository(WorkflowLog::class)->findBy(
                ['triggerDocument' => $id],
                ['id' => 'DESC']
            );
            
            error_log("Found " . count($workflowLogs) . " workflow logs for document ID: " . $id);
            
            $workflowLogsData = [];
            
            foreach ($workflowLogs as $workflowLog) {
                $workflowLogsData[] = [
                    'id' => $workflowLog->getId(),
                    'workflowName' => $workflowLog->getWorkflow() ? $workflowLog->getWorkflow()->getName() : '',
                    'workflowId' => $workflowLog->getWorkflow() ? $workflowLog->getWorkflow()->getId() : null,
                    'jobName' => $workflowLog->getJob() ? $workflowLog->getJob()->getId() : '',
                    'jobId' => $workflowLog->getJob() ? $workflowLog->getJob()->getId() : null,
                    'triggerDocument' => $workflowLog->getTriggerDocument() ? $workflowLog->getTriggerDocument()->getId() : null,
                    'generateDocument' => $workflowLog->getGenerateDocument() ? $workflowLog->getGenerateDocument()->getId() : null,
                    'createdBy' => $workflowLog->getCreatedBy() ? $workflowLog->getCreatedBy()->getUsername() : '',
                    'actionName' => $workflowLog->getAction() ? $workflowLog->getAction()->getName() : '',
                    'actionId' => $workflowLog->getAction() ? $workflowLog->getAction()->getId() : null,
                    'actionType' => $workflowLog->getAction() ? $workflowLog->getAction()->getAction() : '',
                    'status' => $workflowLog->getStatus() ?? '',
                    'dateCreated' => $this->formatDateInUserTimezone($workflowLog->getDateCreated()),
                    'message' => $workflowLog->getMessage() ?? '',
                ];
            }
            
            return new JsonResponse([
                'success' => true,
                'data' => $workflowLogsData
            ]);
            
        } catch (\Exception $e) {
            error_log("Error in getDocumentWorkflowLogs: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error retrieving workflow logs'
            ], 500);
        }
    }
}
