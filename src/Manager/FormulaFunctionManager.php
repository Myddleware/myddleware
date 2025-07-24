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

namespace App\Manager;

use App\Entity\DocumentRelationship as DocumentRelationship;

class FormulaFunctionManager
{
    protected array $names = ['changeTimeZone', 'changeFormatDate', 'changeValue', 'changeMultiValue', 'getValueFromArray','lookup','getRecord'];
    protected string $path = "App\Manager\FormulaFunctionManager::";
	
    public function getNamesFunctions(): array
    {
        return $this->names;
    }

    public function getPathFunctions(): array
    {
        // Concaténation avant envoi du chemin avec le nom
        $return = [];
        foreach ($this->names as $name) {
            $return[] = $this->path.$name;
        }

        return $return;
    }

    public function addPathFunctions($formula)
    {
        if (!empty($this->names)) {
            foreach ($this->names as $name) {
                $formula = str_replace($name, $this->path.$name, $formula);
            }
        }

        return $formula;
    }

    public static function changeTimeZone($dateToChange, $oldTimeZone, $newTimeZone)
    {
        if (empty($dateToChange)) {
            return;
        }
        $date = date_create($dateToChange, timezone_open($oldTimeZone));
        date_timezone_set($date, timezone_open($newTimeZone));

        return date_format($date, 'Y-m-d H:i:s');
    }

    public static function changeFormatDate($dateToChange, $oldFormat, $newFormat)
    {
        if (empty($dateToChange)) {
            return;
        }
        $date = \DateTime::createFromFormat($oldFormat, $dateToChange);

        return date_format($date, $newFormat);
    }

    public static function changeValue($var, $arrayKeyToValue, $acceptNull = null)
    {
        // Transform string into an array
		// Change first and last characters (parentheses) by accolades
		// Replace ' before and after , and : by " (manage space before , and :)
        $arrayKeyToValue = json_decode('{"'.str_replace([ ':\'', '\':',  ': \'', '\' :', ',\'', '\',', ', \'', '\' ,'], [ ':"', '":', ':"', '":', ',"', '",', ',"', '",'], substr($arrayKeyToValue,2,-2)).'"}', true);
        if (in_array($var, array_keys($arrayKeyToValue))) {
            return $arrayKeyToValue[$var];
        }
        if (!empty($acceptNull)) {
            return '';
        }
    }
	
    public static function changeMultiValue($var, $arrayKeyToValue, $delimiter)
    {
        // Transform string into an array
		// Change first and last characters (parentheses) by accolades
		// Replace ' before and after , and : by " (manage space before , and :)
        $return = '';
        $arrayVar = explode($delimiter, $var);
        if (!empty($arrayVar)) {
            $arrayKeyToValue = json_decode('{"'.str_replace([ ':\'', '\':',  ': \'', '\' :', ',\'', '\',', ', \'', '\' ,'], [ ':"', '":', ':"', '":', ',"', '",', ',"', '",'], substr($arrayKeyToValue,2,-2)).'"}', true);
            foreach ($arrayVar as $varValue) {
                // Transform string into an array
                if (!empty($arrayKeyToValue[$varValue])) {
                    // Prepare return value
                    $return .= $arrayKeyToValue[$varValue].$delimiter;
                }
            }

            return rtrim($return, $delimiter);
        }
    }

    public static function getValueFromArray($key, $array)
    {
        if (!empty($array[$key])) {
            return $array[$key];
        }
    }
	
	public static function lookup($entityManager, $connection, $currentRule, $docId, $myddlewareUserId, $sourceFieldName, $field, $rule, $errorIfEmpty=false, $errorIfNotFound=true, $parent=false, $forceDirection=null)
	{
		// Manage error if empty
		if (empty($field)) {
			if ($errorIfEmpty) {
				throw new \Exception('The field '.$sourceFieldName.' is empty. Failed to find the relate value. ');
			} else {
				return '';
			}
		}
		// In case of simulation during rule creation (not edition), we don't have the current rule id.
		// We set direction = 1 by default
		if ($forceDirection !== null) {
			$direction = $forceDirection;
		} elseif ($currentRule === 0) {
			$direction = 1;
		} else {
			// Get rules detail
			$ruleQuery = "SELECT * FROM rule WHERE id = :ruleId";
			$stmt = $connection->prepare($ruleQuery);
			$stmt->bindValue(':ruleId', $currentRule);
			$result = $stmt->executeQuery();
			$ruleRef = $result->fetchAssociative();
			$stmt->bindValue(':ruleId', $rule);
			$result = $stmt->executeQuery();
			$ruleLink = $result->fetchAssociative();
		}

		// Query to search the relate record id is different depending on the direction of the relationship
		// We order the result by date_modified to be sure we retrieve the lastest target id sent
		if (
			(
					!empty($ruleRef)
				AND empty($direction)
				AND	$ruleRef['conn_id_source'] == $ruleLink['conn_id_source']
				AND	$ruleRef['conn_id_target'] == $ruleLink['conn_id_target']
			) 
			// In case of the linked rule has the target connector = source connector, we use module to get the direction of the relationship 
			OR (
					!empty($ruleRef)
				AND empty($direction)
				AND $ruleLink['conn_id_target'] == $ruleLink['conn_id_source']
				AND	(
						$ruleRef['module_source'] == $ruleLink['module_source']
					 OR $ruleRef['module_target'] == $ruleLink['module_target']
				)
			) 
			OR ($currentRule === 0) // Manage simulation
			OR (
					!empty($direction)
				AND $direction == '1'
			)
		){
			$sqlParams = "	SELECT 
									target_id record_id,
									GROUP_CONCAT(DISTINCT document.id ORDER BY document.source_date_modified DESC) document_id,
									GROUP_CONCAT(DISTINCT document.type) types,
									MAX(document.date_modified) date_modified
								FROM document 
								WHERE  
										document.rule_id = :ruleRelateId
									AND document.source_id = :record_id
									AND document.deleted = 0
									AND document.target_id != ''
									AND (
											document.global_status = 'Close'
										 OR document.status = 'No_send'
									)
								GROUP BY target_id
								HAVING types NOT LIKE '%D%'
								ORDER BY date_modified DESC
								LIMIT 1";
			$direction = 1;
		} elseif (
			(
					!empty($ruleRef)
				AND empty($direction)
				AND	$ruleRef['conn_id_source'] == $ruleLink['conn_id_target']
				AND	$ruleRef['conn_id_target'] == $ruleLink['conn_id_source']
			)
			// In case of the linked rule has the target connector = source connector, we use module to get the direction of the relationship 
			OR (
					!empty($ruleRef)
				AND empty($direction)
				AND $ruleLink['conn_id_target'] == $ruleLink['conn_id_source']
				AND	(
						$ruleRef['module_source'] == $ruleLink['module_target']
					 OR $ruleRef['module_target'] == $ruleLink['module_source']
				)
			)
			OR (
					!empty($direction)
				AND $direction == '-1'
			)
		){
			$sqlParams = "	SELECT 
								source_id record_id,
								GROUP_CONCAT(DISTINCT document.id ORDER BY document.source_date_modified DESC) document_id,
								GROUP_CONCAT(DISTINCT document.type) types,
								MAX(document.date_modified) date_modified
							FROM document
							WHERE  
									document.rule_id = :ruleRelateId
								AND document.source_id != ''
								AND document.deleted = 0
								AND document.target_id = :record_id
								AND (
										document.global_status = 'Close'
									 OR document.status = 'No_send'
								)
							GROUP BY source_id
							HAVING types NOT LIKE '%D%'
							ORDER BY date_modified DESC
							LIMIT 1";
			$direction = -1;
		} else {
			throw new \Exception('The connectors do not match between rule '.$currentRule.' and rule '.$rule.'. ');
		}
		// Get the record id
		$stmt = $connection->prepare($sqlParams);
		$stmt->bindValue(':ruleRelateId', $rule);
		$stmt->bindValue(':record_id', $field);
		$result = $stmt->executeQuery();
		$result = $result->fetchAssociative();
		// Manage error if no result found
		if (empty($result['record_id'])) {
			if ($errorIfNotFound) {
				// If no target id found, we check if the parent has been filtered, in this case we filter the relate document too
				$documentSearch = self::searchRelateDocumentByStatus($connection, $rule, $field, 'Filter', $direction);
				if (!empty($documentSearch['id'])) {
					// Return a code with the parent documcnet id
					throw new \Exception('mdw_set_filter_status;'.$documentSearch['id']);
				}
				throw new \Exception('Failed to retrieve a related document. No data for the field '.$sourceFieldName.'. There is not record with the ID '.('1' == $direction ? 'source' : 'target').' '.$field.' in the rule '.$ruleLink['name'].'. This document is queued. ');
			} else {
				return '';
			}
		}
		// In cas of several document found we get only the last one
		if (
				!empty($result['document_id'])
			AND strpos($result['document_id'], ',')
		) {
			$documentList = explode(',',$result['document_id']);
			$result['document_id'] = $documentList[0];
		}
		// No doc id in case of simulation
		if (!empty($docId)) {
			// Add the relationship in the table document Relationship
			try {
				$documentRelationship = new DocumentRelationship();
				$documentRelationship->setDocId($docId);
				$documentRelationship->setDocRelId($result['document_id']);
				$documentRelationship->setDateCreated(new \DateTime());
				$documentRelationship->setCreatedBy((int) $myddlewareUserId);
				$documentRelationship->setSourceField($sourceFieldName);
				$entityManager->persist($documentRelationship);
			} catch (\Exception $e) {
				throw new \Exception('Failed to save the document relationship for the field '.$sourceFieldName.' : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
			}
		}
		return $result['record_id'];
    }
	
	// Search relate document by status
    protected static function searchRelateDocumentByStatus($connection, $ruleRelationship, $record_id, $status, $direction)
    {
        try {
            // We use differents queries depending on the rule 's direction
            if ('-1' == $direction) {
                $sqlParams = '	SELECT *								
								FROM document
								WHERE  
										document.rule_id = :ruleRelateId 
									AND document.target_id = :record_id 
									AND document.status = :status 
									AND document.deleted = 0 
								LIMIT 1';
            } elseif ('1' == $direction) {
                $sqlParams = '	SELECT *
								FROM document 
								WHERE  
										document.rule_id = :ruleRelateId 
									AND document.source_id = :record_id 
									AND document.status = :status 
									AND document.deleted = 0 
								LIMIT 1';
            } 
            $stmt = $connection->prepare($sqlParams);
            $stmt->bindValue(':ruleRelateId', $ruleRelationship);
            $stmt->bindValue(':record_id', $record_id);
            $stmt->bindValue(':status', $status);
            $result = $stmt->executeQuery();
            $result = $result->fetchAssociative();
            if (!empty($result['id'])) {
                return $result;
            }
        } catch (\Exception $e) {
            throw new \Exception('Error searchRelateDocumentByStatus  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
        return null;
    }
	
		public static function getRecord($entityManager, $connection, $solutionManager, $connectorId, $module, $fields, $searchValue, $searchField = 'id', $errorIgnore = false)
	{
		try {
			// Connect to the application using the connector
			$connectionSolution = self::connectionSolution($entityManager, $connection, $solutionManager, $connectorId);
			if (empty($connectionSolution['connexion_valide'])) {
				throw new \Exception('getRecord : Failed to connect to the solution to read record '.$module.' with value '.$searchValue.'.');
			}
			// Prepare parameters t read the data
			$read['module'] = $module;
			$read['fields'] = explode(',',$fields);
			$read['offset'] = 0;
			$read['limit'] = 1;
			// Get all the searchFields and searchValues (we can have several search filter separated by commas)
			$searchFields = explode(',',$searchField);
			$searchValues = explode(',',$searchValue);
			// Error if the number of filters is different than the number od values
			if (count($searchFields) != count($searchValues)) {
				throw new \Exception('Number of search fields and search values has to be the same. You have '.count($searchFields).' searchFields and '.count($searchValues).' searchValues.');
			}
			// Build the query criteria
			if (!empty($searchFields)) {
				foreach($searchFields as $key => $field) {
					$read['query'][$field] = $searchValues[$key];				
				}
			}
			$read['fields'] = array_unique(array_merge($read['fields'], $searchFields));
			$read['call_type'] = 'getRecord';
			$read['ruleParams']['mode'] = '0';
			$read['ruleParams']['fieldId'] = $searchField[0];
			// Not used because query is used but required for some solutions 
			$read['ruleParams']['fieldDateRef'] = $searchField[0]; 
			// Read data from the solution
			$data = $connectionSolution['solution']->readData($read);
			if (empty($data['values'])) {
				throw new \Exception('getRecord : Failed to find the record with calue '.$searchValue.' in the module '.$module.'.');
			}
			return (object)(current($data['values']));
        } catch (\Exception $e) {
			if (!$errorIgnore) {
				new \Exception('Error searchRelateDocumentByStatus  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
			}
        }
		return null;
	}
	
	// Connect to the source or target application
	private static function connectionSolution($entityManager, $connection, $solutionManager, $connectorId) {
		try {
			// Get the name of the application			
		    $sql = "SELECT solution.name  
		    		FROM connector
						INNER JOIN solution 
							ON solution.id  = connector.sol_id
		    		WHERE connector.id = :connId";
		    $stmt = $connection->prepare($sql);
			$stmt->bindValue(":connId", $connectorId);
			$result = $stmt->executeQuery();
            $r = $result->fetchAssociative();
			$solutionName = $r['name'];
		
 			// Get params connection
		    $sql = "SELECT id, conn_id, name, value
		    		FROM connectorparam 
		    		WHERE conn_id = :connId";
		    $stmt = $connection->prepare($sql);
			$stmt->bindValue(":connId", $connectorId);
		    $stmt->execute();	    
			$resultConn = $stmt->executeQuery();
            $tab_params = $resultConn->fetchAllAssociative();
			
			// Prepare the parameters
			$params = array();
			if(!empty($tab_params)) {
				foreach ($tab_params as $key => $value) {
					$params[$value['name']] = $value['value'];
					$params['ids'][$value['name']] = array('id' => $value['id'],'conn_id' => $value['conn_id']);
				}			
			}
			// Login to the application
			$solution = $solutionManager->get($r['name']);			
            $solution->setApi(0);			
            $loginResult = $solution->login($params);		
            $c = (($solution->connexion_valide) ? true : false);
			return array('connexion_valide' => $c, 'solution' => $solution);	 
		} catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			throw new \Exception($error);
		}	
		return $connexion_valide;		
	}
}
