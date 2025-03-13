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
// hs_timestamp	Required. This field marks the email's time of creation and determines where the email sits on the record timeline. You can use either a Unix timestamp in milliseconds or UTC format.
// hubspot_owner_id	The ID of the owner associated with the email. This field determines the user listed as the email creator on the record timeline.
// hs_email_direction	The direction the email was sent in. Possible values include:EMAIL: the email was sent from the CRM or sent and logged to the CRM with the BCC address.INCOMING_EMAIL: the email was a reply to a logged outgoing email. FORWARDED_EMAIL: the email was forwarded to the CRM.
// hs_email_html	The body of an email if it is sent from a CRM record.
// hs_email_status	The send status of the email. The value can be BOUNCED, FAILED, SCHEDULED, SENDING, or SENT.
// hs_email_subject	The subject line of the logged email.
// hs_email_text	The body of the email.
// hs_attachment_ids	The IDs of the email's attachments. Multiple attachment IDs are separated by a semi-colon.
// hs_email_headers	The email's headers. The value for this property will automatically populate certain read only email properties. Learn how to set email hea
$moduleFields = [
    'emails' => [
        'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'associations_to_id' => ['label' => 'Contact ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'associations_category' => ['label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'option' => [
				'HUBSPOT_DEFINED' => 'HUBSPOT DEFINED',
				'USER_DEFINED' => 'USER DEFINED',
				'INTEGRATOR_DEFINED' => 'INTEGRATOR DEFINED',
			],
		],
        'associations_typeId' => ['label' => 'Category', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'option' => [
			'198' => 'Email to contact',
			'186' => 'Email to company',
			'210' => 'Email to deal',
			'224' => 'Email to ticket',
			'917' => 'Email to appointment',
			'871' => 'Email to course',
			'895' => 'Email to listing',
			'843' => 'Email to service	',	
			],
		],
        'traceId' => ['label' => 'Trace ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        // 'hubspot_owner_id' => ['label' => 'Owner', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_email_headers' => ['label' => 'Email headers', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_email_text' => ['label' => 'Email text', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_email_html' => ['label' => 'Email HTML', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_email_subject' => ['label' => 'Subject', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_email_direction' => ['label' => 'Direction', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => [
				'FORWARDED_EMAIL' => 'Forwarded',
				'INCOMING_EMAIL' => 'Incoming ',
				'EMAIL' => 'Outgoing ',
			],
		],
        'hs_timestamp' => ['label' => 'Timestamp', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'hs_email_status' => ['label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => [
				'BOUNCED' => 'Bounced',
				'FAILED' => 'Failed',
				'SCHEDULED' => 'Scheduled',
				'SENDING' => 'Sending',
				'SENT' => 'Sent',
			],
		],
    ],
	'notes' => [
        'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'associations_to_id' => ['label' => 'Contact ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'associations_category' => ['label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'option' => [
				'HUBSPOT_DEFINED' => 'HUBSPOT DEFINED',
				'USER_DEFINED' => 'USER DEFINED',
				'INTEGRATOR_DEFINED' => 'INTEGRATOR DEFINED',
			],
		],
        'associations_typeId' => ['label' => 'Category', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'option' => [
			'202' => 'Note to contact',
			'190' => 'Note to company',
			'214' => 'Note to deal',
			'228' => 'Note to ticket',
			],
		],
		'traceId' => ['label' => 'Trace ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        // 'hubspot_owner_id' => ['label' => 'Owner', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_note_body' => ['label' => 'Body', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_timestamp' => ['label' => 'Timestamp', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
    ],
	'calls' => [
        'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'associations_to_id' => ['label' => 'Contact ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
        'associations_category' => ['label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'option' => [
				'HUBSPOT_DEFINED' => 'HUBSPOT DEFINED',
				'USER_DEFINED' => 'USER DEFINED',
				'INTEGRATOR_DEFINED' => 'INTEGRATOR DEFINED',
			],
		],
        'associations_typeId' => ['label' => 'Category', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'option' => [
			'194' => 'Appel to contact',
			'182' => 'Appel to company',
			'206' => 'Appel to deal',
			'220' => 'Appel to ticket',
			],
		],
		'traceId' => ['label' => 'Trace ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        // 'hubspot_owner_id' => ['label' => 'Owner', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_call_title' => ['label' => 'Title', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_call_body' => ['label' => 'Body', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_call_callee_object_id' => ['label' => 'Callee object ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_call_callee_object_type' => ['label' => 'Callee object type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_call_duration' => ['label' => 'Duration (in milliseconds)', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_call_from_number' => ['label' => 'From number', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_call_to_number' => ['label' => 'To number', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_activity_type' => ['label' => 'Activity type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_call_recording_url' => ['label' => 'Recording URL', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'hs_call_direction' => ['label' => 'Direction', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => [
				'INBOUND' => 'Inbound',
				'OUTBOUND' => 'Outbound ',
			],
		],
        'hs_timestamp' => ['label' => 'Timestamp', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'hs_call_status' => ['label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => [
				'BUSY' => 'Busy',
				'CALLING_CRM_USER' => 'Callig from user',
				'CANCELED' => 'Canceled',
				'COMPLETED' => 'Completed',
				'CONNECTING' => 'Connecting',
				'FAILED' => 'Failed',
				'IN_PROGRESS' => 'In progress',
				'NO_ANSWER' => 'No answer',
				'QUEUED' => 'Queued',
				'RINGING' => 'Ringing',
			],
		],
    ],
];
