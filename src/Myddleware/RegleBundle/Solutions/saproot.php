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

use Symfony\Bridge\Monolog\Logger;
//use Psr\LoggerInterface;

class saprootcore extends solution {
	
	protected $limit = 100;
	protected $options = array(	'trace' => 1, // All fault tracing this allows for recording messages sent and received
								'soap_version' => 'SOAP_1_2',
								'authentication' => 'SOAP_AUTHENTICATION_BASIC',
								'exceptions' => 1,
								// 'encoding'=>'UTF-8', 
								'encoding'=>'ISO-8859-1'
							);
	
	protected $keySubStructure = array();
	protected $subStructureFilter = array();
	protected $guidName = array();
	protected $idName = array();
	protected $required_fields = array();
	protected $relateFieldAllowed = array();

	// Connexion à sapcrm
    public function login($paramConnexion) {
		parent::login($paramConnexion);	
		try{
			try{
				// Define SOAP connection options.
				$this->options['login'] = $paramConnexion['login'];
				$this->options['password'] = $paramConnexion['password'];
		
				$this->client = new \SoapClient($paramConnexion['wsdl'], $this->options);	
				$response = $this->client->ZmydTestConnection();
				if($response->EvSuccess == true) {
					$this->connexion_valide = true;
				} else {
					throw new \Exception("Failed to connect SAP CRM.");
				}
			}
			catch(\SoapFault $fault) 
			{
				if(!empty($fault->getMessage())) {
					throw new \Exception($fault->getMessage());
				}
				throw new \Exception("SOAP FAULT. Logon failed.");
			}
		}
		catch (\Exception $e) {
			$error = 'Failed to login to sapcrm : '.$e->getMessage();
			echo $error . ';';
			$this->logger->error($error);
			return array('error' => $error);
		} 
	} // login($paramConnexion)*/
	

	// Liste des paramètres de connexion
	public function getFieldsLogin() {	
        return array(
                    array(
                            'name' => 'login',
                            'type' => 'text',
                            'label' => 'solution.fields.login'
                        ),
                    array(
                            'name' => 'password',
                            'type' => 'password',
                            'label' => 'solution.fields.password'
                        ),
                    array(
                            'name' => 'wsdl',
                            'type' => 'text',
                            'label' => 'solution.fields.wsdl'
                        )
        );
	} // getFieldsLogin()

	
	// Renvoie les champs du module passé en paramètre
	public function get_module_fields($module, $type = 'source') {
		try{
			try{	
				$multistructure = false;
				$structures = $module;	
				$params = array('IvStructures' => $structures);
				// Récupération de toutes les structures dans SAP 
				$response = $this->client->ZmydGetFields($params);

				// Le module est en structure simple
				if (!$multistructure) {
					foreach($response->EtFields->item->ZmydValues->item as $field) {
						if (!empty($this->relateFieldAllowed[$module][$field->ZzmydKey])) {
							$this->fieldsRelate[$module.'__'.$field->ZzmydKey] = array(
												'label' => $this->relateFieldAllowed[$module][$field->ZzmydKey]['label'],
												'type' => 'varchar(255)',
												'type_bdd' => 'varchar(255)',
												'required_relationship' => $this->relateFieldAllowed[$module][$field->ZzmydKey]['required_relationship']
											);
						}
						else {
							$fields[$module.'__'.$field->ZzmydKey] =  array(
												'label' => $field->ZzmydKey,
												'type' => 'varchar(255)',
												'type_bdd' => 'varchar(255)',
												'required' => false,
												'required_relationship' => 0
											);
						}
					}
				}
				// Le module est en multi structure mais peut n'avoir qu'une seule struture selectionnée
				else {
					// Boucle sur tous les modules sélectionnés
					foreach($module[$moduleKey] as $structure => $substructures) {	
						// Recherche des champs pour la struture
						if (!empty($response->EtFields->item)) {
							// Si une seule struture sélectionnée alors $response->EtFields->item n'est pas un tableau mais la structure directement.
							// On rajoute donc la dimension nécessaire à la suite du programme
							if (!is_array($response->EtFields->item)) {					
								$structureTab[] = $response->EtFields->item;
							}
							else {
								$structureTab = $response->EtFields->item;
							}
							foreach($structureTab as $structureField) {						
								if ($structureField->ZmydRecord == $structure) {
									// La sous structure peut être un tableau ou un module directement
									if (!is_array($substructures)) {
										// Si ce n'est pas un array alors on crée la dimension pour rentrer dansle cadre de la mutli sous structure
										$substructures = array($structure => '');
									}								
									// Pour chaque sous structure on génère les champs
									foreach ($substructures as $key => $value) {
										foreach($structureField->ZmydValues->item as $field){
											if (!empty($this->relateFieldAllowed[$moduleKey][$structure][$field->ZzmydKey])) {
												$this->fieldsRelate[$structure.'__'.(!empty($value) ? $key.'__' : '').$field->ZzmydKey] = array(
																	'label' => $this->relateFieldAllowed[$moduleKey][$structure][$field->ZzmydKey]['label'].' - '.$key,
																	'type' => 'varchar(255)',
																	'type_bdd' => 'varchar(255)',
																	'required_relationship' => $this->relateFieldAllowed[$moduleKey][$structure][$field->ZzmydKey]['required_relationship']
																);
											}
											else {
												$fields[$moduleKey.'__'.$structure.(!empty($value) ? '__'.$key : '')][$structure.'__'.(!empty($value) ? $key.'__' : '').$field->ZzmydKey] =  array(
																	'label' => $field->ZzmydKey,
																	'type' => 'varchar(255)',
																	'type_bdd' => 'varchar(255)',
																	'required' => false,
																	'required_relationship' => 0
																);
											}
										}
									}
								}			
							}
						}
					}
				}			
				return $fields;																					
			}
			catch(\SoapFault $fault) 
			{
				if(!empty($fault->getMessage())) {
					throw new \Exception($fault->getMessage());
				}
				throw new \Exception("SOAP FAULT. Logon failed.");
			}
		}
		catch (\Exception $e){
			$error = $e->getMessage();
		} 
	} // get_module_fields($module)



	// Permet de lire des données et de les sauvegarder dans plusieurs structures
	public function readMultiStructure($param,$function,$parameters,$readLast) {		
		try{
			try{	
				// Ajout des champs obligatoires
				$param['fields'] = $this->addRequiredField($param['fields'],$param['module']);
				// Permet de supprimer l'élement Myddleware_element_id ajouter artificiellement dans un tableau de champ
				$param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
				// Tri des champs pour optimiser la performance dans la recherche des données
				arsort($param['fields']);
				// Réupération des données dans SAP	
				$response = $this->client->$function($parameters);		
				// formattage de la réponse
				$response = $this->formatReadResponse($param,$response);
				if ($response->EvTypeMessage == 'E') {
					throw new \Exception('Read record failed : '.$response->EvTypeMessage);
				}
				
				// Récupération de la table principale
				$subModule = $this->get_submodules($param['module'], 'source', array('action' => 'read'));					
				$headerStructure = $this->transformName(array_keys($subModule[$param['module']])[0]);
				
				// Récupération du champ référence GUID
				$guidField = $this->transformName($this->guidName[$param['module']][array_keys($subModule[$param['module']])[0]]);
		
				// Si des enregistrements ont été lues dans SAP
				if ($response->EvCount > 0) {
					$headers = array();
					// Récupération de toutes les entêtes des enregistrements
					if ($response->EvCount == 1) { // Un seul enregistrement trouvé
						if (!empty($response->$headerStructure->item->$guidField)) {
							$headers[] = $response->$headerStructure->item->$guidField;
						}
						else{
							throw new \Exception('One record found but guid not readable');
						}
					}
					else{
						if (!empty($response->$headerStructure->item)) {
							foreach($response->$headerStructure->item as $obj){
								$headers[] = $obj->$guidField;
							}
						}
						else{
							throw new \Exception('Several records found but guid not readable');
						}
					}
					// Boucle sur tous les enregistrements récupérés dans SAP
						
					if (!empty($headers)) {
						foreach($headers as $header){
							$id = '';
							$structuresFound = array();
							$record = array();
							$record['date_modified'] = '';
							$fisrt = true;
						
							// On boucle sur tous le champs demandés						
							foreach($param['fields'] as $field) {								
								$fieldDetails = explode('__',$field);
								// Recherche de la ou des lignes avec le guid de référence dans la table demandée que si on a changé de structure ou si on est sur la première recherche
								if (
										$fisrt
									|| (
											count($fieldDetails) == 2	// Structure simple (ex ET_ORDERADM_H__CREATED_AT)
										&& $fieldDetails[0] != $oldFieldDetails[0]
									)
									|| (
											count($fieldDetails) == 3	// Structure complexe (ex ET_PARTNER__0000022__ADDR_NP)
										&& (
												$fieldDetails[0] != $oldFieldDetails[0]
											  || $fieldDetails[1] != $oldFieldDetails[1]
										)
									)									
								) {
									$structuresFound = array();
									$fisrt = false;
									// Transformation du nom de la structure (exemple ET_ORDERADM_H devient EtOrderadmH)
									$structureFromat = $this->transformName($fieldDetails[0]);
									// Récupère le nom du champ identifiant (Guid, RefGuid...)
									$guidName = $this->transformName($this->getGuidName($param['module'],$fieldDetails[0]));											

									// Et s'il y a une clé pour la table demandé, alors il faut que la valeur soit celle attendue
									if (
											!empty($response->$structureFromat->item->$guidName) // Si seulement 1 seule ligne dans la table
										&& $response->$structureFromat->item->$guidName == $header // Si on est sur la bonne opération
										&& (
												empty($this->keySubStructure[$param['module']][$fieldDetails[0]]) // Si pas de filtrage dans la structure
											 || (
													!empty($this->keySubStructure[$param['module']][$fieldDetails[0]]) // Si filtrage alors on vérifie la valeur
												&& $response->$structureFromat->item->$this->keySubStructure[$param['module']][$fieldDetails[0]] == $this->transformName($fieldDetails[2])
											)	
										)
									) {
										$structuresFound[] = $response->$structureFromat->item;
									}
									else { // Si plusieurs lignes dans la table
										if (!empty($response->$structureFromat->item)) {												
											foreach($response->$structureFromat->item as $item){									
												// Récupération du champ de filtrage principal 
												$filterName = '';
												if (!empty($this->keySubStructure[$param['module']][$fieldDetails[0]])){
													$filterName = $this->transformName($this->keySubStructure[$param['module']][$fieldDetails[0]]);											
												}														
												if (
														$item->$guidName == $header // Si on est sur la bonne opération
													&& (
															empty($filterName) // Si pas de filtrage dans la structure
														 || (
																!empty($filterName) // Si filtrage alors on vérifie la valeur
															&& !empty($item->$filterName)	
															&& $item->$filterName == $fieldDetails[1]
														)	
													)
												) {												
													$structuresFound[] = $item;
												}
											}
										}
									}
								}									
								// Récupération des données à renvoyer si on a trouvé une structure correspondant à la demande
								if (!empty($structuresFound)){
									// Si on a des filtres supplémentaire on les utilise
									if (!empty($subStructureFilter[$param['module']][$fieldDetails[0]])) {
										$structuresFoundFiltered = array();
										foreach($structuresFound as $structure) { // Pour chaque ligne
											$strutureOk = true;
											foreach($subStructureFilter[$param['module']][$fieldDetails[0]] as $filterField => $filterValue){ // On vérifie le filtre
												// Si un filtre est KO alors on ne garde pas la struture
												if ($structure[$filterField] != $filterValue) {
													$strutureOk = false;
													break;
												}
											}
											// Si tous les filtres sont passants alors on garde la struture
											if ($strutureOk) {
												$structuresFoundFiltered[] = $structure;
											}
										}
										$structuresFound = $structuresFoundFiltered;
									}
									// Si toujours plusieurs erreur même après filtrage alors on envoi un warning
									if (count($structuresFound) > 1) {
										$record['ZmydMessage'] = array('type' => 'W', 'message' => 'Several rows found in the table '.$fieldDetails[0].'. Only the first one has been selected. ');
									}									
									// On récupère ensuite la première trouvée même si plusieurs strutures étaient présentes
									if (count($fieldDetails) == 2) { 	// Structure simple (ex ET_ORDERADM_H__CREATED_AT)
										$fieldName = $this->transformName($fieldDetails[1]);
										$record[$field] = $structuresFound[0]->$fieldName;
									}
									else { // Structure complexe (ex ET_PARTNER__0000022__ADDR_NP)
										$fieldName = $this->transformName($fieldDetails[2]);
										$record[$field] = $structuresFound[0]->$fieldName;
									}
									
									// Si on est sur le champ id (suppression des 0 à gauche pourvant être présent sur le PARTNER_NOou l'OBJECT_ID par exemple)
									if (
											!empty($this->idName[$param['module']][$fieldDetails[0]])
										&& $fieldName == $this->transformName($this->idName[$param['module']][$fieldDetails[0]])
									) {
										$record['id'] = ltrim($structuresFound[0]->$fieldName,'0');
									}
									// On ajoute la date de modification, le champ peut être nommé différemment en fonction des module
									elseif ($fieldName == 'ChangedAt') {
										$record['date_modified'] = $structuresFound[0]->$fieldName;
									}
									// Poru partner, l'heure et la date sont dans 2 champs différents
									elseif ($fieldName == 'Chdat') {
										$record['date_modified'] = $structuresFound[0]->$fieldName.$record['date_modified'];
									}
									elseif ($fieldName == 'Chtim') {
										$record['date_modified'] = $record['date_modified'].' '.$structuresFound[0]->$fieldName;
									}
								}
								// Sauvegarde du champ précédent pour éviter de refaire la recherche si on demande la même struture
								$oldFieldDetails = $fieldDetails;

							}						
							if (!empty($record['id'])) {
								// Sauvegarde des données du document
								$result['values'][$record['id']] = $record;
							}
							else {
								$record['ZmydMessage'] = array('type' => 'E', 'message' => 'No ID for the document.');
							}
						}
					}
				}
				$result['count'] = $response->EvCount;
				$result['date_ref'] = $this->dateTimeToMyddleware($response->EvDateRef);
				
				// Si readLast alors on change le format des données de sortie
				if ($readLast) {
					$result = array();
					if (!empty($record)) {
						$result['values'] = $record;
						$result['done'] = true;
					}
					else {
						$result['done'] = -1;
					}
				}					
				return $result;				
			}
			catch(\SoapFault $fault) 
			{
				if(!empty($fault->getMessage())) {
					throw new \Exception($fault->getMessage());
				}
				throw new \Exception("SOAP FAULT. Read record failed.");
			}
		}
		catch (\Exception $e) {
			$error = 'Failed to read record from sapcrm : '.$e->getMessage().' '.__CLASS__.' Line : '.$e->getLine().'. ';;
			echo $error . ';';
			$this->logger->error($error);
			if ($readLast) {
				return array('done' => -1);
			}	
			return array('error' => $error);
		} 
	}
	

	// Transformation du nom de la structure (exemple ET_ORDERADM_H devient EtOrderadmH)
	protected function transformName($name){
		$result = '';
		$nameArray = explode('_',$name);
		foreach($nameArray as $nameCut){
			$result .= strtoupper(substr($nameCut,0,1)).strtolower(substr($nameCut,1));
		}
		return $result;
	}

	// Permet de récupérer le nom de l'id en fonction de la structure et du module
	protected function getGuidName($module, $struture) {
		if (!empty($this->guidName[$module][$struture])) {
			return $this->guidName[$module][$struture];
		}
		elseif (!empty($this->guidName[$module]['default'])) {
			return $this->guidName[$module]['default'];

		}
		return 'REF_GUID';
	}

	
	// Function de conversion de datetime format solution à un datetime format Myddleware
	protected function dateTimeToMyddleware($dateTime) {
		$date = new \DateTime($dateTime);
		return $date->format('Y-m-d H:i:s');
	}// dateTimeToMyddleware($dateTime)	
	
	// Function de conversion de datetime format Myddleware à un datetime format solution
	protected function dateTimeFromMyddleware($dateTime) {
		$date = new \DateTime($dateTime);
		return $date->format('YmdHis');
	}// dateTimeFromMyddleware($dateTime)    
	
	// Lorsque SAP renvoie un résultat, le réponse est différente s'il y a une seule ligne dans le retour ou s'il y en a plusieurs
	// On formate de sorte que même s'il n'y a qu'une seule ligne, la réponse soit convertie en tableau comme s'il y avait plusieurs lignes
	protected function convertResponseTab($response) {
		if (!is_array($response)) {					
			$result[] = $response;
		}
		else {
			$result = $response;
		}	
		return $result;

	}
}// class saprootcore

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/sapcrm/sap.php';
if(file_exists($file)){
	include($file);
}
else {
	//Sinon on met la classe suivante
	class saproot extends saprootcore {
		
	}
}