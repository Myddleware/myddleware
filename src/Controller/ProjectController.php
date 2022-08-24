<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Uuid;
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
        $projects = $this->entityManager->getRepository(Project::class)->findAll();
        dump($projects);
        return $this->render('project/list.html.twig', [
            'projects' => $projects
        ]);
    }

    /**
     * @Route("/project/create", name="app_create_project")
     */
    public function create_project(Request $request): Response
    {

        $project = new Project();
        $form = $this->createForm(ProjectFormType::class, $project);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $project = $form->getData();
            $this->entityManager->persist($project);
            $this->entityManager->flush();
            return $this->redirectToRoute('app_project');
        }

        return $this->render('project/create.html.twig', [
            'form' => $form->createView()

        ]);
    }

    /**
     * @Route("/{id}/edit", name="project_edit")
     *
     * @param mixed $id
     */
    public function editAction($id)
    {
        $entity = $this->entityManager->getRepository(Project::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JobScheduler entity.');
        }

        return $this->render('project/edit.html.twig');
    }

    /**
     * @Route("/{id}/show", name="project_show")
     *
     * @param mixed $id
     */
    public function showAction($id)
    {
        $entity = $this->entityManager->getRepository(Project::class)->find($id);

        return $this->render('project/show.html.twig');
    }
    
}
