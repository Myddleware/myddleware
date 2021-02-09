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

use Exception;
use Psr\Log\LoggerInterface;
use App\Manager\ToolsManager;
use App\Form\Type\ProfileFormType;
use App\Form\Type\PasswordFormType;
use Symfony\Component\Process\Process;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class AccountController.
 *
 * @package App\Controller
 *
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

    public function __construct(
        KernelInterface $kernel,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params,
        TranslatorInterface $translator,
        ToolsManager $toolsManager
    ) {
        $this->kernel = $kernel;
        $this->env = $kernel->getEnvironment();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->translator = $translator;
        $this->toolsManager = $toolsManager;
    }

    /**
     * @Route("/account/locale/{locale}", name="account_locale", options={"expose"=true})
     */
    public function changeLocaleAction(string $locale, Request $request)
    {
        $request->getSession()->set('_locale', $locale);

        return $this->redirect($request->headers->get('referer'));

    }

    /**
     * Function for forms of my account.
     *
     * @return null|RedirectResponse|Response
     *
     * @Route("/account", name="my_account")
     */
    public function myAccountAction(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('my_account'); // fos_user_profile_show
        }

        $formPassword = $this->createForm(PasswordFormType::class, $user);
        $formPassword->handleRequest($request);
        if ($formPassword->isSubmitted() && $formPassword->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('my_account'); // fos_user_profile_show
        }

        return $this->render('Account/index.html.twig', [
            'locale' => $request->getLocale(),
            'form' => $form->createView(), // change profil
            'formPassword' => $formPassword->createView(), // change password
        ]);
    }
}
