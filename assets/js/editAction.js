console.log('test');
        $(document).ready(function() {
            console.log('test2 we are in the function');
            $('#workflow_action_action').change(function() {
                console.log('this is the value of the action' + $(this).val());
                if ($(this).val() === 'sendNotification') {
                    console.log('we show the subject');
                    $('#workflow_action_subject').parent().show();
                } else {
                    console.log('we hide the subject');
                    $('#workflow_action_subject').parent().hide();
                }
            }).trigger('change');
        });