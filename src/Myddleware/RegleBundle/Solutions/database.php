<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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
use Myddleware\RegleBundle\Classes\rule as ruleMyddleware;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType; 

class databasecore extends solution { 

	protected $driver;
	protected $pdo;
	protected $charset = 'utf8';
	
	protected $stringSeparatorOpen = '`';
	protected $stringSeparatorClose = '`';

	public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
			try {
				$this->pdo = $this->generatePdo();
			    $this->connexion_valide = true;	
			} catch (\PDOException $e) {
				$error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
				$this->logger->error($error);
				return array('error' => $error);
			}
		} catch (\Exception $e) {
			$error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)
 	
	public function getFieldsLogin() {	
		return array(
					 array(
                            'name' => 'login',
                            'type' => TextType::class,
                            'label' => 'solution.fields.login'
                        ),
					array(
                            'name' => 'password',
                            'type' => PasswordType::class,
                            'label' => 'solution.fields.password'
                        ),
					array(
                            'name' => 'host',
                            'type' => TextType::class,
                            'label' => 'solution.fields.host'
                        ),
					array(
                            'name' => 'database_name',
                            'type' => TextType::class,
                            'label' => 'solution.fields.dbname'
                        ),
					array(
                            'name' => 'port',
                            'type' => TextType::class,
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
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			return $error;			
		}
	} 	
	
	// Get all fields from the table selected
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			// parent::get_module_fields($module, $type);
			// Get all fields of the table in input	
			$q = $this->pdo->prepare($this->get_query_describe_table($module));
			$exec = $q->execute();
			if(!$exec) {
				$errorInfo = $this->pdo->errorInfo();
				throw new \Exception('CheckTable: (Describe) '.$errorInfo[2]);
			}
			// Format the fields
			$fields = $q->fetchAll();		
			// Get field ID
			$idFields = $this->getIdFields($module,$type,$fields);			
		
			foreach ($fields as $field) {
				$this->moduleFields[$field[$this->fieldName]] = array(
						'label' => $field[$this->fieldLabel],
						'type' => $field[$this->fieldType],
						'type_bdd' => 'varchar(255)',
						'required' => false
				);
				if (
						strtoupper(substr($field[$this->fieldName],0,2)) == 'ID'
					OR	strtoupper(substr($field[$this->fieldName],-2)) == 'ID'
				) {
					$this->fieldsRelate[$field[$this->fieldName]] = array(
							'label' => $field[$this->fieldLabel],
							'type' => $field[$this->fieldType],
							'type_bdd' => 'varchar(255)',
							'required' => false,
							'required_relationship' => 0
					);
				}
				// If the field contains the id indicator, we add it to the fieldsRelate list
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
			// Add field current ID in the relationships
			if ($type == 'target') {
				$this->fieldsRelate['Myddleware_element_id'] = array(
										'label' => 'ID '.$module,
										'type' => 'varchar(255)',
										'type_bdd' => 'varchar(255)',
										'required' => false,
										'required_relationship' => 0
									);
			}			
			return $this->moduleFields;
		}
		catch (\Exception $e){
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';			
			return false;
		}
	} // get_module_fields($module) 
		
	// Permet de récupérer le dernier enregistrement de la solution (utilisé pour tester le flux)
	public function read_last($param) {		
		$result = array();
		$result['error'] = '';
		try {
			// Add requiered fields
			if(!empty($param['ruleParams']['fieldId'])) {
				$this->required_fields =  array('default' => array($param['ruleParams']['fieldId']));	
			} elseif(!empty($param['ruleParams']['targetFieldId'])) {
				$this->required_fields =  array('default' => array($param['ruleParams']['targetFieldId']));	
			}
			
			$where = '';
			// Generate the WHERE
			if (!empty($param['query'])) {
				foreach ($param['query'] as $key => $value) {
					if (!empty($query)) {
						$where .= ' AND ';
					} else {
						$where .= ' WHERE ';
					}
					// If key is id, it has to be replaced by the real name of the id in the target table 
					if ($key == 'id') {
						if(!empty($param['ruleParams']['targetFieldId'])) {
							$key = $param['ruleParams']['targetFieldId'];
						}
						else {
							throw new \Exception('"targetFieldId" has to be specified for read the data in the target table.');
						}
					}
					$where .= $this->stringSeparatorOpen.$key.$this->stringSeparatorClose." = '".$value."'";
				}
			} // else the function is called for a simulation (rule creation), the limit is manage in the query creation
			
			if(!isset($param['fields'])) {
				$param['fields'] = array();
			}
		
			$param['fields'] = array_unique($param['fields']);	
			$param['fields'] = $this->addRequiredField($param['fields']);	
			$param['fields'] = array_values($param['fields']);		
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);	
			
			// Construction de la requête SQL
			$param['limit'] = 1;	
			$requestSQL = $this->get_query_select_header($param, 'read_last');		
			foreach ($param['fields'] as $field){
				// If key is id, it has to be replaced by the real name of the id in the target table 
				if ($field == 'id') {			
					if(!empty($param['ruleParams']['targetFieldId'])) {
						$field = $param['ruleParams']['targetFieldId'];			
					}
					else {
						throw new \Exception('"targetFieldId" has to be specified for read the data in the target table.');
					}
				}
				$requestSQL .= $this->stringSeparatorOpen.$field.$this->stringSeparatorClose. ", "; // Ajout de chaque champ souhaité
			}
			// Remove the last coma/space
			$requestSQL = rtrim($requestSQL,' '); 
			$requestSQL = rtrim($requestSQL,',').' '; 
			$requestSQL .= "FROM ".$this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose;
			$requestSQL .= $where; // $where vaut '' s'il n'y a pas, ça enlève une condition inutile.
			$requestSQL .= $this->get_query_select_limit_offset($param, 'read_last'); // Add query limit
			// Query validation
			$requestSQL = $this->queryValidation($param, 'read_last', $requestSQL);
		
			// Appel de la requête
			$q = $this->pdo->prepare($requestSQL);
			$exec = $q->execute();
			
			if(!$exec) {
				$errorInfo = $this->pdo->errorInfo();
				throw new \Exception('ReadLast: '.$errorInfo[2].' . Query : '.$requestSQL);
			}
			$fetchAll = $q->fetchAll(\PDO::FETCH_ASSOC);			
			$row = array();
			if(!empty($fetchAll[0])) {
				foreach ($fetchAll[0] as $key => $value) {
					// Could be ampty when we use simulation for example
					if(
							(
								!empty($param['ruleParams']['fieldId'])
							&& $key === $param['ruleParams']['fieldId']
							)
						OR
							(
								!empty($param['ruleParams']['targetFieldId'])
							&& $key === $param['ruleParams']['targetFieldId']						
							)
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
				$result['error'] = "No data found in ".$param['module'];
			}
		}
		catch (\Exception $e) {
			$result['done'] = -1;
		    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';					
		}					
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
			if (empty($param['limit'])) {
				$param['limit'] = 100;
			}
			
			// Add the deletion field into the list field to be read if deletion is enabled on the rule
			if (
					!empty($param['ruleParams']['deletion'])
				AND	!empty($param['ruleParams']['deletionField'])
			) {
				$param['fields'][] = $param['ruleParams']['deletionField'];
			}
			
			// Add requiered fields
			if(!isset($param['ruleParams']['fieldId'])) {
				throw new \Exception('FieldId has to be specified for the read.');
			}
			if(!isset($param['ruleParams']['fieldDateRef'])) {
				throw new \Exception('"fieldDateRef" has to be specified for the read.');
			}
			$this->required_fields =  array('default' => array($param['ruleParams']['fieldId'], $param['ruleParams']['fieldDateRef']));
			
			if(!isset($param['fields'])) {
				$param['fields'] = array();
			}
			$param['fields'] = array_unique($param['fields']);
			$param['fields'] = $this->addRequiredField($param['fields']);
			$param['fields'] = array_values($param['fields']);
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			
			// Query building
			$requestSQL = $this->get_query_select_header($param, 'read');	
			
			foreach ($param['fields'] as $field){
			    $requestSQL .= $this->stringSeparatorOpen.$field.$this->stringSeparatorClose. ", "; // Ajout de chaque champ souhaité
			}
			// Suppression de la dernière virgule en laissant le +
			$requestSQL = rtrim($requestSQL,' '); 
			$requestSQL = rtrim($requestSQL,',').' '; 
			$requestSQL .= "FROM ".$this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose;

			// if a specific query is requeted we don't use date_ref
			if (!empty($param['query'])) {
				$nbFilter = count($param['query']);
				$requestSQL .= " WHERE ";
				foreach ($param['query'] as $queryKey => $queryValue) {
					// Manage query with id, to be replaced by the ref Id fieldname
					if ($queryKey == 'Id') {
						$queryKey = $param['ruleParams']['fieldId'];
					}
					$requestSQL .= $this->stringSeparatorOpen.$queryKey.$this->stringSeparatorClose." = '".$this->escape($queryValue)."' "; 
					$nbFilter--;
					if ($nbFilter > 0){
						$requestSQL .= " AND ";	
					}
				}
			} else {
				$requestSQL .= " WHERE ".$this->stringSeparatorOpen.$param['ruleParams']['fieldDateRef'].$this->stringSeparatorClose. " > '".$param['date_ref']."'";
			}
			
			$requestSQL .= " ORDER BY ".$this->stringSeparatorOpen.$param['ruleParams']['fieldDateRef'].$this->stringSeparatorClose. " ASC"; // Tri par date utilisateur
			$requestSQL .= $this->get_query_select_limit_offset($param, 'read'); // Add query limit
			// Query validation
			$requestSQL = $this->queryValidation($param, 'read', $requestSQL);

			// Appel de la requête
			$q = $this->pdo->prepare($requestSQL);		
			$exec = $q->execute();

			if(!$exec) {
				$errorInfo = $this->pdo->errorInfo();
				throw new \Exception('Read: '.$errorInfo[2].' . Query : '.$requestSQL);
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
						} 
						if($key === $param['ruleParams']['fieldDateRef']) {
							// If the reference isn't a valid date (it could be an ID in case there is no date in the table) we set the current date
							if ((bool)strtotime($value)) {;
								$row['date_modified'] = $value;
							} else {							
								$row['date_modified'] = date('Y-m-d H:i:s');
							}
							$result['date_ref'] = $value;
						}
						if(in_array($key, $param['fields'])) {
							$row[$key] = $value;
						}
						// Manage deletion by adding the flag Myddleware_deletion to the record						
						if (
								$param['ruleParams']['deletion'] == true
							AND $param['ruleParams']['deletionField'] === $key
							AND !empty($value)
						) {
							$row['myddleware_deletion'] = true;
						}
					}
					$result['values'][$row['id']] = $row;
				}
			}
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
		}	
		return $result;
	} // read($param)
	
	// Permet de créer des données
	public function create($param) {	
		try {			
			// Get the target reference field
			if(!isset($param['ruleParams']['targetFieldId'])) {
				throw new \Exception('targetFieldId has to be specified for the data creation.');
			}
		
			// For every document
			foreach($param['data'] as $idDoc => $data) {					
				try {
					unset($idTarget);
					// Check control before create
					$data = $this->checkDataBeforeCreate($param, $data);
					// Query init
					$sql = "INSERT INTO ".$this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose." (";
					$values = "(";
					// We build the query with every fields
					foreach ($data as $key => $value) {				
						if($key == "target_id") {
							continue;
						// If the target reference field is in data sent, we save it to update the document	
						} elseif($key == $param['ruleParams']['targetFieldId']) {
							$idTarget = $value;
						}
						$sql .= $this->stringSeparatorOpen.$key.$this->stringSeparatorClose.",";
						$values .= "'".$this->escape($value)."',";
					}
					
					// Remove the last coma
					$sql = substr($sql, 0, -1); // INSERT INTO table_name (column1,column2,column3,...)
					$values = substr($values, 0, -1);
					$values .= ")"; // VALUES (value1,value2,value3,...)
					$sql .= ") VALUES ".$values; // INSERT INTO table_name (column1,column2,column3,...) VALUES (value1,value2,value3,...)	
					// Query validation
					$sql = $this->queryValidation($param, 'create', $sql);	
					
					$q = $this->pdo->prepare($sql);
					$exec = $q->execute();	
					if(!$exec) {
						$errorInfo = $this->pdo->errorInfo();
						throw new \Exception('Create: '.$errorInfo[2].' . Query : '.$sql);
					}
					
					// If the target reference field isn't in data sent
					if (!isset($idTarget)) {
						// If the target reference field is a primary key auto increment, we retrive the value here
						$idTarget = $this->pdo->lastInsertId();
					}
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
					$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
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
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
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
					// Check control before delete
					$data = $this->checkDataBeforeUpdate($param, $data);
					// Query init
					$sql = "UPDATE ".$this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose." SET "; 
					// We build the query with every fields
					// Boucle sur chaque champ du document
					foreach ($data as $key => $value) {				
						if($key == "target_id") {
							$idTarget = $value;
							continue;
						// Myddleware_element_id is a Myddleware field, it doesn't exist in the target database	
						} elseif ($key == "Myddleware_element_id") {
							continue;
						}								
						$sql .= $this->stringSeparatorOpen.$key.$this->stringSeparatorClose."='".$this->escape($value)."',";
					}
					if(empty($idTarget)) {					
						throw new \Exception('No target id found. Failed to update the record.');
					}
					// Remove the last coma
					$sql = substr($sql, 0, -1);
					$sql .= " WHERE ".$this->stringSeparatorOpen.$param['ruleParams']['targetFieldId'].$this->stringSeparatorClose."='".$idTarget."'";	
					// Query validation
					$sql = $this->queryValidation($param, 'update', $sql);					
					// Execute the query					
					$q = $this->pdo->prepare($sql);
					$exec = $q->execute();
					if(!$exec) {
						$errorInfo = $this->pdo->errorInfo();						
						throw new \Exception('Update: '.$errorInfo[2].' . Query : '.$sql);
					}
					// Send the target ifd to Myddleware
					$result[$idDoc] = array(
											'id' => $idTarget,
											'error' => ($q->rowCount() ? false : 'There is no error but 0 row has been updated.')
									);									
				}
				catch (\Exception $e) {
					$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
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
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$result[$idDoc] = array(
					'id' => '-1',
					'error' => $error
			);
		}
		return $result;
	}
	
	// Function to delete a record
	public function delete($param) {
		try {		
			// For every document
			foreach($param['data'] as $idDoc => $data) {
				try {
					// Check control before delete
					$data = $this->checkDataBeforeDelete($param, $data);
					if (empty($data['target_id'])) {
						throw new \Exception('No target id found. Failed to delete the record.');
					}
					// Query init
					$sql = "DELETE FROM ".$this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose." "; 
					$sql .= " WHERE ".$this->stringSeparatorOpen.$param['ruleParams']['targetFieldId'].$this->stringSeparatorClose."='".$data['target_id']."'";	
					// Query validation
					$sql = $this->queryValidation($param, 'delete', $sql);					
					// Execute the query					
					$q = $this->pdo->prepare($sql);
					$exec = $q->execute();
					if(!$exec) {
						$errorInfo = $this->pdo->errorInfo();						
						throw new \Exception('Delete: '.$errorInfo[2].' . Query : '.$sql);
					}
					// Send the target ifd to Myddleware
					$result[$idDoc] = array(
											'id' => $data['target_id'],
											'error' => ($q->rowCount() ? false : 'There is no error but the record was already deleted.')
										);									
				}
				catch (\Exception $e) {
					$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
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
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$result[$idDoc] = array(
					'id' => '-1',
					'error' => $error
			);
		}
		return $result;
	}
	
	// Function to escape characters 
	protected function escape($value) {
		return $value;
	}
	
	// Get the strings which can identify what field is an id in the table
	protected function getIdFields($module,$type,$fields) {
		// default is id
		return array('id');
	}
	
	// Function to check, modify or validate the query
	protected function queryValidation($param, $functionName, $requestSQL) {
		return $requestSQL;
	}
	
	// Get the header of the select query in the read last function
	protected function get_query_select_header($param, $method) {
		return "SELECT ";
	}

	public function getFieldsParamUpd($type, $module) {	
		try {
			$fieldsSource = $this->get_module_fields($module, $type, false);
			// List only real database field so we remove the Myddleware_element_id field
			unset($fieldsSource['Myddleware_element_id']);
			if(!empty($fieldsSource)) {
				if ($type == 'source'){
					// Add param to store the fieldname corresponding to the record id
					$idParam = array(
								'id' => 'fieldId',
								'name' => 'fieldId',
								'type' => 'option',
								'label' => 'Primary key in your source table',
								'required'	=> true
							);
					// Add param to store the fieldname corresponding to the record reference date		
					$dateParam = array(
								'id' => 'fieldDateRef',
								'name' => 'fieldDateRef',
								'type' => 'option',
								'label' => 'Field Date Reference',
								'required'	=> true
							);
					// Add all fieds to the deletion list fields to get the one which carries the deletion flag
					$deletionParam = array(
								'id' => 'deletionField',
								'name' => 'deletionField',
								'type' => 'option',
								'label' => 'Field with deletion flag',
								'required'	=> false,
								'option' => array('' => '') // Add empty value
							);
					// Add all fieds to the list
					foreach ($fieldsSource as $key => $value) {
						$idParam['option'][$key] = $value['label'];
						$dateParam['option'][$key] = $value['label'];
						$deletionParam['option'][$key] = $value['label'];
					}
					$params[] = $idParam;
					$params[] = $dateParam;
					$params[] = $deletionParam;
				} else {
					// Add param to store the fieldname corresponding to the record id
					$idParam = array(
								'id' => 'targetFieldId',
								'name' => 'targetFieldId',
								'type' => 'option',
								'label' => 'Primary key in your target table',
								'required'	=> true
							);
					// Add all fieds to the list
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