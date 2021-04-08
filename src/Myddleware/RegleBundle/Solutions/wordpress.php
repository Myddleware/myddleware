<?php

/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  StÃ©phane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  StÃ©phane Faure - Myddleware ltd - contact@myddleware.com
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

use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class wordpresscore extends solution {

    protected $apiSuffix = '/wp-json/wp/v2/';
    protected $defaultLimit = 100;
    protected $delaySearch = '-1 month';

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
            $result['date_ref'] = $param['date_ref'];
			$result['count'] = 0;
			// Change the date format only for module with a date as a reference
			if (!in_array($module, $this->moduleWithoutReferenceDate)) {
				$dateRefWPFormat  = $this->dateTimeFromMyddleware($param['date_ref']);
			}

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
            $page = 1;
            $count = 0;
            do {
                $client = HttpClient::create();
				// In case a specific record is requested
				if (!empty($param['query']['id'])) {
					$response = $client->request('GET',$this->paramConnexion['url'].'/wp-json/wp/v2/'.$module.'/'.$param['query']['id']);
					$statusCode = $response->getStatusCode();
					$contentType = $response->getHeaders()['content-type'][0];
					$content2 = $response->getContent();
					$content2 = $response->toArray();
					// Add a dimension to fit with the rest of the method
					$content[] = $content2;
				} else {
					$response = $client->request('GET',$this->paramConnexion['url'].'/wp-json/wp/v2/'.$module.'?per_page='.$this->defaultLimit.'&page='.$page);
					$statusCode = $response->getStatusCode();
					$contentType = $response->getHeaders()['content-type'][0];
					$content = $response->getContent();
					$content = $response->toArray();
				}
              
                if(!empty($content)){
                    $currentCount = 0;
                    //used for complex fields that contain arrays
                    $content = $this->convertResponse($param, $content);     

                    foreach($content as $record){
                        $currentCount++;
						// If the reference is a date we check the date_modified field otherwise we check the id which is an integer
                        if(
							(
									in_array($module, $this->moduleWithoutReferenceDate)
								AND $record['id'] > $param['date_ref']
							)
							OR (
									!in_array($module, $this->moduleWithoutReferenceDate)
								AND $record['modified'] > $dateRefWPFormat
							)
						) {
                            foreach($param['fields'] as $field){
                                $result['values'][$record['id']][$field] = (!empty($record[$field]) ? $record[$field] : '');
                            }
                            if(in_array($module, $this->moduleWithoutReferenceDate)){
                                // the data sent without an API key is different than the one in documentation
                                // need to find a way to generate WP Rest API key / token
                                $result['values'][$record['id']]['date_modified'] = date('Y-m-d H:i:s');
                            }else {
                                $result['values'][$record['id']]['date_modified'] = $this->dateTimeToMyddleware($record['modified']);
                            }
                            $result['values'][$record['id']]['id'] = $record['id'];
                            
							// No reference date for this module so we store the ids in the reference field
							if(in_array($module, $this->moduleWithoutReferenceDate)){
								if ($record['id'] > $result['date_ref']) {
									$result['date_ref'] =  $record['id'];
								}
							}elseif ( $result['values'][$record['id']]['date_modified'] > $result['date_ref']) {
                                if ($result['values'][$record['id']]['date_modified'] > $result['date_ref']) {
									$result['date_ref'] = $result['values'][$record['id']]['date_modified'];
								}
                            }
                            $result['count']++;
                            $count++;
                        }
                    }
                } else {
                    $stop = true;
                }
                $page++;
    
            } while(!$stop && $currentCount === $this->defaultLimit) ;

        }catch(\Exception $e){
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';		  
        }
        return $result;
    }

    //for specific fields (e.g. : event_informations from Woocommerce Event Manager plugin)
    protected function convertResponse($param, $response) {
             $newResponse = array();
             if(!empty($response)){
               foreach($response as $key => $record){  
                    foreach($record as $fieldName => $fieldValue){        
                        if(is_array($fieldValue) ){
                            foreach($fieldValue as $subFieldKey => $subFieldValue){
                                $newSubFieldName = $fieldName.'__'.$subFieldKey;
                                if(is_array($subFieldValue)){
                                    if(array_key_exists(0, $subFieldValue)){
                                        if ($param['module'] != 'mep_event_more_date' ) {
                                            $newResponse[$key][$newSubFieldName] = $subFieldValue[0];  
                                        } elseif ($newSubFieldName === 'event_informations__mep_event_more_date'){
                                            $json = $subFieldValue[0];
                                            $json = unserialize($json);
                                            $moreDatesArray = $json;  
                                            foreach($moreDatesArray as $subSubRecordKey => $subSubRecord){
                                                // we need to manually add the event id here
                                                $eventID = $record['id'];
                                                $moreDatesArray[$subSubRecordKey]['event_id'] = $eventID;
                                                $moreDatesArray[$subSubRecordKey]['id'] = $eventID.'_'.$subSubRecordKey;
                                                $moreDatesArray[$subSubRecordKey]['modified'] = $record['modified'];
                                            } 
                                            $newResponse = array_merge($newResponse, $moreDatesArray);              
                                        }
                                    }
                                } elseif($param['module'] != 'mep_event_more_date') {
                                    $newResponse[$key][$newSubFieldName] = $subFieldValue; 
                                }
                            }
                        } elseif($param['module'] != 'mep_event_more_date'){
                            $newResponse[$key][$fieldName] = $fieldValue;
                        }
                    }    
                }  
            return $newResponse;
        }
        return $response;
    }

    public function read_last($param){
        $result = [];
        try{
            //for simulation purposes, we create a new date_ref in the past
			if (in_array($param['module'], $this->moduleWithoutReferenceDate)) {
				$param['date_ref'] = 0;
			} else {
				$param['date_ref'] = date('Y-m-d H:i:s', strtotime($this->delaySearch));
			}
            $read = $this->read($param);
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
	// 2020-07-08T12:33:06 to 2020-07-08 12:33:06
	protected function dateTimeToMyddleware($dateTime) {
		$dto = new \DateTime($dateTime);
		return $dto->format("Y-m-d H:i:s");
	}
	
    //convert from Myddleware format to Woocommerce format
	protected function dateTimeFromMyddleware($dateTime) {
		$dto = new \DateTime($dateTime);
		return $dto->format('Y-m-d\TH:i:s');
	}

    // Some module hasn't any reference date. In this case, we use the record id as reference
    public function referenceIsDate($module) {
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
