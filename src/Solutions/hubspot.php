<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @copyright Copyright (C) 2017 - 2023  Stéphane Faure - CRMconsult EURL
 * @link http://www.myddleware.com
 *
 * This file is part of Myddleware.
 *
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

namespace App\Solutions;

use Datetime;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

use HubSpot\Factory;
use HubSpot\Client\Crm\Objects\Emails\ApiException;
use HubSpot\Client\Crm\Objects\Emails\Model\AssociationSpec;
use HubSpot\Client\Crm\Objects\Emails\Model\PublicAssociationsForObject;
use HubSpot\Client\Crm\Objects\Emails\Model\PublicObjectId;
use HubSpot\Client\Crm\Objects\Emails\Model\SimplePublicObjectInputForCreate;

use HubSpot\Client\Crm\Objects\Notes\ApiException as ApiExceptionNote;
use HubSpot\Client\Crm\Objects\Notes\Model\AssociationSpec as AssociationSpecNote;
use HubSpot\Client\Crm\Objects\Notes\Model\PublicAssociationsForObject as PublicAssociationsForObjectNote;
use HubSpot\Client\Crm\Objects\Notes\Model\PublicObjectId as PublicObjectIdNote;
use HubSpot\Client\Crm\Objects\Notes\Model\SimplePublicObjectInputForCreate as SimplePublicObjectInputForCreateNote;

use HubSpot\Client\Crm\Objects\Calls\ApiException as ApiExceptionCall;
use HubSpot\Client\Crm\Objects\Calls\Model\AssociationSpec as AssociationSpecCall;
use HubSpot\Client\Crm\Objects\Calls\Model\PublicAssociationsForObject as PublicAssociationsForObjectCall;
use HubSpot\Client\Crm\Objects\Calls\Model\PublicObjectId as PublicObjectIdCall;
use HubSpot\Client\Crm\Objects\Calls\Model\SimplePublicObjectInputForCreate as SimplePublicObjectInputForCreateCall;

class hubspot extends solution
{
    protected $hubspot;
    protected $apiCallLimit = 100;

    protected array $FieldsDuplicate = array(
        'contacts' => array('email')
    );

    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'accesstoken',
                'type' => PasswordType::class,
                'label' => 'solution.fields.accesstoken',
            ],
        ];
    }

    // Connect to Hubspot
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            $this->hubspot = \HubSpot\Factory::createWithAccessToken($this->paramConnexion['accesstoken']);
            // Call the standard API. OK if no exception.
            $response = $this->hubspot->crm()->contacts()->basicApi()->getPage();

            $this->connexion_valide = true;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

	// List modules
    public function get_modules($type = 'source'): array
    {
        $modules = array(
            'contacts' => 'Contacts'
        );
		if ($type == 'target') {
			$modules['emails'] = 'Emails';
			$modules['notes'] = 'Notes';
			$modules['calls'] = 'Calls';
		}
        return $modules;
    }

    // Renvoie les champs du module passé en paramètre
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
			// Use Hubspot metadata
            require 'lib/hubspot/metadata.php';
			// If a metadat exists, we return it
			if (!empty($moduleFields[$module])) {
				$this->moduleFields = $moduleFields[$module];
				return $this->moduleFields;
			}
            $properties = $this->hubspot->crm()->properties()->coreApi()->getAll($module)->getResults();
            if (!empty($properties)){
                foreach($properties as $property) {
                    // List value
                    $options = $property->getOptions();
                    // Don't add records list fields
                    if (
                            $property->getFieldType() == 'select'
                        AND empty($options)
                    ) {
                        continue;
                    }
                    // Don't add the hs fields
                    $name = $property->getName();
                    if (substr($name,0,3) == 'hs_'){
                        continue;
                    }
                    $this->moduleFields[$name] = array(
                                'label' => $property->getLabel(),
                                'type' => 'varchar(255)',
                                'type_bdd' => 'varchar(255)',
                                'required' => false,
                                'relate' => (empty($property->getReferencedObjectType()) ? false : true),
                            );
                    // Add =value list
                    if(!empty($options)) {
                        foreach($options as $option) {
                            $this->moduleFields[$property->getName()]['options'][$option->getValue()] = $option->getLabel();
                        }
                    }
                }
            }
            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }

    public function read($param)
    {
        try {
            // Initialize result and parameters
            $result = array();
            $nbRecords = 0;
            $apiCallLimit = ($param['limit'] < $this->apiCallLimit ? $param['limit'] : $this->apiCallLimit);

            $after = 0;
            $filter = new \HubSpot\Client\Crm\Contacts\Model\Filter();
            $dateRef = $this->dateTimeFromMyddleware($param['date_ref']);
            // Set the filter 
            if (!empty($param['query'])) {
                foreach($param['query'] as $key=>$value) {
                    $filter->setOperator('EQ')
                            ->setPropertyName($key)
                            ->setValue($value);
                }
            } elseif (!empty($param['date_ref'])) {
                $filter->setOperator('GT')
                        ->setPropertyName('lastmodifieddate')
                        ->setValue($dateRef);
            }
            $filterGroup = new \HubSpot\Client\Crm\Contacts\Model\FilterGroup();
            $filterGroup->setFilters([$filter]);
            $searchRequest = new \HubSpot\Client\Crm\Contacts\Model\PublicObjectSearchRequest();
            $searchRequest->setFilterGroups([$filterGroup]);
            // Always sort by last modified date ascending
            $sorts = array(
                        array(
                            'propertyName' => 'lastmodifieddate',
                            'direction' => 'ASCENDING',
                         ),
                    );
            $searchRequest->setSorts($sorts);
            // Set the limit and the offset
            $searchRequest->setLimit($apiCallLimit);

            // Set the fields requested
            $searchRequest->setProperties($param['fields']);

            do {
                // Manage offset
                $searchRequest->setAfter($after);

                // Search records from Hubspot
                if (!empty($param['query']['id'])) {
                    $records[0] = $this->hubspot->crm()->contacts()->basicApi()->getById($param['query']['id']);
                } else {
                    $recordList = $this->hubspot->crm()->contacts()->searchApi()->doSearch($searchRequest);
                    $records = $recordList->getResults();
                }
                
                // Format results
                if (!empty($records)) {
                    foreach($records as $record) {
                        // Stop the process if limit has been reached
                        if ($param['limit'] <= $nbRecords) {
                            break;
                        }
                        $recordValues = $record->getProperties();
                        $recordId = $record->getId();
						// Always return id and lastmodifieddate
                        $result[$recordId]['id'] = $recordId;
						$result[$recordId]['lastmodifieddate'] = $recordValues['lastmodifieddate'];
                        // Fill every rule fields
                        foreach($param['fields'] as $field) {
                            $result[$recordId][$field] = $recordValues[$field] ?? null;
                        }
                        $nbRecords++;
                    }
                }
                // No pagination for search by id (only 1 result)
                if (!empty($param['query']['id'])) {
                    break;
                }
                if (!is_null($recordList->getPaging())) {
                    $after = $recordList->getPaging()->getNext()->getAfter();
                }
            // Stop if no result or if the rule limit has been reached
            } while (
                    is_object($recordList) 
                AND $recordList->getPaging()
                AND $param['limit'] > $nbRecords
            );
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
        }
        return $result;
    }

	/**
     * @throws Exception
     */
    protected function create($param, $record, $idDoc = null) {
		switch ($param['module']) {
			case 'emails':
				return $this->createEmail($param, $record, $idDoc);
			case 'notes':
				return $this->createNote($param, $record, $idDoc);
			case 'calls':
				return $this->createCall($param, $record, $idDoc);
			default:
			   throw new \Exception('Module '.$param['module'].' unknown');
		}
		
	}


	protected function createCall($param, $record, $idDoc = null)
    {		
		$associationSpec = new AssociationSpecCall([
			'association_category' => $record['associations_category'],
			'association_type_id' => $record['associations_typeId']
		]);
		$to = new PublicObjectIdCall([
			'id' =>  $record['associations_to_id']
		]);
		$publicAssociationsForObject = new PublicAssociationsForObjectCall([
			'types' => [$associationSpec],
			'to' => $to
		]);

		// Build properties
		$properties = array();
		foreach($record as $key => $value) {
			// Properties start by hs_
			if(substr($key,0,3) == 'hs_') {
				$properties[$key] = $value;
			}
		}
		$data['associations'] = [$publicAssociationsForObject]; 
		$data['properties'] = $properties; 
		if (!empty($record['traceId'])) {
			$data['object_write_trace_id'] = $record['traceId']; 
		}
		// Prepare data to be sent
		$simplePublicObjectInputForCreate = new SimplePublicObjectInputForCreateCall($data);
		// Create the call to Hubspot
		$apiResponse = $this->hubspot->crm()->objects()->calls()->basicApi()->create($simplePublicObjectInputForCreate);
		if (!empty($apiResponse->getId())) {
			return $apiResponse->getId();
		}
		return null;        
    }
    
	protected function createNote($param, $record, $idDoc = null)
    {		
		$associationSpec = new AssociationSpecNote([
			'association_category' => $record['associations_category'],
			'association_type_id' => $record['associations_typeId']
		]);
		$to = new PublicObjectIdNote([
			'id' =>  $record['associations_to_id']
		]);
		$publicAssociationsForObject = new PublicAssociationsForObjectNote([
			'types' => [$associationSpec],
			'to' => $to
		]);

		// Build properties
		$properties = array();
		foreach($record as $key => $value) {
			// Properties start by hs_
			if(substr($key,0,3) == 'hs_') {
				$properties[$key] = $value;
			}
		}
		$data['associations'] = [$publicAssociationsForObject]; 
		$data['properties'] = $properties; 
		if (!empty($record['traceId'])) {
			$data['object_write_trace_id'] = $record['traceId']; 
		}
		// Prepare data to be sent
		$simplePublicObjectInputForCreate = new SimplePublicObjectInputForCreateNote($data);
		// Create the note to Hubspot
		$apiResponse = $this->hubspot->crm()->objects()->notes()->basicApi()->create($simplePublicObjectInputForCreate);
		if (!empty($apiResponse->getId())) {
			return $apiResponse->getId();
		}
		return null;        
    }

	protected function createEmail($param, $record, $idDoc = null)
    {
		$associationSpec = new AssociationSpec([
			'association_category' => $record['associations_category'],
			'association_type_id' => $record['associations_typeId']
		]);
		$to = new PublicObjectId([
			'id' =>  $record['associations_to_id']
		]);
		$publicAssociationsForObject = new PublicAssociationsForObject([
			'types' => [$associationSpec],
			'to' => $to
		]);

		// Build properties
		$properties = array();
		foreach($record as $key => $value) {
			// Properties start by hs_
			if(substr($key,0,3) == 'hs_') {
				$properties[$key] = $value;
			}
		}

		$data['associations'] = [$publicAssociationsForObject]; 
		$data['properties'] = $properties; 
		if (!empty($record['traceId'])) {
			$data['object_write_trace_id'] = $record['traceId']; 
		}		
		// Prepare data to be sent
		$simplePublicObjectInputForCreate = new SimplePublicObjectInputForCreate($data);
		// Create the email to Hubspot
		$apiResponse = $this->hubspot->crm()->objects()->emails()->basicApi()->create($simplePublicObjectInputForCreate);
		if (!empty($apiResponse->getId())) {
			return $apiResponse->getId();
		}
		return null;        
    }

    public function getRuleMode($module, $type): array
    {
        if (
                $type == 'target'
            && in_array($module, ['notes','calls','emails'])
        ) {
            return [
                'C' => 'create_only',
            ];
        }
        return parent::getRuleMode($module, $type);
    }
	

     /**
     * @throws \Exception
     */
    public function getRefFieldName($param): string
    {
        return 'lastmodifieddate';
    }
	
	// Redefine addRequiredField to remove the field lastmodifieddate from the list fields.
	// This fields is always returned by Hubspot 
	protected function addRequiredField($fields, $module = 'default', $mode = null)
    {
		$fields = parent::addRequiredField($fields, $module, $mode);
		$key = array_search('lastmodifieddate', $fields);
		if ($key !== false) {
			unset($fields[$key]);
		}
		return $fields;
	}

    // Function de conversion de datetime format solution à un datetime format Myddleware
    protected function dateTimeToMyddleware($dateTime)
    {
        $dto = new \DateTime($dateTime);
        // Return date with milliseconds
        return $dto->format('Y-m-d H:i:s.v');
    }

    // Function de conversion de datetime format Myddleware à un datetime format solution
    protected function dateTimeFromMyddleware($dateTime)
    {
        $dto = DateTime::createFromFormat('Y-m-d H:i:s.v', $dateTime);
        // If the user set a reference date manually then there is no milliseconds
        if (empty($dto)) {
            $dto = DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
        }
        return $dto->format('Uv');
    }

}
