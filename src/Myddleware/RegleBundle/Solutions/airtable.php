<?php

/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2022  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

use DateTime;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class airtablecore extends solution {

    protected $airtableURL = 'https://api.airtable.com/v0/';
    protected $metadataApiEndpoint = 'https://api.airtable.com/v0/meta/bases/';
    protected $projectID;
    protected $token;
    protected $delaySearch = '-1 month';
    /**
     * From AirTable API doc : 
     * pageSize = 100 
     * maxRecords => if higher than pageSize, multiple pages must be loaded in request
     * @var integer
     */
    protected $defaultLimit = 100; 

    //Log in form parameters
    public function getFieldsLogin()
    {
        return array(
            array(
                'name' => 'projectid',
                'type' => TextType::class,
                'label' => 'solution.fields.projectid'
            ),
            array(
                'name' => 'apikey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.apikey'
            )
        );
    }

    /**
     * Request to attempt to log in to Airtable
     *
     * @param array $paramConnexion
     * @return void
     */
    public function login($paramConnexion) {
        parent::login($paramConnexion);
        try {
            $this->projectID = $this->paramConnexion['projectid'];
            $this->token =  $this->paramConnexion['apikey'];
            $client = HttpClient::create();
            $options = ['auth_bearer' => $this->token];
            // we test the connection to the API with a request on Contacts
            // TODO: this needs to be changed as I assume that each customer might've different modules
            // request sent to Airtable.com to get metadata API access keys, awaiting response
            $response = $client->request('GET', $this->airtableURL.$this->projectID.'/Contacts', $options);
            // $response = $client->request('GET', $this->metadataApiEndpoint.$this->projectID.'/Accounts', $options);
            // $response = $client->request('GET', $this->metadataApiEndpoint, $options);
            // $response = $client->request('GET', "https://api.airtable.com/v0/meta/bases", $options);
            $statusCode = $response->getStatusCode();
            $contentType = $response->getHeaders()['content-type'][0];
            $content = $response->getContent();
            $content = $response->toArray();
    
            if(!empty($content) && $statusCode === 200){
                $this->connexion_valide = true;
            }
        }catch(\Exception $e){
            $error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
            $this->logger->error($error);
            return array('error' => $error);
        }
    }

    // BIG ISSUE: Modules & Fields are ENTIRELY CUSTOM, therefore, 
    //how can we access these fields from Myd without API Meta ??

    /**
     * Retrieve the list of modules available to be read or sent
     *
     * @param string $type source|target
     * @return array
     */
    public function get_modules($type = 'source') {
        // TODO: once we get access to the metadata API key, 
        // we should be able to use this endpoint to list all modules & fields
        // $metadataTables = "https://api.airtable.com/v0/meta/bases/$this->projectID/tables";
        // return $this->getMetadata();
        return array(
            'Accounts' =>	'Accounts',
            'Contacts' =>	'Contacts',
            );
    }

    /**
     * Retrieve the list of fields (metadata) associated to each module
     *
     * @param string $module
     * @param string $type
     * @return array
     */
    public function get_module_fields($module, $type = 'source') {
        parent::get_module_fields($module, $type);
                // TODO: once we get access to the metadata API key, 
        // we should be able to use this endpoint to list all modules & fields
        // $metadataTables = "https://api.airtable.com/v0/meta/bases/$this->projectID/tables";
        try {
            require_once('lib/airtable/metadata.php');
            // $this->loginMetadataAPI();
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

    /**
     * Read records in source application & transform them to fit standard Myddleware format
     *
     * @param array $param
     * @return array
     */
    public function read($param){
        try {
            $baseID = $this->paramConnexion['projectid'];
            $module = $param['module'];
            $result = [];
            $result['count'] = 0;
            $result['date_ref'] = $param['ruleParams']['datereference'];
            if(empty($param['limit'])){
                $param['limit'] = $this->defaultLimit;
            }
            // Remove Myddleware's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
            // Add required fields
			$param['fields'] = $this->addRequiredField($param['fields'],$module);
            $stop = false;
            $count = 0;
            $page = 1;
            do {
                $client = HttpClient::create();
                $options = ['auth_bearer' => $this->token];
                //specific record requested
                if (!empty($param['query']['id'])) {
                    $id = $param['query']['id'];
                    $response = $client->request('GET', $this->airtableURL.$baseID.'/'.$module.'/'.$id, $options);
                    $statusCode = $response->getStatusCode();
                    $contentType = $response->getHeaders()['content-type'][0];
                    $content2 = $response->getContent();
					$content2 = $response->toArray();
					// Add a dimension to fit with the rest of the method
					$content[] = $content2;
                } else {
                    // all records
                    $response = $client->request('GET', $this->airtableURL.$baseID.'/'.$module, $options);
                    $statusCode = $response->getStatusCode();
                    $contentType = $response->getHeaders()['content-type'][0];
                    $content = $response->getContent();
                    $content = $response->toArray();
                }
                // TODO: relationships !!!! 
                // TODO: records DO NOT HAVE A DATE MODIFIED ATTRIBUTE
                if(!empty($content['records'])){
                    $currentCount = 0;
                    //used for complex fields that contain arrays
                    $content = $this->convertResponse($param, $content['records']);  
                    foreach($content as $record){
                        $currentCount++;
                        foreach($param['fields'] as $field){
                            $result['values'][$record['id']][$field] = (!empty($record['fields'][$field]) ? $record['fields'][$field] : '');
                        }
                
                        // TODO: FIND AN ALTERNATIVE TO THIS => for now if date_modified doesn't exist, we set it to NOW (which ofc isn't viable)
                        $dateModif = (!empty($record['fields']['date_modified'])) ? $record['fields']['date_modified'] : new DateTime();
                        var_dump($dateModif);
                        $result['values'][$record['id']]['date_modified'] = $this->dateTimeToMyddleware($dateModif);
                        $result['values'][$record['id']]['id'] = $record['id'];
                    }
                    $result['count']++;
                    $count++;
                } else {
                    $stop = true;
                }
                $page++;
            } while(!$stop && $currentCount === $this->defaultLimit) ;
        } catch (\Exception $e){
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';	  
        }
        return $result;
    }

    /**
     * Reads the last inserted record (latest) from the source application
     *
     * @param array $param
     * @return array
     */
    public function read_last($param){
        $result = array();
        try{
            //for simulation purposes, we create a new date_ref in the past
            $param['ruleParams']['datereference'] = date('Y-m-d H:i:s', strtotime($this->delaySearch));
            //get all instances of the module
            $read = $this->read($param);

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

    /**
     * Create data into target app
     *
     * @param [type] $param
     * @return void
     */
    public function create($param){
        return $this->upsert('create', $param);
    }

    /**
     * Update existing data into target app
     *
     * @param [type] $param
     * @return void
     */
    public function update($param){
        return $this->upsert('update', $param);
    }

    /**
     * Undocumented function
     *
     * @param string $method create|update
     * @param array $param
     * @return void
     */ 
    public function upsert($method, $param){
        // Airtable expects data to come in a 'records' array
        $body = [];
        $body['records']= [];
        // TODO: paquets de 10 plutot que 1 requet 1 par 1
        foreach($param['data'] as $idDoc => $data){
            try{
                $baseID = $this->paramConnexion['projectid'];
                $result= array();
                $param['method'] = $method;
                $module = ucfirst($param['module']);
                $data = $this->checkDataBeforeCreate($param, $data);
                if($method === 'create'){
                    unset($data['target_id']);
                }
                // unset($data['createdTime']);
                $body['records'][]['fields'] = $data;
                $client = HttpClient::create();
                $options = [
                    'auth_bearer' => $this->token,
                    'json' => $body,
                    'headers' => ['Content-Type' => 'application/json']
                ];
                if($method === 'create'){
                    $response = $client->request('POST', $this->airtableURL.$baseID.'/'.$module, $options);
                    $statusCode = $response->getStatusCode();
                    $contentType = $response->getHeaders()['content-type'][0];
                    $content = $response->getContent();
                    $content = $response->toArray();
                } else {
                    // UPDATE => checking => appel airtable ac ID & READ avec Query !  
                    // TODO: bugfix update (issue with target ID ) : right now duplicates are sent without checking whether there's already a record or not
                    $targetId = $data['target_id'];
                    $body['records'][]['id'] = $targetId;
                    unset($data['target_id']);
                    $response = $client->request('PATCH', $this->airtableURL.$baseID.'/'.$module, $options);
                    $statusCode = $response->getStatusCode();
                    $contentType = $response->getHeaders()['content-type'][0];
                    $content = $response->getContent();
                    $content = $response->toArray();
                }
            $response = $content;
            if($response){
                $record = $response['records'][0];
                if(!empty($record['id'])){
                    $result[$idDoc] = array(
                                            'id' => $record['id'],
                                            'error' => false
                                    );
                } else {
                    throw new \Exception('Error during '.print_r($response));
                }
            }
            }catch(\Exception $e){
                $error = $e->getMessage();
                // var_dump($error);
                $result[$idDoc] = array(
                                        'id' => '-1',
                                        'error' => $error
                                        );
            } 
            // Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
        }
        return $result;
    }

    // Check data before create 
	// Add a throw exeption if error
	protected function checkDataBeforeCreate($param,$data) {
		// Exception if the job has been stopped manually
        $this->isJobActive($param);
		return $data;
	}

	// Check data before update 
	// Add a throw exeption if error
	protected function checkDataBeforeUpdate($param,$data) {
		// Exception if the job has been stopped manually
		$this->isJobActive($param);
		return $data;
	}
	
	// Check data before update 
	// Add a throw exeption if error
	protected function checkDataBeforeDelete($param,$data) {
		// Exception if the job has been stopped manually
		$this->isJobActive($param);
		return $data;
	}


    protected function convertResponse($param, $response) {

        return $response;
    }

    // Convert date to Myddleware format 
	// 2020-07-08T12:33:06 to 2020-07-08 12:33:06
	protected function dateTimeToMyddleware($dateTime) {
		$dto = new \DateTime($dateTime);
		return $dto->format("Y-m-d H:i:s");  //TODO: FIND THE EXACT FORMAT : 2015-08-29T07:00:00.000Z
	}
	
    //convert from Myddleware format to Woocommerce format
	protected function dateTimeFromMyddleware($dateTime) {
		$dto = new \DateTime($dateTime);
		return $dto->format('Y-m-d\TH:i:s');
	}

}

// Include custom file if it exists : used to redefine Myddleware standard core
$file = __DIR__. '/../Custom/Solutions/airtable.php';
if(file_exists($file)){
    require_once($file);
} else { 
    class airtable extends airtablecore {

    }
}
