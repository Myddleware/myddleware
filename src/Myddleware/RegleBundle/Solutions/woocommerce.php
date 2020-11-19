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

// use Symfony\Component\HttpFoundation\Session\Session;
// use \Datetime;

use DateTime;
use DateTimeZone;
use Automattic\WooCommerce\Client;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;



class woocommercecore extends solution {


    protected $apiUrlSuffix = '/wp-json/wc/v3/';
    protected $url;
    protected $consumerKey;
    protected $consumerSecret;
    protected $woocommerce;

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

            // if(!isset($this->paramConnexion)){
            //     throw new \Exception('Please check fields');

            // }

            $this->woocommerce = new Client(
                $this->paramConnexion['url'],
                $this->paramConnexion['consumerkey'],
                $this->paramConnexion['consumersecret'],
                [
                    'wp_api' => true,
                    'version' => 'wc/v3'
                ]
                );

               if(isset($woocommerce)){
               
                // var_dump($this->woocommerce);
               }
               $this->connexion_valide = true;	
            //  var_dump( $this->woocommerce->get('data'));

            //  var_dump($woocommerce->get('products'));   
            // $result = $this->call('login', $woocommerce);

                // if(empty($woocommerce)){
                //     throw new \Exception('Failed to connect to Woocommerce');
                // }else{
                //     $this->connexion_valide = true;
                // }
// var_dump($woocommerce->get('products'));
            // $this->url = $this->paramConnexion['url'];
            // $this->consumerKey = $this->paramConnexion['consumerkey'];
            // $this->consumerSecret = $this->paramConnexion['consumersecret'];
            // var_dump($this->paramConnexion);


            // if(isset($woocommerce)){
            //     // print_r($woocommerce);
         
            //     // var_dump($woocommerce->get('products'));
                
            // }else{
            //     throw new \Exception('ALLO?');
            // }
            // return $this->woocommerce;

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);
            return array('error' => $error);
        }  
    }

    

            // $this->woocommerce = new Client(
            //         $this->paramConnexion['url'], 
            //         $this->paramConnexion['consumerkey'], 
            //         $this->paramConnexion['consumersecret'],
            //         [
            //             'wp_api' => true,
            //             'version' => 'wc/v3'
            //         ]
            // );



            // $this->url = $this->paramConnexion['url'].'/wp-json/wc/v3';
            // $post_fields = array(
            //     'client_id' => 
            // );

            // $store_url = $this->url;
            // $endpoint = '/wc-auth/v1/authorize';
            // $params = [
            //     'app_name' => 'My App Name',
            //     // 'scope' => 'write',
            //     // 'user_id' => 123,
            //     'return_url' => $store_url,
            //     'callback_url' => $store_url
            // ];
            // $query_string = http_build_query( $params );
            
            // echo $store_url . $endpoint . '?' . $query_string;



            // $post_fields = array(
            //     ''
            // );

			// // Delete the "/" at the end of the url if the user have added one
			// $this->url = rtrim($this->paramConnexion['url'],'/').'/api/';
			// $this->apiKey = '?key='.$this->paramConnexion['apikey'];
			// // Try to access to the shop
		// 	$result = $this->call(trim($this->url), 'get', '');	
		// 	// // get the code, if 200 then success otherwise error
		// 	$code = $result->__get('code');
		// 	if ($code <> '200') {
		// 	// 	// Get the error message
		// 		$body = $result->__get('body');
		// 		throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
		// 	}
			// $this->connexion_valide = true;
	//	}
		// catch (\Exception $e) {
		// 	$error = $e->getMessage();
		// 	$this->logger->error($error);
		// 	return array('error' => $error);
		// }
//	} 

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
            'shipping-methods' => 'Shipping Methods'
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

        //  if($module === 'products'){
        //      try {
        //         // $result =  $this->woocommerce->get('products');
        //         // $this->moduleFields = $result;
        //      } catch(\Exception $e){

        //      }
           
        //  }   
        // $this->moduleFields = array();

         return $this->moduleFields;

        } catch (\Exception $e) {		
			$this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());		
			return false;
		}	

    }



// Return the filed reference
public function getDateRefName($moduleSource, $ruleMode) {
    if(in_array($ruleMode,array("0","S"))) {
        return 'date_modified';
    } elseif ($ruleMode == 'C'){
        return 'date_created';
    } else {
        throw new \Exception ("Rule mode $ruleMode unknown.");
    }
    return null;
}





     	// Read one specific record
        public function read($param) {
     
		try {

            $result = [];
            // $result['count'] = 0;
            // $result['date_ref'] = $param['date_ref'];


			// Get the reference date
			// $dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
            // $this->url = $this->paramConnexion['url'];
           $response = $this->woocommerce->get($param['module']);
        //    var_dump(json_encode($response));
        if(isset($response)){
            // $result = $response;
            foreach($response as $key => $value) {
           
                // $result['values'][$key] = json_encode($value);  
                // var_dump(json_encode($value));
                foreach($value as $fieldName => $fieldValue){
                    // var_dump($fieldName.' '.$fieldValue);
                    $result['values'][$fieldName] = $fieldValue;
                    
                }
                //   var_dump($result['values']);
            }
        }
         
        //    if($response){
        //         foreach($response as $key => $record){
        //             foreach($param['fields'] as $field) {
        //                 $result['values'][$record->id][$field]
        //             }
        //         }
        //    }
        //    var_dump($result);
	// 		// Add requiered fields 
	// 		$param['fields'] = $this->addRequiredField($param['fields'],$param['module']);
	// 		// Remove fields that doesn't belong to shop application
	// 		$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			
	    // // We build the url (get all data after the reference date)
	 	// 	$urlApi = $this->url.$param['module'].'/filter/'.$dateRefField.'/superior/'.urlencode($param['date_ref']).'/orderby/date_created/asc'.$this->apiKey;
        
	// 		// Try to access to the shop
	// 		$return = $this->call($urlApi, 'get', '');	
			
	// 		$code = $return->__get('code');
	// 		// If the call is a success
	// 		if ($code == '200') {		
	// 			$body = $return->__get('body');
	// 			if (!empty($body)) {
	// 				// For each record
	// 				foreach ($body as $id => $record) {
	// 					$row = array();
	// 					// For each fields					
	// 					foreach ($param['fields'] as $field) {
	// 						if ($field == $dateRefField) {
	// 							$row['date_modified'] = $record->$field;
	// 							// Save the latest reference date
	// 							if (	
	// 									empty($result['date_ref'])
	// 								 || $record->$field > $result['date_ref']
	// 							) {
	// 								$result['date_ref'] = $record->$field;
	// 							}
	// 						} else {
	// 							// Transform the field to an array in case it is a language fields
	// 							$filedArray = explode('__',$field);						
	// 							$nbLevel = count($filedArray);
	// 							if ($nbLevel == 3) { // Language field
	// 								// We search the language field in the body
	// 								if (
	// 										!empty($param['ruleParams']['language'])
	// 									 &&	!empty($record->$filedArray[0]->$param['ruleParams']['language']->$filedArray[2])
	// 								) {
	// 									$row[$field] = $record->$filedArray[0]->$param['ruleParams']['language']->$filedArray[2];
	// 								}
	// 							} else { // Other fields
	// 								$row[$field] = $record->$field;
	// 							}
	// 						}
	// 					}						
	// 					$result['values'][$id] = $row;
	// 					$result['count']++;
	// 				}
	// 			}
	// 		}
	// 		else {
	// 			// Get the error message
	// 			$body = $return->__get('body');
	// 			throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
	// 		}		
	 	} catch (\Exception $e) {
	 	    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';		
	 	}		
	 	return $result;
    }


    public function read_last($param) {
        $result = array();
        try{
           // '?orderby=modified'
            // Only retrieve one result 
        //    $lastRecordUrlSuffix = '?order=desc&per_page=1' ;

           //get all instances of the module
           $read = $this->read($param);
        
            
           	// Format output values
            if (!empty($read['error'])) {
                $result['error'] = $read['error'];
            } else {
                if (!empty($read['values'])) {
                    $result['done'] = true;
                    // Get only one record
                    $result['values'] = $read['values'];
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
	// 2020-07-08T12:33:06+02:00 to 2020-07-08 10:33:06
	protected function dateTimeToMyddleware($dateTime) {
		$dto = new \DateTime($dateTime);
		// We save the UTC date in Myddleware
		$dto->setTimezone(new \DateTimeZone('UTC'));
		return $dto->format("Y-m-d H:i:s");
	}// dateTimeToMyddleware($dateTime)	
	
	// Convert date to SugarCRM format
	protected function dateTimeFromMyddleware($dateTime) {
		$dto = new \DateTime($dateTime);
		// Return date to UTC timezone
		return $dto->format('Y-m-d\TH:i:s+00:00');
	}// dateTimeToMyddleware($dateTime)    	



}

// Include custom file if it exists : used to redefine Myddleware standard core
$file = __DIR__. '/../Custom/Solutions/woocommerce.php';
if(file_exists($file)){
    require_once($file);
} else { 
    class woocommerce extends woocommercecore {

    }
}