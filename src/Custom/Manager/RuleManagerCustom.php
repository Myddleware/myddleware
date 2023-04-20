<?php

namespace App\Custom\Manager;

use App\Manager\RuleManager;

class RuleManagerCustom extends RuleManager
{

	public function checkParentDocuments($documents = null): array
	{
		$responses = parent::checkParentDocuments($documents);

		// Specific code 
		// If relate_ko and rule binôme status is annule then we try to generate the missing contacts
		// It happends when a binôme isn't filtered anymore while the contacts has been previously filtered too. We have to force the contact generation
		if ($this->ruleId == '61a930273441b') {	//	Aiko binome
			foreach ($responses as $docId => $value) {
				// Empty if relate KO
				if (empty($value)) {
					$documentData = $this->getDocumentData($docId, 'S');
					if (
						!empty($documentData['MydCustRelSugarcrmc_binome_contacts_1contacts_ida'])
						and !empty($documentData['MydCustRelSugarcrmc_binome_contactscontacts_ida'])
					) {
						$this->generatePoleRelationship('61a920fae25c5', $documentData['MydCustRelSugarcrmc_binome_contacts_1contacts_ida'], 'id'); // Engagé - pole
						$this->generatePoleRelationship('61a920fae25c5', $documentData['MydCustRelSugarcrmc_binome_contactscontacts_ida'], 'id'); // Engagé - pole
					}
				}
			}
		}
		return $responses;
	}

	protected function sendTarget($type, $documentId = null): array
	{
		// Call standard function
		$responses = parent::sendTarget($type, $documentId);

		// List of rules with custom action
		$ruleList = array(
							'5ce3621156127', // Engagés
							'5d01a630c217c', // Contact composante
							'5ce362b962b63', // composante
							'5f847b2242138', // Etablissement sup 
							'5ce454613bb17', // Formation
							'5cf98651a17f3', // REEC - Users
							'61a920fae25c5', // Aiko - Contact 
							'61a930273441b', // Aiko Binomes
							'61a9190e40965', // Aiko Referent
							'620e5520c62d6', // Sendinblue - coupon
							'620d3e768e678', // Sendinblue - contact
							'6273905a05cb2', // Esp Rep - Contacts repérants
							'633d94b3ce61e', // Mobilisation - Participation RI -> comet relance
							'627153382dc34', // Mobilisation - Participations RI
							'625fcd2ed442f'  // Mobilisation - Coupons
					);
		// If no response or another rule, we don't do any custom action
		if (
			empty($responses)
			or !in_array($this->ruleId, $ruleList)
		) {
			return $responses;
		}


		// Custom actions
		foreach ($responses as $docId => $response) {
			// Get the type
			$documentData = $this->getDocumentHeader($docId);
			if (!empty($documentData['type'])) {
				$type = $documentData['type'];
			}
			
			// Get the source_id of the document 
			$document = array();
			$sql = "SELECT document.source_id FROM document WHERE id = '$docId'";
			$stmt = $this->connection->prepare($sql);
			// $stmt->execute();	    
			// $document = $stmt->fetch();
			$result = $stmt->executeQuery();
			$document = $result->fetchAssociative();

			if (
					!empty($response['id']) 
				AND	$response['id'] != '-1'
				AND !empty($document['source_id'])
				AND	$type == 'C' 
			) {
				/* // Code transfered to custom document class
				if (
					$this->ruleId == '5ce3621156127' //Engagés
				) {
					// Si un engagé est envoyé dans REEC, on recherche également son pôle
					// En effet quand un engagé est reconduit, on n'enverra pas son pôle qui est une données qui a été créée dans le passé 
					$this->generatePoleRelationship('5d081bd3e1234', $document['source_id'], 'record_id', true); // Engagé - pole
					// Si un engagé est envoyé dans REEC, on recherche également sa composante
					// En effet quand un engagé est envoyé dans REEC, il a peut être filtré avant et la relation avec la composante est donc filtrée aussi
					// On force donc la relance de la relation composante - Engagé à chaque fois qu'un engagé est modifié	
					$this->generatePoleRelationship('5f8486295b5a7', $document['source_id'], 'contact_id', true); // Composante - Engagé
				} */

				// Si un contact composante est envoyé dans REEC, on recherche également son pôle
				// En effet un contact composante dont on  ajoute un mail ne sera plus filtré donc sera envoyé dans REEC,
				// Cependant, dans ce cas, on n'enverra pas son pôle qui est une données qui a été créée dans le passé 
				if (
					$this->ruleId == '5d01a630c217c'	//Contact composante
				) { 
					$this->generatePoleRelationship('5d163d3c1d837', $document['source_id'], 'record_id', true);  // contact composante - pole
				}

				// Si une composante est envoyée dans REEC, on recherche également son pôle 
				// Utilisé quand un établissement (filtre de le règle) est ajouté à une composante
				if (
						$this->ruleId == '5ce362b962b63'	// composante
				) { 
					$this->generatePoleRelationship('5cfaca925116f', $document['source_id'], 'record_id', true);  // composante - pole
				}

				// Si un établissement est envoyée dans REEC, on recherche également son pôle
				if (
					$this->ruleId == '5f847b2242138' // Etablissement sup 
				) {
					$this->generatePoleRelationship('5f847d9927a10', $document['source_id'], 'record_id', true);  // Etablissement sup - pole
				}

				// Si une session de formation est envoyée dans REEC, on recherche également son pôle
				// Utilisé quand une formation change de type (filtre de le règle)
				if (
					$this->ruleId == '5ce454613bb17' // Formation
				) {
					$this->generatePoleRelationship('5d08e425e49ea', $document['source_id'], 'record_id', true);  // Formation - pôle
				}
				
				// If a coupon is created to Airtable, we send the pole relationship too
				// It fix a bug when the coupon has been removed from Airtable and créated again by Myddleware, the pole wasn't sent again
				if (
					$this->ruleId == '625fcd2ed442f' // Mobilisation - Coupons
				) {
					$this->generatePoleRelationship('626931ebbff78', $document['source_id'], 'record_id', true);  // Mobilisation - Relations pôles Coupons
				}
				
				// If a contact reperant is created to the espace reperant, we send the pole relationship too
				if (
					$this->ruleId == '6273905a05cb2' // Esp Rep - Contacts repérants
				) {
					$this->generatePoleRelationship('62743060350ed', $document['source_id'], 'record_id', true);  // Esp Rep - Contact repérant - Pôle
				}
			
				/* // If a users has been sent to REEC, we send the custom data too
				if (
					$this->ruleId == '5cf98651a17f3' // REEC - Users
				) {	
					$this->generatePoleRelationship('63e1007614977', $document['source_id'], 'id', true);  // 	REEC - Users custom
				} */

				/****************************************/
				/************** AirTable Aiko ****************/
				/****************************************/
				// Si un contact est envoyé dans REEC, on recherche également son pôle (seulement pour la migration)
				if (
					$this->ruleId == '61a920fae25c5' // Aiko - Contact 
				) {		
					$this->generatePoleRelationship('61a9329e6d6f2', $document['source_id'], 'record_id', true);  // Aiko Contact - pole
				}

				// Si un binome est envoyé dans REEC, on recherche également son pôle (seulement pour la migration)
				if (
					$this->ruleId == '61a930273441b' // Aiko Binomes 
				) {		
					$this->generatePoleRelationship('61a93469599ae', $document['source_id'], 'record_id', true);  // Aiko Binome - pole
				}

				// Si un référent est envoyé dans REEC, on recherche également son pôle (seulement pour la migration)	
				if (
					$this->ruleId == '61a9190e40965' // Aiko Referent
				) {			
					$this->generatePoleRelationship('61b7662e60774', $document['source_id'], 'user_id', true);  // Aiko Referent(user) - pole
				}
				
				// If the coupon is re invited then we set the statuts RDV pris in COMET	
				if (
					$this->ruleId == '633d94b3ce61e' // Mobilisation - Participation RI -> comet relance
				) {			
					$targetData = $this->getDocumentData($docId, 'T');
					$this->generatePoleRelationship('633ef1ecf11db', $document['source_id'], 'id', true, array('values' => array('id' => $targetData['fp_events_leads_1leads_idb'], 'date_modified' => gmdate('Y-m-d H:i:s'))));  // Mobilisation - relance rdv pris -> comet
				}
			// In case of UPDATE
			} elseif (
					!empty($response['id']) 
				AND	$response['id'] != '-1'
				AND !empty($document['source_id'])
				AND	$type == 'U' 
			) {
				/* // If a users has been sent to REEC, we send the custom data too
				if (
					$this->ruleId == '5cf98651a17f3' // REEC - Users
				) {
					$this->generatePoleRelationship('63e1007614977', $document['source_id'], 'id', true);  // 	REEC - Users custom
				} */
			} 
			
			// In case of error (Create or Update)
			if (
					!empty($response['id']) 
				AND	$response['id'] == '-1'
				AND !empty($document['source_id'])
			) {
				if (
						in_array($this->ruleId, array('620e5520c62d6','620d3e768e678')) // Sendinblue - coupon / contact
					AND	$type != 'D' // No document generated after a deletion
					AND	strpos($response['error'], 'Invalid phone number') !== false
				) {		
					// Use function generatePoleRelationship to generate a document that send the info invalide phone number to COMET
					if ($this->ruleId == '620e5520c62d6') { // Sendinblue - coupon
						$this->generatePoleRelationship('630684804e98c', $document['source_id'], 'id', true);  // Sendinblue - coupon invalid phone
					} else {	// Sendinblue - contact
						$this->generatePoleRelationship('63075042095e8', $document['source_id'], 'id', true);  // Sendinblue - contact invalid phone
					}
					// We cancel this doc because the modification to COMET will generate another document without invalid phone number
					$this->changeStatus($docId, 'Cancel', 'Telephone invalide. Myddleware va notifier la COMET et effacer ce numéro invalide. ');
				}			
				// If there is an "Unprocessable Entity" errro when we try to create/update a binome for the first time
				// Then we try to send again both contacts and referent
				if (
						$this->ruleId == '61a930273441b' 	// 	Aiko binome
					AND	(
							$documentData['attempt'] == 1 		// Only the first try
						 OR !empty($this->manual)				// Or manual run
					) 
					AND	(
							strpos($response['error'], 'Unprocessable Entity returned') !== false
						 OR	strpos($response['error'], 'HTTP/2 422') !== false
					)
				) {	
					$sourceData = $this->getDocumentData($docId, 'S');
					if (!empty($sourceData['MydCustRelSugarcrmc_binome_contacts_1contacts_ida'])) { // Mentoré
						$this->generatePoleRelationship('61a920fae25c5', $sourceData['MydCustRelSugarcrmc_binome_contacts_1contacts_ida'], 'id', true);  // Aiko contact
					}
					if (!empty($sourceData['MydCustRelSugarcrmc_binome_contactscontacts_ida'])) { // Mentor
						$this->generatePoleRelationship('61a920fae25c5', $sourceData['MydCustRelSugarcrmc_binome_contactscontacts_ida'], 'id', true);  // Aiko contact
					}
					if (!empty($sourceData['assigned_user_id'])) { // Referent
						$this->generatePoleRelationship('61a9190e40965', $sourceData['assigned_user_id'], 'id', true);  // Aiko Référent
					}
					// Set back the status to predecessor OK and remove target data to allow Myddleware to recalcultae thetarget data with the new records sent
					$deleteTargetData = $this->deleteDocumentData($docId, 'T');
					if ($deleteTargetData) {
						$this->changeStatus($docId, 'Predecessor_OK', 'Les données liees ont ete relancees car l\'une d\'entre elle doit être supprimee dans Airtable. ');
					}
				}
				
				// If there is an "nprocessable Entity" errro when we try to create a particpation RI for the first time
				// Then we try to send again both Coupon and Participation RI
				if (
						$this->ruleId == '627153382dc34' 	// Mobilisation - Participations RI
					AND	(
							$documentData['attempt'] == 1 		// Only the first try
						 OR !empty($this->manual)				// Or manual run
					) 
					AND	(
							strpos($response['error'], 'Unprocessable Entity returned') !== false
						OR	strpos($response['error'], 'HTTP/2 422') !== false
					)
				) {	
					$sourceData = $this->getDocumentData($docId, 'S');
					if (!empty($sourceData['fp_events_leads_1leads_idb'])) { // Coupon
						$this->generatePoleRelationship('625fcd2ed442f', $sourceData['fp_events_leads_1leads_idb'], 'id', true);  // Mobilisation - Coupons
					}
					if (!empty($sourceData['fp_events_leads_1fp_events_ida'])) { // Mobilisation - Evenement RI
						$this->generatePoleRelationship('6267e128b2c87', $sourceData['fp_events_leads_1fp_events_ida'], 'id', true);  // Mobilisation - Coupons
					}
					// Set back the status to predecessor OK and remove target data to allow Myddleware to recalcultae thetarget data with the new records sent
					$deleteTargetData = $this->deleteDocumentData($docId, 'T');
					if ($deleteTargetData) {
						$this->changeStatus($docId, 'Predecessor_OK', 'Les données liees ont ete relancees car l\'une d\'entre elle doit être supprimee dans Airtable. ');
					}
				}
			}
		}
		return $responses;
	}

	protected function generatePoleRelationship($rulePole, $searchValue, $searchField = 'record_id', $rerun = true, $values = false) {
		try {		
			// Instantiate the rule
			$ruleRel = new RuleManager($this->logger, $this->connection, $this->entityManager, $this->parameterBagInterface, $this->formulaManager, $this->solutionManager, $this->documentManager);
			$ruleRel->setRule($rulePole);
			$ruleRel->setJobId($this->jobId);

			// Cherche tous les pôles de l'enregistrement correspondant à la règle
			$documents = $ruleRel->generateDocuments($searchValue, (empty($values) ? true : false), $values, $searchField); 				
			if (!empty($documents->error)) {					
				throw new \Exception($documents->error);
			}
			// Run documents
			if (
				!empty($documents)
				and $rerun
			) {
				foreach ($documents as $doc) {
					$errors = $ruleRel->actionDocument($doc->id, 'rerun');
					// Check errors
					if (!empty($errors)) {
						$doc->setMessage(' Document ' . $doc->id . ' in error (rule ' . $rulePole . '  : ' . $errors[0] . '. ');
					}
				}
			}
		} catch (\Exception $e) {
			$this->logger->error('Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
		}
	}

	public function getTargetDataDocuments($documents = null): array
	{
		// include_once 'document.php';

		// Permet de charger dans la classe toutes les relations de la règle
		$response = [];

		// Sélection de tous les docuements de la règle au statut 'New' si aucun document n'est en paramètre
		if (empty($documents)) {
			$documents = $this->selectDocuments('Transformed');
		}

		if (!empty($documents)) {
			// Connexion à la solution cible pour rechercher les données
			$this->connexionSolution('target');
			$this->connection->beginTransaction(); // -- BEGIN TRANSACTION suspend auto-commit
			try {
				$i = 0;
				// Récupération de toutes les données dans la cible pour chaque document
				foreach ($documents as $document) {
					if ($i >= $this->limitReadCommit) {
						$this->commit(true); // -- COMMIT TRANSACTION
						$i = 0;
					}
					++$i;
					$param['id_doc_myddleware'] = $document['id'];
					$param['solutionTarget'] = $this->solutionTarget;
					$param['ruleFields'] = $this->ruleFields;
					$param['ruleRelationships'] = $this->ruleRelationships;
					$param['jobId'] = $this->jobId;
					$param['api'] = $this->api;
					// Set the param values and clear all document attributes
					$this->documentManager->setParam($param, true);
					$response[$document['id']] = $this->documentManager->getTargetDataDocument();
					$response['doc_status'] = $this->documentManager->getStatus();
				}
				$this->commit(false); // -- COMMIT TRANSACTION
			} catch (\Exception $e) {
				$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
				$this->logger->error('Failed to create documents : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
				$readSource['error'] = 'Failed to create documents : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
			}
		}

		return $response;
	}
}
