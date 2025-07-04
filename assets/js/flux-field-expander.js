export class FluxFieldExpander {
    static init() {
        this.setupFieldValueClickHandlers();
        this.createPencilIcons();
    }

    static setupFieldValueClickHandlers() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('field-value') && this.isInTargetData(e.target)) {
                this.toggleFieldValueWithIcon(e.target);
            }
        });
    }

    static createPencilIcons() {
        // Only create pencil icons for field values in target data section
        const targetDataContainer = document.querySelector('.target-data-content');
        if (!targetDataContainer) return;
        
        const fieldValues = targetDataContainer.querySelectorAll('.field-value');
        fieldValues.forEach(fieldValue => {
            if (!fieldValue.querySelector('.field-edit-icon')) {
                const icon = document.createElement('i');
                icon.className = 'fas fa-pencil-alt field-edit-icon';
                fieldValue.appendChild(icon);
            }
        });
    }

    static isInTargetData(element) {
        return element.closest('.target-data-content') !== null;
    }

    static async toggleFieldValueWithIcon(fieldValueElement) {
        const icon = fieldValueElement.querySelector('.field-edit-icon');
        const isExpanded = fieldValueElement.classList.contains('expanded');
        
        if (!isExpanded) {
            fieldValueElement.classList.add('expanded');
            await this.delay(200);
            icon.classList.add('show');
            icon.classList.remove('hide');

            console.log('icon class list in if', icon.classList);
        } else {
            icon.classList.remove('show');
            icon.classList.add('hide');
            await this.delay(200);
            fieldValueElement.classList.remove('expanded');
            setTimeout(() => icon.classList.remove('hide'), 400);

            console.log('icon class list in else', icon.classList);
        }
    }

    static delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    static toggleFieldValue(fieldValueElement) {
        fieldValueElement.classList.toggle('expanded');
    }
}