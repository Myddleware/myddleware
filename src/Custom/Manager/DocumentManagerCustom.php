<?php

namespace App\Custom\Manager;

use App\Solutions\suitecrm;
use App\Manager\ruleManager;
use App\Manager\DocumentManager;
use App\Manager\LoadExternalListManager;
use App\Entity\InternalListValue as InternalListValueEntity;

class DocumentManagerCustom extends DocumentManager
{

	protected $etabComet;
	protected $quartierComet;
	protected $emailCoupon = array();
	protected $toBeCancel = array();
	protected $doNotOverrideStatus = false;
	
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
	
	protected function searchRelateDocumentByStatus($ruleRelationship, $record_id, $status) {
		// Don't check if a relate document is filtered for rule Aiko binome
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '61a930273441b' // Aiko binome
			AND $status = 'Filter'
		) {
			return null;
		}
		return parent::searchRelateDocumentByStatus($ruleRelationship, $record_id, $status);
	}
	

	protected function beforeStatusChange($new_status) {	
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
		
		// On annule la relation pôle - contact (engagé) si le contact (jeune accompagné) a été filtré ou n'est pas un jeune accompagné
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '63a325156cd50' // REEC - Composante - Jeune accompag
		) {
			if (
				strpos($this->message, 'No data for the field contact_id.') !== false
				and strpos($this->message, 'in the rule REEC - Jeune accompagn') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme REEC ou n\'est pas un contact de type jeune accompagne. Le lien contact - composante ne sera donc pas créé dans REEC. Ce transfert de données est annulé. ');
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
		
		// We cancel the relation Coupon - Pôle if he has been filtered
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '626931ebbff78' // Mobilisation - Relations pôles Coupons
		) {			
			if (
					strpos($this->message, 'No data for the field record_id.') !== false
				AND strpos($this->message, ' in the rule Mobilisation - Coupons') !== false	
			) {				
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le coupon lié à ce pôle est absent de Airtable. Il s\'agit probablement d\'un coupon d\'un type filtré. Le lien coupon - pôle ne sera donc pas créé dans Airtable. Ce transfert de données est annulé. '); 
			}
		}
		
		// We cancel the document (Sendinblue - contact search into COMET) if we don't find the contact
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '64397bd8c4749' // Sendinblue - contact search into COMET
			AND $new_status == 'Not_found'
		) {			
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Adresse email introuvable dans la COMET. Ce transfert de données est annulé.'); 
		}
		
		// We cancel the document (Sendinblue - coupon search into COMET) if we don't find the coupon
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '64399ff31f587' // Sendinblue - coupon search into COMET
			AND $new_status == 'Not_found'
		) {			
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Adresse email introuvable dans la COMET. Ce transfert de données est annulé.'); 
		}
		
		// No update from REEC to COMET if the contact hasn't been create from COMET
		// Relate KO happens when a contact is created manually into REEC of if the contact is only a user in COMET not a contact
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '60b0b881235e8' // REEC - Engagé vers COMET
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le contact REEC n\existe pas dans la COMET, la mise à jour est donc interrompue. ');
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
		
		// Cancel if the doc is related KO and the email linked to a user (afev.org)
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '6210fcbe4d654' // Sendinblue - email delivered
			AND $new_status == 'Relate_KO'
		) {
			if (strpos($this->sourceData['email'], '@afev.org') !== false) {
				$this->message .= utf8_decode('L\email n\'appartient pas à un contact dans la COMET mais à un salarié (domaine afev.org). Ce transfert de données est annulé. ');
				$new_status = 'Error_expected';
			} elseif (strpos($this->sourceData['subject'], 'Contact SuiteCRM -') !== false) {
				$this->message .= utf8_decode('L\email est une notification pour un salarié. Ce transfert de données est annulé. ');
				$new_status = 'Error_expected';
			}
		}

		// Cancel if the doc is related KO and the email linked to a user (afev.org)
		if (
				!empty($this->toBeCancel[$this->id])
			AND	$this->document_data['rule_id'] == '6210fcbe4d654' // Sendinblue - email delivered
			AND $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('L\email n\'a pas été trouvé dans les contacts et les coupons de la COMET. Ce transfert de données est annulé. ');
		}
				
		// In case a data has already been deleted in Airtable, Myddleware won't be able to process the check, so we cancel the deletion
		if (
				!empty($this->document_data)
			AND	in_array($this->document_data['conn_id_target'], array(4,8)) // Airtable connectors
			AND $new_status == 'Error_checking'
			AND	$this->documentType == 'D' // Deletion
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('L\'enregistrement est certainement déjà supprimé dans Airtable. Ce transfert de données est annulé. ');
		}
		
		// Do not create a contact into USC
		if (
				!empty($this->document_data)
			AND	$this->document_data['rule_id'] == '643eb15eb70ea'	// Mobilisation - Contact vers USC
			AND $new_status == 'Error_checking'
			AND	$this->documentType == 'C' // Creation
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('La COMET ne peut pas créer de contact dans USC. Ce transfert de données est annulé. ');
		}

		return $new_status;
	}

	public function updateStatus($new_status)
	{
		// If the status has been forced during a standard process, we stop the next status change (done by the standard)
		if ($this->doNotOverrideStatus) {
			$this->doNotOverrideStatus = false;
			return null;
		}
		
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

		// Generate a document for the rule REEC - Users custom each time a document is generated for a user
		if (
				!empty($this->document_data['rule_id'])
			and !empty($this->document_data['source_id'])
			and	$this->document_data['rule_id'] == '5cf98651a17f3' // REEC - Users
			and	$new_status == 'Ready_to_send'
		) {
			$this->generateDocument('63e1007614977', $this->document_data['source_id']);	// REEC - Users custom
		}
		
		// Generate Pole and account relationship for creation only but before the duplicate search. 
		// The contact could already exist thanks to the REEC user rule. So we need to check if this is a creation document before the duplicate search.
		if (
				!empty($this->document_data['rule_id'])
			AND $this->document_data['rule_id'] == '5ce3621156127' //Engagés
			AND $new_status == 'Transformed'
			AND $this->documentType == 'C'
		) {
			// Si un engagé est envoyé dans REEC, on recherche également son pôle
			// En effet quand un engagé est reconduit, on n'enverra pas son pôle qui est une données qui a été créée dans le passé 
			$this->generateDocument('5d081bd3e1234', $this->document_data['source_id'], 'record_id', false); // Engagé - pole
			// Si un engagé est envoyé dans REEC, on recherche également sa composante
			// En effet quand un engagé est envoyé dans REEC, il a peut être filtré avant et la relation avec la composante est donc filtrée aussi
			// On force donc la relance de la relation composante - Engagé à chaque fois qu'un engagé est modifié	
			$this->generateDocument('5f8486295b5a7', $this->document_data['source_id'], 'contact_id', false); // Composante - Engagé
		}

		$updateStatus = parent::updateStatus($new_status);

		
		return $updateStatus;
	}

	public function mapTargetFields($internalListData, $suiteCrmData)
	{
		if (!isset($suiteCrmData['externalgouvid_c'])) {
			//create a new field for the new school
			$suiteCrmData['externalgouvid_c'] = $internalListData['Identifiant_de_l_etablissement'];
		}

		//name update: if there is a difference between the original name and the new name
		if ($suiteCrmData['name'] !== $internalListData['Nom_etablissement']) {
			$suiteCrmData['description'] = $internalListData['Nom_etablissement'];
		}

		//account type
		if ($suiteCrmData['type_de_partenaire_c'] == "") {
			switch ($internalListData['libelle_nature']) {
				case "COLLEGE":
					//some types are integer in suitecrm
					$suiteCrmData['account_type'] = 8;
					break;
				case "ECOLE DE NIVEAU ELEMENTAIRE":
					$suiteCrmData['account_type'] = 10;
					break;
				case "ECOLE MATERNELLE":
					$suiteCrmData['account_type'] = 'ecole_maternelle';
					break;
				default:
					throw new \Exception("Error reading school type");
			}
		}

		//phone number map
		if ($suiteCrmData['phone_office'] == "" || $suiteCrmData['phone_office'] != $internalListData['Telephone']) {
			$suiteCrmData['phone_office'] = $internalListData['Telephone'];
		}
		
		//if phone number is format 06xxx, then convert it to +33xxx
		if($suiteCrmData['phone_office'][0] == 0) {
			$phoneRes = "+33".substr($suiteCrmData['phone_office'],1);
			$suiteCrmData['phone_office'] = $phoneRes;
		};

		//email
		if ($suiteCrmData['email1'] == "" || $suiteCrmData['email1'] != $internalListData['Mail']) {
			$suiteCrmData['email1'] = $internalListData['Mail'];
		}

		//rep+
		// if ($suiteCrmData['rep_c'] == "" || $suiteCrmData['rep_c'] != $internalListData['Appartenance_Education_Prioritaire']) {
			switch ($internalListData['Appartenance_Education_Prioritaire']) {
				case "REP+":
					//some types are integer in suitecrm
					$suiteCrmData['rep_c'] = 'REP_PLUS';
					break;
				case "REP":
					$suiteCrmData['account_type'] = "REP";
					break;
				case "":
					$suiteCrmData['account_type'] = '';
					break;
				default:
					throw new \Exception("Error reading REP");
					break;
			}
		// }


		//city
		if ($suiteCrmData['billing_address_city'] == "" || $suiteCrmData['billing_address_city'] != $internalListData['Nom_commune']) {
			$suiteCrmData['billing_address_city'] = $internalListData['Nom_commune'];
		}

		//billing address 1
		if ($suiteCrmData['billing_address_street'] == "" || $suiteCrmData['billing_address_street'] != $internalListData['Adresse_1']) {
			$suiteCrmData['billing_address_street'] = $internalListData['Adresse_1'];
		}


		//billing address 2
		//unlike billing address 1, we do not add an address if the internal list field is empty
		if (($suiteCrmData['billing_address_street_2'] == "" || $suiteCrmData['billing_address_street_2'] != $internalListData['Nom_commune']) && $internalListData['Adresse_2'] != "") {
			$suiteCrmData['billing_address_street_2'] = $internalListData['Adresse_2'];
		}

		//postal code
		if ($suiteCrmData['billing_address_postalcode'] == "" || $suiteCrmData['billing_address_postalcode'] != $internalListData['Code_postal']) {
			$suiteCrmData['billing_address_postalcode'] = $internalListData['Code_postal'];
		}
	} //end define mapTargetFields


	//function to get the data of a row of internalListValue in the correct format
	public function unserializeData($serializedData)
	{
		$data = $serializedData->getData();
		$unserializeData = unserialize($data);
		return $unserializeData;
	}

	//function that reads a row from the internallist and trys to find a match in the target
	public function findMatchCrmEtab()
	{
		//to avoid too many choices, this array must have only one element at the end
		$matchingrows = [];

		//we loop through the suiteCrm accounts
		foreach ($this->etabComet as $index => $suiteCrmSchool) {
			//init name as false at the beginning of the loop

			$validName = false;
			$validCity = false;
			
			// Check validity of code postal
			if (
					!empty($this->sourceData['Code_postal'])
				AND !empty($suiteCrmSchool['billing_address_postalcode'])
			) {
				$validPostalCode = ($this->sourceData['Code_postal'] == $suiteCrmSchool['billing_address_postalcode']);
			}
			
			// Check validity of city
			if (
					!empty($this->sourceData['Nom_commune'])
				AND !empty($suiteCrmSchool['billing_address_city'])
			) {
				$cityCompare = similar_text($this->sourceData['Nom_commune'], $suiteCrmSchool['billing_address_city'], $cityPerc);
				if ($cityPerc >= 90) {
					$validCity = true;
				}
			}
			
			//use algorithm to compare similarity of 2 names, threshold is 60% similar
			$namecompare = similar_text($suiteCrmSchool['name'], $this->sourceData['Nom_etablissement'], $perc);
			if ($perc >= 80) {
				$validName = true;
			}
			//to have a match, we need a similar name and at least the same address or postal code
			if (($validName && ($validPostalCode || $validCity))) {
				//we append the array of matches
				$matchingrows[(int)$perc] = $suiteCrmSchool['id'];
			} else {
				// throw new \Exception("Cet établissement n'a pas assez de champs");
			}
		} // end foreach dataSuiteCrm to find school
		return $matchingrows;
	}


	//function that reads a row from the internallist and trys to find a match in the target
	public function findMatchCrmQuartiers()
	{
		//to avoid too many choices, this array must have only one element at the end
		$matchingrows = [];

		//we loop through the suiteCrm quartier
		foreach ($this->quartierComet as $index => $suiteCrmQuartier) {
			// Do not search match if there is already a externalgouvid_c on teh quartier
			if (!empty($suiteCrmQuartier['externalgouvid_c'])) {
				continue;
			}
			$validCity = false;
			$validDepartement = false;
			//init name as false at the beginning of the loop
			$validName = false;
			// Check the city
			$cityCompare = similar_text($suiteCrmQuartier['ville_c'], $this->sourceData['Noms_des_communes_concernees'], $percCity);
			if ($percCity >= 90) {
				$validCity = true;
			}
			
			if (
					!empty($suiteCrmQuartier['departement_c'])
				AND !empty($this->sourceData['DEPARTEMENT'])
				AND $this->sourceData['DEPARTEMENT'] == $suiteCrmQuartier['departement_c']
			) {
				$validDepartement = true;
			}

			//use algorithm to compare similarity of 2 names, threshold is 60% similar
			$nameCompare = similar_text($suiteCrmQuartier['name'], $this->sourceData['Quartier_prioritaire'], $perc);

			if ($perc >= 80) {
				$validName = true;
			}
			//to have a match, we need a similar name and at least the same address or postal code
			if ($validName && ($validCity || $validDepartement)) {
				//we append the array of matches
				$matchingrows[(int)$perc] = $suiteCrmQuartier['id'];
			} else {
				// throw new \Exception("Cet établissement n'a pas assez de champs");
			}
		} // end foreach dataSuiteCrm to find school
		return $matchingrows;
	}
	
	public function checkParentDocument(): bool {
		try {				
			// If the sendinblue contact is not found in the contact rule, we search in the coupon rule
			if ($this->ruleId == '6210fcbe4d654') { 	// Sendinblue - email delivered		
				$chekParent = parent::checkParentDocument();	
				if ($chekParent) {
					return $chekParent;
				}
				// Change relationship if not found
				$keyRelParent = array_search('parent_id', array_column($this->ruleRelationships, 'field_name_target'));
				if ($keyRelParent === false) {				
					throw new \Exception('Relatiobnship with contact id missing for this rule.');	
				}					
				$this->ruleRelationships[$keyRelParent]['field_id'] = '620e5520c62d6';	// Sendinblue - coupon	
				// Check the parent with the coupon rule
				$chekParent = parent::checkParentDocument();
				if ($chekParent) {
					return $chekParent;
				}
				
				// Change the relationship with the rule Sendinblue - contact search into COMET
				$this->ruleRelationships[$keyRelParent]['field_id'] = '64397bd8c4749';	// Sendinblue - contact search into COMET	
				$chekParent = parent::checkParentDocument();
				if ($chekParent) {
					return $chekParent;
				}
				
				// Change the relationship with the rule Sendinblue - coupon search into COMET
				$this->ruleRelationships[$keyRelParent]['field_id'] = '64399ff31f587';	// Sendinblue - coupon search into COMET	
				
			}
		} catch (\Exception $e) {
			$this->message .= 'Failed to check document related : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->updateStatus('Relate_KO');
			$this->logger->error($this->message);
			return false;
		}	
		$chekParent = parent::checkParentDocument();

		// In case there is no contact or coupon found in Myddlewarere for the email, we try to find the email address directly from SuiteCRM
		// If we don't find the email address we cancel the document 
		if (
				$chekParent == false 
			AND $this->ruleId == '6210fcbe4d654' // Sendinblue - email delivered
		) {
			if (empty($this->solutionTarget)) {
				$this->connexionSolution('target');
			}
			// Search email from SuiteCRM, module Contacts
			$read['module'] = 'Contacts';
			$read['ruleParams']['mode'] = '0';
			$read['query']['email1'] = $this->sourceData['email'];
			$read['rule'] = $this->rule;
			$read['limit'] = 1;
			$read['date_ref'] = '1970-01-01 00:00:00';
			$read['call_type'] = 'read';
			$read['jobId'] = $this->jobId;
			$result = $this->solutionTarget->read($read);
			// If no result found, we search email from SuiteCRM, module Leads
			if (empty($result)){
				$read['module'] = 'Leads';
				$result = $this->solutionTarget->read($read);
				// If no email found, we cancel the document
				if (empty($result)){
					$this->toBeCancel[$this->id] = true;
					// Call again the check parent function to cancel the document using the attribut toBeCancel
					parent::checkParentDocument();
				}
			}
		}
			
		return $chekParent;
	}
	
	public function transformDocument(): bool {		
		// If the sendinblue contact is not found in the contact rule, we search in the coupon rule
		if ($this->ruleId == '6210fcbe4d654') { 	// Sendinblue - email delivered
			$transform = parent::transformDocument();			
			if ($transform) {
				return $transform;
			}
			// Refresh the error flag
			$this->transformError = false;
			
			// Change relationship if not found
			$keyRelParent = array_search('parent_id', array_column($this->ruleRelationships, 'field_name_target'));
			if ($keyRelParent === false) {
				throw new \Exception('Relatiobnship with contact id missing for this rule.');	
			}
			$this->ruleRelationships[$keyRelParent]['field_id'] = '620e5520c62d6';	// Sendinblue - coupon	
			
			$transform = parent::transformDocument();
			if ($transform) {
				// Save the email id linked to a coupon to change the parent type in function insertDataTable 
				$this->emailCoupon[$this->sourceData['messageId']] = true;
				return $transform;
			}
			
			// Refresh the error flag
			$this->transformError = false;
			// Change the relationship with the rule Sendinblue - contact search into COMET
			$this->ruleRelationships[$keyRelParent]['field_id'] = '64397bd8c4749';	// Sendinblue - contact search into COMET
			$transform = parent::transformDocument();
			if ($transform) {
				// Save the email id linked to a coupon to change the parent type in function insertDataTable 
				return $transform;
			}
			
			// Refresh the error flag
			$this->transformError = false;
			// Change the relationship with the rule Sendinblue - coupon search into COMET
			$this->ruleRelationships[$keyRelParent]['field_id'] = '64399ff31f587';	// Sendinblue - coupon search into COMET
			$this->emailCoupon[$this->sourceData['messageId']] = true;
				
		}
			
		return parent::transformDocument();
	}
	
	protected function insertDataTable($data, $type): bool {
		if ($this->ruleId == '6210fcbe4d654') { 	// Sendinblue - email delivered
			// Change parent type if email linked to a coupon
			if (!empty($this->emailCoupon[$data['sendinblue_msg_id_c']])) {
				$data['parent_type'] = 'Leads';
			}
		}
		return parent::insertDataTable($data, $type);
	}
	
	protected function getRelationshipDirection($ruleRelationship) {
		// When we change the relationship on the fly, we have to force the direction of the relationship
		if (
				$this->ruleRelationships[0]['rule_id'] == '6210fcbe4d654'	// Sendinblue - email delivered
			AND	(
					$this->ruleRelationships[0]['field_id'] == '64397bd8c4749'	// Sendinblue - contact search into COMET
				 OR $this->ruleRelationships[0]['field_id'] == '64399ff31f587'	// Sendinblue - coupon search into COMET
			)
		) {
			return '1';
		}
		return parent::getRelationshipDirection($ruleRelationship);
	}
	
	// Connect to the source or target application
    public function connexionSolution($type)
    {
        try {
            if ('source' == $type) {
                $connId = $this->document_data['conn_id_source'];
            } elseif ('target' == $type) {
                $connId = $this->document_data['conn_id_target'];
            } else {
                return false;
            }

            // Get the name of the application
            $sql = 'SELECT solution.name  
		    		FROM connector
						INNER JOIN solution 
							ON solution.id  = connector.sol_id
		    		WHERE connector.id = :connId';
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(':connId', $connId);
            $result = $stmt->executeQuery();
            $r = $result->fetchAssociative();
            // Get params connection
            $sql = 'SELECT id, conn_id, name, value
		    		FROM connectorparam 
		    		WHERE conn_id = :connId';
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(':connId', $connId);
            $result = $stmt->executeQuery();
            $tab_params = $result->fetchAllAssociative();
            $params = [];
            if (!empty($tab_params)) {
                foreach ($tab_params as $key => $value) {
                    $params[$value['name']] = $value['value'];
                    $params['ids'][$value['name']] = ['id' => $value['id'], 'conn_id' => $value['conn_id']];
                }
            }

            // Connect to the application
            if ('source' == $type) {
                $this->solutionSource = $this->solutionManager->get($r['name']);
                $this->solutionSource->setApi($this->api);
                $loginResult = $this->solutionSource->login($params);
                $c = (($this->solutionSource->connexion_valide) ? true : false);
            } else {
                $this->solutionTarget = $this->solutionManager->get($r['name']);
                $this->solutionTarget->setApi($this->api);
                $loginResult = $this->solutionTarget->login($params);
                $c = (($this->solutionTarget->connexion_valide) ? true : false);
            }
            if (!empty($loginResult['error'])) {
                return $loginResult;
            }

            return $c;
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');

            return false;
        }
    }

    public function updateType($new_type) {
		// Call standard
		parent::updateType($new_type);
		
		// DO NOT create binome with chatbot_c = non 
		if (
				$this->ruleId ==  '61a930273441b'
			AND $this->sourceData['chatbot_c'] == 'non'
			AND $new_type == 'C'
		) { 
			$this->message .= utf8_decode('La création de binome avec chatbot = non dans Airtable n\'est pas autorisée. Ce document est annulé.');
			$this->updateStatus('Cancel');
			$this->doNotOverrideStatus = true;
		}
	}

	// Set the quartier parameters
	public function setQuartierDocumentParams()
	{
		$param['rule']['id'] = '63481a0fd40a7';
		$param['fields'] = [
			'id',
			'name',
			'ville_c',
			'departement_c',
			'description',
			'externalgouvid_c',
			'quartier_prioritaire_c'
		];

		$param['jobId'] = $this->jobId;
		$param['api'] = $this->api;

		$param['offset'] = 0;
		$param['module'] = 'mod_2_quartiers';
		$param['ruleParams']['mode'] = '0';
		$param['rule']['id'] = $this->ruleId;
		$param['limit'] = 10000;
		$param['date_ref'] = '1970-01-01 00:00:00';
		$param['call_type'] = 'read';

		return $param;
	}

	// Permet de transformer les données source en données cibles
	public function getTargetDataDocument(): bool
	{
		if (!in_array($this->document_data['rule_id'],(array('63481a0fd40a7','63482d533bd4e')))) {
			return parent::getTargetDataDocument();
		}
	
		// rule should be internallist
		if ($this->document_data['rule_id'] == "63482d533bd4e") {
			//param should be empty to assign the custom params
			if (empty($param)) {
				$param['rule']['id'] = '63482d533bd4e';
				$param['fields'] = [
					'id',
					'name',
					'account_type',
					'billing_address_city',
					'billing_address_postalcode',
					'billing_address_street',
					'billing_address_street_2',
					'email1',
					'phone_office',
					'rep_c',
					'type_de_partenaire_c',
					'description',
					'externalgouvid_c',
				];

				$param['id_doc_myddleware'] = $this->id;
				// $param['solutionTarget'] = $this->solutionTarget;
				$param['ruleFields'] = $this->ruleFields;
				$param['ruleRelationships'] = $this->ruleRelationships;
				$param['jobId'] = $this->jobId;
				$param['api'] = $this->api;

				$param['offset'] = 0;
				$param['module'] = 'Accounts';
				$param['ruleParams']['mode'] = '0';
				$param['query']['type_de_partenaire_c'] = 'ecole_maternelle';
				$param['rule']['id'] = $this->ruleId;
				$param['limit'] = 10000;
				$param['date_ref'] = '1970-01-01 00:00:00';
				$param['call_type'] = 'read';
			}

			//call the full repository of partners
			if (empty($this->etabComet)) {
				$this->etabComet = $this->solutionTarget->read($param);
			}
			//end if filter my rule id
		} elseif ($this->document_data['rule_id'] == "63481a0fd40a7") { // Référentiel - Quartier
			// Assign params for current document
			if (empty($param)) {
				$param = $this->setQuartierDocumentParams();
			}

			//Recall connexion if invalid
			if ($this->solutionTarget->connexion_valide == false) {
				$this->connexionSolution('target');
			}

			//call the full repository of discricts if not already loaded in current instance
			if (empty($this->quartierComet)) {
				$this->quartierComet = $this->solutionTarget->read($param);
			}
		}
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

			//the following code is applied regardless of whether the document is update or create
			//however the type will be changed accordingly inside
			if (
				//todo, remove automatically C
				('U' == $this->documentType
					// or 'D' == $this->documentType
					or 'D' == $this->documentType || 'C' == $this->documentType
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
					if (!empty($param)) {
						if (
							$param['module'] == "Accounts" && !empty($param['rule']['id'])
							and $param['rule']['id'] == '63482d533bd4e'

						) {
							if (empty($this->solutionTarget)) {
								$this->connexionSolution('target');
							}
							// we do a custom search for the gouv id in the rows of the suiteCrm
							$findSuiteCrmId = "";
							foreach ($this->etabComet as $index => $suiteCrmSchool) {
								if (!empty($suiteCrmSchool['externalgouvid_c'])) {
									if ($this->sourceData['Identifiant_de_l_etablissement'] == $suiteCrmSchool['externalgouvid_c']) {
										$findSuiteCrmId = $suiteCrmSchool['id'];
										break;
									}
								}
							}
							if (!empty($findSuiteCrmId)) {
								$this->updateType('U');
								$this->updateTargetId($findSuiteCrmId);
							} else {
								//if we didn't find the exteralgouvid in the suiteCrm database it means that we have to either find the school
								// by name and other fields, or it doesn't exist at all and we need to create it
								$matchingrows = $this->findMatchCrmEtab();

								if (!empty($matchingrows)) {
									if (count($matchingrows) > 1) {
										krsort($matchingrows);
									}
									$this->updateType('U');
									$this->updateTargetId(reset($matchingrows));
								} else {
									$this->updateType('C');
								}
							}	// end else empty find gouv

						} elseif (
								$param['module'] == "mod_2_quartiers" 
							AND !empty($param['rule']['id'])
							AND $param['rule']['id'] == '63481a0fd40a7' // Référentiel - Quartier
						) {
							if (empty($this->solutionTarget)) {
								$this->connexionSolution('target');
							}
							// we do a custom search for the gouv id in the rows of the suiteCrm
							$findSuiteCrmId = "";
							foreach ($this->quartierComet as $index => $suiteCrmQuartier) {
								if (
										isset($suiteCrmQuartier['externalgouvid_c']) 
									AND !empty($suiteCrmQuartier['externalgouvid_c'])
								) {
									//codeQuartier intstead of etab
									if ($this->sourceData['Code_quartier'] == $suiteCrmQuartier['externalgouvid_c']) {
										$findSuiteCrmId = $suiteCrmQuartier['id'];
										break;
									}
								}
							}
							if (!empty($findSuiteCrmId)) {
								$this->updateType('U');
								$this->updateTargetId($findSuiteCrmId);
							} else {
								//if we didn't find the exteralgouvid in the suiteCrm database it means that we have to either find the school
								// by name and other fields, or it doesn't exist at all and we need to create it
								$matchingrows = $this->findMatchCrmQuartiers();

								if (!empty($matchingrows)) {
									if (count($matchingrows) > 1) {
										krsort($matchingrows);
									}
									$this->updateType('U');
									$this->updateTargetId(reset($matchingrows));
								}
							}	// end else empty find gouv
						}
					}
					return parent::getTargetDataDocument();
					// $this->updateStatus('Ready_to_send');
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
	} //end define getTargetDataDocument() method
}// end define class
