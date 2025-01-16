<?php

namespace App\Controller;

use App\Manager\JobManager;
use App\Manager\FormulaManager;
use App\Manager\RuleManager;
use App\Manager\DocumentManager;
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

/**
 * @Route("/api", name="api_")
 */
class ApiController extends AbstractController
{
    private RuleRepository $ruleRepository;
    private JobRepository $jobRepository;
    private DocumentRepository $documentRepository;
    private string $env;
    private KernelInterface $kernel;
    private LoggerInterface $logger;
    private JobManager $jobManager;
    private SolutionManager $solutionManager;
    private DocumentManager $documentManager;
    private FormulaManager $formulaManager;
    private ParameterBagInterface $parameterBag;
    private EntityManagerInterface $entityManager;

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
        DocumentManager $documentManager,
        SolutionManager $solutionManager
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->jobRepository = $jobRepository;
        $this->documentRepository = $documentRepository;
        $this->jobManager = $jobManager;
        $this->solutionManager = $solutionManager;
        $this->formulaManager = $formulaManager;
        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->kernel = $kernel;
        $this->env = $kernel->getEnvironment();
        $this->parameterBag = $parameterBag;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/synchro", name="synchro", methods={"POST"})
     */
    public function synchroAction(Request $request): JsonResponse
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
                'force' => (empty($data['force']) ? false : true),
                'api' => 1,
                '--env' => $this->env,
            ];

            // Prepare input/output parameters
            $arguments['rule'] = $data['rule'];
            $input = new ArrayInput($arguments);
            $output = new BufferedOutput();

            // Run the command
            $application->run($input, $output);

            // Get resut command
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
            $this->jobManager->setId($this->jobManager->getId());
            $jobData = $this->jobManager->getLogData();
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

    /**
     * @Route("/read_record", name="read_record", methods={"POST"})
     */
    public function readRecordAction(Request $request): JsonResponse
    {
        try {
            $return = [];
            $return['error'] = '';

            // Get input data
            //use request content
            $rawData = $request->getContent();
            $data = json_decode($rawData, true);

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
            $input = new ArrayInput($arguments);
            $output = new BufferedOutput();

            // Run the command
            $application->run($input, $output);

            // Get resut command
            $content = $output->fetch();
            if (empty($content)) {
                throw new Exception('No response from Myddleware. ');
            }
            // Log the result
            $this->logger->info(print_r($content, true));

            // Get the job task id, result is <jobId>.....
            $return['jobId'] = substr($content, 0, 23);

            // Get the job statistics
            $job = $this->jobRepository->find($return['jobId']);
            $this->jobManager->setId($this->jobManager->getId());
            $jobData = $this->jobManager->getLogData();
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

    /**
     * @Route("/delete_record", name="delete_record", methods={"POST"})
     */
    public function deleteRecordAction(Request $request): JsonResponse
    {
        try {
			$connection = $this->entityManager->getConnection();
            $return = [];
            $return['error'] = '';

            // Get input data
            $data = json_decode($request->getContent(), true);

            // Check parameter
            if (empty($data['rule'])) {
                throw new Exception('Rule is missing. Please specify a ruleId parameter. ');
            }
            if (empty($data['recordId'])) {
                throw new Exception('recordId is missing. recordId is the id of the record you want to delete. ');
            }

            // Create job instance
            $this->jobManager->setApi(1);
            $this->jobManager->initJob('Delete record '.$data['recordId'].' in rule '.$data['rule']);

            $rule = new RuleManager(
                $this->logger,
                $connection,
                $this->entityManager,
                $this->parameterBag,
                $this->formulaManager,
                $this->solutionManager,
                $this->documentManager
            );

			$rule->setJobId($this->jobManager->getId());
			$rule->setApi(1);
			$rule->setRule($data['rule']);
			
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
			
			// Set all fields not set to empty
			$sourceFields = $rule->getSourceFields();
			if (!empty($sourceFields)) {
				foreach ($sourceFields as $sourceField) {
					if (!array_key_exists($sourceField, $docParam['values'])) {
						$docParam['values'][$sourceField] = '';
					}
				}
			}

			// Add deletion flag
            $docParam['values']['myddleware_deletion'] = true; // Force deleted record type
			
            $document = $rule->generateDocuments($data['recordId'], false, $docParam);
            // Stop the process if error during the data transfer creation as we won't be able to manage it in Myddleware
            if (!empty($document->error)) {
                throw new Exception('Error during data transfer creation (rule '.$data['rule'].')  : '.$document->error.'. ');
            }
            // $connection->commit(); // -- COMMIT TRANSACTION
        } catch (Exception $e) {
            // $connection->rollBack(); // -- ROLLBACK TRANSACTION
            $this->logger->error($e->getMessage());
            $return['error'] .= $e->getMessage();
            // Stop the process if document hasn't been created
            return $this->json($return);
        }

        // Send the document just created if requested
		if (empty($data['asynchronousDeletion'])) {
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
		}

        // Close job if it has been created
        try {
            // $connection->beginTransaction(); // -- BEGIN TRANSACTION
            if (true === $this->jobManager->createdJob) {
                $this->jobManager->closeJob();
            }
            // Get the job statistics even if the job has failed
            if (!empty($this->jobManager->getId())) {
                $return['jobId'] = $this->jobManager->getId();
                $jobData = $this->jobManager->getLogData();
                if (!empty($jobData['jobError'])) {
                    $return['error'] .= $jobData['jobError'];
                }
                $return['jobData'] = $jobData;
            }
        } catch (Exception $e) {
            $this->logger->error('Failed to get the job statistics. '.$e->getMessage());
            $return['error'] .= 'Failed to get the job statistics. '.$e->getMessage();
        }
        // Send the response
        return $this->json($return);
    }


    /**
     * @Route("/mass_action", name="mass_action", methods={"POST"})
     */
    public function massActionAction(Request $request): JsonResponse
    {
        try {
            $return = [];
            $return['error'] = '';

            // Get input data
			$data = json_decode($request->getContent(), true);

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
            $arguments['forceAll'] = (!empty($data['forceAll']) ? $data['forceAll'] : '');
            $arguments['fromStatus'] = (!empty($data['fromStatus']) ? $data['fromStatus'] : '');
            $arguments['toStatus'] = (!empty($data['toStatus']) ? $data['toStatus'] : '');
            $input = new ArrayInput($arguments);
            $output = new BufferedOutput();

            // Run the command
            $application->run($input, $output);

            // Get resut command
            $content = $output->fetch();
            if (empty($content)) {
                throw new Exception('No response from Myddleware. ');
            }
            // Log the result
            $this->logger->info(print_r($content, true));

            // Get the job task id, result is <jobId>.....
            $return['jobId'] = substr($content, 0, 23);

            // Get the job statistics
            $this->jobManager->id = $return['jobId'];
            $jobData = $this->jobManager->getLogData();
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

    /**
     * @Route("/rerun_error", name="rerun_error", methods={"POST"})
     */
    public function rerunErrorAction(Request $request): JsonResponse
    {
        try {
            $return = [];
            $return['error'] = '';

            // Get input data
            $data = json_decode($request->getContent(), true);

            // Check parameter
            if (empty($data['limit'])) {
                throw new Exception('limit parameter is missing. Please specify a number to limit the number of data transfer the program has to rerun. ');
            }
            if (empty($data['attempt'])) {
                throw new Exception('attempt parameteris missing. Please specify the maximum number of attempt. If you set 10, the program will rerun only data transfer with attempt <= 10. ');
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
            $input = new ArrayInput($arguments);
            $output = new BufferedOutput();

            // Run the command
            $application->run($input, $output);

            // Get resut command
            $content = $output->fetch();
            if (empty($content)) {
                throw new Exception('No response from Myddleware. ');
            }
            // Log the result
            $this->logger->info(print_r($content, true));

            // Get the job task id, result is <jobId>.....
            $return['jobId'] = substr($content, 0, 23);

            // Get the job statistics
            $this->jobManager->id = $return['jobId'];
            $jobData = $this->jobManager->getLogData();
            $return['jobData'] = $jobData;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $return['error'] = $e->getMessage();
        }
        // Send the response
        return $this->json($return);
    }

    // /**
    //  * @Route("/statistics", name="statistics", methods={"POST"})
    //  */
    // public function statisticsAction(Request $request): JsonResponse
    // {
    //     try {
    //         $return = [];
    //         $home = $this->container->get('myddleware.home');

    //         $return['errorByRule'] = $this->ruleRepository->errorByRule();
    //         $return['countTypeDoc'] = $this->documentRepository->countTypeDoc();
    //         $return['listJobDetail'] = $this->jobRepository->listJobDetail();
    //         $return['countTransferHisto'] = $home->countTransferHisto();
    //     } catch (Exception $e) {
    //         $this->logger->error($e->getMessage());
    //         $return['error'] = $e->getMessage();
    //     }
    //     // Send the response
    //     return $this->json($return);
    // }
}
