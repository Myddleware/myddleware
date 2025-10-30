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
  let isNameValid   = false;
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
    isNameValid = false;
    inputName.classList.remove('is-valid');
    inputName.classList.add('is-invalid');
    feedback.className = 'form-text text-danger';
    feedback.textContent = msg || '';
  }
  function setSuccess(msg) {
    hideSpinner();
    isNameValid = true;
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
    isNameValid = false;

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
      isNameValid = true;
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
 * STEP 2 — CONNECTORS / MODULES (stateless)
 * =========================================== */
(function () {
  const step2 = document.getElementById('step-2');
  if (!step2) return;

  // URLs
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

  function resetSelect(selectEl, placeholder = '—') {
    if (!selectEl) return;
    selectEl.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;
    selectEl.disabled = true;
  }

  function clearFeedback(side) {
    const el = side === 'source' ? srcFeed : tgtFeed;
    if (el) {
      el.className = 'form-text';
      el.textContent = '';
    }
  }

  // ----- charge les connecteurs -----
  async function loadConnectorsFor(side, solutionId) {
    const selectEl  = side === 'source' ? srcConn : tgtConn;
    const spinnerEl = side === 'source' ? srcSpin : tgtSpin;

    resetSelect(selectEl);
    clearFeedback(side);

    if (!pathListConnectors || !solutionId) return;

    try {
      spinnerEl?.classList.remove('d-none');

      const res  = await fetch(`${pathListConnectors}?solution_id=${encodeURIComponent(solutionId)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const html = await res.text();

      // on injecte UNIQUEMENT des <option>
      selectEl.innerHTML = html || '<option value="" disabled selected>—</option>';
      selectEl.disabled = false;
    } catch (e) {
      resetSelect(selectEl);
      selectEl.disabled = false;
      const el = side === 'source' ? srcFeed : tgtFeed;
      if (el) {
        el.className = 'form-text text-danger';
        el.textContent = 'Impossible de charger les connecteurs.';
      }
    } finally {
      spinnerEl?.classList.add('d-none');
    }
  }

  // ----- charge les modules -----
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

  // ===== listeners =====
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
  });
})();

const step3 = document.getElementById('step-3');

function tryRevealStep3() {
  if (!step3) return;
  const hasSourceModule = srcMod && srcMod.value;
  const hasTargetModule = tgtMod && tgtMod.value;
  if (hasSourceModule && hasTargetModule) {
    step3.classList.remove('d-none');
  }
}

srcMod?.addEventListener('change', tryRevealStep3);
tgtMod?.addEventListener('change', tryRevealStep3);
