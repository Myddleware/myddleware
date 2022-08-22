<?php

namespace App\Custom\Manager;

use App\Solutions\suitecrm;
use App\Manager\ruleManager;
use App\Manager\DocumentManager;
use App\Manager\LoadExternalListManager;
use App\Entity\InternalListValue as InternalListValueEntity;

class DocumentManagerCustom extends DocumentManager
{

	protected $etabExternallist;
	/* // No history for Aiko rules to not surcharge the API
	protected function getDocumentHistory($searchFields) {
		if (
				strpos($this->ruleName, 'aiko') !== false
			AND !empty($searchFields['id'])					// Only history, we keep search duplicate
		) {		
			return false;			
		}		
		return parent::getDocumentHistory($searchFields);
	} */

	protected function beforeStatusChange($new_status)
	{

		// On annule la relation pôle - contact (user) si le contact (user) a été filtré
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '5cfa78d49c536' // Rule User - Pôle
		) {
			if (
				strpos($this->message, 'No data for the field user_id.') !== false
				and strpos($this->message, 'in the rule REEC - Users.') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact (user) lié à ce pôle est absent de la platforme REEC, probablement filtré car inactif. Le lien contact - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. ');
			}
		}

		// On annule la relation pôle - contact (engagé) si le contact (engagé) a été filtré
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '5d081bd3e1234' // Rule User - Pôle
		) {
			if (
				strpos($this->message, 'No data for the field record_id.') !== false
				and strpos($this->message, 'in the rule REEC - Engagé.') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme REEC ou n\'est pas un contact de type engagé. Le lien contact - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. ');
			}
		}

		// On annule la relation pôle - contact (université) si le contact (université) a été filtré
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '5d163d3c1d837' // Rule Contact composante - Pôle
		) {
			if (
				strpos($this->message, 'No data for the field record_id.') !== false
				and strpos($this->message, 'in the rule REEC - Contact - Composante.') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme REEC ou n\'est pas un contact de type contact université. Le lien contact - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. ');
			}
		}

		// We cancel the relation pôle - contact partenaire if he has been filtered
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '62743060350ed' // Esp Rep - Contact repérant - Pôle
		) {
			if (
				strpos($this->message, 'No data for the field record_id.') !== false
				and strpos($this->message, 'Esp Rep - Contact rep') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme l\'epace repérant ou n\'est pas un contact de type contact partenaire. Le lien contact - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. ');
			}
		}

		// We cancel the relation Contact repérant - Pôle if he has been filtered
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '62743060350ed' // Esp Rep - Contact repérant - Pôle
		) {
			if (
				strpos($this->message, 'No data for the field record_id.') !== false
				and strpos($this->message, 'in the rule Esp Rep - Contacts rep') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme epace repérant ou n\'est pas un contact de type contact repérant. Le lien contact - pôle ne sera donc pas créé dans l\'espace repérant. Ce transfert de données est annulé. ');
			}
		}

		// If we don't found the contact (COMET) in the coupon (REEC), we cancel the data transfer. 
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '6273b3b11c63e' // Esp Rep - Relation Contacts Coupons
			and $new_status == 'Not_found'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le mentoré n\a pas été trouvé sur un coupon dans l\'epace repérant. Ce transfert de données est annulé. ');
		}

		// If we don't found the coupon (REEC) corresponding to the contact (COMET), we cancel the data transfer. 
		if (
			!empty($this->document_data['rule_id'])
			and	in_array($this->document_data['rule_id'], array('6274428910b18', '62744b95de96f')) // Esp Rep - Fiche évaluation fin vers Esp Rep
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le mentoré n\a pas été trouvé sur un coupon dans l\'epace repérant. Ce transfert de données est annulé. ');
		}

		/* if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '5cffd54c8842b' // Rule Formation - Engagé
		) {
			if (	
				(	
						strpos($this->message, 'No data for the field fp_events_contactscontacts_idb.') !== false
					AND strpos($this->message, 'in the rule Engagé.') !== false	
				)
				OR (
						strpos($this->message, 'No data for the field fp_events_contactsfp_events_ida.') !== false
					AND strpos($this->message, 'in the 	Formation session.') !== false	
				)
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact de cette formation est absent de la platforme REEC. Le lien Formation - Contact ne sera donc pas créé dans REEC. Ce transfert de données est annulé. '); 
			}
		}
		
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '5d08e425e49ea' // Rule Formation - pôle
		) {
			if (
					strpos($this->message, 'No data for the field record_id.') !== false
				AND strpos($this->message, 'in the rule Formation.') !== false	
			) {	
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('La formation est absente de la platforme REEC, il s\'agit probablement d\'une formation filtrée car de type réunion. Le lien Formation - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. '); 
			}
		} */

		// If we don't found the coupon (REEC) corresponding to the contact (COMET), we cancel the data transfer. 
		if (
			!empty($this->document_data['rule_id'])
			and	in_array($this->document_data['rule_id'], array('628cdd961b093')) // Esp Rep - Coupon - Pôles
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le coupon de la relation pole - coupon n\'a pas été trouvé. Il s\'agit probablement d\'un coupon non mentoré. Ce transfert de données est annulé. ');
		}

		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '5d163d3c1d837' // Rule Contact composante - Pôle
		) {
			if (
				strpos($this->message, 'No data for the field record_id.') !== false
				and strpos($this->message, 'in the rule REEC - Contact - Composante.') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact composante est absent de la platforme REEC, il s\'agit probablement d\'une composante sans adresse email. Le lien Contact composante - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. ');
			}
		}

		// On annule tous les transferts de données en relate ko pour la règle composante - Contact composante
		// En effet des la majorité des relations accounts_contacts ne sont pas des composante - Contact composante
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '5f20b113356e1' // Rule Composante - Contact composante
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('La relation ne concerne probablement pas une composante et un contact composante. Ce transfert de données est annulé. ');
		}

		// On annule tous les transferts de données en relate ko pour la règle composante - Contact partenaire
		// En effet des la majorité des relations accounts_contacts ne sont pas des composante - Contact partenaire
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '62790c7db0a87' // Esp Rep - Composante - Contact partenaire
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('La relation ne concerne probablement pas une composante et un contact partenaire. Ce transfert de données est annulé. ');
		}


		// On annule tous les transferts de données en relate ko pour la règle composante - Engagé
		// En effet une partie des relations accounts_contacts ne sont pas des composante - Engagé
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '5f8486295b5a7' // Rule composante - Engagé
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('La relation ne concerne probablement pas une composante et un contact composante. Ce transfert de données est annulé. ');
		}

		// Si on est sur une suppression d'une composante, le document est souvent filtré car la composante supprimé n'a plus d'établissment supérieur lié
		// La suppression est alors annulée. On souhaite supprimer quand même la données si elle a été envoyée par Myddleware
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '5ce362b962b63' // Rule composante
			and	$this->document_data['type'] == 'D' // Delete
			and $new_status == 'Filter'
		) {
			$new_status = 'Filter_OK';
			$this->message .= utf8_decode('Aucun filtrage appliqué sur la suppression d une composante. Cette composante doit réellement être supprimée dans REEC même si elle n a plus d établissement supérieur dans la COMET. ');
		}

		// No error if the coupon doesn't exist in REEC (no update in this case)
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '62739b419755f' // Esp Rep - Coupons vers Esp Rep
			and $new_status == 'Relate_KO'
		) {
			if (
				strpos($this->message, 'No data for the field Myddleware_element_id.') !== false
				and strpos($this->message, ' in the rule REEC - Coupons vers comet.') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le coupon n\existe pas dans de la platforme epace repérant, la mise à jour est donc interrompue. ');
			}
		}

		/************************************************/
		/************         AIKO         **************/
		/************************************************/
		// If relate_ko and binôme status is annule then we cancel the data transfer
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '61a930273441b' // Rule Aiko binome
			and $new_status == 'Relate_KO'
			and $this->sourceData['statut_c'] == 'annule'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le statut du binôme est annulé. Ce transfert de données est annulé. ');
		}

		// If relate_OK and binôme status is one of these status : termine;annule;accompagnement_termine
		// And if the document type is a creation then we cancel the data transfer
		// However if it is an update we keep the document to set the new status in Airtable (and generate a deletion during the next call)
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '61a930273441b' // Rule Aiko binome
			and $new_status == 'Predecessor_OK'
			and in_array($this->sourceData['statut_c'], array('termine', 'annule', 'accompagnement_termine'))
			and	$this->documentType == 'C' // Creation
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le statut du binôme est annulé ou terminé et le document genère une création donc on annule l envoi vers Airtable. ');
		}

		// If relate_ko on rule Aiko binome - pole then we cancel the data transfer
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '61a93469599ae' // Rule Aiko binome - pole
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Les anciens binômes et les binômes annulés ne sont pas envoyés dans Airtable, la relation pôle tombe logiquement en relate_KO. Ce transfert de données est annulé. ');
		}

		// If relate_ko on rule Aiko contact - pole then we cancel the data transfer
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '61a9329e6d6f2' // Rule Aiko contact - pole
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Les contacts partenaires ne sont pas envoyés dans Airtable, la relation pôle tombe logiquement en relate_KO. Ce transfert de données est annulé. ');
		}

		return $new_status;
	}

	public function updateStatus($new_status)
	{
		// Add error expected status
		$this->globalStatus['Error_expected'] = 'Cancel';

		// Cancel data transfert as the rule Aiko - Suppression generates document into other rules
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '61bb49a310715' // Aiko - Suppression
			and	$new_status == 'Predecessor_OK'
		) {
			$new_status = 'Cancel';
		}

		$updateStatus = parent::updateStatus($new_status);

		return $updateStatus;
	}

	//! ALERT NEW UNTESTED CODE ████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████

	//todo create a function that must be in the scope of the target data document and update the fields
	//todo it has a isCreate argument true or false because if it is false then we don't create the field ['externalgouvid']
	//todo instead we just update the fields
	public function mapTargetFields($source, $target, $isCreate)
	{
		if ($isCreate === true) {
			//todo find the right syntax to target field
			//create a new field for the new school
			$targetAfev['externalgouvid'] = $this->sourceData['externalgouvid'];
		}

		//* update existing document ████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████
		//update fields
		//account type
		switch ($unserializedData['libelle_nature']) {
			case "COLLEGE":
				//some types are integer in suitecrm
				$this->sourceData['account_type'] = 8;
				break;
			case "ECOLE DE NIVEAU ELEMENTAIRE":
				$this->sourceData['account_type'] = 10;
				break;
			case "ECOLE MATERNELLE":
				$this->sourceData['account_type'] = 'ecole_maternelle';
				break;
			default:
				throw new \Exception("Error reading school type");
		}

		//phone number
		$this->sourceData['phone_office'] = $unserializedData['Telephone'];

		//email
		$this->sourceData['email1'] = $unserializedData['Mail'];

		//rep+
		switch ($unserializedData['Appartenance_Education_Prioritaire']) {
			case "REP+":
				//some types are integer in suitecrm
				$this->sourceData['rep_c'] = 'REP_PLUS';
				break;
			case "REP":
				$this->sourceData['account_type'] = "REP";
				break;
			case "":
				$this->sourceData['account_type'] = '';
				break;
			default:
				throw new \Exception("Error reading REP");
				break;
		}

		//city
		$this->sourceData['billing_address_city'] = $unserializedData['Nom_commune'];

		//billing address 1
		$this->sourceData['billing_address_street'] = $unserializedData['Adresse_1'];

		//billing address 2
		//unlike billing address 1, we do not add an address if the internal list field is empty
		if (($this->sourceData['billing_address_street_2'] == "" || $this->sourceData['billing_address_street_2'] != $unserializedData['Nom_commune']) && $unserializedData['Adresse_2'] != "") {
			$this->sourceData['billing_address_street_2'] = $unserializedData['Adresse_2'];
		}

		//postal code
		$this->sourceData['billing_address_postalcode'] = $unserializedData['Code postal'];
		//todo simply update the values



		//* update existing document ████████████████████████████████████████████████████████████████████████████████████

	}

	//! ALERT NEW UNTESTED CODE ████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████



	// Permet de transformer les données source en données cibles
	public function getTargetDataDocument()
	{
		// Return false if job has been manually stopped
		if (!$this->jobActive) {
			$this->message .= 'Job is not active. ';

			return false;
		}
		$history = false;
		try {
			// Check if the rule is a parent and run the child data.
			$this->runChildRule();

			// If the document type is a modification or a deletion we get target data for the record using its ID
			// And if the rule is not a child (no target id is required, it will be send with the parent rule)
			if (
				('U' == $this->documentType
					or 'D' == $this->documentType
				)
				&& !$this->isChild()
			) {
				// Récupération des données avec l'id de la cible
				$searchFields = ['id' => $this->targetId];
				$history = $this->getDocumentHistory($searchFields);

				// History is mandatory before a delete action, however if no record found, it means that the record has already been deleted
				if (
					'D' == $this->documentType
					and false === $history
				) {
					$this->message .= 'This document type is D (delete) and no record have been found in the target application. It means that the record has already been deleted in the target application. This document is cancelled.';
					$this->updateStatus('Cancel');

					return false;
				}

				// From here, the history table has to be filled
				if (-1 !== $history) {

					//! ALERT NEW UNTESTED CODE ████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████

					//todo : before getting ready to send then we do the treatment with the afev
					//todo we start by using the data from the afev

					//todo we do a foreach that encapsulates everything and we do it for every
					//todo etablissement of the internallist
					foreach ($internalListTables as $internaltable) {
						//todo we do a custom search for the gouv id in the row of the afev
						$findGovId = $internaltable['externalgouvid'];
						if (!empty($findGovId)) {
							//3rd argument, isCreate is on false because since we have a gouv id then 
							//we just update the fields
							//? Why do we get an error if function is in the same scope ?
							mapTargetFields($source, $target, false);

							//todo set status to update ?
							//todo the id of the document should be put in the target id of myddleware
							//todo use the parent ?
						} else {
							//if we didn't find the exteralgoivid in the afev database it means that we have to either find the school
							// by name and other fields, or it doesn't exist at all and we need to create it

							if (empty($dataAfev)) {
								//we fetch the full Accounts table from the afev
								$dataAfev = "we get the result of the query from the afev";
							}

							//todo we try to find the school by name etc
							//? we are already in the loop !!!
							//todo start the treatment to check if the etablissement is present in the afev database
							$found = false;
							//to check if all rows of the table were looked at
							$rowschecked = 0;
							//to avoid too many choices, this array must have only one element
							$matchingrows = [];


							//! is it ok to re
							foreach ($dataAfev as $afevSchool) {
							$data = $row->getData();
							$unserializedData = unserialize($data);
							//init name as false at the beginning of the loop
							$validName = false;
							$validPostalCode = ($unserializedData['Code postal'] == $this->sourceData['billing_address_postalcode']);
							$validAddress = ($unserializedData['Adresse_1'] == $this->sourceData['billing_address_street']);
							$validCity = ($unserializedData['Nom_commune'] == $this->sourceData['billing_address_city']);

							//use algorithm to compare similarity of 2 names, threshold is 60% similar
							$namecompare = similar_text($this->sourceData['name'], $unserializedData['Nom_etablissement'], $perc);
							if ($perc >= 80) {
								$validName = true;
							}
							//to have a match, we need a similar name and at least the same address or postal code
							$validRow = ($validName && ($validPostalCode || $validAddress));
							if ($validRow == true) {

								//we append the array of matches
								$matchingrows[(int)$perc] = $unserializedData['Identifiant_de_l_etablissement'];
								$found = true;
							} else {
								$found = false;
								// throw new \Exception("Cet établissement n'a pas assez de champs");
							}
							$rowschecked++;
						}

						if ($found === true) {
							//if we have more than one match, then we sort by percentage of matching
							//and use the closest match
							if (count($matchingrows) > 1) {
								krsort($matchingrows);
							}
						} //end afev loop
					}


					
							//todo start the treatment to check if the etablissement is present in the afev database
									//* transform section ████████████████████████████████████████████████████████████████████████████████████
									
										// if ($this->ruleId == '62f34a724a381' && $this->sourceData['id'] == 'c28c855d-12f9-b8bd-c593-616ebcf16635') {
										//we look for an existing gouv id to see if compare has already been done
												
												//check for row match using the name, the postal code, and the address
												foreach ($this->etabExternallist as $row) {
													

													//modify source data to match internallist
													$this->sourceData['externalgouvid'] = reset($matchingrows);

													//account type
													if ($this->sourceData['type_de_partenaire_c'] == "") {
														switch ($unserializedData['libelle_nature']) {
															case "COLLEGE":
																//some types are integer in suitecrm
																$this->sourceData['account_type'] = 8;
																break;
															case "ECOLE DE NIVEAU ELEMENTAIRE":
																$this->sourceData['account_type'] = 10;
																break;
															case "ECOLE MATERNELLE":
																$this->sourceData['account_type'] = 'ecole_maternelle';
																break;
															default:
																throw new \Exception("Error reading school type");
														}
													}

													//phone number
													if ($this->sourceData['phone_office'] == "" || $this->sourceData['phone_office'] != $unserializedData['Telephone']) {
														$this->sourceData['phone_office'] = $unserializedData['Telephone'];
													}

													//email
													if ($this->sourceData['email1'] == "" || $this->sourceData['email1'] != $unserializedData['Mail']) {
														$this->sourceData['email1'] = $unserializedData['Mail'];
													}

													//rep+
													if ($this->sourceData['rep_c'] == "" || $this->sourceData['rep_c'] != $unserializedData['Appartenance_Education_Prioritaire']) {
														switch ($unserializedData['Appartenance_Education_Prioritaire']) {
															case "REP+":
																//some types are integer in suitecrm
																$this->sourceData['rep_c'] = 'REP_PLUS';
																break;
															case "REP":
																$this->sourceData['account_type'] = "REP";
																break;
															case "":
																$this->sourceData['account_type'] = '';
																break;
															default:
																throw new \Exception("Error reading REP");
																break;
														}
													}

													//city
													if ($this->sourceData['billing_address_city'] == "" || $this->sourceData['billing_address_city'] != $unserializedData['Nom_commune']) {
														$this->sourceData['billing_address_city'] = $unserializedData['Nom_commune'];
													}

													//billing address 1
													if ($this->sourceData['billing_address_street'] == "" || $this->sourceData['billing_address_street'] != $unserializedData['Adresse_1']) {
														$this->sourceData['billing_address_street'] = $unserializedData['Adresse_1'];
													}


													//billing address 2
													//unlike billing address 1, we do not add an address if the internal list field is empty
													if (($this->sourceData['billing_address_street_2'] == "" || $this->sourceData['billing_address_street_2'] != $unserializedData['Nom_commune']) && $unserializedData['Adresse_2'] != "") {
														$this->sourceData['billing_address_street_2'] = $unserializedData['Adresse_2'];
													}

													//postal code
													if ($this->sourceData['billing_address_postalcode'] == "" || $this->sourceData['billing_address_postalcode'] != $unserializedData['Code postal']) {
														$this->sourceData['billing_address_postalcode'] = $unserializedData['Code postal'];
													}
												} else {
													throw new \Exception("Établissement non trouvé dans la liste gouvernementale");
												}
											} catch (\Exception $e) {
												$this->message .= 'Failed to get document with custom id' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
												$this->typeError = 'E';
												$this->updateStatus('Error_transformed');
												$this->logger->error($this->message);
												//make logs
												$this->createDocLog();
											}
										} else {
										}
									} else {
										return parent::transformDocument();
									}
									//* transform section ████████████████████████████████████████████████████████████████████████████████████
								}
							}
							//todo if we have gone through all the rows of the afev and we haven't found the etablissement, then we simply create the entry
							//todo put the status to create, that way it will make a new row in the afev database
						}
					}

					//! ALERT NEW UNTESTED CODE ████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████
					$this->updateStatus('Ready_to_send');
				} else {
					throw new \Exception('Failed to retrieve record in target system before update or deletion. Id target : ' . $this->targetId . '. Check this record is not deleted.');
				}
			}
			// Else if create or search document, if we have duplicate_fields, we search the data in target application
			elseif (!empty($this->ruleParams['duplicate_fields'])) {
				$duplicate_fields = explode(';', $this->ruleParams['duplicate_fields']);
				// Get the field value from the document target data
				$target = $this->getDocumentData('T');
				if (empty($target)) {
					throw new \Exception('Failed to search duplicate data in the target system because there is no target data in this data transfer. This document is queued. ');
				}
				// Prepare the search array with teh value for each duplicate field
				foreach ($duplicate_fields as $duplicate_field) {
					$searchFields[$duplicate_field] = $target[$duplicate_field];
				}
				if (!empty($searchFields)) {
					$history = $this->getDocumentHistory($searchFields);
				}

				if (-1 === $history) {
					throw new \Exception('Failed to search duplicate data in the target system. This document is queued. ');
				}
				// Si la fonction renvoie false (pas de données trouvée dans la cible) ou true (données trouvée et correctement mise à jour)
				elseif (false === $history) {
					// If search document and don't found the record, we return an error
					if ('S' == $this->documentType) {
						$this->typeError = 'E';
						$this->updateStatus('Not_found');
					} else {




						$this->updateStatus('Ready_to_send');
					}
				}
				// renvoie l'id : Si une donnée est trouvée dans le système cible alors on modifie le document pour ajouter l'id target et modifier le type
				else {
					// Add message detail when we have found a record
					if (!empty($searchFields)) {
						$this->message .= 'Found ';
						foreach ($searchFields as $key => $value) {
							$this->message .= $key . ' = ' . $value . ' ; ';
						}
					}
					// If search document we close it.
					if ('S' == $this->documentType) {
						$this->updateStatus('Found');
					} else {


						$this->updateStatus('Ready_to_send');
						$this->updateType('U');
					}
					$this->updateTargetId($history['id']);
				}
			}
			// Sinon on mets directement le document en ready to send (example child rule)
			else {
				$this->updateStatus('Ready_to_send');
			}
			// S'il n'y a aucun changement entre la cible actuelle et les données qui seront envoyée alors on clos directement le document
			// Si le document est en type recherche, alors la cible est forcément égale à la source et il ne fait pas annuler le doc.
			// We always send data if the rule is parent (the child data could be different even if the parent data didn't change)
			// No check for deletion document
			if (
				'S' != $this->documentType
				and 'D' != $this->documentType
				and !$this->isParent()
			) {
				$this->checkNoChange($history);
			}
			// Error if rule mode is update only and the document is a creation
			if (
				$this->documentType == 'C'
				and $this->ruleMode == 'U'
			) {
				throw new \Exception('The document is a creation but the rule mode is UPDATE ONLY. ');
			}
		} catch (\Exception $e) {
			$this->message .= $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
			$this->typeError = 'E';
			if ('S' == $this->documentType) {
				$this->updateStatus('Not_found');
			} else {
				$this->updateStatus('Error_checking');
			}
			$this->logger->error($this->message);

			return false;
		}

		return true;
	}
}
