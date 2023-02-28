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
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use App\Form\Type\FilterType;
use App\Manager\ToolsManager;
use App\Form\Type\ItemFilterType;
use App\Form\Type\ProfileFormType;
use App\Repository\RuleRepository;
use App\Form\Type\ResetPasswordType;
use Pagerfanta\Adapter\ArrayAdapter;
use App\Service\UserManagerInterface;
use App\Repository\DocumentRepository;
use App\Service\AlertBootstrapInterface;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
// use the ItemFilterType
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
    private $params;
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

    public function __construct(
        KernelInterface $kernel,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params,
        TranslatorInterface $translator,
        ToolsManager $toolsManager,
        AlertBootstrapInterface $alert,
        DocumentRepository $documentRepository,
    ) {
        $this->kernel = $kernel;
        $this->env = $kernel->getEnvironment();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->translator = $translator;
        $this->toolsManager = $toolsManager;
        $this->alert = $alert;
        $this->documentRepository = $documentRepository;
    }

    /**
     * @Route("/document/list", name="document_list")
     */
    public function testFilterAction(Request $request)
    {
        $form = $this->createForm(ItemFilterType::class, null, [
            'entityManager' => $this->getDoctrine()->getManager()
        ]);

        $formFilter = $this->createForm(FilterType::class, null);

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            
            $rules = RuleRepository::findActiveRulesNames($this->entityManager, true);
            $cleanData = [
                'rule' => $rules[$form->get('name')->getData()],
            ];

            $limit = $this->getLimitConfig();
            $searchParameters = $this->prepareSearch($cleanData, 1, $limit);
            $documents = $searchParameters['documents'];
            $page = $searchParameters['page'];
            $limit = $searchParameters['limit'];
            


            
        } else {
            $documents = [];
            $page = 1;
            // $this->params['pager'] = 25;
        }

        $compact = $this->nav_pagination([
            'adapter_em_repository' => $documents,
            // 'maxPerPage' => $this->params['pager'] ?? 25,
            'maxPerPage' => 25,
            'page' => $page,
        ], false);
        
        

        return $this->render('testFilter.html.twig', [
            'pager' => $compact['pager'],
            // 'documents' => $documents,
            'form' => $form->createView(),
            'formFilter'=> $formFilter->createView(),
        ]);



        return $this->render('testFilter.html.twig', array(
            'form' => $form->createView(),
            // 'rules' => $listRuleName
        ));
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
