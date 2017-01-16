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

class eventbritecore  extends solution { 
		
	protected $typeRead = array(
				"Organizer" => "User",
				"Events" => "User",
				"Tickets" => "User",
				"Venues" => "User",
				"Access_Codes" => "Event",
				"Discount_Codes" => "Event",
				"Attendees" => "Event",
				"Users" => "User",
			);
		
	private $token;
	private $urlBase = 'https://www.eventbrite.com/json/';
		
	public function getFieldsLogin() {	
		 return array(
					array(
							'name' => 'token',
							'type' => 'text',
							'label' => 'solution.fields.token'
                        )
					);
	}
	
	protected $required_fields =  array('default' => array('id','modified'));
	protected $eventStatuses = ''; //'live,started,ended'
	
 	public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
			$parameters = array();
			$this->token = $this->paramConnexion['token'];
			$response = $this->call($this->urlBase.'user_list_organizers', $parameters);

			// Pour tester la validité du token, on vérifie le retour  du webservice user_list_organizers
			if(isset($response['organizers'])) {
				$this->connexion_valide = true;
			}
			else {
				if (!empty($response['error'])) {
					$error = $response['error']->error_message;
				}
				throw new \Exception($error);
			}
		}
		catch (\Exception $e) {
			$error = 'Failed to login to Eventbrite : '.$e->getMessage();
			echo $error . ';';
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)
	
	public function get_modules($type = 'source') {
		try{
			// Le module attendee n'est accessible d'en source
			if ($type == 'source') {
				$modules = array(
					"Organizer" => "Organizer",
					"Events" => "Events",
					"Tickets" => "Tickets",
					"Venues" => "Venues",
					// "Access_Codes" => "Access Codes",
					// "Discount_Codes" => "Discount Codes",
					"Attendees" => "Attendees",
					"Users" => "Users",
				);
			}
			// target
			else {
				$modules = array(
					"Organizer" => "Organizer",
					"Events" => "Events",
					"Tickets" => "Tickets",
					"Venues" => "Venues",
					// "Access_Codes" => "Access Codes",
					// "Discount_Codes" => "Discount Codes",
					"Users" => "Users",
				);
			}
			return $modules;			
		} catch (\Exception $e) {
			$error = $e->getMessage();
			return $error;			
		}
	} // get_modules()
	
 	// Renvoie les champs du module passé en paramètre
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			// Pour chaque module, traitement différent
			switch ($module) {
			    case 'Organizer':
					$this->moduleFields = array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'name' => array('label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'description' => array('label' => 'Description', 'type' => 'text', 'type_bdd' => 'text', 'required' => 0),
						'url' => array('label' => 'Profile page URL', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					);
					break;
				case 'Events':	
					$this->moduleFields = array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'title' => array('label' => 'Title', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'description' => array('label' => 'Description', 'type' => 'text', 'type_bdd' => 'text', 'required' => 0),
						'start_date' => array('label' => 'Start date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'end_date' => array('label' => 'End date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'timezone' => array('label' => 'Timezone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'created' => array('label' => 'Date created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'modified' => array('label' => 'Date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'privacy' => array('label' => 'Private', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'password' => array('label' => 'Password.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'capacity' => array('label' => 'Capacity', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'url' => array('label' => 'URL', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'logo' => array('label' => 'Logo URL', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'status' => array('label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'option' => array('draft' => 'draft', 'live' => 'live', 'started' => 'started', 'ended' => 'ended', 'canceled' => 'canceled'))
					);
					$this->fieldsRelate = array(
						'organizer_id' => array('label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'required_relationship' => 0),
						'venue_id' => array('label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'required_relationship' => 0)
					);
					break;
				case 'Tickets':	
					$this->moduleFields = array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'name' => array('label' => 'Name.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'description' => array('label' => 'Description.', 'type' => 'text', 'type_bdd' => 'text', 'required' => 0),
						'type' => array('label' => 'Type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'min' => array('label' => 'Minimum ticket', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'max' => array('label' => 'Maximum ticket', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'currency' => array('label' => 'Currency', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'price' => array('label' => 'Price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'start_date' => array('label' => 'Start date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'end_date' => array('label' => 'End date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'quantity_available' => array('label' => 'Quantity available', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'quantity_sold' => array('label' => 'Quantity sold', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'visible' => array('label' => 'Visible', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					);
					$this->fieldsRelate = array(
						'event_id' => array('label' => 'Event id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0),
					);
					break;	
				case 'Venues':	
					$this->moduleFields = array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'name' => array('label' => 'Name.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'address' => array('label' => 'Address.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'address_2' => array('label' => 'Address 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'city' => array('label' => 'City.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'region' => array('label' => 'Region/state/province/county', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'postal_code' => array('label' => 'Postal code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'country' => array('label' => 'Country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'country_code' => array('label' => 'Country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'longitude' => array('label' => 'Longitude', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'latitude' => array('label' => 'Latitude', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'Lat-Long' => array('label' => 'Latitude/Longitude', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					);
					$this->fieldsRelate = array(
						'organizer_id' => array('label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
					);
					break;		
				/* case 'Access_Codes':	
					$this->moduleFields = array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'code' => array('label' => 'Code.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
					// Il faudra probalement faire une relation tickets - access code
						'tickets' => array('label' => 'Tickets', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'start_date' => array('label' => 'Start date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'end_date' => array('label' => 'End date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'quantity_available' => array('label' => 'Quantity available', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'quantity_sold' => array('label' => 'Quantity sold', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					);
					break;		
				case 'Discount_Codes':	
					$this->moduleFields = array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'code' => array('label' => 'Code.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'amount_off' => array('label' => 'Amount off', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'percent_off' => array('label' => 'Tickets', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					// Il faudra probalement faire une relation tickets - discount code
						'tickets' => array('label' => 'Tickets', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'start_date' => array('label' => 'Start date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'end_date' => array('label' => 'End date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'quantity_available' => array('label' => 'Quantity available', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'quantity_sold' => array('label' => 'Quantity sold', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					);
					break;	 */	
				case 'Attendees':	
					$this->moduleFields = array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'quantity' => array('label' => 'Quantity of tickets purchased.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'currency' => array('label' => 'Ticket currency', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'amount_paid' => array('label' => 'Amount paid', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					// Liste de code bar... relation ou module
						'barcode' => array('label' => 'Barcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'order_type' => array('label' => 'Order type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'created' => array('label' => 'Date created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'modified' => array('label' => 'Date modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'event_date' => array('label' => 'Event date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'discount' => array('label' => 'Discount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'notes' => array('label' => 'Notes.', 'type' => 'text', 'type_bdd' => 'text', 'required' => 0),
						'email' => array('label' => 'Email address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'prefix' => array('label' => 'Prefix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'first_name' => array('label' => 'First name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'last_name' => array('label' => 'Last name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'suffix' => array('label' => 'Suffix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'home_address' => array('label' => 'Home address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'home_address_2' => array('label' => 'Home address 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'home_city' => array('label' => 'Home city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'home_postal_code' => array('label' => 'Home postal code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'home_region' => array('label' => 'Home state/province/county', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'home_country' => array('label' => 'Home country name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'home_country_code' => array('label' => 'Home country code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'home_phone' => array('label' => 'Home phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'cell_phone' => array('label' => 'Cell phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'ship_address' => array('label' => 'Shipping address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'ship_address_2' => array('label' => 'Shipping address 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'ship_city' => array('label' => 'Shipping city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'ship_postal_code' => array('label' => 'Shipping postal code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'ship_region' => array('label' => 'Shipping state/province/county', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'ship_country' => array('label' => 'Shipping country name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'ship_country_code' => array('label' => 'Shipping country code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'work_address' => array('label' => 'Work address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'work_address_2' => array('label' => 'Work address 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'work_city' => array('label' => 'Work city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'work_postal_code' => array('label' => 'Work postal code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'work_region' => array('label' => 'Work state/province/county', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'work_country' => array('label' => 'Work country name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'work_country_code' => array('label' => 'Work country code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'work_phone' => array('label' => 'Work phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'job_title' => array('label' => 'Job title', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'company' => array('label' => 'Company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'website' => array('label' => 'Website link', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'blog' => array('label' => 'Blog', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'gender' => array('label' => 'Gender', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'birth_date' => array('label' => 'Birth date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'age' => array('label' => 'Age', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					// Liste des réponses à voir, probablement relation ou module	
						'answers' => array('label' => 'Answers', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'order_id' => array('label' => 'Order ID.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					);
					$this->fieldsRelate = array(
						'event_id' => array('label' => 'Event ID.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1),
						'ticket_id' => array('label' => 'Ticket ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0)
					);
					break;		
				case 'Users':	
					$this->moduleFields = array(
						'id' => array('label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'email' => array('label' => 'Email address.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'first_name' => array('label' => 'First name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'last_name' => array('label' => 'Last name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'user_key' => array('label' => 'API user key', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'date_created' => array('label' => 'Date created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'date_modified' => array('label' => 'Date modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
					// Peut-être une relation user - user
						'subusers' => array('label' => 'Subusers', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
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
			return $this->moduleFields;
		}
		catch (\Exception $e){
			return false;
		}
	} // get_module_fields($module)	

	// Permet de récupérer le dernier enregistrement de la solution (utilisé pour tester le flux ou pour réchercher un doublon dans la cible)
	// Param contient : 
	//	module : le module appelé 
	//	fields : les champs demandés sous forme de tableau, exemple : array('name','date_entered')
	//	query : les champs à rechercher, exemple : array('name'=>'mon_compte')
	// Valeur de sortie est un tableau contenant : 
	//		done : Le nombre d'enregistrement trouvé
	//   	values : les enregsitrements du module demandé (l'id' est obligatoires), exemple Array(['id] => 454664654654, ['name] => dernier)
	public function read_last($param) {
		if(!isset($param['fields'])) {
			$param['fields'] = array();
		}
		$param['fields'] = $this->addRequiredField($param['fields']);
		
		// Le readLast est différent en fonction de la référence de lecture, 
		// s'il s'agit d'un type user alors la lecture est directe
		// mais s'il s'agit d'un type event c'est qu'il faut d'abord récupérer les event du users pour enseuite récupérer les autres éléments (ex : attendee)
		if($this->typeRead[$param['module']] == 'User') {
			return $this->readLastUserType($param);
		}
		if($this->typeRead[$param['module']] == 'Event') {
			return $this->readLastEventType($param);
		}
	} 
	
	
	// Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
	// Param contient : 
	//	date_ref : la date de référence à partir de laquelle on récupère les enregistrements, format bdd AAAA-MM-JJ hh:mm:ss
	//	module : le module appelé 
	//	fields : les champs demandés sous forme de tableau, exemple : array('name','date_entered')
	//	limit : la limite du nombre d'enregistrement récupéré (la limite par défaut étant 100)
	// Valeur de sortie est un tableau contenant : 
	//		count : Le nombre d'enregistrement trouvé
	//		date_ref : la nouvelle date de référence
	//   	values : les enregsitrements du module demandé (l'id et la date de modification (libellés 'id' et 'date_modified') sont obligatoires), exemple Array(['id] => 454664654654, ['name] => dernier,  [date_modified] => 2013-10-11 18:41:18)
	public function read($param) {
		if(!isset($param['fields'])) {
			$param['fields'] = array();
		}
		$param['fields'] = $this->addRequiredField($param['fields']);

		// Le read est différent en fonction de la référence de lecture, 
		// s'il s'agit d'un type user alors la lecture est directe
		// mais s'il s'agit d'un type event c'est qu'il faut d'abord récupérer les event du users pour enseuite récupérer les autres éléments (ex : attendee)
		if($this->typeRead[$param['module']] == 'User') {
			return $this->readUserType($param);
		}
		if($this->typeRead[$param['module']] == 'Event') {
			return $this->readEventType($param);
		}
		
		else {
			$result['error'] = 'Type list unknown for the module '.$param['module'].'. ';
			return $result;
		} 
	}

	// Permet de créer des données
	public function create($param) {	
		$moduleSingle = substr(strtolower($param['module']),0,-1);		
		// Transformation du tableau d'entrée pour être compatible webservice Sugar
		foreach($param['data'] as $parametersEvent) {
			try {
				// Check control before create
				$parametersEvent = $this->checkDataBeforeCreate($param, $parametersEvent);
				$idDoc  = '';
				// array_shift permet de supprimer la première entrée du tableau contenant l'id du docuement et de la sauvegarder dans la variable idDoc
				$idDoc = array_shift($parametersEvent);
				$responseEvent = $this->call( $this->urlBase.$moduleSingle.'_new', $parametersEvent);
				
				// Gestion des retours du webservice
				if (!empty($responseEvent['process']->id)) {
					$result[$idDoc] = array(
											'id' => $responseEvent['process']->id,
											'error' => false
									);
				}
				elseif (!empty($responseEvent['error']->error_type)) {
					$result[$idDoc] = array(
											'id' => '-1',
											'error' => $responseEvent['error']->error_type.' : '.$responseEvent['error']->error_message
									);
				}
				else  {
					$result[$idDoc] = array(
											'id' => '-1',
											'error' => 'Failed to create data in Eventbrite. '
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
	
	// Permet de créer des données
	public function update($param) {
		$moduleSingle = substr(strtolower($param['module']),0,-1);		
		// Transformation du tableau d'entrée pour être compatible webservice Sugar
		foreach($param['data'] as $parametersEvent) {
			try {
				// Check control before update
				$parametersEvent = $this->checkDataBeforeUpdate($param, $parametersEvent);
				$idDoc  = '';
				// array_shift permet de supprimer la première entrée du tableau contenant l'id du docuement et de la sauvegarder dans la variable idDoc
				$idDoc = array_shift($parametersEvent);
				// On renomme l'entrée target_id en id
				$parametersEvent['id'] = $parametersEvent['target_id'];
				unset($parametersEvent['target_id']);
				$responseEvent = $this->call( $this->urlBase.$moduleSingle.'_update', $parametersEvent);

				
				// Gestion des retours du webservice
				if (!empty($responseEvent['process']->id)) {
					$result[$idDoc] = array(
											'id' => $responseEvent['process']->id,
											'error' => false
									);
				}
				elseif (!empty($responseEvent['error']->error_type)) {
					$result[$idDoc] = array(
											'id' => '-1',
											'error' => $responseEvent['error']->error_type.' : '.$responseEvent['error']->error_message
									);
				}
				else  {
					$result[$idDoc] = array(
											'id' => '-1',
											'error' => 'Failed to create data in Eventbrite. '
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
	
	protected function readLastEventType($param) {
		try {
			$parameters = array(
				'only_display'=>implode(',',$this->addRequiredField(array())),
				'asc_or_desc'=>'desc'
			);
			$moduleSingle = substr(strtolower($param['module']),0,-1);		
			$response = $this->call( $this->urlBase.'user_list_events', $parameters);
			
			if(!empty($response['events'][0])) {
				// Boucle sur tous les évènements
				foreach($response['events'] as $event) {
					$parametersEvent = array(
						'id'			=> $event->event->id ,
						'only_display'	=> implode(',',$param['fields'])
					);
					$responseEvent = $this->call( $this->urlBase.'event_list_attendees', $parametersEvent);
					if (!empty($responseEvent[strtolower($param['module'])][0]->$moduleSingle)) {
						$result['done'] = true;
						foreach ($responseEvent[strtolower($param['module'])][0]->$moduleSingle as $key => $value) {
							$result['values'][$key] = $value;
						}
					}
				}
			}
			if(empty($result['values'])) {
				$result['error'] = 'No result. ';
				$result['done'] = false;;
			}
			return $result;
		}
		catch (\Exception $e) {
			$result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;
			return $result;
		}
	}
	
	protected function readLastUserType($param) {
		try {
			$parameters = array(
				'only_display'=>implode(',',$param['fields']),
				'asc_or_desc'=>'desc'
			);
			$moduleSingle = substr(strtolower($param['module']),0,-1);		

			// Si on est sur une demande d'historique alors on utilise la méthode get
			if(!empty($param['query']['id'])) {
				// Si le module n'est pas ticket on récupère l'historique
				if($param['module'] != 'Tickets') {
					$parameters['id'] = $param['query']['id'];
					$response = $this->call($this->urlBase.$moduleSingle.'_get', $parameters);
					// Si une erreur est rencontrée
					if (!empty($response['error'])) {
						$result['error'] = $response['error']->error_type.' : '.$response['error']->error_message;
						$result['done'] = false;
					}
					elseif (!empty($response[$moduleSingle])) {
						$result['done'] = true;
						foreach ($response[$moduleSingle] as $key => $value) {
							$result['values'][$key] = $value;
						}
					}
					else {
						$result['error'] = 'No result. ';
						$result['done'] = false;
					} 

				}
				// Il n'est pas possible de récupérer l'historique des ticket, on renvoie donc vide
				else {
					$result['done'] = true;
					foreach ($param['fields'] as $field) {
						$result['values'][$field] = '';
					}
				}
			}
			// Sinon si on est sur une demande aléatoire pour le test de la règle
			else {
				$response = $this->call($this->urlBase.'user_list_'.strtolower($param['module']), $parameters);
				// Si une erreur est rencontrée
				if (!empty($response['error'])) {
					$result['error'] = $response['error']->error_type.' : '.$response['error']->error_message;
					$result['done'] = false;
				}
				elseif (!empty($response[strtolower($param['module'])])) {
					$result['done'] = true;
					foreach ($response[strtolower($param['module'])][0]->$moduleSingle as $key => $value) {
						$result['values'][$key] = $value;
					}
				}
				else {
					$result['error'] = 'No result. ';
					$result['done'] = false;
				} 
			}
			
			return $result;
		}
		catch (\Exception $e) {
			$result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;
			return $result;
		}
	}
	
	
	// Lescture directe s'il s'agit d'un module lié au user
	protected function readUserType($param) {
		try {
			$i = 0;
			$dateRefTmp = '';
			$moduleSingle = substr(strtolower($param['module']),0,-1);	
			$parameters = array(
				'only_display'=>implode(',',$param['fields']),
				'asc_or_desc'=>'desc'
			);
			$response = $this->call($this->urlBase.'user_list_'.strtolower($param['module']), $parameters);

		 	// Si une erreur est rencontrée
			if (!empty($response['error'])) {
				throw new \Exception($response['error']->error_type.' : '.$response['error']->error_message);
			}
			elseif (!empty($response[strtolower($param['module'])])) {
				$record = array();
				$id = '';			
				foreach ($response[strtolower($param['module'])] as $module) {
					foreach ($module->$moduleSingle as $key => $value) {
						$record[$key] = $value;
						if ($key == 'id') {
							$id = $value;
						}
						elseif ($key == 'modified') {
							// Si la date de modification est ultérieure à la date de référence alors la date de référence est mise jour
							$record['date_modified'] = $value;
							if(strtotime($record['date_modified']) > strtotime($param['date_ref'])) {
								// Si la date de l'enregistrement en cours est supérieure à la date de référence alors mise à jour de la date de référence
								if ($value > $dateRefTmp) {
									$dateRefTmp = $value;
								}
							}
							// Sinon on passe à l'enregistrement suivant
							else {
								$record = array();
								break;
							}
						}
					}
					if (!empty($record)) {
						$result['values'][$id] = $record;
						$i++;
					}
					$record = array();
					$id = '';
				}
			}
			// Si une nouvelle date de référence est trouvée alors elle est retournée
			if (!empty($dateRefTmp)) {
				$result['date_ref'] = $dateRefTmp;
			}
			else {
				$result['date_ref'] = $param['date_ref'];
			}
			$result['count'] = $i;		
			return $result;
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			return $result;
		}
	}
	
	// Lecture indirecte s'il s'agit d'un module lié aux évènements car il faut d'abord récupérer les event du users pour enseuite récupérer les autres éléments (ex : attendee)
	protected function readEventType($param) {
		try {
			// Sauvegarde de la date de référence temp qui est maintenant à GMT
			// Pour cette fonction on récpère la date de maintenant car on a plusieurs évèments qui vont être lus et on pourrait manquer
			// des inscriptions si une personne s'inscrit entre 2 récupération d'évènement.
			// Pour être sûr de ne rine manquer au sauvegarde la date avant lancement. 
			// Il pourra y avoir des particpant appelés 2 fois dans les cas limites, mais il n'y aura pas d'impact fonctionnel.
			// En effet ces éléments doublons ne seront pas envoyé à la cible car il n'auront aucune modification.
			$now = gmdate('Y-m-d H:i:s'); 
		 	
			$i = 0;
			$moduleSingle = substr(strtolower($param['module']),0,-1);	
			$parameters = array(
				'only_display'=>implode(',',$this->addRequiredField(array())),
				'event_statuses'=> $this->eventStatuses , //If you leave this field blank, it will return everything. Also note that the “ended” option will only return events that have ended in the past 7 days.
				'asc_or_desc'=>'desc'
			);
			
			// Récupération de tous les évènement du user		
			$response = $this->call( $this->urlBase.'user_list_events', $parameters);		
			if(!empty($response['events'][0])) {
				// Boucle sur tous les évènements
				foreach($response['events'] as $event) {
					$parametersEvent = array(
						'id'			=> $event->event->id ,
						'modified_after'=> $param['date_ref']
					);
					$responseEvent = $this->call( $this->urlBase.'event_list_attendees', $parametersEvent);

					if (!empty($responseEvent[strtolower($param['module'])][0])) {
						$record = array();
						$id = '';
						// Boucle sur toutes les données pour chaque évènement
						foreach ($responseEvent[strtolower($param['module'])] as $module) {
							// Boucle sur chaque champs
							foreach ($module->$moduleSingle as $key => $value) {
								// Si le champ est demandé dans la règle alors on le renvoie
								if (in_array($key,$param['fields'])) {
									$record[$key] = $value;
								}
								if ($key == 'id') {
									$id = $value;
								}
								elseif ($key == 'modified') {
									// Si la date de modification est antérieure à la date de référence alors on sort des 2 foreach
									$record['date_modified'] = $value;
									if(strtotime($value) <= strtotime($param['date_ref'])) {
										break(2);
									}
									else {
										$dateRefTmp = $value;
									}
								}
							}
							$result['values'][$id] = $record;
							$record = array();
							$id = '';
							$i++;
						}
					}
				}
			} 

			$result['date_ref'] = $now;
			$result['count'] = $i;			
			return $result; 
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			return $result;
		}
	}

	// Permet de renvoyer le mode de la règle en fonction du module target
	// Valeur par défaut "0"
	// Si la règle n'est qu'en création, pas en modicication alors le mode est C
	public function getRuleMode($module,$type) {
		if(
				$type == 'target'
			&& in_array($module, array("Events", "Attendees", "Users"))
		) {
			return array(
				'C' => 'create_only'
			);
		}
		return parent::getRuleMode($module,$type);
	}
	
	// Fonction permettant de faire l'appel REST
	protected function call($url, $parameters){		
		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/x-www-form-urlencoded"));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer ".$this->token));
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$json_data = curl_exec($ch);
			$resp_info = curl_getinfo($ch);
			curl_close($ch);

			$response = get_object_vars(json_decode($json_data));
			return $response;
		}
		catch(\Exception $e) {
			return false;	
		}
    } // call($method, $parameters) 
	
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/eventbrite.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class eventbrite extends eventbritecore {
		
	}
}