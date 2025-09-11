document.addEventListener("DOMContentLoaded", function () {
    const toggleButton = document.querySelector('.minus-workflow-logs');
    const logsContent = document.getElementById('logs-content');
    const logsTableBody = document.querySelector('.workflow-logs-collapse-body');
    const icon = toggleButton?.querySelector('i.fa');
    const logsUrl = logsTableBody?.dataset.url;

    let logsLoaded = false;
    const collapseInstance = new bootstrap.Collapse(logsContent, {
        toggle: false
    });

// Check if button exists before adding event listener
if (!minusWorkflowLogsButton) {
    console.log('workflow logs collapse button not found, skipping functionality');
} else {
    // Set logs table to collapsed by default
    const workflowLogsCollapseBody = document.querySelector('.workflow-logs-collapse-body');
    const icon = minusWorkflowLogsButton.querySelector('svg');
    
    if (workflowLogsCollapseBody) {
        workflowLogsCollapseBody.style.display = 'none';
        console.log('workflow logs table set to collapsed by default');
    }
    
    if (icon) {
        icon.classList.remove('fa-minus');
        icon.classList.add('fa-plus');
    }

    // if that button is clicked, toggle the visibility
    minusWorkflowLogsButton.addEventListener('click', function() {
        // console.log('button clicked');
        // find the tbody with the class workflow-logs-collapse-body
        const workflowLogsCollapseBody = document.querySelector('.workflow-logs-collapse-body');
        // console.log('workflowLogsCollapseBody', workflowLogsCollapseBody);

        // find the icon inside the button
        const icon = minusWorkflowLogsButton.querySelector('svg');
        // console.log('icon', icon);

        if (!workflowLogsCollapseBody) {
            console.log('workflow logs collapse body not found');
            return;
        }

        // Check current display state
        const isVisible = workflowLogsCollapseBody.style.display !== 'none';
        // console.log('isVisible', isVisible);

        if (isVisible) {
            // If visible, hide it and change to plus
            workflowLogsCollapseBody.style.display = 'none';
            if (icon) {
                icon.classList.remove('fa-minus');
                icon.classList.add('fa-plus');
            }
        } else {
            // If hidden, show it and change to minus
            workflowLogsCollapseBody.style.display = 'table-row-group';
            if (icon) {
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-minus');
            }
        }
    });
}