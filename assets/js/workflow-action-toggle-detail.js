console.log('workflow-action-toggle-detail.js loaded');

var path = window.location.href;
console.log('path', path);

// replace showAction by active
path = path.replace('showAction', 'active');
console.log('path final', path);

const workflowActionId = path.split('/').pop();
console.log('workflowActionId', workflowActionId);

// remove the id from the path
path = path.replace(workflowActionId, '');
console.log('path final but without id', path);

// remove final slash
path = path.replace(/\/$/, '');
console.log('path final but without id and final slash', path);

// Get the checkbox input using the class and data attributes
document.addEventListener('DOMContentLoaded', function() {
    // get the button using the id of the workflow action
    const toggleButton = document.getElementById('activeWorkflow_'+workflowActionId);
    
    if (toggleButton) {
        console.log('Found toggle button:', toggleButton);
        
        // No need to get the ID again since we already have it
        console.log('Workflow Action ID:', workflowActionId);
        
        // Add event listener if needed
        toggleButton.addEventListener('change', function(event) {
            console.log('Toggle state changed:', event.target.checked);

            // we send a request to the server to update the status of the workflow action
            fetch(`${path}/${workflowActionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ active: event.target.checked }),
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log(data);
                if (data.success) {
                    // Maybe show a success message
                    console.log(data.message);
                } else {
                    // Handle error
                    console.error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Maybe revert the checkbox state
                event.target.checked = !event.target.checked;
            });
        });
    }
});