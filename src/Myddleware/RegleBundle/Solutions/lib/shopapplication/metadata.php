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
					'customers' =>
						array(
							'id' => array('label' => 'ID customer', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'gender' => array('label' => 'Gender', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('m' => 'Homme', 'f' => 'Femme')),
							'first_name' => array('label' => 'First name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
							'last_name' => array('label' => 'Last_name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
							'email' => array('label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
							'birth_date' => array('label' => 'Birth date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'phone' => array('label' => 'Phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'fax' => array('label' => 'Fax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'discount' => array('label' => 'Discount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'credit' => array('label' => 'Credit', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'credit_expiration_date' => array('label' => 'Credit expiration date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'reward_points' => array('label' => 'Reward points', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'password' => array('label' => 'Password', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'date_created' => array('label' => 'Date created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'date_modified' => array('label' => 'Date modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'auto_connect_url' => array('label' => 'Auto connect url', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'photo' => array('label' => 'Photo', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
						),
					'orders' =>
						array(
							'id' => array('label' => 'ID order', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'language' => array('label' => 'Language', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('m' => 'Homme', 'f' => 'Femme')),
							'status' => array('label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
							'notes' => array('label' => 'Notes', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'date_created' => array('label' => 'Date created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'date_modified' => array('label' => 'Date modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'date_finished' => array('label' => 'Date finished', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'referer' => array('label' => 'Referer', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'currency' => array('label' => 'Currency', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'shipping__tracking_code' => array('label' => 'Shipping tracking code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'shipping__tracking_url' => array('label' => 'Shipping tracking url', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'shipping__method_code' => array('label' => 'Shipping method code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'shipping__method_name' => array('label' => 'Shipping method name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'payment__method_name' => array('label' => 'Payment method name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'payment__method_code' => array('label' => 'Payment method code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'customer_info__name' => array('label' => 'Customer name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'customer_info__company' => array('label' => 'Customer company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'customer_info__city' => array('label' => 'Customer city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'customer_info__street_address' => array('label' => 'Customer street_address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'customer_info__suburb' => array('label' => 'Customer suburb', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'customer_info__doorcode' => array('label' => 'Customer doorcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'customer_info__floor' => array('label' => 'Customer floor', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'customer_info__postcode' => array('label' => 'Customer postcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'customer_info__state' => array('label' => 'Customer state', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'customer_info__country' => array('label' => 'Customer country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'customer_info__phone' => array('label' => 'Customer phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'customer_info__email' => array('label' => 'Customer email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'customer_info__ip' => array('label' => 'Customer IP', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'delivery_address__gender' => array('label' => 'Delivery address gender', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('m' => 'Homme', 'f' => 'Femme')),
							'delivery_address__name' => array('label' => 'Delivery address name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'delivery_address__company' => array('label' => 'Delivery address company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'delivery_address__phone' => array('label' => 'Delivery address phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'delivery_address__street_address' => array('label' => 'Delivery address street_address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'delivery_address__suburb' => array('label' => 'Delivery address suburb', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'delivery_address__doorcode' => array('label' => 'Delivery address doorcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'delivery_address__floor' => array('label' => 'Delivery address floor', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'delivery_address__city' => array('label' => 'Delivery address city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'delivery_address__postcode' => array('label' => 'Delivery address postcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'delivery_address__state' => array('label' => 'Delivery address state', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'delivery_address__country' => array('label' => 'Delivery address country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'delivery_address__country_code' => array('label' => 'Delivery address country code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),							
							'billing_address__gender' => array('label' => 'Billing address gender', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('m' => 'Homme', 'f' => 'Femme')),
							'billing_address__name' => array('label' => 'Billing address name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'billing_address__company' => array('label' => 'Billing address company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'billing_address__phone' => array('label' => 'Billing address phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'billing_address__street_address' => array('label' => 'Billing address street_address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'billing_address__suburb' => array('label' => 'Billing address suburb', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'billing_address__doorcode' => array('label' => 'Billing address doorcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'billing_address__floor' => array('label' => 'Billing address floor', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'billing_address__city' => array('label' => 'Billing address city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'billing_address__postcode' => array('label' => 'Billing address postcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'billing_address__state' => array('label' => 'Billing address state', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'billing_address__country' => array('label' => 'Billing address country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'billing_address__country_code' => array('label' => 'Billing address country code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'origin__mobile' => array('label' => 'Origin mobile', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'origin__cash_register' => array('label' => 'Origin cash register', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'origin__back_office' => array('label' => 'Origin back office', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'origin__api' => array('label' => 'Origin api', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						),	
					'orders_delivery_address' =>
						array(
							'gender' => array('label' => 'Delivery address gender', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('m' => 'Homme', 'f' => 'Femme')),
							'name' => array('label' => 'Delivery address name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'company' => array('label' => 'Delivery address company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'phone' => array('label' => 'Delivery address phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'street_address' => array('label' => 'Delivery address street_address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'suburb' => array('label' => 'Delivery address suburb', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'doorcode' => array('label' => 'Delivery address doorcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'floor' => array('label' => 'Delivery address floor', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'city' => array('label' => 'Delivery address city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'postcode' => array('label' => 'Delivery address postcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'state' => array('label' => 'Delivery address state', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'country' => array('label' => 'Delivery address country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'country_code' => array('label' => 'Delivery address country code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),							
						),		
					'orders_billing_address' =>
						array(
							'gender' => array('label' => 'Delivery address gender', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('m' => 'Homme', 'f' => 'Femme')),
							'name' => array('label' => 'Delivery address name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'company' => array('label' => 'Delivery address company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'phone' => array('label' => 'Delivery address phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'street_address' => array('label' => 'Delivery address street_address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'suburb' => array('label' => 'Delivery address suburb', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'doorcode' => array('label' => 'Delivery address doorcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'floor' => array('label' => 'Delivery address floor', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'city' => array('label' => 'Delivery address city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'postcode' => array('label' => 'Delivery address postcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'state' => array('label' => 'Delivery address state', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'country' => array('label' => 'Delivery address country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'country_code' => array('label' => 'Delivery address country code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),								
						),		
					'orders_products' =>
						array(
							'id' => array('label' => 'ID order product', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'reference' => array('label' => 'Reference', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'stock_reference' => array('label' => 'Stock reference', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'name' => array('label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'quantity' => array('label' => 'Quantity', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'cost' => array('label' => 'Cost', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'final_price' => array('label' => 'Final price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'tax_rate' => array('label' => 'Tax rate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'date_added' => array('label' => 'Date added', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'weight' => array('label' => 'Weight', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'brand_name' => array('label' => 'Brand name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						),							
					'customers_addresses' =>
						array(
							'address_id' => array('label' => 'ID address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_company' => array('label' => 'Company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_vat_number' => array('label' => 'Vat_number', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_gender' => array('label' => 'Gender', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('m' => 'Homme', 'f' => 'Femme')),
							'address_first_name' => array('label' => 'First name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_last_name' => array('label' => 'Last_name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
							'address_phone' => array('label' => 'Phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_street' => array('label' => 'Street', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_suburb' => array('label' => 'Suburb', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_doorcode' => array('label' => 'Door code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_floor' => array('label' => 'Floor', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_postcode' => array('label' => 'Postcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_city' => array('label' => 'City', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_state' => array('label' => 'State', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_department' => array('label' => 'Department', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_region' => array('label' => 'Region', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_latitude' => array('label' => 'Latitude', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'address_longitude' => array('label' => 'Longitude', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'country_id' => array('label' => 'Country ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
						),
					'products' =>
						array(
							'id' => array('label' => 'ID product', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'reference' => array('label' => 'Reference', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'ean' => array('label' => 'stock_ean', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'isbn' => array('label' => 'Isbn', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'status' => array('label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('0' => 'Désactivé', '1' => 'Activé')),
							'guarantee' => array('label' => 'Guarantee', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'default_cost' => array('label' => 'Default cost', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'price' => array('label' => 'Price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'price_recommended' => array('label' => 'Price recommended', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'date_added' => array('label' => 'Date added', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'date_modified' => array('label' => 'Date modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'date_available' => array('label' => 'Date available', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'shipping_delay' => array('label' => 'Shipping delay', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'tax_rate' => array('label' => 'Tax rate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'cart_min_quantity' => array('label' => 'Cart min quantity', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'cart_max_quantity' => array('label' => 'Cart max quantity', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'image' => array('label' => 'Image', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'image_2' => array('label' => 'Image 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'image_3' => array('label' => 'Image 3', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'image_4' => array('label' => 'Image 4', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'image_5' => array('label' => 'Image 5', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'image_6' => array('label' => 'Image 6', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'image_7' => array('label' => 'Image 7', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'image_8' => array('label' => 'Image 8', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'image_9' => array('label' => 'Image 9', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'image_10' => array('label' => 'Image 10', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'stock_total_quantity' => array('label' => 'Stock total quantity', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'weight' => array('label' => 'Weight', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'volume' => array('label' => 'Volume', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'height' => array('label' => 'Height', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'width' => array('label' => 'Width', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'length' => array('label' => 'Length', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'reward_points' => array('label' => 'Reward points', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'disable_stock' => array('label' => 'Disable stock', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'template' => array('label' => 'Template', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'sort_order' => array('label' => 'Sort order', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__name' => array('label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__short_description' => array('label' => 'Short description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__description' => array('label' => 'Description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__description_title' => array('label' => 'Description title', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__description2' => array('label' => 'Description 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__description_title2' => array('label' => 'Description title 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__description3' => array('label' => 'Description 3', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__description_title3' => array('label' => 'Description title 3', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__description4' => array('label' => 'Description 4', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__description_title4' => array('label' => 'Description title 4', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__head_title_tag' => array('label' => 'Head title tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__meta_description_tag' => array('label' => 'Meta description tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__meta_keywords_tag' => array('label' => 'Meta keywords tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__h1_tag' => array('label' => 'H1 tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__h2_tag' => array('label' => 'H2 tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__h3_tag' => array('label' => 'H3 tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__url_title' => array('label' => 'URL title', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__views' => array('label' => 'Views', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						),	
					'products_options' =>
						array(
							'attribute_sort_order' => array('label' => 'Attribute sort order', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'attribute_price' => array('label' => 'Attribute price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'attribute_price_prefix' => array('label' => 'Attribute price prefix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'attribute_photo' => array('label' => 'Attribute photo', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'attribute_weight' => array('label' => 'Attribute weight', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'attribute_volume' => array('label' => 'Attribute volume', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						),
					'options' =>
						array(
							'id' => array('label' => 'ID option', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__name' => array('label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__comment' => array('label' => 'Comment', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'type' => array('label' => 'Type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('0' => 'Liste déroulante', '1' => 'Champ Texte', '2' => 'Bouton Radio', '3' => 'Case à cocher', '4' => 'Zone de texte', '5' => 'Fichier à transférer', '6' => 'Icone')),
							'length' => array('label' => 'Length', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'required' => array('label' => 'Required', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('0' => 'Non', '1' => 'Oui')),
							'track_stock' => array('label' => 'ID option', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('0' => 'Non', '1' => 'Oui')),					
						),
					'options_values' =>
						array(
							'id' => array('label' => 'ID value', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__value_name' => array('label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__value_image' => array('label' => 'Image', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						),
					'products_stock' =>
						array(
							'stock_reference' => array('label' => 'Reference', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'stock_ean' => array('label' => 'EAN', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						),	
					'products_stock_options' =>
						array(
						),		
					'products_stock_entries' =>
						array(
							'stock_entry_date' => array('label' => 'Entry date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'stock_entry_quantity' => array('label' => 'Entry quantity', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'stock_entry_cost' => array('label' => 'Entry cost', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						),	
					'categories' =>
						array(
							'id' => array('label' => 'ID category', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'image' => array('label' => 'Image', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'status' => array('label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('0' => 'Désactivé', '1' => 'Activé')),
							'date_added' => array('label' => 'Date added', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'date_modified' => array('label' => 'Date modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'date_available' => array('label' => 'Date available', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'path' => array('label' => 'Path', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'sort_order' => array('label' => 'Sort order', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__name' => array('label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__description' => array('label' => 'description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__custom_content' => array('label' => 'Custom content', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__head_title_tag' => array('label' => 'Head title tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__meta_description_tag' => array('label' => 'Meta description tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__meta_keywords_tag' => array('label' => 'Meta keywords tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__h1_tag' => array('label' => 'H1 tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__h2_tag' => array('label' => 'H2 tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__h3_tag' => array('label' => 'H3 tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						),					
					'brands' =>
						array(
							'id' => array('label' => 'ID brand', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'image' => array('label' => 'Image', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'name' => array('label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'date_added' => array('label' => 'Date added', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'date_modified' => array('label' => 'Date modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__description' => array('label' => 'description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__meta_title_tag' => array('label' => 'Meta title tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__meta_description_tag' => array('label' => 'Meta description tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
							'multilangual__ISO__meta_keywords_tag' => array('label' => 'Meta keywords tag', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						)	
					);
		
$fieldsRelate = array (
					'customers' =>
						array(
							'store_id' => array('label' => 'Store ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'group_id' => array('label' => 'Group ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'default_address_id' => array('label' => 'Default address id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						),
					'customers_addresses' =>
						array(
							'customers_id' => array('label' => 'Customer ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						),
					'products' =>
						array(
							'pictogram_id' => array('label' => 'Pictogram ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'brand_id' => array('label' => 'Brand ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'category_id' => array('label' => 'Category ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'supplier_id' => array('label' => 'Supplier ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'option_id' => array('label' => 'Option ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'criterion_id' => array('label' => 'Criterion ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'stock_reference' => array('label' => 'Stock reference', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						),
					'categories' =>
						array(
							'parent_id' => array('label' => 'Parent ID (0 if no parent)', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'store_id' => array('label' => 'Store ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						),	
					'orders' =>
						array(
							'store_id' => array('label' => 'Store ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'customer_info__id' => array('label' => 'Customer ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'product_id' => array('label' => 'Product ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						),
					'orders_products' =>
						array(
							'order_id' => array('label' => 'Order ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						),
					'products_options' =>
						array(
							'product_id' => array('label' => 'Product ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'option_id' => array('label' => 'Option ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'option_value_id' => array('label' => 'Option value ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						),
					'products_stock_options' =>
						array(
							'stock_reference' => array('label' => 'Stock reference', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'option_id' => array('label' => 'Option ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
							'option_value_id' => array('label' => 'Option value ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						),
					'products_stock_entries' =>
						array(
							'stock_reference' => array('label' => 'Stock reference', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						),
					'products_stock' =>
						array(
							'product_id' => array('label' => 'Product ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						),
					'options_values' =>
						array(
							'option_id' => array('label' => 'Option ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						),		
					);
					