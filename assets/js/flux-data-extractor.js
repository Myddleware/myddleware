// file that handle the extraction of data from the document
console.log('flux-data-extractor.js loaded');

// Cache for document data to avoid repeated API calls
let documentDataCache = new Map();

// ===== COMPREHENSIVE DOCUMENT DATA FETCHER =====
export function getDocumentData(documentId, callback) {
    console.log('getDocumentData called with documentId:', documentId);
    
    // Check cache first
    if (documentDataCache.has(documentId)) {
        console.log('📋 Using cached data for document:', documentId);
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
    console.log('🚀 Fetching comprehensive document data from:', url);
    
    $.ajax({
        url: url,
        type: 'GET',
        beforeSend: function(xhr) {
            console.log('📡 Sending request for document data...');
        },
        success: function(response) {
            console.log('✅ Document data request successful!');
            console.log('Response:', response);
            
            if (response && typeof response === 'object' && response.success) {
                // Cache the data
                documentDataCache.set(documentId, response.data);
                console.log('💾 Cached document data for:', documentId);
                
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

export function extractSourceData(documentData) {
    if (!documentData) return null;
    
    return {
        sourceData: documentData.source_data || null,
        sourceFields: documentData.source_fields || null
    };
}

export function extractTargetData(documentData) {
    if (!documentData) return null;
    
    return {
        targetData: documentData.target_data || null,
        targetFields: documentData.target_fields || null
    };
}

export function extractHistoryData(documentData) {
    if (!documentData) return null;
    
    return {
        historyData: documentData.history_data || null,
        errorMessage: documentData.error_message || null,
        logs: documentData.logs || null
    };
}

// ===== CONVENIENCE FUNCTIONS =====

export function getAndExtractRuleInfo(documentId, callback) {
    getDocumentData(documentId, function(data, error) {
        if (error) {
            callback(null, error);
            return;
        }
        
        const ruleInfo = extractRuleInfo(data);
        callback(ruleInfo, null);
    });
}

export function getAndExtractDocumentStatus(documentId, callback) {
    getDocumentData(documentId, function(data, error) {
        if (error) {
            callback(null, error);
            return;
        }
        
        const status = extractDocumentStatus(data);
        callback(status, null);
    });
}

// ===== LEGACY COMPATIBILITY =====
// Keep the original getRuleName function for backward compatibility
export function getRuleName(documentId, callback) {
    console.log('getRuleName called with documentId:', documentId);
    
    // Use the new comprehensive function but maintain the old callback signature
    getAndExtractRuleInfo(documentId, function(ruleInfo, error) {
        if (error) {
            callback(null, null, error);
            return;
        }
        
        callback(ruleInfo.name, ruleInfo.id, null);
    });
}

// Helper function to get rule data and automatically update href attributes
export function updateRuleLinks(documentId, linkElementId) {
    getRuleName(documentId, function(ruleName, ruleId, error) {
        if (error) {
            console.error('Failed to update rule links:', error);
            return;
        }
        
        if (ruleName && ruleId) {
            const linkElement = document.getElementById(linkElementId);
            if (linkElement) {
                // Get the base URL for consistency
                const pathParts = window.location.pathname.split('/');
                const publicIndex = pathParts.indexOf('public');
                let baseUrl = window.location.origin;
                if (publicIndex !== -1) {
                    const baseParts = pathParts.slice(0, publicIndex + 1);
                    baseUrl = window.location.origin + baseParts.join('/');
                }
                
                linkElement.href = `${baseUrl}/rule/view/${ruleId}`;
                linkElement.textContent = ruleName;
                console.log('✅ Updated link:', linkElement.href);
            } else {
                console.warn('Element with ID', linkElementId, 'not found');
            }
        }
    });
}