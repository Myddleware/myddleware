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
        this.setupPagination();
        this.setupCollapsible();
    }

    setupPagination() {
        const pageSize = 5;
        const rows = Array.from(
            document.querySelectorAll('.custom-section .custom-table tbody tr')
        );
        const pageCount = Math.ceil(rows.length / pageSize);

        const controls = document.createElement('div');
        controls.className = 'pagination-controls';
        for (let i = 1; i <= pageCount; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = 'pagination-btn';
            btn.addEventListener('click', () => {
                rows.forEach((row, idx) => {
                    row.style.display =
                        idx >= (i - 1) * pageSize && idx < i * pageSize
                            ? ''
                            : 'none';
                });
                controls.querySelectorAll('.pagination-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
            controls.appendChild(btn);
        }

        const customSection = document.querySelector('.custom-section');
        customSection.appendChild(controls);
        controls.querySelector('.pagination-btn').click();
    }

    setupCollapsible() {
        const container = document.getElementById('flux-container');
        const btn = document.querySelector('.custom-header .toggle-btn');
        const content = document.querySelector('.custom-content');

        btn.addEventListener('click', () => {
            const expanded = btn.getAttribute('aria-expanded') === 'true';
            content.style.display = expanded ? 'none' : '';
            btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            btn.textContent = expanded ? '+' : '-';
            });
    }

}

// Initialize the flux manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new Flux();
});