<?php
namespace App\Custom\Manager;

use App\Manager\DocumentManager;
use App\Manager\ruleManager;

class DocumentManagerCustom extends DocumentManager {
	
	protected $emailCoupon = array();
	protected $toBeCancel = array();
	
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
			AND	$this->document_data['rule_id'] == '5cfa78d49c536' // Rule User - Pôle
		) {
			if (
					strpos($this->message, 'No data for the field user_id.') !== false
				AND strpos($this->message, 'in the rule REEC - Users.') !== false	
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
				AND strpos($this->message, 'in the rule REEC - Engagé.') !== false	
			) {	
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme REEC ou n\'est pas un contact de type engagé. Le lien contact - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. '); 
			}
		}

		// On annule la relation pôle - contact (université) si le contact (université) a été filtré
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '5d163d3c1d837' // Rule Contact composante - Pôle
		) {			
			if (
					strpos($this->message, 'No data for the field record_id.') !== false
				AND strpos($this->message, 'in the rule REEC - Contact - Composante.') !== false	
			) {				
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme REEC ou n\'est pas un contact de type contact université. Le lien contact - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. '); 
			}
		}
			
		// We cancel the relation Contact repérant - Pôle if he has been filtered
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '62743060350ed' // Esp Rep - Contact repérant - Pôle
		) {			
			if (
					strpos($this->message, 'No data for the field record_id.') !== false
				AND strpos($this->message, 'in the rule Esp Rep - Contacts rep') !== false	
			) {				
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme epace repérant ou n\'est pas un contact de type contact repérant. Le lien contact - pôle ne sera donc pas créé dans l\'espace repérant. Ce transfert de données est annulé. '); 
			}
		}
		
		// If we don't found the contact (COMET) in the coupon (REEC), we cancel the data transfer. 
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '6273b3b11c63e' // Esp Rep - Relation Contacts Coupons
			AND $new_status == 'Not_found'
		) {			
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le mentoré n\a pas été trouvé sur un coupon dans l\'epace repérant. Ce transfert de données est annulé. '); 
		}
		
		// If we don't found the coupon (REEC) corresponding to the contact (COMET), we cancel the data transfer. 
		if (
				!empty($this->document_data['rule_id'])
			AND	in_array($this->document_data['rule_id'], array('6274428910b18','62744b95de96f')) // Esp Rep - Fiche évaluation fin vers Esp Rep
			AND $new_status == 'Relate_KO'
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
			AND	in_array($this->document_data['rule_id'], array('628cdd961b093')) // Esp Rep - Coupon - Pôles
			AND $new_status == 'Relate_KO'
		) {			
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le coupon de la relation pole - coupon n\'a pas été trouvé. Il s\'agit probablement d\'un coupon non mentoré. Ce transfert de données est annulé. '); 
		}
		
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '5d163d3c1d837' // Rule Contact composante - Pôle
		) {
			if (
					strpos($this->message, 'No data for the field record_id.') !== false
				AND strpos($this->message, 'in the rule REEC - Contact - Composante.') !== false	
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
		
		// On annule tous les transferts de données en relate ko pour la règle composante - Contact partenaire
		// En effet des la majorité des relations accounts_contacts ne sont pas des composante - Contact partenaire
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '62790c7db0a87' // Esp Rep - Composante - Contact partenaire
			AND $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('La relation ne concerne probablement pas une composante et un contact partenaire. Ce transfert de données est annulé. '); 
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

		// No error if the coupon doesn't exist in REEC (no update in this case)
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '62739b419755f' // Esp Rep - Coupons vers Esp Rep
			AND $new_status == 'Relate_KO'
		) {
			if (
					strpos($this->message, 'No data for the field Myddleware_element_id.') !== false
				AND strpos($this->message, ' in the rule REEC - Coupons vers comet.') !== false	
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
		
		// If relate_OK and binôme status is one of these status : termine;annule;accompagnement_termine
		// And if the document type is a creation then we cancel the data transfer
		// However if it is an update we keep the document to set the new status in Airtable (and generate a deletion during the next call)
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '61a930273441b' // Rule Aiko binome
			AND $new_status == 'Predecessor_OK'
			AND in_array($this->sourceData['statut_c'], array('termine','annule','accompagnement_termine'))
			AND	$this->documentType == 'C' // Creation
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le statut du binôme est annulé ou terminé et le document genère une création donc on annule l envoi vers Airtable. '); 
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
		
		// Cancel if the doc is related KO and the email linked to a user (afev.org)
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '6210fcbe4d654' // Sendinblue - email delivered
			AND $new_status == 'Relate_KO'
			AND strpos($this->sourceData['email'], '@afev.org') !== false
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('L\email n\'appartient pas à un contact dans la COMET mais à un salarié (domaine afev.org). Ce transfert de données est annulé. ');
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
				in_array($this->document_data['conn_id_target'], array(4,8)) // Airtable connectors
			AND $new_status == 'Error_checking'
			AND	$this->documentType == 'D' // Deletion
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('L\'enregistrement est certainement déjà supprimé dans Airtable. Ce transfert de données est annulé. ');
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
	
	public function ckeckParentDocument() {
		try {				
			// If the sendinblue contact is not found in the contact rule, we search in the coupon rule
			if ($this->ruleId == '6210fcbe4d654') { 	// Sendinblue - email delivered
				$chekParent = parent::ckeckParentDocument();	
				if ($chekParent) {
					return $chekParent;
				}
				// Change relationship if not found
				$keyRelParent = array_search('parent_id', array_column($this->ruleRelationships, 'field_name_target'));
				if ($keyRelParent === false) {
					throw new \Exception('Relatiobnship with contact id missing for this rule.');	
				}
				$this->ruleRelationships[$keyRelParent]['field_id'] = '620e5520c62d6';	// Sendinblue - coupon	
			}
		} catch (\Exception $e) {
			$this->message .= 'Failed to check document related : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->typeError = 'E';
			$this->updateStatus('Relate_KO');
			$this->logger->error($this->message);
			return false;
		}	
		$chekParent = parent::ckeckParentDocument();

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
					parent::ckeckParentDocument();
				}
			}
		}
			
		return $chekParent;
	}
	
	public function transformDocument() {				
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
			// Save the email id linked to a coupon to change the parent type in function insertDataTable 
			$this->emailCoupon[$this->sourceData['messageId']] = true;
		}
			
		$transform = parent::transformDocument();
		return $transform;
	}
	
	protected function insertDataTable($data, $type) {
		if ($this->ruleId == '6210fcbe4d654') { 	// Sendinblue - email delivered
			// Change parent type if email linked to a coupon
			if (!empty($this->emailCoupon[$data['sendinblue_msg_id_c']])) {
				$data['parent_type'] = 'Leads';
			}
		}
		return parent::insertDataTable($data, $type);
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
} 

