<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class zuora extends solution
{
    protected $instance;
    protected int $debug = 0;
    protected string $version = '85.0'; // Maw limit : 50
    protected bool $update = false;
    protected int $limitCall = 10; // Maw limit : 50

    protected array $required_fields = ['default' => ['Id', 'UpdatedDate', 'CreatedDate']];

    // Connection parameters
    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'login',
                'type' => TextType::class,
                'label' => 'solution.fields.login',
            ],
            [
                'name' => 'password',
                'type' => PasswordType::class,
                'label' => 'solution.fields.password',
            ],
            [
                'name' => 'wsdl',
                'type' => TextType::class,
                'label' => 'solution.fields.wsdl',
            ],
            [
                'name' => 'sandbox',
                'type' => TextType::class,
                'label' => 'solution.fields.sandbox',
            ],
        ];
    }

    // Login to Zuora
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            // Get the wsdl (temporary solution)
            $config = new \stdClass();
            $config->wsdl = __DIR__.'/../Custom/Solutions/zuora/wsdl/'.$this->paramConnexion['wsdl'];
            $this->instance = \Zuora_API::getInstance($config);
            if (
                    !empty($this->paramConnexion['sandbox'])
                && 1 == $this->paramConnexion['sandbox']
            ) {
                $domain = 'https://apisandbox.zuora.com/';
            } else {
                $domain = 'https://api.zuora.com/';
            }

            $this->instance->setLocation($domain.'apps/services/a/'.$this->version);
            $this->instance->login($this->paramConnexion['login'], $this->paramConnexion['password']);

            $this->connexion_valide = true;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Get the modules available
    public function get_modules($type = 'source')
    {
        try {
            require_once 'lib/zuora/lib_zuora.php';
            // Get all modules from te wsdl
            $zuoraModules = getObjectListFromWSDL($this->paramConnexion['wsdl'], $this->debug);
            if (!empty($zuoraModules)) {
                // Generate the output array
                foreach ($zuoraModules as $zuoraModule) {
                    $modules[$zuoraModule] = $zuoraModule;
                }
            }

            return $modules;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Get the fields available for the module in input
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            require_once 'lib/zuora/lib_zuora.php';
            $zupraFields = \ZuoraAPIHelper::getFieldList($this->paramConnexion['wsdl'], $module);
            if (!empty($zupraFields)) {
                // Add each field in the right list (relate fields or normal fields)
                foreach ($zupraFields as $field) {
                    // If the fields is a relationship
                    if ('id' == strtolower(substr($field, -2))) {
                        $this->moduleFields[$field] = [
                            'label' => $field,
                            'type' => 'varchar(36)',
                            'type_bdd' => 'varchar(36)',
                            'required' => 0,
                            'required_relationship' => 0,
                            'relate' => true,
                        ];
                    } else {
                        $this->moduleFields[$field] = [
                            'label' => $field,
                            'type' => 'varchar(255)',
                            'type_bdd' => 'varchar(255)',
                            'required' => 0,
                            'relate' => false,
                        ];
                    }
                }
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    public function createData($param): array
    {
        // Get the action because we use the create function to update data as well
        if ($this->update) {
            $action = 'update';
        // If creation and subscription, we use function subscrbe and we limit the call by one
        } elseif ('Subscription' == $param['module']) {
            return $this->subscribe($param);
        } elseif ('Amendment' == $param['module']) {
            return $this->amend($param);
        } else {
            $action = 'create';
        }

        try {
            $idDocArray = '';
            $i = 0;
            // $first = true;
            $nb_record = count($param['data']);
            foreach ($param['data'] as $idDoc => $data) {
                ++$i;
                // Save all idoc in the right order
                $idDocArray[] = $idDoc;
                // Check control before create
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                $obj = 'Zuora_'.$param['module'];
                $zObject = new $obj();

                foreach ($data as $key => $value) {
                    // Field only used for the update and contains the ID of the record in the target solution
                    if ('target_id' == $key) {
                        // If update then we change the key in Id
                        if (!empty($value)) {
                            $key = 'Id';
                        } else { // If creation, we skip this field
                            continue;
                        }
                    }
                    $zObject->$key = $value;
                }
                $zObjects[] = $zObject;
                unset($zObject);

                // If we have finished to read all data or if the package is full we send the data to Sallesforce
                if (
                        $nb_record == $i
                     || 0 == $i % $this->limitCall
                ) {
                    // Manage calls create and update
                    if ('create' == $action) {
                        $resultCall = $this->instance->create($zObjects);
                    } else {
                        $resultCall = $this->instance->update($zObjects);
                    }
                    // General error
                    if (empty($resultCall)) {
                        throw new \Exception('No response from Zuora. ');
                    }

                    // Manage results
                    $j = 0;
                    // If only one result, we add a dimension
                    if (isset($resultCall->result->Id)) {
                        $resultCall->result = [$resultCall->result];
                    }

                    // Get the response for each records
                    foreach ($resultCall->result as $record) {
                        if (!empty($record->Success)) {
                            if (empty($record->Id)) {
                                $result[$idDocArray[$j]] = [
                                    'id' => '-1',
                                    'error' => 'No Id in the response of Zuora. ',
                                ];
                            } else {
                                $result[$idDocArray[$j]] = [
                                    'id' => $record->Id,
                                    'error' => false,
                                ];
                            }
                        } else {
                            $result[$idDocArray[$j]] = [
                                'id' => '-1',
                                'error' => (empty($record->Errors) ? (empty($record->Code) ? 'No error returned by Zuora.' : $record->Code.' : '.$record->Message) : print_r($record->Errors, true)),
                            ];
                        }
                        $this->updateDocumentStatus($idDocArray[$j], $result[$idDocArray[$j]], $param);
                        ++$j;
                    }
                    // Init variable
                    unset($zObjects);
                    $idDocArray = '';
                }
            }
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
            $result['error'] = $error;
        }

        return $result;
    }

    // We use the create function to update data
    public function updateData($param): array
    {
        $this->update = true;

        return $this->createData($param);
    }

    // Specific function for amend action
    protected function amend($param): array
    {
        try {
            foreach ($param['data'] as $idDoc => $data) {
                // Check control before create
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                $obj = 'Zuora_'.$param['module'];
                $amendment = new $obj();
                foreach ($data as $key => $value) {
                    // Field only used for the update and contains the ID of the record in the target solution
                    if ('target_id' == $key) {
                        continue;
                    }
                    $amendment->$key = $value;
                }
                // Amend the souscription
                $resultCall = $this->instance->amend($amendment, null, null);
                // Manage results
                if (!empty($resultCall->results->Errors)) {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => (empty($resultCall->results->Errors) ? 'No error returned by Zuora.' : print_r($resultCall->results->Errors, true)),
                    ];
                // Succes of the subscription
                } elseif (empty($resultCall->results->AmendmentIds)) {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => 'Failed do get the AmendmentIds. No error sent by Zuora. ',
                    ];
                } else {
                    $result[$idDoc] = [
                        'id' => $resultCall->results->AmendmentIds,
                        'error' => false,
                    ];
                }
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
            $result['error'] = $error;
        }

        return $result;
    }

    // Specific function for subscribe action
    protected function subscribe($param): array
    {
        try {
            foreach ($param['data'] as $idDoc => $data) {
                // Check control before create
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                $obj = 'Zuora_'.$param['module'];
                $zObject = new $obj();

                foreach ($data as $key => $value) {
                    // Field only used for the update and contains the ID of the record in the target solution
                    if ('target_id' == $key) {
                        // If update then we change the key in Id
                        if (!empty($value)) {
                            $key = 'Id';
                        } else { // If creation, we skip this field
                            continue;
                        }
                    }
                    if ('AccountId' == $key) {
                        $zAccount = new \Zuora_Account();
                        $zAccount->Id = $value;
                    } elseif ('RatePlan' == $key) {
                        foreach ($value as $docIdRatePlan => $valueRatePlan) {
                            $docIdArray[$idDoc][$docIdRatePlan] = ['type' => 'RatePlan', 'ProductRatePlanId' => $valueRatePlan['ProductRatePlanId']];
                            $zRatePlan = new \Zuora_RatePlan();
                            foreach ($valueRatePlan as $ratePlankey => $ratePlanValue) {
                                // RatePlanCharge and RatePlanChargeTier are added after
                                if (!in_array($ratePlankey, ['RatePlanChargeTier', 'RatePlanCharge'])) {
                                    $zRatePlan->$ratePlankey = $ratePlanValue;
                                }
                            }
                            // RatePlanCharge
                            if (!empty($valueRatePlan['RatePlanCharge'])) {
                                foreach ($valueRatePlan['RatePlanCharge'] as $docIdRatePlanCharge => $valueRatePlanCharge) {
                                    $docIdArray[$idDoc][$docIdRatePlanCharge] = [
                                        'type' => 'RatePlanCharge',
                                        'ProductRatePlanId' => $valueRatePlan['ProductRatePlanId'],
                                        'ProductRatePlanChargeId' => $valueRatePlanCharge['ProductRatePlanChargeId'],
                                    ];
                                    $zRatePlanCharge = new \Zuora_RatePlanCharge();
                                    foreach ($valueRatePlanCharge as $ratePlanChargeKey => $ratePlanChargeValue) {
                                        $zRatePlanCharge->$ratePlanChargeKey = $ratePlanChargeValue;
                                    }
                                    // RatePlanChargeData to store the RatePlanCharge
                                    $zRatePlanChargeData = new \Zuora_RatePlanChargeData($zRatePlanCharge);
                                    unset($zRatePlanCharge);
                                }
                            }
                            // RatePlanChargeTiers
                            if (!empty($valueRatePlan['RatePlanChargeTier'])) {
                                foreach ($valueRatePlan['RatePlanChargeTier'] as $docIdRatePlanChargeTier => $valueRatePlanChargeTier) {
                                    $docIdArray[$idDoc][$docIdRatePlanChargeTier] = '';
                                    $zRatePlanChargeTier = new \Zuora_RatePlanChargeTier();
                                    foreach ($valueRatePlanChargeTier as $ratePlanChargeTierKey => $ratePlanChargeTierValue) {
                                        $zRatePlanChargeTier->$ratePlanChargeTierKey = $ratePlanChargeTierValue;
                                    }
                                }
                                $zRatePlanChargeData->addRatePlanChargeTier($zRatePlanChargeTier);
                            }
                            $zRatePlanData = new \Zuora_RatePlanData($zRatePlan);
                            if (!empty($zRatePlanChargeData)) {
                                $zRatePlanData->addRatePlanChargeData($zRatePlanChargeData);
                            }
                            $zRatePlanDatas[] = $zRatePlanData;
                        }
                    } else {
                        $zObject->$key = $value;
                    }
                }
                // Create objects for the subscribe function
                $zSubscriptionData = new \Zuora_SubscriptionData($zObject);
                unset($zObject);
                if (!empty($zRatePlanDatas)) {
                    foreach ($zRatePlanDatas as $zRatePlanData) {
                        $zSubscriptionData->addRatePlanData($zRatePlanData);
                    }
                }
                unset($zRatePlanDatas);

                // Manage differents calls (subscripe, create and update
                $zSubscribeOptions = new \Zuora_SubscribeOptions(false, false);
                $zSContact = new \Zuora_Contact();
                $zPaymentMethod = new \Zuora_PaymentMethod();
                try {
                    $resultCall = $this->instance->subscribe($zAccount, $zSubscriptionData, $zSContact, $zPaymentMethod, $zSubscribeOptions);
                } catch (\Exception $e) {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => $e->getMessage().' '.$e->getFile().' '.$e->getLine(),
                    ];
                }

                unset($zAccount);
                unset($zSubscriptionData);

                // General error
                if (empty($resultCall)) {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => 'No response from Zuora. ',
                    ];
                }
                // Manage results
                if (!empty($resultCall->result->Errors)) {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => (empty($resultCall->result->Errors) ? 'No error returned by Zuora.' : print_r($resultCall->result->Errors, true)),
                    ];
                // Succes of the subscription
                } elseif (empty($resultCall->result->SubscriptionId)) {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => 'Failed do get the SubscriptionId. No error sent by Zuora. ',
                    ];
                } else {
                    $result[$idDoc] = [
                        'id' => $resultCall->result->SubscriptionId,
                        'error' => false,
                    ];
                    if (!empty($docIdArray[$idDoc])) {
                        foreach ($docIdArray[$idDoc] as $idSubDoc => $values) {
                            // Get the RatePlanID
                            if ('RatePlan' == $values['type']) {
                                $query = "SELECT Id FROM RatePlan WHERE SubscriptionId = '".$resultCall->result->SubscriptionId."' AND ProductRatePlanId = '".$values['ProductRatePlanId']."'";
                                $resultQuery = $this->instance->query($query);
                                $resultId = '';
                                if (1 == $resultQuery->result->size) {
                                    $resultId = $resultQuery->result->records->Id;
                                } elseif ($resultQuery->result->size > 1) {
                                    $resultId = $resultQuery->result->records[0]->Id;
                                }
                                if (!empty($resultId)) {
                                    // If there is several records, we take the first one
                                    $result[$idSubDoc] = [
                                        'id' => $resultId,
                                        'error' => false,
                                    ];
                                    // Save RatePlanId in case we have RatePlanChargeId to get from Zuora
                                    $arrayRatePlanId[$values['ProductRatePlanId']] = $resultId;
                                } else {
                                    $result[$idSubDoc] = [
                                        'id' => '-1',
                                        'error' => 'Failed do get the RatePlanId from Zuora. ',
                                    ];
                                }
                                // Get the RatePlanCharge
                            } elseif ('RatePlanCharge' == $values['type']) {
                                $query = "SELECT Id FROM RatePlanCharge WHERE RatePlanId = '".$arrayRatePlanId[$values['ProductRatePlanId']]."' AND ProductRatePlanChargeId = '".$values['ProductRatePlanChargeId']."'";
                                $resultQuery = $this->instance->query($query);
                                $resultId = '';
                                if (1 == $resultQuery->result->size) {
                                    $resultId = $resultQuery->result->records->Id;
                                } elseif ($resultQuery->result->size > 1) {
                                    $resultId = $resultQuery->result->records[0]->Id;
                                }
                                if (!empty($resultId)) {
                                    // If there is several records, we take the first one
                                    $result[$idSubDoc] = [
                                        'id' => $resultId,
                                        'error' => false,
                                    ];
                                } else {
                                    $result[$idSubDoc] = [
                                        'id' => '-1',
                                        'error' => 'Failed do get theRatePlanChargeId from Zuora. ',
                                    ];
                                }
                            }
                            unset($resultQuery);
                            $this->updateDocumentStatus($idSubDoc, $result[$idSubDoc], $param);
                        }
                    }
                }
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
            $result['error'] = $error;
        }

        return $result;
    }

    // The function return true if we can display the column parent in the rule view, relationship tab
    // We display the parent column when module is subscription
    public function allowParentRelationship($module): bool
    {
        if (in_array($module, ['Subscription', 'RatePlan'])) {
            return true;
        }

        return false;
    }

    protected function queryAll($query): array
    {
        $moreCount = 0;
        $recordsArray = [];
        $totalStart = time();

        $start = time();
        $result = $this->instance->query($query);

        $end = time();
        $elapsed = $end - $start;

        $done = $result->result->done;
        $size = $result->result->size;
        $records = $result->result->records;

        if (0 == $size) {
        } elseif (1 == $size) {
            array_push($recordsArray, $records);
        } else {
            $locator = $result->result->queryLocator;
            $newRecords = $result->result->records;
            $recordsArray = array_merge($recordsArray, $newRecords);
            while (!$done && $locator && 0 == $moreCount) {
                $start = time();
                $result = $this->instance->queryMore($locator);
                $end = time();
                $elapsed = $end - $start;

                $done = $result->result->done;
                $size = $result->result->size;
                $locator = $result->result->queryLocator;
                echo "\nqueryMore";

                $newRecords = $result->result->records;
                $count = count($newRecords);
                if (1 == $count) {
                    array_push($recordsArray, $newRecords);
                } else {
                    $recordsArray = array_merge($recordsArray, $newRecords);
                }
            }
        }

        $totalEnd = time();
        $totalElapsed = $totalEnd - $totalStart;

        return $recordsArray;
    }
}
