<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 *
 * This file is part of Myddleware.
 *
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

namespace Myddleware\RegleBundle\Classes;

use Symfony\Bridge\Monolog\Logger; // Gestion des logs
use Symfony\Component\DependencyInjection\ContainerInterface as Container; // Accède aux services
use Doctrine\DBAL\Connection; // Connexion BDD
use Symfony\Component\HttpFoundation\Session\Session;
use Myddleware\RegleBundle\Classes\tools as MyddlewareTools;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\BufferedOutput;

class jobSchedulercore
{

    protected $env;
    protected $em;
    protected $jobsToRun;


    public function __construct(Logger $logger, Container $container, Connection $dbalConnection)
    {
        $this->logger = $logger; // gestion des logs symfony monolog
        $this->container = $container;
        $this->connection = $dbalConnection;
        $this->env = $this->container->getParameter("kernel.environment");
        $this->em = $this->container->get('doctrine')->getEntityManager();
    }

    protected $jobList = array('cleardata', 'notification', 'rerunerror', 'synchro');
    public function getJobsParams()
    {
        try {
            $list = array();
            if (!empty($this->jobList)) {
                foreach ($this->jobList as $job) {
                    $list[$job]['name'] = $job;
                    switch ($job) {
                        case 'synchro':
                            $list[$job]['param1'] = array(
                                'rule' => array(
                                    'fieldType' => 'list',
                                    'option' => array('ALL' => 'All active rules', 'TEST' => ' TEST ME')  // Je vais ajouter toutes les règles dans la liste
                                )
                            );
                            $list[$job]['param2'] = array(
                                'rule' => array(
                                    'fieldType' => 'list',
                                    'option' => array('ALL' => 'All active rules', 'TEST' => ' TEST ME')  // Je vais ajouter toutes les règles dans la liste
                                )
                            );
                            break;
                        case 'notification':
                            $list[$job]['param1'] = array(
                                'type' => array(
                                    'fieldType' => 'list',
                                    'option' => array('alert' => 'alert', 'statistics' => 'statistics')
                                )
                            );
                            break;
                        case 'rerunerror':
                            $list[$job]['param1'] = array(
                                'limit' => array(
                                    'fieldType' => 'int'
                                )
                            );
                            $list[$job]['param1'] = array(
                                'attempt' => array(
                                    'fieldType' => 'int'
                                )
                            );
                            break;
                    }
                }
            }
            return $list;
        } catch (\Exception $e) {
            throw new \Exception ('Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
        }
    }



    // Get the job to run in the right order
    public function setJobsToRun()
    {
        try {
            $sqlParams = "	SELECT 
								JobScheduler.id,
								JobScheduler.command,
								JobScheduler.paramName1,
								JobScheduler.paramValue1,
								JobScheduler.paramName2,
								JobScheduler.paramValue2,
								if (RuleOrder.order IS NOT NULL, RuleOrder.order, jobOrder) jobOrder
							FROM JobScheduler
								LEFT OUTER JOIN RuleOrder
									 ON JobScheduler.paramValue1 = RuleOrder.rule_id
									AND JobScheduler.command = 'synchro'
							WHERE 
									JobScheduler.lastRun IS NULL
								OR (
										JobScheduler.lastRun IS NOT NULL
									AND TIMESTAMPDIFF(MINUTE,JobScheduler.lastRun,UTC_TIMESTAMP()) >= JobScheduler.period	
								)	
								AND JobScheduler.active = 1
							ORDER BY jobOrder ASC";
            $stmt = $this->connection->prepare($sqlParams);
            $stmt->execute();
            $this->jobsToRun = $stmt->fetchAll();
        } catch (\Exception $e) {
            throw new \Exception ('Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
        }
    }

    // Run each selected jobs
    public function runJobs()
    {
        if (!empty($this->jobsToRun)) {
            foreach ($this->jobsToRun as $jobToRun) {
                $application = new Application($this->container->get('kernel'));
                $application->setAutoExit(false);
                $arguments = array(
                    'command' => 'myddleware:' . $jobToRun['command'],
                    '--env' => $this->env,
                );

                if (!empty($jobToRun['paramName1'])) {
                    $arguments[$jobToRun['paramName1']] = $jobToRun['paramValue1'];
                }
                if (!empty($jobToRun['paramName2'])) {
                    $arguments[$jobToRun['paramName2']] = $jobToRun['paramValue2'];
                }

                $input = new ArrayInput($arguments);

                // You can use NullOutput() if you don't need the output
                $output = new BufferedOutput();
                $application->run($input, $output);

                $content = $output->fetch();
                // Send output to the logfile if debug mode selected
                if (!empty($content)) {
                    $this->logger->debug(print_r($content, true));
                }

                // Update the lastrun date for the job (GMT timezone)
                $jobScheduler = $this->em
                    ->getRepository('RegleBundle:JobScheduler')
                    ->findOneById($jobToRun['id']);
                // We round the current date to the minute because the period is on minute
                $now = new \DateTime('now', new \DateTimeZone('GMT'));
                $now->setTime($now->format('H'), $now->format('i'), 0);
                $jobScheduler->setLastRun($now);
                $this->em->persist($jobScheduler);
                $this->em->flush();
            }
        }
    }

}


/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__ . '/../Custom/Classes/jobScheduler.php';
if (file_exists($file)) {
    require_once($file);
} else {
    //Sinon on met la classe suivante
    class jobScheduler extends jobSchedulercore
    {

    }
}
?>