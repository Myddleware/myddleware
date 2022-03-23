<?php

namespace App\Custom\Solutions;

use App\Solutions\sendinblue;
use App\Manager\DocumentManager;

class sendinbluecustom extends sendinblue {

    // Update the record 
    protected function update($param, $record, $idDoc = null) {  
		if (!in_array($param['ruleId'], array('620d3e768e678', '620e5520c62d6'))) {
			return parent::update($param, $record, $idDoc);
		}

		// Custom code for contacts
		// Specific action for rules Sendinblue - contact and Sendinblue - coupon
        try {
            $identifier = parent::update($param, $record, $idDoc);
		} catch (\Exception $e) {
			// If update didn't work and there is an error 
			if (
					strpos($e->getMessage(), 'duplicate_parameter') !== false
				 OR strpos($e->getMessage(), 'Contact already exist') !== false	
			) {
				$mask = $this->searchDuplicate($param, $record);
				if (!empty($mask)) {
					$first = true;
					foreach ($mask as $key => $value) {
						// The first record is the one we keep
						if ($first) {
							$first = false;
							$contactNotDeleted = $key;
							continue;
						}
						// Delete the other ones in Sendinblue
						$contactIdDeleted = $this->removeContact($param, $key, $idDoc);			
						// If the partner deleted is the one in the current document, we change the target id of the document with the one that won't be deleted
						if ($contactIdDeleted == $record['target_id']) {
							$paramDoc['id_doc_myddleware'] = $idDoc;
							$paramDoc['jobId'] = $param['jobId'];
							$documentManager = new DocumentManager($this->logger, $this->connection, $this->entityManager);
							$documentManager->setParam($paramDoc);
							$documentManager->updateTargetId($contactNotDeleted);
						}
					}
				}
			}
            throw new \Exception($e->getMessage());	
        }    
        return $identifier; 
    }
	
	// Remove a contact from Sendinblue
	protected function removeContact($param, $contactId, $idDoc) {
		// Get all source field of the ruleId
		$paramDoc['id_doc_myddleware'] = $idDoc;
		$paramDoc['jobId'] = $param['jobId'];
		$documentManager = new DocumentManager($this->logger, $this->connection, $this->entityManager);
		$documentManager->setParam($paramDoc);			
		$documentManager->generateDocLog('S', 'Try to delete contact '.$contactId.' from Sendinblue.');

		try {
			$result = $this->delete($param, array('target_id' => $contactId));
			$documentManager->generateDocLog('S', 'Contact '.$contactId.' deleted from Sendinblue.');
        } catch (\Exception $e) {
            $documentManager->generateDocLog('E', 'Failed to delete the contact '.$contactId.' from Sendinblue.');	
			throw new \Exception('Failed to delete the contact '.$contactId.' from Sendinblue : '. $e->getMessage());	
        }
		
		return $contactId;
	}
	
	protected function searchDuplicate($param, $record) {
		$mask = array();
		$apiInstance = new \SendinBlue\Client\Api\ContactsApi( new \GuzzleHttp\Client(), $this->config ); 

		// Search using id
		if (!empty($record['target_id'])) {
			$resultApi = array();
			$resultApi = $apiInstance->getContactInfo($record['target_id']);
			if (!empty(current($resultApi)['id'])) {
				$duplicates[current($resultApi)['id']] = $resultApi;
			}
		}
		// Search using email address
		if (!empty($record['email'])) {
			$resultApi = array();
			$resultApi = $apiInstance->getContactInfo($record['email']);
			if (!empty(current($resultApi)['id'])) {
				$duplicates[current($resultApi)['id']] = $resultApi;
			}
		}
		// Search using SMS
		if (!empty($record['SMS'])) {
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
			// Put the biggest value first	
			arsort($mask);
		}
		
		return $mask;
	}
}
