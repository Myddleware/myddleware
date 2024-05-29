console.log('test');
        $(document).ready(function() {
            console.log('test2 we are in the function');
            $('#form_action').change(function() {
                console.log('this is the value of the action' + $(this).val());
                if ($(this).val() === 'sendNotification') {
                    console.log('we show the subject');
                    $('#form_subject').parent().show();
                    $('#form_to').parent().show();
                    $('#form_message').parent().show();
                    // hide searchField
                    $('#form_searchField').parent().hide();
                    $('#form_searchValue').parent().hide();
                    // hide Rule field
                    $('#form_Rule').parent().hide();
                    $('#form_status').parent().hide();
                    // hide form_rerun
                    $('#form_rerun').parent().hide();
                } else if ($(this).val() === 'updateStatus') {
                    $('#form_status').parent().show();
                    $('#form_subject').parent().hide();
                    $('#form_to').parent().hide();
                    $('#form_message').parent().hide();
                    $('#form_searchField').parent().hide();
                    $('#form_searchValue').parent().hide();
                    $('#form_Rule').parent().hide();
                    $('#form_rerun').parent().hide();
                } else if ($(this).val() === 'transformDocument') {
                    $('#form_status').parent().hide();
                    $('#form_subject').parent().hide();
                    $('#form_to').parent().hide();
                    $('#form_message').parent().hide();
                    // hide searchField
                    $('#form_searchField').parent().hide();
                    $('#form_searchValue').parent().hide();
                    $('#form_Rule').parent().hide();
                    $('#form_rerun').parent().hide();
                } else if ($(this).val() === 'generateDocument') {
                    $('#form_subject').parent().hide();
                    $('#form_to').parent().hide();
                    $('#form_message').parent().hide();
                    $('#form_searchField').parent().show();
                    $('#form_searchValue').parent().show();
                    $('#form_Rule').parent().show();
                    $('#form_status').parent().hide();
                    $('#form_rerun').parent().show();
                } else {
                    $('#form_subject').parent().hide();
                    $('#form_to').parent().hide();
                    $('#form_message').parent().hide();
                    $('#form_searchField').parent().hide();
                    $('#form_searchValue').parent().hide();
                    $('#form_Rule').parent().hide();
                    $('#form_status').parent().hide();
                    $('#form_rerun').parent().hide();
                }

            }).trigger('change');
        });

        $(document).ready(function() {
    $('#form_Rule').change(function() {
        var selectedRule = $(this).val();

        // Hide all optgroups in form_searchField
        $('#form_searchField optgroup').hide();

        // Show only the optgroup that matches the selected rule
        $('#form_searchField optgroup[label="' + selectedRule + '"]').show();
    }).trigger('change');
});