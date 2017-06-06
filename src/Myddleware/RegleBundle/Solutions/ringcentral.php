<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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
use Symfony\Component\HttpFoundation\Session\Session;

class ringcentralcore  extends solution { 
	
	// const VERSION = '2.0.0';
    const SERVER_PRODUCTION = 'https://platform.ringcentral.com';
    const SERVER_SANDBOX = 'https://platform.devtest.ringcentral.com';
	
	const ACCESS_TOKEN_TTL = 3600; // 60 minutes
    const REFRESH_TOKEN_TTL = 604800; // 1 week
    const TOKEN_ENDPOINT = '/restapi/oauth/token';
    const REVOKE_ENDPOINT = '/restapi/oauth/revoke';
    const AUTHORIZE_ENDPOINT = '/restapi/oauth/authorize';
    const API_VERSION = '/v1.0';
	
	protected $apiKey;
	protected $token;
	protected $server;
	
	protected $required_fields = array(
										'default' => array('id'),
										'call-log' => array('id','startTime'),
										'message-store' => array('id','lastModifiedTime'),
										'presence' => array('id','date_modified'),
									);
	
	public function getFieldsLogin() {	
		return array(
					array(
                            'name' => 'username',
                            'type' => 'text',
                            'label' => 'solution.fields.username'
                        ),
					array(
                            'name' => 'password',
                            'type' => 'password',
                            'label' => 'solution.fields.password'
                        ),
					array(
                            'name' => 'apikey',
                            'type' => 'password',
                            'label' => 'solution.fields.apikey'
                        ),
					array(
                            'name' => 'apikeysecret',
                            'type' => 'password',
                            'label' => 'solution.fields.apikeysecret'
                        ),
					array(
                            'name' => 'sandbox',
                            'type' => 'text',
                            'label' => 'solution.fields.sandbox'
                        )	
		);
	}
	
 	public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
			if (empty($this->paramConnexion['sandbox'])) {
				$this->server = self::SERVER_PRODUCTION;
			} else {
				$this->server = self::SERVER_SANDBOX;
			}
			
			// Call to get the token
			$this->apiKey = base64_encode( $this->paramConnexion['apikey'] . ':' . $this->paramConnexion['apikeysecret'] );
			$this->token  = $this->makeRequest( $this->server, $this->apiKey, self::TOKEN_ENDPOINT, null, "POST", "username=" . $this->paramConnexion['username'] . "&password=" . $this->paramConnexion['password'] . "&grant_type=password" );
			if(!empty($this->token)) {		
				if (!empty($this->token->access_token)) {
					$this->connexion_valide = true; 
				}
				elseif(!empty($this->token->error)) {
					throw new \Exception($this->token->error.(!empty($this->token->error_description) ? ': '.$this->token->error_description : ''));
				} else {
					throw new \Exception('Result from Ring Central : '.print_r($this->token,true));
				}
			} else {
				throw new \Exception('No response from Ring Central. ');
			}	
		}
		catch (\Exception $e) {
			$error = 'Failed to login to Ring Central : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine();
			echo $error . ';';
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)
	
	// Get the modules available
	public function get_modules($type = 'source') {
		try{		
			$modules = array(	
								'call-log'		=> 'Call log',
								'message-store'	=> 'Messages',
								'presence'		=> 'Presence',
							);
			return $modules;			
		} catch (\Exception $e) {
			$error = $e->getMessage();
			return $error;			
		}
	} 
	
	// Get the fields available for the module in input
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			require_once('lib/ringcentral/metadata.php');	
			if (!empty($moduleFields[$module])) {
				$this->moduleFields = $moduleFields[$module];
			}

			if (!empty($fieldsRelate[$module])) {
				$this->fieldsRelate = $fieldsRelate[$module]; 
			}	
		
			// Add relate field in the field mapping 
			if (!empty($this->fieldsRelate)) {
				$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
			}
			return $this->moduleFields;
		}
		catch (\Exception $e){
			return false;
		}
	} // get_module_fields($module)	 
		
		
	public function read_last($param) {
		try {
			// Add required fields			
			$param['fields'] = $this->addRequiredField($param['fields'],$param['module']);
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
	
			// Generate the WHERE // no nedd because Ringcentral is used in source application only
			if (!empty($param['query'])) {
				
			// The function is called for a simulation (rule creation) if there is no query
			} else {
				$date = new \DateTime();
				$date = date_modify($date, '-1 month');	
				$where = "dateFrom=".$date->format('Y-m-d\TH:i:s.Z\Z');
			}
			// Call the function to Ringcentral
			$records = $this->makeRequest( $this->server, $this->token->access_token, "/restapi".self::API_VERSION."/account/~/extension/~/".$param['module']."?perPage=1&".$where);
		
			// Error managment
			if(!empty($records->errorCode)) {
				throw new \Exception($records->errorCode.(!empty($records->message) ? ': '.$records->message : ''));
			}
			
			// Transform result by adding a dimension for the presence module (only one record for each call)
			if ($param['module'] == 'presence') {
				$recordsObj = new \stdClass();
				$recordsObj->records = array($records);
				$records = $recordsObj;
			}
			
			if (!empty($records->records)) {
				// For each records
				foreach($records->records as $record) {			
					// For each fields expected
					foreach($param['fields'] as $field) {
						// The field could be a structure from_phoneNumber for example
						$fieldStructure = explode('__',$field);
						// If 2 dimensions						
						if (!empty($fieldStructure[1])) {
							// If the field is empty, Ringcentral return nothing but we need to set the field empty in Myddleware
							$record->$field = (isset($record->$fieldStructure[0]->$fieldStructure[1]) ? $record->$fieldStructure[0]->$fieldStructure[1] : '');
						}
						$result['values'][$field] = $record->$field;
						$result['done'] = true;
					}
				}
			} else {
				$result['done'] = false;
			} 
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;
		}	
		return $result;
	}	
	
	public function read($param) {
		try {
			$result['date_ref'] = $param['date_ref'];
			$result['count'] = 0;
			// Add required fields			
			$param['fields'] = $this->addRequiredField($param['fields'],$param['module']);
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
		
			// Get the reference date field name
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
			$dateRef = $this->dateTimeFromMyddleware($param['date_ref']);	
			
			// Call RingCEntral
			$records = $this->makeRequest( $this->server, $this->token->access_token, "/restapi".self::API_VERSION."/account/~/extension/~/".$param['module']."?dateFrom=".$dateRef);

			// Error managment
			if(!empty($records->errorCode)) {
				throw new \Exception($records->errorCode.(!empty($records->message) ? ': '.$records->message : ''));
			}
			
			// Transform result by adding a dimension for the presence module (only one record for each call)
			if ($param['module'] == 'presence') {
				$recordsObj = new \stdClass();
				// No/date ref id in the presence module
				$records->id = uniqid('', true).'_'.$records->extension->extensionNumber;
				$records->$dateRefField = date('Y-m-d H:i:s');
				$recordsObj->records = array($records);
				$records = $recordsObj;
			}
			
			if (!empty($records->records)) {
				// For each records
				foreach($records->records as $record) {
					// For each fields expected
					foreach($param['fields'] as $field) {
						// The field could be a structure from_phoneNumber for example
						$fieldStructure = explode('__',$field);
						// If 2 dimensions						
						if (!empty($fieldStructure[1])) {
							// If the field is empty, Ringcentral return nothing but we need to set the field empty in Myddleware
							$record->$field = (isset($record->$fieldStructure[0]->$fieldStructure[1]) ? $record->$fieldStructure[0]->$fieldStructure[1] : '');
						}
						// We check the lower case because the result of the webservice return sfield without capital letter (first_name instead of First_Name)
						if(isset($record->$field)) {
							// The field id in Cirrus shield as a capital letter for the I, not in Myddleware
							if ($field == $dateRefField) {
								$dateMyddlewareFormat = $this->dateTimeToMyddleware($record->$field);
								$row['date_modified'] = $dateMyddlewareFormat;
							}
							$row[$field] = $record->$field;
						} else {
							$row[$field] = '';
						}
					}
					// date à gérer
					if (
							!empty($row['date_modified'])
						&&	$result['date_ref'] <= $row['date_modified']
					) {
						$date = new \DateTime($row['date_modified']);					
						$date = date_modify($date, '+1 seconde');						
						$result['date_ref'] = $date->format('Y-m-d H:i:s');
					}
					$result['values'][$record->id] = $row;
					$result['count']++;
					$row = array();
				}
			}		
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
		}	
		return $result;
	}	
	
	// retrun the reference date field name
	public function getDateRefName($moduleSource, $RuleMode) {
		if ($moduleSource == 'call-log') {
			return 'startTime';
		} elseif ($moduleSource == 'message-store') {
			return 'lastModifiedTime';
		}elseif ($moduleSource == 'presence') {
			return 'date_modified';
		}
	}	
	
	
	// Add the filed extensionId on the rule
	public function getFieldsParamUpd($type, $module, $myddlewareSession) {	
		try {
			$params[] = array(
								'id' => 'extensionId',
								'name' => 'extensionId',
								'type' => 'text',
								'label' => 'Extension Id',
								'required'	=> false
							);	
			return $params;
		}
		catch (\Exception $e){
			return array();
			//return $e->getMessage();
		}
	}
	
	// Function de conversion de datetime format Myddleware à un datetime format solution
	protected function dateTimeFromMyddleware($dateTime) {
		try {
			if (empty($dateTime)) {			
				throw new \Exception("Date empty. Failed to send data. ");
			}
			if(date_create_from_format('Y-m-d H:i:s', $dateTime)) {
				$date = date_create_from_format('Y-m-d H:i:s', $dateTime);
			} else {
				$date = date_create_from_format('Y-m-d', $dateTime);
				if($date) {
					$date->setTime( 0 , 0 , 0 );
				} else {
					throw new \Exception("Wrong format for your date. Please check your date format. Contact us for help.");
				}
			}
			return $date->format('Y-m-d\TH:i:s.Z\Z'); 
		} catch (\Exception $e) {
			$result['error'] = $e->getMessage();
			return $result;
		}
	}// dateTimeFromMyddleware($dateTime)   
	
	// Function de conversion de datetime format solution à un datetime format Myddleware
	protected function dateTimeToMyddleware($dateTime) {
		$date = new \DateTime($dateTime);
		return $date->format('Y-m-d H:i:s');
	}// dateTimeToMyddleware($dateTime)	
	
    // HTTP Request function
    function makeRequest( $server, $token, $path, $args = null, $method = 'GET', $data = null ) {
      if (function_exists('curl_init') && function_exists('curl_setopt')) {
			// The URL to use
			$ch = curl_init( $server . $path );
			// Make sure params is empty or an array
			if( !empty($args) ) {
				$value = json_encode($args);			
				curl_setopt($ch, CURLOPT_POSTFIELDS, $value);
			}
			// Set authorization header properly
			$authPath = '/oauth\/token/';
			if( 1 !== preg_match( $authPath, $path ) ) {
				$authHeader = 'Authorization: Bearer ' . $token ;
				$contentType = 'Content-Type: application/json';
				if( "POST" == $method && "array" !== gettype( $data ) ) {
					curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
					$data_string = json_encode( $data );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
				}
			} else {
				$authHeader = 'Authorization: Basic ' . $token ;
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
			}
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);

			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				$authHeader )
			);
			// Execute request
			$result = curl_exec( $ch );
			// Close Connection
			curl_close( $ch );
			return $result ? json_decode($result) : false;
		}
		throw new \Exception('curl extension is missing!');
    }
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/ringcentral.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class ringcentral extends ringcentralcore {
		
	}
}