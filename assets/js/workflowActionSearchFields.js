console.log("workflowActionSearchFields.js loaded");

$(document).ready(function () {
    // Function to filter search fields based on selected rule
    function updateSearchFields(ruleName) {
        const $searchField = $('#form_searchField');
        
        if (!ruleName) {
            // If no rule selected, hide all options
            $searchField.find('optgroup').hide();
            $searchField.val('');
        } else {
            // Hide all optgroups first
            $searchField.find('optgroup').hide();
            // Show only the optgroup that matches the rule name
            $searchField.find(`optgroup[label="${ruleName}"]`).show();
            // Reset selection
            $searchField.val('');
        }
    }

    // Handle initial load
    const ruleName = $('#form_ruleId option:selected').text();
    updateSearchFields(ruleName);

    // Listen for changes to the rule select
    $('#form_ruleId').on('change', function() {
        const ruleName = $('#form_ruleId option:selected').text();
        updateSearchFields(ruleName);
    });
});