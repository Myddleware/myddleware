        $(document).ready(function() {
            $('#form_action').change(function() {
                console.log('this is the value of the action' + $(this).val());
                if ($(this).val() === 'sendNotification') {
                    console.log('we show the subject');
                    $('#form_subject').parent().show();
                    $('#form_to').parent().show();
                    $('#form_message').parent().show();
                    // hide searchField
                    $('#form_searchField').parent().hide();
                    // set the value of the searchField to null
                    $('#form_searchField').val('');
                    $('#form_searchValue').parent().hide();
                    // set the value of the searchValue to null
                    $('#form_searchValue').val('');
                    // hide rule field
                    $('#form_rule').parent().hide();
                    // set the value of the rule to null
                    $('#form_rule').val('');
                    $('#form_status').parent().hide();
                    //set the value of the status to null
                    $('#form_rerun').parent().hide();
                    //set the value of the rerun to null
                } else if ($(this).val() === 'updateStatus') {
                    $('#form_status').parent().show();
                    $('#form_subject').parent().hide();
                    // set the value of the subject to null
                    $('#form_subject').val('');
                    $('#form_to').parent().hide();
                    // set the value of the to to null
                    $('#form_to').val('');
                    $('#form_message').parent().hide();
                    // set the value of the message to null
                    $('#form_message').val('');
                    $('#form_searchField').parent().hide();
                    // set the value of the searchField to null
                    $('#form_searchField').val('');
                    $('#form_searchValue').parent().hide();
                    // set the value of the searchValue to null
                    $('#form_searchValue').val('');
                    $('#form_rule').parent().hide();
                    // set the value of the rule to null
                    $('#form_rule').val('');
                    $('#form_rerun').parent().hide();
                    // set the value of the rerun to null
                    $('#form_rerun').val('');
                } else if ($(this).val() === 'transformDocument') {
                    $('#form_status').parent().hide();
                    // set the value of the status to null
                    $('#form_status').val('');
                    $('#form_subject').parent().hide();
                    // set the value of the subject to null
                    $('#form_subject').val('');
                    $('#form_to').parent().hide();
                    // set the value of the to to null
                    $('#form_to').val('');
                    $('#form_message').parent().hide();
                    // set the value of the message to null
                    $('#form_message').val('');
                    $('#form_searchField').parent().hide();
                    // set the value of the searchField to null
                    $('#form_searchField').val('');
                    $('#form_searchValue').parent().hide();
                    // set the value of the searchValue to null
                    $('#form_searchValue').val('');
                    $('#form_rule').parent().hide();
                    // set the value of the rule to null
                    $('#form_rule').val('');
                    $('#form_rerun').parent().hide();
                    // set the value of the rerun to null
                    $('#form_rerun').val('');
                } else if ($(this).val() === 'generateDocument') {
                    $('#form_subject').parent().hide();
                    // set the value of the subject to null
                    $('#form_subject').val('');
                    $('#form_to').parent().hide();
                    // set the value of the to to null
                    $('#form_to').val('');
                    $('#form_message').parent().hide();
                    // set the value of the message to null
                    $('#form_message').val('');
                    $('#form_status').parent().hide();
                    // set the value of the status to null
                    $('#form_status').val('');
                    $('#form_searchField').parent().show();
                    $('#form_searchValue').parent().show();
                    $('#form_rule').parent().show();
                    $('#form_rerun').parent().show();
                }

            }).trigger('change');
        });

        $(document).ready(function() {
    $('#form_rule').change(function() {
        var selectedRule = $(this).val();

        // Hide all optgroups in form_searchField
        $('#form_searchField optgroup').hide();

        // Show only the optgroup that matches the selected rule
        $('#form_searchField optgroup[label="' + selectedRule + '"]').show();
    }).trigger('change');
});