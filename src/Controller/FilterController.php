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

use App\Entity\Rule;
use App\Entity\Config;
use App\Entity\Document;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use App\Form\Type\FilterType;
use App\Manager\ToolsManager;
use App\Service\SessionService;
use App\Form\Type\DocFilterType;
use App\Form\Type\ItemFilterType;
use App\Form\Type\ProfileFormType;
use App\Repository\RuleRepository;
use App\Form\Type\ResetPasswordType;
use Pagerfanta\Adapter\ArrayAdapter;
use App\Form\Type\CombinedFilterType;
use App\Service\UserManagerInterface;
use App\Repository\DocumentRepository;
use App\Service\AlertBootstrapInterface;
use Doctrine\ORM\EntityManagerInterface;
// use the ItemFilterType
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;

use Pagerfanta\Exception\NotValidCurrentPageException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use App\Manager\DocumentManager;



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
        ParameterBagInterface $params,
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
        // $this->params = $params;
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

    /**
     * @Route("/document/list/search-{search}", name="document_list", defaults={"page"=1})
     * @Route("/document/list/page-{page}", name="document_list_page", requirements={"page"="\d+"})
     */
    public function testFilterAction(Request $request, int $page = 1, int $search = 1): Response
    {

        $formFilter = $this->createForm(FilterType::class, null);
        $form = $this->createForm(CombinedFilterType::class, null, [
            'entityManager' => $this->getDoctrine()->getManager(),
        ]);
        
        $conditions = 0;
        //Check the user timezone
        if ($timezone = '') {
            $timezone = 'UTC';
        } else {
            $timezone = $this->getUser()->getTimezone();
        }


        if ($request->isMethod('POST') || $page !== 1 || ($request->isMethod('GET') && $this->verifyIfEmptyFilters() === false)) {
           
            $form->handleRequest($request);

            
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

            if ($form->get('save')->isClicked() || $page !== 1 || ($request->isMethod('GET') && $this->verifyIfEmptyFilters() === false)) {

                if ($form->isSubmitted() && $form->isValid()) {

                    $ruleRepository = $this->entityManager->getRepository(Rule::class);
                    $rules = $ruleRepository->findAll();
                    $ruleName = [];
                    foreach ($rules as $value) {
                        if ($value->getDeleted() == false) {
                            $ruleName[] = $value->getName();
                        }
                    }

                    $issetName = ($form->get('rule')->getData()->isNameSet());
                    // Rule name
                    if ($issetName) {
                        if (($form->get('rule')->getData()->getName() !== null)
                            || $form->get('rule')->getData()->getName() === '0'
                        ) {
                            $data['rule'] = $ruleName[$form->get('rule')->getData()->getName()];
                        } elseif (empty($form->get('rule')->getData()->getName())) {
                            $data['rule'] = null;
                        }
                    } else {
                        $data['rule'] = null;
                    }

                    // Statuses
                    if ($form->get('document')->getData() !== null) {
                        if ($form->get('document')->getData()->isStatusSet()) {
                            $documentTest = $form->get('document');
                            $documentTestData = $documentTest->getData();
                            $docStatus = $documentTestData->getStatus();

                            $statuses = DocumentRepository::findStatusType($this->entityManager);
                            $inversedStatuses = array_flip($statuses);


                            $data['status'] = $inversedStatuses[$docStatus];
                        } else {
                            $data['status'] = null;
                        }
                    } else {
                        $data['status'] = null;
                    }


                    // Global Statuses
                    if ($form->get('document')->getData() !== null) {
                        if ($form->get('document')->getData()->isGlobalStatusSet()) {
                            $statusList = [
                                'flux.gbl_status.open' => 'Open',
                                'flux.gbl_status.close' => 'Close',
                                'flux.gbl_status.cancel' => 'Cancel',
                                'flux.gbl_status.error' => 'Error',
                            ];

                            $data['gblstatus'] = $statusList[$form->get('document')->getData()->getGlobalStatus()];
                        } else {
                            $data['gblstatus'] = null;
                        }
                    } else {
                        $data['gblstatus'] = null;
                    }

                    // Source Module
                    if ($form->get('rule')->getData() !== null) {
                        if ($form->get('rule')->getData()->isModuleSourceSet()) {
                            $ruleTest = $form->get('rule');
                            $ruleTestData = $ruleTest->getData();
                            $ruleModuleSource = $ruleTestData->getModuleSource();
                            
                            
                            $sourceModules = RuleRepository::findModuleSource($this->entityManager);
                            $inversedModules = array_flip($sourceModules);
                            
                            
                            $data['module_source'] = $inversedModules[$ruleModuleSource];
                        } else {
                            $data['module_source'] = null;
                        }
                    } else {
                        $data['module_source'] = null;
                    }

                    // Target module
                    if ($form->get('rule')->getData() !== null) {
                        if ($form->get('rule')->getData()->isModuleTargetSet()) {
                            $ruleTestTarget = $form->get('rule');
                            $ruleTestDataTarget = $ruleTestTarget->getData();
                            $ruleModuleTarget = $ruleTestDataTarget->getModuleTarget();
                            
                            
                            $targetModules = RuleRepository::findModuleTarget($this->entityManager);
                            $inversedModules = array_flip($targetModules);
                            
                            
                            $data['module_target'] = $inversedModules[$ruleModuleTarget];
                        } else {
                            $data['module_target'] = null;
                        }
                    } else {
                        $data['module_target'] = null;
                    }

                    // Source
                    if ($form->get('document')->getData() !== null) {
                        if ($form->get('document')->getData()->isSourceSet()) {

                            $data['source_id'] = $form->get('document')->getData()->getSource();
                        } else {
                            $data['source_id'] = null;
                        }
                    } else {
                        $data['source_id'] = null;
                    }
                    
                    // Target 
                    if ($form->get('document')->getData() !== null) {
                        if ($form->get('document')->getData()->isTargetSet()) {

                            $data['target_id'] = $form->get('document')->getData()->getTarget();
                        } else {
                            $data['target_id'] = null;
                        }
                    } else {
                        $data['target_id'] = null;
                    }

                    // Document type
                    if ($form->get('document')->getData() !== null) {
                        if ($form->get('document')->getData()->isTypeSet()) {

                            $listOfTypes = 
                            [
                                'flux.type.create' => 'C',
                                'flux.type.update' => 'U',
                                'flux.type.delete' => 'D',
                                'flux.type.search' => 'S',
                            ];
                            

                            $data['type'] = $listOfTypes[$form->get('document')->getData()->getType()];
                        } else {
                            $data['type'] = null;
                        }
                    } else {
                        $data['type'] = null;
                    }
                    $startDate = $form->get('document')->get('dateModified')->get('date_modif_start')->getData()->getDateModified()->format('Y-m-d, H:i:s');
                    $endDate = $form->get('document')->get('dateModified')->get('date_modif_end')->getData()->getDateModified()->format('Y-m-d, H:i:s');
                    dd($startDate, $endDate);
                    if ($form->get('document')->getData() !== null) {
                        if ($form->get('document')->getData()->isReferenceStartDateSet()) {
                            $data['date_modif_start'] = $form->get('document')->getData()->getSourceDateModified()->format('Y-m-d, H:i:s');
                        } else {
                            $data['date_modif_start'] = null;
                        }
                    } else {
                        $data['date_modif_start'] = null;
                    }
                   

                    // Reference date start
                    // if ($form->get('document')->getData() !== null) {
                    //     if ($form->get('document')->getData()->isReferenceStartDateSet()) {
                    //         $data['date_modif_start'] = $form->get('document')->getData()->getSourceDateModified()->format('Y-m-d, H:i:s');
                    //     } else {
                    //         $data['date_modif_start'] = null;
                    //     }
                    // } else {
                    //     $data['date_modif_start'] = null;
                    // }


                    // Remove the null values
                    foreach ($data as $key => $value) {
                        if (is_null($value)) {
                            unset($data[$key]);
                        }
                    }

                    // dd($data);
                    
                    if ($page === 1) {
                    // Store the filter in session
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
                    
                    if (!empty($data['module_source'])) {
                        $this->sessionService->setFluxFilterModuleSource($data['module_source']);
                    } else {
                        $this->sessionService->removeFluxFilterSourceId();
                    }

                    if (!empty($data['module_target'])) {
                        $this->sessionService->setFluxFilterModuleTarget($data['module_target']);
                    } else {
                        $this->sessionService->removeFluxFilterTargetId();
                    }
                } // end if page === 1

                else { // if page different from 1 so pagination
                        

                }

                } else { // if form is not valid

                    // return $this->render('testFilter.html.twig', [
                    //     'form' => $form->createView(),
                    //     //'formDoc' => $formDoc->createView(), 
                    //     'formFilter'=> $formFilter->createView(),
                    //     // 'pager' => $compact['pager'],
                    //     // 'documents' => $documents,
                    //     // // 'form' => $form->createView(),
                    //     // 'nb' => $compact['nb'],
                    //     // 'entities' => $compact['entities'],
                    //     // 'pager' => $compact['pager'],
                    //     'condition' => $conditions,
                    //     'timezone' => $timezone,
                    // ]);
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
                        $data['module_source']      = $this->sessionService->getFluxFilterModuleSource();
                        $data['module_target']      = $this->sessionService->getFluxFilterModuleTarget();

                    // $documents = [];
                    // $page = 1;
                    // $this->params['pager'] = 25;

                    if (
                        count(array_filter($data)) === 0
                        and $page == 1
                    ) {
                        $doNotSearch = true;
                    }
                }

                if ($doNotSearch) {
                    $documents = array();
                }
                
                if (!$doNotSearch) {
                    $searchParameters = $this->prepareSearch($data, $page, $limit);
                    $documents = $searchParameters['documents'];
                    $page = $searchParameters['page'];
                    $limit = $searchParameters['limit'];
                }
        
                $compact = $this->nav_pagination([
                    'adapter_em_repository' => $documents,
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
        
                    
        
                }
        
                // throw $this->createNotFoundException('Error');

            } // end if click_filter

        } // end if POST

        // else {
        //     $documents = [];
        //             $page = 1;
        // }
        
        if (!isset($compact)) {
            $documents = [];

            $compact = $this->nav_pagination([
                'adapter_em_repository' => $documents,
                'maxPerPage' => 25,
                'page' => 1,
            ], false);
        }
        
        

        return $this->render('testFilter.html.twig', [
            'form' => $form->createView(),
            //'formDoc' => $formDoc->createView(), 
            'formFilter'=> $formFilter->createView(),
            // 'pager' => $compact['pager'],
            'documents' => $documents,
            // 'form' => $form->createView(),
            'nb' => $compact['nb'],
            'entities' => $compact['entities'],
            'pager' => $compact['pager'],
            'condition' => $conditions,
            'timezone' => $timezone,
        ]);

    }

    public function verifyIfEmptyFilters()
    {
        $isEmpty = true;
        $data['target_content']     = $this->sessionService->getFluxFilterSourceContent();
        $data['target_content']     = $this->sessionService->getFluxFilterTargetContent();
        $data['date_modif_start']   = $this->sessionService->getFluxFilterDateModifStart();
        $data['date_modif_end']     = $this->sessionService->getFluxFilterDateModifEnd();
        $data['rule']               = $this->sessionService->getFluxFilterRuleName();
        $data['status']             = $this->sessionService->getFluxFilterStatus();
        $data['gblstatus']          = $this->sessionService->getFluxFilterGlobalStatus();
        $data['type']               = $this->sessionService->getFluxFilterType();
        $data['target_id']          = $this->sessionService->getFluxFilterTargetId();
        $data['source_id']          = $this->sessionService->getFluxFilterSourceId();
        $data['module_source']      = $this->sessionService->getFluxFilterModuleSource();

        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $isEmpty = false;
                break;
            }
        }
        return $isEmpty;
    }

    public function getLimitConfig()
    {
        // Get the limit parameter
        $configRepository = $this->getDoctrine()->getManager()->getRepository(Config::class);
        $searchLimit = $configRepository->findOneBy(['name' => 'search_limit']);
        if (!empty($searchLimit)) {
            $limit = $searchLimit->getValue();
        }
        return $limit;
    }


    public function prepareSearch($cleanData, $page = 1, $limit = 1000)
    {
        $doNotSearch = false;

            if (
                count(array_filter($cleanData)) === 0
                and $page == 1
            ) {
                $doNotSearch = true;
            }

            if (!$doNotSearch) {
                $documents = $this->searchDocuments($cleanData, $page, $limit);
                // $resultSearch = $this->searchDocuments($data, $page, $limit); 
            } else {
                $documents = array();
            }

        $searchParameters = [
            'documents' => $documents,
            'page' => $page,
            'limit' => $limit,
        ];
        return $searchParameters;
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
         // Module source
         if (!empty($data['module_source'])) {
            $where .= " AND rule.module_source = :module_source ";
        }

        // Module target
        if (!empty($data['module_target'])) {
            $where .= " AND rule.module_target = :module_target ";
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
}
