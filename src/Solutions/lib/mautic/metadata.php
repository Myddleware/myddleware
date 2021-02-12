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
    'segment' => [
        'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        // 'dateAdded' => array('label' => 'Date added', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        // 'createdBy' => array('label' => 'Created by', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        // 'createdByUser' => array('label' => 'Created by user', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        // 'dateModified' => array('label' => 'Date  modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        // 'modifiedBy' => array('label' => 'Modified by', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        // 'modifiedByUser' => array('label' => 'Modified by user', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        'name' => ['label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'alias' => ['label' => 'Alias', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'description' => ['label' => 'Description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'isPublished' => ['label' => 'Is published', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'isGlobal' => ['label' => 'Is global', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
    ],
    'companies__contact' => [
        'contact' => ['label' => 'Contact', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1, 'relate' => true],
        'company' => ['label' => 'Company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1, 'relate' => true],
    ],
    'segments__contacts' => [
        'contact' => ['label' => 'Contact', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1, 'relate' => true],
        'segment' => ['label' => 'Segment', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1, 'relate' => true],
    ],
];

// Metadata override if needed
$file = __DIR__.'/../../../Custom/Solutions/lib/mautic/metadata.php';
if (file_exists($file)) {
    require_once $file;
}
