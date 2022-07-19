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

class MicrosoftSQL extends Database
{
    protected $driver;

    protected $fieldName = 'COLUMN_NAME';

    protected $fieldLabel = 'COLUMN_NAME';

    protected $fieldType = 'DATA_TYPE';

    protected $stringSeparatorOpen = '[';

    protected $stringSeparatorClose = ']';

    // Generate PDO object
    protected function generatePdo()
    {
        $this->set_driver();
        if ('sqlsrv' == $this->driver) {
            return new \PDO($this->driver.':Server='.$this->connectionParam['host'].','.$this->connectionParam['port'].';Database='.$this->connectionParam['database_name'], $this->connectionParam['login'], $this->connectionParam['password']);
        }

        return new \PDO($this->driver.':host='.$this->connectionParam['host'].';port='.$this->connectionParam['port'].';dbname='.$this->connectionParam['database_name'].';charset='.$this->charset, $this->connectionParam['login'], $this->connectionParam['password']);
    }

    // We use sqlsrv for windows and dblib for linux
    protected function set_driver()
    {
        if ('WIN' === strtoupper(substr(PHP_OS, 0, 3))) {
            $this->driver = 'sqlsrv';
        } else {
            $this->driver = 'dblib';
        }
    }

    // Query to get all the tables of the database
    protected function get_query_show_tables()
    {
        return 'SELECT table_name FROM information_schema.columns WHERE table_catalog = \''.$this->connectionParam['database_name'].'\'';
    }

    // Query to get all the flieds of the table
    protected function get_query_describe_table($table)
    {
        return 'SELECT * FROM information_schema.columns WHERE table_name = \''.$table.'\'';
    }

    // Get the limit operator of the select query in the read last function
    protected function get_query_select_limit_offset($param, $method)
    {
        // The limit is managed with TOP if we don't have the offset parameter
        if ('read_last' == $method) {
            return;
        }
        if (empty($param['offset'])) {
            $param['offset'] = 0;
        }

        return ' OFFSET '.$param['offset'].' ROWS FETCH NEXT '.$param['limit'].' ROWS ONLY';
    }

    // Function to escape characters
    protected function escape($value)
    {
        return str_replace("'", "''", $value);
    }

    protected function get_query_select_header($param, $method)
    {
        // The limit is managed with TOP if we don't have the offset parameter
        if ('read_last' == $method) {
            return 'SELECT TOP 1 ';
        }

        return parent::get_query_select_header($param, $method);
    }
}
