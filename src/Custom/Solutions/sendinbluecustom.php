<?php

namespace App\Custom\Solutions;

use App\Solutions\sendinblue;

class sendinbluecustom extends sendinblue {

    // Update the record 
    protected function update($param, $record) {  
		if (!in_array($param['ruleId'], array('620d3e768e678'))) {
			return parent::update($param, $record);
		}
		
		// Specific action for rules Sendinblue - contact and Sendinblue - coupon
        try {
            $identifier = parent::update($param, $record);
		} catch (\Exception $e) {
			if (strpos($e->getMessage(), 'duplicate_parameter') !== false) {
				$this->searchDuplicate($param, $record);
			}
            throw new \Exception($e->getMessage());	
        }    
        return $identifier; 
    }
	
	protected function searchDuplicate($param, $record) {
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
				$mask[$key] = (empty($duplicate['emailBlacklisted']) ? '0' : '1').
							  (empty(current($duplicate['statistics'])['messagesSent']) ? '0' : current($duplicate['statistics'])['messagesSent']).
							  (empty(current($duplicate['statistics'])['opened']) ? '0' : current($duplicate['statistics'])['opened']).
							  (empty(current($duplicate['statistics'])['clicked']) ? '0' : current($duplicate['statistics'])['clicked']);
			}
		}
print_r($duplicates);
	}
}
