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

use Myddleware\RegleBundle\Classes\rule as ruleMyddleware;
use Symfony\Component\Form\Extension\Core\Type\TextType; // SugarCRM Myddleware

class bittlecore extends solution { 
	Protected $baseUrl;
	Protected $messages = array();
	Protected $duplicateDoc = array();

	public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
			$this->baseUrl = $this->paramConnexion['url'].'/api/';
			$this->url = $this->baseUrl.'company/'.$this->paramConnexion['company'].'/user/'.$this->paramConnexion['user']."/connector/";
			$restponse = $this->call( 'GET',''); // Call vers l'url: $this->url qui correspond au Retrieve All Connector de l'API Bittle

			if(!isset($restponse['result'])) throw new \Exception ("Failed to connect bittle, call error."); // Si on a pas d'index "result" dans la réponse de Bittle on renvoie une erreur
			
			// Faire un apple vers Bittle, par exemple le récupération de tous les connectors : URL: company/ {idCompany}/user/connector/
			if($restponse['result'] == "Success") { // Si Bittle nous renvoie Success alors connexion valide
				$this->connexion_valide = true;
			}
			else { // Sinon on lève une erreur
				throw new \Exception("Failed to connect bittle, check your infos");
			} 
		}
		catch (\Exception $e) {
			$error = $e->getMessage();
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)
 	
	
	public function getFieldsLogin() {	
		return array(
					 array(
                            'name' => 'token',
                            'type' => 'password',
                            'label' => 'solution.fields.token'
                        ),
					array(
                            'name' => 'company',
                            'type' => 'password',
                            'label' => 'solution.fields.company'
                        ),
					array(
                            'name' => 'user',
                            'type' => 'password',
                            'label' => 'solution.fields.user'
                        ),
					array(
                            'name' => 'url',
                            'type' => TextType::class,
                            'label' => 'solution.fields.url'
                        )
		);
	}
	
	// Renvoie les modules passés en paramètre
	public function get_modules($type = 'source') {
		try{
			// ajout du module de base
			$modules = array('Container' => 'Bittle container (new)');
							
			// Récupération de toutes les règles avec l'id connector en cours qui sont root et qui ont au moins une référence
			$sql = "SELECT DISTINCT
						Rule.id,
						Rule.name,
						Rule.name_slug
					FROM Rule
						INNER JOIN RuleField
							ON Rule.id = RuleField.rule_id
						INNER JOIN RuleParam
							ON Rule.id = RuleParam.rule_id
					WHERE
							Rule.deleted = 0
						AND Rule.conn_id_target = :idConnector
						AND RuleField.target_field_name LIKE '%_Reference'
						AND RuleParam.value = 'root'
						AND RuleParam.name = 'group'";
			$stmt = $this->conn->prepare($sql);
			$stmt->bindValue(":idConnector", $this->paramConnexion['idConnector']);
			$stmt->execute();
			$rules = $stmt->fetchAll();
			if (!empty($rules)) {
				foreach ($rules as $rule) {
					$modules[$rule['name']] = $rule['name'];
				}
			}
			
			return $modules;			
		} catch (\Exception $e) {
			$error = $e->getMessage();
			return $error;			
		}
	} 
	
	// Renvoie les champs du module passé en paramètre
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			$this->moduleFields = array();
			// Ajout du champs date
			$this->moduleFields['Date'] = array('label' => 'Date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => ($module == 'Container' ? 1 : 0));
			
			// Il n'est pas nécessaire d'ajouter des champs obligatoire sur une règle child (le connecteur a déjà ses champs obligatoires avec la règle root)
			if ($module == 'Container') {
				// Ajout du champs metric
				$this->moduleFields['Metric'] = array('label' => 'Metric', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1);	
			}
			return $this->moduleFields;
		}
		catch (\Exception $e){
			return false;
		}
	} // get_module_fields($module) 
	
	
	// Redéfinition de la méthode pour ne plus renvoyer la relation Myddleware_element_id
	public function get_module_fields_relate($module,$param) {
		// Récupération de tous les champ référence de la règle liées (= module)	
		$this->fieldsRelate = array();
		$sql = "SELECT 	
					RuleField.target_field_name,
					Rule.name
				FROM Rule
					INNER JOIN RuleField
						ON Rule.id = RuleField.rule_id
					WHERE
							Rule.name = :name
						AND Rule.deleted = 0	
						AND RuleField.target_field_name LIKE '%_Reference'";
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(":name", $module);
		$stmt->execute();
		$ruleFields = $stmt->fetchAll();
		if (!empty($ruleFields)) {
			foreach ($ruleFields as $ruleField) {
				$this->fieldsRelate[$ruleField['target_field_name']] = array(
																'label' => $ruleField['target_field_name'].' ('.$ruleField['name'].')',
																'type' => 'varchar(255)',
																'type_bdd' => 'varchar(255)',
																'required' => 0,
																'required_relationship' => 0
															);
			}
		}
		
		return $this->fieldsRelate;
	}
	
	// Permet de créer des données
	public function create($param) {
		try {
			// Si on a pas de connector Bittle alors on renvoie une erreur
			if (empty($param['ruleParams']['connectorID'])) {
				throw new \Exception("No connector in Bittle for the Rule. ");
			}
			$idConnector = $param['ruleParams']['connectorID'];
			$this->url = $this->baseUrl.'company/'.$this->paramConnexion['company'].'/user/'.$this->paramConnexion['user']."/connector/".$idConnector;
			$connector = $this->call("GET", '');// Donc ici envoi de la row
			
			// Boucle sur chaque document en entrée
			foreach($param['data'] as $idDoc => $data) {
				try {
					// Check control before create
					$data = $this->checkDataBeforeCreate($param, $data);
					// Construction du XML
					$xml= "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><UploadModelXML>"; 
					$rowsDescription = "<RowsDescription";
					$rows = "<Rows><Row";
					$c = 1;
					$first = true;
					// Boucle sur chaque champ du document
					foreach ($data as $key => $value) {				
						// Saut de la première ligne qui contient l'id du document
						if ($first) {
							$first = false;
							// Récupération du source_id. Bittle ne renvoie pas d'ID donc nous récupérons l'ID de la source
							$sourceId = $this->getSourceId($idDoc);
						
							// Ajout systématique de l'ID dans les données en première colonne
							$rowsDescription	.= " c".$c."=\"rowId\""; // Ajout du nom du champ dans <RowsDescription/>
							$rows				.= " c".$c."=\"".$sourceId."\""; // Ajout de la valeur dans la définition de la balise <Row/>
							$c++;
							continue;
						}
						
						if($key == "target_id") {
							continue;
						}
						
						// Récupération du type du champ courant
						$mappingType = $this->getMappingType($key);
						
						if($mappingType == 'DATE') {
							$rowDate = $this->dateTimeFromMyddleware($value);						
							if(isset($rowDate['error'])) {						
								throw new \Exception ("Error Date: " . $rowDate['error']);
							}
						}
						$rowsDescription	.= " c".$c."=\"".$param['rule']['module_source'].'_'.$key."\""; // Ajout du nom du champ dans <RowsDescription/>
						$rows				.= " c".$c."=\"".($mappingType == 'DATE' ? $this->dateTimeFromMyddleware($value) : $value)."\""; // Ajout de la valeur dans la définition de la balise <Row/>
						$c++; // Incrémentation du compteur
					}
					
					// On ajoute des lignes vides pour tous les champs qui n'appartiennent pas au module root et qui seront remplis par les module child
					// Boucle sur tous les champs du connector Bittle
					if (!empty($connector['data']['ConnectorModel']['mappings']['mapping'])) {
						foreach($connector['data']['ConnectorModel']['mappings']['mapping'] as $filed) {
							// On ne prend pas en compte rowId
							if ($filed['fileField'] == 'rowId') {
								continue;
							}
							// Si les x premiers caractères (avec x le nombre de caractère du module source) du champs sont différents au module source
							// Alors on crée une entrée vide pour le champ
							if (substr($filed['fileField'],0,strlen($param['rule']['module_source'])) != $param['rule']['module_source']) {
								$rowsDescription	.= " c".$c."=\"".$filed['fileField']."\""; // Ajout du nom du champ dans <RowsDescription/>
								$rows				.= " c".$c."=\"".($mappingType == 'METRIC' ? 0 : "")."\""; // Ajout de la valeur dans la définition de la balise <Row/>
								$c++; // Incrémentation du compteur
							}
						}
					}
					
					// Fermeture des balises
					$rowsDescription	.= "/>"; // On ferme <RowsDescription/>
					$rows				.= "/></Rows>"; // On ferme <Row/> et </Rows>
					
					// Construction du XML complet
					$xml .= $rowsDescription . $rows . "</UploadModelXML>";

					// Correction des données (pas de caractère &)
					$xml = str_replace('&', '', $xml);
					
					// Construction de l'URL	
					$this->url = $this->baseUrl."/upload/data/".$idConnector."/";
					// Envoi des données à Bittle
					$response = $this->call("PUT", $xml);// Donc ici envoi de la row
				
					// Gestion des résultat
					if ($response['result'] == 'Success') {
						// Pour chaque donnée envoyée, on génère le ou les documents fils permettant de renseigner les données qui ne corespondent pas au module en cours
						$generateChildDocument = $this->generateChildDocument($param,$data);
						if ($generateChildDocument!==true) {
							throw new \Exception("Failed to create child document : ".$generateChildDocument.". This document est locked. ");		
						}
						$result[$idDoc] = array(
												'id' => $sourceId,
												'error' => false
										);
					}
					else  {
						throw new \Exception("Failed to send data to Bittle : ".$response['message']);				
					}
				}
				catch (\Exception $e) {
					$error = $e->getMessage();
					$result[$idDoc] = array(
							'id' => '-1',
							'error' => $error
					);
				}
				// Modification du statut du flux
				$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
			}
		}
		catch (\Exception $e) {
			$error = $e->getMessage();
			$result[$idDoc] = array(
					'id' => '-1',
					'error' => $error
			);
		}
		return $result;
	}

	// Permet de créer des données
	public function update($param) {
		try {
			// Si on a pas de connector Bittle alors on renvoie une erreur
			if (empty($param['ruleParams']['connectorID'])) {
				throw new \Exception("No connector in Bittle for the Rule. ");
			}
			$idConnector = $param['ruleParams']['connectorID'];
			// Construction de l'URL	
			$this->url = $this->baseUrl.'company/'.$this->paramConnexion['company'].'/user/'.$this->paramConnexion['user']."/connector/".$idConnector."/";
			// Récupération des données du connector
			$response = $this->call("GET");
			if($response['result'] != "Success") {
				throw new \Exception ($response['message']);
			}
			
			// Récupération le mapping du connector
			$mapping = $response['data']['ConnectorModel']['mappings']['mapping'];
			
			// Construction du tableau fieldsBittle qui contient pour chaque nom de champ source, l'ID mapping et d'autres informations
			// Ex: $fieldsBittle['rowId'] = array (
      		//				'idMapping' => 'bfXtDRQepTJtNNe42I3Zww',
      		//				'fileField' => 'rowId',
      		//				'displayName' => 'Row ID',
      		//				'mappingType' => 'FILTER');
			$fieldsBittle = array();
			foreach ($mapping as $f) {
				$fieldsBittle[$f['fileField']] = $f;
			}
		
			// Boucle sur chaque document en entrée
			foreach($param['data'] as $idDoc => $data) {	
				try {
					// Check control before update
					$data = $this->checkDataBeforeUpdate($param, $data);
					$first = true;
					$conditionChild = '';
				
					// Boucle sur chaque champ du document
					foreach ($data as $key => $value) {
						// Saut de la première ligne qui contient l'id du document
						if ($first) {
							$first = false;
							// Récupération du source_id. Bittle ne renvoie pas d'ID donc nous récupérons l'ID de la source
							$sourceId = $this->getSourceId($idDoc);
							
							// On gère la condition en fonction du type de règle (child ou non)
							if (	
									!empty($param['ruleParams']['group'])
								&& $param['ruleParams']['group'] == 'child'
							) {						
								// Si on est sur une règle child alors on récupère le champ qui fait la jointure pour générer la condition
								// Premièrement on récupère le module source de la règle liée afin de reconstruire le nom du champ Bittle
								$sql = "SELECT module_source FROM Rule WHERE id = :ruleId";
								$stmt = $this->conn->prepare($sql);
								// Il n'y a qu'une seule relation possible pour les règle groupé comme Bittle
								$stmt->bindValue(":ruleId", $param['ruleRelationships'][0]['field_id']);
								$stmt->execute();								
								$fetch = $stmt->fetch();
								if(!empty($fetch['module_source'])) {											
									$source = $fetch['module_source'];
								}								
								if (!empty($fieldsBittle[$source.'_'.$param['ruleRelationships'][0]['field_name_target']]['idMapping'])) {						
									$condition = "<condition idmapping=\"". $fieldsBittle[$source.'_'.$param['ruleRelationships'][0]['field_name_target']]['idMapping'] ."\"><operator>EQUAL</operator><values><value>". $sourceId ."</value></values></condition>";
									$conditionChild = $param['ruleRelationships'][0]['field_name_target'];						
								}
								else {						
									throw new \Exception("Failed to find a Bittle mapping for the field ".$source.'_'.$param['ruleRelationships'][0]['field_name_target'].". ");
								}										
							}
							else {
								$condition = "<condition idmapping=\"". $fieldsBittle['rowId']['idMapping'] ."\"><operator>EQUAL</operator><values><value>". $sourceId ."</value></values></condition>";;							
							}
							continue;
						}
						
						// Ce champ est ignoré
						if($key == "target_id") {
							continue;
						}
						
						// Si le champ en cours correspond à la condition, on ne fait pas la mise à jour car le champ est la condition
						if(
								!empty($conditionChild)
							&& $key == $conditionChild
						) {
							continue;
						}
						
						// On remplace le nom du champ key par le champ réel concaténé avec le nom du module
						$key = $param['rule']['module_source'].'_'.$key;
						
						if(!isset($fieldsBittle[$key])) {
							throw new \Exception ('Fields '.$key.' doesn\'t match with connector\'s mapping');
						}
						
						// Construction de l'URL	
						$this->url = $this->baseUrl."upload/condition/".$idConnector."/".$fieldsBittle[$key]['idMapping'];
						
						// Récupération du type du champ courant
						$mappingType = $this->getMappingType($key);

						if($mappingType == "DATE") {
							continue; // Les dates ne peuvent apparemment pas être mises à jour
						}
						// Si une métric est vide alors on envoie 0 pour ne pas planter l'interface
						if(
								$mappingType == "METRIC"
							&& $value == ""	
						) {
							$value = 0;
						}
						
						// Construction du XML
						$xml	 = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><updatecolumn><sequence>"; 
						$xml	.= "<outputvalue><![CDATA[".$value."]]></outputvalue>"; // Valeur à envoyer
						$xml	.= $condition;											// Condition de la ligne (rowId = SourceId);
						$xml	.= "</sequence></updatecolumn>";						// Fermeture du XML
					 	
						// Envoi des données à Bittle
						$response = $this->call("POST", $xml);		
						// Gestion des résultat
						if ($response['result'] != 'Success') {
							throw new \Exception("Failed to send data to Bittle : ".$response['message']);				
						}
					}
					
					$result[$idDoc] = array(
											'id' => $sourceId,
											'error' => false
									);
				}
				catch (\Exception $e) {
					$error = $e->getMessage();
					$result[$idDoc] = array(
							'id' => '-1',
							'error' => $error
					);
				}
				// Modification du statut du flux
				$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
			}
		}
		catch (\Exception $e) {
			$error = $e->getMessage();
			$result[$idDoc] = array(
					'id' => '-1',
					'error' => $error
			);
		}
		return $result;
	}
		
	// Permet de renvoyer l'id de connector Bittle en récupérant le connector lié à la règle ou en le créant s'il n'existe pas
	protected function checkConnector($param) {
		// On entre dans le IF si on n'est pas sur la 1ère version de la règle
		// Ou si on est sur une règle child
		if(
				$param['rule']['version'] != "001"
			|| (
					!empty($param['content']['params']['group'])
				&& $param['content']['params']['group'] == 'child'
			)
		) { 
			// Ici on va aller chercher le idconnector des versions précédentes			
			// Cette requette permet de récupérer toutes les règles portant le même nom que la notre ET AYANT un connectorID
			// Les résultats sont triés de la version la plus récente à la plus vieille
			$sql = "SELECT R1.`value` , R2.`version` 
					FROM  `RuleParam` R1,  `Rule` R2
					WHERE  `name` =  'connectorID'
					AND R1.`rule_id` = R2.`id` 
					AND R1.`rule_id` IN (	SELECT  `id` 
											FROM  `Rule` 
											WHERE  `name` =  :name)
					ORDER BY R2.`version` DESC";
			$stmt = $this->conn->prepare($sql);
			$stmt->bindValue(":name", $param["rule"]["name"]);
			$stmt->execute();
			
			// On récupère d'abord le premier résultat afin de vérifier que le connectorID n'est pas vide
			$fetch = $stmt->fetch();
			if(!empty($fetch['value'])) {
				$connectorId = $fetch['value'];
			}
			
			// Si toutefois il était vide, on prend tous les résultats afin d'en récupérer un non-vide (tjrs dans l'ordre du plus récent au plus vieux)
			$fetchAll = $stmt->fetchAll();
			foreach ($fetchAll as $result) {
				if(!empty($result['value'])) {
					$connectorId = $result['value'];
					break;
				}
			}

			// Dernier test, si on a tjrs rien dans $connectorID et que l'on est pas sur une règle child (jamais de création de connecteur pour une règle child)
			// alors on crée un nouveau connector
			if(
					empty($connectorId)
				&&	(
						$param['content']['params']['group'] != 'child'
					|| empty($param['content']['params']['group'])
				)
			) {
				return $this->createBittleConnector($param);
			}
			// Récupération du connecteur dans la règle root
			elseif (
					empty($connectorId)
				&&	(
						!empty($param['content']['params']['group'])
					&&	$param['content']['params']['group'] == 'child'
				)
			) {
				$sql = "SELECT 
							RuleParam.value
						FROM RuleRelationShip
							INNER JOIN RuleParam
								ON RuleRelationShip.field_id = RuleParam.rule_id
						WHERE 
								RuleRelationShip.rule_id = :ruleId
							AND RuleParam.name = 'connectorID'";
				$stmt = $this->conn->prepare($sql);
				$stmt->bindValue(":ruleId", $param["ruleId"]);
				$stmt->execute();
				
				// On récupère d'abord le premier résultat afin de vérifier que le connectorID n'est pas vide
				$fetch = $stmt->fetch();
				if(!empty($fetch['value'])) {
					$connectorId = $fetch['value'];
				}
			}
			// Si on a pas de connector à ce stade alors on renvoie une erreur car on a besoin de l'ID pour faie la modification de ce connector
			if(empty($connectorId)) {
				$this->messages[] = array('type' => 'error', 'message' => 'Failed to find a connector in Bittle for this Rule. The connector is not updated in Bittle.');
			}
			/*
			 * 		MAJ du connecteur avec le nouveau mapping
			 */
			
			// Récupération du mapping des champs du connector actuel
			$this->url = $this->baseUrl.'company/'.$this->paramConnexion['company'].'/user/'.$this->paramConnexion['user']."/connector/".$connectorId;
			$response = $this->call( 'GET');			
			$mapping = $response['data']['ConnectorModel']['mappings']['mapping'];
			$ConnectorName = $response['data']['ConnectorModel']['connectorName'];			
			/*
			 * 		COMPARAISON DES CHAMPS
			 */
			$bittleFields = array();
			foreach ($mapping as $bittleField) {
				if($bittleField['displayName'] == "Row ID") continue;
				$bittleFields[] = $bittleField['fileField']; // On stocke tous les noms de champs Bittle
			}
			
			$diff = array();
			foreach ($param['ruleFields'] as $ruleField) {
				$mappingType = $this->getMappingType($ruleField['target_field_name']);
				
				if (empty($mappingType)) {
					throw new \Exception("Mapping Type unknown for the field ".$ruleField['target_field_name'].". Failed to create the connector in Bittle");
				}
				// Récupération du nom d'affichage du champ : nom du champ complet sans le type en fin de nom
				$fieldName = substr($ruleField['target_field_name'], 0, strrpos($ruleField['target_field_name'], '_'));
				// Si le nom du champ Bittle que l'on veut envoyer existe déjà dans le connector actuel alors on ne l'envoie pas.
				if (!in_array($param['rule']['module_source'].'_'.$ruleField['target_field_name'], $bittleFields)) {
					$diff[] = array("displayName" => $fieldName, "TYPE" => $mappingType, "fileField" => $param['rule']['module_source'].'_'.$ruleField['target_field_name']);
				}
			}
			if(empty($diff)) {
				$this->messages[] = array('type' => 'success', 'message' => 'No added field on your rule. The connector has not been changed in Bittle. ');
				return $this->saveConnectorParams($param['ruleId'], $connectorId);
			}
			
			/*
			 * 		$DIFF EST DE LA FORME:
			 *		array(1) {
			 *		  [0]=>
			 *		  array(3) {
			 *		    ["displayName"]=>
			 *		    string(12) "account_name"
			 *		    ["TYPE"]=>
			 *		    string(6) "FILTER"
			 *		    ["fileField"]=>
			 *		    string(8) "01Filter"
			 *		  }
			 *		}
			 */
			 
			// URL de MAJ du connector dans Bittle
			$this->url = $this->baseUrl.'company/'.$this->paramConnexion['company'].'/user/'.$this->paramConnexion['user']."/connector/".$connectorId;
			
			// Création du XML
			$xml= "<ConnectorModel>
						<connectorName>".$ConnectorName."</connectorName>
						<mappings>";
			
			$fieldstext = '';
			foreach ($diff as $fieldDiff) {
				$fieldstext .= $fieldDiff['displayName'].' ';  
				$xml.=		"<mapping>
								<fileField>".$fieldDiff['fileField']."</fileField>
								<displayName>".$fieldDiff['displayName']."</displayName>
								<mappingType>".$fieldDiff['TYPE']."</mappingType>
								".($fieldDiff['TYPE'] == 'DATE' ? "<pattern>dd/MM/yyyy hh:mm</pattern>" : "")."
								<action>CREATE</action>
							</mapping>";
			}

			$xml.=		"</mappings>
			   </ConnectorModel>";
		 
			// Envoi de la requête dans Bittle	   
			$response = $this->call('PUT',$xml);		
			if ($response['result'] == 'Success') {
				$this->messages[] = array('type' => 'success', 'message' => 'Connector '.$ConnectorName.' successfully updated in Bittle. Fields added : '.$fieldstext.' .');		
				
				if (!empty($response['data']['ConnectorModel']['idConnector'])) {
					// Mise à jour du connecteur dans la base de données 
					$sqlFields = "INSERT INTO `RuleParam` (`rule_id`,`name`,`value`) VALUES (:ruleId, 'connectorID', :connectorId)";
					$stmt = $this->conn->prepare($sqlFields);
					$stmt->bindValue(":ruleId", $param['ruleId']);
					$stmt->bindValue(":connectorId", $response['data']['ConnectorModel']['idConnector']);
					$stmt->execute();	   				
					return $response['data']['ConnectorModel']['idConnector'];
				}
				else {
					throw new \Exception("Failed to update the connector, no IDconnector sent by Bittle");
				}
			}
			else {
				throw new \Exception("Failed to update the connector : ".$response['Message']);
			}
		}
		else {
			return $this->createBittleConnector($param);
		} 
		return null;
	}
	
	// Créer un connector dans Bittle
	protected function createBittleConnector($param) {
		// URL de création du connector dans Bittle
		 $this->url = $this->baseUrl.'company/'.$this->paramConnexion['company'].'/user/'.$this->paramConnexion['user']."/connector/";
		
		// Création du XML
		$xml= "<ConnectorModel>
					<connectorName>".$param['rule']['name_slug']."</connectorName>
					<mappings>";
		// Ajout systématique de l'ID de la ligne correspondant à l'ID de la source de données
		$xml.=			"<mapping>
							<fileField>rowId</fileField>
							<displayName>Row ID</displayName>
							<mappingType>FILTER</mappingType>
						</mapping>";
						
		if (empty($param['ruleFields'])) {
			throw new \Exception("Failed to create the connector, no field in the Rule ".$param['rule']['name_slug']);
		}
		// Création du mapping dans Bittle
		Foreach ($param['ruleFields'] as $ruleField) {
			$mappingType = $this->getMappingType($ruleField['target_field_name']);
			
			if (empty($mappingType)) {
				throw new \Exception("Mapping Type unknown for the field ".$ruleField['target_field_name'].". Failed to create the connector in Bittle");
			}
			
			// Pour les champs date et metric (fixés car obligatoire), on garde le nom de champ source sinon on met le champ saisi par l'utilisateur pour affichage dans Bittle
			$fieldName = explode('_',$ruleField['target_field_name']);
			$fieldName = $fieldName[0];
			$xml.=		"<mapping>
							<fileField>".$param['rule']['module_source'].'_'.$ruleField['target_field_name']."</fileField>
							<displayName>".(in_array($ruleField['target_field_name'],array('Metric','Date')) ? $ruleField['source_field_name'] : $fieldName)."</displayName>
							<mappingType>".$mappingType."</mappingType>
							".($mappingType == 'DATE' ? "<pattern>dd/MM/yyyy hh:mm</pattern>" : "")."
						</mapping>";
		}
		$xml.=		"</mappings>
			   </ConnectorModel>";

		// Envoi de la requête dans Bittle	   
		$response = $this->call('POST',$xml);

		if ($response['result'] == 'Success') {
			$this->messages[] = array('type' => 'success', 'message' => 'Connector '.$param['rule']['name'].' successfully created in Bittle. ');	
			if (!empty($response['data']['ConnectorModel']['idConnector'])) {		
				return $this->saveConnectorParams($param['ruleId'], $response['data']['ConnectorModel']['idConnector']);
			}
			else {
				throw new \Exception("Failed to create the connector, no IDconnector sent by Bittle");
			}
		}
		else {
			throw new \Exception("Failed to create the connector : ".$response['Message']);
		}
	}
	
	protected function saveConnectorParams($ruleId, $idConnector) {
		// Mise à jour du connecteur dans la base de données 
		$sqlFields = "INSERT INTO `RuleParam` (`rule_id`,`name`,`value`) VALUES (:ruleId, 'connectorID', :connectorId)";
		$stmt = $this->conn->prepare($sqlFields);
		$stmt->bindValue(":ruleId", $ruleId);
		$stmt->bindValue(":connectorId", $idConnector);
		$stmt->execute();	   				
		return $idConnector;
	}
	
	// Permet d indiquer si on envoie les champs standard ou si on renvoie en plus les champs de relation dans get_module_field
	public function extendField ($moduleTarget) {
		return true;
	}
	
	// Permet de générer un document d'une règle child
	protected function generateChildDocument($param,$data) {
		// Si on est en create c'est que l'on est forcément sur une règle root (les child ne fond que de l'update)
		// Si des règles child pointe sur la règle en cours il faut générer des documents sur les autres règles 
		// afin que toutes les données de la ligne en cours soient rensignées
		// Récupération de toutes les règles liées
		$sql = "SELECT 
					RuleRelationShip.rule_id,
					RuleRelationShip.field_name_target
				FROM RuleRelationShip
					INNER JOIN Rule
						ON RuleRelationShip.rule_id = Rule.id
				WHERE 
						RuleRelationShip.field_id = :ruleId
					AND Rule.deleted = 0	
				";
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(":ruleId", $param["ruleId"]);
		$stmt->execute();
		$relationships = $stmt->fetchAll();
		if (!empty($relationships)) {
			// Pour chaque relationship, création d'un document
			foreach ($relationships as $relationship) {
				$param['ruleId'] = $relationship['rule_id'];
				// Récupération de l'ID correspondant à l'enregistrement de la règle liée dans le système source
				// Si l'id de l'enregistrement lié est renseigné alors on génère le docuement sinon on ne le genère pas (il n'est pas obligatoirement renseigné)				
				if (!empty($data[$relationship['field_name_target']])) {
					$rule = new ruleMyddleware($this->logger, $this->container, $this->conn ,$param);
					// Si un document sur la même règle avec le même id source a déjà été fait dans ce paquet d'envoi alors on ne régénère pas un autre document qui serait doublon
					if (empty($this->duplicateDoc[$param['ruleId']][$data[$relationship['field_name_target']]])) {
						$generateDocuments = $rule->generateDocuments($data[$relationship['field_name_target']]);	
						// Si on a eu une erreur alors on arrête de générer les documents child
						if (!empty($generateDocuments->error)) {
							return $generateDocuments->error;
						}
						$this->duplicateDoc[$param['ruleId']][$data[$relationship['field_name_target']]] = 1;
					}
				}
			}
		}
		return true;
	}
	
	//function to make cURL request	
	protected function call($method, $parameters = array()){		
	 	ob_start();
		
		$curl_request = curl_init($this->url);

		curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, $method); // On construit une requête de type $method (GET ou POST)
		curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0); // Ne vérifie pas le certificat SSL
		curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($curl_request, CURLOPT_HEADER, false); // !important, permet d'enlever le header http de la réponse
	
		$headers = array(             
					"Content-type: application/xml",
					"charset=\"utf-8\"", 
					"token:".$this->paramConnexion['token']
				); 
				
				
		curl_setopt($curl_request, CURLOPT_HTTPHEADER, $headers);
		if (!empty($parameters)) {
			curl_setopt($curl_request, CURLOPT_POSTFIELDS, $parameters); 
		}
		$result = curl_exec($curl_request); // Exécute le cURL
		curl_close($curl_request);	
		
		$xml = new \SimpleXMLElement($result); // Transforme la réponse en élément XML
		
		$result = (json_decode(json_encode((array)$xml), true)); // Encode en json (avec une convertion en array) puis le décode afin d'obtenir un array correctement traitable
		if(empty($result))	throw new \Exception ("Call returned an empty response."); // Traitement d'erreur si on a une réponse vide
		
		ob_end_flush();
		return $result;	// Renvoie le résultat de call()
    }
	
	// Function de conversion de datetime format Myddleware à un datetime format solution
	protected function dateTimeFromMyddleware($dateTime) {
		try {
			if (empty($dateTime)) {			
				throw new \Exception("Date empty. Failed to send data. ");
			}
			if(date_create_from_format('Y-m-d H:i:s', $dateTime)) {
				$date = date_create_from_format('Y-m-d H:i:s', $dateTime);
			} else {
				$date = date_create_from_format('Y-m-d', $dateTime);
				if($date) {
					$date->setTime( 0 , 0 , 0 );
				} else {
					throw new \Exception("Wrong format for your date. Please check your date format. Contact us for help.");
				}
				
			}
			return $date->format('d/m/Y H:i');
		} catch (\Exception $e) {
			$result['error'] = $e->getMessage();
			return $result;
		}
	}// dateTimeFromMyddleware($dateTime)   
	
	
	// Fonction permettant de récupérer le type d'un champ
	protected function getMappingType($field) {
		if (stripos($field, 'Date') !== false) {
			return 'DATE';
		}
		if (stripos($field, 'Filter') !== false) {
			return 'FILTER';
		}
		// Les champs référence sont considéré comme des filtres et permettent de lier plusieurs règles
		if (stripos($field, 'Reference') !== false) {
			return 'FILTER';
		}
		if (stripos($field, 'Metric') !== false) {
			return 'METRIC';
		}
		return null;
	}
	
	// Ajout de champ personnalisé dans la target ex : bittle 
	public function getFieldMappingAdd($moduleTarget) {
		return array(
			'Metric' => 'Metric',
			'Date' => 'Date',
			'Filter' => 'Filter',
			'Reference' => 'Reference'
		);
	}
	
	// Permet d'indiquer le type de référence, si c'est une date (true) ou un texte libre (false)
	public function referenceIsDate($module) {
		return false;
	}
	
	// Ajout de contrôle lors d'un sauvegarde de la règle
	public function beforeRuleSave($data,$type) {
		
		// Vérification de la suppression d'un champ référence
		// Si on est sur une édition 'oldRule' existe
		if (!empty($data['oldRule'])) {
			// Récupération des champs référence de cette ancienne règle qui sont utilisés dans une autre règle
			$sql = "SELECT
						Rule.id,
						Rule.name,
						RuleRelationShip.field_name_target
					FROM RuleRelationShip
						INNER JOIN Rule
							ON RuleRelationShip.rule_id = Rule.id
					WHERE 
							RuleRelationShip.field_id = :oldRule
						AND Rule.deleted = 0";
			$stmt = $this->conn->prepare($sql);
			$stmt->bindValue(":oldRule", $data['oldRule']);
			$stmt->execute();
			$referenceFields = $stmt->fetchAll();
			// Pour tous les champs trouvés, on vérifie qu'ils sont toujours existant dans la nouvelle règle
			if (!empty($referenceFields)) {
				foreach ($referenceFields as $referenceField) {
					// Si le champs est absent alors on génère une erreur.
					if (empty($data['content']['fields']['name'][$referenceField['field_name_target']])) {
						return array('done'=>false, 'message'=> 'The field '.$referenceField['field_name_target'].' is linked to the rule '.$referenceField['name'].'. Change this rule before removing this field.');
					}
				}
			}		
		}
		
		// Si le module d'entrée Bittle n'est pas Container alors on est sur une règle Child. On vérifie que la relation est donc bien présente dans la règle
		if (
				$data['module']['target']['name'] != 'Container'
			&& empty($data['relationships'])
		) {
			return array('done'=>false, 'message'=>'Failed to save the rule. You have to create a relationship with the Connector '.$data['module']['target']['name'].' that you selected in the first step.');
		}
	
		// Pour Bittle, les relations sont un peu plus manuelles donc on vérifie que le champ de la relation appartien bien à la règle sélectionnée
		// Il ne peut y avoir qu'un relation par règle avec Bittle
		if (!empty($data['relationships'])) {
			$sql = "SELECT rule_id
					FROM RuleField
					WHERE 
							rule_id = :rule_id
						AND target_field_name = :target_field_name";
			$stmt = $this->conn->prepare($sql);
			$stmt->bindValue(":rule_id", $data['relationships'][0]['rule']);
			$stmt->bindValue(":target_field_name", $data['relationships'][0]['target']);
			$stmt->execute();
			$fetch = $stmt->fetch();
			if(empty($fetch['rule_id'])) {
				return array('done'=>false, 'message'=>'Failed to save the relationship. The field '.$data['relationships'][0]['target'].' doesn\'t belong to the selected rule ('.$data['relationships'][0]['rule'].'). Change the relationShip to save this rule. ');
			}
			// Ajout du paramètre child à la règle puisqu'une relation existe
			return array('done'=>true, 'message'=>'', 'params' => array('group' => 'child'));
		}
		else {
			// Ajout du paramètre root à la règle puisqu'aucune relation n'existe
			return array('done'=>true, 'message'=>'', 'params' => array('group' => 'root'));
		}
		return array('done'=>true, 'message'=>'');
	}
	
	
	// Après la sauvegarde d'une règle Bittle (toujours en cible) on crée ou modifie le connector Bittle
	public function afterRuleSave($data,$type) {
		try {
			$paramLogin = $this->getParamLogin($data['connector']['cible']);
			$this->login($paramLogin);
			if ($this->connexion_valide === false){
				$this->messages[] = array('type' => 'error', 'message' => 'Failed to login to Bittle. The connector is not created in Bittle. ');
			}
			
			// Récupération des données de la règle
			$sql = "SELECT * FROM Rule WHERE id = :ruleId";
			$stmt = $this->conn->prepare($sql);
			$stmt->bindValue(":ruleId", $data['ruleId']);
			$stmt->execute();
			$data['rule'] = $stmt->fetch();
			if(empty($data['rule'])) {
				$this->messages[] = array('type' => 'error', 'message' => 'Failed to retrieve the rule in the database. The connector is not created in Bittle. ');
			}
						
			// Récupération de tous les ruleFields de la règle en cours
			$sql = "SELECT * FROM RuleField WHERE rule_id = :ruleId";
			$stmt = $this->conn->prepare($sql);
			$stmt->bindValue(":ruleId", $data['ruleId']);
			$stmt->execute();
			$data['ruleFields'] = $stmt->fetchAll();
			if(empty($data['ruleFields'])) {
				$this->messages[] = array('type' => 'error', 'message' => 'Failed to retrieve the ruleFields in the database. The connector is not created in Bittle. ');
			}
			
			// Tout d'abord on vérifie si le connector existe déjà sur une version précédente de la règle ousur une règle root
			// La fonction check créera le connector ou renverra l'existant
			if (empty($this->messages)) {
				$this->checkConnector($data);
			}
		}
		catch (\Exception $e) {
			$error = $e->getMessage();
			$this->messages[] = array('type' => 'error', 'message' => 'Failed to create the connector in Bittle : '.$e->getMessage());
		}
		return $this->messages;
	}
	

}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/bittle.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class bittle extends bittlecore {
		
	}
}