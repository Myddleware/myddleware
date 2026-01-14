// ============================================================
// 1. UTILITIES (Helper functions for DOM and Selectize manipulation)
// ============================================================
const UI = {
  // Shortcut for document.getElementById
  get: (id) => document.getElementById(id),
  
  // Show or hide an element (and handle specific opacity for step-2)
  toggle: (el, show) => {
    if (!el) return;
    show ? el.classList.remove('d-none') : el.classList.add('d-none');
    if (show && el.id === 'step-2') el.style.opacity = '1'; 
  },
  
  // Resets an HTML <select> and its Selectize instance if it exists
  resetSelect: (el, placeholder = '') => {
    if (!el) return;
    el.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;
    el.disabled = true;
    
    // Selectize reset (clear cache and disable)
    if (el.selectize) {
      el.selectize.clear();
      el.selectize.clearOptions();
      el.selectize.disable();
      if (placeholder) {
          el.selectize.settings.placeholder = placeholder;
          el.selectize.updatePlaceholder();
      }
    }
  },
  
  // Re-enables a select (native + Selectize)
  enableSelect: (el) => {
    if (!el) return;
    el.disabled = false;
    if (el.selectize) el.selectize.enable();
  },

  // Sets a value (useful for editing)
  setValue: (el, value) => {
      if (!el) return;
      el.value = String(value);
      if (el.selectize) {
          el.selectize.setValue(String(value), false); 
      }
  },

  // Synchronizes native HTML options to the Selectize instance
  // Selectize does not automatically detect .innerHTML changes
  syncSelectize: (el) => {
      if (el && el.selectize) {
          const selectize = el.selectize;
          selectize.clearOptions();
          
          // Repopulate Selectize with DOM options
          Array.from(el.options).forEach(opt => {
              if (opt.value) {
                  selectize.addOption({
                      value: opt.value,
                      text: opt.text
                  });
              }
          });
          selectize.refreshOptions(false);
      }
  },

  // Wrapper for fetch that handles HTTP errors and returns text/HTML
  fetchHtml: async (url, params = {}) => {
      const query = new URLSearchParams(params).toString();
      const target = query ? `${url}?${query}` : url;
      try {
          const res = await fetch(target, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          if (!res.ok) {
              const errorMsg = await res.text();
              throw new Error(errorMsg || `HTTP Error ${res.status}`);
          }
          return await res.text();
      } catch (err) {
          throw err;
      }
    }
};

/* ============================================================
 * STEP 1 — NAME VALIDATION (Rule name verification)
 * ============================================================ */
(function () {
  const isEdit       = !!window.__EDIT_MODE__;
  const inputName    = UI.get('rulename');
  const feedback     = UI.get('rulename-feedback');
  const spinner      = UI.get('rulename-spinner');
  const step2Section = UI.get('step-2');

  if (!inputName || !feedback) return;

  let debounceTimer = null;
  let lastValueSent = '';
  let step2Shown    = false;

  const toggleSpinner = (show) => UI.toggle(spinner, show);

  // Displays validation status (green/red) under the input
  function setStatus(status, msg) {
    toggleSpinner(false);
    inputName.classList.remove('is-invalid', 'is-valid');
    feedback.className = 'form-text';
    
    if (status === 'error') {
      inputName.classList.add('is-invalid');
      feedback.classList.add('text-danger');
    } else if (status === 'success') {
      inputName.classList.add('is-valid');
      feedback.classList.add('text-success');
      revealStep2();
    }
    feedback.textContent = msg || '';
  }

  // Reveals the rest of the form (Step 2) with an animation
  function revealStep2() {
    if (step2Shown || !step2Section) return;
    step2Shown = true;
    UI.toggle(step2Section, true);
    step2Section.style.opacity = 0;
    step2Section.style.transition = 'opacity .25s ease';
    requestAnimationFrame(() => { step2Section.style.opacity = 1; });
    if (window.updateRuleNavLinks) window.updateRuleNavLinks();
  }

  // Calls the server to check if the name is unique
  async function checkUniqueness(nameVal) {
    const url = inputName.getAttribute('data-check-url');
    if (!url) return setStatus('error', 'Validation URL missing.');

    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams({ name: nameVal })
      });
      const text = await res.text();
      let existsFlag;
      try { existsFlag = JSON.parse(text); } catch { existsFlag = text; }

      if (existsFlag === 0 || existsFlag === '0') {
        setStatus('success', window.transRuleNameAvailable || 'Name is available.');
      } else {
        setStatus('error', window.transRuleNameTaken || 'This name is already taken.');
      }
    } catch {
      setStatus('error', window.transRuleNameNetworkErr || 'Network error.');
    }
  }

  if (isEdit) return;

  // Validation on Enter key press
  inputName.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const v = inputName.value.trim();
      if (v.length < 3) return setStatus('error', window.transRuleNameTooShort || 'Min 3 chars.');
      toggleSpinner(true);
      lastValueSent = v;
      checkUniqueness(v);
    }
  });

  // Validation on typing (with Debounce to avoid spamming the server)
  inputName.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    setStatus('neutral');
    const v = inputName.value.trim();
    if (!v) { toggleSpinner(false); return; }
    toggleSpinner(true);

    if (v.length < 3) {
      debounceTimer = setTimeout(() => {
        setStatus('error', window.transRuleNameTooShort || 'Min 3 chars.');
      }, 300);
      return;
    }

    if (v === lastValueSent && inputName.classList.contains('is-valid')) {
      toggleSpinner(false);
      revealStep2();
      return;
    }

    debounceTimer = setTimeout(() => {
      lastValueSent = v;
      checkUniqueness(v);
    }, 400);
  });
})();

/* ============================================================
 * STEP 2 + 3 + 4 + 5 (CORE LOGIC & LOADERS)
 * Managing cascading dropdowns (Solution -> Connector -> Module)
 * ============================================================ */
(function () {
  const step2 = UI.get('step-2');
  if (!step2) return;

  const PATHS = {
    connectors: step2.getAttribute('data-path-connectors'),
    modules: step2.getAttribute('data-path-module')
  };

  // References to DOM elements for Source (src) and Target (tgt)
  const EL = {
    src: { sol: UI.get('source-solution'), conn: UI.get('source-connector'), mod: UI.get('source-module'), spin: UI.get('source-connector-spinner'), modSpin: UI.get('source-module-spinner'), feed: UI.get('source-connector-feedback') },
    tgt: { sol: UI.get('target-solution'), conn: UI.get('target-connector'), mod: UI.get('target-module'), spin: UI.get('target-connector-spinner'), modSpin: UI.get('target-module-spinner'), feed: UI.get('target-connector-feedback') },
    step3: UI.get('step-3'),
    step4: UI.get('step-4'),
    step5: UI.get('step-5'),
    step4Body: UI.get('step-4-body'),
    paramsContainer: UI.get('step-3-params-container')
  };

  const step3ParamsPath = EL.step3 ? EL.step3.getAttribute('data-path-params') : null;
  let filtersLoaded = false;
  async function loadSelectData(type, url, params, targetSelect, spinner, feedbackEl) {
    UI.resetSelect(targetSelect, 'Loading...');
    if (feedbackEl) {
        feedbackEl.textContent = '';
        feedbackEl.classList.remove('text-danger', 'text-success');
    }
    if (!url) return;

    try {
      UI.toggle(spinner, true);
      const htmlParams = new URLSearchParams(params).toString();
      const response = await fetch(`${url}?${htmlParams}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      
      if (!response.ok) {
          const errorMsg = await response.text(); 
          throw new Error(errorMsg || `Error ${response.status}`);
      }
      const html = await response.text();
      
      // Update native HTML
      targetSelect.innerHTML = '';
      targetSelect.appendChild(new Option('', '', true, true));
      
      if (html) {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        Array.from(temp.querySelectorAll('option')).forEach(opt => targetSelect.appendChild(opt));
      }

      if (targetSelect.selectize) {
          // On force le placeholder à revenir à la normale
          targetSelect.selectize.settings.placeholder = 'Search...';
          targetSelect.selectize.updatePlaceholder();
      }

      // synchronization with Selectize
      UI.syncSelectize(targetSelect);
      UI.enableSelect(targetSelect);

    } catch (e) {
      console.warn("Loading error:", e.message);
      UI.resetSelect(targetSelect, 'Error loading list');
      UI.enableSelect(targetSelect);
      
       if (feedbackEl) {
          feedbackEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + e.message; 
          feedbackEl.className = 'form-text text-danger fw-bold';
      }
    } finally {
      UI.toggle(spinner, false);
    }
  }

  // Loads connectors for a given solution
  const loadConnectorsFor = (side, solutionId) => {
    if (!solutionId) return Promise.resolve();
    const group = side === 'source' ? EL.src : EL.tgt;
    return loadSelectData('connectors', PATHS.connectors, { solution_id: solutionId }, group.conn, group.spin, group.feed);
  };

  // Loads modules for a given connector
  const loadModulesFor = (side, connectorId) => {
    if (!connectorId) return Promise.resolve();
    const group = side === 'source' ? EL.src : EL.tgt;
    const type = side === 'source' ? 'source' : 'cible';
    return loadSelectData('modules', PATHS.modules, { id: connectorId, type: type }, group.mod, group.modSpin, group.feed);
  };

// Loads specific Step 3 parameters (date fields, limit, etc.)
  async function loadStep3Params() {
    if (!EL.step3 || !step3ParamsPath || !EL.paramsContainer) return;
    
    const valSrcConn = EL.src.conn.value;
    const valTgtConn = EL.tgt.conn.value;
    const valSrcMod = EL.src.mod.value;
    const valTgtMod = EL.tgt.mod.value;

    if (!valSrcConn || !valTgtConn || !valSrcMod || !valTgtMod) {
        EL.paramsContainer.innerHTML = '';
        return;
    }

    const params = {
      src_connector: valSrcConn,
      tgt_connector: valTgtConn,
      src_module: valSrcMod,
      tgt_module: valTgtMod
    };

    try {
      EL.paramsContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';
      
      const html = await UI.fetchHtml(step3ParamsPath, params);
      EL.paramsContainer.innerHTML = html;

      $('.js-select-search', EL.paramsContainer).selectize({
          sortField: 'text',
          placeholder: 'Search...',
      });

      const dupSelect = UI.get('duplicate-field');
      const badgesContainer = UI.get('duplicate-badges-container');

      if (dupSelect && badgesContainer) {
    
          const addBadge = (val, label) => {
              // Anti-doublon
              if (badgesContainer.querySelector(`[data-field="${CSS.escape(val)}"]`)) return;

              const badge = document.createElement('span');
              badge.className = 'mapping-src-badge rounded-pill px-2 me-2 mb-2 d-inline-flex align-items-center';
              // badge.style.cssText = "background-color: #f8f9fa; border: 1px solid #ced4da; margin-right:5px; font-size: 0.9em;";
              badge.dataset.field = val;
              badge.innerHTML = `<span class="mapping-src-badge-label">${label}</span><button type="button" class="p-0 ms-2 mapping-src-badge-remove">&times;</button>`;

              badge.querySelector('button').onclick = () => {
                  badge.remove();
                  const tbody = UI.get('rule-mapping-body');
                  if(tbody) {
                      tbody.querySelectorAll('button.text-danger').forEach(b => {
                          b.disabled = false; b.style.opacity = '1'; b.style.pointerEvents = 'auto';
                      });
                      Array.from(badgesContainer.querySelectorAll('.mapping-src-badge')).forEach(b => {
                          if(window.ensureDuplicateMappingRow) window.ensureDuplicateMappingRow(b.dataset.field);
                      });
                  }
              };
              
              badgesContainer.appendChild(badge);
              if (window.ensureDuplicateMappingRow) window.ensureDuplicateMappingRow(val);
          };
          $(dupSelect).on('change', function() {
              const val = $(this).val();
              if (!val) return;

              let text = val;
              if (this.selectize) {
                  const item = this.selectize.getItem(val);
                  if(item.length) text = item.text();
                  this.selectize.clear(true);
              } else {
                  text = this.options[this.selectedIndex].text;
                  this.value = '';
              }

              addBadge(val, text);
              if (step3IsComplete()) revealStep4and5();
          });
          if (window.initialRule && window.initialRule.syncOptions?.duplicateField) {
               const raw = window.initialRule.syncOptions.duplicateField;
               const vals = raw.split(';');
               vals.forEach(v => {
                   if(v) addBadge(v, v);
               });
          }
      }
      const modeSelect = UI.get('mode');
      if (modeSelect) {
        modeSelect.addEventListener('change', () => { if (step3IsComplete()) revealStep4and5(); });
        if (modeSelect.value) revealStep4and5();
      }

    } catch (e) {
      console.error(e);
      EL.paramsContainer.innerHTML = '<div class="alert alert-danger">Unable to load parameters. (' + e.message + ')</div>';
    }
  }

  function step3IsComplete() {
    const modeEl = UI.get('mode');
    return !!(modeEl && modeEl.value);
  }

  function bothModulesSelected() {
    return !!(EL.src.mod.value && EL.tgt.mod.value);
  }

  // Progressive display of steps
  function revealStep3() {
    if (!EL.step3) return;
    UI.toggle(EL.step3, true);
    if (window.updateRuleNavLinks) window.updateRuleNavLinks();
  }

  function tryRevealStep3() {
    if (!EL.step3) return;
    if (bothModulesSelected()) {
      revealStep3();
      loadStep3Params();
    }
  }

  // Reset lower steps if a parent module changes
  function resetStep3AndBelow() {
    if (EL.paramsContainer) EL.paramsContainer.innerHTML = '';
    if (EL.step4Body) EL.step4Body.innerHTML = '';
    filtersLoaded = false;
    const mapBody = UI.get('rule-mapping-body');
    if (mapBody) mapBody.innerHTML = '';
    
    UI.toggle(EL.step4, false);
    UI.toggle(EL.step5, false);
  }

  // Loads the Filters UI (Step 4)
  const loadFiltersUI = async () => {
    if (!EL.step4 || !EL.step4Body) return;
    const pathFilter = EL.step4.getAttribute('data-path-filters');
    if (!pathFilter) return;

    const params = {
      src_solution_id: EL.src.sol.value,
      tgt_solution_id: EL.tgt.sol.value,
      src_module: EL.src.mod.value,
      tgt_module: EL.tgt.mod.value,
      src_connector_id: EL.src.conn.value,
      tgt_connector_id: EL.tgt.conn.value
    };

    const rid = new URLSearchParams(location.search).get('rule_id');
    if (rid) params.rule_id = rid;

    try {
      const html = await UI.fetchHtml(pathFilter, params);
      EL.step4Body.innerHTML = html;
      if (window.buildFilterFieldOptions) window.buildFilterFieldOptions();
      if (window.initFiltersUI) window.initFiltersUI();
      if (window.initMappingUI) window.initMappingUI();
    } catch (e) {
      EL.step4Body.innerHTML = '<p class="text-danger">Unable to load filters. (' + e.message + ')</p>';
    }
    console.groupEnd();
  };
  window.mydLoadRuleFilters = loadFiltersUI;

  function revealStep4and5() {
    if (EL.step4 && EL.step4.classList.contains('d-none')) {
      UI.toggle(EL.step4, true);
      if (window.mydLoadRuleFilters && !filtersLoaded) {
        window.mydLoadRuleFilters();
        filtersLoaded = true;
      }
    }
    if (EL.step5) {
      UI.toggle(EL.step5, true);
      // initMappingUI is called from loadFiltersUI after fields are loaded
    }
    if (window.updateRuleNavLinks) window.updateRuleNavLinks();
  }

  // --- LISTENERS ---
  EL.src.sol?.addEventListener('change', () => {
    UI.resetSelect(EL.src.conn); UI.resetSelect(EL.src.mod);
    loadConnectorsFor('source', EL.src.sol.value);
  });
  EL.tgt.sol?.addEventListener('change', () => {
    UI.resetSelect(EL.tgt.conn); UI.resetSelect(EL.tgt.mod);
    loadConnectorsFor('cible', EL.tgt.sol.value);
  });
  EL.src.conn?.addEventListener('change', () => loadModulesFor('source', EL.src.conn.value));
  EL.tgt.conn?.addEventListener('change', () => {
    loadModulesFor('cible', EL.tgt.conn.value);
    if (EL.tgt.mod.value) tryRevealStep3();
  });
  EL.src.mod?.addEventListener('change', () => { resetStep3AndBelow(); tryRevealStep3(); });
  EL.tgt.mod?.addEventListener('change', () => { resetStep3AndBelow(); tryRevealStep3(); });

  // Expose globally for Edit mode
  window.loadConnectorsFor = loadConnectorsFor;
  window.loadModulesFor = loadModulesFor;
  window.tryRevealStep3 = tryRevealStep3;
  window.loadStep3Params = loadStep3Params;
})();

/* ============================================================
 * FILTERS & MAPPING UI FUNCTIONS (Global)
 * ============================================================ */
(function() {
  // Retrieves JSON attributes (data-fields) from options
  const getJsonAttr = (el, attr) => {
    if (!el || !el.value) return null;
    const opt = el.options[el.selectedIndex];
    try { return JSON.parse(opt.getAttribute(attr)); } catch { return null; }
  };

  // Builds the filter field select options
  window.buildFilterFieldOptions = function() {
    // Skip if filter select already has options (server-rendered)
    const filterSelect = UI.get('rule-filter-field');
    if (filterSelect && filterSelect.querySelectorAll('optgroup').length > 0) {
      return; // Options already populated by template
    }
    const srcMod = UI.get('source-module');
    const tgtMod = UI.get('target-module');
    if (!filterSelect) return;

    const placeholder = filterSelect.querySelector('option[value=""]')?.textContent || '';
    filterSelect.innerHTML = '';
    filterSelect.appendChild(new Option(placeholder, '', true, true));

    const addGroup = (label, fields) => {
      if (fields && Object.keys(fields).length) {
        const og = document.createElement('optgroup');
        og.label = label;
        Object.entries(fields).forEach(([val, txt]) => og.appendChild(new Option(val, val)));
        filterSelect.appendChild(og);
      }
    };

    addGroup('Source Fields', getJsonAttr(srcMod, 'data-fields'));
    addGroup('Target Fields', getJsonAttr(tgtMod, 'data-fields'));
  };

  // Adds a visual row to the filter list
  window.addFilterRow = function(fieldVal, opVal, valueVal) {
      const listWrap = UI.get('rule-filters-list');
      const fieldSelect = UI.get('rule-filter-field');
      const opSelect = UI.get('rule-filter-operator');
      
      if (!listWrap || !fieldVal || !opVal) return;
      let fieldLabel = fieldVal;
      let opLabel = opVal;

      if (fieldSelect) {
          const opt = Array.from(fieldSelect.querySelectorAll('option')).find(o => o.value === fieldVal);
          if (opt) fieldLabel = opt.textContent;
      }
      if (opSelect) {
          const opt = Array.from(opSelect.options).find(o => o.value === opVal);
          if (opt) opLabel = opt.textContent;
      }

      const emptyMsg = listWrap.querySelector('p.text-muted');
      if (emptyMsg) emptyMsg.remove();

      let ul = listWrap.querySelector('ul');
      if (!ul) {
        ul = document.createElement('ul');
        ul.className = 'list-group';
        listWrap.appendChild(ul);
      }

      const li = document.createElement('li');
      li.className = 'list-group-item d-flex justify-content-between align-items-center';
      li.dataset.field = fieldVal;
      li.dataset.operator = opVal;
      li.dataset.value = valueVal;
      li.innerHTML = `<span><strong>${fieldLabel}</strong> <small class="text-muted">(${opLabel})</small> = ${valueVal}</span>`;

      const btnGroup = document.createElement('div');
      btnGroup.className = 'd-flex gap-1';

      const editBtn = document.createElement('button');
      editBtn.className = 'btn btn-sm text-primary myddleware-blue-pen';
      editBtn.innerHTML = '<i class="fa-solid fa-pen"></i>';
      editBtn.type = 'button';
      editBtn.title = 'Edit filter';
      editBtn.style.setProperty('color', '#05bfe6', 'important');
      editBtn.onclick = () => {
        const valInput = UI.get('rule-filter-value');

        if (fieldSelect) {
          fieldSelect.value = fieldVal;
          if (fieldSelect.selectize) fieldSelect.selectize.setValue(fieldVal, false);
        }
        if (opSelect) {
          opSelect.value = opVal;
          if (opSelect.selectize) opSelect.selectize.setValue(opVal, false);
        }
        if (valInput) {
          valInput.value = valueVal;
          valInput.focus();
        }

        li.remove();
        if (!ul.children.length) listWrap.innerHTML = '<p class="text-muted mb-0">No filters have been defined yet.</p>';
      };

      const delBtn = document.createElement('button');
      delBtn.className = 'btn btn-sm text-danger';
      delBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
      delBtn.type = 'button';
      delBtn.title = 'Delete filter';
      delBtn.onclick = () => {
        li.remove();
        if (!ul.children.length) listWrap.innerHTML = '<p class="text-muted mb-0">No filters have been defined yet.</p>';
      };

      btnGroup.appendChild(editBtn);
      btnGroup.appendChild(delBtn);
      li.appendChild(btnGroup);
      ul.appendChild(li);
  };

  // Initializes the add filter button
  window.initFiltersUI = function() {
    const addBtn = UI.get('rule-filter-add');
    if (!addBtn || addBtn.dataset.bound) return;
    addBtn.dataset.bound = '1';

    addBtn.addEventListener('click', () => {
      const fieldSel = UI.get('rule-filter-field');
      const opSel = UI.get('rule-filter-operator');
      const valInput = UI.get('rule-filter-value');

      if (!fieldSel.value || !opSel.value || !valInput.value.trim()) return;

      window.addFilterRow(fieldSel.value, opSel.value, valInput.value.trim());
      fieldSel.value = ''; 
      opSel.value = ''; 
      valInput.value = '';
    });
  };

  function createMappingSelect(fields) {
    const sel = document.createElement('select');
    sel.appendChild(new Option('', '', true, true));
    if (fields) Object.entries(fields).forEach(([v, t]) => sel.appendChild(new Option(v, v)));
    return sel;
  }

  function genRowId() {
    return 'row-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
  }

 // Adds a row to the Mapping table (Step 5)
  window.addMappingRow = function(tbody) {
    // Read fields from embedded JSON (from filter template)
    let srcFields = null, tgtFields = null;
    const fieldsDataEl = document.getElementById('rule-fields-data');
    if (fieldsDataEl) {
      try {
        const data = JSON.parse(fieldsDataEl.textContent);
        srcFields = data.source || {};
        tgtFields = data.target || {};
      } catch (e) { console.warn('Error parsing fields data:', e); }
    }

    const tr = document.createElement('tr');
    tr.dataset.rowId = genRowId();

    // Target Select
    const tdTgt = document.createElement('td');
    const tgtSel = createMappingSelect(tgtFields);
    tgtSel.classList.add('rule-mapping-target', 'js-select-search'); // Class for auto Selectize
    tdTgt.appendChild(tgtSel);

    // Source Select + Badges (Multiple Source Fields)
    const tdSrc = document.createElement('td');
    const srcWrapper = document.createElement('div');
    srcWrapper.className = 'mapping-src-wrapper';
    const srcSel = createMappingSelect(srcFields);
    srcSel.classList.add('rule-mapping-source-picker', 'js-select-search'); 
    const badgesDiv = document.createElement('div');
    badgesDiv.className = 'mapping-src-badges pt-1';

    // Manage multiple addition (Badges)
    srcSel.addEventListener('change', () => {
      const val = srcSel.value;    
      if (!val || badgesDiv.querySelector(`[data-field="${CSS.escape(val)}"]`)) {
        if(srcSel.selectize) srcSel.selectize.clear(true);
        else srcSel.value = '';
        return;
      }
      let txt = val;
      if (srcSel.selectize) {
          const item = srcSel.selectize.getItem(val);
          if (item.length) txt = item.text();
      } else if (srcSel.options[srcSel.selectedIndex]) {
          txt = srcSel.options[srcSel.selectedIndex].text;
      }
      const badge = document.createElement('span');
      badge.className = 'mapping-src-badge rounded-pill px-2 me-2 mb-2 d-inline-flex align-items-center';
      badge.dataset.field = val;
      badge.innerHTML = `<span class="mapping-src-badge-label">${txt}</span><button type="button" class="p-0 ms-2 mapping-src-badge-remove">&times;</button>`;  
      badge.querySelector('button').onclick = () => badge.remove();
      badgesDiv.appendChild(badge);
    
      if (srcSel.selectize) {
          srcSel.selectize.clear(true);
      } else {
          srcSel.value = '';
      }
    });

    srcWrapper.append(srcSel, badgesDiv);
    tdSrc.appendChild(srcWrapper);

    // Actions (Formula Button)
    const tdAct = document.createElement('td');
    tdAct.className = 'd-flex align-items-center';
    const slot = document.createElement('div');
    slot.className = 'formula-slot is-empty';
    slot.textContent = '...';
    const btn = document.createElement('button');
    btn.className = 'btn btn-sm ms-2 rule-mapping-formula';
    btn.innerHTML = '<i class="fa fa-code"></i>';
    btn.type = 'button';
    btn.setAttribute('data-bs-toggle', 'modal');
    btn.setAttribute('data-bs-target', '#mapping-formula');
    
    const hidden = document.createElement('input');
    hidden.type = 'hidden'; 
    hidden.className = 'rule-mapping-formula-input'; 
    hidden.name = 'mapping_formula[]';
    hidden.id = `formula-input-${tr.dataset.rowId}`; 

    tdAct.append(slot, btn, hidden);

    // Open formula modal with row context
    btn.onclick = () => {
      const container = UI.get('formula-selected-fields');
      const modal = UI.get('mapping-formula');
      if (container && modal) {
        container.innerHTML = '';
        const badges = tr.querySelectorAll('.mapping-src-badge');
        if (!badges.length) container.innerHTML = '<span class="text-muted">No field</span>';
        
        badges.forEach(b => {
          const chip = document.createElement('span');
          chip.className = 'badge-formula rounded-pill px-3 mb-3';
          chip.textContent = b.querySelector('.mapping-src-badge-label').textContent;
          chip.dataset.field = b.dataset.field;
          container.appendChild(chip);
        });
        
        modal.dataset.currentRowId = tr.dataset.rowId;
        modal.dataset.currentFormulaInputId = hidden.id;
        const area = UI.get('area_insert');
        if (area) area.value = hidden.value || '';
      }
    };

    // Delete Row
    const tdDel = document.createElement('td');
    tdDel.className = 'text-start';
    const delBtn = document.createElement('button');
    delBtn.className = 'btn btn-sm text-danger mt-2';
    delBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
    delBtn.type = 'button';
    delBtn.onclick = () => tr.remove();
    tdDel.appendChild(delBtn);

    tr.append(tdTgt, tdSrc, tdAct, tdDel);
    tbody.appendChild(tr);
    $(tgtSel).selectize({
        sortField: 'text',
        placeholder: 'Search Target...',
        dropdownParent: 'body',
    });
    
    $(srcSel).selectize({
        sortField: 'text',
        placeholder: 'Search Source...',
        dropdownParent: 'body',
        onChange: function(value) {
            if(value && this.$input && this.$input[0]) {
                 var event = new Event('change', { bubbles: true });
                 this.$input[0].dispatchEvent(event);
            }
        }
    });
  };

  window.initMappingUI = function() {
    const btn = UI.get('rule-mapping-add');
    const tbody = UI.get('rule-mapping-body');
    if (!btn || !tbody) return;

    if (!tbody.querySelector('tr')) window.addMappingRow(tbody);
    if (btn.dataset.bound) return;
    btn.dataset.bound = '1';
    btn.addEventListener('click', () => window.addMappingRow(tbody));
  };

// Ensures a mapping row exists for the chosen duplicate field
window.ensureDuplicateMappingRow = function(targetField) {
    const tbody = UI.get('rule-mapping-body');
    if (!tbody || !targetField) return;
    let row = Array.from(tbody.querySelectorAll('tr')).find(tr => {
        const sel = tr.querySelector('.rule-mapping-target');
        if (!sel) return false;
        return sel.value === targetField || (sel.options[sel.selectedIndex]?.text.trim() === targetField);
    });
    if (!row) {
        window.addMappingRow(tbody); 
        row = tbody.lastElementChild;

        const sel = row.querySelector('.rule-mapping-target');
        let matchVal = targetField;
        let opt = Array.from(sel.options).find(o => o.value === targetField);
        if (!opt) opt = Array.from(sel.options).find(o => o.text.trim() === targetField);
        
        if (opt) matchVal = opt.value;
        sel.value = matchVal;

        if (sel.selectize) {
            sel.selectize.setValue(matchVal);
        } else if ($(sel)[0] && $(sel)[0].selectize) {
            $(sel)[0].selectize.setValue(matchVal);
        }
    }
    if (row) {
        const delBtn = row.querySelector('button.text-danger');
        if (delBtn) {
            delBtn.disabled = true;
            delBtn.style.opacity = '0.3';
            delBtn.style.pointerEvents = 'none';
            delBtn.title = 'Champ obligatoire (Duplicate)';
        }
    }
  };
})();

/* ============================================================
 * EDIT MODE (Data hydration)
 * ============================================================ */
(function () {
  const ruleData = window.initialRule || null;
  if (!ruleData) {
    if (typeof window.ruleInitDone === 'function') window.ruleInitDone();
    return;
  }

  window.__EDIT_MODE__ = true;
  const nameInput = UI.get('rulename');

  // Fills the form with JSON sent by the server
  async function hydrateEditFromJson() {
    try {
      if (nameInput) {
        nameInput.value = ruleData.name || '';
        nameInput.classList.add('is-valid');

      }
      const descInput = UI.get('ruledescription');
      if (descInput && ruleData.params?.description) {
        descInput.value = ruleData.params.description;
      }

      if (typeof window.__revealStep2 === 'function') window.__revealStep2();
      else {
          const s2 = UI.get('step-2');
          if(s2) {
              s2.classList.remove('d-none');
              s2.style.opacity = '1';
          }
      }

      // Sequential filling and loading of dependent lists
      const srcSol = UI.get('source-solution');
      if (srcSol && ruleData.connection?.source?.solutionId) {
        UI.setValue(srcSol, ruleData.connection.source.solutionId);
        await window.loadConnectorsFor('source', srcSol.value);
      }
      
      const tgtSol = UI.get('target-solution');
      if (tgtSol && ruleData.connection?.target?.solutionId) {
        UI.setValue(tgtSol, ruleData.connection.target.solutionId);
        await window.loadConnectorsFor('cible', tgtSol.value);
      }

      const srcConn = UI.get('source-connector');
      if (srcConn && ruleData.connection?.source?.connectorId) {
        UI.setValue(srcConn, ruleData.connection.source.connectorId);
        await window.loadModulesFor('source', srcConn.value);
      }

      const tgtConn = UI.get('target-connector');
      if (tgtConn && ruleData.connection?.target?.connectorId) {
        UI.setValue(tgtConn, ruleData.connection.target.connectorId);
        await window.loadModulesFor('cible', tgtConn.value);
      }

      const srcMod = UI.get('source-module');
      if (srcMod && ruleData.connection?.source?.module) {
        UI.setValue(srcMod, ruleData.connection.source.module);
      }

      const tgtMod = UI.get('target-module');
      if (tgtMod && ruleData.connection?.target?.module) {
        UI.setValue(tgtMod, ruleData.connection.target.module);
      }

      // Disable structural fields in edit mode
      [srcSol, tgtSol, srcConn, tgtConn, srcMod, tgtMod].forEach(el => {
        if (!el) return;
        el.disabled = true;
        if (el.selectize) el.selectize.disable();
      });

      // Step 3 Hydration
      const step3 = UI.get('step-3');
      if(step3) {
          UI.toggle(step3, true);
          await window.loadStep3Params();

          if (ruleData.syncOptions?.type) {
             UI.setValue(UI.get('mode'), ruleData.syncOptions.type);
          }
          if (ruleData.syncOptions?.duplicateField) {
            const d = UI.get('duplicate-field');
            if (d) { 
                d.disabled = false; 
                UI.setValue(d, ruleData.syncOptions.duplicateField); 
            }
          }
          if (ruleData.params) {
            Object.entries(ruleData.params).forEach(([k, v]) => {
              UI.setValue(UI.get(k), v);
            });
          }
      }

      // Step 4 Hydration (Filters)
      const step4 = UI.get('step-4');
      if(step4) {
          UI.toggle(step4, true);
          await window.mydLoadRuleFilters();
          
          if (ruleData.filters && ruleData.filters.length > 0) {
              if (typeof window.addFilterRow === 'function') {
                  ruleData.filters.forEach(f => {
                      window.addFilterRow(f.field, f.operator, f.value);
                  });
              } else {
                  console.error('Function addFilterRow not found');
              }
          }
      }

      // Step 5 Hydration (Mapping)
      const step5 = UI.get('step-5');
      if(step5) {
          UI.toggle(step5, true);
          window.initMappingUI();
          
          const tbody = UI.get('rule-mapping-body');
          if (tbody) tbody.innerHTML = ''; 

          (ruleData.mapping || []).forEach(row => {
            window.addMappingRow(tbody);
            const tr = tbody.lastElementChild;
            if(!tr) return;
            
            const tSel = tr.querySelector('.rule-mapping-target');
            if (tSel && row.target) {
                if (tSel.selectize) {
                    tSel.selectize.setValue(row.target);
                } else if ($(tSel)[0] && $(tSel)[0].selectize) {
                    $(tSel)[0].selectize.setValue(row.target);
                } else {
                    tSel.value = row.target;
                }
            }
            const sSel = tr.querySelector('.rule-mapping-source-picker');
            if (sSel && row.source) {
              const srcs = Array.isArray(row.source) ? row.source : row.source.split(';');         
              srcs.filter(Boolean).forEach(s => {
                if (sSel.selectize) {
                    sSel.selectize.setValue(s.trim()); 
                } else if ($(sSel)[0] && $(sSel)[0].selectize) {
                    $(sSel)[0].selectize.setValue(s.trim());
                } else {
                    sSel.value = s.trim();
                    sSel.dispatchEvent(new Event('change'));
                }
              });
            }

            const hidden = tr.querySelector('.rule-mapping-formula-input');
            const slot = tr.querySelector('.formula-slot');
            if (row.formula && hidden) {
              hidden.value = row.formula;
              slot.textContent = row.formula;
              slot.classList.remove('is-empty');
            }
          });
      }
    if (ruleData.syncOptions?.duplicateField && typeof window.ensureDuplicateMappingRow === 'function') {
          let raw = ruleData.syncOptions.duplicateField;
          let values = [];
          
          if (Array.isArray(raw)) values = raw;
          else if (typeof raw === 'string') values = raw.split(';');
        
          values.forEach(v => {
              if(v && v.trim() !== '') window.ensureDuplicateMappingRow(v.trim());
          });
      }

    } catch (e) {
      console.error(e);
    } finally {
      if (typeof window.ruleInitDone === 'function') window.ruleInitDone();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', hydrateEditFromJson, { once: true });
  } else {
    hydrateEditFromJson();
  }
})();

/* ===========================================
 * FUNCTION WIZARD (Formula Editor)
 * =========================================== */
$(function () {
  const insertAtCursor = (el, text) => {
    if (!el) return;
    const [start, end] = [el.selectionStart, el.selectionEnd];
    el.value = el.value.substring(0, start) + text + el.value.substring(end);
    el.selectionStart = el.selectionEnd = start + text.length;
    el.focus();
  };

  const UI_WIZ = {
    sel: $('#function-select'),
    lookupOpts: $('#lookup-options'),
    lookupRule: $('#lookup-rule'),
    lookupField: $('#lookup-field'),
    param: $('#function-parameter'),
    tooltip: $('#function-tooltip'),
    prec: $('#round-precision')
  };

  let tooltipVisible = false;

  // Click on a badge in the list -> Inserts {field}
  $('#formula-selected-fields').on('click', '.badge-formula', function () {
    const field = $(this).data('field') || $(this).text().trim();
    if (field) insertAtCursor(document.getElementById('area_insert'), `{${field}}`);
  });

  $('#toggle-tooltip').on('click', function () {
    tooltipVisible = !tooltipVisible;
    $(this).find('i').toggleClass('fa-question fa-question-circle');
    const tip = UI_WIZ.sel.find(':selected').data('tooltip');
    if (tooltipVisible && tip) UI_WIZ.tooltip.text(tip).show();
    else UI_WIZ.tooltip.hide();
  });

  UI_WIZ.sel.on('change', function () {
    const val = $(this).val();
    const isMdw = val.startsWith('mdw_');
    const isRound = val === 'round';
    const isLookup = val === 'lookup';

    $('#function-parameter-input').toggle(!isLookup);
    UI_WIZ.param.toggle(!isMdw);
    $('#round-precision-input').toggle(isRound);
    UI_WIZ.lookupOpts.toggle(isLookup);

    if (isLookup && typeof lookupgetrule !== 'undefined') {
      $.get(lookupgetrule, { 
        arg1: (typeof connectorsourceidlookup !== 'undefined' ? connectorsourceidlookup : 0), 
        arg2: (typeof connectortargetidlookup !== 'undefined' ? connectortargetidlookup : 0) 
      }, (res) => {
        UI_WIZ.lookupRule.empty().append(new Option('Select...', ''));
        res.forEach(r => UI_WIZ.lookupRule.append(new Option(r.name, r.id)));
        UI_WIZ.lookupRule.prop('disabled', false);
      });
    }
  });

  $('#insert-function-parameter').on('click', function () {
    const func = UI_WIZ.sel.val();
    if (!func) return;
    const cat = UI_WIZ.sel.find(':selected').data('type');
    const val = UI_WIZ.param.val().trim();
    let call = `${func}()`;

    if (func === 'round') {
      const p = parseInt(UI_WIZ.prec.val());
      if (isNaN(p)) return UI_WIZ.prec.addClass('is-invalid');
      UI_WIZ.prec.removeClass('is-invalid');
      call = `round(${val}, ${p})`;
    } else if (func.startsWith('mdw_')) {
      call = `"${func}"`;
    } else if (val) {
      call = (cat === 1 || cat === 4) ? `${func}(${val})` : `${func}("${val}")`;
    }

    insertAtCursor(document.getElementById('area_insert'), call);
    UI_WIZ.param.val('');
  });

  UI_WIZ.lookupRule.on('change', function() {
    if (!this.value) return UI_WIZ.lookupField.prop('disabled', true);
    UI_WIZ.lookupField.empty().append(new Option('Select Field', ''));
    $('#formula-selected-fields .badge-formula').each(function() {
      const t = $(this).data('field') || $(this).text().trim();
      UI_WIZ.lookupField.append(new Option(t, t));
    });
    UI_WIZ.lookupField.prop('disabled', false);
  });

  $('#submit-lookup').on('click', function() {
    const f = UI_WIZ.lookupField.val();
    if (!f) return;
    const r = UI_WIZ.lookupRule.val();
    const e1 = $('#lookup-error-empty').is(':checked') ? 1 : 0;
    const e2 = $('#lookup-error-not-found').is(':checked') ? 1 : 0;
    insertAtCursor(document.getElementById('area_insert'), `lookup({${f.split(' (')[0]}}, "${r}", ${e1}, ${e2})`);
  });

  // Saves the formula into the hidden field of the mapping row
  $('#mapping-formula-save').on('click', function () {
    const modal = UI.get('mapping-formula');
    const id = modal.dataset.currentRowId;
    const inputId = modal.dataset.currentFormulaInputId;
    if (!id || !inputId) return;
    const tr = document.querySelector(`tr[data-row-id="${id}"]`);
    if (tr) {
      const val = $('#area_insert').val().trim();
      const hidden = UI.get(inputId);
      if (hidden) hidden.value = val;
      const slot = tr.querySelector('.formula-slot');
      slot.textContent = val;
      slot.classList.toggle('is-empty', !val);
    }
  });
});

/* ===========================================
 * SAVE (Final rule save)
 * =========================================== */
(function () {
  const saveBtn = UI.get('rule-save');
  if (!saveBtn) return;

  function collectData() {
    const rows = Array.from(document.querySelectorAll('#rule-mapping-body tr'));
    const mapping = { fields: {}, formulas: {} };
    
    rows.forEach(tr => {
      const tgt = tr.querySelector('.rule-mapping-target')?.value;
      if (!tgt) return;
      
      const srcs = Array.from(tr.querySelectorAll('.mapping-src-badge')).map(b => b.dataset.field);
      const form = tr.querySelector('.rule-mapping-formula-input')?.value?.trim();

      if (!mapping.fields[tgt]) mapping.fields[tgt] = [];
      if (!mapping.formulas[tgt]) mapping.formulas[tgt] = [];

      if (srcs.length > 0) {
          mapping.fields[tgt].push(...srcs);
      } else {
          mapping.fields[tgt].push('');
      }
      
      if (form) mapping.formulas[tgt].push(form);
    });

    const filters = [];
    document.querySelectorAll('#rule-filters-list li').forEach(li => {
      if (li.dataset.field && li.dataset.operator) {
        filters.push({
          field: li.dataset.field,
          operator: li.dataset.operator,
          value: li.dataset.value || ''
        });
      }
    });

    return { mapping, filters };
  }

  async function save(e) {
    if(e) e.preventDefault();
    const url = saveBtn.getAttribute('data-path-save');
    if (!url) return alert('Missing save endpoint');

    const fd = new FormData();
    const add = (k, v) => fd.append(k, v);
    const getVal = (id) => UI.get(id)?.value || '';
    const getTxt = (id) => { const el = UI.get(id); return el?.options[el.selectedIndex]?.text || ''; };

    add('name', getVal('rulename'));
    add('description', getVal('ruledescription'));
    add('src_solution_id', getVal('source-solution'));
    add('tgt_solution_id', getVal('target-solution'));
    add('src_solution_name', getTxt('source-solution').toLowerCase());
    add('tgt_solution_name', getTxt('target-solution').toLowerCase());
    add('src_connector_id', getVal('source-connector'));
    add('tgt_connector_id', getVal('target-connector'));
    add('src_module', getVal('source-module'));
    add('tgt_module', getVal('target-module'));

    const pContainer = UI.get('step-3-params-container');
    if (pContainer) {
      pContainer.querySelectorAll('input, select, textarea').forEach(el => {
        if (!el.name || el.disabled) return;
        if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
        let name = el.name;
        let value = el.value;

        if (el.id === 'duplicate-field' && el.multiple) {
            let vals = [];
            if (el.selectize) vals = el.selectize.getValue();
            else vals = Array.from(el.selectedOptions).map(o => o.value);

            if (!Array.isArray(vals)) vals = [vals];
            
            value = vals.join(';');
            name = name.replace('[]', '');
        }

        add(name === 'mode' ? 'sync_mode' : name, value);
      });
    } else {
        const mode = UI.get('mode') || UI.get('sync-mode');
        if(mode) add('sync_mode', mode.value);
    }

    const { mapping, filters } = collectData();
    add('filters', JSON.stringify(filters));
    Object.entries(mapping.fields).forEach(([t, arr]) => arr.forEach(v => add(`champs[${t}][]`, v)));
    Object.entries(mapping.formulas).forEach(([t, arr]) => arr.forEach(v => add(`formules[${t}][]`, v)));

    if (window.initialRule?.mode === 'edit') add('rule_id', window.initialRule.id);

    saveBtn.disabled = true;
    const oldHtml = saveBtn.innerHTML;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

    try {
      const res = await fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
      const text = await res.text();    
      if (!res.ok) throw new Error(text);
      
      let json;
      try { json = JSON.parse(text); } catch(e) { throw new Error(text); }

      if (json.error) throw new Error(json.error); // Gestion des erreurs renvoyées par le serveur
      if (json.redirect) window.location.assign(json.redirect);
      else alert('Rule saved.');
    } catch (e) {
      console.error(e);
      let msg = e.message;
      if (msg.includes('Array to string')) msg = 'Server Error: Format de données incorrect (Duplicate field).';
      alert('Save failed: ' + msg);
    } finally {
      saveBtn.disabled = false;
      saveBtn.innerHTML = oldHtml;
    }
  }

  saveBtn.addEventListener('click', save);
})();

/* ===========================================
 * SIMULATION (Debug Version)
 * =========================================== */
(function () {
  const modal = UI.get('mapping-simulation');
  if (!modal) return;

  const endpoints = { run: modal.getAttribute('data-endpoint-run'), count: modal.getAttribute('data-endpoint-count') };
  const EL = { res: modal.querySelector('#sim-result'), alert: modal.querySelector('#sim-alert'), input: modal.querySelector('#sim-record-id') };

  const showAlert = (msg, type = 'danger') => {
    EL.alert.className = `alert alert-${type}`;
    EL.alert.textContent = msg;
    EL.alert.classList.remove('d-none');
  };

  async function runSim(manual) {
    EL.alert.classList.add('d-none');
    if (!endpoints.run) return showAlert('Missing endpoint');

    const fd = new FormData();
    const getVal = (id) => UI.get(id)?.value || '';
    const getTxt = (id) => { const el = UI.get(id); return el?.options[el.selectedIndex]?.text?.trim().toLowerCase() || ''; };

    fd.append('src_solution_id', getVal('source-solution'));
    fd.append('tgt_solution_id', getVal('target-solution'));
    fd.append('src_solution_name', getTxt('source-solution'));
    fd.append('tgt_solution_name', getTxt('target-solution'));
    fd.append('src_connector_id', getVal('source-connector'));
    fd.append('tgt_connector_id', getVal('target-connector'));
    fd.append('src_module', getVal('source-module'));
    fd.append('tgt_module', getVal('target-module'));

    const pContainer = UI.get('step-3-params-container');
    if (pContainer) {
      pContainer.querySelectorAll('input, select').forEach(el => {
        if(el.name) {
            fd.append(el.name === 'mode' ? 'sync_mode' : el.name, el.value);
        }
      });
    }

    const rows = Array.from(document.querySelectorAll('#rule-mapping-body tr'));
    
    rows.forEach(tr => {
        const tgt = tr.querySelector('.rule-mapping-target')?.value;
        if(!tgt) return;
        
        const badges = Array.from(tr.querySelectorAll('.mapping-src-badge'));
        badges.forEach(b => fd.append(`champs[${tgt}][]`, b.dataset.field));  
        const form = tr.querySelector('.rule-mapping-formula-input')?.value;
        if(form) fd.append(`formules[${tgt}][]`, form);
    });

    if (manual) {
      const id = EL.input.value.trim();
      if (!id) return showAlert('ID required', 'warning');
      fd.append('query', id);
    }
    
    try {
      
      const res = await fetch(endpoints.run, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });      
      const text = await res.text();
      EL.res.innerHTML = text;
      
    } catch (e) {
      EL.res.innerHTML = '';
      showAlert('Simulation network error');
    } finally {
        console.groupEnd();
    }
  }

  modal.querySelector('#sim-run-manual')?.addEventListener('click', () => runSim(true));
  modal.querySelector('#sim-run-simple')?.addEventListener('click', () => runSim(false));
})();
/* ===========================================
 * SELECTIZE INIT (Global initialization)
 * =========================================== */
$(document).ready(function() {
    var renderSolution = function(item, escape) {
        var label = item.text || '';
        if (label) label = label.charAt(0).toUpperCase() + label.slice(1);
        
        var slug = (item.slug || item.text || '').toLowerCase().trim();
        var pathPrefix = '';
        if (window.location.pathname.indexOf('/myddleware') === 0) pathPrefix = '/myddleware';
        var imgPath = pathPrefix + '/assets/images/solution/' + escape(slug) + '.png';

        return '<div class="d-flex align-items-center" style="display: flex; align-items: center; padding: 5px;">' +
               '<img src="' + imgPath + '" style="width: 24px; height: 24px; object-fit: contain; margin-right: 10px;" onerror="this.style.display=\'none\'" />' + 
               '<span>' + escape(label) + '</span>' +
               '</div>';
    };
    $('select').each(function() {
        var $el = $(this);
        var id = $el.attr('id');
        if ($el[0].selectize) return;
        var options = {
            sortField: 'text',
            placeholder: 'Search...',
            onChange: function(value) {
                if(this.$input && this.$input[0]) {
                     var event = new Event('change', { bubbles: true });
                     this.$input[0].dispatchEvent(event);
                }
            }
        };
        if (id === 'source-solution' || id === 'target-solution') {
            options.valueField = 'value';
            options.labelField = 'text';
            options.searchField = ['text'];
            options.render = { option: renderSolution, item: renderSolution };
            
            var selectizeOpts = [];
            $el.find('option').each(function() {
                if($(this).val()) {
                    selectizeOpts.push({
                        value: $(this).val(),
                        text: $(this).text(),
                        slug: $(this).text().toLowerCase().trim()
                    });
                }
            });
        }

        $el.selectize(options);
    });
});

/* ===========================================
 * TEMPLATE LOGIC (Template management Step 1)
 * =========================================== */
(function () {
  const switchEl = UI.get('template-mode-switch');
  const zone = UI.get('rule-template-zone');
  const path = UI.get('step-2')?.getAttribute('data-path-templates');
  const saveBtn = UI.get('rule-save-template');
  
  const sSelect = UI.get('source-solution');
  const tSelect = UI.get('target-solution');

  if (!switchEl) return;

  const loadTemplates = () => {
    if (!switchEl.checked) return;

    if (path) {
      const sSlug = sSelect.options[sSelect.selectedIndex]?.getAttribute('data-solution-slug');
      const tSlug = tSelect.options[tSelect.selectedIndex]?.getAttribute('data-solution-slug');
      
      if (sSlug && tSlug) {
        zone.innerHTML = '<div class="text-center py-4"><div class="spinner-border"></div></div>';
        UI.fetchHtml(path, { src_solution: sSlug, tgt_solution: tSlug })
          .then(html => zone.innerHTML = html || '<p>No templates found for this pair.</p>')
          .catch(() => zone.innerHTML = '<p class="text-danger">Error loading templates.</p>');
      }
    }
  };

  switchEl.addEventListener('change', () => {
    const isTpl = switchEl.checked;
    
    // Toggle visibility between classic mode and template mode
    UI.toggle(UI.get('source-module-group'), !isTpl);
    UI.toggle(UI.get('target-module-group'), !isTpl);
    UI.toggle(UI.get('step-templates'), isTpl);
    [3,4,5].forEach(i => UI.toggle(UI.get(`step-${i}`), false));

    loadTemplates();
  });

  if (sSelect) sSelect.addEventListener('change', loadTemplates);
  if (tSelect) tSelect.addEventListener('change', loadTemplates);
  document.addEventListener('click', e => {
    const btn = e.target.closest('.js-template-choose');
    if (!btn) return;
    
    document.querySelectorAll('.template-card.is-selected').forEach(c => c.classList.remove('is-selected'));
    btn.closest('.template-card').classList.add('is-selected');
    
    let inp = UI.get('selected-template-name');
    if (!inp) {
        inp = document.createElement('input'); 
        inp.type='hidden'; inp.id='selected-template-name'; 
        UI.get('step-1').appendChild(inp);
    }
    inp.value = btn.dataset.templateName;
  });

  if (saveBtn) {
    saveBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      const url = saveBtn.getAttribute('data-path-template-apply');
      const tplName = UI.get('selected-template-name')?.value;
      const name = UI.get('rulename')?.value;
      
      if (!name || !tplName) return alert('Missing Name or Template selection');

      saveBtn.disabled = true;
      saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
      try {
        const res = await fetch(url, {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            ruleName: name,
            templateName: tplName,
            connectorSourceId: UI.get('source-connector').value,
            connectorTargetId: UI.get('target-connector').value
          })
        });
        const json = await res.json();
        if (json.redirect) window.location.assign(json.redirect);
        else alert('Error: ' + (json.message || 'Unknown'));
      } catch (e) {
        alert('Network Error');
      } finally {
        saveBtn.disabled = false;
      }
    });
  }
})();