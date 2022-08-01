<?php

declare(strict_types=1);

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
use DateTime;
use Exception;
use SimpleXMLElement;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class Moodle extends Solution
{
    protected $moodleClient;

    protected array $requiredFields = [
        'default' => ['id'],
        'get_users_completion' => ['id', 'timemodified'],
        'get_users_last_access' => ['id', 'lastaccess'],
        'get_course_completion_by_date' => ['id', 'timecompleted'],
        'get_user_grades' => ['id', 'timemodified'],
    ];

    protected array $fieldsDuplicate = [
        'users' => ['email', 'username'],
        'courses' => ['shortname', 'idnumber'],
    ];

    protected string $delaySearch = '-1 year';

    public function login($connectionParam): void
    {
        parent::login($connectionParam);
        try {
            $this->moodleClient = new curl();
            $params = [];
            $this->connectionParam['token'] = trim($this->connectionParam['token']);
            $methodName = 'core_webservice_get_site_info';
            $url = $this->connectionParam['url'].'/webservice/rest/server.php'.'?wstoken='.$this->connectionParam['token'].'&wsfunction='.$methodName;
            $response = $this->moodleClient->post($url, $params);
            $xml = simplexml_load_string($response);

            if (!empty($xml->SINGLE->KEY[0]->VALUE)) {
                $this->isConnectionValid = true;
            } elseif (!empty($xml->ERRORCODE)) {
                throw new Exception($xml->ERRORCODE.' : '.$xml->MESSAGE);
            } else {
                throw new Exception('Error unknown. ');
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);
        }
    }

    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'url',
                'type' => UrlType::class,
                'label' => 'solution.fields.url',
            ],
            [
                'name' => 'token',
                'type' => PasswordType::class,
                'label' => 'solution.fields.token',
            ],
        ];
    }

    public function getModules($type = 'source'): array
    {
        if ('source' == $type) {
            return [
                'users' => 'Users',
                'courses' => 'Courses',
                'get_users_completion' => 'Get course activity completion',
                'get_users_last_access' => 'Get users last access',
                'get_enrolments_by_date' => 'Get enrolments',
                'get_course_completion_by_date' => 'Get course completion',
                'get_user_compentencies_by_date' => 'Get user compentency',
                'get_competency_module_completion_by_date' => 'Get compentency module completion',
                'get_user_grades' => 'Get user grades',
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
        ];
    }

    public function getModuleFields($module, $type = 'source', $param = null): array
    {
        $moduleFields = [];
        parent::getModuleFields($module, $type);
        try {
            require 'lib/moodle/metadata.php';
            if (!empty($moduleFields[$module])) {
                $this->moduleFields = array_merge($this->moduleFields, $moduleFields[$module]);
            }
            // If the field category ID exist we fill it by requesting Moodle
            if (!empty($this->moduleFields['categoryid'])) {
                try {
                    // Récupération de toutes les catégories existantes
                    $params = [];
                    $methodName = 'core_course_get_categories';
                    $url = $this->connectionParam['url'].'/webservice/rest/server.php'.'?wstoken='.$this->connectionParam['token'].'&wsfunction='.$methodName;
                    $response = $this->moodleClient->post($url, $params);
                    $xml = simplexml_load_string($response);
                    if (!empty($xml->MULTIPLE->SINGLE)) {
                        foreach ($xml->MULTIPLE as $category) {
                            $this->moduleFields['categoryid']['option'][$category->SINGLE->KEY[0]->VALUE->__toString()] = $category->SINGLE->KEY[1]->VALUE->__toString();
                        }
                    }
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage().$e->getFile().$e->getLine());
                }
            }

            return $this->moduleFields;
        } catch (Exception $e) {
            $error = $e->getMessage().$e->getFile().$e->getLine();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    public function read($param): array
    {
        try {
            $result = [];

            // Set parameters to call Moodle
            $parameters = $this->setParameters($param);
            // Get function to call Moodle
            $methodName = $this->getMethodName($param);

            // Call to Moodle
            $url = $this->connectionParam['url'].'/webservice/rest/server.php'.'?wstoken='.$this->connectionParam['token'].'&wsfunction='.$methodName;
            $response = $this->moodleClient->post($url, $parameters);
            $xml = $this->formatResponse('read', $response, $param);
            if (!empty($xml->ERRORCODE)) {
                throw new Exception("Error $xml->ERRORCODE : $xml->MESSAGE");
            }

            // Transform the data to Myddleware format
            if (!empty($xml->MULTIPLE->SINGLE)) {
                foreach ($xml->MULTIPLE->SINGLE as $data) {
                    $row = [];
                    foreach ($data as $field) {
                        // Get all the requested fields
                        if (in_array($field->attributes()->__toString(), $param['fields'])) {
                            $row[$field->attributes()->__toString()] = $field->VALUE->__toString();
                        }
                    }
                    $result[] = $row;
                }
            }
        } catch (Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }

        return $result;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function createData($param): array
    {
        $result = [];
        foreach ($param['data'] as $idDoc => $data) {
            try {
                $methodName = '';
                // Check control before create
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                $obj = new \stdClass();
                foreach ($data as $key => $value) {
                    // We don't send Myddleware_element_id field to Moodle
                    if ('Myddleware_element_id' == $key) {
                        continue;
                    }
                    if (!empty($value)) {
                        $obj->$key = $value;
                    }
                }
                $params = [];
                switch ($param['module']) {
                    case 'users':
                        $users = [$obj];
                        $params = ['users' => $users];
                        $methodName = 'core_user_create_users';
                        break;
                    case 'courses':
                        $courses = [$obj];
                        $params = ['courses' => $courses];
                        $methodName = 'core_course_create_courses';
                        break;
                    case 'groups':
                        $groups = [$obj];
                        $params = ['groups' => $groups];
                        $methodName = 'core_group_create_groups';
                        break;
                    case 'group_members':
                        $members = [$obj];
                        $params = ['members' => $members];
                        $methodName = 'core_group_add_group_members';
                        break;
                    case 'manual_enrol_users':
                        $enrolments = [$obj];
                        $params = ['enrolments' => $enrolments];
                        $methodName = 'enrol_manual_enrol_users';
                        break;
                    case 'manual_unenrol_users':
                        break;
                    case 'notes':
                        $notes = [$obj];
                        $params = ['notes' => $notes];
                        $methodName = 'core_notes_create_notes';
                        break;
                    default:
                        throw new Exception('Module unknown. ');
                }

                $url = $this->connectionParam['url'].'/webservice/rest/server.php'.'?wstoken='.$this->connectionParam['token'].'&wsfunction='.$methodName;
                $response = $this->moodleClient->post($url, $params);
                $xml = simplexml_load_string($response);

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
                    && 'notes' == $param['module']
                ) {
                    $result[$idDoc] = [
                        'id' => $xml->MULTIPLE->SINGLE->KEY[1]->VALUE,
                        'error' => false,
                    ];
                } elseif (!empty($xml->ERRORCODE)) {
                    throw new Exception($xml->ERRORCODE.' : '.$xml->MESSAGE);
                }
                // Si pas d'erreur et module sans retour alors on génère l'id
                elseif ('manual_enrol_users' == $param['module']) {
                    $result[$idDoc] = [
                        'id' => $obj->courseid.'_'.$obj->userid.'_'.$obj->roleid,
                        'error' => false,
                    ];
                } elseif ('group_members' == $param['module']) {
                    $result[$idDoc] = [
                        'id' => $obj->groupid.'_'.$obj->userid,
                        'error' => false,
                    ];
                } else {
                    throw new Exception('Error unknown. ');
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateData($param): array
    {
        $result = [];
        foreach ($param['data'] as $idDoc => $data) {
            try {
                // Check control before update
                $data = $this->checkDataBeforeUpdate($param, $data);
                $obj = new \stdClass();
                foreach ($data as $key => $value) {
                    if ('target_id' == $key) {
                        continue;
                    // We don't send Myddleware_element_id field to Moodle
                    } elseif ('Myddleware_element_id' == $key) {
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
                        $users = [$obj];
                        $params = ['users' => $users];
                        $methodName = 'core_user_update_users';
                        break;
                    case 'courses':
                        $obj->id = $data['target_id'];
                        $courses = [$obj];
                        $params = ['courses' => $courses];
                        $methodName = 'core_course_update_courses';
                        break;
                    case 'manual_enrol_users':
                        $enrolments = [$obj];
                        $params = ['enrolments' => $enrolments];
                        $methodName = 'enrol_manual_enrol_users';
                        break;
                    case 'notes':
                        $obj->id = $data['target_id'];
                        unset($obj->userid);
                        unset($obj->courseid);
                        $notes = [$obj];
                        $params = ['notes' => $notes];
                        $methodName = 'core_notes_update_notes';
                        break;
                    case 'group_members':
                        $members = [$obj];
                        $params = ['members' => $members];
                        $methodName = 'core_group_add_group_members';
                        break;
                    default:
                        throw new Exception('Module unknown. ');
                }

                $url = $this->connectionParam['url'].'/webservice/rest/server.php'.'?wstoken='.$this->connectionParam['token'].'&wsfunction='.$methodName;
                $response = $this->moodleClient->post($url, $params);
                $xml = simplexml_load_string($response);

                // Réponse standard pour les modules avec retours
                if (!empty($xml->ERRORCODE)) {
                    throw new Exception($xml->ERRORCODE.' : '.$xml->MESSAGE.(!empty($xml->DEBUGINFO) ? ' Debug : '.$xml->DEBUGINFO : ''));
                }
                // Si pas d'erreur et module sans retour alors on génère l'id
                elseif ('manual_enrol_users' == $param['module']) {
                    $result[$idDoc] = [
                        'id' => $obj->courseid.'_'.$obj->userid.'_'.$obj->roleid,
                        'error' => false,
                    ];
                } elseif ('group_members' == $param['module']) {
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
            } catch (Exception $e) {
                $error = $e->getMessage();
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    // Permet de renvoyer le mode de la règle en fonction du module target
    // Valeur par défaut "0"
    // Si la règle n'est qu'en création, pas en modification alors le mode est C
    public function getRuleMode($module, $type): array
    {
        if (
            'target' == $type
            && 'groups' == $module
        ) { // Si le module est dans le tableau alors c'est uniquement de la création
            return [
                'C' => 'create_only',
            ];
        }

        return parent::getRuleMode($module, $type);
    }

    protected function dateTimeToMyddleware($dateTime): string
    {
        $date = new DateTime();
        $date->setTimestamp((int) $dateTime);

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @throws Exception
     */
    protected function dateTimeFromMyddleware($dateTime): string
    {
        $date = new DateTime($dateTime);

        return $date->format('U');
    }

    protected function formatResponse($method, $response, $param): SimpleXMLElement|bool
    {
        $xml = simplexml_load_string($response);
        $functionName = $this->getMethodName($param);
        if ('read' == $method) {
            if (in_array($functionName, ['core_user_get_users', 'core_course_get_courses_by_field'])) {
                $xml = $xml->SINGLE->KEY[0];
            }
        }

        return $xml;
    }

    protected function getMethodName($param): string
    {
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
            }
        }
        // In all other cases
        return 'local_myddleware_'.$param['module'];
    }

    /**
     * @throws Exception
     */
    protected function setParameters($param): array
    {
        $functionName = $this->getMethodName($param);
        $parameters['time_modified'] = $this->dateTimeFromMyddleware($param['date_ref']);
        // If standard function called to search by criteria
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
                throw new Exception('Filter criteria empty. Not allowed to run function '.$functionName.' without filter criteria.');
            }
        } elseif (!empty($param['query']['id'])) {
            $parameters['id'] = $param['query']['id'];
        }

        return $parameters;
    }

    public function getRefFieldName($moduleSource, $ruleMode): string
    {
        return match ($moduleSource) {
            'get_course_completion_by_date' => 'timecompleted',
            'get_users_last_access' => 'lastaccess',
            default => 'timemodified',
        };
    }
}
