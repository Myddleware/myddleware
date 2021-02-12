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

namespace App\Solutions;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class magentocore extends solution
{
    protected $token;

    // Tableau de correspondance Module / ID pour les modules qui n'ont pas d'id de type "nommodule"."id"
    protected $idByModule = [
        'customers' => 'id',
        'customer_address' => 'id',
        'orders' => 'entity_id',
    ];

    protected $FieldsDuplicate = [
        'customers' => ['email'],
    ];

    protected $required_fields = ['default' => ['updated_at']];

    // Liste des param�tres de connexion
    public function getFieldsLogin()
    {
        return [
            [
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
            [
                'name' => 'login',
                'type' => TextType::class,
                'label' => 'solution.fields.login',
            ],
            [
                'name' => 'password',
                'type' => PasswordType::class,
                'label' => 'solution.fields.password',
            ],
        ];
    }

    // getFieldsLogin()

    // Connexion à Magento
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            $userData = ['username' => $this->paramConnexion['login'], 'password' => $this->paramConnexion['password']];
            $result = $this->call($this->paramConnexion['url'].'/index.php/rest/V1/integration/admin/token', $method = 'POST', $userData);
            if (!empty($result['message'])) {
                throw new \Exception($result['message']);
            } elseif (!empty($result)) {
                $this->token = $result;
            } else {
                throw new \Exception('No token found. ');
            }
            $this->connexion_valide = true;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // login($paramConnexion)*/

    public function get_modules($type = 'source')
    {
        if ('source' == $type) {
            return [
                'customers' => 'Customers',
                'customer_address' => 'Customer Address',
                'orders' => 'Sales Order',
            ];
        }

        return [
            'customers' => 'Customers',
        ];
    }

    // get_modules()

    // Renvoie les champs du module passé en paramètre
    public function get_module_fields($module, $type = 'source')
    {
        parent::get_module_fields($module, $type);
        try {
            // Pour chaque module, traitement différent
            switch ($module) {
                case 'customers':
                    $this->moduleFields = [
                        'id' => ['label' => 'ID customer', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'created_at' => ['label' => 'Created_at', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'updated_at' => ['label' => 'Updated at', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'created_in' => ['label' => 'Created in', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'email' => ['label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'firstname' => ['label' => 'First name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'middlename' => ['label' => 'Middle name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'lastname' => ['label' => 'Last name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'prefix' => ['label' => 'Prefix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'suffix' => ['label' => 'Suffix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'dob' => ['label' => 'Birthdate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'taxvat' => ['label' => 'Taxvat value', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'confirmation' => ['label' => 'Confirmation flag', 'type' => TextType::class, 'type_bdd' => 'text', 'required' => 0],
                        'increment_id' => ['label' => 'Increment ID.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => false],
                        'store_id' => ['label' => 'Store ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => false],
                        'website_id' => ['label' => 'Website ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => false],
                        'group_id' => ['label' => 'Group ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => false],
                        'default_shipping' => ['label' => 'Default shipping address id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => false],
                        'default_billing' => ['label' => 'Default billing address id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => false],
                    ];
                    break;
                case 'customer_address':
                    $this->moduleFields = [
                        'id' => ['label' => 'ID address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'created_at' => ['label' => 'Created_at', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'updated_at' => ['label' => 'Updated at', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'increment_id' => ['label' => 'Increment ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'city' => ['label' => 'City', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'company' => ['label' => 'Name of the company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'country_id' => ['label' => 'ID of the country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'fax' => ['label' => 'Fax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'firstname' => ['label' => 'Customer first name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'lastname' => ['label' => 'Customer last name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'middlename' => ['label' => 'Customer middle name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'postcode' => ['label' => 'Customer postcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'prefix' => ['label' => 'Customer prefix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'region__region' => ['label' => 'Region name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'region__region_code' => ['label' => 'Region code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'region__region_id' => ['label' => 'Region ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'street__0' => ['label' => 'Street 1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'street__1' => ['label' => 'Street 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'suffix' => ['label' => 'Customer suffix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'telephone' => ['label' => 'Telephone number', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'is_default_billing' => ['label' => 'True if the address is the default one for billing', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'is_default_shipping' => ['label' => 'True if the address is the default one for shipping', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_id' => ['label' => 'Customer ID.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => false],
                    ];
                    try {
                        // Get list of countries
                        $countries = $this->call($this->paramConnexion['url'].'/index.php/rest/V1/directory/countries', 'GET');
                        foreach ($countries as $country) {
                            $this->moduleFields['country_id']['option'][$country['id']] = $country['full_name_locale'];
                        }
                    } catch (\Exception $e) {
                        // We don't bloc the program if the ws for countries didn't work
                    }
                    break;
                case 'orders':
                    $this->moduleFields = [
                        'id' => ['label' => 'ID customer', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'parent_id' => ['label' => 'Parent ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'increment_id' => ['label' => 'Increment ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'store_id' => ['label' => 'Store ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'created_at' => ['label' => 'Date of creation', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'updated_at' => ['label' => 'Date of updating', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'is_active' => ['label' => 'Defines whether the order is active', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'tax_amount' => ['label' => 'Tax amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_amount' => ['label' => 'Shipping amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount_amount' => ['label' => 'Discount amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'subtotal' => ['label' => 'Subtotal sum', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'grand_total' => ['label' => 'Grand total sum', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_paid' => ['label' => 'Total paid', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_refunded' => ['label' => 'Total refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_qty_ordered' => ['label' => 'Total quantity ordered', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_canceled' => ['label' => 'Total canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_invoiced' => ['label' => 'Total invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_online_refunded' => ['label' => 'Total online refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_offline_refunded' => ['label' => 'Total offline refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_tax_amount' => ['label' => 'Base tax amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_shipping_amount' => ['label' => 'Base shipping amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_discount_amount' => ['label' => 'Base discount amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_subtotal' => ['label' => 'Base subtotal', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_grand_total' => ['label' => 'Base grand total', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_paid' => ['label' => 'Base total paid', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_refunded' => ['label' => 'Base total refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_qty_ordered' => ['label' => 'Base total quantity ordered', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_canceled' => ['label' => 'Base total canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_invoiced' => ['label' => 'Base total invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_online_refunded' => ['label' => 'Base total online refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_offline_refunded' => ['label' => 'Base total offline refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'billing_address_id' => ['label' => 'Billing address ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'billing_firstname' => ['label' => 'First name in the billing address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'billing_lastname' => ['label' => 'Last name in the billing address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_address_id' => ['label' => 'Shipping address ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_firstname' => ['label' => 'First name in the shipping address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_lastname' => ['label' => 'Last name in the shipping address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'billing_name' => ['label' => 'Billing name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_name' => ['label' => 'Shipping name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'store_to_base_rate' => ['label' => 'Store to base rate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'store_to_order_rate' => ['label' => 'Store to order rate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_to_global_rate' => ['label' => 'Base to global rate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_to_order_rate' => ['label' => 'Base to order rate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'weight' => ['label' => 'Weight', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'store_name' => ['label' => 'Store name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'remote_ip' => ['label' => 'Remote IP', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'status' => ['label' => 'Order status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'state' => ['label' => 'Order state', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'applied_rule_ids' => ['label' => 'Applied rule IDs', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'global_currency_code' => ['label' => 'Global currency code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_currency_code' => ['label' => 'Base currency code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'store_currency_code' => ['label' => 'Store currency code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'order_currency_code' => ['label' => 'Order currency code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_method' => ['label' => 'Shipping method', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_description' => ['label' => 'Shipping description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_email' => ['label' => 'Email address of the customer', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_firstname' => ['label' => 'Customer first name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_lastname' => ['label' => 'Customer last name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'quote_id' => ['label' => 'Shopping cart ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'is_virtual' => ['label' => 'Defines whether the product is a virtual one', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_group_id' => ['label' => 'Customer group ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_note_notify' => ['label' => 'Customer notification', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_is_guest' => ['label' => 'Defines whether the customer is a guest', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'email_sent' => ['label' => 'Defines whether the email notification is sent', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'entity_id' => ['label' => 'Entity ID (order ID)', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'gift_message_id' => ['label' => 'Gift message ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'gift_message' => ['label' => 'Gift message', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_id' => ['label' => 'Customer ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => false],
                    ];
                    break;
                default:
                    throw new \Exception('Fields unreadable');
                    break;
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
            } catch (\Exception $e) {
                // We don't bloc the program if the ws for countries didn't work
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage();

            return false;
        }
    }
    // get_module_fields($module)


    // Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
    public function read($param)
    {
        $result = [];
        try {
            // Ajout du champ id, obligatoire mais spécifique au module
            if (!empty($this->idByModule[$param['module']])) { // Si le champ id existe dans le tableau
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
            $dateRefField = $this->getDateRefName($param['module'], $param['ruleParams']['mode']);

            // Get all data after the reference date
            $searchCriteria = 'searchCriteria[filter_groups][0][filters][0][field]='.$dateRefField.'&searchCriteria[filter_groups][0][filters][0][value]='.urlencode($param['date_ref']).'&searchCriteria[filter_groups][0][filters][0][condition_type]=gt';
            // order by type de reference date
            $searchCriteria .= '&searchCriteria[sortOrders][0][field]='.$dateRefField.'&searchCriteria[sortOrders][0][direction]=ASC';
            $searchCriteria .= 'searchCriteria[sortOrders][0][field]='.$dateRefField.'&searchCriteria[sortOrders][0][direction]=ASC';

            // Call to Magento
            $resultList = $this->call($this->paramConnexion['url'].'/index.php/rest/V1/'.$function.'?'.$searchCriteria, 'GET');
            if (!empty($resultList['message'])) {
                throw new \Exception($resultList['message'].(!empty($resultList['parameters']) ? ' parameters : '.print_r($resultList['parameters'], true) : ''));
            }

            // Traitement des résultats
            if (!empty($resultList['items'])) {
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
                            $row = [];
                            // Ajout de l'ID, $fieldId vaut "customer_id" pour le module "customer" par exemple
                            if (!empty($subRecordNoDimension[$fieldId])) {
                                $row['id'] = $subRecordNoDimension[$fieldId];
                            } else {
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
                                if (in_array($key, $param['fields'])) {
                                    $row[$key] = $value;
                                }
                            }
                            $result['values'][$row['id']] = $row;
                            ++$cpt;
                            $result['count'] = $cpt;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }

        return $result;
    }

    // read($param)

    // Permet de créer un enregistrement
    public function create($param)
    {
        // Initialisation de paramètre en fonction du module
        switch ($param['module']) {
            case 'customers':
                $keyParameters = 'customer';
                break;
        }

        // Transformation du tableau d'entrée pour être compatible webservice Magento
        foreach ($param['data'] as $idDoc => $data) {
            try {
                // Check control before update
                $data = $this->checkDataBeforeCreate($param, $data);
                $dataMagento = [];
                foreach ($data as $key => $value) {
                    // Target id isn't a field for Magento (it is used for Myddleware)
                    if ('target_id' == $key) {
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
                    throw new \Exception($resultList['message'].(!empty($resultList['parameters']) ? ' parameters : '.print_r($resultList['parameters'], true) : ''));
                }
                if (!empty($resultList['id'])) {
                    $result[$idDoc] = [
                        'id' => $resultList['id'],
                        'error' => false,
                    ];
                } else {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => '01',
                    ];
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
            // Modification du statut du flux
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    // Permet de mettre à jour un enregistrement
    public function update($param)
    {
        // Initialisation de paramètre en fonction du module
        switch ($param['module']) {
            case 'customers':
                $keyParameters = 'customer';
                break;
        }

        // Transformation du tableau d'entrée pour être compatible webservice Magento
        foreach ($param['data'] as $idDoc => $data) {
            try {
                // Check control before update
                $data = $this->checkDataBeforeUpdate($param, $data);
                $target_id = '';
                $dataMagento = [];
                foreach ($data as $key => $value) {
                    // Important de renommer le champ id pour que SuiteCRM puisse effectuer une modification et non une création
                    if ('target_id' == $key) {
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
                    throw new \Exception($resultList['message'].(!empty($resultList['parameters']) ? ' parameters : '.print_r($resultList['parameters'], true) : ''));
                }
                if (!empty($resultList['id'])) {
                    $result[$idDoc] = [
                        'id' => $target_id,
                        'error' => false,
                    ];
                } else {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => '01',
                    ];
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $result[$idDoc] = [
                    'id' => '-1',
                    'error' => $error,
                ];
            }
            // Modification du statut du flux
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    // remove one dimension by replacing the dimension by __
    protected function removeDimension($subRecords)
    {
        foreach ($subRecords as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $result[$key.'__'.$subKey] = $subValue;
                }
            } else {
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
    public function getDateRefName($moduleSource, $RuleMode)
    {
        if (in_array($RuleMode, ['0', 'S'])) {
            return 'updated_at';
        } elseif ('C' == $RuleMode) {
            return 'created_at';
        }
        throw new \Exception("$RuleMode is not a correct Rule mode.");
    }

    /**
     * Performs the underlying HTTP request. Not very exciting.
     *
     * @param string $method  The API method to be called
     * @param array  $args    Assoc array of parameters to be passed
     * @param mixed  $url
     * @param mixed  $timeout
     *
     * @return array Assoc array of decoded result
     */
    protected function call($url, $method = 'GET', $args = [], $timeout = 10)
    {
        if (function_exists('curl_init') && function_exists('curl_setopt')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            $headers = [];
            $headers[] = 'Content-Type: application/json';
            if (!empty($this->token)) {
                $headers[] = 'Authorization: Bearer '.$this->token;
            }
            if (!empty($args)) {
                $jsonArgs = json_encode($args);
                $headers[] = 'Content-Lenght: '.$jsonArgs;
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
if (file_exists($file)) {
    require_once $file;
} else {
    //Sinon on met la classe suivante
    class magento extends magentocore
    {
    }// class magentocore
}
