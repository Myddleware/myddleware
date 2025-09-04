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
    protected array $names = ['changeTimeZone', 'changeFormatDate', 'changeValue', 'changeMultiValue', 'getValueFromArray','lookup'];
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
            $this->message .= 'Error searchRelateDocumentByStatus  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($this->id.' - '.$this->message);
        }
        return null;
    }
}
