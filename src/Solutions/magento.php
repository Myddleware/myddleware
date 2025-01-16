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

class magento extends solution
{
    protected $token;

    // Tableau de correspondance Module / ID pour les modules qui n'ont pas d'id de type "nommodule"."id"
    protected array $idByModule = [
        'customers' => 'id',
        'customer_address' => 'id',
        'orders' => 'entity_id',
        'products' => 'id',
        'orders_items' => 'item_id',
    ];

    protected array $FieldsDuplicate = [
        'customers' => ['email'],
    ];

    protected array $required_fields = ['default' => ['updated_at']];

    protected int $callLimit = 100;

    // Liste des param�tres de connexion
    public function getFieldsLogin(): array
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

    public function get_modules($type = 'source'): array
    {
        if ('source' == $type) {
            return [
                'customers' => 'Customers',
                'customer_address' => 'Customer Address',
                'orders' => 'Sales Order',
                'products' => 'Products',
                'orders_items' => 'Orders Items',
            ];
        }

        return [
            'customers' => 'Customers',
        ];
    }

    // Renvoie les champs du module passé en paramètre
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            // Pour chaque module, traitement différent
            switch ($module) {
                case 'customers':
                    $moduleFields = [
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
                        'increment_id' => ['label' => 'Increment ID.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true],
                        'store_id' => ['label' => 'Store ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true],
                        'website_id' => ['label' => 'Website ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true],
                        'group_id' => ['label' => 'Group ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true],
                        'default_shipping' => ['label' => 'Default shipping address id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => false],
                        'default_billing' => ['label' => 'Default billing address id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => false],
                        'gender' => ['label' => 'Gender', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                    ];
                        break;
                case 'customer_address':
                    $moduleFields = [
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
                        'default_billing' => ['label' => 'True if the address is the default one for billing', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'default_shipping' => ['label' => 'True if the address is the default one for shipping', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_id' => ['label' => 'Customer ID.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true],
                        'vat_id' => ['label' => 'VAT ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                    ];
                    try {
                        // Get list of countries
                        $countries = $this->call($this->paramConnexion['url'].'/index.php/rest/V1/directory/countries', 'GET');
                        foreach ($countries as $country) {
                            $moduleFields['country_id']['option'][$country['id']] = $country['full_name_locale'];
                        }
                    } catch (\Exception $e) {
                        // We don't bloc the program if the ws for countries didn't work
                    }
                    break;
                case 'orders':
                    $moduleFields = [
                        'adjustment_negative' => ['label' => 'Adjustment negative', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'adjustment_positive' => ['label' => 'Adjustment positive', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'applied_rule_ids' => ['label' => 'Applied rule ids', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_adjustment_negative' => ['label' => 'Base adjustment negative', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_adjustment_positive' => ['label' => 'Base adjustment positive', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_currency_code' => ['label' => 'Base currency code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_discount_amount' => ['label' => 'Base discount amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_discount_canceled' => ['label' => 'Base discount canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_discount_invoiced' => ['label' => 'Base discount invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_discount_refunded' => ['label' => 'Base discount refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_grand_total' => ['label' => 'Base grand total', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_discount_tax_compensation_amount' => ['label' => 'Base discount tax compensation amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_discount_tax_compensation_invoiced' => ['label' => 'Base discount tax compensation invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_discount_tax_compensation_refunded' => ['label' => 'Base discount tax compensation refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_shipping_amount' => ['label' => 'Base shipping amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_shipping_canceled' => ['label' => 'Base shipping canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_shipping_discount_amount' => ['label' => 'Base shipping discount amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_shipping_discount_tax_compensation_amnt' => ['label' => 'Base shipping discount tax compensation amnt', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_shipping_incl_tax' => ['label' => 'Base shipping incl tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_shipping_invoiced' => ['label' => 'Base shipping invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_shipping_refunded' => ['label' => 'Base shipping refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_shipping_tax_amount' => ['label' => 'Base shipping tax amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_shipping_tax_refunded' => ['label' => 'Base shipping tax refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_subtotal' => ['label' => 'Base subtotal', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_subtotal_canceled' => ['label' => 'Base subtotal canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_subtotal_incl_tax' => ['label' => 'Base subtotal incl tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_subtotal_invoiced' => ['label' => 'Base subtotal invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_subtotal_refunded' => ['label' => 'Base subtotal refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_tax_amount' => ['label' => 'Base tax amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_tax_canceled' => ['label' => 'Base tax canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_tax_invoiced' => ['label' => 'Base tax invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_tax_refunded' => ['label' => 'Base tax refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_canceled' => ['label' => 'Base total canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_due' => ['label' => 'Base total due', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_invoiced' => ['label' => 'Base total invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_invoiced_cost' => ['label' => 'Base total invoiced cost', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_offline_refunded' => ['label' => 'Base total offline refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_online_refunded' => ['label' => 'Base total online refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_paid' => ['label' => 'Base total paid', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_qty_ordered' => ['label' => 'Base total qty ordered', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_total_refunded' => ['label' => 'Base total refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_to_global_rate' => ['label' => 'Base to global rate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_to_order_rate' => ['label' => 'Base to order rate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'billing_address_id' => ['label' => 'Billing address id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'can_ship_partially' => ['label' => 'Can ship partially', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'can_ship_partially_item' => ['label' => 'Can ship partially item', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'coupon_code' => ['label' => 'Coupon code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'created_at' => ['label' => 'Created at', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_dob' => ['label' => 'Customer dob', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_email' => ['label' => 'Customer email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_firstname' => ['label' => 'Customer firstname', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_gender' => ['label' => 'Customer gender', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_group_id' => ['label' => 'Customer group id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_id' => ['label' => 'Customer ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => true],
                        'customer_is_guest' => ['label' => 'Customer is guest', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_lastname' => ['label' => 'Customer lastname', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_middlename' => ['label' => 'Customer middlename', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_note' => ['label' => 'Customer note', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_note_notify' => ['label' => 'Customer note notify', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_prefix' => ['label' => 'Customer prefix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_suffix' => ['label' => 'Customer suffix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'customer_taxvat' => ['label' => 'Customer taxvat', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount_amount' => ['label' => 'Discount amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount_canceled' => ['label' => 'Discount canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount_description' => ['label' => 'Discount description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount_invoiced' => ['label' => 'Discount invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount_refunded' => ['label' => 'Discount refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'edit_increment' => ['label' => 'Edit increment', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'email_sent' => ['label' => 'Email sent', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'entity_id' => ['label' => 'Entity id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'ext_customer_id' => ['label' => 'Ext customer id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'ext_order_id' => ['label' => 'Ext order id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'forced_shipment_with_invoice' => ['label' => 'Forced shipment with invoice', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'global_currency_code' => ['label' => 'Global currency code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'grand_total' => ['label' => 'Grand total', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount_tax_compensation_amount' => ['label' => 'Discount tax compensation amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount_tax_compensation_invoiced' => ['label' => 'Discount tax compensation invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount_tax_compensation_refunded' => ['label' => 'Discount tax compensation refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'hold_before_state' => ['label' => 'Hold before state', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'hold_before_status' => ['label' => 'Hold before status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'increment_id' => ['label' => 'Increment id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'is_virtual' => ['label' => 'Is virtual', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'order_currency_code' => ['label' => 'Order currency code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'original_increment_id' => ['label' => 'Original increment id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'payment_authorization_amount' => ['label' => 'Payment authorization amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'payment_auth_expiration' => ['label' => 'Payment auth expiration', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'protect_code' => ['label' => 'Protect code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'quote_address_id' => ['label' => 'Quote address id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'quote_id' => ['label' => 'Quote id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'relation_child_id' => ['label' => 'Relation child id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'relation_child_real_id' => ['label' => 'Relation child real id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'relation_parent_id' => ['label' => 'Relation parent id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'relation_parent_real_id' => ['label' => 'Relation parent real id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'remote_ip' => ['label' => 'Remote ip', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_amount' => ['label' => 'Shipping amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_canceled' => ['label' => 'Shipping canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_description' => ['label' => 'Shipping description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_discount_amount' => ['label' => 'Shipping discount amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_discount_tax_compensation_amount' => ['label' => 'Shipping discount tax compensation amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_incl_tax' => ['label' => 'Shipping incl tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_invoiced' => ['label' => 'Shipping invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_refunded' => ['label' => 'Shipping refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_tax_amount' => ['label' => 'Shipping tax amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'shipping_tax_refunded' => ['label' => 'Shipping tax refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'state' => ['label' => 'State', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'status' => ['label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'store_currency_code' => ['label' => 'Store currency code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'store_id' => ['label' => 'Store id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'store_name' => ['label' => 'Store name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'store_to_base_rate' => ['label' => 'Store to base rate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'store_to_order_rate' => ['label' => 'Store to order rate', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'subtotal' => ['label' => 'Subtotal', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'subtotal_canceled' => ['label' => 'Subtotal canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'subtotal_incl_tax' => ['label' => 'Subtotal incl tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'subtotal_invoiced' => ['label' => 'Subtotal invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'subtotal_refunded' => ['label' => 'Subtotal refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'tax_amount' => ['label' => 'Tax amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'tax_canceled' => ['label' => 'Tax canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'tax_invoiced' => ['label' => 'Tax invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'tax_refunded' => ['label' => 'Tax refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_canceled' => ['label' => 'Total canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_due' => ['label' => 'Total due', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_invoiced' => ['label' => 'Total invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_item_count' => ['label' => 'Total item count', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_offline_refunded' => ['label' => 'Total offline refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_online_refunded' => ['label' => 'Total online refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_paid' => ['label' => 'Total paid', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_qty_ordered' => ['label' => 'Total qty ordered', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'total_refunded' => ['label' => 'Total refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'updated_at' => ['label' => 'Updated at', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'weight' => ['label' => 'Weight', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'x_forwarded_for' => ['label' => 'X forwarded for', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                    ];
                    break;

                case 'orders_items':
                    $moduleFields = [
                        'amount_refunded' => ['label' => 'Amount refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'applied_rule_ids' => ['label' => 'Applied rule ids', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_amount_refunded' => ['label' => 'Base amount refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_discount_amount' => ['label' => 'Base discount amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_discount_invoiced' => ['label' => 'Base discount invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'base_discount_refunded' => ['label' => 'Base discount refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_discount_tax_compensation_amount' => ['label' => 'Base discount tax compensation amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_discount_tax_compensation_invoiced' => ['label' => 'Base discount tax compensation invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'base_discount_tax_compensation_refunded' => ['label' => 'Base discount tax compensation refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_original_price' => ['label' => 'Base original price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_price' => ['label' => 'Base price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_price_incl_tax' => ['label' => 'Base price incl tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_row_invoiced' => ['label' => 'Base row invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_row_total' => ['label' => 'Base row total', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_row_total_incl_tax' => ['label' => 'Base row total incl tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_tax_amount' => ['label' => 'Base tax amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'base_tax_before_discount' => ['label' => 'Base tax before discount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'base_tax_invoiced' => ['label' => 'Base tax invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'base_tax_refunded' => ['label' => 'Base tax refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'base_weee_tax_applied_amount' => ['label' => 'Base weee tax applied amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'base_weee_tax_applied_row_amnt' => ['label' => 'Base weee tax applied row amnt', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'base_weee_tax_disposition' => ['label' => 'Base weee tax disposition', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'base_weee_tax_row_disposition' => ['label' => 'Base weee tax row disposition', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'created_at' => ['label' => 'Created at', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount_amount' => ['label' => 'Discount amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount_invoiced' => ['label' => 'Discount invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount_percent' => ['label' => 'Discount percent', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'discount_refunded' => ['label' => 'Discount refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'ext_order_item_id' => ['label' => 'Ext order item id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'free_shipping' => ['label' => 'Free shipping', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'gw_base_price' => ['label' => 'Gw base price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'gw_base_price_invoiced' => ['label' => 'Gw base price invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'gw_base_price_refunded' => ['label' => 'Gw base price refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'gw_base_tax_amount' => ['label' => 'Gw base tax amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'gw_base_tax_amount_invoiced' => ['label' => 'Gw base tax amount invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'gw_base_tax_amount_refunded' => ['label' => 'Gw base tax amount refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'gw_id' => ['label' => 'Gw id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'gw_price' => ['label' => 'Gw price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'gw_price_invoiced' => ['label' => 'Gw price invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'gw_price_refunded' => ['label' => 'Gw price refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'gw_tax_amount' => ['label' => 'Gw tax amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'gw_tax_amount_invoiced' => ['label' => 'Gw tax amount invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'gw_tax_amount_refunded' => ['label' => 'Gw tax amount refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount_tax_compensation_amount' => ['label' => 'Discount tax compensation amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'discount_tax_compensation_canceled' => ['label' => 'Discount tax compensation canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount_tax_compensation_invoiced' => ['label' => 'Discount tax compensation invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'discount_tax_compensation_refunded' => ['label' => 'Discount tax compensation refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'is_qty_decimal' => ['label' => 'Is qty decimal', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'item_id' => ['label' => 'Item id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'locked_do_invoice' => ['label' => 'Locked do invoice', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'locked_do_ship' => ['label' => 'Locked do ship', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'name' => ['label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'no_discount' => ['label' => 'No discount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'order_id' => ['label' => 'Order id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => true],
                        'original_price' => ['label' => 'Original price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'price' => ['label' => 'Price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'price_incl_tax' => ['label' => 'Price incl tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'product_id' => ['label' => 'Product id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'relate' => true],
                        'product_type' => ['label' => 'Product type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'qty_backordered' => ['label' => 'Qty backordered', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'qty_canceled' => ['label' => 'Qty canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'qty_invoiced' => ['label' => 'Qty invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'qty_ordered' => ['label' => 'Qty ordered', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'qty_refunded' => ['label' => 'Qty refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'qty_returned' => ['label' => 'Qty returned', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'qty_shipped' => ['label' => 'Qty shipped', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'quote_item_id' => ['label' => 'Quote item id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'row_invoiced' => ['label' => 'Row invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'row_total' => ['label' => 'Row total', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'row_total_incl_tax' => ['label' => 'Row total incl tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'row_weight' => ['label' => 'Row weight', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'sku' => ['label' => 'Sku', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'store_id' => ['label' => 'Store id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'tax_amount' => ['label' => 'Tax amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'tax_before_discount' => ['label' => 'Tax before discount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'tax_canceled' => ['label' => 'Tax canceled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'tax_invoiced' => ['label' => 'Tax invoiced', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'tax_percent' => ['label' => 'Tax percent', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'tax_refunded' => ['label' => 'Tax refunded', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'updated_at' => ['label' => 'Updated at', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'weee_tax_applied' => ['label' => 'Weee tax applied', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'weee_tax_applied_amount' => ['label' => 'Weee tax applied amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'weee_tax_applied_row_amount' => ['label' => 'Weee tax applied row amount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'weee_tax_disposition' => ['label' => 'Weee tax disposition', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'weee_tax_row_disposition' => ['label' => 'Weee tax row disposition', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'weight' => ['label' => 'Weight', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'additional_data' => ['label' => 'Additional data', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'parent_item_id' => ['label' => 'Parent item id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'parent_item' => ['label' => 'Parent item', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'product_option' => ['label' => 'Product option', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'extension_attributes' => ['label' => 'Extension attributes', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'event_id' => ['label' => 'Event id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'description' => ['label' => 'Description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'base_cost' => ['label' => 'Base cost', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'is_virtual' => ['label' => 'Is virtual', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                    ];
                    break;
                case 'products':
                    $moduleFields = [
                        'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'sku' => ['label' => 'SKU', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'name' => ['label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'attribute_set_id' => ['label' => 'Attribute set ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'price' => ['label' => 'Price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'status' => ['label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'visibility' => ['label' => 'Visibility', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'type_id' => ['label' => 'Type ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'created_at' => ['label' => 'Created at', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'updated_at' => ['label' => 'Updated at', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'weight' => ['label' => 'Weight', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'extension_attributes' => ['label' => 'Extension_attributes', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'product_links' => ['label' => 'Product_links', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'options' => ['label' => 'Options', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'media_gallery_entries' => ['label' => 'Media_gallery_entries', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'tier_prices' => ['label' => 'Tier_prices', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // 'custom_attributes' => ['label' => 'Custom_attributes', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                    ];
                    break;
                default:
                    throw new \Exception('Fields unreadable');
                    break;
            }

            // Add list here (field could exist in several fields or was part of a rrelate field)
            try {
                if (!empty($moduleFields['website_id'])) {
                    // Get list of website
                    $websites = $this->call($this->paramConnexion['url'].'/index.php/rest/V1/store/websites ', 'GET');
                    foreach ($websites as $website) {
                        $moduleFields['website_id']['option'][$website['id']] = $website['name'];
                    }
                }
            } catch (\Exception $e) {
                // We don't bloc the program if the ws for countries didn't work
            }
            $this->moduleFields = array_merge($this->moduleFields, $moduleFields);

            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
    public function readData($param): array
    {
        $result = [];
        $result['count'] = 0;
        try {
            // Ajout du champ id, obligatoire mais spécifique au module
            if (!empty($this->idByModule[$param['module']])) { // Si le champ id existe dans le tableau
                $fieldId = $this->idByModule[$param['module']];
            }

            // Add requiered fields
            $param['fields'] = $this->addRequiredField($param['fields'], $param['module'], $param['ruleParams']['mode']);

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
                case 'orders_items':
                    $function = 'orders';
                    $subModule = 'items';
                    break;
                case 'products':
                    $function = 'products';
                    break;
                default:
                    throw new \Exception('Module unknown. ');
                    break;
            }

            // On va chercher le nom du champ pour la date de référence: Création ou Modification
            $dateRefField = $this->getRefFieldName($param);

            // Limit = pageSize
            if (!empty($param['limit'])) {
                $this->callLimit = $param['limit'];
            }

            // Get all data after the reference date
            // Search by fields (id or duplicate fields)
            $searchCriteria = '';
            if (!empty($param['query'])) {
                $i = 0;
                // Add every filter (AND operator by default)
                foreach ($param['query'] as $key => $value) {
                    // We change id to entity_id whe we serach a specific record with an id. In this case we use only the id filter even if other filters are set.
                    if ('id' == $key) {
                        $searchCriteria = '?searchCriteria[filter_groups][0][filters][0][field]=entity_id&searchCriteria[filter_groups][0][filters][0][value]='.urlencode($value).'&searchCriteria[filter_groups][0][filters][0][condition_type]=eq';
                        break;
                    }
                    // Workaround for a Magento bug, if we keep order_id then we get the Magento error : Column 'order_id' in where clause is ambiguous
                    // So we change the where condition to make it work on Magento side
                    if (
                            'order_id' == $key
                        and in_array($param['module'], ['orders_items', 'orders'])
                    ) {
                        $key = 'main_table.entity_id';
                    }
                    // Create search criteria
                    $searchCriteria .= '?searchCriteria[filter_groups][0][filters]['.$i.'][field]='.$key.'&searchCriteria[filter_groups][0][filters]['.$i.'][value]='.urlencode($value).'&searchCriteria[filter_groups][0][filters]['.$i.'][condition_type]=eq';
                    ++$i;
                }
            } else {
                // Search By reference
                $searchCriteria = '?searchCriteria[pageSize]='.$this->callLimit.'&searchCriteria[filter_groups][0][filters][0][field]='.$dateRefField.'&searchCriteria[filter_groups][0][filters][0][value]='.urlencode($param['date_ref']).'&searchCriteria[filter_groups][0][filters][0][condition_type]=gt';
                // order by reference
                $searchCriteria .= '&searchCriteria[sortOrders][0][field]='.$dateRefField.'&searchCriteria[sortOrders][0][direction]=ASC';
            }

            // Call to Magento
            $resultList = $this->call($this->paramConnexion['url'].'/index.php/rest/V1/'.$function.$searchCriteria, 'GET');
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
                    $subRecordsNoDimension = [];
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
                            ++$result['count'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            $result['error'] = $error;
        }

        return $result;
    }

    // Permet de créer un enregistrement

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function createData($param): array
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
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
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

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateData($param): array
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
                $data = $this->checkDataBeforeUpdate($param, $data, $idDoc);
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
    protected function removeDimension($subRecords): array
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
    /**
     * @throws \Exception
     */
    public function getRefFieldName($param): string
    {
        if (in_array($param['ruleParams']['mode'], ['0', 'S'])) {
            return 'updated_at';
        } elseif ('C' == $param['ruleParams']['mode']) {
            return 'created_at';
        }
        throw new \Exception("$param[ruleParams][mode] is not a correct Rule mode.");
    }

    /**
     * Performs the underlying HTTP request. Not very exciting.
     *
     * @param string $method The API method to be called
     * @param array $args Assoc array of parameters to be passed
     * @param mixed $url
     * @param mixed $timeout
     *
     * @return array Assoc array of decoded result
     * @throws \Exception
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
}
