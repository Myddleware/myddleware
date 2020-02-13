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

namespace Myddleware\RegleBundle\Solutions;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Mautic\MauticApi;
use Mautic\Auth\ApiAuth;  

class mauticcore  extends solution {

	protected $auth;
	
	// Enable to read deletion and to delete data
	protected $sendDeletion = true;	
	
	public function getFieldsLogin() {	
		return array(
					array(
							'name' => 'login',
							'type' => TextType::class,
							'label' => 'solution.fields.login'
						),
					array(
							'name' => 'password',
							'type' => PasswordType::class,
							'label' => 'solution.fields.password'
						),
					array(
							'name' => 'url',
							'type' => TextType::class,
							'label' => 'solution.fields.url'
						)
		);
	}

	
    public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
			// Add login/password
			$settings = array(
				'userName'   => $this->paramConnexion['login'],
				'password'   => $this->paramConnexion['password']
			);

			// Ini api
			$initAuth = new ApiAuth();
			$auth = $initAuth->newAuth($settings, 'BasicAuth');
			$api = new MauticApi();
			
			// Get the current user to check the connection parameters
			$userApi = $api->newApi("users", $auth, $this->paramConnexion['url']);
			$user = $userApi->getSelf();

			// Managed API return. The API call is OK if the user id is found
			if(!empty($user['id'])) {
				$this->auth = $auth;
				$this->connexion_valide = true;	
			} elseif(!empty($user['error']['message'])) {
				throw new \Exception('Failed to login to Mautic. Code '.$user['error']['code'].' : '.$user['error']['message']);
			} else {
				throw new \Exception('Failed to login to Mautic. No error message returned by the API.');
			} 
		} 
		catch (\Exception $e) {
			$error = $e->getMessage();
			$this->logger->error($error);
			return array('error' => $error);
		} 
    }
	
	// Get the modules available 
	public function get_modules($type = 'source') {
		return array('contact' => 'Contacts');
	}
	
	// Get the fields available 
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			// Call Mautic to get the module fields
			$api = new MauticApi();
			$fieldApi = $api->newApi($module."Fields", $this->auth, $this->paramConnexion['url']);
			$fieldlist = $fieldApi->getList();
			// Transform fields to Myddleware format
			if (!empty($fieldlist['fields'])) {
				foreach ($fieldlist['fields'] as $field) {
					$this->moduleFields[$field['alias']] = array(
													'label' => $field['label'],
													'type' => ($field['type'] == 'text' ? TextType::class : 'varchar(255)'),
													'type_bdd' => ($field['type'] == 'text' ? $field['type'] : 'varchar(255)'),
													'required' => (!empty($field['isRequired']) ? true : false),
												);
					// manage dropdown lists
					if (!empty($field['properties']['list'])) {
						$options = explode('|', $field['properties']['list']);
						foreach ($options as $option) {
							$this->moduleFields[$field['alias']]['option'][$option] = $option;
						}
					}
				}
			}	
			return $this->moduleFields;
		} catch (\Exception $e){
			return false;
		}
	} // get_module_fields($module)	 

}

/* * * * * * * *  * * * * * *  * * * * * * 
if a custom file exists we include it
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/mautic.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class mautic extends mauticcore {
		
	}
}
