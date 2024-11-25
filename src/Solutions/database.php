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

namespace App\Solutions;

use App\Entity\Rule;
use Exception;
use PDO;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class databasecore extends solution
{
    protected $driver;
    protected PDO $pdo;
    protected string $charset = 'utf8';
    protected bool $allowSeveralUpdate = false;

    protected string $stringSeparatorOpen = '`';
    protected string $stringSeparatorClose = '`';

    public function login($paramConnexion)
    {
echo 'standard mysql'.chr(10);
        parent::login($paramConnexion);
        try {
            try {
                $this->pdo = $this->generatePdo();
                $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $this->connexion_valide = true;
            } catch (\PDOException $e) {
                $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error($error);

                return ['error' => $error];
            }
        } catch (Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'login',
                'type' => TextType::class,
                'label' => 'solution.fields.login',
            ],
            [
                'name' => 'password',
                'type' => PasswordType::class,
                'label' => 'solution.fields.password',
            ],
            [
                'name' => 'host',
                'type' => TextType::class,
                'label' => 'solution.fields.host',
            ],
            [
                'name' => 'database_name',
                'type' => TextType::class,
                'label' => 'solution.fields.dbname',
            ],
            [
                'name' => 'port',
                'type' => TextType::class,
                'label' => 'solution.fields.dbport',
            ],
        ];
    }

    // Get all tables from the database
    public function get_modules($type = 'source')
    {
        try {
            $modules = [];

            // Send the query to the database
            $q = $this->pdo->prepare($this->get_query_show_tables($type));
            $exec = $q->execute();
            // Error management
            if (!$exec) {
                $errorInfo = $this->pdo->errorInfo();
                throw new Exception('Show Tables: '.$errorInfo[2]);
            }
            // Get every table and add them to the module list
            $fetchAll = $q->fetchAll();
            foreach ($fetchAll as $table) {
                if (isset($table[0])) {
                    $modules[$table[0]] = $table[0];
                }
            }

            return $modules;
        } catch (Exception $e) {
            return 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }
    }

    // Get all fields from the table selected
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            // parent::get_module_fields($module, $type);
            // Get all fields of the table in input
            $q = $this->pdo->prepare($this->get_query_describe_table($module));
            $exec = $q->execute();
            if (!$exec) {
                $errorInfo = $this->pdo->errorInfo();
                throw new Exception('CheckTable: (Describe) '.$errorInfo[2]);
            }
            // Format the fields
            $fields = $q->fetchAll();
            // Get field ID
            $idFields = $this->getIdFields($module, $type, $fields);

            foreach ($fields as $field) {
                // Convert field to be compatible with Myddleware. For example, error happens when there is space in the field name
                $field[$this->fieldName] = rawurlencode($field[$this->fieldName]);

                $this->moduleFields[$field[$this->fieldName]] = [
                    'label' => $field[$this->fieldLabel],
                    'type' => $field[$this->fieldType],
                    'type_bdd' => 'varchar(255)',
                    'required' => false,
                    'relate' => false,
                ];
                if (
                        'ID' == strtoupper(substr($field[$this->fieldName], 0, 2))
                    or 'ID' == strtoupper(substr($field[$this->fieldName], -2))
                ) {
                    $this->moduleFields[$field[$this->fieldName]] = [
                        'label' => $field[$this->fieldLabel],
                        'type' => $field[$this->fieldType],
                        'type_bdd' => 'varchar(255)',
                        'required' => false,
                        'required_relationship' => 0,
                        'relate' => true,
                    ];
                }
                // If the field contains the id indicator, we add it to the moduleFields list
                if (!empty($idFields)) {
                    foreach ($idFields as $idField) {
                        if (false !== strpos($field[$this->fieldName], $idField)) {
                            $this->moduleFields[$field[$this->fieldName]] = [
                                'label' => $field[$this->fieldLabel],
                                'type' => $field[$this->fieldType],
                                'type_bdd' => 'varchar(255)',
                                'required' => false,
                                'required_relationship' => 0,
                                'relate' => true,
                            ];
                        }
                    }
                }
            }

            // Add field current ID in the relationships
            if ('target' == $type) {
                $this->moduleFields['Myddleware_element_id'] = [
                    'label' => 'ID '.$module,
                    'type' => 'varchar(255)',
                    'type_bdd' => 'varchar(255)',
                    'required' => false,
                    'required_relationship' => 0,
                    'relate' => true,
                ];
            }
            // Add relationship fields coming from other rules
            $this->get_module_fields_relate($module, $param);

            return $this->moduleFields;
        } catch (Exception $e) {
            $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return [];
        }
    }

    // Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
    public function readData($param)
    {
        $result = [];
        // Decode field name (converted in method get_module_fields)
        $param['fields'] = array_map('rawurldecode', $param['fields']);
        try {
            // On contrôle la date de référence, si elle est vide on met 0 (cas fréquent si l'utilisateur oublie de la remplir)
            if (empty($param['date_ref'])) {
                $param['date_ref'] = 0;
            }
			$result['date_ref'] = $param['date_ref'];
			
            if (empty($param['limit'])) {
                $param['limit'] = 100;
            }

            // Add the deletion field into the list field to be read if deletion is enabled on the rule
            if (
                    !empty($param['ruleParams']['deletion'])
                and !empty($param['ruleParams']['deletionField'])
                and 'compareTable' != $param['ruleParams']['deletionField']	// Not a physical field, only used to compare table and Myddleware
                and 'history' != $param['call_type'] 	// Deletion flag is requireed only for read action, this field belongs to the source not the target application
            ) {
                $param['fields'][] = $param['ruleParams']['deletionField'];
            }

            // Check and add requiered fields
            // fieldId and fieldDateRef are required for a read action from a rule execution
            if ('read' == $param['call_type']) {
                if (!isset($param['ruleParams']['fieldId'])) {
                    throw new Exception('FieldId has to be specified for the read.');
                }
                if (!isset($param['ruleParams']['fieldDateRef'])) {
                    throw new Exception('"fieldDateRef" has to be specified for the read.');
                }
                $this->required_fields = ['default' => [$param['ruleParams']['fieldId'], $param['ruleParams']['fieldDateRef']]];
            }
            // fieldId and fieldDateRef are required for a read action from a rule execution
            if ('history' == $param['call_type']) {
                if (!isset($param['ruleParams']['targetFieldId'])) {
                    throw new Exception('targetFieldId has to be specified for read the data in the target table.');
                }
                $this->required_fields = ['default' => [$param['ruleParams']['targetFieldId']]];
            }

            if (!isset($param['fields'])) {
                $param['fields'] = [];
            }
            $param['fields'] = array_unique($param['fields']);
            $param['fields'] = $this->addRequiredField($param['fields'], $param['module'], $param['ruleParams']['mode']);
            $param['fields'] = array_values($param['fields']);
            $param['fields'] = $this->cleanMyddlewareElementId($param['fields']);

            // Query building
            $query['select'] = $this->get_query_select_header($param, 'read');
            // Build field list
            foreach ($param['fields'] as $field) {
                // myddleware_generated isn't a real field in the database
                if ('myddleware_generated' != $field) {
                    $query['select'] .= $this->stringSeparatorOpen.$field.$this->stringSeparatorClose.', ';
                }
            }
            // Remove the last coma
            $query['select'] = rtrim($query['select'], ' ');
            $query['select'] = rtrim($query['select'], ',').' ';
            $query['from'] = 'FROM '.$this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose;

            // if a specific query is requested we don't use date_ref
            $query['where'] = '';
            if (!empty($param['query'])) {
                $nbFilter = count($param['query']);
                $query['where'] .= ' WHERE ';
                foreach ($param['query'] as $queryKey => $queryValue) {
                    // Manage query with id, to be replaced by the ref Id fieldname
                    if ('id' == $queryKey) {
                        if (
                                !empty($param['ruleParams']['fieldId'])
                            and 'myddleware_generated' == $param['ruleParams']['fieldId']
                        ) {
                            throw new Exception('Not possible to read a specific record when myddleware_generated is selected as the Primary key in your source table');
                        }
                        // The query key is different if the functyion is call from a read data (database is source) or a read history (database is target)
                        if ('history' == $param['call_type']) {
                            $queryKey = $param['ruleParams']['targetFieldId'];
                        } elseif ('read' == $param['call_type']) {
                            $queryKey = $param['ruleParams']['fieldId'];
                        }
                    }
                    $query['where'] .= $this->stringSeparatorOpen.$queryKey.$this->stringSeparatorClose." = '".$this->escape($queryValue)."' ";
                    --$nbFilter;
                    if ($nbFilter > 0) {
                        $query['where'] .= ' AND ';
                    }
                }
            } elseif (!empty($param['ruleParams']['fieldDateRef'])) { // fieldDateRef can be empty for a simulation when the rule is created
                $query['where'] = ' WHERE '.$this->stringSeparatorOpen.$param['ruleParams']['fieldDateRef'].$this->stringSeparatorClose." > '".$param['date_ref']."'";
            }

            // Order by required only for a read action (no need for a simulation and history because we have only 1 result)
            if ('read' == $param['call_type']) {
                $query['order'] = ' ORDER BY '.$this->stringSeparatorOpen.$param['ruleParams']['fieldDateRef'].$this->stringSeparatorClose.' ASC'; // Tri par date utilisateur
            }
            $query['limit'] = $this->get_query_select_limit_offset($param, 'read'); // Add query limit

            // Build query
            $requestSQL = $this->buildQuery($param, $query);
            // Query validation
            $requestSQL = $this->queryValidation($param, 'read', $requestSQL, '');

            // Appel de la requête
            $q = $this->pdo->prepare($requestSQL);
            $exec = $q->execute();

            if (!$exec) {
                $errorInfo = $this->pdo->errorInfo();
                throw new Exception('Read: '.$errorInfo[2].' . Query : '.$requestSQL);
            }
            $fetchAll = $q->fetchAll(\PDO::FETCH_ASSOC);

            $row = [];
            if (!empty($fetchAll)) {
                $result['count'] = count($fetchAll);
                foreach ($fetchAll as $elem) {
                    $row = [];
                    $row['id'] = ''; // init in case of simulation (when a rule is created)
                    // Generate an id in case myddleware_generated is selected in the rule
                    if (
                            !empty($param['ruleParams']['fieldId'])
                        and 'myddleware_generated' == $param['ruleParams']['fieldId']
                    ) {
                        $row['id'] = $this->generateId($param, $elem);
                    }
                    foreach ($elem as $key => $value) {
                        // date_modified and date_ref are required only for a read action
                        // Id is fieldId for a read action
                        if ('read' == $param['call_type']) {
                            if ($key === $param['ruleParams']['fieldId']) { // key can't be equal to 'myddleware_generated' (no in select part of the query)
                                $row['id'] = $value;
                            }
                            if ($key === $param['ruleParams']['fieldDateRef']) {
                                // If the reference is a valid date, we save it
                                if ((bool) strtotime($value)) {
                                    $row['date_modified'] = $value;
									$result['date_ref'] = $row['date_modified'];
								// If the ref field is a numeric (increment), we transform it to a date (timestamp) to be able to save the corresponding date to the document
                                } elseif (is_numeric($value)) {
									$row['date_modified'] = date('Y-m-d H:i:s', $value);
									$result['date_ref'] = $value;
								// In all other cases, we set the current date
								} else {
                                    $row['date_modified'] = date('Y-m-d H:i:s');
									$result['date_ref'] = $row['date_modified'];
                                }
                            }
                        } elseif ('history' == $param['call_type']) { // Id is fieldId for a history action
                            if ($key === $param['ruleParams']['targetFieldId']) {
                                $row['id'] = $value;
                            }
                        }
                        if (in_array($key, $param['fields'])) {
                            // Encode the field to match with the fields retruned by method get_module_fields
                            $row[rawurlencode($key)] = $value;
                        }
                        // Manage deletion by adding the flag Myddleware_deletion to the record (only for read action)
                        if (
                                'read' == $param['call_type']
                            and !empty($param['ruleParams']['deletion'])
                            and $param['ruleParams']['deletionField'] === $key
                            and !empty($value)
                        ) {
                            $row['myddleware_deletion'] = true;
                        }
                    }
                    $result['values'][$row['id']] = $row;
                }
            }
            // Search for delete data
            $result = $this->searchDeletionByComparison($param, $result);
        } catch (Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }
        return $result;
    }

    /**
     * @throws Exception
     */
    protected function create($param, $record, $idDoc = null)
    {
        // Add this array at the start of the function
        $ignoreQuotesOnQuery = ['bigint', 'numeric', 'bit', 'smallint', 'decimal', 'smallmoney', 'int', 'tinyint', 'money', 'float', 'real'];

        // if we have a class value $ignoreQuotesOnQuery that is inherited from the solution class and the content of the array is different, then we use the one from the solution class
        if (!empty($this->ignoreQuotesOnQuery) && $this->ignoreQuotesOnQuery != $ignoreQuotesOnQuery) {
            $ignoreQuotesOnQuery = $this->ignoreQuotesOnQuery;
        }
        
        // Get the target reference field
        if (!isset($param['ruleParams']['targetFieldId'])) {
            throw new Exception('targetFieldId has to be specified for the data creation.');
        }

        // Query init
        $sql = 'INSERT INTO '.$this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose.' (';
        $values = '(';
        // We build the query with every fields
        foreach ($record as $key => $value) {
            if ('target_id' == $key) {
                continue;
            // If the target reference field is in data sent, we save it to update the document
            } elseif ($key == $param['ruleParams']['targetFieldId']) {
                $idTarget = $value;
            }
            // Decode field to be compatible with the database fields (has been encoded for Myddleware purpose in method get_module_fields)
            $sql .= $this->stringSeparatorOpen.rawurldecode($key).$this->stringSeparatorClose.',';

            // Check if the field type is in the $ignoreQuotesOnQuery array
            if (in_array(gettype($value), $ignoreQuotesOnQuery)) {
                $values .= ('null' == $value ? 'null,' : $this->escape($value).',');
            } else {
                $values .= ('null' == $value ? 'null,' : "'".$this->escape($value)."',");
            }
        }

        // Remove the last coma
        $sql = substr($sql, 0, -1); // INSERT INTO table_name (column1,column2,column3,...)
        $values = substr($values, 0, -1);
        $values .= ')'; // VALUES (value1,value2,value3,...)
        $sql .= ') VALUES '.$values; // INSERT INTO table_name (column1,column2,column3,...) VALUES (value1,value2,value3,...)
        // Query validation
        $sql = $this->queryValidation($param, 'create', $sql, $record);

        $q = $this->pdo->prepare($sql);
        $exec = $q->execute();
        if (!$exec) {
            $errorInfo = $this->pdo->errorInfo();
            throw new Exception('Create: '.$errorInfo[2].' . Query : '.$sql);
        }

        // Check if the row has been created
        if ($q->rowCount() === 0) {
            throw new Exception('No row was created for the id : ' . $idTarget);
        }

        // If the target reference field isn't in data sent
        if (!isset($idTarget)) {
            // If the target reference field is a primary key auto increment, we retrive the value here
            $idTarget = $this->pdo->lastInsertId();
        }

        return $idTarget;
        
    }

    /**
     * @throws Exception
     */
    protected function update($param, $record, $idDoc = null)
    {
            // Add this array at the start of the function
        $ignoreQuotesOnQuery = ['bigint', 'numeric', 'bit', 'smallint', 'decimal', 'smallmoney', 'int', 'tinyint', 'money', 'float', 'real'];

        // if we have a class value $ignoreQuotesOnQuery that is inherited from the solution class and the content of the array is different, then we use the one from the solution class
        if (!empty($this->ignoreQuotesOnQuery) && $this->ignoreQuotesOnQuery != $ignoreQuotesOnQuery) {
            $ignoreQuotesOnQuery = $this->ignoreQuotesOnQuery;
        }
        
        // Query init
        $sql = 'UPDATE '.$this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose.' SET ';
        // We build the query with every fields
        // Boucle sur chaque champ du document
        foreach ($record as $key => $value) {
            // Target_id is a Myddleware field (not send to the database)
            if ('target_id' == $key) {
                continue;
            }
            // Decode field to be compatible with the database fields (has been encoded for Myddleware purpose in method get_module_fields)

            // Check if the field type is in the $ignoreQuotesOnQuery array
            if (in_array(gettype($value), $ignoreQuotesOnQuery)) {
                $sql .= $this->stringSeparatorOpen.rawurldecode($key).$this->stringSeparatorClose.'='.('null' == $value ? 'null,' : $this->escape($value).',');
            } else {
                $sql .= $this->stringSeparatorOpen.rawurldecode($key).$this->stringSeparatorClose.'='.('null' == $value ? 'null,' : "'".$this->escape($value)."',");
            }
        }

        // Remove the last coma
        $sql = substr($sql, 0, -1);
        $sql .= ' WHERE '.$this->stringSeparatorOpen.$param['ruleParams']['targetFieldId'].$this->stringSeparatorClose."='".$record['target_id']."'";
        // Query validation
        $sql = $this->queryValidation($param, 'update', $sql, $record);
        // Execute the query
        $q = $this->pdo->prepare($sql);
        $exec = $q->execute();
        // Query error
        if (!$exec) {
            $errorInfo = $this->pdo->errorInfo();
            throw new Exception('Update: '.$errorInfo[2].' . Query : '.$sql);
        }
        // No modification
        if ($q->rowCount() === 0) {
            throw new Exception('No row was updated for the id : ' . $record['target_id']);
        }
        // Several modifications
        if (
                !$this->allowSeveralUpdate
            and $q->rowCount() > 1
        ) {
            throw new Exception('Update query has modified several records. It should never happens. Please check that your id in your database is unique. Query : '.$sql);
        }

        return $record['target_id'];
    }

    /**
     * @throws Exception
     */
    public function delete($param, $record)
    {
        // Check control before delete
        $record = $this->checkDataBeforeDelete($param, $record);
        if (empty($record['target_id'])) {
            throw new Exception('No target id found. Failed to delete the record.');
        }
        // Query init
        $sql = 'DELETE FROM '.$this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose.' ';
        $sql .= ' WHERE '.$this->stringSeparatorOpen.$param['ruleParams']['targetFieldId'].$this->stringSeparatorClose."='".$record['target_id']."'";
        // Query validation
        $sql = $this->queryValidation($param, 'delete', $sql, $record);

        // Execute the query
        $q = $this->pdo->prepare($sql);
        $exec = $q->execute();
        if (!$exec) {
            $errorInfo = $this->pdo->errorInfo();
            throw new Exception('Delete: '.$errorInfo[2].' . Query : '.$sql);
        }

        // No deletion
        if ($q->rowCount() === 0) {
            throw new Exception('No row was deleted for the id : ' . $record['target_id']);
        }

        return $record['target_id'];
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    protected function searchDeletionByComparison($param, $result)
    {
        // If check deletion by comparaison is selected on the rule param
        if (
                !empty($param['ruleParams']['deletion'])
            and !empty($param['ruleParams']['deletionField'])
            and 'compareTable' == $param['ruleParams']['deletionField']
        ) {
            // Search all data in the source application (can take long time)
            $requestSQL = 'SELECT '.$this->stringSeparatorOpen.$param['ruleParams']['fieldId'].$this->stringSeparatorClose.' FROM '.$this->stringSeparatorOpen.$param['module'].$this->stringSeparatorClose;
            $q = $this->pdo->prepare($requestSQL);
            $exec = $q->execute();
            if (!$exec) {
                $errorInfo = $this->pdo->errorInfo();
                throw new Exception('Read: '.$errorInfo[2].' . Query : '.$requestSQL);
            }
            $fetchAll = $q->fetchAll(\PDO::FETCH_ASSOC);
            // If result is empty, we stop the process because it would remove all data
            if (!empty($fetchAll)) {
                // Format result
                foreach ($fetchAll as $sourceTableRecord) {
                    $sourceTableRecords[$sourceTableRecord[$param['ruleParams']['fieldId']]] = $sourceTableRecord[$param['ruleParams']['fieldId']];
                }
                // Get all records already manage by Myddleware for this rule
                // Prepare query to get the fieldId from the orther rules with the same connectors
                $connection = $this->getConn();
                $query = "	SELECT source_id, GROUP_CONCAT(type) type
							FROM document 
							WHERE 
									global_status != 'Cancel'
								AND rule_id = :id_rule
							GROUP BY source_id";
                $stmt = $connection->prepare($query);
                $stmt->bindValue(':id_rule', $param['rule']['id']);
                $resultQuery = $stmt->executeQuery();
                $documents = $resultQuery->fetchAllAssociative();

                // Test all document found in Myddleware
                foreach ($documents as $document) {
                    // if Myddleware record doesn't exist anymore in the source table
                    // and if no deletion document has alreday been generated for this record
                    // we generate a deletetion document
                    if (
                            !isset($sourceTableRecords[$document['source_id']])
                        and false === strpos($document['type'], 'D')
                    ) {
                        $row = [];
                        // Init all fields of the document
                        foreach ($param['fields'] as $field) {
                            $row[$field] = '';
                        }
                        // Fill Myddleware fields
                        $row['myddleware_deletion'] = true;
                        $row['id'] = $document['source_id'];
                        $row['date_modified'] = date('Y-m-d H:i:s');
                        $result['values'][$document['source_id']] = $row;
                        ++$result['count'];
                    }
                }
            }
        }

        return $result;
    }

    // Function to escape characters
    protected function escape($value)
    {
        return $value;
    }

    // Get the strings which can identify what field is an id in the table
    protected function getIdFields($module, $type, $fields): array
    {
        // default is id
        return ['id'];
    }

    // Function to check, modify or validate the query
    protected function queryValidation($param, $functionName, $requestSQL, $record)
    {
        return $requestSQL;
    }

    // Get the header of the select query in the read last function
    protected function get_query_select_header($param, $method): string
    {
        return 'SELECT ';
    }

    // Function to build the SELECT query
    protected function buildQuery($param, $query): string
    {
        return $query['select'].$query['from'].$query['where'].(!empty($query['order']) ? $query['order'] : '').$query['limit'];
    }

    /**
     * Get the fieldId from the other rules to add them into the source relationship list field.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function get_module_fields_relate($module, $param)
    {
        if (!empty($param)) {
            // Get the rule list with the same connectors (both directions) to get the relate ones
            $ruleListRelation = $this->entityManager->getRepository(Rule::class)->createQueryBuilder('r')
                            ->select('r.id')
                            ->where('(
												r.connectorSource= ?1 
											AND r.connectorTarget= ?2
											AND r.name != ?3
											AND r.deleted = 0
										)
									OR (
												r.connectorTarget= ?1
											AND r.connectorSource= ?2
											AND r.name != ?3
											AND r.deleted = 0
									)')
                            ->setParameter(1, (int) $param['connectorSourceId'])
                            ->setParameter(2, (int) $param['connectorTargetId'])
                            ->setParameter(3, $param['ruleName'])
                            ->getQuery()
                            ->getResult();
            if (!empty($ruleListRelation)) {
                // Prepare query to get the fieldId from the orther rules with the same connectors
                $sql = "SELECT value FROM ruleparam WHERE ruleparam.name = 'fieldId' AND ruleparam.rule_id  in (";
                foreach ($ruleListRelation as $ruleRelation) {
                    $sql .= "'$ruleRelation[id]',";
                }
                // Remove the last coma
                $sql = substr($sql, 0, -1);
                $sql .= ')';
                $stmt = $this->connection->prepare($sql);
                $result = $stmt->executeQuery();
                $fields = $result->fetchAllAssociative();
                if (!empty($fields)) {
                    // Add relate fields to display them in the rule edit view (relationship tab, source list fields)
                    foreach ($fields as $field) {
                        // The field has to exist in the current module
                        if (!empty($this->moduleFields[$field['value']])) {
                            $this->moduleFields[$field['value']] = [
                                'label' => $field['value'],
                                'type' => 'varchar(255)',
                                'type_bdd' => 'varchar(255)',
                                'required' => false,
                                'required_relationship' => 0,
                                'relate' => true,
                            ];
                        }
                    }
                }
            }
        }
    }

    public function getFieldsParamUpd($type, $module): array
    {
        try {
			// Clear moduleFields in case we connect 2 database (source and target)
			$this->moduleFields = array();
            $fieldsSource = $this->get_module_fields($module, $type, false);
            // List only real database field so we remove the Myddleware_element_id field
            unset($fieldsSource['Myddleware_element_id']);
            if (!empty($fieldsSource)) {
                if ('source' == $type) {
                    // Add param to store the fieldname corresponding to the record id
                    $idParam = [
                        'id' => 'fieldId',
                        'name' => 'fieldId',
                        'type' => 'option',
                        'label' => 'Primary key in your source table',
                        'required' => true,
                    ];
                    // Add param to store the fieldname corresponding to the record reference date
                    $dateParam = [
                        'id' => 'fieldDateRef',
                        'name' => 'fieldDateRef',
                        'type' => 'option',
                        'label' => 'Field Date Reference',
                        'required' => true,
                    ];
                    // Add all fieds to the deletion list fields to get the one which carries the deletion flag
                    $deletionParam = [
                        'id' => 'deletionField',
                        'name' => 'deletionField',
                        'type' => 'option',
                        'label' => 'Field with deletion flag',
                        'required' => false,
                        'option' => ['' => ''], // Add empty value
                    ];
                    // Add all fieds to the list
                    foreach ($fieldsSource as $key => $value) {
                        $idParam['option'][$key] = $value['label'];
                        $dateParam['option'][$key] = $value['label'];
                        $deletionParam['option'][$key] = $value['label'];
                    }
                    // Add the possibility to generate an unique id
                    $idParam['option']['myddleware_generated'] = 'Generated by Myddleware';
                    // Add a parameter for deletion to automatically check if a record has been deleted from the database
                    $deletionParam['option']['compareTable'] = 'Compare source table with Myddleware';

                    $params[] = $idParam;
                    $params[] = $dateParam;
                    $params[] = $deletionParam;
                } else {
                    // Add param to store the fieldname corresponding to the record id
                    $idParam = [
                        'id' => 'targetFieldId',
                        'name' => 'targetFieldId',
                        'type' => 'option',
                        'label' => 'Primary key in your target table',
                        'required' => true,
                    ];
                    // Add all fields to the list
                    foreach ($fieldsSource as $key => $value) {
                        $idParam['option'][$key] = $value['label'];
                    }
                    $params[] = $idParam;
                }

                return $params;
            }

            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    // Generate ID for the document
    protected function generateId($param, $record): string
    {
        return uniqid('', true);
    }
}
class database extends databasecore
{
}
