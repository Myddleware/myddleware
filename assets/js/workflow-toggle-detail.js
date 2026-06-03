const workflowId = window.location.href.split('/').pop();

$(document).ready(function() {
    const buttonId = 'activeWorkflow_' + workflowId;

    const button = document.getElementById(buttonId);

    // Check if button exists before adding event listener
    if (!button) {
        console.log('activeWorkflow button not found, skipping toggle functionality');
        return;
    }

    button.addEventListener('click', function() {
        $.ajax({
            url: pathWorkflowtoggle,
            method: 'POST',
            success: function(response) {
            }
        });
    });
});
