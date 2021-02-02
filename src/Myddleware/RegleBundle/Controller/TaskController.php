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

namespace Myddleware\RegleBundle\Controller;
use Exception;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\ArrayAdapter;
use Myddleware\RegleBundle\Entity\Log;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

use Pagerfanta\Exception\NotValidCurrentPageException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TaskController extends Controller
{

	/* ******************************************************
	 * TASK
	 ****************************************************** */

	// Liste des tâches
	public function taskListAction($page) {

		// List of task limited to 1000 and rder by status (start first) and date begin
		$conn = $this->get( 'database_connection' );
		$sql = 'SELECT j.*
				FROM Job j
				ORDER BY status DESC, begin DESC
				LIMIT 1000';
					
		$r = $conn->executeQuery( $sql )->fetchAll();
		
		$compact = $this->nav_pagination(array(
			'adapter_em_repository' => $r,
			'maxPerPage' => $this->container->getParameter('pager'),
			'page' => $page
		),false);

	 	return $this->render('RegleBundle:Task:list.html.twig',array(
		       'nb' => $compact['nb'],
		       'entities' => $compact['entities'],
		       'pager' => $compact['pager']
			)
		);	
	}

	// Fiche d'une tâche
	public function viewTaskAction($id,$page) {


		try {
			$em = $this->getDoctrine()->getManager();
	
			// Infos de la tâche
			$task = $em->getRepository('RegleBundle:Job')
					  ->find($id);

			$compact = $this->nav_pagination(array(
				'adapter_em_repository' => $em->getRepository('RegleBundle:Log')
										      ->findBy(
									             array('job'=> $id), 
									             array('id'	=> 'DESC')
						           				),				
				'maxPerPage' => $this->container->getParameter('pager'),
				'page' => $page
			),false);

	        return $this->render('RegleBundle:Task:view/view.html.twig',array(
				'task' => $task,
		        'nb' => $compact['nb'],
		        'entities' => $compact['entities'],
		        'pager' => $compact['pager']
				)
			);	
					  
		} catch(Exception $e) {
			return $this->redirect($this->generateUrl('task_list'));			
		}		
	}
	
	// Stop task
	public function stopTaskAction($id,$page) {
		try {
			$em = $this->getDoctrine()->getManager();
			
			// Get job detail 
			$job = $this->container->get('myddleware_job.job');
			$job->id = $id;
			$jobData = $job->getLogData();
			
			// Stop task and update statistics
			$taskStop = $em->getRepository('RegleBundle:Job')->findOneById($id);	
			$taskStop->setOpen( $jobData['Open'] );
			$taskStop->setClose( $jobData['Close'] );
			$taskStop->setCancel( $jobData['Cancel'] );
			$taskStop->setError( $jobData['Error'] );
			$taskStop->setStatus( 'End' );
			$taskStop->setEnd( new \DateTime );			
			$em->persist($taskStop);
			
			// Add log to indicate this action
			$log = new Log();	
			$log->setDateCreated(new \DateTime);
			$log->setType('W');
			$log->setMessage('The task has been manually stopped. ');
			$log->setRule('');
			$log->setDocument('');
			$log->setRef('');
			$log->setJob($id);
			$em->persist($log);
			$em->flush();
			
			return $this->redirect( $this->generateURL('task_view', array( 'id'=>$id )) );	  
		} catch(Exception $e) {
			return $this->redirect($this->generateUrl('task_list'));				
		}		
	}


	/* ******************************************************
	 * METHODES PRATIQUES
	 ****************************************************** */

	// Crée la pagination avec le Bundle Pagerfanta en fonction d'une requete
	private function nav_pagination($params, $orm = true) {
		
		/*
		 * adapter_em_repository = requete
		 * maxPerPage = integer
		 * page = page en cours
		 */
		
		if(is_array($params)) {
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
        
       		$compact = array();
					
			#On passe l’adapter au bundle qui va s’occuper de la pagination
			if($orm) {
				$compact['pager'] = new Pagerfanta( new DoctrineORMAdapter($params['adapter_em_repository']) );
			}
			else {
				$compact['pager'] = new Pagerfanta( new ArrayAdapter($params['adapter_em_repository']) );
			}
			

			
			#On définit le nombre d’article à afficher par page (que l’on a biensur définit dans le fichier param)
			$compact['pager']->setMaxPerPage($params['maxPerPage']);
			try {
			     $compact['entities'] = $compact['pager']
			           #On indique au pager quelle page on veut
			           ->setCurrentPage($params['page'])
			           #On récupère les résultats correspondant
			           ->getCurrentPageResults();
					   
				$compact['nb'] = $compact['pager']->getNbResults();
					   
			 } catch (\Pagerfanta\Exception\NotValidCurrentPageException $e) {
				#Si jamais la page n’existe pas on léve une 404
				throw $this->createNotFoundException("Cette page n'existe pas.");
			}        
        
        	return $compact;	
		}
		else {
			return false;
		}		
	}

	
}
