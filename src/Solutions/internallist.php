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

use Symfony\Component\Form\Extension\Core\Type\TextType;

class internallistcore extends mysql
{


    public function getFieldsLogin()
    {
        return [
            [
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
        ];
    }


    public function get_modules($type = 'source')
    {
        return [
            'internal_list' => 'InternalList'
            // 'internal_list_value' => 'InternalListValue'
        ];
    }

    public function get_module_fields($module, $type = 'source', $extension = false)
    {
        parent::get_module_fields($module, $type);
        require 'lib/woocommerce/metadata.php';
        try {
            if (!empty($moduleFields[$module])) {
                $this->moduleFields = array_merge($this->moduleFields, $moduleFields[$module]);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine());
            return false;
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
