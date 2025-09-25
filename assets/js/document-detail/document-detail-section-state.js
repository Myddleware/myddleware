// console.log('flux-section-state.js loaded');

export class DocumentDetailSectionState {
    static STORAGE_KEY_PREFIX = 'document_detail_sections_';
    static EXPIRY_TIME = 60 * 60 * 1000; // 1 hour in milliseconds
    static PAGE_SIZE = 5;

    /**
     * Get document ID from current URL
     */
    static getDocumentId() {
        return window.location.pathname.split('/').pop();
    }

    /**
     * Get storage key for current document
     */
    static getStorageKey() {
        return `${this.STORAGE_KEY_PREFIX}${this.getDocumentId()}`;
    }

    /**
     * Get current state for all sections
     */
    static getState() {
        const storageKey = this.getStorageKey();
        const stored = localStorage.getItem(storageKey);
        
        if (!stored) {
            return this.getDefaultState();
        }

        try {
            const parsed = JSON.parse(stored);
            
            // Check if expired
            if (Date.now() > parsed.expiresAt) {
                localStorage.removeItem(storageKey);
                return this.getDefaultState();
            }

            return parsed.data;
        } catch (error) {
            console.error('Error parsing stored state:', error);
            localStorage.removeItem(storageKey);
            return this.getDefaultState();
        }
    }

    /**
     * Get default state for all sections
     */
    static getDefaultState() {
        return {
            documentsHistory: {
                currentPage: 1,
                isExpanded: true
            },
            parentDocuments: {
                currentPage: 1,
                isExpanded: true
            },
            childDocuments: {
                currentPage: 1,
                isExpanded: true
            },
            workflowLogs: {
                currentPage: 1,
                isExpanded: true
            },
            logs: {
                currentPage: 1,
                isExpanded: true
            }
        };
    }

    /**
     * Save state to localStorage
     */
    static saveState(state) {
        const storageKey = this.getStorageKey();
        const dataToStore = {
            data: state,
            expiresAt: Date.now() + this.EXPIRY_TIME,
            lastUpdated: Date.now()
        };

        try {
            localStorage.setItem(storageKey, JSON.stringify(dataToStore));
        } catch (error) {
            console.error('Error saving state to localStorage:', error);
        }
    }

    /**
     * Update specific section state
     */
    static updateSectionState(sectionName, updates) {
        const currentState = this.getState();
        currentState[sectionName] = { ...currentState[sectionName], ...updates };
        this.saveState(currentState);
        return currentState;
    }

    /**
     * Setup pagination for a section
     */
    static setupPagination(sectionClass, stateKey, rows) {
        const state = this.getState();
        const sectionState = state[stateKey];
        
        if (!sectionState) {
            console.error(`Section state not found for: ${stateKey}. Available states:`, Object.keys(state));
            // Create missing state with defaults
            const updatedState = this.updateSectionState(stateKey, {
                currentPage: 1,
                isExpanded: true
            });
            const newSectionState = updatedState[stateKey];
            if (!newSectionState) {
                console.error(`Failed to create state for: ${stateKey}`);
                return;
            }
            // Use the newly created state
            var currentPage = newSectionState.currentPage;
        } else {
            var currentPage = sectionState.currentPage;
        }
        
        const pageCount = Math.ceil(rows.length / this.PAGE_SIZE);

        if (pageCount <= 1) {
// console.log(`ℹ️ No pagination needed for ${stateKey} (${rows.length} rows)`);
            return; // No pagination needed
        }

        // Create pagination controls
        const controls = document.createElement('div');
        controls.className = 'pagination-controls';
        
        for (let i = 1; i <= pageCount; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = 'pagination-btn';
            
            if (i === currentPage) {
                btn.classList.add('active');
            }

            btn.addEventListener('click', () => {
                this.showPage(sectionClass, stateKey, i, rows);
                this.updatePaginationButtons(controls, i);
                this.updateSectionState(stateKey, { currentPage: i });
// console.log(`✅ Updated ${stateKey} to page:`, i);
            });

            controls.appendChild(btn);
        }

        // Add controls to section
        const section = document.querySelector(`.${sectionClass}`);
        if (section) {
            section.appendChild(controls);
            
            // Show initial page
            this.showPage(sectionClass, stateKey, currentPage, rows);
// console.log(`✅ Pagination setup complete for: ${stateKey} (${pageCount} pages)`);
        } else {
            console.warn(`Section not found for pagination: .${sectionClass}`);
        }
    }

    /**
     * Show specific page for a section
     */
    static showPage(sectionClass, stateKey, pageNumber, rows) {
        const startIndex = (pageNumber - 1) * this.PAGE_SIZE;
        const endIndex = startIndex + this.PAGE_SIZE;
        
        const tableRows = document.querySelectorAll(`.${sectionClass} tbody tr`);
        
        tableRows.forEach((row, index) => {
            if (index >= startIndex && index < endIndex) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        // After changing page visibility, recheck links for hover animations
        setTimeout(() => {
            if (window.documentDetailInstance && window.documentDetailInstance.multilineLinkHandler) {
                window.documentDetailInstance.multilineLinkHandler.recheckLinks();
// console.log(`🔗 Rechecked links after showing page ${pageNumber} for ${stateKey}`);
            }
        }, 50);

// console.log(`✅ Showing page ${pageNumber} for ${stateKey} (rows ${startIndex + 1}-${Math.min(endIndex, rows.length)})`);
    }

    /**
     * Update pagination button states
     */
    static updatePaginationButtons(controls, activePage) {
        const buttons = controls.querySelectorAll('.pagination-btn');
        buttons.forEach((btn, index) => {
            btn.classList.toggle('active', index + 1 === activePage);
        });
    }

    /**
     * Setup collapse/expand functionality for a section
     */
    static setupCollapsible(sectionClass, sectionName, stateKey) {
        const section = document.querySelector(`.${sectionClass}`);
        if (!section) {
            console.warn(`Section not found: .${sectionClass}`);
            return;
        }

        // Remove existing event listeners to avoid duplicates
        const existingToggleBtn = section.querySelector(`.${sectionName}-toggle-btn`);
        if (existingToggleBtn && existingToggleBtn._collapseHandler) {
            existingToggleBtn.removeEventListener('click', existingToggleBtn._collapseHandler);
        }

        const header = section.querySelector(`.${sectionName}-header`);
        let content;
        
        // Handle special case for Documents History section
        if (sectionName === 'custom') {
            content = section.querySelector('.custom-content');
        } else {
            content = section.querySelector(`.${sectionName}-content`);
        }
        let toggleBtn;
        
        // Handle special case for Documents History section
        if (sectionName === 'custom') {
            toggleBtn = section.querySelector('.toggle-btn');
        } else {
            toggleBtn = section.querySelector(`.${sectionName}-toggle-btn`);
        }

        if (!header || !content || !toggleBtn) {
            console.warn(`Elements not found for section: ${sectionName}`);
            console.warn('Header:', !!header, 'Content:', !!content, 'ToggleBtn:', !!toggleBtn);
            return;
        }

        const state = this.getState();
        const sectionState = state[stateKey];

        if (!sectionState) {
            console.error(`Section state not found for: ${stateKey}. Available states:`, Object.keys(state));
            // Create missing state with defaults
            const updatedState = this.updateSectionState(stateKey, {
                currentPage: 1,
                isExpanded: true
            });
            const newSectionState = updatedState[stateKey];
            if (!newSectionState) {
                console.error(`Failed to create state for: ${stateKey}`);
                return;
            }
            // Use the newly created state
            var isExpanded = newSectionState.isExpanded;
        } else {
            var isExpanded = sectionState.isExpanded;
        }

        // Set initial state
        if (isExpanded) {
            content.style.display = '';
            toggleBtn.textContent = '-';
            toggleBtn.setAttribute('aria-expanded', 'true');
        } else {
            content.style.display = 'none';
            toggleBtn.textContent = '+';
            toggleBtn.setAttribute('aria-expanded', 'false');
        }

        // Add click handler
        const collapseHandler = () => {
            const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
            const newExpandedState = !isExpanded;

            content.style.display = newExpandedState ? '' : 'none';
            toggleBtn.textContent = newExpandedState ? '-' : '+';
            toggleBtn.setAttribute('aria-expanded', newExpandedState.toString());

            this.updateSectionState(stateKey, { isExpanded: newExpandedState });
// console.log(`✅ Updated ${stateKey} expand state:`, newExpandedState);
        };
        
        // Store handler reference for cleanup
        toggleBtn._collapseHandler = collapseHandler;
        toggleBtn.addEventListener('click', collapseHandler);

// console.log(`✅ Collapsible setup complete for: ${stateKey}`);
    }

    /**
     * Initialize all sections with state management
     */
    static initializeSections(sectionsData) {
// console.log('🚀 Initializing sections with state management...');
        
        // Documents History
        this.setupCollapsible('custom-section', 'custom', 'documentsHistory');
        this.setupPagination('custom-section', 'documentsHistory', sectionsData.documentsHistory || []);

        // Parent Documents  
        this.setupCollapsible('parent-documents-section', 'parent-documents', 'parentDocuments');
        this.setupPagination('parent-documents-section', 'parentDocuments', sectionsData.parentDocuments || []);

        // Child Documents
        this.setupCollapsible('child-documents-section', 'child-documents', 'childDocuments');
        this.setupPagination('child-documents-section', 'childDocuments', sectionsData.childDocuments || []);

        // Workflow Logs
        this.setupCollapsible('workflow-logs-section', 'workflow-logs', 'workflowLogs');
        this.setupPagination('workflow-logs-section', 'workflowLogs', sectionsData.workflowLogs || []);

        // Logs
        this.setupCollapsible('logs-section', 'logs', 'logs');
        this.setupPagination('logs-section', 'logs', sectionsData.logs || []);

// console.log('✅ All sections initialized with independent state management');
    }

    /**
     * Clean up expired entries from localStorage
     */
    static cleanupExpiredEntries() {
        const keys = Object.keys(localStorage);
        const currentTime = Date.now();

        keys.forEach(key => {
            if (key.startsWith(this.STORAGE_KEY_PREFIX)) {
                try {
                    const stored = JSON.parse(localStorage.getItem(key));
                    if (stored.expiresAt && currentTime > stored.expiresAt) {
                        localStorage.removeItem(key);
// console.log(`🧹 Cleaned up expired state: ${key}`);
                    }
                } catch (error) {
                    // Invalid JSON, remove it
                    localStorage.removeItem(key);
                }
            }
        });
    }
}