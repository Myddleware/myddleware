console.log('workflow-actions-collapse.js');

// first, find the button with the class minus-workflow-actions
const minusWorkflowActionsButton = document.querySelector('.minus-workflow-actions');
console.log('minusWorkflowActionsButton', minusWorkflowActionsButton);

// if that button is clicked, console.log hello
minusWorkflowActionsButton.addEventListener('click', function() {
    console.log('hello');
    // then, findt he tbody with the class workflow-actions-collapse-body
    const workflowActionsCollapseBody = document.querySelector('.workflow-actions-collapse-body');
    console.log('workflowActionsCollapseBody', workflowActionsCollapseBody);

    // then, hide it
    workflowActionsCollapseBody.style.display = 'none';

    // then, our minus button should be changed to a plus button
    // in order to do that, we need to find the icon inside the button
    const icon = minusWorkflowActionsButton.querySelector('svg');
    console.log('icon', icon);
    // then, change the class of the icon to fa-plus
    icon.classList.remove('fa-minus');
    icon.classList.add('fa-plus');
});
