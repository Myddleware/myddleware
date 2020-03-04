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

    public function indexAction(Request $request)
    {
        $data = $request->request->all();

        return new JsonResponse($data);

    } 
	
	public function generateDocumentsAction(Request $request)
    {
        $data = $request->request->all();

        return new JsonResponse($data);

    }
	
	public function commandAction(Request $request)
    {
        $data = $request->request->all();

        return new JsonResponse($data);

    }
	
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
		return new JsonResponse($return);
    }
}
