console.log('flux.js loaded');

import * as THREE from 'three';
import { ThreeModal } from '../../public/assets/js/three-modal.js';
import { FluxTemplate } from './flux-template.js';
import { FluxEvents } from './flux-events.js';
import { FluxFieldExpander } from './flux-field-expander.js';
import { FluxSectionState } from './flux-section-state.js';

export class Flux {
    constructor() {
        console.log('Flux constructor');
        this.init();
    }

    async init() {
        console.log('Flux init');
        this.createUIStructure();
        FluxEvents.setupEventListeners();
        FluxFieldExpander.init();
    }

    createUIStructure() {
        console.log('Flux createUIStructure');

        const fluxContainer = document.getElementById('flux-container');

        if (!fluxContainer) {
            console.error('flux-container not found');
            return;
        }

        fluxContainer.innerHTML = FluxTemplate.generateHTML();
        
        // Initialize state management after DOM is ready
        setTimeout(() => {
            this.initializeSectionStateManagement();
        }, 200);
    }

    initializeSectionStateManagement() {
        // Clean up any expired entries first
        FluxSectionState.cleanupExpiredEntries();
        
        // Get fixture data from template (this would normally come from API)
        const fixtureData = this.getFixtureData();
        
        // Initialize all sections with independent state management
        FluxSectionState.initializeSections({
            documentsHistory: fixtureData,
            parentDocuments: fixtureData,
            childDocuments: fixtureData
        });
    }

    getFixtureData() {
        // This should match the data structure from flux-template.js
        return [
            {
                docId: '6889b6292eb4e6.41501526',
                name: 'REEC – Engagé vers COMET',
                sourceId: '1079335',
                targetId: '5ccf4c12-14e6-7464-a5c8-66d0299f1c2d',
                modificationDate: '30/07/2025 08:30:02',
                type: 'U',
                status: 'Error_transformed'
            },
            {
                docId: '6889aef46f03f7.32307392',
                name: 'REEC – Engagé vers COMET',
                sourceId: '1079335',
                targetId: '5ccf4c12-14e6-7464-a5c8-66d0299f1c2d',
                modificationDate: '30/07/2025 07:30:01',
                type: 'U',
                status: 'Cancel'
            }
            // Add more fixture data as needed for testing
        ].concat(Array(16).fill().map((_, i) => ({
            docId: `test${i}.id`,
            name: `Test Document ${i + 1}`,
            sourceId: '1079335',
            targetId: '5ccf4c12-14e6-7464-a5c8-66d0299f1c2d',
            modificationDate: '30/07/2025 08:30:02',
            type: 'U',
            status: i % 2 === 0 ? 'Error_transformed' : 'Cancel'
        })));
}

} // end of the class 

// Initialize the flux manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new Flux();
});