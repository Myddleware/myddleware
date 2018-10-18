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

// List of fields for each modules
$moduleFields = array (
					'lists' => array (
						'name' => array('label' => 'Name', 'type' => 'text', 'required' => 1),
						'contact__company' => array('label' => 'Contact - Company', 'type' => 'text', 'required' => 1),
						'contact__address1' => array('label' => 'Contact - Adsress1', 'type' => 'text', 'required' => 1),
						'contact__address2' => array('label' => 'Contact - address2', 'type' => 'text', 'required' => 0),
						'contact__city' => array('label' => 'Contact - city', 'type' => 'text', 'required' => 1),
						'contact__state' => array('label' => 'Contact - state', 'type' => 'text', 'required' => 1),
						'contact__zip' => array('label' => 'Contact - zip', 'type' => 'text', 'required' => 1),
						'contact__country' => array('label' => 'Contact - country', 'type' => 'text', 'required' => 1),
						'contact__phone' => array('label' => 'Contact - phone', 'type' => 'text', 'required' => 0),
						'permission_reminder' => array('label' => 'Permission reminder', 'type' => 'text', 'required' => 1),
						'use_archive_bar' => array('label' => 'Use archive bar', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'campaign_defaults__from_name' => array('label' => 'Campaign defaults from name', 'type' => 'text', 'required' => 1),
						'campaign_defaults__from_email' => array('label' => 'Campaign defaults from email', 'type' => 'text', 'required' => 1),
						'campaign_defaults__subject' => array('label' => 'Campaign defaults subject', 'type' => 'text', 'required' => 1),
						'campaign_defaults__language' => array('label' => 'Campaign defaults language', 'type' => 'text', 'required' => 1),
						'notify_on_subscribe' => array('label' => 'Notify on subscribe', 'type' => 'text', 'required' => 0),
						'notify_on_unsubscribe' => array('label' => 'Notify on unsubscribe', 'type' => 'text', 'required' => 0),
						'email_type_option' => array('label' => 'Email type option', 'type' => 'bool', 'required' => 1, 'option' => array( '1' => 'True', '2' => 'False')),
						'visibility' => array('label' => 'Visibility', 'type' => 'text', 'required' => 0),
						'double_optin' => array('label' => 'Visibility', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'marketing_permissions' => array('label' => 'Visibility', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
					),
					'campaigns' => array (
						'settings__title' => array('label' => 'Title', 'type' => 'text', 'required' => 0),
						'settings__subject_line' => array('label' => 'Subject', 'type' => 'text', 'required' => 1),
						'settings__from_name' => array('label' => 'From name', 'type' => 'text', 'required' => 1),
						'settings__reply_to' => array('label' => 'Reply to', 'type' => 'text', 'required' => 1),
						'settings__use_conversation' => array('label' => 'Use conversation', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'settings__to_name' => array('label' => 'To name', 'type' => 'text', 'required' => 0),
						'settings__authenticate' => array('label' => 'Authenticate', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'settings__auto_footer' => array('label' => 'Auto footer', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'settings__inline_css' => array('label' => 'Inline css', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'settings__auto_tweet' => array('label' => 'Auto tweet', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'settings__fb_comments' => array('label' => 'Fb comments', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'variate_settings__winner_criteria' => array('label' => 'Winning Criteria', 'type' => 'text', 'required' => 0, 'option' => array('opens' => 'opens','clicks' => 'clicks','manual' => 'manual','total_revenue' => 'total_revenue')),
						'variate_settings__wait_time' => array('label' => 'Wait_time', 'type' => 'text', 'required' => 0),
						'variate_settings__test_size' => array('label' => 'Test size', 'type' => 'text', 'required' => 0),
						'variate_settings__test_size' => array('label' => 'Test size', 'type' => 'text', 'required' => 0),
						'tracking__opens' => array('label' => 'Opens', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'tracking__html_clicks' => array('label' => 'HTML Click Tracking', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'tracking__text_clicks' => array('label' => 'Plain-Text Click Tracking', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'tracking__goal_tracking' => array('label' => 'MailChimp Goal Tracking', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'tracking__ecomm360' => array('label' => 'E-commerce Tracking', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'tracking__google_analytics' => array('label' => 'Google Analytics Tracking', 'type' => 'text', 'required' => 0),
						'tracking__clicktale' => array('label' => 'ClickTale Analytics Tracking', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'rss_opts__feed_url' => array('label' => 'Feed URL', 'type' => 'text', 'required' => 0),
						'rss_opts__frequency' => array('label' => 'Frequency', 'type' => 'text', 'required' => 0, 'option' => array('daily' => 'daily','weekly' => 'weekly','monthly' => 'monthly')),
						'rss_opts__constrain_rss_img' => array('label' => 'Constrain RSS Images', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'social_card__title' => array('label' => 'Social card title', 'type' => 'text', 'required' => 0),
						'social_card__description' => array('label' => 'Social card description', 'type' => TextType::class, 'required' => 0),
						'social_card__image_url' => array('label' => 'Social card image_url', 'type' => 'text', 'required' => 0),
						'type' => array('label' => 'Campaign Type', 'type' => 'text', 'required' => 1, 'option' => array('regular' => 'regular','plaintext' => 'plaintext','absplit' => 'absplit','rss' => 'rss','variate' => 'variate'))
					),
					'members' => array (
						'email_address' => array('label' => 'Email address', 'type' => 'text', 'required' => 1),
						'email_type' => array('label' => 'Email Type', 'type' => 'text', 'required' => 0, 'option' => array('text' => 'text','html' => 'html')),
						'status' => array('label' => 'Status', 'type' => 'text', 'required' => 1, 'option' => array('pending' => 'pending','subscribed' => 'subscribed','unsubscribed' => 'unsubscribed','cleaned' => 'cleaned')),
						'language' => array('label' => 'Language', 'type' => 'text', 'required' => 0),
						'vip' => array('label' => 'VIP', 'type' => 'bool', 'required' => 0, 'option' => array( '1' => 'True', '2' => 'False')),
						'location__latitude' => array('label' => 'Latitude', 'type' => 'text', 'required' => 0),
						'location__longitude' => array('label' => 'Longitude', 'type' => 'text', 'required' => 0),
						'ip_signup' => array('label' => 'Ip signup', 'type' => 'text', 'required' => 0),
						'timestamp_signup' => array('Timestamp signup' => 'Email', 'type' => 'text', 'required' => 0),
						'ip_opt' => array('label' => 'Ip opt', 'type' => 'text', 'required' => 0),
						'timestamp_opt' => array('label' => 'Timestamp opt', 'type' => 'text', 'required' => 0),
						'merge_fields__FNAME' => array('label' => 'MERGE0', 'type' => 'text', 'required' => 0),
						'merge_fields__LNAME' => array('label' => 'First name', 'type' => 'text', 'required' => 0)
					)
				);
				
$fieldsRelate = array (
					'campaigns' => array(
						'recipients__list_id' => array('label' => 'List ID', 'type' => 'text', 'required' => 0, 'required_relationship' => 0)
						),
					'members' => array(
						'list_id' => array('label' => 'List ID', 'type' => 'text', 'required' => 0, 'required_relationship' => 1)
						),
					);

// Metadata override if needed
$file = __DIR__.'/../../../Custom/Solutions/lib/mailchimp/metadata.php';;
if(file_exists($file)){
	require_once($file);
}					