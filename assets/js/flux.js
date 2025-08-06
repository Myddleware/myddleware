// console.log('flux.js loaded');

import { FluxTemplate } from './flux-template.js';
import { FluxEvents } from './flux-events.js';
import { FluxFieldExpander } from './flux-field-expander.js';
import { FluxSectionState } from './flux-section-state.js';
import { MultilineLinkHandler } from './multiline-links/multiline-link-handler.js';

export class Flux {
    constructor() {
        // console.log('Flux constructor');
        this.multilineLinkHandler = null;
        this.init();
    }

    async init() {
        // console.log('Flux init');
        this.createUIStructure();
        FluxEvents.setupEventListeners();
        FluxFieldExpander.init();
        
        // Initialize multiline link handler after UI is created
        setTimeout(() => {
            this.multilineLinkHandler = new MultilineLinkHandler();
            // console.log('🔗 MultilineLinkHandler initialized in Flux');
        }, 500);
    }

    createUIStructure() {
        // console.log('Flux createUIStructure');

        const fluxContainer = document.getElementById('flux-container');

        if (!fluxContainer) {
            console.error('flux-container not found');
            return;
        }

        fluxContainer.innerHTML = FluxTemplate.generateHTML();
        
        // Wait for the template's setTimeout to complete (template uses 100ms)
        // Then initialize state management
        setTimeout(() => {
            this.initializeSectionStateManagement();
        }, 300);
    }

    initializeSectionStateManagement() {
        // Clean up any expired entries first
        FluxSectionState.cleanupExpiredEntries();
        
        // Initialize all sections with empty data - real data will be loaded via API calls
        FluxSectionState.initializeSections({
            documentsHistory: [],
            parentDocuments: [],
            childDocuments: [],
            logs: []
        });
    }
}

// Initialize the flux manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new Flux();
});