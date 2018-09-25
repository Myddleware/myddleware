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

class hubspotcore extends solution
{

    protected $url = 'https://api.hubapi.com/';
    protected $version = 'v1';

    protected $FieldsDuplicate = array(
        'contacts' => array('email'),
    );

	protected $modifiedField = array(
									'companies' 	=> 'hs_lastmodifieddate',
									'deal' 			=> 'hs_lastmodifieddate',
									'contact' 		=> 'lastmodifieddate',
									'owners' 		=> 'updatedAt',
									'deals' 		=> 'updatedAt',
									'engagements' 	=> 'lastUpdated'
								);
								
	protected $defaultLimit = array(
									'companies' => 100,  // 250 max
									'deal' => 100,  // 250 max
									'contact' => 20, // 100 max
									'engagements' => 10, // 100 max
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
            'engagement_task' => 'Engagement Task',
            'engagement_call' => 'Engagement Call',
            'engagement_email' => 'Engagement Email',
            'engagement_meeting' => 'Engagement Meeting',
            'engagement_note' => 'Engagement Note',
        );

        // Module to create relationship between deals and contacts/companies
        if ($type == 'target') {
            $modules['associate_deal'] = 'Associate deals with companies/contacts';
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
        try {		
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
            $module = $this->formatModuleName($param['module']);
		
            if (!empty($param['fields'])) { //add properties for request
                $property = "";
				$version = $this->getVersion($param, $module);
				// Property label is different for contacts
				$properties = ( $module === "contact" ? "property" : "properties" );
				// Get the id field label
				$id = $this->getIdField($param, $module);
				
                // Get the reference date field name
                if ($module === "companies" || $module === "deal") {
                    $property .= "&" . $properties. "=hs_lastmodifieddate";
                } else if ($module === "contact") {
                    $property .= "&" . $properties. "=lastmodifieddate";
                } 
                foreach ($param['fields'] as $fields) {
                    $property .= "&" . $properties . "=" . $fields;
                }
            }

            if (!empty($param['query'])) {
                if (!empty($param['query']['email'])) {
                    $resultQuery = $this->call($this->url . $param['module'] . "/" . $version . "/" . $module . "/email/" . $param['query']['email'] . "/profile?hapikey=" . $this->paramConnexion['apikey'] . $property);
                } elseif (!empty($param['query']['id'])) {
                    if ($module === "companies" || $module === "deal") {
                        $url_id = $this->url . $param['module'] . "/" . $version . "/" . $module . "/" . $param['query']['id'] . "?hapikey=" . $this->paramConnexion['apikey'] . "&count=1" . $property;
                    } else if ($module === "contact") {
                        $url_id = $this->url . $param['module'] . "/" . $version . "/" . $module . "/vid/" . $param['query']['id'] . "/profile?hapikey=" . $this->paramConnexion['apikey'] . $property;
                    }
                    $resultQuery = $this->call($url_id);
                } else {
                    //@todo  get word for request
                    $resultQuery = $this->call($this->url . $param['module'] . "/" . $version . "/search/query?q=hubspot" . "&count=1&hapikey=" . $this->paramConnexion['apikey'] . $property);
                }
                $identifyProfiles = $resultQuery['exec']['properties'];
                $identifyProfilesId = $resultQuery['exec'][$id];

            } else {
                if ($module === "companies" || $module === "deal") {
                    $url = $this->url . $param['module'] . "/" . $version . "/" . $module . "/paged?hapikey=" . $this->paramConnexion['apikey'] . "&count=1" . $property;
                    $resultQuery = $this->call($url);
                } else if ($module === "contact") {
                    $url = $this->url . $param['module'] . "/v1/lists/all/" . $param['module'] . "/all?hapikey=" . $this->paramConnexion['apikey'] . "&count=1" . $property;
                    $resultQuery = $this->call($url);
                } else if ($module === "owners") {
                    $url = $this->url . $param['module'] . "/" . $version . "/" . $module . "?hapikey=" . $this->paramConnexion['apikey'];
                    $resultQuery = $this->call($url);
                } else if ($module === "deals") {
                    $url = $this->url . $module . "/" . $version . "/pipelines" . "?hapikey=" . $this->paramConnexion['apikey'];
                    $resultQuery = $this->call($url);
                } else if ($module === "engagements") {
                    $url = $this->url . $module . "/" . $version . "/" . $module . "/paged?hapikey=" . $this->paramConnexion['apikey'];
                    $resultQuery = $this->call($url);
                    $resultQuery = $this->selectType($resultQuery, [$param['module']][0], true);
                }		

                if ($module === "engagements") {
                    $identifyProfilesId = $resultQuery['exec']['engagement'][0][$id];
                    $identifyProfiles = $resultQuery['exec']['engagement'][0];
				// if the module is deal_pipeline_stage, we have called the module deal_pipeline and we generate the stage module from ths call
				// A pipeline can have several stages. We format the result to be compatible with the following code
				} elseif ($param['module'] === "deal_pipeline_stage") {
					$identifyProfilesId = $resultQuery["exec"][0]['stages'][0][$id];
                    $identifyProfiles = $resultQuery["exec"][0]['stages'][0];
				} elseif (
						$module === "deals" 
					 or $module === "owners"
				) {
                    $identifyProfilesId = $resultQuery["exec"][0][$id];
                    $identifyProfiles = $resultQuery["exec"][0];
                } else {
                    $identifyProfilesId = $resultQuery['exec'][$param['module']][0][$id];
                    $identifyProfiles = $resultQuery['exec'][$param['module']][0];
				}
            }
						
            // If no result
            if (empty($resultQuery)) {
                $result['done'] = false;
            } else {
                foreach ($param['fields'] as $field) {
                    $fieldStructure = explode('__', $field);  //si on des fields avec la format metadata__body
					// In case of 3 structures, example : metadata__from__email
					if (sizeof($fieldStructure) > 2) {							
						$result['values'][$field] = $identifyProfiles[$fieldStructure[0]][$fieldStructure[1]][$fieldStructure[2]];		
					// In case of 2 structures, example : metadata__status
					} elseif (sizeof($fieldStructure) > 1) {
						if (isset($identifyProfiles[$fieldStructure[0]][$fieldStructure[1]])) {
							// In case of associations with several entries we take only the first one (example associations__contactIds)
							if (is_array($identifyProfiles[$fieldStructure[0]][$fieldStructure[1]])) {
								$result['values'][$field] = current($identifyProfiles[$fieldStructure[0]][$fieldStructure[1]]);				
							} else {				
								$result['values'][$field] = $identifyProfiles[$fieldStructure[0]][$fieldStructure[1]];
							}
						}	
					// For simple field
					} else {			
						if (isset($identifyProfiles["properties"][$field])) {
							$result['values'][$field] = $identifyProfiles["properties"] [$field]['value'];
						// The structure is different for the module owner 
						} elseif (
							(	$module === "owners"
							 or $param['module'] === "deal_pipeline"	
							 or $param['module'] === "deal_pipeline_stage"	
							)
							and isset($identifyProfiles[$field])
						) {
							$result['values'][$field] = $identifyProfiles[$field];
						} else { // Hubspot doesn't return empty field but Myddleware need it
							$result['values'][$field] = '';	
						}
					}
                }
                if (!empty($result['values'])) {
                    $result['done'] = true;
                }
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : ' . $e->getMessage() . ' ' . __CLASS__ . ' Line : ( ' . $e->getLine() . ' )';
            $result['done'] = -1;
        }		
        return $result;
    }// end function read_last

	protected function getUrl($param) {
		// Format the module name
		$module = $this->formatModuleName($param['module']);
		// Get the version label
		$version = $this->getVersion($param, $module);
		
		// Module with only one url
		if ($module === "owners") {
			$url = $this->url . $param['module'] . "/" . $version . "/" . $param['module'] . "?hapikey=" . $this->paramConnexion['apikey'];
		} else {
// print_r($param);		
			// calculate the difference between date_ref and now
			$now = new DateTime("now");
			$dateRef = new DateTime($param['date_ref']);
			$interval = $dateRef->diff($now);
	// echo $interval->format('%a');

			// Get default limit 
			if (!empty($this->defaultLimit[$module])) {
				$limit = $this->defaultLimit[$module];
			} elseif (!empty($param['limit'])) {
				$limit = $param['limit'];
			}
			// ModificationDate or CreationDate
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
			
			// If date_ref is more than 30 days in the past, we will call all records for the module not only the recent ones
			if ($interval->format('%a') >= 30 ) {
				// $property = '&count=' . $limit;
				$property = '&limit=' . $limit;
				switch ($module) {
					case "companies":
						// return "companyId";
						break;
					case "deal":
						// return "dealId";
						break;
					case "contact":
						// return "vid";
						break;
					case "owners":
						// return "ownerId";
						break;
					case "deals":
						// if ($param['module'] === "deal_pipeline") {
							// return "pipelineId";
						// } elseif ($param['module'] === "deal_pipeline_stage"){
							// return "stageId";
						// } else {
							// return 'id';
						// }
						break;
					case "engagements":
						$url = $this->url . $module . "/" . $version . "/" . $module . "/paged" . "?hapikey=" . $this->paramConnexion['apikey'] . $property;
						break;
					default:
					   // return "id";
				}
			} else {
				// Get the reference date field name			
				$property = "";
				if ($module === "companies" || $module === "deal") {
					if (!empty($param['fields'])) { //add properties for request
						foreach ($param['fields'] as $fields) {
							$property .= "&properties=" . $fields;
						}
						$property .= "&properties=hs_lastmodifieddate";
					}
					$property .= '&count=' . $limit;
					if ($dateRefField === "ModificationDate") {
						$url = $this->url . $param['module'] . "/" . $version . "/" . $module . "/recent/modified/" . "?hapikey=" . $this->paramConnexion['apikey'] . $property;
					} else {
						$url = $this->url . $param['module'] . "/" . $version . "/" . $module . "/recent/created/" . "?hapikey=" . $this->paramConnexion['apikey'] . $property;
					}
				} else if ($module === "contact") {
					if (!empty($param['fields'])) { //add properties for request
						foreach ($param['fields'] as $fields) {
							$property .= "&property=" . $fields;
						}
						$property .= "&property=lastmodifieddate";
					}
					$property .= '&count=' . $limit;
					if ($dateRefField === "ModificationDate") {
						$url = $this->url . $param['module'] . "/" . $version . "/lists/recently_updated/" . $param['module'] . "/recent" . "?hapikey=" . $this->paramConnexion['apikey'] . $property;
					} else {
						$url = $this->url . $param['module'] . "/" . $version . "/lists/all/" . $param['module'] . "/recent" . "?hapikey=" . $this->paramConnexion['apikey'] . $property;
					}
				} else if ($module === "deals") {
					$url = $this->url . $module . "/" . $version . "/pipelines" . "?hapikey=" . $this->paramConnexion['apikey'];
				} else if ($module === "engagements") {
					$property .= '&count=' . $limit;
					$url = $this->url . $module . "/" . $version . "/" . $module . "/recent/modified" . "?hapikey=" . $this->paramConnexion['apikey'] . $property;
				}	
			}
		}
// echo $url.chr(10);		
// die();
		return $url;
	}
	
    /**
     * Function read data
     * @param $param
     * @return mixed
     */
    public function read($param) {
        try {
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			// Format the module name
            $module = $this->formatModuleName($param['module']);
			
			// Get the id field label
			$id = $this->getIdField($param, $module);
			// Get modified field label
			$modifiedFieldName = $this->modifiedField[$module];
			
			
			// Créer une fonction qui génère l'URL et si la différence entre la date de reference et aujourd'hui > 30 jours alors on fait l'appel sur tous les enregistrements.
			$url = $this->getUrl($param);

            // if ($dateRefField === "ModificationDate") {
                // $resultCall = $this->call($url_modified);
                $resultCall = $this->call($url);
// echo $url.chr(10);				
                $resultQuery = $this->getresultQuery($resultCall, $url, $param, $modifiedFieldName);
                if ($module === "engagements") {
                    // Fileter on the right engagement type
                    $resultQuery = $this->selectType($resultQuery, $param['module'], false);						
                }
            // } else if ($dateRefField === "CreationDate") {
                // $resultCall = $this->call($ur_created);
                // $resultQuery = $this->getresultQuery($resultCall, $ur_created, $param, $modifiedFieldName);
            // }

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

            $result = array();
            // If no result
            if (empty($resultQuery)) {
                $result['error'] = "Request error";
            } else {
// print_r($identifyProfiles);
// print_r(current($identifyProfiles));
// die();				
                if (!empty($identifyProfiles)) {
                    if ($module === "engagements") {
                        // First record is the more recent
                        $timestampLastmodified = current($identifyProfiles)["engagement"][$modifiedFieldName];						
                    } elseif ($module === "owners") {
                        $timestampLastmodified = current($identifyProfiles)[$modifiedFieldName];
                    // No date for module deal_pipeline
					} elseif (	
							$param['module'] === "deal_pipeline"
						or	$param['module'] === "deal_pipeline_stage") {
                        $timestampLastmodified = 0;
                    } else {
                        // First record is the more recent
                        $timestampLastmodified = current($identifyProfiles)["properties"][$modifiedFieldName]["value"];
                    }
						
                    // Add 1 second to the date ref because the call to Hubspot includes the date ref.. Otherwise we will always read the last record
                    $result['date_ref'] = date('Y-m-d H:i:s', ($timestampLastmodified / 1000) + 1);						
                    foreach ($identifyProfiles as $identifyProfile) {						
                        $records = null;
                        foreach ($param['fields'] as $field) {
                            $fieldStructure = explode('__', $field);  //si on des fields avec la format metadata__body	
                            // In case of 3 structures, example : metadata__from__email
							if (sizeof($fieldStructure) > 2) {							
								$records[$field] = $identifyProfile[$fieldStructure[0]][$fieldStructure[1]][$fieldStructure[2]];							
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
                                    $records[$field] = $identifyProfile["properties"] [$field]['value'];
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
                                } else { // Hubspot doesn't return empty field but Myddleware need it
									 $records[$field] = '';	
								}
                            }								
							// Format date modified
                            $records['date_modified'] = date('Y-m-d H:i:s', $timestampLastmodified / 1000); // add date modified
                            if ($module === "engagements") {
                                $records['id'] = $identifyProfile["engagement"][$id];
                                $result['values'][$identifyProfile["engagement"][$id]] = $records;
                            } else {								
                                $records['id'] = $identifyProfile[$id];
                                $result['values'][$identifyProfile[$id]] = $records;
                            }
                            $result['count'] = count($result['values']);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : ' . $e->getMessage() . ' ' . __CLASS__ . ' Line : ( ' . $e->getLine() . ' )';
        }	
// print_r($result);		
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
    protected function getresultQuery($request, $url, $param, $modifiedFieldName) {		
		// Module contact
        if ($param['module'] === "contacts") {
			// If there is no more data to read
            if (empty($request['exec']['has-more'])) {
                $result = $this->getresultQueryBydate($request['exec'][$param['module']], $param, false);
            // If we have to make several calls to read all the data
			} else {
				// Get the offset contact id
                $vidOffset = $request['exec']['vid-offset'];
                $result = $this->getresultQueryBydate($request['exec'][$param['module']], $param, false);
                do {
                    // Call the next page
					$resultOffset = $this->call($url . "&vidOffset=" . $vidOffset);
                    $timeOffset = $resultOffset['exec']['time-offset'];
                    $vidOffset = $resultOffset['exec']['vid-offset'];	
					// Format results
                    $resultOffsetTemps = $this->getresultQueryBydate($resultOffset['exec'][$param['module']], $param, true);
                    // Add result to the main array
					$merge = array_merge($result['exec'][$param['module']], $resultOffsetTemps);
                    $result['exec'][$param['module']] = $merge;
				// Call again only if we haven't reached the reference date
                } while ($timeOffset > $this->dateTimeToTimestamp($param["date_ref"]));
            }
		// Module Company or Engagement	
        } elseif (
					$param['module'] === "companies"
				 or substr($param['module'],0,10) === "engagement"	
		) {
// echo '0'.chr(10);
// print_r($request);
// die();			
            // if ($request['exec']['offset'] === $request['exec']['total']) {
			// If there is no more data to read	
            if (
					empty($request['exec']['hasMore'])  // Engagement module
				and empty($request['exec']['has-more']) // Company module
			) {			
                $result = $this->getresultQueryBydate($request['exec']['results'], $param, false);
            } else {
				// If we have to call the API several times
                $offset = $request['exec']['offset'];
                // $total = $request['exec']['total'];
                $result = $this->getresultQueryBydate($request['exec']['results'], $param, false);
                do {				
                    $resultOffset = $this->call($url . "&offset=" . $offset);
                    $offset = $resultOffset['exec']['offset'];
                    // $resultOffsetTemps = $this->getresultQueryBydate($resultOffset['exec']['results'], $param, true);
                    $resultOffsetTemps = $this->getresultQueryBydate($resultOffset['exec']['results'], $param, true);
// print_r($resultOffset);
// print_r($resultOffsetTemps);
// print_r($resultOffset);
// echo 'A'.chr(10);
					$merge = array_merge($result['exec']['results'], $resultOffsetTemps);
					$result['exec']['results'] = $merge;
                // } while ($offset !== $total);
                } while (
						!empty($resultOffset['exec']['hasMore'])
					 or	!empty($resultOffset['exec']['has-more'])
				);
            }
        } else {
            $result = $this->getresultQueryBydate($request['exec'], $param, false);
        }
// echo '<pre>';
// print_r($result);
// die();				
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
// print_r($param);
// die();		
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
        $dateTimestamp = $this->dateTimeToTimestamp($param["date_ref"]);
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
// echo 'A'.chr(10);							
                            // array_push($result['exec'][$param['module']], $item);
                            array_push($result['exec']['results'], $item);
                        } else {
// echo 'B'.chr(10);							
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
				if ($param['module'] === "deals") {
					$request = $request['results'];
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
// print_r($result);		
// die();		
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
        if ($RuleMode == "0") {
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
        try {
            if (function_exists('curl_init') && function_exists('curl_setopt')) {
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
        } catch (\Exception $e) {
            throw new \Exception('curl extension is missing!');
        }
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