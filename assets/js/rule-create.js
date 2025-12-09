// ============================================================
// 1. UTILITIES
// ============================================================
const UI = {
  get: (id) => document.getElementById(id),
  
  toggle: (el, show) => {
    if (!el) return;
    show ? el.classList.remove('d-none') : el.classList.add('d-none');
    if (show && el.id === 'step-2') el.style.opacity = '1'; // Force l'opacité pour Step 2
  },
  
  resetSelect: (el, placeholder = '') => {
    if (!el) return;
    el.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;
    el.disabled = true;
    if (el.selectize) {
      el.selectize.clearOptions();
      el.selectize.disable();
    }
  },
  
  enableSelect: (el) => {
    if (!el) return;
    el.disabled = false;
    if (el.selectize) el.selectize.enable();
  },

  setValue: (el, value) => {
      if (!el) return;
      el.value = String(value);
      if (el.selectize) {
          el.selectize.setValue(String(value), true); 
      }
  },

  syncSelectize: (el) => {
      if (el && el.selectize) {
          el.selectize.refreshOptions(false);
      }
  },

  fetchHtml: async (url, params = {}) => {
      const query = new URLSearchParams(params).toString();
      const target = query ? `${url}?${query}` : url;
      const res = await fetch(target, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) {
          const errorMsg = await res.text();
          throw new Error(errorMsg || `Erreur HTTP ${res.status}`);
      }
      return await res.text();
    }
};

/* ============================================================
 * STEP 1 — NAME VALIDATION
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

  function revealStep2() {
    if (step2Shown || !step2Section) return;
    step2Shown = true;
    UI.toggle(step2Section, true);
    step2Section.style.opacity = 0;
    step2Section.style.transition = 'opacity .25s ease';
    requestAnimationFrame(() => { step2Section.style.opacity = 1; });
    step2Section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    if (window.updateRuleNavLinks) window.updateRuleNavLinks();
  }

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
 * ============================================================ */
(function () {
  const step2 = UI.get('step-2');
  if (!step2) return;

  const PATHS = {
    connectors: step2.getAttribute('data-path-connectors'),
    modules: step2.getAttribute('data-path-module')
  };

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
    UI.resetSelect(targetSelect);
    if (feedbackEl) {
        feedbackEl.textContent = '';
        feedbackEl.classList.remove('text-danger', 'text-success');
    }
    if (!url) return;

    try {
      UI.toggle(spinner, true);
      const htmlParams = new URLSearchParams(params).toString();
      const res = await UI.fetchHtml(url, params);
      const response = await fetch(`${url}?${htmlParams}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      
      if (!response.ok) {
          const errorMsg = await response.text(); 
          throw new Error(errorMsg || `Erreur ${response.status}`);
      }
      const html = await response.text();
      
      targetSelect.innerHTML = '';
      targetSelect.appendChild(new Option('', '', true, true));
      
      if (html) {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        Array.from(temp.querySelectorAll('option')).forEach(opt => targetSelect.appendChild(opt));
      }
      
      UI.syncSelectize(targetSelect);
      UI.enableSelect(targetSelect);

    } catch (e) {
      console.warn("Connexion refusée (Normal si mauvais mot de passe):", e.message);
      UI.resetSelect(targetSelect);
      UI.enableSelect(targetSelect);
      
   if (feedbackEl) {
          feedbackEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + e.message; 
          feedbackEl.className = 'form-text text-danger fw-bold';
      }
    } finally {
      UI.toggle(spinner, false);
    }
  }

  const loadConnectorsFor = (side, solutionId) => {
    if (!solutionId) return Promise.resolve();
    const group = side === 'source' ? EL.src : EL.tgt;
    return loadSelectData('connectors', PATHS.connectors, { solution_id: solutionId }, group.conn, group.spin, group.feed);
  };

  const loadModulesFor = (side, connectorId) => {
    if (!connectorId) return Promise.resolve();
    const group = side === 'source' ? EL.src : EL.tgt;
    const type = side === 'source' ? 'source' : 'cible';
    return loadSelectData('modules', PATHS.modules, { id: connectorId, type: type }, group.mod, group.modSpin, group.feed);
  };

  async function loadStep3Params() {
    if (!EL.step3 || !step3ParamsPath || !EL.paramsContainer) return;
    
    if (!EL.src.conn.value || !EL.tgt.conn.value || !EL.src.mod.value || !EL.tgt.mod.value) {
        EL.paramsContainer.innerHTML = '';
        return;
    }

    const params = {
      src_connector: EL.src.conn.value,
      tgt_connector: EL.tgt.conn.value,
      src_module: EL.src.mod.value,
      tgt_module: EL.tgt.mod.value
    };

    try {
      EL.paramsContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';
      const html = await UI.fetchHtml(step3ParamsPath, params);
      EL.paramsContainer.innerHTML = html;

      const modeSelect = UI.get('mode');
      if (modeSelect) {
        modeSelect.addEventListener('change', () => { if (step3IsComplete()) revealStep4and5(); });
        if (modeSelect.value) revealStep4and5();
      }

      const dupSelect = UI.get('duplicate-field');
      if (dupSelect) {
        dupSelect.addEventListener('change', () => {
          if (step3IsComplete()) revealStep4and5();
          const label = dupSelect.options[dupSelect.selectedIndex]?.textContent?.trim();
          if (label && window.ensureDuplicateMappingRow) window.ensureDuplicateMappingRow(label);
        });
      }
    } catch (e) {
      EL.paramsContainer.innerHTML = '<div class="alert alert-danger">Impossible de charger les paramètres.</div>';
    }
  }

  function step3IsComplete() {
    const modeEl = UI.get('mode');
    return !!(modeEl && modeEl.value);
  }

  function bothModulesSelected() {
    return !!(EL.src.mod.value && EL.tgt.mod.value);
  }

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

  function resetStep3AndBelow() {
    if (EL.paramsContainer) EL.paramsContainer.innerHTML = '';
    if (EL.step4Body) EL.step4Body.innerHTML = '';
    filtersLoaded = false;
    const mapBody = UI.get('rule-mapping-body');
    if (mapBody) mapBody.innerHTML = '';
    
    UI.toggle(EL.step4, false);
    UI.toggle(EL.step5, false);
  }

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
    } catch {
      EL.step4Body.innerHTML = '<p class="text-danger">Impossible de charger les filtres.</p>';
    }
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
      if (window.initMappingUI) window.initMappingUI();
    }
    if (window.updateRuleNavLinks) window.updateRuleNavLinks();
  }

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

  window.loadConnectorsFor = loadConnectorsFor;
  window.loadModulesFor = loadModulesFor;
  window.tryRevealStep3 = tryRevealStep3;
  window.loadStep3Params = loadStep3Params;
})();

/* ============================================================
 * FILTERS & MAPPING UI FUNCTIONS (Global)
 * ============================================================ */
(function() {
  const getJsonAttr = (el, attr) => {
    if (!el || !el.value) return null;
    const opt = el.options[el.selectedIndex];
    try { return JSON.parse(opt.getAttribute(attr)); } catch { return null; }
  };

  window.buildFilterFieldOptions = function() {
    const filterSelect = UI.get('rule-filter-field');
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
      
      li.innerHTML = `<span><strong>${fieldLabel}</strong> <small class="text-muted">(${opLabel})</small> = ${valueVal}</span>`;
      
      const delBtn = document.createElement('button');
      delBtn.className = 'btn btn-sm text-danger';
      delBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
      delBtn.type = 'button';
      delBtn.onclick = () => {
        li.remove();
        if (!ul.children.length) listWrap.innerHTML = '<p class="text-muted mb-0">No filters have been defined yet.</p>';
      };
      
      li.appendChild(delBtn);
      ul.appendChild(li);
  };

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
    sel.className = 'form-select';
    sel.appendChild(new Option('', '', true, true));
    if (fields) Object.entries(fields).forEach(([v, t]) => sel.appendChild(new Option(v, v)));
    return sel;
  }

  function genRowId() {
    return 'row-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
  }

  window.addMappingRow = function(tbody) {
    const srcFields = getJsonAttr(UI.get('source-module'), 'data-fields');
    const tgtFields = getJsonAttr(UI.get('target-module'), 'data-fields');

    const tr = document.createElement('tr');
    tr.dataset.rowId = genRowId();

    // Target Select
    const tdTgt = document.createElement('td');
    const tgtSel = createMappingSelect(tgtFields);
    tgtSel.classList.add('rule-mapping-target');
    tdTgt.appendChild(tgtSel);

    // Source Select + Badges
    const tdSrc = document.createElement('td');
    const srcWrapper = document.createElement('div');
    srcWrapper.className = 'mapping-src-wrapper';
    const srcSel = createMappingSelect(srcFields);
    srcSel.classList.add('rule-mapping-source-picker');
    const badgesDiv = document.createElement('div');
    badgesDiv.className = 'mapping-src-badges pt-1';

    srcSel.addEventListener('change', () => {
      const val = srcSel.value;
      if (!val || badgesDiv.querySelector(`[data-field="${CSS.escape(val)}"]`)) {
        srcSel.value = ''; return;
      }
      const txt = srcSel.options[srcSel.selectedIndex].text;
      const badge = document.createElement('span');
      badge.className = 'mapping-src-badge rounded-pill px-2 me-2 mb-2 d-inline-flex align-items-center';
      badge.dataset.field = val;
      badge.innerHTML = `<span class="mapping-src-badge-label">${txt}</span><button type="button" class="p-0 ms-2 mapping-src-badge-remove">&times;</button>`;
      badge.querySelector('button').onclick = () => badge.remove();
      badgesDiv.appendChild(badge);
      srcSel.value = '';
    });

    srcWrapper.append(srcSel, badgesDiv);
    tdSrc.appendChild(srcWrapper);

    // Actions (Formula)
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
    hidden.type = 'hidden'; hidden.className = 'rule-mapping-formula-input'; hidden.name = 'mapping_formula[]';

    tdAct.append(slot, btn, hidden);

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
        const area = UI.get('area_insert');
        if (area) area.value = hidden.value || '';
      }
    };

    // Delete
    const tdDel = document.createElement('td');
    tdDel.className = 'text-end';
    const delBtn = document.createElement('button');
    delBtn.className = 'btn btn-sm text-danger';
    delBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
    delBtn.type = 'button';
    delBtn.onclick = () => tr.remove();
    tdDel.appendChild(delBtn);

    tr.append(tdTgt, tdSrc, tdAct, tdDel);
    tbody.appendChild(tr);
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

  window.ensureDuplicateMappingRow = function(targetField) {
    const tbody = UI.get('rule-mapping-body');
    if (!tbody) return;
    window.initMappingUI();

    const rows = Array.from(tbody.querySelectorAll('tr'));
    const exists = rows.some(tr => tr.querySelector('.rule-mapping-target')?.value === targetField);
    if (exists) return;

    let row = rows.find(tr => !tr.querySelector('.rule-mapping-target')?.value);
    if (!row) { window.addMappingRow(tbody); row = tbody.lastElementChild; }
    
    const sel = row.querySelector('.rule-mapping-target');
    const opt = Array.from(sel.options).find(o => o.value === targetField || o.text.trim() === targetField);
    if (opt) sel.value = opt.value;
  };
})();

/* ============================================================
 * EDIT
 * ============================================================ */
(function () {
  const ruleData = window.initialRule || null;
  if (!ruleData) {
    if (typeof window.ruleInitDone === 'function') window.ruleInitDone();
    return;
  }

  window.__EDIT_MODE__ = true;
  const nameInput = UI.get('rulename');

  async function hydrateEditFromJson() {
    try {
      if (nameInput) {
        nameInput.value = ruleData.name || '';
        nameInput.classList.add('is-valid');
      }

      if (typeof window.__revealStep2 === 'function') window.__revealStep2();
      else {
          const s2 = UI.get('step-2');
          if(s2) {
              s2.classList.remove('d-none');
              s2.style.opacity = '1';
          }
      }

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

      [srcSol, tgtSol, srcConn, tgtConn, srcMod, tgtMod].forEach(el => {
        if (!el) return;
        el.disabled = true;
        if (el.selectize) el.selectize.disable();
      });

      // Step 3
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

      // Step 4
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
                  console.error('Fonction addFilterRow introuvable');
              }
          }
      }

      // Step 5
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
            if (tSel && row.target) tSel.value = row.target;

            const sSel = tr.querySelector('.rule-mapping-source-picker');
            if (sSel && row.source) {
              const srcs = Array.isArray(row.source) ? row.source : row.source.split(';');
              srcs.filter(Boolean).forEach(s => {
                sSel.value = s.trim();
                sSel.dispatchEvent(new Event('change'));
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
 * FUNCTION WIZARD
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

  $('#mapping-formula-save').on('click', function () {
    const id = $('#mapping-formula').data('currentRowId');
    const tr = document.querySelector(`tr[data-row-id="${id}"]`);
    if (tr) {
      const val = $('#area_insert').val().trim();
      tr.querySelector('.rule-mapping-formula-input').value = val;
      const slot = tr.querySelector('.formula-slot');
      slot.textContent = val;
      slot.classList.toggle('is-empty', !val);
    }
  });
});

/* ===========================================
 * SAVE
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

      if (srcs.length) mapping.fields[tgt].push(...srcs);
      if (form) mapping.formulas[tgt].push(form);
    });

    const filters = [];
    document.querySelectorAll('#rule-filters-list li').forEach(li => {
      const txt = li.innerText; 
      const parts = txt.match(/^(.*?) \((.*?)\) = (.*)$/);
      if (parts) filters.push({ field: parts[1], operator: parts[2], value: parts[3] });
    });

    return { mapping, filters };
  }

  async function save() {
    const url = saveBtn.getAttribute('data-path-save');
    if (!url) return alert('Missing save endpoint');

    const fd = new FormData();
    const add = (k, v) => fd.append(k, v);
    const getVal = (id) => UI.get(id)?.value || '';
    const getTxt = (id) => { const el = UI.get(id); return el?.options[el.selectedIndex]?.text || ''; };

    add('name', getVal('rulename'));
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
        add(el.name === 'mode' ? 'sync_mode' : el.name, el.value);
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
      
      const json = JSON.parse(text);
      if (json.redirect) window.location.assign(json.redirect);
      else alert('Rule saved.');
    } catch (e) {
      alert('Save failed: ' + e.message);
    } finally {
      saveBtn.disabled = false;
      saveBtn.innerHTML = oldHtml;
    }
  }

  saveBtn.addEventListener('click', save);
})();

/* ===========================================
 * SIMULATION
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
        if(el.name) fd.append(el.name === 'mode' ? 'sync_mode' : el.name, el.value);
      });
    }

    const rows = Array.from(document.querySelectorAll('#rule-mapping-body tr'));
    rows.forEach(tr => {
        const tgt = tr.querySelector('.rule-mapping-target')?.value;
        if(!tgt) return;
        Array.from(tr.querySelectorAll('.mapping-src-badge')).forEach(b => fd.append(`champs[${tgt}][]`, b.dataset.field));
        const form = tr.querySelector('.rule-mapping-formula-input')?.value;
        if(form) fd.append(`formules[${tgt}][]`, form);
    });

    if (manual) {
      const id = EL.input.value.trim();
      if (!id) return showAlert('ID required', 'warning');
      fd.append('query', id);
    }

    EL.res.innerHTML = '<div class="text-center">Loading...</div>';
    
    try {
      const res = await fetch(endpoints.run, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
      const html = await res.text();
      EL.res.innerHTML = html;
    } catch {
      EL.res.innerHTML = '';
      showAlert('Simulation network error');
    }
  }

  modal.querySelector('#sim-run-manual')?.addEventListener('click', () => runSim(true));
  modal.querySelector('#sim-run-simple')?.addEventListener('click', () => runSim(false));
})();

/* ===========================================
 * TEMPLATE LOGIC
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