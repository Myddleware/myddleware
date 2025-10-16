$(document).ready(function() {
    $('#form_action').change(function() {
        $('#form_subject').parent().hide();
        $('#form_to').parent().hide();
        $('#form_message').parent().hide();
        $('#form_status').parent().hide();
        $('#form_searchField').parent().hide();
        $('#form_searchValue').parent().hide();
        $('#form_ruleGenerate').parent().hide();
        $('#form_rerun').parent().show();
        if ($(this).val() !== 'updateStatus') {
            $('#form_status').val('');
        }
        
        const v = $(this).val();
        if (v !== 'sendNotification' && v !== 'updateStatus') {
            $('#form_subject').val(''); 
            $('#form_to').val('');
            $('#form_message').val('');
        }

        // if the action is changeData, clear the search field and value
        if ($(this).val() === 'changeData') {
            $('#form_searchField').val('');
            $('#form_searchValue').val('');
            $('#form_rerun').val('');
        }
        

        $('#form_targetField').val('');
        $('#form_targetFieldValue').val('');

        if ($(this).val() === 'sendNotification') {
            $('#form_subject').parent().show();
            $('#form_to').parent().show();
            $('#form_targetField').parent().hide();
            $('#form_targetFieldValueContainer').hide();
            $('#form_message').parent().show();
        } else if ($(this).val() === 'updateStatus') {
            $('#form_status').parent().show();
            $('#form_message').parent().show();
            $('#form_targetField').parent().hide();
            $('#form_targetFieldValueContainer').hide();
        } else if ($(this).val() === 'transformDocument') {
            $('#form_targetField').parent().hide();
            $('#form_targetFieldValueContainer').hide();
        } else if ($(this).val() === 'generateDocument') {
            $('#form_searchField').parent().show();
            $('#form_searchValue').parent().show();
            $('#form_ruleGenerate').parent().show();
            filterSearchFieldByGeneratingRule();
        } else if ($(this).val() === 'updateType') {
            $('#form_documentType').parent().show();
        }

    }).trigger('change');
});

function filterSearchFieldByGeneratingRule() {

    var $ruleGen = $('#form_ruleGenerate');
    var $searchField = $('#form_searchField');
    var ruleVal = $ruleGen.val();
    var selectedRuleLabel = $ruleGen.find('option:selected').text().trim();

    if (!selectedRuleLabel) {
        $searchField.find('optgroup').hide();
        return;
    }

    $searchField.find('optgroup').hide();
    var selector = 'optgroup[label="' + selectedRuleLabel.replace(/"/g, '\\"') + '"]';
    var $visibleGroup = $searchField.find(selector);
    $visibleGroup.show();

    var currentVal = $searchField.val();
    if (currentVal) {
        var safeVal = currentVal.replace(/"/g, '\\"');
        var existsInVisible = $visibleGroup.find('option[value="' + safeVal + '"]').length > 0;

        if (!existsInVisible) {
            var $ownerGroup = $searchField.find('optgroup').filter(function(){
                return $(this).find('option[value="' + safeVal + '"]').length > 0;
            }).first();

            if ($ownerGroup.length) {
                var ownerLabel = $ownerGroup.attr('label');
                var $matchingRuleOption = $ruleGen.find('option').filter(function(){
                    return $(this).text().trim() === ownerLabel;
                }).first();

                if ($matchingRuleOption.length) {
                    $ruleGen.val($matchingRuleOption.val());
                    $searchField.find('optgroup').hide();
                    $ownerGroup.show();

                    if ($ruleGen.data('select2')) {
                        $ruleGen.trigger('change.select2'); 
                        } else {
                            $ruleGen.trigger('change'); 
                        }
                    if ($searchField.data('select2')) {
                        $searchField.trigger('change.select2'); } else {
                            $searchField.trigger('change'); 
                        }
                    return;
                } else {
                    $searchField.val('');
                }
            } else {
                $searchField.val('');
            }
        }
    }

    if ($searchField.data('select2')) {
        $searchField.trigger('change.select2');
    } else {
        $searchField.trigger('change');
    }
}