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
    #[Route('/get-modules/{origin?null}/{connectorId<\d+>?null}', name: 'get_modules', methods: ['GET'])]
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
            'label' => sprintf('Module %s', $origin),
        ]);

        $form = $form->getForm();

        return $this->renderForm('rule/index.html.twig', [
            'form' => $form,
            'origin' => $origin,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/get-fields/{connectorId<\d+>?null}', name: 'get_fields', methods: ['GET'])]
    public function getFieldsForm(string $origin, string $connectorId, ConnectorRepository $connectorRepository, ModuleRepository $moduleRepository): Response
    {
        $connector = $connectorRepository->find($connectorId);
        $solution = $connector->getSolution();

        // @todo Je n'ai pas pu finir il me manque les module fields
        // mais l'idée serait d'utiliser ce controller pour renvoyer la liste des fields par connector
        // et ensuite pouvoir manipuler ça côté JS
        // Si on veut rester à utiliser les formulaires symfony je vous invite à lire le code que j'ai mis dans le subscriber EASY ADMIN
        // la logique des 2 eventsubscriber devrait vous permettre de faire la même chose pour la suite
        // Bonne continuation !

        $form = $this->createFormBuilder([]);
        if (method_exists($connector, 'get_module_fields')) {
            $fields = $solution->get_module_fields();

            $choices = [];
            foreach ($fields as $fieldId => $field) {
                $choices[$field['label']] = $fieldId;
            }

            $form->add('fieldSelect', ChoiceType::class, [
                'choices' => $choices,
                'expanded' => true,
                'multiple' => true,
            ]);
        }

        $form = $form->getForm();

        return $this->renderForm('rule/module_field_form.html.twig', [
            'form' => $form,
        ]);
    }
}
