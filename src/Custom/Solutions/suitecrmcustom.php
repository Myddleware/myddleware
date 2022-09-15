<?php

namespace App\Custom\Solutions;

use App\Solutions\suitecrm;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

//Sinon on met la classe suivante
class suitecrmcustom extends suitecrm
{

	protected $limitCall = 100;
	public $anneeScolaire = '2022_2023';
	public $anneeScolaire2 = '2022'; // used to select 2 years
	// protected $moduleWithAnnee = array('Contacts', 'CRMC_binome', 'CRMC_Suivi','FP_events');
	protected $moduleWithAnnee = array('Contacts', 'FP_events');
	protected $urlSuffix = '/custom/service/v4_1_custom/rest.php';
	protected $currentRule;

	protected $FieldsDuplicate = [
		'Contacts' => ['email1', 'last_name', 'Myddleware_element_id'],
		'Accounts' => ['email1', 'name'],
		'Users' => ['email1', 'last_name'],
		'Leads' => ['email1', 'last_name'],
		'Prospects' => ['email1', 'name'],
		'default' => ['name'],
	];

	// Add aiko field to be able to filter on it
	public function get_module_fields($module, $type = 'source', $param = null)
	{
		parent::get_module_fields($module, $type);
		if ($module == 'Contacts') {
			$this->moduleFields['aiko'] = array(
				'label' => 'Aïko',
				'type' => 'varchar(255)',
				'type_bdd' => 'varchar(255)',
				'required' => 0,
				'relate' => false
			);
		}
		if ($module == 'Accounts') {
			$this->moduleFields['myd_filtered'] = array(
				'label' => 'Filtre Myddleware',
				'type' => 'varchar(255)',
				'type_bdd' => 'varchar(255)',
				'required' => 0,
				'relate' => false
			);
		}
		return $this->moduleFields;
	}

	protected function call($method, $parameters)
	{
		if ($this->currentRule == '61a920fae25c5') {
			$parameters['link_name_to_fields_array'][] = array('name' => 'crmc_binome_contacts', 'value' => array('id', 'statut_c', 'chatbot_c'));
			$parameters['link_name_to_fields_array'][] = array('name' => 'crmc_binome_contacts_1', 'value' => array('id', 'statut_c', 'chatbot_c'));
		}

		$result = parent::call($method, $parameters);

		if ($this->currentRule == '61a920fae25c5') {
			if (!empty($result->relationship_list)) {
				foreach ($result->relationship_list as $key => $relationship) {
					$aiko = new \stdClass();
					$aiko->name = 'aiko';
					$aiko->value = '0';
					$result->entry_list[$key]->name_value_list->aiko = $aiko;
					if (
						!empty($relationship
							and !empty($relationship->link_list[0]->records))
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
				}
			}
		}
		return $result;
	}

	public function readData($param)
	{
		$this->currentRule = $param['rule']['id'];
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
		if (
			$param['rule']['id'] == '5ce362b962b63'
			and !empty($read)
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
		}
		return $read;
	}

	// Permet de mettre à jour un enregistrement
	public function updateData($param)
	{
		if ($param['rule']['id'] == '62ff32cd9b6fb' || $param['rule']['id'] == '6321c09e5a1b2') {
			foreach($param['data'] as $idDoc => $data) {
				unset($param['data'][$idDoc]['name']);
			}	
		}

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
		if ($param['rule']['id'] == '62ff32cd9b6fb') {
			
			// This requires a custom formula from Nom_etablissement in internallitst 
			// To name in suiteCrm
			if(!empty($param['data'][$idDoc]['description'])) {
					$data['description'] = $param['dataHistory'][$idDoc]['description'] . " - Nom officiel: ".$param['data'][$idDoc]['description'];
				}
			return $data;
		}
		return parent::checkDataBeforeUpdate($param, $data, $idDoc);
	}

	// Add filter for contact module
	public function getFieldsParamUpd($type, $module)
	{
		try {
			if ($type == 'source') {
				if ($module == 'Contacts') {
					$param = array(
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
					return array($param);
				}
				if ($module == 'Leads') {
					$param = array(
						'id' => 'leadType',
						'name' => 'leadType',
						'type' => 'text',
						'label' => 'Coupon type',
						'required'	=> false
					);
					return array($param);
				}
			}
			return array();
		} catch (\Exception $e) {
			return array();
		}
	}

	public function getRuleMode($module, $type)
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
	protected function generateQuery($param, $method)
	{
		// Call the standard function
		$query = parent::generateQuery($param, $method);
		// Add filter on contact type when the contacts are read from SuiteCRM

		//if my rule and module = 
		if (strpos($query, 'type_de_partenaire_c') !== false && $param['module'] == 'Accounts' && $param['rule']['id'] == '62ff32cd9b6fb') {
			$query = "accounts_cstm.type_de_partenaire_c IN ('ecole_maternelle', '8', '10') ";
		}

		// if ($param['module'] == 'mod_2_quartiers' && $param['rule']['id'] == '6321c09e5a1b2') {
		// 	$query = "mod_2_quartiers_cstm.quartier_prioritaire_c IN (1) ";
		// }

		

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
		if (
			in_array($param['module'], $this->moduleWithAnnee)
			and $param['call_type'] != 'history'
		) {
			// Allows to filter on 2 years for Aïko (to be removed once the data are fixed) 
			if (
				!empty($param['rule']['id'])
				and ($param['rule']['id'] == '61a920fae25c5' // Aiko contact
					or $param['module'] == 'CRMC_binome' 		// Binôme des 2 dernière années
				)
			) {
				$query .= ' AND ' . strtolower($param['module']) . "_cstm.annee_scolaire_c LIKE '%" . $this->anneeScolaire2 . "%' ";
			} else {
				$query .= ' AND ' . strtolower($param['module']) . "_cstm.annee_scolaire_c LIKE '%" . $this->anneeScolaire . "%' ";
			}
		}
		// Add a filter for contact universite 
		if (
			!empty($param['rule']['id'])
			and $param['rule']['id'] == '5d01a630c217c' // Contact - Université
		) {
			$query .= ' AND ' . strtolower($param['module']) . "_cstm.reec_c = 'contact_universite' ";
		}
		// Add a filter for contact reperant 
		if (
			!empty($param['rule']['id'])
			and $param['rule']['id'] == '6273905a05cb2' // Esp Rep - Contacts repérants
		) {
			$query .= ' AND ' . strtolower($param['module']) . "_cstm.reec_c = 'contact_reperant' ";
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
}
