<?php

namespace App\Custom\Solutions;

use App\Solutions\suitecrm;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Manager\DocumentManager;

//Sinon on met la classe suivante
class suitecrmcustom extends suitecrm
{

	protected int $limitCall = 100;
	public $anneeScolaire = '2023_2024';
	public $anneeScolaire2 = '2023'; // used to select 2 years
	// protected $moduleWithAnnee = array('Contacts', 'CRMC_binome', 'CRMC_Suivi','FP_events');
	protected $moduleWithAnnee = array('FP_events', 'CRMC_Suivi', 'Leads');
	protected $moduleWithAnnee2 = array('Contacts', 'CRMC_binome');
	protected string $urlSuffix = '/custom/service/v4_1_custom/rest.php';
	protected $currentRule;
	protected array $FieldsDuplicate = ['Contacts' => ['email1', 'last_name', 'Myddleware_element_id'],
        'Accounts' => ['email1', 'name'],
        'Users' => ['email1', 'last_name'],
        'Leads' => ['email1', 'last_name', 'Myddleware_element_id'],
        'Prospects' => ['email1', 'name'],
        'default' => ['name'],
		'CRMC_Evaluation' => ['type_c', 'annee_scolaire_c', 'MydCustRelSugarcrmc_evaluation_contactscontacts_ida'],
    ];
	
	// Redefine get_modules method
    public function get_modules($type = 'source')
    {
		// Add module convert coupon
		$modules = parent::get_modules($type);
		if ($type == 'target') {
			$modules['convert_coupon'] = 'Convert Coupon';
		}
		return $modules;
    }
	
	// Add aiko field to be able to filter on it
	public function get_module_fields($module, $type = 'source', $param = null): array
	{
		// Add field coupon_id in module convert_coupon
		if ($module == 'convert_coupon') {
			$this->moduleFields['coupon_id'] = array(
				'label' => 'ID coupon',
				'type' => 'varchar(255)',
				'type_bdd' => 'varchar(255)',
				'required' => 0,
				'relate' => false
			);
			// No standard call because the module doesn't exist in SuiteCRM
			return $this->moduleFields;
		}

		parent::get_module_fields($module, $type);
		if ($module == 'Contacts') {
			$this->moduleFields['aiko'] = array(
												'label' => 'Aïko',
												'type' => 'varchar(255)',
												'type_bdd' => 'varchar(255)',
												'required' => 0,
												'relate' => false
											);
			$this->moduleFields['myd_filter_mentor'] = array(
												'label' => 'Mentor OU Mendor acceuil',
												'type' => 'varchar(255)',
												'type_bdd' => 'varchar(255)',
												'required' => 0,
												'relate' => false
											);
		}
		/* if ($module == 'Accounts') {
			$this->moduleFields['myd_filtered'] = array(
				'label' => 'Filtre Myddleware',
				'type' => 'varchar(255)',
				'type_bdd' => 'varchar(255)',
				'required' => 0,
				'relate' => false
			);
		} */

		// if module = crmc suivi
		if ($module == 'CRMC_Suivi') {
			$this->moduleFields['myd_filter_suivi'] = array(
				'label' => 'Filtre Myddleware',
				'type' => 'varchar(255)',
				'type_bdd' => 'varchar(255)',
				'required' => 0,
				'relate' => false
			);
		}
		// Add the field to store the id_historique_mentore
		if ($module == 'CRMC_historique_mentore') {
			$this->moduleFields['id_historique_mentore'] = array(
				'label' => 'Id historique mentore',
				'type' => 'varchar(255)',
				'type_bdd' => 'varchar(255)',
				'required' => 0,
				'relate' => true
			);
			// Change a text field to a relate field
			$this->moduleFields['poles_rattaches']['relate'] = true;
		}
		return $this->moduleFields;
	}

	protected function call($method, $parameters)
	{
		if ($this->currentRule == '61a920fae25c5') {
			$parameters['link_name_to_fields_array'][] = array('name' => 'crmc_binome_contacts', 'value' => array('id', 'statut_c', 'chatbot_c'));
			$parameters['link_name_to_fields_array'][] = array('name' => 'crmc_binome_contacts_1', 'value' => array('id', 'statut_c', 'chatbot_c'));
		}
		$isRuleBilan = false;
		$ruleactive = true;
		if (
				$this->currentRule == '65708a7e59eae'
			AND $method == 'get_entry_list'
			AND !empty($parameters['module_name']
			AND $ruleactive
			// and parameters query contains the substring crmc
			AND strpos($parameters['query'], 'crmc_evaluation_cstm.type_c =') !== false
			)

		) {
			// this is the typical query
			//"crmc_evaluation_cstm.type_c = 'debut'  AND crmc_evaluation_cstm.annee_scolaire_c = '2022_2023'  AND crmc_evaluation.crmc_evaluation_contactscontacts_ida = '1811e41f-2a34-ec3a-e070-65717763e53f'"
			// get the type from counting the characters from the beginning of the query
			// get the query from the params
			
			//echo the query
			echo chr(10);
			echo 'this is the query in our custom call';
			echo chr(10);
			print_r($parameters);
			echo chr(10);
			echo 'this was the query in our custom call';
			echo chr(10);
			$paramQuery = $parameters['query'];
			$type = substr($paramQuery, strpos($paramQuery, 'crmc_evaluation_cstm.type_c =') + 31, 5);
			echo chr(10);
			echo 'this is the type in our custom call';
			echo chr(10);
			print_r($type);
			echo chr(10);
			echo 'this was the type in our custom call';
			echo chr(10);
			// echo chr(10);
			// echo 'this is the parameters in our custom call';
			// echo chr(10);
			// print_r($parameters);
			// echo chr(10);
			// echo 'this was the parameters in our custom call';
			// echo chr(10);
			$schoolYear = substr($parameters['query'], strpos($parameters['query'], 'crmc_evaluation_cstm.annee_scolaire_c =') + 41, 9);
			echo chr(10);
			echo 'this is the school year in our custom call';
			echo chr(10);
			print_r($schoolYear);
			echo chr(10);
			echo 'this was the school year in our custom call';
			echo chr(10);

			$contactId = substr($parameters['query'], strpos($parameters['query'], 'crmc_evaluation.crmc_evaluation_contactscontacts_ida =') + 56, 36);
			echo chr(10);
			echo 'this is the contact id in our custom call';
			echo chr(10);
			print_r($contactId);
			echo chr(10);
			echo 'this was the contact id in our custom call';
			echo chr(10);


			$isRuleBilan = true;
			$method = 'send_special_query';
			// empty the parameters
			$session = $this->session;
			$module_name = $parameters['module_name'];
			$parameters = array();
			$parameters['session'] = $this->session;
			$parameters['query'] = "SELECT
				crmc_evaluation.id,
				crmc_evaluation.date_modified,
				crmc_evaluation_contacts_c.crmc_evaluation_contactscontacts_ida as MydCustRelSugarcrmc_evaluation_contactscontacts_ida,
				crmc_evaluation.name,
				crmc_evaluation_cstm.type_c,
				crmc_evaluation_cstm.annee_scolaire_c,
				crmc_evaluation_cstm.implication_famille_c,
				crmc_evaluation_cstm.travail_personnel_c
			FROM crmc_evaluation
				INNER JOIN crmc_evaluation_cstm 
					ON crmc_evaluation.id = crmc_evaluation_cstm.id_c
				INNER JOIN crmc_evaluation_contacts_c 
					ON crmc_evaluation.id = crmc_evaluation_contacts_c.crmc_evaluation_contactscrmc_evaluation_idb
			WHERE 
				-- get the type from the variable $type
				crmc_evaluation_cstm.type_c = '$type'
				AND crmc_evaluation_cstm.annee_scolaire_c = '$schoolYear'
				AND crmc_evaluation_contacts_c.deleted = 0
				AND crmc_evaluation.deleted = 0
				AND crmc_evaluation_contacts_c.crmc_evaluation_contactscontacts_ida = '$contactId'
			LIMIT 1;";
		}

		echo chr(10);
		echo "this is the query new";
		echo chr(10);
		print_r($parameters['query']);
		echo chr(10);
		echo "this was the query new";
		echo chr(10);


		$result = parent::call($method, $parameters);

		
		if ($this->currentRule == '65708a7e59eae'
		 && $isRuleBilan
		 && $ruleactive
		 ) {
			
			$parameters['module_name'] = $module_name;
			$parameters['session'] = $session;
			// echo chr(10);
			// echo 'this is the result line 156';
			// print_r($result);
			// echo chr(10);
			// result is an empty array
			// echo 'this was the result line 159';
			$decodedResult = json_decode($result);

			// if decoded result status is success and decoded result message is empty string and decoded result values is not set then return
			if ($decodedResult->status == 'success' && $decodedResult->message == '' && !isset($decodedResult->values)) {

				// $result is an empty stdClass object
				$result = new \stdClass();
				$result->result_count = 0;
				$result->total_count = 0;
				$result->entry_list = [];
				$result->relationship_list = [];
				return $result;
				$noresult = true;
			}
			// $result = (array)$decodedResult->values[0];
			// $result = [];
			// $result[0] = $arrrayResult;
			
			$arrayResult = (array)$decodedResult->values[0];
			$result = new \stdClass();
			if (!($noresult)) {
				$result->result_count = 1;
				$result->total_count = 1;
			}
			
			// ------------------------------------test
			$result->entry_list = [];
			// foreach ($arrayResult as $key => $value) {
			// 	$entry = new \stdClass();
			// 	$entry->name_value_list = new \stdClass();

			// 	// Assuming each $value here is a simple value and not an array/object
			// 	$entry->name_value_list->$key = new \stdClass();
			// 	$entry->name_value_list->$key->name = $key;
			// 	$entry->name_value_list->$key->value = $value;

			// 	$result->entry_list[] = $entry;
			// }
			$entry = new \stdClass();
			$entry->name_value_list = new \stdClass();

			foreach ($arrayResult as $key => $value) {
				$entry->name_value_list->$key = new \stdClass();
				$entry->name_value_list->$key->name = $key;
				$entry->name_value_list->$key->value = $value;
			}

			// Add the constructed entry to the entry_list
			$result->entry_list[] = $entry;

			// ------------------------------------test end

			// $result->entry_list[0]['crmc_evaluation_contactscontacts_ida'] = $result->entry_list[0]->name_value_list->MydCustRelSugarcrmc_evaluation_contactscontacts_ida;

			$result->relationship_list = [];
			$isRuleBilan = false;
		}

		if ($this->currentRule == '61a920fae25c5') {
			if (!empty($result->relationship_list)) {
				foreach ($result->relationship_list as $key => $relationship) {
					$aiko = new \stdClass();
					$aiko->name = 'aiko';
					$aiko->value = '0';
					$result->entry_list[$key]->name_value_list->aiko = $aiko;
					if (
							!empty($relationship)
						and !empty($relationship->link_list[0]->records)
					) {
						foreach ($relationship->link_list[0]->records as $binome) {
							// Use the same filter than Airtable
							if (
								!empty($binome->link_value->statut_c->value)
								and $binome->link_value->statut_c->value <> 'termine'
								and $binome->link_value->statut_c->value <> 'annule'
								and $binome->link_value->statut_c->value <> 'accompagnement_termine'
								and !empty($binome->link_value->chatbot_c->value)
								and $binome->link_value->chatbot_c->value <> 'non'
							) {
								// $result->entry_list[$key]->name_value_list->aiko->name = 'aiko';
								$result->entry_list[$key]->name_value_list->aiko->value = '1';
								break;
							}
						}
					}
					// Check the second relationship (should never happen because each contact type has its own relationship type)
					if (
							!empty($relationship)
						and !empty($relationship->link_list[1]->records)
						and empty($result->entry_list[$key]->name_value_list->aiko->value)
					) {
						foreach ($relationship->link_list[1]->records as $binome) {
							// Use the same filter than Airtable
							if (
								!empty($binome->link_value->statut_c->value)
								and $binome->link_value->statut_c->value <> 'termine'
								and $binome->link_value->statut_c->value <> 'annule'
								and $binome->link_value->statut_c->value <> 'accompagnement_termine'
								and !empty($binome->link_value->chatbot_c->value)
								and $binome->link_value->chatbot_c->value <> 'non'
							) {
								// $result->entry_list[$key]->name_value_list->aiko->name = 'aiko';
								$result->entry_list[$key]->name_value_list->aiko->value = '1';
								break;
							}
						}
					}
				}
			}
		}
		// echo chr(10);
		// 	echo 'this is the result line 209';
		// 	echo chr(10);
		// 	print_r($result);
		// 	echo chr(10);
		// 	echo 'this was the result line 213';
		// 	echo chr(10);
		return $result;
	}

	public function readData($param)
	{
		if (!empty($param['rule']['id'])) {
			$this->currentRule = $param['rule']['id'];
		}
		return parent::readData($param);
	}


	// Redifine read function
	public function read($param)
	{
		// No history read action for every the rules => no need of history for the migration
		if (
			$param['call_type'] == 'history'
			and $param['module'] == 'fp_events_leads_1'
		) {
			return array();
		}

		$read = parent::read($param);
		// Add a field to filter by mentor OR mentor accueil
		if (
					$param['module']=='Contacts'
				AND $param['call_type'] == 'read'
		) {
			foreach ($read as $key => $record) {
				// Record filtered by default
				$read[$key]['myd_filter_mentor'] = 'Non';
				if (
						!empty($record['souhaite_faire_de_ai_c'])
					AND	!empty($record['mentor_acceuil_c'])
					AND	!empty($record['volontaire_pour_afev_c'])
					AND(
							$record['souhaite_faire_de_ai_c'] == 'Oui'
						 OR $record['mentor_acceuil_c'] == 'Oui'
						 OR $record['volontaire_pour_afev_c'] == 'Oui'
					)
				) {
					$read[$key]['myd_filter_mentor'] = 'Oui';
				}			
			}
		}

		// Set the new filter to 1 if the interlocuteur is engage OR if the bilan type is SuivirattrapageREEC
		if (
				$param['module'] == 'CRMC_Suivi'
			AND $param['call_type'] == 'read'
		) {
			foreach ($read as $key => $record) {
				// Record filtered by default
				$read[$key]['myd_filter_suivi'] = 0;
				if (
					(
							!empty($record['interlocuteur_c'])
						AND	strpos($record['interlocuteur_c'], 'engage') !== false
					)  
					OR (
							!empty($record['type_suivi_bilan_c'])
						AND	$record['type_suivi_bilan_c'] == 'SuivirattrapageREEC'
					)
				) {
					$read[$key]['myd_filter_suivi'] = 1;
				}
			}
		} 

		/* if (
				!empty($param['rule']['id'])
			AND	$param['rule']['id'] == '5ce362b962b63'
			AND !empty($read)
		) {
			foreach ($read as $key => $record) {
				// Record filtered by default
				$read[$key]['myd_filtered'] = 1;
				// Keep the composante if an university is link to it of if it has the type below
				// Collège = 8
				// École maternelle = ecole_maternelle
				// École élémentaire = 10
				// Lycée general et techno = 1
				// Lycée professionnel = 11
				// Lycée technique = 9
				// Lycée polyvalent = lycee_polyvalent		
				if (
					!empty($record['MydCustRelSugarcrmc__etablissement_sup_accounts_1crmc__etablissement_sup_ida'])
					or in_array($record['type_de_partenaire_c'], array('8', 'ecole_maternelle', '10', '1', '11', '9', 'lycee_polyvalent'))
				) {
					$read[$key]['myd_filtered'] = 0;
				}
			}
		} */

		// Split the result of the read for the pole, so that if we have 1 document with 3 poles, 
		// 1 document with 2 poles, and 1 document with 5 poles, we end up with 10 records in the result
		if (
				$param['module'] == 'CRMC_historique_mentore'
			AND $param['call_type'] == 'read'
			AND in_array('id_historique_mentore', $param['fields']) // If field id_historique_mentore is requested
		) {
			$read2 = array();
			$i = 0;
			foreach ($read as $key => $record) {
				$poles = array();
				if (!empty($record['poles_rattaches'])) {
					// If we have several poles, we split the record
					if (strpos($record['poles_rattaches'], ',') !== false) {
						// Transform poles list string to an array
						$poles = explode(',', str_replace('^', '', $record['poles_rattaches']));
					// If we have only one pole, we create an array with one entry
					} else {
						$poles[] = trim($record['poles_rattaches'], '^');
					}
					// Prepare the result
					foreach ($poles as $pole) {
						$read2[$i] = $record;
						$read2[$i]['poles_rattaches'] = $pole;
						$read2[$i]['id'] = $record['id'].'_'.$pole;
						$read2[$i]['id_historique_mentore'] = $record['id'];
						$i++;
					}
				}
			}
			return $read2;
		}
		return $read;
	}
	
	protected function updateDocumentStatus($idDoc, $value, $param, $forceStatus = null): array {
		if ($param['rule']['id'] == '6281633dcddf1') { // Mobilisation - Participation RI -> comet
			// Change id and use event_id and lead_id
			$value['id'] = $param['data'][$idDoc]['fp_events_leads_1fp_events_ida'].$param['data'][$idDoc]['fp_events_leads_1leads_idb'];			
		}
		
		// We set the document to cancel when we try to update a converted status for a coupon
		if (
				!empty($param['ruleId'])
			AND	in_array($param['ruleId'], array('62695220e54ba','633ef1ecf11db'))	// Mobilisation - relance rdv pris -> comet // 	Mobilisation - Coupons vers Comet		
			AND $value['id'] == '-1'
			AND strpos($value['error'], 'Erreur code W0001') !== false		
		) {
			try {
				$this->connection->beginTransaction();
				$documentManager = new DocumentManager(
										$this->logger, 
										$this->connection, 
										$this->entityManager,
										$this->documentRepository,
										$this->ruleRelationshipsRepository,
										$this->formulaManager
									);
				$param['id_doc_myddleware'] = $idDoc;
				$param['api'] = $this->api;
				$documentManager->setParam($param);
				$documentManager->setMessage($value['error']);
				$documentManager->setTypeError('W');
				$documentManager->updateStatus('Cancel');
				$this->logger->error($value['error']);
				$response[$idDoc] = false;	
				$this->connection->commit(); // -- COMMIT TRANSACTION
			} catch (\Exception $e) {
				echo 'Failed to send document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
				$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
				$documentManager->setMessage('Failed to send document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
				$documentManager->setTypeError('E');
				$documentManager->updateStatus('Error_sending');
				$this->logger->error('Failed to send document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
				$response[$idDoc] = false;
			}			
			return $response;
		}
		
		
		return parent::updateDocumentStatus($idDoc, $value, $param, $forceStatus);                               
	}
	
	// Permet de mettre à jour un enregistrement
    public function createData($param): array
    {
		if ($param['rule']['module_target'] == 'convert_coupon') { // Convert coupon
			foreach ($param['data'] as $idDoc => $data) {
				try {
					// Error if no coupon ID
					if (empty($data['coupon_id'])) {
						throw new \Exception('No coupon id. Failed to convert the coupon. ');
					} else {
						// Send the coupon conversion
						$get_parameters = array(
							'session' => $this->session,
							'coupon_id' => $data['coupon_id']
						);
						$convertCoupon = json_decode($this->call("convert_coupon", $get_parameters));
						// Error if no contact id
						if (empty($convertCoupon->contact_id)) {
							throw new \Exception('error : '.$convertCoupon->error);
						}
						// Add the contact id in the result
						$result[$idDoc] = array(
							'id' => $convertCoupon->contact_id,
							'error' => !$convertCoupon->success,
						);
					}
				} catch (\Exception $e) {
					$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
					$result[$idDoc] = [
						'id' => '-1',
						'error' => $error,
					];
				}
				// Modification du statut du flux
				$this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
			}
			return $result;
		}
		// Call the standard function
		return parent::createData($param);
	}

	// Permet de mettre à jour un enregistrement
    public function updateData($param): array
    {
		if ($param['rule']['id'] == '62d9d41a59b28') { // Mobilisation - Reconduction
			$fieldToBeOverriden = array(
				'dispo_lundi_c',
				'dispo_lundi_fin_c',
				'dispo_mardi_c',
				'dispo_mardi_fin_c',
				'dispo_mercredi_c',
				'dispo_mercredi_fin_c',
				'dispo_jeudi_c',
				'dispo_jeudi_fin_c',
				'dispo_vendredi_c',
				'dispo_vendredi_fin_c',
				'dispo_samedi_c',
				'dispo_samedi_fin_c',
				'dispo_dimanche_c',
				'dispo_dimanche_fin_c'
			);
			// Do not replace empty field into the COMET expect for sepcific fields
			foreach ($param['data'] as $idDoc => $data) {
				foreach ($data as $key => $value) {
					if (in_array($key, $fieldToBeOverriden)) {
						continue;
					}
					if (empty($value)) {
						unset($param['data'][$idDoc][$key]);
					}
				}
			}
		}
		return parent::updateData($param);
	}

	// Custom check before update
	protected function checkDataBeforeUpdate($param, $data, $idDoc)
	{
 		if ($param['rule']['id'] == '62d9d41a59b28') { // Mobilisation - Reconduction
			$now = new \DateTime();
			$currentYear = $now->format('Y');
			// academic year calculation
			$currentAcademicYear = ($now->format('m') < 8 ? ($currentYear - 1) . '_' . $currentYear : $currentYear . '_' . ($currentYear + 1));

			// Manage annee_scolaire_c field using the history
			if (empty($param['dataHistory'][$idDoc]['annee_scolaire_c'])) {
				throw new \Exception('Failed to execute the reconduction. No value in fiedl annee_scolaire_c in the history for the document ' . $idDoc . '.');
			}
			if (strpos($param['dataHistory'][$idDoc]['annee_scolaire_c'], $currentAcademicYear) !== false) {
				throw new \Exception('Failed to execute the reconduction. The contact is already active on the current year (' . $currentAcademicYear . '). ');
			}
			// Add the academic year to the annee_scolaire_c field
			$data['annee_scolaire_c'] = $param['dataHistory'][$idDoc]['annee_scolaire_c'] . ',^' . $currentAcademicYear . '^';
			return $data;
		}

		//handle description edit if there is a difference in account name
		if ($param['rule']['id'] == '63482d533bd4e') {
			// This requires a custom formula from Nom_etablissement in internallitst 
			// Name not updated, we keep the historical name
			unset($data['name']);
			// To name in suiteCrm
			if(!empty($param['dataHistory'][$idDoc]['description'])) {
				$data['description'] = $param['dataHistory'][$idDoc]['description'] . " - Nom officiel: ".$param['data'][$idDoc]['description'];
			}
			return $data;
		}
		// Do not override converted status
		if (
				!empty($param['dataHistory'][$idDoc]['status'])
			AND	
			(
				(
						in_array($param['rule']['id'], array('62695220e54ba','633ef1ecf11db'))	// Mobilisation - relance rdv pris -> comet // 	Mobilisation - Coupons vers Comet
					AND $param['dataHistory'][$idDoc]['status'] == 'Converted'
				)
				OR (
						in_array($param['rule']['id'], array('62695220e54ba'))	// Mobilisation - relance rdv pris -> comet // 	Mobilisation - Coupons vers Comet
					AND $param['dataHistory'][$idDoc]['status'] == 'inscription_attente'
				)
			)
		) { 
			throw new \Exception(utf8_decode('Statut transformé ne peut pas être modifié. Le document est annulé.').' Erreur code W0001.');
		}
		
		return parent::checkDataBeforeUpdate($param, $data, $idDoc);
	}

	// Add filter for contact module
	public function getFieldsParamUpd($type, $module): array
	{
		$param = array();
		try {
			if ($type == 'source') {
				if ($module == 'Contacts') {
					$param[] = array(
						'id' => 'contactType',
						'name' => 'contactType',
						'type' => 'option',
						'label' => 'Contact type',
						'required'	=> false,
						'option'	=> array(
							'' => '',
							'Accompagne' => 'Jeune accompagné',
							'Benevole' => 'Bénévole',
							'contact_partenaire' => 'Contact partenaire',
							'non_contact_partenaire' => 'Pas contact partenaire',
							'non_accompagne' => 'Pas mentoré',
						)
					);
				}
				if ($module == 'Leads') {
					$param[] = array(
						'id' => 'leadType',
						'name' => 'leadType',
						'type' => 'text',
						'label' => 'Coupon type',
						'required'	=> false
					);
				}
				// Annee annee scolaire as parameter 
				if (in_array($module, $this->moduleWithAnnee)) {
					$param[] = array(
						'id' => 'anneeScolaire',
						'name' => 'anneeScolaire',
						'type' => 'option',
						'label' => 'Année scolaire',
						'required'	=> false,
						'option'	=> array(
							'' => '',
							'2022_2023' => '2022-2023',
							'2023_2024' => '2023-2024',
							'2024_2025' => '2024-2025',
						)
					);
				}
			}
			return $param;
		} catch (\Exception $e) {
			return array();
		}
	}

	public function getRuleMode($module, $type): array
	{
		// Authorize update for relationship fp_events_leads_1
		if ($module == 'fp_events_leads_1') {
			return [
				'0' => 'create_modify',
				'C' => 'create_only',
			];
		}
		return parent::getRuleMode($module, $type);
	}


	// Build the query for read data to SuiteCRM
	protected function generateQuery($param, $method): string
	{
		// Call the standard function
		$query = parent::generateQuery($param, $method);
		// Add filter on contact type when the contacts are read from SuiteCRM

		//if my rule and module = 
		if (strpos($query, 'type_de_partenaire_c') !== false && $param['module'] == 'Accounts' && $param['rule']['id'] == '63482d533bd4e') {
			$query = "accounts_cstm.type_de_partenaire_c IN ('ecole_maternelle', '8', '10') ";
		}	

		if (
			$param['module'] == 'Contacts'
			and !empty($param['ruleParams']['contactType'])
		) {
			if ($param['ruleParams']['contactType'] == 'non_contact_partenaire') {
				$query .= ' AND ' . strtolower($param['module']) . "_cstm.contact_type_c <> 'contact_partenaire' ";
			} elseif ($param['ruleParams']['contactType'] == 'non_accompagne') {
				$query .= ' AND ' . strtolower($param['module']) . "_cstm.contact_type_c <> 'Accompagne' ";
			} else {
				$query .= ' AND ' . strtolower($param['module']) . "_cstm.contact_type_c = '" . $param['ruleParams']['contactType'] . "' ";
			}
		}
		// Add filter on lead type when the leads are read from SuiteCRM
		if (
			$param['module'] == 'Leads'
			and !empty($param['ruleParams']['leadType'])
		) {
			$query .= " AND " . strtolower($param['module']) . "_cstm.coupon_type_c IN (" . $param['ruleParams']['leadType'] . ") ";
		}
		// filter by annee
		// The rule parameter anneeScolaire override the genera parameter if exists
		if (
			in_array($param['module'], $this->moduleWithAnnee)
			and $param['call_type'] != 'history'
		) {
			$query .= ' AND '.strtolower($param['module'])."_cstm.annee_scolaire_c LIKE '%".(!empty($param['ruleParams']['anneeScolaire']) ? $param['ruleParams']['anneeScolaire'] : $this->anneeScolaire)."%' ";
		}
		// The rule parameter anneeScolaire2 override the genera parameter if exists
		if (
			in_array($param['module'], $this->moduleWithAnnee2)
			and $param['call_type'] != 'history'
		) {
			$query .= ' AND '.strtolower($param['module'])."_cstm.annee_scolaire_c LIKE '%".(!empty($param['ruleParams']['anneeScolaire']) ? $param['ruleParams']['anneeScolaire'] : $this->anneeScolaire2)."%' ";
		}
		// Add a filter for contact universite 
		if (
				!empty($param['rule']['id'])
			AND $param['rule']['id'] == '5d01a630c217c' //  REEC - Contact partenaire
		){
			$query .= ' AND '.strtolower($param['module'])."_cstm.reec_c LIKE '%contact_universite%' ";
		}
		// Add a filter for contact reperant 
		if (
				!empty($param['rule']['id'])
			AND $param['rule']['id'] == '6273905a05cb2' // Esp Rep - Contacts repérants
		){
			$query .= ' AND '.strtolower($param['module'])."_cstm.espace_reperant_c <> 'non' ";
		}
		
		// Add a filter on field id_1j1m_c non-empty for coupon and contact 
		if (
				!empty($param['rule']['id'])
			AND in_array($param['rule']['id'], array('6530c97bdce08', '6530d3766b3da')) // 1j1m - Coupon / 	1j1m - Contact
		){
			$query .= ' AND '.strtolower($param['module'])."_cstm.id_1j1m_c <> '' ";
		}

		return $query;
	}

	// Permet de récupérer le dernier enregistrement de la solution (utilisé pour tester le flux)
	public function send_query($query)
	{
		try {
			$get_parameters = array(
				'session' => $this->session,
				'query' => $query
			);
			return $this->call("send_special_query", $get_parameters);
		} catch (\Exception $e) {
			throw new \Exception('Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
		}
		return false;
	}
	
	
	 // Build the direct link to the record (used in data transfer view)
    public function getDirectLink($rule, $document, $type): string
    {
		// Get url, module and record ID depending on the type
        if ('source' == $type) {
            // $url = $this->getConnectorParam($rule->getConnectorSource(), 'url');
            $module = $rule->getModuleSource();
            $recordId = $document->getSource();
        } else {
            // $url = $this->getConnectorParam($rule->getConnectorTarget(), 'url');
            $module = $rule->getModuleTarget();
            $recordId = $document->gettarget();
        }
        return 'https://comet'.($_ENV['AFEV_ENV'] == 'PREPROD' ? '.preprod' : '').'.afev.org/index.php?module='.$module.'&action=DetailView&record='.$recordId;
    }
}
