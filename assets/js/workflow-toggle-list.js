console.log('workflow-toggle-list.js loaded');

$(document).ready(function() {

    console.log('workflowListUrl', workflowListUrl);

    workflowListUrl = workflowListUrl.replace('list', '');

    workflowListUrl += 'workflow/';

    const url_toggle = workflowListUrl + 'toggle';
    console.log('url_toggle', url_toggle);


    // Add click handler to all workflow toggle buttons
    $('[id^="activeWorkflow_"]').each(function() {
        const button = $(this);
        console.log('button', button);

        button.on('click', function(e) {
            e.preventDefault();
            console.log('Toggle button clicked for workflow:', button.attr('data-id'));

            url_complete = url_toggle + '/' + button.attr('data-id');
            console.log('url_complete', url_complete);

            // ajax call to toggle workflow, putting the id in the url
            $.ajax({
                url: url_complete,
                method: 'POST',
                success: function(response) {
                    console.log('Workflow toggled:', response);

                    // Update the checkbox checked state based on the response
                    button.prop('checked', response.active);
                }
            });
            
        });
    });
});