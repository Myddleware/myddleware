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

        // First, return the template with placeholders
        const template = `
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
                            <td><a id="rule-link" href="#" style="color: #0F66A9; font-weight: bold; text-decoration: none;">Loading rule...</a></td>
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

        // After returning the template, load the rule data asynchronously
        setTimeout(() => {
            console.log('we are about to get the rule name and ID');
            getRuleName(documentId, function(ruleName, ruleId, error) {
                if (error) {
                    console.error('Could not get the rule name and ID:', error);
                    // Update with error state
                    const linkElement = document.getElementById('rule-link');
                    if (linkElement) {
                        linkElement.textContent = 'Error loading rule';
                        linkElement.style.color = '#dc3545';
                    }
                    return;
                }
                
                if (ruleName && ruleId) {
                    console.log('✅ Successfully retrieved rule data:');
                    console.log('Rule name:', ruleName);
                    console.log('Rule ID:', ruleId);
                    
                    // Update the DOM with the actual rule data
                    const linkElement = document.getElementById('rule-link');
                    if (linkElement) {
                        // Get the base URL for consistency
                        const pathParts = window.location.pathname.split('/');
                        const publicIndex = pathParts.indexOf('public');
                        let baseUrl = window.location.origin;
                        if (publicIndex !== -1) {
                            const baseParts = pathParts.slice(0, publicIndex + 1);
                            baseUrl = window.location.origin + baseParts.join('/');
                        }
                        
                        const ruleLink = `${baseUrl}/rule/view/${ruleId}`;
                        linkElement.href = ruleLink;
                        linkElement.textContent = ruleName;
                        console.log('✅ Updated rule link:', ruleLink);
                    }
                } else {
                    console.error('Could not get the rule name or ID - received null values');
                    const linkElement = document.getElementById('rule-link');
                    if (linkElement) {
                        linkElement.textContent = 'Rule not found';
                        linkElement.style.color = '#dc3545';
                    }
                }
            });
        }, 100); // Small delay to ensure DOM is ready

        return template;
    }
}
// THIS IS THE HARD LIMIT FOR FILE LENGTH, WE CANNOT GO ABOVE 50 LINES --------------------------------- 