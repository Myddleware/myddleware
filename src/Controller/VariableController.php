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

use App\Entity\Variable;
use Pagerfanta\Pagerfanta;
use App\Entity\VariableAudit;
use Pagerfanta\Adapter\ArrayAdapter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Manager\ToolsManager;
use App\Service\DebugLogger;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
class VariableController extends AbstractController
{
    protected ToolsManager $tools;
    private DebugLogger $debugLogger;

    public function __construct(ToolsManager $tools, DebugLogger $debugLogger)
    {
        $this->tools = $tools;
        $this->debugLogger = $debugLogger;
    }

    #[Route('variables', name: 'variable_list', defaults: ['page' => 1])]
    #[Route('variables/page-{page}', name: 'variable_list_page', requirements: ['page' => '\d+'])]
    public function listView(Request $request, EntityManagerInterface $em, int $page = 1): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'em' => $em, 'page' => $page]);
        $__debugReturn = null;
        try {
            if (!$this->tools->isPremium()) {
                return $__debugReturn = $this->redirectToRoute('premium_list');
            }

            try {
                $variables = $em->getRepository(Variable::class)->findBy([], ['id' => 'ASC']);

                $adapter = new ArrayAdapter($variables);
                $pager = new Pagerfanta($adapter);
                $pager->setMaxPerPage(10);
                $pager->setCurrentPage($page);

                return $__debugReturn = $this->render('variable/list.html.twig', [
                    'variables' => $pager->getCurrentPageResults(),
                    'pager' => $pager,
                    'nb_variables' => count($variables),
                ]);
            } catch (\Exception $e) {
                throw $this->createNotFoundException('Error: ' . $e->getMessage());
            }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/variables/new", name="variable_create")
     */
    public function create(EntityManagerInterface $em, Request $request, TranslatorInterface $translator): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['em' => $em, 'request' => $request, 'translator' => $translator]);
        $__debugReturn = null;
        try {
            if (!$this->tools->isPremium()) {
                return $__debugReturn = $this->redirectToRoute('premium_list');
            }

            $variable = new Variable();

            $form = $this->createFormBuilder($variable)
                ->add('name', TextType::class, [
                    'label' => $translator->trans('variable.table_headers.name'),
                    'attr' => [
                        'class' => 'form-control variable-name-input',
                        'pattern' => '^[A-Za-z0-9_]+$',
                        'title' => 'Only letters, numbers, and "_" are allowed (no spaces, periods, or commas)',
                        'maxlength' => 128,
                        'spellcheck' => 'false',
                        'autocapitalize' => 'none',
                        'autocomplete' => 'off',
                    ],
                ])
                ->add('description', TextareaType::class, [
                    'label' => $translator->trans('variable.table_headers.description'),
                    'attr' => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('value', TextareaType::class, [
                    'label' => $translator->trans('variable.table_headers.value'),
                    'attr' => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('save', SubmitType::class, [
                    'label' => $translator->trans('variable.save'),
                    'attr' => [
                        'class' => 'btn btn-primary',
                    ],
                ])
                ->getForm();

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $variable->setCreatedBy($this->getUser());
                $variable->setModifiedBy($this->getUser());

                $name = $variable->getName();
                $variableExists = $this->verifyIfVariableNameExists($em, $name);
                if ($variableExists) {
                    $this->addFlash('variable.create.danger', $translator->trans('variable.name_already_exists'));
                    return $__debugReturn = $this->redirectToRoute('variable_create');
                }

                $this->addFlash('variable.create.success', $translator->trans('variable.created_successfully'));

                $em->persist($variable);
                $em->flush();

                $audit = new VariableAudit();
                $audit->setVariableId($variable->getId());
                $audit->setDateModified(new \DateTime());
                $audit->setAfter($variable->getValue());
                $audit->setByUser($this->getUser()->getUsername());

                $em->persist($audit);
                $em->flush();

                return $__debugReturn = $this->redirectToRoute('variable_list');
            }

            return $__debugReturn = $this->render('variable/create.html.twig', [
                'form' => $form->createView(),
            ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/variables/{id}/edit", name="variable_edit")
     */
    public function edit(EntityManagerInterface $em, Request $request, Variable $variable, TranslatorInterface $translator): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['em' => $em, 'request' => $request, 'variable' => $variable, 'translator' => $translator]);
        $__debugReturn = null;
        try {
            if (!$this->tools->isPremium()) {
                return $__debugReturn = $this->redirectToRoute('premium_list');
            }

            $originalValue = $variable->getValue();

            $form = $this->createFormBuilder($variable)
                ->add('name', TextType::class, [
                    'label' => $translator->trans('variable.table_headers.name'),
                    'attr' => [
                        'class' => 'form-control variable-name-input',
                        'pattern' => '^[A-Za-z0-9_]+$',
                        'title' => 'Only letters, numbers, and "_" are allowed (no spaces, periods, or commas)',
                        'maxlength' => 128,
                        'spellcheck' => 'false',
                        'autocapitalize' => 'none',
                        'autocomplete' => 'off',
                    ],
                ])
                ->add('description', TextareaType::class, [
                    'label' => $translator->trans('variable.table_headers.description'),
                    'attr' => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('value', TextareaType::class, [
                    'label' => $translator->trans('variable.table_headers.value'),
                    'attr' => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('save', SubmitType::class, [
                    'label' => $translator->trans('variable.save'),
                    'attr' => [
                        'class' => 'btn btn-primary',
                    ],
                ])
                ->getForm();

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $variable->setCreatedBy($this->getUser());
                $variable->setModifiedBy($this->getUser());

                $name = $variable->getName();
                $variableExists = $this->verifyIfVariableNameExists($em, $name, $variable->getId());
                if ($variableExists) {
                    $this->addFlash('variable.edit.danger', $translator->trans('variable.name_already_exists'));
                    return $__debugReturn = $this->redirectToRoute('variable_edit', ['id' => $variable->getId()]);
                }
                $audit = new VariableAudit();
                $audit->setVariableId($variable->getId());
                $audit->setDateModified(new \DateTime());
                $audit->setBefore($originalValue);
                $audit->setAfter($variable->getValue());
                $audit->setByUser($this->getUser()->getUsername());

                $this->addFlash('variable.edit.success', $translator->trans('variable.updated_successfully'));
                $em->persist($audit);
                $em->flush();

                $em->flush();

                return $__debugReturn = $this->redirectToRoute('variable_list');
            }

            return $__debugReturn = $this->render('variable/edit.html.twig', [
                'form' => $form->createView(),
                'variable' => $variable,
            ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/variables/verify-name", name="variable_verify_name")
     */
    public function verifyIfVariableNameExists(EntityManagerInterface $em, string $name, ?int $excludeId = null): bool
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['em' => $em, 'name' => $name, 'excludeId' => $excludeId]);
        $__debugReturn = null;
        try {
            $variable = $em->getRepository(Variable::class)->findOneByName($name);
            if ($variable && ($excludeId === null || $variable->getId() !== $excludeId)) {
                return $__debugReturn = true;
            }

            $nameForBetterTesting = str_replace(' ', '_', $name);
            $variable = $em->getRepository(Variable::class)->findOneByName($nameForBetterTesting);
            if ($variable && ($excludeId === null || $variable->getId() !== $excludeId)) {
                return $__debugReturn = true;
            }

            return $__debugReturn = false;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/variables/{id}/delete", name="variable_delete", methods={"POST","DELETE"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(EntityManagerInterface $em, Variable $variable, TranslatorInterface $translator): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['em' => $em, 'variable' => $variable, 'translator' => $translator]);
        $__debugReturn = null;
        try {
            if (!$this->tools->isPremium()) {
                return $__debugReturn = $this->redirectToRoute('premium_list');
            }

            $audit = new VariableAudit();
            $audit->setVariableId($variable->getId());
            $audit->setDateModified(new \DateTime());
            $audit->setBefore($variable->getValue());
            $audit->setAfter(null);
            $audit->setByUser($this->getUser()->getUsername());

            $em->persist($audit);
            $em->remove($variable);
            $em->flush();
            $this->addFlash('variable.delete.success', $translator->trans('variable.deleted_successfully'));

            return $__debugReturn = $this->redirectToRoute('variable_list');
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/variables/{id}", name="variable_show")
     */
    public function show(Variable $variable): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['variable' => $variable]);
        $__debugReturn = null;
        try {
            if (!$this->tools->isPremium()) {
                return $__debugReturn = $this->redirectToRoute('premium_list');
            }

            return $__debugReturn = $this->render('variable/show.html.twig', [
                'variable' => $variable
            ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}
