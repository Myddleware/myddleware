console.log('flux.js loaded');

import * as THREE from 'three';
import { ThreeModal } from '../../public/assets/js/three-modal.js';
import { FluxTemplate } from './flux-template.js';
import { FluxEvents } from './flux-events.js';
import { FluxFieldExpander } from './flux-field-expander.js';
import { FluxSectionState } from './flux-section-state.js';
import { MultilineLinkHandler } from './multiline-links/multiline-link-handler.js';

export class Flux {
    constructor() {
        console.log('Flux constructor');
        this.multilineLinkHandler = null;
        this.init();
    }

    async init() {
        console.log('Flux init');
        this.createUIStructure();
        FluxEvents.setupEventListeners();
        FluxFieldExpander.init();
        
        // Initialize multiline link handler after UI is created
        setTimeout(() => {
            this.multilineLinkHandler = new MultilineLinkHandler();
            console.log('🔗 MultilineLinkHandler initialized in Flux');
        }, 500);
    }

    createUIStructure() {
        console.log('Flux createUIStructure');

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
        
        // Get fixture data from template (this would normally come from API)
        const fixtureData = this.getFixtureData();
        
        // Initialize all sections with independent state management
        FluxSectionState.initializeSections({
            documentsHistory: fixtureData.documents,
            parentDocuments: fixtureData.documents,
            childDocuments: fixtureData.documents,
            logs: fixtureData.logs
        });
    }

    getFixtureData() {
        // Documents data structure
        const documentsData = [
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
        ].concat(Array(16).fill().map((_, i) => ({
            docId: `test${i + 1}.id`,
            name: `Test Document ${i + 1}`,
            sourceId: '1079335',
            targetId: '5ccf4c12-14e6-7464-a5c8-66d0299f1c2d',
            modificationDate: '30/07/2025 08:30:02',
            type: 'U',
            status: i % 2 === 0 ? 'Error_transformed' : 'Cancel'
        })));

        // Logs data structure matching the screenshot
        const logsData = [
            {
                id: '207568887',
                reference: '67c80b9b825da9.34866137',
                job: '67c80b9b825da9.34866137',
                creationDate: '05/03/2025 09:30:30',
                type: 'S ✓',
                message: 'Status : Send'
            },
            {
                id: '207568886',
                reference: '67c80b9b825da9.34866137',
                job: '67c80b9b825da9.34866137',
                creationDate: '05/03/2025 09:30:30',
                type: 'S ✓',
                message: 'Target id : 129055'
            }
        ].concat(Array(16).fill().map((_, i) => ({
            id: `20756${8860 + i}`,
            reference: '67c80b9b825da9.34866137',
            job: '67c80b9b825da9.34866137',
            creationDate: '05/03/2025 09:30:30',
            type: 'S ✓',
            message: [
                'Status : Ready_to_send',
                'Status : Transformed',
                'Status : Relate_OK',
                'Status : Predecessor_OK',
                'Type : U',
                'Status : Filter_OK',
                'Status : New'
            ][i % 7]
        })));

        return {
            documents: documentsData,
            logs: logsData
        };
    }
}

// Initialize the flux manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new Flux();
});