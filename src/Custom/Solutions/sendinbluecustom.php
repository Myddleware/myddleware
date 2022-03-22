<?php

namespace App\Custom\Solutions;

use App\Solutions\sendinblue;

class sendinbluecustom extends sendinblue {

    // Update the record 
    protected function update($param, $record) {  
		if (!in_array($param['ruleId'], array('620d3e768e678'))) {
			return parent::update($param, $record);
		}
// print_r($param);		
		// Specific action for rules Sendinblue - contact and Sendinblue - coupon
        try {
            $identifier = parent::update($param, $record);
		} catch (\Exception $e) {
			if (strpos($e->getMessage(), 'duplicate_parameter') !== false) {
				$mask = $this->searchDuplicate($param, $record);
				if (!empty($mask)) {
					$first = true;
					foreach ($mask as $key => $value) {
						// The first record is the one we keep^
						if ($first) {
							$first = false;
							continue;
						}
						// Delete the other ones in Sendinblue
						$this->removeContact($param['ruleId'], $key);					
					}
				}
			}
            throw new \Exception($e->getMessage());	
        }    
        return $identifier; 
    }
	
	protected function removeContact($ruleId, $contactId) {
		echo 'a suppr : '.$contactId.chr(10);
		// Get all source field of the ruleId
		
		// Empty them and set deletion field to 1
		
		// generate document
		
		// $deletionParam['values']['statut_c'] = 'suppr'; 
		// $deletionParam['values']['chatbot_c'] = 'suppr'; 
		// $deletionParam['values']['fin'] = ''; 
		// $deletionParam['values']['mise_en_place_c'] = ''; 
		// $deletionParam['values']['name'] = ''; 
		// $deletionParam['values']['annee_scolaire_c'] = ''; 
		// $deletionParam['values']['deleted'] = ''; 
		// $deletionParam['values']['heure_babituelle_rencontre_c'] = ''; 
		// $deletionParam['values']['jour_habituel_rencontre_c'] = ''; 
		// $deletionParam['values']['lieu_habituel_rencontre_c'] = ''; 
		// $deletionParam['values']['precision_lieu_c'] = ''; 
		
		$ruleParam['ruleId'] = $ruleId; 
		// $ruleParam['jobId'] = $param['jobId']; 			
		// $ruleDeletion = new rule($this->logger, $this->container, $this->conn, $ruleParam);				
		
		// $deletionParam['values']['myddleware_deletion'] = true;
		// $deletionParam['values']['id'] = $values['IDCOMET'];
		// $deletionParam['values']['date_modified'] = gmdate('Y-m-d H:i:s');					
		// $documents = $ruleDeletion->generateDocuments($values['IDCOMET'], false, $deletionParam); 
	}
	
	protected function searchDuplicate($param, $record) {
		$mask = array();
		$apiInstance = new \SendinBlue\Client\Api\ContactsApi( new \GuzzleHttp\Client(), $this->config ); 
print_r($record);
		// Search using id
		if (!empty($record['target_id'])) {
echo 'id'.chr(10);
			$resultApi = array();
			$resultApi = $apiInstance->getContactInfo($record['target_id']);
echo 'id'.current($resultApi)['id'].chr(10);
			if (!empty(current($resultApi)['id'])) {
				$duplicates[current($resultApi)['id']] = $resultApi;
			}
		}
		// Search using email address
		if (!empty($record['email'])) {
echo 'email'.chr(10);
			$resultApi = array();
			$resultApi = $apiInstance->getContactInfo($record['email']);
			if (!empty(current($resultApi)['id'])) {
				$duplicates[current($resultApi)['id']] = $resultApi;
			}
		}
		// Search using SMS
		if (!empty($record['SMS'])) {
echo 'SMS'.chr(10);
			$resultApi = array();
			$resultApi = $apiInstance->getContactInfo($record['SMS']);
			if (!empty(current($resultApi)['id'])) {
				$duplicates[current($resultApi)['id']] = $resultApi;
			}
		}
		
		// in case of duplicate found
		if (count($duplicates) > 1) {	
			foreach ($duplicates as $key => $duplicate) {		
				$mask[$key] = (empty($duplicate['emailBlacklisted']) ? '1' : '0'). // We keep the bigger so if emailBlacklisted empty then 1
							  (empty(current($duplicate['statistics'])['messagesSent']) ? '0' : current($duplicate['statistics'])['messagesSent']).
							  (empty(current($duplicate['statistics'])['opened']) ? '0' : current($duplicate['statistics'])['opened']).
							  (empty(current($duplicate['statistics'])['clicked']) ? '0' : current($duplicate['statistics'])['clicked']).
							  (($duplicate['id'] ==  $record['target_id']) ? '1' : '0'); // If records equal, we keep the one that Myddleware has created
			}
			// The biggest value first	
			arsort($mask);
		}
print_r($duplicates);
print_r($mask);
		
		return $mask;
	}
}
