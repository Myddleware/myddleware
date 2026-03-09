console.log('Mapping Target Deduplication script loaded');

let _allTargetFields = null;

function getAllTargetFields() {
  if (_allTargetFields) return _allTargetFields;
  const el = document.getElementById('rule-fields-data');
  if (el) {
    try { _allTargetFields = JSON.parse(el.textContent).target || {}; }
    catch (e) { _allTargetFields = {}; }
  }
  return _allTargetFields || {};
}

function getUsedTargetValues(excludeRow) {
  const used = new Set();
  document.querySelectorAll('#rule-mapping-body tr').forEach(tr => {
    if (excludeRow && tr === excludeRow) return;
    const sel = tr.querySelector('.rule-mapping-target') || tr.querySelector('.js-select-search-locked');
    if (!sel) return;
    const val = sel.selectize ? sel.selectize.getValue() : sel.value;
    if (val) used.add(val);
  });
  return used;
}

function refreshTargetDropdowns() {
  const allFields = getAllTargetFields();
  document.querySelectorAll('#rule-mapping-body tr').forEach(tr => {
    const sel = tr.querySelector('.rule-mapping-target');
    if (!sel || !sel.selectize) return;
    const sz = sel.selectize;
    const currentVal = sz.getValue();
    const usedByOthers = getUsedTargetValues(tr);

    Object.keys(allFields).forEach(key => {
      if (key === currentVal) return;
      if (usedByOthers.has(key)) {
        sz.removeOption(key);
      } else if (!sz.options[key]) {
        sz.addOption({ value: key, text: key });
      }
    });
  });
}

function resetTargetFieldsCache() {
  _allTargetFields = null;
}

window.refreshTargetDropdowns = refreshTargetDropdowns;
window.resetTargetFieldsCache = resetTargetFieldsCache;
