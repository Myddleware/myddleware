<?php

namespace App\Controller;

use App\Manager\DocumentManager;
use App\Manager\FormulaManager;
use App\Manager\JobManager;
use App\Manager\RuleManager;
use App\Manager\SolutionManager;
use App\Repository\DocumentRepository;
use App\Repository\JobRepository;
use App\Repository\RuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api', name: 'api_')]
class ApiController extends AbstractController
{
    public ParameterBagInterface $parameterBag;
    private RuleRepository $ruleRepository;
    private JobRepository $jobRepository;
    private DocumentRepository $documentRepository;
    private string $env;
    private KernelInterface $kernel;
    private LoggerInterface $logger;
    private JobManager $jobManager;
    private EntityManagerInterface $entityManager;
    private FormulaManager $formulaManager;
    private SolutionManager $solutionManager;
    private DocumentManager $documentManager;
    public function __construct(
        KernelInterface $kernel,
        LoggerInterface $logger,
        JobManager $jobManager,
        RuleRepository $ruleRepository,
        JobRepository $jobRepository,
        DocumentRepository $documentRepository,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
        FormulaManager $formulaManager,
        SolutionManager $solutionManager,
        DocumentManager $documentManager
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->jobRepository = $jobRepository;
        $this->documentRepository = $documentRepository;
        $this->jobManager = $jobManager;
        $this->logger = $logger;
        $this->kernel = $kernel;
        $this->env = $kernel->getEnvironment();
        $this->parameterBag = $parameterBag;
        $this->entityManager = $entityManager;
        $this->formulaManager = $formulaManager;
        $this->solutionManager = $solutionManager;
        $this->documentManager = $documentManager;
    }
    #[Route(path: '/synchro', name: 'synchro', methods: ['POST'])]
    public function synchroAction(Request $request) : JsonResponse
    {
        try {
            $return = [];
            $return['error'] = '';

            // Get input data
            $data = json_decode($request->getContent(), true);

            // Check parameter
            if (empty($data['rule'])) {
                throw new Exception('Rule is missing. Please specify a rule id or set ALL to run all active rules. ');
            }

            // Prepare command
            $application = new Application($this->kernel);
            $application->setAutoExit(false);
            $arguments = [
                'command' => 'myddleware:synchro',
                'api' => 1,
                '--env' => $this->env,
            ];

            // Prepare input/output parameters
            $arguments['rule'] = $data['rule'];
            $input = new ArrayInput($arguments);
            $output = new BufferedOutput();

            // Run the command
            $application->run($input, $output);

            // Get result command
            $content = $output->fetch();
            if (empty($content)) {
                throw new Exception('No response from Myddleware. ');
            }
            // Log the result
            $this->logger->info(print_r($content, true));

            // Get the job task id, result is 1;<jobId>.....
            $return['jobId'] = substr($content, 2, 23);
            $job = $this->jobRepository->find($return['jobId']);
            // Get the job statistics
            $jobData = $this->jobManager->getLogData($job);
            if (!empty($jobData['jobError'])) {
                throw new Exception('Failed to get the job statistics. '.$jobData['jobError']);
            }
            $return['jobData'] = $jobData;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $return['error'] = $e->getMessage();
        }
        // Send the response
        return $this->json($return);
    }
    #[Route(path: '/read_record', name: 'read_record', methods: ['POST'])]
    public function readRecordAction(Request $request) : JsonResponse
    {
        try {
            $return = [];
            $return['error'] = '';

            // Get input data
            $data = $request->request->all();

            // Check parameter
            if (empty($data['rule'])) {
                throw new Exception('Rule is missing. Please specify a ruleId parameter. ');
            }
            if (empty($data['filterQuery'])) {
                throw new Exception('Filter query is missing. filterQuery is a field used to read data in the source application, eg : id. ');
            }
            if (empty($data['filterValues'])) {
                throw new Exception('Filter value is missing. filterValues is the value corresponding to the filterQuery field. ');
            }

            // Prepare command
            $application = new Application($this->kernel);
            $application->setAutoExit(false);
            $arguments = [
                'command' => 'myddleware:readrecord',
                'api' => 1,
                '--env' => $this->env,
            ];

            // Prepare input/output parameters
            $arguments['ruleId'] = $data['rule'];
            $arguments['filterQuery'] = $data['filterQuery'];
            $arguments['filterValues'] = $data['filterValues'];
            $return = $this->getReturn($arguments, $application, $return);

            // Get the job statistics
            $job = $this->jobRepository->find($return['jobId']);
            $jobData = $this->jobManager->getLogData($job);
            if (!empty($jobData['jobError'])) {
                throw new Exception('Failed to get the job statistics. '.$jobData['jobError']);
            }
            $return['jobData'] = $jobData;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $return['error'] = $e->getMessage();
        }
        // Send the response
        return $this->json($return);
    }
    #[Route(path: '/delete_record', name: 'delete_record', methods: ['POST'])]
    public function deleteRecordAction(Request $request) : JsonResponse
    {
        $return = [];
        $return['error'] = '';
        $connection = $this->container->get('database_connection');
        try {
            $connection->beginTransaction(); // -- BEGIN TRANSACTION
            // Get input data
            $data = $request->request->all();

            // Check parameter
            if (empty($data['rule'])) {
                throw new Exception('Rule is missing. Please specify a ruleId parameter. ');
            }
            if (empty($data['recordId'])) {
                throw new Exception('recordId is missing. recordId is the id of the record you want to delete. ');
            }

            // Set the document values
            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'recordId':
                        $docParam['values']['id'] = $value;
                        break;
                    case 'reference':
                        $docParam['values']['date_modified'] = $value;
                        break;
                    case 'rule':
                        break;
                    default:
                        $docParam['values'][$key] = $value;
                }
            }
            $docParam['values']['myddleware_deletion'] = true; // Force deleted record type

            // Create job instance
            $job = $this->container->get('myddleware_job.job');
            $job->setApi(1);
            $job->initJob('Delete record '.$data['recordId'].' in rule '.$data['rule']);

            // Instantiate the rule
            $ruleParam['ruleId'] = $data['rule'];
            $ruleParam['jobId'] = $job->id;
            $ruleParam['api'] = 1;
            $rule = new RuleManager(
                $this->logger,
                $connection,
                $this->entityManager,
                $this->parameterBag,
                // $ruleParam,
                $this->formulaManager,
                $this->solutionManager,
                $this->documentManager
            );

            $document = $rule->generateDocuments($data['recordId'], false, $docParam);
            // Stop the process if error during the data transfer creation as we won't be able to manage it in Myddleware
            if (!empty($document->error)) {
                throw new Exception('Error during data transfer creation (rule '.$data['rule'].')  : '.$document->error.'. ');
            }
            $connection->commit(); // -- COMMIT TRANSACTION
        } catch (Exception $e) {
            $connection->rollBack(); // -- ROLLBACK TRANSACTION
            $this->logger->error($e->getMessage());
            $return['error'] .= $e->getMessage();
            // Stop the process if document hasn't been created
            return $this->json($return);
        }
        // Send the document just created
        try {
            // db transaction managed into the method actionDocument
            $errors = $rule->actionDocument($document[0]->id, 'rerun');
            // Check errors, but in this case the data transfer is created but Myddleware hasn't been able to send it.
            // We don't roll back the work here as it will be possible to manage the data transfer in Myddleware
            if (!empty($errors)) {
                throw new Exception('Document in error (rule '.$data['rule'].')  : '.$errors[0].'. ');
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $return['error'] .= $e->getMessage();
        }
        // Close job if it has been created
        try {
            $connection->beginTransaction(); // -- BEGIN TRANSACTION
            if (true === $job->createdJob) {
                $job->closeJob();
            }
            // Get the job statistics even if the job has failed
            if (!empty($job->id)) {
                $return['jobId'] = $job->id;
                $jobData = $job->getLogData(1);
                if (!empty($jobData['jobError'])) {
                    $return['error'] .= $jobData['jobError'];
                }
                $return['jobData'] = $jobData;
            }
            $connection->commit(); // -- COMMIT TRANSACTION
        } catch (Exception $e) {
            $connection->rollBack(); // -- ROLLBACK TRANSACTION
            $this->logger->error('Failed to get the job statistics. '.$e->getMessage());
            $return['error'] .= 'Failed to get the job statistics. '.$e->getMessage();
        }
        // Send the response
        return $this->json($return);
    }
    #[Route(path: '/mass_action', name: 'mass_action', methods: ['POST'])]
    public function massActionAction(Request $request) : JsonResponse
    {
        try {
            $return = [];
            $return['error'] = '';

            // Get input data
            $data = $request->request->all();

            // Check parameter
            if (empty($data['action'])) {
                throw new Exception('action is missing. Please specify an action : rerun, cancel, remove, restore or changeStatus. ');
            }
            if (empty($data['dataType'])) {
                throw new Exception('dataType is missing. Please specify a data type : rule or document');
            }
            if (empty($data['ids'])) {
                throw new Exception('ids is missing. Please specify rule or document ids separated by comma. ');
            }

            // Prepare command
            $application = new Application($this->kernel);
            $application->setAutoExit(false);
            $arguments = [
                'command' => 'myddleware:massaction',
                'api' => 1,
                '--env' => $this->env,
            ];

            // Prepare input/output parameters
            $arguments['action'] = $data['action'];
            $arguments['dataType'] = $data['dataType'];
            $arguments['ids'] = $data['ids'];
            $arguments['forceAll'] = (empty($data['forceAll']) ? '' : $data['forceAll']);
            $arguments['fromStatus'] = (empty($data['fromStatus']) ? '' : $data['fromStatus']);
            $arguments['toStatus'] = (empty($data['toStatus']) ? '' : $data['toStatus']);

            $return = $this->getReturn($arguments, $application, $return);

            // Get the job statistics
            $job = $this->container->get('myddleware_job.job');
            $job->id = $return['jobId'];
            $jobData = $job->getLogData(1);
            if (!empty($jobData['jobError'])) {
                throw new Exception('Failed to get the job statistics. '.$jobData['jobError']);
            }
            $return['jobData'] = $jobData;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $return['error'] = $e->getMessage();
        }
        // Send the response
        return $this->json($return);
    }
    #[Route(path: '/rerun_error', name: 'rerun_error', methods: ['POST'])]
    public function rerunErrorAction(Request $request) : JsonResponse
    {
        try {
            $return = [];
            $return['error'] = '';

            // Get input data
            $data = $request->request->all();

            // Check parameter
            if (empty($data['limit'])) {
                throw new Exception('limit parameter is missing. Please specify a number to limit the number of data transfer the program has to rerun. ');
            }
            if (empty($data['attempt'])) {
                throw new Exception('attempt parameter is missing. Please specify the maximum number of attempt. If you set 10, the program will rerun only data transfer with attempt <= 10. ');
            }

            // Prepare command
            $application = new Application($this->kernel);
            $application->setAutoExit(false);
            $arguments = [
                'command' => 'myddleware:rerunerror',
                'api' => 1,
                '--env' => $this->env,
            ];

            // Prepare input/output parameters
            $arguments['limit'] = $data['limit'];
            $arguments['attempt'] = $data['attempt'];
            $return = $this->getReturn($arguments, $application, $return);

            // Get the job statistics
            $job = $this->container->get('myddleware_job.job');
            $job->id = $return['jobId'];
            $jobData = $job->getLogData(1);
            $return['jobData'] = $jobData;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $return['error'] = $e->getMessage();
        }
        // Send the response
        return $this->json($return);
    }
    #[Route(path: '/statistics', name: 'statistics', methods: ['POST'])]
    public function statisticsAction(Request $request) : JsonResponse
    {
        try {
            $return = [];
            $home = $this->container->get('myddleware.home');

            $return['errorByRule'] = $this->ruleRepository->errorByRule();
            $return['countTypeDoc'] = $this->documentRepository->countTypeDoc();
            $return['listJobDetail'] = $this->jobRepository->listJobDetail();
            $return['countTransferHisto'] = $home->countTransferHisto();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $return['error'] = $e->getMessage();
        }
        // Send the response
        return $this->json($return);
    }
    /**
     * @throws Exception
     */
    public function getReturn(array $arguments, Application $application, array $return): array
    {
        $input = new ArrayInput($arguments);
        $output = new BufferedOutput();

        // Run the command
        $application->run($input, $output);

        // Get result command
        $content = $output->fetch();
        if (empty($content)) {
            throw new Exception('No response from Myddleware. ');
        }
        // Log the result
        $this->logger->info(print_r($content, true));

        // Get the job task id, result is <jobId>.....
        $return['jobId'] = substr($content, 0, 23);

        return $return;
    }
}
