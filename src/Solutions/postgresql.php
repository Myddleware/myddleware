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

class postgresql extends database
{
    protected $driver = 'pgsql';

    // Enable to delete data
    protected bool $sendDeletion = true;
    protected bool $readDeletion = true;
    protected string $stringSeparatorOpen = '';
    protected string $stringSeparatorClose = '';

    protected function generatePdo(): \PDO
    {
        return new \PDO($this->driver.':host='.$this->paramConnexion['host'].';port='.$this->paramConnexion['port'].';dbname='.$this->paramConnexion['database_name'], $this->paramConnexion['login'], $this->paramConnexion['password']);
    }

    // Generate query
    protected function get_query_show_tables($type): string
    {
		// Read tables and views
        return "SELECT schemaname, tablename
				FROM pg_catalog.pg_tables
				WHERE 	schemaname != 'pg_catalog'
					AND schemaname != 'information_schema'
				".
			($type == 'source' ? 
				"UNION
					SELECT schemaname, viewname tablename
					FROM pg_catalog.pg_views
					WHERE 	schemaname != 'pg_catalog'
						AND schemaname != 'information_schema'
					" : 
			"");
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
                throw new \Exception('Show Tables: '.$errorInfo[2]);
            }
            // Get every table and add them to the module list
            $fetchAll = $q->fetchAll();
            foreach ($fetchAll as $table) {
                if (isset($table['tablename'])) {
                    $modules[$table['schemaname'].'.'.$table['tablename']] = $table['schemaname'].' '.$table['tablename'];
                }
            }

            return $modules;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Get all fields from the table selected
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        try {
            // Get all fields of the table in input
            $q = $this->pdo->prepare($this->get_query_describe_table($module));
            $exec = $q->execute();

            if (!$exec) {
                $errorInfo = $this->pdo->errorInfo();
                throw new \Exception('CheckTable: (Describe) '.$errorInfo[2]);
            }
            // Format the fields
            $fields = $q->fetchAll();

            // Get field ID
            $idFields = $this->getIdFields($module, $type, $fields);
            foreach ($fields as $field) {
                // Convert field to be compatible with Myddleware. For example, error happens when there is space in the field name
                $field['column_name'] = rawurlencode($field['column_name']);

                $this->moduleFields[$field['column_name']] = [
                    'label' => $field['column_name'],
                    'type' => $field['data_type'],
                    'type_bdd' => 'varchar(255)',
                    'required' => false,
                    'relate' => false,
                ];
                if (
                        'ID' == strtoupper(substr($field['column_name'], 0, 2))
                    or 'ID' == strtoupper(substr($field['column_name'], -2))
                ) {
                    $this->moduleFields[$field['column_name']] = [
                        'label' => $field['column_name'],
                        'type' => $field['data_type'],
                        'type_bdd' => 'varchar(255)',
                        'required' => false,
                        'required_relationship' => 0,
                        'relate' => true,
                    ];
                }
                // If the field contains the id indicator, we add it to the moduleFields list
                if (!empty($idFields)) {
                    foreach ($idFields as $idField) {
                        if (false !== strpos($field['column_name'], $idField)) {
                            $this->moduleFields[$field['column_name']] = [
                                'label' => $field['column_name'],
                                'type' => $field['data_type'],
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
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Query to get all the flieds of the table
    protected function get_query_describe_table($table): string
    {
        // Get the schema and table namespace
        $tableParam = explode('.', $table);

        return "	SELECT column_name, data_type
					FROM information_schema.columns
					WHERE
							table_catalog = '".$this->paramConnexion['database_name']."'
						AND table_schema = '".$tableParam[0]."'
						AND table_name = '".$tableParam[1]."'";
    }

    protected function create($param, $record, $idDoc = null)
    {
        // Change separator for PostgreSQL
        $record = array_map('pg_escape_string', $record);

        return parent::create($param, $record, $idDoc);
    }

    protected function update($param, $record, $idDoc = null)
    {
        // Change separator for PostgreSQL
        $record = array_map('pg_escape_string', $record);

        return parent::update($param, $record, $idDoc);
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
