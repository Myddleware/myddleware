<?php

namespace App\Controller;

use App\Repository\SolutionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConnectorController extends AbstractController
{
    #[Route('/credentials/getform/{solutionId}', name: 'credentials_form')]
    public function getCredentialsForm(int $solutionId, SolutionRepository $solutionRepository): Response
    {
        $solution = $solutionRepository->find($solutionId);
        dd($solution);
        return $this->render('connector/index.html.twig', [
            'controller_name' => 'ConnectorController',
        ]);
    }
}
