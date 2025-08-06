import { FluxDataSections } from './flux-data-sections.js';
import { 
    getDocumentData, 
    extractRuleInfo, 
    extractDocumentStatus, 
    extractDocumentType, 
    extractDocumentAttempts, 
    extractDocumentDates,
    getDocumentHistory
} from './flux-data-extractor.js';

import {
    extractDocumentHistory,
    extractDocumentParents,
    extractDocumentChildren
} from './flux-dynamic-data-extractor.js';

import { getDocumentLogs } from './flux-data-extractor-logs.js';
import { FluxSectionState } from './flux-section-state.js';

export class FluxTemplate {
    static generateHTML() {
        const path_img_modal = "../../../build/images/solution/";
        // Default logos - will be updated with real ones when API data arrives
        const solutionSource = "default.png";
        const solutionTarget = "default.png";
        const solutionHistory = "default.png";

        const fullpathSource = `${path_img_modal}${solutionSource}`;
        const fullpathTarget = `${path_img_modal}${solutionTarget}`;
        const fullpathHistory = `${path_img_modal}${solutionHistory}`;

        // the url is like http://localhost/myddleware_NORMAL/public/rule/flux/modern/6863a07946e8b9.38306852
        // we need to get 6863a07946e8b9.3830685
        let documentId = window.location.pathname.split('/').pop();

        // console.log('🚀 FluxTemplate.generateHTML called with documentId:', documentId);
        
        const myHistoryPayload = extractDocumentHistory(documentId);
        // console.log('📊 History payload extracted:', myHistoryPayload?.length || 0, 'items');
        
        const myParentsPayload = extractDocumentParents(documentId);
        // console.log('👪 Parents payload extracted:', myParentsPayload?.length || 0, 'items');
        
        // Handle the promise from extractDocumentChildren
        let myChildrenPayload = [];
        extractDocumentChildren(documentId).then(data => {
            myChildrenPayload = data;
            // console.log("🔍 myChildrenPayload resolved:", myChildrenPayload?.length || 0, 'items');
        });
        
        // Initialize logs payload and fetch logs data
        let myLogsPayload = [];
        // console.log('📋 Starting logs data fetch for documentId:', documentId);
        getDocumentLogs(documentId, (logsData, error) => {
            if (error) {
                console.error('❌ Error fetching logs data:', error);
            } else {
                // console.log('✅ Logs data fetched successfully:', logsData?.length || 0, 'logs');
                myLogsPayload = logsData || [];
                // Update the logs section with real data
                FluxTemplate.updateLogsSection(myLogsPayload);
            }
        });

        // console.log("🔍 About to generate template with myChildrenPayload:", myChildrenPayload);
        // First, return the template with placeholders
        const template = `
            <div class="flex-row" id="flux-button-container">
                <button class="btn btn-primary" id="run-same-record">Run the same record</button>
                <button class="btn btn-warning" id="cancel-document">Cancel the document</button>
            </div>
            
            <div class="table-wrapper" style="margin: 20px;">
                <table class="shadow-table" id="flux-table">
                    <thead>
                        <tr>
                            <th class="rounded-table-up-left">Rule</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Attempt</th>
                            <th>Global status</th>
                            <th>Reference</th>
                            <th>Creation date</th>
                            <th class="rounded-table-up-right">Modification Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><a id="rule-link" href="#" style="color: #0F66A9; font-weight: bold; text-decoration: none;">Loading rule...</a></td>
                            <td id="document-status">Loading...</td>
                            <td id="document-type">Loading...</td>
                            <td id="document-attempt">Loading...</td>
                            <td id="document-global-status">Loading...</td>
                            <td id="document-reference">Loading...</td>
                            <td id="document-creation-date">Loading...</td>
                            <td id="document-modification-date">Loading...</td>
                        </tr>
                        </tbody>
                        </table>
                        </div>
                        ${FluxDataSections.generateDataSections(fullpathSource, fullpathTarget, fullpathHistory)}
                        ${FluxDataSections.generateDocumentHistory(myHistoryPayload)}
                        ${FluxDataSections.generateParentDocumentsSection(myParentsPayload)}
                        ${FluxDataSections.generateChildDocumentsSection(myChildrenPayload)}
                        ${FluxDataSections.generateLogsSection(myLogsPayload)}
                        `;
                        
        // After returning the template, load ALL document data with a single call
        setTimeout(() => {
// console.log('🚀 Loading comprehensive document data...');
            
            // NEW APPROACH: Single API call + modular extraction
            getDocumentData(documentId, function(data, error) {
                if (error) {
                    console.error('❌ Could not get document data:', error);
                    FluxTemplate.showErrorState();
                    return;
                }
                
                // Extract and update each piece of data using modular functions
                FluxTemplate.updateRuleInfo(extractRuleInfo(data));
                FluxTemplate.updateDocumentStatus(extractDocumentStatus(data));
                FluxTemplate.updateDocumentType(extractDocumentType(data));
                FluxTemplate.updateDocumentAttempts(extractDocumentAttempts(data));
                FluxTemplate.updateDocumentDates(extractDocumentDates(data));
                
                // Update data sections with real data
                FluxTemplate.updateDataSections(data);
                
                // Update logos with real solution information
                FluxTemplate.updateLogos(data);
            });
        }, 100);

        return template;
    }
    
    // ===== MODULAR UPDATE FUNCTIONS =====
    
    static updateRuleInfo(ruleInfo) {
        if (!ruleInfo || !ruleInfo.name) {
            console.warn('⚠️ No rule info available');
            return;
        }
        
        const linkElement = document.getElementById('rule-link');
        if (linkElement) {
            // Get base URL for link construction
            const pathParts = window.location.pathname.split('/');
            const publicIndex = pathParts.indexOf('public');
            let baseUrl = window.location.origin;
            if (publicIndex !== -1) {
                const baseParts = pathParts.slice(0, publicIndex + 1);
                baseUrl = window.location.origin + baseParts.join('/');
            }
            
            const ruleLink = `${baseUrl}/rule/view/${ruleInfo.id}`;
            linkElement.href = ruleLink;
            linkElement.textContent = ruleInfo.name;
// console.log('✅ Updated rule link:', ruleLink);
        }
    }
    
    static updateDocumentStatus(statusInfo) {
        if (!statusInfo) {
            console.warn('⚠️ No status info available');
            return;
        }
        
        const statusElement = document.getElementById('document-status');
        const globalStatusElement = document.getElementById('document-global-status');
        
        if (statusElement && statusInfo.status_label) {
            statusElement.innerHTML = `<span class="${statusInfo.status_class || ''}">${statusInfo.status_label}</span>`;
        }
        
        if (globalStatusElement && statusInfo.global_status_label) {
            globalStatusElement.innerHTML = `<span class="${statusInfo.global_status_class || ''}">${statusInfo.global_status_label}</span>`;
        }
        
// console.log('✅ Updated document status with colors');
    }
    
    static updateDocumentType(typeInfo) {
        if (!typeInfo) {
            console.warn('⚠️ No type info available');
            return;
        }
        
        const typeElement = document.getElementById('document-type');
        if (typeElement && typeInfo.type) {
            typeElement.textContent = typeInfo.type;
// console.log('✅ Updated document type');
        }
    }
    
    static updateDocumentAttempts(attemptInfo) {
        if (!attemptInfo) {
            console.warn('⚠️ No attempt info available');
            return;
        }
        
        const attemptElement = document.getElementById('document-attempt');
        if (attemptElement) {
            let attemptText = attemptInfo.attempt.toString();
            if (attemptInfo.maxAttempts) {
                attemptText += ` / ${attemptInfo.maxAttempts}`;
            }
            attemptElement.textContent = attemptText;
// console.log('✅ Updated document attempts');
        }
    }
    
    static updateDocumentDates(dateInfo) {
        if (!dateInfo) {
            console.warn('⚠️ No date info available');
            return;
        }
        
        const referenceElement = document.getElementById('document-reference');
        const creationElement = document.getElementById('document-creation-date');
        const modificationElement = document.getElementById('document-modification-date');
        
        if (referenceElement && dateInfo.reference) {
            referenceElement.textContent = dateInfo.reference;
        }
        
        if (creationElement && dateInfo.creationDate) {
            creationElement.textContent = dateInfo.creationDate;
        }
        
        if (modificationElement && dateInfo.modificationDate) {
            modificationElement.textContent = dateInfo.modificationDate;
        }
        
// console.log('✅ Updated document dates');
    }
    
    static showErrorState() {
        // Update all elements to show error state
        const elements = [
            'rule-link', 'document-status', 'document-type', 
            'document-attempt', 'document-global-status', 
            'document-reference', 'document-creation-date', 
            'document-modification-date'
        ];
        
        elements.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = 'Error loading data';
                element.style.color = '#dc3545';
            }
        });
    }

    // ===== DATA SECTIONS UPDATE FUNCTIONS =====

    /**
     * Updates all data sections with real data from the API
     * @param {Object} documentData - Complete document data from API
     */
    static updateDataSections(documentData) {
// console.log('🔄 Updating all data sections with real data');
        
        try {
            // Extract data for each section
            const sourceData = FluxTemplate.extractSourceData(documentData);
            const targetData = FluxTemplate.extractTargetData(documentData);
            const historyData = FluxTemplate.extractHistoryData(documentData);
            
            // Import FluxDataSections dynamically if needed, or call directly
            setTimeout(() => {
                FluxDataSections.updateSourceData(sourceData);
                FluxDataSections.updateTargetData(targetData);
                FluxDataSections.updateHistoryData(historyData);
            }, 50); // Small delay to ensure DOM sections are ready
            
// console.log('✅ All data sections update initiated');
            
        } catch (error) {
            console.error('❌ Error updating data sections:', error);
            FluxTemplate.showDataSectionErrors();
        }
    }

    /**
     * Extracts source data from document data
     * @param {Object} documentData - Complete document data
     * @returns {Object|null} Source data object
     */
    static extractSourceData(documentData) {
        try {
            const sourceData = documentData?.source_data;
// console.log('📊 Extracted source data:', sourceData ? 'Available' : 'Not available');
            return sourceData || null;
        } catch (error) {
            console.error('❌ Error extracting source data:', error);
            return null;
        }
    }

    /**
     * Extracts target data from document data
     * @param {Object} documentData - Complete document data
     * @returns {Object|null} Target data object
     */
    static extractTargetData(documentData) {
        try {
            const targetData = documentData?.target_data;
// console.log('🎯 Extracted target data:', targetData ? 'Available' : 'Not available');
            return targetData || null;
        } catch (error) {
            console.error('❌ Error extracting target data:', error);
            return null;
        }
    }

    /**
     * Extracts history data from document data
     * @param {Object} documentData - Complete document data
     * @returns {Object|null} History data object
     */
    static extractHistoryData(documentData) {
        try {
            const historyData = documentData?.history_data;
            // console.log('📜 Extracted history data:', historyData ? 'Available' : 'Not available');
            return historyData || null;
        } catch (error) {
            console.error('❌ Error extracting history data:', error);
            return null;
        }
    }

    /**
     * Shows error state for data sections
     */
    static showDataSectionErrors() {
        const sectionIds = ['source-data-body', 'target-data-body', 'history-data-body'];
        
        sectionIds.forEach(sectionId => {
            const element = document.getElementById(sectionId);
            if (element) {
                element.innerHTML = `
                    <div class="error-data-message">
                        <p style="color: #dc3545;">⚠️ Error loading data</p>
                    </div>
                `;
            }
        });
    }

    /**
     * Updates the logs section with real logs data
     * @param {Array} logsData - Array of logs data
     */
    static updateLogsSection(logsData) {
        // console.log('📋 FluxTemplate.updateLogsSection called with', logsData?.length || 0, 'logs');
        // console.log('📋 Sample log data:', logsData?.[0]);
        
        try {
            const logsContainer = document.querySelector('.logs-section');
            // console.log('📋 Logs container found:', !!logsContainer);
            
            if (!logsContainer) {
                console.warn('⚠️ Logs section container not found in DOM');
                // Let's also check what containers do exist
                const allDataWrappers = document.querySelectorAll('.data-wrapper');
                // console.log('📋 Available data-wrapper containers:', allDataWrappers.length);
                allDataWrappers.forEach((wrapper, index) => {
                    // console.log(`📋 Container ${index}:`, wrapper.className);
                });
                return;
            }

            if (!logsData || logsData.length === 0) {
                // console.log('📋 No logs data available, keeping empty section');
                return;
            }

            // Generate new logs section HTML with real data
            // console.log('📋 Generating new logs HTML...');
            const newLogsHtml = FluxDataSections.generateLogsSection(logsData);
            // console.log('📋 Generated new logs HTML, length:', newLogsHtml.length);
            
            // Replace the existing logs section
            logsContainer.outerHTML = newLogsHtml;
            // console.log('✅ Logs section updated successfully');
            
            // Re-initialize the section state management for the new DOM elements
            console.log('🔄 Re-initializing section state after logs update...');
            FluxSectionState.setupCollapsible('logs-section', 'logs', 'logs');
            FluxSectionState.setupPagination('logs-section', 'logs', logsData);
            console.log('✅ Logs section state re-initialized');
            
        } catch (error) {
            console.error('❌ Error updating logs section:', error);
            console.error('Error stack:', error.stack);
        }
    }

    /**
     * Updates the solution logos with real solution information
     * @param {Object} documentData - Complete document data from API
     */
    static updateLogos(documentData) {
        console.log('🖼️ Updating logos with solution data');
        
        try {
            if (!documentData || !documentData.source_solution || !documentData.target_solution) {
                console.warn('⚠️ Solution information not available in document data');
                return;
            }

            const path_img_modal = "../../../build/images/solution/";
            const sourceSolution = documentData.source_solution.toLowerCase();
            const targetSolution = documentData.target_solution.toLowerCase();
            
            console.log('🖼️ Source solution:', sourceSolution, 'Target solution:', targetSolution);

            // Check what sections and logo elements exist in the DOM
            const dataSections = document.querySelectorAll('.data-wrapper, .source-section, .target-section, .history-section');
            console.log('🖼️ Data sections found:', dataSections.length);
            dataSections.forEach((section, index) => {
                console.log(`🖼️ Section ${index}:`, section.className);
            });
            
            const allLogos = document.querySelectorAll('img');
            console.log('🖼️ All images in DOM:', allLogos.length);
            allLogos.forEach((img, index) => {
                console.log(`🖼️ Image ${index}:`, img.className, img.src);
            });

            // Update all logo images (they all have logo-small-size class)
            const logoImages = document.querySelectorAll('.logo-small-size');
            console.log('🖼️ Found', logoImages.length, 'logo images to update');
            
            logoImages.forEach((img, index) => {
                let solutionName, logoType;
                
                // Determine which solution logo this should be based on position/context
                // For now, let's assume: 0=source, 1=target, 2=history
                switch(index) {
                    case 0:
                        solutionName = sourceSolution;
                        logoType = 'source';
                        break;
                    case 1:
                        solutionName = targetSolution;
                        logoType = 'target';
                        break;
                    case 2:
                        solutionName = targetSolution; // History usually same as target
                        logoType = 'history';
                        break;
                    default:
                        solutionName = sourceSolution;
                        logoType = 'unknown';
                }
                
                const logoPath = `${path_img_modal}${solutionName}.png`;
                console.log(`🖼️ Updating ${logoType} logo (index ${index}):`, logoPath);
                
                img.src = logoPath;
                img.alt = `${solutionName} logo`;
                
                console.log(`✅ Updated ${logoType} logo:`, logoPath);
            });

        } catch (error) {
            console.error('❌ Error updating logos:', error);
        }
    }
}