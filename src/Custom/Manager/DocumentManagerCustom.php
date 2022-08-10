<?php

namespace App\Custom\Manager;

use App\Manager\DocumentManager;
use App\Manager\ruleManager;
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


	public function transformDocument()
	{
		$idrule = $this->document_data['rule_id'];
		$docParams = $this->documentManager->getParam();
		// $docParams = $this->documentManager->setParam($param, true);
		try {
			// if the id of the rule we work with matches the rule we want
			//for our filter
			if ($this->ruleId == '62f34a724a381') {

				//we look for an existing gouv id to see if compare has already been done
				if (empty($externalgouvid)) {

					try {
						if (empty($this->etabExternallist)) {
							//get all the school facilities
							$this->etabExternallist = $this->entityManager->getRepository(InternalListValueEntity::class)->findAll();
						}
						$rowschecked = 0;
						$matchingrows = [];
						//code witth this->sourceData to check the source and compare with the externallist
						// $this->sourceData

						foreach ($this->etabExternallist as $row) {
							$data = $row->getData();
							$unserializedData = unserialize($data);
							$validname = ($unserializedData['Nom_etablissement'] == $this->sourceData['name'] && ($unserializedData['Code postal'] == $this->sourceData['billing_address_postalcode'] || $this->sourceData['billing_address_postalcode'] == ""));
							if ($validname == true) {
								//rechercher l'établissement dans l'externallist
								$found = true;
								$matchingrows[$unserializedData['Identifiant_de_l_etablissement']] = $unserializedData['Nom_etablissement'];
							} else {
							}
							$rowschecked++;
						}
						//si trouvé
						if ($found == true) {
							//in database externalgouvid_c
							$this->sourceData['externalgouvid'] = reset($matchingrows);
							return parent::transformDocument();
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

						return false;
					}
				}
			} else {
				return parent::transformDocument();
			}
		} catch (\Exception $e) {
			$this->message .= 'Failed to get document' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
			$this->typeError = 'E';
			$this->updateStatus('Error_transformed');
			$this->logger->error($this->message);
			//make logs
			$this->createDocLog();
			return false;
		}
	} //end transformDocument



}
