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

$moduleFields = [
    /* 'transactional_stat'=>[
        'range'        => ['label' => 'Range', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'requests'     => ['label' => 'Request', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'delivered'    => ['label' => 'Delivered', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'hardBounces'  => ['label' => 'Hard bounces', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'softBounces'  => ['label' => 'Soft bounces', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'clicks'       => ['label' => 'Clicks', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'uniqueClicks' => ['label' => 'Unique clicks', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'opens'        => ['label' => 'Opens', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'uniqueOpens'  => ['label' => 'Unique opens', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'spamReports'  => ['label' => 'Spam reports', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'blocked'      => ['label' => 'Blocked', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'invalid'      => ['label' => 'Invalid', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'unsubscribed' => ['label' => 'Unsubscribed', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0]
    ], */
    'transactionalEmails' => [
        'email' => ['label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'subject' => ['label' => 'Subject', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'templateId' => ['label' => 'TemplateId', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 1],
        'messageId' => ['label' => 'MessageId', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 1],
        'uuid' => ['label' => 'Uuid', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 1],
        'date' => ['label' => 'Date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
    ],
    'transactionalEmailActivity' => [
        'email' => ['label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'date' => ['label' => 'Date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'subject' => ['label' => 'Subject', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'messageId' => ['label' => 'Message Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 1],
        'event' => ['label' => 'Event', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'tag' => ['label' => 'Tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'ip' => ['label' => 'IP', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'from' => ['label' => 'From', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'templateId' => ['label' => 'Template Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 1],
        'contactId' => ['label' => 'Contact Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 1],
    ],
	'contactHardBounces' => [
        'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
		'contactId' => ['label' => 'Contact Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 1],
        'email' => ['label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'eventTime' => ['label' => 'Unsubscription event time', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'campaignId' => ['label' => 'Unsubscription campaign ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
    ],
	'contactSoftBounces' => [
        'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
		'contactId' => ['label' => 'Contact Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 1],
        'email' => ['label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'eventTime' => ['label' => 'Unsubscription event time', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'campaignId' => ['label' => 'Unsubscription campaign ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
    ],
	'contactUnsubscriptions' => [
        'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
		'contactId' => ['label' => 'Contact Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 1],
        'email' => ['label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'eventTime' => ['label' => 'Unsubscription event time', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
        'campaignId' => ['label' => 'Unsubscription campaign ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => 0],
    ],
];
