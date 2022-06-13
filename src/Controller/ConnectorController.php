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
//            dd($connectorParam);
//            $connectorParamForm = $this->createForm(ConnectorParamFormType::class, $connectorParam);
            $form->add(ConnectorParamFormType::class, $connectorParam);
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
        dump($request);
        die();
        // $type = '';

        // $solution = $solutionRepository->findOneBy(['name' => $this->sessionService->getParamConnectorSourceSolution()]);

        // $connector = new Connector();
        // $connector->setSolution($solution);

        // if (null != $connector->getSolution()) {
        //     $fieldsLogin = $this->solutionManager->get($connector->getSolution()->getName())->getFieldsLogin();
        // } else {
        //     $fieldsLogin = [];
        // }

        // $form = $this->createForm(ConnectorType::class, $connector, [
        //     'method' => 'PUT',
        //     'attr' => ['fieldsLogin' => $fieldsLogin, 'secret' => $this->getParameter('secret')],
        // ]);

        // if ('POST' == $request->getMethod() && $this->sessionService->isParamConnectorExist()) {
        //     try {
        //         $form->handleRequest($request);
        //         $form->submit($request->request->get($form->getName()));
        //         if ($form->isValid()) {
        //             $solution = $connector->getSolution();
        //             $multi = $solution->getSource() + $solution->getTarget();
        //             if ($this->sessionService->getConnectorAnimation()) {
        //                 // animation add connector
        //                 $type = $this->sessionService->getParamConnectorAddType();
        //                 // si la solution ajouté n'existe pas dans la page en cours on va la rajouter manuellement
        //                 $solution = $this->sessionService->getParamConnectorSourceSolution();
        //                 if (!in_array($solution, json_decode($this->sessionService->getSolutionType($type)))) {
        //                     $this->sessionService->setParamConnectorValues($type.';'.$solution.';'.$multi.';'.$solution->getId());
        //                 }
        //             }

        //             $connectorParams = $connector->getConnectorParams();
        //             $connector->setConnectorParams(null);
        //             $connector->setNameSlug($connector->getName());
        //             $connector->setDateCreated(new \DateTime());
        //             $connector->setDateModified(new \DateTime());
        //             $connector->setCreatedBy($this->getUser()->getId());
        //             $connector->setModifiedBy($this->getUser()->getId());
        //             $connector->setDeleted(0);

        //             $this->entityManager->persist($connector);
        //             $this->entityManager->flush();

        //             foreach ($connectorParams as $key => $cp) {
        //                 $cp->setConnector($connector);
        //                 $this->entityManager->persist($cp);
        //                 $this->entityManager->flush();
        //             }

        //             $this->sessionService->removeConnector();
        //             if (
        //                     !empty($this->sessionService->getConnectorAddMessage())
        //                 && 'list' == $this->sessionService->getConnectorAddMessage()
        //             ) {
        //                 $this->sessionService->removeConnectorAdd();

        //                 return $this->redirect($this->generateUrl('regle_connector_list'));
        //             }
        //             // animation
        //             $message = '';
        //             if (!empty($this->sessionService->getConnectorAddMessage())) {
        //                 $message = $this->sessionService->getConnectorAddMessage();
        //             }
        //             $this->sessionService->removeConnectorAdd();

        //             return $this->render('Connector/createout_valid.html.twig', [
        //                 'message' => $message,
        //                 'type' => $type,
        //             ]
        //                 );
        //         }
        //         dump($form);
        //         exit();

        //         return $this->redirect($this->generateUrl('regle_connector_list'));
        //         //-----------
        //     } catch (Exception $e) {
        //         throw $this->createNotFoundException('Error : '.$e->getMessage().' File :  '.$e->getFile().' Line : '.$e->getLine());
        //     }
        // } else {
        //     throw $this->createNotFoundException('Error');
        // }
    }
}
