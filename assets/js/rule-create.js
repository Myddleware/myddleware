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
 * STEP 2 + 3 — CONNECTORS / MODULES / SYNC
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
    
    console.log(srcMod)
    console.log(srcMod.value)
    console.log(tgtMod.value)
    console.log(tgtMod)
    return !!(srcMod && srcMod.value && tgtMod && tgtMod.value);
  }

  function revealStep3() {
    if (!step3) return;
    step3.classList.remove('d-none');
  }

  // charge les duplicate fields dispo pour le module cible
  async function loadDuplicateFields() {
    console.log(duplicateSel)
    console.log(step3)
    if (!step3 || !duplicateSel) return;
    if (!pathDup) return;
    if (!tgtConn?.value || !tgtMod?.value) {
      duplicateSel.innerHTML = '<option value="" disabled selected>—</option>';
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
    loadModulesFor('source', srcConn.value);
  });

  tgtConn?.addEventListener('change', () => {
    loadModulesFor('cible', tgtConn.value);
    if (tgtMod && tgtMod.value) {
      tryRevealStep3();
    }
  });

  srcMod?.addEventListener('change', tryRevealStep3);
  tgtMod?.addEventListener('change', tryRevealStep3);
})();
