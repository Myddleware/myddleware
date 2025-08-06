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
        console.log("⏰ Starting document history fetch after 100ms delay");
        getDocumentHistory(documentId, function(data, error) {
            if (error) {
                console.error('❌ Could not get document history:', error);
                return;
            }
            
            if (data && data.length > 0) {
                console.log('📋 got the history data! Length:', data.length);
                console.log('📊 History data:', data);
                // Update the document history section with real data
                updateDocumentHistorySection(data);
            } else {
                console.log('📋 No history data received or empty array');
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
        console.log("🔍 inside updatedocumenthistorysection in flux dynamic data extractor");
        console.log("📊 historyData received:", historyData);
        
        // Check if DOM is ready
        console.log("🌐 document.readyState:", document.readyState);
        console.log("📋 Looking for history section...");
        
        // Try different selectors to find the element
        const historySection1 = document.querySelector('.data-wrapper.custom-section');
        const historySection2 = document.querySelector('[class*="custom-section"]');
        const historySection3 = document.querySelector('.data-wrapper');
        const historySection4 = document.querySelector('.custom-section');
        
        console.log("🔍 .data-wrapper.custom-section found:", !!historySection1);
        console.log("🔍 [class*='custom-section'] found:", !!historySection2);
        console.log("🔍 .data-wrapper found:", !!historySection3);
        console.log("🔍 .custom-section found:", !!historySection4);
        
        // Log all elements with data-wrapper class
        const allDataWrappers = document.querySelectorAll('.data-wrapper');
        console.log("📦 All .data-wrapper elements found:", allDataWrappers.length);
        allDataWrappers.forEach((el, i) => {
            console.log(`📦 Element ${i}:`, el.className, el.outerHTML.substring(0, 100) + "...");
        });
        
        const historySection = historySection1 || historySection2 || historySection3 || historySection4;
        
        if (historySection) {
            console.log("✅ got the history section !", historySection.className);
            // Import FluxDataSections dynamically and regenerate the section
            import('./flux-data-sections.js').then(module => {
                const newHistoryHTML = module.FluxDataSections.generateDocumentHistory(historyData);
                console.log("🔄 Generated new HTML length:", newHistoryHTML.length);
                historySection.outerHTML = newHistoryHTML;
                console.log('✅ Updated document history section with', historyData.length, 'records');
            });
        } else {
            console.warn('⚠️ Document history section not found in DOM');
            console.log("🌐 Current DOM body innerHTML length:", document.body.innerHTML.length);
            console.log("🌐 Searching in entire document...");
            
            // Try to find any element that might contain our target
            const allElements = document.querySelectorAll('*');
            let foundSimilar = false;
            allElements.forEach(el => {
                if (el.className && (el.className.includes('data-wrapper') || el.className.includes('custom'))) {
                    console.log("🔍 Similar element found:", el.tagName, el.className);
                    foundSimilar = true;
                }
            });
            
            if (!foundSimilar) {
                console.log("❌ No similar elements found at all");
            }
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