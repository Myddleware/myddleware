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

use App\Entity\Rule;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType; // SugarCRM Myddleware

class filecore extends solution
{
    protected $baseUrl;
    protected $messages = [];
    protected $duplicateDoc = [];
    protected $connection;
    protected $delimiter = ';';
    protected $enclosure = '"';
    protected $escape = '';
    protected $removeChar = [' ', '/', '\'', '.', '(', ')'];
    protected $readLimit = 1000;
    protected $lineNumber = 0;

    protected $required_fields = ['default' => ['id', 'date_modified']];
    protected $columnWidth = [];

    private $driver;
    private $host;
    private $port;
    private $dbname;
    private $login;
    private $password;

    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            if (!extension_loaded('ssh2')) {
                throw new \Exception('Please enable extension ssh2. Help here : http://php.net/manual/fr/ssh2.installation.php');
            }
            // Connect to the server
            $this->connection = ssh2_connect($this->paramConnexion['host'], $this->paramConnexion['port']);
            ssh2_auth_password($this->connection, $this->paramConnexion['login'], $this->paramConnexion['password']);

            // Check if the directory exist
            $stream = ssh2_exec($this->connection, 'cd '.$this->paramConnexion['directory'].';pwd');
            stream_set_blocking($stream, true);
            $output = stream_get_contents($stream);
            if (trim($this->paramConnexion['directory']) != trim($output)) {
                throw new \Exception('Failed to access to the directory'.$this->paramConnexion['directory'].'. Could you check if this directory exists and if the user has the right to read it. ');
            }

            // If all check are OK so connexion is valid
            $this->connexion_valide = true;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // login($paramConnexion)

    public function getFieldsLogin()
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
                'name' => 'port',
                'type' => TextType::class,
                'label' => 'solution.fields.ftpport',
            ],
            [
                'name' => 'directory',
                'type' => TextType::class,
                'label' => 'solution.fields.directory',
            ],
        ];
    }

    // Renvoie les modules passés en paramètre
    public function get_modules($type = 'source')
    {
        try {
            // Get the subfolders of the current directory
            $stream = ssh2_exec($this->connection, 'cd '.$this->paramConnexion['directory'].';ls -d */');
            stream_set_blocking($stream, true);
            $output = stream_get_contents($stream);
            // Transform the directory list in an array
            $directories = explode(chr(10), trim($output));
            $modules = [];
            // Add the current directory
            $modules['/'] = 'Root directory';
            // Add the sub directories if exist
            if (!empty($directories)) {
                foreach ($directories as $directory) {
                    $modules[$directory] = $directory;
                }
            }

            return $modules;
        } catch (\Exception $e) {
            $error = $e->getMessage();

            return $error;
        }
    }

    // Renvoie les champs du module passé en paramètre
    public function get_module_fields($module, $type = 'source', $param = null)
    {
        parent::get_module_fields($module, $type);
        try {
            if ('source' == $type) {
                // Get the file with the way of this file
                $file = $this->get_last_file($this->paramConnexion['directory'].'/'.$module, '1970-01-01 00:00:00');
                $fileName = trim($this->paramConnexion['directory'].'/'.$module.$file);
                // Open the file
                $sftp = ssh2_sftp($this->connection);
                $stream = fopen('ssh2.sftp://'.intval($sftp).$fileName, 'r');
                $headerString = trim(fgets($stream));
                // Close the file
                fclose($stream);

                // Get the column names in the file
                $header = $this->transformRow($headerString, ['module' => $module]);
                $i = 1;
                foreach ($header as $field) {
                    // In case the field name is empty
                    if (empty($field)) {
                        $field = 'Column_'.$i;
                    }
                    // Spaces aren't accepted in a field name
                    $this->moduleFields[str_replace($this->removeChar, '', $field)] = [
                        'label' => $field,
                        'type' => 'varchar(255)',
                        'type_bdd' => 'varchar(255)',
                        'required' => false,
                        'relate' => false,
                    ];

                    // If the field contains the id indicator, we add it to the moduleFields list
                    $idFields = $this->getIdFields($module, $type);
                    if (!empty($idFields)) {
                        foreach ($idFields as $idField) {
                            if (false !== strpos($field, $idField)) {
                                $this->moduleFields[str_replace($this->removeChar, '', $field)] = [
                                    'label' => $field,
                                    'type' => 'varchar(255)',
                                    'type_bdd' => 'varchar(255)',
                                    'required' => false,
                                    'required_relationship' => 0,
                                    'relate' => true,
                                ];
                            }
                        }
                    }
                    ++$i;
                }
            } else {
                $this->moduleFields = [];
            }
            // Add relationship fields coming from other rules
            $this->get_module_fields_relate($module, $param);

            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            echo 'Erreur : '.$error;

            return false;
        }
    }

    // get_module_fields($module)

    // Get the fieldId from the other rules to add them into the source relationship list field
    public function get_module_fields_relate($module, $param)
    {
        // Get the rule list with the same connectors (both directions) to get the relate ones
        $ruleListRelation = $this->getEntityManager->getRepository(Rule::class)->createQueryBuilder('r')
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
            $stmt = $this->conn->prepare($sql);
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

    // Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
    // Param contient :
    //	date_ref : la date de référence à partir de laquelle on récupère les enregistrements, format bdd AAAA-MM-JJ hh:mm:ss
    //	module : le module appelé
    //	fields : les champs demandés sous forme de tableau, exemple : array('name','date_entered')
    //	limit : la limite du nombre d'enregistrement récupéré (la limite par défaut étant 100)
    // Valeur de sortie est un tableau contenant :
    //		count : Le nombre d'enregistrement trouvé
    //		date_ref : la nouvelle date de référence
    //   	values : les enregsitrements du module demandé (l'id et la date de modification (libellés 'id' et 'date_modified') sont obligatoires), L'id est en clé du tableau de valeur pour chaque docuement
    // 			     exemple Array([454664654654] => array( ['name] => dernier,  [date_modified] => 2013-10-11 18:41:18))
    // 				 Values peut contenir le tableau ZmydMessage contenant un table de message array (type => 'E', 'message' => 'erreur lors....')

    // Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
    public function readData($param)
    {
        $count = 0;
        $offset = 0;
        $result = [];
        try {
            // Get the file with the way of this file. But we take the oldest file of the folder
            // If query is called then we don't have date_ref, we take the first file (in this case, we should have only one file in the directory because Myddleware search in only one file)
            $file = $this->get_last_file($this->paramConnexion['directory'].'/'.$param['module'], (!empty($param['query']) ? '1970-01-01 00:00:00' : $param['date_ref']));
            // If there is no file
            if (empty($file)) {
                return;
            }
            // If the file has already been read, we get the offset to read from this line
            if (!empty($param['ruleParams'][$file])) {
                $offset = $param['ruleParams'][$file];
            }

            $fileName = $this->paramConnexion['directory'].'/'.$param['module'].$file;

            // Open the file
            $sftp = ssh2_sftp($this->connection);
            $stream = fopen('ssh2.sftp://'.intval($sftp).$fileName, 'r');
            $header = $this->getFileHeader($stream, $param);

            $nbCountHeader = count($header);

            $allRuleField = $param['fields'];
            // Adding id fields "fieldId" and "fieldDateRef" of the array $param
            $allRuleField[] = $param['ruleParams']['fieldId'];

            // Get the date of modification of the file
            $new_date_ref = ssh2_exec($this->connection, 'cd '.$this->paramConnexion['directory'].'/'.$param['module'].';stat -c %y '.$file);
            stream_set_blocking($new_date_ref, true);
            $new_date_ref = stream_get_contents($new_date_ref);
            $new_date_ref = trim($new_date_ref);
            // Detelete microseconds 2016-10-21 12:38:23.219635731 +0200
            $new_date_ref = substr($new_date_ref, 0, 19).substr($new_date_ref, 29, 6);
            if (empty($new_date_ref)) {
                throw new \Exception('Failed to get the reference date from the modification date of the file. ');
            }

            // Create date with timezone
            $date = date_create_from_format('Y-m-d H:i:s O', $new_date_ref);

            // Add one second
            $second = new \DateInterval('PT1S'); /* one second */
            $date->add($second);
            $new_date_ref = $date->format('Y-m-d H:i:s');

            // we check if there are same fields in both array
            $intersectionFields = array_intersect($allRuleField, $header);
            if ('myddleware_generated' == $param['ruleParams']['fieldId']) {
                $intersectionFields[] = 'myddleware_generated';
            }
            if (
                (
                        !empty($difFields)
                    && count($difFields) > 1
                )
                || (
                        !empty($difFields)
                    && 1 == count($difFields)
                    && 'myddleware_generated' != current($difFields)
                )
            ) {
                throw new \Exception('File is not compatible. Missing fields : '.implode(';', $difFields));
            }
            //Control all lines of the file
            $values = [];
            $this->lineNumber = 2; // We count the header
            while (($buffer = fgets($stream)) !== false) {
                $idRow = '';
                // We don't read again line already read in a previous call
                if ($this->lineNumber < $offset) {
                    ++$this->lineNumber;
                    continue;
                }

                //If there are a line empty, we continue to read the file
                if (empty(trim($buffer))) {
                    ++$this->lineNumber;
                    continue;
                }

                $rowFile = $this->transformRow($buffer, $param);

                $checkRow = $this->checkRow($rowFile, $param);
                if (false == $checkRow) {
                    ++$this->lineNumber;
                    continue;
                }
                //If there are not the good number of columns, display an error
                $nbRowLine = count($rowFile);
                if ($nbRowLine != $nbCountHeader) {
                    throw new \Exception('File is rejected because there are '.$nbRowLine.' columns at the line '.$this->lineNumber.'. '.$nbCountHeader.' columns are expected.');
                }
                foreach ($allRuleField as $field) {
                    $column = array_search($field, $header);
                    // If the column isn't found we skip it
                    if (
                            false === $column
                        and 'myddleware_generated' != $field
                    ) {
                        $row[$field] = '';
                        continue;
                    }
                    if ($field == $param['ruleParams']['fieldId']) {
                        if ('myddleware_generated' == $field) {
                            $idRow = $this->generateId($param, $rowFile);
                        } else {
                            $idRow = $rowFile[$column];
                        }
                        $row['id'] = $idRow;
                    }
                    $row[$field] = $rowFile[$column];
                }
                $row['date_modified'] = $new_date_ref;
                $validateRow = $this->validateRow($row, $idRow, $count);
                if (false == $validateRow) {
                    ++$this->lineNumber;
                    continue;
                }
                ++$this->lineNumber; // Save the line number
                // In case of query not empty, we filter the output data
                if (!empty($param['query'])) {
                    $skip = false;
                    foreach ($param['query'] as $key => $value) {
                        if (
                                !isset($row[$key])
                            or $row[$key] != $value
                        ) {
                            $skip = true;
                            break;
                        }
                    }
                    if ($skip) {
                        continue;
                    }
                }
                ++$count; // Save the number of lines read

                $values[$idRow] = $this->addData($param, $idRow, $values, $row);
                // If we have reached the limit we stop to read
                if ($this->limitReached($param, $count)) {
                    break;
                }
            }
            // Generate result
            $result = $this->generateReadResult($param, $count, $values, $new_date_ref);

            // Add the parameter only when it is a standard call (not an query call)
            if (empty($param['query'])) {
                $result['ruleParams'] = [['name' => $file, 'value' => $this->lineNumber]];
            }
        } catch (\Exception $e) {
            $result['error'] = 'File '.(!empty($fileName) ? ' : '.$fileName : '').' : Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }
        // Close the file
        if (!empty($stream)) {
            fclose($stream);
        }

        return $result;
    }

    // Transform the result
    protected function generateReadResult($param, $count, $values, $new_date_ref)
    {
        return [
            'count' => $count,
            'date_ref' => ($count >= $this->readLimit ? (!empty($param['date_ref']) ? $param['date_ref'] : '') : $new_date_ref), // Update date_ref only if the file is read completely. Date_ref could be empty when we read for child document for example.
            'values' => $values,
            'notRecall' => true, // Stop the recall in the function Rule->readSource()
        ];
    }

    // Add data to the result
    protected function addData($param, $idRow, $values, $row)
    {
        return $row;
    }

    // Check if teh limit has been reached
    protected function limitReached($param, $count)
    {
        if ($count >= $this->readLimit) {
            return true;
        }

        return false;
    }

    // Convert the first line of the file to an array with all fields
    protected function getFileHeader($stream, $param)
    {
        $headerString = trim(fgets($stream));
        $fields = $this->transformRow($headerString, $param);
        $i = 1;
        foreach ($fields as $field) {
            if (empty($field)) {
                $header[] = 'Column_'.$i;
            } else {
                // Spaces aren't accepted in a field name
                $header[] = str_replace($this->removeChar, '', $field);
            }
            ++$i;
        }

        return $header;
    }

    // Permet de renvoyer l'id de la table en récupérant la table liée à la règle ou en la créant si elle n'existe pas
    public function getFieldsParamUpd($type, $module)
    {
        try {
            // $fieldsSource = array();
            if ('source' == $type) {
                $this->get_module_fields($module, $type);
                if (!empty($this->moduleFields)) {
                    $idParam = [
                        'id' => 'fieldId',
                        'name' => 'fieldId',
                        'type' => 'option',
                        'label' => 'Field ID',
                        'required' => true,
                    ];
                    foreach ($this->moduleFields as $key => $value) {
                        $idParam['option'][$key] = $value['label'];
                    }
                    $idParam['option']['myddleware_generated'] = 'Generated by Myddleware';
                    $params[] = $idParam;

                    return $params;
                }
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    // Generate ID for the document
    protected function generateId($param, $rowFile)
    {
        return uniqid('', true);
    }

    protected function checkRow($rowFile, $param)
    {
        return true;
    }

    // Transformm the buffer to and array of fields
    protected function transformRow($buffer, $param)
    {
        // If the module contains file with a fix column width (if attribute $columnWidth is set up for your module)
        // Then we manage row using the width of each column
        if (!empty($this->columnWidth[$param['module']])) {
            $start = 0;
            // Cut the row using the width of each column
            foreach ($this->columnWidth[$param['module']] as $columnWidth) {
                $result[] = mb_substr($buffer, $start, $columnWidth);
                $start += $columnWidth;
            }

            return $result;
            // Otherwise we manage row with separator
        }

        return str_getcsv($buffer, $this->getDelimiter($param), $this->getEnclosure($param), $this->getEscape($param));
    }

    // Get the delimiter
    protected function getDelimiter($param)
    {
        return $this->delimiter;
    }

    // Get the enclosure
    protected function getEnclosure($param)
    {
        return $this->enclosure;
    }

    // Get the escape
    protected function getEscape($param)
    {
        return $this->escape;
    }

    protected function validateRow($row, $idRow, $rowNumber)
    {
        // We do "++" because we don't take the "header" so the first line and we have a line to delete
        $rowNumber = $rowNumber + 2;
        // If there are not the id of the line, display an error
        if (empty($idRow)) {
            throw new \Exception('File is rejected because the id of the line '.$rowNumber.' is empty');
        }

        return true;
    }

    protected function get_last_file($directory, $date_ref)
    {
        $stream = ssh2_exec($this->connection, 'cd '.$directory.';find . -newermt "'.$date_ref.'" -type f | sort |  head -n 1');
        stream_set_blocking($stream, true);
        $file = stream_get_contents($stream);
        $file = ltrim($file, './'); // The filename can have ./ at the beginning

        return trim($file);
    }

    // Get the strings which can identify what field is an id in the table
    protected function getIdFields($module, $type)
    {
        // default is id
        return ['id'];
    }
}
class file extends filecore
{
}
