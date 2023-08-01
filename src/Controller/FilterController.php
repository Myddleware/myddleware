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
use App\Entity\Job;
use App\Entity\Log;
use App\Entity\Rule;
use App\Entity\Config;
use App\Entity\Document;
use App\Entity\RuleParam;
use Pagerfanta\Pagerfanta;
use App\Entity\DocumentData;
use Psr\Log\LoggerInterface;
use App\Manager\ToolsManager;
use App\Form\Filter\FilterType;
use App\Service\SessionService;
use App\Repository\RuleRepository;
use Pagerfanta\Adapter\ArrayAdapter;
use App\Form\Type\DocumentCommentType;
use App\Repository\DocumentRepository;
use App\Form\Filter\CombinedFilterType;
use App\Service\AlertBootstrapInterface;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @Route("/rule")
 */
class FilterController extends AbstractController
{
    /**
     * @var ToolsManager
     */
    private $toolsManager;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ParameterBagInterface
     */
    protected $params;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var string
     */
    private $env;

    /**
     * @var AlertBootstrapInterface
     */
    private $alert;

    private DocumentRepository $documentRepository;
    private SessionService $sessionService;
    
    public function __construct(
        SessionService $sessionService,
        KernelInterface $kernel,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        ToolsManager $toolsManager,
        AlertBootstrapInterface $alert,
        DocumentRepository $documentRepository,
    ) {
        $this->sessionService = $sessionService;
        $this->kernel = $kernel;
        $this->env = $kernel->getEnvironment();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->toolsManager = $toolsManager;
        $this->alert = $alert;
        $this->documentRepository = $documentRepository;

        // Init parameters
        $configRepository = $this->entityManager->getRepository(Config::class);
        $configs = $configRepository->findAll();
        if (!empty($configs)) {
            foreach ($configs as $config) {
                $this->params[$config->getName()] = $config->getvalue();
            }
        }
    }


    // Function to disylay the documents with filters

    /**
     * @Route("/document/list/search-{search}", name="document_list", defaults={"page"=1})
     * @Route("/document/list/page-{page}", name="document_list_page", requirements={"page"="\d+"})
     */
    public function testFilterAction(Request $request, int $page = 1, int $search = 1): Response
    {

        $toto = 'toto';
        $formFilter = $this->createForm(FilterType::class, null);
        $form = $this->createForm(CombinedFilterType::class, null, [
            'entityManager' => $this->entityManager,
        ]);
        
        $conditions = 0;

        // set the timezone
        $timezone = !empty($timezone) ? $this->getUser()->getTimezone() : 'UTC';

        if ($request->isMethod('POST') || $page !== 1 || ($request->isMethod('GET') && $this->verifyIfEmptyFilters() === false)) {
           
            $form->handleRequest($request);
            $data = [];
            $operators = $request->request->all();
            unset($operators['combined_filter']);
            if (!empty($this->sessionService->getFluxFilterWhere())) {
                $data['customWhere'] = $this->sessionService->getFluxFilterWhere();
            }
    
            // Get the limit parameter
            $configRepository = $this->entityManager->getRepository(Config::class);
            $searchLimit = $configRepository->findOneBy(['name' => 'search_limit']);
            if (!empty($searchLimit)) {
                $limit = $searchLimit->getValue();
            }
            
            $conditions = 0;
            $doNotSearch = false;
            if ($form->isSubmitted() && $form->isValid()) {

                $ruleRepository = $this->entityManager->getRepository(Rule::class);
                $rules = $ruleRepository->findAll();
                $ruleName = [];
                foreach ($rules as $value) {
                    if ($value->getDeleted() == false) {
                        $ruleName[] = $value->getName();
                    }
                }
                $documentFormData = $form->get('document')->getData();
                $ruleFormData = $form->get('rule')->getData();
                $sourceFormData = $form->get('sourceContent')->getData();

                // If form get document get data is not null or form get rule get data is not null or form get source get data is not null
                if ($documentFormData !== null || $ruleFormData !== null || $sourceFormData !== null) {

                    $data = $this->getDataFromForm($documentFormData, $ruleFormData, $sourceFormData, $ruleName, $operators);


                    // Remove the null values
                    foreach ($data as $key => $value) {
                        if (is_null($value)) {
                            unset($data[$key]);
                        }
                    }
                } // end form data not null
                
                if ($page === 1) {

                    // If the form is submitted and the form is valid, we save the filters in the session
                    $filterMap = [
                        'reference' => 'FluxFilterReference',
                        'operators' => 'FluxFilterOperators',
                        'customWhere' => 'FluxFilterWhere',
                        'source_content' => 'FluxFilterSourceContent',
                        'target_content' => 'FluxFilterTargetContent',
                        'date_modif_start' => 'FluxFilterDateModifStart',
                        'date_modif_end' => 'FluxFilterDateModifEnd',
                        'rule' => 'FluxFilterRuleName',
                        'status' => 'FluxFilterStatus',
                        'gblstatus' => 'FluxFilterGlobalStatus',
                        'type' => 'FluxFilterType',
                        'target_id' => 'FluxFilterTargetId',
                        'source_id' => 'FluxFilterSourceId',
                        'module_source' => 'FluxFilterModuleSource',
                        'module_target' => 'FluxFilterModuleTarget',
                        'sort_field' => 'FluxFilterSortField',
                        'sort_order' => 'FluxFilterSortOrder',
                        ];
                        
                    // Save the filters in the session using the session service and the filter map
                    foreach ($filterMap as $dataKey => $filterName) {
                        if (!empty($data[$dataKey])) {
                            $this->sessionService->{'set'.$filterName}($data[$dataKey]);
                        } elseif ($dataKey !== 'customWhere') {
                            $this->sessionService->{'remove'.$filterName}();
                        }
                    }

                } // end if page === 1
            } else { // if form is not valid
                    $data = $this->getFluxFilterData();

                if (
                    count(array_filter($data)) === 0
                    and $page == 1
                ) {
                    $doNotSearch = true;
                }
            }

            // Return an empty array if the form is not valid so that there will be no documents to display
            if ($doNotSearch) {
                $documents = array();
            }
            
            // If the form is valid, we prepare the search
            if (!$doNotSearch) {
                $searchParameters = $this->prepareSearch($data, $page, $limit);
                $documents = $searchParameters['documents'];
                // if $sortField, $sortOrder not null, then sort the documents accordingly
                // if ($sortField && $sortOrder) {
                //     $documents = $this->sortDocuments($documents, $sortField, $sortOrder);
                // }
                $page = $searchParameters['page'];
                $limit = $searchParameters['limit'];
            }
    
            try {
                $compact = $this->nav_pagination([
                    'adapter_em_repository' => $documents,
                    'maxPerPage' => $this->params['pager'] ?? 25,
                    'page' => $page,
                ], false);
            } catch (\Throwable $th) {
                // redirect to the list page
                // add a flash errore message that says there are not enough results for pagination
                $this->addFlash('error', 'Pagination error, return to page 1');
                return $this->redirectToRoute('document_list');
            }
            
            // If everything is ok with the pagination
            if ($compact) {
                // If no rule
                // display the button to delete the filters if the conditions come from the dashboard
                if ($this->sessionService->isFluxFilterCExist()) {
                    $conditions = 1;
                }
            }
        } // end if POST

        if (!isset($compact)) {
            $documents = [];

            // default pagination
            $compact = $this->nav_pagination([
                'adapter_em_repository' => $documents,
                'maxPerPage' => 25,
                'page' => 1,
            ], false);
        }

        // get the id of every document that will be return in the search results, and put them in a string where they are separated by a comma
        $csvdocumentids = '';

        foreach ($documents as $documentForCsv) {
            $csvdocumentids .= $documentForCsv['id'].',';
        }

        
        
        return $this->render('testFilter.html.twig', [
            'form' => $form->createView(),
            'formFilter'=> $formFilter->createView(),
            'documents' => $documents,
            'nb' => $compact['nb'],
            'entities' => $compact['entities'],
            'pager' => $compact['pager'],
            'condition' => $conditions,
            'timezone' => $timezone,
            'csvdocumentids' => $csvdocumentids,
        ]);
    }

    /**
     * @Route("/rule/flux/comment", name="add_document_comment", methods={"POST"})
     */
    public function updateDescription(Request $request): Response
    {
        $ruleId = $request->request->get('ruleId');
        $description = $request->request->get('description');
        $entityManager = $this->getDoctrine()->getManager();

        // Retrieve the RuleParam entity using the ruleId
        $rule = $entityManager->getRepository(RuleParam::class)->findOneBy(['rule' => $ruleId]);

        if (!$rule) {
            throw $this->createNotFoundException('Couldn\'t find specified rule in database');
        }

        // Retrieve the RuleParam with the name "description" and the same rule as the previously retrieved entity
        $descriptionRuleParam = $entityManager->getRepository(RuleParam::class)->findOneBy([
            'rule' => $rule->getRule(),
            'name' => 'description'
        ]);

        // Check if the description entity was found
        if (!$descriptionRuleParam) {
            throw $this->createNotFoundException('Couldn\'t find description rule parameter');
        }

        // Update the value of the description
        $descriptionRuleParam->setValue($description);
        $entityManager->flush();

        return new Response('', Response::HTTP_OK);
    }

    // // function to sort the documents
    public function sortDocuments(array $documents, string $sortField, string $sortOrder){
        // Sort the arrray of documents according to the sortField and sortOrder
        $sort = array();
        foreach ($documents as $key => $value) {
            $sort[$key] = $value[$sortField];
        }
        if ($sortOrder == 'ASC') {
            array_multisort($sort, SORT_ASC, $documents);
        } else {
            array_multisort($sort, SORT_DESC, $documents);
        }

        return $documents;
    }

    // Verify if each filter is empty, and return true if all filters are empty
    public function verifyIfEmptyFilters()
    {
        $filterMap = [
            'reference' => 'FluxFilterReference',
            'operators' => 'FluxFilterOperators',
            'customWhere' => 'FluxFilterWhere',
            'source_content' => 'FluxFilterSourceContent',
            'target_content' => 'FluxFilterTargetContent',
            'date_modif_start' => 'FluxFilterDateModifStart',
            'date_modif_end' => 'FluxFilterDateModifEnd',
            'rule' => 'FluxFilterRuleName',
            'status' => 'FluxFilterStatus',
            'gblstatus' => 'FluxFilterGlobalStatus',
            'type' => 'FluxFilterType',
            'target_id' => 'FluxFilterTargetId',
            'source_id' => 'FluxFilterSourceId',
            'module_source' => 'FluxFilterModuleSource',
            'module_target' => 'FluxFilterModuleTarget',
            'sort_field' => 'FluxFilterSortField',
            'sort_order' => 'FluxFilterSortOrder',
        ];

        foreach ($filterMap as $dataKey => $filterName) {
            $value = $this->sessionService->{'get' . $filterName}();

            if (!empty($value)) {
                return false;
            }
        }
        return true;
    }

    // Get the data from the session service and return an array
    public function getFluxFilterData() {
        $data = [];
    
        $filterMap = [
            'reference' => 'FluxFilterReference',
            'operators' => 'FluxFilterOperators',
            'customWhere' => 'FluxFilterWhere',
            'source_content' => 'FluxFilterSourceContent',
            'target_content' => 'FluxFilterTargetContent',
            'date_modif_start' => 'FluxFilterDateModifStart',
            'date_modif_end' => 'FluxFilterDateModifEnd',
            'rule' => 'FluxFilterRuleName',
            'status' => 'FluxFilterStatus',
            'gblstatus' => 'FluxFilterGlobalStatus',
            'type' => 'FluxFilterType',
            'target_id' => 'FluxFilterTargetId',
            'source_id' => 'FluxFilterSourceId',
            'module_source' => 'FluxFilterModuleSource',
            'module_target' => 'FluxFilterModuleTarget',
            'sort_field' => 'FluxFilterSortField',
            'sort_order' => 'FluxFilterSortOrder',
        ];
    
        foreach ($filterMap as $dataKey => $filterName) {
            $value = $this->sessionService->{'get'.$filterName}();
                $data[$dataKey] = $value;
        }
    
        return $data;
    }
    
    // Get the data from the form and return an array
    public function getDataFromForm($documentFormData, $ruleFormData, $sourceFormData, $ruleName, $operators)
    {
        try {
            $data = [
                'reference' => ($documentFormData['reference']) ? $documentFormData['reference'] : null,
                'rule' => ($ruleFormData !== null && $ruleFormData->isNameSet()) ? $ruleName[$ruleFormData->getName()] : null,
                'status' => ($documentFormData['status']) ? $this->getStatusData($documentFormData) : null,
                'gblstatus' => $this->getGlobalStatusData($documentFormData)['gblstatus'] ?? null,
                'module_source' => ($ruleFormData !== null && $ruleFormData->isModuleSourceSet()) ? $this->getModuleSourceData($ruleFormData) : null,
                'module_target' => ($ruleFormData !== null && $ruleFormData->isModuleTargetSet()) ? $this->getModuleTargetData($ruleFormData) : null,
                'source_id' => ($documentFormData['sourceId']) ? $documentFormData['sourceId'] : null,
                'target_id' => ($documentFormData['target']) ? $documentFormData['target'] : null,
                'type' => ($documentFormData['type']) ? $this->getDocumentType($documentFormData) : null,
                'source_content' => $sourceFormData['sourceContent'] ?? null,
                'target_content' => $sourceFormData['targetContent'] ?? null,
                'date_modif_start' => $documentFormData['date_modif_start'] ? $documentFormData['date_modif_start']->format('Y-m-d, H:i:s') : null,
                'date_modif_end' => $documentFormData['date_modif_end'] ? $documentFormData['date_modif_end']->format('Y-m-d, H:i:s') : null,
                'operators' => $operators ?? null,
                'customWhere' => $this->getGlobalStatusData($documentFormData)['customWhere'] ?? null,
                'sort_field' => $documentFormData['sort_field'] ?? null,
                'sort_order' => $documentFormData['sort_order'] ?? null,
            ];
            
            
            return $data;
        } catch (\Exception $e) {
            throw new \Exception('Failed to create data from form: ' . $e->getMessage());
        }
    }

    // Get the names of the rules
    public function getRuleNameData($ruleFormData, $ruleName)
    {
        if ($ruleFormData->isNameSet()) {
            return $ruleName[$ruleFormData->getName()];
        }
    }

    // Get the data from the statuses
    public function getStatusData($documentFormData)
    {
        $statusIndex = $documentFormData['status'];

        $statuses =   [
            'flux.status.new' => 'New',
            'flux.status.predecessor_ok' => 'Predecessor_OK',
            'flux.status.relate_ok' => 'Relate_OK',
            'flux.status.transformed' => 'Transformed',
            'flux.status.ready_to_send' => 'Ready_to_send',
            'flux.status.filter_ok' => 'Filter_OK',
            'flux.status.send' => 'Send',
            'flux.status.filter' => 'Filter',
            'flux.status.no_send' => 'No_send',
            'flux.status.cancel' => 'Cancel',
            'flux.status.filter_ko' => 'Filter_KO',
            'flux.status.predecessor_ko' => 'Predecessor_KO',
            'flux.status.relate_ko' => 'Relate_KO',
            'flux.status.error_transformed' => 'Error_transformed',
            'flux.status.error_checking' => 'Error_checking',
            'flux.status.error_sending' => 'Error_sending',
        ];
        
        return $statuses[$statusIndex];
    }

    // Get the data from the global statuses
    public function getGlobalStatusData($documentFormData)
    {
        $statusList = [
            'flux.gbl_status.open' => 'Open',
            'flux.gbl_status.close' => 'Close',
            'flux.gbl_status.cancel' => 'Cancel',
            'flux.gbl_status.error' => 'Error',
        ];

        if (count($documentFormData['globalStatus']) > 1) {
            $data['customWhere']['gblstatus'] = [];
            foreach ($documentFormData['globalStatus'] as $key => $value) {
                $data['customWhere']['gblstatus'][] = $statusList[$value];
            }
        } elseif (count($documentFormData['globalStatus']) == 1){
            $data['gblstatus'] = $statusList[$documentFormData['globalStatus'][0]];
        } else {
            $data['gblstatus'] = null;
        }

        return $data;   
    }

    // Get the data from the document type
    public function getDocumentType($documentFormData)
    {
        $listOfTypes = 
        [
            'flux.type.create' => 'C',
            'flux.type.update' => 'U',
            'flux.type.delete' => 'D',
            'flux.type.search' => 'S',
        ];

        return $listOfTypes[$documentFormData['type']];
    }

    // Get the data from the module source
    public function getModuleSourceData($ruleFormData)
    {
        $sourceModules = RuleRepository::findModuleSource($this->entityManager);
        $inversedModules = array_flip($sourceModules);

        return $inversedModules[$ruleFormData->getModuleSource()];
    }

    // Get the data from the module target
    public function getModuleTargetData($ruleFormData)
    {
        $targetModules = RuleRepository::findModuleTarget($this->entityManager);
        $inversedModules = array_flip($targetModules);

        return $inversedModules[$ruleFormData->getModuleTarget()];
    }

    // Get the data from the configuration of the limimt of the search
    public function getLimitConfig()
    {
        // Get the limit parameter
        $configRepository = $this->entityManager->getRepository(Config::class);
        $searchLimit = $configRepository->findOneBy(['name' => 'search_limit']);
        if (!empty($searchLimit)) {
            $limit = $searchLimit->getValue();
        }
        return $limit;
    }

    // Initialize the search for pagination and limit and launch the search of the documents
    public function prepareSearch(array $cleanData, int $page = 1, int $limit = 1000): array
    {
        if (empty($cleanData) && $page === 1) {
            return [
                'documents' => [],
                'page' => $page,
                'limit' => $limit,
            ];
        }
        $documents = $this->searchDocuments($cleanData, $page, $limit);
        return [
            'documents' => $documents,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    // Search the documents using a query
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

        // Reference
        if (!empty($data['reference'])
            OR !empty($data['customWhere']['reference'])) {
            if (isset($data['operators']['reference'])) {

                $where .= " AND document.source_date_modified <= :reference ";
            } else {
                $where .= " AND document.source_date_modified >= :reference ";
            }
        }

        // Rule
        if (
                !empty($data['rule'])
            OR !empty($data['customWhere']['rule'])
        ) {
            if (isset($data['operators']['name'])) {
                    $where .= " AND rule.name != :ruleName ";
                
            } else {
                $where .= " AND rule.name = :ruleName ";
            }
        }

        // Status
        if (
            !empty($data['status'])
            or !empty($data['customWhere']['status'])
        ) {
            if (isset($data['operators']['status'])) {
                $where .= " AND document.status != :status ";
            } else {
                $where .= " AND document.status = :status ";
            }
        }

        // Module source
        if (
            !empty($data['module_source'])
            or !empty($data['customWhere']['module_source'])
        ) {
            if (isset($data['operators']['moduleSource'])) {
                $where .= " AND rule.module_source != :module_source ";
            } else {
                $where .= " AND rule.module_source = :module_source ";
            }

        }

        // Module target
        if (
            !empty($data['module_target'])
            or !empty($data['customWhere']['module_target'])
        ) {
            if (isset($data['operators']['moduleTarget'])) {
                $where .= " AND rule.module_target != :module_target ";
            } else {
                $where .= " AND rule.module_target = :module_target ";
            }
        }


        // Document type
        if (
            !empty($data['type'])
            or !empty($data['customWhere']['type'])
        ) {
            if (isset($data['operators']['type'])) {
                $where .= " AND document.type != :type ";
            } else {
                $where .= " AND document.type = :type ";
            }
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

        
        // Target ID
        if (!empty($data['target_id'])) {
            $where .= " AND document.target_id LIKE :target_id ";
        }
        // Source ID
        if (!empty($data['source_id'])) {
            $where .= " AND document.source_id LIKE :source_id ";
        }

        // sort_field
        if (!empty($data['sort_field'])) {
            $orderBy = " ORDER BY ".$data['sort_field'];
        } else {
            $orderBy = " ORDER BY document.date_modified";
        }

        // sort_order
        if (!empty($data['sort_order'])) {
            $orderBy .= " ".$data['sort_order'];
        } else {
            $orderBy .= " DESC";
        }
        

        // if not empty 

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
                users.username,
                rule.name as rule_name, 
                rule.module_source,
                rule.module_target,
                rule.id as rule_id 
            FROM document 
                INNER JOIN rule	
                    ON document.rule_id = rule.id
                INNER JOIN users
                    ON document.created_by = users.id "
                .$join. 
            " WHERE 
                    document.deleted = 0 "
                    .$where.
            $orderBy
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

        // Reference
        if (!empty($data['reference'])) {
            $stmt->bindValue(':reference', $data['reference']);
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
        // Module source
        if (!empty($data['module_source'])) {
            $stmt->bindValue(':module_source', $data['module_source']);
        }
        // Module target
        if (!empty($data['module_target'])) {
            $stmt->bindValue(':module_target', $data['module_target']);
        }
        // customWhere can have several status (open and error from the error dashlet in the home page)
        if (!empty($data['customWhere']['gblstatus'])) {
            $i = 0;
            foreach($data['customWhere']['gblstatus'] as $globalStatusIndex => $gblstatus) {
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

    //Create pagination using the Pagerfanta Bundle based on a request
    private function nav_pagination(array $params, bool $orm = true): array
    {
        /*
         * adapter_em_repository = requete
         * maxPerPage = integer
         * page = page en cours
         */

        $compact = [];
        if ($orm) {
            $queryBuilder = $params['adapter_em_repository'];
            $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));
            $compact['pager'] = $pagerfanta;
        } else {
            $compact['pager'] = new Pagerfanta(new ArrayAdapter($params['adapter_em_repository']));
        }
    
        $compact['pager']->setMaxPerPage(intval($params['maxPerPage']));
    
        try {
            $compact['pager']->setCurrentPage(intval($params['page']));
            $compact['nb'] = $compact['pager']->getNbResults();
            $compact['entities'] = $compact['pager']->getCurrentPageResults();
        } catch (NotValidCurrentPageException $e) {
            throw $this->createNotFoundException(sprintf('Page not found. %s %s %d', $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    
        return $compact;
    }

    /**
     * @Route("/flux/export/csv", name="flux_export_docs_csv")
     */
    public function exportDocumentsToCsv(): Response
    {
        if (!(isset($_POST['csvdocumentids']))) {
            throw $this->createNotFoundException('No document selected');
        }

        // converts the string of document ids into an array
        $_POST['csvdocumentids'] = explode(',', $_POST['csvdocumentids']);

        // fetches the documents from the database
        $documents = $this->entityManager->getRepository(Document::class)->findBy(['id' => $_POST['csvdocumentids']]);

        $rootPath = $this->getParameter('kernel.project_dir');
        $envFilePath = $rootPath . '/.env.local';

        // check if te .env.local file exists, if not then use the .env
        if (!file_exists($envFilePath)) {
            $envFilePath = $rootPath . '/.env';
        }

        if (file_exists($envFilePath)) {
            $lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with($line, 'APP_ENV')) {
                    $env = explode('=', $line, 2)[1] ?? null;
                    break;
                }
            }
            if (!isset($env)) {
                throw new Exception('APP_ENV not found in .env.local file');
            }

            // Define the path of your cache directory
            $cacheDir = $rootPath . '/var/cache/' . $env;

            // Check if the cache directory exists, if not, create it
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0777, true);  // true means it will create nested directories as needed
            }

            // Now try to open the file
            $fp = fopen($cacheDir . '/documents.csv', 'w');
            if ($fp === false) {
                throw new Exception('Failed to open file for writing');
            }

            // creates the header of the csv file according to the following sql of the document table

            $header = [
                'id',
                'rule_id',
                'created_by',
                'modified_by',
                'date_created',
                'date_modified',
                'status',
                'source_id',
                'target_id',
                'source_date_modified',
                'mode',
                'type',
                'attempt',
                'global_status',
                'parent_id',
                'deleted',
                'source',
                'target',
                'history'
            ];

            // writes the header in the csv file
            fputcsv($fp, $header);
            // puts the data of each document in the csv file
            foreach ($documents as $document) {
                // Fetch the docmunet data: for each document, we have 3 types of data: source, target and history
                // We fetch the data of each type and we put it in an string. If there is data, then we put it in the row, otherwise we put an empty string

                $documentDataSource = $this->entityManager->getRepository(DocumentData::class)->findOneBy(['doc_id' => $document->getId(), 'type' => 'S']) ? $this->entityManager->getRepository(DocumentData::class)->findOneBy(['doc_id' => $document->getId(), 'type' => 'S'])->getData() : '';
                $documentDataTarget = $this->entityManager->getRepository(DocumentData::class)->findOneBy(['doc_id' => $document->getId(), 'type' => 'T']) ? $this->entityManager->getRepository(DocumentData::class)->findOneBy(['doc_id' => $document->getId(), 'type' => 'T'])->getData() : '';
                $documentDataHistory = $this->entityManager->getRepository(DocumentData::class)->findOneBy(['doc_id' => $document->getId(), 'type' => 'H']) ? $this->entityManager->getRepository(DocumentData::class)->findOneBy(['doc_id' => $document->getId(), 'type' => 'H'])->getData() : '';

                $row = [
                    $document->getId(),
                    $document->getRule()->getId(),
                    $document->getCreatedBy(),
                    $document->getModifiedBy(),
                    $document->getDateCreated()->format('Y-m-d H:i:s'),
                    $document->getDateModified()->format('Y-m-d H:i:s'),
                    $document->getStatus(),
                    $document->getSource(),
                    $document->getTarget(),
                    $document->getSourceDateModified()->format('Y-m-d H:i:s'),
                    $document->getMode(),
                    $document->getType(),
                    $document->getAttempt(),
                    $document->getGlobalStatus(),
                    $document->getParentId(),
                    $document->getDeleted(),
                    $documentDataSource,
                    $documentDataTarget,
                    $documentDataHistory
                    // get the document document data if the doc id is the same as the document id and getType() is S, otherwise empty string

                ];
                try {
                    fputcsv($fp, $row);
                } catch (\Throwable $e) {
                    throw $this->createNotFoundException('Page not found.' . $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine());
                }
            }

            // closes the csv file
            fclose($fp);

            // adds message to the flashbag
            $this->addFlash('success', 'Documents exported successfully');

            // return response with status ok
            return new Response('csv exported successfully', Response::HTTP_OK);
        } else {
            throw new Exception('.env.local file not found');
        }
    }
}