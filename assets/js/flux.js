console.log('flux.js loaded');

import * as THREE from 'three';
import { ThreeModal } from '../../public/assets/js/three-modal.js';
import { FluxTemplate } from './flux-template.js';
import { FluxEvents } from './flux-events.js';
import { FluxFieldExpander } from './flux-field-expander.js';

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
    }
}

// Initialize the flux manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new Flux();
});