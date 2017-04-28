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

class mysqlcore extends database {
	
	protected $driver = 'mysql';
	
	protected $fieldName = 'Field';
	protected $fieldLabel = 'Field';
	protected $fieldType = 'Type';
	
	// Generate query
	protected function get_query_show_tables() {
		return 'SHOW TABLES FROM '.$this->paramConnexion['database_name'];
	}
	
	// Query to get all the flieds of the table
	protected function get_query_describe_table($table) {
		return 'DESCRIBE `'.$table.'`';
	}
	
	// Get the header of the select query in the read last function
	protected function get_query_select_header_read_last() {
		return "SELECT id, date_modified, ";
	}
	
	// Get the limit operator of the select query in the read last function
	protected function get_query_select_limit_read_last() {
		return " LIMIT 1";
	}
	
	// Get the alter column operator
	protected function get_query_alter_column() {
		return " MODIFY COLUMN ";
	}
	
	// Get the header of an insert query
	protected function get_query_create_table_header($table) {
		return  "CREATE TABLE IF NOT EXISTS ".$table." (
			id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
			date_modified datetime default CURRENT_TIMESTAMP,";
	}
	
}// class mysqlcore

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/mysql.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class mysql extends mysqlcore {
		
	}
} 