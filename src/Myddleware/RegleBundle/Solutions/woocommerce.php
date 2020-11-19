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

use Automattic\WooCommerce\Client;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;



class woocommercecore extends solution {


    protected $urlSuffix = '/wp-json/wc/v3/';
    protected $consumerKey;
    protected $consumerSecret;
    // protected $woocommerce;

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

            $woocommerce = new Client(
                $this->paramConnexion['url'],
                $this->paramConnexion['consumerkey'],
                $this->paramConnexion['consumersecret'],
                [
                    'wp_api' => true,
                    'version' => 'wc/v3'
                ]
                );

            $result = $this->call('login', $woocommerce);

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
            if(isset($woocommerce)){
                // print_r($woocommerce);
                 $this->connexion_valide = true;	
                // var_dump($woocommerce->get('products'));
                
            }else{
                throw new \Exception('ALLO?');
            }
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
//	} // login($paramConnexion)*/



//  $woocommerce = new Client(
//     'http://localhost/myddleware/wordpress', 
//     'ck_4d08598e65e7ad6a188fecaeb26d06ecdbdd30b4', 
//     'cs_82858696bfa94993dc4e27cdf59d5cf2432f87c1',
//     [
//         'version' => 'wc/v3',
//     ]
// );

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




    	// Read one specific record
	public function read($param) {
		$result['count'] = 0;
		try {
			// Get the reference date
			$dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
			
			// Add requiered fields 
			$param['fields'] = $this->addRequiredField($param['fields'],$param['module']);
			// Remove fields that doesn't belong to shop application
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
			
			// We build the url (get all data after the reference date)
			$urlApi = $this->url.$param['module'].'/filter/'.$dateRefField.'/superior/'.urlencode($param['date_ref']).'/orderby/date_created/asc'.$this->apiKey;
		
			// Try to access to the shop
			$return = $this->call($urlApi, 'get', '');	
			
			$code = $return->__get('code');
			// If the call is a success
			if ($code == '200') {		
				$body = $return->__get('body');
				if (!empty($body)) {
					// For each record
					foreach ($body as $id => $record) {
						$row = array();
						// For each fields					
						foreach ($param['fields'] as $field) {
							if ($field == $dateRefField) {
								$row['date_modified'] = $record->$field;
								// Save the latest reference date
								if (	
										empty($result['date_ref'])
									 || $record->$field > $result['date_ref']
								) {
									$result['date_ref'] = $record->$field;
								}
							} else {
								// Transform the field to an array in case it is a language fields
								$filedArray = explode('__',$field);						
								$nbLevel = count($filedArray);
								if ($nbLevel == 3) { // Language field
									// We search the language field in the body
									if (
											!empty($param['ruleParams']['language'])
										 &&	!empty($record->$filedArray[0]->$param['ruleParams']['language']->$filedArray[2])
									) {
										$row[$field] = $record->$filedArray[0]->$param['ruleParams']['language']->$filedArray[2];
									}
								} else { // Other fields
									$row[$field] = $record->$field;
								}
							}
						}						
						$result['values'][$id] = $row;
						$result['count']++;
					}
				}
			}
			else {
				// Get the error message
				$body = $return->__get('body');
				throw new \Exception('Code error '.$code.(!empty($body->errors->$code) ? ' : '.$body->errors->$code : ''));
			}		
		}
		catch (\Exception $e) {
		    $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';		
		}		
		return $result;


}

// Include custom file if it exists : used to redefine Myddleware standard core
$file = __DIR__. '/../Custom/Solutions/woocommerce.php';
if(file_exists($file)){
    require_once($file);
} else { 
    class woocommerce extends woocommercecore {

    }
}