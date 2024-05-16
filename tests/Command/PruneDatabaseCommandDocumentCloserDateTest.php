<?php

namespace App\Tests\Command;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PruneDatabaseCommandDocumentCloserDateTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    private string $testNbDays = '8';

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

            $documentId = '66435b3019bad4.47813997';

            //selected date = today - random number of days between 0 and testNbDays
            $date = new \DateTime();
            $randomDays = rand(0, intval($this->testNbDays));
            $date->sub(new \DateInterval('P' . $randomDays . 'D'));
            $closerDate = $date->format('Y-m-d H:i:s');


            // Check if a document with the given ID already exists
            $sql = "SELECT COUNT(*) FROM `document` WHERE `id` = :id";
            $count = $this->entityManager->getConnection()->fetchOne($sql, ['id' => $documentId]);
            
            if ($count == 0) {
                // If the document does not exist, insert it
                $sql = "INSERT INTO `document` VALUES (?, ?, 10, 10, ?, ?, 'Error_sending', '8', '67', ?, '0', 'U', 1, 'Error', '', 1, '')";
                $params = ['66435b3019bad4.47813997', '64807163f1b23', $closerDate, $closerDate, $closerDate];
                $this->entityManager->getConnection()->executeStatement($sql, $params);

                // flush the changes to the database
                $this->entityManager->flush();

            // assert: the document is there at the beginning
            }

            $sqlAfterInsert = "SELECT COUNT(*) FROM `document` WHERE `id` = :id";
            $countAfterInsert = $this->entityManager->getConnection()->fetchOne($sqlAfterInsert, ['id' => $documentId]);
            $this->assertEquals(1, $countAfterInsert);
    }

    public function testExecute()
    {
        $command = ['php', 'bin/console', 'myddleware:prunedatabase', $this->testNbDays];

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
        $documentId = '66435b3019bad4.47813997';

        // Check if a document with the given ID already exists
        $sql = "SELECT COUNT(*) FROM `document` WHERE `id` = :id";
        $count = $this->entityManager->getConnection()->fetchOne($sql, ['id' => $documentId]);
        

        $this->assertEquals(1, $count);

        // then delete the document
        $sql = "DELETE FROM `document` WHERE `id` = :id";
        $this->entityManager->getConnection()->executeStatement($sql, ['id' => $documentId]);

        // flush the changes to the database
        $this->entityManager->flush();

        // assert: the document is deleted
        $sqlAfterDelete = "SELECT COUNT(*) FROM `document` WHERE `id` = :id";
        $countAfterDelete = $this->entityManager->getConnection()->fetchOne($sqlAfterDelete, ['id' => $documentId]);
        $this->assertEquals(0, $countAfterDelete);
    }
}