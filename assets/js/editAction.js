console.log('test');
        $(document).ready(function() {
            console.log('test2 we are in the function');
            $('#form_action').change(function() {
                console.log('this is the value of the action' + $(this).val());
                if ($(this).val() === 'sendNotification') {
                    console.log('we show the subject');
                    $('#form_subject').parent().show();
                } else {
                    console.log('we hide the subject');
                    $('#form_subject').parent().hide();
                }
            }).trigger('change');
        });