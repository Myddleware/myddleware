console.log('workflow-action-toggle-list-inside-workflow-show.js loaded');

document.addEventListener('DOMContentLoaded', function() {
    // Get base URL and construct the toggle URL similar to workflow-toggle-list.js
    let baseUrl = window.location.pathname;
    baseUrl = baseUrl.split('/workflow/')[0]; // Remove everything after /workflow/
    baseUrl = baseUrl + '/workflowAction/active'; // Match the pattern of the working URL
    
    // Get all workflow action toggle buttons by their specific ID pattern
    const toggleButtons = document.querySelectorAll('[id^="activeWorkflowAction_"]');
    
    // console.log('toggleButtons', toggleButtons);
    
    toggleButtons.forEach(toggleButton => {
        const workflowActionId = toggleButton.getAttribute('data-id');
        // console.log('workflowActionId', workflowActionId);

        // Construct the complete URL for this specific action
        const actionUrl = `${baseUrl}/${workflowActionId}`;
        // console.log('actionUrl', actionUrl);

        toggleButton.addEventListener('change', function(event) {
            // console.log('Making fetch request to:', actionUrl);
            // console.log('Request payload:', { active: event.target.checked });

            fetch(actionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ active: event.target.checked }),
            })
            .then(response => {
                // console.log('Response status:', response.status);
                // console.log('Response headers:', [...response.headers.entries()]);
                
                if (!response.ok) {
                    return response.text().then(text => {
                        // console.log('Error response body:', text);
                        throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                // console.log('Success response:', data);
                if (!data.success) {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            })
            .catch((error) => {
                // console.error('Detailed error:', {
                //     message: error.message,
                //     url: actionUrl,
                //     requestBody: { active: event.target.checked }
                // });
                event.target.checked = !event.target.checked;
            });
        });
    });
});