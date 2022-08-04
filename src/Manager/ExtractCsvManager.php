<?php

namespace App\Manager;

use App\Entity\InternalListValue as InternalListValueEntity;
use App\Entity\InternalList as InternalListEntity;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

//progress bar
use Symfony\Component\Console\Helper\ProgressBar;

class extractcsvcore
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

    public function extractCsv($file)
    {
        // ####################################################################################################
        //section for future csv handling
        $file = "C:\laragon\www\myddleware\src\localfiles\\" . $file . ".csv";
        //extract the data from the csv
        //use the array map: this will use a function and iterate over an array of element instead of using a for loop
        $csvRows = array_map(function ($csv) {
            //convert the csv to a string, using ; as a separator
            return str_getcsv($csv, ";");
            //using file path
        }, file($file));
        //we generate a header which use the array shift method
        //array_shift takes of the 1st element of an array and returns it
        //so header will be the 1st element of the array rows
        $header = array_shift($csvRows);
        //initiate an empty array
        $csv    = [];
        //we loop through the rows
        foreach ($csvRows as $csvRow) {
            //we combine each row with the header
            $csv[] = array_combine($header, $csvRow);
        }

        //reference variables
        $internalList = ($this->entityManager->getRepository(InternalListEntity::class)->findBy(['id' => 1])[0]);
        $user = ($this->entityManager->getRepository(User::class)->findBy(['id' => 1])[0]);

        //progress bar
        // $progressBar = new ProgressBar($output, 10);
        // starts and displays the progress bar
        // $progressBar->start();

        foreach ($csv as $etablissement) {
            //we loop through the csv data to add etablissements
            $newRow = new InternalListValueEntity();
            $rowDate = gmdate('Y-m-d h:i:s');
            $newRow->setReference($rowDate);
            $newRowId = $etablissement['Identifiant_de_l_etablissement'];
            $firstRowData = $etablissement;
            $firstRowSerialized = serialize($firstRowData);
            $newRow->setData($firstRowSerialized);
            $newRow->setDeleted(false);
            $newRow->setRecordId($newRowId);
            $newRow->setListId($internalList);
            $newRow->setCreatedBy($user);
            $newRow->setModifiedBy($user);
            $this->entityManager->persist($newRow);

            //progress bar advancement
            // $progressBar->advance();
        }
        // ####################################################################################################

        //! warning don't use simulation, use simulation wih id !
        // ####################################################################################################
        // $this->extractCsv($row);
        // ensures that the progress bar is at 100%
        // $progressBar->finish();
        $this->entityManager->flush();
        // ####################################################################################################
    }
}

class ExtractCsvManager extends extractcsvcore
{
}
