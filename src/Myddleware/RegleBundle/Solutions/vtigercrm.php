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

use Javanile\VtigerClient\VtigerClient;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class vtigercrmcore extends solution
{
    protected $limitPerCall = 100;

    protected $required_fields = [
                                    'default'    => ['id', 'modifiedtime'],
                                ];

    protected $exclude_module_list = [
                                        'default'    => ['LineItem'],
                                        'target'     => [],
                                        'source'     => [],
                                    ];

    protected $exclude_field_list = [
                                        'default'    => ['id', 'modifiedby', 'modifiedtime'],
                                    ];

    protected $FieldsDuplicate = [];

    // Tableau représentant les relation many-to-many de Sugar
    protected $module_relationship_many_to_many = [
                                                    'calls_contacts'                 => ['label' => 'Relationship Call Contact', 'module_name' => 'Calls', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['call_id', 'contact_id']],
                                                    'calls_users'                    => ['label' => 'Relationship Call User', 'module_name' => 'Calls', 'link_field_name' => 'users', 'fields' => [], 'relationships' => ['call_id', 'user_id']],
                                                    'calls_leads'                    => ['label' => 'Relationship Call Lead', 'module_name' => 'Calls', 'link_field_name' => 'leads', 'fields' => [], 'relationships' => ['call_id', 'lead_id']],
                                                    'cases_bugs'                     => ['label' => 'Relationship Case Bug', 'module_name' => 'Cases', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['case_id', 'bug_id']],
                                                    'contacts_bugs'                  => ['label' => 'Relationship Contact Bug', 'module_name' => 'Contacts', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['contact_id', 'bug_id']],
                                                    'contacts_cases'                 => ['label' => 'Relationship Contact Case', 'module_name' => 'Contacts', 'link_field_name' => 'cases', 'fields' => [], 'relationships' => ['contact_id', 'case_id']],
                                                    'meetings_contacts'              => ['label' => 'Relationship Metting Contact', 'module_name' => 'Meetings', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['meeting_id', 'contact_id']],
                                                    'meetings_users'                 => ['label' => 'Relationship Meeting User', 'module_name' => 'Meetings', 'link_field_name' => 'users', 'fields' => [], 'relationships' => ['meeting_id', 'user_id']],
                                                    'meetings_leads'                 => ['label' => 'Relationship Meeting Lead', 'module_name' => 'Meetings', 'link_field_name' => 'leads', 'fields' => [], 'relationships' => ['meeting_id', 'lead_id']],
                                                    'opportunities_contacts'         => ['label' => 'Relationship Opportunity Contact', 'module_name' => 'Opportunities', 'link_field_name' => 'contacts', 'fields' => ['contact_role'], 'relationships' => ['opportunity_id', 'contact_id']], // contact_role exist in opportunities vardef for module contact (entry rel_fields)
                                                    'prospect_list_campaigns'        => ['label' => 'Relationship Prospect_list Campaign', 'module_name' => 'ProspectLists', 'link_field_name' => 'campaigns', 'fields' => [], 'relationships' => ['prospect_list_id', 'campaign_id']],
                                                    'prospect_list_contacts'         => ['label' => 'Relationship Prospect_list Contact', 'module_name' => 'ProspectLists', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['prospect_list_id', 'contact_id']],
                                                    'prospect_list_prospects'        => ['label' => 'Relationship Prospect_list Prospect', 'module_name' => 'ProspectLists', 'link_field_name' => 'prospects', 'fields' => [], 'relationships' => ['prospect_list_id', 'Prospect_id']],
                                                    'prospect_list_leads'            => ['label' => 'Relationship Prospect_list Lead', 'module_name' => 'ProspectLists', 'link_field_name' => 'leads', 'fields' => [], 'relationships' => ['prospect_list_id', 'lead_id']],
                                                    'prospect_list_users'            => ['label' => 'Relationship Prospect_list User', 'module_name' => 'ProspectLists', 'link_field_name' => 'users', 'fields' => [], 'relationships' => ['prospect_list_id', 'user_id']],
                                                    'prospect_list_accounts'         => ['label' => 'Relationship Prospect_list Account', 'module_name' => 'ProspectLists', 'link_field_name' => 'accounts', 'fields' => [], 'relationships' => ['prospect_list_id', 'account_id']],
                                                    'projects_bugs'                  => ['label' => 'Relationship Project Bug', 'module_name' => 'Projects', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['project_id', 'bug_id']],
                                                    'projects_cases'                 => ['label' => 'Relationship Project Case', 'module_name' => 'Projects', 'link_field_name' => 'cases', 'fields' => [], 'relationships' => ['project_id', 'case_id']],
                                                    'projects_accounts'              => ['label' => 'Relationship Project Account', 'module_name' => 'Projects', 'link_field_name' => 'accounts', 'fields' => [], 'relationships' => ['project_id', 'account_id']],
                                                    'projects_contacts'              => ['label' => 'Relationship Project Contact', 'module_name' => 'Projects', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['project_id', 'contact_id']],
                                                    'projects_opportunities'         => ['label' => 'Relationship Project Opportunity', 'module_name' => 'Projects', 'link_field_name' => 'opportunities', 'fields' => [], 'relationships' => ['project_id', 'opportunity_id']],
                                                    'email_marketing_prospect_lists' => ['label' => 'Relationship Email_marketing Prospect_list', 'module_name' => 'EmailMarketing', 'link_field_name' => 'prospect_lists', 'fields' => [], 'relationships' => ['email_marketing_id', 'prospect_list_id']],
                                                    'leads_documents'                => ['label' => 'Relationship Lead Document', 'module_name' => 'Leads', 'link_field_name' => 'documents', 'fields' => [], 'relationships' => ['lead_id', 'document_id']],
                                                    'documents_accounts'             => ['label' => 'Relationship Document Account', 'module_name' => 'Documents', 'link_field_name' => 'accounts', 'fields' => [], 'relationships' => ['document_id', 'account_id']],
                                                    'documents_contacts'             => ['label' => 'Relationship Document Contact', 'module_name' => 'Documents', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['document_id', 'contact_id']],
                                                    'documents_opportunities'        => ['label' => 'Relationship Document Opportunity', 'module_name' => 'Documents', 'link_field_name' => 'opportunities', 'fields' => [], 'relationships' => ['document_id', 'opportunity_id']],
                                                    'documents_cases'                => ['label' => 'Relationship Document Case', 'module_name' => 'Documents', 'link_field_name' => 'cases', 'fields' => [], 'relationships' => ['document_id', 'case_id']],
                                                    'documents_bugs'                 => ['label' => 'Relationship Document Bug', 'module_name' => 'Documents', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['document_id', 'bug_id']],
                                                    'aos_quotes_aos_invoices'        => ['label' => 'Relationship Quote Invoice', 'module_name' => 'AOS_Quotes', 'link_field_name' => 'aos_quotes_aos_invoices', 'fields' => [], 'relationships' => ['aos_quotes77d9_quotes_ida', 'aos_quotes6b83nvoices_idb']],
                                                    'fp_events_contacts'             => ['label' => 'Relationship Event Contact', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_contacts', 'fields' => [], 'relationships' => ['fp_events_contactsfp_events_ida', 'fp_events_contactscontacts_idb']],
                                                    'fp_events_leads_1'              => ['label' => 'Relationship Event Lead', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_leads_1', 'fields' => [], 'relationships' => ['fp_events_leads_1fp_events_ida', 'fp_events_leads_1leads_idb']],
                                                    'fp_events_prospects_1'          => ['label' => 'Relationship Event Prospect', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_prospects_1', 'fields' => [], 'relationships' => ['fp_events_prospects_1fp_events_ida', 'fp_events_prospects_1prospects_idb']],
                                                ];

    /** @var VtigerClient */
    protected $vtigerClient;

    /**
     * Make the login
     *
     * @param array $paramConnexion
     * @return void|array
     */
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);

        try {
            $client = new VtigerClient($this->paramConnexion['url']);
            $result = $client->login($this->paramConnexion['username'], $this->paramConnexion['accesskey']);

            if (!$result['success']) {
                throw new \Exception($result['error']['message']);
            }

            $this->session = $client->getSessionName();
            $this->connexion_valide = true;
            $this->vtigerClient = $client;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    /**
     * Make the logout
     *
     * @return bool
     */
    public function logout()
    {
        // TODO: Creare ed usare il loguot di vtiger (Non Funziona)
        /*
        if(empty($this->vtigerClient))
            return false;

        return $this->vtigerClient->logout();
        */

        return true;
    }

    /**
     * Return the login fields
     *
     * @return array
     */
    public function getFieldsLogin()
    {
        return [
            [
                'name'  => 'username',
                'type'  => TextType::class,
                'label' => 'solution.fields.username',
            ],
            [
                'name'  => 'accesskey',
                'type'  => PasswordType::class,
                'label' => 'solution.fields.accesskey',
            ],
            [
                'name'  => 'url',
                'type'  => TextType::class,
                'label' => 'solution.fields.url',
            ],
        ];
    }

    /**
     * Return of the modules without the specified ones
     *
     * @param string $type
     * @return array|bool
     */
    public function get_modules($type = 'source')
    {
        if (empty($this->vtigerClient)) {
            return false;
        }

        $result = $this->vtigerClient->listTypes();

        if (!$result['success'] || ($result['success'] && count($result['result']) == 0)) {
            return false;
        }

        $modules = $result['result'] ?? null;

        if (empty($modules)) {
            return false;
        }

        $escludedModule = $this->exclude_module_list[$type] ?: $this->exclude_module_list['default'];
        $options = [];
        foreach ($modules['information'] as $moduleName => $moduleInfo) {
            if (!in_array($moduleName, $escludedModule)) {
                $options[$moduleName] = $moduleInfo['label'];
            }
        }

        return $options ?: false;
    }

    /**
     * Return the fields for a specific module without the specified ones
     *
     * @param string $module
     * @param string $type
     * @return array|bool
     */
    public function get_module_fields($module, $type = 'source')
    {
        if (empty($this->vtigerClient)) {
            return false;
        }

        $describe = $this->vtigerClient->describe($module);

        if (!$describe['success'] || ($describe['success'] && count($describe['result']) == 0)) {
            return false;
        }

        $fields = $describe['result']['fields'] ?? null;

        if (empty($fields)) {
            return false;
        }

        $escludeField = $this->exclude_field_list[$module] ?? $this->exclude_field_list['default'];
        $this->moduleFields = [];
        foreach ($fields as $field) {
            if (!in_array($field['name'], $escludeField)) {
                if (substr($field['name'],-3) == '_id') {
                    $this->fieldsRelate[$field['name']] = array(
                                                'label' => $field['label'],
                                                'type' => 'varchar(36)',
                                                'type_bdd' => 'varchar(36)',
                                                'required' => $field['mandatory'],
                                                'required_relationship' => 0
                                            );
                } else {
                    $this->moduleFields[$field['name']] = [
                                                'label'    => $field['label'],
                                                'required' => $field['mandatory'],
                                                'type' => 'varchar(255)', // TODO: Settare il type giusto?
                                            ];
                }
            }
        }

        if (!empty($this->fieldsRelate)) {
            $this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
        }

        return $this->moduleFields ?: false;
    }

    /**
     * Read Last
     *
     * @param array $param
     * @return array
     */
    public function read_last($param)
    {
        if (empty($this->vtigerClient)) {
            return [
                'error' => 'Error: no VtigerClient setup',
                'done'  => false,
            ];
        }

        if (count($param['fields']) == 0) {
            return [
                'error' => 'Error: no Param Given',
                'done'  => false,
            ];
        }

        $queryParam = implode(',', $param['fields']) ?: '*';
        $where = '';
        if (!empty($param['query'])) {
            $where = 'WHERE ';
            foreach ($param['query'] as $key => $item) {
                if (substr($where, -strlen("'")) === "'") {
                    $where .= ' AND ';
                }
                $where .= "$key = '$item'";
            }
        }
        $query = $this->vtigerClient->query("SELECT $queryParam FROM $param[module] $where ORDER BY modifiedtime DESC LIMIT 0,1;");

        if (empty($query) || (!empty($query) && !$query['success'])) {
            return [
                        'error' => 'Error: Request Failed!',
                        'done'  => false,
                    ];
        }

        if (count($query['result']) == 0) {
            return [
                        'error' => 'No Data Retrived',
                        'done'  => false,
                    ];
        }

        $fields = $query['result'][0];
        $result = ['done' => true];

        foreach ($fields as $fieldName => $value) {
            $result['values'][$fieldName] = $value;
        }

        return $result;
    }

    /**
     * Read
     *
     * @param array $param
     * @return array
     */
    public function read($param)
    {
        if (empty($this->vtigerClient)) {
            return [
                        'error' => 'Error: no VtigerClient setup',
                        'done'  => false,
                    ];
        }

        if (count($param['fields']) == 0) {
            return [
                        'error' => 'Error: no Param Given',
                        'done'  => false,
                    ];
        }

        if (empty($param['offset'])) {
            $param['offset'] = 0;
        }
        if (empty($param['limit'])) {
            $param['limit'] = 100;
        }

        // Considerare di implementare Sync API in VtigerClient
        $queryParam = implode(',', $param['fields']) ?: '*';
        if ($queryParam != '*') {
            $requiredField = $this->required_fields[$param['module']] ?? $this->required_fields['default'];
            $queryParam = implode(',', $requiredField).','.$queryParam;
        }
        $queryParam = rtrim($queryParam, ',');
        $where = !empty($param['date_ref']) ? "WHERE modifiedtime > '$param[date_ref]'" : '';
        if (!empty($param['query'])) {
            $where = empty($where) ? 'WHERE ' : ' AND ';
            foreach ($param['query'] as $key => $item) {
                if (substr($where, -strlen("'")) === "'") {
                    $where .= ' AND ';
                }
                $where .= "$key = '$item'";
            }
        }


        $result = [
            'count' => 0,
		];

		$dataLeft = $param["limit"];
        do {
            $limit = $dataLeft - $this->limitPerCall <= 0 ? $dataLeft : $this->limitPerCall;
            $query = $this->vtigerClient->query("SELECT $queryParam FROM $param[module] $where ORDER BY modifiedtime ASC LIMIT $param[offset], $limit;");

            if (empty($query) || (!empty($query) && !$query['success'])) {
                return [
                            'error' => 'Error: Request Failed!',
                            'count' => 0,
                        ];
            }
    
            if ($result['count'] == 0 && count($query['result']) == 0) {
                return [
                            //"error" => "No Data Retrived",
                            'count' => 0,
                        ];
            }
    
            foreach ($query['result'] as $value) {
                $result['values'][$value['id']] = $value;
                $result['date_ref'] = $value['modifiedtime'];
                $result['count']++;
            }

			$param["offset"] += $limit;

			$dataLeft -= $limit;

        } while ($dataLeft > 0);


        return $result;
    }

    /**
     * Create new record in target
     *
     * @param array $param
     * @return array
     */
    public function create($param)
    {
        if (empty($this->vtigerClient)) {
            return ['error' => 'Error: no VtigerClient setup'];
        }

        $result = [];

        foreach ($param['data'] as $idDoc => $data) {
            unset($data['target_id']);
            $resultCreate = $this->vtigerClient->create($param['module'], $data);

            if (!empty($resultCreate) && $resultCreate['success'] && !empty($resultCreate['result'])) {
                $result[$idDoc] = [
                                    'id'    => $resultCreate['result']['id'],
                                    'error' => false,
                                ];
            } else {
                $result[$idDoc] = [
                                    'id'    => '-1',
                                    'error' => 'Errore',
                                ];
            }

            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    /**
     * Update exist record in target
     *
     * @param array $param
     * @return array
     */
    public function update($param)
    {
        if (empty($this->vtigerClient)) {
            return ['error' => 'Error: no VtigerClient setup'];
        }

        $result = [];

        foreach ($param['data'] as $idDoc => $data) {
            $data['id'] = $data['target_id'];
            unset($data['target_id']);
            $resultUpdate = $this->vtigerClient->update($param['module'], $data);

            if (!empty($resultUpdate) && $resultUpdate['success'] && !empty($resultUpdate['result'])) {
                $result[$idDoc] = [
                                    'id'    => $resultUpdate['result']['id'],
                                    'error' => false,
                                ];
            } else {
                $result[$idDoc] = [
                                    'id'    => '-1',
                                    'error' => 'Errore',
                                ];
            }

            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    // Permet de supprimer un enregistrement
    public function delete($id)
    {
    }
}

/* * * * * * * *  * * * * * *  * * * * * *
    si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/vtigercrm.php';
if (file_exists($file)) {
    require_once $file;
} else {
    //Sinon on met la classe suivante
    class vtigercrm extends vtigercrmcore
    {
    }
}
