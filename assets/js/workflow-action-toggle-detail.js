const workflowActionId = window.location.href
    .replace('showAction', 'active')
    .split('/')
    .pop();

const apiUrl = window.location.href
    .replace('showAction', 'active')
    .replace(new RegExp(`${workflowActionId}/?$`), '');

document.addEventListener('DOMContentLoaded', function() {
    const toggleButton = document.getElementById('activeWorkflow_' + workflowActionId);
    
    if (toggleButton) {
        toggleButton.addEventListener('change', function(event) {
            fetch(`${apiUrl}${workflowActionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ active: event.target.checked }),
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (!data.success) throw new Error(data.message);
            })
            .catch(() => {
                event.target.checked = !event.target.checked;
            });
        });
    }
});