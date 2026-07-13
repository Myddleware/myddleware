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
	public $anneeScolaire = '2026_2027';
	public $anneeScolaire2 = '2026'; // used to select 2 years
	public $anneeScolaire3 = '2027'; // used to select 2 years, the current one and the next one
	protected $moduleWithAnnee = array('FP_events', 'CRMC_suivi', 'Leads', 'CRMC_coupon_mentore');
	protected $moduleWithAnnee2 = array('Contacts', 'CRMC_binome', 'CRMC_mentore');
	protected string $urlSuffix = '/custom/service/v4_1_custom/rest.php';
	protected $currentRule;
	protected array $FieldsDuplicate = [
		'Contacts' => ['email1', 'last_name', 'Myddleware_element_id'],
		'CRMC_mentore' => ['email1', 'last_name', 'Myddleware_element_id'],
        'Accounts' => ['email1', 'name'],
        'Users' => ['email1', 'last_name'],
        'Leads' => ['email1', 'last_name', 'Myddleware_element_id'],
        'CRMC_coupon_mentore' => ['email1', 'last_name', 'Myddleware_element_id'],
        'Prospects' => ['email1', 'name'],
        'default' => ['name'],
		'CRMC_evaluation' => ['type_c', 'annee_scolaire_c', 'MydCustRelSugarcrmc_evaluation_crmc_mentorecrmc_mentore_ida'],
    ];
	
	// Tableau représentant les relation many-to-many de Sugar
    protected array $module_relationship_many_to_many = [
        'calls_contacts' => ['label' => 'Relationship Call Contact', 'module_name' => 'Calls', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['call_id', 'contact_id']],
        'calls_users' => ['label' => 'Relationship Call User', 'module_name' => 'Calls', 'link_field_name' => 'users', 'fields' => [], 'relationships' => ['call_id', 'user_id']],
        'calls_leads' => ['label' => 'Relationship Call Lead', 'module_name' => 'Calls', 'link_field_name' => 'leads', 'fields' => [], 'relationships' => ['call_id', 'lead_id']],
        'cases_bugs' => ['label' => 'Relationship Case Bug', 'module_name' => 'Cases', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['case_id', 'bug_id']],
        'contacts_bugs' => ['label' => 'Relationship Contact Bug', 'module_name' => 'Contacts', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['contact_id', 'bug_id']],
        'contacts_cases' => ['label' => 'Relationship Contact Case', 'module_name' => 'Contacts', 'link_field_name' => 'cases', 'fields' => [], 'relationships' => ['contact_id', 'case_id']],
        'meetings_contacts' => ['label' => 'Relationship Metting Contact', 'module_name' => 'Meetings', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['meeting_id', 'contact_id']],
        'meetings_users' => ['label' => 'Relationship Meeting User', 'module_name' => 'Meetings', 'link_field_name' => 'users', 'fields' => [], 'relationships' => ['meeting_id', 'user_id']],
        'meetings_leads' => ['label' => 'Relationship Meeting Lead', 'module_name' => 'Meetings', 'link_field_name' => 'leads', 'fields' => [], 'relationships' => ['meeting_id', 'lead_id']],
        'opportunities_contacts' => ['label' => 'Relationship Opportunity Contact', 'module_name' => 'Opportunities', 'link_field_name' => 'contacts', 'fields' => ['contact_role'], 'relationships' => ['opportunity_id', 'contact_id']], // contact_role exist in opportunities vardef for module contact (entry rel_fields)
        'prospect_list_campaigns' => ['label' => 'Relationship Prospect_list Campaign', 'module_name' => 'ProspectLists', 'link_field_name' => 'campaigns', 'fields' => [], 'relationships' => ['prospect_list_id', 'campaign_id']],
        'prospect_list_contacts' => ['label' => 'Relationship Prospect_list Contact', 'module_name' => 'ProspectLists', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['prospect_list_id', 'contact_id']],
        'prospect_list_prospects' => ['label' => 'Relationship Prospect_list Prospect', 'module_name' => 'ProspectLists', 'link_field_name' => 'prospects', 'fields' => [], 'relationships' => ['prospect_list_id', 'Prospect_id']],
        'prospect_list_leads' => ['label' => 'Relationship Prospect_list Lead', 'module_name' => 'ProspectLists', 'link_field_name' => 'leads', 'fields' => [], 'relationships' => ['prospect_list_id', 'lead_id']],
        'prospect_list_users' => ['label' => 'Relationship Prospect_list User', 'module_name' => 'ProspectLists', 'link_field_name' => 'users', 'fields' => [], 'relationships' => ['prospect_list_id', 'user_id']],
        'prospect_list_accounts' => ['label' => 'Relationship Prospect_list Account', 'module_name' => 'ProspectLists', 'link_field_name' => 'accounts', 'fields' => [], 'relationships' => ['prospect_list_id', 'account_id']],
        'projects_bugs' => ['label' => 'Relationship Project Bug', 'module_name' => 'Projects', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['project_id', 'bug_id']],
        'projects_cases' => ['label' => 'Relationship Project Case', 'module_name' => 'Projects', 'link_field_name' => 'cases', 'fields' => [], 'relationships' => ['project_id', 'case_id']],
        'projects_accounts' => ['label' => 'Relationship Project Account', 'module_name' => 'Projects', 'link_field_name' => 'accounts', 'fields' => [], 'relationships' => ['project_id', 'account_id']],
        'projects_contacts' => ['label' => 'Relationship Project Contact', 'module_name' => 'Projects', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['project_id', 'contact_id']],
        'projects_opportunities' => ['label' => 'Relationship Project Opportunity', 'module_name' => 'Projects', 'link_field_name' => 'opportunities', 'fields' => [], 'relationships' => ['project_id', 'opportunity_id']],
        'email_marketing_prospect_lists' => ['label' => 'Relationship Email_marketing Prospect_list', 'module_name' => 'EmailMarketing', 'link_field_name' => 'prospect_lists', 'fields' => [], 'relationships' => ['email_marketing_id', 'prospect_list_id']],
        'leads_documents' => ['label' => 'Relationship Lead Document', 'module_name' => 'Leads', 'link_field_name' => 'documents', 'fields' => [], 'relationships' => ['lead_id', 'document_id']],
        'documents_accounts' => ['label' => 'Relationship Document Account', 'module_name' => 'Documents', 'link_field_name' => 'accounts', 'fields' => [], 'relationships' => ['document_id', 'account_id']],
        'documents_contacts' => ['label' => 'Relationship Document Contact', 'module_name' => 'Documents', 'link_field_name' => 'contacts', 'fields' => [], 'relationships' => ['document_id', 'contact_id']],
        'documents_opportunities' => ['label' => 'Relationship Document Opportunity', 'module_name' => 'Documents', 'link_field_name' => 'opportunities', 'fields' => [], 'relationships' => ['document_id', 'opportunity_id']],
        'documents_cases' => ['label' => 'Relationship Document Case', 'module_name' => 'Documents', 'link_field_name' => 'cases', 'fields' => [], 'relationships' => ['document_id', 'case_id']],
        'documents_bugs' => ['label' => 'Relationship Document Bug', 'module_name' => 'Documents', 'link_field_name' => 'bugs', 'fields' => [], 'relationships' => ['document_id', 'bug_id']],
        'aos_quotes_aos_invoices' => ['label' => 'Relationship Quote Invoice', 'module_name' => 'AOS_Quotes', 'link_field_name' => 'aos_quotes_aos_invoices', 'fields' => [], 'relationships' => ['aos_quotes77d9_quotes_ida', 'aos_quotes6b83nvoices_idb']],
        'fp_events_contacts' => ['label' => 'Relationship Event Contact', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_contacts', 'fields' => ['accept_status', 'invite_status'], 'relationships' => ['fp_events_contactsfp_events_ida', 'fp_events_contactscontacts_idb']],
        'fp_events_leads_1' => ['label' => 'Relationship Event Lead', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_leads_1', 'fields' => ['accept_status', 'invite_status'], 'relationships' => ['fp_events_leads_1fp_events_ida', 'fp_events_leads_1leads_idb']],
        'fp_events_prospects_1' => ['label' => 'Relationship Event Prospect', 'module_name' => 'FP_events', 'link_field_name' => 'fp_events_prospects_1', 'fields' => ['accept_status', 'invite_status'], 'relationships' => ['fp_events_prospects_1fp_events_ida', 'fp_events_prospects_1prospects_idb']],
        'crmc_dossier_recrutement_kaps_leads' => ['label' => 'Relationship Recrutement Kaps Leads', 'module_name' => 'CRMC_Dossier_Recrutement_Kaps', 'link_field_name' => 'crmc_dossier_recrutement_kaps_leads', 'fields' => [], 'relationships' => ['crmc_dossi448fnt_kaps_ida', 'crmc_dossier_recrutement_kaps_leadsleads_idb']],
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

		// if module = crmc suivi
		if ($module == 'CRMC_suivi') {
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
		
		// Add the field to store the id_historique_mentor
		if ($module == 'CRMC_historique_engage') {
			$this->moduleFields['id_historique_mentor'] = array(
				'label' => 'Id historique mentor',
				'type' => 'varchar(255)',
				'type_bdd' => 'varchar(255)',
				'required' => 0,
				'relate' => true
			);
		}
		return $this->moduleFields;
	}

	protected function call($method, $parameters)
	{
		if ($this->currentRule == '61a920fae25c5') {	// Aiko - Contact
			$parameters['link_name_to_fields_array'][] = array('name' => 'crmc_binome_contacts', 'value' => array('id', 'statut_c', 'chatbot_c'));
		}
		$isRuleBilan = false;
		$ruleactive = true;
		
		// Check if a fiche eval elready exist
		if (
				in_array($this->currentRule, array('65b11699a6edc'))  // Aiko - Bilan vers Comet 
			AND $method == 'get_entry_list'
			AND !empty($parameters['module_name'])
			AND $ruleactive
			// and parameters query contains the substring crmc
			AND strpos($parameters['query'], 'crmc_evaluation_cstm.type_c =') !== false
		) {
			// Extract filters from query string
			$filters = explode('AND', str_replace(' ','', $parameters['query']));
			if (!empty($filters)) {
				foreach($filters as $key => $filter){
					$temp = explode('=', $filter);
					$filtersFinal[$temp[0]] = $temp[1];
				}
			}

			// Check filters
			if (empty($filtersFinal['crmc_evaluation_cstm.type_c'])) {
				throw new \Exception('Type is empty. Failed to search the fiche evaluation into COMET. ');
			}
			if (empty($filtersFinal['crmc_evaluation_cstm.annee_scolaire_c'])) {
				throw new \Exception('Annee scolaire is empty. Failed to search the fiche evaluation into COMET. ');
			}
			if (empty($filtersFinal['crmc_evaluation.MydCustRelSugarcrmc_evaluation_crmc_mentorecrmc_mentore_ida'])) {
				throw new \Exception('Contact ID is empty. Failed to search the fiche evaluation into COMET. ');
			}

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
				crmc_evaluation_crmc_mentore_c.crmc_evaluation_crmc_mentorecrmc_mentore_ida as MydCustRelSugarcrmc_evaluation_crmc_mentorecrmc_mentore_ida,
				crmc_evaluation.name,
				crmc_evaluation_cstm.type_c,
				crmc_evaluation_cstm.annee_scolaire_c,
				crmc_evaluation_cstm.implication_famille_c,
				crmc_evaluation_cstm.travail_personnel_c
			FROM crmc_evaluation
				INNER JOIN crmc_evaluation_cstm 
					ON crmc_evaluation.id = crmc_evaluation_cstm.id_c
				INNER JOIN crmc_evaluation_crmc_mentore_c 
					ON crmc_evaluation.id = crmc_evaluation_crmc_mentore_c.	crmc_evaluation_crmc_mentorecrmc_evaluation_idb
			WHERE 
				-- get the type from the variable $type
				crmc_evaluation_cstm.type_c = ".$filtersFinal['crmc_evaluation_cstm.type_c']."
				AND crmc_evaluation_cstm.annee_scolaire_c = ".$filtersFinal['crmc_evaluation_cstm.annee_scolaire_c']."
				AND crmc_evaluation_crmc_mentore_c.deleted = 0
				AND crmc_evaluation.deleted = 0
				AND crmc_evaluation_crmc_mentore_c.crmc_evaluation_crmc_mentorecrmc_mentore_ida = ".$filtersFinal['crmc_evaluation.MydCustRelSugarcrmc_evaluation_crmc_mentorecrmc_mentore_ida']."
			LIMIT 1;";
		}
	
		// Call standard
		$result = parent::call($method, $parameters);
		
		if (in_array($this->currentRule, array('65b11699a6edc')) // Aiko - Bilan vers Comet 
		 && $isRuleBilan
		 && $ruleactive
		 ) {
			
			$parameters['module_name'] = $module_name;
			$parameters['session'] = $session;
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

			$arrayResult = (array)$decodedResult->values[0];
			$result = new \stdClass();
			if (!($noresult)) {
				$result->result_count = 1;
				$result->total_count = 1;
			}
			
			$result->entry_list = [];
			$entry = new \stdClass();
			$entry->name_value_list = new \stdClass();

			foreach ($arrayResult as $key => $value) {
				$entry->name_value_list->$key = new \stdClass();
				$entry->name_value_list->$key->name = $key;
				$entry->name_value_list->$key->value = $value;
			}

			// Add the constructed entry to the entry_list
			$result->entry_list[] = $entry;

			$result->relationship_list = [];
			$isRuleBilan = false;
		}

		if ($this->currentRule == '61a920fae25c5') { // Aiko - Contact
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
							) {
								$result->entry_list[$key]->name_value_list->aiko->value = '1';
								break;
							}
						}
					}
				}
			}
		}
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
				$param['module'] == 'CRMC_suivi'
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
		
		
		// Split the result of the read for the pole, so that if we have 1 document with 3 poles, 
		// 1 document with 2 poles, and 1 document with 5 poles, we end up with 10 records in the result
		if (
				$param['module'] == 'CRMC_historique_engage'
			AND $param['call_type'] == 'read'
			AND in_array('id_historique_mentor', $param['fields']) // If field id_historique_mentor is requested
		) {
			$read2 = array();
			$i = 0;
			foreach ($read as $key => $record) {
				$poles = array();
				if (!empty($record['poles_c'])) {
					// If we have several poles, we split the record
					if (strpos($record['poles_c'], ',') !== false) {
						// Transform poles list string to an array
						$poles = explode(',', str_replace('^', '', $record['poles_c']));
					// If we have only one pole, we create an array with one entry
					} else {
						$poles[] = trim($record['poles_c'], '^');
					}
					// Prepare the result
					foreach ($poles as $pole) {
						$read2[$i] = $record;
						$read2[$i]['poles_c'] = $pole;
						$read2[$i]['id'] = $record['id'].'_'.$pole;
						$read2[$i]['id_historique_mentor'] = $record['id'];
						$i++;
					}
				}
			}
			return $read2;
		}
		return $read;
	}
	
	protected function updateDocumentStatus($idDoc, $value, $param, $forceStatus = null): array {
		$response = array();
		// Specific logic for 'Mobilisation - Participation RI -> comet' rule
		if ($param['rule']['id'] == '6281633dcddf1') { 
			$value['id'] = $param['data'][$idDoc]['fp_events_leads_1fp_events_ida'].$param['data'][$idDoc]['fp_events_leads_1leads_idb'];
		}
		
		// Mobilisation - Coupons vers Comet
		// Mobilisation - relance rdv pris -> comet
		// Mobilisation - Reconduction
		// Aiko - Binome vers COMET
		if (
				!empty($param['ruleId'])
			AND $value['id'] == '-1'
			AND	in_array($param['ruleId'], array('62695220e54ba','633ef1ecf11db', '62d9d41a59b28','62cb3f449e55f'))
		) {
			try {
				$this->connection->beginTransaction();
				$documentManager = new DocumentManager(
					$this->logger, 
					$this->connection, 
					$this->entityManager,
					$this->formulaManager
				);
				$param['id_doc_myddleware'] = $idDoc;
				$param['api'] = $this->api;
				$documentManager->setParam($param);
				$documentManager->setMessage($value['error']);
				$documentManager->setTypeError('W');
		
				// Additional checks for specific errors and rules
				if (
						in_array($param['ruleId'], array('62695220e54ba','633ef1ecf11db', '62d9d41a59b28')) 
					AND	strpos($value['error'], 'Erreur code W0001') !== false
				) {
					// Handling 'Erreur code W0001' error
					$documentManager->updateStatus('No_send');
					$response[$idDoc] = false;
					$this->connection->commit();
					return $response;
				// Aiko - Binome vers COMET
				} elseif (
						$param['ruleId'] == '62cb3f449e55f' 
					AND	strpos($value['error'], 'Erreur code W0002') !== false
				) {
					// Handling 'Erreur code W0002' error
					$documentManager->updateStatus('Cancel');
					$response[$idDoc] = false;
					$this->connection->commit();
					return $response;
				}
				$this->connection->commit();
			} catch (\Exception $e) {
				echo 'Failed to send document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
				$this->connection->rollBack();
				$documentManager->setMessage('Failed to send document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
				$documentManager->setTypeError('E');
				$documentManager->updateStatus('Error_sending');
				$this->logger->error('Failed to send document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
				$response[$idDoc] = false;
			}
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

		// "Aiko - Binome vers COMET"
		if ($param['rule']['id'] == '62cb3f449e55f') { 
			// Check if history is available
			if (empty($param['dataHistory'][$idDoc])) {
				throw new \Exception(utf8_decode('History not available, the pair no longer exists in the COMET').'. Erreur code W0002.');
			}
		}

		// Do not send statut_volontaire_c if equal to 'DO_NOT_SEND'
		if (
				$param['rule']['id'] == '6437d9a9dcf76'	// 	Mobilisation - USC vers Contact
			AND $data['statut_volontaire_c'] == 'DO_NOT_SEND'
		) {
			unset($data['statut_volontaire_c']);
			return $data;
		}
		return parent::checkDataBeforeUpdate($param, $data, $idDoc);
	}

	// Add filter for contact module
	public function getFieldsParamUpd($type, $module): array
	{
		$param = array();
		try {
			if ($type == 'source') {
				if (in_array($module, array('Leads','CRMC_coupon_mentore'))) {
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
							'2025_2026' => '2025-2026',
							'2026_2027' => '2026-2027',
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
		// Add filter on lead type when the leads are read from SuiteCRM
		if (
				in_array($param['module'], array('Leads', 'CRMC_coupon_mentore'))
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
			// Read the current year and the next one 
			if (in_array($param['rule']['id'], array('620e5520c62d6','625fcd2ed442f','6267e128b2c87'))) {	// Sendinblue - coupon ; Mobilisation - Coupons engagé ; Mobilisation - Evenement RI 
				$query .= ' AND '.strtolower($param['module'])."_cstm.annee_scolaire_c LIKE '%".(!empty($param['ruleParams']['anneeScolaire']) ? $param['ruleParams']['anneeScolaire'] : $this->anneeScolaire3)."%' ";
			} else {
				$query .= ' AND '.strtolower($param['module'])."_cstm.annee_scolaire_c LIKE '%".(!empty($param['ruleParams']['anneeScolaire']) ? $param['ruleParams']['anneeScolaire'] : $this->anneeScolaire)."%' ";
			}
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

		// Add a filter on field situation_c = etudiant for Mentorés Accueil
		if (
				!empty($param['rule']['id'])
			AND in_array($param['rule']['id'], array('66165525cb72d')) // Sendinblue - Mentorés Accueil
		){
			$query .= ' AND '.strtolower($param['module'])."_cstm.situation_c = 'etudiant' ";
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
        return 'https://comet'.($_ENV['AFEV_ENV'] == 'PREPROD' ? '-v3.preprod' : '').'.afev.org/index.php?module='.$module.'&action=DetailView&record='.$recordId;
    }
}
