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
					'leads' => array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'created_time' => array('label' => 'Created time', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'field_data' => array('label' => 'Data', 'type' => TextType::class, 'type_bdd' => 'text', 'required' => 0)
					)
				);
	

$fieldsRelate = array (
					'leads' => array()
				);


// Metadata override if needed
$file = __DIR__.'/../../../Custom/Solutions/lib/facebook/metadata.php';
if(file_exists($file)){
	require_once($file);
}						