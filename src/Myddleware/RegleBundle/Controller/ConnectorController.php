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
//--
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
//--
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Exception\NotValidCurrentPageException;
//--
use Myddleware\RegleBundle\Entity\Connector;
use Myddleware\RegleBundle\Entity\ConnectorParam;
//--
use Myddleware\RegleBundle\Classes\tools;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Request;
use Myddleware\RegleBundle\Form\ConnectorType;
use Myddleware\RegleBundle\Service\SessionService;

class ConnectorController extends Controller
{

	/* ******************************************************
	 * CONNECTOR
	 ****************************************************** */

	// CALLBACK POUR LES APIS
	public function callBackAction() { // REV 1.1.1
		try {	
			/* @var $sessionService SessionService */
			$sessionService = $this->get('myddleware_session.service');
                        
			// Nom de la solution
			if(!$sessionService->isSolutionNameExist()) {
				return new Response('');
			}
			else {
				$solution_name = $sessionService->getSolutionName();			
			}
					
			$solution = $this->get('myddleware_rule.'.$solution_name);
						
			// ETAPE 2 : Récupération du retour de la Popup en GET et génération du token final
			if(isset($_GET[$solution->nameFieldGet])) {	
                            
                                $connectorSource = $sessionService->getParamConnectorSource();
                                
				$solution->init($connectorSource); // Affecte les variables
	
				$solution->setAuthenticate($_GET[$solution->nameFieldGet]);	

				if($solution->refresh_token) { // Si RefreshToken
					$sessionService->setParamConnectorSourceRefreshToken($solution->getRefreshToken());
				}	
				
				$solution->login($connectorSource); 
				
				// Sauvegarde des 2 jetons en session afin de les enregistrer dans les paramètres du connecteur
				$sessionService->setParamConnectorSourceToken($solution->getAccessToken());
                                
				if($solution->refresh_token) { // Si RefreshToken	
					$sessionService->setParamConnectorSourceRefreshToken($solution->getRefreshToken());
				}
					
				return $this->redirect($this->generateUrl('connector_callback'));
			}	
					
			// SOLUTION AVEC POPUP ---------------------------------------------------------------------
			// ATAPE 1 si la solution utilise un callback et le js
			if($solution->callback && $solution->js) {		
				if(!$sessionService->isParamConnectorSourceExist())  {
					$params_connexion_solution = $sessionService->getParamConnectorSource();
				}
				if(!$sessionService->isParamConnectorSourceTokenExist())  {
					$params_connexion_solution['token'] =  $sessionService->getParamConnectorSourceToken();
				}
				if(!$sessionService->isParamConnectorSourceRefreshTokenExist())  {
					$params_connexion_solution['refreshToken'] = $sessionService->getParamConnectorSourceRefreshToken();
				}
	
				$solution->init($params_connexion_solution); // Affecte les variables
				
				$error = $solution->login( $params_connexion_solution );
				
				// Gestion des erreurs retour méthode login
				if(!empty($error)) {
					return new Response('');
				}
									
				// Autorisation de l'application 
				if(!empty($_POST['solutionjs']) ) {				
					// Déclenche la pop up															
					if(!empty($_POST['detectjs'])) {							
						$callbackUrl = $solution->getCreateAuthUrl((isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$this->generateUrl('connector_callback'));								
						if(!$sessionService->isParamConnectorSourceTokenExist()) {			
							$solution->setAccessToken($sessionService->getParamConnectorSourceToken());
						}					
						// Redirection vers une autorisation manuel			
						else {	
							
							return new Response($solution->js.';'.urldecode($callbackUrl)); // Url de l'authentification prêt à être ouvert en popup
						}
					
						// 1er test de validité du Token
						$testToken = $solution->testToken();

						if(!empty($testToken['error']['code'])) {	
							if($testToken['error']['code'] == 401 || $testToken['error']['code'] == 404) {
								$sessionService->setParamConnectorSourceToken(NULL);
								$url = $solution->getCreateAuthUrl($callbackUrl);	
								return new Response($solution->js.';'.urldecode($url)); // Url de l'authentification prêt à être ouvert en popup						
							}
						}	
						
						return new Response($solution->js.';'.$callbackUrl);	// tentative de connexion												

					} // detect js
					
					if($sessionService->isParamConnectorSourceTokenExist()) {
						$solution->setAccessToken($sessionService->getParamConnectorSourceToken());				
					} 
					// 2nd Test la validité du token
					$testToken = $solution->testToken();
					
					// Erreur sans ouvrir la popup
					if($testToken['error']['code'] == 404 || $testToken['error']['code'] === 0) {
						return new Response("2;".$testToken['error']['message']); // Error Not Found
					}
					
					if(isset($testToken['error']['code']) && !empty($testToken['error']['code']) && !empty($testToken['error']['message'])) {	
						return new Response($testToken['error']['code'].';'.$testToken['error']['message']);
					}	
									
					if($sessionService->isParamConnectorSourceTokenExist()) {				
						if(isset($testToken['error']['message']) && !empty($testToken['error']['message'])) {
							return new Response($testToken['error']['message'] . ';'); // Erreur de connexion
						}
						else {
							$solution->connexion_valide = true;
							return new Response(1); // Connexion réussi
						}
					}		
				}        
				return new Response('<script type="text/javascript" language="javascript">window.close();</script>'); // Ferme la popup automatiquement			
			} // fin 
			// SOLUTION AVEC POPUP ---------------------------------------------------------------------
			else {
				throw new \Exception('Failed load class');
			}
		}
		catch (\Exception $e) {
			return new Response($e->getMessage());
		}
		return new Response('');
	} 

	// Contrôle si le fichier upload est valide puis le déplace
    public function uploadAction($solution) // REV 1.1.0
    {			
		if( isset($solution) ) {
			$output_dir = __DIR__."/../Custom/Solutions/".trim($solution)."/file/";
			// Get canonicalized absolute pathname
			$path = realpath($output_dir);
			// If it exist, check if it's a directory
			if($path === false || !is_dir($path)) {
				try {
					if(!mkdir($output_dir,755,true)) {
						echo '0;'.'Directory '.$output_dir.' doesn\'t exist. Failed to create this directory. Please check directory Custom is readable by webuser. You can create manually the directory for the Sage wsdl too. ';
						exit;	
					}
				}
				catch (\Exception $e) {
					echo '0;'.$e->getMessage().'. Please check you have the web user has the permission to write in the directory '.__DIR__.'/../Custom . ';
					exit;	
				}
			}	
		}
		/* @var $sessionService SessionService */
		$sessionService = $this->get('myddleware_session.service');
		
		// Supprime ancien fichier de config s'il existe
		if(isset($_GET['file']) && $_GET['file'] != '') {
			$name_without_space = str_replace(' ', '_', $_GET['file']);	
			$path_delete_old = $output_dir.$name_without_space;
			if(file_exists($path_delete_old)) {
				unlink( $path_delete_old ) ;
				echo '<br/><br/><p><span class="label label-warning">'.$this->get('translator')->trans('create_connector.upload_delete').' : '.htmlentities($name_without_space).'</span></p>';
			}
		}
		
	 	if($solution == 'all') {
                    if($sessionService->isUploadNameExist()) {
                            echo '1;'.$sessionService->getUploadName();
                            $sessionService->removeUpload();
                            exit;

                    }
	
                    if($sessionService->isUploadErrorExist()) {
                            echo '0;'.$sessionService->getUploadError();
                            $sessionService->removeUpload();
                            exit;
                    }		
	 	}
	    				
		if(isset($_FILES['myfile']) && isset($output_dir) && is_dir($output_dir)) {		
			if ($_FILES['myfile']["error"] > 0) {
		    	$error = $_FILES["file"]["error"];	
		    	echo '0;'.$error;
				$sessionService->setUploadError($error);
		    } else {
				// A list of permitted file extensions
				$allowed = $this->container->getParameter('extension_allowed');	
				$extension = pathinfo($_FILES['myfile']['name'], PATHINFO_EXTENSION);
						
				if(!in_array(strtolower($extension), $allowed)){
					echo '0;'.$this->get('translator')->trans('create_connector.upload_error_ext');
					exit;
				}
					
				$name_without_space = str_replace(' ', '_', $_FILES['myfile']['name']);	
				$new_name = time().'_'.$name_without_space;
				
				if(move_uploaded_file($_FILES['myfile']['tmp_name'],$output_dir. $new_name)) {
					echo "1;".$this->get('translator')->trans('create_connector.upload_success').' : '.$new_name;
                                        $sessionService->setUploadName($new_name);					
					exit;
				}
				else {
					echo '0;'.$this->get('translator')->trans('create_connector.upload_error');
					exit;
				}	
			exit;
			}
		} else {
			
		    	return $this->render('RegleBundle:Connector:upload.html.twig',array( 'solution' => $solution )
			);	
		}
    		
	} // Rev 1.1.1 --------------------------
	 
	// CREATION D UN CONNECTEUR LISTE
    public function createAction()
    {	
		$sessionService = $this->get('myddleware_session.service');
		
		$em = $this->getDoctrine()->getManager();
		$solution = $em->getRepository('RegleBundle:Solution')
					   ->solutionActive();
		$lstArray = array();			   
		if($solution) {			
			foreach ($solution as $s) {
				$lstArray[$s->getName()] = ucfirst($s->getName());
			}
		}					   
					   				   
		$lst_solution = tools::composeListHtml($lstArray,$this->get('translator')->trans('create_rule.step1.list_empty'));	
		$sessionService->setConnectorAnimation(false);
		$sessionService->setConnectorAddMessage('list');       
               
        return $this->render('RegleBundle:Connector:index.html.twig',array(
			'solutions'=> $lst_solution )
		);
    }
						
	// CREATION D UN CONNECTEUR
	public function connectorInsertAction(Request $request) {
		/* @var $sessionService SessionService */
		$sessionService = $this->get('myddleware_session.service');    
		$type = '';	
		
		$solution = $this->getDoctrine()
							->getManager()
							->getRepository('RegleBundle:Solution')
							->findOneByName($sessionService->getParamConnectorSourceSolution());

		$connector = new Connector();
		$connector->setSolution($solution);

        if( $connector->getSolution() !=null ){
            $fieldsLogin = $this->container->get('myddleware_rule.' . $connector->getSolution()->getName())->getFieldsLogin();
        }else{
            $fieldsLogin = [];
        }

		$form = $this->createForm(ConnectorType::class,$connector,array(
			'method'    => 'PUT',
			'attr' =>  array('fieldsLogin' => $fieldsLogin, 'secret' => $this->container->getParameter('secret'))
		));
                
		if($request->getMethod()=='POST' && $sessionService->isParamConnectorExist()) {		
			try {
				$form->handleRequest($request);
                $form->submit($request->request->get($form->getName()));
				if($form->isValid()){
					$solution = $connector->getSolution();
					$multi = $solution->getSource() + $solution->getTarget();
					if($sessionService->getConnectorAnimation()){
					// animation add connector
						$type = $sessionService->getParamConnectorAddType();
						// si la solution ajouté n'existe pas dans la page en cours on va la rajouter manuellement
						$solution = $sessionService->getParamConnectorSourceSolution();
						if( !in_array($solution, json_decode($sessionService->getSolutionType($type))) ) {
							$sessionService->setParamConnectorValues($type.';'.$solution.';'.$multi.';'.$solution->getId());
						}
					}

					// On récupére l'EntityManager
					$em = $this->getDoctrine()->getManager();

					$connectorParams = $connector->getConnectorParams();
					$connector->setConnectorParams(null);
					$connector->setNameSlug($connector->getName());
					$connector->setDateCreated(new \DateTime);
					$connector->setDateModified(new \DateTime);
					$connector->setCreatedBy( $this->getUser()->getId() );
					$connector->setModifiedBy( $this->getUser()->getId() );
					$connector->setDeleted(0);

					$em->persist($connector);
					$em->flush();

					foreach ($connectorParams as $key => $cp) {
						$cp->setConnector($connector);
						$em->persist($cp);
						$em->flush();
					}

					$sessionService->removeConnector();
					if(
							!empty($sessionService->getConnectorAddMessage())
						&&  $sessionService->getConnectorAddMessage() == 'list'
					) {
						$sessionService->removeConnectorAdd();
						return $this->redirect($this->generateUrl('regle_connector_list'));
					}
					else { // animation
						$message = '';
						if (!empty($sessionService->getConnectorAddMessage())) {
							$message = $sessionService->getConnectorAddMessage();
						}
						$sessionService->removeConnectorAdd();
						return $this->render('RegleBundle:Connector:createout_valid.html.twig',array(
							   'message' => $message,
							   'type' => $type
							)
						);
					}
				} else {
					dump($form); die();
					return $this->redirect($this->generateUrl('regle_connector_list'));
				}//-----------
			}
			catch(Exception $e) {			
				throw $this->createNotFoundException('Error');
			}		
		}
		else {		
			throw $this->createNotFoundException('Error');
		}		
	}

	// LISTE DES CONNECTEURS
	public function connectorListAction($page) {
		
		try {
		// ---------------	    
		    $em = $this->getDoctrine()->getManager();
			$compact['nb'] = 0;	
			
			// Detecte si la session est le support ---------
			$permission =  $this->get('myddleware.permission');
			// Detecte si la session est le support ---------			
						
			$compact = $this->nav_pagination(array(
				'adapter_em_repository' => $em->getRepository('RegleBundle:Connector')
											  ->findListConnectorByUser( $permission->isAdmin($this->getUser()->getId()), $this->getUser()->getId() ),
				'maxPerPage' => $this->container->getParameter('pager'),
				'page' => $page
			));	
			
			// Si tout se passe bien dans la pagination
			if( $compact ) {
				
				// Si aucun connecteur
				if( $compact['nb'] < 1 && !intval($compact['nb'])) {
					$compact['entities'] = '';
					$compact['pager'] = '';				
				}
				
			 	return $this->render('RegleBundle:Connector:list.html.twig',array(
				       'nb' => $compact['nb'],
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
			throw $this->createNotFoundException('Error : '.$e);
		}
	}
	
	// SUPPRESSION DU CONNECTEUR
	public function connectorDeleteAction(Request $request, $id) {		
		$session = $request->getSession();
		if(isset($id)) {
			// Check permission
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
			
			// Get the connector using its id
			$connector = $this->getDoctrine()
						 ->getManager()
						 ->getRepository('RegleBundle:Connector')
						 ->findOneBy( $list_fields_sql );	
					 
			if($connector === null) {
				return $this->redirect($this->generateUrl('regle_connector_list'));	
			}			 
			try{
				// Check if a rule uses this connector (source and target)
				$rule = $this->getDoctrine()
							 ->getManager()
							 ->getRepository('RegleBundle:Rule')
							  ->findOneBy(array(
												'connectorTarget' => $connector,
												'deleted' => 0
										  ));
				if (empty($rule)) {
					$rule = $this->getDoctrine()
							 ->getManager()
							 ->getRepository('RegleBundle:Rule')
							  ->findOneBy(array(
												'connectorSource' => $connector,
												'deleted' => 0
										  ));
				}	
				// Error message in case a rule using this connector exists
				if (!empty($rule)) {			
					$session->set('error', array($this->get('translator')->trans('error.connector.remove_with_rule').' '.$rule->getName()));
				} else {
					// Flag the connector as deleted
					$connector->setDeleted(1);
					$this->getDoctrine()->getManager()->persist($connector);
					$this->getDoctrine()->getManager()->flush();
				}
			} catch (\Doctrine\DBAL\DBALException $e) {
				$session->set('error', array($e->getPrevious()->getMessage()));
			} 
			return $this->redirect($this->generateUrl('regle_connector_list'));	
		}
	}

	// FICHE D UN CONNECTEUR
	public function connectorOpenAction(Request $request, $id) {     
		// On récupére l'EntityManager
		$em = $this->getDoctrine()->getManager();
                
		$qb = $em->getRepository('RegleBundle:Connector')->createQueryBuilder('c');
		$qb->select('c','cp')->leftjoin('c.connectorParams','cp');
		
		// Detecte si la session est le support ---------
		$permission = $this->get('myddleware.permission');

		if ($permission->isAdmin($this->getUser()->getId())) {
			$qb->where('c.id =:id AND c.deleted = 0')->setParameter('id',$id); 
		} else {
			$qb->where('c.id =:id and c.createdBy =:createdBy AND c.deleted = 0')->setParameter(['id' => $id, 'createdBy' => $this->getUser()->getId()]);
		}
		// Detecte si la session est le support ---------			
		// Infos du connecteur
		$connector = $qb->getQuery()->getOneOrNullResult();
   
		if (!$connector) {
			throw $this->createNotFoundException("This connector doesn't exist");
		}
              
		if ($permission->isAdmin($this->getUser()->getId())) {
			$qb->where('c.id =:id')->setParameter('id',$id); 
		} else {
			$qb->where('c.id =:id and c.createdBy =:createdBy')->setParameter(['id' => $id, 'createdBy' => $this->getUser()->getId()]);
		}
		// Detecte si la session est le support ---------			
		// Infos du connecteur
		$connector = $qb->getQuery()->getOneOrNullResult();             
	   
		if (!$connector) {
			throw $this->createNotFoundException("This connector doesn't exist");
		}
              
		// Create connector form
		// $form = $this->createForm(new ConnectorType($this->container), $connector, ['action' => $this->generateUrl('connector_open', ['id' => $id])]);

        if( $connector->getSolution() !=null ){
            $fieldsLogin = $this->container->get('myddleware_rule.' . $connector->getSolution()->getName())->getFieldsLogin();
        }else{
            $fieldsLogin = [];
        }

		$form = $this->createForm(ConnectorType::class,$connector, array(
			'action'    => $this->generateUrl('connector_open', ['id' => $id]),
			'method'    => 'PUT',
			'attr' =>  array('fieldsLogin' => $fieldsLogin, 'secret' => $this->container->getParameter('secret'))
		));

		// If the connector has been changed
		if($request->getMethod()=='PUT') {
			try {					   						   
				$form->handleRequest($request);
				// SAVE
				$params = $connector->getConnectorParams();			
				// SAVE PARAMS CONNECTEUR	
				if(count($params) > 0) {									   
					$em->persist($connector); 
					$em->flush(); 
					return $this->redirect($this->generateUrl('regle_connector_list'));					
				}
				else {
					return new Response(0);
				}			
			}
			catch(\Exception $e) {				
				return new Response($e->getMessage());
			}
		}
		// Display the connector
		else {		
	        return $this->render('RegleBundle:Connector:edit/fiche.html.twig',array( 
                                'connector' => $connector,
				'form' => $form->createView())
			);			
		}
		
	}	

	/* ******************************************************
	 * ANIMATION
	 ****************************************************** */
	// LISTE DES CONNECTEURS POUR ANIMATION
	public function connectorListSolutionAction(Request $request) {
		
		$id = $request->get('id',null);
                
		if($id !=null) {
							
			// Detecte si la session est le support ---------
			$permission =  $this->get('myddleware.permission');
			
			if( $permission->isAdmin($this->getUser()->getId()) ) {
				$list_fields_sql = array(	'solution' => (int)$id,
											'deleted' => 0
										);
			}
			else {
				$list_fields_sql = 
					array(	'solution' => (int)$id,
							'deleted' => 0,
							'createdBy' => $this->getUser()->getId()
				);
			}
			// Detecte si la session est le support ---------			
							
			$em = $this->getDoctrine()->getManager();
			$listConnector = $em->getRepository('RegleBundle:Connector')
								 ->findBy( $list_fields_sql );
			
			$lstArray = array();			   			
			foreach ($listConnector as $c) {
				$lstArray[$c->getId()] = ucfirst($c->getName());
			}
			$lst = tools::composeListHtml($lstArray, $this->get('translator')->trans('create_rule.step1.choose_connector'));
			return new Response($lst);	
		}
		else {
			return new Response('');
		}		
	}	 

	// CREATION D UN CONNECTEUR LISTE animation
    public function createOutAction($type)
    {           
		/* @var $sessionService SessionService */
		$sessionService = $this->get('myddleware_session.service');

		$em = $this->getDoctrine()->getManager();
		
		$solution = $em->getRepository('RegleBundle:Solution')
				   ->solutionConnectorType($type);

		$lstArray = array();			   
		if($solution) {			
			foreach ($solution as $s) {
				$lstArray[$s->getName()] = ucfirst($s->getName());
			}
		}					   
					   				   
		$lst_solution = tools::composeListHtml($lstArray,$this->get('translator')->trans('create_rule.step1.list_empty'));

		$sessionService->setConnectorAddMessage($this->get('translator')->trans('create_rule.step1.connector'));
		$sessionService->setParamConnectorAddType(strip_tags($type));
		$sessionService->setConnectorAnimation(true);
                
              			  
        return $this->render('RegleBundle:Connector:createout.html.twig',array(
			'solutions'=> $lst_solution
			)
		);
    }

	// RETOURNE LES INFOS POUR L AJOUT D UN CONNECTEUR EN JQUERY	
	public function connectorInsertSolutionAction() {
            
		/* @var $sessionService SessionService */
		$sessionService = $this->get('myddleware_session.service');

		if($sessionService->isConnectorValuesExist()) {
			$values = $sessionService->getConnectorValues();	
			$sessionService->removeConnectorValues();
			return new Response($values);
		}
		else {
			return new Response(0);
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
