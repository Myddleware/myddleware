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
        $solution = $solutionRepository->find(intval($solutionId));
        assert(null !== $solution);
        $loginFields = $solutionManager->get($solution->getName())->getFieldsLogin();

        $connector = new Connector();
        $connector->setSolution($solution);
        $form = $this->createForm(ConnectorType::class, $connector, [
            'method' => 'PUT',
            'action' => $this->generateUrl('app_create_connector'),
            'attr' => [
                'loginFields' => $loginFields,
                'secret' => $this->getParameter('secret'),
            ],
        ]);

        foreach ($loginFields as $loginField) {
            $connectorParam = new ConnectorParam();
            $connectorParam->setName($loginField['name']);
            //    $connectorParamForm = $this->createForm(ConnectorParamFormType::class, $connectorParam);
            $form->add('connectorParams', ConnectorParamFormType::class, [
                'attr' => [
                    'loginFields' => $loginFields,
                    'secret' => $this->getParameter('secret'),
                ],
            ]);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $connectorRepository->add($connector);
        }

        return $this->renderForm('connector/index.html.twig', [
            'solution' => $solution,
            'loginFields' => $loginFields,
            'form' => $form,
        ]);
    }

    #[Route('/connector/create', name: 'app_create_connector', methods: ['GET', 'POST', 'PUT'])]
    public function createConnector(Request $request, SolutionRepository $solutionRepository)
    {
        dd($request);
    }
}
