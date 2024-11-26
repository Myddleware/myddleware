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

use App\Entity\InternalList as InternalListEntity;
use App\Entity\InternalListValue as InternalListValueEntity;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class internallist extends solution
{
    public function getFieldsLogin(): array
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

    public function get_modules($type = 'source'): array
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

    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        try {
            //get the data to obtain the fields of the row
            $data = $this->entityManager->getRepository(InternalListValueEntity::class)->findOneBy(['listId' => $module])->getData();

            //from serialized json to fileds
            $unserializedData = unserialize($data);
            foreach ($unserializedData as $keyData => $valueData) {
                $this->moduleFields[$keyData] = ['label' => $keyData, 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false];
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
            $this->logger->error($error);

            return ['module fields error: ' => $error];
        }
    }

    public function read($params): array
    {
        try {
            //return value
            $result = [];

            //counter for the number of records read
            $recordRead = 0;
            //query choice
            if (!empty($params['query'])) {
                // for special query with specified record id
                $table = $this->entityManager->getRepository(InternalListValueEntity::class)->findBy(['record_id' => $params['query']['id'], 'listId' => $params['module']]);
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
                $result[$recordRead] = (array) $unserializedData;

                //get the reference and the modified date
                $result[$recordRead]['id'] = $row->getRecordId();
                //todo handle datetime format and string ???
                // $result[$recordRead]['date_modified'] = $row->getDateModified();
                $result[$recordRead]['date_modified'] = $row->getDateModified()->format('Y-m-d H:i:s');

                //we increment the number of record read
                ++$recordRead;
            } catch (\Exception $e) {
                $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
                $this->logger->error($error);

                return ['error getting the data from the records' => $error];
            }
        }
        return $result;
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
}
