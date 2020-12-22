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
use Automattic\WooCommerce\Client; 


// $woocommerce = new Client(
//     'http://localhost/myddleware/wordpress', 
//     'ck_4d08598e65e7ad6a188fecaeb26d06ecdbdd30b4', 
//     'cs_82858696bfa94993dc4e27cdf59d5cf2432f87c1',
//     [
//         'version' => 'wc/v3',
//     ]
// );


// foreach($woocommerce->get('payment_gateways') as $key => $value){
//     foreach($value as $clef => $ligne){
//         echo '<br/>';
//         print_r("'".$clef."' => array( 'label' => '".ucfirst($clef)."', 
//         'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),");
//     }
   
// }


// print_r($woocommerce->get('products'));

$moduleFields = array (
                        'products' => array(
                            'id' => array( 'label' => 'Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'name' => array( 'label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'slug' => array( 'label' => 'Slug', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'permalink' => array( 'label' => 'Permalink', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_created' => array( 'label' => 'Date_created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_created_gmt' => array( 'label' => 'Date_created_gmt', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_modified' => array( 'label' => 'Date_modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_modified_gmt' => array( 'label' => 'Date_modified_gmt', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'type' => array( 'label' => 'Type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'status' => array( 'label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'featured' => array( 'label' => 'Featured', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'catalog_visibility' => array( 'label' => 'Catalog_visibility', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'description' => array( 'label' => 'Description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'short_description' => array( 'label' => 'Short_description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'sku' => array( 'label' => 'Sku', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'price' => array( 'label' => 'Price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'regular_price' => array( 'label' => 'Regular_price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'sale_price' => array( 'label' => 'Sale_price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_on_sale_from' => array( 'label' => 'Date_on_sale_from', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_on_sale_from_gmt' => array( 'label' => 'Date_on_sale_from_gmt', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_on_sale_to' => array( 'label' => 'Date_on_sale_to', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_on_sale_to_gmt' => array( 'label' => 'Date_on_sale_to_gmt', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'price_html' => array( 'label' => 'Price_html', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'on_sale' => array( 'label' => 'On_sale', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'purchasable' => array( 'label' => 'Purchasable', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'total_sales' => array( 'label' => 'Total_sales', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'virtual' => array( 'label' => 'Virtual', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'downloadable' => array( 'label' => 'Downloadable', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'downloads' => array( 'label' => 'Downloads', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'download_limit' => array( 'label' => 'Download_limit', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'download_expiry' => array( 'label' => 'Download_expiry', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'external_url' => array( 'label' => 'External_url', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'button_text' => array( 'label' => 'Button_text', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'tax_status' => array( 'label' => 'Tax_status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'tax_class' => array( 'label' => 'Tax_class', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'manage_stock' => array( 'label' => 'Manage_stock', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'stock_quantity' => array( 'label' => 'Stock_quantity', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'stock_status' => array( 'label' => 'Stock_status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'backorders' => array( 'label' => 'Backorders', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'backorders_allowed' => array( 'label' => 'Backorders_allowed', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'backordered' => array( 'label' => 'Backordered', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'sold_individually' => array( 'label' => 'Sold_individually', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'weight' => array( 'label' => 'Weight', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'dimensions__length' => array( 'label' => 'Dimensions : Length', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'dimensions__width' => array( 'label' => 'Dimensions : Width', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'dimensions__height' => array( 'label' => 'Dimensions : Height', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping_required' => array( 'label' => 'Shipping_required', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping_taxable' => array( 'label' => 'Shipping_taxable', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping_class' => array( 'label' => 'Shipping_class', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping_class_id' => array( 'label' => 'Shipping_class_id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'reviews_allowed' => array( 'label' => 'Reviews_allowed', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'average_rating' => array( 'label' => 'Average_rating', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'rating_count' => array( 'label' => 'Rating_count', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'related_ids' => array( 'label' => 'Related_ids', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'upsell_ids' => array( 'label' => 'Upsell_ids', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'cross_sell_ids' => array( 'label' => 'Cross_sell_ids', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'parent_id' => array( 'label' => 'Parent_id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'purchase_note' => array( 'label' => 'Purchase_note', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            // 'categories' => array( 'label' => 'Categories', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            // 'categories__id' => array( 'label' => 'Categories ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            // 'categories__name' => array( 'label' => 'Categories Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            // 'categories__slug' => array( 'label' => 'Categories Slug', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'tags' => array( 'label' => 'Tags', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'images' => array( 'label' => 'Images', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'attributes' => array( 'label' => 'Attributes', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'default_attributes' => array( 'label' => 'Default_attributes', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'variations' => array( 'label' => 'Variations', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'grouped_products' => array( 'label' => 'Grouped_products', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'menu_order' => array( 'label' => 'Menu_order', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'meta_data' => array( 'label' => 'Meta_data', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            '_links' => array( 'label' => '_links', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            // 'attributes__id' => array( 'label' => 'Product attributes id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            // 'attributes__name' => array( 'label' => 'Product attributes name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            // 'attributes__slug' => array( 'label' => 'Product attributes slug', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            // 'attributes__type' => array( 'label' => 'Product attributes type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            // 'attributes__has_archives' => array( 'label' => 'Product attributes has archives', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
                        ),
                        'payment_gateways' => array(
                            'id' => array( 'label' => 'Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'title' => array( 'label' => 'Title', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'description' => array( 'label' => 'Description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'order' => array( 'label' => 'Order', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'enabled' => array( 'label' => 'Enabled', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'method_title' => array( 'label' => 'Method_title', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'method_description' => array( 'label' => 'Method_description', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'method_supports' => array( 'label' => 'Method_supports', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'settings' => array( 'label' => 'Settings', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            '_links' => array( 'label' => '_links', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                        ), 
                        'customers' => array(
                            'id' => array( 'label' => 'Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_created'=> array( 'label' => 'Date Created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_created_gmt' => array( 'label' => 'Date Created GMT', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),	
                            'date_modified'	=> array( 'label' => 'Date Modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_modified_gmt'	=> array( 'label' => 'Date Modified GMT', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'email'	=> array( 'label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'first_name'	=> array( 'label' => 'First Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'last_name'	=> array( 'label' => 'Id', 'Last Name' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'role'	=> array( 'label' => 'Id', 'Role' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'username'	=> array( 'label' => 'Username', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'password'	=> array( 'label' => 'Password', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__first_name'	=> array( 'label' => 'Billing : First Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__last_name'	=> array( 'label' => 'Billing : Last Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__company'	=> array( 'label' => 'Billing : Company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__address_1'	=> array( 'label' => 'Billing : Address 1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__address_2'	=> array( 'label' => 'Billing : Address 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__city'	=> array( 'label' => 'Billing : City', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__postcode'	=> array( 'label' => 'Billing : Postcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__country'	=> array( 'label' => 'Billing : Country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__state'	=> array( 'label' => 'Billing : State', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__email'	=> array( 'label' => 'Billing : Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__phone'	=> array( 'label' => 'Billing : Phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__state'	=> array( 'label' => 'Billing : State', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__first_name'	=> array( 'label' => 'Shipping : First Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__last_name'	=> array( 'label' => 'Shipping : Last Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__company'	=> array( 'label' => 'Shipping : Company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__address_1'	=> array( 'label' => 'Shipping : Address 1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__address_2'	=> array( 'label' => 'Shipping : Address 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__city'	=> array( 'label' => 'Shipping : City', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__state'	=> array( 'label' => 'Shipping : State', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__postcode'	=> array( 'label' => 'Shipping : Postcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__country'	=> array( 'label' => 'Shipping : Country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'is_paying_customer'	=> array( 'label' => 'Is Paying Customer', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'avatar_url'	=> array( 'label' => 'Avatar URL', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'meta_data'=> array( 'label' => 'Meta Data', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                        ),
                        'orders' => array (
                            'id'=> array( 'label' => 'Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'parent_id'	=> array( 'label' => 'Parent ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'number'	=> array( 'label' => 'Number', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'order_key'	=> array( 'label' => 'Order Key', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'created_via'	=> array( 'label' => 'Created via', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'version'	=> array( 'label' => 'Version', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'status'	=> array( 'label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'currency'	=> array( 'label' => 'Currency', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_created'	=> array( 'label' => 'Date created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_created_gmt'	=> array( 'label' => 'Date created GMT', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_modified'	=> array( 'label' => 'Date modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_modified_gmt'	=> array( 'label' => 'Date modified GMT', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'discount_total'	=> array( 'label' => 'Discount total', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'discount_tax'	=> array( 'label' => 'Discount tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping_total'	=> array( 'label' => 'Shipping total', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping_tax'	=> array( 'label' => 'Shipping tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'cart_tax'	=> array( 'label' => 'Cart tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'total'	=> array( 'label' => 'Total', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'total_tax'	=> array( 'label' => 'Total tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'prices_include_tax'	=> array( 'label' => 'Prices include tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            // 'customer_id'	=> array( 'label' => 'customer_id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'customer_ip_address'	=> array( 'label' => 'Customer IP address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'customer_user_agent'	=> array( 'label' => 'Customer user agent', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'customer_note'	=> array( 'label' => 'Customer Note', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__first_name'	=> array( 'label' => 'Billing first name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__last_name'	=> array( 'label' => 'Billing last name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__company'	=> array( 'label' => 'Billing company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__address_1'	=> array( 'label' => 'Billing address 1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__address_2'	=> array( 'label' => 'Billing address 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__city'	=> array( 'label' => 'Billing city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__postcode'	=> array( 'label' => 'Billing postcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__country'	=> array( 'label' => 'Billing country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__state'	=> array( 'label' => 'Billing state', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__email'	=> array( 'label' => 'Billing email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__phone'	=> array( 'label' => 'Billing phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'billing__state'	=> array( 'label' => 'Billing state', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__first_name'	=> array( 'label' => 'Shipping : First Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__last_name'	=> array( 'label' => 'Shipping : Last Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__company'	=> array( 'label' => 'Shipping : Company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__address_1'	=> array( 'label' => 'Shipping : Address 1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__address_2'	=> array( 'label' => 'Shipping : Address 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__city'	=> array( 'label' => 'Shipping : City', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__state'	=> array( 'label' => 'Shipping : State', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__postcode'	=> array( 'label' => 'Shipping : Postcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping__country'	=> array( 'label' => 'Shipping : Country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'payment_method'	=> array( 'label' => 'Payment method', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'payment_method_title'	=> array( 'label' => 'Payment method title', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'transaction_id'	=> array( 'label' => 'Transaction ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_paid'	=> array( 'label' => 'Date paid', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_paid_gmt'	=> array( 'label' => 'Date paid GMT', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_completed'	=> array( 'label' => 'Date completed', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'date_completed_gmt'	=> array( 'label' => 'Date completed GMT', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'cart_hash'	=> array( 'label' => 'Cart hash', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'meta_data'	=> array( 'label' => 'Meta data', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'line_items' => array( 'label' => 'Line items', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'tax_lines'	=> array( 'label' => 'Tax lines', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'shipping_lines'	=> array( 'label' => 'Shipping lines', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'fee_lines'	=> array( 'label' => 'Fee lines', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'coupon_lines'	=> array( 'label' => 'Coupon lines', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'refunds'	=> array( 'label' => 'Refunds', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'set_paid' => array( 'label' => 'Set paid', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            
                        ),
                        'line_items' => array(
                            'id' =>array( 'label' => 'Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'name' =>array( 'label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'product_id' =>array( 'label' => 'Product ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'variation_id' =>array( 'label' => 'Variation ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'quantity' =>array( 'label' => 'Quantity', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'tax_class' =>array( 'label' => 'Tax class', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'subtotal' =>array( 'label' => 'Subtotal', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'subtotal_tax' =>array( 'label' => 'Subtotal tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'total' =>array( 'label' => 'Total', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'total_tax' =>array( 'label' => 'Total tax', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'taxes' =>array( 'label' => 'Taxes', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'meta_data' =>array( 'label' => 'Meta data', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'sku' =>array( 'label' => 'SKU', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            'price' =>array( 'label' => 'Price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
                            )
                    );

$fieldsRelate = array (
    'line_items' => array(
        'order_id' => array( 'label' => 'Order Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        'product_id'=> array( 'label' => 'Product id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
    ),
    'orders' => array(
         'customer_id'	=> array( 'label' => 'Customer id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
    )
);