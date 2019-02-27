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

use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

//use Psr\LoggerInterface;

require_once('lib/lib_moodle.php');

class moodlecore  extends solution { 
	protected $moodleClient;
	protected $required_fields = array(
										'default'						=> array('id'),
										'get_users_completion' 			=> array('id','timemodified'),
										'get_users_last_access' 		=> array('id','lastaccess'),
										'get_course_completion_by_date' => array('id','timecompleted')
								);
								
	protected $FieldsDuplicate = array(	'users' => array('email', 'username')  );
		
    public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
			$this->moodleClient = new \curl;
			$params = array();
			$this->paramConnexion['token'] = trim($this->paramConnexion['token']);
			$functionname = 'core_webservice_get_site_info';
			$serverurl = $this->paramConnexion['url'].'/webservice/rest/server.php'. '?wstoken='.$this->paramConnexion['token'].'&wsfunction='.$functionname;
			$response = $this->moodleClient->post($serverurl, $params);
			$xml = simplexml_load_string($response);
			
			if (!empty($xml->SINGLE->KEY[0]->VALUE)) {
				$this->connexion_valide = true;
			}
			elseif (!empty($xml->ERRORCODE)) {
				throw new \Exception($xml->ERRORCODE.' : '.$xml->MESSAGE);
			}
			else {
				throw new \Exception('Error unknown. ');
			}
		} 
		catch (\Exception $e) {
			$error = $e->getMessage();
			$this->logger->error($error);
			return array('error' => $error);
		} 
    }
	
	public function getFieldsLogin() {	
		return array(
					array(
							'name' => 'url',
							'type' => TextType::class,
							'label' => 'solution.fields.url'
						),
					array(
                            'name' => 'token',
                            'type' => PasswordType::class,
                            'label' => 'solution.fields.token'
                        )
		);
	}
	
	// Permet de récupérer tous les modules accessibles à l'utilisateur
	public function get_modules($type = 'source') {
	    try {
			if ($type == 'source') {
				return array(
					'get_users_completion'			=> 'Get course activity completion',
					'get_users_last_access'			=> 'Get users last access',
					'get_courses_by_date'			=> 'Get courses',
					'get_users_by_date'				=> 'Get users',
					'get_enrolments_by_date'		=> 'Get enrolments',
					'get_course_completion_by_date'	=> 'Get course completion'
				);	
			}
			else {
				return array(
					'users'						=> 'Users',
					'courses'					=> 'Courses',
					'groups'					=> 'Groups',
					'group_members'				=> 'Group members',
					'manual_enrol_users'		=> 'Manual enrol users',
					'manual_unenrol_users'		=> 'Manual unenrol users',
					'notes'						=> 'Notes'
				);	
			}
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
			require_once('lib/moodle/metadata.php');	
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


	// Permet de récupérer le dernier enregistrement de la solution (utilisé pour tester le flux)
	public function read_last($param) {		
		$query = '';
		try {
			// Si le tableau de requête est présent alors construction de la requête
			if (!empty($param['query'])) {
				foreach ($param['query'] as $key => $value) {
					$filters[] = array( 'key' => $key, 'value' => $value );
				}
			}
			switch ($param['module']) {
				case 'users':
					$parameters = array( 'criteria' => $filters );
					$functionname = 'core_user_get_users';
					break;
				case 'get_users_completion':
					// For the simulation we get the last access from last week (we don't put 0 for peformance matters)
					$parameters = array('time_modified' => date('U', strtotime('-1 week')));
					$functionname = 'local_myddleware_get_users_completion';
					break;	
				case 'get_users_last_access':
					// For the simulation we get the last access from last week (we don't put 0 for peformance matters)
					$parameters = array('time_modified' => date('U', strtotime('-1 week')));
					$functionname = 'local_myddleware_get_users_last_access';
					break;	
				default:
					$result['done'] = false;					
					return $result;	
					break;
			}

			$serverurl = $this->paramConnexion['url'].'/webservice/rest/server.php'. '?wstoken=' .$this->paramConnexion['token']. '&wsfunction='.$functionname;			
			$response = $this->moodleClient->post($serverurl, $parameters);				
			$xml = simplexml_load_string($response);
			if (!empty($xml->ERRORCODE)) {
				throw new \Exception("Error $xml->ERRORCODE : $xml->MESSAGE");
			}
			
			// Get the data from the output structure
			if (in_array($param['module'], array('get_users_completion','get_users_last_access'))) {		
				if (!empty($xml->MULTIPLE->SINGLE[0]->KEY[0]->VALUE)) {
					$data = $xml->MULTIPLE->SINGLE[0]->KEY;
				}
			}
			elseif (!empty($xml->SINGLE->KEY[0]->MULTIPLE->SINGLE->KEY->VALUE)) {
				$param['fields'] = $this->addRequiredField($param['fields']);
				$data = $xml->SINGLE->KEY[0]->MULTIPLE->SINGLE->KEY;
				
			}
			
			// Transform the data to Myddleware format
			if (!empty($data)) {
				foreach ($data AS $value) {
					// Si le champ est demandé
					if (array_search($value->attributes()->__toString(), $param['fields']) !== false) {
						$result['values'][$value->attributes()->__toString()] = $value->VALUE->__toString();
					}
				}
				$result['done'] = true;
			}
			else {
				$result['done'] = false;
			}				
			return $result;		 
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;
			return $result;
		}	
	}
	
	// Read data in Moodle
	public function read($param) {	
		try {
			$result['count'] = 0;
			// Put date ref in Moodle format
			$result['date_ref'] = $this->dateTimeFromMyddleware($param['date_ref']);
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
			
			// Add requiered fields 
			$param['fields'] = $this->addRequiredField($param['fields']);

			// Set parameters
			$parameters = array('time_modified' => $result['date_ref']);
			$functionname = 'local_myddleware_'.$param['module'];

			// Call to Moodle
			$serverurl = $this->paramConnexion['url'].'/webservice/rest/server.php'. '?wstoken=' .$this->paramConnexion['token']. '&wsfunction='.$functionname;
// echo $serverurl; 		
			$response = $this->moodleClient->post($serverurl, $parameters);				
// print_r($response);					
			$xml = simplexml_load_string($response);
			if (!empty($xml->ERRORCODE)) {
				throw new \Exception("Error $xml->ERRORCODE : $xml->MESSAGE");
			}
			
			// Transform the data to Myddleware format
			if (!empty($xml->MULTIPLE->SINGLE)) {
				foreach ($xml->MULTIPLE->SINGLE AS $data) {
// print_r($data);					
					foreach ($data AS $field) {
						// Save the new date ref
						if (
								(
									$field->attributes()->__toString() == $dateRefField
								AND	$result['date_ref'] < $field->VALUE->__toString()
								)
							 OR $field->attributes()->__toString() == 'date_ref_override' // The webservice could return a date to override the date_ref
						) {
							$result['date_ref'] = $field->VALUE->__toString();
						}
						// Get the date modified
						if (
								$field->attributes()->__toString() == $dateRefField
						) {
							$row['date_modified'] = $field->VALUE->__toString();
						}
						// Get all the requested fields
						if (array_search($field->attributes()->__toString(), $param['fields']) !== false) {
							$row[$field->attributes()->__toString()] = $field->VALUE->__toString();
						}
					}
					$result['values'][$row['id']] = $row;
					$result['count']++;
				}
			}	
			// Put date ref in Myddleware format
			$result['date_ref'] = $this->dateTimeToMyddleware($result['date_ref']);			
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';;
		}	
// print_r($result);	
// return null;	
		return $result;
	}
	
	// Permet de créer des données
	public function create($param) {
		// Transformation du tableau d'entrée pour être compatible webservice Sugar
		foreach($param['data'] as $idDoc => $data) {
			try {
				// Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
				$dataSugar = array();
				$obj = new \stdClass();
				foreach ($data as $key => $value) {
					// We don't send Myddleware_element_id field to Moodle
					if ($key == 'Myddleware_element_id') {
						continue;
					}
					if (!empty($value)) {
						$obj->$key = $value;
					}
				}			
				switch ($param['module']) {
					case 'users':
						$users = array($obj);
						$params = array('users' => $users);
						$functionname = 'core_user_create_users';
						break;
					case 'courses':		
						$courses = array($obj);
						$params = array('courses' => $courses);
						$functionname = 'core_course_create_courses';
						break;
					case 'groups':	
						$groups = array($obj);
						$params = array('groups' => $groups);
						$functionname = 'core_group_create_groups';
						break;	
					case 'group_members':	
						$members = array($obj);
						$params = array('members' => $members);
						$functionname = 'core_group_add_group_members';
						break;	
					case 'manual_enrol_users':	
						$enrolments = array($obj);
						$params = array('enrolments' => $enrolments);
						$functionname = 'enrol_manual_enrol_users';
						break;	
					case 'manual_unenrol_users':	
						break;	
					case 'notes':	
						$notes = array($obj);
						$params = array('notes' => $notes);
						$functionname = 'core_notes_create_notes';
						break;	
					default:
						throw new \Exception("Module unknown. ");
						break;
				}
				
				$serverurl = $this->paramConnexion['url'].'/webservice/rest/server.php'. '?wstoken=' .$this->paramConnexion['token']. '&wsfunction='.$functionname;			
				$response = $this->moodleClient->post($serverurl, $params);				
				$xml = simplexml_load_string($response);

				// Réponse standard pour les modules avec retours
				if (
						!empty($xml->MULTIPLE->SINGLE->KEY->VALUE)
					&& !in_array($param['module'],array('manual_enrol_users','group_members'))
				) {
					$result[$idDoc] = array(
							'id' => $xml->MULTIPLE->SINGLE->KEY->VALUE,
							'error' => false
					);
				}
				elseif (
						!empty($xml->MULTIPLE->SINGLE->KEY[1]->VALUE)
					&& in_array($param['module'],array('notes'))
				) {
					$result[$idDoc] = array(
							'id' => $xml->MULTIPLE->SINGLE->KEY[1]->VALUE,
							'error' => false
					);
				}
				elseif (!empty($xml->ERRORCODE)) {
					throw new \Exception($xml->ERRORCODE.' : '.$xml->MESSAGE);
				}
				// Si pas d'erreur et module sans retour alors on génère l'id
				elseif(in_array($param['module'],array('manual_enrol_users'))) {
					$result[$idDoc] = array(
							'id' => $obj->courseid.'_'.$obj->userid.'_'.$obj->roleid,
							'error' => false
					);
				}
				elseif(in_array($param['module'],array('group_members'))) {
					$result[$idDoc] = array(
							'id' => $obj->groupid.'_'.$obj->userid,
							'error' => false
					);
				}
				else {
					throw new \Exception('Error unknown. ');
				}
			}
			catch (\Exception $e) {		
				$error = $e->getMessage();
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}	
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}		
		return $result; 
	}
	
	
	// Permet de mettre à jour un enregistrement
	public function update($param) {	
		// Transformation du tableau d'entrée pour être compatible webservice Sugar
		foreach($param['data'] as $idDoc => $data) {	
			try {
				// Check control before update
				$data = $this->checkDataBeforeUpdate($param, $data);
				$dataSugar = array();
				$obj = new \stdClass();
				foreach ($data as $key => $value) {
					if ($key == 'target_id') {
						continue;
					// We don't send Myddleware_element_id field to Moodle
					} elseif ($key == 'Myddleware_element_id') {
						continue;
					}
					if (!empty($value)) {
						$obj->$key = $value;
					}
				}
				
				// Fonctions et paramètres différents en fonction des appels webservice
				switch ($param['module']) {
					case 'users':
						$obj->id = $data['target_id'];
						$users = array($obj);
						$params = array('users' => $users);
						$functionname = 'core_user_update_users';
						break;
					case 'courses':		
						$obj->id = $data['target_id'];
						$courses = array($obj);
						$params = array('courses' => $courses);
						$functionname = 'core_course_update_courses';
						break;
					case 'manual_enrol_users':	
						$enrolments = array($obj);
						$params = array('enrolments' => $enrolments);
						$functionname = 'enrol_manual_enrol_users';
						break;	
					case 'notes':	
						$obj->id = $data['target_id'];
						unset($obj->userid);
						unset($obj->courseid);
						$notes = array($obj);
						$params = array('notes' => $notes);
						$functionname = 'core_notes_update_notes';
						break;	
					case 'group_members':	
						$members = array($obj);
						$params = array('members' => $members);
						$functionname = 'core_group_add_group_members';
						break;	
					default:
						throw new \Exception("Module unknown. ");
						break;
				}	
			
				$serverurl = $this->paramConnexion['url'].'/webservice/rest/server.php'. '?wstoken=' .$this->paramConnexion['token']. '&wsfunction='.$functionname;
				$response = $this->moodleClient->post($serverurl, $params);			
				$xml = simplexml_load_string($response);
		
				// Réponse standard pour les modules avec retours
				if (!empty($xml->ERRORCODE)) {
					throw new \Exception($xml->ERRORCODE.' : '.$xml->MESSAGE.(!empty($xml->DEBUGINFO) ? ' Debug : '.$xml->DEBUGINFO : ''));
				}
				// Si pas d'erreur et module sans retour alors on génère l'id
				elseif(in_array($param['module'],array('manual_enrol_users'))) {
					$result[$idDoc] = array(
							'id' => $obj->courseid.'_'.$obj->userid.'_'.$obj->roleid,
							'error' => false
					);
				}
				elseif(in_array($param['module'],array('group_members'))) {
					$result[$idDoc] = array(
							'id' => $obj->groupid.'_'.$obj->userid,
							'error' => false
					);
				}
				else {
					$result[$idDoc] = array(
							'id' => $obj->id,
							'error' => false
					);
				}
			}
			catch (\Exception $e) {		
				$error = $e->getMessage();
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}		
		return $result; 
	}
	
	// Permet de renvoyer le mode de la règle en fonction du module target
	// Valeur par défaut "0"
	// Si la règle n'est qu'en création, pas en modicication alors le mode est C
	public function getRuleMode($module,$type) {
		if(
				$type == 'target'
			&&	in_array($module, array('groups'))
		) { // Si le module est dans le tableau alors c'est uniquement de la création
			return array(
				'C' => 'create_only'
			);
		}
		return parent::getRuleMode($module,$type);
	}
	
	// Function de conversion de datetime format solution à un datetime format Myddleware
	protected function dateTimeToMyddleware($dateTime) {
		$date = new \DateTime();
		$date->setTimestamp($dateTime);
		return $date->format('Y-m-d H:i:s');
	}// dateTimeToMyddleware($dateTime)	
	
	// Function de conversion de datetime format Myddleware à un datetime format solution
	protected function dateTimeFromMyddleware($dateTime) {
		$date = new \DateTime($dateTime);
		return $date->format('U');
	}// dateTimeFromMyddleware($dateTime)    
	
	// Renvoie le nom du champ de la date de référence en fonction du module et du mode de la règle
	public function getDateRefName($moduleSource, $RuleMode) {
		switch ($moduleSource) {
			case 'get_users_completion':
			case 'get_courses_by_date':
			case 'get_users_by_date':
			case 'get_enrolments_by_date':
				return 'timemodified';
				break;	
			case 'get_course_completion_by_date':
				return 'timecompleted';
				break;	
			case 'get_users_last_access':
				return 'lastaccess';
				break;	
			default:
				throw new \Exception("Module unknown. ");
				break;
		}
		return null;
	}
	
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/moodle.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class moodle extends moodlecore {
		
	}
}
