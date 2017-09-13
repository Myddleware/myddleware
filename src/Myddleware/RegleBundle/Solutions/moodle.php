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
//use Psr\LoggerInterface;

require_once('lib/lib_moodle.php');

class moodlecore  extends solution { 
	protected $moodleClient;
	protected $required_fields = array(
										'default' => array('id'),
										'get_users_completion' => array('id','timemodified'),
										'get_users_last_access' => array('id','lastaccess')
								);
								
	protected $FieldsDuplicate = array(	'users' => array('email', 'username')  );
		
    public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
			$this->moodleClient = new \curl;
			$params = array();
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
			$error = 'Failed to login to Moodle : '.$e->getMessage();
			echo $error . ';';
			$this->logger->error($error);
			return array('error' => $error);
		} 
    }
	
	public function getFieldsLogin() {	
		return array(
					array(
							'name' => 'url',
							'type' => 'text',
							'label' => 'solution.fields.url'
						),
					array(
                            'name' => 'token',
                            'type' => 'password',
                            'label' => 'solution.fields.token'
                        )
		);
	}
	
	// Permet de récupérer tous les modules accessibles à l'utilisateur
	public function get_modules($type = 'source') {
	    try {
			if ($type == 'source') {
				return array(
					'get_users_completion'			=> 'Get users completion',
					'get_users_last_access'			=> 'Get users last access'
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
						'notes'	=> 'Notes',
				);	
			}
	    }
		catch (\Exception $e) {
			return false;
		}	
	}
	
	// Renvoie les champs du module passé en paramètre
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			// Pour chaque module, traitement différent
			switch ($module) {
				case 'users':
					$this->moduleFields = array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'username' => array('label' => 'Username', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'password' => array('label' => 'Password', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'createpassword' => array('label' => 'Create password', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'firstname' => array('label' => 'Firstname', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'lastname' => array('label' => 'Lastname', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'email' => array('label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'auth' => array('label' => 'Auth', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'idnumber' => array('label' => 'Id number', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'lang' => array('label' => 'Language', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'calendartype' => array('label' => 'Calendar type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'theme' => array('label' => 'Theme ', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timezone' => array('label' => 'Timezone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'mailformat' => array('label' => 'Mail format', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'description' => array('label' => 'Description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'city' => array('label' => 'City', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'country' => array('label' => 'Country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'firstnamephonetic' => array('label' => 'Firstname phonetic', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'lastnamephonetic' => array('label' => 'Lastname phonetic', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'middlename' => array('label' => 'Middlename', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'alternatename' => array('label' => 'Alternatename', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					);
					$this->moduleFields['auth']['option'] = array(
						'email' => 'Email-based self-registration',
						'manual' => 'Manual accounts',
						'nologin' => 'No login',
						'cas' => 'CAS server (SSO)',
						'db' => 'External database',
						'fc' => 'FirstClass server',
						'imap' => 'IMAP server',
						'ldap' => 'LDAP server',
						'mnet' => 'MNet authentication',
						'nntp' => 'NNTP server',
						'none' => 'No authentication',
						'pam' => 'PAM (Pluggable Authentication Modules)',
						'pop3' => 'POP3 server',
						'radius' => 'RADIUS server',
						'shibboleth' => 'Shibboleth',
						'webservice' => 'Web services authentication'
					);	
					break;
				case 'courses':	
					$this->moduleFields = array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'fullname' => array('label' => 'Full name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'shortname' => array('label' => 'Short name  ', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'categoryid' => array('label' => 'Category ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'idnumber' => array('label' => 'ID number  ', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'summary' => array('label' => 'Summary', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'summaryformat' => array('label' => 'Summary format', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'format' => array('label' => 'Format', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'showgrades' => array('label' => 'Showgrades', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'newsitems' => array('label' => 'News items', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'startdate' => array('label' => 'Start date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'numsections' => array('label' => 'Num sections', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'maxbytes' => array('label' => 'Max bytes', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'showreports' => array('label' => 'Show reports', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'visible' => array('label' => 'Visible', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'hiddensections' => array('label' => 'Hidden sections', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'groupmode' => array('label' => 'Group mode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'groupmodeforce' => array('label' => 'Group mode force', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'defaultgroupingid' => array('label' => 'default grouping ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'enablecompletion' => array('label' => 'Enable completion', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'completionnotify' => array('label' => 'Completion notify', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'lang' => array('label' => 'Language', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'forcetheme' => array('label' => 'Force theme', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					);
					$this->moduleFields['format']['option'] = array(
						'singleactivity' => 'Single activity format',
						'social' => 'Social format',
						'topics' => 'Topics format',
						'weeks' => 'Weekly format'
					);	
					$this->moduleFields['hiddensections']['option'] = array(
						'0' => 'Hidden sections are shown in collapsed form',
						'1' => 'Hidden sections are completely invisible'
					);	
					$this->moduleFields['hiddensections']['option'] = array(
						'0' => 'Show all sections on one page',
						'1' => 'Show one section per page'
					);	
					$this->moduleFields['maxbytes']['option'] = array(
						'0' => 'Site upload limit (2MB)',
						'2097152' => '2MB',
						'1048576' => '1MB',
						'512000' => '500KB',
						'102400' => '100KB',
						'51200' => '50KB',
						'10240' => '10KB'
					);	
					$this->moduleFields['groupmode']['option'] = array(
						'0' => 'No groups',
						'1' => 'Separate groups',
						'2' => 'Visible groups'
					);	
					$this->moduleFields['summaryformat']['option'] = array(
						'0' => 'MOODLE',
						'1' => 'HTML',
						'2' => 'PLAIN',
						'4' => 'MARKDOWN'
					);
										
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
					break;
				case 'groups':	
					$this->moduleFields = array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'name' => array('label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'description' => array('label' => 'Description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'descriptionformat' => array('label' => 'Description format', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'enrolmentkey' => array('label' => 'Enrolment key', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'idnumber' => array('label' => 'ID number', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					);
					$this->fieldsRelate = array(
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					);
					$this->moduleFields['descriptionformat']['option'] = array(
						'0' => 'MOODLE',
						'1' => 'HTML',
						'2' => 'PLAIN',
						'4' => 'MARKDOWN'
					);
					break;	
				case 'group_members':	
					$this->moduleFields = array();
					$this->fieldsRelate = array(
						'groupid' => array('label' => 'Group ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					);	
					break;	
				case 'manual_enrol_users':	
					$this->moduleFields = array(
						'roleid' => array('label' => 'Role ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'timestart' => array('label' => 'Time start', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timeend' => array('label' => 'Time end', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'suspend' => array('label' => 'Description format', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0)
					);
					$this->fieldsRelate = array(
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					);
					$this->moduleFields['roleid']['option'] = array(
						'1' => 'Manager',
						'3' => 'Teacher',
						'4' => 'Non-editing teacher',
						'5' => 'Student'
					);
					break;	
				case 'manual_unenrol_users':	
					$this->moduleFields = array(
						'roleid' => array('label' => 'Role ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
					);
					$this->fieldsRelate = array(
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					);
					$this->moduleFields['roleid']['option'] = array(
						'1' => 'Manager',
						'3' => 'Teacher',
						'4' => 'Non-editing teacher',
						'5' => 'Student'
					);
					break;	
				case 'notes':	
					$this->moduleFields = array(
						'publishstate' => array('label' => 'Publish state ', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'text' => array('label' => 'Text', 'type' => 'text', 'type_bdd' => 'text', 'required' => 0),
						'format' => array('label' => 'Format', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'clientnoteid' => array('label' => 'Client note id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					);
					$this->fieldsRelate = array(
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					);
					$this->moduleFields['publishstate']['option'] = array(
						'personal' => 'Personal',
						'course' => 'Course',
						'site' => 'Site'
					);
					$this->moduleFields['format']['option'] = array(
						'0' => 'MOODLE',
						'1' => 'HTML',
						'2' => 'PLAIN',
						'4' => 'MARKDOWN'
					);
					break;	
				case 'get_users_completion':	
					$this->moduleFields = array(
						'instance' => array('label' => 'Instance', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'moduletype' => array('label' => 'Module type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'completionstate' => array('label' => 'Completion state', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timemodified' => array('label' => 'Time modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					);
					$this->fieldsRelate = array(
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					);
					break;	
				case 'get_users_last_access':	
					$this->moduleFields = array(
						'lastaccess' => array('label' => 'Last access', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					);
					$this->fieldsRelate = array(
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					);
					break;		
				default:
					throw new \Exception("Module unknown. ");
					break;
			}
			// Ajout des champ relate au mapping des champs 
			if (!empty($this->fieldsRelate)) {
				$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
			}
			return $this->moduleFields;
		}
		catch (\Exception $e){
			$error = $e->getMessage();
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
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
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
			$DateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
			
			// Add requiered fields 
			$param['fields'] = $this->addRequiredField($param['fields']);

			// Get the function name and the parameters
			switch ($param['module']) {
				case 'get_users_completion':
					// For the simulation we get the last access from last week (we don't put 0 for peformance matters)
					$parameters = array('time_modified' => $result['date_ref']);
					$functionname = 'local_myddleware_get_users_completion';
					break;	
				case 'get_users_last_access':
					// For the simulation we get the last access from last week (we don't put 0 for peformance matters)
					$parameters = array('time_modified' => $result['date_ref']);
					$functionname = 'local_myddleware_get_users_last_access';
					break;	
				default:
					throw new \Exception("Module unknown. ");
					break;
			}

			// Call to Moodle
			$serverurl = $this->paramConnexion['url'].'/webservice/rest/server.php'. '?wstoken=' .$this->paramConnexion['token']. '&wsfunction='.$functionname;			
			$response = $this->moodleClient->post($serverurl, $parameters);				
			$xml = simplexml_load_string($response);
		
			if (!empty($xml->ERRORCODE)) {
				throw new \Exception("Error $xml->ERRORCODE : $xml->MESSAGE");
			}
			
			// Transform the data to Myddleware format
			if (!empty($xml->MULTIPLE->SINGLE)) {
				foreach ($xml->MULTIPLE->SINGLE AS $data) {
					foreach ($data AS $field) {
						// Save the new date ref
						if (
								$field->attributes()->__toString() == $DateRefField
							&&	$result['date_ref'] < $field->VALUE->__toString()
						) {
							$result['date_ref'] = $field->VALUE->__toString();
						}
						// Get the date modified
						if (
								$field->attributes()->__toString() == $DateRefField
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
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';;
		}	
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
				return 'timemodified';
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
