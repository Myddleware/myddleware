/**
 * DocumentDetailDirectLinks - Handles direct link generation and display
 * Creates direct links to the actual record in source/target solutions
 */
export class DocumentDetailDirectLinks {
    
    /**
     * Determines if a direct link should be created
     * @param {string} directLinkUrl - The direct link URL from API
     * @returns {boolean} True if direct link should be shown
     */
    static shouldCreateDirectLink(directLinkUrl) {
        return directLinkUrl && typeof directLinkUrl === 'string' && directLinkUrl.trim().length > 0;
    }

    /**
     * Creates a direct link element
     * @param {string} directLinkUrl - The direct link URL
     * @param {string} idValue - The actual ID value to display
     * @param {string} sectionType - Section type ('source' or 'target') for styling
     * @returns {string} HTML string for the direct link
     */
    static createDirectLink(directLinkUrl, idValue, sectionType = 'source') {
        const sanitizedUrl = this.sanitizeUrl(directLinkUrl);
        const sanitizedIdValue = this.sanitizeString(idValue);

        return `
            <div class="field-row direct-link-document" data-field-type="${sectionType}">
                <div class="field-label" title="id">
                    <a href="${sanitizedUrl}" target="_blank" rel="noopener noreferrer" 
                       class="direct-link-modern" 
                       title="Open record in external application">
                        id
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-box-arrow-up-right ms-1" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z"/>
                            <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z"/>
                        </svg>
                    </a>
                </div>
                <div class="field-separator"></div>
                <div class="field-value" id="field-${sectionType}-direct-link" title="${sanitizedIdValue}" data-full-value="${sanitizedIdValue}">
                    ${sanitizedIdValue}
                </div>
            </div>
        `;
    }

    /**
     * Wraps field value with direct link if needed, otherwise returns original
     * @param {string} fieldName - Name of the field
     * @param {any} fieldValue - Value of the field  
     * @param {string} directLinkUrl - Direct link URL if available
     * @param {string} sectionType - Section type ('source' or 'target') for styling
     * @returns {string} HTML string - either original value or with direct link
     */
    static wrapWithDirectLinkIfNeeded(fieldName, fieldValue, directLinkUrl, sectionType = 'source') {
        // Only add direct link for the 'id' field
        if (fieldName && fieldName.toLowerCase() === 'id' && this.shouldCreateDirectLink(directLinkUrl)) {
            return this.createDirectLink(directLinkUrl, fieldValue, sectionType);
        }
        return this.sanitizeString(fieldValue);
    }

    /**
     * Adds direct link section to the data section if direct link is available
     * @param {string} sectionSelector - CSS selector for the section (e.g., '.source-data', '.target-data')
     * @param {string} directLinkUrl - The direct link URL
     * @param {string} idValue - The ID value to display
     */
    static addDirectLinkToSection(sectionSelector, directLinkUrl, idValue = 'id') {
        if (!this.shouldCreateDirectLink(directLinkUrl)) {
            return;
        }

        const section = document.querySelector(sectionSelector);
        if (!section) {
            return;
        }

        // Look for the content body div within the section
        let contentBody;
        if (sectionSelector.includes('source')) {
            contentBody = section.querySelector('.source-data-content-body');
        } else if (sectionSelector.includes('target')) {
            contentBody = section.querySelector('.target-data-content-body');
        }

        if (!contentBody) {
            return;
        }

        // Look for existing direct link and remove it
        const existingDirectLink = contentBody.querySelector('.direct-link-document');
        if (existingDirectLink) {
            existingDirectLink.remove();
        }

        // Determine section type from selector
        const sectionType = sectionSelector.includes('source') ? 'source' : 'target';

        // Also remove any regular ID field that might have been added for non-SuiteCRM solutions
        // since we're about to add a direct link which replaces the need for a regular ID field
        const existingRegularIdField = contentBody.querySelector(`#field-${sectionType}-id`);
        if (existingRegularIdField && existingRegularIdField.closest('.field-row')) {
            existingRegularIdField.closest('.field-row').remove();
        }

        // Create and add the direct link at the beginning of the content body
        const directLinkHtml = this.createDirectLink(directLinkUrl, idValue, sectionType);
        
        // Insert the direct link at the beginning of the content body
        contentBody.insertAdjacentHTML('afterbegin', directLinkHtml);
        
    }

    /**
     * Updates all sections with their respective direct links
     * @param {Object} documentData - Document data from API containing direct links
     */
    static updateAllDirectLinks(documentData) {
        try {
            // Add source direct link
            if (documentData.source_direct_link) {
                const sourceId = documentData.source_id || 'id';
                this.addDirectLinkToSection('.source-data', documentData.source_direct_link, sourceId);
            } else {
                this.addDirectLinkToSection('.source-data', 'empty-id', documentData.source_id);
            }


            
            // Add target direct link  
            if (documentData.target_direct_link) {
                const targetId = documentData.target_id || 'id';
                this.addDirectLinkToSection('.target-data', documentData.target_direct_link, targetId);
            } else {
                this.addDirectLinkToSection('.target-data', 'empty-id', documentData.target_id);
            }
            
            
        } catch (error) {
            console.error(' Error updating direct links:', error);
        }
    }

    /**
     * Sanitizes URL for safe use in href attribute
     * @param {string} url - URL to sanitize
     * @returns {string} Sanitized URL
     */
    static sanitizeUrl(url) {
        if (!url) return '';
        
        // Basic URL validation - ensure it starts with http:// or https://
        const urlString = String(url).trim();
        if (!urlString.match(/^https?:\/\//)) {
            return '#'; // Return safe fallback
        }
        
        return urlString;
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
}