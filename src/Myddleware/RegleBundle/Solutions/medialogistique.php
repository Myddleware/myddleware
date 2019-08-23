<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

use DateTime;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class medialogistiquecore extends solution {
	
	protected $url = 'https://gestion.mvsbusiness.com/rest_ext/';

	public function getFieldsLogin() {
		return array(
			array(
				'name' => 'clientid',
				'type' => TextType::class,
				'label' => 'solution.fields.clientid'
			),
			array(
				'name' => 'authid',
				'type' => PasswordType::class,
				'label' => 'solution.fields.authid'
			),
			array(
				'name' => 'hashkey',
				'type' => PasswordType::class,
				'label' => 'solution.fields.hashkey'
			)
		);
	}

	// Login to Média logistique
	public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {	
			// Call the order list to check the login parameters (OK even if there is no order)
			$timestamp = date('U');
			// Build login parameters
			$parameters = array(
						'id_client' => $this->paramConnexion['clientid'],
						'id_auth' => $this->paramConnexion['authid'],
						'expires' => $timestamp,
						'auth' => hash('sha256',$this->paramConnexion['authid'].'_'.$timestamp.'_gestion_commande')
			);
// print_r($parameters);			
			// $result = $this->call($this->url.'gestion_commande/date/'.date('Y-m-d').'?'.http_build_query($parameters));
			$result = $this->call($this->url.'gestion_commande/date/2019-08-22?'.http_build_query($parameters));
// echo $this->url.'gestion_commande/date/'.date('Y-m-d').'?'.http_build_query($parameters);			
// print_r($result);			
			
			// We have to get a result action equal to Get_commande because this is the action we have called
			if (
					empty($result->action) 
				 OR $result->action <> 'Get_commande'
			) {
				throw new \Exception('Failed to connect to Media Logistique.');
			}

			// Connection validation
			$this->connexion_valide = true;
		} catch (\Exception $e) {
			$error = $e->getMessage();
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)


	public function get_modules($type = 'source') {
        $modules = array(
            'gestion_commande' => 'Commande'
        );
        return $modules;
    } // get_modules()
	
	// Get the fields available for the module in input
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			$this->moduleFields = array();
			$this->fieldsRelate = array();
			
			// Use medialogistique metadata
			require('lib/medialogistique/metadata.php');	
			if (!empty($moduleFields[$module])) {
				$this->moduleFields = $moduleFields[$module];
			} else {
				throw new \Exception('Module '.$module.' unknown. Failed to get the module fields.');
			}
			return $this->moduleFields;
		}
		catch (\Exception $e){
			return false;
		}
	} // get_module_fields($module)	 

	
	/**
	 * Function call
	 * @param $url
	 * @param string $method
	 * @param array $parameters
	 * @param int $timeout
	 * @return mixed|void
	 * @throws \Exception
	 */
	protected function call($url, $method = 'GET', $parameters = array(), $timeout = 300) {
		if (!function_exists('curl_init') OR !function_exists('curl_setopt')) {
			throw new \Exception('curl extension is missing!');
		}
		// $fileTmp = $this->container->getParameter('kernel.cache_dir') . '/myddleware/solutions/erpnext/erpnext.txt';
		// $fs = new Filesystem();
		// try {
			// $fs->mkdir(dirname($fileTmp));
		// } catch (IOException $e) {
			// throw new \Exception ($this->tools->getTranslation(array('messages', 'rule', 'failed_create_directory')));
		// }
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

		// common description bellow
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		// curl_setopt($ch, CURLOPT_COOKIEJAR, $fileTmp);
		// curl_setopt($ch, CURLOPT_COOKIEFILE, $fileTmp);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		$response = curl_exec($ch);
		
		// if Traceback found, we have an error 
		if (
				$method != 'GET'
			AND	strpos($response,'Traceback') !== false
		) {
			// Extraction of the Traceback : Get the lenth between 'Traceback' and '</pre>'
			return substr($response, strpos($response,'Traceback'), strpos(substr($response,strpos($response,'Traceback')),'</pre>'));
		}
		curl_close($ch);
	
		return json_decode($response);
	}
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__ . '/../Custom/Solutions/medialogistique.php';
if (file_exists($file)) {
	require_once($file);
} else {
	//Sinon on met la classe suivante
	class medialogistique extends medialogistiquecore
	{

	}
}