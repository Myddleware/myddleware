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
        // Look for existing document history section first
        let historySection = document.querySelector('.data-wrapper.custom-section');
        
        if (!historySection) {
            // If no existing history section, find where to insert it
            // Look for the main data-wrapper that contains source/target/history sections
            const mainDataWrapper = document.querySelector('.data-wrapper');
            if (mainDataWrapper) {
                // Insert the history section after the main data wrapper
                import('./flux-data-sections.js').then(module => {
                    const newHistoryHTML = module.FluxDataSections.generateDocumentHistory(historyData);
                    mainDataWrapper.insertAdjacentHTML('afterend', newHistoryHTML);
                });
                return;
            }
        }
        
        if (historySection) {
            // Replace existing history section
            import('./flux-data-sections.js').then(module => {
                const newHistoryHTML = module.FluxDataSections.generateDocumentHistory(historyData);
                historySection.outerHTML = newHistoryHTML;
            });
        } else {
            console.warn('⚠️ Could not find appropriate location for document history section');
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
    console.log("calling extractDocumentChildren with id: ", documentId);
    
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
                    console.log('🔄 About to update document children section with', data.length, 'records');
                    // Update the document children section with real data
                    updateDocumentChildrenSection(data);
                    resolve(data);
                } else {
                    console.log('⚠️ No children data to display');
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
    console.log('🔍 updateDocumentChildrenSection called with data:', childrenData);
    try {
        // Find the existing children section and update it
        const childrenSection = document.querySelector('[data-section="child-documents"]');
        console.log('🔍 Found children section in DOM:', childrenSection);
        if (childrenSection) {
            // Import FluxDataSections dynamically and regenerate the section
            import('./flux-data-sections.js').then(module => {
                console.log('🔍 FluxDataSections module loaded:', module);
                const newChildrenHTML = module.FluxDataSections.generateChildDocumentsSection(childrenData);
                console.log('🔍 Generated new HTML:', newChildrenHTML);
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