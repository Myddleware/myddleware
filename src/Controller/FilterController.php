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
     * @Route("/flux/list/search-{search}", name="flux_list", defaults={"page"=1})
     * @Route("/flux/list/page-{page}", name="flux_list_page", requirements={"page"="\d+"})
     */ 
    public function testFilterAction(Request $request)
    {
        $form = $this->createForm(ItemFilterType::class);

        $rules = $this->entityManager->getRepository(Rule::class)->findBy(['deleted' => 0]);
        // Rule list
        $listRuleName = [];
        
        foreach ($rules as $r) {
            $listRuleName[$r->getName()] = $r->getName();
        }

        if ($form->isSubmitted() && $form->isValid()) {

            //    $documentIdString = $form->get('id')->getData();

            // return $this->render('testFilter.html.twig', array(
            //     'doc' => $documentIdString
            // ));
        }


        // if ($form->isValid() && $form->isSubmitted()) {


            
        //     return $this->redirect($this->generateUrl(''));
        // }

       // $form = $this->get('form.factory')->create(ItemFilterType::class);

        //valid 
    
        

        // if ($request->query->has($form->getName())) {
        //     // manually bind values from the request
        //     $form->submit($request->query->get($form->getName()));

        //     // initialize a query builder
        //     $filterBuilder = $this->get('doctrine.orm.entity_manager')
        //         ->getRepository('ProjectSuperBundle:MyEntity')
        //         ->createQueryBuilder('e');

        //     // build the query from the given form object
        //     $this->get('lexik_form_filter.query_builder_updater')->addFilterConditions($form, $filterBuilder);

        //     // now look at the DQL =)
        //     var_dump($filterBuilder->getDql());
        // }

       // $form = $this->get('form.factory')->create(ItemFilterType::class);

        //valid 
    
        

        // if ($request->query->has($form->getName())) {
        //     // manually bind values from the request
        //     $form->submit($request->query->get($form->getName()));

        //     // initialize a query builder
        //     $filterBuilder = $this->get('doctrine.orm.entity_manager')
        //         ->getRepository('ProjectSuperBundle:MyEntity')
        //         ->createQueryBuilder('e');

        //     // build the query from the given form object
        //     $this->get('lexik_form_filter.query_builder_updater')->addFilterConditions($form, $filterBuilder);

        //     // now look at the DQL =)
        //     var_dump($filterBuilder->getDql());
        //}

        return $this->render('testFilter.html.twig', array(
            'form' => $form->createView(),
            'rules' => $listRuleName
        ));
    }
}
