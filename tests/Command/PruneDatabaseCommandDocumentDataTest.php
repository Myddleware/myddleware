<?php

namespace App\Tests\Command;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PruneDatabaseCommandDocumentDataTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    private int $documentDataId;

    protected function setUp(): void
    {

        $this->documentDataId = 8;

        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

            $documentId = '6644a219422800.86915394';

            // Check if a document with the given ID already exists
            $sql = "SELECT COUNT(*) FROM `document` WHERE `id` = :id";
            $count = $this->entityManager->getConnection()->fetchOne($sql, ['id' => $documentId]);
            
            if ($count == 0) {
                // If the document does not exist, insert it
                $sql = "INSERT INTO `document` VALUES ('6644a219422800.86915394', '64807163f1b23', 10, 10, '2024-05-14 12:38:08', '2022-05-14 12:38:08', 'Error_sending', '8', '67', '2023-06-22 06:35:16', '0', 'U', 1, 'Error', '', 1, '')";
                $this->entityManager->getConnection()->executeStatement($sql);

                // flush the changes to the database
                $this->entityManager->flush();

            // assert: the document is there at the beginning
            }

            $sqlAfterInsertDocument = "SELECT COUNT(*) FROM `document` WHERE `id` = :id";
            $countAfterInsertDocument = $this->entityManager->getConnection()->fetchOne($sqlAfterInsertDocument, ['id' => $documentId]);
            $this->assertEquals(1, $countAfterInsertDocument);

            // Check if a document with the given ID already exists
            $sqlCountDocumentData = "SELECT COUNT(*) FROM `documentdata` WHERE `doc_id` = :id";
            $countDocumentData = $this->entityManager->getConnection()->fetchOne($sqlCountDocumentData, ['id' => $documentId]);

            if ($countDocumentData == 0) {
                $documentId = '6644a219422800.86915394';
                $type = 'T';
                $data = serialize([
                    "email" => "choupa@gmail.com",
                    "firstname" => "Henry",
                    "lastname" => "Cavill",
                    "username" => "henrycavill",
                    "createpassword" => "1"
                ]);

                $sqlDocumentData = "INSERT INTO `documentdata` (`id`, `doc_id`, `type`, `data`) VALUES (:id, :doc_id, :type, :data)";
                $this->entityManager->getConnection()->executeStatement($sqlDocumentData, [
                    'id' => $this->documentDataId,
                    'doc_id' => $documentId,
                    'type' => $type,
                    'data' => $data
                ]);

                // flush the changes to the database
                $this->entityManager->flush();
            }

            $sqlAfterInsertDocumentData = "SELECT COUNT(*) FROM `documentdata` WHERE `id` = :id";
            $countAfterInsertDocumentData = $this->entityManager->getConnection()->fetchOne($sqlAfterInsertDocumentData, ['id' => $this->documentDataId]);
            $this->assertEquals(1, $countAfterInsertDocumentData);
    }

    public function testExecute()
    {
        $command = ['php', 'bin/console', 'myddleware:prunedatabase', '8'];

        $process = new Process($command);

        // Run the process
        try {
            $process->mustRun();

            // Output the result
            echo $process->getOutput();
        } catch (ProcessFailedException $exception) {
            // Handle the exception
            echo $exception->getMessage();
        }


        // assert: the document is deleted
        $documentId = '6644a219422800.86915394';

        // Check if a document with the given ID already exists
        $sqlDocument = "SELECT COUNT(*) FROM `document` WHERE `id` = :id";
        $countDocument = $this->entityManager->getConnection()->fetchOne($sqlDocument, ['id' => $documentId]);
        

        $this->assertEquals(0, $countDocument);

        // assert: the document data is deleted
        // Check if a document with the given ID already exists
        $sqlDocumentData = "SELECT COUNT(*) FROM `documentdata` WHERE `id` = :id";
        $countDocument = $this->entityManager->getConnection()->fetchOne($sqlDocumentData, ['id' => $this->documentDataId]);
    }
}