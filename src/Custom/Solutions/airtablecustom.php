<?php

namespace App\Custom\Solutions;

use App\Solutions\airtable;
use Myddleware\RegleBundle\Classes\rule;
use App\Manager\DocumentManager;

class airtablecustom extends airtable {

	protected array $tableName = array(
								'appdKFUpk2X2Ok8Dc' => 'Contacts',
								'appX0PhUGIkBTcWBE' => 'Aiko Auto Supr',
								'apppq0nb5WI815V57' => 'Reponse',			// Aiko PREPROD Reponse
								'app5ustIjI5taRXJS' => 'CONTACTS',		// Mobilisation PROD
								'appP31F11PgaT1f6H' => 'CONTACTS',		// Mobilisation PREPROD
								'appALljzTMc2wjLV1' => 'VSC',			// USC PROD
								'appuC7nsCbe7TxqwK' => 'VSC',			// USC PREPROD
							);

	protected $modules = array(
								'appdKFUpk2X2Ok8Dc' => array(
														'CONTACTS' =>	'CONTACTS',
														'BINOMES' =>	'BINOMES',
														'POLE' => 		'POLES',
														'REFERENTS' => 	'REFERENTS',
														'REPONSE' => 	'REPONSE',
													),
								'appX0PhUGIkBTcWBE' =>  array(
														'Aiko Auto Supr' => 'Aiko Auto Supr'
													),
								'app5ustIjI5taRXJS' => array(
														'COUPONS' => 'COUPONS',
														'CONTACTS' => 'CONTACTS',
														'Relation_POLE' => 'Relation POLE',
														'COMPOSANTES' => 'COMPOSANTES',
														'ETABLISSEMENTS' => 'ETABLISSEMENTS',
														'EVENEMENTS' => 'EVENEMENTS',
														'POLES' => 'POLES',
														'UTILISATEURS' => 'UTILISATEURS',
														'PARTICIPATION_RI' => 'PARTICIPATION RI',
														'RECONDUCTION' => 'RECONDUCTION',
														'RENDEZ-VOUS' => 'RENDEZ-VOUS'
													),
								'appP31F11PgaT1f6H' => array(
														'COUPONS' => 'COUPONS',
														'CONTACTS' => 'CONTACTS',
														'Relation_POLE' => 'Relation POLE',
														'COMPOSANTES' => 'COMPOSANTES',
														'ETABLISSEMENTS' => 'ETABLISSEMENTS',
														'EVENEMENTS' => 'EVENEMENTS',
														'POLES' => 'POLES',
														'UTILISATEURS' => 'UTILISATEURS',
														'PARTICIPATION_RI' => 'PARTICIPATION RI',
														'RECONDUCTION' => 'RECONDUCTION',
														'RENDEZ-VOUS' => 'RENDEZ-VOUS'
													),
								'appALljzTMc2wjLV1' => array(
														'VSC' => 'VSC'
													),
								'appuC7nsCbe7TxqwK' => array(
														'VSC' => 'VSC'
													),
							);

    protected $FieldsDuplicate = array(
        'CONTACTS' => array('fldXhleTPZRv0zBbd'),
        'BINOMES' => array('fldpdbxLe9B1H2i2J'),
        'POLE' => array('fldxWO5Cs8t9z7ZP8'),
        'REFERENTS' => array('fldLt1pZEcUxKlTpH'),
        'COMPOSANTES' => array('fld0FmpZqG5wJFrCP'),
		'PARTICIPATION_RI' => array('fldL4qph2Lg65xKjz', 'fldtIpKCdlbykhkm5'),
		'Relation_POLE' => array('fldNHqlGf5PJhYCMN', 'fldWsjwPo27DVlYMy'),
		'VSC' => array('fldTpnnN8XfbLHADM')
        );


	// Rededine read fucntion
	public function readData($param): array {
		$result = parent::readData($param);

		// if the rule id is 645b827fb6151, we handle the conversion of the emoji to a format that will be compatible with the database encoding which is utf8_general_ci
		if (($param["rule"]["id"] === '645b827fb6151' || $_POST["params"][1]["value"] === '645b827fb6151') && $param['module'] == 'REPONSE') {
			if (!empty($result['values'])) {
				foreach ($result['values'] as $docId => $values) {
					if (!empty($values['fldC7m6zch8Cz6KWQ'])) {
						// $result['values'][$docId]['fldC7m6zch8Cz6KWQ'] = '';
						// We make a switch case for the value of the field, if it is the 😡, 🙁, 😐, 🙂, 😍,  emoji then we convert it to the string respectively ">:(",
						//  ":(",  
						//  ":|",
						// ":)",
						// "<3"
						switch ($values['fldC7m6zch8Cz6KWQ']) {
							case '😡':
								$result['values'][$docId]['fldC7m6zch8Cz6KWQ'] = 1;
								break;
							case '🙁':
								$result['values'][$docId]['fldC7m6zch8Cz6KWQ'] = 2;
								break;
							case '😐':
								$result['values'][$docId]['fldC7m6zch8Cz6KWQ'] = 3;
								break;
							case '🙂':
								$result['values'][$docId]['fldC7m6zch8Cz6KWQ'] = 4;
								break;
							case '😍':
								$result['values'][$docId]['fldC7m6zch8Cz6KWQ'] = 5;
								break;
							default:
								$result['values'][$docId]['fldC7m6zch8Cz6KWQ'] = '';
						}
					}
				}
			}
		}
		// if the rule id is 645b827fb6151, we handle the conversion of the emoji to a format that will be compatible with the database encoding which is utf8_general_ci
		if (($param["rule"]["id"] === '645b827fb6151' || $_POST["params"][1]["value"] === '645b827fb6151') && $param['module'] == 'REPONSE') {
			if (!empty($result['values'])) {
				foreach ($result['values'] as $docId => $values) {
					if (!empty($values['fld4KzcfmV2P8F3E6'])) {
						// $result['values'][$docId]['fld4KzcfmV2P8F3E6'] = '';
						// We make a switch case for the value of the field, if it is the ⭐️, ⭐️⭐️, ⭐️⭐️⭐️, ⭐️⭐️⭐️⭐️, ⭐️⭐️⭐️⭐️⭐️,  emoji then we convert it to the string respectively ">:(",
						//  ":(",  
						//  ":|",
						// ":)",
						// "<3"
						switch ($values['fld4KzcfmV2P8F3E6']) {
							case '⭐️':
								$result['values'][$docId]['fld4KzcfmV2P8F3E6'] = 1;
								break;
							case '⭐️⭐️':
								$result['values'][$docId]['fld4KzcfmV2P8F3E6'] = 2;
								break;
							case '⭐️⭐️⭐️':
								$result['values'][$docId]['fld4KzcfmV2P8F3E6'] = 3;
								break;
							case '⭐️⭐️⭐️⭐️':
								$result['values'][$docId]['fld4KzcfmV2P8F3E6'] = 4;
								break;
							case '⭐️⭐️⭐️⭐️⭐️':
								$result['values'][$docId]['fld4KzcfmV2P8F3E6'] = 5;
								break;
							default:
								$result['values'][$docId]['fld4KzcfmV2P8F3E6'] = '';
						}
					}
				}
			}
		}

		// If we send an update to Airtable but if the data doesn't exist anymore into Airtable, we change the upadet to a creation
		if  (
				!empty($param['rule'])
			AND	in_array($param['rule']['conn_id_target'], array(4,8))
			AND $param['document']['type'] == 'U'
			AND $param['call_type'] == 'history'
			AND !empty($result['error'])
			AND (
					strpos($result['error'], '404 Not Found')
				 OR strpos($result['error'], '404  returned')	// Airtable has changed the error message
			)
		) {
			// Change the document type 
			$documentManager = new DocumentManager(
										$this->logger, 
										$this->connection, 
										$this->entityManager,
										$this->documentRepository,
										$this->ruleRelationshipsRepository,
										$this->formulaManager
									);
			$paramDoc['id_doc_myddleware'] = $param['document']['id'];
			$paramDoc['jobId'] = $param['jobId'];
			$documentManager->setParam($paramDoc);
			// Add a log
			$documentManager->generateDocLog('W','La donnee a ete supprimee dans Airtable. Le type de document passe donc de Update a Create. ');
			// Set the create type to the document
			$documentManager->updateType('C');
			// Clear the error
			$result['error'] = '';
		}
		
		/* Aiko - Suppression isn't used anymore
		if ($param['rule']['id'] == '61bb49a310715') {	// Aiko - Suppression
			if (!empty($result['values'])) {
				foreach ($result['values'] as $docId => $values) {
					$deletionParam = array();
					$ruleId = '';
					switch ($values['SyncSource']):
						case 'Aiko - Binome':
							$ruleId = '61a930273441b';
							$deletionParam['values']['statut_c'] = 'suppr'; 
							$deletionParam['values']['chatbot_c'] = 'suppr'; 
							$deletionParam['values']['fin'] = ''; 
							$deletionParam['values']['mise_en_place_c'] = ''; 
							$deletionParam['values']['name'] = ''; 
							$deletionParam['values']['annee_scolaire_c'] = ''; 
							$deletionParam['values']['deleted'] = ''; 
							$deletionParam['values']['heure_babituelle_rencontre_c'] = ''; 
							$deletionParam['values']['jour_habituel_rencontre_c'] = ''; 
							$deletionParam['values']['lieu_habituel_rencontre_c'] = ''; 
							$deletionParam['values']['precision_lieu_c'] = ''; 
							break;
						case 'Aiko - Contact':
							$ruleId = '61a920fae25c5';
							$deletionParam['values']['aiko'] = '1'; 
							$deletionParam['values']['contact_type_c'] = ''; 
							$deletionParam['values']['salutation'] = ''; 
							$deletionParam['values']['birthdate'] = ''; 
							$deletionParam['values']['email1'] = ''; 
							$deletionParam['values']['phone_mobile'] = ''; 
							$deletionParam['values']['last_name'] = ''; 
							$deletionParam['values']['first_name'] = ''; 
							$deletionParam['values']['deleted'] = ''; 
							break;
						case 'Aiko - Referent':
							$ruleId = '61a9190e40965';
							$deletionParam['values']['status'] = 'Active'; // to skip the rule filter
							// Add all the other values otherwise the source data wont be stored and the status will still be empty
							$deletionParam['values']['email_address'] = ''; 
							$deletionParam['values']['phone_mobile'] = ''; 
							$deletionParam['values']['last_name'] = '';
							$deletionParam['values']['first_name'] = '';
							$deletionParam['values']['deleted'] = '';
							break;
						default:
							throw new \Exception('SyncSource '.$values['SyncSource'].' unknown. Failed to generate deletion.');
					endswitch;
					if (empty($values['IDCOMET'])) {
						throw new \Exception('No COMET ID for the record '.$values['DEVREC_ID'].'. Failed to generate deletion.');
					}
					
					$ruleParam['ruleId'] = $ruleId; 
					$ruleParam['jobId'] = $param['jobId'];
					$ruleDeletion = new rule($this->logger, $this->container, $this->conn, $ruleParam);				

					$deletionParam['values']['myddleware_deletion'] = true;
					$deletionParam['values']['id'] = $values['IDCOMET'];
					$deletionParam['values']['date_modified'] = gmdate('Y-m-d H:i:s');
					$documents = $ruleDeletion->generateDocuments($values['IDCOMET'], false, $deletionParam); 
				}	
			}
		} */
		return $result;
	}
	
	// Check data before create
    protected function checkDataBeforeCreate($param, $data, $idDoc)
    {
		$data = parent::checkDataBeforeCreate($param, $data, $idDoc);
		// If the etab sup is missing then we remove the field from the call
		if ($param['rule']['id'] == '6267e9c106873') { // Mobilisation - Composantes
			if (empty($data['fldBQBCfr1ZgVJmE3'])) {	// Etbalissement sup
				unset($data['fldBQBCfr1ZgVJmE3']);
			}
		}
		
		if ($param['rule']['id'] == '61a930273441b') { // Aiko binome
			if (empty($data['fldqGYsTr5EylIi2f'])) {	// if referent empty we remove it from the data sent
				unset($data['fldqGYsTr5EylIi2f']);
			}
		}

        return $data;
    }

    // Check data before update
    protected function checkDataBeforeUpdate($param, $data, $idDoc)
    {
		$data = parent::checkDataBeforeUpdate($param, $data, $idDoc);
		// If the etab sup is missing then we remove the field from the call
		if ($param['rule']['id'] == '6267e9c106873') { // Mobilisation - Composantes
			if (empty($data['fldBQBCfr1ZgVJmE3'])) {	// Etbalissement sup
				unset($data['fldBQBCfr1ZgVJmE3']);
			}
		}
		
		if ($param['rule']['id'] == '61a930273441b') { // Aiko binome
			if (empty($data['fldqGYsTr5EylIi2f'])) {	// if referent empty we remove it from the data sent
				unset($data['fldqGYsTr5EylIi2f']);
			}
		}

        return $data;
    }

	// Redefine updateDocumentStatus standard function
	protected function updateDocumentStatus($idDoc, $value, $param, $forceStatus = null): array {
		// Make an integromat call if call OK to Mobilisation - Contacts webservice
		if (
				!empty($param['ruleId'])
			AND	in_array($param['ruleId'], array('6303832f0a0b7')) // Mobilisation - Contacts webservice
			AND $value['id'] != '-1'
		) {
			try {
				// Get the COMET contact ID
				$sqlParams = 'SELECT * FROM document where id = :doc_id';
				$stmt = $this->getConn()->prepare($sqlParams);
				$stmt->bindValue(':doc_id', $idDoc);
				$stmt->execute();
				$result = $stmt->executeQuery();
                $document = $result->fetchAssociative();
				if (empty($document['source_id'])) {
					throw new \Exception('No source id found on the document. ');
				}

				// Integromat call
				$return['contactId'] = $document['source_id'];
				$json = json_encode($return);
				$url = 'https://hook.integromat.com/88xslg5tbm19xwdbhofe3jcvrk8nh3fp'; // nouvelle URL
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
				curl_setopt($curl, CURLOPT_TIMEOUT, 300);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
				$response = curl_exec($curl);
				curl_close($curl);
				// Erro if call not accpeted
				if (
						empty($response)
					 OR strpos($response, 'Accepted') === false
				) {
					$value['error'] = (empty($value['error']) ? 'No response from integromat. '.$return['contactId'] : $value['error'].'No response from integromat. ' );
					$value['id'] = '-1';
					$forceStatus = 'Error_sending';
				}
			} catch (\Exception $e) {
				$value['error'] = 'Failed to call integromat : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
				$value['id'] = '-1';
				$forceStatus = 'Error_sending';
			}
		}
		return parent::updateDocumentStatus($idDoc, $value, $param, $forceStatus);
	}
}
