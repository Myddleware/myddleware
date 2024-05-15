<?php

namespace App\Tests\Command;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PruneDatabaseCommandDocumentTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    protected function setUp(): void
    {
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
                $sql = "INSERT INTO `document` VALUES ('6644a219422800.86915394', '64807163f1b23', 10, 10, '2024-05-14 12:38:08', '2024-05-14 12:38:08', 'Error_sending', '8', '67', '2023-06-22 06:35:16', '0', 'U', 1, 'Error', '', 1, '')";
                $this->entityManager->getConnection()->executeStatement($sql);

                // flush the changes to the database
                $this->entityManager->flush();

            // assert: the document is there at the beginning
            }

            $sqlAfterInsert = "SELECT COUNT(*) FROM `document` WHERE `id` = :id";
            $countAfterInsert = $this->entityManager->getConnection()->fetchOne($sqlAfterInsert, ['id' => $documentId]);
            $this->assertEquals(1, $countAfterInsert);

            // Check if a document with the given ID already exists
            $sqlCountDocumentData = "SELECT COUNT(*) FROM `document` WHERE `id` = :id";
            $countDocumentData = $this->entityManager->getConnection()->fetchOne($sqlCountDocumentData, ['id' => $documentId]);

            if ($countDocumentData == 0) {
                $documentDataId = 8;
                $documentId = '6644a219422800.86915394';
                $type = 'T';
                $data = serialize([
                    "email" => "choupa@gmail.com",
                    "firstname" => "Henry",
                    "lastname" => "Cavill",
                    "username" => "henrycavill",
                    "createpassword" => "1"
                ]);

                $sqlDocumentData = "INSERT INTO `documentdata` VALUES (:documentDataId, :documentId, :type, :data)";
                $this->entityManager->getConnection()->executeStatement($sqlDocumentData, [
                    'documentDataId' => $documentDataId,
                    'documentId' => $documentId,
                    'type' => $type,
                    'data' => $data
                ]);

                $this->entityManager->getConnection()->executeStatement($sqlDocumentData);

                // flush the changes to the database
                $this->entityManager->flush();
            }
    }

    public function testExecute()
    {
        $command = ['php', 'bin/console', 'myddleware:prunedatabase'];

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
        $documentId = '66435b3019bad4.47813999';

        // Check if a document with the given ID already exists
        $sql = "SELECT COUNT(*) FROM `document` WHERE `id` = :id";
        $count = $this->entityManager->getConnection()->fetchOne($sql, ['id' => $documentId]);
        

        $this->assertEquals(0, $count);
    }
}