<?php

namespace App\Command;

use App\Service\DocumentElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'elasticsearch:populate-documents',
    description: 'Populate Elasticsearch index with documents from MySQL',
)]
class ElasticsearchPopulateCommand extends Command
{
    private DocumentElasticsearchService $elasticsearchService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        DocumentElasticsearchService $elasticsearchService,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->elasticsearchService = $elasticsearchService;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, 'Batch size for indexing', 1000)
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Maximum number of documents to index (for testing)', null)
            ->addOption('create-index', 'c', InputOption::VALUE_NONE, 'Create the index if it doesn\'t exist')
            ->addOption('recreate-index', 'r', InputOption::VALUE_NONE, 'Delete and recreate the index (WARNING: all data will be reindexed)')
            ->addOption('update-max-result-window', null, InputOption::VALUE_OPTIONAL, 'Update max_result_window setting on existing index (default: 1000000)', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->elasticsearchService->isAvailable()) {
            $io->error('Elasticsearch is not available. Please make sure it is running.');
            return Command::FAILURE;
        }

        if ($input->getOption('recreate-index')) {
            $io->warning('Recreating Elasticsearch index - this will delete all existing data!');
            if ($this->elasticsearchService->deleteIndex()) {
                $io->info('Existing index deleted');
            }
            $io->info('Creating new index with updated mapping...');
            $this->elasticsearchService->createIndex();
            $io->success('Index recreated successfully');
        } elseif ($input->getOption('create-index')) {
            $io->info('Creating Elasticsearch index...');
            $this->elasticsearchService->createIndex();
        }

        $maxResultWindowOption = $input->getOption('update-max-result-window');
        if ($maxResultWindowOption !== false) {
            $maxResultWindow = $maxResultWindowOption === null ? 1000000 : (int) $maxResultWindowOption;
            $io->info(sprintf('Updating max_result_window to %d...', $maxResultWindow));
            try {
                $this->elasticsearchService->updateMaxResultWindow($maxResultWindow);
                $io->success(sprintf('max_result_window updated to %d', $maxResultWindow));
            } catch (\Exception $e) {
                $io->error('Failed to update max_result_window: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $batchSize = (int) $input->getOption('batch-size');
        $limit = $input->getOption('limit') ? (int) $input->getOption('limit') : null;

        $io->title('Populating Elasticsearch with documents');
        $io->info(sprintf('Batch size: %d', $batchSize));

        $query = "
            SELECT
                document.id,
                document.rule_id,
                document.date_created,
                document.date_modified,
                document.status,
                document.source_id,
                document.target_id,
                document.source_date_modified,
                document.mode,
                document.type,
                document.attempt,
                document.global_status,
                document.deleted,
                rule.name as rule_name,
                rule.module_source,
                rule.module_target,
                source_data.data as source_content,
                target_data.data as target_content
            FROM document
            INNER JOIN rule ON document.rule_id = rule.id
            LEFT JOIN documentdata source_data ON document.id = source_data.doc_id AND source_data.type = 'S'
            LEFT JOIN documentdata target_data ON document.id = target_data.doc_id AND target_data.type = 'T'
            WHERE document.deleted = 0
        ";

        if ($limit) {
            $query .= " LIMIT " . $limit;
        }

        $stmt = $this->entityManager->getConnection()->prepare($query);
        $result = $stmt->executeQuery();

        $documents = [];
        $totalProcessed = 0;

        $io->progressStart($limit ?? 100000);

        while ($row = $result->fetchAssociative()) {
            // Prepare document for Elasticsearch
            $elasticDoc = [
                'id' => $row['id'],
                'rule_id' => $row['rule_id'],
                'rule_name' => $row['rule_name'],
                'status' => $row['status'],
                'global_status' => $row['global_status'],
                'source_id' => $row['source_id'],
                'target_id' => $row['target_id'],
                'date_created' => $row['date_created'],
                'date_modified' => $row['date_modified'],
                'source_date_modified' => $row['source_date_modified'],
                'type' => $row['type'],
                'mode' => $row['mode'],
                'attempt' => $row['attempt'],
                'module_source' => $row['module_source'],
                'module_target' => $row['module_target'],
                'deleted' => (bool) $row['deleted'],
            ];

            // Add source content if available (unserialize if needed)
            if (!empty($row['source_content'])) {
                $sourceContent = @unserialize($row['source_content']);
                $elasticDoc['source_content'] = is_string($sourceContent) ? $sourceContent : json_encode($sourceContent);
            }

            // Add target content if available (unserialize if needed)
            if (!empty($row['target_content'])) {
                $targetContent = @unserialize($row['target_content']);
                $elasticDoc['target_content'] = is_string($targetContent) ? $targetContent : json_encode($targetContent);
            }

            $documents[] = $elasticDoc;

            // Index in batches
            if (count($documents) >= $batchSize) {
                $this->elasticsearchService->bulkIndexDocuments($documents);
                $totalProcessed += count($documents);
                $io->progressAdvance(count($documents));
                $documents = []; // Clear batch
            }
        }

        // Index remaining documents
        if (!empty($documents)) {
            $this->elasticsearchService->bulkIndexDocuments($documents);
            $totalProcessed += count($documents);
            $io->progressAdvance(count($documents));
        }

        $io->progressFinish();

        $io->success(sprintf('Successfully indexed %d documents to Elasticsearch', $totalProcessed));

        return Command::SUCCESS;
    }
}
