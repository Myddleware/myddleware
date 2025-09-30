import { DocumentDetailDataSections } from './document-detail-data-sections.js';
import { 
    getDocumentData, 
    extractRuleInfo, 
    extractDocumentStatus, 
    extractDocumentType, 
    extractDocumentAttempts, 
    extractDocumentDates,
    getDocumentHistory
} from './document-detail-data-extractor.js';
import { DocumentDetailDirectLinks } from './document-detail-direct-links.js';

import {
    extractDocumentHistory,
    extractDocumentParents,
    extractDocumentChildren,
    extractDocumentPosts
} from './document-detail-dynamic-data-extractor.js';

import { getDocumentLogs } from './document-detail-data-extractor-logs.js';
import { getDocumentWorkflowLogs } from './document-detail-data-extractor-workflow-logs.js';
import { DocumentDetailSectionState } from './document-detail-section-state.js';
import { DocumentDetailButtons } from './document-detail-buttons.js';
import { DocumentDetailPermissions } from './document-detail-permissions.js';

export class DocumentDetailTemplate {
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

        const myPostsPayload = extractDocumentPosts(documentId);
// console.log('📬 Posts payload extracted:', myPostsPayload?.length || 0, 'items');
        
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
                DocumentDetailTemplate.updateLogsSection(myLogsPayload);
            }
        });

        // Initialize workflow logs payload and fetch workflow logs data
        let myWorkflowLogsPayload = [];
// console.log('📋 Starting workflow logs data fetch for documentId:', documentId);
        getDocumentWorkflowLogs(documentId, (workflowLogsData, error) => {
            if (error) {
                console.error('❌ Error fetching workflow logs data:', error);
            } else {
// console.log('✅ Workflow logs data fetched successfully:', workflowLogsData?.length || 0, 'workflow logs');
                myWorkflowLogsPayload = workflowLogsData || [];
                // Update the workflow logs section with real data
                DocumentDetailTemplate.updateWorkflowLogsSection(myWorkflowLogsPayload);
            }
        });

// console.log("🔍 About to generate template with myChildrenPayload:", myChildrenPayload);
        // First, return the template with placeholders
        const template = `
            ${DocumentDetailButtons.generateButtonsHTML()}
            
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
                        ${DocumentDetailDataSections.generateDataSections(fullpathSource, fullpathTarget, fullpathHistory)}
                        ${DocumentDetailDataSections.generateDocumentHistory(myHistoryPayload)}
                        ${DocumentDetailDataSections.generateParentDocumentsSection(myParentsPayload)}
                        ${DocumentDetailDataSections.generateChildDocumentsSection(myChildrenPayload)}
                        ${DocumentDetailDataSections.generatePostDocumentsSection(myPostsPayload)}
                        ${DocumentDetailDataSections.generateLogsSection(myLogsPayload)}
                        ${DocumentDetailDataSections.generateWorkflowLogsSection([])}
                        `;
                        
        // After returning the template, load ALL document data with a single call
        setTimeout(() => {
// console.log('🚀 Loading comprehensive document data...');
            
            // NEW APPROACH: Single API call + modular extraction
            getDocumentData(documentId, function(data, error) {
                if (error) {
                    console.error('❌ Could not get document data:', error);
                    DocumentDetailTemplate.showErrorState();
                    return;
                }
                
                // Add the document ID to the data object before processing
                data.id = documentId;
                
                // Extract and update each piece of data using modular functions
                DocumentDetailTemplate.updateRuleInfo(extractRuleInfo(data));
                DocumentDetailTemplate.updateDocumentStatus(extractDocumentStatus(data));
                DocumentDetailTemplate.updateDocumentType(extractDocumentType(data));
                DocumentDetailTemplate.updateDocumentAttempts(extractDocumentAttempts(data));
                DocumentDetailTemplate.updateDocumentDates(extractDocumentDates(data));
                
                // Update data sections with real data
                DocumentDetailTemplate.updateDataSections(data);
                
                // Update logos with real solution information
                DocumentDetailTemplate.updateLogos(data);
                
                // Update target editor with global status
                DocumentDetailTemplate.updateTargetEditor(data);
                
                // Update buttons based on document status and permissions
                DocumentDetailTemplate.updateButtons(data);
            });
        }, 100);

        return template;
    }
    
    // ===== MODULAR UPDATE FUNCTIONS =====
    
    static updateRuleInfo(ruleInfo) {
        if (!ruleInfo || !ruleInfo.name) {
            // console.warn('⚠️ No rule info available');
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
            } else {
                baseUrl = window.location.origin + '/index.php';
            }
            
            const ruleLink = `${baseUrl}/rule/view/${ruleInfo.id}`;
            linkElement.href = ruleLink;
            linkElement.textContent = ruleInfo.name;
// console.log('✅ Updated rule link:', ruleLink);
        }
    }
    
    static updateDocumentStatus(statusInfo) {
        if (!statusInfo) {
            // console.warn('⚠️ No status info available');
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
        const typeElement = document.getElementById('document-type');
        
        if (!typeElement) {
            // console.warn('⚠️ Document type element not found');
            return;
        }
        
        if (!typeInfo || !typeInfo.type || typeInfo.type === '') {
            // Handle empty or null type by showing empty string instead of "Loading..."
            typeElement.textContent = '';
// console.log('✅ Updated document type to empty (type is null/empty)');
        } else {
            typeElement.textContent = typeInfo.type;
// console.log('✅ Updated document type:', typeInfo.type);
        }
    }
    
    static updateDocumentAttempts(attemptInfo) {
        if (!attemptInfo) {
            // console.warn('⚠️ No attempt info available');
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
            // console.warn('⚠️ No date info available');
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
        try {
            // Store document data globally so that ID field generation can access it
            window.currentDocumentData = documentData;

            // Extract data for each section
            const sourceData = DocumentDetailTemplate.extractSourceData(documentData);
            const targetData = DocumentDetailTemplate.extractTargetData(documentData);
            const historyData = DocumentDetailTemplate.extractHistoryData(documentData);

            // Import FluxDataSections dynamically if needed, or call directly
            setTimeout(() => {
                DocumentDetailDataSections.updateSourceData(sourceData);
                DocumentDetailDataSections.updateTargetData(targetData);
                DocumentDetailDataSections.updateHistoryData(historyData);

                // Update direct links after data sections have been populated
                setTimeout(() => {
                    DocumentDetailTemplate.updateDirectLinks(documentData);

                    // Trigger field comparison after all data sections are updated
                    setTimeout(() => {
                        // Clean up global reference
                        window.currentDocumentData = null;

                        // Dispatch event to notify field comparator
                        const event = new CustomEvent('fluxDataUpdated', {
                            detail: {
                                source: 'DocumentDetailTemplate',
                                timestamp: new Date().toISOString()
                            }
                        });
                        document.dispatchEvent(event);
                    }, 100);
                }, 10); // Small delay to ensure content is rendered
            }, 50); // Small delay to ensure DOM sections are ready

// console.log('✅ All data sections update initiated');

        } catch (error) {
            console.error('❌ Error updating data sections:', error);
            DocumentDetailTemplate.showDataSectionErrors();
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
                // console.warn('⚠️ Logs section container not found in DOM');
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
            const newLogsHtml = DocumentDetailDataSections.generateLogsSection(logsData);
// console.log('📋 Generated new logs HTML, length:', newLogsHtml.length);
            
            // Replace the existing logs section
            logsContainer.outerHTML = newLogsHtml;
// console.log('✅ Logs section updated successfully');
            
            // Re-initialize the section state management for the new DOM elements
// console.log('🔄 Re-initializing section state after logs update...');
            setTimeout(() => {
                DocumentDetailSectionState.setupCollapsible('logs-section', 'logs', 'logs');
                DocumentDetailSectionState.setupPagination('logs-section', 'logs', logsData);
// console.log('✅ Logs section state re-initialized');
            }, 10);
            
        } catch (error) {
            console.error('❌ Error updating logs section:', error);
            console.error('Error stack:', error.stack);
        }
    }

    /**
     * Updates the workflow logs section with real workflow logs data
     * @param {Array} workflowLogsData - Array of workflow logs data
     */
    static updateWorkflowLogsSection(workflowLogsData) {
// console.log('📋 DocumentDetailTemplate.updateWorkflowLogsSection called with', workflowLogsData?.length || 0, 'workflow logs');
// console.log('📋 Sample workflow log data:', workflowLogsData?.[0]);
        
        try {
            const workflowLogsContainer = document.querySelector('.workflow-logs-section');
// console.log('📋 Workflow logs container found:', !!workflowLogsContainer);
            
            if (!workflowLogsContainer) {
                // console.warn('⚠️ Workflow logs section container not found in DOM');
                // Let's also check what containers do exist
                const allDataWrappers = document.querySelectorAll('.data-wrapper');
// console.log('📋 Available data-wrapper containers:', allDataWrappers.length);
                allDataWrappers.forEach((wrapper, index) => {
// console.log(`📋 Container ${index}:`, wrapper.className);
                });
                return;
            }

            if (!workflowLogsData || workflowLogsData.length === 0) {
// console.log('📋 No workflow logs data available, keeping empty section');
                return;
            }

            // Generate new workflow logs section HTML with real data
// console.log('📋 Generating new workflow logs HTML...');
            const newWorkflowLogsHtml = DocumentDetailDataSections.generateWorkflowLogsSection(workflowLogsData);
// console.log('📋 Generated new workflow logs HTML, length:', newWorkflowLogsHtml.length);
            
            // Replace the existing workflow logs section
            workflowLogsContainer.outerHTML = newWorkflowLogsHtml;
// console.log('✅ Workflow logs section updated successfully');
            
            // Re-initialize the section state management for the new DOM elements
// console.log('🔄 Re-initializing section state after workflow logs update...');
            setTimeout(() => {
                DocumentDetailSectionState.setupCollapsible('workflow-logs-section', 'workflow-logs', 'workflow-logs');
                DocumentDetailSectionState.setupPagination('workflow-logs-section', 'workflow-logs', workflowLogsData);
// console.log('✅ Workflow logs section state re-initialized');
            }, 10);
            
        } catch (error) {
            console.error('❌ Error updating workflow logs section:', error);
            console.error('Error stack:', error.stack);
        }
    }

    /**
     * Updates direct links to source and target records
     * @param {Object} documentData - Complete document data from API
     */
    static updateDirectLinks(documentData) {
        try {
            // Use the DocumentDetailDirectLinks class to update all direct links
            DocumentDetailDirectLinks.updateAllDirectLinks(documentData);
            
        } catch (error) {
            console.error('❌ Error updating direct links:', error);
        }
    }

    /**
     * Updates the target editor with document global status
     * @param {Object} documentData - Complete document data from API
     */
    static updateTargetEditor(documentData) {
        try {
            // Access the target editor instance through window
            if (window.documentDetailInstance && window.documentDetailInstance.targetEditor) {
                const globalStatus = documentData.global_status;
                window.documentDetailInstance.targetEditor.setDocumentGlobalStatus(globalStatus);
                // console.log('✅ Target editor updated with global status:', globalStatus);
            } else {
                // console.warn('⚠️ Target editor instance not found, will retry later');
                // Retry after a short delay as the target editor might still be initializing
                setTimeout(() => {
                    if (window.documentDetailInstance && window.documentDetailInstance.targetEditor) {
                        const globalStatus = documentData.global_status;
                        window.documentDetailInstance.targetEditor.setDocumentGlobalStatus(globalStatus);
                        // console.log('✅ Target editor updated with global status (retry):', globalStatus);
                    }
                }, 1000);
            }
        } catch (error) {
            console.error('❌ Error updating target editor with global status:', error);
        }
    }

    /**
     * Updates the document action buttons based on status and permissions
     * @param {Object} documentData - Complete document data from API
     */
    static async updateButtons(documentData) {
        // console.log('🔘 Updating document buttons based on status and permissions');
        
        try {
            // Get current user permissions
            const userPermissions = await DocumentDetailPermissions.getCurrentUserPermissions();
            const permissions = DocumentDetailButtons.processPermissions(userPermissions);
            
            // For debugging - can be removed in production
            DocumentDetailButtons.debugButtonLogic(documentData, permissions);
            
            // Update the buttons
            DocumentDetailButtons.updateButtons(documentData, permissions);
            
        } catch (error) {
            console.error('❌ Error updating document buttons:', error);
            // Fallback to basic permissions
            const fallbackPermissions = { is_super_admin: false };
            DocumentDetailButtons.updateButtons(documentData, fallbackPermissions);
        }
    }

    /**
     * Updates the solution logos with real solution information
     * @param {Object} documentData - Complete document data from API
     */
    static updateLogos(documentData) {
// console.log('🖼️ Updating logos with solution data');
        
        try {
            if (!documentData || !documentData.source_solution || !documentData.target_solution) {
                // console.warn('⚠️ Solution information not available in document data');
                return;
            }

            let path_img_modal = "../../../build/images/solution/";

            const pathParts = window.location.pathname.split('/');
            const publicIndex = pathParts.indexOf('public');
            if (publicIndex == -1) {
                path_img_modal = "../../../../build/images/solution/";
            }
            const sourceSolution = documentData.source_solution.toLowerCase();
            const targetSolution = documentData.target_solution.toLowerCase();
            
// console.log('🖼️ Source solution:', sourceSolution, 'Target solution:', targetSolution);

            // Check what sections and logo elements exist in the DOM
            const dataSections = document.querySelectorAll('.data-wrapper, .source-section, .target-section, .history-section');
// console.log('🖼️ Data sections found:', dataSections.length);
            dataSections.forEach((section, index) => {
// console.log(`🖼️ Section ${index}:`, section.className);
            });
            
            const allLogos = document.querySelectorAll('img');
// console.log('🖼️ All images in DOM:', allLogos.length);
            allLogos.forEach((img, index) => {
// console.log(`🖼️ Image ${index}:`, img.className, img.src);
            });

            // Update all logo images (they all have logo-small-size class)
            const logoImages = document.querySelectorAll('.logo-small-size');
// console.log('🖼️ Found', logoImages.length, 'logo images to update');
            
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
// console.log(`🖼️ Updating ${logoType} logo (index ${index}):`, logoPath);
                
                img.src = logoPath;
                img.alt = `${solutionName} logo`;
                
// console.log(`✅ Updated ${logoType} logo:`, logoPath);
            });

        } catch (error) {
            console.error('❌ Error updating logos:', error);
        }
    }
}