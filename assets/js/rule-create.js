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
    if (v.length === 0) { hideSpinner(); return; }

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
  const pathFilters  = step4Section ? step4Section.getAttribute('data-path-filters') : null;

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

  // log helper pour les connecteurs
  function logConnector(selectEl, label) {
    if (!selectEl) return;

    const value = selectEl.value || null;
    const text  = selectEl.options[selectEl.selectedIndex]
      ? selectEl.options[selectEl.selectedIndex].text
      : null;

    console.log('[' + label + '] value =', value, '| label =', text);
  }

  // log helper pour les modules + fields (data-fields)
  function logModuleFields(selectEl, label) {
    if (!selectEl || !selectEl.value) return;

    const opt       = selectEl.options[selectEl.selectedIndex];
    const value     = selectEl.value;
    const text      = opt ? opt.text : null;
    const fieldsStr = opt ? opt.getAttribute('data-fields') : null;

    console.log('[' + label + '] value =', value, '| label =', text);

    if (fieldsStr) {
      try {
        const fields = JSON.parse(fieldsStr);
        console.log('[' + label + ' FIELDS]', fields);
      } catch (e) {
        console.error('Erreur parse data-fields pour', label, e);
      }
    } else {
      console.log('[' + label + ' FIELDS] aucun data-fields sur l’option sélectionnée');
    }
  }

  // Construit la liste des champs pour le select de filtres
  function buildFilterFieldOptions() {
    const filterSelect = document.getElementById('rule-filter-field');
    if (!filterSelect) {
      // step 4 pas encore dans le DOM (HTML injecté en AJAX)
      return;
    }

    // Récupère le texte du placeholder existant
    const oldPlaceholder = filterSelect.querySelector('option[value=""]');
    const placeholderText = oldPlaceholder ? oldPlaceholder.textContent : '';

    // On vide tout
    filterSelect.innerHTML = '';

    // On remet le placeholder
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = placeholderText || '';
    filterSelect.appendChild(placeholder);

    // Helper pour lire les fields d’un <select module> (via data-fields)
    function getFieldsFromModuleSelect(selectEl) {
      if (!selectEl || !selectEl.value) return null;
      const opt = selectEl.options[selectEl.selectedIndex];
      if (!opt) return null;

      const fieldsStr = opt.getAttribute('data-fields');
      if (!fieldsStr) return null;

      try {
        return JSON.parse(fieldsStr); // { fieldName: "Label", ... }
      } catch (e) {
        console.error('Erreur parse data-fields', e);
        return null;
      }
    }

    const srcFields = getFieldsFromModuleSelect(srcMod);
    const tgtFields = getFieldsFromModuleSelect(tgtMod);

    // Ajout des fields source
    if (srcFields && Object.keys(srcFields).length > 0) {
      const og = document.createElement('optgroup');
      og.label = 'Source Fields';

      Object.entries(srcFields).forEach(([name, label]) => {
        const opt = document.createElement('option');
        opt.value = name;
        opt.textContent = label;
        og.appendChild(opt);
      });

      filterSelect.appendChild(og);
    }

    // Ajout des fields target
    if (tgtFields && Object.keys(tgtFields).length > 0) {
      const og = document.createElement('optgroup');
      og.label = 'Target Fields';

      Object.entries(tgtFields).forEach(([name, label]) => {
        const opt = document.createElement('option');
        opt.value = name;
        opt.textContent = label;
        og.appendChild(opt);
      });

      filterSelect.appendChild(og);
    }
  }

  // Initialise le comportement du bouton + et de la liste de filtres
  function initFiltersUI() {
    const fieldSelect = document.getElementById('rule-filter-field');
    const opSelect    = document.getElementById('rule-filter-operator');
    const valueInput  = document.getElementById('rule-filter-value');
    const addBtn      = document.getElementById('rule-filter-add');
    const listWrap    = document.getElementById('rule-filters-list');

    if (!fieldSelect || !opSelect || !valueInput || !addBtn || !listWrap) {
      return;
    }

    // Pour éviter de binder plusieurs fois si on réinjecte le HTML
    if (addBtn.dataset.mydFiltersBound === '1') {
      return;
    }
    addBtn.dataset.mydFiltersBound = '1';

    addBtn.addEventListener('click', () => {
      const fieldVal   = fieldSelect.value;
      const fieldLabel = fieldSelect.options[fieldSelect.selectedIndex]
        ? fieldSelect.options[fieldSelect.selectedIndex].text
        : fieldVal;

      const opVal   = opSelect.value;
      const opLabel = opSelect.options[opSelect.selectedIndex]
        ? opSelect.options[opSelect.selectedIndex].text
        : opVal;

      const value = valueInput.value.trim();

      // Tu peux mettre des messages d'erreur si tu veux, là je bloque juste si un champ est vide
      if (!fieldVal || !opVal || !value) {
        return;
      }

      // On enlève le texte "empty" s'il existe
      const emptyP = listWrap.querySelector('p.text-muted');
      if (emptyP) emptyP.remove();

      // On récupère ou crée le <ul>
      let ul = listWrap.querySelector('ul');
      if (!ul) {
        ul = document.createElement('ul');
        ul.className = 'list-group';
        listWrap.appendChild(ul);
      }

      const li = document.createElement('li');
      li.className = 'list-group-item d-flex justify-content-between align-items-center';

      const span = document.createElement('span');
      span.innerHTML =
        '<strong>' + fieldLabel + '</strong> ' +
        '<small class="text-muted">(' + opLabel + ')</small> ' +
        '= ' + value;

      const delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.className = 'btn btn-sm text-danger';
      delBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';

      delBtn.addEventListener('click', () => {
        li.remove();
        // si plus aucun li, on remet le texte "empty"
        if (!ul.querySelector('li')) {
          const p = document.createElement('p');
          p.className = 'text-muted mb-0';
          p.textContent = 'create_rule.step4.filters.empty';
          listWrap.appendChild(p);
        }
      });

      li.appendChild(span);
      li.appendChild(delBtn);
      ul.appendChild(li);

      // On vide les champs
      fieldSelect.value = '';
      opSelect.value    = '';
      valueInput.value  = '';
    });
  }

  // charge les connecteurs pour une solution
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
    } catch (e) {
      resetSelect(selectEl);
      selectEl.disabled = false;
      setFeed(side, 'Impossible de charger les connecteurs.', true);
    } finally {
      spinnerEl?.classList.add('d-none');
    }
  }

  // charge les modules pour un connecteur
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
    } catch (e) {
      resetSelect(selectEl);
    }
  }

  // ---- STEP 3 : conditions pour afficher ----
  function bothModulesSelected() {
    return !!(srcMod && srcMod.value && tgtMod && tgtMod.value);
  }

  function revealStep3() {
    if (!step3) return;
    step3.classList.remove('d-none');
  }

  // charge les duplicate fields dispo pour le module cible
  async function loadDuplicateFields() {
    if (!step3 || !duplicateSel) return;
    if (!pathDup) return;
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
    } catch (e) {
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
   * STEP 4 + 5 — FILTER / MAPPING FIELDS
   * =========================================== */

  function step3IsComplete() {
    const hasDuplicate = duplicateSel && duplicateSel.value && duplicateSel.value !== '';
    const hasSync      = syncSel && syncSel.value && syncSel.value !== '';
    return hasDuplicate && hasSync;
  }

  // charge le HTML des filtres en AJAX
  (function () {
    const step4 = document.getElementById('step-4');
    if (!step4) return;

    const step4Body  = document.getElementById('step-4-body');
    const pathFilter = step4.getAttribute('data-path-filters');

    async function loadFiltersUI() {
      if (!pathFilter || !step4Body) return;

      // On récupère les valeurs actuelles des selects
      const srcSol  = document.getElementById('source-solution');
      const tgtSol  = document.getElementById('target-solution');
      const srcMod  = document.getElementById('source-module');
      const tgtMod  = document.getElementById('target-module');
      const srcConn = document.getElementById('source-connector');
      const tgtConn = document.getElementById('target-connector');

      const params = new URLSearchParams();
      if (srcSol && srcSol.value)   params.append('src_solution_id', srcSol.value);
      if (tgtSol && tgtSol.value)   params.append('tgt_solution_id', tgtSol.value);
      if (srcMod && srcMod.value)   params.append('src_module', srcMod.value);
      if (tgtMod && tgtMod.value)   params.append('tgt_module', tgtMod.value);
      if (srcConn && srcConn.value) params.append('src_connector_id', srcConn.value);
      if (tgtConn && tgtConn.value) params.append('tgt_connector_id', tgtConn.value);

      const url = params.toString()
        ? `${pathFilter}?${params.toString()}`
        : pathFilter;

      try {
        const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const html = await res.text();
        step4Body.innerHTML = html;

        // une fois le HTML de la step 4 injecté, on peut remplir les fields et binder le bouton +
        buildFilterFieldOptions();
        initFiltersUI();
      } catch (e) {
        step4Body.innerHTML = '<p class="text-danger">Impossible de charger les filtres.</p>';
      }
    }

    // exposé globalement pour qu'on puisse l'appeler au moment où la step3 est validée
    window.mydLoadRuleFilters = loadFiltersUI;
  })();

  let filtersLoaded = false;

  function revealStep4and5() {
    if (step4Section && step4Section.classList.contains('d-none')) {
      step4Section.classList.remove('d-none');

      // On charge les filtres uniquement la première fois
      if (window.mydLoadRuleFilters && !filtersLoaded) {
        window.mydLoadRuleFilters();
        filtersLoaded = true;
      }
    }
    if (step5Section) {
      step5Section.classList.remove('d-none');
    }
  }

  // écoute les 2 selects de la step 3
  if (duplicateSel) {
    duplicateSel.addEventListener('change', () => {
      if (step3IsComplete()) {
        revealStep4and5();
      }
    });
  }
  if (syncSel) {
    syncSel.addEventListener('change', () => {
      if (step3IsComplete()) {
        revealStep4and5();
      }
    });
  }

  // si tu changes les modules après coup, on relance la logique de step 3
  // + log + maj des fields pour les filtres
  srcMod?.addEventListener('change', () => {
    tryRevealStep3();
    logModuleFields(srcMod, 'SOURCE MODULE');
    buildFilterFieldOptions();
  });

  tgtMod?.addEventListener('change', () => {
    tryRevealStep3();
    logModuleFields(tgtMod, 'TARGET MODULE');
    buildFilterFieldOptions();
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
    if (tgtMod && tgtMod.value) {
      tryRevealStep3();
    }
  });
})();
