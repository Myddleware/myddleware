<?php

namespace App\Controller;

use App\Entity\Connector;
use App\Form\ConnectorType;
use App\Entity\ConnectorParam;
use App\Manager\SolutionManager;
use App\Form\ConnectorParamFormType;
use App\Repository\SolutionRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConnectorController extends AbstractController
{
    #[Route('/credentials/getform/{solutionId<\d+>?null}', name: 'credentials_form', methods: ['GET'])]
    public function getCredentialsForm(string $solutionId, SolutionRepository $solutionRepository, SolutionManager $solutionManager): Response
    {
        $solution = $solutionRepository->find(intval($solutionId));
        assert(null !== $solution);

        $loginFields = $solutionManager->get($solution->getName())->getFieldsLogin();

        $connector = new Connector();

        $form = $this->createForm(ConnectorType::class, $connector);
        // $form = $this->createFormBuilder(ConnectorParam::class);
        foreach($loginFields as $loginField) {
            // $formInput = $this->createForm($loginField['type'], $loginField['name']);   
            // $form->add($formInput);
            $connectorParam = new ConnectorParam();
            $connectorParam->setName($loginField['name']);
            $connectorParam->setConnector($connector);
            $connectorParamForm = $this->createFormBuilder($connectorParam)->getForm();
            $form->add($connectorParamForm);
            // $form->add($connectorParam, $loginField['type']);
        }
        // $form = $form->getForm();
        // $form  = $this->createForm(ConnectorParamFormType::class, $loginForm);

        return $this->renderForm('connector/index.html.twig', [
            'solution' => $solution,
            'loginFields' =>$loginFields,
            'form' => $form
        ]);
    }
}
