export class FluxFieldExpander {
    static init() {
        this.setupFieldValueClickHandlers();
        this.createPencilIcons();
    }

    static setupFieldValueClickHandlers() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('field-value')) {
                this.toggleFieldValueWithIcon(e.target);
            }
        });
    }

    static createPencilIcons() {
        const fieldValues = document.querySelectorAll('.field-value');
        fieldValues.forEach(fieldValue => {
            if (!fieldValue.querySelector('.field-edit-icon')) {
                const icon = document.createElement('i');
                icon.className = 'fas fa-pencil-alt field-edit-icon';
                fieldValue.appendChild(icon);
            }
        });
    }

    static async toggleFieldValueWithIcon(fieldValueElement) {
        const icon = fieldValueElement.querySelector('.field-edit-icon');
        const isExpanded = fieldValueElement.classList.contains('expanded');
        
        if (!isExpanded) {
            fieldValueElement.classList.add('expanded');
            await this.delay(200);
            icon.classList.add('show');
        } else {
            icon.classList.remove('show');
            icon.classList.add('hide');
            await this.delay(200);
            fieldValueElement.classList.remove('expanded');
            setTimeout(() => icon.classList.remove('hide'), 400);
        }
    }

    static delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    static toggleFieldValue(fieldValueElement) {
        fieldValueElement.classList.toggle('expanded');
    }
}