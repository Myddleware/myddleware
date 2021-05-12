<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 *
 * This file is part of Myddleware.
 *
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

namespace Myddleware\RegleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use FOS\UserBundle\Event\FormEvent;

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Symfony\Component\HttpFoundation\Request;
use FOS\UserBundle\Event\FilterUserResponseEvent;

use Myddleware\RegleBundle\Classes\tools as MyddlewareTools;
use FOS\UserBundle\Controller\ChangePasswordController as BaseController;


use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AccountController extends BaseController
{

    /* ******************************************************
     * Account
     ****************************************************** */
    public function displayAccountAction()
    {
        $language = $this->container->getParameter('locale');
        return $this->render('RegleBundle:Account:index.html.twig', array(
            "locale" => $language
        ));
    }

    public function changeLocaleAction(Request $request)
    {
        try {

            $session = $request->getSession();

            if (isset($_POST['locale'])) {
                $locale = $_POST['locale'];
            } else {
                return Response("Something missing (parameter)");
            }
            $tools = new MyddlewareTools($this->get('logger'), $this->container, $this->get('database_connection'));
            if ($locale == "fr") {
                if ($this->container->getParameter('locale') != "fr") { // Si la langue est déjà en Français ne rien faire, logique
                    $tools->changeMyddlewareParameter(array('locale'), 'fr');
                }
            } else {
                if ($this->container->getParameter('locale') != "en") { // Si la langue est déjà en Anglais ne rien faire, logique
                    $tools->changeMyddlewareParameter(array('locale'), 'en');
                }
            }
            // Clear the cache to change the language
            $process = new \Symfony\Component\Process\Process('php ' . $this->container->get('kernel')->getRootDir() . '/console cache:clear --env=' . $this->container->get('kernel')->getEnvironment());
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \Symfony\Component\Process\Exception\ProcessFailedException($process);
            }
        } catch (Exception $e) {
            $session->set('error', array($this->get('translator')->trans('error.account.language_change') . $e->getMessage()));
        }
        return new Response("Success");
    }

    /**
     * Function for forms of my account
     * @param Request $request
     * @return null|RedirectResponse|Response
     */
    public function myAccountAction(Request $request)
    {
        $user = $this->getUser();

        /** @var $dispatcher EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');
        $dispatcherEdit = $this->get('event_dispatcher');
        $event = new GetResponseUserEvent($user, $request);

        $dispatcher->dispatch(FOSUserEvents::CHANGE_PASSWORD_INITIALIZE, $event);
        $dispatcherEdit->dispatch(FOSUserEvents::PROFILE_EDIT_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        /** @var $formFactory FactoryInterface */
        $formFactory = $this->get('fos_user.change_password.form.factory');
        $formFactoryEdit = $this->get('fos_user.profile.form.factory');

        $form = $formFactory->createForm();
        $form->setData($user);
        $form->handleRequest($request);

        $form_edit = $formFactoryEdit->createForm();
        $form_edit->setData($user);
        $form_edit->handleRequest($request);

        /**
         * edit form validation change password
         */
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var $userManager UserManagerInterface */
            $userManager = $this->get('fos_user.user_manager');

            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::CHANGE_PASSWORD_SUCCESS, $event);

            $userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                $url = $this->generateUrl('my_account'); // fos_user_profile_show
                $response = new RedirectResponse($url);
            }

            $dispatcher->dispatch(FOSUserEvents::CHANGE_PASSWORD_COMPLETED, new FilterUserResponseEvent($user, $request, $response));
            return $response;
        }
        /**
         * edit profile user
         */
        if ($form_edit->isSubmitted() && $form_edit->isValid()) {
            /** @var $userManager UserManagerInterface */
            $userManager = $this->get('fos_user.user_manager');

            $event = new FormEvent($form_edit, $request);
            $dispatcherEdit->dispatch(FOSUserEvents::PROFILE_EDIT_SUCCESS, $event);

            $userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                $url = $this->generateUrl('my_account');
                $response = new RedirectResponse($url);
            }

            $dispatcherEdit->dispatch(FOSUserEvents::PROFILE_EDIT_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

            return $response;
        }
        return $this->render('RegleBundle:Account:index.html.twig', array(
            "locale" => $this->container->getParameter('locale'),
            'form' => $form->createView(), // change password
            'form_edit' => $form_edit->createView() // form edit
        ));

    }

}
