<?php

namespace App\Controller;

use App\Entity\JobScheduler;
use App\Entity\User;
use App\Form\JobSchedulerType;
use App\Form\JobSchedulerCronType;
use App\Manager\JobSchedulerManager;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
//use Cron\CronBundle\Entity\CronJob;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * JobScheduler controller.
 *
 * @Route("/rule/jobscheduler")
 */
class JobSchedulerController extends AbstractController
{
    /**
     * @var jobSchedulerManager
     */
    private $jobSchedulerManager;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, JobSchedulerManager $jobSchedulerManager)
    {
        $this->entityManager = $entityManager;
        $this->jobSchedulerManager = $jobSchedulerManager;
    }

    /**
     * Lists all JobScheduler entities.
     *
     * @Route("/", name="jobscheduler")
     */
    public function indexAction()
    {
        $entities = $this->entityManager->getRepository(JobScheduler::class)->findBy([], ['jobOrder' => 'ASC']);

        return $this->render('JobScheduler/index.html.twig', [
            'entities' => $entities,
        ]);
    }

    /**
     * Creates a new JobScheduler entity.
     *
     * @Route("/create", name="jobscheduler_create", methods={"POST"})
     */
    public function createAction(Request $request)
    {
        $entity = new JobScheduler();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $paramName1 = $form->get('paramName1')->getData();
            $paramName1 = $paramName1 ? $paramName1 : '';

            $paramValue1 = $form->get('paramValue1')->getData();
            $paramValue1 = $paramValue1 ? $paramValue1 : '';

            $paramName2 = $form->get('paramName2')->getData();
            $paramName2 = $paramName2 ? $paramName2 : '';

            $paramValue2 = $form->get('paramValue2')->getData();
            $paramValue2 = $paramValue2 ? $paramValue2 : '';

            $active = 1 == $form->get('active')->getData() ? true : false;
            /*
             * set value by default
             */
            $entity->setDateCreated(new \DateTime());
            $entity->setDateModified(new \DateTime());
            $entity->setCreatedBy($this->getUser()->getId());
            $entity->setModifiedBy($this->getUser()->getId());
            $entity->setParamName1($paramName1);
            $entity->setParamValue1($paramValue1);
            $entity->setParamName2($paramName2);
            $entity->setParamValue2($paramValue2);
            $entity->setActive($active);
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('jobscheduler', ['id' => $entity->getId()]));
        }

        return $this->render('JobScheduler/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

       /**
     * New creates job scheduler with cron
     * @Route("/crontab", name="jobscheduler_cron")
     */
    public function createActionWithCron()
    {
        $command ='';
        $period =' */5 * * * *';
        $entity = new CronJob($command, $period);
        $form = $this->createForm(JobSchedulerCronType::class, $entity);

         if ($form->isSubmitted() && $form->isValid()) {      
            $description = $form->get('description');
            $test = $entity->setDescription($form->getData()->getDescription());
            // if (isset($description)) {

            dump($form->getData()->getDescription());
            // $entity->setEnable($form->getData()->getEnable());
            //$entity->setDescription($description);
            // $entity->setArguments($form->getData()->getArguments());
            // $entity->setMaxInstances($form->getData()->getMaxInstances());
            // $entity->setNumber($form->getData()->getNumber());
            // $entity->setPeriod($form->getData()->getNumber());

            // $this->entityManager->persist($entity);
            // $this->entityManager->flush();
            //}
        //     return $this->render('JobScheduler/crontab.html.twig');
         }

        return $this->render('JobScheduler/crontab.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }
    /**
     * @Route("/crontab_list", name="jobscheduler_cron_list")
     *
     */
    public function crontabList()
    {
        $entity = $this->entityManager->getRepository(CronJob::class)->findAll();
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CronJob entity.');
        }
        foreach($entity as $crontab){
            
        }
        return $this->render('JobScheduler/crontab_list.html.twig', [
            'entity' => $entity,
            'crontab'=> $crontab,
        ]);
    }

    /**
     * Creates a form to create a JobScheduler entity.
     *
     * @param JobScheduler $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(JobScheduler $entity)
    {
        $form = $this->createForm(JobSchedulerType::class, $entity, [
            'action' => $this->generateUrl('jobscheduler_create'),
            'method' => 'POST',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'jobscheduler.new']);

        return $form;
    }

    /**
     * Displays a form to create a new JobScheduler entity.
     *
     * @Route("/new", name="jobscheduler_new")
     */
    public function newAction()
    {
        $entity = new JobScheduler();
        $form = $this->createCreateForm($entity);

        return $this->render('JobScheduler/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a JobScheduler entity.
     *
     * @Route("/{id}/show", name="jobscheduler_show")
     *
     * @param mixed $id
     */
    public function showAction($id)
    {
        $entity = $this->entityManager->getRepository(JobScheduler::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JobScheduler entity.');
        }

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user_created = $userRepository->find($entity->getcreatedBy());
        $user_modified = $userRepository->find($entity->getmodifiedBy());

        return $this->render('JobScheduler/show.html.twig', [
            'entity' => $entity,
            'user_created' => $user_created,
            'user_modified' => $user_modified,
        ]);
    }

    /**
     * Displays a form to edit an existing JobScheduler entity.
     *
     * @Route("/{id}/edit", name="jobscheduler_edit")
     *
     * @param mixed $id
     */
    public function editAction($id)
    {
        $entity = $this->entityManager->getRepository(JobScheduler::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JobScheduler entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('JobScheduler/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a JobScheduler entity.
     *
     * @param JobScheduler $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(JobScheduler $entity)
    {
        $form = $this->createForm(JobSchedulerType::class, $entity, [
            'action' => $this->generateUrl('jobscheduler_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'jobscheduler.update']);

        return $form;
    }

    /**
     * Edits an existing JobScheduler entity.
     *
     * @Route("/{id}/update", name="jobscheduler_update", methods={"POST", "PUT"})
     *
     * @param mixed $id
     */
    public function updateAction(Request $request, $id)
    {
        $entity = $this->entityManager->getRepository(JobScheduler::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JobScheduler entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('jobscheduler'));
        }

        return $this->render('JobScheduler/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ]);
    }

    /**
     * Deletes a JobScheduler entity.
     *
     * @Route("/{id}/delete", name="jobscheduler_delete", methods={"GET", "DELETE"})
     *
     * @param mixed $id
     */
    public function deleteAction(Request $request, $id)
    {
        $id = $request->get('id');
        $entity = $this->entityManager->getRepository(JobScheduler::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JobScheduler entity.');
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('jobscheduler'));
    }

    /**
     * Creates a form to delete a JobScheduler entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('jobscheduler_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm();
    }

    /**
     * get fields select.
     *
     * @return JsonResponse
     *
     * @Route("/getFieldsSelect", name="jobscheduler_input", methods={"GET"}, options={"expose"=true})
     */
    public function getFieldsSelectAction(Request $request)
    {
        $select = [];
        if ($request->isXmlHttpRequest() && 'GET' == $request->getMethod()) {
            $select = $this->getData($request->query->get('type'));
        }

        return $this->json($select);
    }

    private function getData($selectName)
    {
        $paramsCommand = null;
        if (isset($this->jobSchedulerManager->getJobsParams()[$selectName])) {
            $paramsCommand = $this->jobSchedulerManager->getJobsParams()[$selectName];
        } else {
            $paramsCommand = null;
        }

        return $paramsCommand;
    }
}
