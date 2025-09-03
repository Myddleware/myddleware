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
            console.error('❌ Error in field comparison:', error);
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
            console.warn('⚠️ History or target fields not found for comparison');
            return;
        }

        console.log('🔍 Comparing fields - History:', historyFields.size, 'Target:', targetFields.size);

        // Compare each history field with corresponding target field
        historyFields.forEach((historyValue, fieldName) => {
            const targetValue = targetFields.get(fieldName);
            
            if (targetValue !== undefined && historyValue !== targetValue) {
                // Values don't match - apply yellow border to history field
                this.highlightMismatchedField(fieldName, 'history');
                console.log('🟨 Field mismatch found:', fieldName, 'History:', historyValue, 'Target:', targetValue);
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
            console.warn(`⚠️ ${sectionType} section not found`);
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
     * Highlights a mismatched field with yellow border
     * @param {string} fieldName - Name of the field to highlight
     * @param {string} sectionType - Section type ('history', 'target', etc.)
     */
    static highlightMismatchedField(fieldName, sectionType) {
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
            console.log('📢 Field comparator received data update event');
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