<?php

/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 * This file is part of Myddleware.
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

namespace Myddleware\RegleBundle\Solutions;


class sagesdatacore extends solution
{

    const APPLICATION = "accounts50";
    const CONTRACT = "GCRM";
    private $access_token;
    protected $moduleFields;


    /**
     * Function login for connexion sagesData
     * Doc curl : https://curl.haxx.se/libcurl/c/libcurl-errors.html
     * @param $paramConnexion
     * @return array
     */
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            // Call to get the token
            $this->token = base64_encode($this->paramConnexion['login'] . ':' . $this->paramConnexion['password']);
            $this->response = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/bankAccounts?select=name&count=1');

            if ($this->response['curlErrorNumber'] === 0) { // url all fine . precced as usual
                if (!empty($this->response['curlInfo']) && $this->response['curlInfo']['http_code'] === 200) { // token is valid
                    $this->connexion_valide = true;
                    $this->setAccessToken($this->token);

                } else if (!empty($this->response['curlInfo']) && $this->response['curlInfo']['http_code'] === 401) { //if 401 non unauthorized
                    throw new \Exception('Bad auth key');
                } else {
                    throw new \Exception('Error connexion for sagesData');
                }

            } else {
                throw new \Exception('No response from sagesData.');
            }
        } catch (\Exception $e) {
            $error = 'Failed to login to sagesData : ' . $e->getMessage();
            echo $error . ';';
            $this->logger->error($error);
            return array('error' => $error);
        }
    } // login($paramConnexion)

    public function getAccessToken()
    {
        return $this->access_token;
    }

    public function setAccessToken($token)
    {
        $this->access_token = $token;
    }

    public function getFieldModules($index)
    {
        return $this->moduleFields = $this->moduleFields[$index];
    }

    /**
     * Function HTTP Request
     *
     * @param $server
     * @param $token
     * @param $path
     * @param null $args
     * @param string $method
     * @param null $data
     * @return array
     * @throws \Exception
     */
    function makeRequest($server, $token, $path, $args = null, $method = 'GET', $data = null)
    {
        if (function_exists('curl_init') && function_exists('curl_setopt')) {
            // The URL to use
            $ch = curl_init($server . $path);
            // Make sure params is empty or an array
            if (!empty($args)) {
                $value = json_encode($args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $value);
            }
            // Set authorization header properly
            $authPath = '/oauth\/token/';
            if (1 !== preg_match($authPath, $path)) {
                $authHeader = 'Authorization: Basic ' . $token;
                $contentType = 'Content-Type: application/json';
                if ("POST" == $method && "array" !== gettype($data)) {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    $data_string = json_encode($data);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                }
            } else {
                $authHeader = 'Authorization: Basic ' . $token;
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    $authHeader)
            );

            // Execute request
            $result = curl_exec($ch);

            //Object for response curl
            $response = array(
                'curlData' => $result,
                'curlInfo' => curl_getinfo($ch),
                'curlErrorNumber' => curl_errno($ch),
                'curlErrorMessage' => curl_error($ch)
            );

            // Close Connection
            curl_close($ch);

            return $response;
        }
        throw new \Exception('curl extension is missing!');
    }


    /**
     * Function list fields for login
     * @return array
     */
    public function getFieldsLogin()
    {
        return array(
            array(
                'name' => 'login',
                'type' => 'text',
                'label' => 'solution.fields.login'
            ),
            array(
                'name' => 'password',
                'type' => 'password',
                'label' => 'solution.fields.password'
            ),
            array(
                'name' => 'host',
                'type' => 'text',
                'label' => 'solution.fields.host'
            )
        );
    } // getFieldsLogin()


    /**
     * Function for get module of sage
     * @param string $type
     * @return array|void
     */
    public function get_modules($type = 'source')
    {
        try {
            // Call to get the token
            $this->token = $this->getAccessToken();
            $this->response = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/$schema');
            if (strlen($this->response['curlData']) > 0) { // url all fine . precced as usual
                $xml = simplexml_load_string($this->response['curlData']);
                if ($xml) {
                    $modules = $xml->xpath('//xs:element[@sme:canGet="true" and  @sme:role="resourceKind"]/@name');
                    foreach ($modules as $module) {
                        $this->moduleFields[(string)$module["name"]] = (string)$module["name"]; // get attribute who role is resourceKind and can get is true
                    }
                    return $this->moduleFields;
                } else {
                    throw new \Exception("Error: Cannot create object");
                }

            } else {
                throw new \Exception('No modules from sagesData.');
            }
        } catch (\Exception $e) {
            $error = 'Error get modules for sagesData : ' . $e->getMessage();
            echo $error . ';';
            $this->logger->error($error);
            return array('error' => $error);
        }

    }


    /**
     * Get the fields available for the module in input
     * @param $module
     * @param string $type
     * @return array|bool
     */
    public function get_module_fields($module, $type = 'source')
    {
        parent::get_module_fields($module, $type);
        try {
            // Call to get the token
            $this->token = $this->getAccessToken();
            $this->response = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/$schema');
            if (strlen($this->response['curlData']) > 0) { // url all fine . precced as usual
                $xml = simplexml_load_string($this->response['curlData']);
                if ($xml) {
                    $this->moduleFields = array();
                    $modules = $xml->xpath('//xs:complexType[@name="' . $module . '--type"]/xs:all/*'); // on recrée la requete avec l'element sélectionné
                    foreach ($modules as $module) {
                        if ((string)$module["nillable"]) {
                            $existRequired = 1;
                        } else {
                            $existRequired = 0;
                        }
                        str_replace('xs:', '', (string)$module['type']);
                        $this->moduleFields[(string)$module["name"]] = array('label' => (string)$module["name"], 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => $existRequired);
                    }
                    return $this->moduleFields;
                } else {
                    throw new \Exception("Error: Cannot create object");
                }

            } else {
                throw new \Exception('No modules from sagesData.');
            }


        } catch (\Exception $e) {
            return false;
        }
    } // get_module_fields($module)

    /**
     * Read one specific record
     * @param $params
     */
    public function read_last($param)
    {
        try {
            // Call to get the token
            $this->token = $this->getAccessToken();
            $this->response = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/' . $param["module"] . '?count=1');
            if (strlen($this->response['curlData']) > 0) { // url all fine . precced as usual
                //return array value

                //“1” if a data has been found
                //“0”if no data has been found and no error occured
                //“-1” if an error occured
            } else {
                throw new \Exception('No modules from sagesData.');
            }
        } catch (\Exception $e) {
            $error = 'Failed to read data to sagesData : ' . $e->getMessage();
            echo $error . ';';
            $this->logger->error($error);
            return array('error' => $error);
        }
    }


    /*    // Create data in the target solution
       public function create($param) {
           foreach($param['data'] as $idDoc => $data) {
               try {
                   // Check control before create
                   $data = $this->checkDataBeforeCreate($param, $data);

                    // XML creation
                   foreach ($data as $key => $value) {
                       // Field only used for the update and contains the ID of the record in the target solution
                       if ($key=='target_id') {
                           // If updade then we change the key in Id
                           if (!empty($value)) {
                               $key = 'Id';
                           } else { // If creation, we skip this field
                               continue;
                           }
                       }
                      $parameter[$key] = $value;
                   }

                   // Call to get the token
                   $this->token = $this->getAccessToken();
                   $dataSent = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/'.$param['module'], $parameter, 'POST');

                    // General error
                   if (!empty($dataSent['Message'])) {
                       throw new \Exception($dataSent['Message']);
                   }
                   if (!empty($dataSent['ErrorMessage'])) {
                       throw new \Exception($dataSent['ErrorMessage']);
                   }
                   // Error managment for the record creation
                   if (!empty($dataSent[$param['module']]['Success'])) {
                       if ($dataSent[$param['module']]['Success'] == 'False') {
                           throw new \Exception($dataSent[$param['module']]['ErrorMessage']);
                       } else {
                           $result[$idDoc] = array(
                               'id' => $dataSent[$param['module']]['GUID'],
                               'error' => false
                           );
                       }
                   } else {
                       throw new \Exception('No success flag returned by Cirrus Shield');
                   }
               }
               catch (\Exception $e) {
                   $error = $e->getMessage();
                   $result[$idDoc] = array(
                       'id' => '-1',
                       'error' => $error
                   );
               }
               // Transfert status update
               $this->updateDocumentStatus($idDoc,$result[$idDoc],$param);
           }
           return $result;
       } */
}

/* * * * * * * *  * * * * * *  * * * * * *
   if custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__ . '/../Custom/Solutions/sagesdata.php';
if (file_exists($file)) {
    require_once($file);
} else {
    //Sinon on met la classe suivante
    class sagesdata extends sagesdatacore
    {
    }
}
