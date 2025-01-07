// console.log('workflow-toggle-detail.js');

// we start by getting the id of the workflow from the url
const workflowId = window.location.href.split('/').pop();
// console.log('workflowId', workflowId);

// start with waiting for the document to be ready
$(document).ready(function() {
    // console.log('document ready');
    // then we get the id of the button, which is like this activeWorkflow_672ddeddc9e4f
    const buttonId = 'activeWorkflow_' + workflowId;
    // console.log('buttonId', buttonId);

    // console.log('pathWorkflowtoggle', pathWorkflowtoggle);

    // we get the button using the id
    const button = document.getElementById(buttonId);
    // console.log('button', button);

    // we add an event listener to the button
    button.addEventListener('click', function() {
        // console.log('button clicked');
        // we send an ajax request to the controller to toggle the active state of the workflow
        $.ajax({
            url: pathWorkflowtoggle,
            method: 'POST',
            success: function(response) {
                // console.log('response', response);
            }
        });
    });
});