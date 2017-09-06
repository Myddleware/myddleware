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

class sagedatacore extends solution {

    private $wsdl = "";
    private $username = "";
    private $password = "";

    protected $fieldsRelate = array();

    protected $fieldsNotRelate = array('Opportunity' => array('assigneduserid' => true), 'PhoneLink' => array('entityid' => true), 'EmailLink' => array('entityid' => true));

    protected $required_fields =  array('default' => array('updateddate', 'createddate'));

    protected $FieldsDuplicate = array();

    protected $required_relationships = array(
        'default' => array()
    );

    // Tableau de correspondance Module / ID pour les modules qui n'ont pas d'id de type "nommodule"."id"
    protected $IdByModule = array(
    );

    private $access_token;
    private $instance_url;

    // Listes des modules et des champs à exclure
    protected $exclude_module_list = array();

    protected $exclude_field_list = array();

    // Connexion à SageCRM
    // Connexion to Shop-application
    public function login($paramConnexion) {
        // Call parent to set $paramConnexion in an attribut of the class
        parent::login($paramConnexion);
        try{
            // Delete the "/" at the end of the url if the user have added one
            $this->url = rtrim($this->paramConnexion['url'],'/').'/api/';;
            // Try to access to the shop
            $result = $this->call(trim($this->url.$this->apiKey), 'get', '');
            // get the code, if 200 then success otherwise error
            $code = $result->__get('code');
            if ($code <> '200') {
                // Get the error message
                $body = $result->__get('body');
                throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
            }
            $this->connexion_valide = true;
        }
        catch (\Exception $e) {
            $error = 'Failed to login to sagedata : '.$e->getMessage();
            echo $error . ';';
            $this->logger->error($error);
            return array('error' => $error);
        }
    } // login($paramConnexion)*/



    // Liste des paramètres de connexion
    public function getFieldsLogin() {
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

    protected function call($url, $method = 'get', $data=array()){
        if (function_exists('curl_init') && function_exists('curl_setopt')) {
            $response = \Unirest::$method(
                $url, // URL de destination
                array('Accept'=>'application/json'), // Type des données envoyées
                json_encode($data) // On encode nos données en JSON
            );
            return $response;
        }
        throw new \Exception('curl extension is missing!');
    } // call()
}// class sagecrmcore

/* * * * * * * *  * * * * * *  * * * * * *
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/sagedata.php';
if(file_exists($file)){
    require_once($file);
}
else {
    //Sinon on met la classe suivante
    class sagedata extends sagedatacore {

    }
}