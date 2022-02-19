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
use SendinBlue\Client\Model\UpdateContact;
use SendinBlue\Client\Model\GetContacts;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class sendinbluecore extends solution
{
    protected $config;
    protected $required_fields = [
									'default' => ['id', 'modifiedAt'],
									'transactionalEmails' => ['uuid', 'date'],
									'transactionalEmailActivity' => ['messageId', 'event', 'date'],
								];
    protected $FieldsDuplicate = ['contacts' => ['email']];
    
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
               'contacts' => 'Contacts',
               'transactionalEmails' => 'Transactional emails',
               'transactionalEmailActivity' => 'Transactional email activity'
            ];
        }
        return [
            'contacts' => 'Contacts',
        ];
    }

    //Returns the fields of the module passed in parameter
    public function get_module_fields($module, $type = 'source', $param = null)
    {
        parent::get_module_fields($module, $type);

        //Use Sendinblue metadata
        require 'lib/sendinblue/metadata.php';
        if (!empty($moduleFields[$module])) {
            $this->moduleFields = $moduleFields[$module];
            return $this->moduleFields;
        }
		// Contact fields
        try {
            //Add of the different fields according to the modules
            //Use Sendinblue Api
            $apiInstance = new \SendinBlue\Client\Api\AttributesApi( new \GuzzleHttp\Client(), $this->config );
            $results = $apiInstance->getAttributes();
            $attributes = $results->getAttributes();
            // $this->moduleFields = $moduleFields['transactionalEmails'];  //add attributes for transaction             
            foreach ($attributes as $attribute) {       
                $this->moduleFields [$attribute->getName()] = [
                    'label' => $attribute->getName(),
                    'required' => false,
                    'type' => 'varchar(255)',
                    'type_bdd' => 'varchar(255)',
                    'required_relationship' => false,
                    'relate' => false
                ];  
            }   
            $this->moduleFields ['id'] = [
                'label' => 'ID',
                'required' => false,
                'type' => 'varchar(255)',
                'type_bdd' => 'varchar(255)',
                'required_relationship' => false,
                'relate' => true
            ];  
            $this->moduleFields ['email'] = [
                'label' => 'email',
                'required' => false,
                'type' => 'varchar(255)', 
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
// print_r($param);	
		$result = array(); 
		// Function are differents depending on the type of record we read from Sendinblue
        switch ($param['module']) {
           	case 'transactionalEmailActivity': 
				// event is required
				if (empty($param['ruleParams']['event'])) {
					throw new \Exception('No event selected. Please select an event on your rule. ');
				}
				
				// As we build the id (it doesn't exist in Sendinblue), we add it to the param field
				$param['fields'][] = 'id';
				
				// Only date are used note datetime
				$dateStart = new \DateTime($param['date_ref']);
				$dateEnd = new \DateTime($param['date_ref']);
				$dateEnd->add(new \DateInterval('P30D'));
				$dateNow = new \DateTime('NOW');
				// Date end can't be greater than today
				if ($dateEnd->format('Ymd') > $dateNow->format('Ymd')) {
					$dateEnd = $dateNow;
				}
				
				// Make sure that offset exists
				if (empty($param['ruleParams']['offset'])) {
					$param['ruleParams']['offset'] = 0;
				}
				
				$offset = (empty($param['ruleParams']['offset']) ? 0 : $param['ruleParams']['offset']);
				$nbCall = 1;
				$limitCall = 100;
				$records = array();
				// Max call limit = 100
				// Nb call depend on limit param
				if ($param['limit'] > 100) {
					$nbCall = round($param['limit']/$limitCall);
					if ($param['limit']%$limitCall != 0) {
						$nbCall++;
					}
				} else {
					$limitCall = $param['limit'];
				}
                $apiInstance = new \SendinBlue\Client\Api\TransactionalEmailsApi( new \GuzzleHttp\Client(), $this->config);        
				
				$contactRequested = array_search('contactId', $param['fields']);
				if ($contactRequested !== false) {
					$apiContactInstance = new \SendinBlue\Client\Api\ContactsApi( new \GuzzleHttp\Client(), $this->config );
				}
				
				for ($i = 0; $i < $nbCall; $i++) {
echo '$$contactRequested  '.$contactRequested .chr(10);				
echo '$limitCall '.$limitCall.chr(10);				
echo 'offse'.$param['ruleParams']['offset'].chr(10);				
echo 'start'.$dateStart->format('Y-m-d').chr(10);				
echo 'end'.$dateEnd->format('Y-m-d').chr(10);				
echo 'end'.$param['ruleParams']['event'].chr(10);		
print_r($param['fields']);		
// return null;
					$resultApi = $apiInstance->getEmailEventReport($limitCall, $param['ruleParams']['offset'], $dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d'), null, null, $param['ruleParams']['event'], null, null, null, 'asc');
					if (!empty(current($resultApi)['events'])) {
							
						// Add records read into result array
						foreach(current($resultApi)['events'] as $record) {
// print_r($record);				
							$record['id'] = $record['messageId'].'__'.$record['event'];
							
							// if the contactid is requested, we use the email to get it
							if ($contactRequested !== false) {
								try {
									$resultContactApi = $apiContactInstance->getContactInfo($record['email']);
									if (!empty(current($resultContactApi)['id']))
									$record['contactId'] = current($resultContactApi)['id'];
								} catch (\Exception $e) {
									$record['contactId'] = '';
								}
							}
print_r($record);	
// return null;									
							$records[] = $record;
							$param['ruleParams']['offset']++;
							// IF the date change, we set the offset to 0 (because filter is only on date and not dateTime
							// Date ref will also be changed
							$dateRecordObj = new \DateTime($record['date']);
							if ($dateRecordObj->format('Y-m-d') != $dateStart->format('Y-m-d')) {
								$param['ruleParams']['offset'] = 0;
							}
						}
						
						// If the limit hasn't been reached, it means there is no more result to read. We stop the read action.
						if ($limitCall >= count($records)) {
							break;
						}
						// $offset += $limitCall;
					}
					// Save the offset value on the rule
					$param['ruleParams']['offset'] = $offset;       
				}
				
                break;
            case 'contacts':
                $apiInstance = new \SendinBlue\Client\Api\ContactsApi( new \GuzzleHttp\Client(), $this->config ); 
                // Read with a specific id or email
                if (
                    !empty($param['query']['id']) 
                 OR !empty($param['query']['email'])
                    ) {
					// Search key for contact can be email or id
                    $shearchKey = (!empty($param['query']['id']) ? $param['query']['id'] : $param['query']['email']);
                    // Get the info from contact, an exception is generated by getContactInfo if the contact isn't found
					try {
						$resultApi = $apiInstance->getContactInfo($shearchKey);
					} catch (\Exception $e) {
						$error = $e->getMessage();
						// No exception if history call (check if the contact exists) and contact not found
						if (
								$param['call_type'] == 'history'
							AND strpos($error, 'document_not_found') !== false
						) {
							return false;
						}
						// exception generated if not history call
						throw new \Exception('Exception when calling ContactsApi->getContactInfo: '. $error);
					}
					// Format results
                    if (!empty(current($resultApi))) {
						$records[] = current($resultApi);
                    }                     
                }else {
                    $dateRef = $this->dateTimeFromMyddleware($param['date_ref']);
                    $modifiedSince = new \DateTime($dateRef);
                    $resultApi = $apiInstance->getContacts($param['limit'], '0', $modifiedSince, 'asc');
                    $records = $resultApi->getContacts();            
                } 
                break;
            default:  
				throw new \Exception('Unknown module: '.$param['module']);
                break;
        }
// print_r($records);                
		//Recover all contact from sendinblue 
		if(!empty($records)){
			$idField = $this->getIdName($param['module']);
// echo '$idField '.$idField.chr(10);
// return null;			
			foreach($records as $record){
// print_r($record);					
				foreach ($param['fields'] as $field) {
// echo $field.chr(10);					
					if (!empty($record[$field])) {
						$result[$record[$idField]][$field] = $record[$field];
					// Result attribute can be an object (example function getContacts())
					} elseif(!empty($record['attributes']->$field)) {
						$result[$record[$idField]][$field] = $record['attributes']->$field;  
					// Result attribute can be an array (example function getContactInfo())                    
					}elseif(
							is_array($record['attributes'])
						AND !empty($record['attributes'][$field])
					) {
						$result[$record[$idField]][$field] = $record['attributes'][$field];                    
					}
					else {
						$result[$record[$idField]][$field] = '';
					}                    
				}
			} 
		}   
print_r($result);
return null;					
        return $result;
    }

    //fonction for get all your transactional email activity
    public function EmailTransactional($param){
        $apiInstance = new \SendinBlue\Client\Api\TransactionalEmailsApi( new \GuzzleHttp\Client(), $this->config ); 
        $limit = 50;
        $offset = 0;
        $startDate = '2020-01-01'; 
        $endDate = '2020-01-01';
        $messageId= "<202112150919.44488315490@smtp-relay.mailin.fr>";
        $templateId= 2;
        
        try {
            $result = $apiInstance->getEmailEventReport($limit, $offset, $startDate, $endDate, $messageId, $templateId);
            print_r($result);
        } catch (\Exception $e) {
            echo 'Exception when calling TransactionalEmailsApi->getEmailEventReport: ', $e->getMessage();
        }
        return $result;
    }

     // Create the record 
     protected function create($param, $record) {
        // Import or create new contact for sendinblue 
		$apiInstance = new \SendinBlue\Client\Api\ContactsApi(new \GuzzleHttp\Client(), $this->config);
		$createContact = new \SendinBlue\Client\Model\CreateContact(); // Values to create a contact
		$createContact['email'] = $record['email'];
		// Add attributes
		$createContact['attributes'] = $record; 
		$result = $apiInstance->createContact($createContact);			
		return $result->getId();
     }

    // Update the record 
    protected function update($param, $record) {  
        try {
            $apiInstance = new \SendinBlue\Client\Api\ContactsApi(new \GuzzleHttp\Client(), $this->config);
            $updateContact = new \SendinBlue\Client\Model\UpdateContact(); // Values to create a contact
            // target_id contains the id of the record to be modified        
            $identifier = $record['target_id'];                                 
            $updateContact['attributes'] = $record;            
            $result = $apiInstance->updateContact($identifier, $updateContact);
         } catch (\Exception $e) {
            throw new \Exception('Exception when calling ContactsApi->updateContact: '. $e->getMessage());			
        }    
        return $identifier;
    }

    // Convert date to Myddleware format 
    // 2020-07-08T12:33:06 to 2020-07-08 10:33:06
    protected function dateTimeToMyddleware($dateTime) {	
        $dto = new \DateTime($dateTime);	
        return $dto->format("Y-m-d H:i:s");
    }
     //convert from Myddleware format to Sendinble format
	protected function dateTimeFromMyddleware($dateTime) {
		$dto = new \DateTime($dateTime);
		// Return date to UTC timezone
		return $dto->format('Y-m-d\TH:i:sP');
	}
	
	protected function dateTimeToDate($dateTime) {	
        $dto = new \DateTime($dateTime);	
        return $dto->format("Y-m-d");
    }

	public function getFieldsParamUpd($type, $module)
    {
		$params = parent::getFieldsParamUpd($type, $module);
		try {
			if ('source' == $type) {
				if ($module == 'transactionalEmails') {
					// Add param to store the fieldname corresponding to the record id
					$templateId = [
						'id' => 'templateId',
						'name' => 'templateId',
						'type' => 'text',
						'label' => 'Template id(s) separated by ";" . Or set "All" to get all Emails.',
						'required' => true,
					];
					$params[] = $templateId;
				} 
				if ($module == 'transactionalEmailActivity') {
					// Add param to store the fieldname corresponding to the record id
					$templateId = [
						'id' => 'event',
						'name' => 'event',
						'type' => 'option',
						'label' => 'Event',
						'required' => true,
						'option' => array(
										'delivered' => 'delivered',
										'bounces' => 'bounces',
										'hardBounces' => 'hardBounces',
										'softBounces' => 'softBounces',
										'spam' => 'spam',
										'requests' => 'requests',
										'opened' => 'opened',
										'clicks' => 'clicks',
										'invalid' => 'invalid',
										'deferred' => 'deferred',
										'blocked' => 'blocked',
										'unsubscribed' => 'unsubscribed',
										'error' => 'error'
									),
					];
					$params[] = $templateId;
				} 
				
				
				
			} /* else { // target
				if ($module == 'contacts') {
					// Add param to store the fieldname corresponding to the record id
					$fieldId = [
						'id' => 'fieldId',
						'name' => 'fieldId',
						'type' => 'option',
						'label' => 'Reference field (do not change it if documents have already been sent)',
						'required' => true,
						'option' => array(
										'id' => 'Id (recommended)',
										'email' => 'Email (only if you use Transactional email module)'
									),
					];
					$params[] = $fieldId;
				} 	
			} */
			
		} catch (\Exception $e) {
			return [];
		} 
		return $params;
        
    }
	
	// Return a specific id for some modules
	public function getIdName($module)
    {
		if ($module == 'transactionalEmails') {
			return 'uuid';
		}
		return parent::getIdName($module);
    }

    // Returns the name of the reference date field according to the module and mode of the rule	
	public function getRefFieldName($moduleSource, $RuleMode)
    {
        switch ($moduleSource) {
            case 'transactionalEmails':
                return 'date';
                break;
            case 'transactionalEmailActivity':
                return 'date';
                break;
            default:
                return 'modifiedAt';
                break;
        }
    }
}

class sendinblue extends sendinbluecore
{

}

