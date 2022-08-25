<?php

namespace App\Controller;

use App\Entity\Rule;
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
        $rules = $this->entityManager->getRepository(Rule::class)->findAll();
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $project = $form->getData();
            $this->entityManager->persist($project);
            $this->entityManager->flush();
            return $this->redirectToRoute('app_project');
        }

        return $this->render('project/create.html.twig', [
            'form' => $form->createView(),
            'rules'=> $rules

        ]);
    }

      /**
     * @Route("/{id}/show", name="project_show")
     *
     * @param mixed $id
     */
    public function showAction($id)
    {
        $project = $this->entityManager->getRepository(Project::class)->find($id);

        return $this->render('project/show.html.twig', [
            'project' => $project
        ]);
    }

    /**
     * @Route("/{id}/edit", name="project_edit")
     *
     * @param mixed $id
     */
    public function editAction($id)
    {
        $project = $this->entityManager->getRepository(Project::class)->find($id);

        if (!$project) {
            throw $this->createNotFoundException('Unable to find project entity.');
        }

        $deleteFormProject = $this->createDeleteFormProject($id);

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'delete_form' => $deleteFormProject->createView(),
        ]);
    }
 

     /**
     * @Route("/{id}/delete_project", name="delete_project", methods={"GET", "DELETE"})
     *
     * @param mixed $id
     */
    public function deleteActionProject(Request $request, $id)
    {
        $id = $request->get('id');
        $project = $this->entityManager->getRepository(Project::class)->find($id);

        if (!$project) {
            throw $this->createNotFoundException('Unable to find project.');
        }

        $this->entityManager->remove($project);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('app_project'));
    }

    /**
     * Creates a form to delete a project entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteFormProject($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('delete_project', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm();
    }

    
}
