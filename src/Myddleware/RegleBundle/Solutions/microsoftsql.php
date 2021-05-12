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

namespace Myddleware\RegleBundle\Solutions;

class microsoftsqlcore extends database
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
		if ($this->driver == 'sqlsrv') {
			return new \PDO($this->driver.':Server='.$this->paramConnexion['host'].','.$this->paramConnexion['port'].';Database='.$this->paramConnexion['database_name'],$this->paramConnexion['login'], $this->paramConnexion['password']);
		} else {
			return new \PDO($this->driver.':host='.$this->paramConnexion['host'].';port='.$this->paramConnexion['port'].';dbname='.$this->paramConnexion['database_name'].';charset='.$this->charset, $this->paramConnexion['login'], $this->paramConnexion['password']);
		}
	}
	
	// We use sqlsrv for windows and dblib for linux
	protected function set_driver()
	{
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$this->driver = 'sqlsrv';
		} else {
			$this->driver = 'dblib';
		}
	}
	
	// Query to get all the tables of the database
	protected function get_query_show_tables() {
		return 'SELECT table_name FROM information_schema.columns WHERE table_catalog = \''.$this->paramConnexion['database_name'].'\'';
	}
	
	// Query to get all the flieds of the table
	protected function get_query_describe_table($table) {
		return 'SELECT * FROM information_schema.columns WHERE table_name = \''.$table.'\'';
	}
	
	// Get the limit operator of the select query in the read last function
	protected function get_query_select_limit_offset($param, $method) {
		// The limit is managed with TOP if we don't have the offset parameter
		if ($method == 'read_last') {
			return null;
		}
		if (empty($param['offset'])) {
			$param['offset'] = 0;
		}
		return " OFFSET ".$param['offset']." ROWS FETCH NEXT ".$param['limit']." ROWS ONLY";
	}
	

	protected function get_query_select_header($param, $method) {
		// The limit is managed with TOP if we don't have the offset parameter
		if ($method == 'read_last') {
			return "SELECT TOP 1 ";
		}
		return parent::get_query_select_header($param, $method);
	}

}// class microsoftsqlcore

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/microsoftsql.php';
if (file_exists($file)) {
    require_once $file;
} else {
	class microsoftsql extends microsoftsqlcore {}
} 
