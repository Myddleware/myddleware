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
	
	protected $driver = 'dblib';
	
	protected $fieldName = 'COLUMN_NAME';
	protected $fieldLabel = 'COLUMN_NAME';
	protected $fieldType = 'DATA_TYPE';
	
	// Convert type from mysql du mssql
	// https://dev.mysql.com/doc/workbench/en/wb-migration-database-mssql-typemapping.html
	/* protected $convertType = array(
		'TINYINT' => 'tinyint',
		'SMALLINT' => 'smallint',
		'MEDIUMINT' => 'int',
		'INT' => 'int',
		'BIGINT' => 'bigint',
		'DECIMAL' => 'decimal',
		'FLOAT' => 'float',
		'DOUBLE' => 'float',
		'REAL' => 'float',
		'DATETIME' => 'datetime2',
		'DATE' => 'date',
		'TIME' => 'time',
		'TIMESTAMP' => 'smalldatetime',
		'YEAR' => 'smallint',
		'CHAR' => 'nchar',
		'VARCHAR' => 'nvarchar',
		'TINYTEXT' => 'nvarchar',
		'TEXT' => 'nvarchar',
		'MEDIUMTEXT' => 'nvarchar',
		'LONGTEXT' => 'nvarchar',
		'BINARY' => 'binary',
		'VARBINARY' => 'binary',
		'TINYBLOB' => 'binary',
		'BLOB' => 'binary',
		'MEDIUMBLOB' => 'binary',
		'LONGBLOB' => 'binary'
	); */

	// Query to get all the tables of the database
	public function get_query_show_tables() {
		return 'SELECT table_name FROM information_schema.tables';
	}
	
	// Query to get all the flieds of the table
	public function get_query_describe_table($table) {
		return 'SELECT * FROM information_schema.columns WHERE table_name = \''.$table.'\'';
	}
	
	// Get the header of an insert query
	protected function get_query_create_table_header($table) {
		return  "CREATE TABLE ".$param['rule']['name_slug']." (
			id int not null IDENTITY(1, 1) PRIMARY KEY,
			date_modified smalldatetime default CURRENT_TIMESTAMP,";
	}
	
	// Get the header of an insert query
	protected function get_query_insert_header($table) {
		return  "INSERT INTO ".$table." ("; ;
	}
	
/* 		// Créer un table dans Database
	protected function createDatabaseTable($param) {
// $this->logger->error('$paramLogin : '.print_r($paramLogin,true));		
	    $dbh = new \PDO($this->driver.':host='.$this->host.';port='.$this->port.';dbname='.$this->dbname, $this->login, $this->password);

		$sql = "CREATE TABLE ".$param['rule']['name_slug']." (
			id int not null IDENTITY(1, 1) PRIMARY KEY,
			date_modified smalldatetime default CURRENT_TIMESTAMP,";

		
		if (empty($param['ruleFields'])) {
			throw new \Exception("Failed to create the table, no field in the Rule ".$param['rule']['name_slug']);
		}
		// Création du mapping dans Database
		Foreach ($param['ruleFields'] as $ruleField) {
			$mappingType = $this->getMappingType($ruleField['target_field_name']);
			
			if (empty($mappingType)) {
				throw new \Exception("Mapping Type unknown for the field ".$ruleField['target_field_name'].". Failed to create the table in Database");
			}
			
			// Pour les champs date et metric (fixés car obligatoire), on garde le nom de champ source sinon on met le champ saisi par l'utilisateur pour affichage dans Database
			$tab = explode('_',$ruleField['target_field_name'], -1);
			$fieldName = '';
			foreach ($tab as $morceau) {
				$fieldName .= $morceau.'_';
			}
			$fieldName = substr($fieldName, 0, -1);
			$sql.= $fieldName." ".$mappingType.",";
		}
		$sql.= " INDEX ".$param['rule']['name_slug']."_date_modified (date_modified))";						   
	 		
		$q = $dbh->prepare($sql);
		$exec = $q->execute();
		$dbh = null;
		if(!$exec) { // Si erreur
			$errorInfo = $dbh->errorInfo();
			throw new \Exception('Failed to create the table, :' . $errorInfo[2].' - Query : '.$sql);
			$this->logger->error('Failed to create the table, :' . $errorInfo[2].' - Query : '.$sql);
		}
		$this->messages[] = array('type' => 'success', 'message' => 'Table '.$param['rule']['name_slug'].' successfully created in Database. ');		
		return $this->saveConnectorParams($param['ruleId'], $param['rule']['name_slug']);
	}
	 */
		// Fonction permettant de récupérer le type d'un champ
/* 	protected function getMappingType($field) {
		$filedType = parent::getMappingType($field);
		if (!empty($this->convertType[$filedType])) {
			return $this->convertType[$filedType];
		}
		return null;
	} */
	
	// Fonction permettant de récupérer le type d'un champ
	/* protected function getMappingType($field) {
		if (stripos($field, 'TEXT') !== false) {
			return 'TEXT';
		}
		if (stripos($field, 'VARCHAR') !== false) {
			return 'nvarchar(255)';
		}
		// Les champs référence sont considéré comme des filtres et permettent de lier plusieurs règles
		if (stripos($field, 'INT') !== false) {
			return 'int';
		}
		if (stripos($field, 'BOOL') !== false) {
			return 'tinyint';
		}
		if (stripos($field, 'DATE') !== false) {
			return 'date';
		}
		return null;
	}
	 */
	// public function afterRuleSave($data,$type) {
		// return parent::afterRuleSave($data,$type);
	// }

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