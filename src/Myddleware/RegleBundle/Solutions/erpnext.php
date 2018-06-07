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

use DateTime;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Filesystem\Filesystem;

class erpnextcore extends solution
{

    // protected $url = 'https://www.cirrus-shield.net/RestApi/';
    protected $token;
    protected $update;
    protected $organizationTimezoneOffset;
    protected $limitCall = 100;

    protected $required_fields = array('default' => array('Id', 'CreationDate', 'ModificationDate'));

    protected $FieldsDuplicate = array('Contact' => array('Email', 'Name'),
        'default' => array('Name')
    );

    public function getFieldsLogin()
    {
        return array(
            array(
                'name' => 'url',
                'type' => 'text',
                'label' => 'solution.fields.url'
            ),
            array(
                'name' => 'login',
                'type' => 'text',
                'label' => 'solution.fields.login'
            ),
            array(
                'name' => 'password',
                'type' => 'password',
                'label' => 'solution.fields.password'
            )
        );
    }

    // Login to Cirrus Shield
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            // Generate parameters to connect to Cirrus Shield
            $parameters = array("usr" => $this->paramConnexion['login'],
                "pwd" => $this->paramConnexion['password']
            );
            $url = $this->paramConnexion['url'] . '/api/method/login';
            // Connect to ERPNext
            $result = $this->call($url, 'GET', $parameters);

            if (empty($result->message)) {
                throw new \Exception('Login error');
            }
            // Connection validation
            $this->connexion_valide = true;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);
            return array('error' => $error);
        }
    } // login($paramConnexion)


    // Get the modules available
    public function get_modules($type = 'source')
    {
        try {
            $url = $this->paramConnexion['url'] . '/api/resource/DocType';
            $parameters = array("limit_page_length" => 1000);
            $APImodules = $this->call($url, 'GET', $parameters);
            if (!empty($APImodules->data)) {
                foreach ($APImodules->data as $APImodule) {
                    $modules[$APImodule->name] = $APImodule->name;
                }
            }
            return $modules;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            return $error;
        }
    }

    // Get the fields available for the module in input
    public function get_module_fields($module, $type = 'source')
    {
        parent::get_module_fields($module, $type);
        try {
            // Get the list field for a module
            $url = $this->paramConnexion['url'] . '/api/method/frappe.client.get_list?doctype=DocField&parent=' . $module . '&fields=*&filters={%22parent%22:%22' . $module . '%22}&limit_page_length=500';
            $recordList = $this->call($url, 'GET', '');

            // Format outpput data
            if (!empty($recordList->message)) {
                foreach ($recordList->message as $field) {
                    if (empty($field->label)) {
                        continue;
                    }
                    if ($field->fieldtype == 'Link') {
                        $this->fieldsRelate[$field->fieldname] = array(
                            'label' => $field->label,
                            'type' => 'varchar(255)',
                            'type_bdd' => 'varchar(255)',
                            'required' => '',
                            'required_relationship' => '',
                        );
                    } else {
                        $this->moduleFields[$field->fieldname] = array(
                            'label' => $field->label,
                            'type' => 'varchar(255)',
                            'type_bdd' => 'varchar(255)',
                            'required' => '',
                        );
                        if (!empty($field->options)) {
                            $options = explode(chr(10), $field->options);
                            if (
                                !empty($options)
                                AND count($options) > 1
                            ) {
                                foreach ($options as $option) {
                                    $this->moduleFields[$field->fieldname]['option'][$option] = $option;
                                }
                            }
                        }
                    }
                }
            } else {
                throw new \Exception('No data in the module ' . $module . '. Failed to get the field list.');
            }

            // Add relate field in the field mapping
            if (!empty($this->fieldsRelate)) {
                $this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
            }
            return $this->moduleFields;
        } catch (\Exception $e) {
            return false;
        }
    } // get_module_fields($module)


    /**
     * Get the last data in the application
     * @param $param
     * @return mixed
     * @throws \Exception
     */
    public function read_last($param)
    {
        try {
            $module = $param['module'];
            $fields = $param['fields'];
            $result = array();
            $record = array();
            if (!empty($param['query'])) {
                foreach ($param['query'] as $key => $value) {
                    if ($key === 'id') {
                        $key = 'name';
                    }
                    $filters_result[$key] = $value;
                }
                $filters = json_encode($filters_result);
                $data = array('filters' => $filters, 'fields' => '["*"]');
                $q = http_build_query($data);
                $url = $this->paramConnexion['url'] . '/api/resource/' . $module . '?' . $q;
                $resultQuery = $this->call($url, "GET", '');
                $record = $resultQuery->data[0]; // on formate pour qu'il refactoré le code des $result['values"]

//                if (!empty($param['query']['email'])) {
//                    $email = $param['query']['email'];
//                    $filters = '{"email_id": ["=", "' . $email . '"]}';
//                    $data = array('filters' => $filters, 'fields' => '["*"]');
//                    $q = http_build_query($data);
//                    $url = $this->paramConnexion['url'] . '/api/resource/' . $module . '?' . $q;
//                    $resultQuery = $this->call($url, "GET", '');
//                    $record = $resultQuery->data[0]; // on formate pour qu'il refactoré le code des $result['values"]
//
//                } elseif (!empty($param['query']['id'])) { // Je ne vois pas l'id dans les requeetes, idx ?
//                    $id = $param['query']['id'];
//                    // $id = 'TEST MARTIN';
//                    $filters = '{"name": ["=", "' . $id . '"]}';
//                    $data = array('filters' => $filters, 'fields' => '["*"]');
//                    $q = http_build_query($data);
//                    $url = $this->paramConnexion['url'] . '/api/resource/' . $module . '?' . $q;
//
//                    $resultQuery = $this->call($url, "GET", '');
//                    $record = $resultQuery->data[0]; // on formate pour qu'il refactoré le code des $result['values"]
//                }
                // by query
            } else {
                print_r('search by query');
                $data = array('limit_page_length' => 1, 'fields' => '["*"]');
                $q = http_build_query($data);
                $url = $this->paramConnexion['url'] . '/api/resource/' . $module . '?' . $q;
                //get list of modules
                $resultQuery = $this->call($url, "GET", '');
                $record = $resultQuery->data[0];

            }
            if (!empty($record)) {
                foreach ($fields as $field) {
                    $result['values'][$field] = $record->$field; // last record
                }
                $result['values']['date_modified'] = $record->modified; // modified
            }

            // If no result
            if (empty($resultQuery)) {
                $result['done'] = false;
            } else {
                if (!empty($result['values'])) {
                    $result['done'] = true;
                }
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : ' . $e->getMessage() . ' ' . __CLASS__ . ' Line : ( ' . $e->getLine() . ' )';
            $result['done'] = -1;
        }
        return $result;
    } //end read_last

    /**
     * Function read data
     * @param $param
     * @return mixed
     */
    public function read($param)
    {
        try {
            $module = $param['module'];
            $fields = $param['fields'];
            $date_ref = $param['date_ref'];
            $result = array();
            $filters_result = array();
//            $param['query'] = array("id" => 'TEST MARTIN' ,"first_name"=>"TEST");
            if (!empty($param['query'])) { // Je ne vois pas l'id dans les requeetes, idx ?
                foreach ($param['query'] as $key => $value) {
                    if ($key === 'id') {
                        $key = 'name';
                    }
                    $filters_result[$key] = $value;
                }
                $filters = json_encode($filters_result);
                $data = array('filters' => $filters, 'fields' => '["*"]');
            } else {
                $filters = '{"modified": [">", "' . $date_ref . '"]}';
                $data = array('filters' => $filters, 'fields' => '["*"]');
            }
            $q = http_build_query($data);
            $url = $this->paramConnexion['url'] . '/api/resource/' . $module . '?' . $q;
            $resultQuery = $this->call($url, 'GET', '');

            // If no result
            if (empty($resultQuery) && empty($resultQuery->data)) {
                $result['error'] = "Request error";
            } else if (count($resultQuery->data) > 0) {
                $resultQuery = $resultQuery->data;
                foreach ($resultQuery as $key => $recordList) {
                    $records = null;
                    foreach ($fields as $field) {
                        $records[$field] = $recordList->$field;
                        $records['date_created'] = $recordList->creation;
                        $records['date_modified'] = $recordList->modified;
                    }
                    $records['id'] = $recordList->name;
                    $result['values'][$recordList->name] = $records; // last record
                }
                if ($resultQuery[0] && $resultQuery[0]->modified > $date_ref) {
                    $result['date_ref'] = $resultQuery[0]->modified;
                }
                $result['count'] = count($resultQuery);
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : ' . $e->getMessage() . ' ' . __CLASS__ . ' Line : ( ' . $e->getLine() . ' )';
        }
        return $result;
    }// end function read

    public function create($param)
    {
        return $this->CreateOrUpdate('create', $param);

    }// end function create

    /**
     * Function update data
     * @param $param
     * @return mixed
     */
    public function update($param)
    {
        return $this->CreateOrUpdate('update', $param);

    }// end function create


    /**
     * Function for create or update data
     * @param $method
     * @param $param
     * @return array
     */
    function CreateOrUpdate($method, $param)
    {
        try {
            $result = array();
            if ($method === 'update') {
                $method = "PUT";
                $url = $this->paramConnexion['url'] . "/api/resource/" . $param['module'] . "/" . $param['target_id'];

            } elseif ($method === 'create') {
                $method = "POST";
                $url = $this->paramConnexion['url'] . "/api/resource/" . $param['module'];

            }
            foreach ($param['data'] as $idDoc => $data) {
                if (in_array($idDoc, array('target_id', 'Myddleware_element_id'))) {
                    continue;
                }
                $resultQuery = $this->call($url, $method, array('data' => json_encode($data)));
                $result[$idDoc] = array('id' => $resultQuery->data->name, 'error' => '');
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            print_r('error', $error);
            $result[$idDoc] = array(
                'id' => '-1',
                'error' => $error
            );
        }
        return $result;

    }


    protected function call($url, $method = 'GET', $parameters = array(), $timeout = 300)
    {


        if (!function_exists('curl_init') OR !function_exists('curl_setopt')) {
            throw new \Exception('curl extension is missing!');
        }
        $fileTmp = $this->container->getParameter('kernel.cache_dir') . '/myddleware/solutions/erpnext/erpnext.txt';
        $fs = new Filesystem();
        try {
            $fs->mkdir(dirname($fileTmp));
        } catch (IOException $e) {
            throw new \Exception ($this->tools->getTranslation(array('messages', 'rule', 'failed_create_directory')));
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // common description bellow
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $fileTmp);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $fileTmp);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $response = curl_exec($ch);

        // 200? 404? or something?
        $error_no = curl_errno($ch);
        curl_close($ch);
        if ($error_no != 200) {
            // do something for login error
            // return or exit

        }
        if (JSON_ERROR_NONE == json_last_error()) {
            // $response is not valid (as JSON)
            // do something for login error
            // return or exit
        }
//        var_dump(json_decode($response));

        return json_decode($response);
    }

}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__ . '/../Custom/Solutions/erpnext.php';
if (file_exists($file)) {
    require_once($file);
} else {
    //Sinon on met la classe suivante
    class erpnext extends erpnextcore
    {

    }
}