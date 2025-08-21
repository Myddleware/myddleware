/**
 * DocumentDetailButtons - Handles conditional button display logic
 * Implements the same conditional logic as the original Twig template
 */
export class DocumentDetailButtons {
    
    /**
     * Generates the button HTML based on document status and permissions
     * @param {Object} documentData - Document data from API
     * @param {Object} permissions - User permissions object
     * @returns {string} HTML string for buttons
     */
    static generateButtonsHTML(documentData = null, permissions = {}) {
        // If no data yet, show placeholder buttons (they'll be updated later)
        if (!documentData) {
            return `
                <div class="flex-row" id="flux-button-container">
                    <span id="buttons-loading" class="text-muted">Loading buttons...</span>
                </div>
            `;
        }

        const buttons = [];
        const globalStatus = documentData.global_status?.toLowerCase() || '';
        const hasJobLock = documentData.job_lock || false;
        const readRecordBtn = documentData.read_record_btn || false;
        const isSuperAdmin = permissions.is_super_admin || false;
        const documentId = documentData.id || '';

        // Get base URL for button links
        const baseUrl = this.getBaseUrl();

        // 1. Reload/Rerun button (Success) - Show if not cancelled/closed OR if super admin
        if ((globalStatus !== 'cancel' && globalStatus !== 'close') || isSuperAdmin) {
            buttons.push(`
                <a href="${baseUrl}/rule/flux/rerun/${documentId}">
                    <button type="button" class="btn btn-success" data-bs-toggle="tooltip" 
                            data-bs-placement="top" title="Reload the synchronization of this document">
                        Reload
                    </button>
                </a>
            `);
        }

        // 2. Cancel button (Warning) - Show if not cancelled/closed OR if super admin
        if ((globalStatus !== 'cancel' && globalStatus !== 'close') || isSuperAdmin) {
            buttons.push(`
                <a class="btn_action_loading" href="${baseUrl}/rule/flux/cancel/${documentId}">
                    <button type="button" class="btn btn-warning" data-bs-toggle="tooltip" 
                            data-bs-placement="top" title="Cancel execution of this document">
                        Cancel
                    </button>
                </a>
            `);
        }

        // 3. Read Record button (Primary) - Show if read_record_btn flag is true OR if super admin
        if (readRecordBtn || isSuperAdmin) {
            buttons.push(`
                <a class="btn_action_loading hover-button" href="${baseUrl}/rule/flux/readrecord/${documentId}">
                    <button type="button" class="btn btn-primary" data-bs-toggle="tooltip" 
                            data-bs-placement="top" title="Re-execute the processing of this document">
                        Run the same record
                    </button>
                </a>
            `);
        }

        // 4. Unlock button (Danger) - Show if document has job lock
        if (hasJobLock) {
            buttons.push(`
                <a class="btn_action_loading" href="${baseUrl}/rule/document/unlock/${documentId}" title="Unlock document">
                    <button type="button" class="btn btn-danger">
                        Unlock
                    </button>
                </a>
            `);
        }

        // If no buttons should be shown, return empty container
        if (buttons.length === 0) {
            return `
                <div class="flex-row" id="flux-button-container">
                    <!-- No actions available for this document -->
                </div>
            `;
        }

        // Return the container with all applicable buttons
        return `
            <div class="flex-row" id="flux-button-container">
                ${buttons.join('\n                ')}
            </div>
        `;
    }

    /**
     * Updates the button container with new button HTML
     * @param {Object} documentData - Document data from API
     * @param {Object} permissions - User permissions object
     */
    static updateButtons(documentData, permissions = {}) {
        const buttonContainer = document.getElementById('flux-button-container');
        if (!buttonContainer) {
            console.warn('⚠️ Button container not found in DOM');
            return;
        }

        try {
            const newButtonsHTML = this.generateButtonsHTML(documentData, permissions);
            buttonContainer.outerHTML = newButtonsHTML;
            
            console.log('✅ Document buttons updated based on status:', documentData.global_status);
            
            // Initialize tooltips for new buttons if Bootstrap is available
            this.initializeTooltips();
            
        } catch (error) {
            console.error('❌ Error updating document buttons:', error);
        }
    }

    /**
     * Gets the base URL for button links
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
     * Initializes Bootstrap tooltips for buttons if available
     */
    static initializeTooltips() {
        try {
            // Check if Bootstrap tooltips are available
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                tooltipElements.forEach(element => {
                    new bootstrap.Tooltip(element);
                });
                console.log('✅ Button tooltips initialized');
            } else if (typeof $ !== 'undefined' && $.fn.tooltip) {
                // Fallback to jQuery tooltips if available
                $('[data-bs-toggle="tooltip"]').tooltip();
                console.log('✅ Button tooltips initialized (jQuery fallback)');
            }
        } catch (error) {
            console.warn('⚠️ Could not initialize tooltips:', error.message);
        }
    }

    /**
     * Determines if user has specific permissions
     * This should be enhanced based on your actual permission system
     * @param {Object} userPermissions - User permissions from API/session
     * @returns {Object} Processed permissions object
     */
    static processPermissions(userPermissions = {}) {
        return {
            is_super_admin: userPermissions.role === 'ROLE_SUPER_ADMIN' || 
                           userPermissions.roles?.includes('ROLE_SUPER_ADMIN') || 
                           false
        };
    }

    /**
     * Logs button visibility decisions for debugging
     * @param {Object} documentData - Document data
     * @param {Object} permissions - Permissions data
     */
    static debugButtonLogic(documentData, permissions) {
        if (!documentData) return;
        
        const globalStatus = documentData.global_status?.toLowerCase() || '';
        
        console.group('🔧 Document Button Logic Debug');
        console.log('📋 Document ID:', documentData.id);
        console.log('📊 Global Status:', globalStatus);
        console.log('🔒 Job Lock:', documentData.job_lock || false);
        console.log('📖 Read Record Button:', documentData.read_record_btn || false);
        console.log('👑 Is Super Admin:', permissions.is_super_admin || false);
        
        console.log('🚩 Button Conditions:');
        console.log('  - Reload:', (globalStatus !== 'cancel' && globalStatus !== 'close') || permissions.is_super_admin);
        console.log('  - Cancel:', (globalStatus !== 'cancel' && globalStatus !== 'close') || permissions.is_super_admin);
        console.log('  - Read Record:', documentData.read_record_btn || permissions.is_super_admin);
        console.log('  - Unlock:', documentData.job_lock || false);
        
        console.groupEnd();
    }
}