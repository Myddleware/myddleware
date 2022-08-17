<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProjectController extends AbstractController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    /**
     * @Route("/project", name="app_project")
     */
    public function index(): Response
    {
        return $this->render('project/list.html.twig');
    }

    /**
     * @Route("/project/create", name="app_create_project")
     */
    public function create_project(Request $request): Response
    {

        $project = new Project();
        $form = $this->createForm(ProjectFormType::class, $project);
        $form->handleRequest($request);

        // dd($form);

        if ($form->isSubmitted() && $form->isValid()) {
            // $project->setName($form->getData('name'));
            $project = $form->getData();
            // $project->setDescription($form->getData('description'));
            // $project->setName($form->getName());
            // dd($form->getData());
            // dd($project);
            $this->entityManager->persist($project);
            $this->entityManager->flush();
            return $this->redirectToRoute('app_project');
        }

        return $this->render('project/create.html.twig', [
            'form' => $form->createView()

        ]);
    }
}
