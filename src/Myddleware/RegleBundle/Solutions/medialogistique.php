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
			$result = $this->call($this->url.'gestion_commande/date/'.date('Y-m-d').'?'.http_build_query($parameters));
			// $result = $this->call($this->url.'gestion_commande/date/2019-08-22?'.http_build_query($parameters));
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

	
	// Permet de créer des données
	public function create($param) {
		$result = array();
		try {
// print_r($param);
			foreach($param['data'] as $idDoc => $data) {
				try {
					// Order management only for now
					if ($param['module']== 'gestion_commande') {
						$csvArray = array();
						$articles = array();
						$subDocIdArray = array();
						$csv = '';
						// Search for article line as we have to generate one line in the csv file for each article
						if (!empty($data['gestion_commande'])){
							foreach($data['gestion_commande'] as $subOrderObjectId => $subOrderObjectValue) {
								// Save the subIdoc to change the sub data transfer status
								$subDocIdArray[$subOrderObjectId] = array('id' => uniqid('', true));
								// Remove Myddleware fields (not relevant), we keep only the ones of the orderObject (not subOrderObject)
								unset($subOrderObjectValue['id_doc_myddleware']);
								unset($subOrderObjectValue['target_id']);
								unset($subOrderObjectValue['source_date_modified']);
								// In case of article object
								if (isset($subOrderObjectValue['article_EAN'])) {
									$articles[$subOrderObjectId] = $subOrderObjectValue;
								} else {
									$csvArray = array_merge($csvArray, $subOrderObjectValue);
								}
								
							}
							// Build the scv array in the right order
							$csv = $this->buildCsv($data, $articles, $csvArray);
echo $csv;							
						}
					}
				} catch (\Exception $e) {
					$result[$idDoc] = array(
						'id' => '-1',
						'error' => $e->getMessage()
					);
				}
				// Transfert status update
				// if (
						// !empty($subDocIdArray)
					// AND empty($result[$idDoc]['error'])
				// ) {				
					// foreach($subDocIdArray as $idSubDoc => $valueSubDoc) {				
						// $this->updateDocumentStatus($idSubDoc,$valueSubDoc,$param);
					// }
				// }
				// $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
			}
		} catch (\Exception $e) {
			$error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
			$result['error'] = $error;
		}	
		return $result;
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
			$csv .=  $order['code_interne'].';'.$order['compte_client'].';'.$order['ref_commande'].';'.$order['date_commande'].';'.$order['date_livraison_demandee'];
			$csv .=  (!empty($order['commentaire']) ? $order['commentaire'] : '').';'; 								// Not requiered fields
			$csv .=  (!empty($order['origine']) ? $order['origine'] : '').';'; 										// Not requiered fields
			$csv .=  (!empty($csvArray['transporteur']) ? $csvArray['transporteur'] : '').';'; 						// Not requiered fields
			$csv .=  (!empty($csvArray['fichier_source']) ? $csvArray['fichier_source'] : '').';'; 					// Not requiered fields
			$csv .=  (!empty($csvArray['reference_destinataire']) ? $csvArray['reference_destinataire'] : '').';'; 	// Not requiered fields
			$csv .=  $csvArray['Livr_nom'].';'.$csvArray['Livr_adresse1'].';'.$csvArray['Livr_adresse2'].';'.$csvArray['Livr_cp'];
			$csv .=  $csvArray['Livr_ville'].';'.$csvArray['Livr_pays'].';'.$csvArray['Fact_nom'].';'.$csvArray['Fact_adresse1'].';'.$csvArray['Fact_adresse2'];
			$csv .=  $csvArray['Fact_cp'].';'.$csvArray['Fact_ville'].';'.$csvArray['Fact_pays'].';'.$csvArray['email'].';'.$csvArray['telephone'].';'.$article['article_EAN'].';';
			$csv .=  (!empty($article['article_ref_client']) ? $article['article_ref_client'] : '').';'; 			// Not requiered fields
			$csv .=  $article['quantite'].';'.$article['prix_unit_TTC'].';';
			$csv .=  (!empty($article['remise']) ? $article['remise'] : '').';'; 									// Not requiered fields
			$csv .=  $order['total_frais_port_TTC'].';'.$order['total_commande_TTC'].chr(10).chr(13);
		}
		return $csv;
	}
	
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