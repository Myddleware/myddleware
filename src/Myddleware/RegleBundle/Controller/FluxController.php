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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Exception\NotValidCurrentPageException;

use Myddleware\RegleBundle\Entity\Solution;
use Myddleware\RegleBundle\Entity\Connector;
use Myddleware\RegleBundle\Entity\DocumentAudit;

use Myddleware\RegleBundle\Classes\document as doc;

class FluxControllerCore extends Controller
{
	protected $llimitListFlux = 1000;

	/* ******************************************************
	 * FLUX
	 ****************************************************** */

	public function fluxErrorByRuleAction($id) {
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		$em = $this->getDoctrine()->getManager();

		// Detecte si la session est le support ---------
		$permission =  $this->get('myddleware.permission');
		if( $permission->isAdmin($this->getUser()->getId()) ) {
			$list_fields_sql = 
				array('id' => $id
			);			
		}
		else {
			$list_fields_sql = 
				array(
				'id' => $id,
				'createdBy' => $this->getUser()->getId()
			);				
		}
		// Detecte si la session est le support ---------	

		// Infos des flux
		$rule = $em->getRepository('RegleBundle:Rule')
                   ->findBy( $list_fields_sql );		
		if($rule) {
			$myddlewareSession['flux_filter']['c']['rule'] = $rule[0]->getName();
			$myddlewareSession['flux_filter']['c']['gblstatus'] = 'Error';			
			$myddlewareSession['flux_filter']['where'] = "WHERE name='".$rule[0]->getName()."' AND global_status IN ('Error','Open') ";					
		}
		else {
			unset($myddlewareSession['flux_filter']);
		}
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
		return $this->redirect($this->generateUrl('flux_list'));	
	}
	 	 
 	// LISTE DES FLUX
 	public function fluxListAction($page) {
 		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		//--- Liste status traduction
		$lstStatusTwig = doc::lstStatus();		
		foreach ($lstStatusTwig as $key => $value) {
			$lstStatus[ $key ] = $this->get('translator')->trans( $value );
		}	
		asort($lstStatus);
		//---
		
		//--- Liste Global status traduction
		$lstGblStatusTwig = doc::lstGblStatus();
		
		foreach ($lstGblStatusTwig as $key => $value) {
			$lstGblStatus[ $key ] = $this->get('translator')->trans( $value );
		}	
		asort($lstGblStatus);		
		//---
	
		$em = $this->getDoctrine()->getManager();
		
		// Detecte si la session est le support ---------
		$permission = $this->get('myddleware.permission');
		
		if( $permission->isAdmin( $this->getUser()->getId()) ) {
			
			$rule = $this->getDoctrine()
                         ->getManager()
                         ->getRepository('RegleBundle:Rule')
                         ->findAll();			
		}
		else {
			$list_fields_sql = 
				array('createdBy' => $this->getUser()->getId()
			);
			
			$rule = $em->getRepository('RegleBundle:Rule')->findBy($list_fields_sql);			
		}
		// Detecte si la session est le support ---------		

		// Liste des règles
		$lstRuleName = array();		
		if($rule) {
			
			foreach ($rule as $r) {
				$lstRuleName[$r->getName()] = $r->getName();
			}	
			
			asort($lstRuleName);
		}		   
		
		$form = $this->createFormBuilder()
		
					 ->add('date_create_start','text', array(
						'data'=> ((isset($myddlewareSession['flux_filter']['c']['date_create_start'])) ? $myddlewareSession['flux_filter']['c']['date_create_start'] : false),
					 	'required'=> false, 
					 	'attr' => array('class' => 'calendar')))
					 
					 ->add('date_create_end','text', array(
					 	'data'=> ((isset($myddlewareSession['flux_filter']['c']['date_create_end'])) ? $myddlewareSession['flux_filter']['c']['date_create_end'] : false),
					 	'required'=> false, 
					 	'attr' => array('class' => 'calendar')))
					 				 
					 ->add('date_modif_start','text', array(
					 	'data'=> ((isset($myddlewareSession['flux_filter']['c']['date_modif_start'])) ? $myddlewareSession['flux_filter']['c']['date_modif_start'] : false),
					 	'required'=> false, 
					 	'attr' => array('class' => 'calendar')))
					 				 
					 ->add('date_modif_end','text', array(
					 	'data'=> ((isset($myddlewareSession['flux_filter']['c']['date_modif_end'])) ? $myddlewareSession['flux_filter']['c']['date_modif_end'] : false),
					 	'required'=> false, 
					 	'attr' => array('class' => 'calendar')))
					 
					 ->add('rule','text', array( 
					 	'data'=> ((isset($myddlewareSession['flux_filter']['c']['rule'])) ? $myddlewareSession['flux_filter']['c']['rule'] : false),
					 	'required'=> false	))	
						
					 ->add('rule', 'choice', array(
							       'choices'   => $lstRuleName,
								   'data'=> ((isset($myddlewareSession['flux_filter']['c']['rule'])) ? $myddlewareSession['flux_filter']['c']['rule'] : false),
							       'required'  => false
						 ))						
						
					 ->add('status', 'choice', array(
							       'choices'   => $lstStatus,
								   'data'=> ((isset($myddlewareSession['flux_filter']['c']['status'])) ? $myddlewareSession['flux_filter']['c']['status'] : false),
							       'required'  => false
						 ))
						 
					 ->add('gblstatus', 'choice', array(
							       'choices'   => $lstGblStatus,
								   'data'=> ((isset($myddlewareSession['flux_filter']['c']['gblstatus'])) ? $myddlewareSession['flux_filter']['c']['gblstatus'] : false),
							       'required'  => false
						 ))						 
						 
					->add('click_filter', 'submit', array(
					    'attr' => array(
					    	'class' => 'btn-mydinv'
						),
						'label' => $this->get('translator')->trans( 'list_flux.btn.filter' ),
					))		

					->add('source_id','text', array( 
						'data'=> ((isset($myddlewareSession['flux_filter']['c']['source_id'])) ? $myddlewareSession['flux_filter']['c']['source_id'] : false),
						'required'=> false	))	

					->add('target_id','text', array( 
						'data'=> ((isset($myddlewareSession['flux_filter']['c']['target_id'])) ? $myddlewareSession['flux_filter']['c']['target_id'] : false),
						'required'=> false	))							
					 					  
					->getForm();

	    $form->handleRequest( $this->get('request') );
		// condition d'affichage
		$where = ((isset($myddlewareSession['flux_filter']['where']) ? $myddlewareSession['flux_filter']['where'] : ''));
		$conditions = 0;
		//---[ FORM ]-------------------------
		if( $form->get('click_filter')->isClicked() ) {
			$data = $this->getRequest()->get($form->getName());
			$where = 'WHERE ';
			
			if(!empty( $data['date_create_start'] ) && is_string($data['date_create_start'])) {
				$where .= "Document.date_created >= '".$data['date_create_start']."' ";
				$conditions++;
				$myddlewareSession['flux_filter']['c']['date_create_start'] = $data['date_create_start'];							
			}
			else {
				unset($myddlewareSession['flux_filter']['c']['date_create_start']);
			}				
			
			if(!empty( $data['date_create_end'] ) && is_string($data['date_create_end'])) {
				$where .= (($conditions > 0) ? "AND " : "" );
				$where .= "Document.date_created <= '".$data['date_create_end']."' ";
				$conditions++;	
				$myddlewareSession['flux_filter']['c']['date_create_end'] = $data['date_create_end'];							
			}	
			else {
				unset($myddlewareSession['flux_filter']['c']['date_create_end']);
			}							

			if(!empty( $data['date_modif_start'] ) && is_string($data['date_modif_start'])) {
				$where .= (($conditions > 0) ? "AND " : "" );
				$where .= "Document.date_modified >= '".$data['date_modif_start']."' ";
				$conditions++;	
				$myddlewareSession['flux_filter']['c']['date_modif_start'] = $data['date_modif_start'];							
			}
			else {
				unset($myddlewareSession['flux_filter']['c']['date_modif_start']);
			}
							
			if(!empty( $data['date_modif_end'] ) && is_string($data['date_modif_end'])) {
				$where .= (($conditions > 0) ? "AND " : "" );
				$where .= "Document.date_modified <= '".$data['date_modif_end']."' ";
				$conditions++;	
				$myddlewareSession['flux_filter']['c']['date_modif_end'] = $data['date_modif_end'];					
			}
			else {
				unset($myddlewareSession['flux_filter']['c']['date_modif_end']);
			}
			
			if(!empty( $data['rule'] ) && is_string($data['rule'])) {
				$where .= (($conditions > 0) ? "AND " : "" );
				$where .= "Rule.name='".trim($data['rule'])."' ";
				$conditions++;
				$myddlewareSession['flux_filter']['c']['rule'] = $data['rule'];
			}				
			else {
				unset($myddlewareSession['flux_filter']['c']['rule']);
			}
										
			if(!empty( $data['status'] )) {
				$where .= (($conditions > 0) ? "AND " : "" );
				$where .= "Document.status='".$data['status']."' ";
				$conditions++;
				$myddlewareSession['flux_filter']['c']['status'] = $data['status'];
			}
			else {
				unset($myddlewareSession['flux_filter']['c']['status']);
			}	

			if(!empty( $data['gblstatus'] )) {
				$where .= (($conditions > 0) ? "AND " : "" );
				$where .= "Document.global_status='".$data['gblstatus']."' ";
				$conditions++;
				$myddlewareSession['flux_filter']['c']['gblstatus'] = $data['gblstatus'];
			}
			else {
				unset($myddlewareSession['flux_filter']['c']['gblstatus']);
			}
			
			if(!empty( $data['target_id'] )) {
				$where .= (($conditions > 0) ? "AND " : "" );
				$where .= "Document.target_id LIKE '".$data['target_id']."' ";
				$conditions++;
				$myddlewareSession['flux_filter']['c']['target_id'] = $data['target_id'];
			}
			else {
				unset($myddlewareSession['flux_filter']['c']['target_id']);
			}
			
			if(!empty( $data['source_id'] )) {
				$where .= (($conditions > 0) ? "AND " : "" );
				$where .= "Document.source_id LIKE '".$data['source_id']."' ";
				$conditions++;
				$myddlewareSession['flux_filter']['c']['source_id'] = $data['source_id'];
			}
			else {
				unset($myddlewareSession['flux_filter']['c']['source_id']);
			}
			
			// si aucun condition alors on vide le where
			if( $conditions == 0 ) {
				$where = '';
			}		
		} // end clicked
		//---[ FORM ]-------------------------					 

		// si première page on stock les conditions
		if($page == 1) {
			if(!empty($where) || isset($myddlewareSession['flux_filter']['where'])) {
				$myddlewareSession['flux_filter']['where'] = $where;
			}
		}
		
		// si pagination on récupère les conditions
		if((int)$page > 1 && isset($myddlewareSession['flux_filter']['where'])) {
			$where = $myddlewareSession['flux_filter']['where'];
		}
		
		$cond = ((!empty($where)) ? 'AND' : 'WHERE' );


		// Detecte si la session est le support ---------
		$permission =  $this->get('myddleware.permission');
		
		if( $permission->isAdmin($this->getUser()->getId()) ) {
			$user = '';
		}
		else {
			$user = $cond.' created_by = '.$this->getUser()->getId();
		}
		// Detecte si la session est le support ---------

		
		$conn = $this->get( 'database_connection' );
		
		// Le nombre de flux affichés est limité
		$sql = "SELECT Document.*, users.username, Rule.name rule_name
				FROM Document  
				JOIN users ON(users.id = Document.created_by)
				JOIN Rule ON(Rule.id = Document.rule_id)
				$where 
				$user
				ORDER BY date_modified DESC 
				LIMIT $this->llimitListFlux";					
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$r = $stmt->fetchall();
		
		$compact = $this->nav_pagination(array(
			'adapter_em_repository' => $r,
			'maxPerPage' => $this->container->getParameter('pager'),
			'page' => $page
		),false);

		// Si tout se passe bien dans la pagination
		if( $compact ) {
			
			// Si aucune règle
			if( $compact['nb'] < 1 && !intval($compact['nb'])) {
				$compact['entities'] = '';
				$compact['pager'] = '';				
			}
			
			// Si on atteind la limit alors on récupère le nombre total de flux
			if ($compact['nb'] >= $this->llimitListFlux) {
				$sql = "SELECT count(*) nb
						FROM Document 
						JOIN users ON(users.id = Document.created_by)
						JOIN Rule ON(Rule.id = Document.rule_id)
						$where 
						$user";
				$stmt = $conn->prepare($sql);
				$stmt->execute();		
				$count = $stmt->fetch();
				$compact['nb'] = $count['nb'];
			}
			
			// affiche le bouton pour supprimer les filtres si les conditions proviennent du tableau de bord
			if(isset($myddlewareSession['flux_filter']['c'])) {
				$conditions = 1;
			}
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
		 	return $this->render('RegleBundle:Flux:list.html.twig',array(
			       'nb' => $compact['nb'],
			       'entities' => $compact['entities'],
			       'pager' => $compact['pager'],
			       'form' => $form->createView(),
			       'condition' => $conditions
				)
			);					
		}
		else {
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
			throw $this->createNotFoundException('Error');
		}
 	}	 

	// Supprime le filtre des flux
	public function fluxListDeleteFilterAction() {
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		if(isset($myddlewareSession['flux_filter'])) {
			unset($myddlewareSession['flux_filter']);	
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
		}

		return $this->redirect($this->generateUrl('flux_list'));	
	}

	// Info d'un flux
	public function fluxInfoAction($id,$page) {
		
		try {
			$em = $this->getDoctrine()->getManager();

			// Detecte si la session est le support ---------
			$permission =  $this->get('myddleware.permission');
			$list_fields_sql = array('id' => $id);	
				
			// Infos des flux
			$doc = $em->getRepository('RegleBundle:Document')
	                  ->findById($list_fields_sql);						  
			if( !$permission->isAdmin($this->getUser()->getId()) ) {		  
				if(
						empty($doc[0])
					 || $doc[0]->getCreatedBy() != $this->getUser()->getId()
				) {
					return $this->redirect($this->generateUrl('flux_list'));	
				}
			}											
			// Detecte si la session est le support ---------

			// Get rule object
			$rule = $em->getRepository('RegleBundle:Rule')->findOneById($doc[0]->getRule());						   
				
			// Chargement des tables source, target, history
			$source = $this->listeFluxTable($id,'S');	
			$target = $this->listeFluxTable($id,'T');						
			$history = $this->listeFluxTable($id,'H');

			// Get rulefield object
			$ruleFields	= $em->getRepository('RegleBundle:RuleField')->findByRule($doc[0]->getRule());
			// Get each data for each rule fields
			$targetData = array();
			$historyData = array();
			foreach($ruleFields as $ruleField) {
				// There is no field in source when we use my_value, just a formula
				if ($ruleField->getSource()!='my_value') {		
					// We keep only the fields in the rule 
					// It could be several fields in the source fields (in case of formula)
					$sourceFields = explode(";",$ruleField->getSource());				
					foreach ($sourceFields as $sourceField) {
						// Fields can be absent in case the rule have changed since the data tranfer has been sent
						if (isset($source[$sourceField])) {
							$sourceData[$sourceField] = $source[$sourceField];
						} else {
							$sourceData[$sourceField] = '';
						}
					}
				}
				// Target and history
				$targetField = $ruleField->getTarget();

				if (isset($target[$targetField])) {			
					$targetData[$targetField] = $target[$targetField];
					if (
							!empty($history[$targetField])
						 &&	!empty($history)
					) {
						$historyData[$targetField] = $history[$targetField];
					}
				} else {
					$targetData[$targetField] = '';
				}
			}					
			// Get RuleRelationShip object
			$RuleRelationShips = $em->getRepository('RegleBundle:RuleRelationShip')->findByRule($doc[0]->getRule());
			// Get each data for each rule relationship
			foreach($RuleRelationShips as $RuleRelationShip) {		
				$sourceField = $RuleRelationShip->getFieldNameSource();
				if (isset($source[$sourceField])) {
					$sourceData[$sourceField] = $source[$sourceField];
				}
				// Target and history
				$targetField = $RuleRelationShip->getFieldNameTarget();
				if (isset($target[$targetField])) {
					$targetData[$targetField] = $target[$targetField];
				}
				// Only if history exists and if the field exist in history
				if (!empty($history[$targetField])) {
					$historyData[$targetField] = $history[$targetField];
				}
			}	

			$compact = $this->nav_pagination(array(
				'adapter_em_repository' => $em->getRepository('RegleBundle:Log')
	                   						  ->findBy(
														array('document'=> $id),
														array('id' 		=> 'DESC')
												),				
				'maxPerPage' => $this->container->getParameter('pager'),
				'page' => $page
			),false);		
			$childDocuments = $em->getRepository('RegleBundle:Document')->findBy(array('parentId'=> $id));
			// Get the rule name of every child doc
			$childDocumentsRule = array();
			foreach ($childDocuments as $childDocument) {
				$childDocumentsRule[$childDocument->getId()] = $em->getRepository('RegleBundle:Rule')->findOneById( $childDocument->getRule())->getName();
			}
	
			$name_solution_target = $rule->getConnectorTarget()->getSolution()->getName();
				$solution_target = $this->get('myddleware_rule.'.$name_solution_target);
				$solution_target = $solution_target->getDocumentButton( $doc[0]->getId() );	
				$solution_target = (($solution_target == NULL) ? array() : $solution_target );
					
			$name_solution_source = $rule->getConnectorSource()->getSolution()->getName();
				$solution_source = $this->get('myddleware_rule.'.$name_solution_source);
				$solution_source = $solution_source->getDocumentButton( $doc[0]->getId() );			
				$solution_source = (($solution_source == NULL) ? array() : $solution_source );
		
			$list_btn = array_merge( $solution_target, $solution_source );													
	        return $this->render('RegleBundle:Flux:view/view.html.twig',array(
				'source' => $sourceData,
				'target' => $targetData,
				'history' => $historyData,
				'doc' => $doc[0],
		        'nb' => $compact['nb'],
		        'entities' => $compact['entities'],
		        'pager' => $compact['pager'],
		        'rule' => $rule,
		        'child_documents' => $childDocuments,
		        'child_Documents_Rule' => $childDocumentsRule,
		        'nb_child_documents' => count($childDocuments),
		        'ctm_btn' => $list_btn			
				)
			);			
		}
		catch(Exception $e) {
			return $this->redirect($this->generateUrl('flux_list'));	
			exit;
		}

	}

	// Sauvegarde flux
	public function fluxSaveAction() {
		$request = $this->get('request');
		if($request->getMethod()=='POST') {
			// Get the field and value from the request
			$fields = strip_tags($this->getRequest()->request->get('fields'));
			$value = strip_tags($this->getRequest()->request->get('value'));

			if(isset($value)) {
				// get the EntityManager
				$em = $this->getDoctrine()->getManager();	
				// Get target data for the document
				$documentDataEntity = $em->getRepository('RegleBundle:DocumentData')
											->findOneBy( array(
												'doc_id' => $this->getRequest()->request->get('flux'),
												'type' => 'T'
												)
										);
				if(!empty($documentDataEntity)) {							
					$target = json_decode($documentDataEntity->getData(),true);	
					$beforeValue = $target[$fields]; 
					// Change the value 
					$target[$fields] = $value;
					// Save the modification
					$documentDataEntity->setData(json_encode($target)); // Encode in JSON		  

					// Insert in audit			  
					$oneDocAudit = new DocumentAudit();
					$oneDocAudit->setDoc( $this->getRequest()->request->get('flux') );
					$oneDocAudit->setDateModified( new \DateTime );
					$oneDocAudit->setBefore( $beforeValue );
					$oneDocAudit->setAfter( $value );
					$oneDocAudit->setByUser( $this->getUser()->getId() );
					$oneDocAudit->setName( $fields );				
					$em->persist($oneDocAudit);
					$em->flush();
					echo $value;	
					exit;
				}
			} 
		}		
		throw $this->createNotFoundException('Failed to modify the field '.$fields);	
	}

	// Relancer un flux
	public function fluxRerunAction($id) {
		try {
			if(!empty($id)) {
				$job = $this->get('myddleware_job.job');	
				$job->actionMassTransfer('rerun',array($id));			
			}
			return $this->redirect( $this->generateURL('flux_info', array( 'id'=>$id )) );
		}
		catch(Exception $e) {
			return $this->redirect($this->generateUrl('flux_list'));
		}		
	}

	// Annuler un flux
	public function fluxCancelAction($id) {	
		try {
			if(!empty($id)) {
				$job = $this->get('myddleware_job.job');	
				$job->actionMassTransfer('cancel',array($id));			
			}
			return $this->redirect( $this->generateURL('flux_info', array( 'id'=>$id )) );
		}
		catch(Exception $e) {
			return $this->redirect($this->generateUrl('flux_list'));
		}													
	}
	

	// Exécute une action d'un bouton dynamique
	public function fluxBtnDynAction($method,$id,$solution) {
		$solution_ws = $this->get('myddleware_rule.'.mb_strtolower($solution) );
		$solution_ws->documentAction( $id, $method );
	
		return $this->redirect($this->generateUrl('flux_info', array('id'=>$id)));				
	}


	public function fluxMassCancelAction() {							
		if(isset($_POST['ids']) && count($_POST['ids']) > 0) {
			$job = $this->get('myddleware_job.job');	
			$job->actionMassTransfer('cancel',$_POST['ids']);			
		}													
		exit; 
	}

	public function fluxMassRunAction() {

		if(isset($_POST['ids']) && count($_POST['ids']) > 0) {
			$job = $this->get('myddleware_job.job');	
			$job->actionMassTransfer('rerun',$_POST['ids']);			
		}
		
		exit;
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

	// Liste tous les flux d'un type
	private function listeFluxTable($id,$type) {
		try {		
			// Get document data
			$documentDataEntity = $this->getDoctrine()->getManager()->getRepository('RegleBundle:DocumentData')
										->findOneBy( array(
											'doc_id' => $id,
											'type' => $type
											)
										);
			if(!empty($documentDataEntity)) {							
				return json_decode($documentDataEntity->getData(),true);	
			}
			return null;			
		}
		catch(Exception $e) {
			return false;
		}
	}
	
}


/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Controller/FluxController.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class FluxController extends FluxControllerCore {
		
	}
}


