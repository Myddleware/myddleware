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
use Myddleware\RegleBundle\Entity\ConnectorParam;
use Myddleware\RegleBundle\Entity\Rule;
use Myddleware\RegleBundle\Entity\RuleParam;
use Myddleware\RegleBundle\Entity\RuleFilter;
use Myddleware\RegleBundle\Entity\RuleField;
use Myddleware\RegleBundle\Entity\RuleRelationShip;
use Myddleware\RegleBundle\Entity\RuleAudit;
use Myddleware\RegleBundle\Entity\Functions;
use Myddleware\RegleBundle\Entity\FunctionsRelationShips;
use Myddleware\RegleBundle\Entity\FuncCat;
use Myddleware\RegleBundle\Entity\FuncCatRelationShips;

use Myddleware\RegleBundle\Classes\rule as RuleClass;
use Myddleware\RegleBundle\Classes\document;
use Myddleware\RegleBundle\Classes\tools;

class DefaultControllerCore extends Controller
{
	// Connexion bdd doctrine
	protected $em;
	// Connexion direct bdd (utilisé pour créer les tables Z sans doctrine
	protected $connection;
	
	protected function getInstanceBdd() {
		if (empty($this->em)) {
			$this->em = $this->getDoctrine()->getManager();	
		}
		if (empty($this->connection)) {
			$this->connection = $this->get('database_connection');	
		}
	}
	// debug ladybug_dump($compact);
	 
	/* ******************************************************
	 * RULE
	 ****************************************************** */
 	
 	// LISTE DES REGLES
	public function ruleListAction($page) {
		try {
			$request = $this->get('request');
			$session = $request->getSession();
			$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
			// We always add data again in session because these data are removed after the call of the get
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	

			if(isset($myddlewareSession['rule']['newid'])) {
				$id = $myddlewareSession['rule']['newid'];
				unset($myddlewareSession['rule']['newid']);
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
				return $this->redirect($this->generateUrl('regle_open', array('id'=>$id)));	
			}
		
			$this->getInstanceBdd();	
			$compact['nb'] = 0;	
			
			// Detecte si la session est le support ---------
			$permission =  $this->get('myddleware.permission');
			// Detecte si la session est le support ---------		
			
			$compact = $this->nav_pagination(array(
				'adapter_em_repository' => $this->em->getRepository('RegleBundle:Rule')->findListRuleByUser( $permission->isAdmin($this->getUser()->getId()),$this->getUser()->getId() ),
				'maxPerPage' => $this->container->getParameter('pager'),
				'page' => $page
			));	
		
			// Si tout se passe bien dans la pagination
			if( $compact ) {
				
				// Si aucune règle
				if( $compact['nb'] < 1 && !intval($compact['nb'])) {
					$compact['entities'] = "";
					$compact['pager'] = "";				
				}
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
				return $this->render('RegleBundle:Rule:list.html.twig',array(
					   'nb_rule' => $compact['nb'],
					   'entities' => $compact['entities'],
					   'pager' => $compact['pager']
					)
				);					
			}
			else {
				throw $this->createNotFoundException('Error');
			}

		// ---------------
		}catch(Exception $e) {		
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
			throw $this->createNotFoundException('Error : '.$e);
		}
	
	
	}

	// SUPPRESSION D UNE REGLE
	public function ruleDeleteAction($id) {
		$request = $this->get('request');
		$session = $request->getSession();
		
		// First, checking that the rule has document sent (close)
		$docClose = $this->getDoctrine()
					 ->getManager()
					 ->getRepository('RegleBundle:Document')
					 ->findOneBy( array(
								'rule' => $id,
								'globalStatus' => array('Close')
							)
					);
		// Return to the view detail for the rule if we found a document close
		if (!empty($docClose)) {
			$session->set( 'error', array($this->get('translator')->trans('error.rule.delete_document_close')));
			return $this->redirect($this->generateUrl('regle_open', array('id'=>$id)));	
		}
		
		// First, checking that the rule has no document open or in error
		$docErrorOpen = $this->getDoctrine()
					 ->getManager()
					 ->getRepository('RegleBundle:Document')
					 ->findOneBy( array(
								'rule' => $id,
								'globalStatus' => array('Open', 'Error')
							)
					);
		// Return to the view detail of the rule if we found a document open or in error
		if (!empty($docErrorOpen)) {
			$session->set( 'error', array($this->get('translator')->trans('error.rule.delete_document_error_open')));
			return $this->redirect($this->generateUrl('regle_open', array('id'=>$id)));	
		}	
		
		// Checking if the rule is linked to an other one
		$ruleRelationshipError = $this->getDoctrine()
					 ->getManager()
					 ->getRepository('RegleBundle:RuleRelationShip')
					 ->findOneBy( array('fieldId' => $id)
					);				
		// Return to the view detail of the rule if a rule relate to this one
		if (!empty($ruleRelationshipError)) {
			$session->set( 'error', array($this->get('translator')->trans('error.rule.delete_relationship_exists').$ruleRelationshipError->getRule()));
			return $this->redirect($this->generateUrl('regle_open', array('id'=>$id)));	
		}	
		
		
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
		
		if(isset($id)) {
			// Récupère la règle en fonction de son id
			$rule = $this->getDoctrine()
                         ->getManager()
                         ->getRepository('RegleBundle:Rule')
                         ->findBy( $list_fields_sql);
			
			$rule = $rule[0];
			
			// si je supprime une règle qui ne m'appartient pas alors redirection
			if(count($rule) < 1) {
				return $this->redirect($this->generateUrl('regle_list'));
			}
							
			$doc = $this->getDoctrine()
                         ->getManager()
                         ->getRepository('RegleBundle:Document')
                         ->findOneBy( array(
							    	'rule' => $id
							    )
						);				
				
		    // On récupére l'EntityManager
		    $this->getInstanceBdd();	
	  			
			// si aucun document (flux) alors on supprime sinon on flag
			if(is_null($doc)) {					
					// Rule fields					
					$rule_fields = $this->getDoctrine()
	                         ->getManager()
	                         ->getRepository('RegleBundle:RuleField')
	                         ->findByRule( $id );	
					if($rule_fields) {
						foreach ( $rule_fields as $rule_field ) {
							$this->em->remove($rule_field);
							$this->em->flush();
						}
					}
					
					// Rule relationships					
					$rule_relationships = $this->getDoctrine()
	                         ->getManager()
	                         ->getRepository('RegleBundle:RuleRelationShip')
	                         ->findByRule( $id );	
					if($rule_relationships) {
						foreach ( $rule_relationships as $rule_relationship ) {
							$this->em->remove($rule_relationship);
							$this->em->flush();
						}
					}
					
					// Rule params
					$rule_params = $this->getDoctrine()
	                         ->getManager()
	                         ->getRepository('RegleBundle:RuleParam')
	                         ->findByRule( $id );		 						 								 						 										
					if($rule_params) {
						foreach ( $rule_params as $rule_param ) {
							$this->em->remove($rule_param);
							$this->em->flush();	
						}			
					}	
					
					// Rule filters
					$rule_filters = $this->getDoctrine()
	                         ->getManager()
	                         ->getRepository('RegleBundle:RuleFilter')
	                         ->findByRule( $id );
					
					if($rule_filters) {
						foreach ( $rule_filters as $rule_filter ) {
							$this->em->remove($rule_filter);
							$this->em->flush();
						}					
					}
				
					$stmt = $this->connection->prepare('DELETE FROM RuleRelationShip WHERE id =:id'); 	  
					$stmt->bindValue('id', $rule->getId() ); 
					$stmt->execute(); 
					
					// - - - 	
									
					$this->em->remove($rule);
					$this->em->flush();	
			}
			else { // flag
			
				$rule->setDeleted( 1 );
				$rule->setActive( 0 );
				$this->em->persist($rule);
				$this->em->flush(); 
			}
			
			return $this->redirect($this->generateUrl('regle_list'));	 
		}
	}

	// AFFICHE LES FLUX D'UNE REGLE
	public function displayFluxAction($id){
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		$rule = $this->getDoctrine()
                     ->getManager()
                     ->getRepository('RegleBundle:Rule')
                     ->findOneBy( array(
						    	'id' => $id
						    )
					);
					
		$myddlewareSession['flux_filter']['where'] = "WHERE Document.rule_id = '" . $rule->getId() . "'";
		$myddlewareSession['flux_filter']['c']['rule'] = $rule->getName();
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
		return $this->redirect($this->generateUrl('flux_list'));
	}
	
	// ACTIVE UNE REGLE
	public function ruleUpdActiveAction($id) {
		
		try {
			
		    // On récupére l'EntityManager
		    $this->getInstanceBdd();				
			
			
			$rule = $this->getDoctrine()
	                     ->getManager()
	                     ->getRepository('RegleBundle:Rule')
	                     ->findOneById( $id );	
			
			if($rule->getActive()) {
				$r = 0;
				$rule->setActive( $r );	
			}
			else {
				$r = 1;
				$rule->setActive( $r );
			}	

			$this->em->persist($rule);
			$this->em->flush(); 
			
			return new Response($r);		
		}
		catch(Exception $e) {
			echo $e->getMessage();
		}			 		
	}

	// Executer une règle manuellement
	public function ruleExecAction($id) {
		try {
			if($id == "ALL") {
				$rule = $this->container->get('myddleware_rule.rule');
				$result = $rule->actionRule("ALL");
				return $this->redirect($this->generateUrl('regle_list'));
			} else if ($id == "ERROR") {
				$rule = $this->container->get('myddleware_rule.rule');
				$result = $rule->actionRule("ERROR");
				return $this->redirect($this->generateUrl('regle_list'));
			} else {
				$rule = $this->container->get('myddleware_rule.rule');
				$rule->setRule($id);
				$result = $rule->actionRule('runMyddlewareJob');
	
				return $this->redirect( $this->generateURL('regle_open', array( 'id'=>$id )) );
				exit;
			}
		}
		catch(Exception $e) {
			return $this->redirect($this->generateUrl('regle_list'));
		}
	}

	// MODIFIE LES PARAMETRES D UNE REGLE
	public function ruleUpdParamsAction($id) {
		
		try {
		    // On récupére l'EntityManager
		    $this->getInstanceBdd();		
					   
			if(isset($_POST['params']) && is_array($_POST['params'])) {
				foreach($_POST['params']  as $p) {
					$param = $this->em->getRepository('RegleBundle:RuleParam')
		                   ->findOneBy( array(
								    	'rule' => $id, 
								    	'id' => (int)$p['id']
								    )
							);	

					$param->setValue( $p['value'] );	
				    $this->em->persist($param);
				    $this->em->flush();						
				}			
			}		   

			return new Response(1);
		}
		catch(Exception $e) {
			echo $e->getMessage();
		}			 		
	}

	// SIMULE LA LECTURE POUR RETOURNER LE NOMBRE DE TRANSFERS POTENTIELS
	public function ruleSimulateTransfersAction($id) {
		try {
		    // On récupére l'EntityManager
		    $this->getInstanceBdd();	
					   
			// Récupération de date_ref
			$param['date_ref'] = $this->em->getRepository('RegleBundle:RuleParam')
								->findOneBy( array(
								    	'rule' => $id,
								    	'name' => 'datereference'
								    ))
								->getValue();
								
			// Get the other rule params					
			$connectorParams  = $this->getDoctrine()
							 ->getManager()
							 ->getRepository('RegleBundle:RuleParam')
							 ->findByRule( $id );	
			foreach ($connectorParams as $connectorParam) {
				$param['ruleParams'][ $connectorParam->getName() ] = $connectorParam->getValue();
			}
			
			// Infos Rule
			$rule = $this->getDoctrine()
	                     ->getManager()
	                     ->getRepository('RegleBundle:Rule')
	                     ->findOneById( $id );

			// Id Connecteur
			$connectorSourceId = (string)$rule->getConnectorSource()->getId();

			// Champs			   
			$fields	= $this->em->getRepository('RegleBundle:RuleField')	   				
						 ->findByRule( $id );
			
			$param['fields'] = array();	 
			// Extraction des champs sources
			
			foreach ($fields as $obj) {
				// It could be several fields in a source when there is a formula
				$sources = explode(';',$obj->getSource());
				foreach ($sources as $source) {
					$param['fields'][] = $source;
				}
			}
			
			// Module source
			$param['module'] = (string)$rule->getModuleSource();
			
			// Solution source
			$solution_source_nom = $rule->getConnectorSource()->getSolution()->getName();
		 
			// Connector source -------------------
  			$connectorParamsSource = $this->getDoctrine()
                          ->getManager()
                          ->getRepository('RegleBundle:ConnectorParam')
                          ->findByConnector( $rule->getConnectorSource() );		
			
			$connectorSource['solution'] = $rule->getConnectorSource()->getSolution()->getName();
			
			foreach ($connectorParamsSource as $connector) {
				$connectorSource[ $connector->getName() ] = $connector->getValue();
			}
			
			$solution_source = $this->get('myddleware_rule.'.$solution_source_nom);			

			$solution_source->login($connectorSource);

			// Rule Mode
			$param['rule']['mode'] = $this->em->getRepository('RegleBundle:RuleParam')
								->findOneBy( array(
								    	'rule' => $id,
								    	'name' => 'mode'
								    ))
								->getValue();				
			
			if(empty($param['rule']['mode'])) {
				$param['rule']['mode'] = "0";
			}
			$param['offset'] = '0';
			$result = $solution_source->read($param);
			
			if(!empty($result['error'])) {
				throw new \Exception("Reading Issue: ".$result['error']);
			}
			if(isset($result['count'])) {
				return new Response($result['count']);
			}
			return new Response(0);
		}
		catch(\Exception $e) {
			return new Response( json_encode(array('error' => $e->getMessage())) );
		}	
	}

	// MODE EDITION D UNE REGLE
	public function ruleEditAction($id) {
		try {
			$request = $this->get('request');
			$session = $request->getSession();
			// First, checking that the rule has no document open or in error
			$docErrorOpen = $this->getDoctrine()
                         ->getManager()
                         ->getRepository('RegleBundle:Document')
                         ->findOneBy( array(
							    	'rule' => $id,
									'globalStatus' => array('Open', 'Error')
							    )
						);
			// Return to the view detail fo the rule if we found a document open or in error
			if (!empty($docErrorOpen)) {
				$session->set( 'error', array($this->get('translator')->trans('error.rule.edit_document_error_open')));
				return $this->redirect($this->generateUrl('regle_open', array('id'=>$id)));	
			}

			$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
			// We always add data again in session because these data are removed after the call of the get
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
			if(isset($id)) {
			//--
				// si une session existe alors on la supprime
				if(isset($myddlewareSession['param']['rule'])) {
					unset( $myddlewareSession['param']['rule'] );
				}

				// préparation des sessions
	  			$rule = $this->getDoctrine()
	                          ->getManager()
	                          ->getRepository('RegleBundle:Rule')
	                          ->findOneById( $id );	
				if (!empty($rule->getDeleted())) {
					$session->set( 'error', array($this->get('translator')->trans('error.rule.edit_rule_deleted')));
					return $this->redirect($this->generateUrl('regle_open', array('id'=>$id)));	
				}
				
				// composition des sessions
				$myddlewareSession['param']['rule']['rulename_valide'] = true;										
				$myddlewareSession['param']['rule']['rulename'] = $rule->getName();
				$myddlewareSession['param']['rule']['connector']['source'] = (string)$rule->getConnectorSource()->getId();				
				$myddlewareSession['param']['rule']['connector']['cible'] = (string)$rule->getConnectorTarget()->getId();
				$myddlewareSession['param']['rule']['last_version_id'] = $rule->getId();

				// Connector source -------------------
	  			$connectorParamsSource = $this->getDoctrine()
	                          ->getManager()
	                          ->getRepository('RegleBundle:ConnectorParam')
	                          ->findByConnector( $rule->getConnectorSource() );		
							  
				$myddlewareSession['param']['rule']['source']['solution'] = $rule->getConnectorSource()->getSolution()->getName();
				
				foreach ($connectorParamsSource as $connector) {
					$myddlewareSession['param']['rule']['source'][ $connector->getName() ] = $connector->getValue();
				}
				// Connector source -------------------
				
				// Connector target -------------------						  
	  			$connectorParamsTarget = $this->getDoctrine()
	                          ->getManager()
	                          ->getRepository('RegleBundle:ConnectorParam')
	                          ->findByConnector( $rule->getConnectorTarget() );		
							  
				$myddlewareSession['param']['rule']['cible']['solution'] = $rule->getConnectorTarget()->getSolution()->getName();
							  
				foreach ($connectorParamsTarget as $connector) {
					$myddlewareSession['param']['rule']['cible'][ $connector->getName() ] = $connector->getValue();
				}							  
				// Connector target -------------------
				
				$ruleParams = $this->getDoctrine()
	                          ->getManager()
	                          ->getRepository('RegleBundle:RuleParam')
	                          ->findByRule( $rule->getId() );	
				// Paramètre d'une règle
				if($ruleParams) {
					foreach ($ruleParams as $ruleParamsObj) {
						$params[] = array(
							'name' => $ruleParamsObj->getName(),
							'value' => $ruleParamsObj->getValue()
						);							
					}										
					$myddlewareSession['param']['rule']['reload']['params'] = json_encode($params);					
				}	
				
				// Modules --
				$myddlewareSession['param']['rule']['source']['module'] = $rule->getModuleSource();
				$myddlewareSession['param']['rule']['cible']['module'] = $rule->getModuletarget();
				// Modules --

				// reload ---------------
	  			$ruleFields = $this->getDoctrine()
	                          ->getManager()
	                          ->getRepository('RegleBundle:RuleField')
	                          ->findByRule( $rule->getId() );
				// get_modules_fields en source pour avoir l'association fieldid / libellé (ticket 548)
				$solution_source_nom = $myddlewareSession['param']['rule']['source']['solution'];			
				$solution_source = $this->get('myddleware_rule.'.$solution_source_nom);			
				$solution_source->login($this->decrypt_params($myddlewareSession['param']['rule']['source']));
				
				// SOURCE ----- Récupère la liste des champs source
				// O récupère le module de la règle 
				$sourceModule = $rule->getModuleSource();
				$sourceFieldsInfo = $solution_source->get_module_fields($sourceModule);
						
				// Champs et formules d'une règle
				if($ruleFields) {
					foreach ($ruleFields as $ruleFieldsObj) {
						$array = array(
							'target' => $ruleFieldsObj->getTarget(),
							'source' => array(),
							'formula' => $ruleFieldsObj->getFormula()
						);
						$fields_source = explode(';', $ruleFieldsObj->getSource());

						if (!empty($fields_source)) {
							foreach ($fields_source as $field_source) {
								if($field_source == "my_value") {
									$array['source'][$field_source] = "my_value";
								} elseif(isset($sourceFieldsInfo[$field_source])) {
									$array['source'][$field_source] = $sourceFieldsInfo[$field_source]['label'];
								} else {
									if (!empty($sourceFieldsInfo)) {
										foreach ($sourceFieldsInfo as $multiModule) {
											if(isset($multiModule[$field_source])) {
												$array['source'][$field_source] = $multiModule[$field_source]['label'];
											}
										}
									}
								}
								if(!isset($array['source'][$field_source])) {								
									throw new \Exception ("Getting label (reload): Field ".$field_source." not found.");
								}	
							}
							$fields[] = $array;
						}
					}
					$myddlewareSession['param']['rule']['reload']['fields'] = json_encode($fields);
				}			

	  			$ruleRelationShips = $this->getDoctrine()
	                          ->getManager()
	                          ->getRepository('RegleBundle:RuleRelationShip')
	                          ->findByRule( $rule->getId() );
				// Relations d'une règle
				if($ruleRelationShips) {
					foreach ($ruleRelationShips as $ruleRelationShipsObj) {
						$relate[] = array(
							'source' => $ruleRelationShipsObj->getFieldNameSource(),
							'target' => $ruleRelationShipsObj->getFieldNameTarget(),
							'id' => $ruleRelationShipsObj->getFieldId(),
							'parent' => $ruleRelationShipsObj->getParent()
						);
					}						
					$myddlewareSession['param']['rule']['reload']['relate'] = json_encode($relate);
				}	

				// Filter
	  			$ruleFilters = $this->getDoctrine()
	                          ->getManager()
	                          ->getRepository('RegleBundle:RuleFilter')
	                          ->findByRule( $rule->getId() );

				if($ruleFilters) {
					foreach ($ruleFilters as $ruleFiltersObj) {
						$filter[] = array(
							'target' => $ruleFiltersObj->getTarget(),
							'type' => $ruleFiltersObj->getType(),
							'value' => $ruleFiltersObj->getValue()
						);
					}	
				}
				
				$myddlewareSession['param']['rule']['reload']['filter'] = ((isset($filter)) ? json_encode($filter) : json_encode(array()) );	
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);			  
				// reload ---------------							
				return $this->redirect($this->generateUrl('regle_stepthree'));	
				exit;
			//--	
			}		
		}
		catch(Exception $e) {
			$myddlewareSession['error']['create_rule'] = $this->get('translator')->trans('error.rule.update');	
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);			
			return $this->redirect($this->generateUrl('regle_stepone_animation'));	
			exit;				
		}	
	}

	// FICHE D UNE REGLE
	public function ruleOpenAction($id) {
			
		$this->getInstanceBdd();	
		$tools = new tools($this->get('logger'), $this->container, $this->connection);	
		// Detecte si la session est le support ---------
		$permission =  $this->get('myddleware.permission');
		
		if( $permission->isAdmin($this->getUser()->getId()) ) {
			$list_fields_sql = array('id' => $id);
		}
		else {
			$list_fields_sql = 
				array('id' => $id,
				      'createdBy' => $this->getUser()->getId()
			);
		}
		// Detecte si la session est le support ---------
		
		// Infos de la regle
		$rule = $this->em->getRepository('RegleBundle:Rule')
                   ->findOneBy( $list_fields_sql );
				   		   	
		if(!$rule) {
			throw $this->createNotFoundException('La fiche n existe pas dans la base de donnees');
		}
						
		// Liste des relations			
		$rule_relationships = $this->em->getRepository('RegleBundle:RuleRelationShip')
                  				 ->findByRule( $rule->getId() );	

		$solution_cible_nom = $rule->getConnectorTarget()->getSolution()->getName();
		$solution_cible= $this->get('myddleware_rule.'.$solution_cible_nom);
		$moduleCible = (string)$rule->getModuleTarget();
		// Champs pour éviter les doublons
		$fieldsDuplicateTarget = $solution_cible->getFieldsDuplicate($moduleCible);
		// Les champs sélectionnés
		$duplicate_fields = $this->em->getRepository('RegleBundle:RuleParam')
		->findOneBy( array(
		    	'rule' => $id,
		    	'name' => 'duplicate_fields'
		    ));
		
		if (isset($duplicate_fields))
			$duplicate_fields = $duplicate_fields->getValue();
								 					 
		$tab_rs = array();	
		$i = 0;					 
		foreach ($rule_relationships as $r) {
			
			$tab_rs[$i]['getFieldId'] = $r->getFieldId(); 
			$tab_rs[$i]['getFieldNameSource'] = $r->getFieldNameSource();
			$tab_rs[$i]['getFieldNameTarget'] = $r->getFieldNameTarget();
			$tab_rs[$i]['getParent'] = $r->getParent();

			$ruleTmp = $this->em->getRepository('RegleBundle:Rule')
	                   ->findOneBy( array(
							    	'id' => $r->getFieldId()
							    )
						);
			
			$tab_rs[$i]['getName'] = $ruleTmp->getName();
			$i++;
		}
		
		// Infos connecteurs & solutions		
		$connector = $this->em->getRepository('RegleBundle:Rule')
						->infosConnectorByRule( $rule->getId() );
		
		// Changement de référence pour certaines solutions				
		$autorization_source = $connector[0]['solution_source'];		
		$autorization_module_trans = mb_strtolower( $rule->getModuleSource() );
				
		// Infos params				
		$Params = $this->em->getRepository('RegleBundle:RuleParam')
                     ->findByRule( $rule->getId() );	
					 			 
		// Infos champs			   
		$Fields	= $this->em->getRepository('RegleBundle:RuleField')	   				
					 ->findByRule( $rule->getId() );
					 					 	
		// Infos champs			   
		$Filters = $this->em->getRepository('RegleBundle:RuleFilter')	   				
					 ->findByRule( $rule->getId() );	
					 			 					 									
		$ruleParam = RuleClass::getFieldsParamView();	
		$params_suite = false;
		if($Params) {
			foreach ($Params as $field) {
				$standardField = false;
				foreach ($ruleParam as $index => $value) {
					if($field->getName() == $value['name']) {
						$ruleParam[$index]['id_bdd'] = $field->getId();
						$ruleParam[$index]['value_bdd'] = $field->getValue();	
						if($field->getName() == 'datereference') {
							// Test si la date est valide
							$date = $field->getValue();
							$format = 'Y-m-d H:i:s';
							$d = \DateTime::createFromFormat($format, $date);
   							$r = $d && $d->format($format) == $date;
							
							if(!$r) {
								$ruleParam[$index]['id'] = 'datereference_txt';
								$ruleParam[$index]['name'] = 'datereference_txt';
								$ruleParam[$index]['label'] = $autorization_source.'.'.$autorization_module_trans.'.ref';
							} 
						}
						$standardField = true;
						break;
					} 
				}		
				if (!$standardField) {
					if($field->getName() == 'mode') {
						// We send the translation of the mode to the view
						switch ($field->getValue()) {
							case '0':
								$params_suite['mode'] = $tools->getTranslation(array('create_rule', 'step3', 'syncdata', 'create_modify'));
								break;
							case 'C':
								$params_suite['mode'] = $tools->getTranslation(array('create_rule', 'step3', 'syncdata', 'create_only'));
								break;
							case 'S':
								$params_suite['mode'] = $tools->getTranslation(array('create_rule', 'step3', 'syncdata', 'search_only'));;
								break;
							default:
							   $params_suite['mode'] = $field->getValue();
						}			
					} elseif($field->getName() == 'bidirectional') {
						if (!empty($field->getValue())) {
							$ruleBidirectional = $this->em->getRepository('RegleBundle:Rule')
												   ->findOneBy( array(
																'id' => $field->getValue()
															)
													);
							// Send the name and the id of the opposite rule to the view
							$params_suite['bidirectional'] = $field->getValue();
							$params_suite['bidirectionalName'] = $ruleBidirectional->getName();
						}				
					}	
					else {
						$params_suite['customParams'][] = array('name' => $field->getName(), 'value' => $field->getValue());
					}
				}			
			}			
		}
        return $this->render('RegleBundle:Rule:edit/fiche.html.twig',array(
			'rule' => $rule,
			'connector' => $connector[0],
			'fields' => $Fields,
			'relate' => $tab_rs,
			'parentRelationships' => $solution_cible->allowParentRelationship($moduleCible),
			'params' => $ruleParam,
			'filters' => $Filters,
			'params_suite' => $params_suite
			)
		);
	}
	 		
	// CREATION - STEP ONE - CONNEXION : jQuery ajax 
	public function ruleInputsAction() {
		$request = $this->get('request');
		$session = $request->getSession();			
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	

		if($request->getMethod()=='POST') {

			// Retourne la liste des inputs pour la connexion
			if($this->getRequest()->request->get('mod') == 1) {
				
				if(is_string($this->getRequest()->request->get('solution')) && is_string($this->getRequest()->request->get('parent'))) {
					if(preg_match("#[\w]#", $this->getRequest()->request->get('solution')) && preg_match("#[\w]#", $this->getRequest()->request->get('parent')))
					{
						$classe = strtolower($this->getRequest()->request->get('solution'));
	
						$solution = $this->get('myddleware_rule.'.$classe);
						$parent = $this->getRequest()->request->get('parent');
						$liste_input = $solution->getFieldsLogin();
						
						$this->getInstanceBdd();	
						$solution = $this->em->getRepository('RegleBundle:Solution')
									   ->findByName($classe);						
						$solution = $solution[0];

						$contenu = '<p><label><span class="glyphicon glyphicon-sort"></span></label>';
						
						if($solution->getSource()) {
							$contenu .= '<span class="glyphicon glyphicon-download sync"></span> '.$this->get('translator')->trans('create_connector.source');	
						}

						if($solution->getTarget()) {
							$contenu .= '<span class="glyphicon glyphicon-upload sync"></span> '.$this->get('translator')->trans('create_connector.target');	
						}
						
						$contenu .= "</p>";
						
						if($liste_input) {
							foreach ($liste_input as $input) {
								// Rev 1.1.0 -----							
								if($input['name'] == 'wsdl') {
									$contenu .= '<p><a id="link_wsdl" class="fancybox_upload" href="'.$this->generateUrl('upload',array('solution'=>$classe)).'" data-fancybox-type="iframe">
												<label for="'.$input['name'].'">'.ucfirst( $this->get('translator')->trans($input['label']) ).' <span class="glyphicon glyphicon-upload"></span></label>
												<input id="param_'.$input['name'].'" type="'.$input['type'].'" name="'.$input['name'].'" readonly="readonly" placeholder="'.$this->get('translator')->trans('create_connector.upload_placeholder').'" /></a></p>';																
								}
								else {
									$contenu .= '<p><label for="'.$input['name'].'">'.ucfirst( $this->get('translator')->trans($input['label']) ).'</label>
												<input id="param_'.$input['name'].'" type="'.$input['type'].'" name="'.$input['name'].'" /></p>';
								}
								// Rev 1.1.0 -----								
							}					
						}
						
				        return $this->render('RegleBundle:Ajax:result_liste_inputs.html.twig',array(
							'contenu'=>$contenu,
							'parent'=>$parent)
						);		
					}
				}
			} // Vérifie si la connexion peut se faire ou non
			elseif($this->getRequest()->request->get('mod') == 2 || $this->getRequest()->request->get('mod') == 3) {
					
				// Connector	
				if($this->getRequest()->request->get('mod') == 2) {
					
					if(preg_match("#[\w]#", $this->getRequest()->request->get('champs')) && preg_match("#[\w]#", $this->getRequest()->request->get('parent')) && preg_match("#[\w]#", $this->getRequest()->request->get('solution'))) {
						
						$classe = strtolower($this->getRequest()->request->get('solution'));
						$solution = $this->get('myddleware_rule.'.$classe);
						
						// établi un tableau params
						$champs = explode(';',$this->getRequest()->request->get('champs'));
						
						if($champs) {
							foreach ($champs as $key) {
								$input = explode('::',$key);
								if(!empty($input[0])) {
									if(!empty($input[1]) || is_numeric($input[1])) {
										$param[$input[0]] = trim($input[1]);	
										$myddlewareSession['param']['connector'][$this->getRequest()->request->get('parent')][$input[0]] = trim($input[1]);	
									}								
								}
							}
						}					
						
						$myddlewareSession['param']['connector'][$this->getRequest()->request->get('parent')]['solution'] = $classe;
								
						// Vérification du nombre de champs
						if( isset($param) && (count($param) == count($solution->getFieldsLogin())) ) {
							// Save the session befor calling login function because session could be used in this function
							$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
							$solution->login($param);
							$r = $solution->connexion_valide;
							$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
							// We always add data again in session because these data are removed after the call of the get
							$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
							
							if(!empty($r)) {
								$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
								echo "1"; // Connexion valide
								exit;
							}
							else {
								unset($myddlewareSession['param']['rule']);
								echo "0"; // Erreur de connexion
								$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
								exit;					
							}
						}
						else {
							echo "0"; // Erreur pas le même nombre de champs
							$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
							exit;					
						}					
					}					
				} // Rule
				elseif($this->getRequest()->request->get('mod') == 3) {
					
					// 0 : solution
					// 1 : id connector
					$params = explode('_',$this->getRequest()->request->get('solution'));
					
					// Deux params obligatoires
					if(count($params) == 2 && intval($params[1]) && is_string($params[0])) {
						unset($myddlewareSession['param']['rule'][$this->getRequest()->request->get('parent')]);
						$classe = strtolower($params[0]);
						$solution = $this->get('myddleware_rule.'.$classe);
			    
			  			$connector = $this->getDoctrine()
				                          ->getManager()
				                          ->getRepository('RegleBundle:Connector')
				                          ->findOneById( $params[1] );
						
						$connector_params = $this->getDoctrine()
				                          ->getManager()
				                          ->getRepository('RegleBundle:ConnectorParam')
				                          ->findByConnector( $connector );
										  
						if($connector_params) {
							foreach ($connector_params as $key) {
								$myddlewareSession['param']['rule'][$this->getRequest()->request->get('parent')][$key->getName()] = $key->getValue();	
							}							
						}

						$myddlewareSession['param']['rule']['rulename'] = $this->getRequest()->request->get('name');
						
						// Affectation id connector
						$myddlewareSession['param']['rule']['connector'][$this->getRequest()->request->get('parent')] = $params[1];
						//$myddlewareSession['obj'][$this->getRequest()->request->get('parent')] = $connector_params;

						$solution->login($this->decrypt_params($myddlewareSession['param']['rule'][$this->getRequest()->request->get('parent')]));
						$myddlewareSession['param']['rule'][$this->getRequest()->request->get('parent')]['solution'] = $classe;	
						
						$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
						$r = $solution->connexion_valide;
						if(!empty($r)) {
							echo "1"; // Connexion valide
							exit;
						}
						else {
							echo "0"; // Erreur de connexion
							exit;					
						}

						exit;
					
				        return $this->render('RegleBundle:Ajax:result_connexion.html.twig',array()
						);							
					}
					else {
						$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
						echo "0";
						exit;
					}
				}	
			}			
		}
		else {
			throw $this->createNotFoundException('Error');
		}
	}
	
	// CREATION - STEP ONE - CONNEXION
	public function ruleStepOneAction() {
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		// s'il existe des vielles données on les supprime
		if(isset( $myddlewareSession['param']['rule'] )) {
			unset( $myddlewareSession['param']['rule'] );
		}
		
		// Détecte s'il existe des erreurs
		if(isset($myddlewareSession['error']['create_rule']) && !empty($myddlewareSession['error']['create_rule']) ) {
			$error = $myddlewareSession['error']['create_rule'];
			unset($myddlewareSession['error']['create_rule']);
		}
		else {
			$error = false;
		}
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);					
        return $this->render('RegleBundle:Rule:create/step1.html.twig',array(
			'solutions_source'=>$this->liste_connectorAction('source'),
			'solutions_target'=>$this->liste_connectorAction('target'),
			'error' => $error
			)
		);		
	}

	// CREATION - STEP ONE - VERIF ALIAS RULE
	public function ruleNameUniqAction() {
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		
		if($request->getMethod()=='POST') {
			
			$this->getInstanceBdd();	
					
			// Cherche si la règle existe en fonction de son nom
			$rule = $this->em->getRepository('RegleBundle:Rule')
					   ->findOneBy( array(
									'name' => $this->getRequest()->request->get('name')
								)
						);

			// 0 existe pas 1 existe
			if($rule == NULL) {
				echo 0;
				$myddlewareSession['param']['rule']['rulename_valide'] = true;
				$myddlewareSession['param']['rule']['rulename'] = $this->getRequest()->request->get('name');
			}
			else {
				echo 1;
				$myddlewareSession['param']['rule']['rulename_valide'] = false;
			}	
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
			exit;
		}	
		else {
			throw $this->createNotFoundException('Error');
		}			
	}

	// CREATION - STEP TWO - CHOIX MODULES
	public function ruleStepTwoAction() {
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		// si le nom de la règle est inferieur à 3 caractères :
		if(!isset($myddlewareSession['param']['rule']['source']['solution']) || strlen($myddlewareSession['param']['rule']['rulename']) < 3 || $myddlewareSession['param']['rule']['rulename_valide'] == false) {							
			$myddlewareSession['error']['create_rule'] = $this->get('translator')->trans('error.rule.valid');
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
			return $this->redirect($this->generateUrl('regle_stepone_animation'));	
			exit;
		}

		try{
			// ---------------- SOURCE ----------------------------	
			$solution_source_nom = $myddlewareSession['param']['rule']['source']['solution'];			
			$solution_source = $this->get('myddleware_rule.'.$solution_source_nom);
			
			$solution_source->login($this->decrypt_params($myddlewareSession['param']['rule']['source']));
			
			if(empty($solution_source->connexion_valide)) {
				$myddlewareSession['error']['create_rule'] = $this->get('translator')->trans('error.rule.source_module_connect');	
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
				return $this->redirect($this->generateUrl('regle_stepone_animation'));	
				exit;					
			}			
			
			$liste_modules_source = tools::composeListHtml($solution_source->get_modules('source'), $this->get('translator')->trans('create_rule.step2.choice_module'));		
			if(!$liste_modules_source) {			
				$myddlewareSession['error']['create_rule'] = $this->get('translator')->trans('error.rule.source_module_load_list');
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
				return $this->redirect($this->generateUrl('regle_stepone_animation'));	
				exit;
			}
						
			// ---------------- /SOURCE ----------------------------	
		}
		catch(Exception $e) {			
			$myddlewareSession['error']['create_rule'] = $this->get('translator')->trans('error.rule.source_module_all');
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
			return $this->redirect($this->generateUrl('regle_stepone_animation'));	
			exit;			
		}

		try{
			// ---------------- TARGET ----------------------------	
			// Si la solution est la même que la précèdente on récupère les infos
			if($myddlewareSession['param']['rule']['source']['solution'] == $myddlewareSession['param']['rule']['cible']['solution']) {
				$solution_cible = $solution_source;
				$solution_cible_nom = $solution_source_nom;
			}
			else {
				$solution_cible_nom = $myddlewareSession['param']['rule']['cible']['solution'];
				$solution_cible = $this->get('myddleware_rule.'.$solution_cible_nom);	
			}
				$solution_cible->login($this->decrypt_params($myddlewareSession['param']['rule']['cible']));
			
				if(empty($solution_cible->connexion_valide)) {
					$myddlewareSession['error']['create_rule'] = $this->get('translator')->trans('error.rule.target_module_connect');
					$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);					
					return $this->redirect($this->generateUrl('regle_stepone_animation'));	
					exit;					
				}	
				
				$liste_modules_cible = tools::composeListHtml( $solution_cible->get_modules('target'), $this->get('translator')->trans('create_rule.step2.choice_module'));	
			
				if(!$liste_modules_cible) {
					$myddlewareSession['error']['create_rule'] = $this->get('translator')->trans('error.rule.target_module_load_list');
					$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
					return $this->redirect($this->generateUrl('regle_stepone_animation'));
					exit;						
				}				
			// ---------------- /TARGET ----------------------------			
			}
			catch(Exception $e) {				
				$myddlewareSession['error']['create_rule'] = $this->get('translator')->trans('error.rule.target_module_all');
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);				
				return $this->redirect($this->generateUrl('regle_stepone_animation'));	
				exit;				
			}
			
        return $this->render('RegleBundle:Rule:create/step2.html.twig',array(
	        'solution_source'=>$solution_source_nom,
	        'solution_cible'=>$solution_cible_nom,
	        'liste_modules_source'=>$liste_modules_source,
	        'liste_modules_cible'=>$liste_modules_cible,
	        'params' => $myddlewareSession['param']['rule']
			)
		);		
		
	}
	
	// CREATION - STEP THREE - SIMULATION DES DONNEES
	public function ruleSimulationAction() {
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		
		if( $request->getMethod()=='POST' && isset(  $myddlewareSession['param']['rule'] )) {
						
			// retourne un tableau prêt à l'emploi
			$target = $this->createListeParamsRule(
				$this->getRequest()->request->get('champs'), // Fields
				$this->getRequest()->request->get('formules'), // Formula
				'' // Params flux
			); 
			
	
			$solution_source_nom = $myddlewareSession['param']['rule']['source']['solution'];			
			$solution_source = $this->get('myddleware_rule.'.$solution_source_nom);			
			$solution_source->login($myddlewareSession['param']['rule']['source']);
			$doc = $this->get('myddleware.document');
			$tab_simulation = array();				
			$sourcesfields = array();
			
			// récupération de tous les champs
			if(isset($target['fields']) && count($target['fields']) > 0) {
				foreach($target['fields'] as $f) {
					if(isset($f)) {
						foreach($f as $name_fields_target => $k ) {
							
							if(isset($k['champs'])) {
								$sourcesfields = array_merge($k['champs'],$sourcesfields);
							}
							else {
								$sourcesfields = $sourcesfields;
							}

						}						
					}

				}					
			}
			else {
				// ici pour les règles avec des relations uniquement
			 	return $this->render('RegleBundle:Rule:create/onglets/simulation_tab.html.twig',array(
				       'before' => array(), // source
				       'after' => array(), // target
				       'data_source' => false
					)
				);				
			}	
			
			// Récupère données source													
			$source = $solution_source->read_last( array( 
										'module' => $myddlewareSession['param']['rule']['source']['module'],
										'fields' => $sourcesfields));	
					
			if( isset($source['done']) ) {
				$before = array();
				$after = array();
				if($source['done']) {
					foreach($target['fields'] as $f) {
						foreach($f as $name_fields_target => $k ) {
							$r['after'] = array();
							// Préparation pour transformation
							$name = trim($name_fields_target);
							$target_fields = array(
												'target_field_name' => $name,
												'source_field_name' => ((isset($k['champs'])) ? implode(';',$k['champs']) : 'my_value' ),											
												'formula' => ((isset($k['formule'][0]) ? $k['formule'][0] : '' )),
												'related_rule' => ''
												);
							
							
							
													
							// Transformation								
							$r['after'][$name_fields_target] = $doc->getTransformValue( $source['values'] , $target_fields );
							
							if(empty($k['champs']))
								$k['fields']['Formula'] = ((isset($k['formule'][0]) ? $k['formule'][0] : '' ));
							else if(count($k['champs']) > 0) {
								foreach ($k['champs'] as $fields) {
									// Fields couldn't be return. For example Magento return only field not empty
									if (!empty($source['values'][ $fields ])) {
										$k['fields'][ $fields ] = $source['values'][ $fields ];
									}
									else {
										$k['fields'][ $fields ] = '';
									}
								}								
							}
							else {
								$k['fields'] = array();
							}
	
							$tab_simulation[] = array(
								'after' => $r['after'],
								'before' => $k['fields']								
							);
							
						}	
					}
					$after = array();
					// Préparation pour tableau template
					foreach ($tab_simulation as $key => $value) {
						foreach($value as $k => $v) {
							if($k == 'before') {
								$before[] = $v;
							}
							else {
								$after[] = $v;
							}
						}
					}										
				}		
			} 
		 	return $this->render('RegleBundle:Rule:create/onglets/simulation_tab.html.twig',array(
			       'before' => $before, // source
			       'after' => $after, // target
			       'data_source' => $source['done'],
			       'params' => $myddlewareSession['param']['rule']
				)
			);

		}
		else {
			throw $this->createNotFoundException('Error');
		}			
					
	}
	
	// CREATION - STEP THREE - CHOIX DES CHAMPS - MAPPING DES CHAMPS
	public function ruleStepThreeAction() {
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);		
		$this->getInstanceBdd();
			
		// Test que l'ordre des étapes
		if(!isset($myddlewareSession['param']['rule'])) {
			$myddlewareSession['error']['create_rule'] = $this->get('translator')->trans('error.rule.order');
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
			return $this->redirect($this->generateUrl('regle_stepone_animation'));					
			exit;
		}
		
		// Contrôle si la nouvelle règle peut-être valide
		if( strlen($myddlewareSession['param']['rule']['rulename']) < 3 || $myddlewareSession['param']['rule']['rulename_valide'] == false) {
			$myddlewareSession['error']['create_rule'] = $this->get('translator')->trans('error.rule.valid');	
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);			
			return $this->redirect($this->generateUrl('regle_stepone_animation'));	
			exit;
		}
		
		try {
			// ---- Mode update ----
			if(!isset($myddlewareSession['param']['rule']['source']['module']) && !isset($myddlewareSession['param']['rule']['cible']['module'])) {
				// RELOAD : Chargement des données d'une règle en édition
				$myddlewareSession['param']['rule']['source']['module'] = $this->getRequest()->request->get('source_module');
				$myddlewareSession['param']['rule']['cible']['module'] = $this->getRequest()->request->get('cible_module');					
			} 
			// ---- Mode update ----
			
			// Get all data from the target solution first		
			$solution_cible = $this->get('myddleware_rule.'.$myddlewareSession['param']['rule']['cible']['solution']);
			
			// TARGET ------------------------------------------------------------------	
			// We retriev first all data from the target application and the from the source application
			// We can't do both solution in the same time because we could have a bug when these 2 solutions are the same (service are shared by default in Symfony)
			$solution_cible->login($this->decrypt_params($myddlewareSession['param']['rule']['cible']));
			
			if($solution_cible->connexion_valide == false) {
				$myddlewareSession['error']['create_rule'] = $this->get('translator')->trans('error.rule.source_module_connect');
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
				return $this->redirect($this->generateUrl('regle_stepone_animation'));						
				exit;
			}
			
			if($this->getRequest()->request->get('cible_module')) {
				$module['cible'] = $this->getRequest()->request->get('cible_module'); // mode create <<----
			}
			else {
				$module['cible'] = $myddlewareSession['param']['rule']['cible']['module']; // mode update <<----
			}
			
			// Récupère la liste des paramètres cible
			$rule_params_target = $solution_cible->getFieldsParamUpd('target',$module['cible'],$myddlewareSession);
			
			// Récupère la liste des champs cible
			$rule_fields_target = $solution_cible->get_module_fields($module['cible'],'target');

			// Récupération de tous les modes de règle possibles pour la cible et la source
			$targetMode = $solution_cible->getRuleMode($module['cible'],'target');		
					
			$fieldMappingAdd = $solution_cible->getFieldMappingAdd($module['cible']);
			
			// Liste des relations TARGET
			$relation = $solution_cible->get_module_fields_relate($module['cible']);
			
			$allowParentRelationship = $solution_cible->allowParentRelationship($myddlewareSession['param']['rule']['cible']['module']);
			
			// Champs pour éviter les doublons
			$fieldsDuplicateTarget = $solution_cible->getFieldsDuplicate($myddlewareSession['param']['rule']['cible']['module']);
			
			// SOURCE ------------------------------------------------------------------
			// Connexion au service de la solution source			
			$solution_source = $this->get('myddleware_rule.'.$myddlewareSession['param']['rule']['source']['solution']);			
			$solution_source->login($this->decrypt_params($myddlewareSession['param']['rule']['source']));
			
			// Contrôle que la connexion est valide
			if($solution_source->connexion_valide == false) {
				$myddlewareSession['error']['create_rule'] = $this->get('translator')->trans('error.rule.source_module_connect');
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
				return $this->redirect($this->generateUrl('regle_stepone_animation'));						
				exit;
			}
			$modules = $solution_source->get_modules('source');
			if($this->getRequest()->request->get('source_module')) {
				$module['source'] = $this->getRequest()->request->get('source_module'); // mode create <<----
			}
			else {
				$module['source'] = $myddlewareSession['param']['rule']['source']['module']; // mode update <<----
			}
			
			// Met en mémoire la façon de traiter la date de référence
			$myddlewareSession['param']['rule']['source']['datereference'] = $solution_source->referenceIsDate($module['source']);
			
			// Ajoute des champs source pour la validation
			$rule_params_source = $solution_source->getFieldsParamUpd('source',$module['source'],$myddlewareSession);
			
			// Récupère la liste des champs source
			$rule_fields_source = $solution_source->get_module_fields($module['source'],'source');

			if($rule_fields_source) {
				$myddlewareSession['param']['rule']['source']['fields'] = $rule_fields_source;
				
				// Erreur champs, pas de données sources (Exemple: GotoWebinar)
				if(isset($myddlewareSession['param']['rule']['source']['fields']['error']) && !empty($myddlewareSession['param']['rule']['source']['fields']['error'])) {
					$myddlewareSession['error']['create_rule'] = $myddlewareSession['param']['rule']['source']['fields']['error'];
					$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
					return $this->redirect($this->generateUrl('regle_stepone_animation'));						
					exit;
				}
										
				foreach ($rule_fields_source as $t => $k) {
					$source['table'][$module['source']][$t] = $k['label'];
				}	
				// Tri des champs sans tenir compte de la casse						
				asort($source['table'][$module['source']], SORT_NATURAL | SORT_FLAG_CASE);	
			}

			// SOURCE ----- Récupère la liste des champs source
			
			// Type de synchronisation	
			// Récupération de tous les modes de règle possibles pour la source		
			$sourceMode = $solution_source->getRuleMode($module['source'],'source');
			// Si la target à le type S (search) alors on l'ajoute à la source pour qu'il soit préservé par l'intersection
			if (array_key_exists('S', $targetMode)) {
				$sourceMode['S'] = 'search_only';
			}
			$intersectMode = array_intersect($targetMode, $sourceMode);
			// Si jamais l'intersection venait à être vide (ce qui ne devrait jamais arriver) on met par défaut le mode CREATE
			if (empty($intersectMode)) {
				$intersectMode['C'] = 'create_only';
			}
			$myddlewareSession['param']['rule']['cible']['mode'] = $intersectMode;
			
			
			// Préparation des champs cible				
			$cible['table'] = array();
			
			if($rule_fields_target) {
				
				$myddlewareSession['param']['rule']['target']['fields'] = $rule_fields_target;				
					
				$tmp = $rule_fields_target;
				
				$normal = array();
				$required = array();
				foreach ($rule_fields_target as $t => $k) {
										
					if(isset($k['required']) && $k['required'] == true) {
						$required[] = $t;		
					}
					else {
						$normal[] =  $t;
					} 	
				}	
				
				asort($required);
				asort($normal);
		
				$alpha = array_merge($required,$normal);
				$field_target_alpha = array();
				foreach ($alpha as $name_fields) {
					$field_target_alpha[$name_fields] = $tmp[$name_fields]['required'];
				}
				
				$cible['table'][$module['cible']] = $field_target_alpha;
			}
			else {
				$cible['table'][$module['cible']] = array(); // rev 1.1.1
			}
			
			// On ajoute des champs personnalisés à notre mapping
			if($fieldMappingAdd && isset($myddlewareSession['param']['rule']['last_version_id'])) {
			
				$ruleFields = $this->getDoctrine()
						  ->getManager()
						  ->getRepository('RegleBundle:RuleField')
						  ->findByRule( $myddlewareSession['param']['rule']['last_version_id'] );
				
				$tmp = array();
				foreach ($ruleFields as $fields) {
					$tmp[$fields->getTarget()] = 0;
				}
				
				foreach ($cible['table'][$module['cible']] as $k => $value) {							
					$tmp[ $k ] = $value;
				}				
					
				$cible['table'][$module['cible']] = $tmp;	
				
				ksort($cible['table'][$module['cible']]);		
			}
						
// -------------------	TARGET		
			$lst_relation_target = array();
			$lst_relation_target_alpha = array();
			if($relation) {
				
				foreach ($relation as $key => $value) {
					$lst_relation_target[] = $key;
				}
				
				asort($lst_relation_target);
				
				foreach ($lst_relation_target as $name_relate) {
					$lst_relation_target_alpha[ $name_relate ]['required'] = $relation[ $name_relate ][ 'required_relationship' ];
					$lst_relation_target_alpha[ $name_relate ]['name'] = $name_relate;
					$lst_relation_target_alpha[ $name_relate ]['label'] = (!empty($relation[ $name_relate ][ 'label' ]) ? $relation[ $name_relate ][ 'label' ] : $name_relate);
				}								
			}		
			
// -------------------	SOURCE					
			// Liste des relations SOURCE
			$relation_source = $solution_source->get_module_fields_relate($myddlewareSession['param']['rule']['source']['module']);			
			$lst_relation_source = array();
			$lst_relation_source_alpha = array();
			$choice_source = array();
			if($relation_source) {
				
				foreach ($relation_source as $key => $value) {
					$lst_relation_source[] = $key;
				}
				
				asort($lst_relation_source);
				foreach ($lst_relation_source as $name_relate) {
					$lst_relation_source_alpha[ $name_relate ]['label'] = $relation_source[ $name_relate ][ 'label' ];
				}
				
				// préparation de la liste en html
				foreach ($lst_relation_source_alpha as $key => $value) {					
					$choice_source[ $key ] = (!empty($value['label']) ? $value['label'] : $key);	
				}					
											
			}				


			if(!isset($source['table'])) {
				$source['table'][ $myddlewareSession['param']['rule']['source']['module'] ] = array();		
			}

			// -- Relation
			// Liste des règles avec les mêmes connecteurs rev 1.07
			//
			$stmt = $this->connection->prepare('	
					SELECT r.id, r.name, r.module_source
					FROM Rule r
					WHERE 
						(
								conn_id_source=:id_source 
							AND conn_id_target=:id_target
							AND r.name != :name
							AND r.deleted = 0
						)
					OR (
								conn_id_target=:id_source 
							AND conn_id_source=:id_target
							AND r.name != :name
							AND r.deleted = 0
					)
					'); 	  
			$stmt->bindValue('id_source', (int)$myddlewareSession['param']['rule']['connector']['source'] ); 
			$stmt->bindValue('id_target', (int)$myddlewareSession['param']['rule']['connector']['cible'] ); 
			$stmt->bindValue('name', $myddlewareSession['param']['rule']['rulename'] ); 						
			$stmt->execute();
		
			$ruleListRelation = $stmt->fetchAll();
			
			//Verson 1.1.1 : possibilité d'ajouter des relations custom en fonction du module source
			$ruleListRelationSourceCustom = $solution_source->get_rule_custom_relationship($myddlewareSession['param']['rule']['source']['module'],'source');	
			if (!empty($ruleListRelationSourceCustom)) {
				$ruleListRelation = array_merge($ruleListRelation,$ruleListRelationSourceCustom);
			}		
			
			$choice = array();
			$control = array();
			
			foreach ($ruleListRelation as $key => $value) {
				
				if(!in_array($value['name'],$control) ) {
					$choice[ $value['id'] ] = $value['name'];	
					$control[] = $value['name'];				
				}						
			}
			
			asort($choice);

// -------------------	Parent relation 
			// Search if we can send document merged with the target solution
			$lstParentFields = array();
			if ($allowParentRelationship) {
				if (!empty($ruleListRelation)) {
					// We get all relate fields from every source module
					foreach ($ruleListRelation as $ruleRelation) {
						// Get the relate fields from the source module of related rules
						$rule_fields_source = $solution_source->get_module_fields($ruleRelation['module_source'],'source');
						$sourceRelateFields = $solution_source->get_module_fields_relate($ruleRelation['module_source']);
						if (!empty($sourceRelateFields)) {
							foreach($sourceRelateFields as $key => $sourceRelateField) {
								$lstParentFields[$key] = $sourceRelateField['label'];
							}
						}
					}
					// We allow  to search by the id of the module
					$lstParentFields['Myddleware_element_id'] = $this->get('translator')->trans('create_rule.step3.relation.record_id');
				}
				// No parent relation if no rule to link or no fields related
				if (empty($lstParentFields)) {
					$allowParentRelationship = false;
				}				
			}
				
			// On récupére l'EntityManager
			$this->getInstanceBdd();	
			
			// Récupère toutes les catégories
			$lstCategory = $this->em->getRepository('RegleBundle:FuncCat')
						   ->findAll();
			
			// Récupère toutes les functions
			$lstFunctions = $this->em->getRepository('RegleBundle:Functions')
						   ->findAll();			
			
			// Les filtres		
			$lst_filter = array(
						 'content' => $this->get('translator')->trans('filter.content'),
						 'notcontent' => $this->get('translator')->trans('filter.notcontent'),
						 'begin' => $this->get('translator')->trans('filter.begin'),
						 'end' => $this->get('translator')->trans('filter.end'),
						 'gt' => $this->get('translator')->trans('filter.gt'),
						 'lt' => $this->get('translator')->trans('filter.lt'),
						 'equal' => $this->get('translator')->trans('filter.equal'),
						 'different'=> $this->get('translator')->trans('filter.different'),
						 'gteq' => $this->get('translator')->trans('filter.gteq'),
						 'lteq' => $this->get('translator')->trans('filter.lteq'),
						 'in' => $this->get('translator')->trans('filter.in'),
						 'notin' => $this->get('translator')->trans('filter.notin')							 
						 );
			
			// paramètres de la règle				
			$rule_params = array_merge($rule_params_source,$rule_params_target);
			
			// récupération des champs de type liste --------------------------------------------------

			// -----[ SOURCE ]-----
			if(isset($myddlewareSession['param']['rule']['source']['fields'])) {
				foreach($myddlewareSession['param']['rule']['source']['fields'] as $field => $fields_tab) {					
					if (array_key_exists('option', $fields_tab)) {
						$formule_list['source'][$field] = $fields_tab;	
					}	
				}					
			}

			
			if(isset($formule_list['source']) && count($formule_list['source']) > 0) {
				foreach ($formule_list['source'] as $field => $fields_tab) {						
					foreach ($fields_tab['option'] as $field_name => $fields) {
						if(!empty($fields)) {
							$formule_list['source'][ $field ]['option'][ $field_name ] = $field_name . ' ( '.$fields.' )';	
						}							
					}
				}					
			}
			
			$html_list_source='';
			if( isset($formule_list['source']) ) {								
				foreach ($formule_list['source'] as $field  => $fields_tab) {					
					$html_list_source.='<optgroup label="'.$field.'">';
					$html_list_source.= tools::composeListHtml($fields_tab['option']);
					$html_list_source.='</optgroup>';
				}					
			}				
							
			// -----[ TARGET ]-----
			if(isset($myddlewareSession['param']['rule']['target']['fields'])) {
				foreach($myddlewareSession['param']['rule']['target']['fields'] as $field => $fields_tab) {					
					if (array_key_exists('option', $fields_tab)) {
						$formule_list['target'][$field] = $fields_tab;	
					}	
				}					
			}
			
			if(isset($formule_list['target']) && count($formule_list['target']) > 0) {
				foreach ($formule_list['target'] as $field => $fields_tab) {						
					foreach ($fields_tab['option'] as $field_name => $fields) {
						if(!empty($fields)) {
							$formule_list['target'][ $field ]['option'][ $field_name ] = $field_name . ' ( '.$fields.' )';	
						}
					}
				}					
			}				
		
			$html_list_target='';	
			if( isset($formule_list['target']) ) {								
				foreach ($formule_list['target'] as $field  => $fields_tab) {					
					$html_list_target.='<optgroup label="'.$field.'">';
					$html_list_target.= tools::composeListHtml($fields_tab['option']);
					$html_list_target.='</optgroup>';
				}					
			}				

			// récupération des champs de type liste --------------------------------------------------

			
			// Type de synchronisation de données rev 1.06 --------------------------
			if(isset($myddlewareSession['param']['rule']['cible']['mode'])) {
				
				$mode_translate = array();
				foreach ($myddlewareSession['param']['rule']['cible']['mode'] as $key => $value) {
					$mode_translate[ $key ] = $this->get('translator')->trans('create_rule.step3.syncdata.'.$value);
				}
				
				$mode = 
				array(	
					array(
						'id' 		=> 'mode',
						'name' 		=> 'mode',
						'required'	=> false,
						'type'		=> 'option',
						'label' => $this->get('translator')->trans('create_rule.step3.syncdata.label'),
						'option'	=> $mode_translate
						)
				);	
					
				$rule_params = array_merge($rule_params,$mode);	
			}
			// Type de synchronisation de données rev 1.06 --------------------------	
			
			
			//  rev 1.07 --------------------------
			$bidirectional_params['connector']['source'] = $myddlewareSession['param']['rule']['connector']['source'];
			$bidirectional_params['connector']['cible'] = $myddlewareSession['param']['rule']['connector']['cible'];
			$bidirectional_params['module']['source'] = $module['source'];
			$bidirectional_params['module']['cible'] = $module['cible'];
			
			$bidirectional = RuleClass::getBidirectionalRules( $this->get('database_connection'), $bidirectional_params );
			if($bidirectional) {
				$rule_params = array_merge($rule_params,$bidirectional);	
			}				
			//  rev 1.07 --------------------------
			$result = array(
					'source'=>$source['table'],
					'cible'=>$cible['table'],
					'rule_params'=>$rule_params, 
					'lst_relation_target'=>$lst_relation_target_alpha,
					'lst_relation_source'=>$choice_source,
					'lst_rule' =>$choice,
					'lst_category' => $lstCategory,
					'lst_functions' => $lstFunctions,
					'lst_filter' =>$lst_filter,
					'params' => $myddlewareSession['param']['rule'],
					'duplicate_target' => $fieldsDuplicateTarget,
					'opt_target' => $html_list_target,
					'opt_source' => $html_list_source,
					'fieldMappingAddListType' => $fieldMappingAdd,
					'parentRelationships' => $allowParentRelationship,					
					'lst_parent_fields'=> $lstParentFields,
				);
			$result = $this->beforeRender($result);
			
			// Formatage des listes déroulantes : 
			$result['lst_relation_source'] = tools::composeListHtml($result['lst_relation_source'], $this->get('translator')->trans('create_rule.step3.relation.fields'));
			$result['lst_parent_fields'] = tools::composeListHtml($result['lst_parent_fields'], ' ');
			$result['lst_rule'] = tools::composeListHtml($result['lst_rule'], $this->get('translator')->trans('create_rule.step3.relation.fields'));
			$result['lst_filter'] = tools::composeListHtml($result['lst_filter'], $this->get('translator')->trans('create_rule.step3.relation.fields'));
				
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
			return $this->render('RegleBundle:Rule:create/step3.html.twig',$result);					
										
			// ----------------
		}
		catch(Exception $e) {
			$myddlewareSession['error']['create_rule'] = $this->get('translator')->trans('error.rule.mapping');
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
			return $this->redirect($this->generateUrl('regle_stepone_animation'));				
			exit;
		}	
	}
	
	protected function beforeRender($result) {
		return $result;
	}

	// Indique des informations concernant le champ envoyé en paramètre
	public function infoFieldAction($field,$type) {
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		if(isset($field) && !empty($field) && isset($myddlewareSession['param']['rule']) && $field != 'my_value') {
			if(isset($myddlewareSession['param']['rule'][ $type ]['fields'][ $field ])) {
			 	return $this->render('RegleBundle:Rule:create/onglets/info.html.twig',array(
				        'field' => $myddlewareSession['param']['rule'][ $type ]['fields'][ $field ],
				        'name' => htmlentities( trim($field) )
					)
				);
			} else { // Possibilité de Mutlimodules
				foreach ($myddlewareSession['param']['rule'][ $type ]['fields'] as $subModule) { // Ce foreach fonctionnera toujours
					if(isset($subModule[$field])) { // On teste si ça existe pour éviter une erreur PHP éventuelle
					 	return $this->render('RegleBundle:Rule:create/onglets/info.html.twig',array(
						        'field' => $subModule[$field],
						        'name' => htmlentities( trim($field) )
							)
						);
					}
				}
				// On retourne vide si on l'a pas trouvé précédemment
			 	return $this->render('RegleBundle:Rule:create/onglets/info.html.twig',array(
				        'field' => ''
					)
				);
			}
		}
		else {
		 	return $this->render('RegleBundle:Rule:create/onglets/info.html.twig',array(
			        'field' => ''
				)
			);
		}

	}

	// CREATION - STEP THREE - VERIF DES FORMULES
	public function ruleVerifFormulaAction(){
		
		$request = $this->get('request');
		
		if($request->getMethod()=='POST') {
			
			// Mise en place des variables
			$formule = $this->get('formula.myddleware'); // service formule myddleware			
			$formule->init($this->getRequest()->request->get('formula')); // mise en place de la règle dans la classe
			$formule->generateFormule(); // Genère la nouvelle formule à la forme PhP	
			
			echo $formule->parse['error']; 	
			exit;
		}
		else {	
			throw $this->createNotFoundException('Error');
		}						
	}

	// CREATION - STEP THREE - Validation du formulaire 
	public function ruleValidationAction() {
		$request = $this->get('request');	
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
	
	    // On récupére l'EntityManager
		$this->getInstanceBdd();				   
		$this->em->getConnection()->beginTransaction();
		try {	
			
			// retourne un tableau prêt à l'emploi
			$tab_new_rule = $this->createListeParamsRule(
				$this->getRequest()->request->get('champs'), // Fields
				$this->getRequest()->request->get('formules'), // Formula
				$this->getRequest()->request->get('params') // Params
			); 
				
			// fields relate
			if(!empty( $this->getRequest()->request->get('duplicate') )) {
				$tab_new_rule['params']['rule']['duplicate_fields']	= implode($this->getRequest()->request->get('duplicate'),';');
			} 			
			// si le nom de la règle est inferieur à 3 caractères :
			if(strlen($myddlewareSession['param']['rule']['rulename']) < 3 || $myddlewareSession['param']['rule']['rulename_valide'] == false) {
				echo 0;	
				exit;
			}
			
			//------------ Create rule
  			$connector_source = $this->getDoctrine()
	                          ->getManager()
	                          ->getRepository('RegleBundle:Connector')
	                          ->findOneById( $myddlewareSession['param']['rule']['connector']['source'] );
			
  			$connector_target = $this->getDoctrine()
	                          ->getManager()
	                          ->getRepository('RegleBundle:Connector')
	                          ->findOneById( $myddlewareSession['param']['rule']['connector']['cible'] );
			
			$param = RuleClass::getFieldsParamDefault();
			
			// Get the id of the rule if we edit a rule
			// Generate Rule object (create a new one or instanciate the existing one
			if (!empty($myddlewareSession['param']['rule']['last_version_id'])) {
				$oneRule = $this->em->getRepository('RegleBundle:Rule')->find($myddlewareSession['param']['rule']['last_version_id']);
				$oneRule->setDateModified(new \DateTime);
				$oneRule->setModifiedBy( $this->getUser()->getId() );	
			} else {
				$oneRule = new Rule();
				$oneRule->setConnectorSource( $connector_source );	
				$oneRule->setConnectorTarget( $connector_target );				
				$oneRule->setDateCreated(new \DateTime);
				$oneRule->setDateModified(new \DateTime);
				$oneRule->setCreatedBy( $this->getUser()->getId() );
				$oneRule->setModifiedBy( $this->getUser()->getId() );	
				$oneRule->setModuleSource( $myddlewareSession['param']['rule']['source']['module'] );	
				$oneRule->setModuleTarget( $myddlewareSession['param']['rule']['cible']['module'] );	
				$oneRule->setDeleted( 0 );
				$oneRule->setActive( (int)$param['active'] );
				$oneRule->setName( $myddlewareSession['param']['rule']['rulename'] );
			}
		    $this->em->persist($oneRule);
			// On fait le flush pour obtenir le nameSlug. En cas de problème on fait un remove dans le catch
		    $this->em->flush(); 
			
			$myddlewareSession['rule']['newid'] = $oneRule->getId();			
			$nameRule = $oneRule->getNameSlug();		

			// BEFORE SAVE rev 1.08 ----------------------
			$relationshipsBeforeSave = $this->getRequest()->request->get('relations');
			$before_save = RuleClass::beforeSave($this->container,
				array('ruleName' => $nameRule,
					  'RuleId' => $oneRule->getId(),
					  'connector' => $myddlewareSession['param']['rule']['connector'],
					  'content' => $tab_new_rule,
					  'relationships' => $relationshipsBeforeSave,
					  'module' => array(
					  	'source' => 
					  	array(
							'solution' => $myddlewareSession['param']['rule']['source']['solution'],
							'name' => $myddlewareSession['param']['rule']['source']['module']
						),
					  	'target' => 
					  	array(
							'solution' => $myddlewareSession['param']['rule']['cible']['solution'],
							'name' => $myddlewareSession['param']['rule']['cible']['module']
						),				  	
					  )
				)	
			 );		
			if( !$before_save['done'] ) {		
				throw new \Exception($before_save['message']);
			}
			// Si le retour du beforeSave contient des paramètres alors on les ajoute à la règle avant sauvegarde
			if (!empty($before_save['params'])) {
				if (empty($tab_new_rule['params'])) {
					$tab_new_rule['params'] = $before_save['params'];
				}
				else {
					$tab_new_rule['params'] = array_merge($tab_new_rule['params'],$before_save['params']);
				}
			}
					
			// Edit mode
			if(!empty($myddlewareSession['param']['rule']['last_version_id'])) {
				// We delete every data of the rule before we create them again
				// Rule fields
				$ruleFields = $this->em->getRepository('RegleBundle:RuleField')->findByRule($oneRule->getId());
				if($ruleFields) {
					foreach ($ruleFields as $ruleField) {
						$this->em->remove($ruleField);
						$this->em->flush();
					}
				}
				
				// Rule RelationShips
				$ruleRelationShips = $this->em->getRepository('RegleBundle:RuleRelationShip')->findByRule($oneRule->getId());
				if($ruleRelationShips) {				
					foreach ($ruleRelationShips as $ruleRelationShip) {
						$this->em->remove($ruleRelationShip);
						$this->em->flush();
					}
				}
				
				// Rule Filters
				$ruleFilters = $this->em->getRepository('RegleBundle:RuleFilter')->findByRule($oneRule->getId());
				if($ruleFilters) {				
					foreach ($ruleFilters as $ruleFilter) {
						$this->em->remove($ruleFilter);
						$this->em->flush();
					}
				}

				// Rule Params
				$ruleParams = $this->em->getRepository('RegleBundle:RuleParam')->findByRule($oneRule->getId());
				if($ruleParams) {				
					foreach ($ruleParams as $ruleParam) {
						// Save reference date						
						if ($ruleParam->getName() == 'datereference') {
							$date_reference = $ruleParam->getValue();
						}
						$this->em->remove($ruleParam);
						$this->em->flush();
					}
				}   
			}
			// Create mode
			else {
				if(isset($myddlewareSession['param']['rule']['source']['datereference']) && $myddlewareSession['param']['rule']['source']['datereference']) {				
					$date_reference = date('Y-m-d 00:00:00');
				}
				else {
					$date_reference = '';
				}
			}
				
			//------------------------------- Create rule params -------------------
			if(isset($tab_new_rule['params']) || isset($param['RuleParam'])) {

				if(!isset($tab_new_rule['params'])) {
					$p = $param['RuleParam'];
				}
				else {
					$p = array_merge($param['RuleParam'],$tab_new_rule['params']);
				}
		
				$bidirectional = '';												
				foreach($p  as $key => $value) {
					// Value could be empty, for bidirectional parameter for example (we don't test empty because mode could be equal 0)
					if ($value == '') {
						continue;
					}
					$oneRuleParam = new RuleParam();
					$oneRuleParam->setRule( $oneRule->getId() );
										
					// si tableau de doublon
					if($key == 'rule') {
						$oneRuleParam->setName( 'duplicate_fields' );
						$oneRuleParam->setValue( $value['duplicate_fields'] );
					}
					else {				
						$oneRuleParam->setName( $key );
						if($key == 'datereference') {
							// date de référence change en fonction create ou update
							$oneRuleParam->setValue( $date_reference );
						}
						else {
							$oneRuleParam->setValue( $value );
						}	
					} 
					
					// Save the parameter
					if($key == 'bidirectional') {
						$bidirectional = $value;
					}
					
				    $this->em->persist($oneRuleParam);
				    $this->em->flush();						
				}
				
				// If a bidirectional parameter exist, we check if the opposite one exists too
				if (!empty($bidirectional)) {
					// Update the opposite rule if birectional rule
					$ruleParamBidirectionalOpposite = $this->em->getRepository('RegleBundle:RuleParam')
														->findOneBy( array(
																'rule' => $bidirectional,
																'name' => 'bidirectional',
																'value' => $oneRule->getId()
															));			
					// If the bidirectional parameter doesn't exist on the opposite rule we create it
					if (empty($ruleParamBidirectionalOpposite)) {
						$ruleParamBidirectionalOpposite = new RuleParam();
						$ruleParamBidirectionalOpposite->setRule( $bidirectional );
						$ruleParamBidirectionalOpposite->setName( 'bidirectional' );
						$ruleParamBidirectionalOpposite->setValue( $oneRule->getId() );
						$this->em->persist($ruleParamBidirectionalOpposite);
					}
				} else {	
					// If no bidirectional parameter on the rule and if the bidirectional parametr exist on an opposite rule, we delete it
					$ruleParamBidirectionalDelete = $this->em->getRepository('RegleBundle:RuleParam')
										->findOneBy( array(
												'value' => $oneRule->getId(),
												'name' => 'bidirectional'
											));				
					if (!empty($ruleParamBidirectionalDelete)) {
						$this->em->remove($ruleParamBidirectionalDelete);
						$this->em->flush();
					}
				}
			}			
			
			//------------------------------- Create rule fields ------------------- 
			$debug = array();
					
			if(isset($tab_new_rule['fields'])) {			
				foreach ($tab_new_rule['fields']['name'] as $field_target => $c) {	
					$field_source ="";	
					if(isset($c['champs'])) {						
						foreach ($c['champs'] as $name) {
							$field_source .= $name.";";
						}						
						$field_source = trim($field_source,";");					
					}

					// Formula
					$formule ="";
					if(isset($c['formule'])) {
						foreach ($c['formule'] as $name) {
							$formule .= $name." ";
							$debug[] = $name." ";
						}						
					}
					
					// delete space
					$field_source = str_replace(' ', '', $field_source);
					
					// Insert
					$oneRuleField = new RuleField();
					$oneRuleField->setRule( $oneRule->getId() );
					$oneRuleField->setTarget( trim($field_target) );	
					$oneRuleField->setSource( ((!empty($field_source)) ? $field_source : 'my_value' ) );	
					$oneRuleField->setFormula( ((!empty($formule)) ? trim($formule) : NULL ) );	
				    $this->em->persist($oneRuleField);
				    $this->em->flush(); 	
				}		
			}
						
			//------------------------------- RELATIONSHIPS -------------------
			$tabRelationShips = array();
			if( !is_null($this->getRequest()->request->get('relations')) ) {
				foreach ( $this->getRequest()->request->get('relations') as $rel ) {
					if (
							!empty($rel['rule'])
						&&	!empty($rel['source'])
					) {
						// Creation dans la table RelationShips
						$oneRuleRelationShip = new RuleRelationShip();
						$oneRuleRelationShip->setRule( $oneRule->getId() );
						$oneRuleRelationShip->setFieldNameSource( $rel['source'] );
						$oneRuleRelationShip->setFieldNameTarget( $rel['target'] );
						$oneRuleRelationShip->setFieldId( $rel['rule'] );
						$oneRuleRelationShip->setParent( $rel['parent'] );
						// We don't create the field target if the relatiobnship is a parent one 
						// We only use this field to search in the source application, not to send the data to the target application.
						if (empty($rel['parent'])) {
							$tabRelationShips['target'][] = $rel['target'];
						}
						$tabRelationShips['source'][] = $rel['source'];
						
						$this->em->persist($oneRuleRelationShip);
						$this->em->flush(); 
					}
				}
			}
			
			//------------------------------- RuleFilter ------------------------
			
			if( count( $this->getRequest()->request->get('filter') ) > 0 ) {
				foreach ( $this->getRequest()->request->get('filter') as $filter ) {
					$oneRuleFilter = new RuleFilter();	
					$oneRuleFilter->setTarget( $filter['target'] );
					$oneRuleFilter->setRule( $oneRule->getId() );
					$oneRuleFilter->setType( $filter['filter'] );
					$oneRuleFilter->setValue( $filter['value'] );
				    $this->em->persist($oneRuleFilter);
				    $this->em->flush(); 
				}
			}
			
			
			// --------------------------------------------------------------------------------------------------
			// Order all rules
			$job = $this->get('myddleware_job.job');		
			$job->orderRules();
			
			// --------------------------------------------------------------------------------------------------
			// Create rule history in order to follow all modifications
			// Encode every rule parameters
			$ruledata = json_encode(
							array(
								'ruleName' => $nameRule,
								'datereference' => $date_reference,
								'content' => $tab_new_rule,
								'filters' => $this->getRequest()->request->get('filter'),
								'relationships' => $relationshipsBeforeSave,
							)
						);
			// Save the rule audit			
			$oneRuleAudit = new RuleAudit();
			$oneRuleAudit->setRule($oneRule->getId());
			$oneRuleAudit->setDateCreated(new \DateTime);
			$oneRuleAudit->setData($ruledata);			
			$this->em->persist($oneRuleAudit);
			$this->em->flush();			

			
			// notification 
			$solution_source = $this->get('myddleware_rule.'.$myddlewareSession['param']['rule']['source']['solution']);
			$solution_source->setMessageCreateRule($myddlewareSession['param']['rule']['source']['module']);

			$solution_target = $this->get('myddleware_rule.'.$myddlewareSession['param']['rule']['cible']['solution']);
			$solution_target->setMessageCreateRule($myddlewareSession['param']['rule']['cible']['module']); 
			// notification
			
			// --------------------------------------------------------------------------------------------------	
				
			// Détection règle root ou child rev 1.08 ----------------------
			// On réactualise les paramètres
			$tab_new_rule['content']['params'] = $p;
			RuleClass::afterSave($this->container,	array(
						'ruleId' => $oneRule->getId(),
						'ruleName' => $nameRule,
						'oldRule' => (empty($myddlewareSession['param']['rule']['last_version_id']) ? '' : $myddlewareSession['param']['rule']['last_version_id']),
						'datereference' => $date_reference,
						'connector' => $myddlewareSession['param']['rule']['connector'],
						'content' => $tab_new_rule,
						'relationships' => $relationshipsBeforeSave,
						'module' => array(
							'source' => 
							array(
								'solution' => $myddlewareSession['param']['rule']['source']['solution'],
								'name' => $myddlewareSession['param']['rule']['source']['module']
							),
							'target' => 
							array(
								'solution' => $myddlewareSession['param']['rule']['cible']['solution'],
								'name' => $myddlewareSession['param']['rule']['cible']['module']
							),				  	
						)
					)	
			 );		
			if(isset($myddlewareSession['param']['rule'])) {
				unset( $myddlewareSession['param']['rule'] );
			}			
			$this->em->getConnection()->commit();
			echo 1;
		}catch(\Exception $e) {
			$this->em->getConnection()->rollBack();
			$this->get('logger')->error('2;'.htmlentities($e->getMessage().' (line '.$e->getLine().')'));
			echo '2;'.htmlentities($e->getMessage().' (line '.$e->getLine().')'); 
		} 	
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
		$this->em->close();	
		exit;
	}

	/* ******************************************************
	 * TABLEAU DE BORD
	 ****************************************************** */
	public function panelAction() {
		$language = $this->container->getParameter('locale');
		$myddleware_support = $this->container->getParameter('myddleware_support');
		
		$this->getInstanceBdd();	
		$solution = $this->em->getRepository('RegleBundle:Solution')
					   ->solutionActive();
		$lstArray = array();			   
		if($solution) {			
			foreach ($solution as $s) {
				$lstArray[] = $s->getName();
			}
		}
		
		$home = $this->get('myddleware.home');
		$permission =  $this->get('myddleware.permission');
		$active = 0;
		// Display buuton only if support is active
		if ($myddleware_support) {
			$objSupport = $this->getDoctrine()->getManager()->getRepository('LoginBundle:User')->findBy(array('username' => 'support'));
			if (isset($objSupport[0])) {
				$active = $objSupport[0]->isEnabled();
			}
		}
		$isAdmin = $permission->isAdmin($this->getUser()->getId());
		
		$countTypeDoc = array();
		$nbFlux = 0;	
		
		$listFlux = $home->countTypeDoc($isAdmin, $this->getUser()->getId());
		
		foreach ($listFlux as $field => $value) {
			$nbFlux = $nbFlux + (int)$value['nb'];
		}			
		
	 	return $this->render('RegleBundle:Home:index.html.twig',array(
	 			'errorByRule' => $home->errorByRule($isAdmin, $this->getUser()->getId()),
	 			'listJobDetail' => $home->listJobDetail(),
				'nbFlux' => $nbFlux,
				'solutions' => $lstArray,
				'locale' => $language
			)
		);			
	}

	public function graphTypeErrorAction() {
		$home = $this->get('myddleware.home');
		$permission =  $this->get('myddleware.permission');
		$countTypeDoc = array();
		$i=1;
		foreach ($home->countTypeDoc($permission->isAdmin($this->getUser()->getId()), $this->getUser()->getId()) as $field => $value) {
			if($i == 1) {
				$countTypeDoc[] = array('test','test2');	
			} 
			
			$countTypeDoc[] = array($value['global_status'],(int)$value['nb']);
			$i++;
		}

		return new Response(json_encode($countTypeDoc));			
	}
	 
	public function graphTransferRuleAction() {
	
		$home = $this->get('myddleware.home');
		$permission =  $this->get('myddleware.permission');
		$countTransferRule = array();
		$i=1;
		foreach ($home->countTransferRule($permission->isAdmin($this->getUser()->getId()), $this->getUser()->getId()) as $field => $value) {
			if($i == 1) {
				$countTransferRule[] = array('test','test2');	
			} 
			
			$countTransferRule[] = array($value['name'],(int)$value['nb']);
			$i++;
		}

		return new Response(json_encode($countTransferRule));			
	} 
	
	public function graphTransferHistoAction() {
		$tools = new tools($this->get('logger'), $this->container, $this->get('database_connection'));	
		$home = $this->get('myddleware.home');
		$permission =  $this->get('myddleware.permission');
		$countTransferRule = array();
		$i=1;
		foreach ($home->countTransferHisto($permission->isAdmin($this->getUser()->getId()), $this->getUser()->getId()) as $field => $value) {
			if($i == 1) {
				$countTransferRule[] = array('date',$tools->getTranslation(array('flux', 'gbl_status', 'open')),$tools->getTranslation(array('flux', 'gbl_status', 'error')),$tools->getTranslation(array('flux', 'gbl_status', 'cancel')),$tools->getTranslation(array('flux', 'gbl_status', 'close')));	
			} 
			
			$countTransferRule[] = array($value['date'],(int)$value['open'],(int)$value['error'],(int)$value['cancel'],(int)$value['close']);
			$i++;
		}
		return new Response(json_encode($countTransferRule));			
	} 
	
	public function graphJobHistoAction() {
		$tools = new tools($this->get('logger'), $this->container, $this->get('database_connection'));	
		$home = $this->get('myddleware.home');
		$permission =  $this->get('myddleware.permission');
		$countTransferRule = array();
		$i=1;
		foreach ($home->countJobHisto($permission->isAdmin($this->getUser()->getId()), $this->getUser()->getId()) as $field => $value) {
			if($i == 1) {
				$countTransferRule[] = array('date',$tools->getTranslation(array('flux', 'gbl_status', 'open')),$tools->getTranslation(array('flux', 'gbl_status', 'error')),$tools->getTranslation(array('flux', 'gbl_status', 'cancel')),$tools->getTranslation(array('flux', 'gbl_status', 'close')));	
			} 
			
			$countTransferRule[] = array($value['date'],(int)$value['open'],(int)$value['error'],(int)$value['cancel'],(int)$value['close']);
			$i++;
		}
		return new Response(json_encode($countTransferRule));			
	} 
	/* ******************************************************
	 * ANIMATION
	 ****************************************************** */
	// No more submodule in Myddleware. We return a response 0 for the js (animation.js
	public function listSubModulesAction() {
		return new Response(0);		
	} 
	
	// VALIDATION DE L ANIMATION
	public function validationAnimationAction() {
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		try{
			if(isset($_POST['choice_select'])) {
				
				if($_POST['choice_select'] == 'module') {
					
					// si le nom de la règle est inferieur à 3 caractères :
					if(!isset($myddlewareSession['param']['rule']['source']['solution']) || strlen($myddlewareSession['param']['rule']['rulename']) < 3) {
						$myddlewareSession['param']['rule']['rulename_valide'] = false;
					}
					else {
						$myddlewareSession['param']['rule']['rulename_valide'] = true;
					}
					
					$myddlewareSession['param']['rule']['source']['module'] = $_POST['module_source'];
					$myddlewareSession['param']['rule']['cible']['module'] = $_POST['module_target'];
					$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
					return new Response('module');
				}
				else if($_POST['choice_select'] == 'template') {
					$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
					//--
					$template = $this->get('myddleware.template');	
					$template->setIdConnectorSource( (int)$myddlewareSession['param']['rule']['connector']['source'] );
					$template->setIdConnectorTarget( (int)$myddlewareSession['param']['rule']['connector']['cible'] );
					$template->setLang( mb_strtoupper($this->getRequest()->getLocale()) );
					$template->setIdUser( $this->getUser()->getId() );	
					// Rule creation with the template selected in parameter
					$convertTemplate = $template->convertTemplate($_POST['template']);
					// We return to the list of rule even in case of error (session messages will be displyed in the UI)/: See animation.js function animConfirm
					return new Response('template');
				}	
				else {
					return new Response(0);
				}				
			}
			else {
				return new Response(0);
			}			
		}
		catch(Exception $e) {
			return new Response($e->getMessage());
		}
	}

	// LISTE DES TEMPLATES
	public function listTemplateAction() {
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);		
		$template = $this->get('myddleware.template');	
	
		$template->setsolutionSourceName($myddlewareSession['param']['rule']['source']['solution']);
		$template->setSolutionTarget($myddlewareSession['param']['rule']['cible']['solution']);
		$template->setLang( mb_strtoupper($this->getRequest()->getLocale()) );
		$template->setIdUser( $this->getUser()->getId() );
		$templates = $template->getTemplates();
		if(!empty($templates)) {
			$rows='';
			foreach ($templates as $t) {			
				$rows .='<tr>
                            <td><span data-id="'.$t['name'].'" class="glyphicon glyphicon-th-list"></span></td>
                            <td>'.$t['name'].'</td>
                            <td>'.$t['description'].'</td>
                         </tr>';
			}
				
            return new Response('<table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>'.$this->get('translator')->trans('animate.choice.name').'</th>
                        <th>'.$this->get('translator')->trans('animate.choice.description').'</th>
                    </tr>
                </thead>
                <tbody>
				'.$rows.'
                </tbody>
            </table>');				
		}
		else {
			return new Response('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon-warning-sign"></span> '.$this->get('translator')->trans('animate.choice.empty').'</div>');
		}
	}

	// CREATION - STEP ONE - ANIMATION
	public function ruleStepOneAnimationAction() {
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		// s'il existe des vielles données on les supprime
		if(isset( $myddlewareSession['param']['rule'] )) {
			unset( $myddlewareSession['param']['rule'] );
		}	
	
		if(isset( $myddlewareSession['param']['myddleware']['connector'] )) {
			unset( $myddlewareSession['param']['myddleware']['connector'] );
		}	
				
		// Détecte s'il existe des erreurs
		if(isset($myddlewareSession['error']['create_rule']) && !empty($myddlewareSession['error']['create_rule']) ) {
			$error = $myddlewareSession['error']['create_rule'];
			unset($myddlewareSession['error']['create_rule']);
		}
		else {
			$error = false;
		}		

		// Detecte si la session est le support ---------
		$permission =  $this->get('myddleware.permission');
		// Detecte si la session est le support ---------
					
		// Liste source : solution avec au moins 1 connecteur			
		$this->getInstanceBdd();	
	
		$solutionSource = $this->em->getRepository('RegleBundle:Solution')
		                     ->solutionConnector( 'source',$permission->isAdmin($this->getUser()->getId()), $this->getUser()->getId() );
					  		   		   
		if( count($solutionSource) > 0 ) {
			foreach($solutionSource as $s) {
				$source[] = $s->getName();
			}
			
			$myddlewareSession['param']['myddleware']['connector']['solution']['source'] = json_encode($source); 		
		} 			   
			   			   			   			
		// Liste target : solution avec au moins 1 connecteur
		$solutionTarget = $this->em->getRepository('RegleBundle:Solution')
				  	         ->solutionConnector( 'target',$permission->isAdmin($this->getUser()->getId()), $this->getUser()->getId() );
					   
		if( count($solutionTarget) > 0 ) {
			foreach($solutionTarget as $t) {
				$target[] = $t->getName();
			}	
			
			$myddlewareSession['param']['myddleware']['connector']['solution']['target']	= json_encode($target); 			
		} 					   	
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
        return $this->render('RegleBundle:Rule:create/step1simply.html.twig',array(
		       'source' => $solutionSource,
		       'target' => $solutionTarget,
		       'error' => $error
			)
		);		
	}

	// LISTE DES MODULES POUR ANIMATION
	public function ruleListModuleAction() {
		
		try {
			$request = $this->get('request');
			$session = $request->getSession();
			$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
			// We always add data again in session because these data are removed after the call of the get
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	

			$id_connector = (int)$_POST['id'];
			$type = $_POST['type'];
			
			$this->getInstanceBdd();	
			$connector = $this->em->getRepository('RegleBundle:Connector')
							->findById( $id_connector ); // infos connector
							
			$connectorParams = $this->em->getRepository('RegleBundle:ConnectorParam')
								->findByConnector( $id_connector );	// infos params connector
			
			foreach ($connectorParams as $p) {
				$myddlewareSession['param']['rule'][$type][$p->getName()] = $p->getValue();	// params connector
			}
																				
			$myddlewareSession['param']['rule']['connector'][$type] = $id_connector; // id connector
			$myddlewareSession['param']['rule'][$type]['solution'] = $connector[0]->getSolution()->getName(); // nom de la solution		
				
			$solution = $this->get('myddleware_rule.'.$myddlewareSession['param']['rule'][$type]['solution']);
			
			$params_connexion = $this->decrypt_params($myddlewareSession['param']['rule'][$type]);
			$params_connexion['idConnector'] = $id_connector;
			
			$solution->login( $params_connexion );	
			
			$t = (($type == 'source') ? 'source' : 'target' );
			
			$liste_modules = tools::composeListHtml($solution->get_modules($t), $this->get('translator')->trans('create_rule.step1.choose_module'));	
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
			return new Response($liste_modules);			
		}
		catch(\Exception $e) {
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
			return new Response('<option value="">Aucun module pour ce connecteur</option>');	
		}		
	}

	/* ******************************************************
	 * METHODES PRATIQUES
	 ****************************************************** */

	// CREATION REGLE - STEP ONE : Liste des connecteurs pour un user
	private function liste_connectorAction($type) {

		$this->getInstanceBdd();	
		$solution = $this->em->getRepository('RegleBundle:Connector')
				  	   ->findAllConnectorByUser( $this->getUser()->getId(), $type ); // type = source ou target
		$lstArray = array();			   
		if($solution) {			
			foreach ($solution as $s) {
				$lstArray[$s['name'].'_'.$s['id_connector']] = ucfirst($s['label']);
			}
		}					   
					   				   
		$lst_solution = tools::composeListHtml($lstArray,$this->get('translator')->trans('create_rule.step1.list_empty'));

		return $lst_solution;
	}

	// CREATION REGLE - STEP THREE - Retourne les paramètres dans un bon format de tableau
	private function createListeParamsRule($fields,$formula,$params) {

		$phrase_placeholder = $this->get('translator')->trans('rule.step3.placeholder');
		$tab = array();
						
		// FIELDS ------------------------------------------
			if($fields) {
				$champs = explode(';',$fields);
				foreach($champs as $champ) {
					$chp = explode('[=]',$champ);
					
					if($chp[0]) {
						if($phrase_placeholder != $chp[1] && 'my_value' != $chp[1]) {
							$tab['fields']['name'][$chp[0]]['champs'][] = $chp[1];
						}
					}
				}				
			}	
		
		// FORMULA -----------------------------------------	
			if($formula) {
				$formules = explode(';',$formula);
				
				foreach($formules as $formule) {
					$chp = explode('[=]',$formule);
					if($chp[0]) {
						if(!empty($chp[1])) {
							$tab['fields']['name'][$chp[0]]['formule'][] = $chp[1];
						}
					}
				}
			}
			
		// PARAMS -----------------------------------------	
		if($params) {
			foreach ($params as $k => $p) {
				$tab['params'][$p['name']] = $p['value'];
			}			
		}
	
			return $tab;
	}

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

	// Décrypte les paramètres de connexion d'une solution
	private function decrypt_params($tab_params) {
		// Instanciate object to decrypte data
		$encrypter = new \Illuminate\Encryption\Encrypter(substr($this->container->getParameter('secret'),-16));
		if( is_array($tab_params) ) {
			$return_params = array();
			foreach ($tab_params as $key => $value) {				
				if(
						is_string($value)
					 && !in_array($key, array('solution','module')) // Soe data aren't crypted 	
				) {
					$return_params[$key] = $encrypter->decrypt($value);
				}
			}
			return $return_params;				
		}
		else {
			return $encrypter->decrypt($tab_params);	
		}	
	}
	
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Controller/DefaultController.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class DefaultController extends DefaultControllerCore {
		
	}
}

