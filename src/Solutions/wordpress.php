<?php

/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class wordpresscore extends solution
{
    protected string $apiSuffix = '/wp-json/wp/v2/';
    protected int $callLimit = 100;   // Wordpress API only allows 100 records per page to be read
    // Module without reference date
    protected array $moduleWithoutReferenceDate = ['users', 'categories'];

    public function getFieldsLogin(): array
    {
        return [
                    [
                        'name' => 'url',
                        'type' => TextType::class,
                        'label' => 'solution.fields.url',
                    ],
                ];
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            $client = HttpClient::create();
            //we test the connection to the API with a request on pages
            $response = $client->request('GET', $this->paramConnexion['url'].$this->apiSuffix.'pages');
            $statusCode = $response->getStatusCode();
            $contentType = $response->getHeaders()['content-type'][0];
            $content = $response->getContent();
            $content = $response->toArray();
            if (!empty($content) && 200 === $statusCode) {
                $this->connexion_valide = true;
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    public function get_modules($type = 'source'): array
    {
        return [
            'posts' => 'Posts',
            // 'categories' =>	'Categories',
            'pages' => 'Pages',
            'comments' => 'Comments',
            // 'tags' =>	'Tags',
            // 'taxonomies' =>	'Taxonomies',
            // 'media' =>	'Media',
            // 'types'=>	'Post Types',
            // 'statuses'=> 'Post Statuses',
            // 'settings' =>	'Settings',
            // 'themes' =>	'Themes',
            // 'search' =>	'Search',
            // 'block-types'=>	'Block types',
            // 'blocks' =>	'Blocks',
            // 'block-renderer' =>	'Block renderer',
            // 'plugins' =>'Plugins'
            ];
    }

    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            require_once 'lib/wordpress/metadata.php';
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
            $error = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function readData($param): array
    {
        try {
            $result = [];
            $module = $param['module'];
            $result['count'] = 0;
            $result['date_ref'] = $param['date_ref'];
            // Change the date format only for module with a date as a reference
            if (!in_array($module, $this->moduleWithoutReferenceDate)) {
                $dateRefWPFormat = $this->dateTimeFromMyddleware($param['date_ref']);
            }

            //for submodules, we first send the parent module in the request before working on the submodule with convertResponse()
            if (!empty($this->subModules[$param['module']])) {
                $module = $this->subModules[$param['module']]['parent_module'];
            }

            // Remove Myddleware's system fields
            $param['fields'] = $this->cleanMyddlewareElementId($param['fields']);

            // Add required fields
            $param['fields'] = $this->addRequiredField($param['fields'], $param['module'], $param['ruleParams']['mode']);

            if (empty($param['limit'])) {
                $param['limit'] = $this->callLimit;
            } else {
                // to handle situations in which limit set by user is higher than actual WP API limit (100)
                if ($param['limit'] < $this->callLimit) {
                    $this->callLimit = $param['limit'];
                }
            }
            $stop = false;
            $count = 0;
            $page = 1;
            do {
                $content = [];
                $client = HttpClient::create();
                // In case a specific record is requested
                if (!empty($param['query']['id'])) {
                    $response = $client->request('GET', $this->paramConnexion['url'].'/wp-json/wp/v2/'.$module.'/'.$param['query']['id']);
                    $statusCode = $response->getStatusCode();
                    $contentType = $response->getHeaders()['content-type'][0];
                    $content2 = $response->getContent();
                    $content2 = $response->toArray();
                    // Add a dimension to fit with the rest of the method
                    $content[] = $content2;
                } else {
                    try {
                        $response = $client->request('GET', $this->paramConnexion['url'].'/wp-json/wp/v2/'.$module.'?per_page='.$this->callLimit.'&page='.$page.'&orderby=modified');
                        $statusCode = $response->getStatusCode();
                        $contentType = $response->getHeaders()['content-type'][0];
                        $content = $response->getContent();
                        $content = $response->toArray();
                    } catch (\Exception $e) {
                        if (!($e instanceof ClientException)) {
                            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                        } else {
                        }
                    }
                }
                if (!empty($content)) {
                    $currentCount = 0;
                    //used for complex fields that contain arrays
                    $content = $this->convertResponse($param, $content);

                    foreach ($content as $record) {
                        ++$currentCount;
                        // If the reference is a date we check the date_modified field otherwise we check the id which is an integer
                        if (
                            (
                                in_array($module, $this->moduleWithoutReferenceDate)
                                and $record['id'] > $param['date_ref']
                            )
                            or
                            (
                                !in_array($module, $this->moduleWithoutReferenceDate)
                                and $record['modified'] > $dateRefWPFormat
                            )
                        ) {
                            foreach ($param['fields'] as $field) {
                                $result['values'][$record['id']][$field] = (!empty($record[$field]) ? $record[$field] : '');
                            }
                            if (in_array($module, $this->moduleWithoutReferenceDate)) {
                                // the data sent without an API key is different than the one in documentation
                                // need to find a way to generate WP Rest API key / token
                                $result['values'][$record['id']]['date_modified'] = date('Y-m-d H:i:s');
                            } else {
                                $result['values'][$record['id']]['date_modified'] = $this->dateTimeToMyddleware($record['modified']);
                            }
                            $result['values'][$record['id']]['id'] = $record['id'];

                            // No reference date for this module so we store the ids in the reference field
                            if (in_array($module, $this->moduleWithoutReferenceDate)) {
                                if ($record['id'] > $result['date_ref']) {
                                    $result['date_ref'] = $record['id'];
                                }
                            } elseif ($result['values'][$record['id']]['date_modified'] > $result['date_ref']) {
                                if ($result['values'][$record['id']]['date_modified'] > $result['date_ref']) {
                                    $result['date_ref'] = $result['values'][$record['id']]['date_modified'];
                                }
                            }

                            ++$result['count'];
                            ++$count;
                        }
                    }
                } else {
                    $stop = true;
                }
                ++$page;
            } while (!$stop && $count < $param['limit']);
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }

        return $result;
    }

    //for specific fields (e.g. : event_informations from Woocommerce Event Manager plugin)
    protected function convertResponse($param, $response): array
    {
        $newResponse = [];
        if (!empty($response)) {
            foreach ($response as $key => $record) {
                foreach ($record as $fieldName => $fieldValue) {
                    if (is_array($fieldValue)) {
                        foreach ($fieldValue as $subFieldKey => $subFieldValue) {
                            $newSubFieldName = $fieldName.'__'.$subFieldKey;
                            if (is_array($subFieldValue)) {
                                if (array_key_exists(0, $subFieldValue)) {
                                    if ('mep_event_more_date' != $param['module']) {
                                        $newResponse[$key][$newSubFieldName] = $subFieldValue[0];
                                    } elseif ('event_informations__mep_event_more_date' === $newSubFieldName) {
                                        $json = $subFieldValue[0];
                                        $json = unserialize($json);
                                        $moreDatesArray = $json;
                                        foreach ($moreDatesArray as $subSubRecordKey => $subSubRecord) {
                                            // we need to manually add the event id here
                                            $eventID = $record['id'];
                                            $moreDatesArray[$subSubRecordKey]['event_id'] = $eventID;
                                            $moreDatesArray[$subSubRecordKey]['id'] = $eventID.'_'.$subSubRecordKey;
                                            $moreDatesArray[$subSubRecordKey]['modified'] = $record['modified'];
                                        }
                                        $newResponse = array_merge($newResponse, $moreDatesArray);
                                    }
                                }
                            } elseif ('mep_event_more_date' != $param['module']) {
                                $newResponse[$key][$newSubFieldName] = $subFieldValue;
                            }
                        }
                    } elseif ('mep_event_more_date' != $param['module']) {
                        $newResponse[$key][$fieldName] = $fieldValue;
                    }
                }
            }

            return $newResponse;
        }

        return $response;
    }

    // Convert date to Myddleware format
    // 2020-07-08T12:33:06 to 2020-07-08 10:33:06
    /**
     * @throws \Exception
     */
    protected function dateTimeToMyddleware($dateTime)
    {
        $dto = new \DateTime($dateTime);
        // We save the UTC date in Myddleware
        $dto->setTimezone(new \DateTimeZone('UTC'));

        return $dto->format('Y-m-d H:i:s');
    }

    //convert from Myddleware format to Woocommerce format

    /**
     * @throws \Exception
     */
    protected function dateTimeFromMyddleware($dateTime)
    {
        $dto = new \DateTime($dateTime);
        // Return date to UTC timezone
        return $dto->format('Y-m-d\TH:i:s');
    }

    // Permet d'indiquer le type de référence, si c'est une date (true) ou un texte libre (false)
    public function referenceIsDate($module): bool
    {
        // Le module users n'a pas de date de référence. On utilise donc l'ID comme référence
        if (in_array($module, $this->moduleWithoutReferenceDate)) {
            return false;
        }

        return true;
    }
}

class wordpress extends wordpresscore
{
}
