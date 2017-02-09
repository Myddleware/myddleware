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

class ConnectorController extends Controller
{

	/* ******************************************************
	 * CONNECTOR
	 ****************************************************** */

	// CALLBACK POUR LES APIS
	public function callBackAction() { // REV 1.1.1
		try {				
			$request = $this->get('request');
			$session = $request->getSession();
			$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
			// We always add data again in session because these data are removed after the call of the get
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		
			// Nom de la solution
			if(!isset($myddlewareSession['param']['myddleware']['connector']['solution']['callback'])) {
				return new Response('');
			}
			else {
				$solution_name = $myddlewareSession['param']['myddleware']['connector']['solution']['callback'];			
			}
					
			$solution = $this->get('myddleware_rule.'.$solution_name);
						
			// ETAPE 2 : Récupération du retour de la Popup en GET et génération du token final
			if(isset($_GET[$solution->nameFieldGet])) {					
				$solution->init($myddlewareSession['param']['connector']['source']); // Affecte les variables
	
				$solution->setAuthenticate($_GET[$solution->nameFieldGet]);	

				if($solution->refresh_token) { // Si RefreshToken
					$myddlewareSession['param']['connector']['source']['refreshToken'] = $solution->getRefreshToken();
				}	
				
				// Save the session befor calling login function because session could be used in this function
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
				$solution->login( $myddlewareSession['param']['connector']['source'] ); 
				$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
				// We always add data again in session because these data are removed after the call of the get
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
			
				// Sauvegarde des 2 jetons en session afin de les enregistrer dans les paramètres du connecteur
				$myddlewareSession['param']['connector']['source']['token'] = $solution->getAccessToken();	
				if($solution->refresh_token) { // Si RefreshToken	
					$myddlewareSession['param']['connector']['source']['refreshToken'] = $solution->getRefreshToken();;
				}
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
				return $this->redirect($this->generateUrl('connector_callback'));
			}	
					
			// SOLUTION AVEC POPUP ---------------------------------------------------------------------
			// ATAPE 1 si la solution utilise un callback et le js
			if( 
					$solution->callback 
				&& $solution->js 
			) {		
				if(!empty($myddlewareSession['param']['connector']['source']))  {
					$params_connexion_solution = $myddlewareSession['param']['connector']['source'];
				}
				if(!empty($myddlewareSession['param']['connector']['source']['token']))  {
					$params_connexion_solution['token'] = $myddlewareSession['param']['connector']['source']['token'];
				}
				if(!empty($myddlewareSession['param']['connector']['source']['refreshToken']))  {
					$params_connexion_solution['refreshToken'] = $myddlewareSession['param']['connector']['source']['refreshToken'];
				}
	
				$solution->init($params_connexion_solution); // Affecte les variables
				
				// Save the session befor calling login function because session could be used in this function
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
				$error = $solution->login( $params_connexion_solution );
				$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
				// We always add data again in session because these data are removed after the call of the get
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
				
				// Gestion des erreurs retour méthode login
				if(!empty($error)) {
					$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
					return new Response('');
				}
									
				// Autorisation de l'application 
				if(!empty($_POST['solutionjs']) ) {				
					// Déclenche la pop up															
					if(!empty($_POST['detectjs'])) {							
						$callbackUrl = $solution->getCreateAuthUrl((isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$this->generateUrl('connector_callback'));								
						if(!empty($myddlewareSession['param']['connector']['source']['token'])) {			
							$solution->setAccessToken($myddlewareSession['param']['connector']['source']['token'] );
						}					
						// Redirection vers une autorisation manuel			
						else {	
							$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
							return new Response($solution->js.';'.urldecode($callbackUrl)); // Url de l'authentification prêt à être ouvert en popup
						}
					
						// 1er test de validité du Token
						$testToken = $solution->testToken();

						if(!empty($testToken['error']['code'])) {	
							if($testToken['error']['code'] == 401 || $testToken['error']['code'] == 404) {
								$myddlewareSession['param']['connector']['source']['token'] = NULL;
								$url = $solution->getCreateAuthUrl($callbackUrl);	
								$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
								return new Response($solution->js.';'.urldecode($url)); // Url de l'authentification prêt à être ouvert en popup						
							}
						}	
						$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
						return new Response($solution->js.';'.$callbackUrl);	// tentative de connexion												

					} // detect js
					
					if(isset($myddlewareSession['param']['connector']['source']['token'])) {
						$solution->setAccessToken($myddlewareSession['param']['connector']['source']['token'] );				
					} 
					// 2nd Test la validité du token
					$testToken = $solution->testToken();
					
					// Erreur sans ouvrir la popup
					if($testToken['error']['code'] == 404 || $testToken['error']['code'] === 0) {
						$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
						return new Response("2;".$testToken['error']['message']); // Error Not Found
					}
					
					if(isset($testToken['error']['code']) && !empty($testToken['error']['code']) && !empty($testToken['error']['message'])) {	
						$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
						return new Response($testToken['error']['code'].';'.$testToken['error']['message']);
					}	
									
					if(isset($myddlewareSession['param']['connector']['source']['token'])) {				
						if(isset($testToken['error']['message']) && !empty($testToken['error']['message'])) {
							$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
							return new Response($testToken['error']['message'] . ';'); // Erreur de connexion
						}
						else {
							$solution->connexion_valide = true;
							$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
							return new Response(1); // Connexion réussi
						}
					}		
				}
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
				return new Response('<script type="text/javascript" language="javascript">window.close();</script>'); // Ferme la popup automatiquement			
			} // fin 
			// SOLUTION AVEC POPUP ---------------------------------------------------------------------
			else {
				throw new \Exception('Failed load class');
			}
		}
		catch (\Exception $e) {
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
			return new Response($e->getMessage());
		}
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
		return new Response('');
	} 

	// Contrôle si le fichier upload est valide puis le déplace
    public function uploadAction($solution) // REV 1.1.0
    {
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		if( isset($solution) ) {
			if(in_array(trim($solution), array('sagecrm','sapcrm','sap'))){
				$output_dir = __DIR__."/../Custom/Solutions/".trim($solution)."/wsdl/";
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
		}
		

		
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
	     	if(isset($myddlewareSession['param']['myddleware']['upload']['name'])) {
	    		echo '1;'.$myddlewareSession['param']['myddleware']['upload']['name'];
				unset($myddlewareSession['param']['myddleware']['upload']);
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
				exit;
	    	}
	
	    	if(isset($myddlewareSession['param']['myddleware']['upload']['error'])) {
	    		echo '0;'.$myddlewareSession['param']['myddleware']['upload']['error'];
				unset($myddlewareSession['param']['myddleware']['upload']);
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
				exit;
	    	}		
	 	}
	    				
		if(isset($_FILES['myfile']) && isset($output_dir) && is_dir($output_dir)) {		
			if ($_FILES['myfile']["error"] > 0) {
		    	$error = $_FILES["file"]["error"];	
		    	echo '0;'.$error;
				$myddlewareSession['param']['myddleware']['upload']['error'] = $error;
		    } else {
				// A list of permitted file extensions
				$allowed = $this->container->getParameter('extension_allowed');	
				$extension = pathinfo($_FILES['myfile']['name'], PATHINFO_EXTENSION);
						
				if(!in_array(strtolower($extension), $allowed)){
					echo '0;'.$this->get('translator')->trans('create_connector.upload_error_ext');
					$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
					exit;
				}
					
				$name_without_space = str_replace(' ', '_', $_FILES['myfile']['name']);	
				$new_name = time().'_'.$name_without_space;
				
				if(move_uploaded_file($_FILES['myfile']['tmp_name'],$output_dir. $new_name)) {
					echo "1;".$this->get('translator')->trans('create_connector.upload_success').' : '.$new_name;
					$myddlewareSession['param']['myddleware']['upload']['name'] = $new_name;	
					$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);					
					exit;
				}
				else {
					echo '0;'.$this->get('translator')->trans('create_connector.upload_error');		
					$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
					exit;
				}
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
			exit;
			}
		} else {
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
		    	return $this->render('RegleBundle:Connector:upload.html.twig',array( 'solution' => $solution )
			);	
		}
    		
	} // Rev 1.1.1 --------------------------
	 
	// CREATION D UN CONNECTEUR LISTE
    public function createAction()
    {	
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	

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
		$myddlewareSession['param']['myddleware']['connector']['animation'] = false;		
		$myddlewareSession['param']['myddleware']['connector']['add']['message'] = 'list'; 
		// $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
        return $this->render('RegleBundle:Connector:index.html.twig',array(
			'solutions'=> $lst_solution )
		);
    }
						
	// CREATION D UN CONNECTEUR
	public function connectorInsertAction() {
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		$type = '';	
		
		if($request->getMethod()=='POST' && isset($myddlewareSession['param']['connector'])) {		
			try {
				// Récupère l'id d'une solution
				$solution = $this->getDoctrine()
								 ->getManager()
								 ->getRepository('RegleBundle:Solution')
								 ->findOneByName($myddlewareSession['param']['connector']['source']['solution']);
										 
				$multi = $solution->getSource() + $solution->getTarget();			 		
				
				if(!empty($myddlewareSession['param']['myddleware']['connector']['animation'])) {
					// animation add connector
					$type = $myddlewareSession['param']['myddleware']['connector']['add']['type'];
					// si la solution ajouté n'existe pas dans la page en cours on va la rajouter manuellement
					if( !in_array($myddlewareSession['param']['connector']['source']['solution'], json_decode($myddlewareSession['param']['myddleware']['connector']['solution'][$type])) ) {
						$myddlewareSession['param']['myddleware']['connector']['values'] = $type.';'.$myddlewareSession['param']['connector']['source']['solution'].';'.$multi.';'.$solution->getId();
					}					
				}
						
				// On récupére l'EntityManager
				$em = $this->getDoctrine()
						   ->getManager();
	
				// Création d'un connecteur
				$unConnector = new Connector();	 
				$unConnector->setSolution( $solution );
				$unConnector->setName( $this->getRequest()->request->get('label') );
				$unConnector->setNameSlug( $this->getRequest()->request->get('label') );
				$unConnector->setDateCreated(new \DateTime);
				$unConnector->setDateModified(new \DateTime);
				$unConnector->setCreatedBy( $this->getUser()->getId() );
				$unConnector->setModifiedBy( $this->getUser()->getId() );		
	
				$em->persist($unConnector);
				$em->flush(); 		
					
				// Generate object to encrypt data
				$encrypter = new \Illuminate\Encryption\Encrypter(substr($this->container->getParameter('secret'),-16));
				// Insert les paramètres de connexion du connecteur
				foreach ( $myddlewareSession['param']['connector']['source'] as $connexion => $val ) {									
					if( $connexion != "solution" ) {
						$unConnectoParams = new ConnectorParam();		
						$unConnectoParams->setConnector( $unConnector->getId() );
						$unConnectoParams->setName( $connexion );
						$unConnectoParams->setValue( $encrypter->encrypt($val) );
						$em->persist($unConnectoParams);
						$em->flush(); 
					}
				}
				
				unset($myddlewareSession['param']['connector']);
				
				if(
						!empty($myddlewareSession['param']['myddleware']['connector']['add']['message'])
					&&  $myddlewareSession['param']['myddleware']['connector']['add']['message'] == 'list'
				) {
					unset($myddlewareSession['param']['myddleware']['connector']['add']);
					$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
					return $this->redirect($this->generateUrl('regle_connector_list'));	
				}
				else { // animation
					$message = '';
					if (!empty($myddlewareSession['param']['myddleware']['connector']['add']['message'])) {
						$message = $myddlewareSession['param']['myddleware']['connector']['add']['message'];
					}
					unset($myddlewareSession['param']['myddleware']['connector']['add']);		
					$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
					return $this->render('RegleBundle:Connector:createout_valid.html.twig',array(
						   'message' => $message,
						   'type' => $type
						)
					);						
				}
						
				//-----------
			}
			catch(Exception $e) {
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
				throw $this->createNotFoundException('Error');
			}		
		}
		else {
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
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
	public function connectorDeleteAction($id) {

		if(isset($id)) {
			
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
						
			// Récupère le connecteur en fonction de son id
			$connector = $this->getDoctrine()
                         ->getManager()
                         ->getRepository('RegleBundle:Connector')
                         ->findOneBy( $list_fields_sql );	
						 
			if($connector === null) {
				return $this->redirect($this->generateUrl('regle_connector_list'));	
			}			 

		    // On récupére l'EntityManager
		    $em = $this->getDoctrine()
		               ->getManager();	
					   		
			$connector_params = $this->getDoctrine()
                     ->getManager()
                     ->getRepository('RegleBundle:ConnectorParam')
                     ->findByConnector( $id );	
 			
			if($connector_params) {
				foreach ( $connector_params as $connector_param ) {
					$em->remove($connector_param);
					$em->flush();
				}				
			}
			
			$em->remove($connector);
			$em->flush();			
			
			return $this->redirect($this->generateUrl('regle_connector_list'));	
		}
	}

	// FICHE D UN CONNECTEUR
	public function connectorOpenAction($id) {
		$request = $this->get('request');
		// On récupére l'EntityManager
		$em = $this->getDoctrine()->getManager();
		// If the connector has been changed
		if($request->getMethod()=='POST') {
			// SAVE
			try {					   						   
				// Detecte si la session est le support ---------
				$permission =  $this->get('myddleware.permission');
				if( $permission->isAdmin($this->getUser()->getId()) ) {
					$list_fields_sql = 
						array('id' => (int)$id
					);			
				}
				else {
					$list_fields_sql = 
						array(
						'id' => (int)$id,
						'createdBy' => $this->getUser()->getId()
					);				
				}
				// Detecte si la session est le support ---------						   
			 
				// SAVE NOM CONNECTEUR
				$connector = $em->getRepository('RegleBundle:Connector')
		                        ->findBy( $list_fields_sql );						   
				$connector[0]->setName( $_POST['nom'] );	
			    $em->persist($connector[0]);
			    $em->flush();					
				
				// SAVE PARAMS CONNECTEUR		   						   
				if(count($_POST['params']) > 0) {
					// Generate object to encrypt data
					$encrypter = new \Illuminate\Encryption\Encrypter(substr($this->container->getParameter('secret'),-16));
					foreach($_POST['params']  as $p) {
						$param = $em->getRepository('RegleBundle:ConnectorParam')
			                        ->findOneBy( array(
									    	'id' => (int)$p['id']
									    )
								);					
						$param->setValue( $encrypter->encrypt($p['value']) );	
					    $em->persist($param);
					    $em->flush();											
					}	
					// In case of Oath 2, the token can exist and is not in the form so not is the POST too. So we check if the token is existing
					$session = $request->getSession();
					$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
					// We always add data again in session because these data are removed after the call of the get
					$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
					if (
							!empty($myddlewareSession['param']['myddleware']['connector']['solution']['callback']) // Confirm Oath 2
						 &&	!empty($myddlewareSession['param']['connector']['source']['token'])
					) {
						// Get the param with the token_get_all
						$connectorParam = $em->getRepository('RegleBundle:ConnectorParam')->findOneBy( array(
														'connector' => $connector[0],
														'name' => 'token'
													));				
						// If not connector param for the token, we create one (should never happen)							
						if (empty($connectorParam)) {
							$connectorParam = new ConnectorParam();		
							$connectorParam->setConnector($connector[0]->getId());
							$connectorParam->setName('token');
						}
						// Save the token in the connector param
						$connectorParam->setValue($encrypter->encrypt($myddlewareSession['param']['connector']['source']['token']));
						$em->persist($connectorParam);
						$em->flush(); 				
					}
					return new Response($this->generateUrl('regle_connector_list'));					
				}
				else {
					return new Response(0);
				}		   
			}
			catch(Exception $e) {
				return new Response($e->getMessage());
			}
			// SAVE
		}
		// Display the connector
		else {
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
		
			// Infos du connecteur
			$connectorP = $em->getRepository('RegleBundle:ConnectorParam')
	                        ->findByConnector( $id );
			// Infos du connecteur
			$connector = $em->getRepository('RegleBundle:Connector')
	                        ->findBy( $list_fields_sql );	
						
			if(count($connector) == 0) {
				return $this->redirect($this->generateUrl('regle_connector_list'));
			}
									   
			if( isset($connectorP) && count($connectorP > 0) ) {				
				$connector_params = array();
				$connector_params['label'] = $connector[0]->getName();
				
				$connector_params['solution']['name'] = $connector[0]->getSolution()->getName();
				$connector_params['solution']['id'] = $connector[0]->getSolution()->getId();
				foreach ($connectorP as $connectorObj) {	
					$connector_params['id'] = $connectorObj->getConnector();
					$connector_params['params'][$connectorObj->getName()]['value'] = $this->decrypt_params($connectorObj->getValue());	
					$connector_params['params'][$connectorObj->getName()]['id'] = $connectorObj->getId();
				}
	
			}	
			
			$solution = $this->get('myddleware_rule.'.$connector_params['solution']['name']);
			
			foreach ($solution->getFieldsLogin() as $k => $v) {
			
				$connector_params['params'][$v['name']]['type'] = $v['type'];
			}

	        return $this->render('RegleBundle:Connector:edit/fiche.html.twig',array( 
				'connector_params' => $connector_params)
			);			
		}
		
	}	

	/* ******************************************************
	 * ANIMATION
	 ****************************************************** */
	// LISTE DES CONNECTEURS POUR ANIMATION
	public function connectorListSolutionAction() {
		
		if(isset($_POST['id'])) {
							
			// Detecte si la session est le support ---------
			$permission =  $this->get('myddleware.permission');
			
			if( $permission->isAdmin($this->getUser()->getId()) ) {
				$list_fields_sql = array('solution' => (int)$_POST['id']);
			}
			else {
				$list_fields_sql = 
					array('solution' => (int)$_POST['id'],
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
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
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
		$myddlewareSession['param']['myddleware']['connector']['add']['message'] = $this->get('translator')->trans('create_rule.step1.connector');
		$myddlewareSession['param']['myddleware']['connector']['add']['type'] = strip_tags($type);
		$myddlewareSession['param']['myddleware']['connector']['animation'] = true;
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);			  
        return $this->render('RegleBundle:Connector:createout.html.twig',array(
			'solutions'=> $lst_solution
			)
		);
    }

	// RETOURNE LES INFOS POUR L AJOUT D UN CONNECTEUR EN JQUERY	
	public function connectorInsertSolutionAction() {
		$request = $this->get('request');
		$session = $request->getSession();
		$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
		// We always add data again in session because these data are removed after the call of the get
		$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
		if(isset($myddlewareSession['param']['myddleware']['connector']['values'])) {
			$values = $myddlewareSession['param']['myddleware']['connector']['values'];	
			unset($myddlewareSession['param']['myddleware']['connector']['values']);		
			$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
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


	// Décrypte les paramètres de connexion d'une solution
	private function decrypt_params($tab_params) {		
		// Instanciate object to decrypte data
		$encrypter = new \Illuminate\Encryption\Encrypter(substr($this->container->getParameter('secret'),-16));
		if( is_array($tab_params) ) {
			$return_params = array();
			foreach ($tab_params as $key => $value) {				
				if(is_string($value)) {
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
