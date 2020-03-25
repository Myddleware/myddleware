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
// $result->getProperty( 'id' );			
// print_r($response);	
			

// print_r($graphNode);	
// print_r($meId);	
// $result = $this->facebook->get( '/1122084861475672/leads' ); 	
			
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
			// If the field catagory ID exist we fill it by requesting Moodle
			if (!empty($this->moduleFields['categoryid'])) {
				try {
					// Récupération de toutes les catégories existantes
					$params = array();
					$functionname = 'core_course_get_categories';
					$serverurl = $this->paramConnexion['url'].'/webservice/rest/server.php'. '?wstoken='.$this->paramConnexion['token'].'&wsfunction='.$functionname;
					$response = $this->moodleClient->post($serverurl, $params);
					$xml = simplexml_load_string($response);
					if (!empty($xml->MULTIPLE->SINGLE)) {
						foreach($xml->MULTIPLE as $category) {
							$this->moduleFields['categoryid']['option'][$category->SINGLE->KEY[0]->VALUE->__toString()] = $category->SINGLE->KEY[1]->VALUE->__toString();
						}
					}
				} 
				catch (\Exception $e) {
				} 	
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
	
	
	
	// Permet de lire les données
	public function read($param) {		
		try {
			$result = array();
			$result['error'] = '';
			$result['count'] = 0;
			
			if (empty($param['offset'])) {
				$param['offset'] = 0;
			}
			$currentCount = 0;		
			$query = '';
			if (empty($param['limit'])) {
				$param['limit'] = 100;
			}	
			// On va chercher le nom du champ pour la date de référence: Création ou Modification
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
			

			// Ajout des champs obligatoires
			$param['fields'] = $this->addRequiredField($param['fields']);
			$param['fields'] = array_unique($param['fields']);			
			// Construction de la requête pour SugarCRM
			$query = $this->generateQuery($param, 'read');		
			//Pour tous les champs, si un correspond à une relation custom alors on change le tableau en entrée
			$link_name_to_fields_array = array();
			foreach ($param['fields'] as $field) {
				if (substr($field,0,strlen($this->customRelationship)) == $this->customRelationship) {
					$link_name_to_fields_array[] = array('name' => substr($field, strlen($this->customRelationship)), 'value' => array('id'));
				}
			}
			// On lit les données dans le CRM
            do {
				$result = $this->facebook->get( '/1122084861475672/leads' ); 
																						
				// Construction des données de sortie
				if (!empty($result['data'])) {
					// $currentCount = $get_entry_list_result->result_count;
					// $result['count'] += $currentCount;
					$record = array();
					// $i = 0;
					// for ($i = 0; $i < $currentCount; $i++) {
					// For each records
					foreach($result['data'] as $record) {				
						// For each fields expected
						foreach($param['fields'] as $field) {				
							// We check the lower case because the result of the webservice return sfield without capital letter (first_name instead of First_Name)
							if(isset($record[$field])) {
								// If we are on the date ref field, we add the entry date_modified (put to lower case because ModificationDate in the where is modificationdate int the select
								if ($field == $dateRefField) {
									$row['date_modified'] = $record[$field];
								}
								$row[$field] = $record[$field];
							}
						}
						if (
								!empty($record[$dateRefField])
							&&	$result['date_ref'] < $record[$dateRefField]
						) {								
							// Transform the date 
							$dateRef = new \DateTime($record[$dateRefField]);
							$result['date_ref'] = $dateRef->format('Y-m-d H:i:s');
						}
						$result['values'][$row['id']] = $row;
						$result['count']++;
						$row = array();
					}	
						
						
						
					 // Préparation l'offset dans le cas où on fera un nouvel appel à Salesforce
                    $param['offset'] += $this->limitCall;
				}
				else {
					if (!empty($get_entry_list_result->number)) {
						$result['error'] = $get_entry_list_result->number.' : '. $get_entry_list_result->name.'. '. $get_entry_list_result->description;
					} else {
						$result['error'] = 'Failed to read data from SuiteCRM. No error return by SuiteCRM';
					}
				}			
			}
            // On continue si le nombre de résultat du dernier appel est égal à la limite
            while ($currentCount == $this->limitCall AND $result['count'] < $param['limit']-1); // -1 because a limit of 1000 = 1001 in the system				
			// Si on est sur un module relation, on récupère toutes les données liées à tous les module sparents modifiés
			if (!empty($paramSave)) {
				$resultRel = $this->readRelationship($paramSave,$result);
				// Récupération des données sauf de la date de référence qui dépend des enregistrements parent
				if(!empty($resultRel['count'])) {
					$result['count'] = $resultRel['count'];
					$result['values'] = $resultRel['values'];
				}
				// Si aucun résultat dans les relations on renvoie null, sinon un flux vide serait créé. 
				else {
					return null;
				}
			}	
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
		}
		return $result;	
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
								'id' => 'LeadAdsFormId',
								'name' => 'LeadAdsFormId',
								'type' => 'text',
								'label' => 'Lead ads form id',
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