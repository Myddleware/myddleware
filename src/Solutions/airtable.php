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

namespace App\Solutions;

use DateTime;
use Exception;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class airtable extends solution
{
    protected bool $sendDeletion = true;

    protected string $airtableURL = 'https://api.airtable.com/v0/';
    protected string $metadataApiEndpoint = 'https://api.airtable.com/v0/meta/bases/';

    /**
     * Airtable base.
     */
    protected string $projectID;
    /**
     * API key (provided by Airtable).
     */
    protected string $token;
    protected string $delaySearch = '-1 month';
    /**
     * Name of the table / module that will be used as the default table to access the login() method
     * This is initialised to 'Contacts' by default as I've assumed that would be the most common possible value.
     * However, this can of course be changed to any table value already present in your Airtable base.
     */
    protected array $tableName;

    /**
     * Can't be greater than 100.
     */
    protected int $defaultLimit = 100;

    /**
     * Max number of records posted by call.
     */
    protected int $callPostLimit = 10;

    //Log in form parameters
    public function getFieldsLogin(): array
    {
        // QUESTION: could we possibly pass a MODULE here ?
        // This would allow us to then only resort to variable in login etc
        // However it will obviously mean 1 connector per module/table, which is of course not ideal.
        return [
            [
                'name' => 'projectid',
                'type' => TextType::class,
                'label' => 'solution.fields.projectid',
            ],
            [
                'name' => 'apikey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.apikey',
            ],
        ];
    }

    /**
     * Request to attempt to log in to Airtable.
     * @param $paramConnexion
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            $this->projectID = $this->paramConnexion['projectid'];
            $this->token = $this->paramConnexion['apikey'];
            // We test the connection to the API with a request on Module/Table (change the value of tableName to fit your needs)
            $client = HttpClient::create();
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
            ];
            $response = $client->request('GET', $this->airtableURL.$this->projectID.'/'.$this->tableName[$this->projectID], $options);
            $statusCode = $response->getStatusCode();
            $contentType = $response->getHeaders()['content-type'][0];
            $content = $response->getContent();
            $content = $response->toArray();
            if (!empty($content) && 200 === $statusCode) {
                $this->connexion_valide = true;
            }
        } catch (Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
            $this->logger->error($error);
        }
    }

    /**
     * Retrieve the list of modules available to be read or sent.
     *
     * @param string $type source|target
     *
     * @return array
     */
    public function get_modules($type = 'source'): array
    {
        if (!empty($this->modules[$this->projectID])) {
            return $this->modules[$this->projectID];
        }

        return [];
    }

    /**
     * Retrieve the list of fields (metadata) associated to each module.
     *
     * @param string $module
     * @param string $type
     * @param array  $param
     *
     * @return array
     */
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        require 'lib/airtable/metadata.php';
        $this->moduleFields = parent::get_module_fields($module, $type);
        try {
            if (!empty($moduleFields[$this->projectID][$module])) {
                $this->moduleFields = array_merge($this->moduleFields, $moduleFields[$this->projectID][$module]);
            }
            return $this->moduleFields;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());
            return false;
        }
    }

    /**
     * Read records in source application & transform them to fit standard Myddleware format.
     *
     * @param array $param
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function readData($param): array
    {
        try {
            $baseID = $this->paramConnexion['projectid'];
            $result = [];
            $result['count'] = 0;
            $result['date_ref'] = $param['ruleParams']['datereference'];
            if (empty($param['limit'])) {
                $param['limit'] = $this->defaultLimit;
            }
            // Remove Myddleware's system fields
            $param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
            // Add required fields
            $param['fields'] = $this->addRequiredField($param['fields'], $param['module']);

            // Get the reference date field name only when we read using reference date
			if (empty($param['query'])) {
				$dateRefField = $this->getDateRefName($param['module'], $param['ruleParams']['mode']);
				// Add the dateRefField in teh field list
				if (array_search($dateRefField, $param['fields']) === false) {
					$param['fields'][] = $dateRefField;
				}
			}
			
            $stop = false;
            $page = 1;
            $offset = '';

            do {
                $client = HttpClient::create();
                $options = ['auth_bearer' => $this->token];
                //specific record requested
                if (!empty($param['query'])) {
                    if (!empty($param['query']['id'])) {
                        $id = $param['query']['id'];
                        $response = $client->request('GET', $this->airtableURL.$baseID.'/'.$param['module'].'/'.$id.'?returnFieldsByFieldId=true', $options);
                        $statusCode = $response->getStatusCode();
                        $contentType = $response->getHeaders()['content-type'][0];
                        $content2 = $response->getContent();
                        $content2 = $response->toArray();
                        // Add a dimension to fit with the rest of the method
                        $content['records'][] = $content2;
                    } else {
						// There is a bug on the parameter returnFieldsByFieldId soit can't be used
						// In case we use fieldsId, we need to set the label instead of the id of the field
						foreach ($param['query'] as $key => $value) {
							if ('fld' == substr($key, 0, 3)) {
								if (!empty($moduleFields[$baseID][$param['module']][$key]['label'])) {
									unset($param['query'][$key]);
									$param['query'][$moduleFields[$baseID][$param['module']][$key]['label']] = $value;
									continue;
								}
							}
						}
						$filterByFormula = 'filterByFormula=';
                        // Filter by specific field (for example to avoid duplicate records)
                        foreach ($param['query'] as $key => $queryParam) {
							// If there are several filter we manage the AND operator
							if(count($param['query']) > 1) {
								if ($filterByFormula == 'filterByFormula=') {
									$filterByFormula .= 'AND(';
								} else {
									$filterByFormula .= ',';
								}
							}
                            // Transform "___" into space (Myddleware can't have space in the field name)
                            $key = str_replace('___', ' ', $key);
							$filterByFormula .= '{'.$key.'}="'.$queryParam.'"';
                        }
						// If there are several filter we manage the AND operator
						if(count($param['query']) > 1) {
							$filterByFormula = rtrim($filterByFormula, ',');
							$filterByFormula .= ')';
						}
						// Get all records corresponding to the filters
						$response = $client->request('GET', $this->airtableURL.$baseID.'/'.$param['module'].'?returnFieldsByFieldId=true&'.$filterByFormula, $options);
						$statusCode = $response->getStatusCode();
						$contentType = $response->getHeaders()['content-type'][0];
						$content = $response->getContent();
						$content = $response->toArray();
                    }
                } else {
                    // all records
                    $dateRef = $this->dateTimeFromMyddleware($param['date_ref']);
                    $response = $client->request('GET', $this->airtableURL.$baseID.'/'.$param['module']."?sort[0][field]=Last Modified&filterByFormula=IS_AFTER({Last Modified},'$dateRef')&returnFieldsByFieldId=true&pageSize=".$this->defaultLimit.'&maxRecords='.$param['limit'].$offset, $options);
                    $statusCode = $response->getStatusCode();
                    $contentType = $response->getHeaders()['content-type'][0];
                    $content = $response->getContent();
                    $content = $response->toArray();
                }

                // Get the offset id
                $offset = (!empty($content['offset']) ? '&offset='.$content['offset'] : '');
                if (!empty($content['records'])) {
                    $currentCount = 0;
                    //used for complex fields that contain arrays
                    $content = $this->convertResponse($param, $content['records']);
                    foreach ($content as $record) {
                        ++$currentCount;
						foreach ($param['fields'] as $field) {
							if (!empty($record['fields'][$field])) {
								// If the value is an array (relation), we take the first entry
								if (is_array($record['fields'][$field])) {
                                    // if the array has a length of 1, we take the first entry
                                    if (count($record['fields'][$field]) === 1) {
                                        $result['values'][$record['id']][$field] = $record['fields'][$field][0];
                                    } else {
                                        // if the array has a larger length than 1 we convert the array into a string separated by comma and space
                                        $result['values'][$record['id']][$field] = implode(',', $record['fields'][$field]);
                                    }
									
								} else {
									$result['values'][$record['id']][$field] = $record['fields'][$field];
								}
							} else {
                                $result['values'][$record['id']][$field] = '';
                            }
						}

                        // Get the reference date
                        if (
								!empty($dateRefField)
							AND	!empty($record['fields'][$dateRefField])
						) {
                            $dateModified = $record['fields'][$dateRefField];
                        // createdTime not allowed for reading action, only to get an history or a duplicate field
                        } elseif (
                                !empty($record['createdTime'])
                            and !empty($param['query'])
                        ) {
                            $dateModified = $record['createdTime'];
                        } else {
                            throw new Exception('No reference found. Please enable <Last Modified> field in your table '.$param['module'].'. ');
                        }
                        $result['values'][$record['id']]['date_modified'] = $this->dateTimeToMyddleware($dateModified);
                        $result['values'][$record['id']]['id'] = $record['id'];
                        ++$result['count'];
                        // Set the last date ref into the result date ref
                        $result['date_ref'] = $result['values'][$record['id']]['date_modified'];
                        // Stop the read action if we reached the limit
                        if ($result['count'] >= $param['limit']) {
                            break;
                        }
                    }
                } else {
                    $stop = true;
                }

                ++$page;
            } while (
                    !$stop
                and $currentCount === $this->defaultLimit
                and $result['count'] < $param['limit'] // count < rule limit
                and !empty($offset) // Only if there is more data to be read
            );
        } catch (Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());
        }
        return $result;
    }

    /**
     * Create data into target app.
     */
    public function createData($param): array
    {
        return $this->upsert('create', $param);
    }

    /**
     * Update existing data into target app.
     */
    public function updateData($param): array
    {
        return $this->upsert('update', $param);
    }

    // Delete a record
    public function deleteData($param): array
    {
        return $this->upsert('delete', $param);
    }

    /**
     * Insert or update data depending on method's value.
     *
     * @param string $method create|update
     * @param array $param
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \Doctrine\DBAL\Exception
     */
    public function upsert(string $method, array $param): array
    {
        // Init parameters
        $baseID = $this->paramConnexion['projectid'];
        $result = [];
        $param['method'] = $method;
        $module = ucfirst($param['module']);

        /**
         * In order to load relationships, we MUST first load all fields.
         */
        $allFields = $this->get_module_fields($param['module'], 'source');
        // $relationships = $this->get_module_fields_relate($param['module'], 'source');

        // Group records for each calls
        // Split the data into several array using the limite size
        $recordsArray = array_chunk($param['data'], $this->callPostLimit, true);
        foreach ($recordsArray as $records) {
            // Airtable expects data to come in a 'records' array
            $body = [];
            $body['typecast'] = true;
            $body['records'] = [];
            $urlParamDelete = '';
            $i = 0;
            try {
                foreach ($records as $idDoc => $data) {
                    if ('create' === $method) {
                        // trigger to add custom code if needed
                        $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                    } elseif ('update' === $method) {
                        $data = $this->checkDataBeforeUpdate($param, $data, $idDoc);
                    }
                    // Recard are stored in the URL for a deletionj
                    elseif ('delete' === $method) {
                        $data = $this->checkDataBeforeDelete($param, $data);
                        $urlParamDelete .= (!empty($urlParamDelete) ? '&' : '').'records[]='.$data['target_id'];
                        ++$i;
                        continue;
                    }
					// No action if null is returned
					if ($data === null) {
						unset($records[$idDoc]);
						continue;
					}
                    $body['records'][$i]['fields'] = $data;

                    /*
                     * Add dimensional array for relationships fields as Airtable expects arrays of IDs
                     */
                    foreach ($body['records'][$i]['fields'] as $fieldName => $fieldVal) {
                        // Target id isn't an array but the id of the record in Airtable (exist on update and delete)
                        if ('target_id' == $fieldName) {
                            continue;
                        }

                        if (
                                true == $allFields[$fieldName]['relate']
                            and 'text' != $allFields[$fieldName]['type']
                        ) {
                            $arrayVal = [];
                            $arrayVal[] = $fieldVal;
                            $body['records'][$i]['fields'][$fieldName] = $arrayVal;
                        }
                    }

                    // Add the record id in the body if update
                    if ('update' == $method) {
                        $body['records'][$i]['id'] = $data['target_id'];
                        if (isset($body['records'][$i]['fields']['target_id'])) {
                            unset($body['records'][$i]['fields']['target_id']);
                        }
                    }
                    ++$i;
                }

                // Airtable fiueld can contains space which is not compatible in Myddleware.
                // Because we replace space by ___ in Myddleware, we change ___ to space before sending data to Airtable
                if (!empty($body['records'])) {
                    foreach ($body['records'] as $keyRecord => $record) {
                        if (!empty($record['fields'])) {
                            foreach ($record['fields'] as $key => $value) {
                                if (false !== strpos($key, '___')) {
                                    $keyWithSpace = str_replace('___', ' ', $key);
                                    $body['records'][$keyRecord]['fields'][$keyWithSpace] = $value;
                                    unset($body['records'][$keyRecord]['fields'][$key]);
                                }
                            }
                        }
                    }
                }

                // Send records to Airtable
                $client = HttpClient::create();
                $options = [
                    'auth_bearer' => $this->token,
                    'json' => $body,
                    'headers' => ['Content-Type' => 'application/json'],
                ];
                // POST, DELETE or PATCH depending on the method
                if ('delete' === $method) {
                    // Parameters are directly in the URL for a deletion
                    $response = $client->request('DELETE', $this->airtableURL.$baseID.'/'.$module.'?'.$urlParamDelete, $options);
                } elseif ('update' === $method) {
                    $response = $client->request('PATCH', $this->airtableURL.$baseID.'/'.$module, $options);
                } else { // Create
                    $response = $client->request('POST', $this->airtableURL.$baseID.'/'.$module, $options);
                }
                $statusCode = $response->getStatusCode();
                // If error 422, we return directly the error
				if ($statusCode === 422) {
					throw new Exception('422 error : '.$response->getContent(false));
				}
                $content = $response->getContent();
                $content = $response->toArray();
                if (!empty($content)) {
                    $i = 0;
                    foreach ($records as $idDoc => $data) {
                        $record = $content['records'][$i];
                        if (!empty($record['id'])) {
                            $result[$idDoc] = [
                                                    'id' => $record['id'],
                                                    'error' => false,
                                            ];
                        } else {
                            $result[$idDoc] = [
                                'id' => '-1',
                                'error' => 'Failed to send data. Message from Airtable : '.print_r($content['records'][$i], true),
                            ];
                        }
                        ++$i;
                        // Modification du statut du flux
                        $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
                    }
                } else {
                    throw new Exception('Failed to send the record but no error returned by Airtable. ');
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
                foreach ($records as $idDoc => $data) {
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => $error,
                    ];
                    $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
                }
                $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());
            }
        }

        return $result;
    }

    protected function convertResponse($param, $response)
    {
        return $response;
    }

    /**
     * Build the direct link to a record in Airtable (used in document detail view)
     *
     * URL format: https://airtable.com/{projectId}/{tableId}/{recordId}?blocks=hide
     *
     * @param mixed $rule The rule object containing connector information
     * @param mixed $document The document object containing record IDs
     * @param string $type Either 'source' or 'target'
     * @return string The complete direct link URL to the Airtable record
     */
    public function getDirectLink($rule, $document, $type): string
    {
        // Determine which connector to use based on the type (source or target)
        $connector = $this->getConnectorBasedOnType($rule, $type);

        // Extract the Airtable project ID from connector parameters
        $airtableProjectId = $this->getAirtableProjectId($connector);

        // Extract the module name and record ID based on the type
        $moduleNameAndRecordId = $this->extractModuleAndRecordId($rule, $document, $type);
        $moduleName = $moduleNameAndRecordId['module'];
        $recordId = $moduleNameAndRecordId['record_id'];

        // Load metadata to get table mappings
        $metadataMappings = $this->loadAirtableMetadataMappings();

        // if metadatamapping is an empty array or null, returns an empty string
        if (empty($metadataMappings)) {
            return '';
        }
        $tableMapping = $metadataMappings['table_mapping'];

        // Get the table ID for this project and module
        $airtableTableId = $this->getAirtableTableId($tableMapping, $airtableProjectId, $moduleName);

        // if airtableTableId is empty, returns an empty string
        if (empty($airtableTableId)) {
            return '';
        }

        // Build the direct link URL to the Airtable record
        $directLink = $this->buildAirtableRecordUrl(
            $airtableProjectId,
            $airtableTableId,
            $recordId
        );

        return $directLink;
    }

    /**
     * Get the appropriate connector (source or target) based on the type
     *
     * @param mixed $rule The rule object containing connector information
     * @param string $type Either 'source' or 'target'
     * @return mixed The connector object
     */
    private function getConnectorBasedOnType($rule, string $type)
    {
        if ($type === 'source') {
            $connector = $rule->getConnectorSource();
        } else {
            $connector = $rule->getConnectorTarget();
        }

        return $connector;
    }

    /**
     * Extract the Airtable project ID from connector parameters
     *
     * @param mixed $connector The connector object
     * @return string The Airtable project ID (e.g., 'appA10A10A10A10A1')
     */
    private function getAirtableProjectId($connector): string
    {
        // Get the project ID from connector parameters
        $projectId = $this->getConnectorParam($connector, 'projectid');

        return $projectId;
    }

    /**
     * Extract module name and record ID from the rule and document based on type
     *
     * @param mixed $rule The rule object
     * @param mixed $document The document object
     * @param string $type Either 'source' or 'target'
     * @return array Array with 'module' and 'record_id' keys
     */
    private function extractModuleAndRecordId($rule, $document, string $type): array
    {
        if ($type === 'source') {
            $moduleName = $rule->getModuleSource();
            $recordId = $document->getSource();
        } else {
            $moduleName = $rule->getModuleTarget();
            $recordId = $document->getTarget();
        }

        return [
            'module' => $moduleName,
            'record_id' => $recordId
        ];
    }

    /**
     * Load Airtable metadata mappings (table mappings) from metadata files
     *
     * The metadata files should define:
     * - $tableMapping[$projectId][$moduleName] = 'tblT8T8T8T8T8T8T8'
     *
     * @return array Array with 'table_mapping' keys
     */
    private function loadAirtableMetadataMappings(): array
    {
        // Initialize empty mappings
        $tableMapping = [];

        // Include the main metadata file which loads the custom metadata files
        include 'lib/airtable/metadata.php';

        // The metadata.php file will have included the custom metadata files
        // which should define $tableMapping if they exist

        return [
            'table_mapping' => $tableMapping,
        ];
    }

    /**
     * Get the Airtable table ID for a specific project and module
     *
     * @param array $tableMapping The table mapping array from metadata
     * @param string $projectId The Airtable project ID
     * @param string $moduleName The module name
     * @return string The table ID or empty string if not found
     */
    private function getAirtableTableId(array $tableMapping, string $projectId, string $moduleName): string
    {
        // Check if the mapping exists for this project and module
        if (isset($tableMapping[$projectId][$moduleName])) {
            $tableId = $tableMapping[$projectId][$moduleName];
        } else {
            // If table mapping is not found, return empty string
            // This will result in a base Airtable URL being returned
            $tableId = '';
        }

        return $tableId;
    }

    /**
     * Build the complete Airtable URL for a specific record
     *
     * URL format: https://airtable.com/{projectId}/{tableId}/{recordId}?blocks=hide
     * If tableId is empty, returns the base Airtable URL for the project
     *
     * @param string $projectId The Airtable project ID (e.g., 'appP9P9P9P9P9P9P9')
     * @param string $tableId The Airtable table ID (e.g., 'tblT8T8T8T8T8T8T8')
     * @param string $recordId The Airtable record ID (e.g., 'recR7R7R7R7R7R7R7')
     * @return string The complete direct link URL
     */
    private function buildAirtableRecordUrl(
        string $projectId,
        string $tableId,
        string $recordId
    ): string {
        // Start with the base Airtable URL
        $baseUrl = 'https://airtable.com';

        // If we don't have a table ID, we can't build a complete URL
        // Return base URL with project ID only
        if (empty($tableId)) {
            $directLinkUrl = $baseUrl . '/' . $projectId;
            return $directLinkUrl;
        }

        // Build the URL components as an array for cleaner construction
        $urlComponents = [
            $baseUrl,
            $projectId,
            $tableId
        ];

        // Add record ID
        $urlComponents[] = $recordId;

        // Join all components with slashes
        $directLinkUrl = implode('/', $urlComponents);

        // Add the query parameter to hide blocks
        $directLinkUrl .= '?blocks=hide';

        return $directLinkUrl;
    }

    public function getDateRefName($moduleSource, $ruleMode): string
    {
		// Search the field id
		include 'lib/airtable/metadata.php';
		$found_key = array_search('Last Modified', array_column($moduleFields[$this->paramConnexion['projectid']][$moduleSource], 'label'), true);
		// Error if not found
		if ($found_key === false) {
			throw new Exception('Failed to found the date reference field name in the metadata');
		}
		
		// Get the field id 
		$fieldId = key(array_slice($moduleFields[$this->paramConnexion['projectid']][$moduleSource], $found_key, $found_key));
		if (empty($fieldId)) {
			throw new Exception('Failed to found the id corresponding to the date reference field.');
		}

        return $fieldId;
    }

    /**
     * Convert date to Myddleware format
     * 2020-07-08T12:33:06 to 2020-07-08 12:33:06
     * @throws Exception
     */
    protected function dateTimeToMyddleware($dateTime)
    {
        $dto = new DateTime($dateTime);

        return $dto->format('Y-m-d H:i:s');  //TODO: FIND THE EXACT FORMAT : 2015-08-29T07:00:00.000Z
    }

    /**
     * convert from Myddleware format to Airtable format
     * @throws Exception
     */
    protected function dateTimeFromMyddleware($dateTime)
    {
        $dto = new DateTime($dateTime);

        return $dto->format('Y-m-d\TH:i:s');
    }
}
