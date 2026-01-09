<?php

namespace App\Command;

use FOS\ElasticaBundle\Index\IndexManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'myddleware:elastic:clear',
    description: 'Clears all Elasticsearch indices managed by FOSElasticaBundle',
)]
class ClearElasticsearchCommand extends Command
{
    private IndexManager $indexManager;

    public function __construct(IndexManager $indexManager)
    {
        parent::__construct();
        $this->indexManager = $indexManager;
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the operation without confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('force')) {
            if (!$io->confirm('Are you sure you want to delete and recreate all Elasticsearch indices?', false)) {
                $io->warning('Operation cancelled.');
                return Command::FAILURE;
            }
        }

        $io->section('Clearing Elasticsearch Indices');

        try {
            $indexes = $this->indexManager->getAllIndexes();
            
            foreach ($indexes as $name => $index) {
                $io->text(sprintf('Processing index: <info>%s</info>', $name));
                
                $index->delete();
                $index->create();
                
                $io->writeln(" -> <comment>Cleared and recreated.</comment>");
            }

            $io->success('All managed Elasticsearch indices have been cleared.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred while clearing indices: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}