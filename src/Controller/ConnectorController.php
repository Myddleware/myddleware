<?php

namespace App\Controller;

use App\Repository\SolutionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConnectorController extends AbstractController
{
    // #[Route('/credentials/getform/{solutionId<\d+>}', name: 'credentials_form', methods: ['GET'], defaults:['solutionId' => ''])]
    #[Route('/credentials/getform/{solutionId<\d+>?null}', name: 'credentials_form', methods: ['GET'])]
    public function getCredentialsForm(string $solutionId, SolutionRepository $solutionRepository): Response
    {
        $solution = $solutionRepository->find(intval($solutionId));
        return $this->render('connector/index.html.twig', [
            'solution' => $solution
        ]);
    }
}
