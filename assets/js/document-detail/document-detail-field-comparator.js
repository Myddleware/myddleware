export class DocumentDetailFieldComparator {
    /**
     * Compares history section field values with target section field values
     * and applies yellow border styling to mismatched fields
     */
    static compareAndHighlightFields() {
        try {
            // Wait a bit for the DOM to be fully rendered
            setTimeout(() => {
                this.performFieldComparison();
            }, 100);
        } catch (error) {
            console.error(' Error in field comparison:', error);
        }
    }

    /**
     * Performs the actual field comparison logic
     */
    static performFieldComparison() {
        // Get all history section field values
        const historyFields = this.getFieldsFromSection('history');
        
        // Get all target section field values
        const targetFields = this.getFieldsFromSection('target');
        
        if (!historyFields || !targetFields) {
            return;
        }


        // Compare each history field with corresponding target field
        historyFields.forEach((historyValue, fieldName) => {
            const targetValue = targetFields.get(fieldName);
            
            if (targetValue !== undefined && historyValue !== targetValue) {
                // Only compare if both values have meaningful content (at least 1 visible character)
                if (this.hasVisibleContent(historyValue) && this.hasVisibleContent(targetValue)) {
                    // Normalize whitespace and compare - ignore leading/trailing spaces
                    const normalizedHistory = String(historyValue || '').trim();
                    const normalizedTarget = String(targetValue || '').trim();
                    
                    // Only highlight if the normalized values are actually different
                    if (normalizedHistory !== normalizedTarget) {
                        // Apply yellow border highlighting for visible content differences
                        this.highlightMismatchedField(fieldName, 'history', 'general');
                    }
                }
            }
        });
    }

    /**
     * Extracts field values from a section
     * @param {string} sectionType - 'history', 'target', or 'source'
     * @returns {Map} Map of field names to their values
     */
    static getFieldsFromSection(sectionType) {
        const fields = new Map();
        
        // Find all field rows in the specified section
        const sectionElement = document.querySelector(`.${sectionType}-data-content-body`);
        
        if (!sectionElement) {
            return null;
        }

        const fieldRows = sectionElement.querySelectorAll('.field-row');
        
        fieldRows.forEach(row => {
            const labelElement = row.querySelector('.field-label');
            const valueElement = row.querySelector('.field-value');
            
            if (labelElement && valueElement) {
                const fieldName = labelElement.textContent.trim();
                const fieldValue = valueElement.getAttribute('data-full-value') || valueElement.textContent.trim();
                
                fields.set(fieldName, fieldValue);
            }
        });

        return fields;
    }

    /**
     * Checks if a value has visible content (at least 1 non-whitespace character)
     * @param {string} value - Value to check
     * @returns {boolean} True if value has meaningful visible content
     */
    static hasVisibleContent(value) {
        const str = String(value || '');
        return str.trim().length >= 1;
    }

    /**
     * Highlights a mismatched field with appropriate styling
     * @param {string} fieldName - Name of the field to highlight
     * @param {string} sectionType - Section type ('history', 'target', etc.)
     * @param {string} mismatchType - Type of mismatch ('whitespace', 'empty', 'substitution', 'general')
     */
    static highlightMismatchedField(fieldName, sectionType, mismatchType = 'general') {
        const sectionElement = document.querySelector(`.${sectionType}-data-content-body`);
        
        if (!sectionElement) {
            return;
        }

        // Find the specific field row
        const fieldRows = sectionElement.querySelectorAll('.field-row');
        
        fieldRows.forEach(row => {
            const labelElement = row.querySelector('.field-label');
            
            if (labelElement && labelElement.textContent.trim() === fieldName) {
                const valueElement = row.querySelector('.field-value');
                if (valueElement) {
                    // Clear any existing mismatch classes and add simple yellow border
                    valueElement.classList.remove('field-value-mismatch', 'field-value-mismatch-whitespace', 'field-value-mismatch-empty', 'field-value-mismatch-substitution');
                    valueElement.classList.add('field-value-mismatch');
                }
            }
        });
    }

    /**
     * Removes all field mismatch highlighting
     */
    static clearHighlighting() {
        const mismatchedFields = document.querySelectorAll('.field-value-mismatch');
        mismatchedFields.forEach(field => {
            field.classList.remove('field-value-mismatch');
        });
    }

    /**
     * Initializes field comparison when data sections are updated
     */
    static init() {
        // Listen for data section updates
        document.addEventListener('fluxDataUpdated', () => {
            this.compareAndHighlightFields();
        });

        // Also compare on page load if sections already exist
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => this.compareAndHighlightFields(), 1000);
            });
        } else {
            setTimeout(() => this.compareAndHighlightFields(), 1000);
        }
    }
}