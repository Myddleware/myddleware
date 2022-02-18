<?php
namespace App\Custom\Manager;

use App\Manager\DocumentManager;
use App\Manager\ruleManager;

class DocumentManagerCustom extends DocumentManager {
	
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
	
	protected function beforeStatusChange($new_status) {	
		
		// On annule la relation pôle - contact (user) si le contact (user) a été filtré
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '5cfa78d49c536' // Rule User - Pôle
		) {
			if (
					strpos($this->message, 'No data for the field user_id.') !== false
				AND strpos($this->message, 'in the rule Users.') !== false	
			) {	
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact (user) lié à ce pôle est absent de la platforme REEC, probablement filtré car inactif. Le lien contact - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. '); 
			}
		}
	
		// On annule la relation pôle - contact (engagé) si le contact (engagé) a été filtré
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '5d081bd3e1234' // Rule User - Pôle
		) {
			if (
					strpos($this->message, 'No data for the field record_id.') !== false
				AND strpos($this->message, 'in the rule Engagé.') !== false	
			) {	
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme REEC ou n\'est pas un contact de type engagé. Le lien contact - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. '); 
			}
		}

		// On annule la relation pôle - contact (engagé) si le contact (engagé) a été filtré
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '5d163d3c1d837' // Rule Contact composante - Pôle
		) {			
			if (
					strpos($this->message, 'No data for the field record_id.') !== false
				AND strpos($this->message, 'in the rule Contact - Composante.') !== false	
			) {				
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme REEC ou n\'est pas un contact de type contact partenaire. Le lien contact - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. '); 
			}
		}
	
		if (
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
		}
		
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '5d163d3c1d837' // Rule Contact composante - Pôle
		) {
			if (
					strpos($this->message, 'No data for the field record_id.') !== false
				AND strpos($this->message, 'in the rule Contact - Composante.') !== false	
			) {		
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact composante est absent de la platforme REEC, il s\'agit probablement d\'une composante sans adresse email. Le lien Contact composante - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. '); 
			}
		}
		
		// On annule tous les transferts de données en relate ko pour la règle composante - Contact composante
		// En effet des la majorité des relations accounts_contacts ne sont pas des composante - Contact composante
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '5f20b113356e1' // Rule Composante - Contact composante
			AND $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('La relation ne concerne probablement pas une composante et un contact composante. Ce transfert de données est annulé. '); 
		}
		
		// On annule tous les transferts de données en relate ko pour la règle composante - Engagé
		// En effet une partie des relations accounts_contacts ne sont pas des composante - Engagé
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '5f8486295b5a7' // Rule composante - Engagé
			AND $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('La relation ne concerne probablement pas une composante et un contact composante. Ce transfert de données est annulé. '); 
		}
		
		// Si on est sur une suppression d'une composante, le document est souvent filtré car la composante supprimé n'a plus d'établissment supérieur lié
		// La suppression est alors annulée. On souhaite supprimer quand même la données si elle a été envoyée par Myddleware
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '5ce362b962b63' // Rule composante
			AND	$this->document_data['type'] == 'D' // Delete
			AND $new_status == 'Filter'
		) {
			$new_status = 'Filter_OK';
			$this->message .= utf8_decode('Aucun filtrage appliqué sur la suppression d une composante. Cette composante doit réellement être supprimée dans REEC même si elle n a plus d établissement supérieur dans la COMET. '); 
		}


		/************************************************/
		/************         AIKO         **************/
		/************************************************/
		// If relate_ko and binôme status is annule then we cancel the data transfer
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '61a930273441b' // Rule Aiko binome
			AND $new_status == 'Relate_KO'
			AND $this->sourceData['statut_c'] == 'annule'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le statut du binôme est annulé. Ce transfert de données est annulé. '); 
		}
		
		// If relate_ko on rule Aiko binome - pole then we cancel the data transfer
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '61a93469599ae' // Rule Aiko binome - pole
			AND $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Les anciens binômes et les binômes annulés ne sont pas envoyés dans Airtable, la relation pôle tombe logiquement en relate_KO. Ce transfert de données est annulé. '); 
		}
		
		// If relate_ko on rule Aiko contact - pole then we cancel the data transfer
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '61a9329e6d6f2' // Rule Aiko contact - pole
			AND $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Les contacts partenaires ne sont pas envoyés dans Airtable, la relation pôle tombe logiquement en relate_KO. Ce transfert de données est annulé. ');
		}
		
		return $new_status;
	}
	
	public function updateStatus($new_status) {
		// Add error expected status
		$this->globalStatus['Error_expected'] = 'Cancel';
		
		// Cancel data transfert as the rule Aiko - Suppression generates document into other rules
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '61bb49a310715' // Aiko - Suppression
			AND	$new_status == 'Predecessor_OK'
		) {			
			$new_status = 'Cancel';
		}

		$updateStatus = parent::updateStatus($new_status); 
		
		return $updateStatus;
	}
	
} 

