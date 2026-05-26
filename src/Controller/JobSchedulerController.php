<?php

namespace App\Controller;

use Exception;
use App\Entity\User;
use App\Entity\Config;
use App\Entity\JobScheduler;
use App\Form\JobSchedulerType;
use App\Form\JobSchedulerCronType;
use App\Repository\UserRepository;
use App\Repository\ConfigRepository;
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
use App\Command\SynchroCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\BufferedOutput;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Service\DebugLogger;

/**
 * @Route("/rule/jobscheduler")
 */
class JobSchedulerController extends AbstractController
{
    private JobSchedulerManager $jobSchedulerManager;
    private EntityManagerInterface $entityManager;
    private ToolsManager $tools;
    private SynchroCommand $synchroCommand;
    private DebugLogger $debugLogger;

    public function __construct(EntityManagerInterface $entityManager, JobSchedulerManager $jobSchedulerManager,ToolsManager $tools, SynchroCommand $synchroCommand, DebugLogger $debugLogger)
    {
        $this->entityManager = $entityManager;
        $this->jobSchedulerManager = $jobSchedulerManager;
        $this->tools = $tools;
        $this->synchroCommand = $synchroCommand;
        $this->debugLogger = $debugLogger;
    }

    /**
     * @Route("/", name="jobscheduler")
     */
    public function index(): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {

        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        $entities = $this->entityManager->getRepository(JobScheduler::class)->findBy([], ['jobOrder' => 'ASC']);

        return $__debugReturn = $this->render('JobScheduler/index.html.twig', [
            'entities' => $entities,
        ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/create", name="jobscheduler_create", methods={"POST"})
     */
    public function create(Request $request)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

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

            return $__debugReturn = $this->redirect($this->generateUrl('jobscheduler', ['id' => $entity->getId()]));
        }

        return $__debugReturn = $this->render('JobScheduler/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    private function createCreateForm(JobScheduler $entity): FormInterface
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['entity' => $entity]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        $form = $this->createForm(JobSchedulerType::class, $entity, [
            'action' => $this->generateUrl('jobscheduler_create'),
            'method' => 'POST',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'jobscheduler.new']);

        return $__debugReturn = $form;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/new", name="jobscheduler_new")
     */
    public function new(): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        $entity = new JobScheduler();
        $form = $this->createCreateForm($entity);

        return $__debugReturn = $this->render('JobScheduler/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/{id}/show", name="jobscheduler_show")
     */
    public function show($id): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['id' => $id]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        $entity = $this->entityManager->getRepository(JobScheduler::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JobScheduler entity.');
        }

        $userRepository = $this->entityManager->getRepository(User::class);
        $user_created = $userRepository->find($entity->getcreatedBy());
        $user_modified = $userRepository->find($entity->getmodifiedBy());

        return $__debugReturn = $this->render('JobScheduler/show.html.twig', [
            'entity' => $entity,
            'user_created' => $user_created,
            'user_modified' => $user_modified,
        ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/{id}/edit", name="jobscheduler_edit")
     */
    public function edit($id): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['id' => $id]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        $entity = $this->entityManager->getRepository(JobScheduler::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JobScheduler entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $__debugReturn = $this->render('JobScheduler/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * Creates a form to edit a JobScheduler entity.
     */
    private function createEditForm(JobScheduler $entity): FormInterface
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['entity' => $entity]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        $form = $this->createForm(JobSchedulerType::class, $entity, [
            'action' => $this->generateUrl('jobscheduler_update', ['id' => $entity->getId()]),
            'method' => 'POST',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'jobscheduler.update']);

        return $__debugReturn = $form;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * Edits an existing JobScheduler entity.
     *
     * @Route("/{id}/update", name="jobscheduler_update", methods={"POST", "PUT"})
     */
    public function update(Request $request, $id)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'id' => $id]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        $entity = $this->entityManager->getRepository(JobScheduler::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JobScheduler entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $this->entityManager->flush();

            return $__debugReturn = $this->redirect($this->generateUrl('jobscheduler'));
        }

        return $__debugReturn = $this->render('JobScheduler/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/{id}/delete", name="jobscheduler_delete", methods={"POST", "DELETE"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(Request $request, $id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'id' => $id]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        $id = $request->get('id');
        $entity = $this->entityManager->getRepository(JobScheduler::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JobScheduler entity.');
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        return $__debugReturn = $this->redirect($this->generateUrl('jobscheduler'));
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * Creates a form to delete a JobScheduler entity by id.
     */
    private function createDeleteForm($id): FormInterface
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['id' => $id]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        return $__debugReturn = $this->createFormBuilder()
            ->setAction($this->generateUrl('jobscheduler_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/getFieldsSelect", name="jobscheduler_input", methods={"GET"}, options={"expose"=true})
     */
    public function getFieldsSelect(Request $request): JsonResponse
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request]);
        $__debugReturn = null;
        try {
        $select = [];
        if ($request->isXmlHttpRequest() && 'GET' == $request->getMethod()) {
            $select = $this->getData($request->query->get('type'));
        }

        return $__debugReturn = $this->json($select);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @throws Exception
     */
    private function getData($selectName)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['selectName' => $selectName]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        $paramsCommand = null;
        if (isset($this->jobSchedulerManager->getJobsParams()[$selectName])) {
            $paramsCommand = $this->jobSchedulerManager->getJobsParams()[$selectName];
        } else {
            $paramsCommand = null;
        }

        return $__debugReturn = $paramsCommand;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

     /**
     * New creates job scheduler with cron.
     *
     * @Route("/crontab", name="crontab")
     */
    public function createActionWithCron(Request $request, TranslatorInterface $translator)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'translator' => $translator]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

         $command = '';
        $period = '*/5 * * * *';
        $crontabForm = new CronJob($command, $period);
        $entity = $this->entityManager->getRepository(CronJob::class)->findAll();
        $form = $this->createForm(JobSchedulerCronType::class, $crontabForm);
         
        // get the data from the request as command aren't available from the form (command is private and can't be set using the custom method setCommand)
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
                // use the static method create because command can be set

            $command          = $form->get('command')->getData();
            $period           = $form->get('period')->getData();
            $description      = $form->get('description')->getData();
            $enable           = $form->get('enable')->getData();
            $runningInstances = $form->get('runningInstances')->getData();
            $maxInstances     = $form->get('maxInstances')->getData();

            if ($command === '' || $period === '') {
                $this->addFlash('jobscheduler.create.danger', $translator->trans('crontab.incorrect'));
                return $__debugReturn = $this->render('JobScheduler/crontab.html.twig', [
                    'entity' => $entity,
                    'form' => $form->createView(),
                ]);
            }

            try {
                $crontab = CronJob::create($command, $period);
                if (method_exists($crontab, 'setDescription'))    $crontab->setDescription($description);
                if (method_exists($crontab, 'setEnable'))        $crontab->setEnable($enable);
                if (method_exists($crontab, 'setMaxInstances'))   $crontab->setMaxInstances($maxInstances);
                if (method_exists($crontab, 'increaseRunningInstances')) {
                    for ($i = 0; $i < $runningInstances; $i++) {
                        $crontab->increaseRunningInstances();
                    }
                }

                $this->entityManager->persist($crontab);
                $this->entityManager->flush();

                $this->addFlash('jobscheduler.create.success', $translator->trans('crontab.success'));
                return $__debugReturn = $this->redirectToRoute('jobscheduler_cron_list');
            } catch (\Throwable $e) {
                $this->addFlash('jobscheduler.create.danger', $translator->trans('crontab.incorrect'));
            }
        }
        return $__debugReturn = $this->render('JobScheduler/crontab.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/crontab_list", name="jobscheduler_cron_list", defaults={"page"=1})
     * @Route("/crontab_list/page-{page}", name="jobscheduler_cron_list_page", requirements={"page"="\d+"})
     */
    public function crontabList(int $page): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['page' => $page]);
        $__debugReturn = null;
        try {

        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        //Check if crontab is enabled 
        $entitiesCron = $this->entityManager->getRepository(Config::class)->findBy(['name' => 'cron_enabled']);
        $searchLimitConfig = $this->entityManager->getRepository(Config::class)->findOneBy(['name' => 'search_limit']);

        //Check the user timezone
        $timezone = $this->getUser() && $this->getUser()->getTimezone()
                ? $this->getUser()->getTimezone()
                : 'UTC';

        $entity = $this->entityManager->getRepository(CronJob::class)->findAll();
        if (!$searchLimitConfig) {
            throw $this->createNotFoundException('Missing config "search_limit"');
        }

        return $__debugReturn = $this->render('JobScheduler/crontab_list.html.twig', [
            'entity' => $entity,
            'timezone' => $timezone,
            'entitiesCron' => $entitiesCron,
        ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * Deletes a Crontab entity.
     *
     * @Route("/{id}/delete_crontab", name="crontab_delete", methods={"POST", "DELETE"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function deleteCrontab(Request $request, $id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'id' => $id]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        $id = $request->get('id');
        $entity = $this->entityManager->getRepository(CronJob::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Crontab entity.');
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        return $__debugReturn = $this->redirect($this->generateUrl('jobscheduler_cron_list'));
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * Creates a form to delete a Crontab entity by id.
     */
    private function createDeleteFormCrontab($id): FormInterface
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['id' => $id]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        return $__debugReturn = $this->createFormBuilder()
            ->setAction($this->generateUrl('crontab_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/{id}/edit_crontab", name="crontab_edit")
     */
    public function editCrontab($id): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['id' => $id]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        $entity = $this->entityManager->getRepository(CronJob::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Crontab entity.');
        }

        $editForm = $this->createEditFormCrontab($entity);
        $deleteFormCrontab = $this->createDeleteFormCrontab($id);

        return $__debugReturn = $this->render('JobScheduler/edit_crontab.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteFormCrontab->createView(),
        ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    private function createEditFormCrontab(CronJob $entity): FormInterface
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['entity' => $entity]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        return $__debugReturn = $this->createForm(JobSchedulerCronType::class, $entity, [
                'action' => $this->generateUrl('crontab_update', ['id' => $entity->getId()]),
                'method' => 'POST',
            ])
            ->add('command', TextType::class, ['disabled' => true]
        );
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * Edits an existing Crontab entity.
     *
     * @Route("/{id}/update_crontab", name="crontab_update", methods={"POST"})
     */
    public function updateCrontab(Request $request, $id)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'id' => $id]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        $entity = $this->entityManager->getRepository(CronJob::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Crontab entity.');
        }

        $editForm = $this->createEditFormCrontab($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            // Get form data using the form object
            $formData = $editForm->getData();
            
            // Get the new values from the form
            $newRunningInstances = $editForm->get('runningInstances')->getData();
            $newMaxInstances = $editForm->get('maxInstances')->getData();
            $newDescription = $editForm->get('description')->getData();
            $newPeriod = $editForm->get('period')->getData();
            $newEnable = $editForm->get('enable')->getData() ?? 0;

            // Validation: runningInstances should never be greater than maxInstances
            if ($newRunningInstances > $newMaxInstances) {
                $this->addFlash('jobscheduler.edit.danger', 'Running instances cannot be greater than max instances.');

                return $__debugReturn = $this->render('JobScheduler/edit_crontab.html.twig', [
                    'entity' => $entity,
                    'edit_form' => $editForm->createView(),
                ]);
            }

            // Update the entity
            $entity->setDescription($newDescription);
            $entity->setPeriod($newPeriod);
            $entity->setMaxInstances($newMaxInstances);
            $entity->setEnable($newEnable);

            $this->entityManager->flush();

            return $__debugReturn = $this->redirect($this->generateUrl('jobscheduler_cron_list'));
        }

        return $__debugReturn = $this->render('JobScheduler/edit_crontab.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/{id}/enable_crontab/{enable}", name="enable_crontab", methods={"POST"})
     */
    public function enableDisableCrontab(Request $request, $id, $enable): Response
    {
        // Check if it's a valid request
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'id' => $id, 'enable' => $enable]);
        $__debugReturn = null;
        try {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createAccessDeniedException('Only AJAX requests are allowed');
        }

        // Validate enable parameter
        if (!in_array($enable, ['0', '1'])) {
            return $__debugReturn = new JsonResponse([
                'success' => false,
                'message' => 'Invalid enable value'
            ], 400);
        }

        $entity = $this->entityManager->getRepository(CronJob::class)->find($id);
        if (!$entity) {
            return $__debugReturn = new JsonResponse([
                'success' => false,
                'message' => 'Crontab not found'
            ], 404);
        }

        try {
            $entity->setEnable((bool)$enable);
            $this->entityManager->flush();

            return $__debugReturn = new JsonResponse([
                'success' => true,
                'message' => 'Crontab status updated successfully'
            ]);
        } catch (\Exception $e) {
            return $__debugReturn = new JsonResponse([
                'success' => false,
                'message' => 'Failed to update crontab status: ' . $e->getMessage()
            ], 500);
        }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/{id}/show_crontab", name="crontab_show", defaults={"page"=1})
     * @Route("/{id}/show_crontab/page-{page}", name="crontab_show_page", requirements={"page"="\d+"})
     */
    public function showCrontab($id, int $page): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['id' => $id, 'page' => $page]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }
        $entity = $this->entityManager->getRepository(CronJob::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find crontab entity.');
        }

        $searchLimitConfig = $this->entityManager->getRepository(Config::class)->findOneBy(['name' => 'search_limit']);

        if (!$searchLimitConfig) {
            throw $this->createNotFoundException('Missing config "search_limit"');
        }

        $searchLimit = (int) $searchLimitConfig->getValue();
        $query = $this->entityManager->createQuery(
            'SELECT c
            FROM Shapecode\Bundle\CronBundle\Entity\CronJobResult c
            WHERE c.cronJob = :cronJob
            ORDER BY c.runAt DESC'
        )
        ->setParameter('cronJob', $id)
        ->setMaxResults($searchLimit);

        $limitedResults = $query->getResult();

        $adapter = new \Pagerfanta\Adapter\ArrayAdapter($limitedResults);
        $pager   = new \Pagerfanta\Pagerfanta($adapter);
        $pager->setMaxPerPage(3);
        $pager->setCurrentPage($page);

        return $__debugReturn = $this->render('JobScheduler/show_crontab.html.twig', [
            'entity' => $entity,
            'pager'  => $pager,
        ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
        
    /**
     * Disables all cron jobs.
     *
     * @Route("/massdisable", name="massdisable")
     */
    public function disableAllTask(Request $request, TranslatorInterface $translator)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'translator' => $translator]);
        $__debugReturn = null;
        try {
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
            return $__debugReturn = $this->redirectToRoute('jobscheduler_cron_list');
        } catch (Exception $e) {
            $failure = $translator->trans('crontab.incorrect');
            $this->addFlash('jobscheduler.disableAll.danger', $failure);

            return $__debugReturn = $this->redirectToRoute('jobscheduler_cron_list');
        }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * Enables all cron jobs.
     *
     * @Route("/massenable", name="massenable")
     */
    public function enableAllTask(Request $request, TranslatorInterface $translator)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'translator' => $translator]);
        $__debugReturn = null;
        try {
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
            return $__debugReturn = $this->redirectToRoute('jobscheduler_cron_list');
        } catch (Exception $e) {
            $failure = $translator->trans('crontab.incorrect');
            $this->addFlash('jobscheduler.enableAll.danger', $failure);

            return $__debugReturn = $this->redirectToRoute('jobscheduler_cron_list');
        }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
       /**
     * disable all cron jobs.
     *
     * @Route("/massdisableCron", name="massdisableCron")
     */
    public function disableAllCrons(Request $request, TranslatorInterface $translator)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'translator' => $translator]);
        $__debugReturn = null;
        try {

        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

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
            $this->addFlash('jobscheduler.disableAllCrons.success', $translator->trans('crontab.disableAllCrons'));
            return $__debugReturn = $this->redirectToRoute('jobscheduler_cron_list');
        } catch (Exception $e) {
            $failure = $translator->trans('crontab.incorrect');
            $this->addFlash('jobscheduler.disableAllCrons.danger', $failure);
            return $__debugReturn = $this->redirectToRoute('jobscheduler_cron_list');
        }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

           /**
     * Enables all cron jobs.
     *
     * @Route("/massenableCron", name="massenableCron")
     */
    public function enableAllCrons(Request $request, TranslatorInterface $translator)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'translator' => $translator]);
        $__debugReturn = null;
        try {
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
            $this->addFlash('jobscheduler.enableAllCrons.success', $translator->trans('crontab.enableAllCrons'));
            return $__debugReturn = $this->redirectToRoute('jobscheduler_cron_list');
        } catch (Exception $e) {
            $failure = $translator->trans('crontab.incorrect');
            $this->addFlash('jobscheduler.enableAllCrons.danger', $failure);
            return $__debugReturn = $this->redirectToRoute('jobscheduler_cron_list');
        }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @Route("/execute-terminal-command", name="executeTerminalCommand")
     */
    public function executeTerminalCommand(Request $request, SynchroCommand $synchroCommand): JsonResponse
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request, 'synchroCommand' => $synchroCommand]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        $command = $request->request->get('command');
        
        // If the command starts with "synchro", handle it with SynchroCommand
        if (strpos($command, 'synchro') === 0) {
            // Extract the rule ID from the command
            $parts = explode(' ', $command);
            $ruleId = $parts[1] ?? null;

            if (!$ruleId) {
                return $__debugReturn = new JsonResponse(['error' => 'Rule ID is required']);
            }

            ob_start();
            $input = new ArrayInput([
                'rule' => $ruleId,
                'force' => false,
            ]);
            $output = new BufferedOutput();
            $synchroCommand->run($input, $output);
            $result = ob_get_clean();

            return $__debugReturn = new JsonResponse(['result' => $output->fetch()]);
        }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
        
        // ... handle other commands as before ...
    }

    /**
     * @Route("/crontab/results", name="crontab_results_partial")
     */
    public function loadResults(Request $request): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request]);
        $__debugReturn = null;
        try {
        if (!$this->tools->isPremium()) {
            return $__debugReturn = $this->redirectToRoute('premium_list');
        }

        $searchLimitConfig = $this->entityManager->getRepository(Config::class)->findOneBy(['name' => 'search_limit']);

        if (!$searchLimitConfig) {
            throw $this->createNotFoundException('Missing config "search_limit"');
        }

        $searchLimit = (int) $searchLimitConfig->getValue();
        $query = $this->entityManager->createQuery(
            'SELECT c
            FROM Shapecode\Bundle\CronBundle\Entity\CronJobResult c
            ORDER BY c.runAt DESC')->setMaxResults($searchLimit);
        $limitedResults = $query->getResult();

        $adapter = new \Pagerfanta\Adapter\ArrayAdapter($limitedResults);
        $pager   = new \Pagerfanta\Pagerfanta($adapter);
        $pager->setMaxPerPage(10);
        $pager->setCurrentPage($request->query->getInt('page', 1));

        return $__debugReturn = $this->render('JobScheduler/_crontab_results_table.html.twig', [
            'pager' => $pager,
        ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}
