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

use App\Solutions\lib\PrestaShopWebservice;
use App\Solutions\lib\PrestaShopWebserviceException;
//use Psr\LoggerInterface;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class prestashop extends solution
{
    protected array $required_fields = [
        'default' => ['id', 'date_upd', 'date_add'],
        'product_options' => ['id'],
        'product_option_values' => ['id'],
        'combinations' => ['id'],
        'stock_availables' => ['id'],
        'order_histories' => ['id', 'date_add'],
        'order_details' => ['id'],
        'customer_messages' => ['id', 'date_add'],
        'order_carriers' => ['id', 'date_add'],
        'order_payments' => ['id', 'date_add', 'order_reference'],
        'shop_urls' => ['id'],
    ];

    protected array $notWrittableFields = ['products' => ['manufacturer_name', 'quantity']];

    // Module dépendants du langage
    protected array $moduleWithLanguage = ['products', 'categories'];

    // Module without reference date
    protected array $moduleWithoutReferenceDate = ['order_details', 'product_options', 'product_option_values', 'combinations', 'carriers', 'stock_availables','shop_urls'];

    protected $required_relationships = [
        'default' => [],
    ];

    protected array $fieldsIdNotRelate = ['id_gender', 'id_supply_order_state'];

    // List of relationship many to many in Prestashop. We create a module to transform it in 2 relationships one to many.
    protected array $module_relationship_many_to_many = [
        'groups_customers' => ['label' => 'Association groups - customers', 'fields' => [], 'relationships' => ['customer_id', 'groups_id'], 'searchModule' => 'customers', 'subModule' => 'groups', 'subData' => 'group'],
        'carts_products' => ['label' => 'Association carts - products', 'fields' => ['quantity' => 'Quantity', 'id_product_attribute' => 'id_product_attribute', 'id_address_delivery' => 'id_address_delivery'], 'relationships' => ['cart_id', 'id_product'], 'searchModule' => 'carts', 'subModule' => 'cart_rows', 'subData' => 'cart_row', 'subDataId' => 'id_product'],
        'products_options_values' => ['label' => 'Association products options - values', 'fields' => [], 'relationships' => ['product_option_id', 'product_option_values_id'], 'searchModule' => 'product_options', 'subModule' => 'product_option_values', 'subData' => 'product_option_value'],
        'products_categories' => ['label' => 'Association products - categories', 'fields' => [], 'relationships' => ['product_id', 'categories_id'], 'searchModule' => 'products', 'subModule' => 'categories', 'subData' => 'category'],
        'products_combinations' => ['label' => 'Association products - combinations', 'fields' => [], 'relationships' => ['product_id', 'combinations_id'], 'searchModule' => 'products', 'subModule' => 'combinations', 'subData' => 'combination'],
        'combinations_product_options_values' => ['label' => 'Association combinations - product options values', 'fields' => [], 'relationships' => ['combination_id', 'product_option_values_id'], 'searchModule' => 'combinations', 'subModule' => 'product_option_values', 'subData' => 'product_option_value'],
        'combinations_images' => ['label' => 'Association combinations - images', 'fields' => [], 'relationships' => ['combination_id', 'images_id'], 'searchModule' => 'combinations', 'subModule' => 'images', 'subData' => 'image'],
    ];

    private $webService;

    // Listes des modules et des champs à exclure de Salesforce
    protected array $exclude_module_list = [
        'default' => [],
        'target' => [],
        'source' => [],
    ];
    protected array $exclude_field_list = [];

    protected array $FieldsDuplicate = [
        'customers' => ['email'],
        'products' => ['ean13', 'name', 'reference'],
        'stock_availables' => ['id_product'],
    ];

    protected array $threadStatus = ['open' => 'open', 'closed' => 'closed', 'pending1' => 'pending1', 'pending2' => 'pending2'];

    // Connexion à Salesforce - Instancie la classe salesforce et affecte access_token et instance_url
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try { // try-catch Myddleware
            try { // try-catch PrestashopWebservice
                $this->webService = new PrestaShopWebservice($this->paramConnexion['url'], $this->paramConnexion['apikey'], false);

                // Pas de resource à préciser pour la connexion
                $opt['resource'] = '';

                // Function to modify opt (used for custom needs)
                $opt = $this->updateOptions('login', $opt, '');
                // Call
                $xml = $this->webService->get($opt);

                // Si le call s'est déroulé sans Exceptions, alors connexion valide
                $this->connexion_valide = true;
            } catch (PrestaShopWebserviceException $e) {
                // Here we are dealing with errors
                $trace = $e->getTrace();
                if (401 == $trace[0]['args'][0]) {
                    throw new \Exception('Bad auth key');
                }
                throw new \Exception($e->getMessage());
            }
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Liste des paramètres de connexion
    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
            [
                'name' => 'apikey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.apikey',
            ],
        ];
    }

    // getFieldsLogin()

    // Renvoie les modules disponibles
    public function get_modules($type = 'source')
    {
        if ('source' == $type) {
            try { // try-catch Myddleware
                try { // try-catch PrestashopWebservice
                    $opt['resource'] = '';
                    // Function to modify opt (used for custom needs)
                    $opt = $this->updateOptions('get_modules', $opt, $type);

                    $xml = $this->webService->get($opt);
                    $presta_data = json_decode(json_encode((array) $xml), true);

                    foreach ($presta_data['api'] as $module => $value) {
                        if ('@attributes' == $module) {
                            continue;
                        }
                        // On ne renvoie que les modules autorisés
                        if (!in_array($module, $this->exclude_module_list)) {
                            $modules[$module] = $value['description'];
                        }
                    }
                    // Création des modules type relationship
                    foreach ($this->module_relationship_many_to_many as $key => $value) {
                        $modules[$key] = $value['label'];
                    }

                    return (isset($modules)) ? $modules : false;
                } catch (PrestaShopWebserviceException $e) {
                    // Here we are dealing with errors
                    $trace = $e->getTrace();
                    if (401 == $trace[0]['args'][0]) {
                        throw new \Exception('Bad auth key');
                    }
                    throw new \Exception('Call failed '.$e->getTrace());
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $modulesSource = $this->get_modules('source');
            $authorized = [
                'categories' => 'The product categories',
                'customers' => 'The e-shop customers',
                'customer_threads' => 'Customer services threads',
                'customer_messages' => 'Customer services messages',
                'order_histories' => 'The Order histories',
                'products' => 'The products',
                'stock_availables' => 'Available quantities',
                'products_categories' => 'Association products - categories',
            ];

            return array_intersect_key($authorized, $modulesSource);
        }
    }
    // Renvoie les champs du module passé en paramètre
    public function get_module_fields($module, $type = 'source', $extension = false): array
    {
        parent::get_module_fields($module, $type, $extension);
        try { // try-catch Myddleware
            // Si le module est un module "fictif" relation créé pour Myddleware
            if (array_key_exists($module, $this->module_relationship_many_to_many)) {
                foreach ($this->module_relationship_many_to_many[$module]['fields'] as $name => $value) {
                    $this->moduleFields[$name] = [
                        'label' => $name,
                        'type' => 'varchar(255)',
                        'type_bdd' => 'varchar(255)',
                        'required' => 0,
                        'relate' => false,
                    ];
                }
                foreach ($this->module_relationship_many_to_many[$module]['relationships'] as $relationship) {
                    $this->moduleFields[$relationship] = [
                        'label' => $relationship,
                        'type' => 'varchar(255)',
                        'type_bdd' => 'varchar(255)',
                        'required' => 0,
                        'required_relationship' => 1,
                        'relate' => true,
                    ];
                }

                return $this->moduleFields;
            }

            try { // try-catch PrestashopWebservice
                $opt['resource'] = $module.'?schema=synopsis';

                // Function to modify opt (used for custom needs)
                $opt = $this->updateOptions('get_module_fields', $opt, $module);

                // Call
                $xml = $this->webService->get($opt);

                $presta_data = json_decode(json_encode((array) $xml->children()->children()), true);
                foreach ($presta_data as $presta_field => $value) {
                    if (in_array($presta_field, $this->fieldsIdNotRelate)) {
                        $this->moduleFields[$presta_field] = [
                            'label' => $presta_field,
                            'type' => 'varchar(255)',
                            'type_bdd' => 'varchar(255)',
                            'required' => false,
                            'relate' => false,
                        ];
                        if ('id_gender' == $presta_field) {
                            $this->moduleFields['id_gender']['option'] = ['1' => 'Mr.', '2' => 'Mrs.'];
                        }
                        continue;
                    }
                    if (
                            'id_' == substr($presta_field, 0, 3)
                         || '_id' == substr($presta_field, -3)
                    ) {
                        $this->moduleFields[$presta_field] = [
                            'label' => $presta_field,
                            'type' => 'varchar(255)',
                            'type_bdd' => 'varchar(255)',
                            'required' => 0,
                            'required_relationship' => 0,
                            'relate' => true,
                        ];
                    } elseif (empty($value)) {
                        $this->moduleFields[$presta_field] = [
                            'label' => $presta_field,
                            'type' => 'varchar(255)',
                            'type_bdd' => 'varchar(255)',
                            'required' => false,
                            'relate' => false,
                        ];
                    } else {
                        if ('associations' == $presta_field) {
                            continue;
                        }
                        $this->moduleFields[$presta_field] = [
                            'label' => $presta_field,
                            'type' => 'varchar(255)',
                            'type_bdd' => 'varchar(255)',
                            'required' => false,
                            'relate' => false,
                        ];
                        if (isset($value['@attributes']['format'])) {
                            $this->moduleFields[$presta_field]['type'] = $value['@attributes']['format'];
                        }
                        if (isset($value['@attributes']['required'])) {
                            $this->moduleFields[$presta_field]['required'] = true;
                        }
                    }
                }
                // Récupération des listes déroulantes
                if ('orders' == $module && isset($this->moduleFields['current_state'])) {
                    try {
                        $order_states = $this->getList('order_state', 'order_states');
                        $this->moduleFields['current_state']['option'] = $order_states;
                    } catch (\Exception $e) {
                        // No error if order_state not accessible, the order status list won't accessible
                    }
                }
                if ('order_histories' == $module && isset($this->moduleFields['id_order_state'])) {
                    try {
                        $order_states = $this->getList('order_state', 'order_states');
                        $this->moduleFields['id_order_state']['option'] = $order_states;
                    } catch (\Exception $e) {
                        // No error if order_state not accessible, the order status list won't accessible
                    }
                }
                if ('supply_orders' == $module && isset($this->moduleFields['id_supply_order_state'])) {
                    try {
                        $supply_order_states = $this->getList('supply_order_state', 'supply_order_states');
                        $this->moduleFields['id_supply_order_state']['option'] = $supply_order_states;
                    } catch (\Exception $e) {
                        // No error if supply_order_state not accessible, the supply order status list won't accessible
                    }
                }
                // Ticket 450: Si c'est le module customer service messages, on rend la relation id_customer_thread obligatoire
                if ('customer_messages' == $module) {
                    $this->moduleFields['id_customer_thread']['required_relationship'] = 1;
                }
                if ('customer_threads' == $module) {
                    $languages = $this->getList('language', 'languages');
                    $this->moduleFields['id_lang']['option'] = $languages;
                    $this->moduleFields['id_lang']['required'] = 1;
                    $contacts = $this->getList('contact', 'contacts');
                    $this->moduleFields['id_contact']['option'] = $contacts;
                    $this->moduleFields['id_contact']['required'] = 1;
                    // Les status de thread ne semblent pas être une ressource donc on met la liste en dur via un attribut facile à redéfinir)
                    $this->moduleFields['status']['option'] = $this->threadStatus;
                    // Le champ token est renseigné dans le create directement
                    unset($this->moduleFields['token']);
                }
                // If order_payments is requeted, we add the order_id because there is only the order_reference (no useable for relationship)
                if (
                        'order_payments' == $module
                    and 'source' == $type
                ) {
                    $this->moduleFields['id_order'] = [
                        'label' => 'id_order',
                        'type' => 'varchar(255)',
                        'type_bdd' => 'varchar(255)',
                        'required' => 0,
                        'required_relationship' => 0,
                        'relate' => true,
                    ];
                }
                // On enlève les champ date_add et date_upd si le module est en target
                if ('target' == $type) {
                    if (!empty($this->moduleFields['date_add'])) {
                        unset($this->moduleFields['date_add']);
                    }
                    if (!empty($this->moduleFields['date_upd'])) {
                        unset($this->moduleFields['date_upd']);
                    }
                }

                return $this->moduleFields;
            } catch (PrestashopWebserviceException $e) {
                // Here we are dealing with errors
                $trace = $e->getTrace();
                if (401 == $trace[0]['args'][0]) {
                    throw new \Exception('Bad auth key');
                }
                throw new \Exception('Call failed '.$e->getTrace());
            }
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Fonction permettant de récupérer les listes déroulantes
    protected function getList($field, $fields)
    {
        $opt = [
            'resource' => $fields,
        ];
        // Call
        $xml = $this->webService->get($opt);

        $xml = $xml->asXML();
        $simplexml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $records = json_decode(json_encode((array) $simplexml->children()->children()), true);
        foreach ($records[$field] as $record) {
            // The structure is different if there is only one language or several languages in Prestashop
            $attributeId = (!empty($record['@attributes']['id']) ? $record['@attributes']['id'] : $record['id']);
            $opt = [
                'resource' => $fields,
                'id' => $attributeId,
            ];

            // Function to modify opt (used for custom needs)
            $opt = $this->updateOptions('getList', $opt, $field);
            // Call
            $xml = $this->webService->get($opt);
            $xml = $xml->asXML();
            $simplexml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            $state = json_decode(json_encode((array) $simplexml->children()->children()), true);
            // S'il y a une langue on prends la liste dans le bon language
            if (!empty($state['name']['language'])) {
                // We don't know the language here because the user doesn't chose it yet. So we take the first one.
                $list[$state['id']] = (is_array($state['name']['language']) ? current($state['name']['language']) : $state['name']['language']);
            }
            // Sinon on prend sans la langue (utile pour la liste language par exemple)
            elseif (!empty($state['name'])) {
                $list[$attributeId] = $state['name'];
            }
        }
        if (!empty($list)) {
            return $list;
        }
    }

    // Conversion d'un SimpleXMLObject en array
    public function xml2array($xmlObject, $out = [])
    {
        foreach ((array) $xmlObject as $index => $node) {
            $out[$index] = (is_object($node)) ? $this->xml2array($node) : $node;
        }

        return $out;
    }

    // Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution

    /**
     * @throws \Exception
     */
    public function read($param): ?array
    {
        // traitement spécial pour module de relation Customers / Groupe
        if (array_key_exists($param['module'], $this->module_relationship_many_to_many)) {
            $result = $this->readManyToMany($param);

            return $result;
        }

        // On va chercher le nom du champ pour la date de référence: Création ou Modification
        $dateRefField = $this->getRefFieldName($param);

        try { // try-catch PrestashopWebservice
            $result = [];
            // Le champ current_state n'est plus lisible (même s'il est dans la liste des champs disponible!) dans Prestashop 1.6.0.14, il faut donc le gérer manuellement
            $getCurrentState = false;
            if (
                    'orders' == $param['module']
                && in_array('current_state', $param['fields'])
            ) {
                $getCurrentState = true;
                unset($param['fields'][array_search('current_state', $param['fields'])]);
            }

            $opt['limit'] = $param['limit'];
            $opt['resource'] = $param['module'].'&date=1';
            $opt['display'] = '[';
            foreach ($param['fields'] as $field) {
                // On ne demande pas les champs spécifiques à Myddleware
                if (
                        !in_array($field, ['Myddleware_element_id', 'my_value'])
                    and !('id_order' == $field and 'order_payments' == $param['module'])
                ) {
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
            } else {
                // Si la référence est une date alors la requête dépend de la date
                if ($this->referenceIsDate($param['module'])) {
                    if ('date_add' == $dateRefField) {
                        $opt['filter[date_add]'] = '['.$param['date_ref'].',9999-12-31 00:00:00]';

                        $opt['sort'] = '[date_add_ASC]';
                    } else {
                        // $opt['filter[date_upd]'] = '[' . $param['date_ref'] .',9999-12-31 00:00:00]';
                        $opt['filter[date_upd]'] = '['.$param['date_ref'].',9999-12-31 00:00:00]';

                        $opt['sort'] = '[date_upd_ASC]';
                    }
                }
                // Si la référence n'est pas une date alors c'est l'ID de prestashop
                else {
                    if (
                            in_array($param['module'], $this->moduleWithoutReferenceDate)
                        AND (
                                empty($param['date_ref'])
                             OR !is_numeric($param['date_ref'])
                        )    
                    ) {
                        $param['date_ref'] = 1;
                    } else {
						// We read the next record
						$param['date_ref']++;
                    }
                    $opt['filter[id]'] = '['.$param['date_ref'].',999999999]';
                    $opt['sort'] = '[id_ASC]';
                }
            }

            // Function to modify opt (used for custom needs)
            $opt = $this->updateOptions('read', $opt, $param);

            // Call
            $xml = $this->webService->get($opt);

            $xml = $xml->asXML();
            $simplexml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            $record = [];
            foreach ($simplexml->children()->children() as $data) {
                if (!empty($data)) {
                    foreach ($data as $key => $value) {
                        // If field is requested (field corresponding to the reference date could be requested in the field mapping too)
                        if (false !== array_search($key, $param['fields'])) {
                            if (isset($value->language)) {
                                if (!empty($value->language[1])) {
                                    $record[$key] = (string) $value->language[1];
                                } else {
                                    $record[$key] = (string) $value->language;
                                }
                            } else {
                                $record[$key] = (string) $value;
                            }
                        }
                    }
                    // If id_order is requested for the module order_payments, we have to get the id order by using the order_reference
                    if (
                            false !== array_search('id_order', $param['fields'])
                        and 'order_payments' == $param['module']
                        and !empty($data->order_reference)
                    ) {
                        // Get the id_order from Prestashop
                        $optOrder['limit'] = 1;
                        $optOrder['resource'] = 'orders&date=1';
                        $optOrder['display'] = '[id]';
                        $optOrder['filter[reference]'] = '['.(string) $data->order_reference.']';
                        $xml = $this->webService->get($optOrder);
                        $xml = $xml->asXML();
                        $simplexml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
                        if (!empty($simplexml->orders->order->id)) {
                            $record['id_order'] = (string) $simplexml->orders->order->id;
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
                            $record['current_state'] = (string) $currentState->order_history->id_order_state;
                        }
                    }
                    $result[] = $record;
                    $record = [];
                }
            }
        } catch (PrestashopWebserviceException $e) {
            // Here we are dealing with errors
            $trace = $e->getTrace();
            if (401 == $trace[0]['args'][0]) {
                throw new \Exception('Bad auth key');
            }

            throw new \Exception('Call failed '.$e->getMessage());
        }

        return $result;
    }

    // Method de find the date ref after a read call

    /**
     * @throws \Exception
     */
    protected function getReferenceCall($param, $result)
    {
        // Keep the same date ref if no result
		if (empty($result['count'])) {
			return $param['date_ref'];
		}
        if ($this->referenceIsDate($param['module'])) {
            // Add 1 second to the date ref because the read function is a >= not a >
            $date = new \DateTime(end($result['values'])['date_modified']);
            $second = new \DateInterval('PT1S'); // one second
            $date->add($second);

            return $date->format('Y-m-d H:i:s');
        }
        // if the reference is an increment
        else {
            if (isset($result['values']) && is_array($result['values'])) {
                $maxId = max(array_column($result['values'], 'id'));
                return $maxId;
            }
            return end($result['values'])['date_modified']++;
        }
    }

    // Read pour les modules fictifs sur les relations many to many
    protected function readManyToMany($param): array
    {
        try { // try-catch Myddleware
            // On va chercher le nom du champ pour la date de référence: Création ou Modification
            $dateRefField = $this->getRefFieldName($param);
            try { // try-catch PrestashopWebservice
                $result = [];
                // Init parameter to read in Prestashop
                $searchModule = $this->module_relationship_many_to_many[$param['module']]['searchModule'];
                $subModule = $this->module_relationship_many_to_many[$param['module']]['subModule'];
                $subData = $this->module_relationship_many_to_many[$param['module']]['subData'];
                $subDataId = (!empty($this->module_relationship_many_to_many[$param['module']]['subDataId']) ? $this->module_relationship_many_to_many[$param['module']]['subDataId'] : 'id');

                // Ajout des champs obligatoires
                $param['fields'] = $this->addRequiredField($param['fields'], $searchModule, $param['ruleParams']['mode']);
                $opt['limit'] = $param['limit'];
                $opt['resource'] = $searchModule.'&date=1';
                $opt['display'] = 'full';

                // Query creation
                // if a specific query is requeted we don't use date_ref
                if (!empty($param['query'])) {
                    foreach ($param['query'] as $key => $value) {
                        $opt['filter['.$key.']'] = '['.$value.']';
                    }
                } else {
                    // Si la référence est une date alors la requête dépend de la date
                    if ($this->referenceIsDate($searchModule)) {
                        if ('date_add' == $dateRefField) {
                            $opt['filter[date_add]'] = '['.$param['date_ref'].',9999-12-31 00:00:00]';

                            $opt['sort'] = '[date_add_ASC]';
                        } else {
                            $opt['filter[date_upd]'] = '['.$param['date_ref'].',9999-12-31 00:00:00]';

                            $opt['sort'] = '[date_upd_ASC]';
                        }
                    }
                    // Si la référence n'est pas une date alors c'est l'ID de prestashop
                    else {
                        if ('' == $param['date_ref']) {
                            $param['date_ref'] = 1;
                        }
                        $opt['filter[id]'] = '['.$param['date_ref'].',999999999]';
                        $opt['sort'] = '[id_ASC]';
                    }
                }
                // Function to modify opt (used for custom needs)
                $opt = $this->updateOptions('readManyToMany', $opt, $param);
                // Call
                $xml = $this->webService->get($opt);
                $xml = $xml->asXML();
                $simplexml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

                $cpt = 0;
                $record = [];
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
                            $record['date_modified'] = (string) $value;
                            continue;
                        }
                        // Si la clé de référence est un id et que celui-ci est supérieur alors on sauvegarde cette référence
                        elseif (
                                !$this->referenceIsDate($searchModule)
                            && 'id' == $key
                            && (
                                    empty($result['date_ref'])
                                 || (
                                        !empty($result['date_ref'])
                                    && $value >= $result['date_ref']
                                )
                            )
                        ) {
                            // Ajout de 1 car le filtre de la requête inclus la valeur minimum
                            $result['date_ref'] = $value + 1;
                            // Une date de modification est mise artificiellement car il n'en existe pas dans le module
                            $record['date_modified'] = (string) date('Y-m-d H:i:s');
                        }
                        if (isset($value->language)) {
                            $record[$key] = (string) $value->language;
                        } else {
                            $record[$key] = (string) $value;
                        }

                        if ('associations' == $key) {
                            foreach ($resultRecord->associations->$subModule->$subData as $data) {
                                $subRecord = [];
                                $idRelation = (string) $resultRecord->id.'_'.(string) $data->$subDataId;
                                $subRecord[$this->module_relationship_many_to_many[$param['module']]['relationships'][0]] = (string) $resultRecord->id;
                                $subRecord[$this->module_relationship_many_to_many[$param['module']]['relationships'][1]] = (string) $data->$subDataId;
                                // Add fields in the relationship
                                if (!empty($this->module_relationship_many_to_many[$param['module']]['fields'])) {
                                    foreach ($this->module_relationship_many_to_many[$param['module']]['fields'] as $name => $label) {
                                        // Add only requested fields
                                        if (false !== array_search($name, $param['fields'])) {
                                            $subRecord[$name] = (string) $data->$name;
                                        }
                                    }
                                }
                                $subRecord['id'] = $idRelation;
                                $subRecord['date_modified'] = $record['date_modified'];
                                $result['values'][$idRelation] = $subRecord;
                                ++$cpt;
                            }
                        }
                    }
                    $record = [];
                }
                $result['count'] = $cpt;
            } catch (PrestashopWebserviceException $e) {
                // Here we are dealing with errors
                $trace = $e->getTrace();
                if (401 == $trace[0]['args'][0]) {
                    throw new \Exception('Bad auth key');
                }

                throw new \Exception('Call failed '.$e->getMessage());
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }

        return $result;
    }

    // Permet de créer des données

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Exception
     */
    public function createData($param): array
    {
        // If a sub record is created, it means that we will update the main module
        if (!empty($this->module_relationship_many_to_many[$param['module']])) {
            return $this->updateData($param);
        }

        foreach ($param['data'] as $idDoc => $data) {
            // Check control before create
            $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
            // on ajoute le token pour le module customer_threads
            if ('customer_threads' == $param['module']) {
                $data['token'] = 'token';
            }
            try { // try-catch Myddleware
                try { // try-catch PrestashopWebservice
                    $fields = [];
                    $opt = [
                        'resource' => $param['module'].'?schema=blank',
                    ];

                    // Function to modify opt (used for custom needs)
                    $opt = $this->updateOptions('create1', $opt, $param);
                    // Call
                    $xml = $this->webService->get($opt);
                    $modele = $xml->children()->children();
                    $toSend = $xml->children()->children();
                    foreach ($modele as $nodeKey => $node) {
                        if (isset($data[$nodeKey])) {
                            // If we use an element with language, we update only the language selected
                            if (!empty($modele->$nodeKey->children())) {
                                $i = 0;
                                $languageFound = false;
                                foreach ($modele->$nodeKey->children() as $node) {
                                    if (!empty($param['ruleParams']['language'])) {
                                        if ($node->attributes() == $param['ruleParams']['language']) {
                                            $toSend->$nodeKey->language[$i][0] = $data[$nodeKey];
                                            $languageFound = true;
                                        }
                                    }
                                    ++$i;
                                }
                                if (!$languageFound) {
                                    throw new \Exception('Failed to find the language '.$param['ruleParams']['language'].' in the Prestashop XML');
                                }
                            } else {
                                $toSend->$nodeKey = $data[$nodeKey];
                            }
                        }
                    }

                    if (isset($toSend->message)) {
                        $toSend->message = str_replace(chr(13).chr(10), "\n", $toSend->message);
                        $toSend->message = str_replace(chr(13), "\n", $toSend->message);
                        $toSend->message = str_replace(chr(10), "\n", $toSend->message);
                    }

                    $opt = [
                        'resource' => $param['module'],
                        'postXml' => $xml->asXML(),
                    ];

                    // Function to modify opt (used for custom needs)
                    $opt = $this->updateOptions('create2', $opt, $param);
                    $new = $this->webService->add($opt);
                    $result[$idDoc] = [
                        'id' => (string) $new->children()->children()->id,
                        'error' => false,
                    ];
                } catch (PrestashopWebserviceException $e) {
                    // Here we are dealing with errors
                    $trace = $e->getTrace();
                    if (401 == $trace[0]['args'][0]) {
                        throw new \Exception('Bad auth key');
                    }

                    throw new \Exception('Please check your data.'.$e->getMessage());
                }
            } catch (\Exception $e) {
                $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
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

    // Permet de modifier des données
    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateData($param): array
    {
        // We never update order_histories even if the methode update is called
        // For this module we always create a new line (so create methode is called)
        if ('order_histories' == $param['module']) {
            return $this->createData($param);
        }

        foreach ($param['data'] as $idDoc => $data) {
            try { // try-catch Myddleware
                try { // try-catch PrestashopWebservice
                    // Check control before update
                    $data = $this->checkDataBeforeUpdate($param, $data, $idDoc);
                    $fields = [];
                    $submodule = [];
                    $module = $param['module'];
                    $targetId = (int) $data['target_id'];
                    $targetIdResult = $data['target_id']; // Used for many to many module, the id is build with both ids
                    // Override $module and $targetId in case of many-to-many module
                    if (!empty($this->module_relationship_many_to_many[$param['module']])) {
                        $submodule = $this->module_relationship_many_to_many[$param['module']];
                        $module = $submodule['searchModule'];
                        $targetId = (int) $data[$submodule['relationships'][0]];
                        $targetIdResult = $data[$submodule['relationships'][0]].'_'.$data[$submodule['relationships'][1]];
                    }
                    $opt = [
                        'resource' => $module,
                        'id' => $targetId,
                    ];

                    // Function to modify opt (used for custom needs)
                    $opt = $this->updateOptions('update1', $opt, $param);
                    // Call
                    $xml = $this->webService->get($opt);
                    $toUpdate = $xml->children()->children();

                    if (!empty($submodule)) {
                        $submoduleString = $submodule['subModule'];
                        // We add the child to the main module. Here is an example : $product->associations->categories->addChild('category')->addChild('id', $ps_category_id);
                        $toUpdate->associations->$submoduleString->addChild($submodule['subData'])->addChild('id', (int) $data[$submodule['relationships'][1]]);
                    } else {
                        $modele = $xml->children()->children();
                        foreach ($modele as $nodeKey => $node) {
                            if (isset($data[$nodeKey])) {
                                // If we use an element with language, we update only the language selected
                                if (!empty($modele->$nodeKey->children())) {
                                    $i = 0;
                                    $languageFound = false;
                                    foreach ($modele->$nodeKey->children() as $node) {
                                        if ($node->attributes() == $param['ruleParams']['language']) {
                                            $toUpdate->$nodeKey->language[$i][0] = $data[$nodeKey];
                                            $languageFound = true;
                                        }
                                        ++$i;
                                    }
                                    if (!$languageFound) {
                                        throw new \Exception('Failed to find the language '.$param['ruleParams']['language'].' in the Prestashop XML');
                                    }
                                } else {
                                    $toUpdate->$nodeKey = $data[$nodeKey];
                                }
                            }
                        }
                    }

                    // We remove non writtable fields
                    if (!empty($this->notWrittableFields[$module])) {
                        foreach ($this->notWrittableFields[$module] as $notWrittableField) {
                            unset($xml->children()->children()->$notWrittableField);
                        }
                    }

                    if (isset($toUpdate->message)) {
                        $toUpdate->message = str_replace(chr(13).chr(10), "\n", $toUpdate->message);
                        $toUpdate->message = str_replace(chr(13), "\n", $toUpdate->message);
                        $toUpdate->message = str_replace(chr(10), "\n", $toUpdate->message);
                    }

                    // Function to modify opt (used for custom needs)
                    $opt = $this->updateOptions('update2', $opt, $param);

                    $opt = [
                        'resource' => $module,
                        'putXml' => $xml->asXML(),
                        'id' => $targetId,
                    ];

                    $new = $this->webService->edit($opt);
                    $result[$idDoc] = [
                        'id' => $targetIdResult,
                        'error' => false,
                    ];
                } catch (PrestashopWebserviceException $e) {
                    // Here we are dealing with errors
                    $trace = $e->getTrace();
                    if (500 == $trace[0]['args'][0]) {
                        $result[$idDoc] = [
                            'id' => $targetIdResult,
                            'error' => false,
                        ];
                    } elseif (401 == $trace[0]['args'][0]) {
                        throw new \Exception('Bad auth key');
                    } else {
                        throw new \Exception('Please check your data.'.$e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
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

    // Permet de renvoyer le mode de la règle en fonction du module target
    // Valeur par défaut "0"
    // Si la règle n'est qu'en création, pas en modicication alors le mode est C
    public function getRuleMode($module, $type): array
    {
        if (
                'target' == $type
            and (
                    in_array($module, ['customer_messages', 'order_details'])
                 or array_key_exists($module, $this->module_relationship_many_to_many)
            )
        ) { // Si le module est dans le tableau alors c'est uniquement de la création
            return [
                'C' => 'create_only',
            ];
        }

        return parent::getRuleMode($module, $type);
    }

    // Renvoie le nom du champ de la date de référence en fonction du module et du mode de la règle

    /**
     * @throws \Exception
     */
    public function getRefFieldName($param): string
    {
        // We force date_add for some module (when there is no date_upd (order_histories) or when the date_upd can be empty (customer_messages))
        if (in_array($param['module'], ['order_histories', 'order_payments', 'order_carriers', 'customer_messages'])) {
            return 'date_add';
        }
        if (in_array($param['module'], ['order_details','shop_urls'])) {
            return 'id';
        }
        if (in_array($param['ruleParams']['mode'], ['0', 'S', 'U'])) {
            return 'date_upd';
        } elseif ('C' == $param['ruleParams']['mode']) {
            return 'date_add';
        }
        throw new \Exception("$param[ruleParams][mode] is not a correct Rule mode.");
    }

    // Permet d'indiquer le type de référence, si c'est une date (true) ou un texte libre (false)
    public function referenceIsDate($module): bool
    {
        // Le module order détail n'a pas de date de référence. On utilise donc l'ID comme référence
        if (in_array($module, $this->moduleWithoutReferenceDate)) {
            return false;
        }

        return true;
    }

    // Permet de renvoyer l'id de la table en récupérant la table liée à la règle ou en la créant si elle n'existe pas
    public function getFieldsParamUpd($type, $module): array
    {
        try {
            if (
                    'target' == $type
                && in_array($module, $this->moduleWithLanguage)
            ) {
                $params = [];
                $languages = $this->getList('language', 'languages');
                if (!empty($languages)) {
                    $idParam = [
                        'id' => 'language',
                        'name' => 'language',
                        'type' => 'option',
                        'label' => 'Language',
                        'required' => true,
                    ];
                    foreach ($languages as $key => $value) {
                        $idParam['option'][$key] = $value;
                    }
                    $params[] = $idParam;
                }

                return $params;
            }

            return [];
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    protected function updateOptions($method, $opt, $param)
    {
        return $opt;
    }

     // Manage specific reference date for the module shop_urls
     protected function getModifiedDate($param, $record, $dateRefField)
     {
        // No reference date for module shop_urls so we set one by default
        if ($param['module'] == 'shop_urls') {
            return '1970-01-01 00:00:00';
        }

        if (in_array($param['module'], $this->moduleWithoutReferenceDate)) {
            return gmdate('Y-m-d H:i:s') ;
        }
        return $this->dateTimeToMyddleware($record[$dateRefField]);
     }

    // Fonction permettant de faire l'appel REST
    protected function call($url, $parameters)
    {
    }
}
