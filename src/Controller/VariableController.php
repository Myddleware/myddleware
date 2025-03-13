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
use Exception;
class VariableController extends AbstractController
{
    protected ToolsManager $tools;

    public function __construct(ToolsManager $tools)
    {
        $this->tools = $tools;
    }

    /**
     * @return Response
     */
    #[Route('variables', name: 'variable_list', defaults: ['page' => 1])]
    #[Route('variables/page-{page}', name: 'variable_list_page', requirements: ['page' => '\d+'])]
    public function listView(Request $request, EntityManagerInterface $em, int $page = 1): Response
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        try {
            $variables = $em->getRepository(Variable::class)->findBy([], ['id' => 'ASC']);

            $adapter = new ArrayAdapter($variables);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage(10);
            $pager->setCurrentPage($page);

            return $this->render('variable/list.html.twig', [
                'variables' => $pager->getCurrentPageResults(),
                'pager' => $pager,
                'nb_variables' => count($variables),
            ]);
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Error: ' . $e->getMessage());
        }
    }

  /**
     * @Route("/variables/new", name="variable_create")
     */
    public function create(EntityManagerInterface $em, Request $request, TranslatorInterface $translator): Response
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        $variable = new Variable();

        $form = $this->createFormBuilder($variable)
            ->add('name', TextType::class, [
                'label' => $translator->trans('variable.table_headers.name'),
            ])
            ->add('description', TextareaType::class, [
                'label' => $translator->trans('variable.table_headers.description'),
            ])
            ->add('value', TextareaType::class, [
                'label' => $translator->trans('variable.table_headers.value'),
            ])
            ->add('save', SubmitType::class, [
                'label' => $translator->trans('variable.save'),
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $variable->setCreatedBy($this->getUser());
            $variable->setModifiedBy($this->getUser());

            // verify if the name doesn't already exist, for this call the function verifyIfVariableNameExists
            $name = $variable->getName();
            $variableExists = $this->verifyIfVariableNameExists($em, $name);
            if ($variableExists) {
                $this->addFlash('danger', $translator->trans('variable.name_already_exists'));
                return $this->redirectToRoute('variable_create');
            }

            // replace the spaces in the name of the variable with underscores
            $variable->setName(str_replace(' ', '_', $variable->getName()));

            $em->persist($variable);
            $em->flush();

            // Create an audit entry
            $audit = new VariableAudit();
            $audit->setVariableId($variable->getId());
            $audit->setDateModified(new \DateTime());
            $audit->setAfter($variable->getValue());
            $audit->setByUser($this->getUser()->getUsername());

            $em->persist($audit);
            $em->flush();

            return $this->redirectToRoute('variable_list');
        }

        return $this->render('variable/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/variables/{id}/edit", name="variable_edit")
     */
    public function edit(EntityManagerInterface $em, Request $request, Variable $variable, TranslatorInterface $translator): Response
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        $originalValue = $variable->getValue();

        $form = $this->createFormBuilder($variable)
            ->add('name', TextType::class, [
                'label' => $translator->trans('variable.table_headers.name'),
            ])
            ->add('description', TextareaType::class, [
                'label' => $translator->trans('variable.table_headers.description'),
            ])
            ->add('value', TextareaType::class, [
                'label' => $translator->trans('variable.table_headers.value'),
            ])
            ->add('save', SubmitType::class, [
                'label' => $translator->trans('variable.save'),
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $variable->setCreatedBy($this->getUser());
            $variable->setModifiedBy($this->getUser());

            // verify if the name doesn't already exist, for this call the function verifyIfVariableNameExists
            $name = $variable->getName();
            $variableExists = $this->verifyIfVariableNameExists($em, $name, $variable->getId());
            if ($variableExists) {
                $this->addFlash('danger', $translator->trans('variable.name_already_exists'));
                return $this->redirectToRoute('variable_edit', ['id' => $variable->getId()]);
            }

            // replace the spaces in the name of the variable with underscores
            $variable->setName(str_replace(' ', '_', $variable->getName()));
            // Create an audit entry
            $audit = new VariableAudit();
            $audit->setVariableId($variable->getId());
            $audit->setDateModified(new \DateTime());
            $audit->setBefore($originalValue);
            $audit->setAfter($variable->getValue());
            $audit->setByUser($this->getUser()->getUsername());

            $em->persist($audit);
            $em->flush();

            $em->flush();

            return $this->redirectToRoute('variable_list');
        }

        return $this->render('variable/edit.html.twig', [
            'form' => $form->createView(),
            'variable' => $variable,
        ]);
    }

    /**
     * @Route("/variables/verify-name", name="variable_verify_name")
     */
    public function verifyIfVariableNameExists(EntityManagerInterface $em, string $name, ?int $excludeId = null): bool
    {
        // First check with the exact name
        $variable = $em->getRepository(Variable::class)->findOneByName($name);
        if ($variable && ($excludeId === null || $variable->getId() !== $excludeId)) {
            return true;
        }

        // Second test with spaces replaced by underscores
        $nameForBetterTesting = str_replace(' ', '_', $name);
        $variable = $em->getRepository(Variable::class)->findOneByName($nameForBetterTesting);
        if ($variable && ($excludeId === null || $variable->getId() !== $excludeId)) {
            return true;
        }

        return false;
    }

    /**
     * @Route("/variables/{id}/delete", name="variable_delete")
     */
    public function delete(EntityManagerInterface $em, Variable $variable): Response
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        // Create an audit entry
        $audit = new VariableAudit();
        $audit->setVariableId($variable->getId());
        $audit->setDateModified(new \DateTime());
        $audit->setBefore($variable->getValue());
        $audit->setAfter(null);
        $audit->setByUser($this->getUser()->getUsername());

        $em->persist($audit);

        $em->remove($variable);
        $em->flush();

        return $this->redirectToRoute('variable_list');
    }

    /**
     * @Route("/variables/{id}", name="variable_show")
     */
    public function show(Variable $variable): Response
    {
        if (!$this->tools->isPremium()) {
            return $this->redirectToRoute('premium_list');
        }

        return $this->render('variable/show.html.twig', [
            'variable' => $variable
        ]);
    }
}
