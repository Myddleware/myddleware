// file that handle the extraction of data from the document
// console.log('flux-data-extractor.js loaded');

// Cache for document data to avoid repeated API calls
let documentDataCache = new Map();

// ===== COMPREHENSIVE DOCUMENT DATA FETCHER =====
export function getDocumentData(documentId, callback) {
    console.log('getDocumentData called with documentId:', documentId);
    
    // Check cache first
    if (documentDataCache.has(documentId)) {
        // console.log('📋 Using cached data for document:', documentId);
        const cachedData = documentDataCache.get(documentId);
        if (callback) callback(cachedData, null);
        return;
    }
    
    // Validate parameters
    if (!documentId) {
        console.error('getDocumentData: documentId is required');
        if (callback) callback(null, 'Document ID is required');
        return;
    }
    
    if (!callback || typeof callback !== 'function') {
        console.error('getDocumentData: callback function is required');
        return;
    }
    
    // Build URL for comprehensive document data
    const pathParts = window.location.pathname.split('/');
    const publicIndex = pathParts.indexOf('public');
    let baseUrl;
    
    if (publicIndex !== -1) {
        const baseParts = pathParts.slice(0, publicIndex + 1);
        baseUrl = window.location.origin + baseParts.join('/');
    } else {
        baseUrl = window.location.origin;
    }
    
    const url = `${baseUrl}/rule/api/flux/document-data/${documentId}`;
    // console.log('🚀 Fetching comprehensive document data from:', url);
    
    $.ajax({
        url: url,
        type: 'GET',
        beforeSend: function(xhr) {
            // console.log('📡 Sending request for document data...');
        },
        success: function(response) {
            // console.log('✅ Document data request successful!');
            // console.log('Response:', response);
            
            if (response && typeof response === 'object' && response.success) {
                // Cache the data
                documentDataCache.set(documentId, response.data);
                // console.log('💾 Cached document data for:', documentId);
                
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
            console.error('❌ Document data request failed!');
            console.error('Status:', status, 'Error:', error);
            
            let errorMessage = `AJAX Error: ${status} - ${error}`;
            if (xhr.status === 404) {
                errorMessage = 'Document data endpoint not found (404)';
            } else if (xhr.status === 403) {
                errorMessage = 'Access forbidden (403)';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error (500)';
            }
            
            callback(null, errorMessage);
        }
    });
}

// ===== MODULAR DATA EXTRACTION FUNCTIONS =====

export function extractRuleInfo(documentData) {
    if (!documentData) return { name: null, id: null };
    
    return {
        name: documentData.rule_name || null,
        id: documentData.rule_id || null,
        url: documentData.rule_url || null
    };
}

export function extractDocumentStatus(documentData) {
    if (!documentData) return null;
    
    return {
        // Basic status values
        status: documentData.status || null,
        globalStatus: documentData.global_status || null,
        
        // Display labels with proper formatting
        status_label: documentData.status_label || null,
        global_status_label: documentData.global_status_label || null,
        
        // Color classes for styling
        status_class: documentData.status_class || null,
        global_status_class: documentData.global_status_class || null
    };
}

export function extractDocumentType(documentData) {
    if (!documentData) return null;
    
    return {
        type: documentData.type || null,
        typeLabel: documentData.type_label || null
    };
}

export function extractDocumentAttempts(documentData) {
    if (!documentData) return null;
    
    return {
        attempt: documentData.attempt || 0,
        maxAttempts: documentData.max_attempts || null
    };
}

export function extractDocumentDates(documentData) {
    if (!documentData) return null;
    
    return {
        creationDate: documentData.creation_date || null,
        modificationDate: documentData.modification_date || null,
        reference: documentData.reference || null
    };
}

// ===== DOCUMENT HISTORY FETCHER =====
export function getDocumentHistory(documentId, callback) {
    // console.log('getDocumentHistory called with documentId:', documentId);
    // Validate parameters
    if (!documentId) {
        console.error('getDocumentHistory: documentId is required');
        if (callback) callback(null, 'Document ID is required');
        return;
    }
    
    if (!callback || typeof callback !== 'function') {
        console.error('getDocumentHistory: callback function is required');
        return;
    }
    
    // Build URL for document history
    const pathParts = window.location.pathname.split('/');
    const publicIndex = pathParts.indexOf('public');
    let baseUrl;
    
    if (publicIndex !== -1) {
        const baseParts = pathParts.slice(0, publicIndex + 1);
        baseUrl = window.location.origin + baseParts.join('/');
    } else {
        baseUrl = window.location.origin;
    }
    
    const url = `${baseUrl}/rule/api/flux/document-history/${documentId}`;
    // console.log('🚀 Fetching document history from:', url);
    
    $.ajax({
        url: url,
        type: 'GET',
        beforeSend: function(xhr) {
            // console.log('📡 Sending request for document history...');
        },
        success: function(response) {
            // console.log('✅ Document history request successful!');
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
            console.error('❌ Document history request failed!');
            console.error('Status:', status, 'Error:', error);
            
            let errorMessage = `AJAX Error: ${status} - ${error}`;
            if (xhr.status === 404) {
                errorMessage = 'Document history endpoint not found (404)';
            } else if (xhr.status === 403) {
                errorMessage = 'Access forbidden (403)';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error (500)';
            }
            
            callback(null, errorMessage);
        }
    });
}