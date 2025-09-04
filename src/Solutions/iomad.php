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
	protected $iomadModules = array('get_companies');
	
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
		}
		return $modules;
	}
	
	public function read($param): array
    {
		$records = parent::read($param);
		if (!empty($records)) {
			$functionName = $this->getFunctionName($param);
			// No date modified returned by block_iomad_company_admin_get_companies, we set id by default
			if (in_array($functionName, ['block_iomad_company_admin_get_companies'])) {
				foreach($records as $key => $record) {
					$records[$key]['date_modified'] = $record['id'];
				}
			}
		}
		return $records;
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
        // Search with empty criteria for company
        if (in_array($functionName, ['block_iomad_company_admin_get_companies'])) {
			$filters[] = ['key' => '', 'value' => ''];
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
            if (in_array($functionName, ['block_iomad_company_admin_get_companies'])) {
                return $xml->SINGLE->KEY[0];
            }
        }
        return parent::formatResponse($method, $response, $param);
    }
	
    public function getRefFieldName($param): string
    {
		$functionName = $this->getFunctionName($param);
        switch ($functionName) {
			// No date modified returned by the webservice get_companies, we set the id
            case 'block_iomad_company_admin_get_companies':
                return 'id';
                break;
            default:
                return parent::getRefFieldName($param);
                break;
        }
    }
}