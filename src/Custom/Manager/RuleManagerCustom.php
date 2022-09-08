<?php

namespace App\Custom\Manager;

use App\Manager\RuleManager;

class RuleManagerCustom extends RuleManager
{

	public function ckeckParentDocuments($documents = null)
	{
		$responses = parent::ckeckParentDocuments($documents);

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

	protected function sendTarget($type, $documentId = null)
	{
		// Call standard function
		$toto = "\n 2 sendTarget() in RuleManagerCustom \n";
		echo $toto;
		$responses = parent::sendTarget($type, $documentId);

		// List of rules with custom action
		$ruleList = array(
			'5ce3621156127', //Engagés
			'5d01a630c217c', //Contact composante
			'5ce362b962b63', // composante
			'5f847b2242138', // Etablissement sup 
			'5ce454613bb17', // Formation
			'61a920fae25c5', // Aiko - Contact 
			'61a930273441b', // Aiko Binomes
			'61a9190e40965' // Aiko Referent
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
				and	$response['id'] != '-1'
				and !empty($document['source_id'])
			) {
				// Si un engagé est envoyé dans REEC, on recherche également son pôle
				// En effet quand un engagé est reconduit, on n'enverra pas son pôle qui est une données qui a été créée dans le passé 
				if ($this->ruleId == '5ce3621156127') { //Engagés
					$this->generatePoleRelationship('5d081bd3e1234', $document['source_id'], 'record_id', false); // Engagé - pole
				}

				// Si un engagé est envoyé dans REEC, on recherche également sa composante
				// En effet quand un engagé est envoyé dans REEC, il a peut être filtré avant et la relation avec la composante est donc filtrée aussi
				// On force donc la relance de la relation composante - Engagé à chaque fois qu'un engagé est modifié	
				if ($this->ruleId == '5ce3621156127') { //Engagés
					$this->generatePoleRelationship('5f8486295b5a7', $document['source_id'], 'contact_id', false); // Composante - Engagé
				}

				// Si un contact composante est envoyé dans REEC, on recherche également son pôle
				// En effet un contact composante dont on  ajoute un mail ne sera plus filtré donc sera envoyé dans REEC,
				// Cependant, dans ce cas, on n'enverra pas son pôle qui est une données qui a été créée dans le passé 
				if ($this->ruleId == '5d01a630c217c') { //Contact composante
					$this->generatePoleRelationship('5d163d3c1d837', $document['source_id'], 'record_id', false);  // contact composante - pole
				}

				// Si une composante est envoyée dans REEC, on recherche également son pôle 
				// Utilisé quand un établissement (filtre de le règle) est ajouté à une composante
				if ($this->ruleId == '5ce362b962b63') { // composante
					$this->generatePoleRelationship('5cfaca925116f', $document['source_id'], 'record_id', false);  // composante - pole
				}

				// Si un établissement est envoyée dans REEC, on recherche également son pôle
				if (
					$this->ruleId == '5f847b2242138' // Etablissement sup 
					and $type != 'U' // Seulement en modif car en création les 2 documents se bloquent
					and	$type != 'D' // Seulement en modif car en création les 2 documents se bloquent
				) {
					$this->generatePoleRelationship('5f847d9927a10', $document['source_id'], 'record_id', false);  // Etablissement sup - pole
				}

				// Si une session de formation est envoyée dans REEC, on recherche également son pôle
				// Utilisé quand une formation change de type (filtre de le règle)
				if (
					$this->ruleId == '5ce454613bb17' // Formation
					and	$type != 'D'
				) {
					$this->generatePoleRelationship('5d08e425e49ea', $document['source_id'], 'record_id', false);  // Formation - pôle
				}

				/****************************************/
				/************** AirTable Aiko ****************/
				/****************************************/
				// Si un contact est envoyé dans REEC, on recherche également son pôle (seulement pour la migration)
				if (
					$this->ruleId == '61a920fae25c5' // Aiko - Contact 
					and	$type != 'D' // No document generated after a deletion
				) {
					$this->generatePoleRelationship('61a9329e6d6f2', $document['source_id'], 'record_id', false);  // Aiko Contact - pole
				}

				// Si un binome est envoyé dans REEC, on recherche également son pôle (seulement pour la migration)
				if (
					$this->ruleId == '61a930273441b' // Aiko Binomes
					and	$type != 'D' // No document generated after a deletion
				) {
					$this->generatePoleRelationship('61a93469599ae', $document['source_id'], 'record_id', false);  // Aiko Binome - pole
				}

				// Si un référent est envoyé dans REEC, on recherche également son pôle (seulement pour la migration)	
				if (
					$this->ruleId == '61a9190e40965' // Aiko Referent
					and	$type != 'D' // No document generated after a deletion
				) {
					$this->generatePoleRelationship('61b7662e60774', $document['source_id'], 'user_id', false);  // Aiko Referent(user) - pole
				}
			}
		}
		return $responses;
	}

	protected function generatePoleRelationship($rulePole, $searchValue, $searchField = 'record_id', $rerun = true)
	{
		try {
			// Instantiate the rule
			$ruleRel = new RuleManager($this->logger, $this->connection, $this->entityManager, $this->parameterBagInterface, $this->formulaManager, $this->solutionManager, $this->documentManager);
			$ruleRel->setRule($rulePole);
			$ruleRel->setJobId($this->jobId);

			// Cherche tous les pôles de l'enregistrement correspondant à la règle
			$documents = $ruleRel->generateDocuments($searchValue, true, '', $searchField);
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

	public function getTargetDataDocuments($documents = null)
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
