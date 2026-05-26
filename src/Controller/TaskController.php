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
use App\Entity\Job;
use App\Entity\Log;
use App\Entity\Document;
use App\Entity\Rule;
use App\Manager\JobManager;
use App\Repository\DocumentRepository;
use App\Repository\JobRepository;
use App\Service\DebugLogger;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/task')]
class TaskController extends AbstractController
{
    protected $params;
    private JobManager $jobManager;
    private JobRepository $jobRepository;
    private EntityManagerInterface $entityManager;
    private DocumentRepository $documentRepository;
    private LoggerInterface $logger;
    private DebugLogger $debugLogger;

    public function __construct(
        JobManager $jobManager,
        JobRepository $jobRepository,
        DocumentRepository $documentRepository,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        DebugLogger $debugLogger
    ) {
        $this->jobRepository = $jobRepository;
        $this->jobManager = $jobManager;
        $this->documentRepository = $documentRepository;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->debugLogger = $debugLogger;
        $configRepository = $this->entityManager->getRepository(Config::class);
        $configs = $configRepository->findAll();
        if (!empty($configs)) {
            foreach ($configs as $config) {
                $this->params[$config->getName()] = $config->getvalue();
            }
        }
        if (empty($this->DocumentRepository)) {
            $this->documentRepository = $this->entityManager->getRepository(Document::class);
        }
    }

    #[Route('/list', name: 'task_list', defaults: ['page' => 1])]
    #[Route('/list/page-{page}', name: 'task_list_page', requirements: ['page' => '\d+'])]
    public function tasksList(Request $request, $page): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'page' => $page]);
        $__debugReturn = null;
        try {
            $searchLimit = $this->params['search_limit'] ?? 1000;

            $filterKeys = ['param', 'status', 'begin_date', 'end_date'];
            $filters = [];
            foreach ($filterKeys as $key) {
                $val = $request->query->get($key, '');
                if ($val !== '' && $val !== null) {
                    $filters[$key] = $val;
                }
            }

            $hasFilters = !empty($filters);

            if ($hasFilters) {
                $jobs = $this->jobRepository->findJobsFiltered($filters, $searchLimit)
                    ->getQuery()
                    ->getResult();
            } else {
                $jobs = $this->jobRepository->findJobsForPagination($searchLimit)
                    ->getQuery()
                    ->getResult();
            }

            $compact = $this->nav_pagination([
                'adapter_em_repository' => $jobs,
                'maxPerPage' => $this->params['pager'] ?? 25,
                'page' => $page,
            ], false);

            $filterOptions = $this->jobRepository->getFilterOptions($searchLimit);

            $timezone = $this->getUser()->getTimezone();
            if (empty($timezone)) {
                $timezone = 'UTC';
            }

            return $__debugReturn = $this->render('Task/list.html.twig', [
                'nb' => $compact['nb'],
                'entities' => $compact['entities'],
                'pager' => $compact['pager'],
                'timezone' => $timezone,
                'filterOptions' => $filterOptions,
                'filters' => $filters,
            ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    #[Route('/view/{id}/log', name: 'task_view', defaults: ['page' => 1])]
    #[Route('/view/{id}/log/page-{page}', name: 'task_view_page', requirements: ['page' => '\d+'])]
    public function viewTask(Job $task, $page)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['task' => $task, 'page' => $page]);
        $__debugReturn = null;
        try {
            try {
                $em = $this->entityManager;
                $task = $this->jobRepository->find($task);
                $taskId = $task->getId();
                $compact = $this->nav_pagination([
                    'adapter_em_repository' => $em->getRepository(Log::class)->findBy(['job' => $taskId], ['id' => 'DESC']),
                    'maxPerPage' => $this->params['pager'] ?? 25,
                    'page' => $page,
                ], false);
                if ($timezone = '') {
                    $timezone = 'UTC';
                } else {
                    $timezone = $this->getUser()->getTimezone();
                }

                return $__debugReturn = $this->render('Task/view/view.html.twig', [
                    'task' => $task,
                    'nb' => $compact['nb'],
                    'entities' => $compact['entities'],
                    'pager' => $compact['pager'],
                    'timezone' => $timezone,
                ]
                );
            } catch (Exception $e) {
                $this->logger->error($e->getMessage().''.$e->getFile().' '.$e->getLine());

                return $__debugReturn = $this->redirect($this->generateUrl('task_list'));
            }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    #[Route('/stop/{id}', name: 'task_stop')]
    public function stopTask(Job $taskStop): RedirectResponse
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['taskStop' => $taskStop]);
        $__debugReturn = null;
        try {
            try {
                $em = $this->entityManager;

                $this->jobManager->setId($taskStop->getId());
                $jobData = $this->jobManager->getLogData();

                $taskStop->setOpen($jobData['Open']);
                $taskStop->setClose($jobData['Close']);
                $taskStop->setCancel($jobData['Cancel']);
                $taskStop->setError($jobData['Error']);
                $taskStop->setStatus('End');
                $taskStop->setEnd(new \DateTime());
                $em->persist($taskStop);

                $log = new Log();
                $log->setCreated(new \DateTime());
                $log->setType('W');
                $log->setMessage('The task has been manually stopped. ');
                $log->setJob($taskStop);
                $em->persist($log);

                $this->documentRepository->removeLock($taskStop->getId());

                $ruleRepository = $this->entityManager->getRepository(Rule::class);
                $ruleRepository->removeLock($taskStop->getId());
                $em->flush();

                return $__debugReturn = $this->redirect($this->generateURL('task_view', ['id' => $taskStop->getId()]));
            } catch (Exception $e) {
                $this->logger->error('Failed to stop task '.$taskStop->getId().' : '.$e->getMessage().''.$e->getFile().' '.$e->getLine());
                return $__debugReturn = $this->redirect($this->generateUrl('task_list'));
            }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    #[Route('/stopall', name: 'task_stopall')]
    public function stopAllTask(): RedirectResponse
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            $tasks = $this->jobRepository->findBy(['status' => 'Start']);
            foreach ($tasks as $task) {
                $this->stopTask($task);
            }

            $this->addFlash('task.stopAll.success', 'All the tasks have been stopped');

            return $__debugReturn = $this->redirect($this->generateUrl('task_list'));
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    private function nav_pagination($params, $orm = true)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['params' => $params, 'orm' => $orm]);
        $__debugReturn = null;
        try {
            if (is_array($params)) {
                $compact = [];

                if ($orm) {
                    $compact['pager'] = new Pagerfanta(new QueryAdapter($params['adapter_em_repository']));
                } else {
                    $compact['pager'] = new Pagerfanta(new ArrayAdapter($params['adapter_em_repository']));
                }

                $compact['pager']->setMaxPerPage($params['maxPerPage']);
                try {
                    $compact['entities'] = $compact['pager']
                           ->setCurrentPage($params['page'])
                           ->getCurrentPageResults();

                    $compact['nb'] = $compact['pager']->getNbResults();
                } catch (\Pagerfanta\Exception\NotValidCurrentPageException $e) {
                    throw $this->createNotFoundException("Cette page n'existe pas.");
                }

                return $__debugReturn = $compact;
            }

            return $__debugReturn = false;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}
