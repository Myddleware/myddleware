
import { DocumentDetailTemplate } from './document-detail-template.js';
import { DocumentDetailEvents } from './document-detail-events.js';
import { DocumentDetailFieldExpander } from './document-detail-field-expander.js';
import { DocumentDetailSectionState } from './document-detail-section-state.js';
import { MultilineLinkHandler } from '../multiline-links/multiline-link-handler.js';
import { DocumentDetailTargetEditor } from './document-detail-target-editor.js';
import { DocumentDetailFieldComparator } from './document-detail-field-comparator.js';

export class DocumentDetail {
    constructor() {
        this.multilineLinkHandler = null;
        this.targetEditor = null;
        this.init();
    }

    async init() {
        this.createUIStructure();
        DocumentDetailEvents.setupEventListeners();
        DocumentDetailFieldExpander.init();
        DocumentDetailFieldComparator.init();
        
        // Initialize multiline link handler and target editor after UI is created
        setTimeout(() => {
            this.multilineLinkHandler = new MultilineLinkHandler();
            
            this.targetEditor = new DocumentDetailTargetEditor();
        }, 500);
    }

    createUIStructure() {

        const fluxContainer = document.getElementById('flux-container');

        if (!fluxContainer) {
            console.error(' flux-container not found in DOM');
            return;
        }

        fluxContainer.innerHTML = DocumentDetailTemplate.generateHTML();
        
        // Wait for the template's setTimeout to complete (template uses 100ms)
        // Then initialize state management
        setTimeout(() => {
            this.initializeSectionStateManagement();
        }, 300);
    }

    initializeSectionStateManagement() {
        // Clean up any expired entries first
        DocumentDetailSectionState.cleanupExpiredEntries();
        
        // Initialize all sections with empty data - real data will be loaded via API calls
        DocumentDetailSectionState.initializeSections({
            documentsHistory: [],
            parentDocuments: [],
            childDocuments: [],
            workflowLogs: [],
            logs: []
        });
    }
}

// Initialize the flux manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.documentDetailInstance = new DocumentDetail();
});