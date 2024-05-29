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
                } else if ($(this).val() === 'updateStatus') {
                    $('#form_subject').parent().hide();
                    $('#form_to').parent().hide();
                    $('#form_message').parent().hide();
                    // hide searchField
                    $('#form_searchField').parent().hide();
                    $('#form_searchValue').parent().hide();
                } else {
                    console.log('we hide the subject');
                    $('#form_subject').parent().hide();
                    $('#form_to').parent().hide();
                    $('#form_message').parent().hide();
                }

            }).trigger('change');
        });