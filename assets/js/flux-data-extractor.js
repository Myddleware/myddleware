// file that handle the extraction of data from the document
console.log('flux-data-extractor.js loaded');

// we start by testing the extraction of data from the document with id 6863a07946e8b9.38306852

// let's start by getting the rule

export function getRuleName(documentId, callback) {
    console.log('getRuleName called with documentId:', documentId);
    
    // Validate documentId parameter
    if (!documentId) {
        console.error('getRuleName: documentId is required but was not provided');
        if (callback) callback(null, null, 'Document ID is required');
        return;
    }
    
    // Validate callback parameter
    if (!callback || typeof callback !== 'function') {
        console.error('getRuleName: callback function is required');
        return;
    }
    
    // Log the full URL that will be called
    // Get the base URL from the current location
    // Current URL: http://localhost/myddleware_NORMAL/public/rule/flux/modern/ID
    // We need: http://localhost/myddleware_NORMAL/public/api/flux/rule-get/ID
    const pathParts = window.location.pathname.split('/');
    const publicIndex = pathParts.indexOf('public');
    let baseUrl;
    
    if (publicIndex !== -1) {
        // Take everything up to and including 'public'
        const baseParts = pathParts.slice(0, publicIndex + 1);
        baseUrl = window.location.origin + baseParts.join('/');
    } else {
        // Fallback: assume we're already at the root
        baseUrl = window.location.origin;
    }
    
    const url = `${baseUrl}/rule/api/flux/rule-get/${documentId}`;
    console.log('Current pathname:', window.location.pathname);
    console.log('Path parts:', pathParts);
    console.log('Public index:', publicIndex);
    console.log('Base URL detected:', baseUrl);
    console.log('Making AJAX request to URL:', url);
    console.log('Request type: GET');
    
    // get the rule from the document using an ajax request
    $.ajax({
        url: url,
        type: 'GET',
        beforeSend: function(xhr) {
            console.log('AJAX request about to be sent');
            console.log('XHR object:', xhr);
        },
        success: function(response) {
            console.log('AJAX request successful!');
            console.log('Response received:', response);
            console.log('Response type:', typeof response);
            
            // Handle the new JSON response format
            if (response && typeof response === 'object') {
                if (response.success) {
                    console.log('✅ Rule found successfully!');
                    console.log('Rule name:', response.rule_name);
                    console.log('Rule ID:', response.rule_id);
                    console.log('Document ID:', response.document_id);
                    
                    // Call the callback with rule name and ID
                    callback(response.rule_name, response.rule_id, null);
                } else if (response.error) {
                    console.error('❌ Server returned error:', response.error);
                    callback(null, null, response.error);
                }
            } else {
                // Handle legacy string response (if backend returns plain text)
                console.log('Response length:', response ? response.length : 'N/A');
                console.log('Rule name (legacy format):', response);
                callback(response, null, null);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX request failed!');
            console.error('Status:', status);
            console.error('Error:', error);
            console.error('XHR status:', xhr.status);
            console.error('XHR statusText:', xhr.statusText);
            console.error('XHR responseText:', xhr.responseText);
            console.error('XHR responseJSON:', xhr.responseJSON);
            
            let errorMessage = `AJAX Error: ${status} - ${error}`;
            
            // Log specific error cases
            if (xhr.status === 404) {
                console.error('ERROR: 404 - Endpoint not found. Check if the URL path is correct.');
                console.error('Expected endpoint: /rule/api/flux/rule-get/{id}');
                errorMessage = 'Endpoint not found (404)';
            } else if (xhr.status === 403) {
                console.error('ERROR: 403 - Access forbidden. Check authentication/authorization.');
                errorMessage = 'Access forbidden (403)';
            } else if (xhr.status === 500) {
                console.error('ERROR: 500 - Server error. Check server logs.');
                errorMessage = 'Server error (500)';
            } else if (xhr.status === 0) {
                console.error('ERROR: Network error or CORS issue. Check if server is running.');
                errorMessage = 'Network error or CORS issue';
            }
            
            // Call the callback with error
            callback(null, null, errorMessage);
        },
        complete: function(xhr, status) {
            console.log('AJAX request completed with status:', status);
            console.log('Final XHR state:', xhr.readyState);
        }
    });
    
    console.log('getRuleName function execution completed (async request sent)');
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