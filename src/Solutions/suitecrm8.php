<?php

namespace App\Solutions;

use DateTime;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class suitecrm8core extends solution
{
    protected int $limitCall = 100;
    protected string $urlSuffix = '/Api/V8';

    // Enable to read deletion and to delete data
    protected bool $readDeletion = true;
    protected bool $sendDeletion = true;

    protected array $required_fields = ['default' => ['id', 'date_modified', 'date_entered']];

    protected array $FieldsDuplicate = [
        'Contacts' => ['email1', 'last_name'],
        'Accounts' => ['email1', 'name'],
        'Users' => ['email1', 'last_name'],
        'Leads' => ['email1', 'last_name'],
        'Prospects' => ['email1', 'name'],
        'default' => ['name'],
    ];

    protected $required_relationships = [
        'default' => [],
        'Contacts' => [],
        'Cases' => [],
    ];

    // liste des modules à exclure pour chaque solution
    protected array $exclude_module_list = [
        'default' => ['Home', 'Calendar', 'Documents', 'Administration', 'Currencies', 'CustomFields', 'Connectors', 'Dropdown', 'Dynamic', 'DynamicFields', 'DynamicLayout', 'EditCustomFields', 'Help', 'Import', 'MySettings', 'FieldsMetaData', 'UpgradeWizard', 'Sync', 'Versions', 'LabelEditor', 'Roles', 'OptimisticLock', 'TeamMemberships', 'TeamSets', 'TeamSetModule', 'Audit', 'MailMerge', 'MergeRecords', 'Schedulers', 'Schedulers_jobs', 'Groups', 'InboundEmail', 'ACLActions', 'ACLRoles', 'DocumentRevisions', 'ACL', 'Configurator', 'UserPreferences', 'SavedSearch', 'Studio', 'SugarFeed', 'EAPM', 'OAuthKeys', 'OAuthTokens'],
        'target' => [],
        'source' => [],
    ];

    protected array $exclude_field_list = [
        'default' => ['date_entered', 'date_modified', 'created_by_name', 'modified_by_name', 'created_by', 'modified_user_id'],
        'Contacts' => ['c_accept_status_fields', 'm_accept_status_fields', 'accept_status_id', 'accept_status_name', 'opportunity_role_fields', 'opportunity_role_id', 'opportunity_role', 'email'],
        'Leads' => ['email'],
        'Accounts' => ['email'],
        'Cases' => ['case_number'],
    ];

    // Tableau représentant les relation many-to-many de Sugar
    protected array $module_relationship_many_to_many = [
        'calls_contacts' => ['label' => 'Relationship Call Contact', 'module_name' => 'Calls', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['call_id', 'contact_id']],
        'calls_users' => ['label' => 'Relationship Call User', 'module_name' => 'Calls', 'link_field_name' => 'users', 'fields' => [], 'relationships' => ['call_id', 'user_id']],
        'calls_leads' => ['label' => 'Relationship Call Lead', 'module_name' => 'Calls', 'link_field_name' => 'leads', 'fields' => [], 'relationships' => ['call_id', 'lead_id']],
        'cases_bugs' => ['label' => 'Relationship Case Bug', 'module_name' => 'Cases', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['case_id', 'bug_id']],
        'contacts_bugs' => ['label' => 'Relationship Contact Bug', 'module_name' => 'Contacts', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['contact_id', 'bug_id']],
        'contacts_cases' => ['label' => 'Relationship Contact Case', 'module_name' => 'Contacts', 'link_field_name' => 'cases', 'fields' => [], 'relationships' => ['contact_id', 'case_id']],
        'meetings_contacts' => ['label' => 'Relationship Metting Contact', 'module_name' => 'Meetings', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['meeting_id', 'contact_id']],
        'meetings_users' => ['label' => 'Relationship Meeting User', 'module_name' => 'Meetings', 'link_field_name' => 'users', 'fields' => [], 'relationships' => ['meeting_id', 'user_id']],
        'meetings_leads' => ['label' => 'Relationship Meeting Lead', 'module_name' => 'Meetings', 'link_field_name' => 'leads', 'fields' => [], 'relationships' => ['meeting_id', 'lead_id']],
        'opportunities_contacts' => ['label' => 'Relationship Opportunity Contact', 'module_name' => 'Opportunities', 'link_field_name' => 'contacts', 'fields' => ['contact_role'], 'relationships' => ['opportunity_id', 'contact_id']], // contact_role exist in opportunities vardef for module contact (entry rel_fields)
        'prospect_list_campaigns' => ['label' => 'Relationship Prospect_list Campaign', 'module_name' => 'ProspectLists', 'link_field_name' => 'campaigns', 'fields' => [], 'relationships' => ['prospect_list_id', 'campaign_id']],
        'prospect_list_contacts' => ['label' => 'Relationship Prospect_list Contact', 'module_name' => 'ProspectLists', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['prospect_list_id', 'contact_id']],
        'prospect_list_prospects' => ['label' => 'Relationship Prospect_list Prospect', 'module_name' => 'ProspectLists', 'link_field_name' => 'prospects', 'fields' => [], 'relationships' => ['prospect_list_id', 'Prospect_id']],
        'prospect_list_leads' => ['label' => 'Relationship Prospect_list Lead', 'module_name' => 'ProspectLists', 'link_field_name' => 'leads', 'fields' => [], 'relationships' => ['prospect_list_id', 'lead_id']],
        'prospect_list_users' => ['label' => 'Relationship Prospect_list User', 'module_name' => 'ProspectLists', 'link_field_name' => 'users', 'fields' => [], 'relationships' => ['prospect_list_id', 'user_id']],
        'prospect_list_accounts' => ['label' => 'Relationship Prospect_list Account', 'module_name' => 'ProspectLists', 'link_field_name' => 'accounts', 'fields' => [], 'relationships' => ['prospect_list_id', 'account_id']],
        'projects_bugs' => ['label' => 'Relationship Project Bug', 'module_name' => 'Projects', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['project_id', 'bug_id']],
        'projects_cases' => ['label' => 'Relationship Project Case', 'module_name' => 'Projects', 'link_field_name' => 'cases', 'fields' => [], 'relationships' => ['project_id', 'case_id']],
        'projects_accounts' => ['label' => 'Relationship Project Account', 'module_name' => 'Projects', 'link_field_name' => 'accounts', 'fields' => [], 'relationships' => ['project_id', 'account_id']],
        'projects_contacts' => ['label' => 'Relationship Project Contact', 'module_name' => 'Projects', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['project_id', 'contact_id']],
        'projects_opportunities' => ['label' => 'Relationship Project Opportunity', 'module_name' => 'Projects', 'link_field_name' => 'opportunities', 'fields' => [], 'relationships' => ['project_id', 'opportunity_id']],
        'email_marketing_prospect_lists' => ['label' => 'Relationship Email_marketing Prospect_list', 'module_name' => 'EmailMarketing', 'link_field_name' => 'prospect_lists', 'fields' => [], 'relationships' => ['email_marketing_id', 'prospect_list_id']],
        'leads_documents' => ['label' => 'Relationship Lead Document', 'module_name' => 'Leads', 'link_field_name' => 'documents', 'fields' => [], 'relationships' => ['lead_id', 'document_id']],
        'documents_accounts' => ['label' => 'Relationship Document Account', 'module_name' => 'Documents', 'link_field_name' => 'accounts', 'fields' => [], 'relationships' => ['document_id', 'account_id']],
        'documents_contacts' => ['label' => 'Relationship Document Contact', 'module_name' => 'Documents', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['document_id', 'contact_id']],
        'documents_opportunities' => ['label' => 'Relationship Document Opportunity', 'module_name' => 'Documents', 'link_field_name' => 'opportunities', 'fields' => [], 'relationships' => ['document_id', 'opportunity_id']],
        'documents_cases' => ['label' => 'Relationship Document Case', 'module_name' => 'Documents', 'link_field_name' => 'cases', 'fields' => [], 'relationships' => ['document_id', 'case_id']],
        'documents_bugs' => ['label' => 'Relationship Document Bug', 'module_name' => 'Documents', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['document_id', 'bug_id']],
        'aos_quotes_aos_invoices' => ['label' => 'Relationship Quote Invoice', 'module_name' => 'AOS_Quotes', 'link_field_name' => 'aos_quotes_aos_invoices', 'fields' => [], 'relationships' => ['aos_quotes77d9_quotes_ida', 'aos_quotes6b83nvoices_idb']],
        'fp_events_contacts' => ['label' => 'Relationship Event Contact', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_contacts', 'fields' => ['accept_status', 'invite_status'], 'relationships' => ['fp_events_contactsfp_events_ida', 'fp_events_contactscontacts_idb']],
        'fp_events_leads_1' => ['label' => 'Relationship Event Lead', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_leads_1', 'fields' => ['accept_status', 'invite_status'], 'relationships' => ['fp_events_leads_1fp_events_ida', 'fp_events_leads_1leads_idb']],
        'fp_events_prospects_1' => ['label' => 'Relationship Event Prospect', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_prospects_1', 'fields' => ['accept_status', 'invite_status'], 'relationships' => ['fp_events_prospects_1fp_events_ida', 'fp_events_prospects_1prospects_idb']],
    ];

    protected string $customRelationship = 'MydCustRelSugar';

    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {

            //    if param connexion login is a string of at least 180 characters
            if (is_string($paramConnexion['login']) && strlen($paramConnexion['login']) > 180) {

                // it means that we have to use $this->paramConnexion instead
                $paramConnexion['login'] = $this->paramConnexion['login'];
                $paramConnexion['password'] = $this->paramConnexion['password'];
                $paramConnexion['url'] = $this->paramConnexion['url'];
                $paramConnexion['client_id'] = $this->paramConnexion['client_id'];
                $paramConnexion['client_secret'] = $this->paramConnexion['client_secret'];
            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $paramConnexion['url'] . '/login',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => 1,  // Enable header output
            ));

            $response = curl_exec($curl);

            // Separate headers and body
            list($headers, $body) = explode("\r\n\r\n", $response, 2);

            // Match Set-Cookie headers
            preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches);

            $cookies = array();
            foreach ($matches[1] as $item) {
                parse_str($item, $cookie);
                $cookies = array_merge($cookies, $cookie);
            }

            // Extract XSRF-TOKEN
            $xsrfToken = $cookies['XSRF-TOKEN'];
            $phpsessid = $cookies['PHPSESSID'];
            $legacySessid = $cookies['LEGACYSESSID'];

            curl_close($curl);



            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $paramConnexion['url'] . '/Api/access_token',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
    "grant_type": "password",
    "username": "' . $paramConnexion['login'] . '",
    "password": "' . $paramConnexion['password'] . '",
    "client_id": "e7c35a46-9738-b555-d68c-6527ff03c34c",
    "client_secret": "cocoronochizu"
}',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Grant-Type: password-credentials',
                    'Cookie: LEGACYSESSID=' . $legacySessid . '; PHPSESSID=' . $phpsessid . '; XSRF-TOKEN=' . $xsrfToken . '; sugar_user_theme=suite8'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            $response_array = json_decode($response, true);
            $access_token = $response_array['access_token'];
            $refresh_token = $response_array['refresh_token'];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $paramConnexion['url'] . '/login',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $access_token,
                    'Cookie: LEGACYSESSID=' . $legacySessid . '; PHPSESSID=' . $phpsessid . '; XSRF-TOKEN=' . $xsrfToken . '; sugar_user_theme=suite8'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            $result = json_decode($response);

            if (false != $result) {
                // assign this session as an array containing the following key values
                // token, refresh token, url
                $this->session = [
                    'token' => $access_token,
                    'refresh_token' => $refresh_token,
                    'url' => $paramConnexion['url'],
                    'xsrfToken' => $xsrfToken,
                    'phpsessid' => $phpsessid,
                    'legacySessid' => $legacySessid,
                ];

                $this->connexion_valide = true;
            } else {
                throw new \Exception('Please check url');
            }
        } catch (\Exception $e) {
            $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    public function logout(): bool
    {
        try {
            // $logout_parameters = ['session' => $this->session];
            // $this->call('logout', $logout_parameters, $this->paramConnexion['url']);

            $curl = curl_init();

            $curlUrl = $this->session['url'] . '/Api/V8/logout';

            curl_setopt_array($curl, array(
                CURLOPT_URL => $curlUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->session['token'],
                    'Cookie: LEGACYSESSID=' . $legacySessid . '; PHPSESSID=' . $phpsessid . '; XSRF-TOKEN=' . $xsrfToken . '; sugar_user_theme=suite8'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);


            $decodedResponse = json_decode($response);

            if($decodedResponse->meta->message === "You have been successfully logged out")
            {
                return true;
            }



        } catch (\Exception $e) {
            $this->logger->error('Error logout REST ' . $e->getMessage());

            return false;
        }
    }

    public function getFieldsLogin(): array
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
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
            [
                'name' => 'client_id',
                'type' => TextType::class,
                'label' => 'solution.fields.client_id',
            ],
            [
                'name' => 'client_secret',
                'type' => TextType::class,
                'label' => 'solution.fields.client_secret',
            ],
        ];
    }

    // Permet de récupérer tous les modules accessibles à l'utilisateur
    public function get_modules($type = 'source')
    {
        try {


            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->session['url'] . '/Api/V8/meta/modules',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->session['token'],
                    'Cookie: LEGACYSESSID=' . $legacySessid . '; PHPSESSID=' . $phpsessid . '; XSRF-TOKEN=' . $xsrfToken . '; sugar_user_theme=suite8'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            $modules = json_decode($response);

            // modules is a std class called data
            // we need to extract the modules that are in attributes, it is an array
            // each element of the array is a std class with 2 attributes : access and label
            // for now we only want to have a an array of labels
            $modules = $modules->data;
            $modulesAttributes = $modules->attributes;
            $modulesFinal = [];
            foreach ($modulesAttributes as $index => $module) {
                $modulesFinal[$index] = $module->label;
            }





            return (isset($modulesFinal)) ? $modulesFinal : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    // Permet de récupérer tous les champs d'un module
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->session['url'] . '/Api/V8/meta/fields/' . $module,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->session['token'],
                    'Cookie: LEGACYSESSID=' . $legacySessid . '; PHPSESSID=' . $phpsessid . '; XSRF-TOKEN=' . $xsrfToken . '; sugar_user_theme=suite8'
                ),
            ));

            $response = curl_exec($curl);

            $responseData = json_decode($response);

            $data = $responseData->data;

            $attributes = $data->attributes;

            $moduleFields = [];

            // moduleFields should be an array of array, and for each field, ther should be an array with the following keys: label, required, type, type_bdd, relate
            foreach ($attributes as $index => $field) {
                $moduleFields[$index]['label'] = $field->label;
                $moduleFields[$index]['required'] = $field->required;
                $moduleFields[$index]['type'] = $field->type;
                $moduleFields[$index]['type_bdd'] = $field->type_bdd;
                $moduleFields[$index]['relate'] = $field->relate;
            }

            curl_close($curl);
            // echo $response;


            return $moduleFields;
        } catch (\Exception $e) {
            return false;
        }
    }



    // Permet de lire les données

    /**
     * @throws \Exception
     */
    public function read($param)
    {

        $result = [];

        // if there is a record id in the query, then we are running the rule by id
        if (!empty($param['query']['id'])) {
            $result[] = $this->readOneRecord($param);
        }

        // if there is no record id in the query, then we are running the rule normally or running a simulation inside the source connector, with the reference date
        if (empty($param['query']['id'])) {
            $result = $this->readSeveralRecords($param);
        }

        return $result;
    }

    // Function to set the url to a format that will be suitable for curl, as the date format is not suitable for curl
    public function encodeUrlApiRequest($url)
    {
        // Parsing the URL into components
        $components = parse_url($url);

        // Parsing the query string into an associative array
        parse_str($components['query'], $params);

        // Building the modified query string
        $modified_query = http_build_query($params);

        // Encoding the spaces and colons
        $encoded_query = str_replace(array(' ', ':'), array('%20', '%3A'), $modified_query);

        // Building the modified URL
        $modified_url = $components['scheme'] . '://' . $components['host'] . $components['path'] . '?' . $encoded_query;

        // Output the modified URL
        return $modified_url;
    }

    // Function to read several records using the reference date
    public function readSeveralRecords($param)
    {
        $fieldsFormattedParams = $this->formatFieldsParams($param);

        $daterefFilter = $this->createDateRefFilter($param);

        $curlUrl = $this->createCurlUrl($fieldsFormattedParams, $daterefFilter, $param['module']);

        $response = $this->getCurlResponse($curlUrl);

        return $this->processResponseData($response);
    }

    // Function to get the fields to put in the curl request url
    private function formatFieldsParams($param)
    {
        $fields = $param['fields'];
        $module = $param['module'];

        $fieldnames = implode(',', $fields);
        return 'fields[' . $module . ']=' . $fieldnames;
    }

    // Function get and format the reference date to put in the curl request url
    private function createDateRefFilter($param)
    {
        $dateRefField = $this->getRefFieldName($param);
        return '&filter[' . $dateRefField . '][GT]=' . $param['date_ref'];
    }

    // Function combine the params, the fields and the date reference to create the curl url
    private function createCurlUrl($fieldsFormattedParams, $daterefFilter=null, $module, $recordId=null)
    {
        $url = '';
        // if record id is not null
        if($recordId !== null)
        {
            $url = $this->session['url'] . '/Api/V8/module/' . $module . '/' . $recordId . '?' . $fieldsFormattedParams . $daterefFilter;
        }
        else
        {
            $url = $this->session['url'] . '/Api/V8/module/' . $module . '?' . $fieldsFormattedParams . $daterefFilter;
        }
        return $url;
    }

    // Function to encode and launch the curl request
    private function getCurlResponse($curlUrl)
    {
        $encodedCurlUrl = $this->encodeUrlApiRequest($curlUrl);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $encodedCurlUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->session['token'],
                'Cookie: sugar_user_theme=suite8'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    // Function to process the response data
    private function processResponseData($response)
    {
        $decodedResponse = json_decode($response);
        $data = $decodedResponse->data;

        // use the array_map function to apply the following processing to each element of the array $data
        return array_map(function ($object) {
            $item = (array) $object;
            $attributes = $item['attributes'];

            foreach ($attributes as $key => $value) {
                $item[$key] = $value;
            }

            // unset the attributes and relationships keys because they are not value, but just links to other data provided by the auto-discoverability of the api
            unset($item['attributes'], $item['relationships']);

            // Convert the date_modified to a string of the following format "2023-09-07 06:57:19"
            if (isset($item['date_modified']) && preg_match('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $item['date_modified'])) {
                $dateTimeAttribute = new DateTime($item['date_modified']);
                $item['date_modified'] = $dateTimeAttribute->format('Y-m-d H:i:s');
            }

            // do the same for date_entered
            if (isset($item['date_entered']) && preg_match('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $item['date_entered'])) {
                $dateTimeAttribute = new DateTime($item['date_entered']);
                $item['date_entered'] = $dateTimeAttribute->format('Y-m-d H:i:s');
            }

            return $item;
        }, $data);
    }


    public function readOneRecord($param)
    {
        $fieldsFormattedParams = $this->formatFieldsParams($param);

        $curl = curl_init();

        $curlUrl = $this->createCurlUrl($fieldsFormattedParams, $daterefFilter, $param['module'], $param['query']['id']);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $curlUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->session['token'],
                'Cookie: LEGACYSESSID=' . $legacySessid . '; PHPSESSID=' . $phpsessid . '; XSRF-TOKEN=' . $xsrfToken . '; sugar_user_theme=suite8'
            ),
        ));

        $response = curl_exec($curl);
        
        curl_close($curl);
        
        $decodedResponse = json_decode($response);

        // if there is an error in the response, we return the error
        if (!empty($decodedResponse->errors->detail)) {
            throw new \Exception($decodedResponse->errors->detail);
        }

        $responseData = $decodedResponse->data;
        $attributes = $responseData->attributes;
        
        $result = [];
        $result['id'] = $responseData->id;
        $result['type'] = $responseData->type;
        
        try {
            //! Warning ! the following fields result in a std class, so we avoid them:
            //! member_of, campaign_accounts
            //! if the field is an std class, we avoid it
            foreach ($attributes as $index => $attribute) {
                // if attribute format is like "2023-10-12T08:52:00+00:00", then we use the DateTime class to format it
                if (!(is_object($attribute)) && preg_match('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $attribute)) {
                    $dateTimeAttribute = new DateTime($attribute);
                    // then we convert data attribute to a string of the following format "2023-09-07 06:57:19"
                    $dateTimeAttributeString = $dateTimeAttribute->format('Y-m-d H:i:s');
                    $result[$index] = $dateTimeAttributeString;
                    
                } else {
                    $result[$index] = $attribute;
                }
            }
        } catch (\Exception $e) {
            $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
        }
        
        return $result;
    }

    // Build the direct link to the record (used in data transfer view)
    public function getDirectLink($rule, $document, $type): string
    {
        // Get url, module and record ID depending on the type
        if ('source' == $type) {
            $url = $this->getConnectorParam($rule->getConnectorSource(), 'url');
            $module = $rule->getModuleSource();
            $recordId = $document->getSource();
        } else {
            $url = $this->getConnectorParam($rule->getConnectorTarget(), 'url');
            $module = $rule->getModuleTarget();
            $recordId = $document->gettarget();
        }

        // Build the URL (delete if exists / to be sure to not have 2 / in a row)
        return rtrim($url, '/') . '/index.php?module=' . $module . '&action=DetailView&record=' . $recordId;
    }

    protected function readRelationship($param, $dataParent): array
    {
        if (empty($param['limit'])) {
            $param['limit'] = 100;
        }
        $result['error'] = '';
        $i = 0;
        // Pour toutes les données parents, on récupère toutes les données liées de la relation
        if (!empty($dataParent['values'])) {
            $module_relationship_many_to_many = $this->module_relationship_many_to_many[$param['module']];

            foreach ($dataParent['values'] as $parent) {
                $get_relationships_parameters = [
                    'session' => $this->session,
                    'module_name' => $module_relationship_many_to_many['module_name'],
                    'module_id' => $parent['id'],
                    'link_field_name' => $module_relationship_many_to_many['link_field_name'],
                    'related_module_query' => '',
                    'related_fields' => ['id'],
                    'related_module_link_name_to_fields_array' => [],
                    'deleted' => '0',
                    'order_by' => '',
                    'offset' => 0,
                    'limit' => $param['limit'],
                ];
                $get_entry_list_result = $this->call('get_relationships', $get_relationships_parameters);

                if (!empty($get_entry_list_result)) {
                    $record = [];
                    foreach ($get_entry_list_result->entry_list as $entry) {
                        // R2cupération de l'id parent
                        $record[$module_relationship_many_to_many['relationships'][0]] = $parent['id'];
                        foreach ($entry->name_value_list as $value) {
                            if ('id' == $value->name) {
                                $record[$module_relationship_many_to_many['relationships'][1]] = $value->value;
                            } else {
                                $record[$value->name] = $value->value;
                            }
                        }
                        // La date de référence de chaque relation est égale à la date de référence du parent
                        $record['date_modified'] = $parent['date_modified'];
                        // L'id de la relation est généré en concatenant les 2 id
                        $record['id'] = $record[$module_relationship_many_to_many['relationships'][0]] . $record[$module_relationship_many_to_many['relationships'][1]];
                        $result['values'][$record['id']] = $record;
                        $record = [];
                        ++$i;
                    }
                } else {
                    $result['error'] .= $get_entry_list_result->number . ' : ' . $get_entry_list_result->name . '. ' . $get_entry_list_result->description . '       ';
                }
            }
        }
        $result['count'] = $i;

        return $result;
    }




    // Permet de créer les relation many-to-many (considéré comme un module avec 2 relation 1-n dans Myddleware)

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function createRelationship($param)
    {
        foreach ($param['data'] as $idDoc => $data) {
            try {
                // Check control before create
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                $dataSugar = [];
                if (!empty($this->module_relationship_many_to_many[$param['module']]['fields'])) {
                    foreach ($this->module_relationship_many_to_many[$param['module']]['fields'] as $field) {
                        if (isset($data[$field])) {
                            $dataSugar[] = ['name' => $field, 'value' => $data[$field]];
                        }
                    }
                }
                $set_relationship_params = [
                    'session' => $this->session,
                    'module_name' => $this->module_relationship_many_to_many[$param['module']]['module_name'],
                    'module_id' => $data[$this->module_relationship_many_to_many[$param['module']]['relationships'][0]],
                    'link_field_name' => $this->module_relationship_many_to_many[$param['module']]['link_field_name'],
                    'related_ids' => [$data[$this->module_relationship_many_to_many[$param['module']]['relationships'][1]]],
                    'name_value_list' => $dataSugar,
                    'delete' => (!empty($data['deleted']) ? 1 : 0),
                ];
                $set_relationship_result = $this->call('set_relationship', $set_relationship_params);

                if (!empty($set_relationship_result->created)) {
                    $result[$idDoc] = [
                        'id' => $idDoc, // On met $idDoc car onn a pas l'id de la relation
                        'error' => false,
                    ];
                } else {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => '01',
                    ];
                }
            } catch (\Exception $e) {
                $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
            // Modification du statut du flux
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    // Create method :
    // - input : array with the record's data
    // - output : the id of the new record
    // An exception has to be generated when an error happends during the creation.
    // this exception will be catched by the method createData
    protected function create($param, $record, $idDoc = null)
    {

        $newData = [
            "data" => [
                "type" => $param['module'],
                "attributes" => []
            ]
        ];

        // loop through record to populate the attributes in the new structure, except for the id
        foreach ($record as $key => $value) {
            // Important de renommer le champ id pour que SuiteCRM puisse effectuer une modification et non une création
            if ('id' == $key) {
                continue;
            }

            $newData['data']['attributes'][$key] = $value;
        }

        $newDataJson = json_encode($newData);

        $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $this->session['url'] . '/Api/V8/module',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $newDataJson,
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->session['token'],
                        'Cookie: sugar_user_theme=suite8'
                    ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);

                // decode the response
                $decodedResponse = json_decode($response);
                if (!empty($decodedResponse->errors)) {
                    throw new \Exception($decodedResponse->errors->detail);
                }

                // the response is a std class with a data attribute and in that data there is the id of the new record
                $newRecordId = $decodedResponse->data->id;


                // if the response does not contain errors
                return $newRecordId;
    }

    protected function update($param, $data, $idDoc = null)
    {
        $newData = [
            "data" => [
                "type" => $param['module'],
                "id" => $data['target_id'],
                "attributes" => []
            ]
        ];

        // loop through record to populate the attributes in the new structure, except for the id
        foreach ($data as $key => $value) {
            // Remove the id from the data, because it is not a field that can be updated
            if ('id' == $key || 'target_id' == $key) {
                continue;
            }

            $newData['data']['attributes'][$key] = $value;
        }

        $newDataJson = json_encode($newData);

        $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $this->session['url'] . '/Api/V8/module',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'PATCH',
                    CURLOPT_POSTFIELDS => $newDataJson,
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->session['token'],
                        'Cookie: sugar_user_theme=suite8'
                    ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);

                // decode the response
                $decodedResponse = json_decode($response);

                if (isset($decodedResponse->errors)) {
                    throw new \Exception($decodedResponse->errors->detail);
                }
                $newRecordId = $decodedResponse->data->id;


                return $newRecordId;
    }

    // Function to send a note
    protected function setNoteAttachement($data, $noteId)
    {
        $setNoteAttachementParameters = array(
            'session' => $this->session,
            'note' => array(
                'id' => $noteId,
                'filename' => $data['filename'],
                'file' => $data['filecontents'],
            ),
        );

        $set_not_attachement_result = $this->call('set_note_attachment', $setNoteAttachementParameters);
        if (
            empty($set_not_attachement_result->id)
            or (!empty($set_not_attachement_result->id)
                and $set_not_attachement_result->id == '-1'
            )
        ) {
            throw new \Exception('Failed to create the attachement on the note. ');
        }
    }


    protected function delete($param, $data)
    {
        $idDoc = $data['id_doc_myddleware'];

        $param['data'][$idDoc]['deleted'] = 1;

        $curl = curl_init();

        $curlUrl = $this->session['url'] . '/Api/V8/module/' . $param['module'] . '/' . $data['target_id'];

        curl_setopt_array($curl, array(
            CURLOPT_URL => $curlUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->session['token'],
                'Cookie: LEGACYSESSID=' . $legacySessid . '; PHPSESSID=' . $phpsessid . '; XSRF-TOKEN=' . $xsrfToken . '; sugar_user_theme=suite8'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $decodedResponse = json_decode($response);

        // if the response contains the following "Accounts module with id a5430019-afa7-b33d-f2ca-653f6fce7676 is not found" then we consider that the record has been deleted and we throw an error
        if (strpos($decodedResponse->errors->detail, 'is not found')) {
            throw new \Exception('Failed to delete the record. ' . $decodedResponse->meta->message);
        }

        // get the id from the api response
        $deletedId = substr($decodedResponse->meta->message, 15, 36);

        return $deletedId;
    }

    // Build the query for read data to SuiteCRM
    /**
     * @throws \Exception
     */
    protected function generateQuery($param, $method): string
    {
        $query = '';
        // if a specific query is requeted we don't use date_ref
        if (!empty($param['query'])) {
            foreach ($param['query'] as $key => $value) {
                if (!empty($query)) {
                    $query .= ' AND ';
                }
                if ('email1' == $key) {
                    $query .= strtolower($param['module']) . ".id in (SELECT eabr.bean_id FROM email_addr_bean_rel eabr JOIN email_addresses ea ON (ea.id = eabr.email_address_id) WHERE eabr.deleted=0 and ea.email_address LIKE '" . $value . "') ";
                } else {
                    // Pour ProspectLists le nom de la table et le nom de l'objet sont différents
                    if ('ProspectLists' == $param['module']) {
                        $query .= 'prospect_lists.' . $key . " = '" . $value . "' ";
                    } elseif ('Employees' == $param['module']) {
                        $query .= 'users.' . $key . " = '" . $value . "' ";
                    } else {
                        $query .= strtolower($param['module']) . '.' . $key . " = '" . $value . "' ";
                    }
                }
            }
            // Filter by date only for read method (no need for read_last method
        } elseif ('read' == $method) {
            $dateRefField = $this->getRefFieldName($param);
            // Pour ProspectLists le nom de la table et le nom de l'objet sont différents
            if ('ProspectLists' == $param['module']) {
                $query = 'prospect_lists.' . $dateRefField . " > '" . $param['date_ref'] . "'";
            } elseif ('Employees' == $param['module']) {
                $query = 'users.' . $dateRefField . " > '" . $param['date_ref'] . "'";
            } else {
                $query = strtolower($param['module']) . '.' . $dateRefField . " > '" . $param['date_ref'] . "'";
            }
        }

        return $query;
    }

    // Permet de renvoyer le mode de la règle en fonction du module target
    // Valeur par défaut "0"
    // Si la règle n'est qu'en création, pas en modicication alors le mode est C
    // public function getRuleMode($module, $type): array
    // {
    // if (
    // 'target' == $type
    // && array_key_exists($module, $this->module_relationship_many_to_many)
    // ) {
    // return [
    // 'C' => 'create_only',
    // ];
    // }

    // return parent::getRuleMode($module, $type);
    // }

    // Renvoie le nom du champ de la date de référence en fonction du module et du mode de la règle

    /**
     * @throws \Exception
     */
    public function getRefFieldName($param): string
    {
        if (in_array($param['ruleParams']['mode'], ['0', 'S', 'U'])) {
            return 'date_modified';
        } elseif ('C' == $param['ruleParams']['mode']) {
            return 'date_entered';
        }
        throw new \Exception("$param[ruleParams][mode] is not a correct Rule mode.");
    }

    // Get the list of field (name and id) for each custom relationship
    protected function getCustomRelationshipListFields($module): array
    {
        $get_module_fields_parameters = [
            'session' => $this->session,
            'module_name' => $module,
        ];
        $get_module_fields = $this->call('get_module_fields', $get_module_fields_parameters);
        // Get all custom relationship fields
        if (!empty($get_module_fields->link_fields)) {
            foreach ($get_module_fields->link_fields as $field) {
                if (
                    '_id' == substr($field->name, -3)
                    || '_ida' == substr($field->name, -4)
                    || '_idb' == substr($field->name, -4)
                    || ('id' == $field->type
                        && 'id' != $field->name
                    )
                ) {
                    // Build the result array to get the relationship name for all field name
                    $result[$field->name]['id'] = $this->customRelationship . $field->name;
                    $result[$field->name]['name'] = $this->customRelationship . $field->relationship . '_name';
                }
            }
        }

        return $result;
    }

    //function to make cURL request
    protected function call($method, $parameters)
    {
        try {
            ob_start();
            $curl_request = curl_init();
            curl_setopt($curl_request, CURLOPT_URL, $this->paramConnexion['url']);
            curl_setopt($curl_request, CURLOPT_POST, 1);
            curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            curl_setopt($curl_request, CURLOPT_HEADER, 1);
            curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);

            $jsonEncodedData = json_encode($parameters);
            $post = [
                'method' => $method,
                'input_type' => 'JSON',
                'response_type' => 'JSON',
                'rest_data' => $jsonEncodedData,
            ];

            curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post);
            $result = curl_exec($curl_request);
            curl_close($curl_request);
            if (empty($result)) {
                return false;
            }
            $result = explode("\r\n\r\n", $result, 2);
            $response = json_decode($result[1]);
            ob_end_flush();

            return $response;
        } catch (\Exception $e) {
            return false;
        }
    }
}
class suitecrm8 extends suitecrm8core
{
}
