<?php

namespace App\Controller;

use Exception;
use App\Entity\User;
use App\Entity\Config;
use App\Entity\JobScheduler;
use App\Form\JobSchedulerType;
use App\Form\JobSchedulerCronType;
use App\Repository\UserRepository;
use App\Manager\JobSchedulerManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Shapecode\Bundle\CronBundle\Entity\CronJob as CronJob;
use Shapecode\Bundle\CronBundle\Entity\CronJobResult;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use App\Manager\ToolsManager;


/**
 * @Route("/rule/jobscheduler")
 */
class JobSchedulerController extends AbstractController
{
    private JobSchedulerManager $jobSchedulerManager;
    private EntityManagerInterface $entityManager;
    private ToolsManager $tools;

    public function __construct(EntityManagerInterface $entityManager, JobSchedulerManager $jobSchedulerManager,ToolsManager $tools)
    {
        $this->entityManager = $entityManager;
        $this->jobSchedulerManager = $jobSchedulerManager;
        $this->tools = $tools;
    }

    /**
     * @Route("/", name="jobscheduler")
     */
    public function index(): Response
    {

        

        $entities = $this->entityManager->getRepository(JobScheduler::class)->findBy([], ['jobOrder' => 'ASC']);

        return $this->render('JobScheduler/index.html.twig', [
            'entities' => $entities,
        ]);
    }

    /**
     * @Route("/create", name="jobscheduler_create", methods={"POST"})
     */
    public function create(Request $request)
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

            $active = 1 == $form->get('active')->getData();
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

    private function createCreateForm(JobScheduler $entity): FormInterface
    {
        $form = $this->createForm(JobSchedulerType::class, $entity, [
            'action' => $this->generateUrl('jobscheduler_create'),
            'method' => 'POST',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'jobscheduler.new']);

        return $form;
    }

    /**
     * @Route("/new", name="jobscheduler_new")
     */
    public function new(): Response
    {
        $entity = new JobScheduler();
        $form = $this->createCreateForm($entity);

        return $this->render('JobScheduler/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/show", name="jobscheduler_show")
     */
    public function show($id): Response
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
     * @Route("/{id}/edit", name="jobscheduler_edit")
     */
    public function edit($id): Response
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
     */
    private function createEditForm(JobScheduler $entity): FormInterface
    {
        $form = $this->createForm(JobSchedulerType::class, $entity, [
            'action' => $this->generateUrl('jobscheduler_update', ['id' => $entity->getId()]),
            'method' => 'POST',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'jobscheduler.update']);

        return $form;
    }

    /**
     * Edits an existing JobScheduler entity.
     *
     * @Route("/{id}/update", name="jobscheduler_update", methods={"POST", "PUT"})
     */
    public function update(Request $request, $id)
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
     * @Route("/{id}/delete", name="jobscheduler_delete", methods={"GET", "DELETE"})
     */
    public function delete(Request $request, $id): \Symfony\Component\HttpFoundation\RedirectResponse
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
     */
    private function createDeleteForm($id): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('jobscheduler_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm();
    }

    /**
     * @Route("/getFieldsSelect", name="jobscheduler_input", methods={"GET"}, options={"expose"=true})
     */
    public function getFieldsSelect(Request $request): JsonResponse
    {
        $select = [];
        if ($request->isXmlHttpRequest() && 'GET' == $request->getMethod()) {
            $select = $this->getData($request->query->get('type'));
        }

        return $this->json($select);
    }

    /**
     * @throws Exception
     */
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
                
                for ($i = 0; $i < $formParam['runningInstances']; $i++) {
                    $crontab->increaseRunningInstances();
                }
                
                $crontab->setMaxInstances((int) $formParam['maxInstances']);
                
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
     * @Route("/crontab_list", name="jobscheduler_cron_list", defaults={"page"=1})
     * @Route("/crontab_list/page-{page}", name="jobscheduler_cron_list_page", requirements={"page"="\d+"})
     */
    public function crontabList(int $page): Response
    {
        //Check if crontab is enabled 
        $entitiesCron = $this->entityManager->getRepository(Config::class)->findBy(['name' => 'cron_enabled']);
		$searchLimit = $this->entityManager->getRepository(Config::class)->findOneBy(['name' => 'search_limit'])->getValue();

        //Check the user timezone
        if ($timezone = '') {
            $timezone = 'UTC';
        } else {
            $timezone = $this->getUser()->getTimezone();
        }

        $entity = $this->entityManager->getRepository(CronJob::class)->findAll();

        // Pagination for cron_job_result
        $query = $this->entityManager->createQuery(
            'SELECT c FROM Shapecode\Bundle\CronBundle\Entity\CronJobResult c ORDER BY c.runAt DESC'
        );
    
        $adapter = new QueryAdapter($query);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage(10);
        $pager->setCurrentPage($page);

        return $this->render('JobScheduler/crontab_list.html.twig', [
            'entity' => $entity,
            'timezone' => $timezone,
            'entitiesCron' => $entitiesCron,
            'pager' => $pager,
        ]);
    }

    /**
     * Deletes a Crontab entity.
     *
     * @Route("/{id}/delete_crontab", name="crontab_delete", methods={"GET", "DELETE"})
     */
    public function deleteCrontab(Request $request, $id): \Symfony\Component\HttpFoundation\RedirectResponse
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
     */
    private function createDeleteFormCrontab($id): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('crontab_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm();
    }

    /**
     * @Route("/{id}/edit_crontab", name="crontab_edit")
     */
    public function editCrontab($id): Response
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

    private function createEditFormCrontab(CronJob $entity): FormInterface
    {
        // Field command can't be changed
        return $this->createForm(JobSchedulerCronType::class, $entity, [
                'action' => $this->generateUrl('crontab_update', ['id' => $entity->getId()]),
                'method' => 'POST',
            ])
            ->add('command', TextType::class, ['disabled' => true]
        );
    }

    /**
     * Edits an existing Crontab entity.
     *
     * @Route("/{id}/update_crontab", name="crontab_update", methods={"POST"})
     */
    public function updateCrontab(Request $request, $id)
    {
        $entity = $this->entityManager->getRepository(CronJob::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Crontab entity.');
        }

        // Get the current running instances from the $entity
        $currentMaxInstancesString = (string) $entity->getMaxInstances();

        $currentRunningInstancesString = (string) $entity->getRunningInstances();

        // get the new value of the running instances from the request
        $newRunningInstances = $request->request->get('job_scheduler_cron')['runningInstances'];

        $newRunningInstancesInteger = (int) $newRunningInstances;

        $newDescription = $request->request->get('job_scheduler_cron')['description'];
        $newPeriod = $request->request->get('job_scheduler_cron')['period'];
        $newMaxInstances = $request->request->get('job_scheduler_cron')['maxInstances'];

        $currentEnable = $entity->isEnable();

        $newEnable = $request->request->get('job_scheduler_cron')['enable'] ?? 0;


        // if the new value is different from the current value, update the request to not update the running instances
        if ($entity->getRunningInstances() !== $newRunningInstancesInteger) {
            $request->request->set('job_scheduler_cron', ['runningInstances' => $currentRunningInstancesString, 'maxInstances' => $currentMaxInstancesString, "period" => $entity->getPeriod(), "command" => $entity->getCommand(), "description" => $entity->getDescription(), "enable" => $entity->isEnable()]);
        }

        $editForm = $this->createEditFormCrontab($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('jobscheduler_cron_list'));
        } else if ($editForm->isSubmitted() && !($editForm->isValid())) {
            // do an sql statement to update the running instances
            $this->entityManager->getConnection()->executeQuery('UPDATE cron_job SET running_instances = :running_instances,
            max_instances = :max_instances,
            period = :period,
            description = :description,
            enable = :enable
            
            WHERE id = :id', 
            ['running_instances' => $newRunningInstances,
             'id' => $id,
            'max_instances' => $newMaxInstances,
            'period' => $newPeriod,
            'description' => $newDescription,
            'enable' => $newEnable
            ]);
            $this->entityManager->flush();

            // redirect to the show view
            return $this->redirect($this->generateUrl('jobscheduler_cron_list'));
        }

        return $this->render('JobScheduler/edit_crontab.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ]);
    }

    /**
     * @Route("/{id}/show_crontab", name="crontab_show", defaults={"page"=1})
     * @Route("/{id}/show_crontab/page-{page}", name="crontab_show_page", requirements={"page"="\d+"})
     */
    public function showCrontab($id, int $page): Response
    {
        $entity = $this->entityManager->getRepository(CronJob::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find crontab entity.');
        }

        $query = $this->entityManager->createQuery(
            'SELECT c FROM Shapecode\Bundle\CronBundle\Entity\CronJobResult c WHERE c.cronJob = :cronJob ORDER BY c.runAt DESC'
        )->setParameter('cronJob', $id);

        $adapter = new QueryAdapter($query);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage(10);
        $pager->setCurrentPage($page);

        return $this->render('JobScheduler/show_crontab.html.twig', [
            'entity' => $entity,
            'pager' => $pager,
        ]);
    }

    
    /**
     * Disables all cron jobs.
     *
     * @Route("/massdisable", name="massdisable")
     */
    public function disableAllTask(Request $request, TranslatorInterface $translator)
    {
        try {
            $entities = $this->entityManager->getRepository(CronJob::class)->findBy(["enable" => 1]);
            if (!($entities)) {
                throw new Exception("Couldn't fetch Cronjobs");
            }
            foreach ($entities as $entity) {
                $entity->setEnable(0);
                $this->entityManager->persist($entity);
            }
            $this->entityManager->flush();
            return $this->redirectToRoute('jobscheduler_cron_list');
        } catch (Exception $e) {
            $failure = $translator->trans('crontab.incorrect');
            $this->addFlash('error', $failure);

            return $this->redirectToRoute('jobscheduler_cron_list');
        }
    }

    /**
     * Enables all cron jobs.
     *
     * @Route("/massenable", name="massenable")
     */
    public function enableAllTask(Request $request, TranslatorInterface $translator)
    {
        try {
            $entities = $this->entityManager->getRepository(CronJob::class)->findBy(["enable" => 0]);
            if (!($entities)) {
                throw new Exception("Couldn't fetch Cronjobs");
            }
            foreach ($entities as $entity) {
                $entity->setEnable(1);
                $this->entityManager->persist($entity);
            }
            $this->entityManager->flush();
            return $this->redirectToRoute('jobscheduler_cron_list');
        } catch (Exception $e) {
            $failure = $translator->trans('crontab.incorrect');
            $this->addFlash('error', $failure);

            return $this->redirectToRoute('jobscheduler_cron_list');
        }
    }
       /**
     * disable all cron jobs.
     *
     * @Route("/massdisableCron", name="massdisableCron")
     */
    public function disableAllCrons(Request $request, TranslatorInterface $translator)
    {
        try {
            $entities = $this->entityManager->getRepository(Config::class)->findBy(['name' => 'cron_enabled']);
            if (!($entities)) {
                throw new Exception("Couldn't fetch Cronjobs");
            }
            foreach ($entities as $entity) {
                $entity->setvalue(0);
                $this->entityManager->persist($entity);
            }
            $this->entityManager->flush();
            $success = $translator->trans('crontab.disableAllCrons');
            $this->addFlash('success', $success);
            return $this->redirectToRoute('jobscheduler_cron_list');
        } catch (Exception $e) {
            $failure = $translator->trans('crontab.incorrect');
            $this->addFlash('error', $failure);
            return $this->redirectToRoute('jobscheduler_cron_list');
        }
    }

           /**
     * Enables all cron jobs.
     *
     * @Route("/massenableCron", name="massenableCron")
     */
    public function enableAllCrons(Request $request, TranslatorInterface $translator)
    {
        try {
            $entities = $this->entityManager->getRepository(Config::class)->findBy(['name' => 'cron_enabled']);
            if (!($entities)) {
                throw new Exception("Couldn't fetch Cronjobs");
            }
            foreach ($entities as $entityCron) {
                $entityCron->setvalue(1);
                $this->entityManager->persist($entityCron);
            }
            $this->entityManager->flush();
            // Message success
            $success = $translator->trans('crontab.enableAllCrons');
            $this->addFlash('success', $success);
            return $this->redirectToRoute('jobscheduler_cron_list');
        } catch (Exception $e) {
            $failure = $translator->trans('crontab.incorrect');
            $this->addFlash('error', $failure);
            return $this->redirectToRoute('jobscheduler_cron_list');
        }
    }

}
