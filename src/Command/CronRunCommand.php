<?php

declare(strict_types=1);

namespace App\Command;

use function count;
use DateTime;
use Exception;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Shapecode\Bundle\CronBundle\Console\Style\CronStyle;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Shapecode\Bundle\CronBundle\Domain\CronJobRunning;
use Shapecode\Bundle\CronBundle\CronJob\CommandHelper;
use Shapecode\Bundle\CronBundle\Repository\CronJobRepository;
use function sleep;
use function sprintf;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use App\Entity\Config;
use Doctrine\ORM\EntityManagerInterface;

use App\Manager\ToolsManager;

#[AsCommand(
    name: 'myddleware:cronrun',
    description: 'Runs any currently schedule cron jobs'
)]
final class CronRunCommand extends Command
{
    private CommandHelper $commandHelper;
    protected EntityManagerInterface $entityManager;
    private ToolsManager $toolsManager;
    private CronJobRepository $cronJobRepository;

    public function __construct(
        CommandHelper $commandHelper,
        ManagerRegistry $registry,
        EntityManagerInterface $entityManager,
        ToolsManager $toolsManager,
    ) {
        parent::__construct();

        $this->commandHelper = $commandHelper;
        $this->entityManager = $entityManager;
        $this->toolsManager = $toolsManager;
        $this->cronJobRepository = $registry->getRepository(CronJob::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

		if (!$this->toolsManager->isPremium()) {
            $style = new CronStyle($input, $output);
            $style->error('This feature is only available in the premium version. However you can use your linux crontab to run command like synchro or rerunerror.');
            return CronJobResult::EXIT_CODE_FAILED;
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
            $em = $this->entityManager;
    
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
            return Command::SUCCESS;
            
        } else {
            $style->error('Your crontabs are disabled');
            return Command::FAILURE;
        }  
    }

    /**
     * @param CronJobRunning[] $processes
     */
    public function waitProcesses(array $processes): void
    {
        $em = $this->entityManager;

        while (count($processes) > 0) {
            foreach ($processes as $key => $running) {
                $process = $running->process;

                try {
                    $process->checkTimeout();

                    if ($process->isRunning() === true) {
                        break;
                    }
                } catch (ProcessTimedOutException $e) {
                }

                $job = $running->cronJob;
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
