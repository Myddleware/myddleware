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
 * Class TaskController.
 *
 * @Route("/rule")
 */
class TaskController extends AbstractController
{
    protected $params;

    /**
     * @var JobManager
     */
    private $jobManager;

    /**
     * @var JobRepository
     */
    private $jobRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var DocumentRepository
     */
    private $documentRepository;
    /**
     * @var LoggerInterface
     */
    private $LoggerInterface;

    /**
     * TaskController constructor.
     */
    public function __construct(
        JobManager $jobManager,
        JobRepository $jobRepository,
        DocumentRepository $documentRepository,
        LoggerInterface $LoggerInterface,
        EntityManagerInterface $entityManager
    ) {
        $this->jobRepository = $jobRepository;
        $this->jobManager = $jobManager;
        $this->documentRepository = $documentRepository;
        $this->LoggerInterface = $LoggerInterface;
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

    /**
     * Liste des tâches.
     *
     * @param $page
     *
     * @return Response
     *
     * @Route("/task/list", name="task_list", defaults={"page"=1})
     * @Route("/task/list/page-{page}", name="task_list_page", requirements={"page"="\d+"})
     */
    public function taskListAction($page)
    {
        // List of task limited to 1000 and rder by status (start first) and date begin
        $jobs = $this->jobRepository->findBy([], ['status' => 'DESC', 'begin' => 'DESC'], 1000);
        $compact = $this->nav_pagination([
            'adapter_em_repository' => $jobs,
            'maxPerPage' => isset($this->params['pager']) ? $this->params['pager'] : 25,
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

    // Fiche d'une tâche

    /**
     * @param $page
     *
     * @return RedirectResponse|Response
     *
     * @Route("/task/view/{id}/log", name="task_view", defaults={"page"=1})
     * @Route("/task/view/{id}/log/page-{page}", name="task_view_page", requirements={"page"="\d+"})
     */
    public function viewTaskAction(Job $task, $page)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $task = $this->jobRepository->find($task);
            $taskId = $task->getId();
            $compact = $this->nav_pagination([
                'adapter_em_repository' => $em->getRepository(Log::class)->findBy(['job' => $taskId], ['id' => 'DESC']),
                'maxPerPage' => isset($this->params['pager']) ? $this->params['pager'] : 25,
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
     * Stop task.
     *
     * @return RedirectResponse
     *
     * @Route("/task/stop/{id}", name="task_stop")
     */
    public function stopTaskAction(Job $taskStop)
    {
        try {
            $em = $this->getDoctrine()->getManager();

            // Get job detail
            $jobData = $this->jobManager->getLogData($taskStop);

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
            $log->setDateCreated(new \DateTime());
            $log->setType('W');
            $log->setMessage('The task has been manually stopped. ');
            $log->setJob($taskStop);
            $em->persist($log);
            $em->flush();

            return $this->redirect($this->generateURL('task_view', ['id' => $taskStop->getId()]));
        } catch (Exception $e) {
            return $this->redirect($this->generateUrl('task_list'));
        }
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
