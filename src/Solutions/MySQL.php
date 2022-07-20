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

class MySQL extends Database
{
    protected $driver = 'mysql';

    protected $fieldName = 'Field';

    protected $fieldLabel = 'Field';

    protected $fieldType = 'Type';

    // Enable to delete data
    protected bool $sendDeletion = true;

    protected bool $readDeletion = true;

    protected function generatePdo()
    {
        return new \PDO($this->driver.':host='.$this->connectionParam['host'].';port='.$this->connectionParam['port'].';dbname='.$this->connectionParam['database_name'].';charset='.$this->charset, $this->connectionParam['login'], $this->connectionParam['password']);
    }

    // Generate query
    protected function get_query_show_tables()
    {
        return 'SHOW TABLES FROM '.$this->stringSeparatorOpen.$this->connectionParam['database_name'].$this->stringSeparatorClose;
    }

    // Query to get all the flieds of the table
    protected function get_query_describe_table($table)
    {
        return 'DESCRIBE '.$this->stringSeparatorOpen.$table.$this->stringSeparatorClose;
    }

    // Get the limit operator of the select query in the read last function
    protected function get_query_select_limit_offset($param, $method)
    {
        if (empty($param['offset'])) {
            $param['offset'] = 0;
        }

        return ' LIMIT '.$param['limit'].' OFFSET '.$param['offset'];
    }
}
