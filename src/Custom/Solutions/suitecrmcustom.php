<?php

namespace App\Custom\Solutions;

use App\Solutions\suitecrm;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

//Sinon on met la classe suivante
class suitecrmcustom extends suitecrm {
	
	protected $limitCall = 100;
	public $anneeScolaire = '2021_2022';
	public $anneeScolaire2 = '2021'; // used to select 2 years
	// protected $moduleWithAnnee = array('Contacts', 'CRMC_binome', 'CRMC_Suivi','FP_events');
	protected $moduleWithAnnee = array('Contacts', 'FP_events');
	protected $urlSuffix = '/custom/service/v4_1_custom/rest.php';
	protected $currentRule;
	
	
	// Add aiko field to be able to filter on it
	public function get_module_fields($module, $type = 'source', $param = null) {
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
	
	protected function call($method, $parameters){
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
						AND !empty($relationship->link_list[0]->records)
					)) {						
						foreach ($relationship->link_list[0]->records as $binome) {
							// Use the same filter than Airtable
							if (
									!empty($binome->link_value->statut_c->value)
								AND $binome->link_value->statut_c->value <> 'termine' 	
								AND $binome->link_value->statut_c->value <> 'annule' 	
								AND $binome->link_value->statut_c->value <> 'accompagnement_termine' 	
								AND !empty($binome->link_value->chatbot_c->value)
								AND $binome->link_value->chatbot_c->value <> 'non'
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
	
	public function readData($param) {		
		$this->currentRule = $param['rule']['id'];		
		return parent::readData($param);
	}

	
	// Redifine read function
	public function read($param) {
		$read = parent::read($param);
		if (
				$param['rule']['id'] == '5ce362b962b63'
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
					 OR in_array($record['type_de_partenaire_c'], array('8','ecole_maternelle','10','1','11','9','lycee_polyvalent'))
				) {
					$read[$key]['myd_filtered'] = 0;
				}			
			}
		}		
		return $read;
	}
	
	// Add filter for contact module
	public function getFieldsParamUpd($type, $module) {	
		try {
			if ($type == 'source'){
				if ($module == 'Contacts'){
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
				if ($module == 'Leads'){
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
		}
		catch (\Exception $e){
			return array();
		}
	}
	
	// Build the query for read data to SuiteCRM
	protected function generateQuery($param, $method){
		// Call the standard function
		$query = parent::generateQuery($param, $method);
		// Add filter on contact type when the contacts are read from SuiteCRM
		if (
				$param['module'] == 'Contacts'
			AND !empty($param['ruleParams']['contactType'])	
		) {
			if ($param['ruleParams']['contactType'] == 'non_contact_partenaire') {
				$query .= ' AND '.strtolower($param['module'])."_cstm.contact_type_c <> 'contact_partenaire' ";
			}elseif ($param['ruleParams']['contactType'] == 'non_accompagne') {
				$query .= ' AND '.strtolower($param['module'])."_cstm.contact_type_c <> 'Accompagne' ";
			} else {
				$query .= ' AND '.strtolower($param['module'])."_cstm.contact_type_c = '".$param['ruleParams']['contactType']."' ";
			}
		}
		// Add filter on lead type when the leads are read from SuiteCRM
		if (
				$param['module'] == 'Leads'
			AND !empty($param['ruleParams']['leadType'])	
		) {
			$query .= " AND ".strtolower($param['module'])."_cstm.coupon_type_c IN (".$param['ruleParams']['leadType'].") ";
		}
		// filter by annee
		if (in_array($param['module'], $this->moduleWithAnnee)) {
			// Allows to filter on 2 years for Aïko (to be removed once the data are fixed) 
			if (
					!empty($param['rule']['id'])
				AND (
						$param['rule']['id'] == '61a920fae25c5' // Aiko contact
					 OR $param['module'] == 'CRMC_binome' 		// Binôme des 2 dernière années
				)
			){
				$query .= ' AND '.strtolower($param['module'])."_cstm.annee_scolaire_c LIKE '%".$this->anneeScolaire2."%' ";
			} else {
				$query .= ' AND '.strtolower($param['module'])."_cstm.annee_scolaire_c LIKE '%".$this->anneeScolaire."%' ";
			}
		}
		// Add a filter for contact universite (only the one with reec set to 1)
		if (
				!empty($param['rule']['id'])
			AND $param['rule']['id'] == '5d01a630c217c' // Contact - Université
		){
			$query .= ' AND '.strtolower($param['module'])."_cstm.reec_c = 'Oui' ";
		}	
		return $query;
	}
	
	// Permet de récupérer le dernier enregistrement de la solution (utilisé pour tester le flux)
	public function send_query($query) {		
		try {
			$get_parameters = array(
										'session' => $this->session,
										'query' => $query
									);													
			return $this->call("send_special_query", $get_parameters);		
		}
		catch (\Exception $e) {
			throw new \Exception('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
		}	
		return false;
	}

}
