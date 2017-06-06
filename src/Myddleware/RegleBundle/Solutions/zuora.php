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
			require_once('lib/zuora/lib_zuora.php');
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
			require_once('lib/zuora/lib_zuora.php');
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

	// Cirrus Shield use the same function for record's creation and modification
	public function create($param) {
		
		// Get the action because we use the create function to update data as well
		if ($this->update) {
			$action = 'update';
		// If creation and subscription, we use function subscrbe and we limit the call by one
		} elseif ($param['module'] == 'Subscription') {
			$action = 'subscribe';
			$this->limitCall = 1;
		} else {
			$action = 'create';
		}

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
					if ($key=='AccountId') {
						if ($action == 'subscribe') {
							$zAccount = new \Zuora_Account();
							$zAccount->AccountId = $value;
						}
					}
					if ($key=='AccountId') {
						if ($action == 'subscribe') {
							$zBillToContact = new \Zuora_Contact();
							$zBillToContact->ContactId = '2c92c0f85c656dbf015c7a5ef4617b73';
						}
					}
				}	
				// Create objects for the subscribe function 
				if ($action == 'subscribe') {
					$zSubscriptionData = new \Zuora_SubscriptionData($zObject);
					unset($zObject);
					$zRatePlan = new \Zuora_RatePlan();
					$zRatePlan->ProductRatePlanId = '2c92c0f85c48f2ef015c5f099d5b5a04';
					$zRatePlanData = new \Zuora_RatePlanData($zRatePlan);
					unset($zRatePlan);
					$zSubscriptionData->addRatePlanData($zRatePlanData);
					unset($zRatePlanData); 
				// Create the object collection
				} else {
					$zObjects[] = $zObject;
					unset($zObject);
				}
				// If we have finished to read all data or if the package is full we send the data to Sallesforce
				if (
						$nb_record == $i
					 || $i % $this->limitCall  == 0
				) {
					// Manage differents calls (subscripe, create and update
					if ($action == 'subscribe') {
						$resultCall = $this->instance->subscribe($zAccount,$zSubscriptionData,$zBillToContact);
						unset($zAccount);
						unset($zSubscriptionData);
						unset($zBillToContact);
					} elseif ($action == 'create') {
						$resultCall = $this->instance->create($zObjects);
					} else {
						$resultCall = $this->instance->update($zObjects);
					}
					
					// General error
					if (empty($resultCall)) {
						throw new \Exception('No response from Zuora. ');
					}
					
					// Manage results
					$j = 0;
					if ($action == 'subscribe') {			
						if (!empty($resultCall->result->Errors)) {
							$result[$idDocArray[$j]] = array(
												'id' => '-1',
												'error' => (empty($resultCall->result->Errors) ? 'No error returned by Zuora.' : print_r($resultCall->result->Errors,true))
												);	
						// Succes of the subscription
						} else {
						
						}
						$this->updateDocumentStatus($idDocArray[$j],$result[$idDocArray[$j]],$param);	
					} else {
						// If only one result, we add a dimension
						if (isset($resultCall->result->Id)) {
							$resultCall->result = array($resultCall->result);
						}

						// Get the response for each records
						foreach($resultCall->result as $record) {
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
						unset($zObjects);
					}
				}
				$idDocArray = '';
			}
		}
		catch (\Exception $e) {
			$error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
			$result['error'] = $error;
		}			
		return $result;
	}
	
	// We use the create function to update data
	public function update($param) {	
		$this->update = true;
		return $this->create($param);
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