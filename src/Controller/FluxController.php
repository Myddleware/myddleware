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

use App\Entity\Config;
use App\Entity\Document;
use App\Entity\DocumentAudit;
use App\Entity\DocumentData;
use App\Entity\DocumentRelationship;
use App\Entity\Log;
use App\Entity\Rule;
use App\Manager\document as doc;
use App\Manager\DocumentManager;
use App\Manager\JobManager;
use App\Manager\SolutionManager;
use App\Repository\DocumentRepository;
use App\Service\SessionService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class FluxController.
 *
 * @Route("/rule")
 */
class FluxController extends AbstractController
{
    protected $params;

    private $sessionService;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var JobManager
     */
    private $jobManager;
    /**
     * @var SolutionManager
     */
    private $solutionManager;
    /**
     * @var DocumentRepository
     */
    private $documentRepository;

    public function __construct(
        SessionService $sessionService,
        TranslatorInterface $translator,
        JobManager $jobManager,
        SolutionManager $solutionManager,
        DocumentRepository $documentRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->sessionService = $sessionService;
        $this->translator = $translator;
        $this->jobManager = $jobManager;
        $this->solutionManager = $solutionManager;
        $this->documentRepository = $documentRepository;
        $this->entityManager = $entityManager;
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
     * @param $id
     *
     * @return RedirectResponse
     *
     * @Route("/flux/error/{id}", name="flux_error_rule")
     */
    public function fluxErrorByRuleAction($id)
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

        return $this->redirect($this->generateUrl('flux_list', ['search' => 1]));
    }

    /**
     * LISTE DES FLUX.
     *
     * @param $page
     * @param int $search
     *
     * @return Response
     *
     * @Route("/flux/list/search-{search}", name="flux_list", defaults={"page"=1})
     * @Route("/flux/list/page-{page}", name="flux_list_page", requirements={"page"="\d+"})
     */
    public function fluxListAction(Request $request, $page, $search = 1)
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
                ['createdBy' => $this->getUser()->getId(),
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
                'required' => false, ])

            ->add('target_content', TextType::class, [
                'data' => ($this->sessionService->isFluxFilterCTargetContentExist() ? $this->sessionService->getFluxFilterTargetContent() : false),
                'required' => false, ])

            ->add('date_modif_start', TextType::class, [
                'data' => ($this->sessionService->isFluxFilterCDateModifStartExist() ? $this->sessionService->getFluxFilterDateModifStart() : false),
                'required' => false,
                'attr' => ['class' => 'calendar'], ])

            ->add('date_modif_end', TextType::class, [
                'data' => ($this->sessionService->isFluxFilterCDateModifEndExist() ? $this->sessionService->getFluxFilterDateModifEnd() : false),
                'required' => false,
                'attr' => ['class' => 'calendar'], ])

            ->add('rule', TextType::class, [
                'data' => ($this->sessionService->isFluxFilterCRuleExist() ? $this->sessionService->getFluxFilterRuleName() : false),
                'required' => false,	])

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
                'required' => false,	])

            ->add('target_id', TextType::class, [
                'data' => ($this->sessionService->isFluxFilterCTargetIdExist() ? $this->sessionService->getFluxFilterTargetId() : false),
                'required' => false,	])

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

        $conditions = 0;
        //---[ FORM ]-------------------------
        if ($form->get('click_filter')->isClicked()) {
            $data = $form->getData();
            $data['user'] = $this->getUser();
            $data['search'] = $search;
            $data['page'] = $page;

            // Get the limit parameter
            $configRepository = $this->getDoctrine()->getManager()->getRepository(Config::class);
            $searchLimit = $configRepository->findOneBy(['name' => 'search_limit']);
            if (!empty($searchLimit)) {
                $data['limit'] = $searchLimit->getValue();
            }

            $r = $this->documentRepository->getFluxPagination($data);
            if (empty($data['source_content'])) {
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
                $this->sessionService->removeFluxFilterGblStatus();
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

        $r = $this->documentRepository->getFluxPagination($data);
        $compact = $this->nav_pagination([
            'adapter_em_repository' => $r,
            'maxPerPage' => isset($this->params['pager']) ? $this->params['pager'] : 25,
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

            return $this->render('Flux/list.html.twig', [
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

    /**
     * Supprime le filtre des flux.
     *
     * @return RedirectResponse
     *
     * @Route("/flux/list/delete/filter", name="flux_list_delete_filter")
     */
    public function fluxListDeleteFilterAction()
    {
        if ($this->sessionService->isFluxFilterExist()) {
            $this->sessionService->removeFluxFilter();
        }

        return $this->redirect($this->generateUrl('flux_list', ['search' => 1]));
    }

    /**
     * Info d'un flux.
     *
     * @param $id
     * @param $page
     *
     * @return RedirectResponse|Response
     *
     * @Route("/flux/{id}/log/", name="flux_info", defaults={"page"=1})
     * @Route("/flux/{id}/log/page-{page}", name="flux_info_page", requirements={"page"="\d+"})
     */
    public function fluxInfoAction(Request $request, $id, $page)
    {
        try {
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
                'adapter_em_repository' => $em->getRepository(Log::class)
                    ->findBy(
                        ['document' => $id],
                        ['id' => 'DESC']
                    ),
                'maxPerPage' => $this->params['pager'],
                'page' => $page,
            ], false);

            // POST DOCUMENT
            // Get the post documents (Document coming from a child rule)
            $postDocuments = $em->getRepository(Document::class)->findBy(
                ['parentId' => $id],
                ['dateCreated' => 'DESC'],	// order
                10								// limit
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
                ['dateCreated' => 'DESC'],		// order
                10									// limit
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
                ['dateCreated' => 'DESC'],			// order
                10										// limit
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
            // Call the view
            return $this->render('Flux/view/view.html.twig', [
                'current_document' => $id,
                'source' => $sourceData,
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
                'history_documents' => $historyDocuments,
                'nb_history_documents' => count($historyDocuments),
                'ctm_btn' => $list_btn,
                'read_record_btn' => $solution_source->getReadRecord(),
                'timezone' => $timezone,
            ]
            );
        } catch (Exception $e) {
            return $this->redirect($this->generateUrl('flux_list', ['search' => 1]));
            exit;
        }
    }

    /**
     * Sauvegarde flux.
     *
     * @Route("/flux/save", name="flux_save")
     */
    public function fluxSaveAction(Request $request)
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
                    ->findOneBy([
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
     * Relancer un flux.
     *
     * @param $id
     *
     * @return RedirectResponse
     *
     * @Route("/flux/rerun/{id}", name="flux_rerun")
     */
    public function fluxRerunAction($id)
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
     * Annuler un flux.
     *
     * @param $id
     *
     * @return RedirectResponse
     *
     * @Route("/flux/cancel/{id}", name="flux_cancel")
     */
    public function fluxCancelAction($id)
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
     * Read record.
     *
     * @param $id
     *
     * @return RedirectResponse
     *
     * @Route("/flux/readrecord/{id}", name="flux_readrecord")
     */
    public function fluxReadRecordAction($id)
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

    // Exécute une action d'un bouton dynamique

    /**
     * @param $method
     * @param $id
     * @param $solution
     *
     * @return RedirectResponse
     *
     * @Route("/flux/{id}/action/{method}/solution/{solution}", name="flux_btn_dyn")
     */
    public function fluxBtnDynAction($method, $id, $solution)
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
        if (isset($_POST['ids']) && count($_POST['ids']) > 0) {
            $this->jobManager->actionMassTransfer('rerun', 'document', $_POST['ids']);
        }

        exit;
    }

    /* ******************************************************
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
                $queryBuilder = $params['adapter_em_repository'];
                $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));
                $compact['pager'] = $pagerfanta;
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
                throw $this->createNotFoundException('Page not found. '.$e->getMessage());
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
                ->findOneBy([
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
}
