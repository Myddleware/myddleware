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
    'customers' => [
		'id' => ['label' => 'Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
		'displayName' => ['label' => 'displayName', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'number' => ['label' => 'number', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'type' => ['label' => 'type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'addressLine1' => ['label' => 'addressLine1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'addressLine2' => ['label' => 'addressLine2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'city' => ['label' => 'city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'state' => ['label' => 'state', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'country' => ['label' => 'country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'postalCode' => ['label' => 'postalCode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'phoneNumber' => ['label' => 'phoneNumber', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'email' => ['label' => 'email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'website' => ['label' => 'website', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'salespersonCode' => ['label' => 'salespersonCode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'balanceDue' => ['label' => 'balanceDue', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'creditLimit' => ['label' => 'creditLimit', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'taxLiable' => ['label' => 'taxLiable', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'taxAreaId' => ['label' => 'taxAreaId', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'taxAreaDisplayName' => ['label' => 'taxAreaDisplayName', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'taxRegistrationNumber' => ['label' => 'taxRegistrationNumber', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'currencyId' => ['label' => 'currencyId', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'currencyCode' => ['label' => 'currencyCode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'paymentTermsId' => ['label' => 'paymentTermsId', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'shipmentMethodId' => ['label' => 'shipmentMethodId', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'paymentMethodId' => ['label' => 'paymentMethodId', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'blocked' => ['label' => 'blocked', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
        'lastModifiedDateTime' => ['label' => 'lastModifiedDateTime', 'type' => 'datetime', 'type_bdd' => 'datetime', 'required' => 0],
    ]
];
