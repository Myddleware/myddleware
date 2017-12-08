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

// require_once('lib/sagesdata/Conn.php');
// require_once('lib/sagesdata/Query.php');
// require_once('lib/sagesdata/Schema.php');
// require_once('lib/sagesdata/Query/Type/Create.php');

class sage50core extends solution
{

    const APPLICATION = "accounts50";
    const CONTRACT = "GCRM";
    private $access_token;
    protected $plurial_name = array();
    protected $sdata;
    protected $moduleFields;
    protected $xml;
    protected $dataToHTML = array();
	protected $required_fields = array('default' => array('id','updated','published'));
	protected $update;
	protected $subModules = array('salesOrderLine', 'salesInvoiceLine','purchaseOrderLine','purchaseOrderDeliveryLine'/* ,'bankAccount' */);
	protected $parentModules = array('salesOrder','salesInvoice'/* ,'tradingAccount' */);
	protected $createOnlyModules = array('salesOrder','salesInvoice');
    protected $readLimit = 100;

	protected $moduleSubFields = array(	
										'tradingAccount' => array( // Name of the main module into Sage
																	'phoneNumber' => array( // Name of the module into Sage
																							'title' => 'phones',	// Name of the structure in the title in the link structure
																							'structureNames' => array('Business Phone', 'Other Phone','Business Fax'), // Every type of data linked to the searchField
																							'searchField' => 'type', // Search field where the structureNames are strored
																							'structureFields' => array('uuid','text') // Fields belonging to the structure available in Myddleware
																						),
																	'postalAddress' => array( // Name of the module into Sage
																							'title' => 'postalAddresses',	// Name of the structure in the title in the link structure
																							'structureNames' => array('Billing', 'Shipping', 'Registered'), // Every type of data linked to the searchField
																							'searchField' => 'type', // Search field where the structureNames are strored
																							'structureFields' => array('uuid','active','name','address1','address2','address3','address4','townCity','county','stateRegion','zipPostCode','country','primacyIndicator','primacyIndicator','description'), // Fields belonging to the structure available in Myddleware
																							// 'additionalFilters' => array(array('key' => 'primacyIndicator', 'value' => 'true'))
																						),	
																	'email' => array( // Name of the module into Sage
																							'title' => 'emails',	// Name of the structure in the title in the link structure
																							'structureNames' => array('Supplier Registered Email', 'Supplier Registered Email2', 'Supplier Registered Email3','Supplier Delivery Email'), // Every type of data linked to the searchField
																							'searchField' => 'label', // Search field where the structureNames are strored
																							'structureFields' => array('uuid','address') // Fields belonging to the structure available in Myddleware
																						),
																	'contact' => array( // Name of the module into Sage
																							'title' => 'contacts',	// Name of the structure in the title in the link structure
																							'structureNames' => array('1','2'), // Every type of data linked to the searchField
																							'searchField' => 'addressType', // Search field where the structureNames are strored
																							'structureFields' => array('uuid','fullName','salutation','firstName','familyName') // Fields belonging to the structure available in Myddleware
																						),
																	'bankAccount' => array( // Name of the module into Sage
																							'title' => 'bankAccounts',	// Name of the structure in the title in the link structure
																							'structureFields' => array('uuid','active','name','description','branchIdentifier','accountNumber','iBANNumber','bICSwiftCode','rollNumber','currency','operatingCompanyCurrency','paymentAllowedFlag','receiptAllowedFlag') // Fields belonging to the structure available in Myddleware
																						),	
																),
										'bankAccount' => array( // Name of the main module into Sage
																	'tradingAccount' => array( // Name of the module into Sage
																								'title' => 'tradingAccount',	// Name of the structure in the title in the link structure
																								'structureFields' => array('uuid','active','customerSupplierFlag','companyPersonFlag','reference','name'), // Fields belonging to the structure available in Myddleware
																								// 'additionalFilters' => array(array('key' => 'primacyIndicator', 'value' => 'true'))
																							),
																	'postalAddress' => array( // Name of the module into Sage
																								'title' => 'postalAddress',	// Name of the structure in the title in the link structure
																								'structureNames' => array('Other'), // Every type of data linked to the searchField
																								'searchField' => 'type', // Search field where the structureNames are strored
																								'structureFields' => array('uuid','active','name','address1','address2','address3','address4','townCity','county','stateRegion','zipPostCode','country','primacyIndicator','primacyIndicator','description'), // Fields belonging to the structure available in Myddleware
																								// 'additionalFilters' => array(array('key' => 'primacyIndicator', 'value' => 'true'))
																							),					
																)
								);		
	
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
            // $response = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/$system/registry/-/contracts', 'login');
		
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

			if ($type == 'source') {
				$modules_names = $xml->xpath('//xs:element[@sme:canGet="true" and @sme:role="resourceKind"]/@name');		
			} else {
				$modules_names = $xml->xpath('//xs:element[@sme:canPost="true" and @sme:role="resourceKind"]/@name');
				// Add submodules if exist
				if (!empty($this->subModules)) {
					foreach($this->subModules as $subModule) {
						$modules_names[$subModule] = $subModule;	
					}
				}
			}
            if (count($modules_names) > 0) { // url all fine . precced as usual
                foreach ($modules_names as $key => $moduleName) {
                    $modules[(string)$moduleName] = (string)$moduleName; // get attribute who role is resourceKind and can get is true
                }	
            } else {
                throw new \Exception('No modules from sage50.');
            }
			return $modules;
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
    public function get_module_fields($module, $type = 'source') {
        parent::get_module_fields($module, $type);		
        try {			
            // Call to get the token
            $this->token = $this->getAccessToken();
            $xml = $this->getXML();				
			
			if ($xml) {
				$fields = $xml->xpath('//xs:complexType[@name="' . $module . '--type"]/xs:all/*'); // on recrée la requete avec l'element sélectionné			
				if (count($fields) > 0) { // url all fine . precced as usual	
                    foreach ($fields as $key => $field) {
						
						// If the type is a list or a structure, we check if we have registered it in the moduleSubFields array
						if (
								(
									substr($field['type'],-6) == '--type' 
								 OR	substr($field['type'],-6) == '--list' 
								)
						){
			
							// Get structure name from the full name (e.g. "sc:phoneNumber--list")
							$structureKey = substr($field['type'], strpos($field['type'],':')+1, strlen($field['type'])-6-(strpos($field['type'],':')+1));							
							if (!empty($this->moduleSubFields[$module][$structureKey])) {
								$fieldList = $this->moduleSubFields[$module][$structureKey];
								// If it is registered in the moduleSubFields array, we add the field in structure
								if (!empty($fieldList['structureNames'])) {
									foreach ($fieldList['structureNames'] as $structureName) {
										foreach ($fieldList['structureFields'] as $structureField) {
											if ($structureField == 'uuid') {
												$this->fieldsRelate[$structureKey.'__'.str_replace(' ','',$structureName).'__'.str_replace(' ','',$structureField)] = array('label' => $structureKey.' : '.$structureName.' - '.$structureField, 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required_relationship' => 0);
											} else {
												$this->moduleFields[$structureKey.'__'.str_replace(' ','',$structureName).'__'.str_replace(' ','',$structureField)] = array('label' => $structureKey.' : '.$structureName.' - '.$structureField, 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0);
											}
										}
									}
								} else {
									foreach ($fieldList['structureFields'] as $structureField) {
										if ($structureField == 'uuid') {
											$this->fieldsRelate[$structureKey.'__'.str_replace(' ','',$structureField)] = array('label' => $structureKey.' - '.$structureField, 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required_relationship' => 0);
										} else {
											$this->moduleFields[$structureKey.'__'.str_replace(' ','',$structureField)] = array('label' => $structureKey.' - '.$structureField, 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0);
										}
									}
								}
							}
						} else {
							switch ($field['type']) {
								case 'xs:boolean':
									$typeData = 'bool';
									break;
								case 'xs:date':
									$typeData = 'date';
									break;
								case 'xs:time':
									$typeData = 'time';
									break;
								default:
									$typeData = 'varchar(255)';
							}
							$this->moduleFields[(string)$field["name"]] = array('label' => (string)$field['name'], 'type' => $typeData, 'type_bdd' => 'varchar(255)', 'required' => 0);
						}
                    }
                }
				
				if ($type == 'target') {
					$relateFields = $xml->xpath('//xs:complexType[@name="' . $module . '--type"]/xs:all/xs:element[@sme:relationship="reference" or @sme:relationship="parent"]/@name'); // on recrée la requete avec l'element sélectionné
					if (count($relateFields) > 0) { // url all fine . precced as usual
						foreach ($relateFields as $key => $field) {						
							$this->fieldsRelate[(string)$field["name"]] = array('label' => (string)$field["name"], 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required_relationship' => 0);
						}
					}
				}
				
				// Add relate field in the field mapping 
				if (!empty($this->fieldsRelate)) {
					$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
				}	
            } else {
                throw new \Exception('No modules from sage50.');
            }				
			return $this->moduleFields;
        } catch (\Exception $e) {
            return false;
        }
    } // get_module_fields($module)

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
        $result = array();
        try {
            // Call to get the token
            $this->token = $this->getAccessToken();
            $modules_pluralName = $this->getPluralName($param ["module"]);
			// Get one data from Sage	
			if (!empty($param['query']['id'])) {
				// The ID is the url. We just change the format to json
				$response = $this->makeRequest('', $this->token, $param['query']['id'], 'read');
				// $response['curlData']['entry'] = $response['curlData'];
			} else {				
				$response = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/' . $modules_pluralName . '?count=1', 'read');
				$response['curlData']['entry'] = $response['curlData']['entry'];
			}
            if (!empty($response['curlInfo']) && $response['curlInfo']['http_code'] === 200) { // token is valid
				// The format returned by Sage is different when we list or when we search by ID
				if (empty($param['query']['id'])) {
					$response['curlData'] = $response['curlData']['entry'];
				}
		
			
                // if (!empty($response['curlData']['$resources'][0])) { 
				if (!empty($response['curlData']['id'])) {				
					$linkFound = array();
					$linkData = array();
					// Get the data for every field					
                    foreach ($param['fields'] as $field) {		
						// If the field is an array, it means that there is a structure, and we have to call Sage to get the detail of this structure (e.g. phoneNumber__BusinessPhone__text)
						$fieldArray = explode('__',$field);						
						if (count($fieldArray) > 1) {
							// Make sure our field is in $moduleSubFields
							if (!empty($this->moduleSubFields[$param['module']][$fieldArray[0]])) {
								// Search the link for the structure expexted (phones for exemple)								
								foreach($response['curlData']['link'] as $link) {
									if (
											!empty($link['@attributes']['title'])
										AND $link['@attributes']['title'] == $this->moduleSubFields[$param['module']][$fieldArray[0]]['title']
									) {
										$linkFound = $link['@attributes'];
										break;
									}
								}							
								if (!empty($linkFound)) {	
									// Call sage if it isn't done already for the current record and structure (e.g.the phones for a specific tradingAccount). 
									if (empty($linkData[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']])) {
										// $linkData[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']] = $this->makeRequest($this->paramConnexion['host'], $this->token, substr($linkFound['href'],strpos($linkFound['href'],'/sdata/')).'&count='.count($this->moduleSubFields[$param['module']][$fieldArray[0]]['structureNames']), 'read');
										$linkData[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']] = $this->makeRequest($this->paramConnexion['host'], $this->token, substr($linkFound['href'],strpos($linkFound['href'],'/sdata/')), 'read');
										// If only one record, we add a dimension to be able to use the foreach below
										if (empty($linkData[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']]['curlData']['entry'][0])) {
											$tmp[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']]['curlData']['entry'][0] = $linkData[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']]['curlData']['entry'];
											$linkData = $tmp;
										}
									}	
									// if we have a result from Sage
									if (!empty($linkData[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']]['curlData']['entry'])) {
										// Search the right record whith the search field (e.g. field type for the phone)
										foreach($linkData[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']]['curlData']['entry'] as $linkRecord) {
											// We always add the uuid in the structure
											if (!empty($linkRecord['payload'][$fieldArray[0]]['@attributes']['uuid'])) {
												$linkRecord['payload'][$fieldArray[0]]['uuid'] = $linkRecord['payload'][$fieldArray[0]]['@attributes']['uuid'];										
											}
											// If there is a search field then we filter on this search field
											if (!empty($this->moduleSubFields[$param['module']][$fieldArray[0]]['searchField'])) {
												// If we have found the right record, we save the field of this structure
												// We delete space in the names of the fields because we did it in the method get_module_fields (Myddleware doesn't allow space in fieldname)
												if (str_replace(' ', '',$linkRecord['payload'][$fieldArray[0]][$this->moduleSubFields[$param['module']][$fieldArray[0]]['searchField']]) == $fieldArray[1]) {
													$result['values'][$field] = (empty($linkRecord['payload'][$fieldArray[0]][$fieldArray[2]]) ? '' : $linkRecord['payload'][$fieldArray[0]][$fieldArray[2]]); // empty = array for Sage
												}
											// Otherwise, we take the first data (only one structure), the array has only 2 value (main struture + field)
											} else {
												$result['values'][$field] = (empty($linkRecord['payload'][$fieldArray[0]][$fieldArray[1]]) ? '' : $linkRecord['payload'][$fieldArray[0]][$fieldArray[1]]);   // empty = array for Sage
											}
										}
									}
								} else {
									throw new \Exception('Failed to find the link with the title '.$this->moduleSubFields[$param['module']][$fieldArray[0]]['title'].'.' );	
								}
								// If we couldn't get the field, we set the finid to empty
								if (!isset($result['values'][$field])) {
									$result['values'][$field] = '';
								}								
							}
						} else {
							if ($field == 'id') {
								$result['values'][$field] = $response['curlData']['payload'][$param ['module']]['@attributes']['uuid'];
							} elseif (!empty($response['curlData']['payload'][$param ['module']][$field])) {
								$result['values'][$field] = $response['curlData']['payload'][$param ['module']][$field];
							} else {
								$result['values'][$field] = '';
							}
						}
                    }
					if (!empty($response['curlData']['id'])) {	
						$result['values']['id'] = $response['curlData']['id'];
					}
                    $result['done'] = true;
                } else {
					$result['done'] = false;
                }
            // If the query return an error 
			} elseif (!empty($response['Message'])) {
				throw new \Exception($response['Message']);	
			} else {
				throw new \Exception('Failed to call Sage with no error returned.');	
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
			$this->get_module_fields($param['module'],'source');		
			$result['date_ref'] = $param['date_ref'];
			// If we use an offset, we have to restart from the same beginning
			if (!empty($param['ruleParams']['OffsetDateREf'])) {
				$param['date_ref'] = $param['ruleParams']['OffsetDateREf'];
			}
			$result['count'] = 0;
			$param['limit'] = $this->readLimit;

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
				// $param['date_ref'] = $this->dateTimeFromMyddleware($param['date_ref']);		
				$param['date_ref'] = $param['date_ref'];		
				$query .= '&where='.$dateRefField.' gt @'.$param['date_ref'].'@';
				$query .= '&orderBy='.$dateRefField.' asc';
			}			
			// convert space
			$query = str_replace(' ','%20',$query);		
			
			// Call to get the token
            $this->token = $this->getAccessToken();
            $modules_pluralName = $this->getPluralName($param ['module']);
			$startIndex = (!empty($param['ruleParams']['Offset']) ? $param['ruleParams']['Offset'] : 0);

			// Get one data from Sage
			$response = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/' . $modules_pluralName . '?'.$query.'&startIndex='.$startIndex.'&count='.$param['limit'], 'read');
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
						$row['date_modified'] = $record[$dateRefField];				
						$row['updated'] = $record['updated'];
						$row['published'] = $record['published'];
						$linkFound = array();
						$linkData = array();

						// For each fields expected
						foreach($param['fields'] as $field) {					
							if (in_array($field, array('updated', 'published'))) {
								continue;
							}
							// If the field is an array, it means that there is a structure, and we have to call Sage to get the detail of this structure (e.g. phoneNumber__BusinessPhone__text)
							$fieldArray = explode('__',$field);
							if (count($fieldArray) > 1) {
								// $phones = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/accounts50/GCRM/%7BA8C43BE7-A572-4E7C-A141-14C848413F84%7D/PhoneNumbers?where=reference%20eq%20"SFE-UK"&count=4', 'read');
								// Make sure our field is in $moduleSubFields
								if (!empty($this->moduleSubFields[$param['module']][$fieldArray[0]])) {
									// Search the link for the structure expexted (phones for exemple)
									foreach($record['link'] as $link) {
										if (
												!empty($link['@attributes']['title'])
											AND $link['@attributes']['title'] == $this->moduleSubFields[$param['module']][$fieldArray[0]]['title']
										) {
											$linkFound = $link['@attributes'];
											break;
										}
									}
									if (!empty($linkFound)) {	
										// Call sage if it isn't done already for the current record and structure (e.g.the phones for a specific tradingAccount). 
										if (empty($linkData[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']])) {
											$linkData[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']] = $this->makeRequest($this->paramConnexion['host'], $this->token, substr($linkFound['href'],strpos($linkFound['href'],'/sdata/')), 'read');
											// If only one record, we add a dimension to be able to use the foreach below
											if (empty($linkData[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']]['curlData']['entry'][0])) {
												$tmp[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']]['curlData']['entry'][0] = $linkData[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']]['curlData']['entry'];
												$linkData = $tmp;
											}	
										}
										// if we have a result from Sage
										if (!empty($linkData[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']]['curlData']['entry'])) {
											// Search the right record whith the search field (e.g. field type for the phone)
											foreach($linkData[$this->moduleSubFields[$param['module']][$fieldArray[0]]['title']]['curlData']['entry'] as $linkRecord) {
												// We always add the uuid in the structure
												if (!empty($linkRecord['payload'][$fieldArray[0]]['@attributes']['uuid'])) {
													$linkRecord['payload'][$fieldArray[0]]['uuid'] = $linkRecord['payload'][$fieldArray[0]]['@attributes']['uuid'];										
												}
												// If there is a search field then we filter on this search field
												if (!empty($this->moduleSubFields[$param['module']][$fieldArray[0]]['searchField'])) {
													// If we have found the right record, we save the field of this structure
													// We delete space in the names of teh fields because we did it in the method get_module_fields (Myddleware doesn't allow space in fieldname)
													if (str_replace(' ', '',$linkRecord['payload'][$fieldArray[0]][$this->moduleSubFields[$param['module']][$fieldArray[0]]['searchField']]) == $fieldArray[1]) {
														$row[$field] = (empty($linkRecord['payload'][$fieldArray[0]][$fieldArray[2]]) ? '' : $linkRecord['payload'][$fieldArray[0]][$fieldArray[2]]); // empty = array for Sage
													}
												// Otherwise, we take the first data (only one structure), the array has only 2 value (main struture + field)
												} else {
													$row[$field] = (empty($linkRecord['payload'][$fieldArray[0]][$fieldArray[1]]) ? '' : $linkRecord['payload'][$fieldArray[0]][$fieldArray[1]]);   // empty = array for Sage
												}
											}
										}
									} else {
										throw new \Exception('Failed to find the link with the title '.$this->moduleSubFields[$param['module']][$fieldArray[0]]['title'].'.' );	
									}
									// If we couldn't get the field, we set the finid to empty
									if (!isset($row[$field])) {
										$row[$field] = '';
									}								
								}								
							} else {
								// echo count($fieldArray).chr(10);
								if ($field == 'id') {
									$row[$field] = $record['payload'][$param ['module']]['@attributes']['uuid'];
								} elseif (!empty($record['payload'][$param ['module']][$field])) {
									$row[$field] = $record['payload'][$param ['module']][$field];
								} else {
									$row[$field] = '';
								}
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
			} elseif (!empty($response['Message'])) {
				throw new \Exception($response['Message']);	
			} else {
				throw new \Exception('Failed to call Sage with no error returned.');	
			}		
			// Set the offset for the next call if needed. We keep teh reference date too, we ned it to restart from the same beginning
			if ($result['count'] == $param['limit']) {				
				$result['ruleParams'][] = array('name' => 'Offset', 'value' => $startIndex + $result['count']);
				$result['ruleParams'][] = array('name' => 'OffsetDateREf', 'value' => $param['date_ref']);
			} else {			
				$result['ruleParams'][] = array('name' => 'Offset', 'value' => 0);
				$result['ruleParams'][] = array('name' => 'OffsetDateREf', 'value' => '');
			}
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
		}		
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
		$subDocIdArray = array();
		$this->get_module_fields($param ['module'],'source');		
		foreach($param['data'] as $idDoc => $data) {
			try {
				// If update we add the target id in the xml
				if ($this->update) {
					$uuid = substr($data['target_id'], strpos($data['target_id'],'(')+1, strpos($data['target_id'],')')-(strpos($data['target_id'],'(')+1));
					$xmlId = '<id>'.$data['target_id'].'</id>'.chr(10);
				} else {
					$xmlId = '<id/>'.chr(10);
					$uuid = $this->generate_uuid();
				}			
				// Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
				// Call to get the token
				$this->token = $this->getAccessToken();
				$modules_pluralName = $this->getPluralName($param ["module"]);
				// Generate XML for creation
				$xmlData = 
'<?xml version="1.0" encoding="utf-8"?>
<entry xmlns:sdata="http://schemas.sage.com/sdata/2008/1" 
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xmlns="http://www.w3.org/2005/Atom">'.chr(10).
  $xmlId.
  '<title/>
  <content/>
  <sdata:payload>
    <'.$param ["module"].' xmlns="http://schemas.sage.com/crmErp/2008" xmlns:sc="http://schemas.sage.com/sc/2009"
      sdata:uuid="'.$uuid.'">'.chr(10);
// print_r($data);	  
				foreach (array_reverse($data) as $key => $value) {				
					// Target id is managed above, so we skip this field			
					if ($key=='target_id' OR $key=='ID') {					
						continue;
					// If the field is a submodule	
					} elseif (is_array($value)) {
						if(in_array($key, $this->subModules)) {
							$subModulesPluralName = $this->getPluralName($key);
							$xmlData .= '<'.$subModulesPluralName.'>'.chr(10);
							// Could be done with recursive function
							foreach($value as $subIdDoc => $subData) {
								// We can have a target ID if we update a record
								if (!empty($subData['target_id'])) {
									if (strlen($subData['target_id'])>36) {
										$uuid = substr($subData['target_id'], strpos($subData['target_id'],'(')+1, strpos($subData['target_id'],')')-(strpos($subData['target_id'],'(')+1));
									} else  {
										$uuid = $subData['target_id'];
									}
								} else {
									$uuid = $this->generate_uuid();
								}
								$subDocIdArray[$subIdDoc] = array('id' => $uuid);

								$xmlData .= '<'.$key.' sdata:uuid="'.$uuid.'">'.chr(10);
								foreach ($subData as $subKey => $subValue) {
									if(in_array($subKey, array($param["module"], 'id_doc_myddleware', 'target_id', 'source_date_modified'))) {
										continue;
									}	
									// If relate field or commodity (submodule)
									if (!empty($this->fieldsRelate[$subKey]) OR $subKey == 'commodity') { 
										// If relationship empty we continue
										if (empty($subValue)) {
											continue;
										}
										// Retrieve the id in parentheses if the id is included in an URL
										if (strlen($subValue) > 36) {
											$subId = substr($subValue, strpos($subValue,'(')+1, strpos($subValue,')')-(strpos($subValue,'(')+1));
										} else {
											$subId = $subValue;
										}
										$xmlData .= '      <'.$subKey.' sdata:uuid="'.$subId.'" />'.chr(10);	
										$subId = '';
									} else {
										$xmlData .= '      <'.$subKey.'>'.$subValue.'</'.$subKey.'>'.chr(10);
									}
								}
								$xmlData .= '    </'.$key.'>'.chr(10);
							}
							$xmlData .= '</'.$subModulesPluralName.'>'.chr(10);
						} else {
							throw new \Exception('The submodule '.$key.' is not registered. ');
						}
					// Relate field			
					} elseif (!empty($this->fieldsRelate[$key])) {
						// Retrieve the id in parentheses if the id is included in an URL					
						if (strlen($value) > 36) {
							$id = substr($value, strpos($value,'(')+1, strpos($value,')')-(strpos($value,'(')+1));
						} else {
							$id = $value;
						}
						$xmlData .= '      <'.$key.' sdata:uuid="'.$id.'" />'.chr(10);	
						$id = '';
					} else {
						$xmlData .= '      <'.$key.'>'.$value.'</'.$key.'>'.chr(10);					
					}
				}	
$xmlData .= '    </'.$param ["module"].'>
  </sdata:payload>
</entry>';
			
				// Send data to Sage
				if ($this->update) {					
					// target id contains the right url
					$dataSent = $this->makeRequest('', $this->token, $data['target_id'], 'update', null, $xmlData);
				} else {				
					$dataSent = $this->makeRequest($this->paramConnexion['host'], $this->token, '/sdata/' . self::APPLICATION . '/' . self::CONTRACT . '/-/' . $modules_pluralName, 'create', null, $xmlData);
				}	
				// General error
				if (!empty($dataSent['curlData']->message)) {
					throw new \Exception($dataSent['curlData']->message);
				}
				if (empty($dataSent['curlData']->id)) {
					throw new \Exception('No ID retruned by Sage. ');
				}

				// Retrieve the id in parentheses		
				if (empty($dataSent['curlData']->id)) {
					throw new \Exception('Failed to get the id in parentheses from this URL : '.$dataSent['curlData']->id.'. ');
				}
				$result[$idDoc] = array(
										'id' => $dataSent['curlData']->id,
										'error' => false
										);		
			}
			catch (\Exception $e) {
				// $error = $e->getMessage();
				$error ='Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
				$result[$idDoc] = array(
										'id' => '-1',
										'error' => $error
									);
			}
			// Transfert status update
			if (!empty($subDocIdArray)) {				
				foreach($subDocIdArray as $idSubDoc => $valueSubDoc) {				
					$this->updateDocumentStatus($idSubDoc,$valueSubDoc,$param);
				}
			}
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);
		}	
		return $result;
	} 
	
	// The function return true if we can display the column parent in the rule view, relationship tab
	// We display the parent column when module is subscription
	public function allowParentRelationship($module) {
		if (in_array($module, $this->parentModules)) {
			return true;
		}
		return false;
	}
	
	// We use the same function for record's creation and modification
	public function update($param) {
		$this->update = true;
		return $this->create($param);
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

	public function getRuleMode($module,$type) {
		if(
				$type == 'target'
			&&	in_array($module, $this->createOnlyModules)
		) { // Si le module est dans le tableau alors c'est uniquement de la création
			return array(
				'C' => 'create_only'
			);
		}
		return parent::getRuleMode($module,$type);
	}
	
	public function getAccessToken() {
        return $this->access_token;
    }//getAccessToken

    public function setAccessToken($token) {
        $this->access_token = $token;
    }//setAccessToken

    public function getFieldModules($index) {
        return $this->moduleFields = $this->moduleFields[$index];
    }//getFieldModules

    /**
     * setter for xml schema
     * @param $xml
     */
    public function setXML($xml) {
        $this->xml = $xml;
    }//setXML

    /**
     * getter for xml schema
     * @return mixed
     */
    public function getXML() {
        return $this->xml;
    }//getXML


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
    function makeRequest($server, $token, $path, $method, $args = null, $xml = null, $read_last = false)
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
                $headers[] = 'Authorization: Basic ' . $token;
                if (
						$method == 'create' 
					 OR $method == 'update'
				) {	
					$headers[] = "Content-Type: application/atom+xml;type=entry";
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					if ($method == 'create') {
						// curl_setopt($ch, CURLOPT_POST, true );     
						curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 						
					} else {
						// curl_setopt($ch, CURLOPT_PUT, true ); 
						curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); 
					}
					curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);        
					$headers[] = 'Content-Length: '.strlen($xml);    				
                }
            } else {
                // $authHeader = 'Authorization: Basic ' . $token;
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            // Execute request
            $result = curl_exec($ch);

			if ($method == 'read') {			
				$result = str_replace('sdata:','',$result);
				$result = str_replace('crm:','',$result);
				$result = str_replace('sc:','',$result);
				$xml = simplexml_load_string($result);
				$json = json_encode($xml);
				$result = json_decode($json,TRUE);									
			} elseif ($method == 'create' OR $method == 'update') {		
				$result = str_replace('sdata:','',$result);
				$result = str_replace('crm:','',$result);
				$result = str_replace('sc:','',$result);
				$result = simplexml_load_string($result);
			}else {
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
