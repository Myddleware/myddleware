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
//use Psr\LoggerInterface;

// Librairie prestashop
require_once('lib/lib_prestashop.php');

class prestashopcore extends solution {

	protected $required_fields =  array(
										'default' => array('id', 'date_upd', 'date_add'),
										'product_options' => array('id'),
										'product_option_values' => array('id'),
										'combinations' => array('id'),
										'order_histories' => array('id', 'date_add'),
								);
	
	protected $notWrittableFields = array('products' => array('manufacturer_name', 'quantity'));
	
	// Module dépendants du langage
	protected $moduleWithLanguage = array('products');
	
	// Module without reference date
	protected $moduleWithoutReferenceDate = array('order_details','product_options','product_option_values','combinations');

	protected $required_relationships = array(
												'default' => array()
											);

	protected $fieldsIdNotRelate = array('id_gender', 'id_supply_order_state');

	// List of relationship many to many in Prestashop. We create a module to transform it in 2 relationships one to many.
	protected $module_relationship_many_to_many = array(
														'groups_customers' => array('label' => 'Association groups - customers', 'fields' => array(), 'relationships' => array('customer_id','groups_id'), 'searchModule' => 'customers', 'subModule' => 'groups', 'subData' => 'group'),
														'carts_products' => array('label' => 'Association carts - products', 'fields' => array('quantity'=>'Quantity'), 'relationships' => array('id_product','id_product_attribute','id_address_delivery'), 'searchModule' => 'cart', 'subModule' => 'cart_rows', 'subData' => 'cart_row'),
														'products_options_values' => array('label' => 'Association products options - values', 'fields' => array(), 'relationships' => array('product_option_id','product_option_values_id'), 'searchModule' => 'product_options', 'subModule' => 'product_option_values', 'subData' => 'product_option_value'),
														'products_combinations' => array('label' => 'Association products - combinations', 'fields' => array(), 'relationships' => array('product_id','combinations_id'), 'searchModule' => 'products', 'subModule' => 'combinations', 'subData' => 'combination'),
														'combinations_product_options_values' => array('label' => 'Association combinations - product options values', 'fields' => array(), 'relationships' => array('combination_id','product_option_values_id'), 'searchModule' => 'combinations', 'subModule' => 'product_option_values', 'subData' => 'product_option_value'),
														'combinations_images' => array('label' => 'Association combinations - images', 'fields' => array(), 'relationships' => array('combination_id','images_id'), 'searchModule' => 'combinations', 'subModule' => 'images', 'subData' => 'image'),
														);
	
	private $webService;
	
	// Listes des modules et des champs à exclure de Salesforce
	protected $exclude_module_list = array(
										'default' => array(),
										'target' => array(),
										'source' => array()
									);
	protected $exclude_field_list = array();
	
	protected $FieldsDuplicate = array('customers' => array('email'));
	
	protected $threadStatus = array('open'=>'open','closed'=>'closed','pending1'=>'pending1','pending2'=>'pending2');

	// Connexion à Salesforce - Instancie la classe salesforce et affecte access_token et instance_url
    public function login($paramConnexion) {
    	parent::login($paramConnexion);	
		try { // try-catch Myddleware
			try{ // try-catch PrestashopWebservice
				$this->webService = new \PrestaShopWebservice($this->paramConnexion['url'], $this->paramConnexion['apikey'], false);
		
				// Pas de resource à préciser pour la connexion
				$opt['resource'] = '';
				
				// Call
				$xml = $this->webService->get($opt);

				// Si le call s'est déroulé sans Exceptions, alors connexion valide
				$this->connexion_valide = true;
			}
			catch (\PrestaShopWebserviceException $e)
			{
				// Here we are dealing with errors
				$trace = $e->getTrace();
				if ($trace[0]['args'][0] == 401) throw new \Exception('Bad auth key');
				else throw new \Exception($e->getMessage());
			}
		}
		catch (\Exception $e) {
			$error = 'Failed to login to Prestashop : '.$e->getMessage();
			echo $error . ';';
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)

	// Liste des paramètres de connexion
	public function getFieldsLogin() {	
        return array(
					array(
							'name' => 'url',
							'type' => 'text',
							'label' => 'solution.fields.url'
						),
                   array(
                            'name' => 'apikey',
                            'type' => 'password',
                            'label' => 'solution.fields.apikey'
                        )
        );
	} // getFieldsLogin()

	// Renvoie les modules disponibles
	public function get_modules($type = 'source') {
		if($type == 'source') {
			try { // try-catch Myddleware
				try{ // try-catch PrestashopWebservice
					$opt['resource'] = '';
					$xml = $this->webService->get($opt);
					$presta_data = json_decode(json_encode((array) $xml), true);
					
					foreach ($presta_data['api'] as $module => $value) {
						if($module == "@attributes") continue;
						// On ne renvoie que les modules autorisés
						if (!in_array($module,$this->exclude_module_list)) {
							$modules[$module] = $value['description'];
						}
					}
					// Création des modules type relationship
					foreach ($this->module_relationship_many_to_many as $key => $value){
						$modules[$key] = $value['label'];
					}
					
					return ((isset($modules)) ? $modules : false );
				}
				catch (\PrestaShopWebserviceException $e)
				{
					// Here we are dealing with errors
					$trace = $e->getTrace();
					if ($trace[0]['args'][0] == 401) throw new \Exception('Bad auth key');
					else throw new \Exception('Call failed '.$e->getTrace());
				}
			}
			catch (\Exception $e) {
				$error = $e->getMessage();
			}
		} else {
			$modulesSource = $this->get_modules("source");
			$authorized = array("customers" => "The e-shop's customers",
								"customer_threads" => "Customer services threads",
								"customer_messages" => "Customer services messages",
								"products" => "The products"
								);
			
			return array_intersect_key($authorized, $modulesSource);
		}
	} // get_modules()

	// Renvoie les champs du module passé en paramètre
	public function get_module_fields($module, $type = 'source', $extension = false) {
		parent::get_module_fields($module, $type, $extension);
		try { // try-catch Myddleware
			// Si le module est un module "fictif" relation créé pour Myddleware
			if(array_key_exists($module, $this->module_relationship_many_to_many)) {

				foreach ($this->module_relationship_many_to_many[$module]['fields'] as $name => $value) {
					$this->moduleFields[$name] = array(
												'label' => $name,
												'type' => 'varchar(255)',
												'type_bdd' => 'varchar(255)',
												'required' => 0
											);						
				}
				foreach ($this->module_relationship_many_to_many[$module]['relationships'] as $relationship) {
					$this->fieldsRelate[$relationship] = array(
												'label' => $relationship,
												'type' => 'varchar(255)',
												'type_bdd' => 'varchar(255)',
												'required' => 0,
												'required_relationship' => 1
											);
				}
				// Ajout des champ relate au mapping des champs 
				if (!empty($this->fieldsRelate)) {
					$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
				}
				return $this->moduleFields;
			}

			try{ // try-catch PrestashopWebservice
				$opt['resource'] = $module.'?schema=synopsis';
				
				// Call
				$xml = $this->webService->get($opt);
				
				$presta_data = json_decode(json_encode((array) $xml->children()->children()), true);
				foreach ($presta_data as $presta_field => $value) {
					if(in_array($presta_field, $this->fieldsIdNotRelate)){
						$this->moduleFields[$presta_field] = array(
										'label' => $presta_field,
										'type' => 'varchar(255)',
										'type_bdd' => 'varchar(255)',
										'required' => false
									);
						if($presta_field == 'id_gender'){
							$this->moduleFields['id_gender']['option'] = array('1' => 'Mr.', '2' => 'Mrs.');
						}
						continue;
					}
					if (
							substr($presta_field,0,3) == 'id_'
						 || substr($presta_field,-3) == '_id'
					) {
						$this->fieldsRelate[$presta_field] = array(
													'label' => $presta_field,
													'type' => 'varchar(255)',
													'type_bdd' => 'varchar(255)',
													'required' => 0,
													'required_relationship' => 0
												);
					}
					elseif(empty($value)){
							$this->moduleFields[$presta_field] = array(
											'label' => $presta_field,
											'type' => 'varchar(255)',
											'type_bdd' => 'varchar(255)',
											'required' => false
										);
					} else {
						if($presta_field == "associations") continue;
						$this->moduleFields[$presta_field] = array(
											'label' => $presta_field,
											'type' => 'varchar(255)',
											'type_bdd' => 'varchar(255)',
											'required' => false
										);
						if(isset($value['@attributes']['format'])){
							$this->moduleFields[$presta_field]['type'] = $value['@attributes']['format'];
						}
						if(isset($value['@attributes']['required'])){
							$this->moduleFields[$presta_field]['required'] = true;
						}
					}
				}
				// Récupération des listes déroulantes
				if($module == 'orders' && isset($this->moduleFields['current_state'])) {
					$order_states = $this->getList('order_state','order_states');				
					$this->moduleFields['current_state']['option'] = $order_states;
				}
				if($module == 'order_histories' && isset($this->fieldsRelate['id_order_state'])) {
					$order_states = $this->getList('order_state','order_states');			
					$this->fieldsRelate['id_order_state']['option'] = $order_states;
				}
				if($module == 'supply_orders' && isset($this->moduleFields['id_supply_order_state'])) {
					$supply_order_states = $this->getList('supply_order_state','supply_order_states');
					$this->moduleFields['id_supply_order_state']['option'] = $supply_order_states;
				}
				// Ticket 450: Si c'est le module customer service messages, on rend la relation id_customer_thread obligatoire
				if($module == "customer_messages") {
					$this->fieldsRelate['id_customer_thread']['required_relationship'] = 1;
				}
				if($module == "customer_threads") {
					$languages = $this->getList('language','languages');
					$this->moduleFields['id_lang']['option'] = $languages;
					$this->moduleFields['id_lang']['required'] = 1;
					$contacts = $this->getList('contact','contacts');
					$this->moduleFields['id_contact']['option'] = $contacts;
					$this->moduleFields['id_contact']['required'] = 1;
					// Les status de thread ne semblent pas être une ressource donc on met la liste en dur via un attribut facile à redéfinir)
					$this->moduleFields['status']['option'] = $this->threadStatus;
					// Le champ token est renseigné dans le create directement
					unset($this->moduleFields['token']);
				}
				// On enlève les champ date_add et date_upd si le module est en target 
				if ($type == 'target') {
					if (!empty($this->moduleFields['date_add'])) {
						unset($this->moduleFields['date_add']);
					}
					if (!empty($this->moduleFields['date_upd'])) {
						unset($this->moduleFields['date_upd']);
					}
				}

				// Ajout des champ relate au mapping des champs 
				if (!empty($this->fieldsRelate)) {
					$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
				}

				// Si l'extension est demandée alors on vide relate 
				if ($extension) {
					$this->fieldsRelate = array();
				}
				// Si le module est order_histories alors on ajoute les champs ID dans les champs diponible dans le mapping des champs
				if (
						$module == 'order_histories'
					&&	$type == 'source'
				) {
					$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
				}
				return $this->moduleFields;
			}
			catch (\PrestaShopWebserviceException $e)
			{
				// Here we are dealing with errors
				$trace = $e->getTrace();
				if ($trace[0]['args'][0] == 401) throw new \Exception('Bad auth key');
				else throw new \Exception('Call failed '.$e->getTrace());
			}
		}
		catch (\Exception $e) {
			$error = $e->getMessage();
			return false;
		}
	} // get_module_fields($module)	
	
	
	// Fonction permettant de récupérer les listes déroulantes
	protected function getList($field,$fields) {
		$opt = array(
			'resource' => $fields
		);
		
		// Call
		$xml = $this->webService->get($opt);
	
		$xml = $xml->asXML();
		$simplexml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		$records = json_decode(json_encode((array) $simplexml->children()->children()), true);
		//$nbStates = sizeof($states['order_state']);	
		foreach ($records[$field] as $record) {
			$opt = array(
				'resource' => $fields,
				'id' => $record['@attributes']['id']
			);
			// Call
			$xml = $this->webService->get($opt);
			$xml = $xml->asXML();
			$simplexml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
			$state = json_decode(json_encode((array) $simplexml->children()->children()), true);	
		
			// S'il y a une langue on prends la liste dans le bon language
			if (!empty($state['name']['language'])) {
				// We don't know the language here because the user doesn't chose it yet. So we take the first one.
				$list[$record['@attributes']['id']] = (is_array($state['name']['language']) ? current($state['name']['language']) : $state['name']['language']);
			}
			// Sinon on prend sans la langue (utile pour la liste language par exemple)
			elseif (!empty($state['name'])) {
				$list[$record['@attributes']['id']] = $state['name'];
			}
		}
		if (!empty($list)) {
			return $list;
		}
		return null;
	}

	// Conversion d'un SimpleXMLObject en array
	public function xml2array ( $xmlObject, $out = array () )
	{
	    foreach ( (array) $xmlObject as $index => $node )
	        $out[$index] = ( is_object ( $node ) ) ? $this->xml2array ( $node ) : $node;
	
	    return $out;
	} // xml2array ( $xmlObject, $out = array () )
	
	
	// Permet de récupérer le dernier enregistrement de la solution (utilisé pour tester le flux)
	public function read_last($param) {	
		try { // try-catch Myddleware
			try{ // try-catch PrestashopWebservice
				$result = array();
				
				if(array_key_exists($param['module'], $this->module_relationship_many_to_many)) {
					$result['done'] = true;					
					return $result;
				}
				
				if(!isset($param['fields'])) {
					$param['fields'] = array();
				}
				// Ajout des champs obligatoires
				$param['fields'] = $this->addRequiredField($param['fields'],$param['module']);
				$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
				
				// Le champ current_state n'est plus lisible (même s'il est dans la liste des champs disponible!) dans Prestashop 1.6.0.14, il faut donc le gérer manuellement
				$getCurrentState = false;
				if(
						$param['module'] == 'orders'
					&& in_array('current_state',$param['fields'])	
				){
					$getCurrentState = true;
					unset($param['fields'][array_search('current_state', $param['fields'])]);
				}
				
				$opt['limit'] = 1;
				$opt['resource'] = $param['module'].'&date=1';
				$opt['display'] = '[';
				foreach ($param['fields'] as $field) {
					$opt['display'] .= $field.',';
				}
				$opt['display'] = substr($opt['display'], 0, -1); // Suppression de la dernière virgule
				$opt['display'] .= ']';
				
				// On trie que si la référence est une date
				if ($this->referenceIsDate($param['module'])) {
					$dateRefField = $this->getDateRefName($param['module'], '0');
					if($dateRefField == 'date_add') {
						$opt['sort'] = '[date_add_ASC]';
					} else {
						$opt['sort'] = '[date_upd_ASC]';
					}
				}

				// Si le tableau de requête est présent alors construction de la requête
				if (!empty($param['query'])) {
					// Building of the option array
					if(!empty($param['query']['id'])) {
						$options['id'] = (int) $param['query']['id'];
					}
					else {
						foreach ($param['query'] as $key => $value) {
							$options['filter['.$key.']'] = '['.$value.']';
						}
					}					
					$options['resource'] = $param['module'];
					$xml = $this->webService->get($options);		

					// If we search by ID we get directly all the data of the record
					$resources = $xml->children()->children();
					// Otherwise we have to get it if a ressource has been found
					if(
							!empty($resources) 
						AND empty($param['query']['id'])
					) {
						// Get the id of the record
						foreach($resources->attributes() as $key => $value) {
							if ($key == 'id') {
								$optionsDetail['id'] = (int) $value;
								$optionsDetail['resource'] = $param['module'];
								$xml = $this->webService->get($optionsDetail);	
								$resources = $xml->children()->children();
								break;
							}
						}
					}
					
					// Creation of the output parameter
					if(empty($resources->id)) {
						$result['done'] = false;
					} else {
						$result['done'] = true;
						foreach ($resources as $key => $resource)
						{
							if(in_array($key, $param['fields']))
								$result['values'][$key] = (string) $resource;
						}
					}				
					return $result;
				}
					
				// Call when there is no query (simulation)
				$xml = $this->webService->get($opt);
				$xml = $xml->asXML();
				$simplexml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
				
				if(empty(json_decode(json_encode((array) $simplexml->children()->children()), true))){
					$result['done'] = false;
				} else {
					$result['done'] = true;
					foreach ($simplexml->children()->children() as $data) {
						foreach ($data as $key => $value) {
							if(isset($value->language)){
								$result['values'][$key] = (string) $value->language;
							} else {
								$result['values'][$key] = (string) $value;
							}
						}
					}
				}
				// Récupération du statut courant de la commande si elle est demandée
				if ($getCurrentState) {
					$optState['limit'] = 1;
					$optState['resource'] = 'order_histories&date=1';
					$optState['display'] = '[id_order_state]';
					$optState['filter[id_order]'] = '['.$data->id.']';
					$optState['sort'] = '[date_add_DESC]';
					$xml = $this->webService->get($optState);
					$xml = $xml->asXML();
					$simplexml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

					$currentState = $simplexml->children()->children();
					if (!empty($currentState)) {
						$result['values']['current_state'] = (string)$currentState->order_history->id_order_state;
					}
				}
				return $result;	
			}
			catch (\PrestaShopWebserviceException $e)
			{
				// Here we are dealing with errors
				$trace = $e->getTrace();
				if ($trace[0]['args'][0] == 401) throw new \Exception('Bad auth key');
				else {
					throw new \Exception($e->getMessage());
				}
			}
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;	
			return $result;
		}
	} // read_last($param)	
	
	// Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
	public function read($param) {
		try { // try-catch Myddleware
			// traitement spécial pour module de relation Customers / Groupe
			if(array_key_exists($param['module'], $this->module_relationship_many_to_many)) {
				$result = $this->readManyToMany($param);
				return $result;
			}
			
			// On va chercher le nom du champ pour la date de référence: Création ou Modification
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
			
			try{ // try-catch PrestashopWebservice
				$result = array();
				if (empty($param['limit'])) {
					$param['limit'] = 100;
				}
				// Ajout des champs obligatoires
				$param['fields'] = $this->addRequiredField($param['fields'],$param['module']);
				$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
				
				// Le champ current_state n'est plus lisible (même s'il est dans la liste des champs disponible!) dans Prestashop 1.6.0.14, il faut donc le gérer manuellement
				$getCurrentState = false;
				if(
						$param['module'] == 'orders'
					&& in_array('current_state',$param['fields'])	
				){
					$getCurrentState = true;
					unset($param['fields'][array_search('current_state', $param['fields'])]);
				}
					
				$opt['limit'] = $param['limit'];
				$opt['resource'] = $param['module'].'&date=1';
				$opt['display'] = '[';
				foreach ($param['fields'] as $field) {
					// On ne demande pas les champs spécifiques à Myddleware
					if (!in_array($field, array('Myddleware_element_id','my_value'))) {
						$opt['display'] .= $field.',';
					}
				}

				$opt['display'] = substr($opt['display'], 0, -1); // Suppression de la dernière virgule
				$opt['display'] .= ']';
				
				// Query creation
				// if a specific query is requeted we don't use date_ref
				if (!empty($param['query'])) {
					foreach ($param['query'] as $key => $value) {
						$opt['filter['.$key.']'] = '['.$value.']';
					}
				}
				else{
					// Si la référence est une date alors la requête dépend de la date
					if ($this->referenceIsDate($param['module'])) {
						if($dateRefField == 'date_add') {
							$opt['filter[date_add]'] = '[' . $param['date_ref'] .',9999-12-31 00:00:00]';
							
							$opt['sort'] = '[date_add_ASC]';
						} else {
							$opt['filter[date_upd]'] = '[' . $param['date_ref'] .',9999-12-31 00:00:00]';
							
							$opt['sort'] = '[date_upd_ASC]';
						}
					}
					// Si la référence n'est pas une date alors c'est l'ID de prestashop
					else {
						if ($param['date_ref'] == '') {
							$param['date_ref'] = 1;
						}
						$opt['filter[id]'] = '[' . $param['date_ref'] .',999999999]';
						$opt['sort'] = '[id_ASC]';
					}
				}				
				// Call						
				$xml = $this->webService->get($opt);
				$xml = $xml->asXML();
				$simplexml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);							
				$result['count'] = $simplexml->children()->children()->count();
				
				$record = array();
				foreach ($simplexml->children()->children() as $data) {
					if (!empty($data)) {			
						foreach ($data as $key => $value) {
							// Si la clé de référence est une date
							if (
									$this->referenceIsDate($param['module'])
								&& $key == $dateRefField
							) {
								// Ajout d'un seconde à la date de référence pour ne pas prendre 2 fois la dernière commande
								$date_ref = date_create($value);
								date_modify($date_ref, '+1 seconde');							
								$result['date_ref'] = date_format($date_ref, 'Y-m-d H:i:s');

								$record['date_modified'] = (string)$value;
								continue;
							}
							// Si la clé de référence est un id et que celui-ci est supérieur alors on sauvegarde cette référence
							elseif (
									!$this->referenceIsDate($param['module'])
								&& $key == 'id'
								&& (
										empty($result['date_ref'])
									 || (
											!empty($result['date_ref'])
										&&	$value >= $result['date_ref']
									)
								)
							) {
								// Ajout de 1 car le filtre de la requête inclus la valeur minimum
								$result['date_ref'] = $value + 1;
								// Une date de modification est mise artificiellement car il n'en existe pas dans le module
								$record['date_modified'] = (string)date('Y-m-d H:i:s');
							}
							if(isset($value->language)){
								$record[$key] = (string) $value->language;
							} else {
								$record[$key] = (string)$value;
							}
							
						}	
						// Récupération du statut courant de la commande si elle est demandée
						if ($getCurrentState) {
							$optState['limit'] = 1;
							$optState['resource'] = 'order_histories&date=1';
							$optState['display'] = '[id_order_state]';
							$optState['filter[id_order]'] = '['.$data->id.']';
							$optState['sort'] = '[date_add_DESC]';
							$xml = $this->webService->get($optState);
							$xml = $xml->asXML();
							$simplexml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

							$currentState = $simplexml->children()->children();
							if (!empty($currentState)) {
								$record['current_state'] = (string)$currentState->order_history->id_order_state;
							}
						}				
						$result['values'][(string)$data->id] = $record;
						$record = array();
					}
				}					
			}
			catch (\PrestaShopWebserviceException $e)
			{
				// Here we are dealing with errors
				$trace = $e->getTrace();
				if ($trace[0]['args'][0] == 401) {
					throw new \Exception('Bad auth key');
				}
				else {
					throw new \Exception('Call failed '.$e->getMessage());
				}
			}
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
		}	
		return $result;
	} // read($param)
	
	// Read pour les modules fictifs sur les relations many to many
	protected function readManyToMany($param){
		try { // try-catch Myddleware	
			// On va chercher le nom du champ pour la date de référence: Création ou Modification
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);			
			try{ // try-catch PrestashopWebservice
				$result = array();
				// Init parameter to read in Prestashop
				$searchModule = $this->module_relationship_many_to_many[$param['module']]['searchModule'];
				$subModule = $this->module_relationship_many_to_many[$param['module']]['subModule'];
				$subData = $this->module_relationship_many_to_many[$param['module']]['subData'];
				
				// Ajout des champs obligatoires
				$param['fields'] = $this->addRequiredField($param['fields'],$searchModule);				
				$opt['resource'] = $searchModule.'&date=1';			
				$opt['display'] = 'full';		
				
				// Query creation
				// if a specific query is requeted we don't use date_ref
				if (!empty($param['query'])) {
					foreach ($param['query'] as $key => $value) {
						$opt['filter['.$key.']'] = '['.$value.']';
					}
				}
				else{
					// Si la référence est une date alors la requête dépend de la date
					if ($this->referenceIsDate($searchModule)) {
						if($dateRefField == 'date_add') {
							$opt['filter[date_add]'] = '[' . $param['date_ref'] .',9999-12-31 00:00:00]';
							
							$opt['sort'] = '[date_add_ASC]';
						} else {
							$opt['filter[date_upd]'] = '[' . $param['date_ref'] .',9999-12-31 00:00:00]';
							
							$opt['sort'] = '[date_upd_ASC]';
						}
					}
					// Si la référence n'est pas une date alors c'est l'ID de prestashop
					else {
						if ($param['date_ref'] == '') {
							$param['date_ref'] = 1;
						}
						$opt['filter[id]'] = '[' . $param['date_ref'] .',999999999]';
						$opt['sort'] = '[id_ASC]';
					}
				}

				// Call
				$xml = $this->webService->get($opt);
				$xml = $xml->asXML();
				$simplexml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

				$cpt = 0;			
				$record = array();
				foreach ($simplexml->children()->children() as $resultRecord) {
					foreach ($resultRecord as $key => $value) {
						// Si la clé de référence est une date
						if (
								$this->referenceIsDate($searchModule)
							&& $key == $dateRefField
						) {
							// Ajout d'un seconde à la date de référence pour ne pas prendre 2 fois la dernière commande
							$date_ref = date_create($value);
							date_modify($date_ref, '+1 seconde');							
							$result['date_ref'] = date_format($date_ref, 'Y-m-d H:i:s');
							$record['date_modified'] = (string)$value;
							continue;
						}
						// Si la clé de référence est un id et que celui-ci est supérieur alors on sauvegarde cette référence
						elseif (
								!$this->referenceIsDate($searchModule)
							&& $key == 'id'
							&& (
									empty($result['date_ref'])
								 || (
										!empty($result['date_ref'])
									&&	$value >= $result['date_ref']
								)
							)
						) {
							// Ajout de 1 car le filtre de la requête inclus la valeur minimum
							$result['date_ref'] = $value + 1;
							// Une date de modification est mise artificiellement car il n'en existe pas dans le module
							$record['date_modified'] = (string)date('Y-m-d H:i:s');
						}
						if(isset($value->language)){
							$record[$key] = (string) $value->language;
						} else {
							$record[$key] = (string)$value;
						}				
				
						if($key == 'associations'){
							foreach ($resultRecord->associations->$subModule->$subData as $data) {
								$subRecord = array();
								$idRelation = (string) $resultRecord->id . '_' . (string) $data->id;
								$subRecord[$this->module_relationship_many_to_many[$param['module']]['relationships'][0]] = (string) $resultRecord->id;
								$subRecord[$this->module_relationship_many_to_many[$param['module']]['relationships'][1]] = (string) $data->id;
								$subRecord['id'] = $idRelation;
								$subRecord['date_modified'] = $record['date_modified'];
								$result['values'][$idRelation] = $subRecord;
								$cpt++;
							}
						}
						
					}
					$record = array();
				}
				$result['count'] = $cpt;
			}
			catch (\PrestaShopWebserviceException $e)
			{
				// Here we are dealing with errors
				$trace = $e->getTrace();
				if ($trace[0]['args'][0] == 401) {
					throw new \Exception('Bad auth key');
				}
				else {
					throw new \Exception('Call failed '.$e->getMessage());
				}
			}
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
		}		
		return $result;
	}// readManyToMany($param)
	
	// Permet de créer des données
	public function create($param) {
		foreach($param['data'] as $idDoc => $data) {
			// Check control before create
			$data = $this->checkDataBeforeCreate($param, $data);
			// on ajoute le token pour le module customer_threads 
			if($param['module'] == "customer_threads") {
				$data['token'] = 'token';	
			}
			try{ // try-catch Myddleware
				try{ // try-catch PrestashopWebservice
				    $fields = array();						
					$opt = array(
					    'resource' => $param['module'].'?schema=blank',
					);
					
					// Call
					$xml = $this->webService->get($opt);
					$modele = $xml->children()->children();
								
					$toSend = $xml->children()->children();
					foreach ($modele as $nodeKey => $node){
						if(isset($data[$nodeKey])) {
							// If we use an element with language, we update only the language selected
							if (!empty($modele->$nodeKey->children())) {
								$i = 0;
								$languageFound = false;
								foreach($modele->$nodeKey->children() as $node) {
									if ($node->attributes() == $param['ruleParams']['language']) {
										$toSend->$nodeKey->language[$i][0] = $data[$nodeKey];
										$languageFound = true;
									}
									$i++;
								}
								if (!$languageFound) {
									throw new \Exception('Failed to find the language '.$param['ruleParams']['language'].' in the Prestashop XML');
								}
							}
							else {
								$toSend->$nodeKey = $data[$nodeKey];
							}
						}
					}			
					
					if(isset($toSend->message)) {
						$toSend->message = str_replace(chr(13).chr(10), "\n", $toSend->message);
						$toSend->message = str_replace(chr(13), "\n", $toSend->message);
						$toSend->message = str_replace(chr(10), "\n", $toSend->message);
					}

					$opt = array(
						'resource' => $param['module'],
						'postXml' => $xml->asXML()
					);
				
					$new = $this->webService->add($opt);
					$result[$idDoc] = array(
							'id' => (string)$new->children()->children()->id,
							'error' => false
					);	
				}
				catch (\PrestaShopWebserviceException $e)
				{
					// Here we are dealing with errors
					$trace = $e->getTrace();
					if ($trace[0]['args'][0] == 401) {
						throw new \Exception('Bad auth key');
					}
					else {
						throw new \Exception("Please check your data." . $e->getMessage());
					}
				}
			}
			catch (\Exception $e) {
				$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}
		return $result;
	} // create($param)
	
	// Permet de modifier des données
	public function update($param) {
		foreach($param['data'] as $idDoc => $data) {
			try{ // try-catch Myddleware
				try{ // try-catch PrestashopWebservice
					// Check control before update
					$data = $this->checkDataBeforeUpdate($param, $data);
				    $fields = array();							
					$opt = array(
					    'resource' => $param['module'],
					    'id' => (int) $data['target_id']
					);
					
					// Call
					$xml = $this->webService->get($opt);
			
					$toUpdate = $xml->children()->children();
					$modele = $xml->children()->children();
					
					foreach ($modele as $nodeKey => $node){
						if(isset($data[$nodeKey])) {
							// If we use an element with language, we update only the language selected
							if (!empty($modele->$nodeKey->children())) {
								$i = 0;
								$languageFound = false;
								foreach($modele->$nodeKey->children() as $node) {
									if ($node->attributes() == $param['ruleParams']['language']) {
										$toUpdate->$nodeKey->language[$i][0] = $data[$nodeKey];
										$languageFound = true;
									}
									$i++;
								}
								if (!$languageFound) {
									throw new \Exception('Failed to find the language '.$param['ruleParams']['language'].' in the Prestashop XML');
								}
							}
							else {
								$toUpdate->$nodeKey = $data[$nodeKey];
							}
						}
					}
			
					// We remove non writtable fields
					if (!empty($this->notWrittableFields[$param['module']])) {
						foreach($this->notWrittableFields[$param['module']] as $notWrittableField) {
							unset($xml->children()->children()->$notWrittableField);
						}
					}
					
					if(isset($toSend->message)) {
						$toUpdate->message = str_replace(chr(13).chr(10), "\n", $toUpdate->message);
						$toUpdate->message = str_replace(chr(13), "\n", $toUpdate->message);
						$toUpdate->message = str_replace(chr(10), "\n", $toUpdate->message);
					}

				
					$opt = array(
						'resource' => $param['module'],
						'putXml' => $xml->asXML(),
						'id' => (int) $data['target_id']
					);
					$new = $this->webService->edit($opt);		
					$result[$idDoc] = array(
							'id' => $data['target_id'],
							'error' => false
					);	
				}
				catch (\PrestaShopWebserviceException $e)
				{
					// Here we are dealing with errors
					$trace = $e->getTrace();
					if ($trace[0]['args'][0] == 500) {
						$result[$idDoc] = array(
								'id' => $data['target_id'],
								'error' => false
						);
					}
					else if ($trace[0]['args'][0] == 401) {
						throw new \Exception('Bad auth key');
					}
					else {
						throw new \Exception("Please check your data." . $e->getMessage());
					}
				}
			}
			catch (\Exception $e) {
				$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}		
		return $result;
	} // update($param)	
	
	// Permet de renvoyer le mode de la règle en fonction du module target
	// Valeur par défaut "0"
	// Si la règle n'est qu'en création, pas en modicication alors le mode est C
	public function getRuleMode($module,$type) {
		if(
				$type == 'target'
			&&	in_array($module, array('customer_messages'))
		) { // Si le module est dans le tableau alors c'est uniquement de la création
			return array(
				'C' => 'create_only'
			);
		}
		return parent::getRuleMode($module,$type);
	}
	
	// Renvoie le nom du champ de la date de référence en fonction du module et du mode de la règle
	public function getDateRefName($moduleSource, $RuleMode) {
		// Le module order_histories n'a que date_add
		if (in_array($moduleSource, array('order_histories'))) {
			return "date_add";
		}
		if($RuleMode == "0") {
			return "date_upd";
		} else if ($RuleMode == "C"){
			return "date_add";
		} else {
			throw new \Exception ("$RuleMode is not a correct Rule mode.");
		}
		return null;
	}
	
	// Permet d'indiquer le type de référence, si c'est une date (true) ou un texte libre (false)
	public function referenceIsDate($module) {
		// Le module order détail n'a pas de date de référence. On utilise donc l'ID comme référence
		if(in_array($module, $this->moduleWithoutReferenceDate)){
			return false;
		}
		return true;
	}
	
	// Permet de renvoyer l'id de la table en récupérant la table liée à la règle ou en la créant si elle n'existe pas
	public function getFieldsParamUpd($type, $module, $myddlewareSession) {	
		try {
			if (
					$type == 'target'
				&& in_array($module,$this->moduleWithLanguage)	
			){
				$params = array();
				$languages = $this->getList('language','languages');
				if(!empty($languages)) {
					$idParam = array(
								'id' => 'language',
								'name' => 'language',
								'type' => 'option',
								'label' => 'Language',
								'required'	=> true
							);
					foreach ($languages as $key => $value) {
						$idParam['option'][$key] = $value;
					}
					$params[] = $idParam;
				}			
				return $params;
			}
			return array();
		}
		catch (\Exception $e){
			return array();
			//return $e->getMessage();
		}
	}
	
	// Fonction permettant de faire l'appel REST
	protected function call($url, $parameters){	

    } // call($method, $parameters)
}// class prestashopcore

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/prestashop.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class prestashop extends prestashopcore {
		
	}
}