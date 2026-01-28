
import { getBaseUrl } from './document-detail-url-utils.js';

/**
 * Gets workflow logs data for a specific document from the API
 * @param {string} documentId - The document ID
 * @param {function} callback - Callback function(logsData, error)
 */
export function getDocumentWorkflowLogs(documentId, callback) {
    
    if (!documentId) {
        console.error(' Document ID is required for workflow logs fetch');
        callback(null, 'Document ID is required');
        return;
    }

    if (typeof callback !== 'function') {
        console.error('getDocumentWorkflowLogs: callback function is required');
        return;
    }
    
    // Build URL for document workflow logs
    const baseUrl = getBaseUrl();
    const apiUrl = `${baseUrl}/rule/api/flux/document-workflow-logs/${documentId}`;
    
    fetch(apiUrl, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
    })
    .then(response => {
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.json();
    })
    .then(data => {
        
        if (data.success && data.data) {
            callback(data.data, null);
        } else {
            callback([], data.error || 'Unknown error');
        }
    })
    .catch(error => {
        console.error(' Error fetching workflow logs data:', error);
        callback(null, error.message || error.toString());
    });
}