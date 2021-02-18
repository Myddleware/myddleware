<?php

namespace App\Controller;

use Exception;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class InstallUserSetupController extends AbstractController
{


/**
     * @Route("install/user/setup", name="install_user_setup")
     */
    public function setupUserAction(Request $request){
        try {   
            $user = new User();
            $em = $this->getDoctrine()->getManager();
            $form = $this->createForm(CreateUserType::class, $user);
            $form->handleRequest($request);
            
            //persist form data to database
            if ($form->isSubmitted() && $form->isValid()) {        
                $user->addRole('ROLE_ADMIN');
                // allows user to login to Myddleware
                $user->setEnabled(true);
                $em->persist($user);
                $em->flush();
                // return $this->redirect($this->generateUrl('LoginBundleUser'));
            }

        }catch(Exception $e){
            $message = $e->getMessage();
             // Retrieve flashbag from the controller
             $flashbag = $this->get('session')->getFlashBag();
              // Give confirmation to the user that the form has been sent
              $flashbag->add("error", $message);
        }

        return $this->render('install_user_setup/user_setup.html.twig',
                                                        array(
                                                            'form' => $form->createView(),
                                                        )
                            );
    }

}