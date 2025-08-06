// Document logs data extractor

// ===== DOCUMENT LOGS FETCHER =====
export function getDocumentLogs(documentId, callback) {
    // console.log('getDocumentLogs called with documentId:', documentId);
    
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
    const pathParts = window.location.pathname.split('/');
    const publicIndex = pathParts.indexOf('public');
    let baseUrl;
    
    if (publicIndex !== -1) {
        const baseParts = pathParts.slice(0, publicIndex + 1);
        baseUrl = window.location.origin + baseParts.join('/');
    } else {
        baseUrl = window.location.origin;
    }
    
    const url = `${baseUrl}/rule/api/flux/document-logs/${documentId}`;
    // console.log('🚀 Fetching document logs from:', url);
    
    $.ajax({
        url: url,
        type: 'GET',
        beforeSend: function(xhr) {
            // console.log('📡 Sending request for document logs...');
        },
        success: function(response) {
            // console.log('✅ Document logs request successful!');
            // console.log('📊 Logs response:', response);
            
            if (response && typeof response === 'object' && response.success) {
                // console.log('📋 Logs data received:', response.data);
                callback(response.data, null);
            } else if (response && response.error) {
                console.error('❌ Server returned error:', response.error);
                callback(null, response.error);
            } else {
                console.error('❌ Unexpected response format');
                callback(null, 'Unexpected response format');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Document logs request failed!');
            console.error('Status:', status, 'Error:', error);
            
            let errorMessage = `AJAX Error: ${status} - ${error}`;
            if (xhr.status === 404) {
                errorMessage = 'Document logs endpoint not found (404)';
            } else if (xhr.status === 403) {
                errorMessage = 'Access forbidden (403)';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error (500)';
            }
            
            // console.error('📝 Final error message for logs:', errorMessage);
            callback(null, errorMessage);
        }
    });
}