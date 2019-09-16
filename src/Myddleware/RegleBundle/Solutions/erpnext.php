<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 *
 * This file is part of Myddleware.
 *
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

namespace Myddleware\RegleBundle\Solutions;

use DateTime;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class erpnextcore extends solution
{

	// protected $url = 'https://www.cirrus-shield.net/RestApi/';
	protected $token;
	protected $update;
	protected $organizationTimezoneOffset;
	protected $limitCall = 100;

	protected $required_fields = array('default' => array('name', 'creation', 'modified'));

	protected $FieldsDuplicate = array(	'Contact' => array('Email', 'Name'),
										'default' => array('Name')
									);
									
	// Module list that allows to make parent relationships
	protected $allowParentRelationship = array('Sales Invoice', 'Sales Order', 'Payment Entry', 'Item Attribute', 'Item', 'Payment');
	
	protected $childModuleKey = array(	'Sales Invoice Item' => 'items', 
										'Sales Order Item' => 'items', 
										'Payment Entry Reference' => 'references', 
										'Item Attribute Value' => 'item_attribute_values',
										'Item Variant Attribute' => 'attributes', 
										'Sales Invoice Payment' => 'payments'
									);
	
	// Get isTable parameter for each module
	protected $isTableModule = array();

	public function getFieldsLogin() {
		return array(
			array(
				'name' => 'url',
				'type' => TextType::class,
				'label' => 'solution.fields.url'
			),
			array(
				'name' => 'login',
				'type' => TextType::class,
				'label' => 'solution.fields.login'
			),
			array(
				'name' => 'password',
				'type' => PasswordType::class,
				'label' => 'solution.fields.password'
			)
		);
	}

	// Login to Cirrus Shield
	public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
			// Generate parameters to connect to Cirrus Shield
			$parameters = array("usr" => $this->paramConnexion['login'],
				"pwd" => $this->paramConnexion['password']
			);
			$url = $this->paramConnexion['url'] . '/api/method/login';
			// Connect to ERPNext
			$result = $this->call($url, 'GET', $parameters);

			if (empty($result->message)) {
				throw new \Exception('Login error');
			}
			// Connection validation
			$this->connexion_valide = true;
		} catch (\Exception $e) {
			$error = $e->getMessage();
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)


	// Get the modules available
	public function get_modules($type = 'source') {
		try {
			// Get 
			$url = $this->paramConnexion['url'] .'/api/resource/DocType?limit_page_length=1000&fields=[%22name%22,%20%22istable%22]';			
			$APImodules = $this->call($url, 'GET');
			if (!empty($APImodules->data)) {
				foreach ($APImodules->data as $APImodule) {
					$modules[$APImodule->name] = $APImodule->name;
					// Save istable parameter for each modules
					$this->isTableModule[$APImodule->name] = $APImodule->istable;
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
		try {
			// Call get modules to fill the isTableModule array and ge the module list.
			$modules = $this->get_modules();			

			// Get the list field for a module
			$url = $this->paramConnexion['url'] . '/api/method/frappe.desk.form.load.getdoctype?doctype='.$module;
			$recordList = $this->call($url, 'GET', '');
			// Format outpput data					
			if (!empty($recordList->docs[0]->fields)) {
				foreach ($recordList->docs[0]->fields as $field) {
					if (empty($field->label)) {
						continue;
					}
					if ($field->fieldtype == 'Link') {
						$this->fieldsRelate[$field->fieldname] = array(
							'label' => $field->label,
							'type' => 'varchar(255)',
							'type_bdd' => 'varchar(255)',
							'required' => '',
							'required_relationship' => '',
						);
					// Add field to manage dymamic links
					} elseif (
							$field->fieldtype == 'Table'
						AND $field->options == 'Dynamic Link'
					) {	
						$this->moduleFields['link_doctype'] = array(
							'label' => 'Link Doc Type',
							'type' => 'varchar(255)',
							'type_bdd' => 'varchar(255)',
							'required' => '',
							'option' => $modules
						);
						$this->fieldsRelate['link_name'] = array(
							'label' => 'Link name',
							'type' => 'varchar(255)',
							'type_bdd' => 'varchar(255)',
							'required' => '',
							'required_relationship' => '',
						);
					} else {
						$this->moduleFields[$field->fieldname] = array(
							'label' => $field->label,
							'type' => 'varchar(255)',
							'type_bdd' => 'varchar(255)',
							'required' => '',
						);
						if (!empty($field->options)) {
							$options = explode(chr(10), $field->options);
							if (
								!empty($options)
								AND count($options) > 1
							) {
								foreach ($options as $option) {
									$this->moduleFields[$field->fieldname]['option'][$option] = $option;
								}
							}
						}
					}
				}
			} else {
				throw new \Exception('No data in the module ' . $module . '. Failed to get the field list.');
			}

			//If the module is a table and the solution is used in target, we add 3 fields
			if(
					$type == 'target'
				AND !empty($this->isTableModule[$module])
			) {
				// Parenttype => relate module/DocType de la relation (eg for Sales Invoice Item, it will be Sales Invoice)
				$this->moduleFields['parenttype'] = array(
														'label' => 'Parent type',
														'type' => 'varchar(255)',
														'type_bdd' => 'varchar(255)',
														'required' => '',
														'option' => $modules
													);
				// Parentfield => field name in the parent module (eg. "items" in module Sales Invoice). We can't give the field list because we don't know the module selected yet
				$this->moduleFields['parentfield'] = array(
														'label' => 'Parent field',
														'type' => 'varchar(255)',
														'type_bdd' => 'varchar(255)',
														'required' => ''
													);
				// Parent => value of the parent field (eg SINV-00001 which is the "Sales Invoice" parent)
				$this->fieldsRelate['parent'] = array(
							'label' => 'Parent',
							'type' => 'varchar(255)',
							'type_bdd' => 'varchar(255)',
							'required' => '',
							'required_relationship' => '',
						);
			}
			
			// Add relate field in the field mapping
			if (!empty($this->fieldsRelate)) {
				$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
			}	
			return $this->moduleFields;
		} catch (\Exception $e) {		
			$this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());		
			return false;
		}
	} // get_module_fields($module)


	/**
	 * Get the last data in the application
	 * @param $param
	 * @return mixed
	 * @throws \Exception
	 */
	public function read_last($param) {
		try {
			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields']);
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			$fields = $param['fields'];
			$result = array();
			$record = array();
			if (!empty($param['query'])) {
				foreach ($param['query'] as $key => $value) {
					if ($key === 'id') {
						$key = 'name';
					}
					$filters_result[$key] = $value;
				}
				$filters = json_encode($filters_result);
				$data = array('filters' => $filters, 'fields' => '["*"]');
				$q = http_build_query($data);
				$url = $this->paramConnexion['url'] . '/api/resource/' . rawurlencode($param['module']) . '?' . $q;
				$resultQuery = $this->call($url, "GET", '');
				$record = $resultQuery->data[0]; // on formate pour qu'il refactoré le code des $result['values"]

			} else {

				$data = array('limit_page_length' => 1, 'fields' => '["*"]');
				$q = http_build_query($data);
				$url = $this->paramConnexion['url'] . '/api/resource/' . rawurlencode($param['module']) . '?' . $q;
				//get list of modules

				$resultQuery = $this->call($url, "GET", '');
				// If no result
				if (empty($resultQuery)) {
					$result['done'] = false;
				} else {
					$record = $resultQuery->data[0];
				}
			}
			if (!empty($record)) {
				foreach ($fields as $field) {
					if (isset($record->$field)) {
						$result['values'][$field] = $record->$field; // last record
					}
				}
				$result['values']['id'] = $record->name; // add id
				$result['values']['date_modified'] = $record->modified; // modified
			}
			// If no result
			if (empty($resultQuery)) {
				$result['done'] = false;
			} else {
				if (!empty($result['values'])) {
					$result['done'] = true;
				}
			}
		} catch (\Exception $e) {
			$result['error'] = 'Error : ' . $e->getMessage().' '.$e->getFile().' '.$e->getLine();
			$result['done'] = -1;
		}
		return $result;
	} //end read_last

	/**
	 * Function read data
	 * @param $param
	 * @return mixed
	 */
	public function read($param) {
		try {	
			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields']);
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			// Get the reference date field name
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
			
			$fields = $param['fields'];
			$result['date_ref'] = $param['date_ref'];			
			$result['count'] = 0;
			$filters_result = array();
			// Build the query for ERPNext
			if (!empty($param['query'])) { 
				foreach ($param['query'] as $key => $value) {
					// The id field is name in ERPNext
					if ($key === 'id') {
						$key = 'name';
					}
					$filters_result[$key] = $value;
				}
				$filters = json_encode($filters_result);
				$data = array('filters' => $filters, 'fields' => '["*"]');
			} else {
				$filters = '{"'.$dateRefField.'": [">", "' . $param['date_ref'] . '"]}';
				$data = array('filters' => $filters, 'fields' => '["*"]');
			}	
		
			// Send the query
			$q = http_build_query($data);
			$url = $this->paramConnexion['url'] . '/api/resource/' . rawurlencode($param['module']) . '?' . $q;		
			$resultQuery = $this->call($url, 'GET', '');				
		  
			// If no result
			if (empty($resultQuery)) {
				$result['error'] = "Request error";
			} else if (count($resultQuery->data) > 0) {
				$resultQuery = $resultQuery->data;
				foreach ($resultQuery as $key => $recordList) {
					$record = null;
					foreach ($fields as $field) {
						if ($field == $dateRefField) {
							$record['date_modified'] = $this->dateTimeToMyddleware($recordList->$field);
							if ($recordList->$field > $result['date_ref']) {
								$result['date_ref'] = $recordList->$field;
							}
						} 
						if ($field != 'id') {
							$record[$field] = $recordList->$field;
						}
					}
					$record['id'] = $recordList->name;
					$result['values'][$recordList->name] = $record; // last record
				}
				$result['count'] = count($resultQuery);
			}
		} catch (\Exception $e) {
			$result['error'] = 'Error : ' . $e->getMessage().' '.$e->getFile().' '.$e->getLine();
		}		
		return $result;
	}// end function read

	public function create($param) {
		return $this->createUpdate('create', $param);

	}// end function create

	/**
	 * Function update data
	 * @param $param
	 * @return mixed
	 */
	public function update($param) {
		return $this->createUpdate('update', $param);
	}// end function create


	/**
	 * Function for create or update data
	 * @param $method
	 * @param $param
	 * @return array
	 */
	function createUpdate($method, $param) {	
		try {
			$result = array();
			$subDocIdArray = array();
			$url = $this->paramConnexion['url'] . "/api/resource/" . rawurlencode($param['module']);
			if ($method == 'update') {
				$method = "PUT";
			} else {
				$method = "POST";
			}
			foreach ($param['data'] as $idDoc => $data) {
				try {
					foreach ($data as $key => $value) {
						// We don't send Myddleware fields
						if (in_array($key, array('target_id', 'Myddleware_element_id'))) {
							if ($key == 'target_id') {
								$url = $this->paramConnexion['url'] . "/api/resource/" . rawurlencode($param['module'])."/" .$value;
							}
							unset($data[$key]);
						// if the data is a link 
						} elseif ($key == 'link_doctype') {
							$data['links'] = array(array('link_doctype' =>  $data[$key], 'link_name' => $data['link_name']));
							unset($data[$key]);
							unset($data['link_name']);
						// If the data is a submodule (eg : invoice lines)	
						} elseif (is_array($value)) {
							if(empty($this->childModuleKey[$key])) {
								throw new \Exception('The childModuleKey is missing for the module '.$key);
							}	
							if (!empty($value)) {
								foreach ($value as $subIdDoc => $subData) {
									// Save the subIdoc to change the sub data transfer status
									$subDocIdArray[$subIdDoc] = array('id' => uniqid('', true));
									foreach ($subData as $subKey => $subValue) {
										// We don't send Myddleware fields
										if (in_array($subKey, array('target_id', 'id_doc_myddleware','source_date_modified'))) {
											unset($subData[$subKey]);
										// if the data is a link 
										} elseif ($subKey == 'link_doctype') {
											$subData['links'] = array(array('link_doctype' =>  $subData[$subKey], 'link_name' => $subData['link_name']));
											unset($subData[$subKey]);
											unset($subData['link_name']);
										} 										
									} 
									$data[$this->childModuleKey[$key]][] = $subData;
								}
							}
							// Remove the original array
							unset($data[$key]);
						}
					}
					
					// Send data to ERPNExt
					$resultQuery = $this->call($url, $method, array('data' => json_encode($data)));
					if (!empty($resultQuery->data->name)) {
						// utf8_decode because the id could be a name with special characters
						$result[$idDoc] = array('id' => utf8_decode($resultQuery->data->name), 'error' => '');
					} elseif(!empty($resultQuery)) {
						throw new \Exception($resultQuery);
					} else {
						throw new \Exception('No result from ERPNext. ');
					}
				} catch (\Exception $e) {
					$result[$idDoc] = array(
						'id' => '-1',
						'error' => $e->getMessage()
					);
				}
				// Transfert status update
				if (
						!empty($subDocIdArray)
					AND empty($result[$idDoc]['error'])
				) {				
					foreach($subDocIdArray as $idSubDoc => $valueSubDoc) {				
						$this->updateDocumentStatus($idSubDoc,$valueSubDoc,$param);
					}
				}
				$this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
			}
		} catch (\Exception $e) {
			$error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
			$result['error'] = $error;
		}	
		return $result;
	}

	// retrun the reference date field name
	public function getDateRefName($moduleSource, $ruleMode) {
		// Creation and modification mode
		if(in_array($RuleMode,array("0","S"))) {
			return "modified";
		// Creation mode only
		} else if ($ruleMode == "C"){
			return "creation";
		} else {
			throw new \Exception ("$ruleMode is not a correct Rule mode.");
		}
		return null;
	}

	// Function de conversion de datetime format solution à un datetime format Myddleware
	protected function dateTimeToMyddleware($dateTime) {
		$date = new \DateTime($dateTime);
		return $date->format('Y-m-d H:i:s');
	}// dateTimeToMyddleware($dateTime)	
	
	// Function de conversion de datetime format Myddleware à un datetime format solution
	protected function dateTimeFromMyddleware($dateTime) {
		$date = new \DateTime($dateTime);
		return $date->format('Y-m-d H:i:s.u');
	}// dateTimeFromMyddleware($dateTime)    
	
	/**
	 * Function call
	 * @param $url
	 * @param string $method
	 * @param array $parameters
	 * @param int $timeout
	 * @return mixed|void
	 * @throws \Exception
	 */
	protected function call($url, $method = 'GET', $parameters = array(), $timeout = 300) {
		if (!function_exists('curl_init') OR !function_exists('curl_setopt')) {
			throw new \Exception('curl extension is missing!');
		}
		$fileTmp = $this->container->getParameter('kernel.cache_dir') . '/myddleware/solutions/erpnext/erpnext.txt';
		$fs = new Filesystem();
		try {
			$fs->mkdir(dirname($fileTmp));
		} catch (IOException $e) {
			throw new \Exception ($this->tools->getTranslation(array('messages', 'rule', 'failed_create_directory')));
		}
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

		// common description bellow
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $fileTmp);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $fileTmp);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		$response = curl_exec($ch);
		// if Traceback found, we have an error 
		if (
				$method != 'GET'
			AND	strpos($response,'Traceback') !== false
		) {
			// Extraction of the Traceback : Get the lenth between 'Traceback' and '</pre>'
			return substr($response, strpos($response,'Traceback'), strpos(substr($response,strpos($response,'Traceback')),'</pre>'));
		}
		curl_close($ch);
	
		return json_decode($response);
	}

}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__ . '/../Custom/Solutions/erpnext.php';
if (file_exists($file)) {
	require_once($file);
} else {
	//Sinon on met la classe suivante
	class erpnext extends erpnextcore
	{

	}
}