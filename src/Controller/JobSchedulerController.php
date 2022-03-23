<?php

namespace App\Controller;

use App\Entity\JobScheduler;
use App\Entity\User;
use App\Form\JobSchedulerCronType;
use App\Form\JobSchedulerType;
use App\Manager\JobSchedulerManager;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Shapecode\Bundle\CronBundle\Entity\CronJob as CronJob;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    /**
     * New creates job scheduler with cron.
     *
     * @Route("/crontab", name="crontab")
     */
    public function createActionWithCron(Request $request, TranslatorInterface $translator)
    {
        try {
            $command = '';
            $period = ' */5 * * * *';
            $crontabForm = new CronJob($command, $period);
            $entity = $this->entityManager->getRepository(CronJob::class)->findAll();
            $form = $this->createForm(JobSchedulerCronType::class, $crontabForm);

            // get the data from the request as command aren't available from the form (command is private and can't be set using the custom method setCommand)
            $formParam = $request->request->get('job_scheduler_cron');
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // use the static method create because command can be set
                $crontab = CronJob::create($formParam['command'], $formParam['period']);
                $crontab->setDescription($formParam['description']);
                $this->entityManager->persist($crontab);
                $this->entityManager->flush();
                $success = $translator->trans('crontab.success');
                $this->addFlash('success', $success);

                return $this->redirectToRoute('jobscheduler_cron_list');
            } else {
                return $this->render('JobScheduler/crontab.html.twig', [
                    'entity' => $entity,
                    'form' => $form->createView(),
                ]);
            }
        } catch (Exception $e) {
            $failure = $translator->trans('crontab.incorrect');
            $this->addFlash('error', $failure);

            return $this->redirectToRoute('jobscheduler_cron_list');
        }
    }

    /**
     * @Route("/crontab_list", name="jobscheduler_cron_list")
     */
    public function crontabList()
    {
        //Check the user timezone
        if ($timezone = '') {
            $timezone = 'UTC';
        } else {
            $timezone = $this->getUser()->getTimezone();
        }

        $entity = $this->entityManager->getRepository(CronJob::class)->findAll();

        return $this->render('JobScheduler/crontab_list.html.twig', [
            'entity' => $entity,
            'timezone' => $timezone,
        ]);
    }

    /**
     * Deletes a Crontab entity.
     *
     * @Route("/{id}/delete_crontab", name="crontab_delete", methods={"GET", "DELETE"})
     *
     * @param mixed $id
     */
    public function deleteActionCrontab(Request $request, $id)
    {
        $id = $request->get('id');
        $entity = $this->entityManager->getRepository(CronJob::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Crontab entity.');
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('jobscheduler_cron_list'));
    }

    /**
     * Creates a form to delete a Crontab entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteFormCrontab($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('crontab_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm();
    }

    /**
     * Displays a form to edit an existing Crontab entity.
     *
     * @Route("/{id}/edit_crontab", name="crontab_edit")
     *
     * @param mixed $id
     */
    public function editActionCrontab($id)
    {
        $entity = $this->entityManager->getRepository(CronJob::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Crontab entity.');
        }

        $editForm = $this->createEditFormCrontab($entity);
        $deleteFormCrontab = $this->createDeleteFormCrontab($id);

        return $this->render('JobScheduler/edit_crontab.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteFormCrontab->createView(),
        ]);
    }

    /**
     * Creates a form to edit a Crontab entity.
     *
     * @param CronJob $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditFormCrontab(CronJob $entity)
    {
        // Field command can't be changed
        $form = $this->createForm(JobSchedulerCronType::class, $entity, [
                'action' => $this->generateUrl('crontab_update', ['id' => $entity->getId()]),
                'method' => 'PUT',
            ])
            ->add('command', TextType::class, ['disabled' => true]
        );

        return $form;
    }

    /**
     * Edits an existing Crontab entity.
     *
     * @Route("/{id}/update_crontab", name="crontab_update", methods={"POST", "PUT"})
     *
     * @param mixed $id
     */
    public function updateActionCronatb(Request $request, $id)
    {
        $entity = $this->entityManager->getRepository(CronJob::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Crontab entity.');
        }
        $editForm = $this->createEditFormCrontab($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('jobscheduler_cron_list'));
        }

        return $this->render('JobScheduler/edit_crontab.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ]);
    }

    /**
     * Finds and displays a crontab entity.
     *
     * @Route("/{id}/show_crontab", name="crontab_show")
     *
     * @param mixed $id
     */
    public function showActionCrontab($id)
    {
        $entity = $this->entityManager->getRepository(CronJob::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find crontab entity.');
        }

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        return $this->render('JobScheduler/show_crontab.html.twig', [
            'entity' => $entity,
        ]);
    }
}
