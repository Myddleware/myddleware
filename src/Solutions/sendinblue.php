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

use ApiPlatform\Core\OpenApi\Model\Contact;
use ArrayObject;
use DateTime;
use DoctrineExtensions\Query\Mysql\Field;
use PhpParser\Node\Name;
use SendinBlue\Client\Model\CreateContact;
use SendinBlue\Client\Model\GetContacts;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class sendinbluecore extends solution
{
    protected $config;
    protected $required_fields = ['default' => ['id', 'modifiedAt']];

    
    public function getFieldsLogin()
    {
        return [
            [
                'name' => 'login',
                'type' => TextType::class,
                'label' => 'solution.fields.login',
            ],
            [
                'name' => 'apikey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.apikey',
            ],        
        ];
    }

// connect to Sendinblue
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);

        // Configure API key authorization: api-key
        $this->config = \SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->paramConnexion['apikey']);
        $apiInstance = new \SendinBlue\Client\Api\AccountApi( new \GuzzleHttp\Client(), $this->config);

        try {
            $result = $apiInstance->getAccount();
            if (!empty($result->getEmail())) {
                $this->connexion_valide = true;
            } else {
                return ['error' => 'Failed to connect to Sendinblue: '. $result->message];
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);
            
            return ['error' => $error];
        }        
    }
    
    //Get module list
    public function get_modules($type = 'source')
    {
        if ('source' == $type) {
            return [
               'contacts' => 'contacts'
            ];
        }
        return [
            'contacts' => 'contacts',
        ];
    }

    //Returns the fields of the module passed in parameter
    public function get_module_fields($module, $type = 'source', $param = null)
    {
        parent::get_module_fields($module, $type);
        
        try {
            $apiInstance = new \SendinBlue\Client\Api\AttributesApi( new \GuzzleHttp\Client(), $this->config );
            $results = $apiInstance->getAttributes();
            $attributes = $results->getAttributes();
                       
            foreach ($attributes as $attribute) {       
                $this->moduleFields [$attribute->getName()] = [
                    'label' => $attribute->getName(),
                    'required' => false,
                    'type' => 'varchar(255)', // Define the correct type
                    'type_bdd' => 'varchar(255)',
                    'required_relationship' => false,
                    'relate' => false
                ];  
            }   
            $this->moduleFields ['email'] = [
                'label' => 'email',
                'required' => false,
                'type' => 'varchar(255)', // Define the correct type
                'type_bdd' => 'varchar(255)',
                'required_relationship' => false,
                'relate' => false
            ];  
            return $this->moduleFields;  

        } catch (\Exception $e) {
            $error = $e->getMessage();
            return false;
        }      
    }

     // Read all fields
    public function read($param){
        $apiInstance = new \SendinBlue\Client\Api\ContactsApi( new \GuzzleHttp\Client(), $this->config ); 
        $result = array();
        // Read with a specific id or email
        if (
                !empty($param['query']['id']) 
             OR !empty($param['query']['email'])
        ) {
            $shearchKey = (!empty($param['query']['id']) ? $param['query']['id'] : $param['query']['email']);
            $resultApi = $apiInstance->getContactInfo($shearchKey);
            if (!empty(current($resultApi))) {
               $records[] = current($resultApi);
            }             
        }else {
            $dateRef = $this->dateTimeFromMyddleware($param['date_ref']);
            echo $dateRef;
            $modifiedSince = new \DateTime('2011-12-08T14:25:04.848+01:00');
            //$resultApi = $apiInstance->getContacts(100, 0, $modifiedSince);
            $resultApi = $apiInstance->getContacts();
            $records = $resultApi->getContacts();
        }      
    
        //Recover all contact sendinblue 
        if(!empty($records)){
            foreach($records as $record){
                foreach ($param['fields'] as $field) {
                    echo $field.chr(10);   
                    if (!empty($record[$field])) {
                        echo $record[$field].chr(10);
                        $result[$record['id']][$field] = $record[$field];
                    // Result attribute can be an object (example function getContacts())
                    } elseif(!empty($record['attributes']->$field)) {
                        echo $record['attributes']->$field.chr(10);
                        $result[$record['id']][$field] = $record['attributes']->$field;  
                    // Result attribute can be an array (example function getContactInfo())                    
                    }elseif(
                            !empty($param['query'])
                        AND !empty($record['attributes'][$field])
                    ) {
                        echo $record['attributes'][$field].chr(10);
                        $result[$record['id']][$field] = $record['attributes'][$field];                    
                    } else {
                        $result[$record['id']][$field] = '';
                    }                    
                }
            } 
        }
        return $result;
    }

     // Create the record 
     protected function create($param, $record)
     {
        // Import or create new contact for sendinblue 
        print_r($param);    
        //print_r($record);    
        //try {
        $apiInstance = new \SendinBlue\Client\Api\ContactsApi(new \GuzzleHttp\Client(), $this->config);
        $createContact = new \SendinBlue\Client\Model\CreateContact(); // Values to create a contact
        //$record['email'] $record["NOM"] $record["PRENOM"]
        $createContact['email'] = 'exemple10@gmail.com';
        $createContact['attributes']['NOM'] = 'test6';
        $createContact['attributes']['PRENOM'] = 'test6';

        print_r($createContact);
        
       /* $data = '{"
                "attributes": {
                    "NOM": "tototo",
                    "PRENOM": "tototo"
            }
        }';
        $obj = json_decode($data);*/
       

        //$createContact['listIds'] = [1];
            
            $result = $apiInstance->createContact($createContact);
            print_r($result);
            if(!empty($result->getId())){
                print_r($obj->FNAME);
            }
            $result->getId();
            echo $result->getId();
            
       /* } catch (\Exception $e) {
            $error = $e->getMessage();
            return false;
        }    */
        return null;
        //return $result->getId();
     }

    // Update the record 
    protected function update($param, $record)
    {
        // Import or create new contact for sendinblue 
        //print_r($param);    
        //print_r($record);    
        //try {
        $apiInstance = new \SendinBlue\Client\Api\ContactsApi(new \GuzzleHttp\Client(), $this->config);
        $createContact = new \SendinBlue\Client\Model\CreateContact(); // Values to create a contact
        //$record['email'] $record["NOM"] $record["PRENOM"]
        $createContact['email'] = 'exemple10@gmail.com';
        
        //$createContact['attributes']['PRENOM'] = 'test6';

        //print_r($createContact);
        //---Ne fonctionne pas => array_merge/array_push/array_map/créer une fonction autre pour array_map
        $attributes = [
            "NOM" => "tototo",
            "PRENOM" => "tototo"               
            ];
        echo $createContact['email'];
        echo $attributes['NOM'];
        //$createContact->attributes;
        //echo json_encode();
            
            echo $createContact;
        //$createContact['listIds'] = [1];
            
            $result = $apiInstance->createContact($createContact);
            //print_r($result);
            $result->getId();
            echo $result->getId();
            
        /* } catch (\Exception $e) {
            $error = $e->getMessage();
            return false;
        }    */
        return null;
        //return $result->getId();
    }

    // Convert date to Myddleware format 
    // 2020-07-08T12:33:06 to 2020-07-08 10:33:06
    protected function dateTimeToMyddleware($dateTime) {	
        $dto = new \DateTime($dateTime);	
        return $dto->format("Y-m-d H:i:s");
    }
     //convert from Myddleware format to Woocommerce format
	protected function dateTimeFromMyddleware($dateTime) {
		$dto = new \DateTime($dateTime);
		// Return date to UTC timezone
		return $dto->format('Y-m-d\TH:i:sP');
	}

    // Returns the name of the reference date field according to the module and mode of the rule
    public function getRefFieldName($moduleSource, $RuleMode) {
    return 'modifiedAt';
    }
}

class sendinblue extends sendinbluecore
{

}

