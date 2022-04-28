<?php

namespace App\Custom\Solutions;

use App\Solutions\airtable;
use Myddleware\RegleBundle\Classes\rule;

class airtablecustom extends airtable {

	protected $tableName = array(
								'appdKFUpk2X2Ok8Dc' => 'Contacts',
								'appX0PhUGIkBTcWBE' => 'Aiko Auto Supr',
								'app5ustIjI5taRXJS' => 'CONTACTS',
							);

	protected $modules = array(
								'appdKFUpk2X2Ok8Dc' => array(
														'CONTACTS' =>	'CONTACTS',
														'BINOMES' =>	'BINOMES',
														'POLE' => 		'POLES',
														'REFERENTS' => 	'REFERENTS'
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
														'PARTICIPATION_RI' => 'PARTICIPATION RI'
													),
							);

    protected $FieldsDuplicate = array(
        'CONTACTS' => array('ID___COMET'),
        'BINOMES' => array('ID___COMET'),
        'POLE' => array('nom___du___pole'),
        'REFERENTS' => array('ID___COMET')
        );


	// Rededine read fucntion
	public function readData($param) {
		$result = parent::readData($param);
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
		}
		return $result;
	}
}