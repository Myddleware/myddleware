$(document).ready(function () {
  const $ruleGen      = $('#form_ruleGenerate');
  const $searchField  = $('#form_searchField');

  if (!$searchField.data('original-options')) {
    $searchField.data('original-options', $searchField.html());
  }

  function notifySelect2($el){
    if ($el && $el.length) {
      if ($el.data('select2')) $el.trigger('change.select2');
      else $el.trigger('change');
    }
  }

  function afficherMessageSansRegle() {
    $searchField.html('<option value="">Please select a rule first</option>');
    notifySelect2($searchField);
  }

  function updateSearchFields() {
    const ruleId  = $ruleGen.val();
    const ruleLib = $ruleGen.find('option:selected').text().trim();

    if (!ruleId) {
      afficherMessageSansRegle();
      return;
    }

    if ($searchField.find('optgroup').length === 0) {
      $searchField.html($searchField.data('original-options'));
    }

    $searchField.find('optgroup').hide();
    $searchField.find(`optgroup[label="${ruleLib}"]`).show();
    $searchField.val('');
    notifySelect2($searchField);
  }

  updateSearchFields();
  $ruleGen.on('change', updateSearchFields);
});
