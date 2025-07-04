export class FluxFieldExpander {
    static init() {
        console.log('🔧 FluxFieldExpander initializing...');
        this.setupFieldValueClickHandlers();
        this.createPencilIcons();
        this.setupDataUpdateListener();
    }

    static setupDataUpdateListener() {
        // Listen for data updates from FluxDataSections
        document.addEventListener('fluxDataUpdated', (event) => {
            console.log('🔄 FluxFieldExpander received data update notification:', event.detail);
            // Re-initialize pencil icons for new content
            setTimeout(() => {
                this.createPencilIcons();
            }, 100); // Small delay to ensure DOM is updated
        });
    }

    static setupFieldValueClickHandlers() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('field-value') && this.isInTargetData(e.target)) {
                this.toggleFieldValueWithIcon(e.target);
            }
        });
    }

    static createPencilIcons() {
        console.log('✏️ Creating pencil icons for target data fields...');
        
        // Only create pencil icons for field values in target data section
        const targetDataContainer = document.querySelector('.target-data-content');
        if (!targetDataContainer) {
            console.warn('⚠️ Target data container not found, skipping pencil icon creation');
            return;
        }
        
        const fieldValues = targetDataContainer.querySelectorAll('.field-value');
        console.log(`📝 Found ${fieldValues.length} field values in target section`);
        
        fieldValues.forEach((fieldValue, index) => {
            try {
                if (!fieldValue.querySelector('.field-edit-icon')) {
                    const icon = document.createElement('i');
                    icon.className = 'fas fa-pencil-alt field-edit-icon';
                    fieldValue.appendChild(icon);
                    console.log(`✅ Added pencil icon to field ${index + 1}`);
                } else {
                    console.log(`📌 Field ${index + 1} already has pencil icon`);
                }
            } catch (error) {
                console.error(`❌ Error adding pencil icon to field ${index + 1}:`, error);
            }
        });
    }

    static isInTargetData(element) {
        return element.closest('.target-data-content') !== null;
    }

    static async toggleFieldValueWithIcon(fieldValueElement) {
        try {
            const icon = fieldValueElement.querySelector('.field-edit-icon');
            
            if (!icon) {
                console.warn('⚠️ No edit icon found for field value');
                return;
            }
            
            const isExpanded = fieldValueElement.classList.contains('expanded');
            
            if (!isExpanded) {
                fieldValueElement.classList.add('expanded');
                await this.delay(200);
                icon.classList.add('show');
                icon.classList.remove('hide');

                console.log('📖 Field expanded, icon class list:', icon.classList.toString());
            } else {
                icon.classList.remove('show');
                icon.classList.add('hide');
                await this.delay(200);
                fieldValueElement.classList.remove('expanded');
                setTimeout(() => icon.classList.remove('hide'), 400);

                console.log('📕 Field collapsed, icon class list:', icon.classList.toString());
            }
        } catch (error) {
            console.error('❌ Error toggling field value:', error);
        }
    }

    static delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    static toggleFieldValue(fieldValueElement) {
        fieldValueElement.classList.toggle('expanded');
    }
}