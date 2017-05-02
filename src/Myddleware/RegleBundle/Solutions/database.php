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

use Symfony\Bridge\Monolog\Logger;
use Myddleware\RegleBundle\Classes\rule as ruleMyddleware; // SugarCRM Myddleware

class databasecore extends solution { 
	// Protected $baseUrl;
	// Protected $messages = array();
	// Protected $duplicateDoc = array();
	
	// protected $required_fields =  array('default' => array('id','date_modified'));

	protected $driver;
	protected $pdo;
	protected $charset = 'utf8';
	
	protected $stringSeparator = '`';

	public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
			try {
			    $this->pdo = new \PDO($this->driver.':host='.$this->paramConnexion['host'].';port='.$this->paramConnexion['port'].';dbname='.$this->paramConnexion['database_name'].';charset='.$this->charset, $this->paramConnexion['login'], $this->paramConnexion['password']);
			    $this->connexion_valide = true;	
			} catch (\PDOException $e) {
				$error = 'Failed to login to Database : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
				echo $error . ';';
				$this->logger->error($error);
				return array('error' => $error);
			}
		} catch (\Exception $e) {
			$error = 'Failed to login to Database : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			echo $error . ';';
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)
 	
	public function getFieldsLogin() {	
		return array(
					 array(
                            'name' => 'login',
                            'type' => 'text',
                            'label' => 'solution.fields.login'
                        ),
					array(
                            'name' => 'password',
                            'type' => 'password',
                            'label' => 'solution.fields.password'
                        ),
					array(
                            'name' => 'host',
                            'type' => 'text',
                            'label' => 'solution.fields.host'
                        ),
					array(
                            'name' => 'database_name',
                            'type' => 'text',
                            'label' => 'solution.fields.dbname'
                        ),
					array(
                            'name' => 'port',
                            'type' => 'text',
                            'label' => 'solution.fields.dbport'
                        )
		);
	}
	
	// Get all tables from the database
	public function get_modules($type = 'source') {		
		try{
			$modules = array();
			
			// Send the query to the database
			$q = $this->pdo->prepare($this->get_query_show_tables());
			$exec = $q->execute();
			// Error management
			if(!$exec) {
				$errorInfo = $this->pdo->errorInfo();
				throw new \Exception('Show Tables: '.$errorInfo[2]);
			}
			// Get every table and add them to the module list
			$fetchAll = $q->fetchAll();
			foreach ($fetchAll as $table) {
				if(isset($table[0]))
					$modules[$table[0]] = $table[0];
			}		
			return $modules;
		} catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			return $error;			
		}
	} 	
	
	// Get all fields from the table selected
	public function get_module_fields($module, $type = 'source') {
		try{
			// Get all fields of the table in input	
			$q = $this->pdo->prepare($this->get_query_describe_table($module));
			$exec = $q->execute();
			if(!$exec) {
				$errorInfo = $this->pdo->errorInfo();
				throw new \Exception('CheckTable: (Describe) '.$errorInfo[2]);
			}	
			// Format the fields
			$fetchAll = $q->fetchAll();		
		
			foreach ($fetchAll as $field) {
				$this->moduleFields[$field[$this->fieldName]] = array(
						'label' => $field[$this->fieldLabel],
						'type' => $field[$this->fieldType],
						'type_bdd' => 'varchar(255)',
						'required' => false
				);
				// If the field contains the id indicator, we add it to the fieldsRelate list
				$idFields = $this->getIdFields($module,$type);			
				if (!empty($idFields)) {
					foreach ($idFields as $idField) {		
						if (strpos($field[$this->fieldName],$idField) !== false) {
							$this->fieldsRelate[$field[$this->fieldName]] = array(
									'label' => $field[$this->fieldLabel],
									'type' => $field[$this->fieldType],
									'type_bdd' => 'varchar(255)',
									'required' => false,
									'required_relationship' => 0
							);
						}
					}
				}
			}
			// Add relate field in the field mapping 
			if (!empty($this->fieldsRelate)) {
				$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
			}		
			return $this->moduleFields;
		}
		catch (\Exception $e){
			$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';			
			return false;
		}
	} // get_module_fields($module) 
		
	// Permet de récupérer le dernier enregistrement de la solution (utilisé pour tester le flux)
	public function read_last($param) {
		$result = array();
		$result['error'] = '';
		try {
			$query = '';
			// Si le tableau de requête est présent alors construction de la requête
			if (!empty($param['query'])) {
				foreach ($param['query'] as $key => $value) {
					if (!empty($query)) {
						$query .= ' AND ';
					} else {
						$query .= ' WHERE ';
					}
					$query .= $key." = '".$value."'";
				}
			}
			
			// ************************************************************************** TARGET
			if($param['module'] == 'NewTable') {
				// Si on a pas de table Database alors on renvoie une erreur
				if (empty($param['ruleParams']['tableID'])) {
					throw new \Exception("No table in Database for the Rule. ");
				}
				$tableID = $param['ruleParams']['tableID'];
				$tableID = mb_strtolower($tableID); // On s'assure que le nom de la règle est bien le slug en miniscule
				
				if(!isset($param['fields'])) {
					$param['fields'] = array();
				}
				$param['fields'] = array_unique($param['fields']);
				// !important $param['fields'] = $this->addRequiredField($param['fields']);
				$param['fields'] = array_values($param['fields']);
				$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
				
				// Construction de la requête SQL
				$requestSQL = $this->get_query_select_header_read_last();
				
				// Tableau des noms de champs cible de la forme 'targetField' => 'myddlewareField_TYPE'
				$targetFields = array();
				
				foreach ($param['fields'] as $field){
					// On enlève le type pour avoir le nom du champ Source
					$tab = explode('_',$field, -1);
					$fieldName = '';
					foreach ($tab as $morceau) {
						$fieldName .= $morceau.'_';
					}
					$fieldName = substr($fieldName, 0, -1);
					$targetFields[$fieldName] = $field;
					// $field = str_replace(" ", "", $field); // Solution si JQuery ajoute un espace, à n'utiliser qu'en dernier recours car fonctionne pas bien
				    $requestSQL .= $fieldName . ", "; // Ajout de chaque champ souhaité
				}
				// Suppression de la dernière virgule en laissant le +
				$requestSQL = rtrim($requestSQL,' '); 
				$requestSQL = rtrim($requestSQL,',').' '; 
				$requestSQL .= "FROM ".$tableID;

				$requestSQL .= $query; // $query vaut '' s'il n'y a pas, ça enlève une condition inutile.
					
				$requestSQL .= " ORDER BY date_modified DESC"; // Tri par date de modification
				$requestSQL .= $this->get_query_select_limit_read_last(); // Ajout de la limite souhaitée
				
				// Appel de la requête
				$q = $this->pdo->prepare($requestSQL);
				$exec = $q->execute();
				
				if(!$exec) {
					$errorInfo = $this->pdo->errorInfo();
					throw new \Exception('ReadLast: '.$errorInfo[2]);
				}
				$fetchAll = $q->fetchAll(\PDO::FETCH_ASSOC);
				$row = array();
				if(!empty($fetchAll[0])) {
					foreach ($fetchAll[0] as $key => $value) {
						if($key === 'id') {
							$row[$key] = $value;
							continue;
						} elseif($key === 'date_modified') {
							$row[$key] = $value;
							continue;
						} elseif(in_array($key, array_keys($targetFields))) {
							// Si $key existe dans les clés du tableau, on enregistre sa valeur dans $row à la clé $myddlewareField_TYPE correspondant
							$row[$targetFields[$key]] = $value;
						}
				    }
					$result['values'] = $row;
					$result['done'] = true;
				} 
				else {
					$result['done'] = false;
					$result['error'] = "No data found in ".$tableID;
				}
			} else { // **************************************************************** SOURCE
				// Si le tableau de requête est présent alors construction de la requête
				if (!empty($param['query'])) {
					$query = '';
					foreach ($param['query'] as $key => $value) {
						if (!empty($query)) {
							$query .= ' AND ';
						} else {
							$query .= ' WHERE ';
						}
						$query .= $key." = '".$value."'";
					}
				}
				
				if(!isset($param['fields'])) {
					$param['fields'] = array();
				}
				$param['fields'] = array_unique($param['fields']);
				$param['fields'] = $this->addRequiredField($param['fields']);
				$param['fields'] = array_values($param['fields']);
				$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
				
				// Construction de la requête SQL
				$requestSQL = $this->get_query_select_header_read_last();
				// TODO Ajout des champs id et date de l'utilisateur
				
				foreach ($param['fields'] as $field){
				    $requestSQL .= $field . ", "; // Ajout de chaque champ souhaité
				}
				// Suppression de la dernière virgule en laissant le +
				$requestSQL = rtrim($requestSQL,' '); 
				$requestSQL = rtrim($requestSQL,',').' '; 
				$requestSQL .= "FROM ".$param['module'];
	
				$requestSQL .= $query; // $query vaut '' s'il n'y a pas, ça enlève une condition inutile.
				
				// $requestSQL .= " ORDER BY date_modified DESC"; // Tri par date de modification TODO
				$requestSQL .= $this->get_query_select_limit_read_last(); // Ajout de la limite souhaitée	
				
				// Appel de la requête
				$q = $this->pdo->prepare($requestSQL);
				$exec = $q->execute();
				
				if(!$exec) {
					$errorInfo = $this->pdo->errorInfo();
					throw new \Exception('ReadLast: '.$errorInfo[2]);
				}
				$fetchAll = $q->fetchAll(\PDO::FETCH_ASSOC);	
				
				$row = array();
				if(!empty($fetchAll[0])) {
					foreach ($fetchAll[0] as $key => $value) {
						// Could be ampty when we use simulation for example
						if(
								!empty($param['ruleParams']['fieldId'])
							&& $key === $param['ruleParams']['fieldId']
						) { // ID non trouvé
							$row[$key] = $value;
							$row['id'] = $value;
						} 
						if(
								!empty($param['ruleParams']['fieldDateRef'])
							&& $key === $param['ruleParams']['fieldDateRef']
						) {
							$row[$key] = $value;
							$row['date_modified'] = $value;
						} 
						// On doit faire le continue de façon extérieur car le fieldId peut être égal au fieldDateRef
						if (!empty($row[$key])) {
							continue;
						}
						if(in_array($key, $param['fields'])) {
							$row[$key] = $value;
						}
				    }
					$result['values'] = $row;
					$result['done'] = true;
				} 
				else {
					$result['done'] = false;
					$result['error'] = "No data found in ".$tableID;
				}
			}
		}
		catch (\Exception $e) {
			$result['done'] = -1;
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
		}
// print_r($result);			
		return $result;
	} // read_last($param)
	
	// Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
	public function read($param) {
		$result = array();
		try {
			// On contrôle la date de référence, si elle est vide on met 0 (cas fréquent si l'utilisateur oublie de la remplir)		
			if(empty($param['date_ref'])) {
				$param['date_ref'] = 0;
			}
			
			// On contrôle que les champs "id" et "date_modified" ont bien été renseignés
			if(!isset($param['ruleParams']['fieldId'])) throw new \Exception('FieldId has to be specified for the read.');
			if(!isset($param['ruleParams']['fieldDateRef'])) throw new \Exception('"fieldDateRef" has to be specified for the read.');
			$this->required_fields =  array('default' => array($param['ruleParams']['fieldId'], $param['ruleParams']['fieldDateRef']));
			
			if(!isset($param['fields'])) {
				$param['fields'] = array();
			}
			$param['fields'] = array_unique($param['fields']);
			$param['fields'] = $this->addRequiredField($param['fields']);
			$param['fields'] = array_values($param['fields']);
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			
			// Construction de la requête SQL
			$requestSQL = "SELECT ";
			// TODO Ajout des champs id et date de l'utilisateur
			
			foreach ($param['fields'] as $field){
			    $requestSQL .= $field . ", "; // Ajout de chaque champ souhaité
			}
			// Suppression de la dernière virgule en laissant le +
			$requestSQL = rtrim($requestSQL,' '); 
			$requestSQL = rtrim($requestSQL,',').' '; 
			$requestSQL .= "FROM ".$param['module'];

			$requestSQL .= " WHERE ".$param['ruleParams']['fieldDateRef']. " > '".$param['date_ref']."'";
			
			$requestSQL .= " ORDER BY ".$param['ruleParams']['fieldDateRef']. " ASC"; // Tri par date utilisateur
			
			// Appel de la requête
			$q = $this->pdo->prepare($requestSQL);
			$exec = $q->execute();
			
			if(!$exec) {
				$errorInfo = $this->pdo->errorInfo();
				throw new \Exception('Read: '.$errorInfo[2]);
			}
			$fetchAll = $q->fetchAll(\PDO::FETCH_ASSOC);
			
			$row = array();
			if(!empty($fetchAll)) {
				$result['count'] = count($fetchAll);
				foreach ($fetchAll as $elem) {
					$row = array();
					foreach ($elem as $key => $value) {
						if($key === $param['ruleParams']['fieldId']) {
							$row["id"] = $value;
						} elseif($key === $param['ruleParams']['fieldDateRef']) {
							$row['date_modified'] = $value;
							$result['date_ref'] = $value;
						}
						if(in_array($key, $param['fields'])) {
							$row[$key] = $value;
						}
				    }
					$result['values'][$row['id']] = $row;
				}
			} 
			else {
				$result['error'] = "No Results";
			}
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
		}
		return $result;	
	} // read($param)
	
	// Permet de créer des données
	public function create($param) {	
		try {					
			// For every document
			foreach($param['data'] as $idDoc => $data) {					
				try {
					// Check control before create
					$data = $this->checkDataBeforeCreate($param, $data);
					// Query init
					$sql = "INSERT INTO ".$this->stringSeparator.$param['module'].$this->stringSeparator." (";
					$values = "(";
					// We build the query with every fields
					foreach ($data as $key => $value) {				
						if($key == "target_id") {
							continue;
						}
						$sql .= $this->stringSeparator.$key.$this->stringSeparator.",";
						$values .= "'".$value."',";
					}
					// Remove the last coma
					$sql = substr($sql, 0, -1); // INSERT INTO table_name (column1,column2,column3,...)
					$values = substr($values, 0, -1);
					$values .= ")"; // VALUES (value1,value2,value3,...)
					$sql .= ") VALUES ".$values; // INSERT INTO table_name (column1,column2,column3,...) VALUES (value1,value2,value3,...)				
					$q = $this->pdo->prepare($sql);
					$exec = $q->execute();
					
					if(!$exec) {
						$errorInfo = $this->pdo->errorInfo();
						throw new \Exception('Create: '.$errorInfo[2]);
					}
					
					$idTarget = $this->pdo->lastInsertId();
					if(!isset($idTarget)) { // could be 0
						throw new \Exception('Create: No ID returned.');
					}
					// Send the target ifd to Myddleware
					$result[$idDoc] = array(
											'id' => $idTarget,
											'error' => false
									);
				}
				catch (\Exception $e) {
					$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
					$result[$idDoc] = array(
							'id' => '-1',
							'error' => $error
					);
				}
				// Status modification for the transfer
				$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
			}
		}
		catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$result[$idDoc] = array(
					'id' => '-1',
					'error' => $error
			);
		}
		return $result;
	}

	// Permet de créer des données
	public function update($param) {
		try {			
			// For every document
			foreach($param['data'] as $idDoc => $data) {
				try {
					// Check control before update
					$data = $this->checkDataBeforeUpdate($param, $data);
					// Query init
					$sql = "UPDATE ".$this->stringSeparator.$param['module'].$this->stringSeparator." SET "; 
					// We build the query with every fields
					// Boucle sur chaque champ du document
					foreach ($data as $key => $value) {				
						if($key == "target_id") {
							$idTarget = $value;
							continue;
						}				
						$sql .= $key."='".$value."',";
					}
					// Remove the last coma
					$sql = substr($sql, 0, -1);
					$sql .= " WHERE ".$param['ruleParams']['targetfieldId']."='".$idTarget."'";	
					// Execute the query
					$q = $this->pdo->prepare($sql);
					$exec = $q->execute();
					if(!$exec) {
						$errorInfo = $this->pdo->errorInfo();
						throw new \Exception('Create: '.$errorInfo[2]);
					}
					// Send the target ifd to Myddleware
					$result[$idDoc] = array(
											'id' => $idTarget,
											'error' => false
									);
				}
				catch (\Exception $e) {
					$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
					$result[$idDoc] = array(
							'id' => '-1',
							'error' => $error
					);
				}
				// Status modification for the transfer
				$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
			}
		}
		catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$result[$idDoc] = array(
					'id' => '-1',
					'error' => $error
			);
		}
		return $result;
	}
	
	// Get the strings which can identify what field is an id in the table
	protected function getIdFields($module,$type) {
		// default is id
		return array('id');
	}

	public function getFieldsParamUpd($type, $module, $myddlewareSession) {	
		try {
			$fieldsSource = $this->get_module_fields($module, $type, false);
			if(!empty($fieldsSource)) {
				if ($type == 'source'){
					$idParam = array(
								'id' => 'fieldId',
								'name' => 'fieldId',
								'type' => 'option',
								'label' => 'Primary key in your source table',
								'required'	=> true
							);
					$dateParam = array(
								'id' => 'fieldDateRef',
								'name' => 'fieldDateRef',
								'type' => 'option',
								'label' => 'Field Date Reference',
								'required'	=> true
							);
					foreach ($fieldsSource as $key => $value) {
						$idParam['option'][$key] = $value['label'];
						$dateParam['option'][$key] = $value['label'];
					}
					$params[] = $idParam;
					$params[] = $dateParam;
				} else {
					$idParam = array(
								'id' => 'targetFieldId',
								'name' => 'targetfieldId',
								'type' => 'option',
								'label' => 'Primary key in your target table',
								'required'	=> true
							);
					foreach ($fieldsSource as $key => $value) {
						$idParam['option'][$key] = $value['label'];
					}
					$params[] = $idParam;
				}
				return $params;
			}
			return array();
		}
		catch (\Exception $e){
			return array();
		}
	}

}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/database.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class database extends databasecore {
		
	}
}