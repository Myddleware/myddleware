import { FluxDataSections } from './flux-data-sections.js';

export class FluxTemplate {
    static generateHTML() {
        const path_img_modal = "../../../build/images/solution/";
        const solutionSource = "salesforce.png";
        const solutionTarget = "hubspot.png";
        const solutionHistory = "hubspot.png";

        const fullpathSource = `${path_img_modal}${solutionSource}`;
        const fullpathTarget = `${path_img_modal}${solutionTarget}`;
        const fullpathHistory = `${path_img_modal}${solutionHistory}`;

        return `
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
${FluxDataSections.generateDataSections(fullpathSource, fullpathTarget, fullpathHistory)}
        `;
    }
}
// THIS IS THE HARD LIMIT FOR FILE LENGTH, WE CANNOT GO ABOVE 50 LINES --------------------------------- 