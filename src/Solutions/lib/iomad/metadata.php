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
// Get Moodle metadata
include '../moodle/metadata.php';

// Add IOMAD metadata
$moduleFields = [
    'get_companies' => [
        'id' => ['label' => 'Companid ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'name' => ['label' => 'Company long name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'shortname' => ['label' => 'Compay short name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'code' => ['label' => 'Compay code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'address' => ['label' => 'Company location address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'city' => ['label' => 'Company location city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'region' => ['label' => 'Company location region', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'country' => ['label' => 'Company location country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'maildisplay' => ['label' => 'User default email display', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'mailformat' => ['label' => 'User default email format', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'maildigest' => ['label' => 'User default digest type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'autosubscribe' => ['label' => 'User default forum auto-subscribe', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'trackforums' => ['label' => 'User default forum tracking', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'htmleditor' => ['label' => 'User default text editor', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'screenreader' => ['label' => 'User default screen reader', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'timezone' => ['label' => 'User default timezone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'lang' => ['label' => 'User default language', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'suspended' => ['label' => 'Company is suspended when <> 0', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'ecommerce' => ['label' => 'Ecommerce is disabled when = 0', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'parentid' => ['label' => 'ID of parent company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'customcss' => ['label' => 'Company custom css', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'validto' => ['label' => 'Contract termination date in unix timestamp', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'suspendafter' => ['label' => 'Number of seconds after termination date to suspend the company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'companyterminated' => ['label' => 'Company contract is terminated when <> 0', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'theme' => ['label' => 'Company theme', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'hostname' => ['label' => 'Company hostname', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'maxusers' => ['label' => 'Company maximum number of users', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'maincolor' => ['label' => 'Company main color', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'headingcolor' => ['label' => 'Company heading color', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'linkcolor' => ['label' => 'Company ink color', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'custom1' => ['label' => 'Company custom 1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'custom2' => ['label' => 'Company custom 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'custom3' => ['label' => 'Company custom 3', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
    ],
];
