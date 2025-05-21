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
    }
}

// Initialize the account manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new Flux();
});
