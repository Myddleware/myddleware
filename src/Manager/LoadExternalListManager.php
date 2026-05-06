<?php

namespace App\Manager;

use App\Entity\InternalList as InternalListEntity;
use App\Entity\InternalListValue as InternalListValueEntity;
use App\Entity\User;
use App\Service\DebugLogger;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LoadExternalListManager
{
    private $entityManager;

    private $user;

    private DebugLogger $debugLogger;

    public function __construct(
        EntityManagerInterface $entityManager,
        DebugLogger $debugLogger
    ) {
        $this->entityManager = $entityManager;
        $this->debugLogger = $debugLogger;
    }

    private $io;

    public function loadExternalList($file, $listId, InputInterface $input, OutputInterface $output)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['file' => $file, 'listId' => $listId, 'input' => $input, 'output' => $output]);
        try {
            $csvRows = array_map(function ($csv) {
                return str_getcsv($csv, ';');
            }, file($file));
            $header = array_shift($csvRows);
            $csv = [];
            foreach ($csvRows as $csvRow) {
                $csv[] = array_combine($header, $csvRow);
            }

            $internalList = ($this->entityManager->getRepository(InternalListEntity::class)->findBy(['id' => $listId])[0]);
            $user = ($this->entityManager->getRepository(User::class)->findBy(['id' => 1])[0]);

            $progressBar = new ProgressBar($output);
            $progressBar->setFormat('debug');

            $progressBar->start();

            $this->entityManager->getConnection()->beginTransaction();

            try {
                foreach ($csv as $value) {
                    $newRow = new InternalListValueEntity();
                    $rowDate = gmdate('Y-m-d h:i:s');
                    $newRow->setReference($rowDate);
                    $newRowId = $value['Code_quartier'];
                    $firstRowData = $value;
                    $firstRowSerialized = serialize($firstRowData);
                    $newRow->setData($firstRowSerialized);
                    $newRow->setDeleted(false);
                    $newRow->setRecordId($newRowId);
                    $newRow->setListId($internalList);
                    $newRow->setCreatedBy($user);
                    $newRow->setModifiedBy($user);

                    $progressBar->advance();
                    $this->entityManager->persist($newRow);
                }

                $this->entityManager->flush();
                $this->entityManager->getConnection()->commit();
            } catch (Exception $e) {
                $this->entityManager->getConnection()->rollBack();
                $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
                $this->logger->error($error);
                throw $e;
            }

            $progressBar->finish();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }
}
