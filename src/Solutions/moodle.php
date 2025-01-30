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

namespace App\Solutions;

use App\Solutions\lib\curl;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
//use Psr\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class moodle extends solution
{
    protected $moodleClient;
    protected array $required_fields = [
        'default' => ['id'],
        'get_users_statistics_by_date' => ['id', 'timemodified'],
        'get_users_completion' => ['id', 'timemodified'],
        'get_users_last_access' => ['id', 'lastaccess'],
        'get_course_completion_by_date' => ['id', 'timecompleted'],
        'get_user_grades' => ['id', 'timemodified'],
        'get_quiz_attempts' => ['id', 'timemodified'],
        'groups' => ['id', 'timemodified'],
        'group_members' => ['id', 'timeadded'],
    ];

    protected array $FieldsDuplicate = [
        'users' => ['email', 'username'],
        'courses' => ['shortname', 'idnumber'],
        'manual_enrol_users' => ['userid', 'courseid'],
        'manual_unenrol_users' => ['userid', 'courseid'],
    ];
	protected array $createOnlyFields = [
        'courses' => ['lang'],
    ];
	
    protected string $delaySearch = '-1 year';

    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            $this->moodleClient = new curl();
            $params = [];
            $this->paramConnexion['token'] = trim($this->paramConnexion['token']);
            $functionname = 'core_webservice_get_site_info';
            $serverurl = $this->paramConnexion['url'].'/webservice/rest/server.php'.'?wstoken='.$this->paramConnexion['token'].'&wsfunction='.$functionname;
            $response = $this->moodleClient->post($serverurl, $params);
            $xml = simplexml_load_string($response);

            if (!empty($xml->SINGLE->KEY[0]->VALUE)) {
                $this->connexion_valide = true;
            } elseif (!empty($xml->ERRORCODE)) {
                throw new \Exception($xml->ERRORCODE.' : '.$xml->MESSAGE.(!empty($xml->DEBUGINFO) ? ' - '.$xml->DEBUGINFO : ''));
            } else {
                throw new \Exception('Error unknown. ');
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
            [
                'name' => 'token',
                'type' => PasswordType::class,
                'label' => 'solution.fields.token',
            ],
			[
                'name' => 'user_custom_fields',
                'type' => TextType::class,
                'label' => 'solution.fields.user_custom_fields',
            ],
			[
                'name' => 'course_custom_fields',
                'type' => TextType::class,
                'label' => 'solution.fields.course_custom_fields',
            ],
        ];
    }

    // Permet de récupérer tous les modules accessibles à l'utilisateur
    public function get_modules($type = 'source'): array
    {
        try {
            if ('source' == $type) {
                return [
                    'users' => 'Users',
                    'courses' => 'Courses',
                    'get_users_completion' => 'Get course activity completion',
                    'get_users_last_access' => 'Get users last access',
                    'get_users_statistics_by_date' => 'Get users statistics',
                    'get_enrolments_by_date' => 'Get enrolments',
                    'get_course_completion_by_date' => 'Get course completion',
                    'get_user_compentencies_by_date' => 'Get user compentency',
                    'get_competency_module_completion_by_date' => 'Get compentency module completion',
                    'get_user_grades' => 'Get user grades',
                    'get_quiz_attempts' => 'Get quiz attempts',
                    'groups' => 'Groups',
					'group_members' => 'Group members',
                ];
            }

            return [
                'users' => 'Users',
                'courses' => 'Courses',
                'groups' => 'Groups',
                'group_members' => 'Group members',
                'manual_enrol_users' => 'Manual enrol users',
                'manual_unenrol_users' => 'Manual unenrol users',
                'notes' => 'Notes',
                'core_user_set_user_preferences' => 'User preferences',
            ];
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Get the fields available for the module in input
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            // Use Moodle metadata
            require 'lib/moodle/metadata.php';
            if (!empty($moduleFields[$module])) {
                $this->moduleFields = array_merge($this->moduleFields, $moduleFields[$module]);
            }
            // If the field catagory ID exist we fill it by requesting Moodle
            if (!empty($this->moduleFields['categoryid'])) {
                try {
                    // Récupération de toutes les catégories existantes
                    $params = [];
                    $functionname = 'core_course_get_categories';
                    $serverurl = $this->paramConnexion['url'].'/webservice/rest/server.php'.'?wstoken='.$this->paramConnexion['token'].'&wsfunction='.$functionname;
                    $response = $this->moodleClient->post($serverurl, $params);
                    $xml = simplexml_load_string($response);
                    if (!empty($xml->MULTIPLE->SINGLE)) {
                        foreach ($xml->MULTIPLE as $category) {
                            $this->moduleFields['categoryid']['option'][$category->SINGLE->KEY[0]->VALUE->__toString()] = $category->SINGLE->KEY[1]->VALUE->__toString();
                        }
                    }
                } catch (\Exception $e) {
                }
            }

			// Add user custom fields
			$this->addCustomFields($module, $type, $param);
            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Read data in Moodle
    // public function readData($param)
    public function read($param): array
    {
        try {
			// No read action in case of history on enrolment module (except if user_id and course_id are duplicate search parameters for enrolment)
			if (
					$param['call_type'] == 'history'
				AND (
						$param['module'] == 'manual_unenrol_users' // Don't want a no_send for manual_unenrol_users
					OR  $param['module'] == 'core_user_set_user_preferences' 
					OR (
							$param['module'] == 'manual_enrol_users'
						AND (
								empty($param['query']['userid'])
							 OR empty($param['query']['courseid'])
						)
					)
				)
			) {
				return array();
			}
            $result = [];
            // Set parameters to call Moodle
            $parameters = $this->setParameters($param);
            // Get function to call Moodle
            $functionName = $this->getFunctionName($param);
            // Get the custom fields set in the connector
            $customFieldList = $this->getCustomFields($param);
            // Init the attribute name and value for custom fields
            $attributeName = ($param['module'] == 'courses' || $param['module'] == 'groups' ? 'shortname' : 'name');
            $attributeValue = ($param['module'] == 'courses'? 'valueraw' : 'value');

            // Call to Moodle
            $serverurl = $this->paramConnexion['url'].'/webservice/rest/server.php'.'?wstoken='.$this->paramConnexion['token'].'&wsfunction='.$functionName;
            $response = $this->moodleClient->post($serverurl, $parameters);
            $xml = $this->formatResponse('read', $response, $param);
            if (!empty($xml->ERRORCODE)) {
                throw new \Exception("Error code $xml->ERRORCODE : $xml->MESSAGE. ".(!empty($xml->DEBUGINFO) ? "Info : $xml->DEBUGINFO" : ""));
            }
            // Transform the data to Myddleware format
            if (!empty($xml->MULTIPLE->SINGLE)) {
                foreach ($xml->MULTIPLE->SINGLE as $data) {
                    $row = array();
                    // Init custom fields to empty because Moodle returns custom field only if they exist for the current record
                    if (!empty($customFieldList)) {
                        foreach($customFieldList as $custom) {
                            $row[$custom] = '';
                        }
                    }
                    foreach ($data as $field) {
                        // Get all the requested fields
                        if (array_search($field->attributes()->__toString(), $param['fields']) !== false) {
                            $row[$field->attributes()->__toString()] = $field->VALUE->__toString();
                        }
                        // Manage custom field
                        elseif (
                                $field->attributes()->__toString() == 'customfields'
                            AND !empty($customFieldList)
                        ) {
                            // Get the curstom field values
                            // Loop on each custom field returns by Moodle
                            foreach($field->MULTIPLE->SINGLE as $customField) {
                                // Get the name and the value of each field
                                $customFieldValue = '';
                                $customFieldName = '';
                                foreach($customField->KEY as $customFieldValues) {
                                    if ($customFieldValues->attributes()->__toString() == 'shortname') {
                                        $customFieldName = $customFieldValues->VALUE->__toString();
                                    } elseif ($customFieldValues->attributes()->__toString() == $attributeValue) {
                                        $customFieldValue = $customFieldValues->VALUE->__toString();
                                    }
                                }
                                // Set the custom value to the output result
                                if (
                                        !empty($customFieldName)
                                    AND in_array($customFieldName, $customFieldList)
                                ) {
                                    $row[$customFieldName] = $customFieldValue;
                                }
                            }
                        }
                    }
                    $result[] = $row;
                }
            } elseif (!empty($xml->MESSAGE)) {
                throw new \Exception("Error : $xml->MESSAGE. ".(!empty($xml->DEBUGINFO) ? "Info : $xml->DEBUGINFO" : ""));
            }
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            throw new \Exception('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
        return $result;
    }

    // Permet de créer des données

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function createData($param): array
    {
        // Transformation du tableau d'entrée pour être compatible webservice Sugar
        foreach ($param['data'] as $idDoc => $data) {
            try {
                // Get the custom fields set in the connector
                $customFieldList = $this->getCustomFields($param);

                // Check control before create
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                $dataSugar = [];
                $obj = new \stdClass();
                foreach ($data as $key => $value) {
                    if (!empty($value)) {
                        // if $value belongs to $this->paramConnexion[user_custom_fields] then we add it to $obj->customfields
                        if (in_array($key, $customFieldList)) {
                            $customField = new \stdClass();
                            // Param names are differents depending on the module
                            if($param['module'] == 'users') {
                                $customField->type = $key;
                            } elseif($param['module'] == 'courses') {
                                $customField->shortname = $key; 
                            }
                            $customField->value = $value;
                            $obj->customfields[] = $customField;
                            
                        } else {
                            $obj->$key = $value;
                        }
                    }
                }
                switch ($param['module']) {
                    case 'users':
                        $users = [$obj];
                        $params = ['users' => $users];
                        $functionname = 'core_user_create_users';
                        break;
                    case 'courses':
                        $courses = [$obj];
                        $params = ['courses' => $courses];
                        $functionname = 'core_course_create_courses';
                        break;
                    case 'groups':
                        $groups = [$obj];
                        $params = ['groups' => $groups];
                        $functionname = 'core_group_create_groups';
                        break;
                    case 'group_members':
                        $members = [$obj];
                        $params = ['members' => $members];
                        $functionname = 'core_group_add_group_members';
                        break;
                    case 'manual_enrol_users':
                        $enrolments = [$obj];
                        $params = ['enrolments' => $enrolments];
                        $functionname = 'enrol_manual_enrol_users';
                        break;
                    case 'manual_unenrol_users':
						$enrolments = [$obj];
                        $params = ['enrolments' => $enrolments];
                        $functionname = 'manual_unenrol_users';
                        break;
                    case 'notes':
                        $notes = [$obj];
                        $params = ['notes' => $notes];
                        $functionname = 'core_notes_create_notes';
                        break;
					case 'core_user_set_user_preferences':
                        $preferences = [$obj];
                        $params = ['preferences' => $preferences];
                        $functionname = 'core_user_set_user_preferences';
                        break;
                    default:
                        throw new \Exception('Module unknown. ');
                        break;
                }

                $serverurl = $this->paramConnexion['url'].'/webservice/rest/server.php'.'?wstoken='.$this->paramConnexion['token'].'&wsfunction='.$functionname;
                $response = $this->moodleClient->post($serverurl, $params);
                $xml = simplexml_load_string($response);

				// Check if there is a warning
				if (
						!empty($xml->SINGLE)
					AND $xml->SINGLE->KEY->attributes()->__toString() == 'warnings'
					AND !empty($xml->SINGLE->KEY->MULTIPLE->SINGLE->KEY[3])
				) {
					throw new \Exception('ERROR : '.$xml->SINGLE->KEY->MULTIPLE->SINGLE->KEY[3]->VALUE.chr(10));
				}
				
				// Check if there is a warning in the second entry (for function core_user_set_user_preferences for exemple)
				if (
						!empty($xml->SINGLE)
					AND $xml->SINGLE->KEY[1]->attributes()->__toString() == 'warnings'
					AND !empty($xml->SINGLE->KEY[1]->MULTIPLE->SINGLE->KEY[3])
				) {
					throw new \Exception('ERROR : '.$xml->SINGLE->KEY[1]->MULTIPLE->SINGLE->KEY[3]->VALUE.chr(10));
				}

                // Réponse standard pour les modules avec retours
                if (
                        !empty($xml->MULTIPLE->SINGLE->KEY->VALUE)
                    && !in_array($param['module'], ['manual_enrol_users', 'group_members'])
                ) {
                    $result[$idDoc] = [
                        'id' => $xml->MULTIPLE->SINGLE->KEY->VALUE,
                        'error' => false,
                    ];
                } elseif (
                        !empty($xml->MULTIPLE->SINGLE->KEY[1]->VALUE)
                    && in_array($param['module'], ['notes'])
                ) {
                    $result[$idDoc] = [
                        'id' => $xml->MULTIPLE->SINGLE->KEY[1]->VALUE,
                        'error' => false,
                    ];
				} elseif (
                        in_array($param['module'], ['core_user_set_user_preferences'])
					&& !empty($xml->SINGLE)
					&& $xml->SINGLE->KEY[0]->attributes()->__toString() == 'saved'
                ) {
                    $result[$idDoc] = [
                        'id' => $obj->userid.'_'.$obj->name,
                        'error' => false,
                    ];
                } elseif (!empty($xml->ERRORCODE)) {
                    throw new \Exception($xml->ERRORCODE.' : '.$xml->MESSAGE);
                }
                // Si pas d'erreur et module sans retour alors on génère l'id
                elseif (in_array($param['module'], ['manual_enrol_users'])) {
                    $result[$idDoc] = [
                        'id' => $obj->courseid.'_'.$obj->userid.'_'.$obj->roleid,
                        'error' => false,
                    ];
                } elseif (in_array($param['module'], ['group_members'])) {
                    $result[$idDoc] = [
                        'id' => $obj->groupid.'_'.$obj->userid,
                        'error' => false,
                    ];
                } else {
                    throw new \Exception('Error unknown. ');
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
            // Modification du statut du flux
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateData($param): array
    {
		// Preference is always a creation
		if ($param['module'] == 'core_user_set_user_preferences') {
			return $this->createData($param);
		}
        // Transformation du tableau d'entrée pour être compatible webservice Sugar
        foreach ($param['data'] as $idDoc => $data) {
            try {
                // Check control before update
                $data = $this->checkDataBeforeUpdate($param, $data, $idDoc);
                // Get the custom fields set in the connector
                $customFieldList = $this->getCustomFields($param);
                $dataSugar = [];
                $obj = new \stdClass();
                foreach ($data as $key => $value) {
                    if ('target_id' == $key) {
                        continue;
                    } 
                    if (!empty($value)) {
                        // if $value belongs to $this->paramConnexion[user_custom_fields] then we add it to $obj->customfields
                        if (in_array($key, $customFieldList)) {
                            $customField = new \stdClass();
                            // Param names are differents depending on the module
                            if($param['module'] == 'users') {
                                $customField->type = $key;
                            } elseif($param['module'] == 'courses') {
                                $customField->shortname = $key;
                            }
                            $customField->value = $value;
                            $obj->customfields[] = $customField;
                        } else {
                            $obj->$key = $value;
                        }
                    }
                }

                // Fonctions et paramètres différents en fonction des appels webservice
                switch ($param['module']) {
                    case 'users':
                        $obj->id = $data['target_id'];
                        $users = [$obj];
                        $params = ['users' => $users];
                        $functionname = 'core_user_update_users';
                        break;
                    case 'courses':
                        $obj->id = $data['target_id'];
                        $courses = [$obj];
                        $params = ['courses' => $courses];
                        $functionname = 'core_course_update_courses';
                        break;
                    case 'manual_enrol_users':
                        $enrolments = [$obj];
                        $params = ['enrolments' => $enrolments];
                        $functionname = 'enrol_manual_enrol_users';
                        break;
					case 'manual_unenrol_users':
						$enrolments = [$obj];
                        $params = ['enrolments' => $enrolments];
                        $functionname = 'enrol_manual_unenrol_users';
                        break;
                    case 'notes':
                        $obj->id = $data['target_id'];
                        unset($obj->userid);
                        unset($obj->courseid);
                        $notes = [$obj];
                        $params = ['notes' => $notes];
                        $functionname = 'core_notes_update_notes';
                        break;
                    case 'group_members':
                        $members = [$obj];
                        $params = ['members' => $members];
                        $functionname = 'core_group_add_group_members';
                        break;
                    default:
                        throw new \Exception('Module unknown. ');
                        break;
                }

                $serverurl = $this->paramConnexion['url'].'/webservice/rest/server.php'.'?wstoken='.$this->paramConnexion['token'].'&wsfunction='.$functionname;
                $response = $this->moodleClient->post($serverurl, $params);			
                $xml = simplexml_load_string($response);
				
				// Check if there is a warning
				if (
						!empty($xml->SINGLE->KEY)
					AND $xml->count() != 0	// Empty xml
					AND $xml->SINGLE->KEY->attributes()->__toString() == 'warnings'
					AND !empty($xml->SINGLE->KEY->MULTIPLE->SINGLE->KEY[3])
				) {
					throw new \Exception('ERROR : '.$xml->SINGLE->KEY->MULTIPLE->SINGLE->KEY[3]->VALUE.chr(10));
				}

                // Réponse standard pour les modules avec retours
                if (!empty($xml->ERRORCODE)) {
                    throw new \Exception($xml->ERRORCODE.' : '.$xml->MESSAGE.(!empty($xml->DEBUGINFO) ? ' Debug : '.$xml->DEBUGINFO : ''));
                }
                // Si pas d'erreur et module sans retour alors on génère l'id
                elseif (in_array($param['module'], ['manual_enrol_users', 'manual_unenrol_users'])) {
                    $result[$idDoc] = [
                        'id' => $obj->courseid.'_'.$obj->userid.'_'.$obj->roleid,
                        'error' => false,
                    ];
                } elseif (in_array($param['module'], ['group_members'])) {
                    $result[$idDoc] = [
                        'id' => $obj->groupid.'_'.$obj->userid,
                        'error' => false,
                    ];
                } else {
                    $result[$idDoc] = [
                        'id' => $obj->id,
                        'error' => false,
                    ];
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
            // Modification du statut du flux
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

	// Check data before update
    // Add a throw exeption if error
    protected function checkDataBeforeUpdate($param, $data, $idDoc=null)
    {
		// createpassword field can be oly used in creation
		if (
				$param['module'] == 'users'
			AND isset($data['createpassword'])
		) {
			unset($data['createpassword']);
		}
		// Rempove create only field
		if (!empty($this->createOnlyFields[$param['module']])) {
			foreach($this->createOnlyFields[$param['module']] as $createOnlyField) {
				if (isset($data[$createOnlyField])) {
					unset($data[$createOnlyField]);
				}
			}
		}
        return parent::checkDataBeforeUpdate($param, $data, $idDoc);
    }

    // Permet de renvoyer le mode de la règle en fonction du module target
    // Valeur par défaut "0"
    // Si la règle n'est qu'en création, pas en modicication alors le mode est C
    public function getRuleMode($module, $type): array
    {
        if (
                'target' == $type
            && in_array($module, ['groups'])
        ) { // Si le module est dans le tableau alors c'est uniquement de la création
            return [
                'C' => 'create_only',
            ];
        }

        return parent::getRuleMode($module, $type);
    }

    // Function de conversion de datetime format solution à un datetime format Myddleware
    protected function dateTimeToMyddleware($dateTime)
    {
        $date = new \DateTime();
        $date->setTimestamp($dateTime);

        return $date->format('Y-m-d H:i:s');
    }

    // Function de conversion de datetime format Myddleware à un datetime format solution

    /**
     * @throws \Exception
     */
    protected function dateTimeFromMyddleware($dateTime)
    {
        $date = new \DateTime($dateTime);

        return $date->format('U');
    }

    // Format webservice result if needed
    protected function formatResponse($method, $response, $param)
    {
        $xml = simplexml_load_string($response);
        $functionName = $this->getFunctionName($param);
        if ('read' == $method) {
            if (in_array($functionName, ['core_user_get_users', 'core_course_get_courses_by_field'])) {
                $xml = $xml->SINGLE->KEY[0];
            }
        }

        return $xml;
    }

    // Get the function name
    protected function getFunctionName($param): string
    {
		if (
				$param['call_type'] == 'history'
			AND in_array($param['module'], array('manual_enrol_users', 'manual_unenrol_users'))
		) {
			return 'local_myddleware_search_enrolment';
		}
        // In case of duplicate search (search with a criteria)
        if (
                !empty($param['query'])
            and empty($param['query']['id'])
        ) {
            // We use the standard function to search for a user (allow Myddleware to search a user by username or email)
            if ('users' == $param['module']) {
                return 'core_user_get_users';
            } elseif ('courses' == $param['module']) {
                return 'core_course_get_courses_by_field';
            }
		// In case of read by date or search a specific record with an id for specific modules user or course
        } else {
            if ('users' == $param['module']) {
                return 'local_myddleware_get_users_by_date';
            } elseif ('courses' == $param['module']) {
                return 'local_myddleware_get_courses_by_date';
            } elseif ('groups' == $param['module']) {
                return 'local_myddleware_get_groups_by_date';
            } elseif ('group_members' == $param['module']) {
                return 'local_myddleware_get_group_members_by_date';
            }
        }
        // In all other cases
        return 'local_myddleware_'.$param['module'];
    }

    // Prepare parameters for read function

    /**
     * @throws \Exception
     */
    protected function setParameters($param): array
    {
		$functionName = $this->getFunctionName($param);
		// Specific parameters for function local_myddleware_search_enrolment
		if ($functionName == 'local_myddleware_search_enrolment') {
			if (
					empty($param['query']['userid'])
				 OR empty($param['query']['courseid'])
			) {
				throw new \Exception('CourseId and UserId are both requiered to check if an enrolment exists. One of them is empty here or is not added as duplicate serach parameter in the rule. ');
			}
			$parameters['userid'] = $param['query']['userid'];
			$parameters['courseid'] = $param['query']['courseid'];
			return $parameters;
        }
		
        // If standard function called to search by criteria
        $parameters['time_modified'] = $this->dateTimeFromMyddleware($param['date_ref']);
        if (in_array($functionName, ['core_user_get_users', 'core_course_get_courses_by_field'])) {
            if (!empty($param['query'])) {
                foreach ($param['query'] as $key => $value) {
                    if ('users' == $param['module']) {
                        $filters[] = ['key' => $key, 'value' => $value];
                        $parameters = ['criteria' => $filters];
                    } else { // course
                        $parameters = ['field' => $key, 'value' => $value];
                    }
                }
            } else {
                throw new \Exception('Filter criteria empty. Not allowed to run function '.$functionName.' without filter criteria.');
            }
        } 
		elseif (!empty($param['query']['id'])) {
            $parameters['id'] = $param['query']['id'];
        }

        return $parameters;
    }

    // Renvoie le nom du champ de la date de référence en fonction du module et du mode de la règle
    public function getRefFieldName($param): string
    {
        switch ($param['module']) {
            case 'get_course_completion_by_date':
                return 'timecompleted';
                break;
            case 'get_users_last_access':
                return 'lastaccess';
                break;
            case 'users': 
				$functionName = $this->getFunctionName($param);
				if ($functionName == 'core_user_get_users') { // Only use to get one user (history purpose)
					return 'id';
				} else {
					return 'timemodified';
				}
                break;
            case 'group_members': 
                return 'timeadded';
                break; 
            default:
                return 'timemodified';
                break;
        }
    }

    // Get the custom fields depending on the module
    protected function getCustomFields ($param) {
        // User and course Moodle fields aren't stored in the same parameter
        if (
                $param['module'] == 'users'
            AND !empty($this->paramConnexion['user_custom_fields'])
        ) {
            return explode(',',$this->paramConnexion['user_custom_fields']);
        }
        if (
                $param['module'] == 'courses'
            AND !empty($this->paramConnexion['course_custom_fields'])
        ) {
            return explode(',',$this->paramConnexion['course_custom_fields']);
        } 
        return array();
    }

	// Function to add custom fields for course and user modules.
	// The custom fields are stored into the connector parameters
	protected function addCustomFields($module, $type, $param) {
		$customFields = array();
		// Check if custom fields exist
		if (
				$module == 'users'
			AND !empty($this->paramConnexion['user_custom_fields'])
		) {
			$customFields = explode(',',$this->paramConnexion['user_custom_fields']);
		} elseif (
				$module == 'courses'
			AND !empty($this->paramConnexion['course_custom_fields'])
		) {
			$customFields = explode(',',$this->paramConnexion['course_custom_fields']);
		}
		// Add the custom fields in the attribute $moduleFields
		if (!empty($customFields)) {
			foreach ($customFields as $customField) {
				$this->moduleFields[$customField] = [
					'label' => $customField,
					'type' => 'varchar(255)',
					'type_bdd' => 'varchar(255)',
					'required' => 0,
					'required_relationship' => 0,
					'relate' => false,
				];
			}
		}
	}
}