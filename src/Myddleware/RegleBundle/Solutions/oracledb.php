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

class oracledbcore extends database {
    
    protected $driver = 'oci';
    
    protected $fieldName = 'Name';
    protected $fieldLabel = 'Name';
    protected $fieldType = 'Type';

    protected $stringSeparatorOpen = '';
    protected $stringSeparatorClose = '';

    protected function generatePdo() {
        return new \PDO($this->driver.':dbname=//' . $this->paramConnexion['host'] .':' . $this->paramConnexion['port'] . '/' . $this->paramConnexion['database_name'] . ';charset=' . $this->charset, $this->paramConnexion['login'], $this->paramConnexion['password']);
    }
    
    // Generate query
    protected function get_query_show_tables()
    {
        return "SELECT * FROM (SELECT CONCAT(CONCAT(owner, '.'), table_name) AS \"TableName\", owner FROM all_tables UNION SELECT CONCAT(CONCAT(owner, '.'), view_name) AS \"ViewName\", owner FROM all_views) WHERE owner NOT LIKE '%SYS'";
        // return "SELECT * FROM (SELECT table_name, owner FROM all_tables UNION SELECT view_name, owner FROM all_views) WHERE owner NOT LIKE '%SYS'";
    }

    // Query to get all the flieds of the table
    protected function get_query_describe_table($table)
    {
        $table = substr($table, (strpos($table, '.') ?: -1) + 1);
        return "SELECT column_name AS \"Name\", nullable AS \"Null?\", concat(concat(concat(data_type,'('),data_length),')') AS \"Type\" FROM all_tab_columns WHERE table_name = '$table'";
    }

    // Permet de récupérer le dernier enregistrement de la solution (utilisé pour tester le flux)
    public function read_last($param)
    {
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
            $requestSQL .= (empty($where) ? " WHERE" : " AND") . $this->get_query_select_limit_offset($param, 'read_last'); // Add query limit
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
    public function read($param)
    {
        $result = array();
        try {
            // On contrôle la date de référence, si elle est vide on met 0 (cas fréquent si l'utilisateur oublie de la remplir)
            if(empty($param['date_ref'])) {
                $param['date_ref'] = 0;
            }
            if (empty($param['limit'])) {
                $param['limit'] = 100;
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
                $requestSQL .= " WHERE CAST(" . $this->stringSeparatorOpen.$param['ruleParams']['fieldDateRef'].$this->stringSeparatorClose . " AS TIMESTAMP WITH LOCAL TIME ZONE) > CAST(TO_TIMESTAMP('$param[date_ref]', 'YYYY-MM-DD HH24:MI:SS')  AS TIMESTAMP WITH LOCAL TIME ZONE)";
            }

            $requestSQL .= " AND" . $this->get_query_select_limit_offset($param, 'read'); // Add query limit
            $requestSQL .= " ORDER BY ".$this->stringSeparatorOpen.$param['ruleParams']['fieldDateRef'].$this->stringSeparatorClose. " ASC"; // Tri par date utilisateur
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
                            $strtime = strtotime($value);
                            // If the reference isn't a valid date (it could be an ID in case there is no date in the table) we set the current date
                            if ((bool)$strtime) {
                                $row['date_modified'] = date("Y-m-d H:i:s", $strtime);
                            } else {
                                $row['date_modified'] = date('Y-m-d H:i:s');
                            }
                            $result['date_ref'] = date("Y-m-d H:i:s", $strtime);
                        }
                        if(in_array($key, $param['fields'])) {
                            $row[$key] = $value;
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

    // Get the limit operator of the select query in the read last function
    protected function get_query_select_limit_offset($param, $method)
    {
        if (empty($param['offset'])) {
            $param['offset'] = 0;
        }
        /*
        $minVersion = 12.1;
        $bool = ((float)substr($this->pdo->getAttribute(PDO::ATTR_CLIENT_VERSION), 0, strlen((string)$minVersion))) >= $minVersion;
        $bool &= (float)substr($this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION), 0, strlen((string)$minVersion)) >= $minVersion;
        if ($bool) {
            return " OFFSET $param[offset] ROWS FETCH NEXT $param[limit] ROWS ONLY;";
        } else {
            return " ROWNUM BETWEEN $param[offset] AND " . ($param["limit"] + $param["offset"]);
        }
        */

        return " ROWNUM BETWEEN $param[offset] AND " . ($param["limit"] + $param["offset"]);
    }
    
}// class oracledbcore

/* * * * * * * *  * * * * * *  * * * * * * 
    si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/oracledb.php';
if(file_exists($file)){
    require_once($file);
}
else {
    //Sinon on met la classe suivante
    class oracledb extends oracledbcore {
        
    }
} 