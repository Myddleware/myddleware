<?php

namespace App\Controller;

use App\Entity\Connector;
use App\Entity\ConnectorParam;
use App\Form\ConnectorParamFormType;
use App\Form\ConnectorType;
use App\Manager\SolutionManager;
use App\Repository\ConnectorRepository;
use App\Repository\SolutionRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConnectorController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('/credentials/get-form/{solutionId<\d+>?null}', name: 'credentials_form', methods: ['GET', 'POST'])]
    public function getCredentialsForm(string $solutionId, SolutionRepository $solutionRepository, SolutionManager $solutionManager, ConnectorRepository $connectorRepository, Request $request): Response
    {
        $solution = $solutionRepository->find((int) $solutionId);
        assert(null !== $solution);
        $loginFields = $solutionManager->get($solution->getName())->getFieldsLogin();

        $form = $this->createFormBuilder([]);
        foreach ($loginFields as $loginField) {
            $form->add($loginField['name'], $loginField['type']);
        }

        $form = $form->getForm();

        return $this->renderForm('connector/index.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/connector/create', name: 'app_create_connector', methods: ['GET', 'POST', 'PUT'])]
    public function createConnector(Request $request, SolutionRepository $solutionRepository)
    {
        return;
    }
}
