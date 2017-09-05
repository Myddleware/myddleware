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
        "Orders" => "orderquoteid",
        "Address_Link" => "addresslinkid",
        "Comm_Link" => "commlinkid",
        "Notes" => "noteid",
        "NewProduct" => "productid",
        "OrderItems" => "intid",
        "Person_Link" => "personlinkid",
        "PhoneLink" => "linkid",
        "EmailLink" => "linkid",
        "Products" => "productid",
        "QuoteItems" => "lineitemid",
        "UOMFamily" => "familyid",
        "Users" => "userid"
    );

    private $access_token;
    private $instance_url;

    // Listes des modules et des champs à exclure
    protected $exclude_module_list = array();

    protected $exclude_field_list = array();

    // Connexion à SageCRM
    public function login($paramConnexion) {
        parent::login($paramConnexion);
        try{
            try{

                // Define SOAP connection options.
                $options = array(
                    'trace' => 1, // All fault tracing this allows for recording messages sent and received
                    'soap_version' => SOAP_1_1,
                    'authentication' => SOAP_AUTHENTICATION_BASIC,
                    'exceptions' => TRUE
                );
                $this->paramConnexion['wsdl'] = __DIR__.'/../Custom/Solutions/sagecrm/wsdl/'.$this->paramConnexion['wsdl'];
                $client = new \SoapClient($this->paramConnexion['wsdl'], $options);
                $login_details  = array('username' => $this->paramConnexion['login'], 'password' => $this->paramConnexion['password']);
                $response = $client->logon($login_details);

                if(isset($response->result->sessionid)) {
                    $sessionid = $response->result->sessionid;
                } else {
                    throw new \Exception("No SessionID. Logon failed.");
                }

                $response = $client->logoff(array("sessionId" => $sessionid));

                // Instanciation des variables de classes
                $this->wsdl = $this->paramConnexion['wsdl'];
                $this->username = $this->paramConnexion['login'];
                $this->password = $this->paramConnexion['password'];
                $this->connexion_valide = true;
            }
            catch(\SoapFault $fault)
            {
                if(!empty($fault->getMessage())) {
                    throw new \Exception($fault->getMessage());
                }
                throw new \Exception("SOAP FAULT. Logon failed.");
            }
        }
        catch (\Exception $e) {
            $error = 'Failed to login to SageCRM : '.$e->getMessage();
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
            )
        );
    } // getFieldsLogin()


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