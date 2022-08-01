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

use App\Entity\InternalListValue as EntityInternalListValue;
use App\Entity\InternalList as EntityInternalList;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class internallistcore extends mysql
{
    public function getFieldsLogin()
    {
        try {
            return [
                [
                    'name' => 'url',
                    'type' => TextType::class,
                    'label' => 'solution.fields.url',
                ],
            ];
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);
            return array('Login field error: ' => $error);
        }
    }


    public function get_modules($type = 'source')
    {
        try {
            $modules = [];
            $table = $this->entityManager->getRepository(EntityInternalList::class)->findAll();
            foreach ($table as $column) {
                $modules[$column->getId()] = $column->getName();
            }
            return $modules;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);
            return array('module error: ' => $error);
        }
    }

    public function get_module_fields($module, $type = 'source', $extension = false)
    {
        try {
            $data = $this->entityManager->getRepository(EntityInternalListValue::class)->find($module)->getData();
            $unserializedData = unserialize($data);
            $jsondata = json_decode($unserializedData);
            foreach ($jsondata as $keydata => $valuedata) {
                $this->moduleFields[$keydata] = array('label' => $keydata, 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false);
            }
            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);
            return array('module fileds error: ' => $error);
        }
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
