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


require_once('lib/zuora/API.php');

class zuoracore  extends solution { 
	
	protected $client;
	protected $instance;
	protected $sessionId;
	protected $debug = 0;
	protected $header;
	protected $defaultApiNamespaceURL = 'http://api.zuora.com/';	
	protected $maxZObjectCount = 50;
	protected $defaultApiNamespace = "ns1";
	protected $defaultObjectNamespace = "ns2";
	protected $defaultObjectNamespaceURL = "http://object.api.zuora.com/";
	protected $update = false;
	protected $limitCall = 10; // Maw limit : 50
	
	protected $required_fields =  array('default' => array('Id','UpdatedDate', 'CreatedDate'));
	
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
			
			$config = new \stdClass();
			$config->wsdl = $this->paramConnexion['wsdl'];
			$this->instance = \Zuora_API::getInstance($config);
			
			$this->instance->setLocation('https://apisandbox.zuora.com/apps/services/a/85.0');
			$this->instance->login($this->paramConnexion['login'], $this->paramConnexion['password']);
			
			
			
			/* // Create the soap client
			$this->client = createClient($this->paramConnexion['wsdl'], $this->debug);
			// Connection to Zuora
			$this->sessionId = login($this->client, $this->paramConnexion['login'], $this->paramConnexion['password'], $this->debug);
			
			// error managment
			if(!empty($this->sessionId)) {
				// Header creation
				$this->header = $this->getHeader($this->sessionId);
			} else {
				throw new \ Exception("No SessionID. Logon failed.");
			} */
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
			require_once('lib/lib_zuora.php');
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
			require_once('lib/lib_zuora.php');
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
				$query .= " WHERE UpdatedDate < '".date('Y-m-d\TH:i:s')."'" ; // Need to add 'limit 1' here when the command LIMIT will be available
			}
			// limit to 1 result
			$this->instance->setQueryOptions(1);
			// Call Zuora
			$resultQuery = $this->instance->query($query);

			// If no result
			if (empty($resultQuery->result->records)) {
				$result['done'] = false;
			} else {		
				foreach($param['fields'] as $field) {
					// We check the lower case because the result of the webservice return sfield without capital letter (first_name instead of First_Name)
					if(isset($resultQuery->result->records->$field)) {
						// The field id in Zuora  as a capital letter for the I, not in Myddleware
						if ($field == 'Id') {
							$result['values']['id'] = $resultQuery->result->records->$field;
						} else {
							$result['values'][$field] = $resultQuery->result->records->$field;
						}
					}
				}
				if (!empty($result['values'])) {
					$result['done'] = true;
				}
			}
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;
		}		
		return $result;
	}

	protected function getSoapVar($array,$object = 'zObject')
    {
        return new \SoapVar(
            (array)$array,
            SOAP_ENC_OBJECT,
            $object,
            'http://object.api.zuora.com/'
        );
    }
	
	// Create data in the target solution
	public function create($param) {
		// Get the action because we use the create function to update data as well
		$action = ($this->update ? 'update' : 'create');
		try {
			$idDocArray = '';
			$i = 0;
			// $first = true;
			$nb_record = count($param['data']);	
print_r($param['data']);			
			foreach($param['data'] as $idDoc => $data) {
				$i++;
				// Save all idoc in the right order
				$idDocArray[]= $idDoc;
				 // Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
print_r($data);				
				$obj = 'Zuora_'.$param['module'];
				$zObject = new $obj();
			
				foreach ($data as $key => $value) {
					// Field only used for the update and contains the ID of the record in the target solution
					if ($key=='target_id') {
						// If update then we change the key in Id
						if (!empty($value)) {
							$key = 'Id';
						} else { // If creation, we skip this field
							continue;
						} 
					}
					// Init the array $fieldList oly one time
					// if ($first == true) {
						// $fieldList[] = $key;
					// }
					
					// $zAccount = new Zuora_Account();
    // $zAccount->AllowInvoiceEdit = 1;
    // $zAccount->AutoPay = 0;
    // $zAccount->Batch = 'Batch1';
    // $zAccount->BillCycleDay = 1;
    // $zAccount->Currency = $currency;
    // $zAccount->Name = $name;    
    // $zAccount->PaymentTerm = 'Due Upon Receipt';
    // $zAccount->Status = $status;

		//$zAccount->CrmId = 'SFDC-1223471249003';
		//$zAccount->PurchaseOrderNumber = 'PO-1223471249003';
		// return $zAccount;
					$zObject->$key = $value;
					// $val[$key] = $value;
				}	
// print_r($zObject);				
// return null;					
				// $first = false;
				$zObjects[] = $zObject;
				unset($zObject);
				// If we have finished to read all data or if the package is full we send the data to Sallesforce
				if (
						$nb_record == $i
					 || $i % $this->limitCall  == 0
				) {
	 			if (
						$param['module'] == 'Subscription'
					AND (
							$action = 'create'	
						 OR $action = 'subscribe'
					)
				) {
					$action = 'subscribe';
				}
				
print_r($zObjects);	
				$resultUpsert = $this->instance->create($zObjects);
print_r($resultUpsert);	
					
					
return null;	
// $val['Number'] = 'A-S00000041';
// print_r($param);				
// $action = 'subscribe';				
	/* 	$subscribeRequest = array(
            // 'SubscriptionNumber'=>'A-S99000041',
            'Account'=>array('AccountId'=>'2c92c0f85c48f335015c56eb44450455'),
            'BillToContact'=>array('ContactId'=>'2c92c0f95c490174015c56f0659e3e22'),
			// 'SubscriptionData'=>array('Subscription' => $val, 'RatePlanData' => array('RatePlan' => array('ProductRatePlanId'=>'2c92c0f957bc82720157becaa2d61655'), 'RatePlanCharge'=>array('Name' => 'test', 'ProductRatePlanChargeId' => '2c92c0f857bc72b90157becc0292347d'))),
			'SubscriptionData'=>array('Subscription' => $val, 'RatePlanData' => array('RatePlan' => array('ProductRatePlanId'=>'2c92c0f957bc82720157becaa2d61655'))),
        );
					*/		
	$subscribeRequest[$param['module']] = $val;				
print_r(array($subscribeRequest));		 	
					// try {
						$result = $this->client->__soapCall($action, array('zObjects'=>array($subscribeRequest)), null, $this->header);   
					// } catch (\SoapFault $fault) {
						// throw new \Exception($fault->getMessage());
					// }
// print_r($operation);					
// echo 'AAAA'.chr(10);					
print_r($result->result);
return null;					
					$xml = \ZuoraAPIHelper::printXMLWithNS($action, $param['module'], $fieldList, $values, $this->debug, 0, $this->defaultApiNamespace, $this->defaultObjectNamespace, false);
					$operation = \ZuoraAPIHelper::bulkOperation($this->client, $this->header, $action, $xml, count($values), $this->debug);
					// Transform the SOAP xml to an array
					$responseArray = $this->SoapXmlToArray($operation['response']);
					 // General error
					if (!empty($responseArray['soapenvBody']['soapenvFault'])) {
						throw new \Exception(print_r($responseArray['soapenvBody']['soapenvFault'],true));
					}
					if (empty($responseArray['soapenvBody']['ns1'.$action.'Response']['ns1result'])) {
						throw new \Exception('No response from Zuora. ');
					}
					// If only on document sent, we add a dimension to keep the compatibility with the code
					if (count($idDocArray) == 1) {
						$responseArrayTmp = array($responseArray['soapenvBody']['ns1'.$action.'Response']['ns1result']);
						$responseArray['soapenvBody']['ns1'.$action.'Response']['ns1result'] = $responseArrayTmp;
					}
					
					// Check the number result
					if (count($responseArray['soapenvBody']['ns1'.$action.'Response']['ns1result']) <> count($idDocArray)) {
						throw new \Exception('The number of result from Zuora ('.count($responseArray['soapenvBody']['ns1'.$action.'Response']['ns1result']).') is different of the number of data sent to Zuora ('.count($idDocArray).'). Myddleware is not able to analyse the result. ');
					}
					// Get the response for each records
					$j = 0;
					foreach($responseArray['soapenvBody']['ns1'.$action.'Response']['ns1result'] as $recordResponse) {
						if ($recordResponse['ns1Success'] == 'true') {
							if (empty($recordResponse['ns1Id'])) {
								$result[$idDocArray[$j]] = array(
										'id' => '-1',
										'error' => 'No Id in the response of Zuora. '
										);									
							} else {
								$result[$idDocArray[$j]] = array(
											'id' => $recordResponse['ns1Id'],
											'error' => false
											);
							}
						} else {
							$result[$idDocArray[$j]] = array(
											'id' => '-1',
											'error' => (empty($recordResponse['ns1Errors']) ? 'No error returned by Zuora.' : print_r($recordResponse['ns1Errors'],true))
											);	
						}
						$this->updateDocumentStatus($idDocArray[$j],$result[$idDocArray[$j]],$param);	
						$j++;
					}
					// Init variable
					$values = '';
					$operation = '';
					$responseArray = '';
					$idDocArray = '';
				}
			}
		}
		catch (\Exception $e) {
			$error = $e->getMessage().' '.$e->getFile().' line '.$e->getLine();
			// $error = $e->getMessage();
			$result['error'] = $error;
		}	
// print_r($result);		
		return $result;
	}

	// Cirrus Shield use the same function for record's creation and modification
	public function update($param) {
		// Get the action because we use the create function to update data as well
		$action = ($this->update ? 'update' : 'create');
		try {
			$idDocArray = '';
			$i = 0;
			// $first = true;
			$nb_record = count($param['data']);				
			foreach($param['data'] as $idDoc => $data) {
				$i++;
				// Save all idoc in the right order
				$idDocArray[]= $idDoc;
				 // Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);		
				$obj = 'Zuora_'.$param['module'];
				$zObject = new $obj();
			
				foreach ($data as $key => $value) {
					// Field only used for the update and contains the ID of the record in the target solution
					if ($key=='target_id') {
						// If update then we change the key in Id
						if (!empty($value)) {
							$key = 'Id';
						} else { // If creation, we skip this field
							continue;
						} 
					}
					$zObject->$key = $value;
				}	
				// Create the object collection
				$zObjects[] = $zObject;
				unset($zObject);
				// If we have finished to read all data or if the package is full we send the data to Sallesforce
				if (
						$nb_record == $i
					 || $i % $this->limitCall  == 0
				) {
					$resultUpdate = $this->instance->update($zObjects);
					if (empty($resultUpdate)) {
						throw new \Exception('No response from Zuora. ');
					}
					// If only one result, we add a dimension
					if (isset($resultUpdate->result->Id)) {
						$resultUpdate->result = array($resultUpdate->result);
					}

					// Get the response for each records
					$j = 0;
					foreach($resultUpdate->result as $record) {
						if ($record->Success) {
							if (empty($record->Id)) {
								$result[$idDocArray[$j]] = array(
										'id' => '-1',
										'error' => 'No Id in the response of Zuora. '
										);									
							} else {					
								$result[$idDocArray[$j]] = array(
											'id' => $record->Id,
											'error' => false
											);
							}
						} else {
							$result[$idDocArray[$j]] = array(
											'id' => '-1',
											'error' => (empty($record->Errors) ? 'No error returned by Zuora.' : print_r($record->Errors,true))
											);	
						}
						$this->updateDocumentStatus($idDocArray[$j],$result[$idDocArray[$j]],$param);	
						$j++;
					}
					// Init variable
					$idDocArray = '';
					unset($zObjects);
				}
			}
		}
		catch (\Exception $e) {
			$error = $e->getMessage();
			$result['error'] = $error;
		}	
		return $result;
	}

	
	// Transform the SOAP xml to an array
	protected function SoapXmlToArray($soapXml){
		$response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $soapXml);
		$xml2 = new \SimpleXMLElement($response);
		return json_decode(json_encode((array)$xml2), TRUE); 
	}
	 
	// Build the header because it can't be created in lib_zuora.php
	protected function getHeader($sessionId){
       $sessionVal = array('session'=>$sessionId);
       $header = new \SoapHeader($this->defaultApiNamespaceURL,
    				'SessionHeader',
    				$sessionVal);
       return $header;
    }
	
	
	
	protected function queryAll($query){

		$moreCount = 0;
		$recordsArray = array();
		$totalStart = time();

		$start = time();
		$result = $this->instance->query($query);
	
		$end = time();
		$elapsed = $end - $start;

		$done = $result->result->done;
		$size = $result->result->size;
		$records = $result->result->records;

		if ($size == 0){
		} else if ($size == 1){
			array_push($recordsArray, $records);
		} else {

			$locator = $result->result->queryLocator;
			$newRecords = $result->result->records;
			$recordsArray = array_merge($recordsArray, $newRecords);
			while (!$done && $locator && $moreCount == 0){
			
				$start = time();
				$result = $this->instance->queryMore($locator);
				$end = time();
				$elapsed = $end - $start;
		
				$done = $result->result->done;
				$size = $result->result->size;
				$locator = $result->result->queryLocator;
				print "\nqueryMore";

				$newRecords = $result->result->records;
				$count = count($newRecords);
				if ($count == 1){
					array_push($recordsArray, $newRecords);
				} else {
					$recordsArray = array_merge($recordsArray, $newRecords);
				}
		
			}
		}

		$totalEnd = time();
		$totalElapsed = $totalEnd - $totalStart;

		return $recordsArray;

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