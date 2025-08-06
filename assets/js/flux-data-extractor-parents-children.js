// Additional functions for parent and child document fetching

// ===== DOCUMENT PARENTS FETCHER =====
export function getDocumentParents(documentId, callback) {
    console.log('getDocumentParents called with documentId:', documentId);
    
    // Validate parameters
    if (!documentId) {
        console.error('getDocumentParents: documentId is required');
        if (callback) callback(null, 'Document ID is required');
        return;
    }
    
    if (!callback || typeof callback !== 'function') {
        console.error('getDocumentParents: callback function is required');
        return;
    }
    
    // Build URL for document parents
    const pathParts = window.location.pathname.split('/');
    const publicIndex = pathParts.indexOf('public');
    let baseUrl;
    
    if (publicIndex !== -1) {
        const baseParts = pathParts.slice(0, publicIndex + 1);
        baseUrl = window.location.origin + baseParts.join('/');
    } else {
        baseUrl = window.location.origin;
    }
    
    const url = `${baseUrl}/rule/api/flux/document-parents/${documentId}`;
    // console.log('🚀 Fetching document parents from:', url);
    
    $.ajax({
        url: url,
        type: 'GET',
        beforeSend: function(xhr) {
            // console.log('📡 Sending request for document parents...');
        },
        success: function(response) {
            console.log('✅ Document parents request successful!');
            console.log('Response:', response);
            
            if (response && typeof response === 'object' && response.success) {
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
            console.error('❌ Document parents request failed!');
            console.error('Status:', status, 'Error:', error);
            
            let errorMessage = `AJAX Error: ${status} - ${error}`;
            if (xhr.status === 404) {
                errorMessage = 'Document parents endpoint not found (404)';
            } else if (xhr.status === 403) {
                errorMessage = 'Access forbidden (403)';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error (500)';
            }
            
            callback(null, errorMessage);
        }
    });
}

// ===== DOCUMENT CHILDREN FETCHER =====
export function getDocumentChildren(documentId, callback) {
    // console.log('getDocumentChildren called with documentId:', documentId);
    
    // Validate parameters
    if (!documentId) {
        console.error('getDocumentChildren: documentId is required');
        if (callback) callback(null, 'Document ID is required');
        return;
    }
    
    if (!callback || typeof callback !== 'function') {
        console.error('getDocumentChildren: callback function is required');
        return;
    }
    
    // Build URL for document children
    const pathParts = window.location.pathname.split('/');
    const publicIndex = pathParts.indexOf('public');
    let baseUrl;
    
    if (publicIndex !== -1) {
        const baseParts = pathParts.slice(0, publicIndex + 1);
        baseUrl = window.location.origin + baseParts.join('/');
    } else {
        baseUrl = window.location.origin;
    }
    
    const url = `${baseUrl}/rule/api/flux/document-children/${documentId}`;
    // console.log('🚀 Fetching document children from:', url);
    
    $.ajax({
        url: url,
        type: 'GET',
        beforeSend: function(xhr) {
            // console.log('📡 Sending request for document children...');
        },
        success: function(response) {
            // console.log('✅ Document children request successful!');
            // console.log('Response:', response);
            
            if (response && typeof response === 'object' && response.success) {
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
            console.error('❌ Document children request failed!');
            console.error('Status:', status, 'Error:', error);
            
            let errorMessage = `AJAX Error: ${status} - ${error}`;
            if (xhr.status === 404) {
                errorMessage = 'Document children endpoint not found (404)';
            } else if (xhr.status === 403) {
                errorMessage = 'Access forbidden (403)';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error (500)';
            }
            
            callback(null, errorMessage);
        }
    });
}