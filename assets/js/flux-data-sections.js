console.log('flux-data-sections.js loaded');

export class FluxDataSections {
    /**
     * Generates the complete data sections HTML with real data
     * @param {string} sourceImagePath - Path to source system logo
     * @param {string} targetImagePath - Path to target system logo  
     * @param {string} historyImagePath - Path to history section logo
     * @returns {string} HTML string for all data sections
     */
    static generateDataSections(sourceImagePath, targetImagePath, historyImagePath) {
        console.log('🏗️ Generating data sections with placeholder containers');
        
        try {
            return `
                <div class="data-wrapper" style="margin: 20px;">
                    ${this.generateSourceSection(sourceImagePath)}
                    ${this.generateTargetSection(targetImagePath)}
                    ${this.generateHistorySection(historyImagePath)}
                </div>
            `;
        } catch (error) {
            console.error('❌ Error generating data sections:', error);
            return this.generateErrorSection('Failed to generate data sections');
        }
    }

    /**
     * Renders a full‑width placeholder table under Source/Target/History.
     * @param {Array<Object>} rows
     *   Each row should have: docId, name, sourceId, targetId,
     *   modificationDate, type, status
     */
    static generateCustomSection(rows = []) {
        if (!rows.length) return ``;

        // build each row's <tr>…
        const body = rows
        .map(({ docId, name, sourceId, targetId, modificationDate, type, status }) => {
            // turn "Error_transformed" → "error_transformed" for class names
            const statusClass = status.toLowerCase().replace(/[^a-z0-9]+/g, `_`);

            return `
            <tr>
                <td>${docId}</td>
                <td>${name}</td>
                <td>${sourceId}</td>
                <td>${targetId}</td>
                <td>${modificationDate}</td>
                <td>${type}</td>
                <td>
                <span class="status‑badge status‑${statusClass}">
                    ${status}
                </span>
                </td>
            </tr>
            `;
        })
        .join(``);

        return `
        <div class="data-wrapper custom-section">
            <div class="custom-header">
            <h3>Documents history</h3>
            <span class="custom-count">(${rows.length})</span>
            <button class="toggle-btn" aria-expanded="true">-</button>
            </div>

            <div class="custom-content">
            <table class="custom-table">
            <thead>
                <tr>
                <th>Doc Id</th>
                <th>Name</th>
                <th>Source id</th>
                <th>Target id</th>
                <th>Modification date</th>
                <th>Type</th>
                <th>Status</th>
                </tr>
            </thead>
            <tbody>
                ${body}
            </tbody>
            </table>
        </div>
        </div>
        `;
    }

    /**
     * Generates Parent documents section
     * @param {Array<Object>} rows - Same structure as Documents history
     */
    static generateParentDocumentsSection(rows = []) {
        if (!rows.length) return ``;

        const body = rows
        .map(({ docId, name, sourceId, targetId, modificationDate, type, status }) => {
            const statusClass = status.toLowerCase().replace(/[^a-z0-9]+/g, `_`);

            return `
            <tr>
                <td>${docId}</td>
                <td>${name}</td>
                <td>${sourceId}</td>
                <td>${targetId}</td>
                <td>${modificationDate}</td>
                <td>${type}</td>
                <td>
                <span class="status‑badge status‑${statusClass}">
                    ${status}
                </span>
                </td>
            </tr>
            `;
        })
        .join(``);

        return `
        <div class="data-wrapper parent-documents-section">
            <div class="parent-documents-header">
            <h3>Parent documents</h3>
            <span class="parent-documents-count">(${rows.length})</span>
            <button class="parent-documents-toggle-btn" aria-expanded="true">-</button>
            </div>

            <div class="parent-documents-content">
            <table class="parent-documents-table">
            <thead>
                <tr>
                <th>Doc Id</th>
                <th>Name</th>
                <th>Source id</th>
                <th>Target id</th>
                <th>Modification date</th>
                <th>Type</th>
                <th>Status</th>
                </tr>
            </thead>
            <tbody>
                ${body}
            </tbody>
            </table>
        </div>
        </div>
        `;
    }

    /**
     * Generates Child documents section
     * @param {Array<Object>} rows - Same structure as Documents history
     */
    static generateChildDocumentsSection(rows = []) {
        if (!rows.length) return ``;

        const body = rows
        .map(({ docId, name, sourceId, targetId, modificationDate, type, status }) => {
            const statusClass = status.toLowerCase().replace(/[^a-z0-9]+/g, `_`);

            return `
            <tr>
                <td>${docId}</td>
                <td>${name}</td>
                <td>${sourceId}</td>
                <td>${targetId}</td>
                <td>${modificationDate}</td>
                <td>${type}</td>
                <td>
                <span class="status‑badge status‑${statusClass}">
                    ${status}
                </span>
                </td>
            </tr>
            `;
        })
        .join(``);

        return `
        <div class="data-wrapper child-documents-section">
            <div class="child-documents-header">
            <h3>Child documents</h3>
            <span class="child-documents-count">(${rows.length})</span>
            <button class="child-documents-toggle-btn" aria-expanded="true">-</button>
            </div>

            <div class="child-documents-content">
            <table class="child-documents-table">
            <thead>
                <tr>
                <th>Doc Id</th>
                <th>Name</th>
                <th>Source id</th>
                <th>Target id</th>
                <th>Modification date</th>
                <th>Type</th>
                <th>Status</th>
                </tr>
            </thead>
            <tbody>
                ${body}
            </tbody>
            </table>
        </div>
        </div>
        `;
    }

    /**
     * Generates Logs section
     * @param {Array<Object>} rows - Logs data with id, reference, job, creationDate, type, message
     */
    static generateLogsSection(rows = []) {
        if (!rows.length) return ``;

        const body = rows
        .map(({ id, reference, job, creationDate, type, message }) => {
            // Determine color based on type
            let typeColor = '#28a745'; // default green for 'S ✓'
            if (type.startsWith('W')) {
                typeColor = '#ffc107'; // yellow for 'W x' types
            } else if (type.startsWith('E')) {
                typeColor = '#dc3545'; // red for 'E' types
            }

            return `
            <tr>
                <td>${id}</td>
                <td><a href="#" class="log-reference" style="color: #0F66A9; text-decoration: none;">${reference}</a></td>
                <td>${job}</td>
                <td>${creationDate}</td>
                <td><span style="color: ${typeColor}; font-weight: bold;">${type}</span></td>
                <td>${message}</td>
            </tr>
            `;
        })
        .join(``);

        return `
        <div class="data-wrapper logs-section">
            <div class="logs-header">
            <h3>Logs</h3>
            <span class="logs-count">(${rows.length})</span>
            <button class="logs-toggle-btn" aria-expanded="true">-</button>
            </div>

            <div class="logs-content">
            <table class="logs-table">
            <thead>
                <tr>
                <th>Id</th>
                <th>Reference</th>
                <th>Job</th>
                <th>Creation date</th>
                <th>Type</th>
                <th>Message</th>
                </tr>
            </thead>
            <tbody>
                ${body}
            </tbody>
            </table>
        </div>
        </div>
        `;
    }

    /**
     * Generates the source data section HTML
     * @param {string} logoPath - Path to source logo image
     * @returns {string} HTML string for source section
     */
    static generateSourceSection(logoPath) {
        return this.generateDataSection('source', 'Source', logoPath);
    }

    /**
     * Generates the target data section HTML
     * @param {string} logoPath - Path to target logo image
     * @returns {string} HTML string for target section
     */
    static generateTargetSection(logoPath) {
        return this.generateDataSection('target', 'Target', logoPath);
    }

    /**
     * Generates the history data section HTML
     * @param {string} logoPath - Path to history logo image
     * @returns {string} HTML string for history section
     */
    static generateHistorySection(logoPath) {
        return this.generateDataSection('history', 'History', logoPath);
    }

    /**
     * Generates a generic data section template
     * @param {string} sectionType - Type of section (source, target, history)
     * @param {string} sectionTitle - Display title for the section
     * @param {string} logoPath - Path to logo image
     * @returns {string} HTML string for the section
     */
    static generateDataSection(sectionType, sectionTitle, logoPath) {
        const sectionId = `${sectionType}-data-body`;
        
        return `
            <div class="${sectionType}-data">
                <div class="${sectionType}-data-content">
                    <div class="${sectionType}-data-content-header">
                        <div class="${sectionType}-logo-container">
                            <img class="logo-small-size" src="${logoPath}" alt="${sectionTitle} Logo">
                        </div>
                        <h3>${sectionTitle}</h3>
                    </div>
                    <div class="${sectionType}-data-content-body" id="${sectionId}">
                        <div class="loading-message">Loading ${sectionTitle.toLowerCase()} data...</div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Updates source section with real data
     * @param {Object|null} sourceData - Source data object from API
     */
    static updateSourceData(sourceData) {
        console.log('📊 Updating source data section');
        this.updateDataSection('source', sourceData, 'Source');
    }

    /**
     * Updates target section with real data
     * @param {Object|null} targetData - Target data object from API
     */
    static updateTargetData(targetData) {
        console.log('🎯 Updating target data section');
        this.updateDataSection('target', targetData, 'Target');
    }

    /**
     * Updates history section with real data
     * @param {Object|null} historyData - History data object from API
     */
    static updateHistoryData(historyData) {
        console.log('📜 Updating history data section');
        this.updateDataSection('history', historyData, 'History');
    }

    /**
     * Generic method to update any data section
     * @param {string} sectionType - Type of section (source, target, history)
     * @param {Object|null} sectionData - Data object from API
     * @param {string} sectionName - Display name for logging
     */
    static updateDataSection(sectionType, sectionData, sectionName) {
        const sectionBodyId = `${sectionType}-data-body`;
        const sectionElement = document.getElementById(sectionBodyId);
        
        if (!sectionElement) {
            console.error(`❌ ${sectionName} section element not found:`, sectionBodyId);
            return;
        }

        try {
            if (!sectionData || Object.keys(sectionData).length === 0) {
                sectionElement.innerHTML = this.generateEmptyDataMessage(sectionName);
                console.warn(`⚠️ No ${sectionName.toLowerCase()} data available`);
                return;
            }

            const fieldsHtml = this.generateDataFields(sectionData, sectionType);
            sectionElement.innerHTML = fieldsHtml;
            
            // Add click handlers for field expansion
            this.addFieldClickHandlers(sectionElement);
            
            console.log(`✅ ${sectionName} data updated successfully`);
            
        } catch (error) {
            console.error(`❌ Error updating ${sectionName.toLowerCase()} data:`, error);
            sectionElement.innerHTML = this.generateErrorMessage(`Failed to load ${sectionName.toLowerCase()} data`);
        }
    }

    /**
     * Generates HTML for data fields
     * @param {Object} fieldData - Object containing field key-value pairs
     * @param {string} sectionType - Type of section for CSS classes
     * @returns {string} HTML string for all fields
     */
    static generateDataFields(fieldData, sectionType) {
        if (!fieldData || typeof fieldData !== 'object') {
            console.warn('⚠️ Invalid field data provided:', fieldData);
            return this.generateEmptyDataMessage('data');
        }

        const fieldEntries = Object.entries(fieldData);
        
        if (fieldEntries.length === 0) {
            return this.generateEmptyDataMessage('fields');
        }

        return fieldEntries
            .map(([fieldName, fieldValue]) => this.generateSingleField(fieldName, fieldValue, sectionType))
            .join('');
    }

    /**
     * Generates HTML for a single field
     * @param {string} fieldName - Name/label of the field
     * @param {any} fieldValue - Value of the field
     * @param {string} sectionType - Type of section for CSS classes
     * @returns {string} HTML string for the field
     */
    static generateSingleField(fieldName, fieldValue, sectionType) {
        const sanitizedFieldName = this.sanitizeString(fieldName);
        const sanitizedFieldValue = this.sanitizeString(fieldValue);
        const fieldId = `field-${sectionType}-${this.generateFieldId(fieldName)}`;

        return `
            <div class="field-row" data-field-type="${sectionType}">
                <div class="field-label" title="${sanitizedFieldName}">${sanitizedFieldName}</div>
                <div class="field-separator"></div>
                <div class="field-value" 
                     id="${fieldId}"
                     title="${sanitizedFieldValue}" 
                     data-full-value="${sanitizedFieldValue}">
                    ${sanitizedFieldValue}
                </div>
            </div>
        `;
    }

    /**
     * Generates a unique field ID from field name
     * @param {string} fieldName - Original field name
     * @returns {string} Sanitized field ID
     */
    static generateFieldId(fieldName) {
        return fieldName
            .toLowerCase()
            .replace(/[^a-z0-9]/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    /**
     * Sanitizes string values for safe HTML display
     * @param {any} value - Value to sanitize
     * @returns {string} Sanitized string
     */
    static sanitizeString(value) {
        if (value === null || value === undefined) {
            return '';
        }
        
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /**
     * Adds click handlers for field expansion - REMOVED: Using existing FluxFieldExpander system
     * @param {HTMLElement} sectionElement - Section container element
     */
    static addFieldClickHandlers(sectionElement) {
        // Notify the existing FluxFieldExpander system that new content is available
        this.notifyFieldExpanderOfNewContent();
    }

    /**
     * Notifies the existing FluxFieldExpander system that new content has been loaded
     */
    static notifyFieldExpanderOfNewContent() {
        // Dispatch a custom event to let FluxFieldExpander know it should re-initialize
        const event = new CustomEvent('fluxDataUpdated', {
            detail: { 
                source: 'FluxDataSections',
                timestamp: new Date().toISOString()
            }
        });
        document.dispatchEvent(event);
        console.log('📢 Notified FluxFieldExpanner of new content');
    }

    /**
     * Generates empty data message
     * @param {string} dataType - Type of data that's empty
     * @returns {string} HTML for empty message
     */
    static generateEmptyDataMessage(dataType) {
        return `
            <div class="empty-data-message">
                <p>No ${dataType} available</p>
            </div>
        `;
    }

    /**
     * Generates error message HTML
     * @param {string} errorMessage - Error message to display
     * @returns {string} HTML for error message
     */
    static generateErrorMessage(errorMessage) {
        return `
            <div class="error-data-message">
                <p style="color: #dc3545;">⚠️ ${errorMessage}</p>
            </div>
        `;
    }

    /**
     * Generates error section HTML
     * @param {string} errorMessage - Error message to display
     * @returns {string} HTML for error section
     */
    static generateErrorSection(errorMessage) {
        return `
            <div class="data-wrapper error-wrapper" style="margin: 20px;">
                <div class="error-message">
                    <p style="color: #dc3545; text-align: center; padding: 20px;">
                        ⚠️ ${errorMessage}
                    </p>
                </div>
            </div>
        `;
    }
}