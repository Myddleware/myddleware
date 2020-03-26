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

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;


class facebookcore  extends solution {
	
	protected $baseUrl = 'https://graph.facebook.com';
	protected $apiVersion = 'v6.0';
	protected $facebook;
	protected $readLast = false;
	
	protected $required_fields = array('default' => array('id','created_time'));
	
	public function getFieldsLogin() {	
		return array(
					array(
							'name' => 'clientid',
							'type' => TextType::class,
							'label' => 'solution.fields.clientid'
						),
					array(
							'name' => 'clientsecret',
							'type' => PasswordType::class,
							'label' => 'solution.fields.clientsecret'
						),
					array(
							'name' => 'useraccesstoken',
							'type' => PasswordType::class,
							'label' => 'solution.fields.useraccesstoken'
						)
		);
	}
	
	public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
			// Create Facebook object
			$this->facebook = new \Facebook\Facebook([
			  'app_id' => $this->paramConnexion['clientid'],
			  'app_secret' => $this->paramConnexion['clientsecret'],
			  'default_graph_version' => $this->apiVersion, 
			  ]);

			// Set the user access token
			$this->facebook->setDefaultAccessToken($this->paramConnexion['useraccesstoken']);
			
			// Test the access getting me info
			$response = $this->facebook->get( '/me' ); 
			$graphNode = $response->getGraphNode();
			$meId = $graphNode->getField('id'); 
			if (empty($meId)) {
				throw new \Exception('Failed to get the access token from Facebook');				
			}			
			$this->connexion_valide = true;
			return null;
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
			$error = 'Graph returned an error: ' . $e->getMessage();
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
			$error = 'Facebook SDK returned an error: ' . $e->getMessage();
		} catch (\Exception $e) {
			$error = $e->getMessage();
		}
		$this->logger->error($error);
		return array('error' => $error);		
    }
	

	// Get available modules
	public function get_modules($type = 'source') {
	    try {
			return array(
				'leads' => 'Leads from capture form'
			);
	    }
		catch (\Exception $e) {
			return false;
		}
	}
	
	// 1122084861475672?fields=id,name,questions
	// Get the fields available for the module in input
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			$this->moduleFields = array();
			$this->fieldsRelate = array();
			
			// Use Moodle metadata
			require('lib/facebook/metadata.php');	
			if (!empty($moduleFields[$module])) {
				$this->moduleFields = $moduleFields[$module];
			}
			
			// Field relate
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
	
	

	// Get only one data from the application 
    public function read_last($param) {	
		// Set the attribut readLast to true to stop the search when we found at leas one record
		$this->readLast = true;
		// date_ref far in the past to be sure to found at least one record
		$param['date_ref'] = '1970-01-01 00:00:00';
		$param['rule']['mode'] = '0';
		// We re use read function for the read_last 
		$read = $this->read($param);

		// Format output values
		if (!empty($read['error'])) {
			$result['error'] = $read['error'];
		} else {
			if (!empty($read['values'])) {
				$result['done'] = true;
				// Get only one record
				$result['values'] = current($read['values']);
			} else {
				$result['done'] = false;
			}
		}	
		return $result; 
    }// end function read_last	
	
	// Permet de lire les données
	public function read($param) {		
		try {
			$result = array();
			$result['error'] = '';
			$result['count'] = 0;
			$result['date_ref'] = $param['date_ref'];
			
			// Get the reference field
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
		
			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields']);
			
			// Check the leadsAdsId is filled
			if (empty($param['ruleParams']['LeadAdsId'])) {
				throw new \Exception('Failed to read lead into Facebook because the leadsAdsId is empty.');
			}
			
			// Call to Facebook
			$response = $this->facebook->get( '/'.$param['ruleParams']['LeadAdsId'].'/leads'); 
			
			// Browse all leads as it isn't possible to filter data by date
			$recordsEdge = $response->getGraphEdge();
			while ($recordsEdge != null) {
				foreach ($recordsEdge as $recordNode) {			
					$row = array();
					// For each fields used in Myddleware rule
					foreach($param['fields'] as $field) {				
						// if reference date, we convert it to Myddleware date format
						if($field == $dateRefField) {
							$row[$field] =  $recordNode->getField($field)->format('Y-m-d H:i:s');				
						// If field_data, we transform the complex struture to a json ($key => $value)
						} elseif($field == 'field_data') {
							$row[$field] = $this->formatToJson($recordNode->getField($field));
						} else {
							$row[$field] = $recordNode->getField($field);
						}
					}
					// Saved the record only if the record reference date is greater than the rule reference date
					if (
							!empty($row[$dateRefField])
						&&	$result['date_ref'] < $row[$dateRefField]
					) {								
						$result['values'][$row['id']] = $row;
						$result['count']++;
						$result['date_ref'] = $row[$dateRefField];
						// If read last, we just read one record
						if ($this->readLast) {
							break(2);
						}
					}
				}	
				// Read next page
				$recordsEdge = $this->facebook->next($recordsEdge);
			}			
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
			$result['error'] = 'Graph returned an error: ' . $e->getMessage();
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
			$result['error'] = 'Facebook SDK returned an error: ' . $e->getMessage();
		} catch (\Exception $e) {
			$result['error'] = $e->getMessage();
		}		
		return $result;	
	}
	
	// Transform Facebook data structure to a json type key => value
	protected function formatToJson($fbDataObject) {
		if (!empty($fbDataObject)) {
			foreach ($fbDataObject as $field) {
				$data[$field->getField('name')] = $field->getField('values')->getField('0'); 			
			}
			return json_encode($data);
		}
		return null;
	}
	
	public function getDateRefName($moduleSource, $RuleMode) {
		// Only leads module for now
		return "created_time";
	}
	
	public function getFieldsParamUpd($type, $module) {	
		try {
			if (
					$type == 'source'
				AND $module == 'leads'	
			){
				// Add param to store the fieldname corresponding to the record id
				return array(
							array(
								'id' => 'LeadAdsId',
								'name' => 'LeadAdsId',
								'type' => 'text',
								'label' => 'Lead ads id (form, campaign or group)',
								'required'	=> true
							)
						);
			}
			return array();
		}
		catch (\Exception $e){
			return array();
		}
	}
	
}

/* * * * * * * *  * * * * * *  * * * * * * 
	Include custom class if exists
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/facebook.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class facebook extends facebookcore {
		
	}
}