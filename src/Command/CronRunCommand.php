<?php

declare(strict_types=1);

namespace App\Command;

use function count;
use DateTime;
use Exception;
use Doctrine\Persistence\ManagerRegistry;
use Shapecode\Bundle\CronBundle\Command\BaseCommand;
use Shapecode\Bundle\CronBundle\Console\Style\CronStyle;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Shapecode\Bundle\CronBundle\Entity\CronJobResult;
use Shapecode\Bundle\CronBundle\Model\CronJobRunning;
use Shapecode\Bundle\CronBundle\Service\CommandHelper;
use function sleep;
use function sprintf;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use App\Entity\Config;
use Doctrine\ORM\EntityManagerInterface;

use App\Manager\ToolsManager;

final class CronRunCommand extends BaseCommand
{
    private CommandHelper $commandHelper;
    //protected $configParams;
    protected EntityManagerInterface $entityManager;
	
	private ToolsManager $toolsManager;

    public function __construct(
        CommandHelper $commandHelper,
        ManagerRegistry $registry,
        EntityManagerInterface $entityManager,
		ToolsManager $toolsManager,
    ) {
        parent::__construct($registry);

        $this->commandHelper = $commandHelper;
        $this->entityManager = $entityManager;
        $this->toolsManager = $toolsManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('myddleware:cronrun')
            ->setDescription('Runs any currently schedule cron jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
		if (!$this->toolsManager->isPremium()) {
			return $this->redirectToRoute('premium_list');
		}
        $jobRepo = $this->getCronJobRepository();
        $style = new CronStyle($input, $output);
        //Check if crontab is enabled
        $entity = $this->entityManager->getRepository(Config::class)->findOneBy(['name' => 'cron_enabled']);
        if (!($entity)) {
            throw new Exception("Couldn't fetch Cronjobs");
        }
		$valueCron = $entity->getValue();
        if (
                $valueCron == 1 
			 && !empty($valueCron))
        {
            $jobsToRun = $jobRepo->findAll();
    
            $jobCount = count($jobsToRun);
            $style->comment(sprintf('Cronjobs started at %s', (new DateTime())->format('r')));
    
            $style->title('Execute cronjobs');
            $style->info(sprintf('Found %d jobs', $jobCount));
    
            // Update the job with it's next scheduled time
            $now = new DateTime();
    
            /** @var CronJobRunning[] $processes */
            $processes = [];
            $em = $this->getManager();
    
            foreach ($jobsToRun as $job) {
                sleep(1);
    
                $style->section(sprintf('Running "%s"', $job->getFullCommand()));
    
                if (!$job->isEnable()) {
                    $style->notice('cronjob is disabled');
    
                    continue;
                }
    
                if ($job->getNextRun() > $now) {
                    $style->notice(sprintf('cronjob will not be executed. Next run is: %s', $job->getNextRun()->format('r')));
    
                    continue;
                }
    
                $job->increaseRunningInstances();
                $process = $this->runJob($job);
    
                $job->calculateNextRun();
                $job->setLastUse($now);
    
                $em->persist($job);
                $em->flush();
    
                $processes[] = new CronJobRunning($job, $process);
    
                if ($job->getRunningInstances() > $job->getMaxInstances()) {
                    $style->notice('cronjob will not be executed. The number of maximum instances has been exceeded.');
                } else {
                    $style->success('cronjob started successfully and is running in background');
                }
            }
			
			sleep(1);

			$style->section('Summary');

			if (count($processes) > 0) {
				$style->text('waiting for all running jobs ...');

				// wait for all processes
				$this->waitProcesses($processes);

				$style->success('All jobs are finished.');
			} else {
				$style->info('No jobs were executed. See reasons below.');
			}
			return CronJobResult::EXIT_CODE_SUCCEEDED;
			
        } else {
            $style->error('Your crontabs are disabled');
            return CronJobResult::EXIT_CODE_FAILED;
        }  
    }

    /**
     * @param CronJobRunning[] $processes
     */
    public function waitProcesses(array $processes): void
    {
        $em = $this->getManager();

        while (count($processes) > 0) {
            foreach ($processes as $key => $running) {
                $process = $running->getProcess();

                try {
                    $process->checkTimeout();

                    if ($process->isRunning() === true) {
                        break;
                    }
                } catch (ProcessTimedOutException $e) {
                }

                $job = $running->getCronJob();
                $job->decreaseRunningInstances();

                $em->persist($job);
                $em->flush();

                unset($processes[$key]);
            }

            sleep(1);
        }
    }

    private function runJob(CronJob $job): Process
    {
        $command = [
            $this->commandHelper->getPhpExecutable(),
            $this->commandHelper->getConsoleBin(),
            'shapecode:cron:process',
            $job->getId(),
        ];

        $process = new Process($command);
        $process->disableOutput();

        $timeout = $this->commandHelper->getTimeout();
        if ($timeout !== null && $timeout > 0) {
            $process->setTimeout($timeout);
        }

        $process->start();

        return $process;
    }
}
