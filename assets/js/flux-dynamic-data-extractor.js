import { getDocumentHistory } from './flux-data-extractor.js';

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