const minusWorkflowActionsButton = document.querySelector('.minus-workflow-actions');

minusWorkflowActionsButton.addEventListener('click', function() {
    const workflowActionsCollapseBody = document.querySelector('.workflow-actions-collapse-body');

    const icon = minusWorkflowActionsButton.querySelector('svg');

    const isVisible = workflowActionsCollapseBody.style.display !== 'none';

    if (isVisible) {
        workflowActionsCollapseBody.style.display = 'none';
        icon.classList.remove('fa-minus');
        icon.classList.add('fa-plus');
    } else {
        workflowActionsCollapseBody.style.display = 'table-row-group';
        icon.classList.remove('fa-plus');
        icon.classList.add('fa-minus');
    }
});
