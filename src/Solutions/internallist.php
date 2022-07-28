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
            for ($i = 1; $i <= 2; $i++) {
                $id = $this->entityManager->getRepository(EntityInternalList::class)->find($i)->getId();
                $name = $this->entityManager->getRepository(EntityInternalList::class)->find($i)->getName();
                $modules[(string)$id] = $name;
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
            require 'lib/internallist/metadata.php';
            $this->moduleFields = $moduleFields[$module];
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

    public function read($params)
    {
    }
} // class mysqlcore

class internallist extends internallistcore
{
}
