$(document).ready(function () {
  const $ruleGen     = $('#form_ruleGenerate');
  const $searchField = $('#form_searchField');

  function notifySelect2($el){
    if ($el && $el.length) {
      if ($el.data('select2')) $el.trigger('change.select2');
      else $el.trigger('change');
    }
  }

  function showPlaceholderIfNoRule() {
    if ($searchField.find('optgroup').length === 0 && !$searchField.val()) {
      $searchField.html('<option value="">Please select a rule first</option>');
      notifySelect2($searchField);
    }
  }

  function updateSearchFields(keepCurrentValue) {
    const ruleId  = $ruleGen.val();
    const ruleLib = $ruleGen.find('option:selected').text().trim();

    if (ruleId == null || ruleId === '') {
      showPlaceholderIfNoRule();
      return;
    }

    $searchField.find('optgroup').hide();

    const $group = $searchField.find('optgroup').filter(function () {
      return $(this).attr('label') === ruleLib;
    });

    if ($group.length) {
      $group.show();

      if (keepCurrentValue) {
        const cur = $searchField.val();
        if (cur) {
          const exists = $group.find('option').filter(function () { 
            return $(this).val() === cur; 
          }).length > 0;
        }
      }
    }
    notifySelect2($searchField);
  }

  updateSearchFields(true);
  $ruleGen.on('change', function(){
    updateSearchFields(false);
  });
});
