$(document).ready(function() {
    $('#form_action').change(function() {
        $('#form_subject').parent().hide();
        $('#form_to').parent().hide();
        $('#form_message').parent().hide();
        $('#form_status').parent().hide();
        $('#form_searchField').parent().hide();
        $('#form_searchValue').parent().hide();
        $('#form_rule').parent().hide();
        $('#form_rerun').parent().show();
        if ($(this).val() !== 'updateStatus') {
            $('#form_status').val('');
        }

        if ($(this).val() !== 'sendNotification') {
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

        $('#form_rule').val('');

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
            $('#form_rule').parent().show();
            $('#form_targetField').parent().hide();
            $('#form_targetFieldValueContainer').hide();
        }

    }).trigger('change');
});

$(document).ready(function() {
    $('#form_rule').change(function() {
        var selectedRule = $(this).val();
        $('#form_searchField optgroup').hide();

        $('#form_searchField optgroup[label="' + selectedRule + '"]').show();
    }).trigger('change');
});