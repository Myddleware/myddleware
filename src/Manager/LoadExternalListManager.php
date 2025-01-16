<?php

namespace App\Manager;

use App\Entity\InternalList as InternalListEntity;
use App\Entity\InternalListValue as InternalListValueEntity;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
//progress bar
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LoadExternalListManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var User
     */
    private $user;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    //for progress bar
    /**
     * @var SymfonyStyle
     */
    private $io;

    public function loadExternalList($file, $listId, InputInterface $input, OutputInterface $output)
    {
        //section for future csv handling
        // $file = "C:\laragon\www\myddleware\src\localfiles\\" . $file . ".csv";
        //extract the data from the csv
        //use the array map: this will use a function and iterate over an array of element instead of using a for loop
        $csvRows = array_map(function ($csv) {
            //convert the csv to a string, using ; as a separator
            return str_getcsv($csv, ';');
        //using file path
        }, file($file));
        //we generate a header which use the array shift method
        //array_shift takes of the 1st element of an array and returns it
        //so header will be the 1st element of the array rows
        $header = array_shift($csvRows);
        //initiate an empty array
        $csv = [];
        //we loop through the rows
        foreach ($csvRows as $csvRow) {
            //we combine each row with the header
            $csv[] = array_combine($header, $csvRow);
        }

        //reference variables
        $internalList = ($this->entityManager->getRepository(InternalListEntity::class)->findBy(['id' => $listId])[0]);
        $user = ($this->entityManager->getRepository(User::class)->findBy(['id' => 1])[0]);

        //progress bar settings
        $progressBar = new ProgressBar($output);
        $progressBar->setFormat('debug');

        // starts and displays the progress bar
        $progressBar->start();

        //transaction start
        $this->entityManager->getConnection()->beginTransaction();

        try {
            foreach ($csv as $value) {
                //we loop through the csv data to add values
                $newRow = new InternalListValueEntity();
                $rowDate = gmdate('Y-m-d h:i:s');
                $newRow->setReference($rowDate);
                // $newRowId = $value['Identifiant_de_l_etablissement'];
                $newRowId = $value['Code_quartier'];
                $firstRowData = $value;
                $firstRowSerialized = serialize($firstRowData);
                $newRow->setData($firstRowSerialized);
                $newRow->setDeleted(false);
                $newRow->setRecordId($newRowId);
                $newRow->setListId($internalList);
                $newRow->setCreatedBy($user);
                $newRow->setModifiedBy($user);

                //progress bar advancement
                $progressBar->advance();
                //apply change to database every row
                $this->entityManager->persist($newRow);
            }

            //final push to the database and commit
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
            $this->logger->error($error);
            throw $e;
        }

        // ensures that the progress bar is at 100%
        $progressBar->finish();
    }
}
