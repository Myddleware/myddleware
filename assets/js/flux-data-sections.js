export class FluxDataSections {
    static generateDataSections(fullpathSource, fullpathTarget, fullpathHistory) {
        return `
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
                            ${this.generateFields()}
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
                            ${this.generateFields()}
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
                            ${this.generateFields()}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    static generateFields() {
        const fields = [
            { label: 'Email', value: 'luciefaure@myddleware.com' },
            { label: 'Firstname', value: 'Lucie' },
            { label: 'Lastname', value: 'FAURE' },
            { label: 'Address', value: '1234 rue de la fleur 16800 Rive' },
            { label: 'CourseID', value: 'XID80073' }
        ];
        
        return fields.map(field => `
            <div class="field-row">
                <div class="field-label">${field.label}</div>
                <div class="field-separator"></div>
                <div class="field-value" title="${field.value}">${field.value}</div>
            </div>
        `).join('');
    }
}