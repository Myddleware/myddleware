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
                                ];
    protected array $FieldsDuplicate = ['contacts' => ['email', 'SMS']];
    protected int $limitEmailActivity = 100;
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
                $offset = 0;
                $records = [];
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
                    // Because offset is managed in this function, we don't need the +1 in the rule param limit
                    if ($param['limit'] > 1) {
                        --$param['limit'];
                    }

                    $event = $param['ruleParams']['event'];
                    // if simulation, we init the date start to today - 30 days
                    if ('simulation' == $param['call_type']) {
                        $dateStartObj = new \DateTime('NOW');
                        $dateStartObj->sub(new \DateInterval('P30D'));
                    } else {
                        $dateStartObj = new \DateTime($param['date_ref']);
                    }
                    // Only date (not datetime) are used to filter transaction email activity
                    $dateStart = $dateStartObj->format('Y-m-d');
                    $dateEnd = $this->getDateEnd($dateStartObj);

                    // Make sure that offset exists
                    if (empty($param['ruleParams']['offset'])) {
                        $param['ruleParams']['offset'] = 0;
                    } else {
                        // Change offset if the parameter exists on the rule
                        $offset = $param['ruleParams']['offset'];
                    }

                    // Max call limit = 100
                    // Nb call depend on limit param
                    if ($param['limit'] > $this->limitEmailActivity) {
                        $nbCall = floor($param['limit'] / $this->limitEmailActivity);
                        // Add 1 call if needid (using modulo function)
                        if ($param['limit'] % $this->limitEmailActivity != 0) {
                            $limitLastCall = $param['limit'] % $this->limitEmailActivity;
                            ++$nbCall;
                        }
                        $limitCall = $this->limitEmailActivity;
                    } else {
                        // If rule limit < $limitCall , there is no need to call more records
                        $limitCall = $param['limit'];
                    }

                    // If rule limit modulo limitcall is null then last call will be equal to limitcall
                    if ($param['limit'] % $this->limitEmailActivity == 0) {
                        $limitLastCall = $this->limitEmailActivity;
                    }
                }
                $apiInstance = new \SendinBlue\Client\Api\TransactionalEmailsApi(new \GuzzleHttp\Client(), $this->config);

                $contactRequested = array_search('contactId', $param['fields']);
                if (false !== $contactRequested) {
                    $apiContactInstance = new \SendinBlue\Client\Api\ContactsApi(new \GuzzleHttp\Client(), $this->config);
                }

                for ($i = 1; $i <= $nbCall; ++$i) {
                    // The limit can be different for the last call (in case of several call)
                    if (
                            $i == $nbCall
                        and $nbCall > 1
                    ) {
                        $limitCall = $limitLastCall;
                    }
                    $resultApi = $apiInstance->getEmailEventReport($limitCall, $offset, $dateStart, $dateEnd, null, null, $event, null, $messageId, null, 'asc');

                    if (!empty(current($resultApi)['events'])) {
                        $events = current($resultApi)['events'];

                        // Add records read into result array
                        foreach ($events as $record) {
                            ++$offset;
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

                            $records[] = $record;
                            // IF the date change, we set the offset to 0 (because filter is only on date and not dateTime
                            /// Date start we be changed with the date of the current record
                            // Date ref will also be changed
                            $dateRecordObj = new \DateTime($record['date']);
                            if (
                                    empty($param['query']['id'])	// No offset management if search by id
                                and $dateRecordObj->format('Y-m-d') != $dateStartObj->format('Y-m-d')
                            ) {
                                $dateStartObj = $dateRecordObj;
                                $dateStart = $dateStartObj->format('Y-m-d');
                                $dateEnd = $this->getDateEnd($dateStartObj);

                                // Offset = 1 not 0 becquse we have alredy read the first record of the day
                                $offset = 1;
                            }
                        }

                        // If the limit hasn't been reached, it means there is no more result to read. We stop the read action.
                        if (count($events) < $this->limitEmailActivity) {
                            break;
                        }
                    }
                }
                // Save the offset value on the rule
                // No offset management if search by id
                if (empty($param['query']['id'])) {
                    $result['ruleParams'][] = ['name' => 'offset', 'value' => $offset];
                }
                break;
            case 'contacts':
                $apiInstance = new \SendinBlue\Client\Api\ContactsApi(new \GuzzleHttp\Client(), $this->config);
                // Read with a specific id, email or phone
                if (
                    !empty($param['query']['id'])
                 or !empty($param['query']['email'])
                 or !empty($param['query']['SMS'])
                ) {
                    // Search key for contact can be email, SMS or id
                    if (!empty($param['query']['id'])) {
                        $shearchKey = $param['query']['id'];
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
                } else {
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

    protected function getDateEnd($dateObj): string
    {
        $dateEndObj = clone $dateObj;
        $dateEndObj->add(new \DateInterval('P30D'));
        $dateNow = new \DateTime('NOW');
        // Date end can't be greater than today
        if ($dateEndObj->format('Ymd') > $dateNow->format('Ymd')) {
            $dateEndObj = $dateNow;
        }

        return $dateEndObj->format('Y-m-d');
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
            print_r($result);
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
