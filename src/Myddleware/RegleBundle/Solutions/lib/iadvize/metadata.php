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
					'visitor' => array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'unique_id' => array('label' => 'Visitor unique identifier', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'external_id' => array('label' => 'Your id if provided', 'type' => PasswordType::class, 'type_bdd' => 'varchar(255)', 'required' => 0),
						'lastname' => array('label' => 'Last name', 'type' => 'bool', 'type_bdd' => 'bool', 'required' => 0),
						'firstname' => array('label' => 'First name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'address' => array('label' => 'Address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'city' => array('label' => 'City', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'zip' => array('label' => 'Zip code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'country' => array('label' => 'Country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'phone' => array('label' => 'Phone number', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'email' => array('label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'browser' => array('label' => 'Browser used by visitor', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'created_at' => array('label' => 'Visitor creation date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					),
					'conversation.json-unicode' => array(
						'id' => array('label' => 'Conversation identifier', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'channel' => array('label' => 'Conversation channel', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('chat'=>'Chat', 'call'=>'Call', 'video'=> 'Video')),
						'history' => array('label' => 'Conversation history', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'operator_answer' => array('label' => 'Conversation answered by operator', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'operator_closed' => array('label' => 'Conversation closed by operator', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'waitinglist' => array('label' => 'Waiting list status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'page_type' => array('label' => 'Page type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'created_at' => array('label' => 'Conversation start time', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'closed_at' => array('label' => 'Conversation end time', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'tag_list' => array('label' => 'List of tag identifiers', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					),
				);


$fieldsRelate = array (
					'visitor' => array(
						'website_id' => array('label' => 'List of website identifiers', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1)
					),
					'conversation.json-unicode' => array(
						'visitor_uid' => array('label' => 'Visitor unique identifier', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'website_id' => array('label' => 'Website identifier', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'operator_id' => array('label' => 'Operator identifier', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'skill_id' => array('label' => 'Skill identifier', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'rule_id' => array('label' => 'Rule identifier', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'xmpp_id' => array('label' => 'XMPP related identifier', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1)
					),
				);


// Metadata override if needed
$file = __DIR__.'/../../../Custom/Solutions/lib/moodle/metadata.php';
if(file_exists($file)){
	require_once($file);
}						