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
$moduleFields = [
    'lists' => [
        'name' => ['label' => 'Name', 'type' => 'text', 'required' => 1],
        'contact__company' => ['label' => 'Contact - Company', 'type' => 'text', 'required' => 1],
        'contact__address1' => ['label' => 'Contact - Adsress1', 'type' => 'text', 'required' => 1],
        'contact__address2' => ['label' => 'Contact - address2', 'type' => 'text', 'required' => 0],
        'contact__city' => ['label' => 'Contact - city', 'type' => 'text', 'required' => 1],
        'contact__state' => ['label' => 'Contact - state', 'type' => 'text', 'required' => 1],
        'contact__zip' => ['label' => 'Contact - zip', 'type' => 'text', 'required' => 1],
        'contact__country' => ['label' => 'Contact - country', 'type' => 'text', 'required' => 1],
        'contact__phone' => ['label' => 'Contact - phone', 'type' => 'text', 'required' => 0],
        'permission_reminder' => ['label' => 'Permission reminder', 'type' => 'text', 'required' => 1],
        'use_archive_bar' => ['label' => 'Use archive bar', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'campaign_defaults__from_name' => ['label' => 'Campaign defaults from name', 'type' => 'text', 'required' => 1],
        'campaign_defaults__from_email' => ['label' => 'Campaign defaults from email', 'type' => 'text', 'required' => 1],
        'campaign_defaults__subject' => ['label' => 'Campaign defaults subject', 'type' => 'text', 'required' => 1],
        'campaign_defaults__language' => ['label' => 'Campaign defaults language', 'type' => 'text', 'required' => 1],
        'notify_on_subscribe' => ['label' => 'Notify on subscribe', 'type' => 'text', 'required' => 0],
        'notify_on_unsubscribe' => ['label' => 'Notify on unsubscribe', 'type' => 'text', 'required' => 0],
        'email_type_option' => ['label' => 'Email type option', 'type' => 'bool', 'required' => 1, 'option' => ['1' => 'True', '2' => 'False']],
        'visibility' => ['label' => 'Visibility', 'type' => 'text', 'required' => 0],
        'double_optin' => ['label' => 'Visibility', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'marketing_permissions' => ['label' => 'Visibility', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
    ],
    'campaigns' => [
        'settings__title' => ['label' => 'Title', 'type' => 'text', 'required' => 0],
        'settings__subject_line' => ['label' => 'Subject', 'type' => 'text', 'required' => 1],
        'settings__from_name' => ['label' => 'From name', 'type' => 'text', 'required' => 1],
        'settings__reply_to' => ['label' => 'Reply to', 'type' => 'text', 'required' => 1],
        'settings__use_conversation' => ['label' => 'Use conversation', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'settings__to_name' => ['label' => 'To name', 'type' => 'text', 'required' => 0],
        'settings__authenticate' => ['label' => 'Authenticate', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'settings__auto_footer' => ['label' => 'Auto footer', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'settings__inline_css' => ['label' => 'Inline css', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'settings__auto_tweet' => ['label' => 'Auto tweet', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'settings__fb_comments' => ['label' => 'Fb comments', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'variate_settings__winner_criteria' => ['label' => 'Winning Criteria', 'type' => 'text', 'required' => 0, 'option' => ['opens' => 'opens', 'clicks' => 'clicks', 'manual' => 'manual', 'total_revenue' => 'total_revenue']],
        'variate_settings__wait_time' => ['label' => 'Wait_time', 'type' => 'text', 'required' => 0],
        'variate_settings__test_size' => ['label' => 'Test size', 'type' => 'text', 'required' => 0],
        'variate_settings__test_size' => ['label' => 'Test size', 'type' => 'text', 'required' => 0],
        'tracking__opens' => ['label' => 'Opens', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'tracking__html_clicks' => ['label' => 'HTML Click Tracking', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'tracking__text_clicks' => ['label' => 'Plain-Text Click Tracking', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'tracking__goal_tracking' => ['label' => 'MailChimp Goal Tracking', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'tracking__ecomm360' => ['label' => 'E-commerce Tracking', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'tracking__google_analytics' => ['label' => 'Google Analytics Tracking', 'type' => 'text', 'required' => 0],
        'tracking__clicktale' => ['label' => 'ClickTale Analytics Tracking', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'rss_opts__feed_url' => ['label' => 'Feed URL', 'type' => 'text', 'required' => 0],
        'rss_opts__frequency' => ['label' => 'Frequency', 'type' => 'text', 'required' => 0, 'option' => ['daily' => 'daily', 'weekly' => 'weekly', 'monthly' => 'monthly']],
        'rss_opts__constrain_rss_img' => ['label' => 'Constrain RSS Images', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'social_card__title' => ['label' => 'Social card title', 'type' => 'text', 'required' => 0],
        'social_card__description' => ['label' => 'Social card description', 'type' => TextType::class, 'required' => 0],
        'social_card__image_url' => ['label' => 'Social card image_url', 'type' => 'text', 'required' => 0],
        'type' => ['label' => 'Campaign Type', 'type' => 'text', 'required' => 1, 'option' => ['regular' => 'regular', 'plaintext' => 'plaintext', 'absplit' => 'absplit', 'rss' => 'rss', 'variate' => 'variate']],
        'recipients__list_id' => ['label' => 'List ID', 'type' => 'text', 'required' => 0, 'required_relationship' => 0, 'relate' => true],
    ],
    'members' => [
        'email_address' => ['label' => 'Email address', 'type' => 'text', 'required' => 1],
        'email_type' => ['label' => 'Email Type', 'type' => 'text', 'required' => 0, 'option' => ['text' => 'text', 'html' => 'html']],
        'status' => ['label' => 'Status', 'type' => 'text', 'required' => 1, 'option' => ['pending' => 'pending', 'subscribed' => 'subscribed', 'unsubscribed' => 'unsubscribed', 'cleaned' => 'cleaned']],
        'language' => ['label' => 'Language', 'type' => 'text', 'required' => 0],
        'vip' => ['label' => 'VIP', 'type' => 'bool', 'required' => 0, 'option' => ['1' => 'True', '2' => 'False']],
        'location__latitude' => ['label' => 'Latitude', 'type' => 'text', 'required' => 0],
        'location__longitude' => ['label' => 'Longitude', 'type' => 'text', 'required' => 0],
        'ip_signup' => ['label' => 'Ip signup', 'type' => 'text', 'required' => 0],
        'timestamp_signup' => ['Timestamp signup' => 'Email', 'type' => 'text', 'required' => 0],
        'ip_opt' => ['label' => 'Ip opt', 'type' => 'text', 'required' => 0],
        'timestamp_opt' => ['label' => 'Timestamp opt', 'type' => 'text', 'required' => 0],
        'merge_fields__FNAME' => ['label' => 'MERGE0', 'type' => 'text', 'required' => 0],
        'merge_fields__LNAME' => ['label' => 'First name', 'type' => 'text', 'required' => 0],
        'list_id' => ['label' => 'List ID', 'type' => 'text', 'required' => 0, 'required_relationship' => 1, 'relate' => true],
    ],
];
