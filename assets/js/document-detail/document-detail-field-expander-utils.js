export class DocumentDetailFieldExpanderUtils {
    static resetAllExpanded() {
        const expandedElements = document.querySelectorAll('.field-value.expanded');
        const icons = document.querySelectorAll('.field-edit-icon.show');
        
        expandedElements.forEach(element => {
            element.classList.remove('expanded');
        });
        
        icons.forEach(icon => {
            icon.classList.remove('show');
        });
    }

    static expandAllFieldValues() {
        const fieldValues = document.querySelectorAll('.field-value');
        fieldValues.forEach(element => {
            element.classList.add('expanded');
            const icon = element.querySelector('.field-edit-icon');
            if (icon) {
                icon.classList.add('show');
            }
        });
    }

    static getExpandedCount() {
        return document.querySelectorAll('.field-value.expanded').length;
    }

    static hideAllIcons() {
        const icons = document.querySelectorAll('.field-edit-icon.show');
        icons.forEach(icon => {
            icon.classList.remove('show');
            icon.classList.add('hide');
        });
    }
} 