console.log("workflowActionSearchFields.js loaded");

$(document).ready(function () {
    // Function to filter search fields based on selected rule
    function updateSearchFields(ruleName) {
        const $searchField = $('#form_searchField');
        
        if (!ruleName) {
            // If no rule selected, hide all options and show default message
            $searchField.find('optgroup').hide();
            $searchField.find('option').hide();  // Hide the empty option too
            $searchField.html('<option value="">Please select a rule first</option>');
        } else {
            // Restore original options if they were replaced
            if ($searchField.find('optgroup').length === 0) {
                $searchField.html($searchField.data('original-options'));
            }
            // Hide all optgroups first
            $searchField.find('optgroup').hide();
            // Show only the optgroup that matches the rule name
            $searchField.find(`optgroup[label="${ruleName}"]`).show();
            // Reset selection but only if the action is changeData
            if ($('#form_action').val() === 'changeData') {
                $searchField.val('');
            }
        }
    }

    // Store original options when page loads
    const $searchField = $('#form_searchField');
    $searchField.data('original-options', $searchField.html());

    // Handle initial load
    const ruleName = $('#form_ruleId option:selected').text();
    updateSearchFields(ruleName);

    // Listen for changes to the rule select
    $('#form_ruleId').on('change', function() {
        const ruleName = $('#form_ruleId option:selected').text();
        updateSearchFields(ruleName);
    });
});