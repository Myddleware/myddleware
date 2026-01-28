// Document logs data extractor

import { getBaseUrl } from './document-detail-url-utils.js';

// ===== DOCUMENT LOGS FETCHER =====
export function getDocumentLogs(documentId, callback) {
    
    // Validate parameters
    if (!documentId) {
        console.error('getDocumentLogs: documentId is required');
        if (callback) callback(null, 'Document ID is required');
        return;
    }
    
    if (!callback || typeof callback !== 'function') {
        console.error('getDocumentLogs: callback function is required');
        return;
    }
    
    // Build URL for document logs
    const baseUrl = getBaseUrl();
    const url = `${baseUrl}/rule/api/flux/document-logs/${documentId}`;
    
    $.ajax({
        url: url,
        type: 'GET',
        beforeSend: function(xhr) {
        },
        success: function(response) {
            
            if (response && typeof response === 'object' && response.success) {
                callback(response.data, null);
            } else if (response && response.error) {
                console.error(' Server returned error:', response.error);
                callback(null, response.error);
            } else {
                console.error(' Unexpected response format');
                callback(null, 'Unexpected response format');
            }
        },
        error: function(xhr, status, error) {
            console.error(' Document logs request failed!');
            console.error('Status:', status, 'Error:', error);
            
            let errorMessage = `AJAX Error: ${status} - ${error}`;
            if (xhr.status === 404) {
                errorMessage = 'Document logs endpoint not found (404)';
            } else if (xhr.status === 403) {
                errorMessage = 'Access forbidden (403)';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error (500)';
            }
            
            callback(null, errorMessage);
        }
    });
}