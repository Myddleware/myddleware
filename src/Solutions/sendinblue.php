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
use DateTime;
use DoctrineExtensions\Query\Mysql\Field;
use PhpParser\Node\Name;
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

        $apiInstance = new \SendinBlue\Client\Api\AccountApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            new \GuzzleHttp\Client(),
            $this->config
        );

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
            return $this->moduleFields;  

        } catch (\Exception $e) {
            $error = $e->getMessage();
            return false;
        }      
    }

    public function read($param){
          //Recover date and other...
          print_r($param);
          
          $apiInstance = new \SendinBlue\Client\Api\ContactsApi( new \GuzzleHttp\Client(), $this->config );
          
       $filterArgs = [
			$limit = $param['limit'],
            $offset = $param['offset'],
            $sort = "desc",
		];

        if (!empty($param['query']['id'])) {
            $resultApi = $apiInstance->getContactInfo($param['query']['id']);
            if (!empty(current($resultApi))) {
               $contacts[] = current($resultApi);
               //print_r($contacts);
            }
            
            
           /* print_r(current($result));
            return null;

            $contactInfos = $result[$param['query']['id']];
            foreach ($contactInfos as $contactInfo) {
                if(!empty($contactInfo)){
                    echo $result = $contactInfo.chr(10);  
                }
            }    */                
        }else {
            $filterArgs[$modifiedSince] = $param['date_ref'];
            $resultApi = $apiInstance->getContacts();
            $contacts = $resultApi->getContacts();
        }
      
        
        //Recover all contact sendinblue 
        /*$apiInstance = new \SendinBlue\Client\Api\ContactsApi( new \GuzzleHttp\Client(), $this->config );
        $resultApi = $apiInstance->getContacts();
        $contacts = $resultApi->getContacts();*/

        if(!empty($contacts)){
            foreach($contacts as $contact){
               // print_r($contact);
                //print_r($param['fields']);
                foreach ($param['fields'] as $field) {
                    //echo $field.chr(10);   
                    if (!empty($contact[$field])) {
                        //echo $contact[$field].chr(10);
                        $result[$contact['id']][$field] = $contact[$field];
                    // Result attribute can be an object (example function getContacts())
                    } elseif(!empty($contact['attributes']->$field)) {
                        //echo $contact['attributes']->$field.chr(10);
                        $result[$contact['id']][$field] = $contact['attributes']->$field;  
                    // Result attribute can be an array (example function getContactInfo())                    
                    }elseif(!empty($contact['attributes'][$field])) {
                        //echo $contact['attributes'][$field].chr(10);
                        $result[$contact['id']][$field] = $contact['attributes'][$field];                    
                    } else {
                        $result[$contact['id']][$field] = '';
                    }                    
                }
            } 
        }

        // Read with a specific id   

        /*if (!empty($param['query']['id'])) {
                    $contactInfos = $result[$param['query']['id']];
                    foreach ($contactInfos as $contactInfo) {
                        if(!empty($contactInfo)){
                            echo $result = $contactInfo.chr(10);  
                        }
                    }                    
                }else {
                    $contactInfo = '';
                }
            try {
                if (!empty($param['query']['id'])) {
                    $contactInfos = $result[$param['query']['id']];
                    foreach ($contactInfos as $contactInfo) {
                        if(!empty($contactInfo)){
                            echo $result = $contactInfo.chr(10);  
                        }
                    }                    
                }else {
                    $contactInfo = '';
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }    */
            //print_r($result);
        //return null;
        return $result;
    }

    // Convert date to Myddleware format 
    // 2020-07-08T12:33:06 to 2020-07-08 10:33:06
    protected function dateTimeToMyddleware($dateTime) {	
        $dto = new \DateTime($dateTime);	
        return $dto->format("Y-m-d H:i:s");
    }

    // Returns the name of the reference date field according to the module and mode of the rule
    public function getRefFieldName($moduleSource, $RuleMode) {
    return 'modifiedAt';
    }
}

class sendinblue extends sendinbluecore
{

}

