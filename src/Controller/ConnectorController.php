<?php

namespace App\Controller;

use App\Entity\Connector;
use App\Entity\ConnectorParam;
use App\Form\ConnectorParamFormType;
use App\Manager\SolutionManager;
use App\Repository\SolutionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConnectorController extends AbstractController
{
    #[Route('/credentials/getform/{solutionId<\d+>?null}', name: 'credentials_form', methods: ['GET'])]
    public function getCredentialsForm(string $solutionId, SolutionRepository $solutionRepository, SolutionManager $solutionManager): Response
    {
        $solution = $solutionRepository->find(intval($solutionId));
        assert(null !== $solution);

        $loginFields = $solutionManager->get($solution->getName())->getFieldsLogin();

        $connector = new Connector();
        $connector->setSolution($solution);

        $form = $this->createForm(ConnectorType::class, $connector, [
            'action' => $this->generateUrl('regle_connector_insert'),
            'attr' => [
                'loginFields' => $loginFields,
                'secret' => $this->getParameter('secret'),
            ],
        ]);

        foreach ($loginFields as $loginField) {
            $connectorParam = new ConnectorParam();
            $connectorParam->setName($loginField['name']);
            $connectorParamForm = $this->createForm(ConnectorParamFormType::class, $connectorParam);
        }

        return $this->renderForm('connector/index.html.twig', [
            'solution' => $solution,
            'loginFields' => $loginFields,
            'form' => $connectorParamForm,
        ]);
    }
}
