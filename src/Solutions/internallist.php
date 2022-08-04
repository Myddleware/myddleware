<?php

/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com

 This file is part of Myddleware.

 Myddleware is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Myddleware is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

namespace App\Solutions;

use App\Entity\InternalListValue as InternalListValueEntity;
use App\Entity\InternalList as InternalListEntity;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class internallistcore extends solution
{
    public function getFieldsLogin()
    {
        try {
            return [
                [
                    //fake url because of minimum required field
                    'name' => 'url',
                    'type' => TextType::class,
                    'label' => 'solution.fields.url',
                ],
            ];
        } catch (\Exception $e) {
            $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
            $this->logger->error($error);
            return ['Login field error: ' => $error];
        }
    }


    public function get_modules($type = 'source')
    {
        try {
            $modules = [];
            //get all the modules
            $table = $this->entityManager->getRepository(InternalListEntity::class)->findAll();
            foreach ($table as $column) {
                //get the id and the name
                $modules[$column->getId()] = $column->getName();
            }
            return $modules;
        } catch (\Exception $e) {
            $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
            $this->logger->error($error);
            return ['module error: ' => $error];
        }
    }

    public function get_module_fields($module, $type = 'source', $extension = false)
    {
        try {
            //get the data to obtain the fields of the row
            $data = $this->entityManager->getRepository(InternalListValueEntity::class)->find($module)->getData();

            //from serialized json to fileds
            $unserializedData = unserialize($data);
            $jsonData = json_decode($unserializedData);
            foreach ($jsonData as $keyData => $valueData) {
                $this->moduleFields[$keyData] = ['label' => $keyData, 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false];
            }
            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
            $this->logger->error($error);
            return ['module fields error: ' => $error];
        }
    }



    public function read($params)
    {

        try {
            //return value
            $result = [];

            //counter for the number of records read
            $recordRead = 0;

            //query choice
            if (!empty($param['query'])) {
                // for special query with specified record id
                $idValue = $param['query']['id'];
                $table = $this->entityManager->getRepository(InternalListValueEntity::class)->findBy(['record_id' => $idValue], [$row->getReference() => 'ASC'], [(int)$params['limit']]);
            } else {
                //standard query using reference
                $table = $this->entityManager->getRepository(InternalListValueEntity::class)->searchRecords($params);
            }
        } catch (\Exception $e) {
            $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
            $this->logger->error($error);
            return ['error getting the records' => $error];
        }

        foreach ($table as $row) {
            try {
                //get the data
                $getRecords = $row->getData();
                $unserializedData = unserialize($getRecords);
                $jsonData = json_decode($unserializedData);
                $result[$recordRead] = (array)$jsonData;

                //get the reference and the modified date
                $result[$recordRead]['id'] = $row->getRecordId();
                $result[$recordRead]['date_modified'] = $row->getDateModified();

                //we increment the number of record read
                $recordRead++;

                // ####################################################################################################
                $this->extractCsv($row);
                $this->entityManager->flush();
                // ####################################################################################################


            } catch (\Exception $e) {
                $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
                $this->logger->error($error);
                return ['error getting the data from the records' => $error];
            }
        };


        return $result;
    }

    public function extractCsv($row)
    {
        // ####################################################################################################
        //section for future csv handling
        $file = "C:\laragon\www\myddleware\src\localfiles\\educationwithlabel.csv";
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


        //csv
        // var_dump($csv);
        // $jsoncsv = json_encode($csv);
        // dump($jsoncsv);


        // serialize
        // $serializedcsv = serialize($csv);
        // dump($serializedcsv);

        $newRow = new InternalListValueEntity();
        $rowDate = gmdate('Y-m-d h:i:s');
        $newRow->setReference($rowDate);
        $newRowId = $csv['1']['Identifiant_de_l_etablissement'];
        $firstRowData = $csv["1"];
        $firstRowSerialized = serialize($firstRowData);
        $newRow->setData($firstRowSerialized);
        $newRow->setDeleted(false);
        $newRow->setRecordId($newRowId);
        $newRow->setListId($row->getListId());
        $newRow->setCreatedBy($row->getCreatedBy());
        $newRow->setModifiedBy($row->getModifiedBy());


        $this->entityManager->persist($newRow);
        // die('fin du programme');
        // ####################################################################################################
    }


    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            try {
                $this->connexion_valide = true;
            } catch (\PDOException $e) {
                $error = $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
                $this->logger->error($error);

                return ['error' => $error];
            }
        } catch (\Exception $e) {
            $error = $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }
} // class mysqlcore

class internallist extends internallistcore
{
}
