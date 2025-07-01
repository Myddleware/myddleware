export class FluxFieldExpander {
    static init() {
        this.setupFieldValueClickHandlers();
    }

    static setupFieldValueClickHandlers() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('field-value')) {
                this.toggleFieldValue(e.target);
            }
        });
    }

    static toggleFieldValue(fieldValueElement) {
        fieldValueElement.classList.toggle('expanded');
    }

    static resetAllExpanded() {
        const expandedElements = document.querySelectorAll('.field-value.expanded');
        expandedElements.forEach(element => {
            element.classList.remove('expanded');
        });
    }

    static expandAllFieldValues() {
        const fieldValues = document.querySelectorAll('.field-value');
        fieldValues.forEach(element => {
            element.classList.add('expanded');
        });
    }

    static getExpandedCount() {
        return document.querySelectorAll('.field-value.expanded').length;
    }
}