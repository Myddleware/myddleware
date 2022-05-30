<?php

namespace App\Controller;

use App\Repository\SolutionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConnectorController extends AbstractController
{
    #[Route('/credentials/getform', name: 'credentials_form')]
    public function getCredentialsForm(int $solutionId= null, SolutionRepository $solutionRepository): Response
    {
        // $solution = $solutionRepository->find($solutionId);
        return $this->render('connector/index.html.twig', [
            // 'solution' => $solution
        ]);
    }
}
