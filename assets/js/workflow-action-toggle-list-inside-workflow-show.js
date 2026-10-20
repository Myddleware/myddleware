console.log('workflow-action-toggle-list-inside-workflow-show.js loaded');

document.addEventListener('DOMContentLoaded', function() {
    // Get base URL and construct the toggle URL similar to workflow-toggle-list.js
    let baseUrl = window.location.pathname;
    baseUrl = baseUrl.split('/workflow/')[0];
    baseUrl = baseUrl + '/workflowAction/active';

    // Get all workflow action toggle buttons by their specific ID pattern
    const toggleButtons = document.querySelectorAll('[id^="activeWorkflowAction_"]');

    toggleButtons.forEach(toggleButton => {
        const workflowActionId = toggleButton.getAttribute('data-id');

        const actionUrl = `${baseUrl}/${workflowActionId}`;

        toggleButton.addEventListener('change', function(event) {
            fetch(actionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ active: event.target.checked }),
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            })
            .catch((error) => {
                event.target.checked = !event.target.checked;
            });
        });
    });
});
