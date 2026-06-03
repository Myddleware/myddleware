$(document).ready(function() {

    workflowListUrl = workflowListUrl.replace('list', '');

    workflowListUrl += 'workflow/';

    const url_toggle = workflowListUrl + 'toggle';

    // Add click handler to all workflow toggle buttons
    $('[id^="activeWorkflow_"]').each(function() {
        const button = $(this);

        button.on('click', function(e) {
            e.preventDefault();

            url_complete = url_toggle + '/' + button.attr('data-id');

            // ajax call to toggle workflow, putting the id in the url
            $.ajax({
                url: url_complete,
                method: 'POST',
                success: function(response) {
                    // Update the checkbox checked state based on the response
                    button.prop('checked', response.active);
                }
            });

        });
    });
});
