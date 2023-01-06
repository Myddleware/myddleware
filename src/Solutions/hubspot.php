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

namespace App\Solutions;

use Datetime;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class hubspotcore extends solution
{
    protected string $url = 'https://api.hubapi.com/';
    protected string $version = 'v1';
    protected bool $readLast = false;
    protected bool $migrationMode = false;

    protected array $FieldsDuplicate = [
        // 'contacts' => array('email'), // No duplicate search for now
    ];

    // Requiered fields for each modules
    protected array $required_fields = [
        'companies' => ['hs_lastmodifieddate'],
        'deal' => ['hs_lastmodifieddate'],
        'contact' => ['lastmodifieddate'],
        'owners' => ['updatedAt'],
        'deals' => ['updatedAt'],
        'engagements' => ['lastUpdated'],
        'products' => ['objectId'],
        'line_items' => ['objectId'],
    ];

    // Name of reference fields for each module
    protected array $modifiedField = [
        'companies' => 'hs_lastmodifieddate',
        'deal' => 'hs_lastmodifieddate',
        'contact' => 'lastmodifieddate',
        'owners' => 'updatedAt',
        'deals' => 'updatedAt',
        'engagements' => 'lastUpdated',
        'products' => 'date_modified',
        'line_items' => 'date_modified',
    ];

    protected array $limitCall = [
        'companies' => 100,  // 100 max
        'deal' => 100,  // 100 max
        'contact' => 100, // 100 max
        'engagements' => 100, // 100 max
    ];

    protected array $objectModule = [
        'products' => ['properties' => ['name', 'price']],
        'line_items' => ['properties' => ['name', 'price', 'quantity', 'hs_product_id']],
    ];

    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'apikey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.apikey',
            ],
        ];
    }

    // Connect to Hubspot
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            $result = $this->call($this->url.'properties/'.$this->version.'/contacts/properties?hapikey='.$this->paramConnexion['apikey']);
            if (!empty($result['exec']['message'])) {
                throw new \Exception($result['exec']['message']);
            } elseif (empty($result)) {
                throw new \Exception('Failed to connect but no error returned by Hubspot. ');
            }
            $this->connexion_valide = true;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }


    public function get_modules($type = 'source'): array
    {
        $modules = [
            'companies' => 'Companies',
            'contacts' => 'Contacts',
            'deals' => 'Deals',
            'owners' => 'Owners',
            'deal_pipeline' => 'Deal pipeline',
            'deal_pipeline_stage' => 'Deal pipeline stage',
            'engagement_note' => 'Engagement Note',
        ];

        // Module to create relationship between deals and contacts/companies
        if ('target' == $type) {
            $modules['associate_deal'] = 'Associate deals with companies/contacts';
        } elseif ('source' == $type) {
            $modules['associate_deal_contact'] = 'Associate deals with contacts';
            $modules['associate_deal_company'] = 'Associate deals with contacts';
        }
        // Module only available in source
        if ('source' == $type) {
            $modules['engagement_task'] = 'Engagement Task';
            $modules['engagement_call'] = 'Engagement Call';
            $modules['engagement_email'] = 'Engagement Email';
            $modules['engagement_meeting'] = 'Engagement Meeting';
            $modules['products'] = 'Products';
            $modules['line_items'] = 'Line items';
        }

        return $modules;
    }

    // Renvoie les champs du module passé en paramètre
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            $engagement = 'engagement' === explode('_', $module)[0] ? true : false;
            $engagement_module = explode('_', $module);

            // Manage custom module to deal with associate_deal
            if ('associate_deal' == $module) {
                $result = [
                    ['name' => 'deal_id', 'label' => 'Deal Id', 'type' => 'varchar(36)'],
                    ['name' => 'record_id', 'label' => 'Contact or company ID', 'type' => 'varchar(36)'],
                    ['name' => 'object_type', 'label' => 'Object Type', 'type' => 'varchar(36)', 'options' => [['value' => 'CONTACT', 'label' => 'Contact'], ['value' => 'COMPANY', 'label' => 'Company']]],
                ];
            } elseif ('owners' === $module) {
                $result = [
                    ['name' => 'portalId', 'label' => 'portal Id', 'type' => 'varchar(36)'],
                    ['name' => 'Type', 'label' => 'Type', 'type' => 'varchar(36)'],
                    ['name' => 'firstName', 'label' => 'Firstname', 'type' => 'varchar(255)'],
                    ['name' => 'lastName', 'label' => 'Lastname', 'type' => 'varchar(255)'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'varchar(255)'],
                    ['name' => 'created_at', 'label' => 'Created at', 'type' => 'varchar(36)'],
                    ['name' => 'updated_at', 'label' => 'Updated at', 'type' => 'varchar(36)'],
                    ['name' => 'remoteList__portalId', 'label' => 'RemoteList portal Id', 'type' => 'varchar(36)'],
                    ['name' => 'remoteList__ownerId', 'label' => 'RemoteList owner Id', 'type' => 'varchar(36)'],
                    ['name' => 'remoteList__remoteId', 'label' => 'RemoteList remote Id', 'type' => 'varchar(36)'],
                    ['name' => 'remoteList__remoteType', 'label' => 'RemoteList remote Type', 'type' => 'varchar(36)'],
                    ['name' => 'remoteList__active', 'label' => 'RemoteList active', 'type' => 'varchar(36)'],
                ];
            } elseif ('deal_pipeline' === $module) {
                $result = [
                    ['name' => 'active', 'label' => 'Active', 'type' => 'varchar(1)'],
                    ['name' => 'label', 'label' => 'Label', 'type' => 'varchar(255)'],
                    ['name' => 'pipelineId', 'label' => 'Pipeline Id', 'type' => 'varchar(36)'],
                ];
            } elseif ('deal_pipeline_stage' === $module) {
                $result = [
                    ['name' => 'id', 'label' => 'Id', 'type' => 'varchar(36)'],
                    ['name' => 'pipelineId', 'label' => 'Pipeline Id', 'type' => 'varchar(36)'],
                    ['name' => 'active', 'label' => 'Active', 'type' => 'varchar(1)'],
                    ['name' => 'closedWon', 'label' => 'closedWon', 'type' => 'varchar(255)'],
                    ['name' => 'displayOrder', 'label' => 'DisplayOrder', 'type' => 'varchar(255)'],
                    ['name' => 'label', 'label' => 'Label', 'type' => 'varchar(255)'],
                    ['name' => 'probability', 'label' => 'Probability', 'type' => 'varchar(255)'],
                ];
            } elseif ('products' === $module) {
                $result = [
                    ['name' => 'objectId', 'label' => 'Id', 'type' => 'varchar(36)'],
                    ['name' => 'objectType', 'label' => 'Object Type', 'type' => 'varchar(36)'],
                    ['name' => 'portalId', 'label' => 'Portal Id', 'type' => 'varchar(1)'],
                    ['name' => 'properties__price__value', 'label' => 'Price', 'type' => 'varchar(255)'],
                    ['name' => 'properties__name__value', 'label' => 'Name', 'type' => 'varchar(255)'],
                    ['name' => 'isDeleted', 'label' => 'Is deleted', 'type' => 'varchar(255)'],
                ];
            } elseif ('line_items' === $module) {
                $result = [
                    ['name' => 'objectId', 'label' => 'Id', 'type' => 'varchar(36)'],
                    ['name' => 'objectType', 'label' => 'Object Type', 'type' => 'varchar(36)'],
                    ['name' => 'portalId', 'label' => 'Portal Id', 'type' => 'varchar(1)'],
                    ['name' => 'dealdId', 'label' => 'Deal Id', 'type' => 'varchar(1)'],
                    ['name' => 'properties__name__value', 'label' => 'Name', 'type' => 'varchar(255)'],
                    ['name' => 'properties__hs_product_id__value', 'label' => 'Product Id', 'type' => 'varchar(255)'],
                    ['name' => 'properties__quantity__value', 'label' => 'Quantity', 'type' => 'varchar(255)'],
                    ['name' => 'properties__price__value', 'label' => 'Price', 'type' => 'varchar(255)'],
                    ['name' => 'isDeleted', 'label' => 'Is deleted', 'type' => 'varchar(255)'],
                ];
            } elseif ($engagement) {
                $result = [
                    ['name' => 'engagement__id', 'label' => 'Id', 'type' => 'varchar(36)'],
                    ['name' => 'engagement__portalId', 'label' => 'Portal id', 'type' => 'varchar(36)'],
                    ['name' => 'engagement__createdAt', 'label' => 'Created at', 'type' => 'varchar(255)'],
                    ['name' => 'engagement__lastUpdated', 'label' => 'Last updated', 'type' => 'varchar(255)'],
                    ['name' => 'engagement__ownerId', 'label' => 'OwnerId', 'type' => 'varchar(36)'],
                    ['name' => 'engagement__type', 'label' => 'Type', 'type' => 'varchar(255)'],
                    ['name' => 'engagement__timestamp', 'label' => 'Timestamp', 'type' => 'varchar(255)'],
                    ['name' => 'associations__contactIds', 'label' => 'Contact Ids', 'type' => 'varchar(36)'],
                    ['name' => 'associations__companyIds', 'label' => 'Company Ids', 'type' => 'varchar(36)'],
                    ['name' => 'associations__dealIds', 'label' => 'Deal Ids', 'type' => 'varchar(36)'],
                ];

                switch ($engagement_module[1]) {
                    case 'note':
                        array_push($result,
                            ['name' => 'metadata__body', 'label' => 'Note body', 'type' => 'text']
                        );
                        break;
                     case 'call':
                        array_push($result,
                            ['name' => 'metadata__toNumber', 'label' => 'To number', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__fromNumber', 'label' => 'From number', 'type' => 'varchar(25)'],
                            ['name' => 'metadata__status', 'label' => 'Status', 'type' => 'varchar(36)', 'options' => [['value' => 'COMPLETED', 'label' => 'COMPLETED']]],
                            ['name' => 'metadata__externalId', 'label' => 'External Id', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__durationMilliseconds', 'label' => 'Duration Milliseconds', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__externalAccountId', 'label' => 'External Account Id', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__recordingUrl', 'label' => 'RecordingUrl', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__body', 'label' => 'Body', 'type' => 'text'],
                            ['name' => 'metadata__disposition', 'label' => 'Disposition', 'type' => 'varchar(255)']
                        );
                        break;
                    case 'task':
                        array_push($result,
                            //metadata
                            ['name' => 'metadata__body', 'label' => 'Body', 'type' => 'text'],
                            ['name' => 'metadata__status', 'label' => 'Status', 'type' => 'varchar(255)', 'options' => [
                                ['value' => 'NOT_STARTED', 'label' => 'NOT_STARTED'],
                                ['value' => 'COMPLETED', 'label' => 'COMPLETED'],
                                ['value' => 'IN_PROGRESS', 'label' => 'IN_PROGRESS'],
                                ['value' => 'WAITING', 'label' => 'WAITING'],
                                ['value' => 'DEFERRED', 'label' => 'DEFERRED'], ]],
                            ['name' => 'metadata__subject', 'label' => 'Subject', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__forObjectType', 'label' => 'Object Type', 'type' => 'varchar(255)', 'options' => [['value' => 'CONTACT', 'label' => 'Contact'], ['value' => 'COMPANY', 'label' => 'Company']]]
                        );
                        break;

                    case 'meeting':
                        array_push($result,
                            //metadata
                            ['name' => 'metadata__body', 'label' => 'Body', 'type' => 'text'],
                            ['name' => 'metadata__startTime', 'label' => 'startTime', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__endTime', 'label' => 'endTime', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__title', 'label' => 'Title', 'type' => 'varchar(255)']
                        );
                        break;

                    case 'email':
                        array_push($result,
                            //metadata
                            ['name' => 'metadata__from__email', 'label' => 'From email', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__from__firstName', 'label' => 'From firstName', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__from__lastName', 'label' => 'From lastName', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__to__email', 'label' => 'To email', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__cc', 'label' => 'CC', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__bcc', 'label' => 'BCC', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__subject', 'label' => 'Subject', 'type' => 'varchar(255)'],
                            ['name' => 'metadata__html', 'label' => 'HTML', 'type' => 'text'],
                            ['name' => 'metadata__text', 'label' => 'Text', 'type' => 'text']
                        );
                        break;
                }
            } else {
                $result = $this->call($this->url.'properties/'.$this->version.'/'.$module.'/properties?hapikey='.$this->paramConnexion['apikey']);
                $result = $result['exec'];
                // Add fields to manage deals relationships
                if ('deals' === $module) {
                    $result[] = ['name' => 'associations__associatedVids', 'label' => 'Contact Id', 'type' => 'varchar(36)'];
                    $result[] = ['name' => 'associations__associatedCompanyIds', 'label' => 'Company Id', 'type' => 'varchar(36)'];
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
                    and 'target' == $type
                ) {
                    continue;
                }
                $this->moduleFields[$field['name']] = [
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'type_bdd' => $field['type'],
                    'required' => 0,
                    'relate' => false,
                ];
                // If the fields is a relationship
                if (
                        'ID' == strtoupper(substr($field['name'], -2))
                     or 'IDS' == strtoupper(substr($field['name'], -3))
                     or 'ID__VALUE' == strtoupper(substr($field['name'], -9)) // Used for module's type object
                ) {
                    $this->moduleFields[$field['name']]['relate'] = true;
                }
                // Add list of values
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $value) {
                        $this->moduleFields[$field['name']]['option'][$value['value']] = $value['label'];
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

    public function readData($param)
    {
        try {
            $result = [];
            $result['count'] = 0;
            // Remove Myddleware 's system fields
            $param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
            // Format the module name
            $module = $this->formatModuleName($param['module']);
            // Add required fields
            $param['fields'] = $this->addRequiredField($param['fields'], $module, $param['ruleParams']['mode']);

            // Get the id field label
            $id = $this->getIdField($param, $module);
            // Get modified field label
            $modifiedFieldName = $this->modifiedField[$module];

            // In case we search a specific record, we set a date_ref far in the past to be sure to not filter the result by date
            if (!empty($param['query']['id'])) {
                $param['date_ref'] = '1970-01-01 00:00:00';
            // No search with filter for now.
            } elseif (!empty($param['query'])) {
                return;
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

            if ('engagements' === $module) {
                // Filter on the right engagement type
                $resultQuery = $this->selectType($resultQuery, $param['module'], false);
                // date ref is managed directly with record date modified for Engagement
                $result['date_ref'] = $param['date_ref'];
            }

            $resultQuery = $resultQuery['exec'];
            if (
                    'companies' === $module
                 or 'deal' === $module
                 or 'engagements' === $module
            ) {
                $identifyProfiles = $resultQuery['results'];
            } elseif ('deals' === $module || 'owners' === $module) {
                // if the module is deal_pipeline_stage, we have called the module deal_pipeline and we generate the stage module from ths call
                // A pipeline can have several stages. We format the result to be compatible with the following code
                if ('deal_pipeline_stage' === $param['module']) {
                    if (!empty($resultQuery[$param['module']])) {
                        // For each pipeline
                        foreach ($resultQuery[$param['module']] as $pipeline) {
                            if (!empty($pipeline['stages'])) {
                                // For each stage
                                foreach ($pipeline['stages'] as $stage) {
                                    $stage['pipelineId'] = $pipeline['pipelineId'];
                                    $stage[$id] = $pipeline['pipelineId'].'_'.$stage[$id];
                                    $identifyProfiles[] = $stage;
                                }
                            }
                        }
                    }
                } else {
                    $identifyProfiles = $resultQuery[$param['module']];
                }
            } else {
                $identifyProfiles = $resultQuery[$param['module']];
            }

            // If no result
            if (empty($resultQuery)) {
                $result['error'] = 'Request error';
            } else {
                $identifyProfiles = $this->beforeGenerateResult($identifyProfiles, $param);
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
                                if (isset($identifyProfile['properties'][$field])) {
                                    $records[$field] = $identifyProfile['properties'][$field]['value'];
                                // The structure is different for the module owner
                                } elseif (
                                    (
                                        'owners' === $module
                                     or 'deal_pipeline' === $param['module']
                                     or 'deal_pipeline_stage' === $param['module']
                                     or !empty($this->objectModule[$module]) // In case of object module, we return objectId
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
                                    'engagements' === $module
                                and !empty($identifyProfile['engagement'][$id])
                            ) {
                                $records['id'] = $identifyProfile['engagement'][$id];
                                if (isset($identifyProfile['engagement']['properties'][$modifiedFieldName])) {
                                    $records['date_modified'] = date('Y-m-d H:i:s', $identifyProfile['engagement']['properties'][$modifiedFieldName]['value'] / 1000);
                                } else {
                                    $records['date_modified'] = date('Y-m-d H:i:s', $identifyProfile['engagement'][$modifiedFieldName] / 1000);
                                }
                                $result['values'][$identifyProfile['engagement'][$id]] = $records;
                            } elseif (!empty($identifyProfile[$id])) {
                                $records['id'] = $identifyProfile[$id];
                                if (isset($identifyProfile['properties'][$modifiedFieldName])) {
                                    $records['date_modified'] = date('Y-m-d H:i:s', $identifyProfile['properties'][$modifiedFieldName]['value'] / 1000);
                                } elseif (isset($identifyProfile[$modifiedFieldName])) {
                                    $records['date_modified'] = date('Y-m-d H:i:s', $identifyProfile[$modifiedFieldName] / 1000);
                                } else { //deal_pipeline_stage has no reference field
                                    $records['date_modified'] = date('Y-m-d H:i:s');
                                }
                                $result['values'][$identifyProfile[$id]] = $records;
                            }

                            // Don't set reference date normal mode, $result['date_ref'] is already set with the offset
                            if (false == $this->migrationMode) {
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
            $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
        }

        return $result;
    }

    public function createData($param): array
    {
        try {
            // Associate deal is always an update to Hubspot
            if ('associate_deal' == $param['module']) {
                return $this->updateData($param);
            }
            // Tranform Myddleware data to Mailchimp data
            foreach ($param['data'] as $idDoc => $data) {
                $dataHubspot = [];
                $records = [];

                //formatModuleName contact
                $module = $this->formatModuleName($param['module']);
                if ('companies' === $module || 'deal' === $module) {
                    $version = 'companies' === $module ? 'v2' : 'v1';
                    $id = 'companies' === $module ? 'companyId' : 'dealId';
                    $url = $this->url.$param['module'].'/'.$version.'/'.$module.'?hapikey='.$this->paramConnexion['apikey'];
                    $property = 'name';
                } elseif ('contact' === $module) {
                    $url = $this->url.$param['module'].'/v1/'.$module.'?hapikey='.$this->paramConnexion['apikey'];
                    $id = 'vid';
                    $property = 'property';
                // Engagement module (only note enabled for now)
                } elseif ('engagements' == $module) {
                    $url = $this->url.'engagements/v1/engagements?hapikey='.$this->paramConnexion['apikey'];
                    $moduleArray = explode('_', $param['module']);
                    $data['type'] = strtoupper($moduleArray[1]); // For example : NOTE
                    unset($data['target_id']); // Used only in UPDATE
                    // Format data
                    foreach ($data as $key => $value) {
                        // Field can have 2 dimensions, e.g. associations__contactIds
                        $fieldArray = explode('__', $key);
                        if (!empty($fieldArray[1])) {
                            // If field contains Ids, then we add it as an array
                            if ('Ids' == substr($key, -3)) {
                                // Hubspot doesn't support that we send an empty relationship
                                if (!empty($value)) {
                                    $dataHubspot[$fieldArray[0]][$fieldArray[1]][] = $value;
                                }
                            } else {
                                $dataHubspot[$fieldArray[0]][$fieldArray[1]] = $value;
                            }
                        } else {
                            $dataHubspot['engagement'][$key] = $value;
                        }
                    }
                    $id = 'engagement__id';
                }
                // Only for non engagement module
                if ('engagements' != $module) {
                    foreach ($param['data'][$idDoc] as $key => $value) {
                        if (in_array($key, ['target_id', 'Myddleware_element_id'])) {
                            continue;
                        }
                        array_push($records, [$property => $key, 'value' => $value]);
                    }
                    $dataHubspot['properties'] = $records;
                }
                // Call to Hubspot
                $resultQuery = $this->call($url, 'POST', $dataHubspot);
                if (isset($resultQuery['exec']['status']) && 'error' === $resultQuery['exec']['status']) {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => 'Failed to create data in hubspot. '.(!empty($resultQuery['exec']['validationResults'][0]['message']) ? $resultQuery['exec']['validationResults'][0]['message'] : (!empty($resultQuery['exec']['message']) ? $resultQuery['exec']['message'] : '')),
                    ];
                } else {
                    $idFieldArray = explode('__', $id);
                    // If id in a substructure (example : engagement)
                    if (!empty($idFieldArray[1])) {
                        $result[$idDoc] = [
                            'id' => $resultQuery['exec'][$idFieldArray[0]][$idFieldArray[1]],
                            'error' => false,
                        ];
                    } else {
                        $result[$idDoc] = [
                            'id' => $resultQuery['exec'][$id],
                            'error' => false,
                        ];
                    }
                }
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            $result[$idDoc] = [
                'id' => '-1',
                'error' => $error,
            ];
        }

        return $result;
    }

    public function updateData($param): array
    {
        try {
            $module = $this->formatModuleName($param['module']);
            if ('companies' === $module || 'deal' === $module) {
                $property = 'name';
                $method = 'PUT';
                $version = 'companies' === $module ? 'v2' : 'v1';
            } elseif ('contact' === $module) {
                $property = 'property';
                $method = 'POST';
            } elseif ('engagements' == $module) {
                $method = 'PATCH';
            }

            // Tranform Myddleware data to hubspot data
            foreach ($param['data'] as $idDoc => $data) {
                $records = [];
                // No properties for module associate_deal
                if ('associate_deal' != $param['module']) {
                    $dataHubspot = [];
                    foreach ($param['data'][$idDoc] as $key => $value) {
                        if ('target_id' == $key) {
                            $idProfile = $value;
                            continue;
                        } elseif ('Myddleware_element_id' == $key) {
                            continue;
                        }
                        // Specific code for engagement
                        if ('engagements' == $module) {
                            // Field can have 2 dimensions, e.g. associations__contactIds
                            $fieldArray = explode('__', $key);
                            if (!empty($fieldArray[1])) {
                                // If field contains Ids, then we add it as an array
                                if ('Ids' == substr($key, -3)) {
                                    // Hubspot doesn't support that we send an empty relationship
                                    if (!empty($value)) {
                                        $dataHubspot[$fieldArray[0]][$fieldArray[1]][] = $value;
                                    }
                                } else {
                                    $dataHubspot[$fieldArray[0]][$fieldArray[1]] = $value;
                                }
                            } else {
                                $dataHubspot['engagement'][$key] = $value;
                            }
                        } else {
                            array_push($records, [$property => $key, 'value' => $value]);
                        }
                    }
                    // No properties for engagement module
                    if ('engagements' != $module) {
                        $dataHubspot['properties'] = $records;
                    }
                }

                if ('associate_deal' === $param['module']) {
                    // Id profile is the deal_id. It is possible that we haven't target_id because the update function can be called by the create function
                    $idProfile = $data['deal_id'];
                    $url = $this->url.'deals/'.$version.'/'.$module.'/'.$idProfile.'/associations/'.$data['object_type'].'?id='.$data['record_id'].'&hapikey='.$this->paramConnexion['apikey'];
                    $dataHubspot = [];
                } elseif ('companies' === $module || 'deal' === $module) {
                    $url = $this->url.$param['module'].'/'.$version.'/'.$module.'/'.$idProfile.'?hapikey='.$this->paramConnexion['apikey'];
                } elseif ('contact' === $module) {
                    $url = $this->url.$param['module'].'/v1/'.$module.'/vid/'.$idProfile.'/profile'.'?hapikey='.$this->paramConnexion['apikey'];
                } elseif ('engagements' === $module) {
                    $url = $this->url.$module.'/v1/'.$module.'/'.$idProfile.'?hapikey='.$this->paramConnexion['apikey'];
                } else {
                    throw new \Exception('Module '.$module.' unknown.');
                }
                // Call to Hubspot
                $resultQuery = $this->call($url, $method, $dataHubspot);

                if (
                    $resultQuery['info']['http_code'] >= 200 // 200 is used to update deals for example
                    and $resultQuery['info']['http_code'] <= 204 //204 is good
                ) {
                    $result[$idDoc] = [
                        'id' => $idProfile,
                        'error' => false,
                    ];
                } else {
                    $result[$idDoc] = [
                        'id' => '-1',

                        'error' => 'Failed to create data in hubspot. '.(!empty($resultQuery['exec']['validationResults'][0]['message']) ? $resultQuery['exec']['validationResults'][0]['message'] : (!empty($resultQuery['exec']['message']) ? $resultQuery['exec']['message'] : '')),
                    ];
                }
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $result[$idDoc] = [
                'id' => '-1',
                'error' => $error,
            ];
        }

        return $result;
    }


    // Change the result
    protected function beforeGenerateResult($identifyProfiles, $param): array
    {
        if (!empty($identifyProfiles)) {
            // In case of line Item, we add the dealId if requested
            if (
                    'line_items' == $param['module']
                and in_array('dealdId', $param['fields'])
            ) {
                foreach ($identifyProfiles as $key => $identifyProfile) {
                    // 20 => association Line item to deal
                    $resultCall = $this->call($this->url.'crm-associations/v1/associations/'.$identifyProfile['objectId'].'/HUBSPOT_DEFINED/20?hapikey='.$this->paramConnexion['apikey']);
                    if (!empty($resultCall['exec']['results'][0])) {
                        $identifyProfiles[$key]['dealdId'] = $resultCall['exec']['results'][0];
                    }
                }
            }
        }

        return $identifyProfiles;
    }

    /**
     * Build the url depending on the module
     * @throws \Exception
     */
    protected function getUrl($param): array
    {
        // Format the module name
        $module = $this->formatModuleName($param['module']);
        // Get the version label
        $version = $this->getVersion($param, $module);

        // In case we search a specific record
        if (!empty($param['query']['id'])) {
            // Calls can be differents depending on the modules
            switch ($module) {
                case 'deal':
                    $result['url'] = $this->url.'deals/'.$version.'/'.$module.'/'.$param['query']['id'].'?hapikey='.$this->paramConnexion['apikey'];
                    break;
                case 'contact':
                    $result['url'] = $this->url.'contacts/'.$version.'/'.$module.'/vid/'.$param['query']['id'].'/profile?hapikey='.$this->paramConnexion['apikey'];
                    break;
                case 'deals':
                    $result['url'] = $this->url.'deals/'.$version.'/pipelines/'.$param['query']['id'].'?hapikey='.$this->paramConnexion['apikey'];
                    break;
                default:
                    $result['url'] = $this->url.$module.'/'.$version.'/'.$module.'/'.$param['query']['id'].'?hapikey='.$this->paramConnexion['apikey'];
            }

            return $result;
        }

        // Module with only one url
        if ('owners' === $module) {
            $result['url'] = $this->url.$param['module'].'/'.$version.'/'.$param['module'].'?hapikey='.$this->paramConnexion['apikey'];
        } elseif ('deals' === $module) {
            $result['url'] = $this->url.$module.'/'.$version.'/pipelines'.'?hapikey='.$this->paramConnexion['apikey'];
        } elseif (!empty($this->objectModule[$module])) {
            // Build the query with the properties fields
            $properties = '';
            foreach ($this->objectModule[$param['module']]['properties'] as $field) {
                $properties .= '&properties='.$field;
            }
            $result['url'] = $this->url.'crm-objects/v1/objects/'.$module.'/paged?hapikey='.$this->paramConnexion['apikey'].$properties;
        } else {
            // calculate the difference between date_ref and now
            if (!is_numeric($param['date_ref'])) {
                $now = new DateTime('now');
                $dateRef = new DateTime($param['date_ref']);
                $interval = $dateRef->diff($now);
            }

            // ModificationDate or CreationDate
            $dateRefField = $this->getRefFieldName($param);

            $property = '';
            // If date_ref is more than 30 days in the past or if an offset is in the reference
            // We are in migration mode and we will call all records for the module not only the recent ones
            if (
                    is_numeric($param['date_ref'])
                 or empty($param['date_ref'])	// In case the user removed the reference on the rule
                 or (
                        isset($interval)
                    and $interval->format('%a') >= 30
                )
            ) {
                // In case we have more than 30 days, we set offeset to 0 to read all records
                if (
                        isset($interval)
                    and $interval->format('%a') >= 30
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
                    case 'companies' === $module
                         or 'deal' === $module:
                        if (!empty($param['fields'])) { // Add fields in the call
                            foreach ($param['fields'] as $fields) {
                                $property .= '&properties='.$fields;
                            }
                        }
                        $result['url'] = $this->url.$param['module'].'/'.$version.'/'.$module.'/paged'.'?hapikey='.$this->paramConnexion['apikey'].$property.'&limit='.$this->limitCall[$module];
                        $result['offset'] = '&offset='.$offset;
                        break;
                    case 'contact':
                        if (!empty($param['fields'])) {// Add fields in the call
                            foreach ($param['fields'] as $fields) {
                                $property .= '&property='.$fields;
                            }
                        }
                        $result['url'] = $this->url.$param['module'].'/'.$version.'/lists/all/'.$param['module'].'/all'.'?hapikey='.$this->paramConnexion['apikey'].$property.'&count='.$this->limitCall[$module];
                        $result['offset'] = '&vidOffset='.$offset;
                        break;
                    case 'engagements':
                        $result['url'] = $this->url.$module.'/'.$version.'/'.$module.'/paged'.'?hapikey='.$this->paramConnexion['apikey'].'&limit='.$this->limitCall[$module];
                        $result['offset'] = '&offset='.$offset;
                        break;
                    default:
                        throw new \Exception('No API call for search more than 30 days in the past with the module '.$module);
                }
            } else {
                switch ($module) {
                    case 'companies' === $module
                         or 'deal' === $module:
                        if (!empty($param['fields'])) { // Add fields in the call
                            foreach ($param['fields'] as $fields) {
                                $property .= '&properties='.$fields;
                            }
                        }
                        // Calls are different for creation or modification
                        if ('ModificationDate' === $dateRefField) {
                            $result['url'] = $this->url.$param['module'].'/'.$version.'/'.$module.'/recent/modified/'.'?hapikey='.$this->paramConnexion['apikey'].$property.'&count='.$this->limitCall[$module].'&since='.$dateRef->getTimestamp().'000';
                        } else {
                            $result['url'] = $this->url.$param['module'].'/'.$version.'/'.$module.'/recent/created/'.'?hapikey='.$this->paramConnexion['apikey'].$property.'&count='.$this->limitCall[$module];
                        }
                        break;
                    case 'contact':
                        if (!empty($param['fields'])) { // Add fields in the call
                            foreach ($param['fields'] as $fields) {
                                $property .= '&property='.$fields;
                            }
                        }
                        // Calls are different for creation or modification
                        if ('ModificationDate' === $dateRefField) {
                            $result['url'] = $this->url.$param['module'].'/'.$version.'/lists/recently_updated/'.$param['module'].'/recent'.'?hapikey='.$this->paramConnexion['apikey'].$property.'&count='.$this->limitCall[$module];
                        } else {
                            $result['url'] = $this->url.$param['module'].'/'.$version.'/lists/all/'.$param['module'].'/recent'.'?hapikey='.$this->paramConnexion['apikey'].$property.'&count='.$this->limitCall[$module];
                        }
                        break;
                    case 'engagements':
                        $result['url'] = $this->url.$module.'/'.$version.'/'.$module.'/recent/modified'.'?hapikey='.$this->paramConnexion['apikey'].'&count='.$this->limitCall[$module].'&since='.$dateRef->getTimestamp().'000';
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
     * 9***.selon le type.
     *
     * @param $results
     * @param $module
     * @param mixed $first
     *
     * @return array
     */
    public function selectType($results, $module, $first = true): array
    {
        $moduleResult = explode('_', $module);
        $resultFinal = [];
        // Delete all engagement not in the type searched
        foreach ($results['exec']['results'] as $key => $record) {
            if ($record['engagement']['type'] != strtoupper($moduleResult[1])) {
                unset($results['exec']['results'][$key]);
            }
        }

        return $results;
    }

    // Get version label
    protected function getVersion($param, $module): string
    {
        if (
                'companies' === $module
             or 'owners' === $module
        ) {
            return 'v2';
        }

        return 'v1';
    }

    //Get the id label depending of the module
    protected function getIdField($param, $module): string
    {
        // In case of object module, we return objectId
        if (!empty($this->objectModule[$module])) {
            return 'objectId';
        }
        // Specifics Ids for the other module
        switch ($module) {
            case 'companies':
                return 'companyId';
                break;
            case 'deal':
                return 'dealId';
                break;
            case 'contact':
                return 'vid';
                break;
            case 'owners':
                return 'ownerId';
                break;
            case 'deals':
                if ('deal_pipeline' === $param['module']) {
                    return 'pipelineId';
                } elseif ('deal_pipeline_stage' === $param['module']) {
                    return 'stageId';
                }

                    return 'id';

                break;
            default: // engagement for example
               return 'id';
        }
    }

    /**
     * Function for get data.
     *
     * @param $request
     * @param $url
     * @param $param
     *
     * @return array
     */
    protected function getresultQuery($request, $url, $param): array
    {
        // Module contact or Deal
        if (
                'contacts' == $param['module']
             or 'deals' == $param['module']
        ) {
            // In case on the search for a specific record is requested we add a dimension to the result array
            if (!empty($param['query']['id'])) {
                $requestTmp['exec'][$param['module']][0] = $request['exec'];
                $request = $requestTmp;
            }
            // The key of the array return is different depending the module
            // If there is no more data to read
            if (
                    true == $this->readLast // Only one call if read_last is requested
                or (
                        empty($request['exec']['has-more'])
                    and empty($request['exec']['hasMore'])
                )
            ) {
                $keyResult = (isset($request['exec'][$param['module']]) ? $param['module'] : 'results');
                $result = $this->getresultQueryBydate($request['exec'][$keyResult], $param, false);
            // If we have to make several calls to read all the data
            } else {
                // Get the offset contact id or deal id
                $offset = ('contacts' == $param['module'] ? $request['exec']['vid-offset'] : $request['exec']['offset']);
                $keyResult = (isset($request['exec'][$param['module']]) ? $param['module'] : 'results');
                $result = $this->getresultQueryBydate($request['exec'][$keyResult], $param, false);
                do {
                    // Call the next page
                    $offsetStr = ('contacts' == $param['module'] ? '&vidOffset='.$offset : '&offset='.$offset);
                    $resultOffset = $this->call($url.$offsetStr);
                    $keyResultOffset = (isset($resultOffset['exec'][$param['module']]) ? $param['module'] : 'results');
                    // $timeOffset = $resultOffset['exec']['time-offset'];
                    $offset = ('contacts' == $param['module'] ? $resultOffset['exec']['vid-offset'] : $resultOffset['exec']['offset']);
                    // Format results
                    $resultOffsetTemps = $this->getresultQueryBydate($resultOffset['exec'][$keyResultOffset], $param, true);

                    // Add result to the main array
                    $keyResult = (isset($result['exec'][$param['module']]) ? $param['module'] : 'results');
                    $merge = array_merge($result['exec'][$keyResult], $resultOffsetTemps);

                    $result['exec']['results'] = $merge;

                    // Call again only if we haven't reached the reference date
                } while (
                        !empty($resultOffsetTemps)	// Date_ref has been reached (no result in getresultQueryBydate)
                    and (!empty($resultOffset['exec']['hasMore']) // No more data to read
                         or !empty($resultOffset['exec']['has-more'])
                        )
                    and count($result['exec'][$keyResult]) < ($param['limit'] - 1) // Stop if we reach the limit (-1 see method rule->setLimit)  set in the rule
                );
            }
            // Module Company or Engagement
        } elseif (
                    'companies' === $param['module']
                 or 'engagement' === substr($param['module'], 0, 10)
                 or 'engagement' === substr($param['module'], 0, 10)
        ) {
            // The response key can be different depending the API call
            if ('companies' === $param['module']) {
                if (false !== strpos($url, 'paged')) {
                    $key = $param['module'];
                } else {
                    $key = 'results';
                }
            } elseif (!empty($this->objectModule[$param['module']])) {
                $key = 'objects';
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
                or true == $this->readLast  // Only one call if read_last is requested
            ) {
                $result = $this->getresultQueryBydate($request['exec'][$key], $param, false);
            } else {
                // If we have to call the API several times
                $offset = $request['exec']['offset'];
                $result = $this->getresultQueryBydate($request['exec'][$key], $param, false);
                do {
                    $resultOffset = $this->call($url.'&offset='.$offset);
                    if (!empty($resultOffset)) {
                        // Check if error
                        if (
                                !empty($resultOffset['exec']['status'])
                            and 'error' == $resultOffset['exec']['status']
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
                    and (!empty($resultOffset['exec']['hasMore']) // No more data to read
                         or !empty($resultOffset['exec']['has-more'])
                        )
                    and count($result['exec']['results']) < ($param['limit'] - 1) // Stop if we reach the limit (-1 see method rule->setLimit)  set in the rule
                );
            }
        } else {
            // In case on the search for a specific record is requested we add a dimension to the result array
            if (!empty($param['query']['id'])) {
                if (
                        'owners' === $param['module']
                     or 'deal_pipeline' === $param['module']
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
             or count($result['exec']['results']) < ($param['limit'] - 1) // (-1 see method rule->setLimit)
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
     * Function for get data with date_ref.
     *
     * @param $request
     * @param $url
     * @param $param
     * @param mixed $offset
     *
     * @return array
     */
    protected function getresultQueryBydate($request, $param, $offset): array
    {
        'deals' === $param['module'] || 'companies' === $param['module'] ? $modified = 'hs_lastmodifieddate' : $modified = 'lastmodifieddate';
        if ('owners' === $param['module']) {
            $modified = 'updatedAt';
        } elseif (
                'engagement_call' === $param['module']
             or 'engagement_task' === $param['module']
             or 'engagement_note' === $param['module']
             or 'engagement_meeting' === $param['module']
             or 'engagement_email' === $param['module']) {
            $modified = 'lastUpdated';
        } elseif ('deal_pipeline' === $param['module']) {
            $modified = 'updatedAt';
        }
        if (!$offset) {
            if (
                    'deals' === $param['module']
                 or 'companies' === $param['module']
                 or 'engagement' === substr($param['module'], 0, 10)
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
            $dateTimestamp = $this->dateTimeToTimestamp($param['date_ref']);
        }
        // Init the reference with the current date_ref
        // $result['date_ref'] = $dateTimestamp;
        if (
                'engagement_call' === $param['module']
             or 'engagement_task' === $param['module']
             or 'engagement_meeting' === $param['module']
             or 'engagement_note' === $param['module']
             or 'engagement_email' === $param['module']
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
                'deal_pipeline' === $param['module']
             or 'deal_pipeline_stage' === $param['module']
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
            // Module type object
        } elseif (!empty($this->objectModule[$param['module']])) {
            if (!empty($request['objects'])) {
                foreach ($request['objects'] as $item) {
                    // Get the last property modified
                    $lastChange = 0;
                    foreach ($this->objectModule[$param['module']]['properties'] as $field) {
                        if (!empty($item['properties'][$field]['timestamp'] > $lastChange)) {
                            $lastChange = $item['properties'][$field]['timestamp'];
                        }
                    }
                    // For product, we take the date on the latest version of the name or the price
                    if ($lastChange >= $dateTimestamp) { // >= because we have added 1 second to the reference date in the previous call
                        // We take the most recent date modified between name modified and price modified
                        $item['date_modified'] = $lastChange;
                        if (!$offset) {
                            array_push($result['exec'][$module], $item);
                        } else {
                            array_push($result, $item);
                        }
                    }
                }
            }
        } elseif ('owners' === $param['module']) {
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
                        'deals' === $param['module']
                    and (
                            isset($request['deals'])
                         or isset($request['results'])
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
    protected function dateTimeToTimestamp($dateTime)
    {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);

        return $date->getTimestamp() * 1000;
    }

    // dateTimeToMyddleware($dateTime)

    /**
     * return the reference date field name.
     *
     * @param $param
     *
     * @return string|null
     *
     * @throws \Exception
     */
    public function getRefFieldName($param): ?string
    {
        // Creation and modification mode
        if (in_array($param['ruleParams']['mode'], ['0', 'S'])) {
            return 'ModificationDate';
        // Creation mode only
        } elseif ('C' == $param['ruleParams']['mode']) {
            return 'CreationDate';
        }
        throw new \Exception("$param[ruleParams][mode] is not a correct Rule mode.");
    }

    /**
     * get singular of module.
     *
     * @param $name
     *
     * @return string
     */
    public function formatModuleName($name): string
    {
        if ('contacts' === $name) {
            return 'contact';
        } elseif ('companies' === $name) {
            return 'companies';
        } elseif ('deals' === $name or 'associate_deal' === $name) {
            return 'deal';
        } elseif ('owners' === $name) {
            return 'owners';
        } elseif (
                'deal_pipeline' === $name
             or 'deal_pipeline_stage' === $name
        ) {
            return 'deals';
        } elseif (
                'engagement_call' === $name
             or 'engagement_task' === $name
             or 'engagement_email' === $name
             or 'engagement_note' === $name
             or 'engagement_meeting' === $name
        ) {
            return 'engagements';
        }

        return $name;
    }

    /**
     * Performs the underlying HTTP request. Not very exciting.
     *
     * @param string $method  The API method to be called
     * @param array  $args    Assoc array of parameters to be passed
     * @param mixed  $url
     * @param mixed  $timeout
     *
     * @return array Assoc array of decoded result
     */
    protected function call($url, $method = 'GET', $args = [], $timeout = 120): array
    {
        if (!function_exists('curl_init') or !function_exists('curl_setopt')) {
            throw new \Exception('curl extension is missing!');
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        $headers = [];
        $headers[] = 'Content-Type: application/json';
        if (!empty($this->token)) {
            $headers[] = 'Authorization: Bearer '.$this->token;
        }
        if (!empty($args)) {
            $jsonArgs = json_encode($args);

            $headers[] = 'Content-Lenght: '.$jsonArgs;
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

class hubspot extends hubspotcore
{
}
