<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
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

require_once('lib/lib_zuora.php');

class zuoracore  extends solution { 
	
	protected $client;
	protected $sessionId;
	protected $debug = 0;
	protected $header;
	protected $defaultApiNamespaceURL = 'http://api.zuora.com/';	
	protected $maxZObjectCount = 50;
	protected $defaultApiNamespace = "ns1";
	protected $defaultObjectNamespace = "ns2";
	protected $defaultObjectNamespaceURL = "http://object.api.zuora.com/";
	protected $update = false;
	
	// Connection parameters
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
                    // array(
                            // 'name' => 'wsdl',
                            // 'type' => 'text',
                            // 'label' => 'solution.fields.wsdl'
                        // )
        );
	} // getFieldsLogin()

	// Login to Zuora
    public function login($paramConnexion) {
		parent::login($paramConnexion);
		try{
			// Get the wsdl (temporary solution)
			$this->paramConnexion['wsdl'] = __DIR__.'/../Custom/Solutions/zuora/wsdl/zuora.a.85.0.wsdl';		
			// Create the soap client
			$this->client = createClient($this->paramConnexion['wsdl'], $this->debug);
			// Connection to Zuora
			$this->sessionId = login($this->client, $this->paramConnexion['login'], $this->paramConnexion['password'], $this->debug);
			
			// error managment
			if(!empty($this->sessionId)) {
				// Header creation
				$this->header = $this->getHeader($this->sessionId);
			} else {
				throw new \Exception("No SessionID. Logon failed.");
			} 
			$this->connexion_valide = true; 
		}
		catch (\Exception $e) {
			$error = 'Failed to login to Zuora : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			echo $error . ';';
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)*/
		
		
	// Get the modules available
	public function get_modules($type = 'source') {
		try{	
			// Get all modules from te wsdl
			$zuoraModules = getObjectListFromWSDL($this->paramConnexion['wsdl'], $this->debug);		
			if (!empty($zuoraModules)) {
				// Generate the output array
				foreach($zuoraModules as $zuoraModule) {
					$modules[$zuoraModule] = $zuoraModule;
				}
			}
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
			$zupraFields = \ZuoraAPIHelper::getFieldList($this->paramConnexion['wsdl'], $module);	
			if (!empty($zupraFields)) {
				// Add each field in the right list (relate fields or normal fields)
				foreach($zupraFields as $field) {
					// If the fields is a relationship
					if (strtolower(substr($field,-2)) == 'id') {
						$this->fieldsRelate[$field] = array(
													'label' => $field,
													'type' => 'varchar(36)',
													'type_bdd' => 'varchar(36)',
													'required' => 0,
													'required_relationship' => 0,
												);
					} else {							
						$this->moduleFields[$field] = array(
													'label' => $field,
													'type' => 'varchar(255)',
													'type_bdd' => 'varchar(255)',
													'required' => 0
												);
					}
				}
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
/* 	
	// Get the last data in the application
	public function read_last($param) {	
		try {
			$param['fields'] = $this->addRequiredField($param['fields']);
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			
			$query = 'SELECT ';
			// Build the SELECT 
			if (!empty($param['fields'])) {
				foreach ($param['fields'] as $field) {
					$query .= $field.',';
				}
				// Delete the last coma 
				$query = rtrim($query, ',');
			} else {
				$query .= ' * ';
			}
			
			// Add the FROM
			$query .= ' FROM '.$param['module'].' ';
			
			// Generate the WHERE
			if (!empty($param['query'])) {
				$query .= ' WHERE ';
				$first = true;
				foreach ($param['query'] as $key => $value) {
					// Add the AND only if we are not on the first condition
					if ($first) {
						$first = false;
					} else {
						$query .= ' AND ';
					}
					// The field id in Cirrus shield as a capital letter for the I, not in Myddleware
					if ($key == 'id') {
						$key = 'Id';
					}
					// Add the condition
					$query .= $key." = '".$value."' ";
				}
			// The function is called for a simulation (rule creation) if there is no query
			} else {
				// $query .= " WHERE UpdatedDate < '".date('Y-m-d\TH:i:s')."' LIMIT 1" ; // Need to add 'limit 1' here when the command LIMIT will be available
				$query .= " WHERE status = 'Draft'" ; // Need to add 'limit 1' here when the command LIMIT will be available
			}
	
			// Buid the input parameter
			// $selectparam = ["authToken" 	=> $this->token,
							// "selectQuery" 	=> $query,
							// ];
			// $url = sprintf("%s?%s", $this->url."Query", http_build_query($selectparam));
			// $resultQuery = $this->call($url);
			\ZuoraAPIHelper::$client = $this->client;
			\ZuoraAPIHelper::$header = $this->header;
			// $query = "select Id, ProductRatePlanId from ProductRatePlanCharge";
echo $query.'<BR><PRE>';
$query = htmlentities($query);
echo $query.'<BR><PRE>';
			$resultat = \ZuoraAPIHelper::queryAPIWithSession($query, $this->debug);
print_r($resultat);		
// \ZuoraAPIHelper::getQueryResponseFieldValues
die();			
		
			// If the query return an error 
			if (!empty($resultQuery['Message'])) {
				throw new \Exception($resultQuery['Message']);	
			}	
			// If no result
			if (empty($resultQuery)) {
				$result['done'] = false;
			}
			// Format the result
			// If several results, we take the first one
			if (!empty($resultQuery[$param['module']][0])) {
				$record = $resultQuery[$param['module']][0];	
			// If one result we take the first one
			} else {
				$record = $resultQuery[$param['module']];
			}
			
			foreach($param['fields'] as $field) {
				// We check the lower case because the result of the webservice return sfield without capital letter (first_name instead of First_Name)
				if(isset($record[$field])) {
					// Cirrus return an array when the data is empty
					if (is_array($record[$field])) {
						$result['values'][$field] = '';
					} else {
						// The field id in Cirrus shield as a capital letter for the I, not in Myddleware
						if ($field == 'Id') {
							$result['values']['id'] = $record[$field];
						} else {
							$result['values'][$field] = $record[$field];
						}
					}
				}
			}
			if (!empty($result['values'])) {
				$result['done'] = true;
			}
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;
		}			
		return $result;
	}
 */
	/* // Get the last data in the application
	public function read_last($param) {	
		try {
		 	$param['fields'] = $this->addRequiredField($param['fields']);
print_r($this->header);			
			var_dump("IN");
			// $query = "select Id, ProductRatePlanId from ProductRatePlanCharge where AccountingCode = '$accountingCode'";
$query = 
"<ns1:query>
 <ns1:queryString>selectId, AccountNumber, EndDateTime, Quantity, RbeStatus, SourceName, SourceType, StartDateTime, SubmissionDateTime, UOM from Usage where AccountId = '4028e485225d1d5f0122662fd6b249c8'</ns1:queryString>
</ns1:query>";
			
			\ZuoraAPIHelper::$client = $this->client;
			\ZuoraAPIHelper::$header = $this->header;
			$query = "select Id, ProductRatePlanId from ProductRatePlanCharge";
			$resultat = \ZuoraAPIHelper::queryAPIWithSession($query, $this->debug);
	               // $resultat = \ZuoraAPIHelper::callAPIWithClient($this->client, $this->header, $query, $this->debug);
				  // $resultat =  \ZuoraAPIHelper::queryAPI($this->paramConnexion['wsdl'], $this->paramConnexion['login'], $this->paramConnexion['password'], $query, $this->debug);
		     $result['value'] = $resultat;
		     $result['done'] = true;
echo '<pre>';			 
print_r($result);
		 }
		catch (\Exception $e) {
			$result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;
		// var_dump($result);
		}
		// var_dump($result);
		return $result;
	} */
/*
	public function read($param) {
		try {
			$result['date_ref'] = $param['date_ref'];
			$result['count'] = 0;
			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields']);
			
			// Get the reference date field name
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
			
			// Get the organization timezone
			if (empty($this->organizationTimezoneOffset)) {
				$this->getOrganizationTimezone();
				// If the organization timezone is still empty, we generate an error
				if (empty($this->organizationTimezoneOffset)) {
					throw new \Exception('Failed to get the organization Timezone. This timezone is requierd to save the reference date.');
				}
			}
			
			$query = 'SELECT ';
			// Build the SELECT 
			if (!empty($param['fields'])) {
				foreach ($param['fields'] as $field) {
					$query .= $field.',';
				}
				// Delete the last coma 
				$query = rtrim($query, ',');
			} else {
				$query .= ' * ';
			}
			
			// Add the FROM
			$query .= ' FROM '.$param['module'].' ';
			
			// Generate the WHERE
			// if a specific query is requeted we don't use date_ref (used for child document)
			if (!empty($param['query'])) {
				$query .= ' WHERE ';
				$first = true;
				foreach ($param['query'] as $key => $value) {
					// Add the AND only if we are not on the first condition
					if ($first) {
						$first = false;
					} else {
						$query .= ' AND ';
					}
					// The field id in Cirrus shield as a capital letter for the I, not in Myddleware
					if ($key == 'id') {
						$key = 'Id';
					}
					// Add the condition
					$query .= $key." = '".$value."' ";
				}
			// Function called as a standard read, we use the reference date
			} else {
				$query .= " WHERE ".$dateRefField." > ".$param['date_ref'].$this->organizationTimezoneOffset; 
				// $query .= " WHERE ".$dateRefField." > 2017-03-30 14:42:35-05 "; 
			}		

			// Buid the parameters to call the solution
			$selectparam = ["authToken" 	=> $this->token,
							"selectQuery" 	=> $query,
							];
			$url = sprintf("%s?%s", $this->url."Query", http_build_query($selectparam));
			$resultQuery = $this->call($url);

			// If the query return an error 
			if (!empty($resultQuery['Message'])) {
				throw new \Exception($resultQuery['Message']);	
			}	
			// If no result
			if (!empty($resultQuery[$param['module']])) {
				// If only one record, we add a dimension to be able to use the foreach below
				if (empty($resultQuery[$param['module']][0])) {
					$tmp[$param['module']][0] = $resultQuery[$param['module']];
					$resultQuery = $tmp;
				}
				// For each records
				foreach($resultQuery[$param['module']] as $record) {				
					// For each fields expected
					foreach($param['fields'] as $field) {
						if ($field == 'id') {
							$field = 'Id';
						}					
						// We check the lower case because the result of the webservice return sfield without capital letter (first_name instead of First_Name)
						if(isset($record[$field])) {
							// If we are on the date ref field, we add the entry date_modified (put to lower case because ModificationDate in the where is modificationdate int the select
							if ($field == $dateRefField) {
								$row['date_modified'] = $record[$field];
							} elseif ($field == 'Id') {
								$row['id'] = $record[$field];
							} else {
								// Cirrus return an array when the data is empty
								if (is_array($record[$field])) {
									$row[$field] = '';
								} else {
									$row[$field] = $record[$field];
								}
							}
						}
					}
					if (
							!empty($record[$dateRefField])
						&&	$result['date_ref'] < $record[$dateRefField]
					) {								
						// Transform the date with the organization timezone
						$dateRef = new \DateTime($record[$dateRefField]);
						$dateRef->modify($this->organizationTimezoneOffset.' hours');
						$result['date_ref'] = $dateRef->format('Y-m-d H:i:s');
					}
					$result['values'][$row['id']] = $row;
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
*/	
	// Create data in the target solution
	public function create($param) {
// print_r($param);
		$action = ($this->update ? 'update' : 'create');
		try {
			foreach($param['data'] as $idDoc => $data) {
				 // Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
				
				// XML creation
				// $xmlData = '<Data><'.$param['module'].'>';
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
					$fieldList[] = $key;
					$val[$key] = $value;
					// $xmlData .= '<'.$key.'>'.$value.'</'.$key.'>';
				}	
				$values[] = $val;
			}	

print_r($values);
			$xml = \ZuoraAPIHelper::printXMLWithNS($action, $param['module'], $fieldList, $values, $this->debug, 0, $this->defaultApiNamespace, $this->defaultObjectNamespace, false);

			$operation = \ZuoraAPIHelper::bulkOperation($this->client, $this->header, $action, $xml, count($values), $this->debug);
 

 
 $response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $operation['response']);
$xml2 = new \SimpleXMLElement($response);
$array = json_decode(json_encode((array)$xml2), TRUE); 
 print_r($array);

			 // General error
			if (!empty($operation['errorList'])) {
				throw new \Exception(print_r($operation['errorList'][0],true));
			}
			if (empty($operation['response'])) {
				throw new \Exception('No response from Zuora. ');
			}
 // $xml_obj = new \SimpleXMLElement($operation['response']);
        // $xml_obj->registerXPathNamespace($this->defaultApiNamespace,$this->defaultApiNamespaceURL);
        // $xml_obj->registerXPathNamespace($this->defaultObjectNamespace,$this->defaultObjectNamespaceURL);

/* 
foreach ($operation['response'] as $myXml) {
echo chr(10).chr(10).'$successCount'.chr(10); 
// $xml   = simplexml_load_string($buffer);
// $array = $this->XML2Array($myXml);
// print_r( $array);
// $array = array($xml->getName() => $array);
$array = json_decode(json_encode((array)$myXml), TRUE);
print_r( $array);
}
// $array = json_decode(json_encode((array)simplexml_load_string($operation['response'])),1);
 */
//invalid xml file
// $xmldata = $operation['response'];
// $xmlparser = xml_parser_create();
// xml_parse_into_struct($xmlparser,$xmldata,$values);
// xml_parser_free($xmlparser);
// print_r($values);

			$result[$idDoc] = array(
									'id' => $dataSent[$param['module']]['GUID'],
									'error' => false
							);
			// Transfert status update
			// $this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}
		catch (\Exception $e) {
			$error = $e->getMessage();
			$result[$idDoc] = array(
					'id' => '-1',
					'error' => $error
			);
		}
print_r($result);		
return null;	
		return $result;
	}
	
protected function XML2Array($parent)
{
    $array = array();

    foreach ($parent as $name => $element) {
        ($node = & $array[$name])
            && (1 === count($node) ? $node = array($node) : 1)
            && $node = & $node[];

        $node = $element->count() ? XML2Array($element) : trim($element);
    }

    return $array;
}
	// Cirrus Shield use the same function for record's creation and modification
	public function update($param) {
		$this->update = true;
		return $this->create($param);
	}
/*		
	// retrun the reference date field name
	public function getDateRefName($moduleSource, $RuleMode) {
		// Creation and modification mode
		if($RuleMode == "0") {
			return "ModificationDate";
		// Creation mode only
		} else if ($RuleMode == "C"){
			return "CreationDate";
		} else {
			throw new \Exception ("$RuleMode is not a correct Rule mode.");
		}
		return null;
	}
	
	protected function getOrganizationTimezone() {
		// Get the organization in Cirrus
		$query = 'SELECT DefaultTimeZoneSidKey FROM Organization';
		// Buid the parameters to call the solution
		$selectparam = ["authToken" 	=> $this->token,
						"selectQuery" 	=> $query,
						];
		$url = sprintf("%s?%s", $this->url."Query", http_build_query($selectparam));
		$resultQuery = $this->call($url);
		if (empty($resultQuery['Organization']['DefaultTimeZoneSidKey'])) {
			throw new \Exception('Failed to retrieve the organisation timezone : no organization found '.$resultQuery['Organization']['DefaultTimeZoneSidKey'].'. ');	
		}
		
		// Get the list of timeZone  Cirrus
		$organizationFields = $this->call($this->url.'Describe/Organization?authToken='.$this->token);
		
		if (!empty($organizationFields['Fields'])) {
			// Get the content of the field DefaultTimeZoneSidKey
			$timezoneFieldKey = array_search('DefaultTimeZoneSidKey', array_column($organizationFields['Fields'], 'Name'));
			if (!empty($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'])) {
				// Get the key of the timezone of the organization
				$timezoneOrganizationKey = array_search($resultQuery['Organization']['DefaultTimeZoneSidKey'], array_column($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'], 'Name'));		
				if (!empty($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'][$timezoneOrganizationKey]['Label'])) {
					// Get the offset of the timezone formatted like (GMT-05:00) Eastern Standard Time (America/New_York)
					$this->organizationTimezoneOffset = substr($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'][$timezoneOrganizationKey]['Label'], strpos($organizationFields['Fields'][$timezoneFieldKey]['PicklistValues'][$timezoneOrganizationKey]['Label'], 'GMT')+3, 3);
				}
			}
		}	
		// Error management
		if (empty($this->organizationTimezoneOffset)) {
			throw new \Exception('Failed to retrieve the organisation timezone : no timezone found for the value ');	
		}
	}

	
	protected function call($url, $method = 'GET', $xmlData='', $timeout = 10){   
		if (function_exists('curl_init') && function_exists('curl_setopt')) {
            $ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			
			// Some additional parameters are required for POST 
			if ($method=='POST') {
				$headers = array(
								"Content-Type: application/x-www-form-urlencoded",
								"charset=utf-8"
								);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_POSTFIELDS, "=".$xmlData);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLINFO_HEADER_OUT, true);
				curl_setopt($ch, CURLOPT_SSLVERSION, 6);
				curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			}
            $result = curl_exec($ch);	
            curl_close($ch);
			// The login function return a string not an XML
			if ($method=='login') {
				return $result ? json_decode($result, true) : false;
			} else {
				if (@simplexml_load_string($result)) {
					$xml = simplexml_load_string($result);
					$json = json_encode($xml);
					return json_decode($json,TRUE);   
				// The result can be a json directly, in case of an error of query call (read last for example)
				} else {
					return json_decode($result,TRUE); 
				}
			} 
        }
        throw new \Exception('curl extension is missing!');
    }	
	  */
	 
	// Build the header because it can't be created in lib_zuora.php
	protected function getHeader($sessionId){
       $sessionVal = array('session'=>$sessionId);
       $header = new \SoapHeader($this->defaultApiNamespaceURL,
    				'SessionHeader',
    				$sessionVal);
       return $header;
    }
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/zuora.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class zuora extends zuoracore {
		
	}
}