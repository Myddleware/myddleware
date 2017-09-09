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
    protected $fieldsRelate = array();
    protected $fieldsNotRelate = array('Opportunity' => array('assigneduserid' => true), 'PhoneLink' => array('entityid' => true), 'EmailLink' => array('entityid' => true));
    protected $required_fields = array('default' => array('updateddate', 'createddate'));
    protected $FieldsDuplicate = array();
    protected $required_relationships = array(
        'default' => array()
    );

    private $access_token;
    private $instance_url;


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
            $this->response = $this->makeRequest($this->paramConnexion['host'], $this->apiKey, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/bankAccounts?select=name&count=1');

            if ($this->response['curlErrorNumber'] === 0) { // url all fine . precced as usual
                if (!empty($this->response['curlInfo']) && $this->response['curlInfo']['http_code'] === 200) { // token is valid
                    $this->connexion_valide = true;
                    $this->setAccessToken( $this->token);

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

    public function getAccessToken() {
        return 	$this->access_token;
    }

    public function setAccessToken($token) {
        $this->access_token = $token;
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
            curl_exec($ch);

            //Object for response curl
            $response = array(
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
