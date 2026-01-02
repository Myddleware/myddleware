// Additional functions for parent and child document fetching

import { getBaseUrl } from './document-detail-url-utils.js';

// ===== DOCUMENT PARENTS FETCHER =====
export function getDocumentParents(documentId, callback) {
    
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
    const baseUrl = getBaseUrl();
    const url = `${baseUrl}/rule/api/flux/document-parents/${documentId}`;
    
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
            console.error(' Document parents request failed!');
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
    const baseUrl = getBaseUrl();
    const url = `${baseUrl}/rule/api/flux/document-children/${documentId}`;
    
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
            console.error(' Document children request failed!');
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

// ===== DOCUMENT POSTS FETCHER =====
export function getDocumentPosts(documentId, callback) {

    // Validate parameters
    if (!documentId) {
        console.error('getDocumentPosts: documentId is required');
        if (callback) callback(null, 'Document ID is required');
        return;
    }

    if (!callback || typeof callback !== 'function') {
        console.error('getDocumentPosts: callback function is required');
        return;
    }

    // Build URL for document posts
    const baseUrl = getBaseUrl();
    const url = `${baseUrl}/rule/api/flux/document-posts/${documentId}`;

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
            console.error(' Document posts request failed!');
            console.error('Status:', status, 'Error:', error);

            let errorMessage = `AJAX Error: ${status} - ${error}`;
            if (xhr.status === 404) {
                errorMessage = 'Document posts endpoint not found (404)';
            } else if (xhr.status === 403) {
                errorMessage = 'Access forbidden (403)';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error (500)';
            }

            callback(null, errorMessage);
        }
    });
}