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

namespace Myddleware\RegleBundle\Solutions;

use Symfony\Bridge\Monolog\Logger;
use Myddleware\RegleBundle\Classes\rule as ruleMyddleware;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType; 

class databasebase extends databasecore
{
    /**
     * @param $type
     * @param $module
     * @return array
     */
	public function getFieldsParamUpd($type, $module)
    {
		try {
			// Get standard parameters
			$params = parent::getFieldsParamUpd($type, $module);
			// Add readAll parameter
			if ($type == 'source') {
				$params[] = array(
                    'id' => 'readAll',
                    'name' => 'readAll',
                    'type' => 'option',
                    'label' => 'Read all at each run',
                    'required'	=> false,
                    'option' =>  array('0' => 'No', '1' => 'Yes')
                );
			}
			
			return $params;
		}
		catch (\Exception $e){
			return array();
		}
	}

    /**
     * Function to buid the SELECT query.
     *
     * @param $param
     * @param $query
     * @return string
     */
	protected function buildQuery($param, $query)
    {
		// If readAll parameter is set to 1, we read all parameters
		if (!empty($param['ruleParams']['readAll'])) {
			$query['where'] = '';
		}

		return parent::buildQuery($param, $query);
	}
}

$file = __DIR__.'/database.client.php';
if (file_exists($file)) {
    require_once $file;
} else {
    class database extends databasebase {}
}
