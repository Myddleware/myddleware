import { FluxDataSections } from './flux-data-sections.js';
import { 
    getDocumentData, 
    extractRuleInfo, 
    extractDocumentStatus, 
    extractDocumentType, 
    extractDocumentAttempts, 
    extractDocumentDates,
    getRuleName  // Keep for backward compatibility
} from './flux-data-extractor.js';

export class FluxTemplate {
    static generateHTML() {
        const path_img_modal = "../../../build/images/solution/";
        const solutionSource = "salesforce.png";
        const solutionTarget = "hubspot.png";
        const solutionHistory = "hubspot.png";

        const fullpathSource = `${path_img_modal}${solutionSource}`;
        const fullpathTarget = `${path_img_modal}${solutionTarget}`;
        const fullpathHistory = `${path_img_modal}${solutionHistory}`;

        // the url is like http://localhost/myddleware_NORMAL/public/rule/flux/modern/6863a07946e8b9.38306852
        // we need to get 6863a07946e8b9.3830685
        let documentId = window.location.pathname.split('/').pop();

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
        `;

        // After returning the template, load ALL document data with a single call
        setTimeout(() => {
            console.log('🚀 Loading comprehensive document data...');
            
            // NEW APPROACH: Single API call + modular extraction
            getDocumentData(documentId, function(data, error) {
                if (error) {
                    console.error('❌ Could not get document data:', error);
                    FluxTemplate.showErrorState();
                    return;
                }
                
                console.log('✅ Document data loaded successfully!');
                
                // Extract and update each piece of data using modular functions
                FluxTemplate.updateRuleInfo(extractRuleInfo(data));
                FluxTemplate.updateDocumentStatus(extractDocumentStatus(data));
                FluxTemplate.updateDocumentType(extractDocumentType(data));
                FluxTemplate.updateDocumentAttempts(extractDocumentAttempts(data));
                FluxTemplate.updateDocumentDates(extractDocumentDates(data));
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
            console.log('✅ Updated rule link:', ruleLink);
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
        
        console.log('✅ Updated document status with colors');
    }
    
    static updateDocumentType(typeInfo) {
        if (!typeInfo) {
            console.warn('⚠️ No type info available');
            return;
        }
        
        const typeElement = document.getElementById('document-type');
        if (typeElement && typeInfo.type) {
            typeElement.textContent = typeInfo.type;
            console.log('✅ Updated document type');
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
            console.log('✅ Updated document attempts');
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
        
        console.log('✅ Updated document dates');
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
}