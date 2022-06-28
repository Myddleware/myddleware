<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {          
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        if($lastUsername != "")
        {
            return $this->redirectToRoute('admin_dashboard');
        }else{
            return $this->render('@EasyAdmin/page/login.html.twig', [
                'error' => $error,
                'last_username' => $lastUsername,
                // OPTIONAL parameters to customize the login form:

                // the string used to generate the CSRF token. If you don't define
                // this parameter, the login form won't include a CSRF token
                'csrf_token_intention' => 'authenticate',

                // the title visible above the login form (define this option only if you are
                // rendering the login template in a regular Symfony controller; when rendering
                // it from an EasyAdmin Dashboard this is automatically set as the Dashboard title)
                'page_title' => '<img src="build/images/logo/logo.png" alt="Myddleware">',

                // the URL users are redirected to after the login (default: '/admin')
                'target_path' => $this->generateUrl('admin_dashboard'),

                // the label displayed for the username form field (the |trans filter is applied to it)
                'username_label' => 'Your email address',

                // the label displayed for the password form field (the |trans filter is applied to it)
                'password_label' => 'Your password',

                // the label displayed for the Sign In form button (the |trans filter is applied to it)
                'sign_in_label' => 'Log in',

                // whether to enable or not the "forgot password?" link (default: false)
                'forgot_password_enabled' => true,

                // the path (i.e. a relative or absolute URL) to visit when clicking the "forgot password?" link (default: '#')
                'forgot_password_path' => $this->generateUrl('app_forgot_password_request'),

                // the label displayed for the "forgot password?" link (the |trans filter is applied to it)
                'forgot_password_label' => 'Forgot your password?',

                // whether to enable or not the "remember me" checkbox (default: false)
                'remember_me_enabled' => true,

                // whether to check by default the "remember me" checkbox (default: false)
                'remember_me_checked' => true,

                // the label displayed for the remember me checkbox (the |trans filter is applied to it)
                'remember_me_label' => 'Remember me',
            ]);
        }
    }
}


