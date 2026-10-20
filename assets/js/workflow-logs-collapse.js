const minusWorkflowLogsButton = document.querySelector('.minus-workflow-logs');

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

    minusWorkflowLogsButton.addEventListener('click', function() {
        const workflowLogsCollapseBody = document.querySelector('.workflow-logs-collapse-body');

        const icon = minusWorkflowLogsButton.querySelector('svg');

        if (!workflowLogsCollapseBody) {
            console.log('workflow logs collapse body not found');
            return;
        }

        const isVisible = workflowLogsCollapseBody.style.display !== 'none';

        if (isVisible) {
            workflowLogsCollapseBody.style.display = 'none';
            if (icon) {
                icon.classList.remove('fa-minus');
                icon.classList.add('fa-plus');
            }
        } else {
            workflowLogsCollapseBody.style.display = 'table-row-group';
            if (icon) {
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-minus');
            }
        }
    });
}
