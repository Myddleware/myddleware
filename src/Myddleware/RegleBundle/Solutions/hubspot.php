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

use Symfony\Component\HttpFoundation\Session\Session;
use \Datetime;

class hubspotcore extends solution
{

    protected $url = 'https://api.hubapi.com/';
    protected $version = 'v1';

    /* protected $token;
    protected $update;
    protected $organizationTimezoneOffset;

    protected $required_fields = array('default' => array('Id','CreationDate','ModificationDate'));

    protected $FieldsDuplicate = array(	'Contact' => array('Email','Name'),
                                        'default' => array('Name')
                                      ); */

    public function getFieldsLogin()
    {
        return array(
            array(
                'name' => 'apikey',
                'type' => 'password',
                'label' => 'solution.fields.apikey'
            )
        );
    }

    // Conect to Hubspot
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            // $this->paramConnexion['apikey'] = 'f91a946d-701e-4a0d-acdf-c5204c556901';
            $result = $this->call($this->url . 'properties/' . $this->version . '/contacts/properties?hapikey=' . $this->paramConnexion['apikey']);
            if (!empty($result['message'])) {
                throw new \Exception($result['message']);
            } elseif (empty($result)) {
                throw new \Exception('Failed to connect but no error returned by Hubspot. ');
            }
            $this->connexion_valide = true;
        } catch (\Exception $e) {
            $error = 'Failed to login to Hubspot : ' . $e->getMessage();
            echo $error . ';';
            $this->logger->error($error);
            return array('error' => $error);
        }
    } // login($paramConnexion)*/


    public function get_modules($type = 'source')
    {
        $modules = array(
            'companies' => 'Companies',
            'contacts' => 'Contacts',
            'deals' => 'Deals',
        );
        // Module to create relationship between deals and contacts/companies
        if ($type == 'target') {
            $modules['associate_deal'] = 'Associate deals with companies/contacts';
        }
        return $modules;
    } // get_modules()

    // Renvoie les champs du module passé en paramètre
    public function get_module_fields($module, $type = 'source')
    {
        parent::get_module_fields($module, $type);
        try {
            // Manage custom module to deal with associate_deal
            if ($module == 'associate_deal') {
                $result = array(
                    array('name' => 'deal_id', 'label' => 'Deal Id', 'type' => 'varchar(36)'),
                    array('name' => 'record_id', 'label' => 'Contact or company ID', 'type' => 'varchar(36)'),
                    array('name' => 'object_type', 'label' => 'Object Type', 'type' => 'varchar(36)', 'options' => array(array('value' => 'CONTACT', 'label' => 'Contact'), array('value' => 'COMPANY', 'label' => 'Company')))
                );
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
                if (substr($field['name'], -2) == 'id') {
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
    public function read_last($param)
    {
        try {

            $module = $this->getsingular($param['module']);

            if (!empty($param['fields'])) { //add properties for request
                $property = "";
                // Get the reference date field name
                if ($module === "companies" || $module === "deal") {
                    $properties = "properties";
                    $property .= "&properties=hs_lastmodifieddate";
                    $id = $module === "companies" ? "companyId" : "dealId";
                    $version = $module === "companies" ? "v2" : "v1";
                    $modified = "hs_lastmodifieddate";

                } else if ($module === "contact") {
                    $properties = "property";
                    $property .= "&property=lastmodifieddate";
                    $id = "vid";
                    $modified = "lastmodifieddate";
                }
                foreach ($param['fields'] as $fields) {
                    $property .= "&" . $properties . "=" . $fields;
                }
            }

            if (!empty($param['query'])) {
                if (!empty($param['query']['email'])) {
                    $resultQuery = $this->call($this->url . $param['module'] . "/v1/" . $param['module'] . "/email/" . $param['query']['email'] . "/profile?hapikey=" . $this->paramConnexion['apikey'] . $property);
                } elseif (!empty($param['query']['id'])) {
                    if ($module === "companies" || $module === "deal") {
                        $url_id = $this->url . $param['module'] . "/" . $version . "/" . $module . "/" . $param['query']['id'] . "?hapikey=" . $this->paramConnexion['apikey'] . "&count=1" . $property;
                    } else if ($module === "contact") {
                        $url_id = $this->url . $param['module'] . "/v1/" . $module . "/vid/" . $param['query']['id'] . "/profile?hapikey=" . $this->paramConnexion['apikey'] . $property;
                    }
                    $resultQuery = $this->call($url_id);
                } else {
                    //@todo  get word for request
                    $resultQuery = $this->call($this->url . $param['module'] . "/v1/search/query?q=hubspot" . "&count=1&hapikey=" . $this->paramConnexion['apikey'] . $property);
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
                }

                $identifyProfilesId = $resultQuery['exec'][$param['module']][0][$id];

                //on ajoute l'email car elle se trouve dans la proprietes
                $identifyProfiles = $resultQuery['exec'][$param['module']][0]['properties'];
            }
            // If no result
            if (empty($resultQuery)) {
                $result['done'] = false;
            } else {
                foreach ($param['fields'] as $field) {
                    if (isset($identifyProfiles[$field])) {
                        $result['values'][$field] = $identifyProfiles[$field]['value'];
                    }
                }
                $result['values']['id'] = $identifyProfilesId; // Add id
                $result['values']['date_modified'] = $identifyProfiles[$modified]['value']; // add date modified
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

    /**
     * Function read data
     * @param $param
     * @return mixed
     */
    public function read($param)
    {
        try {
            $dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
            $module = $this->getsingular($param['module']);

            // Get the reference date field name
            if ($module === "companies" || $module === "deal") {
                $version = $module === "companies" ? "v2" : "v1";
                if (!empty($param['fields'])) { //add properties for request
                    $property = "";
                    foreach ($param['fields'] as $fields) {
                        $property .= "&properties=" . $fields;
                    }
                    $property .= "&properties=hs_lastmodifieddate";
                }
                $url_modified = $this->url . $param['module'] . "/" . $version . "/" . $module . "/recent/modified/" . "?hapikey=" . $this->paramConnexion['apikey'] . $property;
                $ur_created = $this->url . $param['module'] . "/" . $version . "/" . $module . "/recent/created/" . "?hapikey=" . $this->paramConnexion['apikey'] . $property;

            } else if ($module === "contact") {
                if (!empty($param['fields'])) { //add properties for request
                    $property = "";
                    foreach ($param['fields'] as $fields) {
                        $property .= "&property=" . $fields;
                    }
                    $property .= "&property=lastmodifieddate";
                }
                $url_modified = $this->url . $param['module'] . "/v1/lists/recently_updated/" . $param['module'] . "/recent" . "?hapikey=" . $this->paramConnexion['apikey'] . $property;
                $ur_created = $this->url . $param['module'] . "/v1/lists/all/" . $param['module'] . "/recent" . "?hapikey=" . $this->paramConnexion['apikey'] . $property;
            }

            if ($dateRefField === "ModificationDate") {
                $result = $this->call($url_modified);
                $resultQuery = $this->getresultQuery($result, $url_modified, $param);

            } else if ($dateRefField === "CreationDate") {

                $result = $this->call($ur_created);
                $resultQuery = $this->getresultQuery($result, $ur_created, $param);
            }
            $resultQuery = $resultQuery['exec'];

            if ($module === "companies" || $module === "deal") {
                $identifyProfiles = $resultQuery['results'];
                $modified = "hs_lastmodifieddate";
                $id = $module === "companies" ? "companyId" : "dealId";

            } else if ($module === "contact") {
                $identifyProfiles = $resultQuery[$param['module']];
                $modified = "lastmodifieddate";
                $id = 'vid';

            }

            // If no result
            if (empty($resultQuery)) {
                $result['error'] = "Request error";
            } else {
                $timestampLastmodified = $identifyProfiles[0]["properties"][$modified]["value"];
                $result['date_ref'] = date('Y-m-d H:i:s', $timestampLastmodified / 1000);
                foreach ($identifyProfiles as $identifyProfile) {
                    $records = null;
                    foreach ($param['fields'] as $field) {
                        if (isset($identifyProfile["properties"] [$field])) {
                            $records[$field] = $identifyProfile["properties"] [$field]['value'];

                        }
                        $records['date_modified'] = $identifyProfile["properties"][$modified]['value']; // add date modified
                        $records['id'] = $identifyProfile[$id];
                        $result['values'][$identifyProfile[$id]] = $records;
                    }
                }
                $result['count'] = count($result['values']);
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
    public function create($param)
    {
        try {
            // Associate deal is always an update to Hubspot
            if ($param['module'] == 'associate_deal') {
                return $this->update($param);
            }
            // Tranform Myddleware data to Mailchimp data
            foreach ($param['data'] as $idDoc => $data) {

                $dataHubspot["properties"] = null;
                $records = array();

                //getsingular contact
                $module = $this->getsingular($param['module']);

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
    public function update($param)
    {
        try {

            $module = $this->getsingular($param['module']);
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
     * Function for get data
     * @param $request
     * @param $url
     * @param $param
     * @return array
     *
     */
    protected function getresultQuery($request, $url, $param)
    {
        if ($param['module'] === "contacts") {
            if ($request['exec']['time-offset'] === 0) {
                $result = $this->getresultQueryBydate($request['exec'][$param['module']], $param, false);
            } else {
                $timeOffset = $request['exec']['time-offset'];
                $vidOffset = $request['exec']['vid-offset'];
                $result = $this->getresultQueryBydate($request['exec'][$param['module']], $param, false);

                do {
                    $resultOffset = $this->call($url . "&timeOffset=" . $timeOffset . "&vidOffset=" . $vidOffset);
                    $timeOffset = $resultOffset['exec']['time-offset'];
                    $vidOffset = $resultOffset['exec']['vid-offset'];
                    $resultOffsetTemps = $this->getresultQueryBydate($resultOffset['exec'][$param['module']], $param, true);
                    $merge = array_merge($result['exec'][$param['module']], $resultOffsetTemps);
                    $result['exec'][$param['module']] = $merge;
                } while ($timeOffset >= $this->dateTimeToTimestamp($param["date_ref"]));
            }
        } elseif ($param['module'] === "deals" || $param['module'] === "companies") {
            if ($request['exec']['offset'] === $request['exec']['total']) {
                $result = $this->getresultQueryBydate($request['exec']['results'], $param, false);

            } else {
                $offset = $request['exec']['offset'];
                $total = $request['exec']['total'];
                $result = $this->getresultQueryBydate($request['exec']['results'], $param, false);

                do {
                    $resultOffset = $this->call($url . "&offset=" . $offset);
                    $offset = $resultOffset['exec']['offset'];
                    $resultOffsetTemps = $this->getresultQueryBydate($resultOffset['exec']['results'], $param, true);
                    $merge = array_merge($result['exec']['results'], $resultOffsetTemps);
                    $result['exec']['results'] = $merge;
                } while ($offset !== $total);
            }
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
    protected function getresultQueryBydate($request, $param, $offset)
    {
        $param['module'] === "deals" || $param['module'] === "companies" ? $modified = "hs_lastmodifieddate" : $modified = "lastmodifieddate";
        if (!$offset) {
            if ($param['module'] === "deals" || $param['module'] === "companies") {
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
        foreach ($request as $key => $item) {
            if ($item['properties'][$modified]['value'] > $dateTimestamp) {
                if (!$offset) {
                    array_push($result['exec'][$module], $item);
                } else {
                    array_push($result, $item);
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
    }// dateTimeToMyddleware($dateTime)

    /**
     * return the reference date field name
     * @param $moduleSource
     * @param $RuleMode
     * @return null|string
     * @throws \Exception
     */
    public function getDateRefName($moduleSource, $RuleMode)
    {
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
    public function getsingular($name)
    {
        if ($name === "contacts") {
            return "contact";
        } else if ($name === "companies") {
            return "companies";
        } else if ($name === "deals" OR $name === "associate_deal") {
            return "deal";
        }
    }

    /**
     * Performs the underlying HTTP request. Not very exciting
     * @param  string $method The API method to be called
     * @param  array $args Assoc array of parameters to be passed
     * @return array          Assoc array of decoded result
     */
    protected
    function call($url, $method = 'GET', $args = array(), $timeout = 120)
    {
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