import { getDocumentHistory } from './document-detail-data-extractor.js';
import { getDocumentParents, getDocumentChildren, getDocumentPosts } from './document-detail-data-extractor-parents-children.js';
import { DocumentDetailDataSections } from './document-detail-data-sections.js';
import { DocumentDetailSectionState } from './document-detail-section-state.js';
import { DocumentDetailPermissions } from './document-detail-permissions.js';

export function extractDocumentHistory(documentId) {

    

    // Return empty array initially - will be populated asynchronously
    if (!documentId) {
        return [];
    }
    
    // Start async fetch immediately - this will update the DOM when ready
    setTimeout(() => {
        getDocumentHistory(documentId, function(data, error) {
            if (error) {
                console.error(' Could not get document history:', error);
                return;
            }
            
            if (data && data.length > 0) {
                // Update the document history section with real data
                updateDocumentHistorySection(data);
            }
        });
    }, 100);
    
    // Return empty array for now - the async call will populate the DOM
    return [];
}

/**
 * Updates the document history section with real data
 * @param {Array} historyData - Array of history document objects
 */
async function updateDocumentHistorySection(historyData) {
    try {
        // Get user permissions
        const permissions = await DocumentDetailPermissions.getCurrentUserPermissions();

        // Look for existing document history section first
        let historySection = document.querySelector('.data-wrapper.custom-section');

        if (!historySection) {
            // If no existing history section, find where to insert it
            // Look for the main data-wrapper that contains source/target/history sections
            const mainDataWrapper = document.querySelector('.data-wrapper');
            if (mainDataWrapper) {
                // Insert the history section after the main data wrapper
                const newHistoryHTML = DocumentDetailDataSections.generateDocumentHistory(historyData, permissions);
                mainDataWrapper.insertAdjacentHTML('afterend', newHistoryHTML);

                // Re-initialize section state management for the new DOM elements
                DocumentDetailSectionState.setupCollapsible('custom-section', 'custom', 'documentsHistory');
                DocumentDetailSectionState.setupPagination('custom-section', 'documentsHistory', historyData);
                return;
            }
        }

        if (historySection) {
            // Replace existing history section
            const newHistoryHTML = DocumentDetailDataSections.generateDocumentHistory(historyData, permissions);
            historySection.outerHTML = newHistoryHTML;

            // Re-initialize section state management for the new DOM elements
            DocumentDetailSectionState.setupCollapsible('custom-section', 'custom', 'documentsHistory');
            DocumentDetailSectionState.setupPagination('custom-section', 'documentsHistory', historyData);
        } else {
        }
    } catch (error) {
        console.error(' Error updating document history section:', error);
    }
}

export function extractDocumentParents(documentId) {
    // Return empty array initially - will be populated asynchronously
    if (!documentId) {
        return [];
    }
    
    // Start async fetch immediately - this will update the DOM when ready
    setTimeout(() => {
        getDocumentParents(documentId, function(data, error) {
            if (error) {
                console.error(' Could not get document parents:', error);
                return;
            }
            
            if (data && data.length > 0) {
                // Update the document parents section with real data
                updateDocumentParentsSection(data);
            } else {
            }
        });
    }, 150);
    
    // Return empty array for now - the async call will populate the DOM
    return [];
}

export function extractDocumentChildren(documentId) {
    
    return new Promise((resolve) => {
        if (!documentId) {
            resolve([]);
            return;
        }
        
        // Start async fetch immediately - this will update the DOM when ready
        setTimeout(() => {
            getDocumentChildren(documentId, function(data, error) {
                if (error) {
                    console.error(' Could not get document children:', error);
                    resolve([]);
                    return;
                }
                
                if (data && data.length > 0) {
                    // Update the document children section with real data
                    updateDocumentChildrenSection(data);
                    resolve(data);
                } else {
                    resolve([]);
                }
            });
        }, 200);
    });
}

/**
 * Updates the document parents section with real data
 * @param {Array} parentsData - Array of parent document objects
 */
function updateDocumentParentsSection(parentsData) {
    try {
        // Find the existing parents section and update it
        const parentsSection = document.querySelector('[data-section="parent-documents"]');
        if (parentsSection) {
            // Generate and update the section
            const newParentsHTML = DocumentDetailDataSections.generateParentDocumentsSection(parentsData);
            parentsSection.outerHTML = newParentsHTML;
            
            // Re-initialize section state management for the new DOM elements
            DocumentDetailSectionState.setupCollapsible('parent-documents-section', 'parent-documents', 'parentDocuments');
            DocumentDetailSectionState.setupPagination('parent-documents-section', 'parentDocuments', parentsData);
        } else {
        }
    } catch (error) {
        console.error(' Error updating document parents section:', error);
    }
}

/**
 * Updates the document children section with real data
 * @param {Array} childrenData - Array of child document objects
 */
function updateDocumentChildrenSection(childrenData) {
    try {
        // Find the existing children section and update it
        const childrenSection = document.querySelector('[data-section="child-documents"]');
        if (childrenSection) {
            // Generate and update the section
            const newChildrenHTML = DocumentDetailDataSections.generateChildDocumentsSection(childrenData);
            childrenSection.outerHTML = newChildrenHTML;

            // Re-initialize section state management for the new DOM elements
            DocumentDetailSectionState.setupCollapsible('child-documents-section', 'child-documents', 'childDocuments');
            DocumentDetailSectionState.setupPagination('child-documents-section', 'childDocuments', childrenData);
        } else {
        }
    } catch (error) {
        console.error(' Error updating document children section:', error);
    }
}

export function extractDocumentPosts(documentId) {
    // Return empty array initially - will be populated asynchronously
    if (!documentId) {
        return [];
    }

    // Start async fetch immediately - this will update the DOM when ready
    setTimeout(() => {
        getDocumentPosts(documentId, function(data, error) {
            if (error) {
                console.error(' Could not get document posts:', error);
                return;
            }

            if (data && data.length > 0) {
                // Update the document posts section with real data
                updateDocumentPostsSection(data);
            } else {
            }
        });
    }, 250);

    // Return empty array for now - the async call will populate the DOM
    return [];
}

/**
 * Updates the document posts section with real data
 * @param {Array} postsData - Array of post document objects
 */
function updateDocumentPostsSection(postsData) {
    try {
        // Find the existing posts section and update it
        const postsSection = document.querySelector('[data-section="post-documents"]');
        if (postsSection) {
            // Generate and update the section
            const newPostsHTML = DocumentDetailDataSections.generatePostDocumentsSection(postsData);
            postsSection.outerHTML = newPostsHTML;

            // Re-initialize section state management for the new DOM elements
            DocumentDetailSectionState.setupCollapsible('post-documents-section', 'post-documents', 'postDocuments');
            DocumentDetailSectionState.setupPagination('post-documents-section', 'postDocuments', postsData);
        } else {
        }
    } catch (error) {
        console.error(' Error updating document posts section:', error);
    }
}