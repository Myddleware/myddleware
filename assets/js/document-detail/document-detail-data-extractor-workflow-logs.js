// console.log('document-detail-data-extractor-workflow-logs.js loaded');

/**
 * Gets workflow logs data for a specific document from the API
 * @param {string} documentId - The document ID
 * @param {function} callback - Callback function(logsData, error)
 */
export function getDocumentWorkflowLogs(documentId, callback) {
    // console.log('📋 getDocumentWorkflowLogs called for document:', documentId);
    
    if (!documentId) {
        console.error('❌ Document ID is required for workflow logs fetch');
        callback(null, 'Document ID is required');
        return;
    }

    if (typeof callback !== 'function') {
        console.error('getDocumentWorkflowLogs: callback function is required');
        return;
    }
    
    // Build URL for document workflow logs
    const pathParts = window.location.pathname.split('/');
    const publicIndex = pathParts.indexOf('public');
    let baseUrl;
    
    if (publicIndex !== -1) {
        // Build from public directory
        const baseParts = pathParts.slice(0, publicIndex + 1);
        baseUrl = window.location.origin + baseParts.join('/');
    } else {
        baseUrl = window.location.origin;
    }
    
    const apiUrl = `${baseUrl}/rule/api/flux/document-workflow-logs/${documentId}`;
    // console.log('📋 Fetching workflow logs from:', apiUrl);
    
    fetch(apiUrl, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
    })
    .then(response => {
        // console.log('📋 Workflow logs API response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.json();
    })
    .then(data => {
        // console.log('📋 Workflow logs API response data:', data);
        
        if (data.success && data.data) {
            // console.log('✅ Workflow logs data retrieved successfully:', data.data.length, 'logs');
            callback(data.data, null);
        } else {
            console.warn('⚠️ Workflow logs API returned error:', data.error || 'Unknown error');
            callback([], data.error || 'Unknown error');
        }
    })
    .catch(error => {
        console.error('❌ Error fetching workflow logs data:', error);
        callback(null, error.message || error.toString());
    });
}