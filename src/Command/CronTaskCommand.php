<?php

namespace App\Command;

use Cron\CronBundle\Entity\CronJob;
use App\Repository\ConfigRepository;
use Cron\CronBundle\Entity\CronJobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronTaskCommand extends Command
{
    // protected static $defaultName = 'CronTaskCommand';
    // protected static $defaultDescription = 'Create a cron job';

    // private $entityManager;
    // private $passwordEncoder;
    // private $validator;
    // private $users;
    // private $configRepository;

    // public function __construct(EntityManagerInterface $em, ConfigRepository $configRepository, CronJobRepository $cronJob)
    // {
    //     parent::__construct();

    //     $this->entityManager = $em;
    //     $this->configRepository = $configRepository;
    //     $this->cronJobRepository = $cronJob;
  
    // }

    // protected function configure(): void
    // {
    //     $this->setName('cron:created')
    //         ->setDescription(self::$defaultDescription)
    //         ->addOption('name', null, InputOption::VALUE_REQUIRED, 'The job name')
    //         ->addOption('command', null, InputOption::VALUE_REQUIRED, 'The job command')
    //         ->addOption('schedule', null, InputOption::VALUE_REQUIRED, 'The job schedule')
    //         ->addOption('description', null, InputOption::VALUE_REQUIRED, 'The job description')
    //         ->addOption('enabled', null, InputOption::VALUE_REQUIRED, 'Is the job enabled');
    // }

    // protected function execute(InputInterface $input, OutputInterface $output): int
    // {
    //     $job = new CronJob();
    //     $io = new SymfonyStyle($input, $output);
    //     $name = $input->getOption('name');
    //     if (is_null($name)) {
    //         $io->ask('name ?', 'cron job');
    //         $job->setName($name);
    //     }
    //     if ($name) {
    //         $io->note(sprintf('You passed an name: %s', $name));
    //     }
    //     $command = $input->getOption('command');
    //     if (is_null($command)) {
    //         $io->ask('command ?', 'cron job');
    //         $job->setCommand($command);
    //     }
    //     if ($command) {
    //         $io->note(sprintf('You passed an command: %s', $command));
    //     }
    //     $schedule = $input->getOption('schedule');
    //     if (is_null($schedule)) {
    //         $io->ask('schedule ?', 'cron job');
    //         $job->setSchedule($schedule);
    //     }
    //     if ($schedule) {
    //         $io->note(sprintf('You passed an schedule: %s', $schedule));
    //     }
    //     $description = $input->getOption('description');
    //     if (is_null($description)) {
    //         $io->ask('description ?', 'cron job');
    //         $job->setDescription($description);
    //     }
    //     if ($description) {
    //         $io->note(sprintf('You passed an description: %s', $description));
    //     }
    //     $enabled = $input->getOption('enabled');
    //     if (is_null($enabled)) {
    //         $io->ask('enabled ?', 'cron job');
    //         $job->setEnabled($enabled);
    //     }
    //     if ($enabled) {
    //         $io->note(sprintf('You passed an enabled: %s', $enabled));
    //     }
    //     $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

    //     return 0;
    // }
}
