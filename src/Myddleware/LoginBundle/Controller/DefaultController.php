<?php

namespace Myddleware\LoginBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Myddleware\RegleBundle\Solutions\crm;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {				
		$session = $request->getSession();	
        $csrfToken = $this->container->has('form.csrf_provider')
        ? $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate')
        : null;

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } elseif (null !== $session && $session->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = '';
        }

        if ($error) {
            // TODO: this is a potential security risk (see http://trac.symfony-project.org/ticket/9523)
            $error = $error->getMessage();
        }
        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get(SecurityContext::LAST_USERNAME);
		
		$securityContext = $this->get('security.context');
		if( $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') ){
			return $this->redirect( $this->generateUrl('regle_panel') );				 
		}	
		else {
					
			$this->calculBan( $lastUsername );	
				
			$attempt = ((isset($_SESSION['myddleware']['secure'][$lastUsername]['attempt'])) ? $_SESSION['myddleware']['secure'][$lastUsername]['attempt']  : 0 );
			$remaining = ((isset($_SESSION['myddleware']['secure'][$lastUsername]['remaining'])) ? $_SESSION['myddleware']['secure'][$lastUsername]['remaining']  : 0 );

			return $this->render('LoginBundle:Default:index.html.twig',array(
				'last_username' => $lastUsername,
				'error'         => $error,
				'csrf_token' => $csrfToken,
				'attempt' => $attempt,
				'remaining' => $remaining
			));				
				
		}
    }

	private function calculBan($lastUsername) {
		
		if(isset($_SESSION['myddleware']['secure'][$lastUsername]['time'])) {
			if(time() > $_SESSION['myddleware']['secure'][$lastUsername]['time']) {
				$_SESSION['myddleware']['secure'][$lastUsername]['attempt'] = 1;
			}
			else {
	
				// RESTE X MINUTES AVANT LA FUTUR CONNEXION
				$date1   = time();
				$date2 = $_SESSION['myddleware']['secure'][$lastUsername]['time'];
				$diff  = abs($date1 - $date2);
	
			    $diff = abs($date1 - $date2); // abs pour avoir la valeur absolute, ainsi éviter d'avoir une différence négative
			    $remaining = array();
			 
			    $tmp = $diff;
			    $remaining['second'] = $tmp % 60;
			 
			    $tmp = floor( ($tmp - $remaining['second']) /60 );
			    $remaining['minute'] = $tmp % 60;
			 
			    $tmp = floor( ($tmp - $remaining['minute'])/60 );
			    $remaining['hour'] = $tmp % 24;
			 
			    $tmp = floor( ($tmp - $remaining['hour'])  /24 );
			    $remaining['day'] = $tmp;
				
				$_SESSION['myddleware']['secure'][$lastUsername]['remaining'] = $remaining;
			}			
		}	
	}

	public function verifAccountAction(Request $request) {
		
		try {
			if ($request->isMethod('POST')) {

				$lastUsername = trim($this->getRequest()->request->get('login'));
				
				// contrôle des tentatives
				// si le nombre de tentative n'existe pas on affecte 0
				if(!isset($_SESSION['myddleware']['secure'][$lastUsername]['attempt'])) {
					$_SESSION['myddleware']['secure'][$lastUsername]['attempt'] = 1;
				}
				else { // si existe on ajoute +1
					$_SESSION['myddleware']['secure'][$lastUsername]['attempt']++;
				}
				
				// si le nombre de tentative est supérieur à 5 alors on ajoute une date de contrôle			
				if($_SESSION['myddleware']['secure'][$lastUsername]['attempt'] > 4 ) {
					
					if(!isset($_SESSION['myddleware']['secure'][$lastUsername]['time'])) {
						$_SESSION['myddleware']['secure'][$lastUsername]['time'] = strtotime("+15 minutes", time());
					}
					else {
						$this->calculBan( $lastUsername );	
					} 
				}	
	       		return new Response(1);
			}
			else {
				return new Response(0);
			}	
		}
		catch(Exception $e) {
			return new Response(0);		
		}
		
	}

}
