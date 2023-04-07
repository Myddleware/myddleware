<?php
namespace App\Custom\Manager;

use App\Manager\ToolsManager;

class ToolsManagerCustom extends ToolsManager {

	// Add contact type parameter in the list
	protected array $ruleParam = array('datereference','bidirectional','fieldId','mode','duplicate_fields','limit','delete', 'fieldDateRef', 'fieldId', 'targetFieldId','contactType','recordType','deletionField','deletion');

	// Allow more relationships
	public function beforeRuleEditViewRender($data) {
	
		// We allow a relationshipwith rôle Poles when we a rule has module securitygroups_users in source
		if (current(array_keys($data['source'])) == 'securitygroups_users') {
			$data['lst_rule']['5cddddcce2e3e'] = 'Poles';
			$data['lst_rule']['61a900506e8f1'] = 'Aiko pole';
		}
		
		if (current(array_keys($data['source'])) == 'securitygroups_records') {
			$data['lst_rule']['5cddddcce2e3e'] = 'REEC - Poles';
			$data['lst_rule']['5ce362b962b63'] = 'REEC - Composante';
			$data['lst_rule']['5ce3621156127'] = 'REEC - Engagé';
			$data['lst_rule']['5ce454613bb17'] = 'REEC - Formation';
			$data['lst_rule']['5d01a630c217c'] = 'REEC - Contact - Composante';
			$data['lst_rule']['5f847b2242138'] = 'REEC - Etablissement sup';
			$data['lst_rule']['62738aabafcd2'] = 'Esp Rep - Quartiers';
			$data['lst_rule']['6273905a05cb2'] = 'Esp Rep - Contacts repérants';
			$data['lst_rule']['62739b419755f'] = 'Esp Rep - Coupons vers Esp Rep';
			$data['lst_rule']['61a920fae25c5'] = 'Aiko Contacts';
			$data['lst_rule']['61a900506e8f1'] = 'Aiko Pôles';
			$data['lst_rule']['61a930273441b'] = 'Aiko Binomes';
			// Pôle relationship for mobilisation
			$data['lst_rule']['62596c212a4cb'] = 'Mobilisation - Etablissements';
			$data['lst_rule']['625fcd2ed442f'] = 'Mobilisation - Coupons';
			$data['lst_rule']['625fd5aeb4d29'] = 'Mobilisation - Utilisateurs';
			$data['lst_rule']['6267e128b2c87'] = 'Mobilisation - Evenement RI';
			$data['lst_rule']['6267e9c106873'] = 'Mobilisation - Composantes';
			$data['lst_rule']['62690f697e610'] = 'Mobilisation - Pôles';
		}

		// Une règle avec le module fp_events_contacts_c peut être liée aux module engagé et formation
		if (current(array_keys($data['source'])) == 'fp_events_contacts_c') {
			$data['lst_rule']['5ce3621156127'] = 'REEC - Engagé';
			$data['lst_rule']['5ce454613bb17'] = 'REEC - Formation';
			$data['lst_rule']['60c09c8dd8db8'] = 'REEC - Formation session';
		}
		
		// Mobilisation
		if (current(array_keys($data['source'])) == 'fp_events_leads_1_c') {
			$data['lst_rule']['625fcd2ed442f'] = 'Mobilisation - Coupons';
			$data['lst_rule']['6267e128b2c87'] = 'Mobilisation - Evenement RI';
		}
		// Mobilisation
		if (current(array_keys($data['source'])) == 'PARTICIPATION_RI') {
			$data['lst_rule']['625fcd2ed442f'] = 'Mobilisation - Coupons';
			$data['lst_rule']['62695220e54ba'] = 'Mobilisation - Coupons vers Comet';
			$data['lst_rule']['6267e128b2c87'] = 'Mobilisation - Evenement RI';
		}

		if (current(array_keys($data['source'])) == 'Leads') {
			$data['lst_rule']['62690f697e610'] = 'Mobilisation - Pôles';
		}
		
		// Une règle avec le module Contacts de SuiteCRM peut être liée aux module user de MySQL
		if (current(array_keys($data['source'])) == 'Contacts') {
			$data['lst_rule']['5cf98651a17f3'] = 'REEC - User';
		}
		
		// Une règle avec le module Binôme de SuiteCRM peut être liée aux module user de MySQL
		if (current(array_keys($data['source'])) == 'CRMC_binome') {
			$data['lst_rule']['5cf98651a17f3'] = 'REEC - User';
			$data['lst_rule']['61a9190e40965'] = 'Aiko Référent';
		}
		
		// Une règle avec le module accounts_contacts peut être liée aux module Composante et Contact - Composante
		if (current(array_keys($data['source'])) == 'accounts_contacts') {
			$data['lst_rule']['5ce362b962b63'] = 'REEC - Composante';
			$data['lst_rule']['5d01a630c217c'] = 'REEC - Contact - Composante';
			$data['lst_rule']['5ce3621156127'] = 'REEC - Engagé';
			$data['lst_rule']['5cdf83721067d'] = 'REEC - Jeune accompagné';
			$data['lst_rule']['6273905a05cb2'] = 'Esp Rep - Contacts repérants';
		}
		
		if (current(array_keys($data['source'])) == 'CRMC__etablissement_sup') {
			$data['lst_rule']['5cddddcce2e3e'] = 'REEC - Poles';
		}

		// Add bidirectional rule for Mobilisation - Participation RI
		if ($data['regleId'] == '6281633dcddf1') { // Mobilisation - Participation RI -> comet
				$data['rule_params'][] = array(
									'id' => 'bidirectional',
									'name' => 'bidirectional',
									'required' => false,
									'type' => 'option',
									'label' => 'create_rule.step3.params.sync',
									'option' => array('627153382dc34' => 'Mobilisation - Participations RI'));
		}
		
		if ($data['regleId'] == '627153382dc34') {	// Mobilisation - Participations RI
				$data['rule_params'][] = array(
									'id' => 'bidirectional',
									'name' => 'bidirectional',
									'required' => false,
									'type' => 'option',
									'label' => 'create_rule.step3.params.sync',
									'option' => array('6281633dcddf1' => 'Mobilisation - Participation RI -> comet'));
		}

		return $data;
	}
} 

