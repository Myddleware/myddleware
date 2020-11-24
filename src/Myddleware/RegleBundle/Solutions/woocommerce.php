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
            $result = [];
            $result['count'] = 0;
            // $result['date_ref'] = $param['ruleParams']['datereference'];

            if(empty($param['limit'])){
                $param['limit'] = $this->defaultLimit;
            }

            //get all data, sorted by date_modified
            $response = $this->woocommerce->get($param['module'], array('orderby' => 'modified'));

            if(isset($response)){
                foreach($response as $record){
                    foreach($param['fields'] as $field){
                        $result['values'][$record->id][$field] = (!empty($record->$field) ? $record->$field : '');
                        $result['values'][$record->id]['date_modified'] = $record->date_modified;
                    }
                    $result['values'][$record->id]['id'] = $record->id;
                }
                    $result['date_ref'] = $this->dateTimeToMyddleware($record->date_modified);
                    $result['count'] = count($response);
            }	

//TODO QUERY ID  (voir sugar)

        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';		
        }		
    // var_dump($result);
        return $result;
    }



    //Get last field (we read ALL then only retrieve one)
    public function read_last($param) {
        $result = array();
        try{
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