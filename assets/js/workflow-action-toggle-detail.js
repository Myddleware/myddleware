var path = window.location.href;

// replace showAction by active
path = path.replace('showAction', 'active');

const workflowActionId = path.split('/').pop();

// remove the id from the path
path = path.replace(workflowActionId, '');

// remove final slash
path = path.replace(/\/$/, '');

// Get the checkbox input using the class and data attributes
document.addEventListener('DOMContentLoaded', function() {
    // get the button using the id of the workflow action
    const toggleButton = document.getElementById('activeWorkflow_'+workflowActionId);
    
    if (toggleButton) {
        // Add event listener if needed
        toggleButton.addEventListener('change', function(event) {
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
                if (!data.success) {
                    // Handle error
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                // Maybe revert the checkbox state
                event.target.checked = !event.target.checked;
            });
        });
    }
});