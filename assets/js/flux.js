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

        const path_img_modal = "../../../build/images/solution/";

        const solutionSource = "salesforce.png";
        const solutionTarget = "hubspot.png";
        const solutionHistory = "hubspot.png";

        const fullpathSource = `${path_img_modal}${solutionSource}`;
        const fullpathTarget = `${path_img_modal}${solutionTarget}`;
        const fullpathHistory = `${path_img_modal}${solutionHistory}`;






        fluxContainer.innerHTML = `
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
                            <td><a href="#" style="color: #0F66A9; font-weight: bold; text-decoration: none;">Moodle Users to contacts</a></td>
                            <td><span class="gblstatus_close">Send ✓</span></td>
                            <td>C</td>
                            <td>1</td>
                            <td>Error</td>
                            <td>2024-01-15 10:30:00</td>
                            <td>2024-01-15 10:30:00</td>
                            <td>2024-01-15 10:30:00</td>
                        </tr>
                    </tbody>
                </table>
            </div>


            <div class="data-wrapper" style="margin: 20px;">
                <div class="source-data">
                    <div class="source-data-content">
                        <div class="source-data-content-header">
                            <div class="source-logo-container">
                                <img src="${fullpathSource}" alt="Source Logo">
                            </div>
                            <h3>Source</h3>
                        </div>
                        <div class="source-data-content-body">
                            <div class="field-row">
                                <div class="field-label">Email</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="luciefaure@myddleware.com">luciefaure@myddleware.com</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">FirstnameFirstnameFirstnameFirstnameFirstname</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="Lucie">Lucie</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">Lastname</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="FAURE">FAURE</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">Address</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="1234 rue de la fleur 16800 Rive">1234 rue de la fleur 16800 Rive</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">CourseID</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="XID80073">XID80073</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="target-data">
                    <div class="target-data-content">
                        <div class="target-data-content-header">
                            <div class="target-logo-container">
                                <img src="${fullpathTarget}" alt="Target Logo">
                            </div>
                            <h3>Target</h3>
                        </div>
                        <div class="target-data-content-body">
                            <div class="field-row">
                                <div class="field-label">Email</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="luciefaure@myddleware.com">luciefaure@myddleware.com</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">Firstname</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="Lucie">Lucie</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">Lastname</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="FAURE">FAURE</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">Address</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="1234 rue de la fleur 16800 Rive">1234 rue de la fleur 16800 Rive</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">CourseID</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="XID80073">XID80073</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="history-data">
                    <div class="history-data-content">
                        <div class="history-data-content-header">
                            <div class="history-logo-container">
                                <img src="${fullpathHistory}" alt="History Logo">
                            </div>
                            <h3>History</h3>
                        </div>
                        <div class="history-data-content-body">
                            <div class="field-row">
                                <div class="field-label">Email</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="luciefaure@myddleware.com">luciefaure@myddleware.com</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">Firstname</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="Lucie">Lucie</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">Lastname</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="FAURE">FAURE</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">Address</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="1234 rue de la fleur 16800 Rive">1234 rue de la fleur 16800 Rive</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">CourseID</div>
                                <div class="field-separator"></div>
                                <div class="field-value" title="XID80073">XID80073</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

    }
}

// Initialize the account manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new Flux();
});
