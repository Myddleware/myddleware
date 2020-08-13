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
	
$moduleFields = array (
					'users' => array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'username' => array('label' => 'Username', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'password' => array('label' => 'Password', 'type' => PasswordType::class, 'type_bdd' => 'varchar(255)', 'required' => 0),
						'createpassword' => array('label' => 'Create password', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'firstname' => array('label' => 'Firstname', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'lastname' => array('label' => 'Lastname', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'email' => array('label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'auth' => array('label' => 'Auth', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array(
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
						)),
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
					),
				
					'courses' => array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'fullname' => array('label' => 'Full name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'shortname' => array('label' => 'Short name  ', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'categoryid' => array('label' => 'Category ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'idnumber' => array('label' => 'ID number  ', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'summary' => array('label' => 'Summary', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'summaryformat' => array('label' => 'Summary format', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array(
																																				'0' => 'MOODLE',
																																				'1' => 'HTML',
																																				'2' => 'PLAIN',
																																				'4' => 'MARKDOWN'
																																			)),
						'format' => array('label' => 'Format', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array(
																																				'singleactivity' => 'Single activity format',
																																				'social' => 'Social format',
																																				'topics' => 'Topics format',
																																				'weeks' => 'Weekly format'
																																			)),
						'showgrades' => array('label' => 'Showgrades', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'newsitems' => array('label' => 'News items', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'startdate' => array('label' => 'Start date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'numsections' => array('label' => 'Num sections', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'maxbytes' => array('label' => 'Max bytes', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array(
																																				'0' => 'Site upload limit (2MB)',
																																				'2097152' => '2MB',
																																				'1048576' => '1MB',
																																				'512000' => '500KB',
																																				'102400' => '100KB',
																																				'51200' => '50KB',
																																				'10240' => '10KB'
																																			)),
						'showreports' => array('label' => 'Show reports', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'visible' => array('label' => 'Visible', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'hiddensections' => array('label' => 'Hidden sections', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array(
																																				'0' => 'Hidden sections are shown in collapsed form',
																																				'1' => 'Hidden sections are completely invisible'
																																			)),
						'groupmode' => array('label' => 'Group mode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array(
																																				'0' => 'No groups',
																																				'1' => 'Separate groups',
																																				'2' => 'Visible groups'
																																			)),
						'groupmodeforce' => array('label' => 'Group mode force', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'defaultgroupingid' => array('label' => 'default grouping ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'enablecompletion' => array('label' => 'Enable completion', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'completionnotify' => array('label' => 'Completion notify', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'lang' => array('label' => 'Language', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'forcetheme' => array('label' => 'Force theme', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					),
					
					'groups' => array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'name' => array('label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'description' => array('label' => 'Description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'descriptionformat' => array('label' => 'Description format', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array(
																																				'0' => 'MOODLE',
																																				'1' => 'HTML',
																																				'2' => 'PLAIN',
																																				'4' => 'MARKDOWN'
																																			)),
						'enrolmentkey' => array('label' => 'Enrolment key', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'idnumber' => array('label' => 'ID number', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					),

					'manual_enrol_users' => array(
						'roleid' => array('label' => 'Role ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'timestart' => array('label' => 'Time start', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timeend' => array('label' => 'Time end', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'suspend' => array('label' => 'Description format', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0)
					),
					
					'get_enrolments_by_date' => array(
						'roleid' => array('label' => 'Role ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'status' => array('label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'enrol' => array('label' => 'Enrolment method', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timestart' => array('label' => 'Time start', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timeend' => array('label' => 'Time end', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timecreated' => array('label' => 'Time created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timemodified' => array('label' => 'Time modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					),

					'manual_unenrol_users' => array(
						'roleid' => array('label' => 'Role ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
					),
	
					'notes' => array(
						'publishstate' => array('label' => 'Publish state ', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array(
																																				'personal' => 'Personal',
																																				'course' => 'Course',
																																				'site' => 'Site'
																																			)),
						'text' => array('label' => 'Text', 'type' => TextType::class, 'type_bdd' => 'text', 'required' => 0),
						'format' => array('label' => 'Format', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array(
																																				'0' => 'MOODLE',
																																				'1' => 'HTML',
																																				'2' => 'PLAIN',
																																				'4' => 'MARKDOWN'
																																			)),
						'clientnoteid' => array('label' => 'Client note id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					),

					'get_users_completion' => array(
						'instance' => array('label' => 'Instance', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'section' => array('label' => 'Section', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'moduletype' => array('label' => 'Module', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'completionstate' => array('label' => 'Completion state', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'modulename' => array('label' => 'Module name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'coursemodulename' => array('label' => 'Course module name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timemodified' => array('label' => 'Time modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					),

					'get_course_completion_by_date' => array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timeenrolled' => array('label' => 'Time enrolled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timestarted' => array('label' => 'Time start', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timecompleted' => array('label' => 'Time completed', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					),
	
					'get_users_last_access' => array(
						'lastaccess' => array('label' => 'Last access', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					),
					
					'get_user_compentencies_by_date' => array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'status' => array('label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'proficiency' => array('label' => 'Proficiency', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'grade' => array('label' => 'Grade', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timecreated' => array('label' => 'Time created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timemodified' => array('label' => 'Time modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'usermodified' => array('label' => 'User modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'competency_shortname' => array('label' => 'Competency shortname ', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'competency_description' => array('label' => 'Competency description ', 'type' => TextType::class, 'type_bdd' => 'varchar(255)', 'required' => 0),
						'competency_descriptionformat' => array('label' => 'Competency description format', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array(
																																											'0' => 'MOODLE',
																																											'1' => 'HTML',
																																											'2' => 'PLAIN',
																																											'4' => 'MARKDOWN'
																																										)),
						'competency_idnumber' => array('label' => 'Competency idnumber', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'competency_path' => array('label' => 'Competency path', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'competency_sortorder' => array('label' => 'Competency sort order', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'competency_ruletype' => array('label' => 'Competency rule type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'competency_ruleoutcome' => array('label' => 'Competency rule outcome', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'competency_ruleconfig' => array('label' => 'Competency rule config', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'competency_scaleconfiguration' => array('label' => 'Competency scale configuration', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'competency_timecreated' => array('label' => 'Competency time created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'competency_timemodified' => array('label' => 'Competency time modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'competency_usermodified' => array('label' => 'Competency user modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					),
					
					'get_competency_module_completion_by_date' => array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timecreated' => array('label' => 'Time created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timemodified' => array('label' => 'Time modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'usermodified' => array('label' => 'User modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'sortorder' => array('label' => 'Sort order', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'modulename' => array('label' => 'Module name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'coursemodulename' => array('label' => 'Course module name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'ruleoutcome' => array('label' => 'Rule outcome', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					),
					
					'get_user_grades' => array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timecreated' => array('label' => 'Time created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timemodified' => array('label' => 'Time modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'usermodified' => array('label' => 'User modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'itemid' => array('label' => 'Item ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'rawgrade' => array('label' => 'Raw grade', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'rawgrademax' => array('label' => 'Raw grade max', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'rawgrademin' => array('label' => 'Raw grade min', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'rawscaleid' => array('label' => 'Raw scale ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'finalgrade' => array('label' => 'Final grade', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'hidden' => array('label' => 'Hidden', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'locked' => array('label' => 'Locked', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'locktime' => array('label' => 'Lock time', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'exported' => array('label' => 'Exported', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'overridden' => array('label' => 'Overridden', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'excluded' => array('label' => 'Excluded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'feedback' => array('label' => 'Feedback ', 'type' => TextType::class, 'type_bdd' => 'varchar(255)', 'required' => 0),
						'feedbackformat' => array('label' => 'Feedback format', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'information' => array('label' => 'Information ', 'type' => TextType::class, 'type_bdd' => 'varchar(255)', 'required' => 0),
						'informationformat' => array('label' => 'Information format', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'information' => array('label' => 'Information ', 'type' => TextType::class, 'type_bdd' => 'varchar(255)', 'required' => 0),
						'aggregationstatus' => array('label' => 'Aggregation status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'aggregationweight' => array('label' => 'Aggregation weight', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'itemname' => array('label' => 'Item name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'course_fullname' => array('label' => 'Course fullname', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'course_shortname' => array('label' => 'Course shortname', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					),

				);


$fieldsRelate = array (
					'groups' => array(
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1)
					),
					
					'group_members' => array(
						'groupid' => array('label' => 'Group ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					),
					
					'manual_enrol_users' => array(
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					),
					
					'get_enrolments_by_date' => array(
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					),

					'manual_unenrol_users' =>  array(
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					),
	
					'notes' => array(
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					),

					'get_users_completion' => array(
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'coursemoduleid' => array('label' => 'Course module ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					),

					'get_course_completion_by_date' => array(
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					),
	
					'get_users_last_access' => array(
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					),
					
					'get_user_compentencies_by_date' => array(
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						'competencyid' => array('label' => 'Competency ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						'reviewerid' => array('label' => 'Reviewer ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						'competency_competencyframeworkid' => array('label' => 'Competency framework ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						'competency_parentid' => array('label' => 'Competency parent ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						'competency_scaleid' => array('label' => 'Competency scale ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0)
					),
					
					'get_competency_module_completion_by_date' => array(
						'cmid' => array('label' => 'Course module ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						'competencyid' => array('label' => 'Competency ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0)
					),
					
					'get_user_grades' => array(
						'userid' => array('label' => 'User ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'courseid' => array('label' => 'Course ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1)
					),
				);


// Metadata override if needed
$file = __DIR__.'/../../../Custom/Solutions/lib/moodle/metadata.php';
if(file_exists($file)){
	require_once($file);
}						