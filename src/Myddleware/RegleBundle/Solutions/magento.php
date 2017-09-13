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

namespace Myddleware\RegleBundle\Solutions;

use Symfony\Bridge\Monolog\Logger;
	
class magentocore extends solution {
	
	protected $token;
	
	// Tableau de correspondance Module / ID pour les modules qui n'ont pas d'id de type "nommodule"."id"
	protected $idByModule = array(
							'customers' => 'id',
							'customer_address' => 'id',
							'orders' => 'entity_id',
							);
	
	protected $FieldsDuplicate = array(
										'customers' => array('email'),
									  );
	
	protected $required_fields = array('default' => array('updated_at'));
	
	// Liste des param�tres de connexion
	public function getFieldsLogin() {	
        return array(
                    array(
                            'name' => 'url',
                            'type' => 'text',
                            'label' => 'solution.fields.url'
                        ),
                    array(
                            'name' => 'login',
                            'type' => 'text',
                            'label' => 'solution.fields.login'
                        ), 
                     array(
                            'name' => 'password',
                            'type' => 'password',
                            'label' => 'solution.fields.password'
                        ), 
        );
	} // getFieldsLogin()		
	
	// Connexion à Magento
    public function login($paramConnexion) {
		parent::login($paramConnexion);
		try{			
			$userData = array("username" => $this->paramConnexion['login'], "password" => $this->paramConnexion['password']);
			$result = $this->call($this->paramConnexion['url'].'/index.php/rest/V1/integration/admin/token', $method = 'POST', $userData);	
			if (!empty($result['message'])) {
				throw new \Exception($result['message']);
			}
			elseif (!empty($result)) {
				$this->token = $result;
			}
			else {
				throw new \Exception('No token found. ');
			}	
			$this->connexion_valide = true;	
		}
		catch (\Exception $e) {
			$error = 'Failed to login to Magento : '.$e->getMessage();
			echo $error . ';';
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)*/


		
	public function get_modules($type = 'source') {
		if ($type == 'source') {
			return array(	
						'customers' => 'Customers',
						'customer_address' => 'Customer Address',
						'orders' => 'Sales Order',
					);
		}
		else {
			return array(	
						'customers' => 'Customers',
					);
		}		
	} // get_modules()	
	
	
		// Renvoie les champs du module passé en paramètre
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			// Pour chaque module, traitement différent
			switch ($module) {
				case 'customers':
					$this->moduleFields = array(
						'id' => array('label' => 'ID customer', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'created_at' => array('label' => 'Created_at', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'updated_at' => array('label' => 'Updated at', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'created_in' => array('label' => 'Created in', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'email' => array('label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'firstname' => array('label' => 'First name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'middlename' => array('label' => 'Middle name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'lastname' => array('label' => 'Last name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'prefix' => array('label' => 'Prefix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'suffix' => array('label' => 'Suffix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'dob' => array('label' => 'Birthdate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'taxvat' => array('label' => 'Taxvat value', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'confirmation' => array('label' => 'Confirmation flag', 'type' => 'text', 'type_bdd' => 'text', 'required' => 0),
					);
					$this->fieldsRelate = array(
						'increment_id' => array('label' => 'Increment ID.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						'store_id' => array('label' => 'Store ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						'website_id' => array('label' => 'Website ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						'group_id' => array('label' => 'Group ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						'default_shipping' => array('label' => 'Default shipping address id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
						'default_billing' => array('label' => 'Default billing address id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0)
					);
					break;
				case 'customer_address':
					$this->moduleFields = array(
						'id' => array('label' => 'ID address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'created_at' => array('label' => 'Created_at', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'updated_at' => array('label' => 'Updated at', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'increment_id' => array('label' => 'Increment ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'city' => array('label' => 'City', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'company' => array('label' => 'Name of the company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'country_id' => array('label' => 'ID of the country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'fax' => array('label' => 'Fax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'firstname' => array('label' => 'Customer first name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'lastname' => array('label' => 'Customer last name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'middlename' => array('label' => 'Customer middle name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'postcode' => array('label' => 'Customer postcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'prefix' => array('label' => 'Customer prefix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'region__region' => array('label' => 'Region name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'region__region_code' => array('label' => 'Region code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'region__region_id' => array('label' => 'Region ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'street__0' => array('label' => 'Street 1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'street__1' => array('label' => 'Street 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'suffix' => array('label' => 'Customer suffix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'telephone' => array('label' => 'Telephone number', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'is_default_billing' => array('label' => 'True if the address is the default one for billing', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'is_default_shipping' => array('label' => 'True if the address is the default one for shipping', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					);
					$this->fieldsRelate = array(
						'customer_id' => array('label' => 'Customer ID.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
					);
					try {
						// Get list of countries
						$countries = $this->call($this->paramConnexion['url'].'/index.php/rest/V1/directory/countries', 'GET');				
						foreach ($countries as $country) {
							$this->moduleFields['country_id']['option'][$country['id']] = $country['full_name_locale'];
						}
					}
					catch (\Exception $e){
						// We don't bloc the program if the ws for countries didn't work
					} 
					break;
				case 'orders':
					$this->moduleFields = array(
						'id' => array('label' => 'ID customer', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'parent_id' => array('label' => 'Parent ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'increment_id' => array('label' => 'Increment ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'store_id' => array('label' => 'Store ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'created_at' => array('label' => 'Date of creation', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'updated_at' => array('label' => 'Date of updating', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'is_active' => array('label' => 'Defines whether the order is active', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'tax_amount' => array('label' => 'Tax amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'shipping_amount' => array('label' => 'Shipping amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'discount_amount' => array('label' => 'Discount amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'subtotal' => array('label' => 'Subtotal sum', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'grand_total' => array('label' => 'Grand total sum', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'total_paid' => array('label' => 'Total paid', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'total_refunded' => array('label' => 'Total refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'total_qty_ordered' => array('label' => 'Total quantity ordered', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'total_canceled' => array('label' => 'Total canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'total_invoiced' => array('label' => 'Total invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'total_online_refunded' => array('label' => 'Total online refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'total_offline_refunded' => array('label' => 'Total offline refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_tax_amount' => array('label' => 'Base tax amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_shipping_amount' => array('label' => 'Base shipping amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_discount_amount' => array('label' => 'Base discount amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_subtotal' => array('label' => 'Base subtotal', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_grand_total' => array('label' => 'Base grand total', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_total_paid' => array('label' => 'Base total paid', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_total_refunded' => array('label' => 'Base total refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_total_qty_ordered' => array('label' => 'Base total quantity ordered', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_total_canceled' => array('label' => 'Base total canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_total_invoiced' => array('label' => 'Base total invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_total_online_refunded' => array('label' => 'Base total online refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_total_offline_refunded' => array('label' => 'Base total offline refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'billing_address_id' => array('label' => 'Billing address ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'billing_firstname' => array('label' => 'First name in the billing address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'billing_lastname' => array('label' => 'Last name in the billing address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'shipping_address_id' => array('label' => 'Shipping address ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'shipping_firstname' => array('label' => 'First name in the shipping address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'shipping_lastname' => array('label' => 'Last name in the shipping address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'billing_name' => array('label' => 'Billing name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'shipping_name' => array('label' => 'Shipping name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'store_to_base_rate' => array('label' => 'Store to base rate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'store_to_order_rate' => array('label' => 'Store to order rate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_to_global_rate' => array('label' => 'Base to global rate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_to_order_rate' => array('label' => 'Base to order rate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'weight' => array('label' => 'Weight', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'store_name' => array('label' => 'Store name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'remote_ip' => array('label' => 'Remote IP', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'status' => array('label' => 'Order status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'state' => array('label' => 'Order state', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'applied_rule_ids' => array('label' => 'Applied rule IDs', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'global_currency_code' => array('label' => 'Global currency code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'base_currency_code' => array('label' => 'Base currency code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'store_currency_code' => array('label' => 'Store currency code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'order_currency_code' => array('label' => 'Order currency code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'shipping_method' => array('label' => 'Shipping method', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'shipping_description' => array('label' => 'Shipping description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'customer_email' => array('label' => 'Email address of the customer', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'customer_firstname' => array('label' => 'Customer first name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'customer_lastname' => array('label' => 'Customer last name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'quote_id' => array('label' => 'Shopping cart ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'is_virtual' => array('label' => 'Defines whether the product is a virtual one', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'customer_group_id' => array('label' => 'Customer group ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'customer_note_notify' => array('label' => 'Customer notification', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'customer_is_guest' => array('label' => 'Defines whether the customer is a guest', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'email_sent' => array('label' => 'Defines whether the email notification is sent', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'entity_id' => array('label' => 'Entity ID (order ID)', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'gift_message_id' => array('label' => 'Gift message ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'gift_message' => array('label' => 'Gift message', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),			
					);
					$this->fieldsRelate = array(
						'customer_id' => array('label' => 'Customer ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					);
					break;
				default:
					throw new \Exception("Fields unreadable");
					break;
			}
			// Ajout des champ relate au mapping des champs 
			if (!empty($this->fieldsRelate)) {
				$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
			}
			
			// Add list here (field could exist in several fields or was part of a rrelate field)
			try {
				if (!empty($this->moduleFields['website_id'])) {
					// Get list of website
					$websites = $this->call($this->paramConnexion['url'].'/index.php/rest/V1/store/websites ', 'GET');						
					foreach ($websites as $website) {
						$this->moduleFields['website_id']['option'][$website['id']] = $website['name'];
					}
				}
			}
			catch (\Exception $e){
				// We don't bloc the program if the ws for countries didn't work
			} 
			
			return $this->moduleFields;
		}
		catch (\Exception $e){
			$error = $e->getMessage();
			return false;
		}
	} // get_module_fields($module)	 
	

	// Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
	public function read_last($param) {
		$result = array();	
		try {
			// Ajout du champ id, obligatoire mais spécifique au module
			if(!empty($this->idByModule[$param['module']])) { // Si le champ id existe dans le tableau
				$fieldId = $this->idByModule[$param['module']];
			}
			
			// Init parameters for modules or submodules
			$function = '';
			$subModule = '';
			switch ($param['module']) {
				case 'customers':
					$function = 'customers/search';
					// No search if id is in the query, we call a get with the id in parameter
					if (!empty($param['query']['id'])) {
						$function = 'customers';
					}
					break;
				case 'customer_address':
					$function = 'customers/search';
					$subModule = 'addresses';
					break;
				case 'orders':
					$function = 'orders';
					break;
				default:
					throw new \Exception('Module unknown. ');
					break;
			}
			
			// On va chercher le nom du champ pour la date de référence: Modification
			$dateRefField = $this->getDateRefName($param['module'], "0");

			$searchCriteria = '?';
			$get = '';
			// could be empty when simulation in Myddleware
			if (!empty($param['query'])) {
				foreach ($param['query'] as $key => $value) {
					// No search if id is in the query, we call a get with the id in parameter
					if ($key == 'id') {
						$get = '/'.$value;
						break;
					}
					if (!empty($searchCriteria)) {
						$searchCriteria .= '&';
					}
					$searchCriteria .= 'searchCriteria[filter_groups][0][filters][0][field]='.$key.'&searchCriteria[filter_groups][0][filters][0][value]='.$value.'&searchCriteria[filter_groups][0][filters][0][condition_type]=eq';
				}
			}
			if (!empty($searchCriteria)) {
				$searchCriteria .= '&';
			}
			$searchCriteria .= 'searchCriteria[pageSize]=1&searchCriteria[sortOrders][0][field]='.$dateRefField.'&searchCriteria[sortOrders][0][direction]=ASC'; 
	
			// Call to Magento, get is the priority otherwise we use searchCriteria
			$resultList = $this->call($this->paramConnexion['url'].'/index.php/rest/V1/'.$function.(!empty($get) ? $get : $searchCriteria), 'GET');
			if (!empty($resultList['message'])) {
				throw new \Exception($resultList['message'].(!empty($resultList['parameters']) ? ' parameters : '.print_r($resultList['parameters'],true) : ''));
			}
			
			// The respon of Magento when there is a simple get and when there is searchcriteria are differents.
			if(!empty($resultList['items'][0])) {
				$resultList = $resultList['items'][0];
			}
	
			// Traitement des résultats
			if(!empty($resultList)) {
				// if submodule, example addresses in the module customer
				if (!empty($subModule)) {
					if (!empty($resultList[$subModule])) {
						$subRecords = $resultList['items'][0][$subModule];
						// date ref is taking from the main module
						$result['values']['date_modified'] = $resultList[$dateRefField];				
					 }
					 else {
						$result['done'] = false;
						return $result;
					 }
				}
				// Change format to be always compatible, submodule or not
				else {
					$subRecords[0] = $resultList;
				}

				// remove one dimension by replacing the dimension by __
				$subRecords[0] = $this->removeDimension($subRecords[0]);
						
				foreach ($subRecords[0]  as $key => $value) {
					if ($key == $fieldId) {
						$result['values']['id'] = $value; 
					}
					// If test if the field exist because Magento doens't return empty field
					if(in_array($key, $param['fields'])) {
						$result['values'][$key] = $value;
					}
				}
				$result['done'] = true;
			}
			else {
				$result['done'] = false;
			}				
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;			
		}		
		return $result;
	} // read_last($param)
		
	// Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
	public function read($param) {
		$result = array();	
		try {
			// Ajout du champ id, obligatoire mais spécifique au module
			if(!empty($this->idByModule[$param['module']])) { // Si le champ id existe dans le tableau
				$fieldId = $this->idByModule[$param['module']];
			}
			
			// Add requiered fields 
			$param['fields'] = $this->addRequiredField($param['fields']);
	
			// Init parameters for modules or submodules
			$function = '';
			$subModule = '';
			switch ($param['module']) {
				case 'customers':
					$function = 'customers/search';
					break;
				case 'customer_address':
					$function = 'customers/search';
					$subModule = 'addresses';
					break;
				case 'orders':
					$function = 'orders';
					break;
				default:
					throw new \Exception('Module unknown. ');
					break;
			}
		
			// On va chercher le nom du champ pour la date de référence: Création ou Modification
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);

			// Get all data after the reference date
			$searchCriteria = 'searchCriteria[filter_groups][0][filters][0][field]='.$dateRefField.'&searchCriteria[filter_groups][0][filters][0][value]='.urlencode($param['date_ref']).'&searchCriteria[filter_groups][0][filters][0][condition_type]=gt';
			// order by type de reference date
			$searchCriteria .= '&searchCriteria[sortOrders][0][field]='.$dateRefField.'&searchCriteria[sortOrders][0][direction]=ASC'; 
			$searchCriteria .= 'searchCriteria[sortOrders][0][field]='.$dateRefField.'&searchCriteria[sortOrders][0][direction]=ASC'; 
			
			// Call to Magento
			$resultList = $this->call($this->paramConnexion['url'].'/index.php/rest/V1/'.$function.'?'.$searchCriteria, 'GET');
			if (!empty($resultList['message'])) {
				throw new \Exception($resultList['message'].(!empty($resultList['parameters']) ? ' parameters : '.print_r($resultList['parameters'],true) : ''));
			}

			// Traitement des résultats			
			if(!empty($resultList['items'])) {
				$cpt = 0;				
				foreach ($resultList['items'] as $record) {					
					// if submodule, example addresses in the module customer
					if (!empty($subModule)) {
						if (!empty($record[$subModule])) {
							foreach ($record[$subModule] as $subRecord) {
								// date ref is taking from the main module for each submodules
								$subRecord[$dateRefField] = $record[$dateRefField];
								$subRecords[] = $subRecord;
							}			
						 }
						 // if no submodule, we read the next record
						 else {
							continue;
						 }
					}
					// Change format to be always compatible, submodule or not
					else {
						$subRecords[0] = $record;
					}

					// remove one dimension by replacing the dimension by __
					foreach ($subRecords as $subRecord) {
						$subRecordsNoDimension[] = $this->removeDimension($subRecord);
					}
						
					// Transform data from Magento to create document in Myddleware
					if (!empty($subRecordsNoDimension)) {
						foreach ($subRecordsNoDimension as $subRecordNoDimension) {
							$row = array();							
							// Ajout de l'ID, $fieldId vaut "customer_id" pour le module "customer" par exemple
							if (!empty($subRecordNoDimension[$fieldId])) {
								$row['id'] = $subRecordNoDimension[$fieldId]; 
							}
							else {
								throw new \Exception('Failed to find an Id for a record.');
							}
							foreach ($subRecordNoDimension as $key => $value) {
								if ($key == $dateRefField) {
									$row['date_modified'] = $value;
									// Sauvegarde de la date de référence
									if (	
											empty($result['date_ref'])
										 || $value > $result['date_ref']
									) {
										$result['date_ref'] = $value;
									}
								}
								// Magento doens't return empty field
								if(in_array($key, $param['fields'])) {
									$row[$key] = $value;
								}
							}
							$result['values'][$row['id']] = $row;
							$cpt++;
							$result['count'] = $cpt;
						}
					}
				}
			}		
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';				
		}
		return $result;	
	} // read($param)
	
	// Permet de créer un enregistrement
	public function create($param) {
		// Initialisation de paramètre en fonction du module
		switch ($param['module']) {
			case 'customers':
				$keyParameters = 'customer';
				break;
		}
			
		// Transformation du tableau d'entrée pour être compatible webservice Magento
		foreach($param['data'] as $idDoc => $data) {
			try {		
				// Check control before update
				$data = $this->checkDataBeforeCreate($param, $data);
				$dataMagento = array();
				foreach ($data as $key => $value) {				
					// Target id isn't a field for Magento (it is used for Myddleware)
					if ($key == 'target_id') {
						continue;
					}
					$dataMagento[$key] = $value;
				}
				// Add a dimension for Magento call
				if (!empty($keyParameters)) {
					$dataMagentoTmp = $dataMagento;
					unset($dataMagento);
					$dataMagento[$keyParameters] = $dataMagentoTmp;
				}
				$resultList = $this->call($this->paramConnexion['url'].'/index.php/rest/V1/'.$param['module'], 'POST', $dataMagento);
				
				if (!empty($resultList['message'])) {
					throw new \Exception($resultList['message'].(!empty($resultList['parameters']) ? ' parameters : '.print_r($resultList['parameters'],true) : ''));
				}				
				if (!empty($resultList['id'])) {
					$result[$idDoc] = array(
											'id' => $resultList['id'],
											'error' => false
									);
				}
				else  {
					$result[$idDoc] = array(
											'id' => '-1',
											'error' => '01'
									);
				} 
			}
			catch (\Exception $e) {
				$error = $e->getMessage();
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}
		return $result;			
	}	
	// Permet de mettre à jour un enregistrement
	public function update($param) {
		// Initialisation de paramètre en fonction du module
		switch ($param['module']) {
			case 'customers':
				$keyParameters = 'customer';
				break;
		}
			
		// Transformation du tableau d'entrée pour être compatible webservice Magento
		foreach($param['data'] as $idDoc => $data) {
			try {		
				// Check control before update
				$data = $this->checkDataBeforeUpdate($param, $data);
				$target_id = '';
				$dataMagento = array();
				foreach ($data as $key => $value) {				
					// Important de renommer le champ id pour que SuiteCRM puisse effectuer une modification et non une création
					if ($key == 'target_id') {
						$target_id = $value;
						continue;
					}
					$dataMagento[$key] = $value;
				}
				if (empty($target_id)) {
					throw new \Exception('Failed to update the record. No ID found for the record in the data transfer. ');
				}
				// Add a dimension for Magento call
				if (!empty($keyParameters)) {
					$dataMagentoTmp = $dataMagento;
					unset($dataMagento);
					$dataMagento[$keyParameters] = $dataMagentoTmp;
				}
				$resultList = $this->call($this->paramConnexion['url'].'/index.php/rest/V1/'.$param['module'].'/'.$target_id, 'PUT', $dataMagento);
				
				if (!empty($resultList['message'])) {
					throw new \Exception($resultList['message'].(!empty($resultList['parameters']) ? ' parameters : '.print_r($resultList['parameters'],true) : ''));
				}				
				if (!empty($resultList['id'])) {
					$result[$idDoc] = array(
											'id' => $target_id,
											'error' => false
									);
				}
				else  {
					$result[$idDoc] = array(
											'id' => '-1',
											'error' => '01'
									);
				} 
			}
			catch (\Exception $e) {
				$error = $e->getMessage();
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}
		return $result;			
	}
	
	// remove one dimension by replacing the dimension by __
	protected function removeDimension($subRecords) {
		foreach ($subRecords as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $subKey => $subValue) {
					$result[$key.'__'.$subKey] = $subValue;
				}
			}
			else {
				$result[$key] = $value;
			}
		}
		return $result;
	}
		
	
	// For module address, we only update data
	// public function getRuleMode($module,$type) {
		// if (
				// $type == 'source'
			// AND $module == 'customer_address'
		// ) {
			// return array(
				// 'U' => 'update_only',
			// );
		// }
		// return parent::getRuleMode($module,$type);
	// }
	
	
	// Renvoie le nom du champ de la date de référence en fonction du module et du mode de la règle
	public function getDateRefName($moduleSource, $RuleMode) {
		if(in_array($RuleMode, array('0'))) {
			return "updated_at";
		} else if ($RuleMode == "C"){
			return "created_at";
		} else {
			throw new \Exception ("$RuleMode is not a correct Rule mode.");
		}
		return null;
	}
	
	 /**
     * Performs the underlying HTTP request. Not very exciting
     * @param  string $method The API method to be called
     * @param  array  $args   Assoc array of parameters to be passed
     * @return array          Assoc array of decoded result
     */   
    protected function call($url, $method = 'GET', $args=array(), $timeout = 10){   
		if (function_exists('curl_init') && function_exists('curl_setopt')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method );
			$headers = array();
			$headers[] = "Content-Type: application/json";
			if (!empty($this->token)) {	
				$headers[] = "Authorization: Bearer ".$this->token;
			}
			if (!empty($args)) {
				$jsonArgs = json_encode($args);
				$headers[] = "Content-Lenght: ".$jsonArgs;
				curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonArgs);			
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
            $result = curl_exec($ch);
            curl_close($ch);            
            return $result ? json_decode($result, true) : false;
        }
        throw new \Exception('curl extension is missing!');
    }	
	 
}// class magentocore

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/magento.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class magento extends magentocore {
	}// class magentocore
}