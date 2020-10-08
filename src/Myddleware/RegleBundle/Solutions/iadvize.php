<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2020  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

namespace Myddleware\RegleBundle\Solutions;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Iadvize\ApiRestClient\Client;

class iadvizecore extends solution {

	protected $client;

	// Requiered fields for each modules
	protected $required_fields = array(
									'default' => array('id','created_at')
								);
		

	// List of field required to connect to Iadvize
    public function getFieldsLogin(){
        return array(
            array(
                'name' => 'apikey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.apikey'
            )
        );
    }
	
	// Conect to Iadvize
    public function login($paramConnexion) {
        parent::login($paramConnexion);
        try {
            // Create client
			$this->client = new Client();
			$this->client->setAuthenticationKey($this->paramConnexion['apikey']);

			// Get resource
			$websites = $this->client->getResources('website',true);
			if (!empty($websites[0]['id'])) {
				$this->connexion_valide = true;
			} else {
				$message = $this->client->getLastResponse()->getMeta()->getMessage();
				if (!empty($message)) {
					throw new \Exception('Connexion to Advize failed : '.$message);
				} else {
					throw new \Exception('Connexion to Advize failed : Invalide API key');
				}
			}           
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);
            return array('error' => $error);
        }
    } // login($paramConnexion)*/

	// Return the list of available modules
	public function get_modules($type = 'source') {
        if ($type == 'source') {
			$modules = array(
				'visitor' => 'Visitor'
			);
		}
        return $modules;
    } // get_modules()

	
	// Get the fields available for the module in input
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			$this->moduleFields = array();
			$this->fieldsRelate = array();
			
			// Use Iadvize metadata
			require('lib/iadvize/metadata.php');	
			if (!empty($moduleFields[$module])) {
				$this->moduleFields = $moduleFields[$module];
			}
			
			// Field relate
			if (!empty($fieldsRelate[$module])) {
				$this->fieldsRelate = $fieldsRelate[$module]; 
			}	
		
			// Add relate field in the field mapping 
			if (!empty($this->fieldsRelate)) {
				$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
			}
			return $this->moduleFields;
		}
		catch (\Exception $e){
			return false;
		}
	} // get_module_fields($module)	 	
	
	
	// We cant' read visitor using date, so we have to read it until we reach the reference date
	protected function readVisitors($param) {
		$page = 1;
		do {
			$lastRecord = array();
			$result = array();
			// Call Iadvize
			$result = $this->client->getResources($param['module'],true,array(),$param['fields'],$page,10);
			// If empty, whe check the last record of the result
			if(!empty($result)) {
				foreach($result as $record) {
					// if the last records is greater than the reference date, we week all records
					if ($lastRecord['created_at'] > $param['date_ref']) {
						$records[$record['id']] = $record;
					} else {
						$stop = true;
					}
				}
			}
			$page++;
		} while (!$stop);
		
		// We have to returns only the number of records corresponding to the limit 
		// We start by the end of the array (record sorted by created_at ASC)
		$offset = $param['limit'] * (-1);
		return array_slice($records, $offset);
	}
	
	
	protected function readRecords($param) {
		switch ($param['module']) {
			case 'visitor':
				return $this->readVisitors($param);
				break;
			case 1:
				echo "i égal 1";
				break;
			case 2:
				echo "i égal 2";
				break;
		}
	}
	  /**
     * Function read data
     * @param $param
     * @return mixed
     */
    public function read($param) {
        try {			
// print_r($param);
			$result = array();
			$result['count'] = 0;
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);

			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields'],$param['module']);
// print_r($param['fields']);			
// return null;
			// In case we search a specific record with an ID, we call the function getResource
			if (!empty($param['query']['id'])) {
				$records = $this->client->getResource($param['module'],$param['query']['id']);
			// Search by other fields (duplicate fields)
			} elseif (!empty($param['query'])) { // Iadvise used only in source so we don't have to develop this part
				// $records = $this->client->getResources($param['module'],true,$param['query'],$param['fields'],1,10);
			// Search By reference
			} else {
				$records = $this->readRecords($param);
				// $records = $this->client->getResources($param['module'],true,array(),$param['fields'],1,10);
			}
print_r($records);			
return null;		
			
			

        } catch (\Exception $e) {
            $result['error'] = 'Error : ' . $e->getMessage() . ' ' . __CLASS__ . ' Line : ( ' . $e->getLine() . ' )';	
        }				
		return $result;
    }// end function read

}

/* * * * * * * *  * * * * * *  * * * * * *
    Include custom file if exists : used to redefine Myddleware standard code
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__ . '/../Custom/Solutions/iadvize.php';
if (file_exists($file)) {
    require_once($file);
} else {
    // Otherwise, we use the current class (in this file)
    class iadvize extends iadvizecore
    {

    }
}