<?php

declare(strict_types=1);

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

use stdClass;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * YouSign API Swagger documentation : https://swagger.yousign.com/
 * YouSign API Dev documentation : https://dev.yousign.com/
 * As per YouSign API doc, there's no native way to switch between prod & staging (sandbox), for further info :
 * https://dev.yousign.com/#environments
 * https://dev.yousign.com/#323c7029-0d92-46a7-b10e-3d409147831e
 * 
 * TODO: We need to find a way to use the events triggered via the YouSign WebHooks
 * and possibly use those instead of datereference as a trigger to read files data (& then download the PDF files)?
 * Or pass updatedAT property from the /files/{id} endpoint while actually targettting /files/{id}/download.
 */
class yousign extends solution
{
    protected $callLimit = 10;
    // Enable to read deletion and to delete data
    protected bool $readDeletion = true;
    protected bool $sendDeletion = true;
    /**
     * All YouSign API calls go through the same URL, it is the API Key only which determines which API we're using
     * and serves as auth credentials.
     * Therefore, the user only needs to choose whether they want to use the prod or the staging (sandbox) environment.
     *
     * @var string
     */
    protected $prodBaseUrl = 'https://api.yousign.com';
    protected $stagingBaseUrl = 'https://staging-api.yousign.com';

    protected array $required_fields = ['default' => ['id', 'updatedAt', 'createdAt']];
	
	protected $parentModules = array(
									'file_objects' => array(
											// Member module is parenty of file_objects module
											'parentModule' => 'members', 
											// In member module, fileObjects is the key that contains file_objects records
											'key' => 'fileObjects',
											// Additional fields to be returned as relate fields 
											'fieldsLevel1' => array('procedure' => 'id'),
											'fieldsLevel2' => array('member' => 'id', 'user' => 'user'),
									),
									'members' => array(
											'parentModule' => 'procedures',
											'key' => 'members',
											'fieldsLevel1' => array('procedure' => 'id'),
									)
								);		


    /**
     * Fields displayed on UI for user to fill in in order to be able to log in to the YouSign API.
     * The Sandbox field only accepts '0' and '1' and acts as a boolean to determine which base URL to use.
     *
     * @return array
     */
    public function getFieldsLogin()
    {
        return [
            [
                'name' => 'sandbox',
                'type' => TextType::class,
                'label' => 'solution.fields.sandbox',
            ],
            [
                'name' => 'apikey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.apikey',
            ],
        ];
    }

    /**
     * Using the API key & the URL (staging sandbox or prod) provided by the user's input,
     * this method attempts to get a success response from the YouSign API via a GET request.
     * Since there's no actual endpoint to verify succesful authentication / login,
     * we currently make an API call to a 'random' endpoint which should be filled in from the get-go (organizations)
     * to test whether the API key provided by the user is correct.
     *
     * @param mixed $paramConnexion
     *
     * @return void
     */
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            $result = $this->youSignCall('organizations');
            if (!empty($result)) {
                $this->connexion_valide = true;
            } else {
                throw new \Exception('Failed to connect but no error returned by YouSign API.');
            }
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function get_modules($type = 'source')
    {
        return [
            'users' => 'Users',
            'file_objects' => 'File objects',
            'files' => 'Files',
            'download_files' => 'Download Files',
            'procedures' => 'Procedures',
            'members' => 'Members',
        ];
    }

    /**
     * @param string $module
     * @param string $type
     * @param null   $param
     *
     * @return array|bool
     */
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            //Use yousign metadata !just for user and procedure! to review
            require 'lib/yousign/metadata.php';
            if (!empty($moduleFields[$module])) {
                $this->moduleFields = array_merge($this->moduleFields, $moduleFields[$module]);
            }

            if (!empty($fieldsRelate[$module])) {
                $this->fieldsRelate = $fieldsRelate[$module];
            }

            if (!empty($this->fieldsRelate)) {
                $this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
            $this->logger->error($error);

            return false;
        }
    }

    /**
     * Get data from YouSign API via cURL call based on parameters (limit, date_ref),
     * Transform it to Myddleware-readable data,
     * Return the data.
     *
     * @param array $param
     *
     * @return array|bool
     */
    public function read($param)
    {	
		// We don't use limit because we can't sort data by updated date. We could miss records if we use limit.
		$nbPage = 1;
		$result = array();
		// If call by date_ref, we use the parent module (ex: procedure) because only parent module can read by date
		// But search by id is available for all modules
		if (!empty($param['query']['id'])) {
			$moduleApi = $param['module'];
		} else {
			// Get the module used for API call (check sub module level 1, e.g memebers->procedures)
			$moduleApi = (!empty($this->parentModules[$param['module']]['parentModule']) ? $this->parentModules[$param['module']]['parentModule'] : $param['module']);	
			// Get the module used for API call (check sub module level 2, e.g file_objects->memebers->procedures)
			$moduleApi = (!empty($this->parentModules[$moduleApi]) ? $this->parentModules[$moduleApi]['parentModule'] : $moduleApi);
		}
		
		// Yousign use only date as filter, not datetime
		$dateRef = $this->removeTimeFromDateRef($param['date_ref']);
		// We read all data from the day (not hour) of the refrerence date and we keep only the ones with an updatedDate (datetime type) > dateRef (with time)
		do {
			$response = array();
			// In case an id is specified
			if (!empty($param['query']['id'])) {
				$endpoint = $moduleApi.'/'.$param['query']['id'];
			// Read action using reference date	
			} else {
				$endpoint = $moduleApi.'?itemsPerPage='.$this->callLimit.'&pagination=true&page='.$nbPage.'&updatedAt[after]='.$dateRef;	
			}
	
			// Call YouSign API			
			$responseYouSign = $this->youSignCall($endpoint);
			
			// Format response
			if (!empty($responseYouSign)) {
				// Add a dimension to the array if the call is executed with an id. By this way we will get the same format result than the call by reference date
				if (empty($param['query']['id'])) {
					$response = $responseYouSign;
				} else {	
					$response[] = $responseYouSign;
				}
			}
			// Error management
			if (!empty($response->title)) {
				throw new \Exception('Failed to read '.$moduleApi.' : '.$response->title.' - '.$response->detail.' ('.$response->type.')');
			}
			// Format and filter result		
			$resultCall = $this->transformResponseToMyddlewareResultsFormat($response, $param);			
			// Merge the result call for the current page into the global result
			if (!empty($resultCall)) {
				$result = array_merge($result,$resultCall);
			}
			// Read the next page
			$nbPage++;			
		// Stop if there is no more record to read
		// or if we read a specific record
		} while (
				!empty($response)
			AND count($response) >= $this->callLimit
			AND empty($param['query']['id'])
		);			
        return $result;
    }

    
    

    /**
     * Convert the result of the GET request to YouSign API to Myddleware-readable data.
     */
    public function transformResponseToMyddlewareResultsFormat(array $response, array $param): array
    {
        $result = [];
		// Manage result for each records returned by YouSign
        foreach ($response as $record) {
			// Current module (e.g file_objects)
			$module = $param['module'];
		
			// Browse the module to check if it is a main module (procedure) , a sub module (members) or a sub sub module (file_objects) 
			// Example level 1 members		: members->procedures
			// Example level 2 file_objects : file_objects->members->procedures
			if (
					!empty($this->parentModules[$module]['parentModule'])
				AND empty($param['query']['id'])	// No search in parent module if we search a specific id	
			) {
				// Get parent module data 
				// Example level 1 (file_objects) : $parentModule = members ; $moduleKey = fileObjects 
				$moduleKey = $this->parentModules[$module]['key'];
				$parentModule = $this->parentModules[$module]['parentModule'];
				
				// Check if the module called is a parent module level 2 
				if (!empty($this->parentModules[$parentModule]['parentModule'])) {
					// Get parent parent module data 
					// Example level 2 (members) : $parentModuleKey = members
					$parentModuleKey = $this->parentModules[$parentModule]['key'];
					
					// Read all sub records level 1 to get sub records level 2
					if (!empty($record->$parentModuleKey)) {
						$subSubRecords = array();
						// Each sub records level 1 can contains several sub records (level 2)
						foreach($record->$parentModuleKey as $subrecords) {
							// If sub records (level 2) exist, we add them to the result
							if (!empty($subrecords->$moduleKey)) {
								foreach ($subrecords->$moduleKey as $subLevel2Records) {
									// Get additional fields from parent module level 1 
									if (!empty($this->parentModules[$module]['fieldsLevel1'])) {
										foreach ($this->parentModules[$module]['fieldsLevel1'] as $keyFieldLevel1 => $valueFieldLevel1) {
											$subLevel2Records->$keyFieldLevel1 = $this->cleanId($record->$valueFieldLevel1);
										}
									}
									// Get additional fields from parent module level 2 
									if (!empty($this->parentModules[$module]['fieldsLevel2'])) {
										foreach ($this->parentModules[$module]['fieldsLevel2'] as $keyFieldLevel2 => $valueFieldLevel2) {
											$subLevel2Records->$keyFieldLevel2 = $this->cleanId($subrecords->$valueFieldLevel2);
										}
									}
									$subSubRecords[] = $subLevel2Records;
								}
							}
						}
					}
				// Only 1 sub level	
				} else {
					// Each record can contains several sub records (level 1)
					if (!empty($record->$module)) {
						foreach ($record->$module as $subLevel1Records) {
							// Get additional fields from parent module level 1 
							if (!empty($this->parentModules[$module]['fieldsLevel1'])) {
								foreach ($this->parentModules[$module]['fieldsLevel1'] as $keyFieldLevel1 => $valueFieldLevel1) {
									$subLevel1Records->$keyFieldLevel1 = $this->cleanId($record->$valueFieldLevel1);
								}
							}
							$subSubRecords[] = $subLevel1Records;
						}
					}
				}
			// No sub level
			} else {
				$subSubRecords[] = $record;	
			}
		
			// $subSubRecords contains all records into an array without sub level. 
			if (!empty($subSubRecords)) {
				// We check each records
				foreach ($subSubRecords as $subSubRecord) {	
					// Remove /moduleName/ from the id because / at begining of the id is incompatible with myddleware (readrecord command)
					$recordId = $this->cleanId($subSubRecord->id);
					
					// If record update date < date ref, we skip the record because it has already been read					
					$updatedAt = $this->dateTimeToMyddleware($subSubRecord->updatedAt);				
					if ($updatedAt <= $param['date_ref']) {						
						continue;
					}
					// Save the record 
					foreach ($param['fields'] as $field) {
						$fieldStructure = explode('__', $field);
						// Direct field
						if (empty($fieldStructure[1])) {
							$result[$recordId][$field] = (!empty($subSubRecord->$field) ? $subSubRecord->$field : '');
						// Field in a sub structure
						} else {
							$structureKey = $fieldStructure[0];
							$structureFieldName = $fieldStructure[1];
							$result[$recordId][$field] = (!empty($subSubRecord->$structureKey->$structureFieldName) ? $subSubRecord->$structureKey->$structureFieldName : '');
						}
					}
					$result[$recordId]['id'] = $recordId;
				}
			}
        }
		// Return result with only the fields requested and records with updatedDate > date_ref
        return $result;
    }
	
	// Function used to check if the source solution has to be called before we send data to the target solution
	public function sourceCallRequestedBeforeSend($send) {	
		// In case the file content is requested, we return that YouSign has to be called to get the file content before sending data
		if ($send['rule']['module_source'] == 'file_objects') {
			if (!empty($send['ruleFields'])) {
				foreach ($send['ruleFields'] as $ruleField) {
					if($ruleField['source_field_name'] == 'file__content') {
						return true;						
					}
				}
			}
		}
		return false;
	}
	
	// Action to be done into the source solution before sending data
	public function sourceActionBeforeSend($send) {		
		// In case the file content is requested, we return that YouSign has to be called to get the file content before sending data
		if ($send['rule']['module_source'] == 'file_objects') {
			if (!empty($send['ruleFields'])) {
				foreach ($send['ruleFields'] as $ruleField) {
					if($ruleField['source_field_name'] == 'file__content') {
						// Get the file content
						if (!empty($send['data'])) {
							foreach ($send['data'] as $docId => $record) {
								// Get the file id from the source data 
								if (!isset($send['source'][$docId]['file__id'])) {
									throw new \Exception('file__id is required if you want to send file content tp the target application. Please concen your document, map the field file__id into you rule. Then run again this document.' );
								}
								// Downloads file content and return it into file_content field
								if (!empty($send['source'][$docId]['file__id'])) {	
									$endpoint = 'files/'.$this->cleanId($send['source'][$docId]['file__id']).'/download';								
									$responseYouSign = $this->youSignCall($endpoint);
									if (
											empty($responseYouSign)
										 OR !empty($responseYouSign->error)
									) {
										throw new \Exception('File content not returned by YouSign'.(empty($responseYouSign->error) ? '.' : $responseYouSign->error.' - '.$responseYouSign->error_description));
									}							
									// Add content to send data
									$send['source'][$docId]['file__content'] = $responseYouSign;
									$this->fieldsChangedBeforeSend[] = 'file__content';
								}
							}
						}						
					}
				}
			}
		}	
		return parent::sourceActionBeforeSend($send);
	}

	// Format id are like /<module>/id, this function returns only the id
	protected function cleanId($id){
		if (!empty($id)) {
			// Transform id into an arry and return the last value
			$arrayId = explode('/', $id);
			if (!empty($arrayId[2])) {
				return $arrayId[2];
			}
		}
		return $id;
	}
	

    /**
     * Returns the reference date field name according to the module & rulemode.
     *
     * @param string $param
     *
     * @return string|null
     */
    public function getRefFieldName($param)
    {
        if (in_array($param['ruleParams']['mode'], ['0', 'S', 'C'])) {
            return 'updatedAt';
        } else {
            throw new \Exception("$param[ruleParams][mode] is not a correct Rule mode.");
        }

        return null;
    }

    /**
     * Convert date to Myddleware format.
     *
     * 2020-07-08T12:33:06+02:00 to 2020-07-08 10:33:06
     *
     * @param string $dateTime
     *
     * @return string
     */
    protected function dateTimeToMyddleware($dateTime)
    {
        $dto = new \DateTime($dateTime);
        // We save the UTC date in Myddleware
        $dto->setTimezone(new \DateTimeZone('UTC'));

        return $dto->format('Y-m-d H:i:s');
    }

    /**
     * Converts the date format to a YouSign format.
     *
     * @param string $dateTime
     *
     * @return string
     */
    protected function dateTimeFromMyddleware($dateTime)
    {
        $dto = new \DateTime($dateTime);
        // Return date to UTC timezone
        return $dto->format('Y-m-d\TH:i:s+00:00');
    }

    /**
     * We take off the 'time' part of the Myddleware 'date_ref' field
     * as the YouSign API query string only accepts a date format as a sorting filter for 'after'/'strictly_after'.
     *
     * @param \DateTime $dateRef
     */
    public function removeTimeFromDateRef(string $dateRef): string
    {
        $dto = new \DateTime($dateRef);

        return $dto->format('Y-m-d');
    }

    /**
     * URLEncode the query string filters to be added to the cURL API request.
     */
    public function cleanUpQueryStringFilter(string $queryString): string
    {
        return urlencode($queryString);
    }
	
	/**
     * Determines which query string to add to the GET request to the YouSign API
     * depending on the module (not all modules are born equal).
     *
     * @param string $module
     * @param array|null $queryStringParams
     * @return string
     */
    public function determineQueryStringBasedOnModule(string $module, ?array $queryStringParams) :string
    {
        $dateRef = $queryStringParams['date_ref'];
        switch ($module) {
            case 'users':
                $queryString = "";
                break;

            case 'files':
                $id= $queryStringParams['file_id'];
                $queryString = "?id=$id";
                break;

            case 'procedures':
                $queryString = "?updatedAt[strictly_after]=$dateRef";
                break;
            
            default:
            $queryString = "";
                break;
        }  
        return $queryString;
        
    }
	
	/**
     * GET cURL call to YouSign API endpoints (modules).
     *
     * @param string $url
     * @param array  $parameters : the APIkey is required on ALL endpoints
     *
     * @return string|bool
     */
    protected function youSignCall($endpoint)
    {
        try {
			// URL changes if we use a sandbox
			$url = (empty($this->paramConnexion['sandbox']) ? $this->prodBaseUrl : $this->stagingBaseUrl);
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url.'/'.$endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer '.$this->paramConnexion['apikey'],
                    'Content-Type: application/json',
                ],
            ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                $this->logger->error($err);
                throw new \Exception('cURL Error #: '.$err);
            }

            return json_decode($response);
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
            $this->logger->error($error);
            return false;
        }
    }
}
