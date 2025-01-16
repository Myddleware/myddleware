// console.log('workflow-actions-collapse.js');

// first, find the button with the class minus-workflow-actions
const minusWorkflowActionsButton = document.querySelector('.minus-workflow-actions');
// console.log('minusWorkflowActionsButton', minusWorkflowActionsButton);

// if that button is clicked, toggle the visibility
minusWorkflowActionsButton.addEventListener('click', function() {
    // console.log('button clicked');
    // find the tbody with the class workflow-actions-collapse-body
    const workflowActionsCollapseBody = document.querySelector('.workflow-actions-collapse-body');
    // console.log('workflowActionsCollapseBody', workflowActionsCollapseBody);

    // find the icon inside the button
    const icon = minusWorkflowActionsButton.querySelector('svg');
    // console.log('icon', icon);

    // Check current display state
    const isVisible = workflowActionsCollapseBody.style.display !== 'none';
    // console.log('isVisible', isVisible);

    if (isVisible) {
        // If visible, hide it and change to plus
        workflowActionsCollapseBody.style.display = 'none';
        icon.classList.remove('fa-minus');
        icon.classList.add('fa-plus');
    } else {
        // If hidden, show it and change to minus
        workflowActionsCollapseBody.style.display = 'table-row-group';
        icon.classList.remove('fa-plus');
        icon.classList.add('fa-minus');
    }
});
