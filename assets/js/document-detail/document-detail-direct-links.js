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
     * @param {string} linkText - Text to display for the link (default: 'Direct link')
     * @returns {string} HTML string for the direct link
     */
    static createDirectLink(directLinkUrl, linkText = 'Direct link') {
        const sanitizedUrl = this.sanitizeUrl(directLinkUrl);
        const sanitizedText = this.sanitizeString(linkText);

        return `
            <div class="center direct-link-document" style="margin-bottom: 1rem;">
                <u>
                    <a href="${sanitizedUrl}" target="_blank" rel="noopener noreferrer" 
                       class="direct-link-modern" 
                       title="Open record in external application">
                        ${sanitizedText}
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-box-arrow-up-right ms-1" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z"/>
                            <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z"/>
                        </svg>
                    </a>
                </u>
            </div>
        `;
    }

    /**
     * Wraps field value with direct link if needed, otherwise returns original
     * @param {string} fieldName - Name of the field
     * @param {any} fieldValue - Value of the field  
     * @param {string} directLinkUrl - Direct link URL if available
     * @returns {string} HTML string - either original value or with direct link
     */
    static wrapWithDirectLinkIfNeeded(fieldName, fieldValue, directLinkUrl) {
        // Only add direct link for the 'id' field
        if (fieldName && fieldName.toLowerCase() === 'id' && this.shouldCreateDirectLink(directLinkUrl)) {
            return this.createDirectLink(directLinkUrl, 'Direct link');
        }
        return this.sanitizeString(fieldValue);
    }

    /**
     * Adds direct link section to the data section if direct link is available
     * @param {string} sectionSelector - CSS selector for the section (e.g., '.source-data', '.target-data')
     * @param {string} directLinkUrl - The direct link URL
     */
    static addDirectLinkToSection(sectionSelector, directLinkUrl) {
        if (!this.shouldCreateDirectLink(directLinkUrl)) {
            console.log(`🔗 No direct link available for section: ${sectionSelector}`);
            return;
        }

        const section = document.querySelector(sectionSelector);
        if (!section) {
            console.warn(`⚠️ Section not found: ${sectionSelector}`);
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
            console.warn(`⚠️ Content body not found in section: ${sectionSelector}`);
            return;
        }

        // Look for existing direct link and remove it
        const existingDirectLink = contentBody.querySelector('.direct-link-document');
        if (existingDirectLink) {
            existingDirectLink.remove();
        }

        // Create and add the direct link at the beginning of the content body
        const directLinkHtml = this.createDirectLink(directLinkUrl);
        
        // Insert the direct link at the beginning of the content body
        contentBody.insertAdjacentHTML('afterbegin', directLinkHtml);
        
        console.log(`✅ Added direct link to content body in section: ${sectionSelector}`);
    }

    /**
     * Updates all sections with their respective direct links
     * @param {Object} documentData - Document data from API containing direct links
     */
    static updateAllDirectLinks(documentData) {
        try {
            console.log('🔗 Updating direct links for all sections');
            
            // Add source direct link
            if (documentData.source_direct_link) {
                this.addDirectLinkToSection('.source-data', documentData.source_direct_link);
                console.log('✅ Source direct link added:', documentData.source_direct_link);
            }
            
            // Add target direct link  
            if (documentData.target_direct_link) {
                this.addDirectLinkToSection('.target-data', documentData.target_direct_link);
                console.log('✅ Target direct link added:', documentData.target_direct_link);
            }
            
            console.log('✅ Direct links update completed');
            
        } catch (error) {
            console.error('❌ Error updating direct links:', error);
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
            console.warn('⚠️ Direct link URL does not start with http:// or https://:', urlString);
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