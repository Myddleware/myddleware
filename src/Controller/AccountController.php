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

namespace App\Controller;

use App\Form\Type\ProfileFormType;
use App\Form\Type\ResetPasswordType;
use App\Manager\ToolsManager;
use App\Service\AlertBootstrapInterface;
use App\Service\UserManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/rule")
 */
class AccountController extends AbstractController
{
    /**
     * @var ToolsManager
     */
    private $toolsManager;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ParameterBagInterface
     */
    private $params;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var string
     */
    private $env;

    /**
     * @var AlertBootstrapInterface
     */
    private $alert;

    public function __construct(
        KernelInterface $kernel,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params,
        TranslatorInterface $translator,
        ToolsManager $toolsManager,
        AlertBootstrapInterface $alert
    ) {
        $this->kernel = $kernel;
        $this->env = $kernel->getEnvironment();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->translator = $translator;
        $this->toolsManager = $toolsManager;
        $this->alert = $alert;
    }

    /**
     * @Route("/account/locale/{locale}", name="account_locale", options={"expose"=true})
     */
    public function changeLocale(string $locale, Request $request): RedirectResponse
    {
        $request->getSession()->set('_locale', $locale);

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/account", name="my_account")
     */
    public function myAccount(Request $request, UserPasswordEncoderInterface $encoder, UserManagerInterface $userManager): Response
    {
        $user = $this->getUser();
        $em = $this->entityManager;
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);
        $timezone = $user->getTimezone();
        if ($form->isSubmitted() && $form->isValid()) {
            $request->getSession()->set('_timezone', $timezone);
            $this->entityManager->flush();

            return $this->redirectToRoute('my_account');
        }

        return $this->render('Account/index.html.twig', [
            'locale' => $request->getLocale(),
            'form' => $form->createView(), // change profile form
        ]);
    }

    /**
     * @return RedirectResponse|Response|null
     *
     * @Route("/account/reset-password", name="my_account_reset_password")
     */
    public function resetPasswordAction(Request $request, UserPasswordEncoderInterface $encoder, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $form = $this->createForm(ResetPasswordType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $oldPassword = $request->request->get('reset_password')['oldPassword'];
            // first we test whether the old password input is correct
            if ($encoder->isPasswordValid($user, $oldPassword)) {
                $newEncodedPassword = $encoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($newEncodedPassword);
                $em->persist($user);
                $em->flush();
                $success = $translator->trans('password_reset.success');
                $this->addFlash('success', $success);

                return $this->redirectToRoute('my_account');
            } else {
                $failure = $translator->trans('password_reset.incorrect_password');
                $this->addFlash('error', $failure);
            }
        }

        return $this->render('Account/resetPassword.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
