<?php

/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class wordpresscore extends solution {

    protected $apiSuffix = '/wp-json/wp/v2/';
    protected $defaultLimit = 100;
    protected $delaySearch = '-1 month';

    public function getFieldsLogin(){
        return array(
                    array(
                        'name' => 'url',
                        'type' => TextType::class,
                        'label' => 'solution.fields.url'
                    )
                );
        
    }

    public function login($paramConnexion){
        parent::login($paramConnexion);
        try  {
             
            $client = HttpClient::create();
            $response = $client->request('GET', $this->paramConnexion['url'].$this->apiSuffix.'pages');
            $statusCode = $response->getStatusCode();
            $contentType = $response->getHeaders()['content-type'][0];
            $content = $response->getContent();
            $content = $response->toArray();
            if(!empty($content) && $statusCode === 200){
                $this->connexion_valide = true;
            }
        }catch(\Exception $e){
            $error = $e->getMessage();
            $this->logger->error($error);
            return array('error' => $error);
        }
    }

    public function get_modules($type = 'source') {
        return array(
            'posts' =>	'Posts',
            'categories' =>	'Categories',
            'tags' =>	'Tags',
            'pages'	 => 'Pages',
            'comments' =>	'Comments',
            'taxonomies' =>	'Taxonomies',
            'media' =>	'Media',
            'users'	 =>'Users',
            'types'=>	'Post Types',
            'statuses'=> 'Post Statuses',
            'settings' =>	'Settings',
            'themes' =>	'Themes',
            'search' =>	'Search',
            'block-types'=>	'Block types',
            'blocks' =>	'Blocks',
            'block-renderer' =>	'Block renderer',
            'plugins' =>'Plugins'
            );
    }


    public function get_module_fields($module, $type = 'source') {
        parent::get_module_fields($module, $type);
        try {
            require_once('lib/wordpress/metadata.php');
            if(!empty($moduleFields[$module])){
                $this->moduleFields = $moduleFields[$module];
            }

            if (!empty($fieldsRelate[$module])) {
				$this->fieldsRelate = $fieldsRelate[$module]; 
			}	

            return $this->moduleFields;

        }catch(\Exception $e){
            $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());		
			return false;
        }
    }

    public function read($param){
        try {
          
            $result = [];
            $module = $param['module'];
            $result['count'] = 0;
            $result['date_ref'] = $param['ruleParams']['datereference'];
          //il faudra rajouter le if < 
            $dateRefWooFormat  = $this->dateTimeFromMyddleware($param['ruleParams']['datereference']);


            //for submodules, we first send the parent module in the request before working on the submodule with convertResponse()
            if(!empty($this->subModules[$param['module']])){
                $module = $this->subModules[$param['module']]['parent_module'];
            } 

            // Remove Myddleware's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);

			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields'],$module);

            if(empty($param['limit'])){
                $param['limit'] = $this->defaultLimit;
            }
           
            $stop = false;
            $count = 0;
            $page = 1;
//boucle infinie ???
            // do {
               
                $client = HttpClient::create();
                $response = $client->request('GET',$this->paramConnexion['url'].'/wp-json/wp/v2/'.$module);
                $statusCode = $response->getStatusCode();
                $contentType = $response->getHeaders()['content-type'][0];
                $content = $response->getContent();
                $content = $response->toArray();
                
              
           
                if(!empty($content)){
                    //used for submodules (e.g. line_items)
                    $content = $this->convertResponse($param, $content);
                    foreach($content as $record){
                        foreach($param['fields'] as $field){        
                            // If we have a 2 dimensional array we break it down  
                            $fieldStructure = explode('__',$field);
                            $fieldGroup = '';
                            $fieldName = '';
                            if (!empty($fieldStructure[1])) {
                                $fieldGroup = $fieldStructure[0];
                                $fieldName = $fieldStructure[1];
                                $result['values'][$record['id']][$field] = (!empty($record[$fieldGroup][$fieldName]) ? $record[$fieldGroup][$fieldName] : '');
                            } else {
                                $result['values'][$record['id']][$field] = (!empty($record[$field]) ? $record[$field] : '');
                            }
                        }

                        if($module === 'users'){
                            // the data sent without an API key is different than the one in documentation
                            // need to find a way to generate WP Rest API key / token
                            // $result['values'][$record['id']]['date_modified'] = $record['registered_date'];
                            // $result['values'][$record['id']]['date_modified'] = new \Datetime();
                            $result['values'][$record['id']]['date_modified'] = date('Y-m-d H:i:s', strtotime($this->delaySearch));
                        }else{
                            $result['values'][$record['id']]['date_modified'] = $record['modified'];
                        }
                        
                        $result['values'][$record['id']]['id'] = $record['id'];
                        $count++;
                    }
                }
                $page++;
                $result['count'] = $count;
            
        

        }catch(\Exception $e){
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';		  
        }
var_dump($result);
        return $result;
    }

    //for specific modules (e.g. : event_informations)
    public function convertResponse($param, $response) {
        if(array_key_exists($param['module'], $this->subModules)){
            $subModule = $param['module'];   //event_informations
            $newResponse = array();
            if(!empty($response)){
                foreach($response as $key => $record){  
                    foreach($record[$subModule] as $subKey => $subRecord){
                        $newResponse[$subKey]['id'] = $subKey;
                        foreach($subRecord as $subValueKey => $subValue) {
                           if(is_int($subValueKey) ){
                            //  var_dump($newResponse[$subKey]);
                              var_dump($subValue);
                             array_push($newResponse[$subKey], $subValue);
                            //  array_push($subValue, $newResponse[$subKey]);
                            //    array_push($subRecord[$subValueKey], $newResponse[$subKey]);
                            //    var_dump($newResponse[$subKey]);
                           }
                        }

                        // var_dump($newResponse);  
                        // $newResponse[$subKey] = $subRecord[0];
                    
                         $subRecord['date_modified'] = $record['modified'];
                    
                     
                        // //we add the ID of the parent field in the data (e.g. : for event_informations, we add mep_events_id)
                         $parentFieldName = $this->subModules[$subModule]['parent_id'];
                         $subRecord[$parentFieldName] = $record['id'];
                        //  $newResponse[$subKey] = $subRecord;
                    // var_dump($subRecord[0]);
                    //  var_dump($subKey);
                        //   var_dump($subKey, $subRecord);
                        //   $newRecord[]
                        // $newResponse[$subKey] = $subRecord;
                        //  $newResponse[$subRecord][$subkey] = $subRecord;
                    }    
                }
               
            }   
         var_dump($newResponse);   
            return $newResponse;
        }
        return $response;
    }

    public function read_last($param){
        $result = [];
        try{
            //for simulation purposes, we create a new date_ref in the past
            $param['ruleParams']['datereference'] = date('Y-m-d H:i:s', strtotime($this->delaySearch));
            $read = $this->read($param);
            //  var_dump($param);

            if(!empty($read['error'])){
                $result['error'] = $read['error'];
            } else{
                if (!empty($read['values'])){
                    $result['done'] = true;
                    // Get only one record (the API sorts them by date by default, first one is therefore latest modified)
                    $result['values'] = $read['values'][array_key_first($read['values'])]; 
                } else{
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
$file = __DIR__. '/../Custom/Solutions/wordpress.php';
if(file_exists($file)){
    require_once($file);
} else { 
    class wordpress extends wordpresscore {

    }
}