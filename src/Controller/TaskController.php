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
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/rule")
 */
class TaskController extends AbstractController
{
    protected $params;
    private JobManager $jobManager;
    private JobRepository $jobRepository;
    private EntityManagerInterface $entityManager;
    private DocumentRepository $documentRepository;
    private LoggerInterface $logger;

    public function __construct(
        JobManager $jobManager,
        JobRepository $jobRepository,
        DocumentRepository $documentRepository,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ) {
        $this->jobRepository = $jobRepository;
        $this->jobManager = $jobManager;
        $this->documentRepository = $documentRepository;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        // Init parameters
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

    /**
     * @Route("/task/list", name="task_list", defaults={"page"=1})
     * @Route("/task/list/page-{page}", name="task_list_page", requirements={"page"="\d+"})
     */
    public function tasksList($page): Response
    {
        // Get the search limit from requesting the database for config 'search_limit'
        $searchLimit = $this->params['search_limit'] ?? 1000;

        // Execute query with limit to prevent timeouts on large job tables
        $jobs = $this->jobRepository->findJobsForPagination($searchLimit)
            ->getQuery()
            ->getResult();

        $compact = $this->nav_pagination([
            'adapter_em_repository' => $jobs,
            'maxPerPage' => $this->params['pager'] ?? 25,
            'page' => $page,
        ], false);

        //Check the user timezone
        $timezone = $this->getUser()->getTimezone();
        if (empty($timezone)) {
            $timezone = 'UTC';
        }

        return $this->render('Task/list.html.twig', [
            'nb' => $compact['nb'],
            'entities' => $compact['entities'],
            'pager' => $compact['pager'],
            'timezone' => $timezone,
        ]
        );
    }

    /**
     * @Route("/task/view/{id}/log", name="task_view", defaults={"page"=1})
     * @Route("/task/view/{id}/log/page-{page}", name="task_view_page", requirements={"page"="\d+"})
     */
    public function viewTask(Job $task, $page)
    {
        try {
            $em = $this->entityManager;
            $task = $this->jobRepository->find($task);
            $taskId = $task->getId();
            $compact = $this->nav_pagination([
                'adapter_em_repository' => $em->getRepository(Log::class)->findBy(['job' => $taskId], ['id' => 'DESC']),
                'maxPerPage' => $this->params['pager'] ?? 25,
                'page' => $page,
            ], false);
            //Check the user timezone
            if ($timezone = '') {
                $timezone = 'UTC';
            } else {
                $timezone = $this->getUser()->getTimezone();
            }

            return $this->render('Task/view/view.html.twig', [
                'task' => $task,
                'nb' => $compact['nb'],
                'entities' => $compact['entities'],
                'pager' => $compact['pager'],
                'timezone' => $timezone,
            ]
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage().''.$e->getFile().' '.$e->getLine());

            return $this->redirect($this->generateUrl('task_list'));
        }
    }

    /**
     * @Route("/task/stop/{id}", name="task_stop")
     */
    public function stopTask(Job $taskStop): RedirectResponse
    {
        try {
            $em = $this->entityManager;

            // Get job detail
            $this->jobManager->setId($taskStop->getId());
            $jobData = $this->jobManager->getLogData();

            // Stop task and update statistics
            $taskStop->setOpen($jobData['Open']);
            $taskStop->setClose($jobData['Close']);
            $taskStop->setCancel($jobData['Cancel']);
            $taskStop->setError($jobData['Error']);
            $taskStop->setStatus('End');
            $taskStop->setEnd(new \DateTime());
            $em->persist($taskStop);

            // Add log to indicate this action
            $log = new Log();
            $log->setCreated(new \DateTime());
            $log->setType('W');
            $log->setMessage('The task has been manually stopped. ');
            $log->setJob($taskStop);
            $em->persist($log);

			// Remove lock on document locked by this job
			$this->documentRepository->removeLock($taskStop->getId());

            // Remove lock (send and read) on rule locked by this job
            $ruleRepository = $this->entityManager->getRepository(Rule::class);
            $ruleRepository->removeLock($taskStop->getId());
            $em->flush();

            return $this->redirect($this->generateURL('task_view', ['id' => $taskStop->getId()]));
        } catch (Exception $e) {
			$this->logger->error('Failed to stop task '.$taskStop->getId().' : '.$e->getMessage().''.$e->getFile().' '.$e->getLine());
            return $this->redirect($this->generateUrl('task_list'));
        }
    }


    /**
     * @Route("/task/stopall", name="task_stopall")
     */
    public function stopAllTask(): RedirectResponse
    {   
        // Find all the tasks that are started
        $tasks = $this->jobRepository->findBy(['status' => 'Start']);
        foreach ($tasks as $task) {
            $this->stopTask($task);
        }

        // Add a flash message of success to say that all the tasks have been stopped
        $this->addFlash('task.stopAll.success', 'All the tasks have been stopped');

        return $this->redirect($this->generateUrl('task_list'));

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
}
