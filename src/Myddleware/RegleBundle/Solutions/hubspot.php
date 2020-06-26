<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

namespace Myddleware\RegleBundle\Solutions;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Session\Session;
use \Datetime;

class hubspotcore extends solution {

    protected $url = 'https://api.hubapi.com/';
    protected $version = 'v1';
    protected $readLast = false;
    protected $migrationMode = false;

    protected $FieldsDuplicate = array(
        'contacts' => array('email'),
    );
	
	// Requiered fields for each modules
	protected $required_fields = array(
									'companies' 	=> array('hs_lastmodifieddate'),
									'deal' 			=> array('hs_lastmodifieddate'),
									'contact' 		=> array('lastmodifieddate'),
									'owners' 		=> array('updatedAt'),
									'deals' 		=> array('updatedAt'),
									'engagements' 	=> array('lastUpdated')
								);
		
	// Name of reference fields for each module
	protected $modifiedField = array(
									'companies' 	=> 'hs_lastmodifieddate',
									'deal' 			=> 'hs_lastmodifieddate',
									'contact' 		=> 'lastmodifieddate',
									'owners' 		=> 'updatedAt',
									'deals' 		=> 'updatedAt',
									'engagements' 	=> 'lastUpdated'
								);
								
	protected $limitCall = array(
									'companies' => 100,  // 100 max
									'deal' => 100,  // 100 max
									'contact' => 100, // 100 max
									'engagements' => 100, // 100 max							
								);	
					
    public function getFieldsLogin(){
        return array(
            array(
                'name' => 'apikey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.apikey'
            )
        );
    }

    // Conect to Hubspot
    public function login($paramConnexion) {
        parent::login($paramConnexion);
        try {
            $result = $this->call($this->url . 'properties/' . $this->version . '/contacts/properties?hapikey=' . $this->paramConnexion['apikey']);
            if (!empty($result['exec']['message'])) {
                throw new \Exception($result['exec']['message']);
            } elseif (empty($result)) {
                throw new \Exception('Failed to connect but no error returned by Hubspot. ');
            }
            $this->connexion_valide = true;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);
            return array('error' => $error);
        }
    } // login($paramConnexion)*/


    public function get_modules($type = 'source') {
        $modules = array(
            'companies' => 'Companies',
            'contacts' => 'Contacts',
            'deals' => 'Deals',
            'owners' => 'Owners',
            'deal_pipeline' => 'Deal pipeline',
            'deal_pipeline_stage' => 'Deal pipeline stage',
        );

        // Module to create relationship between deals and contacts/companies
        if ($type == 'target') {
            $modules['associate_deal'] = 'Associate deals with companies/contacts';
        } elseif ($type == 'source') {
            $modules['associate_deal_contact'] = 'Associate deals with contacts';
            $modules['associate_deal_company'] = 'Associate deals with contacts';
        }
		// Module only available in source
		if ($type == 'source') {
			$modules['engagement_task'] = 'Engagement Task';
			$modules['engagement_call'] = 'Engagement Call';
			$modules['engagement_email'] = 'Engagement Email';
			$modules['engagement_meeting'] = 'Engagement Meeting';
			$modules['engagement_note'] = 'Engagement Note';
		}
        return $modules;
    } // get_modules()

    // Renvoie les champs du module passé en paramètre
    public function get_module_fields($module, $type = 'source') {
        parent::get_module_fields($module, $type);
        try {

            $engagement = explode("_", $module) [0] === 'engagement' ? true : false;
            $engagement_module = explode("_", $module);

            // Manage custom module to deal with associate_deal
            if ($module == 'associate_deal') {
                $result = array(
                    array('name' => 'deal_id', 'label' => 'Deal Id', 'type' => 'varchar(36)'),
                    array('name' => 'record_id', 'label' => 'Contact or company ID', 'type' => 'varchar(36)'),
                    array('name' => 'object_type', 'label' => 'Object Type', 'type' => 'varchar(36)', 'options' => array(array('value' => 'CONTACT', 'label' => 'Contact'), array('value' => 'COMPANY', 'label' => 'Company')))
                );
            } else if ($module === "owners") {
                $result = array(
                    array('name' => 'portal Id', 'label' => 'PortalId', 'type' => 'varchar(36)'),
                    array('name' => 'Type', 'label' => 'Type', 'type' => 'varchar(36)'),
                    array('name' => 'firstName', 'label' => 'Firstname', 'type' => 'varchar(255)'),
                    array('name' => 'lastName', 'label' => 'Lastname', 'type' => 'varchar(255)'),
                    array('name' => 'email', 'label' => 'Email', 'type' => 'varchar(255)'),
                    array('name' => 'created_at', 'label' => 'Created at', 'type' => 'varchar(36)'),
                    array('name' => 'updated_at', 'label' => 'Updated at', 'type' => 'varchar(36)'),
                    array('name' => 'remoteList__portalId', 'label' => 'RemoteList portal Id', 'type' => 'varchar(36)'),
                    array('name' => 'remoteList__ownerId', 'label' => 'RemoteList owner Id', 'type' => 'varchar(36)'),
                    array('name' => 'remoteList__remoteId', 'label' => 'RemoteList remote Id', 'type' => 'varchar(36)'),
                    array('name' => 'remoteList__remoteType', 'label' => 'RemoteList remote Type', 'type' => 'varchar(36)'),
                    array('name' => 'remoteList__active', 'label' => 'RemoteList active', 'type' => 'varchar(36)'),
                );
            } elseif ($module === "deal_pipeline") {
                $result = array(
                    array('name' => 'active', 'label' => 'Active', 'type' => 'varchar(1)'),
                    array('name' => 'label', 'label' => 'Label', 'type' => 'varchar(255)'),
                    array('name' => 'pipelineId', 'label' => 'Pipeline Id', 'type' => 'varchar(36)'),
                );
            } elseif ($module === "deal_pipeline_stage") {
                $result = array(
                    array('name' => 'id', 'label' => 'Id', 'type' => 'varchar(36)'),
                    array('name' => 'pipelineId', 'label' => 'Pipeline Id', 'type' => 'varchar(36)'),
                    array('name' => 'active', 'label' => 'Active', 'type' => 'varchar(1)'),
                    array('name' => 'closedWon', 'label' => 'closedWon', 'type' => 'varchar(255)'),
                    array('name' => 'displayOrder', 'label' => 'DisplayOrder', 'type' => 'varchar(255)'),
                    array('name' => 'label', 'label' => 'Label', 'type' => 'varchar(255)'),
                    array('name' => 'probability', 'label' => 'Probability', 'type' => 'varchar(255)'),
                );
            } elseif ($engagement) {
                $result = array(
                    array('name' => 'engagement__id', 'label' => 'Id', 'type' => 'varchar(36)'),
                    array('name' => 'engagement__portalId', 'label' => 'Portal id', 'type' => 'varchar(36)'),
                    array('name' => 'engagement__createdAt', 'label' => 'Created at', 'type' => 'varchar(255)'),
                    array('name' => 'engagement__lastUpdated', 'label' => 'Last updated', 'type' => 'varchar(255)'),
                    array('name' => 'engagement__ownerId', 'label' => 'OwnerId', 'type' => 'varchar(36)'),
                    array('name' => 'engagement__type', 'label' => 'Type', 'type' => 'varchar(255)'),
                    array('name' => 'engagement__timestamp', 'label' => 'Timestamp', 'type' => 'varchar(255)'),
                    array('name' => 'associations__contactIds', 'label' => 'Contact Ids', 'type' => 'varchar(36)'),
                    array('name' => 'associations__companyIds', 'label' => 'Company Ids', 'type' => 'varchar(36)'),
                    array('name' => 'associations__dealIds', 'label' => 'Deal Ids', 'type' => 'varchar(36)'),
                );

                switch ($engagement_module[1]) {
                    case 'note':
                        array_push($result,
                            array('name' => 'metadata__body', 'label' => 'Note body', 'type' => 'text')
                        );
                        break;
                     case 'call':
                        array_push($result,
                            array('name' => 'metadata__toNumber', 'label' => 'To number', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__fromNumber', 'label' => 'From number', 'type' => 'varchar(25)'),
                            array('name' => 'metadata__status', 'label' => 'Status', 'type' => 'varchar(36)', 'options' => array(array('value' => 'COMPLETED', 'label' => 'COMPLETED'))),
                            array('name' => 'metadata__externalId', 'label' => 'External Id', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__durationMilliseconds', 'label' => 'Duration Milliseconds', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__externalAccountId', 'label' => 'External Account Id', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__recordingUrl', 'label' => 'RecordingUrl', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__body', 'label' => 'Body', 'type' => 'text'),
                            array('name' => 'metadata__disposition', 'label' => 'Disposition', 'type' => 'varchar(255)')
                        );
                        break;
                    case 'task':
                        array_push($result,
                            //metadata
                            array('name' => 'metadata__body', 'label' => 'Body', 'type' => 'text'),
                            array('name' => 'metadata__status', 'label' => 'Status', 'type' => 'varchar(255)', 'options' => array(
																																array('value' => 'NOT_STARTED', 'label' => 'NOT_STARTED'), 
																																array('value' => 'COMPLETED', 'label' => 'COMPLETED'), 
																																array('value' => 'IN_PROGRESS', 'label' => 'IN_PROGRESS'), 
																																array('value' => 'WAITING', 'label' => 'WAITING'), 
																																array('value' => 'DEFERRED', 'label' => 'DEFERRED'))),
                            array('name' => 'metadata__subject', 'label' => 'Subject', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__forObjectType', 'label' => 'Object Type', 'type' => 'varchar(255)', 'options' => array(array('value' => 'CONTACT', 'label' => 'Contact'), array('value' => 'COMPANY', 'label' => 'Company')))
                        );
                        break;

                    case 'meeting':
                        array_push($result,
                            //metadata
                            array('name' => 'metadata__body', 'label' => 'Body', 'type' => 'text'),
                            array('name' => 'metadata__startTime', 'label' => 'startTime', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__endTime', 'label' => 'endTime', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__title', 'label' => 'Title', 'type' => 'varchar(255)')
                        );
                        break;

                    case 'email':
                        array_push($result,
                            //metadata
                            array('name' => 'metadata__from__email', 'label' => 'From email', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__from__firstName', 'label' => 'From firstName', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__from__lastName', 'label' => 'From lastName', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__to__email', 'label' => 'To email', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__cc', 'label' => 'CC', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__bcc', 'label' => 'BCC', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__subject', 'label' => 'Subject', 'type' => 'varchar(255)'),
                            array('name' => 'metadata__html', 'label' => 'HTML', 'type' => 'text'),
                            array('name' => 'metadata__text', 'label' => 'Text', 'type' => 'text')
                        );
                        break;
                }
            } else {
                $result = $this->call($this->url . 'properties/' . $this->version . '/' . $module . '/properties?hapikey=' . $this->paramConnexion['apikey']);
                $result = $result['exec'];
				// Add fields to manage deals relationships
				if ($module === "deals") {
					$result[] = array('name' => 'associations__associatedVids', 'label' => 'Contact Id', 'type' => 'varchar(36)');
					$result[] = array('name' => 'associations__associatedCompanyIds', 'label' => 'Company Id', 'type' => 'varchar(36)');
				}				
            }
            if (!empty($result['message'])) {
                throw new \Exception($result['message']);
            } elseif (empty($result)) {
                throw new \Exception('No fields returned by Hubspot. ');
            }		
            // Add each field in the right list (relate fields or normal fields)
            foreach ($result as $field) {
                // Field not editable can't be display on the target side
                if (
                    !empty($field['readOnlyValue'])
                    AND $type == 'target'
                ) {
                    continue;
                }
                // If the fields is a relationship
                if (
						strtoupper(substr($field['name'], -2)) == 'ID'
					 or	strtoupper(substr($field['name'], -3)) == 'IDS'
				) {
                    $this->fieldsRelate[$field['name']] = array(
                        'label' => $field['label'],
                        'type' => 'varchar(36)',
                        'type_bdd' => 'varchar(36)',
                        'required' => 0,
                        'required_relationship' => 0,
                    );
                }
                $this->moduleFields[$field['name']] = array(
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'type_bdd' => $field['type'],
                    'required' => 0
                );
                // Add list of values
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $value) {
                        $this->moduleFields[$field['name']]['option'][$value['value']] = $value['label'];
                    }
                }
            }
            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            return false;
        }
    } // get_module_fields($module)
	
    /**
     * Get the last data in the application
     * @param $param
     * @return mixed
     */
    public function read_last($param) {	
		// Set the attribut readLast to true to stop the search when we found at leas one record
		$this->readLast = true;
		// date_ref far in the past to be sure to found at least one record
		$param['date_ref'] = '1970-01-01 00:00:00';
		$param['rule']['mode'] = '0';
		$param['limit'] = 1;
		// We re use read function for the read_last 
		$read = $this->read($param);

		// Format output values
		if (!empty($read['error'])) {
			$result['error'] = $read['error'];
		} else {
			if (!empty($read['values'])) {
				$result['done'] = true;
				// Get only one record
				$result['values'] = current($read['values']);
			} else {
				$result['done'] = false;
			}
		}	
		return $result; 
    }// end function read_last


    /**
     * Function read data
     * @param $param
     * @return mixed
     */
    public function read($param) {
        try {	
			$result = array();
			$result['count'] = 0;
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			// Format the module name
            $module = $this->formatModuleName($param['module']);
			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields'],$module);
			
			// Get the id field label
			$id = $this->getIdField($param, $module);
			// Get modified field label
			$modifiedFieldName = $this->modifiedField[$module];

			// In case we search a specific record, we set a date_ref far in the past to be sure to not filter the result by date
			if (!empty($param['query']['id'])) {
				$param['date_ref'] = '1970-01-01 00:00:00';			
			}
			$result['date_ref'] = $param['date_ref'];			
			// Créer une fonction qui génère l'URL et si la différence entre la date de reference et aujourd'hui > 30 jours alors on fait l'appel sur tous les enregistrements.
			$resultUrl = $this->getUrl($param);	
			$resultCall = $this->call($resultUrl['url'].(!empty($resultUrl['offset']) ? $resultUrl['offset'] : ''));		
			$resultQuery = $this->getresultQuery($resultCall, $resultUrl['url'], $param);

			// If migration mode, we return the offset in date_ref
			if (!empty($resultQuery['date_ref'])) {
				$result['date_ref'] = $resultQuery['date_ref'];
			}
			
			if ($module === "engagements") {
				// Fileter on the right engagement type
				$resultQuery = $this->selectType($resultQuery, $param['module'], false);
				// date ref is managed directly with record date modified for Engagement
				$result['date_ref'] = $param['date_ref'];				
			}

            $resultQuery = $resultQuery['exec'];
            if (
					$module === "companies" 
				 or	$module === "deal"
				 or	$module === "engagements"
			) {
                $identifyProfiles = $resultQuery['results'];
            } else if ($module === "contact") {
                $identifyProfiles = $resultQuery[$param['module']];
            } else if ($module === "deals" || $module === "owners") {
				// if the module is deal_pipeline_stage, we have called the module deal_pipeline and we generate the stage module from ths call
				// A pipeline can have several stages. We format the result to be compatible with the following code
				if ($param['module'] === "deal_pipeline_stage"){
					if (!empty($resultQuery[$param['module']])) {
						// For each pipeline
						foreach ($resultQuery[$param['module']] as $pipeline) {
							if (!empty($pipeline['stages'])) {
								// For each stage
								foreach ($pipeline['stages'] as $stage) {
									$stage['pipelineId'] = $pipeline['pipelineId'];
									$stage[$id] = $pipeline['pipelineId']."_".$stage[$id];
									$identifyProfiles[] = $stage;									
								}
							}
						}
					}					
				} else {
					$identifyProfiles = $resultQuery[$param['module']];				
                }
            }
            // If no result
            if (empty($resultQuery)) {
                $result['error'] = "Request error";
            } else {				
                if (!empty($identifyProfiles)) {										
                    foreach ($identifyProfiles as $identifyProfile) {						
                        $records = null;
                        foreach ($param['fields'] as $field) {
                            $fieldStructure = explode('__', $field);  //si on des fields avec la format metadata__body	
                            // In case of 3 structures, example : metadata__from__email
							if (sizeof($fieldStructure) > 2) {			
								if (isset($identifyProfile[$fieldStructure[0]][$fieldStructure[1]][$fieldStructure[2]])) {
									$records[$field] = $identifyProfile[$fieldStructure[0]][$fieldStructure[1]][$fieldStructure[2]];
								}
							} elseif (sizeof($fieldStructure) > 1) {
                                if (isset($identifyProfile[$fieldStructure[0]][$fieldStructure[1]])) {
									// In case of associations with several entries we take only the first one (example associations__contactIds)
									if (is_array($identifyProfile[$fieldStructure[0]][$fieldStructure[1]])) {
										$records[$field] = current($identifyProfile[$fieldStructure[0]][$fieldStructure[1]]);				
									} else {				
										$records[$field] = $identifyProfile[$fieldStructure[0]][$fieldStructure[1]];
									}
                                }		
                            } else {			
                                if (isset($identifyProfile["properties"][$field])) {
                                    $records[$field] = $identifyProfile["properties"][$field]['value'];
								// The structure is different for the module owner 
								} elseif (
									(	
										$module === "owners"
									 or $param['module'] === "deal_pipeline"	
									 or $param['module'] === "deal_pipeline_stage"	
									)
									and isset($identifyProfile[$field])
								) {
									$records[$field] = $identifyProfile[$field];
                                }
                            }	
							// Hubspot doesn't return empty field but Myddleware need it
							if (!isset($records[$field])) {	
								$records[$field] = '';	
							}	
							
							// Result are different with the engagement module
							if (
									$module === "engagements"
								AND !empty($identifyProfile["engagement"][$id])
							) {
								$records['id'] = $identifyProfile["engagement"][$id];
								if (isset($identifyProfile["engagement"]["properties"][$modifiedFieldName])) {
									$records['date_modified'] = date('Y-m-d H:i:s', $identifyProfile["engagement"]['properties'][$modifiedFieldName]['value'] / 1000);
								} else {
									$records['date_modified'] = date('Y-m-d H:i:s', $identifyProfile["engagement"][$modifiedFieldName] / 1000);
								}
								$result['values'][$identifyProfile["engagement"][$id]] = $records;
								
							} elseif (!empty($identifyProfile[$id])) {								
								$records['id'] = $identifyProfile[$id];
								if (isset($identifyProfile["properties"][$modifiedFieldName])) {
									$records['date_modified'] = date('Y-m-d H:i:s', $identifyProfile['properties'][$modifiedFieldName]['value'] / 1000);
								} elseif(isset($identifyProfile[$modifiedFieldName])) {
									$records['date_modified'] = date('Y-m-d H:i:s', $identifyProfile[$modifiedFieldName] / 1000);
								} else { //deal_pipeline_stage has no reference field
									$records['date_modified'] = date('Y-m-d H:i:s');
								}
								$result['values'][$identifyProfile[$id]] = $records;
							}
							
							// Don't set reference date normal mode, $result['date_ref'] is already set with the offset
							if ($this->migrationMode == false) { 
								// Get the last modified date 
								$dateModified = new \DateTime($records['date_modified']);							
								$dateRef = new \DateTime($result['date_ref']);			
						
								if ($dateModified >= $dateRef) {
									// Add 1 second to the date ref because the call to Hubspot includes the date ref.. Otherwise we will always read the last record
									$dateRef = date_modify($dateModified, '+1 seconde');						
									$result['date_ref'] = $dateRef->format('Y-m-d H:i:s');
								}
							}
                        }
                    }
					if (!empty($result['values'])) {
						$result['count'] = count($result['values']);
					}
                }
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : ' . $e->getMessage() . ' ' . __CLASS__ . ' Line : ( ' . $e->getLine() . ' )';	
        }				
		return $result;
    }// end function read

    /**
     * Function create data
     * @param $param
     * @return mixed
     */

    public function create($param) {
        try {
            // Associate deal is always an update to Hubspot
            if ($param['module'] == 'associate_deal') {
                return $this->update($param);
            }
            // Tranform Myddleware data to Mailchimp data
            foreach ($param['data'] as $idDoc => $data) {
                $dataHubspot["properties"] = null;
                $records = array();

                //formatModuleName contact
                $module = $this->formatModuleName($param['module']);

                if ($module === "companies" || $module === "deal") {
                    $version = $module === "companies" ? "v2" : "v1";
                    $id = $module === "companies" ? "companyId" : "dealId";
                    $url = $this->url . $param['module'] . "/" . $version . "/" . $module . "?hapikey=" . $this->paramConnexion['apikey'];
                    $property = "name";
                } else if ($module === "contact") {
                    $url = $this->url . $param['module'] . "/v1/" . $module . "?hapikey=" . $this->paramConnexion['apikey'];
                    $id = 'vid';
                    $property = "property";
                }
                foreach ($param['data'][$idDoc] as $key => $value) {
                    if (in_array($key, array('target_id', 'Myddleware_element_id'))) {
                        continue;
                    }
                    array_push($records, array($property => $key, "value" => $value));
                }
                $dataHubspot["properties"] = $records;
                $resultQuery = $this->call($url, "POST", $dataHubspot);

                if (isset($resultQuery['exec']['status']) && $resultQuery['exec']['status'] === 'error') {
                    $result[$idDoc] = array(
                        'id' => '-1',
                        'error' => 'Failed to create data in hubspot. ' . (!empty($resultQuery['exec']['validationResults'][0]['message']) ? $resultQuery['exec']['validationResults'][0]['message'] : (!empty($resultQuery['exec']['message']) ? $resultQuery['exec']['message'] : ''))
                    );
                } else {
                    $result[$idDoc] = array(
                        'id' => $resultQuery['exec'][$id],
                        'error' => false
                    );
                }
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $result[$idDoc] = array(
                'id' => '-1',
                'error' => $error
            );
        }
        return $result;
    }// end function create

    /**
     * Function update data
     * @param $param
     * @return mixed
     */
    public function update($param) {
        try {
            $module = $this->formatModuleName($param['module']);
            if ($module === "companies" || $module === "deal") {
                $property = "name";
                $method = 'PUT';
                $version = $module === "companies" ? "v2" : "v1";
            } else if ($module === "contact") {
                $property = "property";
                $method = 'POST';
            }

            // Tranform Myddleware data to hubspot data
            foreach ($param['data'] as $idDoc => $data) {
                $records = array();
                // No properties for module associate_deal
                if ($param['module'] != "associate_deal") {
                    $dataHubspot["properties"] = null;
                    foreach ($param['data'][$idDoc] as $key => $value) {
                        if ($key == 'target_id') {
                            $idProfile = $value;
                            continue;
                        } elseif ($key == 'Myddleware_element_id') {
                            continue;
                        }
                        array_push($records, array($property => $key, "value" => $value));
                    }
                    $dataHubspot["properties"] = $records;
                }

                if ($param['module'] === "associate_deal") {
                    // Id profile is the deal_id. It is possible that we haven't target_id because the update function can be called by the create function
                    $idProfile = $data['deal_id'];
                    $url = $this->url . "deals/" . $version . "/" . $module . "/" . $idProfile . "/associations/" . $data['object_type'] . "?id=" . $data['record_id'] . "&hapikey=" . $this->paramConnexion['apikey'];
                    $dataHubspot = array();
                } elseif ($module === "companies" || $module === "deal") {
                    $url = $this->url . $param['module'] . "/" . $version . "/" . $module . "/" . $idProfile . "?hapikey=" . $this->paramConnexion['apikey'];
                } elseif ($module === "contact") {
                    $url = $this->url . $param['module'] . "/v1/" . $module . "/vid/" . $idProfile . "/profile" . "?hapikey=" . $this->paramConnexion['apikey'];
                } else {
                    throw new \Exception('Module ' . $module . ' unknown.');
                }
                // Call Hubspot
                $resultQuery = $this->call($url, $method, $dataHubspot);
                if (
                    $resultQuery['info']['http_code'] >= 200 // 200 is used to update deals for example
                    AND $resultQuery['info']['http_code'] <= 204 //204 is good
                ) {
                    $result[$idDoc] = array(
                        'id' => $idProfile,
                        'error' => false
                    );
                } else {
                    $result[$idDoc] = array(
                        'id' => '-1',

                        'error' => 'Failed to create data in hubspot. ' . (!empty($resultQuery['exec']['validationResults'][0]['message']) ? $resultQuery['exec']['validationResults'][0]['message'] : (!empty($resultQuery['exec']['message']) ? $resultQuery['exec']['message'] : ''))
                    );
                }
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $result[$idDoc] = array(
                'id' => '-1',
                'error' => $error
            );
        }
        return $result;
    }// end function update


	protected function getUrl($param) {	
		// Format the module name
		$module = $this->formatModuleName($param['module']);
		// Get the version label
		$version = $this->getVersion($param, $module);
		
		// In case we search a specific record
		if (!empty($param['query']['id'])) {
			// Calls can be differents depending on the modules
			switch ($module) {
				case "deal":
					$result['url'] = $this->url . "deals/" . $version . "/" . $module . "/" . $param['query']['id'] . "?hapikey=" . $this->paramConnexion['apikey'];
					break; 
				case "contact":
					$result['url'] = $this->url . "contacts/" . $version . "/" . $module . "/vid/" . $param['query']['id'] . "/profile?hapikey=" . $this->paramConnexion['apikey'];
					break;
				case "deals":
					$result['url'] = $this->url . "deals/" . $version . "/pipelines/" . $param['query']['id'] . "?hapikey=" . $this->paramConnexion['apikey'];
					break;
				default:
					$result['url'] = $this->url . $module . "/" . $version . "/" . $module . "/" . $param['query']['id'] . "?hapikey=" . $this->paramConnexion['apikey'];
			}
			return $result;
		}

		// Module with only one url
		if ($module === "owners") {
			$result['url'] = $this->url . $param['module'] . "/" . $version . "/" . $param['module'] . "?hapikey=" . $this->paramConnexion['apikey'];
		} elseif ($module === "deals") {
			$result['url'] = $this->url . $module . "/" . $version . "/pipelines" . "?hapikey=" . $this->paramConnexion['apikey'];	
		} else {		
			// calculate the difference between date_ref and now
			if (!is_numeric($param['date_ref'])) {
				$now = new DateTime("now");
				$dateRef = new DateTime($param['date_ref']);
				$interval = $dateRef->diff($now);
			}
			
			// ModificationDate or CreationDate
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
			
			$property = "";
			// If date_ref is more than 30 days in the past or if an offset is in the reference
			// We are in migration mode and we will call all records for the module not only the recent ones
			if (
					is_numeric($param['date_ref'])
				 OR empty($param['date_ref'])	// In case the user removed the reference on the rule
				 OR (
						isset($interval)
					AND $interval->format('%a') >= 30 
				)
			) {
				// In case we have more than 30 days, we set offeset to 0 to read all records
				if (
						isset($interval)
					AND $interval->format('%a') >= 30
				) {
					$param['date_ref'] = 0;
					$offset = 0;
				// If the reference is a numeric, it is the offset	
				} else {
					$offset = $param['date_ref'];
				}
				
				// We set migration mode = true to put the offset in the reference date 
				$this->migrationMode = true;
				switch ($module) {
					case (
							$module === "companies" 
						 or	$module === "deal"
						) :
						if (!empty($param['fields'])) { // Add fields in the call
							foreach ($param['fields'] as $fields) {
								$property .= "&properties=" . $fields;
							}
						}
						$result['url'] = $this->url . $param['module'] . "/" . $version . "/" . $module . "/paged" . "?hapikey=" . $this->paramConnexion['apikey'] . $property . '&limit=' . $this->limitCall[$module];
						$result['offset'] = "&offset=" . $offset;						
						break;
					case "contact":
						if (!empty($param['fields'])) {// Add fields in the call
							foreach ($param['fields'] as $fields) {
								$property .= "&property=" . $fields;
							}
						}
						$result['url'] = $this->url . $param['module'] . "/" . $version . "/lists/all/" . $param['module'] . "/all" . "?hapikey=" . $this->paramConnexion['apikey'] . $property . '&count=' . $this->limitCall[$module];
						$result['offset'] = "&vidOffset=" . $offset;
						break;
					case "engagements":
						$result['url'] = $this->url . $module . "/" . $version . "/" . $module . "/paged" . "?hapikey=" . $this->paramConnexion['apikey'] . '&limit=' . $this->limitCall[$module];
						$result['offset'] = "&offset=" . $offset;
						break;
					default:
						throw new \Exception('No API call for search more than 30 days in the past with the module '.$module);
				}
			} else {				
				switch ($module) {
					case (
							$module === "companies" 
						 or	$module === "deal"
						) :
						if (!empty($param['fields'])) { // Add fields in the call
							foreach ($param['fields'] as $fields) {
								$property .= "&properties=" . $fields;
							}
						}
						// Calls are different for creation or modification
						if ($dateRefField === "ModificationDate") {
							$result['url'] = $this->url . $param['module'] . "/" . $version . "/" . $module . "/recent/modified/" . "?hapikey=" . $this->paramConnexion['apikey'] . $property . '&count=' . $this->limitCall[$module] . '&since=' . $dateRef->getTimestamp().'000';
						} else {
							$result['url'] = $this->url . $param['module'] . "/" . $version . "/" . $module . "/recent/created/" . "?hapikey=" . $this->paramConnexion['apikey'] . $property . '&count=' . $this->limitCall[$module];
						}
						break;
					case "contact":
						if (!empty($param['fields'])) { // Add fields in the call
							foreach ($param['fields'] as $fields) {
								$property .= "&property=" . $fields;
							}
						}
						// Calls are different for creation or modification
						if ($dateRefField === "ModificationDate") {
							$result['url'] = $this->url . $param['module'] . "/" . $version . "/lists/recently_updated/" . $param['module'] . "/recent" . "?hapikey=" . $this->paramConnexion['apikey'] . $property . '&count=' . $this->limitCall[$module];
						} else {
							$result['url'] = $this->url . $param['module'] . "/" . $version . "/lists/all/" . $param['module'] . "/recent" . "?hapikey=" . $this->paramConnexion['apikey'] . $property . '&count=' . $this->limitCall[$module];
						}
						break;
					case "engagements":
						$result['url'] = $this->url . $module . "/" . $version . "/" . $module . "/recent/modified" . "?hapikey=" . $this->paramConnexion['apikey'] . '&count=' . $this->limitCall[$module] . '&since=' . $dateRef->getTimestamp().'000';
						break;
					default:
					   throw new \Exception('No API call with the module '.$module);
				}	   
			} 
		}
		return $result;
	}
	
	
    /**
     * Select les engagements+6+
     * 9***.selon le type
     * @param $results
     * @param $module
     * @return array
     */
    public function selectType($results, $module, $first = true) {
        $moduleResult = explode('_', $module);
        $resultFinal = array();
        // Delete all engagement not in the type searched
		foreach ($results["exec"]["results"] as $key => $record) {
            if ($record['engagement']['type'] != strtoupper($moduleResult[1])) {
                unset($results["exec"]["results"][$key]);
			}
        }
        return $results;

    }
		
	// Get version label
	protected function getVersion($param, $module) {
		if (
				$module === "companies"
			 or $module === "owners"	
		) {
			return "v2";
		}
		return "v1";
	}
	
	//Get the id label depending of the module
	protected function getIdField($param, $module) {
		switch ($module) {
			case "companies":
				return "companyId";
				break;
			case "deal":
				return "dealId";
				break;
			case "contact":
				return "vid";
				break;
			case "owners":
				return "ownerId";
				break;
			case "deals":
				if ($param['module'] === "deal_pipeline") {
					return "pipelineId";
				} elseif ($param['module'] === "deal_pipeline_stage"){
					return "stageId";
				} else {
					return 'id';
				}
				break;
			case "engagements":
				return "id";
				break;
			default:
			   return "id";
		}
	}

    /**
     * Function for get data
     * @param $request
     * @param $url
     * @param $param
     * @return array
     *
     */
    protected function getresultQuery($request, $url, $param) {		
		// Module contact
        if (
				$param['module'] == "contacts"
			 OR	$param['module'] == "deals"
		) {
			// In case on the search for a specific record is requested we add a dimension to the result array
			if (!empty($param['query']['id'])) {
				$requestTmp['exec'][$param['module']][0] = $request['exec'];
				$request = $requestTmp;
			}
			// The key of the array return is different depending the module
			// If there is no more data to read
			if (
					$this->readLast == true // Only one call if read_last is requested				
				OR (
						empty($request['exec']['has-more'])
					AND empty($request['exec']['hasMore'])
				) 
			) {				
				$keyResult = (isset($request['exec'][$param['module']]) ? $param['module'] : 'results');
                $result = $this->getresultQueryBydate($request['exec'][$keyResult], $param, false);
            // If we have to make several calls to read all the data
			} else {
				// Get the offset contact id or deal id
                $offset = ($param['module'] == "contacts" ? $request['exec']['vid-offset'] : $request['exec']['offset']);					
				$keyResult = (isset($request['exec'][$param['module']]) ? $param['module'] : 'results');				
                $result = $this->getresultQueryBydate($request['exec'][$keyResult], $param, false);			
                do {
                    // Call the next page
					$offsetStr = ($param['module'] == "contacts" ? "&vidOffset=" . $offset : "&offset=" . $offset);				
					$resultOffset = $this->call($url . $offsetStr);				
					$keyResultOffset = (isset($resultOffset['exec'][$param['module']]) ? $param['module'] : 'results');
                    // $timeOffset = $resultOffset['exec']['time-offset'];
                    $offset = ($param['module'] == "contacts" ? $resultOffset['exec']['vid-offset'] : $resultOffset['exec']['offset']);
					// Format results
                    $resultOffsetTemps = $this->getresultQueryBydate($resultOffset['exec'][$keyResultOffset], $param, true);

                    // Add result to the main array
					$keyResult = (isset($result['exec'][$param['module']]) ? $param['module'] : 'results');	
					$merge = array_merge($result['exec'][$keyResult], $resultOffsetTemps);
				
                    $result['exec']['results'] = $merge;
			
				// Call again only if we haven't reached the reference date
                } while (
						!empty($resultOffsetTemps)	// Date_ref has been reached (no result in getresultQueryBydate)
					AND	(	!empty($resultOffset['exec']['hasMore']) // No more data to read
						 OR	!empty($resultOffset['exec']['has-more'])
						)
					AND	count($result['exec'][$keyResult]) < ($param['limit'] - 1) // Stop if we reach the limit (-1 see method rule->setLimit)  set in the rule	 	
				);
            }
		// Module Company or Engagement	
        } elseif (
					$param['module'] === "companies"
				 or substr($param['module'],0,10) === "engagement"	
		) {	
			// The response key can be different depending the API call
			if ($param['module'] === "companies") {
				if (strpos($url,'paged')!==false) {
					$key = $param['module'];
				} else {
					$key = 'results';
				}
			} else {
				$key = 'results';
			}
			// In case on the search for a specific record is requested we add a dimension to the result array
			if (!empty($param['query']['id'])) {
				$requestTmp['exec'][$key][0] = $request['exec'];
				$request = $requestTmp;
			}	
				
			// If there is no more data to read	
            if (
				(
						empty($request['exec']['hasMore'])  // Engagement module
					and empty($request['exec']['has-more']) // Company module
				)
				or $this->readLast == true  // Only one call if read_last is requested
			) {				
                $result = $this->getresultQueryBydate($request['exec'][$key], $param, false);
            } else {			
				// If we have to call the API several times
                $offset = $request['exec']['offset'];
                $result = $this->getresultQueryBydate($request['exec'][$key], $param, false);
                do {					
                    $resultOffset = $this->call($url . "&offset=" . $offset);
					if (!empty($resultOffset)) {
						// Check if error
						if (
								!empty($resultOffset['exec']['status']) 
							AND $resultOffset['exec']['status'] == 'error'
						) {						
							 throw new \Exception($resultOffset['exec']['message']);
						}
				
						$offset = $resultOffset['exec']['offset'];
						$resultOffsetTemps = $this->getresultQueryBydate($resultOffset['exec'][$key], $param, true);
						$merge = array_merge($result['exec']['results'], $resultOffsetTemps);
						$result['exec']['results'] = $merge;
					}
                } while (
						!empty($resultOffsetTemps)	// Date_ref has been reached (no result in getresultQueryBydate)
					AND	(	!empty($resultOffset['exec']['hasMore']) // No more data to read
						 OR	!empty($resultOffset['exec']['has-more'])
						)
					AND	count($result['exec']['results']) < ($param['limit'] - 1) // Stop if we reach the limit (-1 see method rule->setLimit)  set in the rule	 	
				);
            }
        } else {
			// In case on the search for a specific record is requested we add a dimension to the result array
			if (!empty($param['query']['id'])) {
				if (
						$param['module'] === "owners"
					 or $param['module'] === "deal_pipeline"		
				) {	
					$requestTmp['exec'][0] = $request['exec'];
					$request = $requestTmp;
				} else {	
					$requestTmp['exec'][$param['module']][0] = $request['exec'];
					$request = $requestTmp;
				}
			}			
            $result = $this->getresultQueryBydate($request['exec'], $param, false);		
        }	
	
		// If we have read all records we set the migration mode to false and let the read function set the reference date thanks to the default value '1970-01-01 00:00:00'
		if (
				empty($result['exec']['results']) // We can have another index than result, with deal_pipeline for example 
			 OR	count($result['exec']['results']) < ($param['limit'] - 1) // (-1 see method rule->setLimit)
		) { 
			$this->migrationMode = false;
			$result['date_ref'] = '1970-01-01 00:00:00';
		// If there is still data to read, we set the offset in the result date ref
		} else {
			if (!empty($offset)) {
				$result['date_ref'] = $offset;
			}
			// We set again migrationMode = true because a rule could have a reference date less than 30 days in a past and reach the rule limit.
			// In this case we use the offset as a reference not the date
			$this->migrationMode = true;
		}		
        return $result;
    }


    /**
     * Function for get data with date_ref
     * @param $request
     * @param $url
     * @param $param
     * @return array
     *
     */
    protected function getresultQueryBydate($request, $param, $offset) {
        $param['module'] === "deals" || $param['module'] === "companies" ? $modified = "hs_lastmodifieddate" : $modified = "lastmodifieddate";
        if ($param['module'] === "owners") {
            $modified = "updatedAt";
        } else if (
				$param['module'] === "engagement_call" 
			 or $param['module'] === "engagement_task" 
			 or $param['module'] === "engagement_note" 
			 or $param['module'] === "engagement_meeting" 
			 or $param['module'] === "engagement_email") {
            $modified = "lastUpdated";
        } else if ($param['module'] === "deal_pipeline") {
            $modified = "updatedAt";
        }		
        if (!$offset) {
            if (
					$param['module'] === "deals" 
				 or $param['module'] === "companies"
				 or substr($param['module'],0,10) === "engagement"	
			) {
                $module = 'results';
                $result['exec'][$module] = [];
            } else {
                $module = $param['module'];
                $result['exec'][$module] = [];
            }
        } else {
            $result = [];
        }
		// If migration mode, we take all records so we set timestamp to 0
		if ($this->migrationMode) {
			$dateTimestamp = 0;
		} else {
			$dateTimestamp = $this->dateTimeToTimestamp($param["date_ref"]);
		}
		// Init the reference with the current date_ref
		// $result['date_ref'] = $dateTimestamp;
        if (
				$param['module'] === "engagement_call" 
			 or	$param['module'] === "engagement_task" 
			 or	$param['module'] === "engagement_meeting" 
			 or	$param['module'] === "engagement_note" 
			 or $param['module'] === "engagement_email"
		) {
            if (!empty($request)) {				
                foreach ($request as $key => $item) {
                    if ($item['engagement'][$modified] > $dateTimestamp) {
                        if (!$offset) {							
                            // array_push($result['exec'][$param['module']], $item);
                            array_push($result['exec']['results'], $item);
                        } else {							
                            array_push($result, $item);
                        }
                    }
                }
            }
        } elseif (
				$param['module'] === "deal_pipeline"
			 or	$param['module'] === "deal_pipeline_stage"
		) {					
            if (!empty($request)) {		
				// For pipeline, we read all data
                foreach ($request as $key => $item) {
					if (!$offset) {
						array_push($result['exec'][$module], $item);
					} else {
						array_push($result, $item);
					}
                }
            }
        } elseif ($param['module'] === "owners") {			
            if (!empty($request)) {
                foreach ($request as $key => $item) {
                    if ($item[$modified] > $dateTimestamp) {
                        if (!$offset) {
                            array_push($result['exec'][$module], $item);
                        } else {
                            array_push($result, $item);
                        }
                    }
                }
            }
        } else {
            if (!empty($request)) {
				// An entry result exists for the module deals
				if (
						$param['module'] === "deals"
					AND (
							isset($request['deals'])	
						 OR	isset($request['results'])
					)
				) {					
					// The response key can be different : deals for deal/paged/ and result for recent/modified/
					$request = (isset($request['deals']) ? $request['deals'] : $request['results']);
				}

                foreach ($request as $key => $item) {
                    if ($item['properties'][$modified]['value'] > $dateTimestamp) {
                        if (!$offset) {
                            array_push($result['exec'][$module], $item);
                        } else {
                            array_push($result, $item);
                        }
                    }
                }
            }
        }				
        return $result;
    }

// Function de conversion de datetime format solution à un datetime format Myddleware
    protected function dateTimeToTimestamp($dateTime) {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
        return $date->getTimestamp() * 1000;
    }// dateTimeToMyddleware($dateTime)

    /**
     * return the reference date field name
     * @param $moduleSource
     * @param $RuleMode
     * @return null|string
     * @throws \Exception
     */
    public function getDateRefName($moduleSource, $RuleMode) {
        // Creation and modification mode
        if(in_array($RuleMode,array("0","S"))) {
            return "ModificationDate";
            // Creation mode only
        } else if ($RuleMode == "C") {
            return "CreationDate";
        } else {
            throw new \Exception ("$RuleMode is not a correct Rule mode.");
        }
        return null;
    }

    /**
     * get singular of module
     * @param $name
     * @return string
     */
    public function formatModuleName($name) {
        if ($name === "contacts") {
            return "contact";
        } else if ($name === "companies") {
            return "companies";
        } else if ($name === "deals" OR $name === "associate_deal") {
            return "deal";
        } else if ($name === "owners") {
            return "owners";
        } else if (
				$name === "deal_pipeline"
			 or	$name === "deal_pipeline_stage"
		) {
            return "deals";
        } else if (
				$name === "engagement_call" 
			 or	$name === "engagement_task" 
			 or $name === "engagement_email" 
			 or $name === "engagement_note"
			 or $name === "engagement_meeting"
		) {
            return "engagements";
        }
    }

    /**
     * Performs the underlying HTTP request. Not very exciting
     * @param  string $method The API method to be called
     * @param  array $args Assoc array of parameters to be passed
     * @return array          Assoc array of decoded result
     */
    protected function call($url, $method = 'GET', $args = array(), $timeout = 120) {
		if (!function_exists('curl_init') OR !function_exists('curl_setopt')) {
			throw new \Exception('curl extension is missing!');
		}
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		$headers = array();
		$headers[] = "Content-Type: application/json";
		if (!empty($this->token)) {
			$headers[] = "Authorization: Bearer " . $this->token;
		}
		if (!empty($args)) {
			$jsonArgs = json_encode($args);

			$headers[] = "Content-Lenght: " . $jsonArgs;
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonArgs);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($ch);
		$resultCurl['exec'] = json_decode($result, true);
		$resultCurl['info'] = curl_getinfo($ch);
		curl_close($ch);
		return $resultCurl;
    }
}

/* * * * * * * *  * * * * * *  * * * * * *
    si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__ . '/../Custom/Solutions/hubspot.php';
if (file_exists($file)) {
    require_once($file);
} else {
    //Sinon on met la classe suivante
    class hubspot extends hubspotcore
    {

    }
}