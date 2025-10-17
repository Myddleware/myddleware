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

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class iomad extends moodle
{
	protected $iomadModules = array('get_companies', 'get_company_courses');
	protected $currentUserId;
	protected $currentUserCompanyId;
		
	protected function getSiteInfo($xml){
		parent::getSiteInfo($xml);
		// Get the userID
		if (!empty($xml->SINGLE->KEY)) {
			foreach ($xml->SINGLE->KEY as $keyElement) {
				// Get the userId
				if ((string)$keyElement['name'] === 'userid') {
					$this->currentUserId = (string)$keyElement->VALUE;
					break; // Stop the loop if we found the userId
				}
			}
		}
	}
	
    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'url',
                'type' => TextType::class,
                'label' => 'solution.fields.url',
            ],
            [
                'name' => 'token',
                'type' => PasswordType::class,
                'label' => 'solution.fields.token',
            ],
			[
                'name' => 'user_custom_fields',
                'type' => TextType::class,
                'label' => 'solution.fields.user_custom_fields',
            ],
			[
                'name' => 'course_custom_fields',
                'type' => TextType::class,
                'label' => 'solution.fields.course_custom_fields',
            ],
        ];
    }
	
	public function get_modules($type = 'source'): array
    {
		// Moodle modules
		$modules = parent::get_modules($type);
		// Add Iomad modules
		if ($type == 'source') {
			$modules['get_companies'] = 'Get companies';
			$modules['get_company_courses'] = 'Get relationship company courses';
		}
		return $modules;
	}
	
	// Read data from Iomad
	public function read($param): array
    {
		// Call Moodle read function
		$records = parent::read($param);
		// Redefine read action for Iomad modules
		if (!empty($records)) {
			$functionName = $this->getFunctionName($param);
			// No date modified returned by block_iomad_company_admin_get_companies, we set id by default
			if (in_array($functionName, ['block_iomad_company_admin_get_companies'])) {
				foreach($records as $key => $record) {
					$records[$key]['date_modified'] = $record['id'];
				}
			}
			if (in_array($functionName, ['block_iomad_company_admin_get_company_courses'])) {
				foreach($records as $key => $record) {
					$records[$key]['date_modified'] = str_replace('_','000',$record['id']);
				}
			}
		}
		return $records;
	}
	
	// public function createData($param): array 
	public function createData($param): array 
	{
		// Call Moodle function
		$result = parent::createData($param);
		if ($param['module'] != 'users') {
			return $result;
		}
		if (empty($result)) {
			return $result;
		}
		// Enroll the new user in the company of the user linked to the token
		// Get the company of the user linked to the token
		if (empty($this->currentUserCompanyId)) {
			$parameters['userid'] = $this->currentUserId;
			$functionName = 'block_iomad_company_admin_get_user_companies';
			$serverurl = $this->paramConnexion['url'].'/webservice/rest/server.php'.'?wstoken='.$this->paramConnexion['token'].'&wsfunction='.$functionName;
			$response = $this->moodleClient->post($serverurl, $parameters);
			$xml = $this->formatResponse('read', $response, $param);
			 
			if (!empty($xml->SINGLE->KEY->MULTIPLE->SINGLE->KEY)) {
				foreach ($xml->SINGLE->KEY->MULTIPLE->SINGLE->KEY as $keyElement) {
					// Get the userId
					if ((string)$keyElement['name'] === 'id') {
						$this->currentUserCompanyId = (string)$keyElement->VALUE;
						break; // Stop the loop if we found the userId
					}
				}
			}
		}
		$parameters = [];
		// Enrol the user to the company
		foreach($result as $idDoc => $record) {		
			$parameters['users'][0]['userid'] = current($record['id']);
			$parameters['users'][0]['companyid'] = $this->currentUserCompanyId;		
			$functionName = 'block_iomad_company_admin_assign_users';
			$serverurl = $this->paramConnexion['url'].'/webservice/rest/server.php'.'?wstoken='.$this->paramConnexion['token'].'&wsfunction='.$functionName;
			$response = $this->moodleClient->post($serverurl, $parameters);
			$xml = $this->formatResponse('read', $response, $param);
			// Manage error
			if (!empty($xml->ERRORCODE)) {
				$result[$idDoc]['error'] = $xml->ERRORCODE.' : '.$xml->MESSAGE;
				// Change status
				$this->updateDocumentStatus($idDoc, $result[$idDoc], $param, 'Error_sending');
			}
		}	
		return $result;
	}
	
	// Set metadata
	protected function setMetadata(){
		require 'lib/iomad/metadata.php';
		return $moduleFields;
	}
	
	// Get the function name
    protected function getFunctionName($param): string
    {
		if (in_array($param['module'], $this->iomadModules)) {
			return 'block_iomad_company_admin_'.$param['module'];
		}
		return parent::getFunctionName($param);
	}
	
	protected function setParameters($param): array
    {
		$functionName = $this->getFunctionName($param);
        // Search with empty criteria for company, we will retrieve all companies
        if (in_array($functionName, ['block_iomad_company_admin_get_companies'])) {
			$filters[] = ['key' => '', 'value' => ''];
			return ['criteria' => $filters];
		}
		if (in_array($functionName, ['block_iomad_company_admin_get_company_courses'])) {
			// We set 0 to get all companies
			$filters[] = ['companyid' => '0'];
			return ['criteria' => $filters];
		}
        return parent::setParameters($param);
    }
	
    // Format webservice result if needed
    protected function formatResponse($method, $response, $param)
    {
        $xml = simplexml_load_string($response);
        $functionName = $this->getFunctionName($param);
        if ('read' == $method) {
            if (in_array($functionName, ['block_iomad_company_admin_get_companies', 'block_iomad_company_admin_get_company_courses'])) {
                return $xml->SINGLE->KEY[0];
            }
        }
        return parent::formatResponse($method, $response, $param);
    }
	
	protected function formatRecord($param, $data){
		$functionName = $this->getFunctionName($param);
		// We can generate several documents for one company depending on the number of courses linked to the company
		if (in_array($functionName, ['block_iomad_company_admin_get_company_courses'])) {
			$companyCourses = $this->xmlToArray($data);
			if (!empty($companyCourses['courses'])) {
				foreach($companyCourses['courses'] as $course) {
					$row[]= array(
						'id' => $companyCourses['id'].'_'.$course['id'],
						'company_id' => $companyCourses['id'],
						'company_name' => $companyCourses['name'],
						'course_id' => $course['id'],
						'course_name' => $course['fullname']
					);
				}
			}
			return $row; 
		}
		return parent::formatRecord($param, $data);
	}
	
    public function getRefFieldName($param): string
    {
		$functionName = $this->getFunctionName($param);
        switch ($functionName) {
			// No date modified returned by the webservice get_companies, we set the id
            case 'block_iomad_company_admin_get_companies':
                return 'id';
                break;
			// No date modified returned by the webservice get_company_courses, we set the id
            case 'block_iomad_company_admin_get_company_courses':
                return 'id';
                break;
            default:
                return parent::getRefFieldName($param);
                break;
        }
    }
	
	// Recursive function to transform xml to array
	protected function xmlToArray($xml) {
		$result = [];
		if (isset($xml->KEY)) {
			foreach ($xml->KEY as $item) {
				$attributes = $item->attributes();
				$name = (string) $attributes['name'];
				if (isset($item->VALUE)) {
					$result[$name] = (string) $item->VALUE !== '' ? (string) $item->VALUE : null;
				} elseif (isset($item->MULTIPLE)) {
					$result[$name] = [];

					if (isset($item->MULTIPLE->SINGLE)) {
						foreach ($item->MULTIPLE->SINGLE as $single) {
							$result[$name][] = $this->xmlToArray($single);
						}
					}
				}
			}
		}
		return $result;
	}
}