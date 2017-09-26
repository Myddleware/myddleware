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


class sage50core extends solution
{

    const APPLICATION = "accounts50";
    const CONTRACT = "GCRM";
    private $access_token;
    protected $plurial_name = array();
    protected $moduleFields;
    protected $xml;
    protected $dataToHTML = array();
	protected $required_fields = array('default' => array('id','updated','published'));

	
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
     * Function login for connexion sage50
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
            $response = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/$schema', 'login');	
            if ($response['curlErrorNumber'] === 0) { // url all fine . precced as usual
                if (!empty($response['curlInfo']) && $response['curlInfo']['http_code'] === 200) { // token is valid
                    $this->connexion_valide = true;
                    $this->setAccessToken($this->token);
                    $xml = simplexml_load_string($response['curlData']);
                    $this->setXML($xml);
                } else if (!empty($response['curlInfo']) && $response['curlInfo']['http_code'] === 401) { //if 401 non unauthorized
                    throw new \Exception('Bad auth key');
                } else {
                    throw new \Exception('Error connexion for sage50');
                }

            } else {
                throw new \Exception('No response from sage50.');
            }
        } catch (\Exception $e) {
            $error = 'Failed to login to sage50 : ' . $e->getMessage();
            echo $error . ';';
            $this->logger->error($error);
            return array('error' => $error);
        }
    } // login($paramConnexion)

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
            $xml = $this->getXML();
            $modules_names = $xml->xpath('//xs:element[@sme:canGet="true" and @sme:role="resourceKind"]/@name');			
            if (count($modules_names) > 0) { // url all fine . precced as usual
                foreach ($modules_names as $key => $moduleName) {
                    $this->moduleFields[(string)$moduleName] = (string)$moduleName; // get attribute who role is resourceKind and can get is true
                }			
                return $this->moduleFields;
            } else {
                throw new \Exception('No modules from sage50.');
            }
        } catch (\Exception $e) {
            $error = 'Error get modules for sage50 : ' . $e->getMessage();
            echo $error . ';';
            $this->logger->error($error);
            return array('error' => $error);
        }

    }//get_modules


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
            $xml = $this->getXML();
             $modules = $xml->xpath('//xs:complexType[@name="' . $module . '--type"]/xs:all/*'); // on recrée la requete avec l'element sélectionné
	
            if (count($modules) > 0) { // url all fine . precced as usual
                if ($xml) {
                    $this->moduleFields = array();
	// echo '<pre>';
	// print_r($modules);
	// die(); 					
                    foreach ($modules as $key => $module) {						
                        if ((string)$module["nillable"]) { // required or not
                            $existRequired = 1;
                        } else {
                            $existRequired = 0;
                        }
                        str_replace('xs:', '', (string)$module['type']);
                        $this->moduleFields[(string)$module["name"]] = array('label' => (string)$module["name"], 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => $existRequired);
                    }
/* // GET http://www.example.com/sdata/myApp/-/-/products?**includeMetadata**=true 			
 $this->token = $this->getAccessToken();
$modules_pluralName = $this->getPluralName($module);
// Get one data from Sage
// $response = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/-/-/' . $modules_pluralName . '?**includeMetadata**=true&format=json', 'read_last');
// $response = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/-/-/$prototypes/addresses', 'read_last');
$response = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/-/-/contacts?**includeMetadata**=true', 'read_last');
echo '<pre>test';
print_r($response);
die(); */
                    return $this->moduleFields;
                }

            } else {
                throw new \Exception('No modules from sage50.');
            }


        } catch (\Exception $e) {
            return false;
        }
    } // get_module_fields($module)

	
    public function getAccessToken()
    {
        return $this->access_token;
    }//getAccessToken

    public function setAccessToken($token)
    {
        $this->access_token = $token;
    }//setAccessToken

    public function getFieldModules($index)
    {
        return $this->moduleFields = $this->moduleFields[$index];
    }//getFieldModules

    /**
     * setter for xml schema
     * @param $xml
     */
    public function setXML($xml)
    {
        $this->xml = $xml;
    }//setXML

    /**
     * getter for xml schema
     * @return mixed
     */
    public function getXML()
    {
        return $this->xml;
    }//getXML


    /**
     * Function for get plural name of attribute selected, required for routes get modules field
     * @param $name
     * @return bool|string
     */
    public function getPluralName($name)
    {
        $xml = $this->getXML();
        $modules_names = $xml->xpath('//xs:element[@name="' . $name . '"]/@sme:pluralName');
        return $modules_names ? (string)$modules_names[0] : false;
    }//getPluralName

 
    public function repairJson($result) {
		for ($i = 0; $i <= 31; ++$i) {
			$result = str_replace(chr($i), "", $result);
		}
		$result = str_replace(chr(127), "", $result);

		if (0 === strpos(bin2hex($result), 'efbbbf')) {
		   $result = substr($result, 3);
		}
		return $result;
	}

    /**
     * Read one specific record
     * @param $result
     * @return array|mixed
     */

    public function read_last($param)
    {
		// return null;
        $result = array();
        try {
            // Call to get the token
            $this->token = $this->getAccessToken();
            $modules_pluralName = $this->getPluralName($param ["module"]);
			// Get one data from Sage
            $response = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/' . $modules_pluralName . '?count=1&format=json', 'read_last');
			
            if (!empty($response['curlInfo']) && $response['curlInfo']['http_code'] === 200) { // token is valid
                if (!empty($response['curlData']['$resources'][0])) { 	
					// Get the data for every field
                    foreach ($param['fields'] as $field) {						
                        $result['values'][$field] = $response['curlData']['$resources'][0][$field];
                    }
                    $result['done'] = true;
                    //“1” if a data has been found
                    //“0”if no data has been found and no error occured
                    //“-1” if an error occured
                } else {
                    if (strlen($response['curlData']) === 0) {
                        $result['done'] = false;
                    } else {
                        throw new \Exception('No data from sage50.');
                    }
                }
            } else {
                throw new \Exception('Error read data from sagesSata.');
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : ' . $e->getMessage() . ' ' . __CLASS__ . ' Line : ( ' . $e->getLine() . ' )';
            $result['done'] = -1;
        }
        return $result;
    }
	
    /**
     * Function read
     */
	public function read($param) {
		try {
			$result['date_ref'] = $param['date_ref'];
			$result['count'] = 0;
			if (empty($param['limit'])) {
				$param['limit'] = 100;
			}
$param['limit'] = 1;
			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields']);
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			
			// Get the reference date field name
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);		
			$query = 'select=';
			// Build the SELECT 
			if (!empty($param['fields'])) {
				foreach ($param['fields'] as $field) {
					$query .= $field.',';
				}
				// Delete the last coma 
				$query = rtrim($query, ',');
			} 
			
			// Generate the WHERE
			// if a specific query is requeted we don't use date_ref (used for child document)
			if (!empty($param['query'])) {
				$query .= '&where=';
				$first = true;
				foreach ($param['query'] as $key => $value) {
					// Add the AND only if we are not on the first condition
					if ($first) {
						$first = false;
					} else {
						$query .= ',';
					}
					// Add the condition
					$query .= $key." = '".$value."' ";
				}
			// Function called as a standard read, we use the reference date
			} else {
				$param['date_ref'] = $this->dateTimeFromMyddleware($param['date_ref']);		
				$query .= '&where='.$dateRefField.' gt @'.$param['date_ref'].'@';

				$query .= '&orderBy='.$dateRefField.' desc';
				// $query .= '&orderBy=firstName desc';
			}			
// $param['limit'] = 10;
			// convert space
			$query = str_replace(' ','%20',$query);			
			// Call to get the token
            $this->token = $this->getAccessToken();
            $modules_pluralName = $this->getPluralName($param ["module"]);
			// Get one data from Sage
            $response = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/' . $modules_pluralName . '?'.$query.'&count='.$param['limit'], 'read');
	
			if (!empty($response['curlInfo']) && $response['curlInfo']['http_code'] === 200) { // token is valid
				// If no result
                if (!empty($response['curlData']['entry'])) { 
					// If only one record, we add a dimension to be able to use the foreach below
					if (empty($response['curlData']['entry'][0])) {
						$tmp['curlData']['entry'][0] = $response['curlData']['entry'];
						$response = $tmp;
					}
					// For each records
					foreach($response['curlData']['entry'] as $record) {			
						// Add date_modified					
						$row['date_modified'] = $this->dateTimeToMyddleware($record[$dateRefField]);				
						$row['updated'] = $record['updated'];
						$row['published'] = $record['published'];
	
						// Get the detail of the current record (only in json format)
						$detailRecord = $this->makeRequest('', $this->token, str_replace('atomentry', 'json',$record['id']), 'read_last');
		
						if (empty($detailRecord['curlData']['$resources'][0])) {
							throw new \Exception('Failed to get the detail of the record '.$record['id'].'.');
						}
						// For each fields expected
						foreach($param['fields'] as $field) {					
							if (in_array($field, array('updated', 'published'))) {
								continue;
							}
							if ($field == 'id') {
								$row[$field] = $detailRecord['curlData']['$resources'][0]['$uuid'];
							} else {
								$row[$field] = $detailRecord['curlData']['$resources'][0][$field];
							}
						}
						// Calculae the reference date
						if (
								!empty($row['date_modified'])
							&&	$result['date_ref'] < $row['date_modified']
						) {								
							$result['date_ref'] = $row['date_modified'];
						}
						$result['values'][$row['id']] = $row;
						$result['count']++;
						$row = array();
					}
				}
			// If the query return an error 
			}elseif (!empty($resultQuery['Message'])) {
				throw new \Exception($resultQuery['Message']);	
			}	
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
		}	
// print_r($result);		
// return null;		
		return $result;
	}	
	
	// Function de conversion de datetime format solution à un datetime format Myddleware
	protected function dateTimeToMyddleware($dateTime) {
		$date = new \DateTime($dateTime);
		return $date->format('Y-m-d H:i:s');
	}// dateTimeToMyddleware($dateTime)	
	
	// Function de conversion de datetime format Myddleware à un datetime format solution
	protected function dateTimeFromMyddleware($dateTime) {
		$date = new \DateTime($dateTime);
		return $date->format(\DateTime::ISO8601);
	}// dateTimeFromMyddleware($dateTime)    

   	protected function generate_uuid() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}
	
	// Create data in the target solution
	public function create($param) {
		print_r($param);
		foreach($param['data'] as $idDoc => $data) {
			try {
				
				/*  $this->token = $this->getAccessToken();
            $modules_pluralName = $this->getPluralName($param ["module"]);
echo ' $modules_pluralName : '. $modules_pluralName.chr(10);			
			// Get one data from Sage
            $response = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/salesOrders/$template&format=json', 'read');
print_r($response);	
return null;  */
				// GET /sdata/myApp/myContract/-/salesOrders/$template
				
				// Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
	$uuid = $this->generate_uuid();
echo '$uuid  : '.$uuid .chr(10);	
				// Call to get the token
				$this->token = $this->getAccessToken();
				$modules_pluralName = $this->getPluralName($param ["module"]);
				$xmlData = 
'<?xml version="1.0" encoding="utf-8"?>
<entry xmlns:sdata="http://schemas.sage.com/sdata/2008/1" 
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xmlns="http://www.w3.org/2005/Atom">
  <id/>
  <title/>
  <content/>
  <sdata:payload>
    <'.$param ["module"].' xmlns="http://schemas.sage.com/crmErp/2008">'.chr(10);
      // sdata:uuid="'.$uuid.'">'.chr(10);
				/* foreach ($data as $key => $value) {
					// Field only used for the update and contains the ID of the record in the target solution			
					 if ($key=='target_id') {					
						// If updade then we change the key in Id
						if (!empty($value)) {
							$key = 'Id';
						} else { // If creation, we skip this field
							continue;
						}
					} 
					$xmlData .= '      <'.$key.'>'.$value.'</'.$key.'>'.chr(10);				
				} */
				
				
				// $xmlData .= '      <active xsi:nil="true" />'.chr(10);
				// $xmlData .= '      <companyPersonFlag>Company</companyPersonFlag>'.chr(10);
				// $xmlData .= '      <status>Open</status>'.chr(10);
				// $xmlData .= '      <type>Unknown</type>'.chr(10);
$xmlData .= 
'  <familyName>Doe</familyName>
   <firstName>John</firstName>
   <tradingAccount sdata:uuid="c10e13ab-1403-4a2d-b8bc-c4ddf2f46daf" />
   <type>Customer Delivery Contact</type>'.chr(10);		
/* $xmlData .= 
'  <familyName>Doe</familyName>
   <firstName>John</firstName>
   <reference>999</reference>
   <tradingAccount sdata:uuid="c10e13ab-1403-4a2d-b8bc-c4ddf2f46daf" />
   <type>Customer Delivery Contact</type>'.chr(10);	 */	
								
				// $xmlData .= '      <tradingAccount sdata:uuid="c10e13ab-1403-4a2d-b8bc-c4ddf2f46daf" />'.chr(10);
				// $xmlData .= '      <type>Customer Delivery Contact</type>'.chr(10);
						
$xmlData .= '    </'.$param ["module"].'>
  </sdata:payload>
</entry>';
							
			/* 				
				$xmlData  = 	'<?xml version="1.0" encoding="utf-8"?>
								<entry xmlns:atom="http://www.w3.org/2005/Atom" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:cf="http://www.microsoft.com/schemas/rss/core/2005" xmlns="http://www.w3.org/2005/Atom" xmlns:sdatasync="http://schemas.sage.com/sdata/sync/2008/1" xmlns:sdata="http://schemas.sage.com/sdata/2008/1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/" xmlns:sme="http://schemas.sage.com/sdata/sme/2007" xmlns:http="http://schemas.sage.com/sdata/http/2008/1" xmlns:sc="http://schemas.sage.com/sc/2009" xmlns:crm="http://schemas.sage.com/crmErp/2008">
								  <id/>
								  <title/>
								  <content/>
								  <sdata:payload>
									<'.$param ["module"].' xmlns="http://schemas.sage.com/'.self::CONTRACT.'">
									  <familyName>fauretest</familyName>
									</contact>
								  </sdata:payload>
								</entry>';	
								 */
								// <contact sdata:uri="'.$this->paramConnexion['host'].'/sdata/accounts50/GCRM/contact" xmlns="http://schemas.sage.com/crmErp/2008">
								// <salesOrder sdata:uri="$this->paramConnexion['host']/sdata/accounts50/GCRM/contact" xmlns="http://schemas.sage.com/crmErp/2008">
								// <salesOrder sdata:uri="http://40.127.137.52:5493/sdata/accounts50/GCRM/{3BEF6B40-1059-4FED-B801-E19279096657}/salesOrder" xmlns="http://schemas.sage.com/crmErp/2008">
								
/* 								
<?xml version="1.0" encoding="utf-8"?>
<entry xmlns:sme="http://schemas.sage.com/sdata/sme/2007"
       xmlns:sdata="http://schemas.sage.com/sdata/2008/1"
       xmlns:cf="http://www.microsoft.com/schemas/rss/core/2005"
       xmlns="http://www.w3.org/2005/Atom">
  <id>http://localhost:8001/sdata/aw/dynamic/-/employees(1)</id>
  <link href="http://localhost:8001/sdata/aw/dynamic/-/employees(1)?format=html" rel="alternate" type="text/html" title="" />
  <link href="http://localhost:8001/sdata/aw/dynamic/-/employees(1)" rel="self" type="application/atom+xml" title="" />
  <link href="http://localhost:8001/sdata/aw/dynamic/-/employees(1)" rel="edit" type="application/atom+xml" title="" />
  <link href="http://localhost:8001/sdata/aw/dynamic/-/employees(1)?format=atomentry" rel="via" type="application/atom+xml" title="" />
  <published>0001-01-01T00:00:00+00:00</published>
  <sdata:payload>
    <Employee xmlns="http://schemas.sage.com/dynamic/2007">
      <Title>Production Technician - WC60</Title>
      <NationalIdNumber>14417807</NationalIdNumber>
<ContactId>1209</ContactId> */

/* // <?xml version="1.0" encoding="utf-8"?><xs:schema targetNamespace="http://schemas.sage.com/crmErp/2008" xmlns="http://schemas.sage.com/crmErp/2008" xmlns:crm="http://schemas.sage.com/crmErp/2008" xmlns:sc="http://schemas.sage.com/s
// c/2009" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:sdata="http://schemas.sage.com/sdata/2008/1" xmlns:sme="http://schemas.sage.com/sdata/sme/2007" xmlns:xs="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified"><xs:
// import namespace="http://schemas.sage.com/sc/2009" schemaLocation="http://bps-test01:5493/sdata/accounts50/GCRM/-/$schema/$schema?namespace=http://schemas.sage.com/sc/2009" /><xs:element name="bankAccount" type="crm:bankAccount--t
// ype" sme:canGet="true" sme:canPost="false" sme:canPut="false" sme:canDelete="false" sme:canSearch="false" sme:pluralName="bankAccounts" sme:canPagePrevious="false" sme:canPageNext="false" sme:canPageIndex="false" sme:supportsETag=
// "false" sme:hasUuid="true" sme:batchingMode="none" sme:role="resourceKind" sme:iArray
// ( */
								
			// $xmlData  = 	'<entry xmlns:sdata="http://schemas.sage.com/sdata/2008/1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.w3.org/2005/Atom"><id/><title/><content/><sdata:payload><contact xmlns="http://schemas.sage.com/'.self::CONTRACT.'" sdata:uuid="BE7D7445-7FA4-4c67-AC22-5F6446314771"><familyName>fauretest</familyName></contact></sdata:payload></entry>';						
print_r($xmlData);
// print_r($parameter);
			
			// Get one data from Sage
			// POST /sdata/myApp/myContract/-/salesOrders
            $dataSent = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/' . $modules_pluralName, 'create', null, $xmlData);
		return null;
				// Call to get the token
 				// $this->token = $this->getAccessToken();
				// $dataSent = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/'.$param['module'], $parameter, 'POST');
/*
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
} */
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
} 

   // retrun the reference date field name
	public function getDateRefName($moduleSource, $RuleMode) {
		// Creation and modification mode
		if($RuleMode == '0') {
			return 'updated';
		// Creation mode only
		} else if ($RuleMode == 'C'){
			return 'published';
		} else {
			throw new \Exception ("$RuleMode is not a correct Rule mode.");
		}
		return null;
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
    function makeRequest($server, $token, $path, $method, $args = null, $data = null, $read_last = false)
    {
// echo 'A'.chr(10);		
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
                $headers[] = 'Authorization: Basic ' . $token;
                // $contentType = 'Content-Type: application/atom+xml; type=entry';
                // $contentType = 'Content-Type: application/x-www-form-urlencoded';
				// curl_setopt($ch, CURLOPT_HTTPHEADER, array($contentType));
                if ("create" == $method/*  && "array" !== gettype($data) */) {
// echo 'AAZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZ '.$data.chr(10);		
					$headers[] = "Content-Type: application/atom+xml; type=entry";
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLINFO_HEADER_OUT, true);
					curl_setopt($ch, CURLOPT_SSLVERSION, 6);
					// curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
					
                    // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    // $data_string = json_encode($data);
                    // curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                }
            } else {
                // $authHeader = 'Authorization: Basic ' . $token;
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            // Execute request
// echo 'B'.chr(10);		
            $result = curl_exec($ch);
			// if ("create" == $method) 
// print_r($result);		
// else echo substr($result,0,5000);	
// else echo $result;	
// echo 'C'.chr(10);		

			if ($method == 'read') {
				$xml = simplexml_load_string($result);
				$json = json_encode($xml);
				$result = json_decode($json,TRUE);						
			} else {
				$result = $this->repairJson($result);
				$result = (!empty(json_decode($result,TRUE)) ? json_decode($result,TRUE) : $result);
			}
            //Object for response curl
            $response = array(
                'curlData' => $result,
                'curlInfo' => curl_getinfo($ch),
                'curlErrorNumber' => curl_errno($ch),
                'curlErrorMessage' => curl_error($ch)
            );

            // Close Connection
            curl_close($ch);
// echo '<pre>';
            return $response;
        }
        throw new \Exception('curl extension is missing!');
    }//makeRequest

	
}

/* * * * * * * *  * * * * * *  * * * * * *
   if custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__ . '/../Custom/Solutions/sage50.php';
if (file_exists($file)) {
    require_once($file);
} else {
    //Sinon on met la classe suivante
    class sage50 extends sage50core
    {
    }
}
