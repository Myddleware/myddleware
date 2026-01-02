import { getBaseUrl } from './document-detail-url-utils.js';

/**
 * Document Detail Target Editor
 * Handles inline editing of target data fields with pencil/check/cross icons
 */
export class DocumentDetailTargetEditor {
    constructor() {
        this.currentlyEditingField = null;
        this.originalValue = null;
        this.lastCleanupTime = 0; // Throttle cleanup calls
        this.activeNotifications = new Map(); // Track active notifications
        this.documentGlobalStatus = null; // Store document global status
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.checkUserPermissions();
    }

    /**
     * Check if current user has admin permissions for editing
     */
    async checkUserPermissions() {
        try {
            // Build proper URL
            const baseUrl = getBaseUrl();
            const permissionsUrl = `${baseUrl}/rule/api/flux/user-permissions`;
            
            const response = await fetch(permissionsUrl);
            const data = await response.json();
            
            
            if (!data.success || !data.permissions.is_admin) {
                return false;
            }
            
            return true;
        } catch (error) {
            console.error(' Error checking user permissions:', error);
            return false;
        }
    }

    /**
     * Setup event listeners for the target editor
     */
    setupEventListeners() {
        // Listen for clicks on pencil icons in target section
        document.addEventListener('click', (e) => {
            if (e.target.closest('.edit-icon.pencil-icon')) {
                const fieldElement = e.target.closest('.field-row').querySelector('.field-value');
                this.startEditingField(fieldElement);
            }
        });

        // Listen for clicks on check/cross icons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.edit-icon.check-icon')) {
                this.saveFieldChanges();
            } else if (e.target.closest('.edit-icon.cross-icon')) {
                this.cancelFieldChanges();
            }
        });

        // Listen for Enter key to save, Escape to cancel
        document.addEventListener('keydown', (e) => {
            if (this.currentlyEditingField) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.saveFieldChanges();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    this.cancelFieldChanges();
                }
            }
        });

        // Listen for new content being loaded (when API data updates the target section)
        document.addEventListener('fluxDataUpdated', () => {
            setTimeout(() => {
                this.addEditCapabilityToNewFields();
            }, 200);
        });

        // Also listen for when DOM content is initially loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    this.addEditCapabilityToNewFields();
                }, 1000);
            });
        } else {
            setTimeout(() => {
                this.addEditCapabilityToNewFields();
            }, 1000);
        }
    }

    /**
     * Update the document global status
     * @param {string} globalStatus - The document's global status
     */
    setDocumentGlobalStatus(globalStatus) {
        this.documentGlobalStatus = globalStatus;
        
        // Re-evaluate existing pencil icons based on new status
        this.reevaluatePencilIcons();
    }

    /**
     * Re-evaluate and add/remove pencil icons based on current global status
     */
    reevaluatePencilIcons() {
        const targetSection = document.querySelector('.target-data');
        if (!targetSection) {
            return;
        }

        const fieldRows = targetSection.querySelectorAll('.field-row[data-field-type="target"]');
        const isErrorStatus = this.documentGlobalStatus && this.documentGlobalStatus.toLowerCase() === 'error';
        
        fieldRows.forEach(fieldRow => {
            const fieldValue = fieldRow.querySelector('.field-value');
            const editContainer = fieldValue ? fieldValue.querySelector('.edit-icons-container') : null;
            
            if (!fieldValue) return;

            if (isErrorStatus) {
                // Add pencil icon if it doesn't exist and status is Error
                if (!editContainer && !fieldValue.hasAttribute('data-edit-enabled')) {
                    this.addEditIconToField(fieldValue);
                    fieldValue.setAttribute('data-edit-enabled', 'true');
                }
            } else {
                // Remove pencil icon if it exists and status is not Error
                if (editContainer) {
                    editContainer.remove();
                }
                fieldValue.removeAttribute('data-edit-enabled');
            }
        });
        
    }

    /**
     * Add editing capability to newly loaded target fields
     */
    addEditCapabilityToNewFields() {
        const targetSection = document.querySelector('.target-data');
        if (!targetSection) {
            return;
        }

        const fieldRows = targetSection.querySelectorAll('.field-row[data-field-type="target"]');
        
        let addedCount = 0;
        fieldRows.forEach(fieldRow => {
            const fieldValue = fieldRow.querySelector('.field-value');
            
            // More thorough check to prevent duplicate icons
            if (fieldValue && 
                !fieldValue.querySelector('.edit-icons-container') && 
                !fieldValue.hasAttribute('data-edit-enabled') &&
                !fieldValue.textContent.includes('✏️')) {
                
                this.addEditIconToField(fieldValue);
                
                // Mark as having edit capability added
                fieldValue.setAttribute('data-edit-enabled', 'true');
                addedCount++;
            }
        });
        
        
        // Clean up any fields that might have gotten corrupted with emoji data (throttled)
        const now = Date.now();
        if (now - this.lastCleanupTime > 1000) { // Only cleanup once per second
            this.cleanupCorruptedFields();
            this.lastCleanupTime = now;
        }
    }

    /**
     * Clean up any fields that have emoji icons mixed into their content
     */
    cleanupCorruptedFields() {
        const targetSection = document.querySelector('.target-data');
        if (!targetSection) return;

        const fieldValues = targetSection.querySelectorAll('.field-value');
        let cleanedCount = 0;
        
        fieldValues.forEach(fieldValue => {
            const originalText = fieldValue.textContent;
            const cleanText = originalText.replace(/[✏️]/g, '').trim();
            
            if (originalText !== cleanText) {
                // Update the text content, preserving any edit icons
                const editContainer = fieldValue.querySelector('.edit-icons-container');
                fieldValue.textContent = cleanText;
                
                // Re-add the edit container if it existed
                if (editContainer) {
                    fieldValue.appendChild(editContainer);
                }
                
                // Update attributes
                fieldValue.setAttribute('title', cleanText);
                fieldValue.setAttribute('data-full-value', cleanText);
                
                cleanedCount++;
            }
        });
        
        if (cleanedCount > 0) {
        }
    }

    /**
     * Add pencil edit icon to a field
     * @param {HTMLElement} fieldElement - The field value element
     */
    addEditIconToField(fieldElement) {
        // Only show pencil icon if document global status is "Error"
        if (this.documentGlobalStatus && this.documentGlobalStatus.toLowerCase() !== 'error') {
            return;
        }
        
        
        
        // Ensure the field element has relative positioning for absolute positioned icons
        const computedStyle = window.getComputedStyle(fieldElement);
        if (computedStyle.position === 'static') {
            fieldElement.style.position = 'relative';
        }
        
        // Create pencil icon container
        const iconContainer = document.createElement('div');
        iconContainer.className = 'edit-icons-container';
        iconContainer.innerHTML = `
            <span class="edit-icon pencil-icon" title="Edit field">
                <i class="fa fa-pen" aria-hidden="true"></i>
            </span>
        `;
        
        fieldElement.appendChild(iconContainer);
    }

    /**
     * Start editing a field by replacing content with input
     * @param {HTMLElement} fieldElement - The field value element to edit
     */
    async startEditingField(fieldElement) {
        // Check permissions first
        const hasPermissions = await this.checkUserPermissions();
        if (!hasPermissions) {
            this.showNotification('You do not have permission to edit fields', 'error');
            return;
        }

        // Prevent multiple fields being edited at once
        if (this.currentlyEditingField && this.currentlyEditingField !== fieldElement) {
            this.cancelFieldChanges();
        }

        this.currentlyEditingField = fieldElement;
        // Clean the original value to remove any emojis that might have been mixed in
        this.originalValue = fieldElement.textContent.replace(/[✏️]/g, '').trim();

        // Get field information
        const fieldRow = fieldElement.closest('.field-row');
        const fieldLabel = fieldRow.querySelector('.field-label').textContent;
        

        // Create input element
        const input = document.createElement('input');
        input.type = 'text';
        input.value = this.originalValue;
        input.className = 'field-edit-input';
        
        // Replace field content with input
        fieldElement.innerHTML = '';
        fieldElement.appendChild(input);
        
        // Create check/cross icons
        const iconsContainer = document.createElement('div');
        iconsContainer.className = 'edit-icons-container editing';
        iconsContainer.innerHTML = `
            <span class="edit-icon check-icon" title="Save changes">
                <i class="fa fa-check text-success"></i>
            </span>
            <span class="edit-icon cross-icon" title="Cancel editing">
                <i class="fa fa-times text-danger"></i>
            </span>
        `;
        
        fieldElement.appendChild(iconsContainer);
        
        // Focus input and select all text
        input.focus();
        input.select();
        
        // Add orange color to indicate editing
        fieldElement.style.backgroundColor = '#ffc107';
        fieldElement.style.border = '2px solid #fd7e14';
        fieldElement.style.borderRadius = '4px';
        fieldElement.style.padding = '4px';
    }

    /**
     * Save changes made to the current field
     */
    async saveFieldChanges() {
        if (!this.currentlyEditingField) return;

        const input = this.currentlyEditingField.querySelector('.field-edit-input');
        const newValue = input.value.trim();
        
        // Get field information
        const fieldRow = this.currentlyEditingField.closest('.field-row');
        const fieldLabel = fieldRow.querySelector('.field-label').textContent;
        

        // Show loading state and store the promise
        const savingNotification = this.showNotification('Saving changes...', 'info');
        
        try {
            // Get document ID from URL
            const documentId = window.location.pathname.split('/').pop();
            
            // Build proper URL for the update request
            const baseUrl = getBaseUrl();
            const updateUrl = `${baseUrl}/rule/flux/update-field`;
            
            // Make AJAX request to save the field
            const response = await fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    documentId: documentId,
                    fieldName: fieldLabel,
                    fieldValue: newValue
                })
            });

            const result = await response.json();
            
            if (result.success) {
                
                // Store reference to the field element before clearing editing state
                const savedFieldElement = this.currentlyEditingField;
                
                // Update the field with the new value
                this.finishEditing(newValue);
                
                // Wait for the "Saving changes..." notification to disappear before showing success
                savingNotification.then(() => {
                    this.showNotification('Field updated successfully', 'info');
                });
                
                // Keep orange color briefly to show it was updated
                setTimeout(() => {
                    if (savedFieldElement) {
                        savedFieldElement.style.backgroundColor = '';
                        savedFieldElement.style.border = '';
                        savedFieldElement.style.borderRadius = '';
                        savedFieldElement.style.padding = '';
                    }
                }, 2000);
                
            } else {
                console.error(' Failed to save field:', result.error);
                this.showNotification(result.error || 'Failed to save field', 'error');
                this.cancelFieldChanges();
            }
            
        } catch (error) {
            console.error(' Error saving field:', error);
            this.showNotification('Network error while saving field', 'error');
            this.cancelFieldChanges();
        }
    }

    /**
     * Cancel field editing and restore original value
     */
    cancelFieldChanges() {
        if (!this.currentlyEditingField) return;

        this.finishEditing(this.originalValue);
    }

    /**
     * Finish editing and restore normal field display
     * @param {string} value - Value to display in the field
     */
    finishEditing(value) {
        if (!this.currentlyEditingField) return;

        // Clean the value to make sure no emojis or icons are mixed in
        const cleanValue = value.replace(/[✏️]/g, '').trim();
        
        // Store reference to the field before clearing editing state
        const fieldElement = this.currentlyEditingField;
        
        // Restore field display with clean value
        fieldElement.innerHTML = cleanValue;
        
        // Update the data attributes with clean value
        fieldElement.setAttribute('title', cleanValue);
        fieldElement.setAttribute('data-full-value', cleanValue);
        
        // Reset the data-edit-enabled flag so we can add the icon again
        fieldElement.removeAttribute('data-edit-enabled');
        
        // Always add pencil icon back after finishing editing
        this.addEditIconToField(fieldElement);
        
        // Reset styles
        fieldElement.style.backgroundColor = '';
        fieldElement.style.border = '';
        fieldElement.style.borderRadius = '';
        fieldElement.style.padding = '';
        
        // Clear editing state
        this.currentlyEditingField = null;
        this.originalValue = null;
        
    }

    /**
     * Show notification to user
     * @param {string} message - Message to show
     * @param {string} type - Type of notification (success, error, info)
     * @returns {Promise} Promise that resolves when the notification disappears
     */
    showNotification(message, type = 'info') {
        return new Promise((resolve) => {
            // Create notification element
            const notification = document.createElement('div');
            const notificationId = Date.now() + '_' + Math.random();
            notification.className = `field-edit-notification ${type}`;
            notification.textContent = message;
            notification.dataset.notificationId = notificationId;
            
            // Style the notification
            Object.assign(notification.style, {
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '12px 20px',
                borderRadius: '4px',
                color: 'white',
                fontWeight: 'bold',
                zIndex: '9999',
                boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)'
            });
            
            // Set background color based on type
            switch (type) {
                case 'success':
                    notification.style.backgroundColor = '#28a745';
                    break;
                case 'error':
                    notification.style.backgroundColor = '#dc3545';
                    break;
                case 'info':
                default:
                    notification.style.backgroundColor = '#17a2b8';
                    break;
            }
            
            // Add to document
            document.body.appendChild(notification);
            
            // Track the notification
            this.activeNotifications.set(notificationId, notification);
            
            // Remove after 3 seconds and resolve the promise
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
                this.activeNotifications.delete(notificationId);
                resolve(); // Resolve the promise when notification disappears
            }, 3000);
            
        });
    }
}