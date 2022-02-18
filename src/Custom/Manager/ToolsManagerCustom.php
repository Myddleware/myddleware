<?php
namespace App\Custom\Manager;

use App\Manager\ToolsManager;

class ToolsManagerCustom extends ToolsManager {

	// Add contact type parameter in the list
	protected $ruleParam = array('datereference','bidirectional','fieldId','mode','duplicate_fields','limit','delete', 'fieldDateRef', 'fieldId', 'targetFieldId','contactType','recordType','deletionField','deletion');

	// Allow more relationships
	public function beforeRuleEditViewRender($data) {
	
		// We allow a relationshipwith rôle Poles when we a rule has module securitygroups_users in source
		if (current(array_keys($data['source'])) == 'securitygroups_users') {
			$data['lst_rule']['5cddddcce2e3e'] = 'Poles';
			$data['lst_rule']['61a900506e8f1'] = 'Aiko pole';
		}
		
		if (current(array_keys($data['source'])) == 'securitygroups_records') {
			$data['lst_rule']['5cddddcce2e3e'] = 'Poles';
			$data['lst_rule']['5ce362b962b63'] = 'Composante';
			$data['lst_rule']['5ce3621156127'] = 'Engagé';
			$data['lst_rule']['5ce454613bb17'] = 'Formation';
			$data['lst_rule']['5d01a630c217c'] = 'Contact - Composante';
			$data['lst_rule']['5f847b2242138'] = 'Etablissement sup';
			$data['lst_rule']['61a920fae25c5'] = 'Aiko Contacts';
			$data['lst_rule']['61a900506e8f1'] = 'Aiko Pôles';
			$data['lst_rule']['61a930273441b'] = 'Aiko Binomes';
		}

		// Une règle avec le module fp_events_contacts_c peut être liée aux module engagé et formation
		if (current(array_keys($data['source'])) == 'fp_events_contacts_c') {
			$data['lst_rule']['5ce3621156127'] = 'Engagé';
			$data['lst_rule']['5ce454613bb17'] = 'Formation';
			$data['lst_rule']['60c09c8dd8db8'] = 'Formation session';
		}
		
		// Une règle avec le module Contacts de SuiteCRM peut être liée aux module user de MySQL
		if (current(array_keys($data['source'])) == 'Contacts') {
			$data['lst_rule']['5cf98651a17f3'] = 'User';
		}
		
		// Une règle avec le module Binôme de SuiteCRM peut être liée aux module user de MySQL
		if (current(array_keys($data['source'])) == 'CRMC_binome') {
			$data['lst_rule']['5cf98651a17f3'] = 'User';
			$data['lst_rule']['61a9190e40965'] = 'Aiko Référent';
		}
		
		// Une règle avec le module accounts_contacts peut être liée aux module Composante et Contact - Composante
		if (current(array_keys($data['source'])) == 'accounts_contacts') {
			$data['lst_rule']['5ce362b962b63'] = 'Composante';
			$data['lst_rule']['5d01a630c217c'] = 'Contact - Composante';
			$data['lst_rule']['5ce3621156127'] = 'Engagé';
		}
		return $data;
	}
} 

