// console.log('flux-section-state.js loaded');

export class DocumentDetailSectionState {
    static STORAGE_KEY_PREFIX = 'document_detail_sections_';
    static EXPIRY_TIME = 60 * 60 * 1000; // 1 hour in milliseconds
    static PAGE_SIZE = 5;

    static _historyAnchoredOnce = false;

    static normalizeStateKey(key) {
        if (!key) return key;
        const map = {
            'documents-history': 'documentsHistory',
            'parent-documents': 'parentDocuments',
            'child-documents': 'childDocuments',
            'workflow-logs': 'workflowLogs',
            'logs': 'logs',
            'documentsHistory': 'documentsHistory',
            'parentDocuments': 'parentDocuments',
            'childDocuments': 'childDocuments',
            'workflowLogs': 'workflowLogs'
        };
        const normalized = map[key] || key;
        return normalized;
    }

    /**
     * Get document ID from current URL
     */
    static getDocumentId() {
        const id = window.location.pathname.split('/').pop();
        return id;
    }
    
    /**
     * Get storage key for current document
     */
    static getStorageKey() {
        const k = `${this.STORAGE_KEY_PREFIX}${this.getDocumentId()}`;
        return k;
    }

    static getDefaultState() {
        return {
            documentsHistory: {
                currentPage: 1,
                isExpanded: true
            },
            parentDocuments:  {
                currentPage: 1,
                isExpanded: true
            },
            childDocuments:   {
                currentPage: 1,
                isExpanded: true
            },
            workflowLogs:     {
                currentPage: 1,
                isExpanded: true
            },
            logs:             {
                currentPage: 1,
                isExpanded: true
            },
        };
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

            return parsed.data || this.getDefaultState();
        } catch (error) {
            console.error('Error parsing stored state:', error);
            localStorage.removeItem(storageKey);
            return this.getDefaultState();
        }
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
        const key = this.normalizeStateKey(sectionName);
        const currentState = this.getState();
        const next = { ...(currentState[key] || { currentPage: 1, isExpanded: true }), ...updates };
        currentState[key] = next;
        this.saveState(currentState);
        return currentState;
    }

    static sectionEl(sectionClass) {
        const el = document.querySelector(`.${sectionClass}`) || null;
        return el;
    }
    static getContentEl(sectionClass) {
        const section = this.sectionEl(sectionClass);
        if (!section) return null;
        const content = section.querySelector(
            '.custom-content, .parent-documents-content, .child-documents-content, .workflow-logs-content, .logs-content'
        );
        return content || null;
    }
    static tableEl(sectionClass) {
        const s = this.sectionEl(sectionClass);
        const t = s ? s.querySelector('table') : null;
        return t;
    }
    static tableRows(sectionClass) {
        const t = this.tableEl(sectionClass);
        return t ? Array.from(t.querySelectorAll('tbody tr')) : [];
    }

    static waitForEl(selector, { tries = 40, delay = 50 } = {}) {
        return new Promise(resolve => {
            const tick = (left) => {
                const el = document.querySelector(selector);
                if (el) return resolve(el);
                if (left <= 0) return resolve(null);
                setTimeout(() => tick(left - 1), delay);
            };
            tick(tries);
        });
    }

    static waitForRows(sectionClass, { tries = 20, delay = 50 } = {}) {
        return new Promise(resolve => {
            const tick = (left) => {
                const rows = this.tableRows(sectionClass);
                if (rows.length > 0) return resolve(rows);
                if (left <= 0) return resolve([]);
                setTimeout(() => tick(left - 1), delay);
            };
            tick(tries);
        });
    }

    static getHistoryPageForCurrentDoc() {
        const rows = this.tableRows('custom-section');
        const currentId = this.getDocumentId();
        let idx = -1;
        rows.forEach((row, i) => {
            const a = row.querySelector('a.doc-id');
            const txt = a ? a.textContent.trim() : '';
            if (txt === currentId) idx = i;
        });
        if (idx < 0) return null;
        const page = Math.floor(idx / this.PAGE_SIZE) + 1;
        return page;
    }
   
    /**
     * Setup pagination for a section
     */
    static async setupPagination(sectionClass, stateKey, rows) {
        const normalizedKey = this.normalizeStateKey(stateKey);
        const domRowsReady = await this.waitForRows(sectionClass);
        const state = this.getState();
        const sectionState = state[normalizedKey] || { currentPage: 1, isExpanded: true };
        const domRows = domRowsReady;
        const totalRows = domRows.length || (Array.isArray(rows) ? rows.length : 0);
        const pageCount = Math.max(1, Math.ceil(totalRows / this.PAGE_SIZE));

        let desiredPage = sectionState.currentPage || 1;

        if (normalizedKey === 'documentsHistory' && !this._historyAnchoredOnce) {
            const p = this.getHistoryPageForCurrentDoc();
            if (p) {
                desiredPage = p;
                this._historyAnchoredOnce = true;
                this.updateSectionState(normalizedKey, { currentPage: desiredPage });
            }
        }

        const currentPage = Math.max(1, Math.min(desiredPage, pageCount));
        const content = this.getContentEl(sectionClass);
        if (!content) return;
        this.showPage(sectionClass, normalizedKey, currentPage);

        const old = content.querySelector('.pagination-controls');
        if (old) old.remove();

        if (pageCount <= 1) return;
        // Create pagination controls
        const controls = document.createElement('nav');
        controls.className = 'pagination-controls';
        controls.setAttribute('aria-label', 'Pagination');
        
        // Add page input field
        const list = document.createElement('ul');
        list.className = 'pagination-list';
        controls.appendChild(list);

        const addBtn = (label, page, { disabled = false, active = false, aria = '' } = {}) => {
            const li = document.createElement('li');
            li.className = 'pagination-item';
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pagination-btn';
            if (active) btn.classList.add('active');
            btn.disabled = !!disabled;
            btn.dataset.page = String(page);
            if (aria) btn.setAttribute('aria-label', aria);
            btn.textContent = label;
            li.appendChild(btn);
            list.appendChild(li);
        };
        const addEllipsis = () => {
            const li = document.createElement('li');
            li.className = 'pagination-ellipsis';
            li.setAttribute('aria-hidden', 'true');
            li.textContent = '…';
            list.appendChild(li);
        };

        addBtn('«', 1, { disabled: currentPage === 1, aria: 'Première page' });
        addBtn('‹', Math.max(1, currentPage - 1), { disabled: currentPage === 1, aria: 'Page précédente' });

        const windowSize = 1;
        const startWindow = Math.max(2, currentPage - windowSize);
        const endWindow = Math.min(pageCount - 1, currentPage + windowSize);

        addBtn('1', 1, { active: currentPage === 1 });
        if (startWindow > 2) addEllipsis();
        for (let p = startWindow; p <= endWindow; p++) addBtn(String(p), p, { active: p === currentPage });
        if (endWindow < pageCount - 1) addEllipsis();
        if (pageCount > 1) addBtn(String(pageCount), pageCount, { active: currentPage === pageCount });

        addBtn('›', Math.min(pageCount, currentPage + 1), { disabled: currentPage === pageCount, aria: 'Page suivante' });
        addBtn('»', pageCount, { disabled: currentPage === pageCount, aria: 'Dernière page' });

        list.addEventListener('click', (e) => {
            const btn = e.target?.closest?.('.pagination-btn');
            if (!btn) return;
            const nextPage = parseInt(btn.dataset.page, 10);
            if (Number.isNaN(nextPage)) return;

            if (normalizedKey === 'documentsHistory') this._historyAnchoredOnce = true;

            this.showPage(sectionClass, normalizedKey, nextPage);
            this.updateSectionState(normalizedKey, { currentPage: nextPage });

            // re-render
            this.setupPagination(sectionClass, normalizedKey, rows);
        });
        content.appendChild(controls);
    }

    /**
     * Show specific page for a section
     */
    static showPage(sectionClass, stateKey, pageNumber) {
        const PAGE = this.PAGE_SIZE;

        const apply = () => {
            const trs = this.tableRows(sectionClass);
            const pageCount = Math.max(1, Math.ceil(trs.length / PAGE));
            const safePage = Math.max(1, Math.min(pageNumber, pageCount));
            const startIndex = (safePage - 1) * PAGE;
            const endIndex = startIndex + PAGE;

            trs.forEach((row, i) => {
                row.style.display = (i >= startIndex && i < endIndex) ? '' : 'none';
            });

            // After changing page visibility, recheck links for hover animations
            setTimeout(() => {
                if (window.documentDetailInstance?.multilineLinkHandler) {
                    window.documentDetailInstance.multilineLinkHandler.recheckLinks();
// console.log(`🔗 Rechecked links after showing page ${pageNumber} for ${stateKey}`);
                }
            }, 50);
        };

        const trs = this.tableRows(sectionClass);
        if (trs.length === 0) {
            this.waitForRows(sectionClass).then(apply);
        } else {
            apply();
        }
    }
    
    /**
     * Setup collapse/expand functionality for a section
     */
    static setupCollapsible(sectionClass, sectionName, stateKey) {
        const normalizedKey = this.normalizeStateKey(stateKey);
        const section = this.sectionEl(sectionClass);
        if (!section) return;

        const content = sectionName === 'custom'
            ? section.querySelector('.custom-content')
            : section.querySelector(`.${sectionName}-content`);
        const toggleBtn = sectionName === 'custom'
            ? section.querySelector('.toggle-btn')
            : section.querySelector(`.${sectionName}-toggle-btn`);

        if (!content || !toggleBtn) return;

        const st = this.getState();
        let isExpanded = !!(st[normalizedKey]?.isExpanded ?? true);

        content.style.display = isExpanded ? '' : 'none';
        toggleBtn.textContent = isExpanded ? '-' : '+';
        toggleBtn.setAttribute('aria-expanded', String(isExpanded));

        if (toggleBtn._collapseHandler) {
            toggleBtn.removeEventListener('click', toggleBtn._collapseHandler);
        }
        const collapseHandler = () => {
            const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
            const newExpandedState = !isExpanded;

            content.style.display = newExpandedState ? '' : 'none';
            toggleBtn.textContent = newExpandedState ? '-' : '+';
            toggleBtn.setAttribute('aria-expanded', String(newExpandedState));

            this.updateSectionState(normalizedKey, { isExpanded: newExpandedState });
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
    static async initializeSections(sectionsData) {
        this._historyAnchoredOnce = false;
        await this.waitForEl('.custom-section');

        // Documents History
        this.setupCollapsible('custom-section', 'custom', 'documentsHistory');
        this.setupPagination('custom-section', 'documentsHistory', sectionsData?.documentsHistory || []);

         // Parent Documents  
        this.setupCollapsible('parent-documents-section', 'parent-documents', 'parentDocuments');
        this.setupPagination('parent-documents-section', 'parentDocuments', sectionsData?.parentDocuments || []);

        // Child Documents
        this.setupCollapsible('child-documents-section', 'child-documents', 'childDocuments');
        this.setupPagination('child-documents-section', 'childDocuments', sectionsData?.childDocuments || []);

        // Workflow Logs
        this.setupCollapsible('workflow-logs-section', 'workflow-logs', 'workflowLogs');
        this.setupPagination('workflow-logs-section', 'workflowLogs', sectionsData?.workflowLogs || []);

         // Logs
        this.setupCollapsible('logs-section', 'logs', 'logs');
        this.setupPagination('logs-section', 'logs', sectionsData?.logs || []);
   
        // console.log('✅ All sections initialized with independent state management');
    }

    /**
     * Clean up expired entries from localStorage
     */
    static cleanupExpiredEntries() {
        const currentTime = Date.now();
        for (const key of Object.keys(localStorage)) {
            if (!key.startsWith(this.STORAGE_KEY_PREFIX)) continue;
            try {
                const stored = JSON.parse(localStorage.getItem(key));
                if (stored?.expiresAt && currentTime > stored.expiresAt) {
                    localStorage.removeItem(key);
// console.log(`🧹 Cleaned up expired state: ${key}`);
                }
            } catch (error) {
                    // Invalid JSON, remove it
                localStorage.removeItem(key);
            }
        }
    }
}