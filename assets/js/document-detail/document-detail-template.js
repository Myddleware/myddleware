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
import { DocumentDetailDateFormatter } from './document-detail-date-formatter.js';

import {
    extractDocumentHistory,
    extractDocumentParents,
    extractDocumentChildren
} from './document-detail-dynamic-data-extractor.js';

import { getDocumentLogs } from './document-detail-data-extractor-logs.js';
import { getDocumentWorkflowLogs } from './document-detail-data-extractor-workflow-logs.js';
import { DocumentDetailSectionState } from './document-detail-section-state.js';
import { DocumentDetailButtons } from './document-detail-buttons.js';
import { DocumentDetailPermissions } from './document-detail-permissions.js';
import { getBaseUrl, getSolutionImagePath } from './document-detail-url-utils.js';

export class DocumentDetailTemplate {
    static generateHTML() {
        const path_img_modal = DocumentDetailTemplate.getSolutionImagePath();
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

        
        const myHistoryPayload = extractDocumentHistory(documentId);
        
        const myParentsPayload = extractDocumentParents(documentId);

        // Handle the promise from extractDocumentChildren
        let myChildrenPayload = [];
        extractDocumentChildren(documentId).then(data => {
            myChildrenPayload = data;
        });
        
        // Initialize logs payload and fetch logs data
        let myLogsPayload = [];
        getDocumentLogs(documentId, (logsData, error) => {
            if (error) {
                console.error(' Error fetching logs data:', error);
            } else {
                myLogsPayload = logsData || [];
                // Update the logs section with real data
                DocumentDetailTemplate.updateLogsSection(myLogsPayload);
            }
        });

        // Initialize workflow logs payload and fetch workflow logs data
        let myWorkflowLogsPayload = [];
        getDocumentWorkflowLogs(documentId, (workflowLogsData, error) => {
            if (error) {
                console.error(' Error fetching workflow logs data:', error);
            } else {
                myWorkflowLogsPayload = workflowLogsData || [];
                // Update the workflow logs section with real data
                DocumentDetailTemplate.updateWorkflowLogsSection(myWorkflowLogsPayload);
            }
        });

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
                            <th>Reference 
                                <button type="button" class="help-pop" data-help="Date of data in the source application" aria-label="Help" style="background:none;border:0;padding:0;cursor:pointer;">
                                    <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                                </button>
                            </th>
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
                        ${DocumentDetailDataSections.generateLogsSection(myLogsPayload)}
                        ${DocumentDetailDataSections.generateWorkflowLogsSection([])}
                        `;
                        
        // After returning the template, load ALL document data with a single call
        setTimeout(() => {
            
            // NEW APPROACH: Single API call + modular extraction
            getDocumentData(documentId, function(data, error) {
                if (error) {
                    console.error(' Could not get document data:', error);
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

                // Update parent_id column (conditionally displayed)
                DocumentDetailTemplate.updateParentId(data);
            });
        }, 100);

        return template;
    }
    
    // ===== MODULAR UPDATE FUNCTIONS =====
    
    static updateRuleInfo(ruleInfo) {
        if (!ruleInfo || !ruleInfo.name) {
            return;
        }
        
        const linkElement = document.getElementById('rule-link');
        if (linkElement) {
            // Get base URL for link construction
            const baseUrl = getBaseUrl();
            const ruleLink = `${baseUrl}/rule/view/${ruleInfo.id}`;
            linkElement.href = ruleLink;
            linkElement.textContent = ruleInfo.name;
        }
    }
    
    static updateDocumentStatus(statusInfo) {
        if (!statusInfo) {
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
        
    }
    
    static updateDocumentType(typeInfo) {
        const typeElement = document.getElementById('document-type');
        
        if (!typeElement) {
            return;
        }
        
        if (!typeInfo || !typeInfo.type || typeInfo.type === '') {
            // Handle empty or null type by showing empty string instead of "Loading..."
            typeElement.textContent = '';
        } else {
            typeElement.textContent = typeInfo.type;
        }
    }
    
    static updateDocumentAttempts(attemptInfo) {
        if (!attemptInfo) {
            return;
        }
        
        const attemptElement = document.getElementById('document-attempt');
        if (attemptElement) {
            let attemptText = attemptInfo.attempt.toString();
            if (attemptInfo.maxAttempts) {
                attemptText += ` / ${attemptInfo.maxAttempts}`;
            }
            attemptElement.textContent = attemptText;
        }
    }
    
    static updateDocumentDates(dateInfo) {
        if (!dateInfo) {
            console.warn('No date info available');
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

    }

    /**
     * Updates the parent_id column - only displays if parent_id is not empty
     * @param {Object} documentData - Complete document data from API
     */
    static updateParentId(documentData) {
        const parentId = documentData?.parent_id;

        // Only display parent_id if it's not empty
        if (!parentId || parentId === '' || parentId === null) {
            return;
        }

        // Find the table header and body
        const table = document.getElementById('flux-table');
        if (!table) {
            console.error('Flux table not found');
            return;
        }

        const headerRow = table.querySelector('thead tr');
        const bodyRow = table.querySelector('tbody tr');

        if (!headerRow || !bodyRow) {
            console.error('Table header or body row not found');
            return;
        }

        // Find the last header cell (Modification Date) to insert before it
        const lastHeaderCell = headerRow.querySelector('.rounded-table-up-right');

        // Create the Parent ID header cell
        const parentIdHeader = document.createElement('th');
        parentIdHeader.textContent = 'Parent ID';

        // Insert before the last header cell (Modification Date stays as last with rounded corner)
        if (lastHeaderCell) {
            headerRow.insertBefore(parentIdHeader, lastHeaderCell);
        } else {
            headerRow.appendChild(parentIdHeader);
        }

        // Find the modification date cell (last cell) in the body to insert before it
        const lastBodyCell = bodyRow.querySelector('#document-modification-date');

        // Build proper URL for parent document link
        const pathParts = window.location.pathname.split('/');
        const publicIndex = pathParts.indexOf('public');
        let baseUrl = window.location.origin;
        if (publicIndex !== -1) {
            const baseParts = pathParts.slice(0, publicIndex + 1);
            baseUrl = window.location.origin + baseParts.join('/');
            if (publicIndex > 0 && pathParts[publicIndex - 1] !== 'index.php') {
                baseUrl += '/index.php';
            }
        } else {
            baseUrl = window.location.origin + '/index.php';
        }

        const parentDocUrl = `${baseUrl}/rule/flux/modern/${parentId}`;

        // Create the Parent ID body cell with a link
        const parentIdCell = document.createElement('td');
        parentIdCell.id = 'document-parent-id';
        parentIdCell.innerHTML = `<a href="${parentDocUrl}" style="color: #0F66A9; text-decoration: none;">${parentId}</a>`;

        // Insert before the modification date cell
        if (lastBodyCell) {
            bodyRow.insertBefore(parentIdCell, lastBodyCell);
        } else {
            bodyRow.appendChild(parentIdCell);
        }

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


        } catch (error) {
            console.error(' Error updating data sections:', error);
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
            return sourceData || null;
        } catch (error) {
            console.error(' Error extracting source data:', error);
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
            return targetData || null;
        } catch (error) {
            console.error(' Error extracting target data:', error);
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
            return historyData || null;
        } catch (error) {
            console.error(' Error extracting history data:', error);
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
        
        try {
            const logsContainer = document.querySelector('.logs-section');
            
            if (!logsContainer) {
                // Let's also check what containers do exist
                const allDataWrappers = document.querySelectorAll('.data-wrapper');
                allDataWrappers.forEach((wrapper, index) => {
                });
                return;
            }

            if (!logsData || logsData.length === 0) {
                return;
            }

            // Generate new logs section HTML with real data
            const newLogsHtml = DocumentDetailDataSections.generateLogsSection(logsData);
            
            // Replace the existing logs section
            logsContainer.outerHTML = newLogsHtml;
            
            // Re-initialize the section state management for the new DOM elements
            setTimeout(() => {
                DocumentDetailSectionState.setupCollapsible('logs-section', 'logs', 'logs');
                DocumentDetailSectionState.setupPagination('logs-section', 'logs', logsData);
            }, 10);
            
        } catch (error) {
            console.error(' Error updating logs section:', error);
            console.error('Error stack:', error.stack);
        }
    }

    /**
     * Updates the workflow logs section with real workflow logs data
     * @param {Array} workflowLogsData - Array of workflow logs data
     */
    static updateWorkflowLogsSection(workflowLogsData) {
        
        try {
            const workflowLogsContainer = document.querySelector('.workflow-logs-section');
            
            if (!workflowLogsContainer) {
                // Let's also check what containers do exist
                const allDataWrappers = document.querySelectorAll('.data-wrapper');
                allDataWrappers.forEach((wrapper, index) => {
                });
                return;
            }

            if (!workflowLogsData || workflowLogsData.length === 0) {
                return;
            }

            // Generate new workflow logs section HTML with real data
            const newWorkflowLogsHtml = DocumentDetailDataSections.generateWorkflowLogsSection(workflowLogsData);
            
            // Replace the existing workflow logs section
            workflowLogsContainer.outerHTML = newWorkflowLogsHtml;
            
            // Re-initialize the section state management for the new DOM elements
            setTimeout(() => {
                DocumentDetailSectionState.setupCollapsible('workflow-logs-section', 'workflow-logs', 'workflow-logs');
                DocumentDetailSectionState.setupPagination('workflow-logs-section', 'workflow-logs', workflowLogsData);
            }, 10);
            
        } catch (error) {
            console.error(' Error updating workflow logs section:', error);
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
            console.error(' Error updating direct links:', error);
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
            } else {
                // Retry after a short delay as the target editor might still be initializing
                setTimeout(() => {
                    if (window.documentDetailInstance && window.documentDetailInstance.targetEditor) {
                        const globalStatus = documentData.global_status;
                        window.documentDetailInstance.targetEditor.setDocumentGlobalStatus(globalStatus);
                    }
                }, 1000);
            }
        } catch (error) {
            console.error(' Error updating target editor with global status:', error);
        }
    }

    /**
     * Updates the document action buttons based on status and permissions
     * @param {Object} documentData - Complete document data from API
     */
    static async updateButtons(documentData) {
        
        try {
            // Get current user permissions
            const userPermissions = await DocumentDetailPermissions.getCurrentUserPermissions();
            const permissions = DocumentDetailButtons.processPermissions(userPermissions);

            // Update the buttons
            DocumentDetailButtons.updateButtons(documentData, permissions);
            
        } catch (error) {
            console.error(' Error updating document buttons:', error);
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
        
        try {
            if (!documentData || !documentData.source_solution || !documentData.target_solution) {
                return;
            }

            const path_img_modal = DocumentDetailTemplate.getSolutionImagePath();
            const sourceSolution = documentData.source_solution.toLowerCase();
            const targetSolution = documentData.target_solution.toLowerCase();
            

            // Check what sections and logo elements exist in the DOM
            const dataSections = document.querySelectorAll('.data-wrapper, .source-section, .target-section, .history-section');
            dataSections.forEach((section, index) => {
            });
            
            const allLogos = document.querySelectorAll('img');
            allLogos.forEach((img, index) => {
            });

            // Update all logo images (they all have logo-small-size class)
            const logoImages = document.querySelectorAll('.logo-small-size');
            
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
                
                img.src = logoPath;
                img.alt = `${solutionName} logo`;
                
            });

        } catch (error) {
            console.error(' Error updating logos:', error);
        }
    }

    /**
     * Gets the absolute path to solution images, handling index.php in URL
     * @returns {string} Absolute path to solution images folder
     */
    static getSolutionImagePath() {
        return getSolutionImagePath();
    }
}
if (!window.__helpPopBound) {
    window.__helpPopBound = true;

    let __pop = null;
    const __close = () => { if (__pop) { __pop.remove(); __pop = null; } };
    const __show = (anchor, html) => {
        __close();
        // Position the popover just below the clicked icon, accounting for page scroll
        const r = anchor.getBoundingClientRect(), px = window.scrollX, py = window.scrollY;
        __pop = document.createElement('div');
        __pop.className = 'popover';
        __pop.innerHTML = `<button class="close-x" aria-label="Close">&times;</button>${html || ''}`;
        Object.assign(__pop.style, {
        position: 'absolute', left: (r.left + px - 10) + 'px', top: (r.bottom + py + 10) + 'px', zIndex: 9999
        });
        document.body.appendChild(__pop);
        __pop.querySelector('.close-x').addEventListener('click', __close, { once: true });
        
        // Close on outside click or Escape for good UX and a11y
        setTimeout(() => document.addEventListener('click', (e) => {
        if (__pop && !__pop.contains(e.target)) __close();
        }, { once: true }), 0);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') __close(); }, { once: true });
    };

    // Event delegation: handle clicks on any .help-pop (even if added dynamically)
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.help-pop[data-help]');
        if (!btn) return;
        e.preventDefault(); e.stopPropagation();
        __show(btn, btn.getAttribute('data-help') || '');
    });
}

