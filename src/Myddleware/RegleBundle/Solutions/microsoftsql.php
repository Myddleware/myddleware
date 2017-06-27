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

class microsoftsqlcore extends database {
	
	protected $driver = 'sqlsrv';
	
	protected $fieldName = 'COLUMN_NAME';
	protected $fieldLabel = 'COLUMN_NAME';
	protected $fieldType = 'DATA_TYPE';
	
	protected $stringSeparator = '';

	protected function generatePdo() {		    
		return new \PDO($this->driver.':Server='.$this->paramConnexion['host'].','.$this->paramConnexion['port'].',Database='.$this->paramConnexion['database_name'],$this->paramConnexion['login'], $this->paramConnexion['password']);
	}
	
	// Query to get all the tables of the database
	protected function get_query_show_tables() {
		return 'SELECT table_name FROM information_schema.tables';
	}
	
	// Query to get all the flieds of the table
	protected function get_query_describe_table($table) {
		return 'SELECT * FROM information_schema.columns WHERE table_name = \''.$table.'\'';
	}
	
	// Get the header of the select query in the read last function
	protected function get_query_select_header_read_last() {
		return "SELECT TOP 1 ";
	}
	
	// Get the limit operator of the select query in the read last function
	protected function get_query_select_limit_read_last() {
		return "";
	}

}// class microsoftsqlcore

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/microsoftsql.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class microsoftsql extends microsoftsqlcore {

	}
} 