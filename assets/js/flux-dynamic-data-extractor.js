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
                    
                    // Re-initialize section state management for the new DOM elements
                    console.log('🔄 Re-initializing document history section state (new insertion)...');
                    import('./flux-section-state.js').then(stateModule => {
                        const FluxSectionState = stateModule.FluxSectionState;
                        FluxSectionState.setupCollapsible('custom-section', 'custom', 'documentsHistory');
                        FluxSectionState.setupPagination('custom-section', 'documentsHistory', historyData);
                        console.log('✅ Document history section state initialized (new)');
                    }).catch(stateError => {
                        console.error('❌ Error initializing document history section state (new):', stateError);
                    });
                });
                return;
            }
        }
        
        if (historySection) {
            // Replace existing history section
            import('./flux-data-sections.js').then(module => {
                const newHistoryHTML = module.FluxDataSections.generateDocumentHistory(historyData);
                historySection.outerHTML = newHistoryHTML;
                
                // Re-initialize section state management for the new DOM elements
                console.log('🔄 Re-initializing document history section state (replacement)...');
                import('./flux-section-state.js').then(stateModule => {
                    const FluxSectionState = stateModule.FluxSectionState;
                    FluxSectionState.setupCollapsible('custom-section', 'custom', 'documentsHistory');
                    FluxSectionState.setupPagination('custom-section', 'documentsHistory', historyData);
                    console.log('✅ Document history section state re-initialized (replacement)');
                }).catch(stateError => {
                    console.error('❌ Error re-initializing document history section state (replacement):', stateError);
                });
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
            // Import FluxDataSections dynamically and regenerate the section
            import('./flux-data-sections.js').then(module => {
                // console.log('🔍 FluxDataSections module loaded for parents:', module);
                const newParentsHTML = module.FluxDataSections.generateParentDocumentsSection(parentsData);
                // console.log('🔍 Generated new parents HTML:', newParentsHTML);
                parentsSection.outerHTML = newParentsHTML;
                // console.log('✅ Updated document parents section with', parentsData.length, 'records');
                
                // Re-initialize section state management for the new DOM elements
                console.log('🔄 Re-initializing parent documents section state...');
                import('./flux-section-state.js').then(stateModule => {
                    const FluxSectionState = stateModule.FluxSectionState;
                    FluxSectionState.setupCollapsible('parent-documents-section', 'parent-documents', 'parentDocuments');
                    FluxSectionState.setupPagination('parent-documents-section', 'parentDocuments', parentsData);
                    console.log('✅ Parent documents section state re-initialized');
                }).catch(stateError => {
                    console.error('❌ Error re-initializing parent documents section state:', stateError);
                });
            }).catch(moduleError => {
                console.error('❌ Error loading FluxDataSections module for parents:', moduleError);
            });
        } else {
            console.warn('⚠️ Document parents section not found in DOM');
            console.log('🔍 Available sections in DOM:', document.querySelectorAll('[data-section]'));
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
            // Import FluxDataSections dynamically and regenerate the section
            import('./flux-data-sections.js').then(module => {
                // console.log('🔍 FluxDataSections module loaded:', module);
                const newChildrenHTML = module.FluxDataSections.generateChildDocumentsSection(childrenData);
                // console.log('🔍 Generated new HTML:', newChildrenHTML);
                childrenSection.outerHTML = newChildrenHTML;
                // console.log('✅ Updated document children section with', childrenData.length, 'records');
                
                // Re-initialize section state management for the new DOM elements
                console.log('🔄 Re-initializing child documents section state...');
                import('./flux-section-state.js').then(stateModule => {
                    const FluxSectionState = stateModule.FluxSectionState;
                    FluxSectionState.setupCollapsible('child-documents-section', 'child-documents', 'childDocuments');
                    FluxSectionState.setupPagination('child-documents-section', 'childDocuments', childrenData);
                    console.log('✅ Child documents section state re-initialized');
                }).catch(stateError => {
                    console.error('❌ Error re-initializing child documents section state:', stateError);
                });
            });
        } else {
            console.warn('⚠️ Document children section not found in DOM');
        }
    } catch (error) {
        console.error('❌ Error updating document children section:', error);
    }
}