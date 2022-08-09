<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\DataTransformer\ConnectorParamsValueTransformer;
use App\Manager\SolutionManager;
use App\Repository\ConnectorParamRepository;
use App\Repository\ConnectorRepository;
use App\Repository\SolutionRepository;
use App\Solutions\Solution;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConnectorController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('/credentials/get-form/{solutionId<\d+>?null}', name: 'credentials_form', methods: ['GET', 'POST'])]
    public function getCredentialsForm(string $solutionId, SolutionRepository $solutionRepository, SolutionManager $solutionManager): Response
    {
        $solution = $solutionRepository->find((int) $solutionId);
        assert(null !== $solution);
        $loginFields = $solutionManager->get($solution->getName())->getFieldsLogin();

        $form = $this->createFormBuilder([]);
        // Iterate on each login fields from the given solution and add needed inputs
        foreach ($loginFields as $loginField) {
            $form->add($loginField['name'], $loginField['type'], [
                'attr' => [
                    'data-connector-target' => 'testing',
                ],
            ]);
        }

        $form = $form->getForm();

        return $this->renderForm('connector/index.html.twig', [
            'form' => $form,
            'loginFields' => $loginFields,
            'urlTest' => $this->generateUrl('test_connection'),
            'solution' => $solution->getName(),
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/credentials/get-form-edit/{connectorId}', name: 'credentials_form_edit', methods: ['GET', 'POST', 'PUT'])]
    public function getCredentialsEditForm(ConnectorRepository $connectorRepository, ConnectorParamRepository $connectorParamRepository, ConnectorParamsValueTransformer $connectorParamsValueTransformer, SolutionManager $solutionManager, ?string $connectorId = null): Response
    {
        $connector = $connectorRepository->find($connectorId);
        $loginFields = $solutionManager->get($connector->getSolution()->getName())->getFieldsLogin();

        $form = $this->createFormBuilder([]);
        foreach ($loginFields as $loginField) {
            $connectorParam = $connectorParamRepository->findOneBy([
                'name' => $loginField['name'],
                'connector' => $connector,
            ]);

            // If connector param does not exist add an empty input.
            if ($connectorParam) {
                $transformParam = $connectorParamsValueTransformer->transform($connectorParam);
                $form->add($connectorParam->getName(), TextType::class, [
                    'data' => $transformParam->getValue(),
                    'attr' => [
                        'data-connector-target' => 'testing',
                    ],
                ]);
            } else {
                $form->add($loginField['name'], $loginField['type'], [
                    'attr' => [
                        'data-connector-target' => 'testing',
                    ],
                ]);
            }
        }

        $form = $form->getForm();

        return $this->renderForm('connector/index.html.twig', [
            'form' => $form,
            'loginFields' => $loginFields,
            'urlTest' => $this->generateUrl('test_connection'),
            'solution' => $connector->getSolution()->getName(),
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/connector/test-connection', name: 'test_connection', methods: ['POST'])]
    public function testConnection(Request $request, SolutionManager $solutionManager): Response
    {
        $content = json_decode($request->getContent(), true);

        /** @var Solution $solution */
        $solution = $solutionManager->get($content['solution']);

        $connectionParams = [];
        foreach ($content['connectorParams'] as $paramData) {
            $connectionParams[$paramData['name']] = $paramData['value'];
        }

        // Check login
        $solution->login($connectionParams);
        if ($isValid = $solution->isConnectionValid) {
            $statusCode = Response::HTTP_OK;
        } else {
            $statusCode = Response::HTTP_FORBIDDEN;
        }

        return $this->render('connector/test-connector-params.html.twig', [
            'isCredentialsValid' => $isValid,
        ], new Response(null, $statusCode));
    }
}
