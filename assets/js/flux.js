console.log('flux.js loaded');

import * as THREE from 'three';
import axios from 'axios';
import { ThreeModal } from '../../public/assets/js/three-modal.js';

export class Flux {
    constructor() {
        console.log('Flux constructor');
        this.init();
    }

    async init() {
        console.log('Flux init');
        this.createUIStructure();
    }

    createUIStructure() {
        console.log('Flux createUIStructure');

        const fluxContainer = document.getElementById('flux-container');

        if (!fluxContainer) {
            console.error('flux-container not found');
            return;
        }

        fluxContainer.innerHTML = `
            <div class="flex-row" id="flux-button-container">
                <button id="run-same-record">Run same record</button>
                <button id="cancel-document">Cancel document</button>
            </div>
        `;

    }
}

// Initialize the account manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new Flux();
});
