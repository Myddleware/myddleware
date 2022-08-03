<?php

namespace App\Controller;

use App\Entity\Module;
use App\Manager\SolutionManager;
use App\Repository\ConnectorRepository;
use App\Repository\ModuleRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    public function getModulesForm(
        string $origin,
        string $connectorId,
        ConnectorRepository $connectorRepository,
        ModuleRepository $moduleRepository,
        SolutionManager $solutionManager,
        EntityManagerInterface $entityManager
    ): Response {
        $connector = $connectorRepository->find($connectorId);
        // handle the case when the user clicks on the little cross which makes $connector = null to avoid a 500 error
        if (null === $connector) {
            return new Response();
        }
        $modules = $moduleRepository->findBy(['solution' => $connector->getSolution()]);
        // Not all modules have been converted into an actual Module object yet, therefore for some solutions we will still resort to calling
        // the old getSolutionModules() method from the Solution class while we find a way to get all modules transferred to the new architecture
        if (empty($modules)) {
            $solution = $solutionManager->get($connector->getSolution()->getName());
            $loginParams = $solution->getLoginParameters($connectorId);
            $login = $solution->login($loginParams);
            $modules = $solution->getSolutionModules();
            // TODO: for now, modules will be pushed into database here (only the 1st time a user clicks on select)
            // but of course this will need to be moved to a different location for performance reasons
            // maybe on connector creation submit ?
            foreach ($modules as $moduleName => $moduleLabelName) {
                $module = new Module();
                $module->setName($moduleName);
                $module->setNameKey($moduleLabelName);
                $module->setSolution($connector->getSolution());
                // TODO: for now, I'm hard-coding this as source by default, but we need to find a
                // way to determine this parameter properly
                $module->setDirection('source');
                $entityManager->persist($module);
            }
            $entityManager->flush();
        }

        $choices = [];
        foreach ($modules as $module) {
            if (!is_string($module)) {
                $choices[$module->__toString()] = $module->getId();
            }
        }

        $form = $this->createFormBuilder([]);
        $form->add($origin, ChoiceType::class, [
            'choices' => $choices,
            'label' => sprintf('Module %s', $origin),
            'row_attr' => [
                'data-controller' => 'rule',
            ],
            'attr' => [
                'data-action' => 'change->rule#onSelectModule'.ucfirst($origin),
                'data-rule-target' => 'field',
            ],
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
    #[Route('/get-fields/{origin?null}/{connectorId<\d+>?null}', name: 'get_fields', methods: ['GET'])]
    public function getFieldsForm(
        string $origin,
        string $connectorId,
        ConnectorRepository $connectorRepository,
        ModuleRepository $moduleRepository,
        SolutionManager $solutionManager
    ): Response {
        $connector = $connectorRepository->find($connectorId);
        // handle the case when the user clicks on the little cross which makes $connector = null to avoid a 500 error
        if (null === $connector) {
            return new Response();
        }

        $solution = $solutionManager->get($connector->getSolution()->getName());

        // @todo Je n'ai pas pu finir il me manque les module fields
        // mais l'idée serait d'utiliser ce controller pour renvoyer la liste des fields par connector
        // et ensuite pouvoir manipuler ça côté JS
        // Si on veut rester à utiliser les formulaires symfony je vous invite à lire le code que j'ai mis dans le subscriber EASY ADMIN
        // la logique des 2 eventsubscriber devrait vous permettre de faire la même chose pour la suite
        // Bonne continuation !

        $form = $this->createFormBuilder([]);
        if (method_exists($connector, 'getModuleFields')) {
            $loginParams = $solution->getLoginParameters($connectorId);
            $login = $solution->login($loginParams);
            $fields = $solution->getModuleFields();
            var_dump($fields);
            $choices = [];
            foreach ($fields as $fieldId => $field) {
                $choices[$field['label']] = $fieldId;
            }

            $form->add('fieldSelect', ChoiceType::class, [
                'choices' => $choices,
                'expanded' => true,
//                'multiple' => true,
            ]);
        }

        $form = $form->getForm();

        return $this->renderForm('rule/module_field_form.html.twig', [
            'form' => $form,
        ]);
    }
}
