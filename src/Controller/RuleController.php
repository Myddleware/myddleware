<?php

namespace App\Controller;

use App\Repository\ConnectorRepository;
use App\Repository\ModuleRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RuleController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('/get-modules/{origin?null}/{connectorId<\d+>?null}', name: 'get-modules', methods: ['GET'])]
    public function getCredentialsForm(string $origin, string $connectorId, ConnectorRepository $connectorRepository, ModuleRepository $moduleRepository): Response
    {
        $connector = $connectorRepository->find($connectorId);

        $modules = $moduleRepository->findBy(['solution' => $connector->getSolution()]);
        $choices = [];
        foreach ($modules as $module) {
            $choices[$module->__toString()] = $module->getId();
        }

        $form = $this->createFormBuilder([]);
        $form->add($origin, ChoiceType::class, [
            'choices' => $choices,
            'label' => sprintf('Module %s', $origin)
        ]);

        $form = $form->getForm();

        return $this->renderForm('rule/index.html.twig', [
            'form' => $form,
            'origin' => $origin
        ]);
    }
}
