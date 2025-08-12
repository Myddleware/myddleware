/**
 * DocumentDetailLookupLinks - Handles automatic lookup link generation
 * Creates links to document list filtered by source ID for fields that appear to be IDs
 */
export class DocumentDetailLookupLinks {
    
    /**
     * Determines if a field should have a lookup link
     * @param {string} fieldName - Name of the field
     * @param {any} fieldValue - Value of the field
     * @returns {boolean} True if field should have lookup link
     */
    static shouldCreateLookupLink(fieldName, fieldValue) {
        if (!fieldName || !fieldValue) {
            return false;
        }

        const fieldNameLower = String(fieldName).toLowerCase();
        const fieldValueStr = String(fieldValue).trim();

        // Both conditions must be met:
        // 1. Field name contains 'id' 
        // 2. Field value length is exactly 36 characters (UUID format)
        return fieldNameLower.includes('id') && fieldValueStr.length === 36;
    }

    /**
     * Creates a lookup link element for a field value
     * @param {string} fieldValue - The field value to link
     * @param {string} fieldName - Name of the field (for CSS classes)
     * @returns {string} HTML string for the lookup link
     */
    static createLookupLink(fieldValue, fieldName) {
        const baseUrl = this.getBaseUrl();
        const linkUrl = `${baseUrl}/document/list/page-1?source_id=${encodeURIComponent(fieldValue)}`;
        const sanitizedValue = this.sanitizeString(fieldValue);
        const sanitizedFieldName = this.sanitizeString(fieldName);

        return `
            <a class="help-link all-link lookup-link-myddleware lookup-link-myddleware-${sanitizedFieldName}" 
               href="${linkUrl}" 
               title="Search documents by this id">
                ${sanitizedValue}
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-up-right" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z"/>
                    <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z"/>
                </svg>
            </a>
        `;
    }

    /**
     * Wraps a field value with a lookup link if conditions are met
     * @param {string} fieldName - Name of the field
     * @param {any} fieldValue - Value of the field
     * @returns {string} HTML string - either original value or wrapped in lookup link
     */
    static wrapWithLookupLinkIfNeeded(fieldName, fieldValue) {
        if (this.shouldCreateLookupLink(fieldName, fieldValue)) {
            return this.createLookupLink(fieldValue, fieldName);
        }
        return this.sanitizeString(fieldValue);
    }

    /**
     * Gets the base URL for creating links
     * @returns {string} Base URL
     */
    static getBaseUrl() {
        const pathParts = window.location.pathname.split('/');
        const publicIndex = pathParts.indexOf('public');
        
        if (publicIndex !== -1) {
            const baseParts = pathParts.slice(0, publicIndex + 1);
            return window.location.origin + baseParts.join('/');
        }
        
        return window.location.origin;
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