// console.log('workflow-logs-collapse.js');

// find the button with the class minus-workflow-logs
const minusWorkflowLogsButton = document.querySelector('.minus-workflow-logs');
// console.log('minusWorkflowLogsButton', minusWorkflowLogsButton);

// if that button is clicked, toggle the visibility
minusWorkflowLogsButton.addEventListener('click', function() {
    // console.log('button clicked');
    // find the tbody with the class workflow-logs-collapse-body
    const workflowLogsCollapseBody = document.querySelector('.workflow-logs-collapse-body');
    // console.log('workflowLogsCollapseBody', workflowLogsCollapseBody);

    // find the icon inside the button
    const icon = minusWorkflowLogsButton.querySelector('svg');
    // console.log('icon', icon);

    // Check current display state
    const isVisible = workflowLogsCollapseBody.style.display !== 'none';
    // console.log('isVisible', isVisible);

    if (isVisible) {
        // If visible, hide it and change to plus
        workflowLogsCollapseBody.style.display = 'none';
        icon.classList.remove('fa-minus');
        icon.classList.add('fa-plus');
    } else {
        // If hidden, show it and change to minus
        workflowLogsCollapseBody.style.display = 'table-row-group';
        icon.classList.remove('fa-plus');
        icon.classList.add('fa-minus');
    }
}); 