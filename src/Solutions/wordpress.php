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

 namespace App\Solutions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class wordpresscore extends solution {

    protected $apiSuffix = '/wp-json/wp/v2/';
    protected $defaultLimit = 100;
    protected $delaySearch = '-1 month';
    protected $delaySearch2 = '-1 week';
  	// Module without reference date
	protected $moduleWithoutReferenceDate = array('users');

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
            //we test the connection to the API with a request on pages
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

            if (!empty($this->fieldsRelate)) {
				$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
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
            $dateRefWPFormat  = $this->dateTimeFromMyddleware($param['ruleParams']['datereference']);
          

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

                $client = HttpClient::create();
                $response = $client->request('GET',$this->paramConnexion['url'].'/wp-json/wp/v2/'.$module);
                $statusCode = $response->getStatusCode();
                $contentType = $response->getHeaders()['content-type'][0];
                $content = $response->getContent();
                $content = $response->toArray();
                
              
                if(!empty($content)){             
                    //used for complex fields that contain arrays
                    $content = $this->convertResponse($param, $content);
                   
                    foreach($content as $record){
                        //ATTENTION CETTE PARTIE DOIT ETRE CHANGEE CAR CEST UN TRAITEMENT FICTIF : on ne recoit aucune date dans users
                       //de toute facon users ne renvoie qu'un seul record !! 
                        if($module === 'users' || $module === 'mep_cat' || $module === 'mep_org'){
                            $record['modified'] =  date('Y-m-d H:i:s', strtotime($this->delaySearch2));
                        }

                        if($dateRefWPFormat < $record['modified']){
                            foreach($param['fields'] as $field){        
                                $result['values'][$record['id']][$field] = (!empty($record[$field]) ? $record[$field] : '');
                              
                            }
                            if($module === 'users'){
                                // the data sent without an API key is different than the one in documentation
                                // need to find a way to generate WP Rest API key / token
                                $result['values'][$record['id']]['date_modified'] = date('Y-m-d H:i:s', strtotime($this->delaySearch));
                            }else{
                                $result['values'][$record['id']]['date_modified'] = $record['modified'];
                            }
                            
                            $result['values'][$record['id']]['id'] = $record['id'];
                            $count++;
                        }
                    }
                }
                $page++;
                $result['count'] = $count;
        }catch(\Exception $e){
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';		  
        }
        return $result;
    }

    //for specific fields (e.g. : event_informations from Woocommerce Event Manager plugin)
    public function convertResponse($param, $response) {
             $newResponse = array();
             if(!empty($response)){
               foreach($response as $key => $record){  
                    foreach($record as $fieldName => $fieldValue){        
                        if(is_array($fieldValue) ){
                            foreach($fieldValue as $subFieldKey => $subFieldValue){
                                $newSubFieldName = $fieldName.'__'.$subFieldKey;
                                if(is_array($subFieldValue)){
                                    if(array_key_exists(0, $subFieldValue)){
                                        $newResponse[$key][$newSubFieldName] = $subFieldValue[0];  
                                        if($newSubFieldName === 'event_informations__mep_event_more_date'){
                                            $json = $subFieldValue[0];
                                            $json = unserialize($json);
                                            $moreDatesArray = $json;
                                            foreach($moreDatesArray as $subSubRecordKey => $subSubRecord){
                                                foreach($subSubRecord as $subSubFieldName => $subSubFieldValue){
                                                    $newResponse[$key][$subSubFieldName] = $subSubFieldValue;
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $newResponse[$key][$newSubFieldName] = $subFieldValue; 
                                }
                            }
                        } else{
                            $newResponse[$key][$fieldName] = $fieldValue;
                        }
                    }    
                }  
    
            return $newResponse;
        }
        return $response;
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

    // Permet d'indiquer le type de référence, si c'est une date (true) ou un texte libre (false)
    public function referenceIsDate($module) {
        // Le module users n'a pas de date de référence. On utilise donc l'ID comme référence
        if(in_array($module, $this->moduleWithoutReferenceDate)){
            return false;
        }
        return true;
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