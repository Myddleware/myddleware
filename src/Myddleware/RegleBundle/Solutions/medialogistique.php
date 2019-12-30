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
	
	// Module list that allows to make parent relationships
	protected $allowParentRelationship = array('gestion_commande');
	
	protected $idByModule = array(
							'suivi_commande' => 'ref_client',
							'gestion_article' => 'ref_client'
						);

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
			$timestamp = gmdate('U');
			// Build login parameters
			$parameters = array(
						'id_client' => $this->paramConnexion['clientid'],
						'id_auth' => $this->paramConnexion['authid'],
						'expires' => $timestamp,
						'auth' => hash_hmac('sha256',$this->paramConnexion['authid'].'_'.$timestamp.'_gestion_commande',$this->paramConnexion['hashkey'])
			);
			$result = $this->call($this->url.'gestion_commande/date/'.date('Y-m-d').'?'.http_build_query($parameters));

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
		if ($type == 'source') {
			$modules = array(
				'suivi_commande' => 'Suivi commande',
				'gestion_article' => 'Article'
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
			
			// Use medialogistique metadata
			require('lib/medialogistique/metadata.php');	
			if (!empty($moduleFields[$module])) {
				$this->moduleFields = $moduleFields[$module];
			} else {
				throw new \Exception('Module '.$module.' unknown. Failed to get the module fields.');
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

	/**
	 * Function read data
	 * @param $param
	 * @return mixed
	 */
	public function read($param) {					
		try {			
			// Field id can change depending of the module
			if(!empty($this->idByModule[$param['module']])) { // Si le champ id existe dans le tableau
				$fieldId = $this->idByModule[$param['module']];
			}
			
			// Add required fields
			$param['fields'] = $this->addRequiredField($param['fields']);
			// Remove Myddleware 's system fields
			$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
		 	// Get the reference date field name
			// $dateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
			
			$fields = $param['fields'];
			$result['date_ref'] = $param['date_ref'];	
			// Change to format Y-m-d
			$param['date_ref'] = $this->dateTimeFromMyddleware($param['date_ref']);
			$result['count'] = 0;
			$lastCall = false;
			
			// Call the order list with the reference date in parameter
			$timestamp = gmdate('U');
			// Build login parameters
			$moduleCall = ($param['module']=='gestion_article' ? $param['module'] : 'gestion_commande');
			$parameters = array(
						'id_client' => $this->paramConnexion['clientid'],
						'id_auth' => $this->paramConnexion['authid'],
						'expires' => $timestamp,
						'auth' => hash_hmac('sha256',$this->paramConnexion['authid'].'_'.$timestamp.'_'.$moduleCall,$this->paramConnexion['hashkey'])
			);		
			$yesterday = date("Y-m-d", strtotime( '-1 days' ) );
			while (!$lastCall) {
				// If date ref = today or yesterday then we don't add the date in the URL
				if (
						$param['date_ref'] == gmdate('Y-m-d')
					 OR	$param['date_ref'] == $yesterday
					 OR	$moduleCall == 'gestion_article' // We don't get historic for stock
				) {
					$resultQuery = $this->call($this->url.$moduleCall.'?'.http_build_query($parameters));			
					$lastCall = true;
				} else {
					$resultQuery = $this->call($this->url.$moduleCall.'/date/'.$param['date_ref'].'?'.http_build_query($parameters));			
				}			
				
				// If no result
				if (!empty($resultQuery->ok)) {
					foreach ($resultQuery->data as $recordData) {
						// Date modified equal current date_ref
						$record['date_modified'] = $this->dateTimeToMyddleware($param['date_ref']);
						foreach ($fields as $field) {
							// MVS doesn't return value for some fields if they are empty (ex : field reserve in module gestion_article)
							$record[$field] = (!empty($recordData->$field) ? $recordData->$field : '');
						}
						// In case of suivi_commande module, a commande can be read several times, so we concatenate the commande ref and its status (valide)
						if ($param['module'] == 'suivi_commande') {
							$record['id'] = $recordData->ref_client.'_'.$recordData->valide;
						} else {
							$record['id'] = $recordData->$fieldId;
						}
						$result['values'][$record['id']] = $record; // last record
						$result['count']++;
					}
				}
				
				
				// Add 1 day to date_ref if not last call
				if (!$lastCall) {
					$dateRef = DateTime::createFromFormat('Y-m-d', $param['date_ref']);
					$dateRef->modify('+1 day');
					$param['date_ref'] = $dateRef->format('Y-m-d');
					$result['date_ref'] = $this->dateTimeToMyddleware($param['date_ref']);
				}
			}
		} catch (\Exception $e) {
			$result['error'] = 'Error : ' . $e->getMessage().' '.$e->getFile().' '.$e->getLine();
		}		
		return $result;
	}// end function read

	
	// Permet de créer des données
	public function create($param) {			
		$result = array();
		try {
			foreach($param['data'] as $idDoc => $data) {
				try {
					// Order management only for now
					if ($param['module']== 'gestion_commande') {						
						$createOrder = $this->createOrder($param, $idDoc);							
						$result = array_merge($result,$createOrder);
						$this->updateDocumentStatus($idDoc, $createOrder[$idDoc], $param);	
					}
				} catch (\Exception $e) {
					$result[$idDoc] = array(
						'id' => '-1',
						'error' => $e->getMessage()
					);
					$this->updateDocumentStatus($idDoc, $result[$idDoc], $param);	
				}
				
			}
		} catch (\Exception $e) {
			$error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
			$result['error'] = $error;
		}		
		return $result;
	}
	
	protected function createOrder($param, $idDoc) {		
		$csvArray = array();
		$articles = array();
		$result[$idDoc]= array();;
		$subDocIdArray = array();
		$csv = '';
		$order = $param['data'][$idDoc];
		$missingEan = false;
		// Search for article line as we have to generate one line in the csv file for each article
		if (!empty($order['gestion_commande'])){
			foreach($order['gestion_commande'] as $subOrderObjectId => $subOrderObjectValue) {
				// Save the subIdoc to change the sub data transfer status
				$subDocIdArray[$subOrderObjectId] = array('id' => uniqid('', true));
				// Remove Myddleware fields (not relevant), we keep only the ones of the orderObject (not subOrderObject)
				unset($subOrderObjectValue['id_doc_myddleware']);
				unset($subOrderObjectValue['target_id']);
				unset($subOrderObjectValue['source_date_modified']);
				// In case of article object
				if (isset($subOrderObjectValue['article_EAN'])) {
					// Error if article without EAN code
					if (empty($subOrderObjectValue['article_EAN'])) {
						throw new \Exception('Article without EAN code.');
					}
					$articles[$subOrderObjectId] = $subOrderObjectValue;
				} else {
					$csvArray = array_merge($csvArray, $subOrderObjectValue);
				}			
			}
			if (empty($articles)) {
				throw new \Exception('No article on the order.');
			}
			
			// Build the scv array in the right order
			$csv = $this->buildCsv($order, $articles, $csvArray);
			$timestamp = gmdate('U');
			// Build login parameters
			$parameters = array(
						'id_client' => $this->paramConnexion['clientid'],
						'id_auth' => $this->paramConnexion['authid'],
						'expires' => $timestamp,
						'auth' => hash_hmac('sha256',$this->paramConnexion['authid'].'_'.$timestamp.'_gestion_commande',$this->paramConnexion['hashkey'])
			);	
		
			// Call MediaLogistique function
			$resultCall = $this->call($this->url.'gestion_commande?'.http_build_query($parameters), 'POST', array('csv' => $csv));			
			
			// Error management
			if ($resultCall->ok == 1 AND $resultCall->data[0]->msg == 'OK') {
				$result[$idDoc] = array('id' => $resultCall->data[0]->ref_cmd, 'error' => '');
				// If no exception, we update sub data transfer, main data transfer will be updated in the create function
				if (!empty($subDocIdArray)) {				
					foreach($subDocIdArray as $idSubDoc => $valueSubDoc) {				
						$this->updateDocumentStatus($idSubDoc,$valueSubDoc,$param);
					}
				}
			// Global error management
			} elseif(!empty($resultCall->msg)) {
				$result[$idDoc] = array('id' => -1, 'error' => $resultCall->msg);
			// Order error management	
			} elseif(!empty($resultCall->data[0]->msg)) {
				$result[$idDoc] = array('id' => -1, 'error' => $resultCall->data[0]->msg);
			} else {
				$result[$idDoc] = array('id' => -1, 'error' => 'No result from MVS. ');
			}
			
			return $result;				
		}
	}
		
		
	// Build csv
	protected function buildCsv($order, $articles, $csvArray) {
		// Error if no article
		if (empty($articles)) {
			throw new \Exception('No article found in this order. Failed to create the order into Media Logistique.');
		}
		// Build the csv string
		$csv = '';
		foreach ($articles as $article) {
			// Add Carriage Return expect for the first line
			if (!empty($csv)) {
				$csv .= chr(10);
			}
			$csv .=  '"'.$order['code_interne'].'";"'.$order['compte_client'].'";"'.$order['ref_commande'].'";"'.$order['date_commande'].'";"'.$order['date_livraison_demandee'].'";';
			$csv .=  (!empty($order['commentaire']) ? '"'.$order['commentaire'].'"' : '').';'; 								// Not requiered fields
			$csv .=  (!empty($order['origine']) ? '"'.$order['origine'].'"' : '').';'; 										// Not requiered fields
			$csv .=  (!empty($csvArray['transporteur']) ? '"'.$csvArray['transporteur'].'"' : '').';'; 						// Not requiered fields
			$csv .=  (!empty($csvArray['fichier_source']) ? '"'.$csvArray['fichier_source'].'"' : '').';'; 					// Not requiered fields
			$csv .=  (!empty($csvArray['reference_destinataire']) ? $csvArray['reference_destinataire'] : '').';'; 	// Not requiered fields
			$csv .=  '"'.$csvArray['Livr_nom'].'";"'.$csvArray['Livr_adresse1'].'";"'.$csvArray['Livr_adresse2'].'";"'.$csvArray['Livr_cp'].'";';
			$csv .=  '"'.$csvArray['Livr_ville'].'";"'.$csvArray['Livr_pays'].'";"'.$csvArray['Fact_nom'].'";"'.$csvArray['Fact_adresse1'].'";"'.$csvArray['Fact_adresse2'].'";';
			$csv .=  '"'.$csvArray['Fact_cp'].'";"'.$csvArray['Fact_ville'].'";"'.$csvArray['Fact_pays'].'";"'.$csvArray['email'].'";'.$csvArray['telephone'].';'.$article['article_EAN'].';';
			$csv .=  (!empty($article['article_ref_client']) ? '"'.$article['article_ref_client'].'"' : '').';'; 			// Not requiered fields
			$csv .=  $article['quantite'].';'.$article['prix_unit_TTC'].';';
			$csv .=  (!empty($article['remise']) ? $article['remise'] : '').';'; 									// Not requiered fields
			$csv .=  $order['total_frais_port_TTC'].';'.$order['total_commande_TTC'];
		}
		return $csv;
	}
	
	
	public function getRuleMode($module,$type) {
		// only creationallowed for gestion_commande
		if(
				$type == 'target'
			&&	in_array($module, array('gestion_commande'))
		) { 
			return array(
				'C' => 'create_only'
			);
		}
		return parent::getRuleMode($module,$type);
	}
	
	// Convert Myddleware datetime format to MediaLogistique datetime format
	protected function dateTimeFromMyddleware($dateTime) {
		$date = new \DateTime($dateTime);
		return $date->format('Y-m-d');
	}// dateTimeFromMyddleware($dateTime)    
	
	// Convert MediaLogistique datetime format to Myddleware datetime format
	protected function dateTimeToMyddleware($dateTime) {
		$date = new \DateTime($dateTime);
		return $date->format('Y-m-d H:i:s');
	}// dateTimeToMyddleware($dateTime)	
	
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
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

		// common description bellow
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch, CURLOPT_SSLVERSION, 6);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		$response = curl_exec($ch);
		// $info = curl_getinfo($ch);	
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