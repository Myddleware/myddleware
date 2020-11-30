<?php 
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 *
 * This file is part of Myddleware.
 *
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

namespace Myddleware\RegleBundle\Solutions;

use Automattic\WooCommerce\Client;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;



class woocommercecore extends solution {


    protected $apiUrlSuffix = '/wp-json/wc/v3/';
    protected $url;
    protected $consumerKey;
    protected $consumerSecret;
    protected $woocommerce;
    //TODO : à remplir avec Stéphane
    protected $FieldsDuplicate = array();
    protected $defaultLimit = 100;
    protected $delaySearch = '-1 month';
    protected $subModules = array(
                                'line_items' => 'orders'
                            );

    //Log in parameters
    public function getFieldsLogin()
    {
        return array(
                    array(
                        'name' => 'url',
                        'type' => TextType::class,
                        'label' => 'solution.fields.url'
                    ),
                    array(
                        'name' => 'consumerkey',
                        'type' => PasswordType::class,
                        'label' => 'solution.fields.consumerkey'
                    ),
                    array(
                        'name' => 'consumersecret',
                        'type' => PasswordType::class,
                        'label' => 'solution.fields.consumersecret'
                    )
                );
    }
    
    // Logging in to Woocommerce
    public function login($paramConnexion) {
        parent::login($paramConnexion);
		try{	
            $this->woocommerce = new Client(
                $this->paramConnexion['url'],
                $this->paramConnexion['consumerkey'],
                $this->paramConnexion['consumersecret'],
                [
                    'wp_api' => true,
                    'version' => 'wc/v3'
                ]
                );
            if($this->woocommerce->get('data'))   {
                $this->connexion_valide = true;	
            }    
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);
            return array('error' => $error);
        }  
    }

    public function get_modules($type = 'source'){
        return array(
            'customers' => 'Customers',
            'orders' => 'Orders',
            'products' => 'Products',
            'reports' => 'Reports',
            'settings' => 'Settings',
            // 'shipping' => 'Shipping',  shipping/zones
            // 'taxes' => 'Taxes',
            // 'webhooks' => 'Webhooks',
            'shipping-methods' => 'Shipping Methods',
            'line_items' => 'Line Items'
        );
    }

    public function get_module_fields($module, $type = 'source')
    {
        require_once('lib/woocommerce/metadata.php');
        parent::get_module_fields($module, $type);

        try {
            if(!empty($moduleFields[$module])){
                $this->moduleFields = $moduleFields[$module];
            }
            if (!empty($fieldsRelate[$module])) {
				$this->fieldsRelate = $fieldsRelate[$module]; 
			}	

         return $this->moduleFields;

        } catch (\Exception $e) {		
			$this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());		
			return false;
		}	
    }

    // Read all fields, ordered by date_modified
    // $param => [[module],[rule], [date_ref],[ruleParams],[fields],[offset],[limit],[jobId],[manual]]
    public function read($param) {
    //TODO: tester pair impair  
        try {

            $module = $param['module'];
            $result = [];
            $result['count'] = 0;
            $result['date_ref'] = $param['ruleParams']['datereference'];
            $dateRefWooFormat  = $this->dateTimeFromMyddleware($param['ruleParams']['datereference']);
            if(empty($param['limit'])){
                $param['limit'] = $this->defaultLimit;
            }
// var_dump($this->subModules[$param['module']]);
//  var_dump($param['module']);
            //pour les sous-modules
            if(!empty($this->subModules[$param['module']])){
                $module = $this->subModules[$param['module']];
            } 

            //for submodules, we pass the parent module
            // if(in_array($module, $this->subModules)){
            //     // $module = $this->subModules[$param['module']];
            //      $module = key($this->subModules);
            // } else {
            //     $module = $param['module'];
            // }
    
// var_dump($this->subModules);
// var_dump($param['module']);
// var_dump($module);
            // Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);

			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields'],$module);

            //get all data, sorted by date_modified
            $stop = false;
            $count = 0;
            $page = 1;
            do {
                //orderby modified isn't available for customers in the API filters so we sort by creation date
                if($module === 'customers'){
                    $response = $this->woocommerce->get($module, array('orderby' => 'registered_date',
                                                                                'order' => 'desc',
                                                                                'per_page' => $this->defaultLimit,
                                                                                'page' => $page));
                } else {
                    $response = $this->woocommerce->get($module, array('orderby' => 'modified',
                                                                                'per_page' => $this->defaultLimit,
                                                                                'page' => $page));
                }                                      
                if(!empty($response)){
// var_dump($response);
            //  pour line items convertir la response 
                $response = $this->convertResponse($param, $response);
          
                    foreach($response as $record){

                        if($dateRefWooFormat < $record->date_modified){
                            
                            foreach($param['fields'] as $field){
                                // If we have a 2 dimensional array we break it down  
                                $fieldStructure = explode('__',$field);
                                $fieldGroup = '';
                                $fieldName = '';
                                if (!empty($fieldStructure[1])) {
                                    $fieldGroup = $fieldStructure[0];
                                    $fieldName = $fieldStructure[1];
                                    $result['values'][$record->id][$field] = (!empty($record->$fieldGroup->$fieldName) ? $record->$fieldGroup->$fieldName : '');
                                } else {
                                    $result['values'][$record->id][$field] = (!empty($record->$field) ? $record->$field : '');
                            }
                        }
                            $result['values'][$record->id]['date_modified'] = $record->date_modified;
                            $result['values'][$record->id]['id'] = $record->id;
                            $count++;
                        } else {
                            $stop = true;
                        }
                    }   
                }else{
                    $stop = true; 
                }
                $page++;	
            } while(!$stop);
//TODO QUERY ID  (voir sugar)

            //As the records sent from the API are ordered by date_modified, 
            // we pass date_modified from the first record as the date_ref
            if(!empty($result['values'])){
                $latestModification = $result['values'][array_key_first($result['values'])]['date_modified'];
                $result['date_ref'] = $this->dateTimeToMyddleware($latestModification);
            }
            $result['count'] = $count; 
        
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';		
        }	
    
            //  var_dump($result);
        //   return null;
        return $result;
     
    }

    //for specific modules (ex : line_items)
    public function convertResponse($param, $response) {
        //  var_dump($param);
        if(array_key_exists($param['module'], $this->subModules)){
            $subModule = $param['module'];
            $newResponse = array();
            if(!empty($response)){
                foreach($response as $key => $record){      
                     foreach($record->$subModule as $subRecord){
                       
                        $subRecord->date_modified = $record->date_modified;
                        $newResponse[$subRecord->id] = $subRecord;
                     }    
                }
            }
                return $newResponse;
        }
        return $response;

    }



    //Get last field (we read ALL then only retrieve one)
    public function read_last($param) {
        $result = array();
        try{

            //for simulation purposes, we create a new date_ref in the past
                $param['ruleParams']['datereference'] = date('Y-m-d H:i:s', strtotime($this->delaySearch));
         
           //get all instances of the module
           $read = $this->read($param);

        //ONLY RETRIEVE ONE RECORD 
            if (!empty($read['error'])) {
                $result['error'] = $read['error'];
            } else {
                if (!empty($read['values'])) {
                    $result['done'] = true;
                    // Get only one record (the API sorts them by date by default, first one is therefore latest modified)
                    $result['values'] = $read['values'][array_key_first($read['values'])];
                    
                } else {
                    $result['done'] = false;
                }
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$result['done'] = -1;		
        }
		return $result;
	}

    // Convert date to Myddleware format 
	// 2020-07-08T12:33:06 to 2020-07-08 10:33:06
	protected function dateTimeToMyddleware($dateTime) {
		$dto = new \DateTime($dateTime);
		// We save the UTC date in Myddleware
		$dto->setTimezone(new \DateTimeZone('UTC'));
		return $dto->format("Y-m-d H:i:s");
	}
	
    //convert from Myddleware format to Woocommerce format
	protected function dateTimeFromMyddleware($dateTime) {
		$dto = new \DateTime($dateTime);
		// Return date to UTC timezone
		return $dto->format('Y-m-d\TH:i:s');
	}

}





// Include custom file if it exists : used to redefine Myddleware standard core
$file = __DIR__. '/../Custom/Solutions/woocommerce.php';
if(file_exists($file)){
    require_once($file);
} else { 
    class woocommerce extends woocommercecore {

    }
}