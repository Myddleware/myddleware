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

class eventbritecore extends solution
{
    protected array $typeRead = [
        'Organizer' => 'User',
        'Events' => 'User',
        'Tickets' => 'User',
        'Venues' => 'User',
        'Access_Codes' => 'Event',
        'Discount_Codes' => 'Event',
        'Attendees' => 'Event',
        'Users' => 'User',
    ];

    private $token;
    private string $urlBase = 'https://www.eventbrite.com/json/';

    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'token',
                'type' => TextType::class,
                'label' => 'solution.fields.token',
            ],
        ];
    }

    protected array $required_fields = ['default' => ['id', 'modified']];
    protected string $eventStatuses = ''; //'live,started,ended'

    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            $parameters = [];
            $this->token = $this->paramConnexion['token'];
            $response = $this->call($this->urlBase.'user_list_organizers', $parameters);

            // Pour tester la validité du token, on vérifie le retour  du webservice user_list_organizers
            if (isset($response['organizers'])) {
                $this->connexion_valide = true;
            } else {
                if (!empty($response['error'])) {
                    $error = $response['error']->error_message;
                }
                throw new \Exception($error);
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    public function get_modules($type = 'source'): array
    {
        try {
            // Le module attendee n'est accessible d'en source
            if ('source' == $type) {
                $modules = [
                    'Organizer' => 'Organizer',
                    'Events' => 'Events',
                    'Tickets' => 'Tickets',
                    'Venues' => 'Venues',
                    // "Access_Codes" => "Access Codes",
                    // "Discount_Codes" => "Discount Codes",
                    'Attendees' => 'Attendees',
                    'Users' => 'Users',
                ];
            }
            // target
            else {
                $modules = [
                    'Organizer' => 'Organizer',
                    'Events' => 'Events',
                    'Tickets' => 'Tickets',
                    'Venues' => 'Venues',
                    // "Access_Codes" => "Access Codes",
                    // "Discount_Codes" => "Discount Codes",
                    'Users' => 'Users',
                ];
            }

            return $modules;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            return ['error' => $error];
        }
    }
    // Renvoie les champs du module passé en paramètre
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            // Pour chaque module, traitement différent
            switch ($module) {
                case 'Organizer':
                    $this->moduleFields = [
                        'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'name' => ['label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'description' => ['label' => 'Description', 'type' => TextType::class, 'type_bdd' => 'text', 'required' => 0],
                        'url' => ['label' => 'Profile page URL', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                    ];
                    break;
                case 'Events':
                    $this->moduleFields = [
                        'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'title' => ['label' => 'Title', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'description' => ['label' => 'Description', 'type' => TextType::class, 'type_bdd' => 'text', 'required' => 0],
                        'start_date' => ['label' => 'Start date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'end_date' => ['label' => 'End date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'timezone' => ['label' => 'Timezone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'created' => ['label' => 'Date created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'modified' => ['label' => 'Date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'privacy' => ['label' => 'Private', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'password' => ['label' => 'Password.', 'type' => PasswordType::class, 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'capacity' => ['label' => 'Capacity', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'url' => ['label' => 'URL', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'logo' => ['label' => 'Logo URL', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'status' => ['label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'option' => ['draft' => 'draft', 'live' => 'live', 'started' => 'started', 'ended' => 'ended', 'canceled' => 'canceled']],
                        'organizer_id' => ['label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'required_relationship' => 0, 'relate' => true],
                        'venue_id' => ['label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'required_relationship' => 0, 'relate' => true],
                    ];
                    break;
                case 'Tickets':
                    $this->moduleFields = [
                        'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'name' => ['label' => 'Name.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'description' => ['label' => 'Description.', 'type' => TextType::class, 'type_bdd' => 'text', 'required' => 0],
                        'type' => ['label' => 'Type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'min' => ['label' => 'Minimum ticket', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'max' => ['label' => 'Maximum ticket', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'currency' => ['label' => 'Currency', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'price' => ['label' => 'Price', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'start_date' => ['label' => 'Start date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'end_date' => ['label' => 'End date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'quantity_available' => ['label' => 'Quantity available', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'quantity_sold' => ['label' => 'Quantity sold', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'visible' => ['label' => 'Visible', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'event_id' => ['label' => 'Event id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true],
                    ];
                    break;
                case 'Venues':
                    $this->moduleFields = [
                        'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'name' => ['label' => 'Name.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'address' => ['label' => 'Address.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'address_2' => ['label' => 'Address 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'city' => ['label' => 'City.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'region' => ['label' => 'Region/state/province/county', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'postal_code' => ['label' => 'Postal code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'country' => ['label' => 'Country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'country_code' => ['label' => 'Country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1],
                        'longitude' => ['label' => 'Longitude', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'latitude' => ['label' => 'Latitude', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'Lat-Long' => ['label' => 'Latitude/Longitude', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'organizer_id' => ['label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1, 'relate' => true],
                    ];
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
                    $this->moduleFields = [
                        'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'quantity' => ['label' => 'Quantity of tickets purchased.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'currency' => ['label' => 'Ticket currency', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'amount_paid' => ['label' => 'Amount paid', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // Liste de code bar... relation ou module
                        'barcode' => ['label' => 'Barcode', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'order_type' => ['label' => 'Order type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'created' => ['label' => 'Date created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'modified' => ['label' => 'Date modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'event_date' => ['label' => 'Event date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'discount' => ['label' => 'Discount', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'notes' => ['label' => 'Notes.', 'type' => TextType::class, 'type_bdd' => 'text', 'required' => 0],
                        'email' => ['label' => 'Email address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'prefix' => ['label' => 'Prefix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'first_name' => ['label' => 'First name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'last_name' => ['label' => 'Last name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'suffix' => ['label' => 'Suffix', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'home_address' => ['label' => 'Home address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'home_address_2' => ['label' => 'Home address 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'home_city' => ['label' => 'Home city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'home_postal_code' => ['label' => 'Home postal code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'home_region' => ['label' => 'Home state/province/county', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'home_country' => ['label' => 'Home country name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'home_country_code' => ['label' => 'Home country code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'home_phone' => ['label' => 'Home phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'cell_phone' => ['label' => 'Cell phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'ship_address' => ['label' => 'Shipping address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'ship_address_2' => ['label' => 'Shipping address 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'ship_city' => ['label' => 'Shipping city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'ship_postal_code' => ['label' => 'Shipping postal code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'ship_region' => ['label' => 'Shipping state/province/county', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'ship_country' => ['label' => 'Shipping country name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'ship_country_code' => ['label' => 'Shipping country code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'work_address' => ['label' => 'Work address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'work_address_2' => ['label' => 'Work address 2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'work_city' => ['label' => 'Work city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'work_postal_code' => ['label' => 'Work postal code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'work_region' => ['label' => 'Work state/province/county', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'work_country' => ['label' => 'Work country name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'work_country_code' => ['label' => 'Work country code', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'work_phone' => ['label' => 'Work phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'job_title' => ['label' => 'Job title', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'company' => ['label' => 'Company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'website' => ['label' => 'Website link', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'blog' => ['label' => 'Blog', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'gender' => ['label' => 'Gender', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'birth_date' => ['label' => 'Birth date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'age' => ['label' => 'Age', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // Liste des réponses à voir, probablement relation ou module
                        'answers' => ['label' => 'Answers', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'order_id' => ['label' => 'Order ID.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'event_id' => ['label' => 'Event ID.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1, 'relate' => true],
                        'ticket_id' => ['label' => 'Ticket ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 0, 'relate' => true],
                    ];
                    break;
                case 'Users':
                    $this->moduleFields = [
                        'id' => ['label' => 'ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'email' => ['label' => 'Email address.', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'first_name' => ['label' => 'First name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'last_name' => ['label' => 'Last name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'user_key' => ['label' => 'API user key', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'date_created' => ['label' => 'Date created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        'date_modified' => ['label' => 'Date modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                        // Peut-être une relation user - user
                        'subusers' => ['label' => 'Subusers', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0],
                    ];
                    break;
                default:
                    throw new \Exception('Fields unreadable');
                    break;
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
            return false;
        }
    }

    // get_module_fields($module)

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
    public function readData($param): array
    {
        if (!isset($param['fields'])) {
            $param['fields'] = [];
        }
        $param['fields'] = $this->addRequiredField($param['fields'], $param['module'], $param['ruleParams']['mode']);

        // Le read est différent en fonction de la référence de lecture,
        // s'il s'agit d'un type user alors la lecture est directe
        // mais s'il s'agit d'un type event c'est qu'il faut d'abord récupérer les event du users pour enseuite récupérer les autres éléments (ex : attendee)
        if ('User' == $this->typeRead[$param['module']]) {
            return $this->readUserType($param);
        }
        if ('Event' == $this->typeRead[$param['module']]) {
            return $this->readEventType($param);
        }

        $result['error'] = 'Type list unknown for the module '.$param['module'].'. ';

        return $result;
    }

    // Permet de créer des données
    public function createData($param): array
    {
        $moduleSingle = substr(strtolower($param['module']), 0, -1);
        // Transformation du tableau d'entrée pour être compatible webservice Sugar
        foreach ($param['data'] as $parametersEvent) {
            try {
                // Check control before create
                $parametersEvent = $this->checkDataBeforeCreate($param, $parametersEvent);
                $idDoc = '';
                // array_shift permet de supprimer la première entrée du tableau contenant l'id du docuement et de la sauvegarder dans la variable idDoc
                $idDoc = array_shift($parametersEvent);
                $responseEvent = $this->call($this->urlBase.$moduleSingle.'_new', $parametersEvent);

                // Gestion des retours du webservice
                if (!empty($responseEvent['process']->id)) {
                    $result[$idDoc] = [
                        'id' => $responseEvent['process']->id,
                        'error' => false,
                    ];
                } elseif (!empty($responseEvent['error']->error_type)) {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => $responseEvent['error']->error_type.' : '.$responseEvent['error']->error_message,
                    ];
                } else {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => 'Failed to create data in Eventbrite. ',
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

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateData($param): array
    {
        $moduleSingle = substr(strtolower($param['module']), 0, -1);
        // Transformation du tableau d'entrée pour être compatible webservice Sugar
        foreach ($param['data'] as $idDoc => $parametersEvent) {
            try {
                // Check control before update
                $parametersEvent = $this->checkDataBeforeUpdate($param, $parametersEvent, $idDoc);
                $idDoc = '';
                // array_shift permet de supprimer la première entrée du tableau contenant l'id du docuement et de la sauvegarder dans la variable idDoc
                $idDoc = array_shift($parametersEvent);
                // On renomme l'entrée target_id en id
                $parametersEvent['id'] = $parametersEvent['target_id'];
                unset($parametersEvent['target_id']);
                $responseEvent = $this->call($this->urlBase.$moduleSingle.'_update', $parametersEvent);

                // Gestion des retours du webservice
                if (!empty($responseEvent['process']->id)) {
                    $result[$idDoc] = [
                        'id' => $responseEvent['process']->id,
                        'error' => false,
                    ];
                } elseif (!empty($responseEvent['error']->error_type)) {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => $responseEvent['error']->error_type.' : '.$responseEvent['error']->error_message,
                    ];
                } else {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => 'Failed to create data in Eventbrite. ',
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

    protected function readLastEventType($param): array
    {
        try {
            $parameters = [
                'only_display' => implode(',', $this->addRequiredField([])),
                'asc_or_desc' => 'desc',
            ];
            $moduleSingle = substr(strtolower($param['module']), 0, -1);
            $response = $this->call($this->urlBase.'user_list_events', $parameters);

            if (!empty($response['events'][0])) {
                // Boucle sur tous les évènements
                foreach ($response['events'] as $event) {
                    $parametersEvent = [
                        'id' => $event->event->id,
                        'only_display' => implode(',', $param['fields']),
                    ];
                    $responseEvent = $this->call($this->urlBase.'event_list_attendees', $parametersEvent);
                    if (!empty($responseEvent[strtolower($param['module'])][0]->$moduleSingle)) {
                        $result['done'] = true;
                        foreach ($responseEvent[strtolower($param['module'])][0]->$moduleSingle as $key => $value) {
                            $result['values'][$key] = $value;
                        }
                    }
                }
            }
            if (empty($result['values'])) {
                $result['error'] = 'No result. ';
                $result['done'] = false;
            }

            return $result;
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $result['done'] = -1;

            return $result;
        }
    }

    protected function readLastUserType($param): array
    {
        try {
            $parameters = [
                'only_display' => implode(',', $param['fields']),
                'asc_or_desc' => 'desc',
            ];
            $moduleSingle = substr(strtolower($param['module']), 0, -1);

            // Si on est sur une demande d'historique alors on utilise la méthode get
            if (!empty($param['query']['id'])) {
                // Si le module n'est pas ticket on récupère l'historique
                if ('Tickets' != $param['module']) {
                    $parameters['id'] = $param['query']['id'];
                    $response = $this->call($this->urlBase.$moduleSingle.'_get', $parameters);
                    // Si une erreur est rencontrée
                    if (!empty($response['error'])) {
                        $result['error'] = $response['error']->error_type.' : '.$response['error']->error_message;
                        $result['done'] = false;
                    } elseif (!empty($response[$moduleSingle])) {
                        $result['done'] = true;
                        foreach ($response[$moduleSingle] as $key => $value) {
                            $result['values'][$key] = $value;
                        }
                    } else {
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
                } elseif (!empty($response[strtolower($param['module'])])) {
                    $result['done'] = true;
                    foreach ($response[strtolower($param['module'])][0]->$moduleSingle as $key => $value) {
                        $result['values'][$key] = $value;
                    }
                } else {
                    $result['error'] = 'No result. ';
                    $result['done'] = false;
                }
            }

            return $result;
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $result['done'] = -1;

            return $result;
        }
    }

    // Lescture directe s'il s'agit d'un module lié au user
    protected function readUserType($param): array
    {
        try {
            $i = 0;
            $dateRefTmp = '';
            $moduleSingle = substr(strtolower($param['module']), 0, -1);
            $parameters = [
                'only_display' => implode(',', $param['fields']),
                'asc_or_desc' => 'desc',
            ];
            $response = $this->call($this->urlBase.'user_list_'.strtolower($param['module']), $parameters);

            // Si une erreur est rencontrée
            if (!empty($response['error'])) {
                throw new \Exception($response['error']->error_type.' : '.$response['error']->error_message);
            } elseif (!empty($response[strtolower($param['module'])])) {
                $record = [];
                $id = '';
                foreach ($response[strtolower($param['module'])] as $module) {
                    foreach ($module->$moduleSingle as $key => $value) {
                        $record[$key] = $value;
                        if ('id' == $key) {
                            $id = $value;
                        } elseif ('modified' == $key) {
                            // Si la date de modification est ultérieure à la date de référence alors la date de référence est mise jour
                            $record['date_modified'] = $value;
                            if (strtotime($record['date_modified']) > strtotime($param['date_ref'])) {
                                // Si la date de l'enregistrement en cours est supérieure à la date de référence alors mise à jour de la date de référence
                                if ($value > $dateRefTmp) {
                                    $dateRefTmp = $value;
                                }
                            }
                            // Sinon on passe à l'enregistrement suivant
                            else {
                                $record = [];
                                break;
                            }
                        }
                    }
                    if (!empty($record)) {
                        $result['values'][$id] = $record;
                        ++$i;
                    }
                    $record = [];
                    $id = '';
                }
            }
            // Si une nouvelle date de référence est trouvée alors elle est retournée
            if (!empty($dateRefTmp)) {
                $result['date_ref'] = $dateRefTmp;
            } else {
                $result['date_ref'] = $param['date_ref'];
            }
            $result['count'] = $i;

            return $result;
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';

            return $result;
        }
    }

    // Lecture indirecte s'il s'agit d'un module lié aux évènements car il faut d'abord récupérer les event du users pour enseuite récupérer les autres éléments (ex : attendee)
    protected function readEventType($param): array
    {
        try {
            // Sauvegarde de la date de référence temp qui est maintenant à GMT
            // Pour cette fonction on récpère la date de maintenant car on a plusieurs évèments qui vont être lus et on pourrait manquer
            // des inscriptions si une personne s'inscrit entre 2 récupération d'évènement.
            // Pour être sûr de ne rine manquer au sauvegarde la date avant lancement.
            // Il pourra y avoir des particpant appelés 2 fois dans les cas limites, mais il n'y aura pas d'impact fonctionnel.
            // En effet ces éléments doublons ne seront pas envoyé à la cible car il n'auront aucune modification.
            $now = gmdate('Y-m-d H:i:s');

            $i = 0;
            $moduleSingle = substr(strtolower($param['module']), 0, -1);
            $parameters = [
                'only_display' => implode(',', $this->addRequiredField([])),
                'event_statuses' => $this->eventStatuses, //If you leave this field blank, it will return everything. Also note that the “ended” option will only return events that have ended in the past 7 days.
                'asc_or_desc' => 'desc',
            ];

            // Récupération de tous les évènement du user
            $response = $this->call($this->urlBase.'user_list_events', $parameters);
            if (!empty($response['events'][0])) {
                // Boucle sur tous les évènements
                foreach ($response['events'] as $event) {
                    $parametersEvent = [
                        'id' => $event->event->id,
                        'modified_after' => $param['date_ref'],
                    ];
                    $responseEvent = $this->call($this->urlBase.'event_list_attendees', $parametersEvent);

                    if (!empty($responseEvent[strtolower($param['module'])][0])) {
                        $record = [];
                        $id = '';
                        // Boucle sur toutes les données pour chaque évènement
                        foreach ($responseEvent[strtolower($param['module'])] as $module) {
                            // Boucle sur chaque champs
                            foreach ($module->$moduleSingle as $key => $value) {
                                // Si le champ est demandé dans la règle alors on le renvoie
                                if (in_array($key, $param['fields'])) {
                                    $record[$key] = $value;
                                }
                                if ('id' == $key) {
                                    $id = $value;
                                } elseif ('modified' == $key) {
                                    // Si la date de modification est antérieure à la date de référence alors on sort des 2 foreach
                                    $record['date_modified'] = $value;
                                    if (strtotime($value) <= strtotime($param['date_ref'])) {
                                        break 2;
                                    }

                                    $dateRefTmp = $value;
                                }
                            }
                            $result['values'][$id] = $record;
                            $record = [];
                            $id = '';
                            ++$i;
                        }
                    }
                }
            }

            $result['date_ref'] = $now;
            $result['count'] = $i;

            return $result;
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';

            return $result;
        }
    }

    // Permet de renvoyer le mode de la règle en fonction du module target
    // Valeur par défaut "0"
    // Si la règle n'est qu'en création, pas en modicication alors le mode est C
    public function getRuleMode($module, $type): array
    {
        if (
                'target' == $type
            && in_array($module, ['Events', 'Attendees', 'Users'])
        ) {
            return [
                'C' => 'create_only',
            ];
        }

        return parent::getRuleMode($module, $type);
    }

    // Fonction permettant de faire l'appel REST
    protected function call($url, $parameters)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/x-www-form-urlencoded']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer '.$this->token]);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $json_data = curl_exec($ch);
            $resp_info = curl_getinfo($ch);
            curl_close($ch);

            $response = get_object_vars(json_decode($json_data));

            return $response;
        } catch (\Exception $e) {
            return false;
        }
    }
}

class eventbrite extends eventbritecore
{
}
