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

use App\Entity\Rule;
use Psr\Log\LoggerInterface;
use App\Manager\ToolsManager;
use App\Form\Type\ItemFilterType;
use App\Form\Type\ProfileFormType;
use App\Form\Type\ResetPasswordType;
use App\Service\UserManagerInterface;
use App\Service\AlertBootstrapInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use the ItemFilterType
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use App\Repository\DocumentRepository;


/**
 * @Route("/rule")
 */
class FilterController extends AbstractController
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

    private DocumentRepository $documentRepository;

    public function __construct(
        KernelInterface $kernel,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params,
        TranslatorInterface $translator,
        ToolsManager $toolsManager,
        AlertBootstrapInterface $alert,
        DocumentRepository $documentRepository,
    ) {
        $this->kernel = $kernel;
        $this->env = $kernel->getEnvironment();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->translator = $translator;
        $this->toolsManager = $toolsManager;
        $this->alert = $alert;
        $this->documentRepository = $documentRepository;
    }

    /**
     * @Route("/flux/list/search-{search}", name="flux_list", defaults={"page"=1})
     * @Route("/flux/list/page-{page}", name="flux_list_page", requirements={"page"="\d+"})
     */
    public function testFilterAction(Request $request)
    {
        $form = $this->createForm(ItemFilterType::class, null, [
            'entityManager' => $this->getDoctrine()->getManager()
        ]);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $queryBuilder = $this->documentRepository->createQueryBuilder('d');

            // apply filters to query builder
            $filterQueryBuilder = $this->get('lexik_form_filter.query_builder_updater')
                ->addFilterConditions($form, $queryBuilder);

            // get filtered results
            $documents = $filterQueryBuilder->getQuery()->getResult();
        } else {
            // get all documents if form is not submitted or invalid
            $documents = $this->documentRepository->findAll();
        }

        return $this->render('testFilter.html.twig', [
            'documents' => $documents,
            'form' => $form->createView(),
        ]);



        return $this->render('testFilter.html.twig', array(
            'form' => $form->createView(),
            // 'rules' => $listRuleName
        ));
    }
}
