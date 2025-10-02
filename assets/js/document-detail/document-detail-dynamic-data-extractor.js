import { getDocumentHistory } from './document-detail-data-extractor.js';
import { getDocumentParents, getDocumentChildren, getDocumentPosts } from './document-detail-data-extractor-parents-children.js';
import { DocumentDetailDataSections } from './document-detail-data-sections.js';
import { DocumentDetailSectionState } from './document-detail-section-state.js';
import { DocumentDetailPermissions } from './document-detail-permissions.js';

export function extractDocumentHistory(documentId) {

// console.log('documentId: ', documentId);
    

    // Return empty array initially - will be populated asynchronously
    if (!documentId) {
        return [];
    }
    
    // Start async fetch immediately - this will update the DOM when ready
    setTimeout(() => {
        getDocumentHistory(documentId, function(data, error) {
            if (error) {
                console.error('❌ Could not get document history:', error);
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
// console.log('🔄 Re-initializing document history section state (new insertion)...');
                DocumentDetailSectionState.setupCollapsible('custom-section', 'custom', 'documentsHistory');
                DocumentDetailSectionState.setupPagination('custom-section', 'documentsHistory', historyData);
// console.log('✅ Document history section state initialized (new)');
                return;
            }
        }

        if (historySection) {
            // Replace existing history section
            const newHistoryHTML = DocumentDetailDataSections.generateDocumentHistory(historyData, permissions);
            historySection.outerHTML = newHistoryHTML;

            // Re-initialize section state management for the new DOM elements
            // console.log('🔄 Re-initializing document history section state (replacement)...');
            DocumentDetailSectionState.setupCollapsible('custom-section', 'custom', 'documentsHistory');
            DocumentDetailSectionState.setupPagination('custom-section', 'documentsHistory', historyData);
            // console.log('✅ Document history section state re-initialized (replacement)');
        } else {
            // console.warn('⚠️ Could not find appropriate location for document history section');
        }
    } catch (error) {
        console.error('❌ Error updating document history section:', error);
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
                console.error('❌ Could not get document parents:', error);
                return;
            }
            
            if (data && data.length > 0) {
// console.log('🔄 About to update document parents section with', data.length, 'records');
                // Update the document parents section with real data
                updateDocumentParentsSection(data);
            } else {
// console.log('⚠️ No parent data received or data is empty');
            }
        });
    }, 150);
    
    // Return empty array for now - the async call will populate the DOM
    return [];
}

export function extractDocumentChildren(documentId) {
// console.log("calling extractDocumentChildren with id: ", documentId);
    
    return new Promise((resolve) => {
        if (!documentId) {
            resolve([]);
            return;
        }
        
        // Start async fetch immediately - this will update the DOM when ready
        setTimeout(() => {
            getDocumentChildren(documentId, function(data, error) {
                if (error) {
                    console.error('❌ Could not get document children:', error);
                    resolve([]);
                    return;
                }
                
                if (data && data.length > 0) {
// console.log('🔄 About to update document children section with', data.length, 'records');
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
// console.log('🔍 Looking for parent documents section in DOM...');
        // Find the existing parents section and update it
        const parentsSection = document.querySelector('[data-section="parent-documents"]');
// console.log('🔍 Found parents section in DOM:', parentsSection);
        if (parentsSection) {
            // Generate and update the section
// console.log('🔍 FluxDataSections module loaded for parents');
            const newParentsHTML = DocumentDetailDataSections.generateParentDocumentsSection(parentsData);
// console.log('🔍 Generated new parents HTML:', newParentsHTML);
            parentsSection.outerHTML = newParentsHTML;
// console.log('✅ Updated document parents section with', parentsData.length, 'records');
            
            // Re-initialize section state management for the new DOM elements
            // console.log('🔄 Re-initializing parent documents section state...');
            DocumentDetailSectionState.setupCollapsible('parent-documents-section', 'parent-documents', 'parentDocuments');
            DocumentDetailSectionState.setupPagination('parent-documents-section', 'parentDocuments', parentsData);
            // console.log('✅ Parent documents section state re-initialized');
        } else {
            // console.warn('⚠️ Document parents section not found in DOM');
            // console.log('🔍 Available sections in DOM:', document.querySelectorAll('[data-section]'));
        }
    } catch (error) {
        console.error('❌ Error updating document parents section:', error);
    }
}

/**
 * Updates the document children section with real data
 * @param {Array} childrenData - Array of child document objects
 */
function updateDocumentChildrenSection(childrenData) {
// console.log('🔍 updateDocumentChildrenSection called with data:', childrenData);
    try {
        // Find the existing children section and update it
        const childrenSection = document.querySelector('[data-section="child-documents"]');
// console.log('🔍 Found children section in DOM:', childrenSection);
        if (childrenSection) {
            // Generate and update the section
// console.log('🔍 FluxDataSections module loaded');
            const newChildrenHTML = DocumentDetailDataSections.generateChildDocumentsSection(childrenData);
// console.log('🔍 Generated new HTML:', newChildrenHTML);
            childrenSection.outerHTML = newChildrenHTML;
// console.log('✅ Updated document children section with', childrenData.length, 'records');

            // Re-initialize section state management for the new DOM elements
            // console.log('🔄 Re-initializing child documents section state...');
            DocumentDetailSectionState.setupCollapsible('child-documents-section', 'child-documents', 'childDocuments');
            DocumentDetailSectionState.setupPagination('child-documents-section', 'childDocuments', childrenData);
            // console.log('✅ Child documents section state re-initialized');
        } else {
            // console.warn('⚠️ Document children section not found in DOM');
        }
    } catch (error) {
        console.error('❌ Error updating document children section:', error);
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
                console.error('❌ Could not get document posts:', error);
                return;
            }

            if (data && data.length > 0) {
// console.log('🔄 About to update document posts section with', data.length, 'records');
                // Update the document posts section with real data
                updateDocumentPostsSection(data);
            } else {
// console.log('⚠️ No post data received or data is empty');
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
// console.log('✅ Updated document posts section with', postsData.length, 'records');

            // Re-initialize section state management for the new DOM elements
            DocumentDetailSectionState.setupCollapsible('post-documents-section', 'post-documents', 'postDocuments');
            DocumentDetailSectionState.setupPagination('post-documents-section', 'postDocuments', postsData);
        } else {
            // console.warn('⚠️ Document posts section not found in DOM');
        }
    } catch (error) {
        console.error('❌ Error updating document posts section:', error);
    }
}