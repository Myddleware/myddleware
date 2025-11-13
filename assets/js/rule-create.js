/* =========================
 * STEP 1 — NAME VALIDATION
 * ========================= */
(function () {
  const inputName    = document.getElementById('rulename');
  const feedback     = document.getElementById('rulename-feedback');
  const spinner      = document.getElementById('rulename-spinner');
  const step2Section = document.getElementById('step-2');

  if (!inputName || !feedback) return;

  let debounceTimer = null;
  let lastValueSent = '';
  let step2Shown    = false;

  const showSpinner = () => spinner?.classList.remove('d-none');
  const hideSpinner = () => spinner?.classList.add('d-none');

  function setNeutral() {
    inputName.classList.remove('is-invalid', 'is-valid');
    feedback.className = 'form-text';
    feedback.textContent = '';
  }
  function setError(msg) {
    hideSpinner();
    inputName.classList.remove('is-valid');
    inputName.classList.add('is-invalid');
    feedback.className = 'form-text text-danger';
    feedback.textContent = msg || '';
  }
  function setSuccess(msg) {
    hideSpinner();
    inputName.classList.remove('is-invalid');
    inputName.classList.add('is-valid');
    feedback.className = 'form-text text-success';
    feedback.textContent = msg || '';
    revealStep2();
  }
  function basicCheck(v) {
    if (v.length < 3) {
      setError(window.transRuleNameTooShort || 'Please enter at least 3 characters.');
      return false;
    }
    return true;
  }

  function revealStep2() {
    if (step2Shown || !step2Section) return;
    step2Shown = true;
    step2Section.classList.remove('d-none');
    step2Section.style.opacity = 0;
    step2Section.style.transition = 'opacity .25s ease';
    requestAnimationFrame(() => { step2Section.style.opacity = 1; });
    step2Section.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  async function checkUniqueness(nameVal) {
    const url = inputName.getAttribute('data-check-url');
    if (!url) return setError('Validation URL missing.');

    try {
      const res  = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams({ name: nameVal })
      });

      const text = await res.text();
      let existsFlag;
      try { existsFlag = JSON.parse(text); } catch { existsFlag = text; }

      if (existsFlag === 0 || existsFlag === '0') {
        setSuccess(window.transRuleNameAvailable || 'Name is available.');
      } else {
        setError(window.transRuleNameTaken || 'This name is already taken.');
      }
    } catch {
      setError(window.transRuleNameNetworkErr || 'Network error, try again.');
    }
  }

  inputName.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const v = inputName.value.trim();
      if (!basicCheck(v)) return;
      showSpinner();
      lastValueSent = v;
      checkUniqueness(v);
    }
  });

  inputName.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    setNeutral();

    const v = inputName.value.trim();
    if (!v) { hideSpinner(); return; }
    showSpinner();

    if (v.length < 3) {
      debounceTimer = setTimeout(() => {
        hideSpinner();
        setError(window.transRuleNameTooShort || 'Please enter at least 3 characters.');
      }, 200);
      return;
    }

    if (v === lastValueSent && inputName.classList.contains('is-valid')) {
      hideSpinner();
      revealStep2();
      return;
    }

    debounceTimer = setTimeout(() => {
      lastValueSent = v;
      if (!basicCheck(v)) { hideSpinner(); return; }
      checkUniqueness(v);
    }, 300);
  });
})();

/* ===========================================
 * STEP 2 + 3 + 4 + 5
 * CONNECTORS / MODULES / SYNC / FILTERS / MAPPING
 * =========================================== */
(function () {
  const step2 = document.getElementById('step-2');
  if (!step2) return;

  // URLs step 2
  const pathListConnectors = step2.getAttribute('data-path-connectors');
  const pathListModule     = step2.getAttribute('data-path-module');

  // Source
  const srcSol   = document.getElementById('source-solution');
  const srcConn  = document.getElementById('source-connector');
  const srcMod   = document.getElementById('source-module');
  const srcSpin  = document.getElementById('source-connector-spinner');
  const srcFeed  = document.getElementById('source-connector-feedback');

  // Target
  const tgtSol   = document.getElementById('target-solution');
  const tgtConn  = document.getElementById('target-connector');
  const tgtMod   = document.getElementById('target-module');
  const tgtSpin  = document.getElementById('target-connector-spinner');
  const tgtFeed  = document.getElementById('target-connector-feedback');

  // STEP 3
  const step3        = document.getElementById('step-3');
  const duplicateSel = step3 ? document.getElementById('duplicate-field') : null;
  const syncSel      = step3 ? document.getElementById('sync-mode') : null;
  const pathDup      = step3 ? step3.getAttribute('data-path-duplicate') : null;

  // STEP 4 & 5
  const step4Section = document.getElementById('step-4');
  const step5Section = document.getElementById('step-5');
  const step4Body    = document.getElementById('step-4-body');
  let filtersLoaded = false;

  // helpers ---------------
  function resetSelect(selectEl, placeholder = '—') {
    if (!selectEl) return;
    selectEl.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;
    selectEl.disabled = true;
  }

  function setFeed(side, msg, isError = false) {
    const el = side === 'source' ? srcFeed : tgtFeed;
    if (!el) return;
    el.className = 'form-text' + (isError ? ' text-danger' : '');
    el.textContent = msg || '';
  }

  function getFieldsFromModuleSelect(selectEl) {
    if (!selectEl || !selectEl.value) return null;
    const opt = selectEl.options[selectEl.selectedIndex];
    if (!opt) return null;
    const fieldsStr = opt.getAttribute('data-fields');
    if (!fieldsStr) return null;
    try { return JSON.parse(fieldsStr); } catch { return null; }
  }

  // connecteurs pour une solution
  async function loadConnectorsFor(side, solutionId) {
    const selectEl  = side === 'source' ? srcConn : tgtConn;
    const spinnerEl = side === 'source' ? srcSpin : tgtSpin;

    resetSelect(selectEl);
    setFeed(side, '');
    if (!pathListConnectors || !solutionId) return;

    try {
      spinnerEl?.classList.remove('d-none');
      const res  = await fetch(`${pathListConnectors}?solution_id=${encodeURIComponent(solutionId)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const html = await res.text();
      selectEl.innerHTML = html || '<option value="" disabled selected>—</option>';
      selectEl.disabled = false;
    } catch {
      resetSelect(selectEl);
      selectEl.disabled = false;
      setFeed(side, 'Impossible de charger les connecteurs.', true);
    } finally {
      spinnerEl?.classList.add('d-none');
    }
  }

  // modules pour un connecteur
  async function loadModulesFor(side, connectorId) {
    const selectEl = side === 'source' ? srcMod : tgtMod;
    resetSelect(selectEl);
    if (!pathListModule || !connectorId) return;

    const type = side === 'source' ? 'source' : 'cible';
    const url  = `${pathListModule}?id=${encodeURIComponent(connectorId)}&type=${encodeURIComponent(type)}`;

    try {
      const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const html = await res.text();
      selectEl.innerHTML = html || '<option value="" disabled selected>—</option>';
      selectEl.disabled = false;
    } catch {
      resetSelect(selectEl);
    }
  }

  // STEP 3 logic
  function bothModulesSelected() {
    return !!(srcMod && srcMod.value && tgtMod && tgtMod.value);
  }
  function revealStep3() {
    if (!step3) return;
    step3.classList.remove('d-none');
  }

  async function loadDuplicateFields() {
    if (!step3 || !duplicateSel || !pathDup) return;
    if (!tgtConn?.value || !tgtMod?.value) {
      duplicateSel.innerHTML = '<option value="" selected></option>';
      duplicateSel.disabled = true;
      return;
    }
    const url = `${pathDup}?connector_id=${encodeURIComponent(tgtConn.value)}&module=${encodeURIComponent(tgtMod.value)}`;
    try {
      const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const html = await res.text();
      duplicateSel.innerHTML = html || '<option value="" disabled selected>—</option>';
      duplicateSel.disabled = false;
      if (syncSel) syncSel.disabled = false;
    } catch {
      duplicateSel.innerHTML = '<option value="" disabled selected>—</option>';
      duplicateSel.disabled = true;
    }
  }

  function tryRevealStep3() {
    if (!step3) return;
    if (bothModulesSelected()) {
      revealStep3();
      loadDuplicateFields();
    }
  }

  /* ===========================================
   * STEP 4 — FILTERS
   * =========================================== */
  function buildFilterFieldOptions() {
    const filterSelect = document.getElementById('rule-filter-field');
    if (!filterSelect) return; // HTML injecté en AJAX

    const placeholderText = filterSelect.querySelector('option[value=""]')?.textContent || '';
    filterSelect.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = placeholderText;
    filterSelect.appendChild(placeholder);

    const srcFields = getFieldsFromModuleSelect(srcMod);
    const tgtFields = getFieldsFromModuleSelect(tgtMod);

    if (srcFields && Object.keys(srcFields).length) {
      const og = document.createElement('optgroup');
      og.label = 'Source Fields';
      Object.entries(srcFields).forEach(([name, label]) => {
        const opt = document.createElement('option');
        opt.value = name; opt.textContent = label;
        og.appendChild(opt);
      });
      filterSelect.appendChild(og);
    }

    if (tgtFields && Object.keys(tgtFields).length) {
      const og = document.createElement('optgroup');
      og.label = 'Target Fields';
      Object.entries(tgtFields).forEach(([name, label]) => {
        const opt = document.createElement('option');
        opt.value = name; opt.textContent = label;
        og.appendChild(opt);
      });
      filterSelect.appendChild(og);
    }
  }

  function initFiltersUI() {
    const fieldSelect = document.getElementById('rule-filter-field');
    const opSelect    = document.getElementById('rule-filter-operator');
       const valueInput  = document.getElementById('rule-filter-value');
    const addBtn      = document.getElementById('rule-filter-add');
    const listWrap    = document.getElementById('rule-filters-list');

    if (!fieldSelect || !opSelect || !valueInput || !addBtn || !listWrap) return;
    if (addBtn.dataset.mydFiltersBound === '1') return;
    addBtn.dataset.mydFiltersBound = '1';

    addBtn.addEventListener('click', () => {
      const fieldVal   = fieldSelect.value;
      const fieldLabel = fieldSelect.options[fieldSelect.selectedIndex]?.text || fieldVal;
      const opVal      = opSelect.value;
      const opLabel    = opSelect.options[opSelect.selectedIndex]?.text || opVal;
      const value      = valueInput.value.trim();
      if (!fieldVal || !opVal || !value) return;

      const emptyP = listWrap.querySelector('p.text-muted');
      if (emptyP) emptyP.remove();

      let ul = listWrap.querySelector('ul');
      if (!ul) {
        ul = document.createElement('ul');
        ul.className = 'list-group';
        listWrap.appendChild(ul);
      }

      const li = document.createElement('li');
      li.className = 'list-group-item d-flex justify-content-between align-items-center';

      const span = document.createElement('span');
      span.innerHTML = `<strong>${fieldLabel}</strong> <small class="text-muted">(${opLabel})</small> = ${value}`;

      const delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.className = 'btn btn-sm text-danger';
      delBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
      delBtn.addEventListener('click', () => {
        li.remove();
        if (!ul.querySelector('li')) {
          const p = document.createElement('p');
          p.className = 'text-muted mb-0';
          p.textContent = 'No filters have been defined yet.';
          listWrap.appendChild(p);
        }
      });

      li.appendChild(span);
      li.appendChild(delBtn);
      ul.appendChild(li);

      fieldSelect.value = '';
      opSelect.value    = '';
      valueInput.value  = '';
    });
  }

  /* ===========================================
   * STEP 5 — MAPPING FIELDS
   * =========================================== */

  function createMappingSelect(fieldsObj, placeholderText) {
    const select = document.createElement('select');
    select.className = 'form-select';

    const optEmpty = document.createElement('option');
    optEmpty.value = '';
    optEmpty.textContent = placeholderText || '—';
    select.appendChild(optEmpty);

    if (fieldsObj && Object.keys(fieldsObj).length) {
      Object.entries(fieldsObj).forEach(([name, label]) => {
        const opt = document.createElement('option');
        opt.value = name; opt.textContent = label;
        select.appendChild(opt);
      });
    }
    return select;
  }

  function genRowId() {
    if (typeof window !== 'undefined' && window.crypto && typeof window.crypto.getRandomValues === 'function') {
      const buf = new Uint8Array(16);
      window.crypto.getRandomValues(buf);
      buf[6] = (buf[6] & 0x0f) | 0x40;
      buf[8] = (buf[8] & 0x3f) | 0x80;
      const toHex = n => n.toString(16).padStart(2,'0');
      const hex = Array.from(buf, toHex).join('');
      return `${hex.slice(0,8)}-${hex.slice(8,12)}-${hex.slice(12,16)}-${hex.slice(16,20)}-${hex.slice(20)}`;
    }
    return 'row-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
  }

  function addMappingRow(tbody) {
    const srcFields = getFieldsFromModuleSelect(srcMod);
    const tgtFields = getFieldsFromModuleSelect(tgtMod);

    const tr  = document.createElement('tr');
    const tdTgt  = document.createElement('td'); tdTgt.className  = 'cell-target';
    const tdSrc  = document.createElement('td'); tdSrc.className  = 'cell-source';
    const tdAct  = document.createElement('td'); tdAct.className  = 'cell-actions';
    const tdDel  = document.createElement('td'); tdDel.className  = 'cell-delete text-end';

    // Target select
    const tgtSelect = createMappingSelect(tgtFields, '—');
    tgtSelect.classList.add('rule-mapping-target');

    // Source wrapper (badges AU-DESSUS + select)
    const srcWrapper = document.createElement('div');
    srcWrapper.className = 'mapping-src-wrapper';

    const srcBadgesContainer = document.createElement('div');
    srcBadgesContainer.className = 'mapping-src-badges';

    const srcSelect = createMappingSelect(srcFields, '—');
    srcSelect.classList.add('rule-mapping-source-picker');

    srcSelect.addEventListener('change', () => {
      const value = srcSelect.value;
      if (!value) return;
      const label = srcSelect.options[srcSelect.selectedIndex]?.text || value;
      if (srcBadgesContainer.querySelector(`[data-field="${CSS.escape(value)}"]`)) {
        srcSelect.value = '';
        return;
      }
      const badge = document.createElement('span');
      badge.className = 'mapping-src-badge rounded-pill px-2 me-2 mb-2 d-inline-flex align-items-center';
      badge.dataset.field = value;

      const badgeLabel = document.createElement('span');
      badgeLabel.className = 'mapping-src-badge-label';
      badgeLabel.textContent = label;

      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'p-0 ms-2 mapping-src-badge-remove';
      removeBtn.innerHTML = '&times;';
      removeBtn.addEventListener('click', () => badge.remove());

      badge.appendChild(badgeLabel);
      badge.appendChild(removeBtn);
      srcBadgesContainer.appendChild(badge);
      srcSelect.value = '';
    });

    srcWrapper.appendChild(srcSelect);
    srcWrapper.appendChild(srcBadgesContainer);

    // Actions: slot + bouton
    const actions = document.createElement('div');
    actions.className = 'mapping-actions d-flex align-items-center';

    const formulaSlot = document.createElement('div');
    formulaSlot.className = 'formula-slot is-empty';
    formulaSlot.textContent = '—';

    const formBtn = document.createElement('button');
    formBtn.type  = 'button';
    formBtn.setAttribute('data-bs-toggle', 'modal');
    formBtn.setAttribute('data-bs-target', '#mapping-formula');
    formBtn.className = 'btn btn-sm ms-2 rule-mapping-formula';
    formBtn.innerHTML = '<i class="fa fa-code"></i>';

    actions.appendChild(formulaSlot);
    actions.appendChild(formBtn);

    // hidden (stockage formule)
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.className = 'rule-mapping-formula-input';
    hidden.name = 'mapping_formula[]';

    const delBtn = document.createElement('button');
    delBtn.type  = 'button';
    delBtn.className = 'btn btn-sm text-danger';
    delBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
    delBtn.addEventListener('click', () => tr.remove());

    tdTgt.appendChild(tgtSelect);
    tdSrc.appendChild(srcWrapper);
    tdAct.appendChild(actions);
    tdAct.appendChild(hidden);
    tdDel.appendChild(delBtn);

    tr.appendChild(tdTgt);
    tr.appendChild(tdSrc);
    tr.appendChild(tdAct);
    tr.appendChild(tdDel);

    tbody.appendChild(tr);

    // Ouverture modale → remplir chips & lier la ligne + préremplir textarea
    formBtn.addEventListener('click', () => {
      const container = document.getElementById('formula-selected-fields');
      if (!container) return;

      container.innerHTML = '';
      const srcBadges = tr.querySelectorAll('.mapping-src-badge');
      srcBadges.forEach((b) => {
        const label = b.querySelector('.mapping-src-badge-label')?.textContent || b.dataset.field;
        const chip  = document.createElement('span');
        chip.className = 'badge-formula rounded-pill px-3 mb-3';
        chip.textContent = label;
        if (b.dataset.field) chip.dataset.field = b.dataset.field;
        container.appendChild(chip);
      });
      if (!container.children.length) {
        const span = document.createElement('span');
        span.className = 'text-muted';
        span.textContent = 'No field';
        container.appendChild(span);
      }

      const modalEl = document.getElementById('mapping-formula');
      if (!modalEl) return;

      const id = tr.dataset.rowId || genRowId();
      tr.dataset.rowId = id;
      modalEl.dataset.currentRowId = id;

      const area = document.getElementById('area_insert');
      if (area) area.value = hidden.value || '';
    });
  }

  function initMappingUI() {
    const addBtn = document.getElementById('rule-mapping-add');
    const tbody  = document.getElementById('rule-mapping-body');
    if (!addBtn || !tbody) return;
    if (addBtn.dataset.mydMappingBound === '1') return;
    addBtn.dataset.mydMappingBound = '1';

    addBtn.addEventListener('click', () => addMappingRow(tbody));
    if (!tbody.querySelector('tr')) addMappingRow(tbody);
  }

  // RESET steps 3,4,5
  function resetStep3AndBelow() {
    if (duplicateSel) {
      duplicateSel.innerHTML = '<option value="" disabled selected></option>';
      duplicateSel.value     = '';
      duplicateSel.disabled  = true;
    }
    if (syncSel) {
      syncSel.value    = '';
      syncSel.disabled = true;
    }
    if (step4Body) step4Body.innerHTML = '';
    filtersLoaded = false;

    const mappingBody = document.getElementById('rule-mapping-body');
    if (mappingBody) mappingBody.innerHTML = '';
  }

  // STEP 4 + 5 — logique générale
  function step3IsComplete() {
    return !!(syncSel && syncSel.value);
  }

  (function () {
    const step4 = document.getElementById('step-4');
    if (!step4) return;

    const step4BodyLocal  = document.getElementById('step-4-body');
    const pathFilter      = step4.getAttribute('data-path-filters');

    async function loadFiltersUI() {
      if (!pathFilter || !step4BodyLocal) return;

      const params = new URLSearchParams();
      const srcSol  = document.getElementById('source-solution');
      const tgtSol  = document.getElementById('target-solution');
      const srcMod  = document.getElementById('source-module');
      const tgtMod  = document.getElementById('target-module');
      const srcConn = document.getElementById('source-connector');
      const tgtConn = document.getElementById('target-connector');

      if (srcSol?.value)  params.append('src_solution_id', srcSol.value);
      if (tgtSol?.value)  params.append('tgt_solution_id', tgtSol.value);
      if (srcMod?.value)  params.append('src_module', srcMod.value);
      if (tgtMod?.value)  params.append('tgt_module', tgtMod.value);
      if (srcConn?.value) params.append('src_connector_id', srcConn.value);
      if (tgtConn?.value) params.append('tgt_connector_id', tgtConn.value);

      const url = params.toString() ? `${pathFilter}?${params.toString()}` : pathFilter;

      try {
        const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const html = await res.text();
        step4BodyLocal.innerHTML = html;

        buildFilterFieldOptions();
        initFiltersUI();
        initMappingUI();
      } catch {
        step4BodyLocal.innerHTML = '<p class="text-danger">Impossible de charger les filtres.</p>';
      }
    }

    window.mydLoadRuleFilters = loadFiltersUI;
  })();

  function revealStep4and5() {
    if (step4Section && step4Section.classList.contains('d-none')) {
      step4Section.classList.remove('d-none');
      if (window.mydLoadRuleFilters && !filtersLoaded) {
        window.mydLoadRuleFilters();
        filtersLoaded = true;
      }
    }
    if (step5Section) {
      step5Section.classList.remove('d-none');
      initMappingUI();
    }
  }

  // STEP 3 listeners
  if (duplicateSel) {
    duplicateSel.addEventListener('change', () => {
      if (step3IsComplete()) revealStep4and5();
    });
  }
  if (syncSel) {
    syncSel.addEventListener('change', () => {
      if (step3IsComplete()) revealStep4and5();
    });
  }

  // Changement de modules → reset + reload
  srcMod?.addEventListener('change', () => {
    resetStep3AndBelow();
    tryRevealStep3();
    logModuleFields(srcMod, 'SOURCE MODULE');
    buildFilterFieldOptions();
    if (step4Section && !step4Section.classList.contains('d-none') && window.mydLoadRuleFilters) {
      window.mydLoadRuleFilters(); filtersLoaded = true;
    }
  });

  tgtMod?.addEventListener('change', () => {
    resetStep3AndBelow();
    tryRevealStep3();
    logModuleFields(tgtMod, 'TARGET MODULE');
    buildFilterFieldOptions();
    if (step4Section && !step4Section.classList.contains('d-none') && window.mydLoadRuleFilters) {
      window.mydLoadRuleFilters(); filtersLoaded = true;
    }
  });

  // listeners step 2 ----------
  srcSol?.addEventListener('change', () => {
    resetSelect(srcConn);
    resetSelect(srcMod);
    loadConnectorsFor('source', srcSol.value);
  });

  tgtSol?.addEventListener('change', () => {
    resetSelect(tgtConn);
    resetSelect(tgtMod);
    loadConnectorsFor('cible', tgtSol.value);
  });

  srcConn?.addEventListener('change', () => {
    logConnector(srcConn, 'SOURCE CONNECTOR (change)');
    loadModulesFor('source', srcConn.value);
  });

  tgtConn?.addEventListener('change', () => {
    logConnector(tgtConn, 'TARGET CONNECTOR (change)');
    loadModulesFor('cible', tgtConn.value);
    if (tgtMod && tgtMod.value) tryRevealStep3();
  });

  // log au chargement de la page si déjà pré-sélectionné
  logConnector(srcConn, 'SOURCE CONNECTOR (onload)');
  logConnector(tgtConn, 'TARGET CONNECTOR (onload)');

  initMappingUI();
})();

/* ===========================================
 * FUNCTION WIZARD — POPUP #mapping-formula
 * =========================================== */
$(function () {
  const functionSelect      = $('#function-select');
  const lookupOptions       = $('#lookup-options');
  const lookupRule          = $('#lookup-rule');
  const lookupField         = $('#lookup-field');
  const functionParameter   = $('#function-parameter');
  const insertFunctionBtn   = $('#insert-function-parameter');
  const tooltipBox          = $('#function-tooltip');
  const roundPrecisionInput = $('#round-precision');
  const mappingFormulaModal = $('#mapping-formula');

  let tooltipVisible   = false;
  let currentTooltip   = '';
  let selectedFunction = '';

  // helper insertion
  function insertAtCursor($textarea, text) {
    const el = $textarea[0];
    if (!el) return;
    const start = typeof el.selectionStart === 'number' ? el.selectionStart : el.value.length;
    const end   = typeof el.selectionEnd   === 'number' ? el.selectionEnd   : start;
    const before = el.value.substring(0, start);
    const after  = el.value.substring(end);
    el.value = before + text + after;
    const newPos = start + text.length;
    el.selectionStart = el.selectionEnd = newPos;
    el.focus();
  }

  // chips “Selected fields”
  $('#formula-selected-fields').on('click', '.badge-formula', function () {
    const fieldName = $(this).data('field') || $(this).text().trim();
    if (!fieldName) return;
    insertAtCursor($('#area_insert'), `{${fieldName}}`);
    if (typeof colorationSyntax === 'function') colorationSyntax();
    if (typeof theme === 'function' && typeof style_template !== 'undefined') theme(style_template);
  });

  // bouton ? (tooltip)
  $('#toggle-tooltip').on('click', function () {
    tooltipVisible = !tooltipVisible;
    if (tooltipVisible) {
      $(this).find('i').removeClass('fa-question').addClass('fa-question-circle');
      if (functionSelect.val() && currentTooltip) tooltipBox.text(currentTooltip).show();
    } else {
      $(this).find('i').removeClass('fa-question-circle').addClass('fa-question');
      tooltipBox.hide();
    }
  });

  // changement de fonction
  functionSelect.on('change', function () {
    const selectedOption = $(this).find('option:selected');
    selectedFunction = $(this).val();
    currentTooltip   = selectedOption.data('tooltip');

    if (selectedFunction && selectedFunction.startsWith('mdw_')) {
      $('#function-parameter-input').show();
      $('#function-parameter').hide();
    } else {
      $('#function-parameter-input').show();
      $('#function-parameter').show();
    }

    if (currentTooltip && tooltipVisible && selectedFunction) tooltipBox.text(currentTooltip).show();
    else tooltipBox.hide();

    $('#round-precision-input').toggle(selectedFunction === 'round');

    if (selectedFunction === 'lookup') {
      lookupOptions.show();
      $('#function-parameter-input').hide();

      $.ajax({
        url: lookupgetrule,
        method: 'GET',
        data: { arg1: connectorsourceidlookup, arg2: connectortargetidlookup },
        success: function (rules) {
          lookupRule.empty();
          lookupRule.append('<option value="">' + translations.selectRule + '</option>');
          rules.forEach(rule => {
            lookupRule.append(`<option value="${rule.id}">${rule.name}</option>`);
          });
          lookupRule.prop('disabled', false);
        }
      });
    } else {
      lookupOptions.hide();
      $('#function-parameter-input').show();
    }
  });

  // insertion de la fonction
  insertFunctionBtn.on('click', function () {
    if (!selectedFunction) return;

    const functionCategory = $('#function-select option:selected').data('type');
    const areaInsert       = $('#area_insert');
    const content          = areaInsert.val();
    const position         = areaInsert[0]?.selectionStart ?? content.length;

    let functionCall = '';

    if (selectedFunction === 'round') {
      const parameterValue = functionParameter.val().trim();
      const precision      = parseInt(roundPrecisionInput.val(), 10);
      if (isNaN(precision) || precision < 1 || precision > 100) {
        roundPrecisionInput.addClass('is-invalid');
        return;
      }
      roundPrecisionInput.removeClass('is-invalid');
      functionCall = `round(${parameterValue}, ${precision})`;
      functionParameter.val(''); roundPrecisionInput.val('');
    } else if (selectedFunction.startsWith('mdw_')) {
      functionCall = `"${selectedFunction}"`;
    } else {
      const parameterValue = functionParameter.val().trim();
      if (parameterValue) {
        switch (functionCategory) {
          case 1: functionCall = `${selectedFunction}(${parameterValue})`; break;
          case 2:
          case 3: functionCall = `${selectedFunction}("${parameterValue}")`; break;
          case 4: functionCall = `${selectedFunction}()`; break;
          default:functionCall = `${selectedFunction}("${parameterValue}")`;
        }
      } else {
        functionCall = `${selectedFunction}()`;
      }
      functionParameter.val('');
    }

    const before = content.substring(0, position);
    const after  = content.substring(position);
    areaInsert.val(before + functionCall + after);

    if (typeof colorationSyntax === 'function') colorationSyntax();
    if (typeof theme === 'function' && typeof style_template !== 'undefined') theme(style_template);
  });

  // lookup : règle sélectionnée → charger champs à partir des badges
  lookupRule.on('change', function () {
    const selectedRule = $(this).val();
    if (selectedRule) {
      lookupField.empty().append('<option value="">' + translations.selectField + '</option>');
      $('#formula-selected-fields .badge-formula').each(function () {
        const fieldName = $(this).data('field') || $(this).text().trim();
        lookupField.append(`<option value="${fieldName}">${fieldName}</option>`);
      });
      lookupField.prop('disabled', false);
    } else {
      lookupField.prop('disabled', true);
    }
  });

  // submit lookup
  $('#submit-lookup').on('click', function () {
    const val = lookupField.val();
    if (!val) return;

    const fieldName = String(val).split(' (')[0];
    const errorEmpty    = $('#lookup-error-empty').is(':checked') ? 1 : 0;
    const errorNotFound = $('#lookup-error-not-found').is(':checked') ? 1 : 0;

    const lookupFormula = `lookup({${fieldName}}, "${lookupRule.val()}", ${errorEmpty}, ${errorNotFound})`;

    const areaInsert = $('#area_insert');
    const content    = areaInsert.val();
    const position   = areaInsert[0]?.selectionStart ?? content.length;

    const before = content.substring(0, position);
    const after  = content.substring(position);
    areaInsert.val(before + lookupFormula + after);

    if (typeof colorationSyntax === 'function') colorationSyntax();
    if (typeof theme === 'function' && typeof style_template !== 'undefined') theme(style_template);
  });

  // validation live précision
  roundPrecisionInput.on('input', function () {
    const sanitized = this.value.replace(/[^0-9]/g, '');
    if (sanitized !== this.value) this.value = sanitized;
    const n = parseInt(sanitized, 10);
    $(this).toggleClass('is-invalid', isNaN(n) || n < 1 || n > 100);
  });

  // SAVE: remplit la .formula-slot + hidden; la fermeture se fait via data-bs-dismiss="modal" sur le bouton
  $('#mapping-formula-save').off('click').on('click', function () {
    const modalEl = document.getElementById('mapping-formula');
    const rowId = modalEl?.dataset?.currentRowId;
    if (!rowId) return;

    const tr = document.querySelector(`tr[data-row-id="${CSS.escape(rowId)}"]`);
    if (!tr) return;

    const formula = ($('#area_insert').val() || '').trim();

    // hidden
    let hidden = tr.querySelector('.rule-mapping-formula-input');
    if (!hidden) {
      hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.className = 'rule-mapping-formula-input';
      hidden.name = 'mapping_formula[]';
      tr.querySelector('.cell-actions')?.appendChild(hidden);
    }
    hidden.value = formula;

    // slot aperçu
    let slot = tr.querySelector('.formula-slot');
    if (!slot) {
      slot = document.createElement('div');
      slot.className = 'formula-slot';
      (tr.querySelector('.mapping-actions') || tr.querySelector('.cell-actions'))?.prepend(slot);
    }

    const escaped = formula
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;');

    if (formula) {
      slot.classList.remove('is-empty');
      slot.innerHTML = `<code>${escaped}</code>`;
      slot.setAttribute('title', formula);
    } else {
      slot.classList.add('is-empty');
      slot.textContent = '—';
      slot.removeAttribute('title');
    }
    // (Pas de hide JS ici: mets data-bs-dismiss="modal" sur #mapping-formula-save dans le HTML)
  });

  function resetFunctionWizard() {
    functionSelect.val('').trigger('change');
    lookupRule.val('').trigger('change');
    lookupField.val('').prop('disabled', true);
    functionParameter.val('');
    roundPrecisionInput.val('').removeClass('is-invalid');
    tooltipBox.hide();
    tooltipVisible   = false;
    currentTooltip   = '';
    selectedFunction = '';
  }
  mappingFormulaModal.on('hidden.bs.modal', resetFunctionWizard);
});
