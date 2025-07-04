import { FluxDataSections } from './flux-data-sections.js';
import { getRuleName } from './flux-data-extractor.js';

export class FluxTemplate {
    static generateHTML() {
        const path_img_modal = "../../../build/images/solution/";
        const solutionSource = "salesforce.png";
        const solutionTarget = "hubspot.png";
        const solutionHistory = "hubspot.png";

        const fullpathSource = `${path_img_modal}${solutionSource}`;
        const fullpathTarget = `${path_img_modal}${solutionTarget}`;
        const fullpathHistory = `${path_img_modal}${solutionHistory}`;

        // the url is like http://localhost/myddleware_NORMAL/public/rule/flux/modern/6863a07946e8b9.38306852
        // we need to get 6863a07946e8b9.3830685
        let documentId = window.location.pathname.split('/').pop();

        // get the rulename using the function from the flux-data-extractor.js
        console.log('we are about to get the rule name');
        const ruleName = getRuleName(documentId);
        // console error if we could not get the rule name
        if (!ruleName) {
            console.error('Could not get the rule name');
        } else {
            console.log('Rule name: ' + ruleName);
        }

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
                            <td><a href="#" style="color: #0F66A9; font-weight: bold; text-decoration: none;">${ruleName}</a></td>
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