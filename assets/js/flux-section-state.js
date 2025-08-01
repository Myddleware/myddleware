console.log('flux-section-state.js loaded');

export class FluxSectionState {
    static STORAGE_KEY_PREFIX = 'flux_sections_';
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
    static setupPagination(sectionClass, sectionName, rows) {
        const state = this.getState();
        const sectionState = state[sectionName];
        const pageCount = Math.ceil(rows.length / this.PAGE_SIZE);

        if (pageCount <= 1) return; // No pagination needed

        // Create pagination controls
        const controls = document.createElement('div');
        controls.className = 'pagination-controls';
        
        for (let i = 1; i <= pageCount; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = 'pagination-btn';
            
            if (i === sectionState.currentPage) {
                btn.classList.add('active');
            }

            btn.addEventListener('click', () => {
                this.showPage(sectionClass, sectionName, i, rows);
                this.updatePaginationButtons(controls, i);
                this.updateSectionState(sectionName, { currentPage: i });
            });

            controls.appendChild(btn);
        }

        // Add controls to section
        const section = document.querySelector(`.${sectionClass}`);
        if (section) {
            section.appendChild(controls);
            
            // Show initial page
            this.showPage(sectionClass, sectionName, sectionState.currentPage, rows);
        }
    }

    /**
     * Show specific page for a section
     */
    static showPage(sectionClass, sectionName, pageNumber, rows) {
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
    static setupCollapsible(sectionClass, sectionName) {
        const section = document.querySelector(`.${sectionClass}`);
        if (!section) return;

        const header = section.querySelector(`.${sectionName}-header`);
        const content = section.querySelector(`.${sectionName}-content`);
        const toggleBtn = section.querySelector(`.${sectionName}-toggle-btn`);

        if (!header || !content || !toggleBtn) return;

        const state = this.getState();
        const sectionState = state[sectionName];

        // Set initial state
        if (sectionState.isExpanded) {
            content.style.display = '';
            toggleBtn.textContent = '-';
            toggleBtn.setAttribute('aria-expanded', 'true');
        } else {
            content.style.display = 'none';
            toggleBtn.textContent = '+';
            toggleBtn.setAttribute('aria-expanded', 'false');
        }

        // Add click handler
        toggleBtn.addEventListener('click', () => {
            const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
            const newExpandedState = !isExpanded;

            content.style.display = newExpandedState ? '' : 'none';
            toggleBtn.textContent = newExpandedState ? '-' : '+';
            toggleBtn.setAttribute('aria-expanded', newExpandedState.toString());

            this.updateSectionState(sectionName, { isExpanded: newExpandedState });
        });
    }

    /**
     * Initialize all sections with state management
     */
    static initializeSections(sectionsData) {
        // Documents History
        this.setupCollapsible('custom-section', 'custom');
        this.setupPagination('custom-section', 'documentsHistory', sectionsData.documentsHistory || []);

        // Parent Documents  
        this.setupCollapsible('parent-documents-section', 'parent-documents');
        this.setupPagination('parent-documents-section', 'parentDocuments', sectionsData.parentDocuments || []);

        // Child Documents
        this.setupCollapsible('child-documents-section', 'child-documents');
        this.setupPagination('child-documents-section', 'childDocuments', sectionsData.childDocuments || []);

        console.log('✅ All sections initialized with independent state management');
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
                        console.log(`🧹 Cleaned up expired state: ${key}`);
                    }
                } catch (error) {
                    // Invalid JSON, remove it
                    localStorage.removeItem(key);
                }
            }
        });
    }
}