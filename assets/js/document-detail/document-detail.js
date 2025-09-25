// console.log('flux.js loaded');

import { DocumentDetailTemplate } from './document-detail-template.js';
import { DocumentDetailEvents } from './document-detail-events.js';
import { DocumentDetailFieldExpander } from './document-detail-field-expander.js';
import { DocumentDetailSectionState } from './document-detail-section-state.js';
import { MultilineLinkHandler } from '../multiline-links/multiline-link-handler.js';
import { DocumentDetailTargetEditor } from './document-detail-target-editor.js';
import { DocumentDetailFieldComparator } from './document-detail-field-comparator.js';

export class DocumentDetail {
    constructor() {
// console.log('Flux constructor called');
        this.multilineLinkHandler = null;
        this.targetEditor = null;
        this.init();
    }

    async init() {
// console.log('Flux init starting');
        this.createUIStructure();
        DocumentDetailEvents.setupEventListeners();
        DocumentDetailFieldExpander.init();
        DocumentDetailFieldComparator.init();
        
        // Initialize multiline link handler and target editor after UI is created
        setTimeout(() => {
            this.multilineLinkHandler = new MultilineLinkHandler();
// console.log('🔗 MultilineLinkHandler initialized in Flux');
            
            this.targetEditor = new DocumentDetailTargetEditor();
// console.log('🖊️ DocumentDetailTargetEditor initialized in Flux');
        }, 500);
    }

    createUIStructure() {
// console.log('Flux createUIStructure called');

        const fluxContainer = document.getElementById('flux-container');

        if (!fluxContainer) {
            console.error('❌ flux-container not found in DOM');
            return;
        }

// console.log('✅ flux-container found, generating template...');
        fluxContainer.innerHTML = DocumentDetailTemplate.generateHTML();
// console.log('✅ Template HTML inserted into flux-container');
        
        // Wait for the template's setTimeout to complete (template uses 100ms)
        // Then initialize state management
        setTimeout(() => {
// console.log('🔧 Initializing section state management...');
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
// console.log('🚀 DOM loaded, initializing Flux...');
    window.documentDetailInstance = new DocumentDetail();
});