<?php

namespace Myddleware\ApiBundle\v1_0\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class DefaultController extends Controller
{

	public function synchroAction(Request $request)
    {
		try {
			$logger = $this->container->get('logger');
			$return = array();
			$return['error'] = '';
			
			// Get input data
			$data = $request->request->all();
			
			// Check parameter
			if (empty($data['rule'])) {
				throw new \Exception('Rule is missing. Please specify a rule id or set ALL to run all active rules. ');
			}

			// Prepare command
			$application = new Application($this->container->get('kernel'));
			$application->setAutoExit(false);
			$arguments = array(
				'command' => 'myddleware:synchro',
				'api'	  => 1,
				'--env'   => $this->container->getParameter("kernel.environment"),
			);

			// Prepare input/output parameters
			$arguments['rule'] = $data['rule'];
			$input = new ArrayInput($arguments);
			$output = new BufferedOutput();
			
			// Run the command
			$application->run($input, $output);

			// Get resut command
			$content = $output->fetch();
			if (empty($content)) {
				throw new \Exception('No response from Myddleware. ');
			}
			// Log the result
			$logger->info(print_r($content, true));
			
			// Get the job task id, result is 1;<jobId>.....
			$return['jobId'] = substr($content,2,23);
			
			// Get the job statistics
			$job = $this->container->get('myddleware_job.job');
			$job->id = $return['jobId'];
			$jobData = $job->getLogData(1);
			if (!empty($jobData['jobError'])) {
				throw new \Exception('Failed to get the job statistics. '.$jobData['jobError']);
			}
			$return['jobData'] = $jobData;
		}
		catch(\Exception $e) {
			$logger->error($e->getMessage());
			$return['error'] = $e->getMessage();
		}
		// Send the response
		return new JsonResponse($return);
    }
	
	
	public function readRecordAction(Request $request)
    {
		try {
			$logger = $this->container->get('logger');
			$return = array();
			$return['error'] = '';
			
			// Get input data
			$data = $request->request->all();
			
			// Check parameter
			if (empty($data['rule'])) {
				throw new \Exception('Rule is missing. Please specify a ruleId parameter. ');
			}
			if (empty($data['filterQuery'])) {
				throw new \Exception('Filter query is missing. filterQuery is a field used to read data in the source application, eg : id. ');
			}
			if (empty($data['filterValues'])) {
				throw new \Exception('Filter value is missing. filterValues is the value corresponding to the filterQuery field. ');
			}

			// Prepare command
			$application = new Application($this->container->get('kernel'));
			$application->setAutoExit(false);
			$arguments = array(
				'command' => 'myddleware:readrecord',
				'api'	  => 1,
				'--env'   => $this->container->getParameter("kernel.environment"),
			);

			// Prepare input/output parameters
			$arguments['ruleId'] 			= $data['rule'];
			$arguments['filterQuery'] 	= $data['filterQuery'];
			$arguments['filterValues'] 	= $data['filterValues'];
			$input = new ArrayInput($arguments);
			$output = new BufferedOutput();
			
			// Run the command
			$application->run($input, $output);

			// Get resut command
			$content = $output->fetch();
			if (empty($content)) {
				throw new \Exception('No response from Myddleware. ');
			}
			// Log the result
			$logger->info(print_r($content, true));
			
			// Get the job task id, result is <jobId>.....
			$return['jobId'] = substr($content,0,23);
			
			// Get the job statistics
			$job = $this->container->get('myddleware_job.job');
			$job->id = $return['jobId'];
			$jobData = $job->getLogData(1);
			if (!empty($jobData['jobError'])) {
				throw new \Exception('Failed to get the job statistics. '.$jobData['jobError']);
			}
			$return['jobData'] = $jobData;
		}
		catch(\Exception $e) {
			$logger->error($e->getMessage());
			$return['error'] = $e->getMessage();
		}
		// Send the response
		return new JsonResponse($return);
    }
	
	
	public function massActionAction(Request $request)
    {
		try {
			$logger = $this->container->get('logger');
			$return = array();
			$return['error'] = '';
			
			// Get input data
			$data = $request->request->all();
			
			// Check parameter
			if (empty($data['action'])) {
				throw new \Exception('action is missing. Please specify an action : rerun, cancel, remove, restore or changeStatus. ');
			}
			if (empty($data['dataType'])) {
				throw new \Exception('dataType is missing. Please specify a data type : rule or document');
			}
			if (empty($data['ids'])) {
				throw new \Exception('ids is missing. Please specify rule or document ids separated by comma. ');
			}

			// Prepare command
			$application = new Application($this->container->get('kernel'));
			$application->setAutoExit(false);
			$arguments = array(
				'command' => 'myddleware:massaction',
				'api'	  => 1,
				'--env'   => $this->container->getParameter("kernel.environment"),
			);

			// Prepare input/output parameters
			$arguments['action'] 	= $data['action'];
			$arguments['dataType'] 	= $data['dataType'];
			$arguments['ids'] 		= $data['ids'];
			$arguments['forceAll'] 	= (!empty($data['forceAll']) ? $data['forceAll'] : '') ;
			$arguments['fromStatus']= (!empty($data['fromStatus']) ? $data['fromStatus'] : '') ;
			$arguments['toStatus'] 	= (!empty($data['toStatus']) ? $data['toStatus'] : '') ;
			$input = new ArrayInput($arguments);
			$output = new BufferedOutput();
			
			// Run the command
			$application->run($input, $output);

			// Get resut command
			$content = $output->fetch();
			if (empty($content)) {
				throw new \Exception('No response from Myddleware. ');
			}
			// Log the result
			$logger->info(print_r($content, true));
			
			// Get the job task id, result is <jobId>.....
			$return['jobId'] = substr($content,0,23);
			
			// Get the job statistics
			$job = $this->container->get('myddleware_job.job');
			$job->id = $return['jobId'];
			$jobData = $job->getLogData(1);
			if (!empty($jobData['jobError'])) {
				throw new \Exception('Failed to get the job statistics. '.$jobData['jobError']);
			}
			$return['jobData'] = $jobData;
		}
		catch(\Exception $e) {
			$logger->error($e->getMessage());
			$return['error'] = $e->getMessage();
		}
		// Send the response
		return new JsonResponse($return);
    }	
	
	
	public function rerunErrorAction(Request $request)
    {
		try {
			$logger = $this->container->get('logger');
			$return = array();
			$return['error'] = '';
			
			// Get input data
			$data = $request->request->all();
			
			// Check parameter
			if (empty($data['limit'])) {
				throw new \Exception('limit parameter is missing. Please specify a number to limit the number of data transfer the program has to rerun. ');
			}
			if (empty($data['attempt'])) {
				throw new \Exception('attempt parameteris missing. Please specify the maximum number of attempt. If you set 10, the program will rerun only data transfer with attempt <= 10. ');
			}

			// Prepare command
			$application = new Application($this->container->get('kernel'));
			$application->setAutoExit(false);
			$arguments = array(
				'command' => 'myddleware:rerunerror',
				'api'	  => 1,
				'--env'   => $this->container->getParameter("kernel.environment"),
			);

			// Prepare input/output parameters
			$arguments['limit'] 	= $data['limit'];
			$arguments['attempt'] 	= $data['attempt'];
			$input = new ArrayInput($arguments);
			$output = new BufferedOutput();
			
			// Run the command
			$application->run($input, $output);

			// Get resut command
			$content = $output->fetch();
			if (empty($content)) {
				throw new \Exception('No response from Myddleware. ');
			}
			// Log the result
			$logger->info(print_r($content, true));
			
			// Get the job task id, result is <jobId>.....
			$return['jobId'] = substr($content,0,23);
			
			// Get the job statistics
			$job = $this->container->get('myddleware_job.job');
			$job->id = $return['jobId'];
			$jobData = $job->getLogData(1);
			$return['jobData'] = $jobData;
		}
		catch(\Exception $e) {
			$logger->error($e->getMessage());
			$return['error'] = $e->getMessage();
		}
		// Send the response
		return new JsonResponse($return);
    }	
	
	
	public function statisticsAction(Request $request)
    {
		try {
			$return = array();
 			$logger = $this->container->get('logger');
			$home = $this->container->get('myddleware.home');
			
			$return['errorByRule'] = $home->errorByRule(1, '');
			$return['countTypeDoc'] = $home->countTypeDoc(1, '');
			$return['listJobDetail'] = $home->listJobDetail();
			$return['countTransferHisto'] = $home->countTransferHisto(1, '');
		}
		catch(\Exception $e) {
			$logger->error($e->getMessage());
			$return['error'] = $e->getMessage();
		}
		// Send the response
		return new JsonResponse($return);
    }
}
