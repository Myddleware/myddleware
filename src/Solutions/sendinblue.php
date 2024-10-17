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
    protected string $baseUrl = 'https://app.sendinblue.com/';
    protected array $required_fields = [
                                    'default' => ['id', 'modifiedAt'],
                                    'transactionalEmails' => ['uuid', 'date'],
                                    'transactionalEmailActivity' => ['messageId', 'event', 'date'],
                                    'contactHardBounces' => ['id', 'eventTime'],
                                    'contactSoftBounces' => ['id', 'eventTime'],
                                    'contactUnsubscriptions' => ['id', 'eventTime'],
                                ];
    protected array $FieldsDuplicate = ['contacts' => ['email', 'SMS']];
    protected int $limitEmailActivity = 100;
    protected int $limitCallContact = 1000;
    protected bool $sendDeletion = true;

    public function getFieldsLogin(): array
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
        $apiInstance = new \SendinBlue\Client\Api\AccountApi(new \GuzzleHttp\Client(), $this->config);

        try {
            $result = $apiInstance->getAccount();
            if (!empty($result->getEmail())) {
                $this->connexion_valide = true;
            } else {
                return ['error' => 'Failed to connect to Sendinblue: '.$result->message];
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    //Get module list
    public function get_modules($type = 'source'): array
    {
        if ('source' == $type) {
            return [
               'contacts' => 'Contacts',
               'contactHardBounces' => 'Contact hard bounces',
               'contactSoftBounces' => 'Contact soft bounces',
               'contactUnsubscriptions' => 'Contact unsubscriptions',
               'transactionalEmails' => 'Transactional emails',
               'transactionalEmailActivity' => 'Transactional email activity',
            ];
        }

        return [
            'contacts' => 'Contacts',
        ];
    }

    //Returns the fields of the module passed in parameter
    public function get_module_fields($module, $type = 'source', $param = null): array
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
            $apiInstance = new \SendinBlue\Client\Api\AttributesApi(new \GuzzleHttp\Client(), $this->config);
            $results = $apiInstance->getAttributes();
            $attributes = $results->getAttributes();
            // $this->moduleFields = $moduleFields['transactionalEmails'];  //add attributes for transaction
            foreach ($attributes as $attribute) {
                $this->moduleFields[$attribute->getName()] = [
                    'label' => $attribute->getName(),
                    'required' => false,
                    'type' => 'varchar(255)',
                    'type_bdd' => 'varchar(255)',
                    'required_relationship' => false,
                    'relate' => false,
                ];
            }
            $this->moduleFields['id'] = [
                'label' => 'ID',
                'required' => false,
                'type' => 'varchar(255)',
                'type_bdd' => 'varchar(255)',
                'required_relationship' => false,
                'relate' => true,
            ];
            $this->moduleFields['email'] = [
                'label' => 'email',
                'required' => false,
                'type' => 'varchar(255)',
                'type_bdd' => 'varchar(255)',
                'required_relationship' => false,
                'relate' => false,
            ];
			$this->moduleFields['emailBlacklisted'] = [
                'label' => 'Email blacklisted',
                'required' => false,
                'type' => 'bool',
                'type_bdd' => 'bool',
                'required_relationship' => false,
                'relate' => false,
            ];
			$this->moduleFields['smsBlacklisted'] = [
                'label' => 'SMS blacklisted',
                'required' => false,
                'type' => 'bool',
                'type_bdd' => 'bool',
                'required_relationship' => false,
                'relate' => false,
            ];

            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    /**
     * @throws \SendinBlue\Client\ApiException
     * @throws \Exception
     */
    public function read($param)
    {
        $result = [];
		$offset = 0;
		$records = [];
        // Function are differents depending on the type of record we read from Sendinblue
        switch ($param['module']) {
			case 'transactionalEmailActivity':
                // event is required
                if (empty($param['ruleParams']['event'])) {
                    throw new \Exception('No event selected. Please select an event on your rule. ');
                }
                // As we build the id (it doesn't exist in Sendinblue), we add it to the param field
                $param['fields'][] = 'id';
                // ini call parameters
                $nbCall = 1;
                $limitCall = $this->limitEmailActivity;
                $dateStart = null;
                $dateEnd = null;
                $event = null;
                $messageId = null;
                $limitLastCall = 1;

                // Set call parameters when we search a specific transactional email
                if (!empty($param['query']['id'])) {
                    // id = <message_id>+__+<event>
                    $searchParam = explode('__', $param['query']['id']);
                    if (!empty($searchParam[0])) {
                        $messageId = $searchParam[0];
                    } else {
                        throw new \Exception('No event found in the id  '.$param['query']['id'].'. Failed to search the record into Sendinblue');
                    }
                    if (!empty($searchParam[1])) {
                        $event = $searchParam[1];
                    } else {
                        throw new \Exception('No event found in the id  '.$param['query']['id'].'. Failed to search the record into Sendinblue');
                    }
                    // Set call parameters when we read transactional email using reference date
                } else {
                    $event = $param['ruleParams']['event'];
                    // if simulation, we init the date start to today - 30 days
                    if ('simulation' == $param['call_type']) {
                        $dateRefObj = new \DateTime('NOW');
                        $dateRefObj->sub(new \DateInterval('P30D'));
                    } else {
						// TO BE CHANGED
						$dateRefObj = date_create($param['date_ref'], new \DateTimeZone("Europe/Paris"));
                    }
                    // Only date (not datetime) are used to filter transaction email activity
                    $dateStart = $dateRefObj->format('Y-m-d');
					$dateEnd   = $dateRefObj->format('Y-m-d');
                }
                $apiInstance = new \SendinBlue\Client\Api\TransactionalEmailsApi(new \GuzzleHttp\Client(), $this->config);

                $contactRequested = array_search('contactId', $param['fields']);
                if (false !== $contactRequested) {
                    $apiContactInstance = new \SendinBlue\Client\Api\ContactsApi(new \GuzzleHttp\Client(), $this->config);
                }
				$offset = 0;
				$exit = false;				
				do {
                    $resultApi = $apiInstance->getEmailEventReport($this->limitEmailActivity, $offset, $dateStart, $dateEnd, null, null, $event, null, $messageId, null, 'desc');

					// Exit the loop if no result 
                    if (empty(current($resultApi)['events'])) {
						$exit = true;
						break;
					}
					// Add records read into result array
					$events = current($resultApi)['events'];
					foreach ($events as $record) {
						$offset++;
						$record['id'] = $record['messageId'].'__'.$record['event'];

						// if the contactid is requested, we use the email to get it
						if (false !== $contactRequested) {
							try {
								$resultContactApi = $apiContactInstance->getContactInfo($record['email']);
								if (!empty(current($resultContactApi)['id'])) {
									$record['contactId'] = current($resultContactApi)['id'];
								}
							} catch (\Exception $e) {
								$record['contactId'] = '';
							}
						}

						$dateRecordObj = \DateTime::createFromFormat(DATE_RFC3339_EXTENDED, $record['date']);
						if ($dateRefObj->format('U') > $dateRecordObj->format('U')) {
							$exit = true;
							break;
						}
						$records[] = $record;
					}

					// If the limit hasn't been reached, it means there is no more result to read. We stop the read action.
					if (count($events) < $this->limitEmailActivity) {
						$exit = true;
					}
                } while (!$exit);
                break;
			// Manage contacts and data linked to the contacts
            case 'contacts':
            case 'contactSoftBounces':
            case 'contactHardBounces':
            case 'contactUnsubscriptions':
                $apiInstance = new \SendinBlue\Client\Api\ContactsApi(new \GuzzleHttp\Client(), $this->config);
                // Read with a specific id, email or phone
                if (
                    !empty($param['query']['id'])
                 or !empty($param['query']['email'])
                 or !empty($param['query']['SMS'])
                ) {
                    // Search key for contact can be email, SMS or id
                    if (!empty($param['query']['id'])) {
						if ($param['module'] == 'contacts') {
							$shearchKey = $param['query']['id'];
						// In case of other modules, we extract the contact id (first part) from the record id
						} else { 
							$shearchKey = explode('_',$param['query']['id'])[0];
						}
                    } elseif (
                            !empty($param['query']['email'])
                        and !empty($param['query']['SMS'])
                    ) {
                        $shearchKey = $param['query']['email'];
                        $shearchKey2 = $param['query']['SMS'];
                    } else {
                        $shearchKey = (!empty($param['query']['email']) ? $param['query']['email'] : $param['query']['SMS']);
                    }
                    // Get the info from contact, an exception is generated by getContactInfo if the contact isn't found
                    try {
                        // Search with first key
                        $resultApi = $apiInstance->getContactInfo($shearchKey);
                    } catch (\Exception $e) {
                        // Search with second key if not found with first key
                        if (!empty($shearchKey2)) {
                            try {
                                $resultApi = $apiInstance->getContactInfo($shearchKey2);
                            } catch (\Exception $e) {
                                $error = $e->getMessage();
                                // No exception if history call (check if the contact exists) and contact not found
                                if (
                                        'history' == $param['call_type']
                                    and false !== strpos($error, 'document_not_found')
                                ) {
                                    return false;
                                }
                                // exception generated if not history call
                                throw new \Exception('Exception when calling ContactsApi->getContactInfo: '.$error);
                            }
                        } else {
                            $error = $e->getMessage();
                            // No exception if history call (check if the contact exists) and contact not found
                            if (
                                    'history' == $param['call_type']
                                and false !== strpos($error, 'document_not_found')
                            ) {
                                return false;
                            }
                            // exception generated if not history call
                            throw new \Exception('Exception when calling ContactsApi->getContactInfo: '.$error);
                        }
                    }
                    // Format results
                    if (!empty(current($resultApi))) {
                        $records[] = current($resultApi);
                    }
				// Search all contact modified after the reference date
                } else {
					$limitReached = false;
                    $dateRef = $this->dateTimeFromMyddleware($param['date_ref']);
                    $modifiedSince = new \DateTime($dateRef);
					if ($param['limit'] < $this->limitCallContact) {
						$this->limitCallContact = $param['limit'];
					}
					// Get all contacts modified since the date in parameter
					do {
						$recordsCall = [];
						$resultApi = $apiInstance->getContacts($this->limitCallContact, $offset, $modifiedSince, 'asc');
						$recordsCall = $resultApi->getContacts();
						if (!empty($recordsCall)) {
							$records = array_merge($recordsCall, $records);
						}
						$offset += $this->limitCallContact;
					} while (!empty($recordsCall));
					// If several call, we sort by date modified (sort is by date created in Brevo) and limit the result
					if ($offset > $param['limit']) {
						// Order data in the date_modified order
						$modified = array_column($records, 'modifiedAt');
						array_multisort($modified, SORT_ASC, $records);
						// Get only the number of record requested
						$records = array_slice($records, 0, $param['limit']); 
						$limitReached = true;
					}
                }

				// In case we search data linked to teh contacts
				if ($param['module'] != 'contacts') {
					// Get the stats linked to the contact
					$contactsStats = $this->getContactsStats($param, $records);
					// Return records only if exist (contacts can have no record linked)
					if (!empty($contactsStats['records'])) {
						$records = $contactsStats['records'];
					} else {
						$records = array();
					}
					
					// Return param, we could force the reference date
					if (!empty($contactsStats['ruleParams'])) {
						$result['ruleParams'] = $contactsStats['ruleParams'];
					}

					// if we reach the limit and the new date ref equal the old date ref then we generate an error
					if (
							$limitReached
						AND $contactsStats['ruleParams'][0]['value'] == $param['date_ref']
					) {
						throw new \Exception('All records read have the same reference date. Please increase the number of data read by changing the limit attribute in job');
					}
				}
                break;
            default:
                throw new \Exception('Unknown module: '.$param['module']);
                break;
        }

        //Recover all contact from sendinblue
        if (!empty($records)) {
            $idField = $this->getIdName($param['module']);
            foreach ($records as $record) {
                foreach ($param['fields'] as $field) {
                    if (!empty($record[$field])) {
                        $result[$record[$idField]][$field] = $record[$field];
                    // Result attribute can be an object (example function getContacts())
                    } elseif (!empty($record['attributes']->$field)) {
                        $result[$record[$idField]][$field] = $record['attributes']->$field;
                    // Result attribute can be an array (example function getContactInfo())
                    } elseif (
                            is_array($record['attributes'])
                        and !empty($record['attributes'][$field])
                    ) {
                        $result[$record[$idField]][$field] = $record['attributes'][$field];
                    } else {
                        $result[$record[$idField]][$field] = '';
                    }
                }
            }
        }
        return $result;
    }
	
	protected function getContactsStats($param, $records) {
		$compaignId = '';
		$newDateRef = '';
		$result = array();
		if (!empty($records)) {
			$apiInstance = new \SendinBlue\Client\Api\ContactsApi(new \GuzzleHttp\Client(), $this->config);
			$dateRef = $this->dateTimeFromMyddleware($param['date_ref']);
			$moduleKey = lcfirst(str_replace('contact','',$param['module']));
			// For each contact found in the first search
			foreach ($records as $record) {
				// Get contact detail to retrive the statistics
				$recordDetail = $apiInstance->getContactInfo($record['id']);
				if (!empty($recordDetail['statistics'][$moduleKey])) {
					// Get only the record requested using the campignId (only in case a specific record is requested)
					if (!empty($param['query']['id'])) {
						$compaignId = explode('_',$param['query']['id'])[2];
					}
					// For unsubscriptions, we merge user and admin unsubscriptions
					if ($moduleKey == 'unsubscriptions') {
						$contactStats = array_merge($recordDetail['statistics'][$moduleKey]['userUnsubscription'],$recordDetail['statistics'][$moduleKey]['adminUnsubscription']);
					} else {
						$contactStats = $recordDetail['statistics'][$moduleKey];
					}
					// For each statistics corresponding to the module 
					foreach($contactStats as $contactStat) {
						$recordId = $record['id'].'_'.$moduleKey.'_'.$contactStat['campaignId'];
						// Get the data when a specific record is requested
						if(!empty($compaignId)) {
							if($contactStat['campaignId'] == $compaignId) {
								$result['records'][] = array(
												'id' => $recordId,
												'contactId' => $record['id'],
												'email' => $record['email'],
												'eventTime' => $contactStat['eventTime'],
												'campaignId' => $contactStat['campaignId']
											);
								break;
							} 
						// Get the data when search by date_ref
						} else {
							if (
									$contactStat['eventTime'] > $dateRef
								AND !empty($record['email'])
							) {
								$result['records'][] = array(
												'id' => $recordId,
												'contactId' => $record['id'],
												'email' => $record['email'],
												'eventTime' => $contactStat['eventTime'],
												'campaignId' => $contactStat['campaignId']
											);
							}
						}
					}
				}
				// Save the max date ref from the contact list
				if (
						empty($newDateRef)
					 OR $newDateRef < $record['modifiedAt']
				) {
					$newDateRef = $record['modifiedAt'];
				}
			}
			// We force reference date using ruleParams to set the last contact read even if there is no statistics for these contact
			// Because we don't want Myddleware to read again the same contacts (happens if no statitistic on the contacts read) 
			if (!empty($newDateRef)) {
				$result['ruleParams'][] = array('name' => 'datereference', 'value' => $this->dateTimeToMyddleware($newDateRef));
			}
		}
		return $result;
	}

	// Method de find the date ref after a read call
    protected function getReferenceCall($param, $result)
    {
		if ($param['module'] == 'transactionalEmailActivity') {
			$currentDate = new DateTime();
			$dateRefObj = new \DateTime($param['date_ref']);
			// If date ref < today then we force the referenece date to date+1 at midnight
			if ($dateRefObj->format('Y-m-d') < $currentDate->format('Y-m-d')) {
				$dateRefObj->modify('+1 day');
				$dateRefObj->setTime(0, 0, 0);
				return $dateRefObj->format('Y-m-d H:i:s');
			}
		}
        // Call parent function (calsse solution
        return parent::getReferenceCall($param, $result);
    }

    //fonction for get all your transactional email activity
    public function EmailTransactional($param): \SendinBlue\Client\Model\GetEmailEventReport
    {
        $apiInstance = new \SendinBlue\Client\Api\TransactionalEmailsApi(new \GuzzleHttp\Client(), $this->config);
        $limit = 50;
        $offset = 0;
        $startDate = '2020-01-01';
        $endDate = '2020-01-01';
        $messageId = '<202112150919.44488315490@smtp-relay.mailin.fr>';
        $templateId = 2;

        try {
            $result = $apiInstance->getEmailEventReport($limit, $offset, $startDate, $endDate, $messageId, $templateId);
        } catch (\Exception $e) {
            echo 'Exception when calling TransactionalEmailsApi->getEmailEventReport: ', $e->getMessage();
        }

        return $result;
    }

    // Create the record

    /**
     * @throws \SendinBlue\Client\ApiException
     */
    protected function create($param, $record, $idDoc = null): ?int
    {    
		try {
			// Import or create new contact for sendinblue
			$apiInstance = new \SendinBlue\Client\Api\ContactsApi(new \GuzzleHttp\Client(), $this->config);
			$createContact = new \SendinBlue\Client\Model\CreateContact(); // Values to create a contact
			$createContact['email'] = $record['email'];
			// Add attributes
			$createContact['attributes'] = $record;
			// Change the position of the data emailBlacklisted and smsBlacklisted
			if (isset($updateContact['attributes']['emailBlacklisted'])) {
				$updateContact['emailBlacklisted'] = $updateContact['attributes']['emailBlacklisted'];
			}
			if (isset($updateContact['attributes']['smsBlacklisted'])) {
				$updateContact['smsBlacklisted'] = $updateContact['attributes']['smsBlacklisted'];
			}	
			$result = $apiInstance->createContact($createContact);
		} catch (\Exception $e) {
            throw new \Exception('Exception when calling ContactsApi->createContact: '.$e->getMessage());
        }
		return $result->getId();
    }

    // Update the record

    /**
     * @throws \Exception
     */
    protected function update($param, $record, $idDoc = null)
    {
        try {
            $apiInstance = new \SendinBlue\Client\Api\ContactsApi(new \GuzzleHttp\Client(), $this->config);
            $updateContact = new \SendinBlue\Client\Model\UpdateContact(); // Values to create a contact
            // target_id contains the id of the record to be modified
            $identifier = $record['target_id'];
            $updateContact['attributes'] = $record;
			// Change the position of the data emailBlacklisted and smsBlacklisted
			if (isset($updateContact['attributes']['emailBlacklisted'])) {
				$updateContact['emailBlacklisted'] = $updateContact['attributes']['emailBlacklisted'];
			}
			if (isset($updateContact['attributes']['smsBlacklisted'])) {
				$updateContact['smsBlacklisted'] = $updateContact['attributes']['smsBlacklisted'];
			}			
            $result = $apiInstance->updateContact($identifier, $updateContact);
        } catch (\Exception $e) {
            throw new \Exception('Exception when calling ContactsApi->updateContact: '.$e->getMessage());
        }

        return $identifier;
    }

    // Check data before create
    protected function checkDataBeforeCreate($param, $data, $idDoc)
    {
        $data = parent::checkDataBeforeCreate($param, $data, $idDoc);

        return $this->setBooleanValues($data);
    }

    // Check data before create
    protected function checkDataBeforeUpdate($param, $data, $idDoc)
    {
        $data = parent::checkDataBeforeUpdate($param, $data, $idDoc);

        return $this->setBooleanValues($data);
    }

    // Change text value true and false to boolean value
    protected function setBooleanValues($record)
    {
        foreach ($record as $field => $value) {
            if ('true' === $value) {
                $record[$field] = true;
            } elseif ('false' === $value) {
                $record[$field] = false;
            }
        }

        return $record;
    }

    // delete the record
    protected function delete($param, $record)
    {
        try {
            $apiInstance = new \SendinBlue\Client\Api\ContactsApi(new \GuzzleHttp\Client(), $this->config);
            $updateContact = new \SendinBlue\Client\Model\UpdateContact(); // Values to create a contact
            // target_id contains the id of the record to be modified
            $identifier = $record['target_id'];
            $updateContact['attributes'] = $record;
            $result = $apiInstance->deleteContact($identifier);
        } catch (\Exception $e) {
            throw new \Exception('Exception when calling ContactsApi->deleteContact: '.$e->getMessage());
        }

        return $identifier;
    }

    // Convert date to Myddleware format
    // 2020-07-08T12:33:06 to 2020-07-08 10:33:06
    /**
     * @throws \Exception
     */
    protected function dateTimeToMyddleware($dateTime)
    {
        $dto = new \DateTime($dateTime);
		$dto->setTimezone(new \DateTimeZone('UTC'));
        return $dto->format('Y-m-d H:i:s');
    }

    //convert from Myddleware format to Sendinble format

    /**
     * @throws \Exception
     */
    protected function dateTimeFromMyddleware($dateTime)
    {
        $dto = new \DateTime($dateTime);
        // Return date to UTC timezone
        return $dto->format('Y-m-d\TH:i:s.uP');
    }

    /**
     * @throws \Exception
     */
    protected function dateTimeToDate($dateTime): string
    {
        $dto = new \DateTime($dateTime);

        return $dto->format('Y-m-d');
    }

    public function getFieldsParamUpd($type, $module): array
    {
        $params = parent::getFieldsParamUpd($type, $module);
        try {
            if ('source' == $type) {
                if ('transactionalEmails' == $module) {
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
                if ('transactionalEmailActivity' == $module) {
                    // Add param to store the fieldname corresponding to the record id
                    $templateId = [
                        'id' => 'event',
                        'name' => 'event',
                        'type' => 'option',
                        'label' => 'Event',
                        'required' => true,
                        'option' => [
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
                                        'error' => 'error',
                                    ],
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
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }

        return $params;
    }

    // Return a specific id for some modules
    public function getIdName($module): string
    {
        if ('transactionalEmails' == $module) {
            return 'uuid';
        }

        return parent::getIdName($module);
    }

    // Returns the name of the reference date field according to the module and mode of the rule
    public function getRefFieldName($param): string
    {
        switch ($param['module']) {
            case 'transactionalEmails':
                return 'date';
                break;
            case 'transactionalEmailActivity':
                return 'date';
                break;
            case 'contactHardBounces':
            case 'contactSoftBounces':
            case 'contactUnsubscriptions':
                return 'eventTime';
                break;
            default:
                return 'modifiedAt';
                break;
        }
    }

    // Build the direct link to the record (used in data transfer view)
    public function getDirectLink($rule, $document, $type): ?string
    {
        // Get url, module and record ID depending on the type
        if ('source' == $type) {
            $module = $rule->getModuleSource();
            $recordId = $document->getSource();
        } else {
            $module = $rule->getModuleTarget();
            $recordId = $document->gettarget();
        }
        if ('contacts' == $module) {
            // Build the URL (delete if exists / to be sure to not have 2 / in a row)
            return $this->baseUrl.'contact/index/'.$recordId;
        }

        return null;
    }
}

class sendinblue extends sendinbluecore
{
}
