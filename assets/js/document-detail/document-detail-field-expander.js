export class DocumentDetailFieldExpander {
    static init() {
        this.setupFieldClickHandlers();
        this.createPencilIcons();
        this.setupDataUpdateListener();
    }

    static setupDataUpdateListener() {
        // Listen for data updates from FluxDataSections
        document.addEventListener('fluxDataUpdated', (event) => {
            // Re-initialize pencil icons for new content
            setTimeout(() => {
                this.createPencilIcons();
            }, 100); // Small delay to ensure DOM is updated
        });
    }

    static setupFieldClickHandlers() {
        document.addEventListener('click', (e) => {
            // Handle clicks on both field labels and field values in ALL sections
            if (e.target.classList.contains('field-label') || e.target.classList.contains('field-value')) {
                this.handleFieldClick(e.target);
            }
        });
    }

    static handleFieldClick(element) {
        // Always toggle the expansion persistence
        this.toggleFieldExpansion(element);
        
        // Only show pencil icon for target field values (not labels)
        if (element.classList.contains('field-value') && this.isInTargetData(element)) {
            this.togglePencilIcon(element);
        }
    }

    static toggleFieldExpansion(element) {
        // Toggle the expanded class for persistence
        element.classList.toggle('expanded');
        
        const isExpanded = element.classList.contains('expanded');
        const elementType = element.classList.contains('field-label') ? 'label' : 'value';
        const section = this.getDataSection(element);
        
    }

    static async togglePencilIcon(fieldValueElement) {
        try {
            const icon = fieldValueElement.querySelector('.field-edit-icon');
            
            if (!icon) {
                return;
            }
            
            const isExpanded = fieldValueElement.classList.contains('expanded');
            
            if (isExpanded) {
                await this.delay(200);
                icon.classList.add('show');
                icon.classList.remove('hide');
            } else {
                icon.classList.remove('show');
                icon.classList.add('hide');
                await this.delay(200);
                setTimeout(() => icon.classList.remove('hide'), 400);
            }
        } catch (error) {
            console.error(' Error toggling pencil icon:', error);
        }
    }

    static createPencilIcons() {
        
        // Only create pencil icons for field values in target data section
        const targetDataContainer = document.querySelector('.target-data-content');
        if (!targetDataContainer) {
            return;
        }
        
        const fieldValues = targetDataContainer.querySelectorAll('.field-value');
        
        fieldValues.forEach((fieldValue, index) => {
            try {
                if (!fieldValue.querySelector('.field-edit-icon')) {
                    // const icon = document.createElement('i');
                    // icon.className = 'fas fa-pencil-alt field-edit-icon';
                    // fieldValue.appendChild(icon);
                } else {
                }
            } catch (error) {
                console.error(` Error adding pencil icon to field ${index + 1}:`, error);
            }
        });
    }

    static isInTargetData(element) {
        return element.closest('.target-data-content') !== null;
    }

    static getDataSection(element) {
        if (element.closest('.source-data-content')) return 'source';
        if (element.closest('.target-data-content')) return 'target';
        if (element.closest('.history-data-content')) return 'history';
        return 'unknown';
    }

    static delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}