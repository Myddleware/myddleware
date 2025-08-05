import { getDocumentHistory } from './flux-data-extractor.js';
import { getDocumentParents, getDocumentChildren } from './flux-data-extractor-parents-children.js';

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
                // console.log('got the data!');
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
function updateDocumentHistorySection(historyData) {
    try {
        // Find the existing history section and update it
        const historySection = document.querySelector('[class="data-wrapper custom-section"]');
        if (historySection) {
            // console.log("got the history section !")
            // Import FluxDataSections dynamically and regenerate the section
            import('./flux-data-sections.js').then(module => {
                const newHistoryHTML = module.FluxDataSections.generateDocumentHistory(historyData);
                historySection.outerHTML = newHistoryHTML;
                // console.log('✅ Updated document history section with', historyData.length, 'records');
            });
        } else {
            console.warn('⚠️ Document history section not found in DOM');
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
                // Update the document parents section with real data
                updateDocumentParentsSection(data);
            }
        });
    }, 150);
    
    // Return empty array for now - the async call will populate the DOM
    return [];
}

export function extractDocumentChildren(documentId) {
    // Return empty array initially - will be populated asynchronously
    if (!documentId) {
        return [];
    }
    
    // Start async fetch immediately - this will update the DOM when ready
    setTimeout(() => {
        getDocumentChildren(documentId, function(data, error) {
            if (error) {
                console.error('❌ Could not get document children:', error);
                return;
            }
            
            if (data && data.length > 0) {
                // Update the document children section with real data
                updateDocumentChildrenSection(data);
            }
        });
    }, 200);
    
    // Return empty array for now - the async call will populate the DOM
    return [];
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
            // Import FluxDataSections dynamically and regenerate the section
            import('./flux-data-sections.js').then(module => {
                const newParentsHTML = module.FluxDataSections.generateParentDocumentsSection(parentsData);
                parentsSection.outerHTML = newParentsHTML;
                console.log('✅ Updated document parents section with', parentsData.length, 'records');
            });
        } else {
            console.warn('⚠️ Document parents section not found in DOM');
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
    try {
        // Find the existing children section and update it
        const childrenSection = document.querySelector('[data-section="child-documents"]');
        if (childrenSection) {
            // Import FluxDataSections dynamically and regenerate the section
            import('./flux-data-sections.js').then(module => {
                const newChildrenHTML = module.FluxDataSections.generateChildDocumentsSection(childrenData);
                childrenSection.outerHTML = newChildrenHTML;
                console.log('✅ Updated document children section with', childrenData.length, 'records');
            });
        } else {
            console.warn('⚠️ Document children section not found in DOM');
        }
    } catch (error) {
        console.error('❌ Error updating document children section:', error);
    }
}