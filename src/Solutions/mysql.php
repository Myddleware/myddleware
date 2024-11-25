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

class mysqlcore extends database
{
    protected $driver = 'mysql';

    protected string $fieldName = 'Field';
    protected string $fieldLabel = 'Field';
    protected string $fieldType = 'Type';
    // Enable to delete data
    protected bool $sendDeletion = true;
    protected bool $readDeletion = true;

    protected function generatePdo(): \PDO
    {
        return new \PDO($this->driver.':host='.$this->paramConnexion['host'].';port='.$this->paramConnexion['port'].';dbname='.$this->paramConnexion['database_name'].';charset='.$this->charset, $this->paramConnexion['login'], $this->paramConnexion['password']);
    }

    // Generate query
    protected function get_query_show_tables($type): string
    {
        return 'SHOW FULL TABLES 
				FROM '.$this->stringSeparatorOpen.$this->paramConnexion['database_name'].$this->stringSeparatorClose 
				.($type == 'target' ? ' WHERE table_type = "BASE TABLE"' : '');
    }

    // Query to get all the flieds of the table
    protected function get_query_describe_table($table): string
    {
        return 'DESCRIBE '.$this->stringSeparatorOpen.$table.$this->stringSeparatorClose;
    }

    // Get the limit operator of the select query in the read last function
    protected function get_query_select_limit_offset($param, $method): string
    {
        if (empty($param['offset'])) {
            $param['offset'] = 0;
        }

        return ' LIMIT '.$param['limit'].' OFFSET '.$param['offset'];
    }
}

// Manage custom development
$file = __DIR__.'/../Custom/Solutions/mysqlcustom.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class mysql extends mysqlcore {
		
	}
} 