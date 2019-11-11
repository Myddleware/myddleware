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

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class sagecrmcore extends solution {
	
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
				$this->paramConnexion['wsdl'] = __DIR__.'/../Custom/Solutions/sagecrm/file/'.$this->paramConnexion['wsdl'];
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
			$error = $e->getMessage();
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)*/
	
	// Fonction qui renvoie les données de connexion
	public function getToken() {
		return array('sf_access_token' => $this->access_token,
					 'sf_instance_url' => $this->instance_url);
	} // getToken()

	// Liste des paramètres de connexion
	public function getFieldsLogin() {	
        return array(
                    array(
                            'name' => 'login',
                            'type' => TextType::class,
                            'label' => 'solution.fields.login'
                        ),
                    array(
                            'name' => 'password',
                            'type' => PasswordType::class,
                            'label' => 'solution.fields.password'
                        ),
                    array(
                            'name' => 'wsdl',
                            'type' => TextType::class,
                            'label' => 'solution.fields.wsdl'
                        )
        );
	} // getFieldsLogin()

	// Renvoie les modules disponibles du compte Salesforce connecté
	public function get_modules($type = 'source') {
		try{
			try{
				// Define SOAP connection options.
				$options = array(
								'trace' => 1, // All fault tracing this allows for recording messages sent and received
								'soap_version' => SOAP_1_1,
								'authentication' => SOAP_AUTHENTICATION_BASIC,
								'exceptions' => TRUE
							);
				// Création du client et Connexion
				$client = new \SoapClient($this->wsdl, $options);
				$login_details  = array('username' => $this->username, 'password' => $this->password);
				$response = $client->logon($login_details);
				
				if(isset($response->result->sessionid)) {
					$sessionid = $response->result->sessionid;
				} else {
					throw new \Exception("No SessionID. Logon failed.");
				}
				
				// Création du SoapHeader
				$header = "<SessionHeader xmlns='http://tempuri.org/type'>
								<sessionId>".$sessionid."</sessionId>
							</SessionHeader>";
				$session_var = new \SoapVar($header, XSD_ANYXML, null, 'http://www.w3.org/2001/XMLSchema-instance', null);
				$session_header = new \SoapHeader('http://tempuri.org/type', 'SessionHeader', $session_var);
				// Apply header to client
				$client->__setSoapHeaders(array($session_header));
				
				// Récupération des meta data
				$tables = $client->getallmetadata();
				
				// Déconnexion
				$response = $client->logoff(array("sessionId" => $sessionid));
				
				foreach ($tables->result->tables as $object) {
					$modules[$object->prefix . "_" . $object->tablename] = $object->tablename;
				}
				return ((isset($modules)) ? $modules : false );
			}
			catch(\SoapFault $fault) 
			{
				if(!empty($fault->getMessage())) {
					throw new \Exception($fault->getMessage());
				}
				throw new \Exception("SOAP FAULT. Logon failed.");
			}
		}
		catch (\Exception $e){
			$error = $e->getMessage();
			return $error;
		}
	} // get_modules()

	// Renvoie les champs du module passé en paramètre
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		
		// $module vaut "Prefix_Module", on fait donc un explode pour séparer les 2
		$tmp = explode("_", $module, 2);
		$module = $tmp[1];
		$prefix = $tmp[0];
		$dropdownvalues = array();
		
		try{
			try{
				// Define SOAP connection options.
				$options = array(
								'trace' => 1, // All fault tracing this allows for recording messages sent and received
								'soap_version' => SOAP_1_1,
								'authentication' => SOAP_AUTHENTICATION_BASIC,
								'exceptions' => TRUE
							);
				// Création du client et Connexion
				$client = new \SoapClient($this->wsdl, $options);
				$login_details  = array('username' => $this->username, 'password' => $this->password);
				$response = $client->logon($login_details);
				
				if(isset($response->result->sessionid)) {
					$sessionid = $response->result->sessionid;
				} else {
					throw new \Exception("No SessionID. Logon failed.");
				}
				
				// Création du SoapHeader
				$header = "<SessionHeader xmlns='http://tempuri.org/type'>
								<sessionId>".$sessionid."</sessionId>
							</SessionHeader>";
				$session_var = new \SoapVar($header, XSD_ANYXML, null, 'http://www.w3.org/2001/XMLSchema-instance', null);
				$session_header = new \SoapHeader('http://tempuri.org/type', 'SessionHeader', $session_var);
				// Apply header to client
				$client->__setSoapHeaders(array($session_header));
				
				// Récupération de la meta data pour le module choisi
				$metadata = $client->getmetadata(array("entityname" => $module));
				
				// Get dropdown list
				$lists = $client->getdropdownvalues(array("entityname" => $module));
				
				// Format list
				if (!empty($lists->result->records)) {
					foreach ($lists->result->records as $list) {
						// Some lists aren't array
						if (
								!empty($list->records)
							AND	is_array($list->records)
						) {
							$dropdownvalues[$list->fieldname] = $list->records;
						}
					}
				}
				
				// Déconnexion
				$response = $client->logoff(array("sessionId" => $sessionid));
				
				foreach ($metadata->result->records as $field) {
	                if (!in_array(substr($field->type, 3),$this->type_valide)) { 
	                    $type_bdd = 'varchar(255)';
	                }
	                else {
	                    $type_bdd = $field->type;
	                }
					// Si le champ finit par "id"
					// Et si le champ ne fait pas partie des champs du tableau $fieldsNotRelate
					if(
							substr($field->name, -2) == "id"
						&& !isset($this->fieldsNotRelate[$module][$field->name]) 
					){
							// Si le champ n'est pas $module + "id" concaténés
							if($field->name != strtolower($module) . "id") {
								// Et si le champ n'est pas dans les id du tableau $IdByModule
								if(!(isset($this->IdByModule[$module]) && $field->name == $this->IdByModule[$module])) {
									// Alors c'est un champ relation (OUF)
									$this->fieldsRelate[$field->name] = array(
										                        'label' => $field->name,
										                        'type' => substr($field->type, 3),
										                        'type_bdd' => $type_bdd,
										                        'required' => $field->required,
																'required_relationship' => $field->required
										                    );
									continue;
								}
							}
					}
					else {
						$fields[$field->name] = array(
													'label' => $field->name,
													'type' => substr($field->type, 3),
													'type_bdd' => $type_bdd,
													'required' => $field->required,
												);
						if (!empty($dropdownvalues[$field->name])) {
							$fields[$field->name]['option'] = $dropdownvalues[$field->name];
						}
					}
				}
				// Ajout des champ relate au mapping des champs 
				if (!empty($this->fieldsRelate)) {
					$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
				}
				return $fields;
			}
			catch(\SoapFault $fault) 
			{
				if(!empty($fault->getMessage())) {
					throw new \Exception($fault->getMessage());
				}
				throw new \Exception("SOAP FAULT. Logon failed.");
			}
		}
		catch (\Exception $e){
			return false;
		}
	} // get_module_fields($module)


	// Permet de récupérer le dernier enregistrement de la solution (utilisé pour tester le flux)
	public function read_last($param) {
		// $module vaut "Prefix_Module", on fait donc un explode pour séparer les 2
		$tmp = explode("_", $param['module'], 2);
		$module = $tmp[1];
		$prefix = $tmp[0];

		$result = array();
		try {
			// Define SOAP connection options.
			$options = array(
							'trace' => 1, // All fault tracing this allows for recording messages sent and received
							'soap_version' => SOAP_1_1,
							'authentication' => SOAP_AUTHENTICATION_BASIC,
							'exceptions' => TRUE
						);
			// Création du client et Connexion
			$client = new \SoapClient($this->wsdl, $options);
			$login_details  = array('username' => $this->username, 'password' => $this->password);
			$response = $client->logon($login_details);
			
			if(isset($response->result->sessionid)) {
				$sessionid = $response->result->sessionid;
			} else {
				throw new \Exception("No SessionID. Logon failed.");
			}
			
			// Création du SoapHeader
			$header = "<SessionHeader xmlns='http://tempuri.org/type'>
							<sessionId>".$sessionid."</sessionId>
						</SessionHeader>";
			$session_var = new \SoapVar($header, XSD_ANYXML, null, 'http://www.w3.org/2001/XMLSchema-instance', null);
			$session_header = new \SoapHeader('http://tempuri.org/type', 'SessionHeader', $session_var);
			// Apply header to client
			$client->__setSoapHeaders(array($session_header));

			// On traite les champs que l'on veut récupérer
			if(!isset($param['fields'])) {
				$param['fields'] = array();
			}
			$param['fields'] = array_unique($param['fields']);
			$param['fields'] = $this->addRequiredField($param['fields']);
			$param['fields'] = array_values($param['fields']);
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);

			// Si le tableau de requête est présent alors construction de la requête
			if(!empty($param['query']['id'])) {
				// Appel de la requête
				$request = $client->queryentity(array("entityname" => $module, "id" => $param['query']['id']));

				// Déconnexion
				$response = $client->logoff(array("sessionId" => $sessionid));

				// Traitement des résultats
				if(!empty($request->result->records)) {
					foreach ($request->result->records as $key => $value) {
						if($key == 'updateddate'){
				            $row["date_modified"] = $this->DateConverter($value);
						}
						if(in_array($key, $param['fields']))
							$row[$key] = $value;
				    }
				    // On ajoute l'id du résultat, ici on peut directement mettre $param['query']['id'] (on ne serait pas ici si l'élément n'existait pas)
					$row['id'] = $param['query']['id'];
					$result['values'] = $row;
					$result['done'] = true;
				} else {
					$result['done'] = false;
				}
			} else { // Sinon, on fait un readLast normal
				$queryrecord = array( "fieldlist" => "", "queryString" => "", "entityname" => $module, "orderby" => $prefix."_updateddate DESC");

				// Ajout du champ id, obligatoire mais spécifique au module
				if(isset($this->IdByModule[$module])) { // Si le champ id existe dans le tableau
					$fieldID = $this->IdByModule[$module];
				} else { // S'il n'existe pas alors on met "companyid" par exemple pour le module Company
					$fieldID = strtolower($module)."id";
				}
				$queryrecord["fieldlist"] = $prefix."_".$fieldID.",";

				foreach ($param['fields'] as $field){
					$queryrecord["fieldlist"] .= $prefix."_".$field.", ";
				}
				// Supprime l'espace et la dernière virgule
				$queryrecord["fieldlist"] = rtrim($queryrecord["fieldlist"],' ');
				$queryrecord["fieldlist"] = rtrim($queryrecord["fieldlist"],',');
				
				// Appel de la requête
				$request = $client->queryrecord($queryrecord);
				
				// Déconnexion
				$response = $client->logoff(array("sessionId" => $sessionid));
				
				// Traitement des résultats
				if(isset($request->result->records)) {
					$row = array();
					if(is_array($request->result->records)) { // SI ON A PLUSIEURS RESULTATS
						foreach ($request->result->records{0}->records as $field) {
							if($field->name == $fieldID) {
								$row['id'] = $field->value;
							}
							if($field->name == 'updateddate'){
					            $row["date_modified"] = $this->DateConverter($field->value);
							}
							if(in_array($field->name, $param['fields']))
								$row[$field->name] = $field->value;
					    }
					} else { // SI ON A QU'UN SEUL RESULTAT
						foreach ($request->result->records->records as $field) {
							if($field->name == $fieldID) {
								$row['id'] = $field->value;
							}
							if($field->name == 'updateddate'){
					            $row["date_modified"] = $this->DateConverter($field->value);
							}
							if(in_array($field->name, $param['fields']))
								$row[$field->name] = $field->value;
						}
					}
					$result['values'] = $row;
					$result['done'] = true;
				} else {
					$result['done'] = false;
				}
			}
			return $result;
		}
		catch (\Exception $e) {
			$result['done'] = -1;
		    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			return $result;
		}
	} // read_last($param)	
	
	// Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
	public function read($param) {
		$result = array();
		if (empty($param['limit'])) {
			$param['limit'] = 100;
		}
		// $module vaut "Prefix_Module", on fait donc un explode pour séparer les 2
		$tmp = explode("_", $param['module'], 2);
		$module = $tmp[1];
		$prefix = $tmp[0];

		$result = array();
		try {
			// On va chercher le nom du champ pour la date de référence: Création ou Modification
			$DateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);

			// Define SOAP connection options.
			$options = array(
							'trace' => 1, // All fault tracing this allows for recording messages sent and received
							'soap_version' => SOAP_1_1,
							'authentication' => SOAP_AUTHENTICATION_BASIC,
							'exceptions' => TRUE
						);
			// Création du client et Connexion
			$client = new \SoapClient($this->wsdl, $options);
			$login_details  = array('username' => $this->username, 'password' => $this->password);
			$response = $client->logon($login_details);
			
			if(isset($response->result->sessionid)) {
				$sessionid = $response->result->sessionid;
			} else {
				throw new \Exception("No SessionID. Logon failed.");
			}
			
			// Création du SoapHeader
			$header = "<SessionHeader xmlns='http://tempuri.org/type'>
							<sessionId>".$sessionid."</sessionId>
						</SessionHeader>";
			$session_var = new \SoapVar($header, XSD_ANYXML, null, 'http://www.w3.org/2001/XMLSchema-instance', null);
			$session_header = new \SoapHeader('http://tempuri.org/type', 'SessionHeader', $session_var);
			// Apply header to client
			$client->__setSoapHeaders(array($session_header));

			// On traite les champs que l'on veut récupérer
			if(!isset($param['fields'])) {
				$param['fields'] = array();
			}
			$param['fields'] = array_unique($param['fields']);
			$param['fields'] = $this->addRequiredField($param['fields']);
			$param['fields'] = array_values($param['fields']);
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);

			$queryrecord = array( "fieldlist" => "", "queryString" => $prefix."_".$DateRefField." > '".$this->DateConverter($param['date_ref'], 1)."'", "entityname" => $module, "orderby" => $prefix."_".$DateRefField." ASC");

			// Ajout du champ id, obligatoire mais spécifique au module
			if(isset($this->IdByModule[$module])) { // Si le champ id existe dans le tableau
				$fieldID = $this->IdByModule[$module];
			} else { // S'il n'existe pas alors on met "companyid" par exemple pour le module Company
				$fieldID = strtolower($module)."id";
			}
			$queryrecord["fieldlist"] = $prefix."_".$fieldID.",";

			foreach ($param['fields'] as $field){
				$queryrecord["fieldlist"] .= $prefix."_".$field.",";
			}
			// Supprime l'espace et la dernière virgule
			$queryrecord["fieldlist"] = rtrim($queryrecord["fieldlist"],' ');
			$queryrecord["fieldlist"] = rtrim($queryrecord["fieldlist"],',');
			// Appel de la requête
			$request = $client->queryrecord($queryrecord);

			// Déconnexion
			$response = $client->logoff(array("sessionId" => $sessionid));

			// Traitement des résultats
			if(!empty($request->result->records)) {
				if(is_array($request->result->records)) { // SI ON A PLUSIEURS RESULTATS
					$cpt = 0;
					foreach ($request->result->records as $record) {
						$row = array();
						$recordFields = array();
						foreach ($record->records as $field) {
							$recordFields[$field->name] = $field->value;
						}
						$row["id"] = $recordFields[$fieldID]; // Ajout de l'ID, $fieldID vaut "companyid" pour le module "company" par exemple
						foreach ($recordFields as $key => $value) {
							if ($key == $DateRefField) {
								$row['date_modified'] = $this->DateConverter($value);
								$result['date_ref'] = $this->DateConverter($value);
							}
							if(in_array($key, $param['fields']))
								$row[$key] = $value;
						}
						$result['values'][$recordFields[$fieldID]] = $row;
						$cpt++;
						$result['count'] = $cpt;
						if($cpt >= $param['limit']) break;
				    }
				} else { // SI ON A QU'UN SEUL RESULTAT
					$result['count'] = 1;

					$row = array();
					$recordFields = array();
					foreach ($request->result->records->records as $field) {
						$recordFields[$field->name] = $field->value;
					}
					$row["id"] = $recordFields[$fieldID]; // Ajout de l'ID, $fieldID vaut "companyid" pour le module "company" par exemple
					foreach ($recordFields as $key => $value) {
						if ($key == $DateRefField) {
							$row['date_modified'] = $this->DateConverter($value);
							$result['date_ref'] = $this->DateConverter($value);
						}
						if(in_array($key, $param['fields']))
							$row[$key] = $value;
					}
					$result['values'][$recordFields[$fieldID]] = $row;
				}
			}
			return $result;
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			return $result;
		}
	} // read($param)
	
	// Permet de créer des données
	public function create($param) {
		// $module vaut "Prefix_Module", on fait donc un explode pour séparer les 2
		$tmp = explode("_", $param['module'], 2);
		$module = $tmp[1];
		$prefix = $tmp[0];

		// Define SOAP connection options.
		$options = array(
						'trace' => 1, // All fault tracing this allows for recording messages sent and received
						'soap_version' => SOAP_1_1,
						'authentication' => SOAP_AUTHENTICATION_BASIC,
						'exceptions' => TRUE
					);
		// Création du client et Connexion
		$client = new \SoapClient($this->wsdl, $options);
		$login_details  = array('username' => $this->username, 'password' => $this->password);
		$response = $client->logon($login_details);
		
		if(isset($response->result->sessionid)) {
			$sessionid = $response->result->sessionid;
		} else {
			throw new \Exception("No SessionID. Logon failed.");
		}
		
		// Création du SoapHeader
		$header = "<SessionHeader xmlns='http://tempuri.org/type'>
						<sessionId>".$sessionid."</sessionId>
					</SessionHeader>";
		$session_var = new \SoapVar($header, XSD_ANYXML, null, 'http://www.w3.org/2001/XMLSchema-instance', null);
		$session_header = new \SoapHeader('http://tempuri.org/type', 'SessionHeader', $session_var);
		// Apply header to client
		$client->__setSoapHeaders(array($session_header));

		if(!(isset($param['data']))) throw new \Exception ('Data missing for create');
		foreach($param['data'] as $idDoc =>$data) {
			try{
				// Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
			    $record = array();
			    $object = array();
			    foreach ($data as $key => $value) {
			        if ($key == 'target_id') {
			            continue;
			        }
					if(isset($value)){
						$record[$key] = $value;
					}
			    }
				$record['assigneduserid'] = '1';
			    if(empty($record)) throw new \Exception ('Values missing for create');

				//create soap variable to send
				foreach ($record as $key => $value) {
					if(is_integer($value))
						$object[]= new \SoapVar($value,XSD_INT,null,null, $key);
					else
						$object[] = new \SoapVar($value, XSD_STRING,null,null, $key);
				}
				
				// Prepare the data for sending 
				$data = new \SoapVar($object, SOAP_ENC_OBJECT, NULL, NULL, NULL);
				$objectToSend = array('entityname' => $module, 'records' => $data);

				// Appel de la requête
				$addResult = $client->add($objectToSend);

				if (!empty($addResult->result->records->crmid)) {
					$result[$idDoc] = array(
											'id' => $addResult->result->records->crmid,
											'error' => false
									);
				}
				else  {
					$result[$idDoc] = array(
											'id' => '-1',
											'error' => 'Failed to create Data in Salesforce '.curl_error($ch)
									);
				}
			}
			catch (\Exception $e) {
				$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}			
		return $result;
	} // create($param)
	
	// Permet de modifier des données
	public function update($param) {
		// $module vaut "Prefix_Module", on fait donc un explode pour séparer les 2
		$tmp = explode("_", $param['module'], 2);
		$module = $tmp[1];
		$prefix = $tmp[0];

		// Define SOAP connection options.
		$options = array(
						'trace' => 1, // All fault tracing this allows for recording messages sent and received
						'soap_version' => SOAP_1_1,
						'authentication' => SOAP_AUTHENTICATION_BASIC,
						'exceptions' => TRUE
					);
		// Création du client et Connexion
		$client = new \SoapClient($this->wsdl, $options);
		$login_details  = array('username' => $this->username, 'password' => $this->password);
		$response = $client->logon($login_details);
		
		if(isset($response->result->sessionid)) {
			$sessionid = $response->result->sessionid;
		} else {
			throw new \Exception("No SessionID. Logon failed.");
		}
		
		// Création du SoapHeader
		$header = "<SessionHeader xmlns='http://tempuri.org/type'>
						<sessionId>".$sessionid."</sessionId>
					</SessionHeader>";
		$session_var = new \SoapVar($header, XSD_ANYXML, null, 'http://www.w3.org/2001/XMLSchema-instance', null);
		$session_header = new \SoapHeader('http://tempuri.org/type', 'SessionHeader', $session_var);
		// Apply header to client
		$client->__setSoapHeaders(array($session_header));

		if(!(isset($param['data']))) throw new \Exception ('Data missing for create');
		foreach($param['data'] as $idDoc => $data) {
			try{
				// Check control before update
				$data = $this->checkDataBeforeUpdate($param, $data);
			    $record = array();
			    $object = array();
			    foreach ($data as $key => $value) {
			        if ($key == 'target_id') {
			        	$target_id = $value;
			            continue;
			        }
					if(isset($value)){
						$record[$key] = $value;
					}
			    }
			    if(empty($target_id)) throw new \Exception ('Target ID missing for update');
			    if(empty($record)) throw new \Exception ('Values missing for update');

				// Ajout du champ id spécifique au module pour modifier l'élément cible
				if(isset($this->IdByModule[$module])) { // Si le champ id existe dans le tableau
					$record[$this->IdByModule[$module]] = $target_id;
				} else { // S'il n'existe pas alors on met "companyid" par exemple pour le module Company
					$record[strtolower($module)."id"] = $target_id;
				}
				//create soap variable to send
				foreach ($record as $key => $value) {
					if(is_integer($value))
						$object[]= new \SoapVar($value,XSD_INT,null,null, $key);
					else
						$object[] = new \SoapVar($value, XSD_STRING,null,null, $key);
				}
				
				// Prepare the data for sending 
				$data = new \SoapVar($object, SOAP_ENC_OBJECT, NULL, NULL, NULL);
				$objectToSend = array('entityname' => $module, 'records' => $data);

				// Appel de la requête
				$updateResult = $client->update($objectToSend);

				if ($updateResult->result->updatesuccess) {
					$result[$idDoc] = array(
											'id' => $target_id,
											'error' => false
									);
				}
				else  {
					$result[$idDoc] = array(
											'id' => '-1',
											'error' => 'Failed to create Data in Salesforce '
									);
				}
			}
			catch (\Exception $e) {
				$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}			
		return $result;
	} // update($param)	
	
	
	// Renvoie le nom du champ de la date de référence en fonction du module et du mode de la règle
	public function getDateRefName($moduleSource, $RuleMode) {
		if(in_array($RuleMode,array("0","S"))) {
			return "updateddate";
		} else if ($RuleMode == "C"){
			return "createddate";
		} else {
			throw new \Exception ("$RuleMode is not a correct Rule mode.");
		}
		return null;
	}
    
	// Function de conversion de date format solution à une date format Myddleware
	protected function DateConverter($dateTime, $sens = 0) {
		if($sens) { // Vers SageCRM
			$formatReturn = "Y-m-d\TH:i:s";
			$format = "Y-m-d H:i:s";
		} else { // Vers Myddleware
			$format = "Y-m-d\TH:i:s";
			$formatReturn = "Y-m-d H:i:s";
		}
		if (empty($dateTime)) {			
			throw new \Exception("Date empty. Failed to convert it.");
		}
		if(date_create_from_format($format, $dateTime)) {
			$date = date_create_from_format($format, $dateTime);
		} elseif(date_create_from_format('Y-m-d', $dateTime)) {
			$date = date_create_from_format('Y-m-d', $dateTime);
			$date->setTime( 0 , 0 , 0 );
		} else {
			throw new \Exception("Wrong format for your date. Please check your date format. Contact us for help.");
		}
		return $date->format($formatReturn);
	}// dateToMyddleware ($date)  
    
}// class sagecrmcore

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/sagecrm.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class sagecrm extends sagecrmcore {
		
	}
}